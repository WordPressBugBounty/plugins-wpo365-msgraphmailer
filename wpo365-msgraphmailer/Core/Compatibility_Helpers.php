<?php

namespace Wpo\Core;

use Wpo\Core\WordPress_Helpers;
use Wpo\Core\Wpmu_Helpers;
use Wpo\Services\Authentication_Service;
use Wpo\Services\Log_Service;
use Wpo\Services\Options_Service;
use Wpo\Services\Wp_Config_Service;

// Prevent public access to this script
defined( 'ABSPATH' ) || die();

if ( ! class_exists( '\Wpo\Core\Compatibility_Helpers' ) ) {

	class Compatibility_Helpers {

		/**
		 * Writes the compatibility warning as an error to the log but only if it's not currently already in the list
		 * of WPO365 Health Messages.
		 *
		 * @since   20.0
		 *
		 * @param   string $warning
		 *
		 * @return  void
		 */
		public static function compat_warning( $warning ) {
			$wpo_errors = Wpmu_Helpers::mu_get_transient( 'wpo365_errors' );

			if ( empty( $wpo_errors ) || ! is_array( $wpo_errors ) ) {
				Log_Service::write_log( 'ERROR', $warning );
			} else {
				// Log_Service stores health messages sanitized (e.g. "->" becomes "-&gt;"), so compare like with like.
				$health_warning = wp_kses( $warning, WordPress_Helpers::get_allowed_message_html() );

				$same_errors = array_filter(
					$wpo_errors,
					function ( $wpo_error ) use ( $health_warning ) {
						return isset( $wpo_error['body'] ) && WordPress_Helpers::stripos( $wpo_error['body'], $health_warning ) !== false;
					}
				);

				if ( count( $same_errors ) === 0 ) {
					Log_Service::write_log( 'ERROR', $warning );
				}
			}
		}

		/**
		 * Reduces the key of the extra_user_fields array by removing the name part for custom
		 * WordPress usermeta that was introduced with version 20.
		 *
		 * @since   20.0
		 *
		 * @param   array $extra_user_fields  The array of extra user fields that will be updated.
		 *
		 * @return  array
		 */
		public static function update_user_field_key( $extra_user_fields ) {
			if ( ! class_exists( '\Wpo\Services\User_Details_Service' ) || method_exists( '\Wpo\Services\User_Details_Service', 'parse_user_field_key' ) ) {
				return $extra_user_fields;
			}

			// Iterate over the configured graph fields and identify any supported expandable properties
			$extra_user_fields = array_map(
				function ( $kv_pair ) {
					$marker_pos = WordPress_Helpers::stripos( $kv_pair['key'], ';#' );

					if ( $marker_pos > 0 ) {
						$kv_pair['key'] = substr( $kv_pair['key'], 0, $marker_pos );
					}

					return $kv_pair;
				},
				$extra_user_fields
			);

			$compat_warning = sprintf(
				'%s -> The administrator configured <em>Entra ID user attributes to WordPress user meta mappings</em> on the plugin\'s <strong>User sync</strong> page. These mappings have been recently upgraded to allow administrators to specify their own name for the usermeta key. This new feature, however, breaks existing functionality. To remain compatible you should update your premium WPO365 extension and optionally update the existing mappings.',
				__METHOD__
			);

			self::compat_warning( $compat_warning );

			return $extra_user_fields;
		}

		/**
		 * Starting with version 31.0 mappings to save user details as WP user meta must be prefixed with their corresponding source or else WPO365 cannot decide whether
		 * or not the user meta should be removed. For example, "department" may be a SAML claim for a user set to "Communications". If that user property is emptied, the
		 * claim will be omitted from the SAML response (instead of being sent as a null value).
		 *
		 * @param mixed $claim
		 * @return bool True if $claim has the expected prefix otherwise false
		 */
		public static function check_user_claim_prefix( $claim ) {

			if (
				WordPress_Helpers::stripos( $claim, 'graph::' ) !== 0
				&& WordPress_Helpers::stripos( $claim, 'scim::' ) !== 0
				&& WordPress_Helpers::stripos( $claim, 'saml::' ) !== 0
				&& WordPress_Helpers::stripos( $claim, 'oidc::' ) !== 0
			) {
				$compat_warning = sprintf(
					'Starting with version 31.0 WPO365 requires that you add a prefix to each ID token claim (prefix: "oidc::"), SAML 2.0 claim (prefix: "saml::"), SCIM attribute (prefix: scim::) or Microsoft Graph property (prefix: "graph::") for which you have entered a mapping on the plugin\'s "User Sync" configuration page. See https://docs.wpo365.com/article/98-synchronize-microsoft-365-azure-ad-profile-fields for further details.',
					__METHOD__
				);

				self::compat_warning( $compat_warning );
				return false;
			}

			return true;
		}

		/**
		 * Warns the administrator - whilst an administrator is viewing a WP Admin page - that support for the
		 * option to allow apps to request any Microsoft Graph endpoint has been discontinued and that its value
		 * is being ignored.
		 *
		 * @since   45.0
		 *
		 * @return  void
		 */
		public static function check_deprecated_graph_options() {

			if ( ! Options_Service::get_global_boolean_var( 'graph_allow_all_endpoints' ) ) {
				return;
			}

			$compat_warning = sprintf(
				'%s -> Support for the option <em>Allow apps to request any Microsoft Graph endpoint</em> on the plugin\'s <a href="#integration">Integration</a> configuration page has been discontinued, because of security concerns. The option is now ignored and each destination must instead be added to the list of <em>Allowed endpoints</em>. Please review that list and then uncheck the option (once unchecked it cannot be re-enabled).',
				__METHOD__
			);

			self::compat_warning( $compat_warning );
		}

		/**
		 * Warns the administrator - whilst an administrator is viewing a WP Admin page - that "Require login for the Media
		 * Folder" is switched on for a multisite network, but the active premium plugin predates multisite support (it
		 * has no authorize_request()), so it may not protect the files of every site.
		 *
		 * @since   45.0
		 * @since   45.1    Supported on multisite networks with a premium plugin 45.1 or later.
		 *
		 * @return  void
		 */
		public static function check_unsupported_media_folder_protection() {

			if (
				! is_multisite()
				|| ! Options_Service::get_global_boolean_var( 'block_direct_media_access' )
				|| ! class_exists( '\Wpo\Services\Secure_Download_Service' )
				|| method_exists( '\Wpo\Services\Secure_Download_Service', 'authorize_request' )
			) {
				return;
			}

			self::compat_warning(
				sprintf(
					'%s -> <em>Require login for the Media Folder</em> is switched on, but your WPO365 premium plugin does not yet support WordPress multisite networks and may not protect your files. Please update the premium plugin to version 45.1 or later, or turn the option off on the plugin\'s <a href="#rolesAccess">Roles + Access</a> configuration page.',
					__METHOD__
				)
			);
		}

		/**
		 * Warns the administrator - whilst an administrator is viewing a WP Admin page - that "Require login for the Media
		 * Folder" is on but its rules are missing from .htaccess, outdated, or (on a multisite network) not where they
		 * take effect. That happens when the option is switched on without the wizard's toggle, e.g. by importing a
		 * configuration, because only the toggle writes the rules.
		 *
		 * In dedicated multisite mode it also removes this site's "public" cache entry, left from a time the option was
		 * off, which the fast path would otherwise keep serving to anyone. A missing entry fails closed: the next request
		 * loads WordPress, which applies the option.
		 *
		 * @since   45.1
		 *
		 * @return  void
		 */
		public static function check_media_folder_rules_not_applied() {

			// Not during the wizard's toggle request: it runs with the option already saved, before it writes the rules.
			if (
				wp_doing_ajax()
				|| ! Options_Service::get_global_boolean_var( 'block_direct_media_access' )
				|| ! method_exists( '\Wpo\Services\Secure_Download_Service', 'htaccess_rules_applied' )
			) {
				return;
			}

			if ( Options_Service::mu_use_subsite_options() ) {

				// In dedicated mode the network admin's option governs no site.
				if ( Wpmu_Helpers::mu_is_network_admin() ) {
					return;
				}

				Secure_Download_Handler::delete_public_entry( get_current_blog_id() );
			}

			if ( \Wpo\Services\Secure_Download_Service::htaccess_rules_applied() ) {
				return;
			}

			self::compat_warning(
				sprintf(
					'%s -> <em>Require login for the Media Folder</em> is switched on, but its rules are missing from .htaccess or in the wrong place, so Media Library items may not be protected. Please switch the option off and on again on the plugin\'s <a href="#rolesAccess">Roles + Access</a> configuration page to rewrite the rules. That also clears this message if your web server (e.g. nginx) uses its own rules instead of .htaccess. See <a href="https://docs.wpo365.com/article/229-require-login-for-the-wordpress-media-folder" target="_blank">the documentation</a> for details.',
					__METHOD__
				)
			);
		}

		/**
		 * Warns the administrator - whilst an administrator is viewing a WP Admin page - that "Require login for the Media
		 * Folder" is on but the directory that caches its signing key cannot be written. The feature still protects files;
		 * it just takes the slow (full WordPress load) path on every request until the directory is writable.
		 *
		 * @since   45.1
		 *
		 * @return  void
		 */
		public static function check_media_folder_cache_writable() {

			// The cache directory is network-wide, so on a multisite network the answer is the same for every site.
			if (
				! Options_Service::get_global_boolean_var( 'block_direct_media_access' )
				|| ! method_exists( '\Wpo\Services\Secure_Download_Service', 'cache_is_writable' )
				|| \Wpo\Services\Secure_Download_Service::cache_is_writable()
			) {
				return;
			}

			self::compat_warning(
				sprintf(
					'%s -> <em>Require login for the Media Folder</em> is active but WPO365 cannot write to the <em>wp-content</em> folder to cache its signing key, so every protected item in the Media Library will load WordPress in full. Your files stay protected, but to restore performance please make the <em>wp-content</em> folder writable. See <a href="https://docs.wpo365.com/article/229-require-login-for-the-wordpress-media-folder" target="_blank">https://docs.wpo365.com/article/229-require-login-for-the-wordpress-media-folder</a> for details.',
					__METHOD__
				)
			);
		}

		/**
		 * Warns the administrator - whilst an administrator is viewing a WP Admin page - that requests are still being
		 * routed through the Media Folder download proxy even though "Require login for the Media Folder" is off. Each
		 * such request loads WordPress in full - a real performance hit - and the fix is to remove the rewrite rules
		 * that WPO365 does not manage (a hand-written nginx or LiteSpeed rule, or a leftover block in .htaccess).
		 *
		 * @since   45.1
		 *
		 * @return  void
		 */
		public static function check_media_proxy_reached_while_off() {

			// In dedicated multisite mode one rewrite rule covers every site, so sites that have the option off are reached
			// by design (Secure_Download_Service::authorize_request() doesn't set the flag there).
			if (
				Options_Service::mu_use_subsite_options()
				|| Options_Service::get_global_boolean_var( 'block_direct_media_access' )
				|| ! get_site_transient( 'wpo365_media_proxy_off' )
			) {
				return;
			}

			self::compat_warning(
				sprintf(
					'%s -> Requests for Media Library items are still being routed through WPO365\'s download proxy, even though <em>Require login for the Media Folder</em> is switched off. This loads WordPress for every such request and will slow down your website. Please remove WPO365\'s rewrite rules from your web server configuration (for example a hand-written nginx or LiteSpeed rule, or a leftover block in .htaccess), or switch the option back on if the protection was intended. See <a href="https://docs.wpo365.com/article/229-require-login-for-the-wordpress-media-folder" target="_blank">the documentation</a> for details.',
					__METHOD__
				)
			);
		}

		/**
		 * Warns the administrator - whilst an administrator is viewing a WP Admin page - that "Require login for the Media
		 * Folder" is on but the premium plugin that provides it predates the 45.1 security improvements: its download
		 * service has no MLDA_VER constant (a counter, not a plugin version), or an older one. A warning only, so that
		 * Media Library items keep working until the premium plugin is updated.
		 *
		 * @since   45.1
		 *
		 * @return  void
		 */
		public static function check_outdated_media_folder_service() {

			if (
				! Options_Service::get_global_boolean_var( 'block_direct_media_access' )
				|| ! class_exists( '\Wpo\Services\Secure_Download_Service' )
			) {
				return;
			}

			$mlda_ver_constant = \Wpo\Services\Secure_Download_Service::class . '::MLDA_VER';
			$mlda_ver          = defined( $mlda_ver_constant ) ? (string) constant( $mlda_ver_constant ) : '0';

			if ( version_compare( $mlda_ver, '1', '>=' ) ) {
				return;
			}

			self::compat_warning(
				sprintf(
					'%s -> Please update your WPO365 premium plugin(s) to the latest version, so that the security improvements for <em>Require login for the Media Folder</em> are fully applied. See <a href="https://docs.wpo365.com/article/229-require-login-for-the-wordpress-media-folder" target="_blank">the documentation</a> for details.',
					__METHOD__
				)
			);
		}

		/**
		 * Warns the administrator - whilst an administrator is viewing a WP Admin page - that "Require login for the Media
		 * Folder" is on but no active premium plugin provides it. Core/Secure_Download_Handler.php then serves Media
		 * Library items as public, because only an active plugin's download service may decide who gets them.
		 *
		 * @since   45.1
		 *
		 * @return  void
		 */
		public static function check_media_folder_without_premium() {

			// update.php is also the request that reactivates a premium plugin after updating it, which is still inactive here.
			if (
				( isset( $GLOBALS['pagenow'] ) && $GLOBALS['pagenow'] === 'update.php' )
				|| ! Options_Service::get_global_boolean_var( 'block_direct_media_access' )
				|| class_exists( '\Wpo\Services\Secure_Download_Service' )
			) {
				return;
			}

			self::compat_warning(
				sprintf(
					'%s -> <em>Require login for the Media Folder</em> is switched on, but no active WPO365 premium plugin provides it. Therefore, items in the Media Library are currently not protected. Please activate the premium plugin again. If you no longer want to use the feature, switch it off on the plugin\'s <a href="#rolesAccess">Roles + Access</a> configuration page while the premium plugin is active, and only then deactivate it. See <a href="https://docs.wpo365.com/article/229-require-login-for-the-wordpress-media-folder" target="_blank">the documentation</a> for details.',
					__METHOD__
				)
			);
		}

		/**
		 * Whether the WPO365 block in .htaccess still carries the earlier Referer exception - an Apache-only rule that
		 * weakened the access check for Media Library items. Read directly from the file (scoped to WPO365's own marker
		 * block), so it does not depend on the admin-only extract_from_markers() being loaded. Used once, from the
		 * 'media_folder_referer_warned' upgrade action.
		 *
		 * @since   45.1
		 *
		 * @return  bool
		 */
		private static function htaccess_block_has_referer() {
			$htaccess = trailingslashit( ABSPATH ) . '.htaccess';

			if ( ! is_readable( $htaccess ) ) { // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_is_readable
				return false;
			}

			$contents = file_get_contents( $htaccess ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents

			if ( $contents === false || ! preg_match( '/# BEGIN WPO365(.*?)# END WPO365/s', $contents, $matches ) ) {
				return false;
			}

			// Only react to a Referer condition inside WPO365's own marker block, not an unrelated rule elsewhere.
			return strpos( $matches[1], 'HTTP_REFERER' ) !== false;
		}

		/**
		 * Warns the administrator - whilst an administrator is viewing a WP Admin page - that "Skip NONCE verification" makes
		 * the website vulnerable to replay attacks. Only when a posted ID token can reach the nonce check: not when SSO is off
		 * (has_route() then only processes mail authorization, which never receives a posted ID token) and not when every
		 * identity provider uses the authorization code flow (has_route() then ignores a posted ID token, and an ID token from
		 * the token endpoint is returned for a single-use code).
		 *
		 * @since   45.1
		 *
		 * @return  void
		 */
		public static function check_skip_nonce_verification() {

			if ( ! Options_Service::get_global_boolean_var( 'skip_nonce_verification' ) || Options_Service::get_global_boolean_var( 'no_sso' ) ) {
				return;
			}

			$idps = Wp_Config_Service::get_multiple_idps();

			if ( is_array( $idps ) ) {
				// The sender of a sign-in response chooses the IdP (idp_id in the state), so each one counts. Without its own
				// oidc_flow an IdP uses the global option, like Options_Service::get_aad_option() does.
				$global_flow = isset( $GLOBALS['WPO_CONFIG']['options']['oidc_flow'] ) && is_string( $GLOBALS['WPO_CONFIG']['options']['oidc_flow'] )
					? $GLOBALS['WPO_CONFIG']['options']['oidc_flow']
					: '';
				$flows       = array();

				foreach ( $idps as $idp ) {
					$flows[] = is_array( $idp ) && isset( $idp['oidc_flow'] ) && is_string( $idp['oidc_flow'] ) ? $idp['oidc_flow'] : $global_flow;
				}
			} else {
				$flows = array( Options_Service::get_aad_option( 'oidc_flow' ) );
			}

			$code_flow_only = ! empty( $flows ) && count(
				array_filter(
					$flows,
					function ( $flow ) {
						return strcasecmp( $flow, 'code' ) !== 0;
					}
				)
			) === 0;

			if ( $code_flow_only ) {
				return;
			}

			self::compat_warning(
				sprintf(
					'%s -> <strong>Skip NONCE verification</strong> is turned on, on the plugin\'s <a href="#miscellaneous">Miscellaneous</a> configuration page. WPO365 then also accepts a sign-in response whose nonce it cannot verify, so the current configuration must be considered <strong>vulnerable to replay attacks</strong>. Please turn this option off. If users have trouble signing in once it is off, please contact WPO365 support (support@wpo365.com).',
					__METHOD__
				)
			);
		}

		/**
		 * Advises the administrator - when a user signs in inside Microsoft Teams (silent authentication) - if a failed
		 * sign-in would end on wp-login.php, which WordPress does not allow to be shown inside Teams. Only called then,
		 * because only then is it certain that WPO365 is used for silent authentication inside Teams.
		 *
		 * @since   45.1
		 *
		 * @return  void
		 */
		public static function check_teams_loggedout_page() {

			if ( ! Authentication_Service::goodbye_lands_on_default_login_page() ) {
				return;
			}

			// User_Create_Update_Service ships with most premium extensions, which also include the custom routes.
			if ( class_exists( '\Wpo\Services\User_Create_Update_Service' ) ) {
				$advice = 'Please configure WPO365\'s <a href="https://tutorials.wpo365.com/courses/hide-wp-login-php-and-configure-a-custom-login-experience/lessons/configure-a-custom-login-route-and-hide-wp-login-php/" target="_blank">custom login route and logged-out page</a> on the plugin\'s <a href="#loginLogout">Login / Logout</a> configuration page.';
			} else {
				$advice = 'A better error and logged-out experience is possible with the premium <a href="https://www.wpo365.com/downloads/wpo365-essentials/" target="_blank">WPO365 | ESSENTIALS</a> plugin, by configuring its custom login route and logged-out page.';
			}

			self::compat_warning(
				sprintf(
					'%s -> This website is embedded in a Microsoft Teams app or tab. When silent authentication fails, WPO365 sends the user to the WordPress login page, which WordPress does not allow to be shown inside Microsoft Teams, so the user sees an empty page instead of the error. %s',
					__METHOD__,
					$advice
				)
			);
		}

		public static function upgrade_actions( $plugin_name = 'wpo365_login' ) {

			/**
			 * @since 45.0  upgrade_actions is read from the database only (see Options_Service::is_db_only_option).
			 * Runs first, so that the actions below don't run again when WPO_OVERRIDES_<blog id> records them as done.
			 */

			self::upgrade_actions_from_db();

			/**
			 * @since 45.0  License keys are read from the database only (see Options_Service::is_db_only_option).
			 */

			self::license_keys_from_db();

			if ( $plugin_name === 'wpo365_login' ) {
				$upgrade_actions = Options_Service::get_global_list_var( 'upgrade_actions' );

				if ( ! in_array( 'client_side_redirect', $upgrade_actions, true ) ) {

					if ( Options_Service::is_wpo365_configured() ) {
						Options_Service::add_update_option( 'use_teams', true );

						self::compat_warning(
							sprintf(
								'Starting with WPO365 | LOGIN version 33.0 you can configure WPO365 to redirect users to Microsoft faster (using a server-side redirect). This is generally recommended to avoid issues with server-side / external caching services. Uncheck the option "Use client-side redirect" on the plugin\'s "Login / Logout" configuration page, unless your WordPress site is integrated in Microsoft Teams, or you wish to briefly display a "loading" icon when the user is redirected. See https://docs.wpo365.com/article/223-use-client-side-redirect for details.',
								__METHOD__
							)
						);
					}

					$upgrade_actions[] = 'client_side_redirect';
					Options_Service::add_update_option( 'upgrade_actions', $upgrade_actions );
				}

				/**
				 * @since 43.x  "Use custom Logged Out Page" (use_custom_logged_out_page) is a
				 * new option that decides whether error_page_url is used at all. Existing
				 * installs that already configured an error_page_url before this option
				 * existed should keep using it, so this one-time check turns the new option on
				 * for them; new installs (and sites where error_page_url was never configured)
				 * get the new default of false, meaning the built-in /wpo/loggedout page is
				 * used instead. Deliberately a one-time migration rather than inferred on every
				 * wizard load, so that explicitly unchecking the option later - while a stale
				 * error_page_url value happens to still be present - is never silently
				 * overridden back to true.
				 */

				if ( ! in_array( 'use_custom_logged_out_page_default', $upgrade_actions, true ) ) {

					if ( ! empty( Options_Service::get_global_string_var( 'error_page_url' ) ) ) {
						Options_Service::add_update_option( 'use_custom_logged_out_page', true );
					}

					$upgrade_actions[] = 'use_custom_logged_out_page_default';
					Options_Service::add_update_option( 'upgrade_actions', $upgrade_actions );
				}

				/**
				 * @since 45.0  Support for "Allow apps to request any Microsoft Graph endpoint"
				 * (graph_allow_all_endpoints) has been discontinued. A website that relied on that
				 * option never needed to allow-list a single destination, so without this one-time
				 * migration every request from a (WPO365) app would suddenly be refused. Seed the
				 * list with the destinations that can be derived from the current configuration.
				 */

				if ( ! in_array( 'graph_allow_all_endpoints_retired', $upgrade_actions, true ) ) {

					if ( Options_Service::get_global_boolean_var( 'graph_allow_all_endpoints' ) ) {
						self::seed_graph_allowed_endpoints();
					}

					$upgrade_actions[] = 'graph_allow_all_endpoints_retired';
					Options_Service::add_update_option( 'upgrade_actions', $upgrade_actions );
				}

				/**
				 * @since 45.0  From version 25.0 until 40.3, exporting the SAML 2.0 SP metadata saved the default
				 * Single Logout Service URL as ".../wp-login.php&action=loggedout". Repair it once in the database. Only
				 * the database value is read, so a value from wp-config.php is never copied in - that one is repaired
				 * whenever it is read (see Saml2_Service::repair_query_string).
				 */

				if ( ! in_array( 'saml_sp_sls_url_repaired', $upgrade_actions, true ) ) {
					$db_options = Options_Service::mu_use_subsite_options() && ! Wpmu_Helpers::mu_is_network_admin()
						? get_option( 'wpo365_options', array() )
						: get_site_option( 'wpo365_options', array() );
					$db_sls_url = is_array( $db_options ) && isset( $db_options['saml_sp_sls_url'] ) && is_string( $db_options['saml_sp_sls_url'] )
						? $db_options['saml_sp_sls_url']
						: '';
					$repaired   = \Wpo\Services\Saml2_Service::repair_query_string( $db_sls_url );

					if ( $repaired !== $db_sls_url ) {
						Options_Service::add_update_option( 'saml_sp_sls_url', $repaired );
					}

					$upgrade_actions[] = 'saml_sp_sls_url_repaired';
					Options_Service::add_update_option( 'upgrade_actions', $upgrade_actions );
				}

				/**
				 * @since 45.0  The option "Use Firebase\JWT instead of phpseclib" (use_id_token_parser_v2) and the
				 * deprecated ID token parser behind it have been removed. Tell an administrator who had it enabled - once -
				 * that ID tokens are now always validated with phpseclib. The stored value is simply ignored.
				 */

				if ( ! in_array( 'id_token_parser_v2_retired', $upgrade_actions, true ) ) {

					if ( Options_Service::get_global_boolean_var( 'use_id_token_parser_v2' ) ) {
						self::compat_warning(
							sprintf(
								'%s -> The option <em>Use Firebase\JWT instead of phpseclib</em> on the plugin\'s <a href="#miscellaneous">Miscellaneous</a> configuration page has been removed because of security concerns. WPO365 now always validates ID tokens with phpseclib. If users can no longer sign in with Microsoft, please run the plugin\'s self-test and contact support@wpo365.com.',
								__METHOD__
							)
						);
					}

					$upgrade_actions[] = 'id_token_parser_v2_retired';
					Options_Service::add_update_option( 'upgrade_actions', $upgrade_actions );
				}

				/**
				 * @since 45.0  The option "Skip ID token verification" (skip_id_token_verification) has been
				 * removed. Tell an administrator who had it enabled - once - that ID tokens are now always checked.
				 */

				if ( ! in_array( 'skip_id_token_verification_retired', $upgrade_actions, true ) ) {

					if ( Options_Service::get_global_boolean_var( 'skip_id_token_verification' ) ) {
						self::compat_warning(
							sprintf(
								'%s -> The option <em>Skip ID token verification</em> on the plugin\'s <a href="#singleSignOn">Single Sign-on</a> configuration page has been removed because of security concerns. WPO365 now always checks that an ID token was issued for your application by the issuer that Microsoft publishes for your tenant. If users can no longer sign in with Microsoft, please run the plugin\'s self-test and contact support@wpo365.com.',
								__METHOD__
							)
						);
					}

					$upgrade_actions[] = 'skip_id_token_verification_retired';
					Options_Service::add_update_option( 'upgrade_actions', $upgrade_actions );
				}

				/**
				 * @since 45.1  A website that enabled "Require login for the Media Folder" before this version has
				 * an older .htaccess rule that weakens the access check on Apache. The rule is not rewritten automatically
				 * (that would be the only upgrade action to touch a file, and auto-editing .htaccess is risky); instead the
				 * administrator is warned once to disable and re-enable the option, which rewrites the rule. Only when the
				 * WPO365 .htaccess block still carries the old rule, so nginx/LiteSpeed installs are not warned.
				 */

				if ( ! in_array( 'media_folder_referer_warned', $upgrade_actions, true ) ) {

					if (
						! is_multisite()
						&& Options_Service::get_global_boolean_var( 'block_direct_media_access' )
						&& self::htaccess_block_has_referer()
					) {
						self::compat_warning(
							sprintf(
								'%s -> Because of a security improvement, please disable and then re-enable the <em>Require login for the Media Folder</em> option on the plugin\'s <a href="#rolesAccess">Roles + Access</a> configuration page. On Apache websites that use .htaccess for access control, this updates the rules that enforce the login requirement for Media Library items. See <a href="https://docs.wpo365.com/article/229-require-login-for-the-wordpress-media-folder" target="_blank">the documentation</a> for details.',
								__METHOD__
							)
						);
					}

					$upgrade_actions[] = 'media_folder_referer_warned';
					Options_Service::add_update_option( 'upgrade_actions', $upgrade_actions );
				}
			}
		}

		/**
		 * Adds the destinations that can be derived from the current configuration to the list of allowed
		 * endpoints. Existing entries are never modified or removed and application-level permissions are
		 * only ever seeded for an app that has been configured for app-only access.
		 *
		 * @since   45.0
		 *
		 * @return  void
		 */
		private static function seed_graph_allowed_endpoints() {
			$allowed_endpoints = Options_Service::get_global_list_var( 'graph_allowed_endpoints' );
			$tld               = Options_Service::get_aad_option( 'tld' );
			$tld               = ! empty( $tld ) ? $tld : '.com';

			$seeds = array(
				array(
					'key'     => sprintf( 'https://graph.microsoft%s/', $tld ),
					'boolVal' => false,
				),
			);

			$app_instances     = class_exists( '\Wpo\Graph\Apps_Db' ) ? \Wpo\Graph\Apps_Db::get_app_instances() : array();
			$power_bi          = false;
			$power_bi_app_only = false;

			if ( ! is_wp_error( $app_instances ) && is_array( $app_instances ) ) {

				foreach ( $app_instances as $app_instance ) {
					// Continue with an associative array to avoid tripping the WordPress sniff for camelCase members.
					$instance = json_decode( wp_json_encode( $app_instance ), true );

					if ( empty( $instance ) || ! is_array( $instance ) ) {
						continue;
					}

					$app_only = isset( $instance['appliedRequirements']['userRequirements']['appOnlyAccess'] )
						&& $instance['appliedRequirements']['userRequirements']['appOnlyAccess'] === true;

					// The SharePoint host an app connects to is website-specific and therefore not part of an app's static requirements.
					if ( ! empty( $instance['config']['legacy']['hostname'] ) ) {
						$host    = preg_replace( '#^https?://#i', '', $instance['config']['legacy']['hostname'] );
						$seeds[] = array(
							'key'     => sprintf( 'https://%s', untrailingslashit( $host ) ),
							'boolVal' => false,
						);
					}

					if ( ! empty( $instance['appType'] ) && strcasecmp( $instance['appType'], 'pbi' ) === 0 ) {
						$power_bi          = true;
						$power_bi_app_only = $power_bi_app_only || $app_only;
					}
				}
			}

			// Seeded once for all Power BI apps, so that a second app cannot lower the permissions a first app needs.
			if ( $power_bi ) {
				$seeds[] = array(
					'key'     => 'https://api.powerbi.com/',
					'boolVal' => $power_bi_app_only,
				);
			}

			foreach ( $seeds as $seed ) {
				$exists = false;

				foreach ( $allowed_endpoints as $allowed_endpoint ) {

					if ( ! empty( $allowed_endpoint['key'] ) && strcasecmp( $allowed_endpoint['key'], $seed['key'] ) === 0 ) {
						$exists = true;
						break;
					}
				}

				if ( ! $exists ) {
					$allowed_endpoints[] = $seed;
				}
			}

			Options_Service::add_update_option( 'graph_allowed_endpoints', $allowed_endpoints );

			$compat_warning = sprintf(
				'%s -> Because support for the option <em>Allow apps to request any Microsoft Graph endpoint</em> has been discontinued, WPO365 has added the destinations it could derive from your configuration to the list of <em>Allowed endpoints</em> on the plugin\'s <a href="#integration">Integration</a> configuration page. Please review that list: a destination that WPO365 could not derive - for example the SharePoint Home URL used by the <em>Content by Search</em> app - must be added manually, and each entry that does not need application-level permissions should remain unchecked.',
				__METHOD__
			);

			self::compat_warning( $compat_warning );
		}

		/**
		 * Adds, once, the upgrade actions that WPO_OVERRIDES_<blog id> records as done to the list in the
		 * database, together with upgrade_actions_from_db itself.
		 *
		 * @since   45.0
		 *
		 * @return  void
		 */
		private static function upgrade_actions_from_db() {
			$upgrade_actions = Options_Service::get_global_list_var( 'upgrade_actions' );

			if ( in_array( 'upgrade_actions_from_db', $upgrade_actions, true ) ) {
				return;
			}

			$wpo_overrides = Wp_Config_Service::get_options_overrides();

			if ( is_array( $wpo_overrides ) && isset( $wpo_overrides['upgrade_actions'] ) && is_array( $wpo_overrides['upgrade_actions'] ) ) {

				foreach ( $wpo_overrides['upgrade_actions'] as $upgrade_action ) {

					if ( is_string( $upgrade_action ) && ! in_array( $upgrade_action, $upgrade_actions, true ) ) {
						$upgrade_actions[] = $upgrade_action;
					}
				}
			}

			$upgrade_actions[] = 'upgrade_actions_from_db';
			Options_Service::add_update_option( 'upgrade_actions', $upgrade_actions );
		}

		/**
		 * Copies, once, the license keys that WPO_OVERRIDES_<blog id> holds to the database. When the two
		 * differ, the wp-config.php key wins. License keys are never written to the log.
		 *
		 * @since   45.0
		 *
		 * @return  void
		 */
		private static function license_keys_from_db() {
			$upgrade_actions = Options_Service::get_global_list_var( 'upgrade_actions' );

			if ( in_array( 'license_keys_from_db', $upgrade_actions, true ) ) {
				return;
			}

			// License keys are stored network-wide, so a dedicated subsite leaves the migration to a network admin page load.
			if ( Options_Service::mu_use_subsite_options() && ! Wpmu_Helpers::mu_is_network_admin() ) {
				return;
			}

			$wpo_overrides     = Wp_Config_Service::get_options_overrides();
			$license_key_names = array();

			if ( is_array( $wpo_overrides ) ) {

				foreach ( $wpo_overrides as $key => $value ) {

					if ( preg_match( '/^license_\d+$/', (string) $key ) !== 1 || ! is_string( $value ) ) {
						continue;
					}

					// Only the key counts, not the URL that a successful license check appends after the "|".
					$config_key = WordPress_Helpers::trim( explode( '|', $value )[0] );

					if ( $config_key === '' ) {
						continue;
					}

					$license_key_names[] = $key;
					$db_key              = WordPress_Helpers::trim( explode( '|', Options_Service::get_global_string_var( $key ) )[0] );

					if ( $db_key === $config_key ) {
						continue;
					}

					if ( $db_key !== '' ) {
						Log_Service::write_log(
							'WARN',
							sprintf(
								'%s -> The license key saved as %s in the database has been replaced by the license key found in WPO_OVERRIDES_%s',
								__METHOD__,
								$key,
								Wpmu_Helpers::get_options_blog_id()
							)
						);
					}

					Options_Service::add_update_option( $key, $value );
				}
			}

			if ( ! empty( $license_key_names ) ) {
				self::compat_warning(
					sprintf(
						'%1$s -> WPO365 now reads license keys from the database only and ignores the license keys in your site\'s wp-config.php file. The license keys found in WPO_OVERRIDES_%2$s (%3$s) are now saved in the database. Please remove these entries from WPO_OVERRIDES_%2$s and from now on manage your license keys on the <strong>WPO365 > Licenses</strong> page.',
						__METHOD__,
						Wpmu_Helpers::get_options_blog_id(),
						implode( ', ', $license_key_names )
					)
				);
			}

			$upgrade_actions[] = 'license_keys_from_db';
			Options_Service::add_update_option( 'upgrade_actions', $upgrade_actions );
		}
	}
}
