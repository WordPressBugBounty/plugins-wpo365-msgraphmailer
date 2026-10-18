<?php

namespace Wpo\Services;

use Wpo\Core\Compatibility_Helpers;

// Prevent public access to this script
defined( 'ABSPATH' ) || die();

if ( ! class_exists( '\Wpo\Services\Nonce_Service' ) ) {

	class Nonce_Service {

		/**
		 * Version of the nonce table's schema. Version 2 added the used_at column.
		 *
		 * @since 45.1
		 */
		const DB_VERSION = '2';

		/**
		 * Set to true once the nonce table has been confirmed to exist in the current request.
		 *
		 * @var bool
		 */
		private static $table_ready = false;

		/**
		 * Creates a nonce to ensure the request for an Entra ID token originates from the current server.
		 * Uses cryptographically secure randomness and stores the value directly in the database,
		 * bypassing any persistent object cache.
		 *
		 * @since   21.6
		 * @since   45.1    The nonce is self-describing: 8 hex characters for the creation time, 24 for 96 random bits and 32
		 *                  for an HMAC of both. Its validity still depends only on the database row; the creation time and the
		 *                  HMAC are only used to log why a nonce check failed.
		 *
		 * @return string
		 */
		public static function create_nonce() {
			global $wpdb;

			self::maybe_create_table();

			$data       = sprintf( '%08x', time() ) . bin2hex( random_bytes( 12 ) );
			$nonce      = $data . self::get_signature( $data );
			$table_name = self::table_name();

			$inserted = $wpdb->insert( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
				$table_name,
				array(
					'nonce'      => $nonce,
					'created_at' => current_time( 'mysql', true ),
				),
				array( '%s', '%s' )
			);

			if ( $inserted === false ) {
				Log_Service::write_log(
					'ERROR',
					sprintf(
						'%s -> Could not save a new nonce [fp=%s] and therefore signing in will fail with "Your login might be tampered with" [Error: %s]',
						__METHOD__,
						self::get_fingerprint( $nonce ),
						$wpdb->last_error
					)
				);
			} else {
				$request_path = isset( $GLOBALS['WPO_CONFIG']['url_info']['request_uri'] )
					? wp_parse_url( $GLOBALS['WPO_CONFIG']['url_info']['request_uri'], PHP_URL_PATH )
					: '';

				Log_Service::write_log( 'DEBUG', sprintf( '%s -> Created a nonce [fp=%s path=%s]', __METHOD__, self::get_fingerprint( $nonce ), $request_path ) );
			}

			// Delete any nonces older than 24 hours.
			if ( wp_rand( 1, 100 ) === 1 ) {
				$cutoff = gmdate( 'Y-m-d H:i:s', time() - DAY_IN_SECONDS );
				$wpdb->query( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
					$wpdb->prepare(
						"DELETE FROM `{$table_name}` WHERE created_at < %s", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
						$cutoff
					)
				);
			}

			return $nonce;
		}

		/**
		 * Verifies the nonce that Microsoft returns together with the requested token.
		 * Reads directly from the database to bypass any persistent object cache.
		 *
		 * Calls Authentication_Service::goodbye() and exits on failure, unless "Skip NONCE-verification" is on:
		 *   - Nonce not found, already used, missing or unreadable → Error_Service::TAMPERED_WITH
		 *   - Nonce older than 5 min                               → Error_Service::NONCE_EXPIRED
		 *
		 * @since   45.1    A nonce is marked as used (used_at) instead of deleted, also when it has expired, so that a later
		 *                  attempt with the same nonce is recognized as such. Each failed check writes a WARN line that says
		 *                  why. The 24-hour purge in create_nonce() deletes marked nonces too.
		 *
		 * @param mixed $nonce
		 * @return void
		 */
		public static function verify_nonce( $nonce ) {
			global $wpdb;

			self::maybe_create_table();

			$table_name = self::table_name();
			$skip       = Options_Service::get_global_boolean_var( 'skip_nonce_verification' );

			if ( empty( $nonce ) || ! is_string( $nonce ) ) {
				Log_Service::write_log( 'WARN', sprintf( '%s -> %s', __METHOD__, self::get_failure_message( 'no_claim', '', null ) ) );
				self::refuse( $skip );
				return;
			}

			$row = $wpdb->get_row( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
				$wpdb->prepare(
					"SELECT * FROM `{$table_name}` WHERE nonce = %s", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
					$nonce
				)
			);

			if ( $row === null ) {

				// $wpdb clears last_error at the start of every query, so it belongs to the SELECT above.
				$db_error = $wpdb->last_error;
				$created  = empty( $db_error ) ? self::get_created_from_nonce( $nonce ) : false;
				$reason   = ! empty( $db_error ) ? 'db_error' : ( $created === false ? 'foreign' : 'missing' );
				$created  = $created === false ? null : $created;

				Log_Service::write_log( 'WARN', sprintf( '%s -> %s', __METHOD__, self::get_failure_message( $reason, $nonce, $created ) ) );
				self::refuse( $skip );
				return;
			}

			$created = strtotime( $row->created_at . ' UTC' );

			// Without the used_at column (the table could not be updated) nonces are deleted on use, as before 45.1.
			$can_mark = property_exists( $row, 'used_at' );

			if ( $can_mark && ! empty( $row->used_at ) ) {
				Log_Service::write_log( 'WARN', sprintf( '%s -> %s', __METHOD__, self::get_failure_message( 'used', $nonce, $created ) ) );
				self::refuse( $skip );
				return;
			}

			self::mark_as_used( $nonce, $can_mark );

			if ( time() - $created > 300 ) {
				Log_Service::write_log( 'WARN', sprintf( '%s -> %s', __METHOD__, self::get_failure_message( 'expired', $nonce, $created ) ) );

				if ( ! $skip ) {
					Authentication_Service::goodbye( Error_Service::NONCE_EXPIRED );
				}

				return;
			}

			Log_Service::write_log( 'DEBUG', sprintf( '%s -> Verified a nonce [fp=%s age=%ds]', __METHOD__, self::get_fingerprint( $nonce ), time() - $created ) );
		}

		/**
		 * Returns the nonce table name. Note that we are using base_prefix instead of prefix and
		 * thus expect one table per installation and not per subsite in a WPMU network.
		 *
		 * @return string
		 */
		private static function table_name() {
			global $wpdb;
			return $wpdb->base_prefix . 'wpo365_nonces';
		}

		/**
		 * Creates the nonce table if it does not already exist, or adds the used_at column to an existing one. Runs at most
		 * once per PHP process, guarded by the static $table_ready flag.
		 *
		 * The schema version is a network option, written only once used_at exists. If the table cannot be updated (e.g. no
		 * ALTER privilege) an hour passes before the next attempt, and nonces are deleted on use as before.
		 *
		 * Column types are lowercase, because dbDelta() in WordPress 5.x and 6.0 compares them case-sensitively with the
		 * (lowercase) types that DESCRIBE returns.
		 *
		 * @return void
		 */
		private static function maybe_create_table() {

			if ( self::$table_ready ) {
				return;
			}

			global $wpdb;
			$table_name = self::table_name();

			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
			$table_exists = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table_name ) ) === $table_name;

			if (
				$table_exists && (
					get_site_option( 'wpo365_nonces_db_version' ) === self::DB_VERSION ||
					get_site_transient( 'wpo365_nonces_db_retry' ) !== false
				)
			) {
				self::$table_ready = true;
				return;
			}

			$charset_collate = $wpdb->get_charset_collate();
			$sql             = "CREATE TABLE {$table_name} (
				nonce varchar(64) NOT NULL,
				created_at datetime NOT NULL,
				used_at datetime NULL DEFAULT NULL,
				PRIMARY KEY  (nonce)
			) {$charset_collate};";

			require_once ABSPATH . 'wp-admin/includes/upgrade.php';

			// A failing ALTER must not print database errors into the response (e.g. a redirect to Microsoft).
			$suppress = $wpdb->suppress_errors();
			dbDelta( $sql );
			$columns = $wpdb->get_col( "DESCRIBE `{$table_name}`", 0 ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$wpdb->suppress_errors( $suppress );

			if ( is_array( $columns ) && in_array( 'used_at', $columns, true ) ) {
				update_site_option( 'wpo365_nonces_db_version', self::DB_VERSION );
			} else {
				Compatibility_Helpers::compat_warning(
					sprintf(
						'%s -> Could not create or update the table %s. Signing in still works, but WPO365 cannot log why a nonce check fails. A new attempt will be made in an hour [Error: %s]',
						__METHOD__,
						$table_name,
						$wpdb->last_error
					)
				);
				set_site_transient( 'wpo365_nonces_db_retry', 1, HOUR_IN_SECONDS );
			}

			self::$table_ready = true;
		}

		/**
		 * Marks a nonce as used - or deletes it, when the table has no used_at column.
		 *
		 * @param string $nonce
		 * @param bool   $can_mark Whether the table has the used_at column.
		 *
		 * @return void
		 */
		private static function mark_as_used( $nonce, $can_mark ) {
			global $wpdb;

			if ( ! $can_mark ) {
				$wpdb->delete( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
					self::table_name(),
					array( 'nonce' => $nonce ),
					array( '%s' )
				);
				return;
			}

			$updated = $wpdb->update( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
				self::table_name(),
				array( 'used_at' => current_time( 'mysql', true ) ),
				array(
					'nonce'   => $nonce,
					'used_at' => null,
				),
				array( '%s' ),
				array( '%s', '%s' )
			);

			if ( $updated === false ) {
				Log_Service::write_log(
					'ERROR',
					sprintf(
						'%s -> Could not mark a nonce as used [fp=%s Error: %s]',
						__METHOD__,
						self::get_fingerprint( $nonce ),
						$wpdb->last_error
					)
				);
			}
		}

		/**
		 * Handles a nonce that cannot be accepted, as before 45.1: a health message and the "tampered with" error page, or
		 * nothing when "Skip NONCE-verification" is on.
		 *
		 * @param bool $skip
		 *
		 * @return void
		 */
		private static function refuse( $skip ) {

			if ( $skip ) {
				return;
			}

			Compatibility_Helpers::compat_warning(
				sprintf(
					'%s -> Nonce not found or already used. Please make sure that you exclude the endpoint %s from being cached. Open the WPO365 <a href="#debug">Debug</a> page to view a detailed warning why nonce-verification failed.',
					__METHOD__,
					home_url( '/wpo/sso/start' )
				)
			);
			Authentication_Service::goodbye( Error_Service::TAMPERED_WITH );
		}

		/**
		 * The log message for a failed nonce check: the reason, the nonce's fingerprint, when it was created and how old it
		 * is, plus a hint about the likely cause.
		 *
		 * @param string   $reason  used, expired, missing, foreign, no_claim or db_error.
		 * @param string   $nonce
		 * @param int|null $created Unix time the nonce was created, or null if unknown.
		 *
		 * @return string
		 */
		private static function get_failure_message( $reason, $nonce, $created ) {
			global $wpdb;

			$age = $created === null ? null : time() - $created;

			switch ( $reason ) {
				case 'used':
					$hint = 'This sign-in link has been used before. If it happens once, the user probably went back to the Microsoft page, or used the browser history, a restored tab or a bookmark. If it happens to many users with the same fingerprint, the redirect to Microsoft (e.g. /wpo/sso/start) is probably being cached. A HAR file of a failed sign-in can confirm this.';
					break;
				case 'expired':
					$hint = $age < 1800
						? 'The sign-in took longer than 5 minutes, probably because the user took long to sign in with Microsoft.'
						: 'The sign-in link is much older than 5 minutes, so it was probably stored somewhere, e.g. in a bookmark or a cached redirect to Microsoft (e.g. /wpo/sso/start). A HAR file of a failed sign-in can confirm this.';
					break;
				case 'missing':
					$hint = $age < DAY_IN_SECONDS
						? 'This website created the nonce, but it was not found. It was probably not saved (see an earlier error about saving a new nonce), or this request cannot see it, e.g. because of database replication.'
						: 'The sign-in link is older than 24 hours and its nonce has been deleted, so it was probably stored somewhere, e.g. in a bookmark or a cached redirect to Microsoft (e.g. /wpo/sso/start). A HAR file of a failed sign-in can confirm this.';
					break;
				case 'foreign':
					$hint = 'This website did not create the nonce. The sign-in probably started on another website that uses the same app registration (e.g. a staging site), the website\'s salts have changed, or the nonce dates from before the last update.';
					break;
				case 'no_claim':
					$hint = 'The ID token has no nonce, so the request for it was probably sent without one, e.g. changed by the wpo365/oidc/params filter.';
					break;
				default:
					$hint = sprintf( 'The nonce could not be looked up [Error: %s]', substr( $wpdb->last_error, 0, 200 ) );
			}

			if ( $age !== null && $age < 0 ) {
				$hint .= ' Its negative age means that the clocks of the web servers differ.';
			}

			return sprintf(
				'Nonce check failed [reason=%s fp=%s created=%s age=%s] %s',
				$reason,
				$nonce === '' ? '-' : self::get_fingerprint( $nonce ),
				$created === null ? '-' : get_date_from_gmt( gmdate( 'Y-m-d H:i:s', $created ), 'm-d-Y H:i:s' ),
				$age === null ? '-' : $age . 's',
				$hint
			);
		}

		/**
		 * The first 8 hex characters of the nonce's SHA-256 hash, so that log lines about the same nonce can be matched
		 * without logging the nonce itself.
		 *
		 * @param string $nonce
		 *
		 * @return string
		 */
		private static function get_fingerprint( $nonce ) {
			return substr( hash( 'sha256', (string) $nonce ), 0, 8 );
		}

		/**
		 * The HMAC part of a self-describing nonce (see create_nonce()).
		 *
		 * @param string $data The creation time and the random part (32 hex characters).
		 *
		 * @return string 32 hex characters.
		 */
		private static function get_signature( $data ) {
			return substr( hash_hmac( 'sha256', 'wpo365_nonce|' . $data, wp_salt( 'nonce' ) ), 0, 32 );
		}

		/**
		 * The time a nonce was created, if this website created it.
		 *
		 * @param string $nonce
		 *
		 * @return int|false Unix time, or false if the nonce was not created by this website (or before 45.1).
		 */
		private static function get_created_from_nonce( $nonce ) {

			if ( preg_match( '/^[0-9a-f]{64}$/', $nonce ) !== 1 ) {
				return false;
			}

			if ( ! hash_equals( self::get_signature( substr( $nonce, 0, 32 ) ), substr( $nonce, 32 ) ) ) {
				return false;
			}

			return (int) hexdec( substr( $nonce, 0, 8 ) );
		}
	}
}
