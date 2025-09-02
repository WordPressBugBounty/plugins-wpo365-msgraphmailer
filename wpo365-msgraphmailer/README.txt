=== WPO365 | MICROSOFT 365 GRAPH MAILER ===
Contributors: wpo365
Tags: Microsoft, SMTP, Email, wp_mail, PHPMailer
Requires at least: 5.0
Tested up to: 6.8
Stable tag: 4.0
Requires PHP: 7.4
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Send WordPress emails from a M365 / Exchange Online Mailbox using Microsoft Graph, leveraging OAuth for authentication which is more secure than SMTP

== Description ==

**WPO365 | MS GRAPH MAILER** provides you with a modern, reliable and efficient way to send WordPress transactional emails from one of your Microsoft 365 / Exchange Online / Mail enabled accounts. 

The plugin re-configures your WordPress website to send emails using the **Microsoft Graph API** instead of - for example - SMTP. Sending WordPress emails using the **Microsoft Graph API** has become the only available alternative after Microsoft has disabled basic authentication (username and password) over the SMTP protocol.

= DELIVERY =

- Send WordPress transactional emails from one of your **Microsoft 365 Exchange Online / Mail enabled accounts** using Microsoft Graph instead of - for example - SMTP.
- Choose between delegated (send mail as a user) and application-level (send mail as any user) type permissions.
- Or: Select either a Microsoft 365 account or a personal Microsoft account, like Hotmail.com or Outlook.com, to send WordPress emails.
- Or: Configure [RBAC for Exchange Online](https://learn.microsoft.com/en-us/Exchange/permissions-exo/application-rbac) and authorize as an application but with a limited scope e.g. one specific mailbox.

= SEND AS HTML =

- Send emails formatted as **HTML**.

= SAVE TO SENT ITEMS =

- Emails sent will be saved in the account's mailbox in the **Sent Items** folder, further helping to track (successful) mail delivery.

= ATTACHMENTS =

- Send files from your WordPress website as *attachments*. 

= WPO365 INSIGHTS =

- **See what matters, when it happens** Track key WPO365 events like sent emails with WPO365 Insights [more](https://docs.wpo365.com/article/210-wpo365-insights)

= CONFIGURATION / TEST EMAIL DELIVERY =

- Easy configuration with detailed step-by-step [Getting started](https://docs.wpo365.com/article/141-send-email-using-microsoft-graph-mailer) guide and video.
- Send *test email* to recipients incl. CC, BCC and attachment.

https://youtu.be/1CK7Fl8f8iA

**ADD FUNCTIONALITY WITH PREMIUM EXTENSIONS**

The following features can be unlocked with the [WPO365 | MAIL](https://www.wpo365.com/downloads/wpo365-mail/) extension.

= WPO365 INSIGHTS =

- Get **WPO35 Alerts** in your inbox when email delivery is failing [more](https://docs.wpo365.com/article/210-wpo365-insights)

= Auto-Retry =

- **Log every email** sent from your WordPress website, review errors and (automatically) try to send unsuccessfully **sent mails again**.


= LARGE ATTACHMENTS =

- Add support to send WordPress emails with **attachments larger than 3 MB** using Microsoft Graph.

= SEND AS / SEND ON BEHALF OF =

- Send email **as / on behalf of** another user or distribution list.

= SHARED MAILBOX =

- Send email from **Microsoft 365 Shared Mailbox**.

= STAGING MODE =

- **Mail Staging Mode** is useful for debugging and staging environments. WordPress emails will be logged and saved in the database instead of being sent.

= DYNAMIC SEND-FROM =

- Allow forms to **override "From"** address e.g allow Contact Form 7 to dynamically configure the account used to send the email from (requires application-level Mail.Send permissions).

= MAIL THROTTLE =

- **Throttle** the number of emails sent from your website per minute.

= WP-CONFIG FOR AAD SECRETS =

- Further improve overall security by choosing to store Azure Active Directory secrets in your WordPress WP-Config.php (on disk) and have those secrets removed from the database.

= SEND AS BCC =

- Send emails **as BCC** instead and prevent reply-to-all mail pollution.

= REPLY-TO =

- Configure a **default reply-to** mail address if this should differ from the account's mail address that is used to send WordPress transactional emails from.

== Prerequisites ==

- We have tested our plugin with Wordpress >= 5.0 and PHP >= 5.6.40.
- You need to be an Entra ID Tenant Administrator to configure both Azure Active Directory and the plugin.

== Support ==

We will go to great length trying to support you if the plugin doesn't work as expected. Go to our [Support Page](https://www.wpo365.com/how-to-get-support/) to get in touch with us. We haven't been able to test our plugin in all endless possible Wordpress configurations and versions so we are keen to hear from you and happy to learn!

== Feedback ==

We are keen to hear from you so share your feedback with us on [LinkedIn](https://www.linkedin.com/company/downloads-by-van-wieren) and help us get better!

== Open Source ==

When you're a developer and interested in the code you should have a look at our repo over at [WordPress](http://plugins.svn.wordpress.org/wpo365-msgraphmailer/).

== Installation ==

Please refer to [these **Getting started** articles](https://docs.wpo365.com/article/141-send-email-using-microsoft-graph-mailer) for detailed installation and configuration instructions.

== Frequently Asked Questions ==

== Screenshots ==
1. Configuration page
2. Mail audit log

== Changelog ==

= 4.0 =

= v38.0 =
* Feature: **See what matters, when it happens** Track key WPO365 events like sent emails with **WPO365 Insights** and get alerted when when a critical WPO365 event occurs. Consult the [updated documentation](https://docs.wpo365.com/article/210-wpo365-insights) for instructions how to configure WPO365 Alerts. [ALL PREMIUM]
* Improvement: A new **Daily WPO365 Insights** widget has been added to the WP Admin Dashboard, informing administrator about key WPO365 events like logins, sent emails and user creation and updates. Refer to the [update documentation](https://docs.wpo365.com/article/210-wpo365-insights) for further details or how to hide the widget. [ALL]
* Fix: The plugin checks whether the request parameter with the key idp_id was added by WPO365, and ignores it if not. [ALL PREMIUM]
* Fix: Addressed various technical problems to enhance plugin reliability. [ALL]

= v3.7 =
* Fix: WPO365 will avoid using "wp_print_inline_script_tag" and instead create a <script> tag itself, if the active WordPress theme does not declare support for the 'html5' and 'script' features. [ALL]

= v3.6 =
* Improvement: The built-in WordPress Mailer for Microsoft Graph now supports [RBAC for Exchange Online](https://learn.microsoft.com/en-us/Exchange/permissions-exo/application-rbac). As a result, administrators should now explicitely configure the desired authorization scenario, as explained in a [new lesson in the tutorial](https://tutorials.wpo365.com/courses/email-configure-microsoft-graph-mailer/lessons/select-authorization-scenario/).
* Improvement: All scripts now are created using either wp_print_script_tag or wp_print_inline_script_tag. As a result, developers can add your own nonce attribute using the wp_script_attributes and wp_inline_script_attributes filters e.g. to enable a Content-Security-Policy (or CSP).
* Fix: For premium plugins, WPO365 would check the license status with every admin request, which could slow down your site unnecessarily. [MAIL]

= v3.5 =
* Improvement: Confirms support for WordPress 6.8.
* Improvement: Configuring a "GCC (High)" tenant using wp-config.php is now supported for both single and multiple IdP scenarios. [ESSENTIALS, PROFESSIONAL, INTEGRATE, CUSTOMERS (LOGIN+, SYNC, INTRANET)]
* Fix: Minor code quality improvements.

= v3.4 =
* Fix: The TLD "lan" has been added to the license-checker list of exceptions.
* Fix: An issue causing - under specific circumstances - an "array-to-string conversion" warning in the Url_Helpers class has been resolved.

= v3.3 =
* Improvement: All source-code is now constantly reviewed for violations of coding standards (using phpcs) and if possible corrected to adhere to the WordPress Coding Standards.
* Fix: Functionality to save the state before the user is sent to Microsoft to authenticate and to read that state when the user returns has been refactored, to ensure redirection back to the "intended" location before WPO365 sent the user to Microsoft is working as expected.
* Fix: WPO365 will no longer show a "doing_it_wrong" warning when installed for the very first time.
* Fix: During the previous release, premium plugins were not zipped correctly, causing issues with folder-naming when installing. [ALL PREMIUM]

= v3.2 =
* Fix: (Composer) Dependencies have been updated to remove the requirement for PHP >= 8.0.0.

= v3.1 =
* Improvement: You can now choose between a Microsoft 365 account or a personal Microsoft account, like Hotmail.com or Outlook.com, to send WordPress emails. See the [updated tutorial](https://tutorials.wpo365.com/courses/email-configure-microsoft-graph-mailer/lessons/send-email-from-a-personal-microsoft-account-e-g-hotmail-com/) for details.
* Fix: Plugin no longer (falsely) reports an error when enabling the auto-retry mail-send functionality fails. Instead, it generates a warning with a more verbose description. [MAIL]
* Fix: The plugin now properly URL encodes a user's UPN when trying to submit email messages to Microsoft Graph. [LOGIN, MICROSOFT GRAPH MAILER

= v3.0 =
* Change: The PHPSECLIB v3 library has been updated to the latest version 3.0.43 and any customizations have been abandonned.
* Fix: Sending a test email from the "Mail" configuration page will no longer delete all cached (user) access tokens.

= v2.39 =
* Improvement: Developers can now filter the parameters used to build the authorization URL / token URL.

= v2.38 =
* Fix: Non-specific technical improvements.

= v2.37 =
* Fix: Non-specific technical improvements.

= v2.36 =
* Fix: Non-specific technical improvements.

= v2.35 =
* Fix: Non-specific technical improvements.

= v2.34 =
* Improvement: The HelpScout beacon on the plugin's configuration pages would be blocked from loading - for example when using Microsoft Edge - and has therefore been replaced with a new help button that opens the WPO365 Contact Form instead.
* Fix: The default value for the redirect URL now again corresponds to the site's home URL.
* Fix: Some WP Cron Jobs that rely on a custom cron schedule "wpo-every-minute" e.g. Auto-Retry for sending emails and User Sync Monitor to ensure user synchronization keeps running, should no longer be removed when the custom schedule is not found. [PREMIUM]
* Fix: Mail Log Viewer will show no results if a filter e.g. Errors returns no results. [PREMIUM]

= v2.33 =
* Support for new [WPO365 feature bundles](https://www.wpo365.com/news/its-not-just-a-change-its-a-leap-forward).

= v2.32 =
* Improvement: The Mail Audit Log Viewer has been updated to show nr. of attempts and time of last attempt for a better general understanding of the send-status of the email in question. [PREMIUM]
* Improvement: The Debug Log entries now display timestamps in the WordPress timezone (see WP Admin > Settings > General > Timezone).
* Improvement: The Mail Audit Log entries now display timestamps in the WordPress timezone (see WP Admin > Settings > General > Timezone). [PREMIUM]
* Improvement: The WPO365 Insights entries now display timestamps in the WordPress timezone (see WP Admin > Settings > General > Timezone).
* Improvement: A small icon on the plugin's Mail configuration page will show the status of the "Resending failed emails automatically" feature. [PREMIUM]
* Improvement: The Microsoft Graph Mailer for WordPress will not be instantiated if no authorization information can be found.
* Improvement: The Mail Authorization Status Popup will now appear only after 4 seconds and will no longer show if authorization is under way.
* Fix: WordPress no longer shows that an update for a premium addon or bundle is available when the latest version is already installed. [PREMIUM]
* Fix: The recently added Mail Audit Log Retention Policy (to clean up entries older than 90 days) no longer fails if an older version of WPO365 | MICROSOFT GRAPH MAILER or WPO365 | LOGIN would be installed in combination with the latest version of the WPO365 | MAIL addon. [PREMIUM]
* Fix: The WPO365 configuration pages will now show the correct values for Entra ID / AAD related options retrieved from wp-config.php (instead of from the database). [PREMIUM]
* Fix: The Mail Audit Log will now create a new table at the correct "level" in case WordPress Multisite would be activated and WPO365's default support mode for WPMU (= Shared) is configured. [PREMIUM]
* Fix: The Redirect URI for the WPO365 Microsoft Graph Mailer no longer indicates an error for the Redirect URI migrated from "Mail Integration for Office 365 / Outlook" plugin.

= v2.31 =
* Breaking Change (Microsoft Graph Mailer): WPO365 [retains **mail log** entries](https://docs.wpo365.com/article/217-mail-log-retention) that are less than approximately 90 days old and deletes entries that exceed the configured number of days. [PREMIUM]
* Improvement: In an attempt to better understand errors that involve *cURL*, administrators can now enable [verbose logging for cURL](https://docs.wpo365.com/article/214-enable-curl-verbose). [ALL]
* Fix: The premium WPO365 | MAIL option to resend failed emails automatically can now be started when the premium addon is used in combination with WPO365 | MICROSOFT GRAPH MAILER. [MICROSOFT GRAPH MAILER]

= v2.30 =
* Fix: By fixing a caching issue, WPO365 should - after this update - no longer show a notification that "There is a new version of [...] available [...]" for WPO365 premium addons and bundles, after those were updated to the lastest version. [ALL PREMIUM ADDONS / BUNDLES]

= v2.29 =
* Fix: "Strict Mode" for the Redirect URI can now also be enabled for the WPO365 | MICROSOFT GRAPH MAILER plugin (so it will only try process an Oauth response / payload detected at the exact URL which must be a path below the site's home address e.g. /oidc-auth/).
* Fix: The plugin will not try and process an Oauth response / payload if both features SSO and MICROSOFT GRAPH MAILER are disabled or if SSO is disabled but MICROSOFT GRAPH MAILER is enabled and but the administrator did not start an attempt to authorize an account to send emails from.
* Fix: WPO365 Health Messages are now correctly displayed on the corresponding panel for the MICROSOFT GRAPH MAILER plugin.

= v2.28 =
* Breaking Change: To support devOps workflows and site replication scenarios, WPO365 now automatically detects named constants in your website's wp-config.php file that either configure an [single Identity Provider (IdP)](https://tutorials.wpo365.com/courses/wp-config-php-single-identity-provider-idp/) or any of the [WPO365 settings](https://tutorials.wpo365.com/courses/wp-config-php-configuration-w-o-idps/) that are not directly related to an IdP. As a result, the option **Use WP-Config.php for AAD secrets** has been renamed to [Obfuscate AAD options](https://docs.wpo365.com/article/137-use-wp-config-for-aad-secrets). [ANY PREMIUM ADDON / BUNDLE]
* Feature (preview): Now administrators can enable **WPO365 Insights** and aggregate various events into straightforward management dashboards. These dashboards are designed to offer valuable insights, such as tracking the count of users who have authenticated successfully or unsuccessfully, monitoring emails that have been sent successfully or unsuccessfully, and overseeing the synchronization status of users, whether through SCIM, WPO365 User synchronization, or during their initial sign-in. See the new [online guide](https://www.wpo365.com/feature/wpo365-insights/) for further details. [MICROSOFT GRAPH MAILER, ANY PREMIUM ADDON / BUNDLE]
* Improvement: **WPO365 Health Messages** will no longer be displayed on a default WordPress notification banner, but instead a dismissable panel will slide over the configuration app. [MICROSOFT GRAPH MAILER]
* Improvement: When deleting a WPO365 configuration, several caches e.g. for access tokens and certificates, are cleaned as well. [MICROSOFT GRAPH MAILER]

= v2.27 =
* Fix: The plugin attempted to process any POST request with parameter "error", mistakenly assuming that it would be an authentication-error sent by Microsoft. [LOGIN, MICROSOFT GRAPH MAILER]

= v2.26 =
* Fix: Updated parts of the PHP Security Library v3 to improve compatibility with older PHP versions.

= v2.25 =
* Fix: Fixed "Fatal error: Cannot use ::class with dynamic class name" for 2 files in PHP Security Library v3.

= v2.24 =
* Improvement: The default response mode - for new installations - when requesting an (OIDC) authorization code has been updated to "query". This will help preserve the code, especially if the administrator has configured a 3rd party multi-factor authentication provider such as Duo. Existing installations are not affected, however, and the response mode remains "form_post". See the [updated documentation](https://docs.wpo365.com/article/208-select-oidc-response-mode) for details.
* Improvement: Admins configuring the Microsoft Graph Mailer portion of WPO365 can now select an option to skip all checks. Checking this option instructs the Microsoft Graph Mailer to skip the check whether the default "from" email address is registered for the corresponding account and whether the "from" email address specified by a plugin has a different email-domain compared to the default "from" email address used to submit email message to Microsoft Graph.
* Fix: The PHP Secure Communications library has been updated and the plugin now uses version 3.0 (to verify an ID token's signature). [LOGIN, MICROSOFT GRAPH MAILER]

= v2.23 =
* Breaking Change: Sending WordPress email using Microsoft Graph now always will use the Azure AD configuration from the plugin's Mail configuration page. [LOGIN]
* Tested up to 6.4. [ALL]

= v2.21 =
* Feature: WPO365 can now send a daily notification to the administation email address if one of the application / client secrets is about to expire in the next 30 days. Consult [this article](https://www.wpo365.com/article/client-secret-expiration-notification/) for details. [LOGIN, MICROSOFT GRAPH MAILER]
* Fix: The plugin's updater will now display a notification when a newer version of a premium addon is available.

= v2.20 =
* Feature: **(Auto-) Retry sending failed emails** using Microsoft Graph. See the [online documentation](https://docs.wpo365.com/article/183-resending-failed-emails-automatically) for details. [MAIL]
* Feature: **Throttle nr. of emails send per minute** using Microsoft Graph. See the [online documentation](https://docs.wpo365.com/article/182-throttle-the-number-of-emails-sent-per-minute) for details. [MAIL]
* Improvement: The WPO365 | MAIL premium addon now also unlocks the option to use WP-Config.php to override (some) config options. Now administrators can - for example on their staging environment - enable mail-staging mode, simply by adding a global constant to the WP-Config.php file. See the [updated documentation](https://docs.wpo365.com/article/171-mail-staging-mode). [MAIL]
* Fix: Tested with PHP 8.2. [ALL]

= v2.19 =
* Fix: The plugin update checker did not always return the expected result. [LOGIN, MS GRAPH MAILER]

= v2.18 =
* Fix: Various modifications to **Microsoft Graph Mailer** configurator should make it easier and more intuitive to configure it.
* Fix: In an attempt to prevent the error "cURL error 28: Operation timed out after 15001 milliseconds with 0 bytes received" when integrating with Microsoft Graph, the use of the Expect: header has been disabled by default.
* Fix: [PREMIUM] The Log Viewer - to view and optionally resend emails sent using the Microsoft Graph Mailer - now calculates the last inserted logged item ID using MAX() instead of looking up the AUTO INCREMENT value, which may not be up-to-date.
* Fix: [PREMIUM] If the license key can not be verified, it will not be deleted. The corresponding error is logged as an error.

= v2.17 =
* Fix: The built-in Microsoft Graph Mailer for WordPress will now exclude any custom headers that do not start with x- or X-, to prevent Microsoft Graph from not sending the message and reporting the following error instead: "The internet message header name [...] should start with 'x-' or 'X-'.". [LOGIN, MICROSOFT GRAPH MAILER]

= v2.16 =
* Improvement: The WPO365 | MICROSOFT GRAPH MAILER plugin can now also log remotely to ApplicationInsights, allowing administrators to configure **Azure's Monitoring / Alerts** feature e.g. to send an SMS whenever an exception is logged. 
* Fix: The Microsoft Graph Mailer for WordPress no longer "unauthorizes" itself, after it fails to retrieve an access token. Instead, WPO365 Health Messages are created and administrators should regularly check for errors.
* Fix: Refactored the flow when sending emails from a different account than the one submitting the request to send an email to Microsoft Graph (= the default "From" account) to improve consistency, even when the alternative sending-from account is a Shared Mailbox, a Distribution List or Group or normal User Mailbox. [PREMIUM]

= v2.15 =
* Feature: Administrators can now enable **Mail Staging Mode**. If enabled, the WPO365 plugin will not send emails using Microsoft Graph anymore but instead will write them to the central *Mail Log*. This makes especially sense for a staging environment. [PREMIUM]
* Improvement: The WPO365 plugin will now handle forms (e.g. Contact Form 7) that propose to send emails from a different account than the "default from" mail account, after it handles any other option (e.g Shared Mailbox or Send as / Send on behalf of). The proposed "alternative from" therefore always prevail. It can also be any type of mailbox e.g. User Mailbox, Shared Mailbox or Distributionlist. But it's up to the adminstrator to ensure that the "default from" mail account is a either a member (e.g. of the Shared Mailbox) or has sufficient permissions to send emails as / on behalf of an alternative account (e.g. the Distributionlist). [PREMIUM]
* Fix: The initial OpenID Connect authorization request will now always include https://graph.microsoft.com/User.Read.
* Fix: A public property $ErrorInfo has been added to the PHPMailer object to support integration with Gravity Forms.
* Fix: The plugin now better understands - in the context of WordPress Multisite installations - whether the configuration must be retrieved / stored at site or at network level.

= v2.14 =
* Fix: ID Token validation now also validates audiences that are defined using an Application ID URI instead of the Application ID (e.g. this is the case for Microsoft Teams). [LOGIN, MICROSOFT GRAPH MAILER]
* Fix: The plugin does no longer rely on the HTTP_HOST key of the global $_SERVER variable, which - if not initialized - may cause a critical error on the website. [LOGIN, MICROSOFT GRAPH MAILER]
* Fix: The link to launch the Mail Log Viewer would return "false" for FireFox users. [MAIL]


= v2.13 =
* Improvement: The Microsoft Graph Mailer for WordPress will notify the administrator in the form of a WPO365 Health Message when another plugin with mail-sending capabilities is detected.
* Fix: An alternative system for nonces has been introduced to work around the fact that some browsers would not send the WordPress auth cookie along with HTTP 302 redirect requests, causing WordPress nonce verification to fail unexpectedly, in which case the plugin would then log the warning "Could not successfully validate oidc nonce with value xyz".

= v2.12 =
* Fix: The recently added *ID token verification* did not take the mail-authorization flow into account.
* Improvement: Administrators can now re-configure the WPO365 | LOGIN plugin to skip the *ID token verification* altogether, on the plugin's *Miscellaneous* configuration page (but this is not recommended for production environments).


= v2.11 =
* Fix: Various issues with the builtin license and update checker for premium extensions and bundles.

= v2.10 =
* Fix: License check for WPO365 | MAIL extension would show "unknown error occurred" for valid licenses.
* Fix: Update check for WPO365 | MAIL extension now better aligned with the recently updated license management service.


= v2.9 =
* Fix: The *Allow forms to override "From" address* was only enabled for application-level *Mail.Send* permissions.
* Fix: Overriding the "From" address was sometimes ignored.
* Fix: Sending from a Shared Mailbox was sometimes ignored.

= v2.8 =

= v2.7 =
* Fix: The mail authorization may falsely indicate that the plugin is not authorized to send emails using Microsoft Graph due to how the plugin compared permissions.

= v2.6 =
* Feature: Websites that are using the [Mail Integration for Office 365/Outlook](https://wordpress.org/plugins/mail-integration-365/] are now urged to switch to [WPO365 | MICROSOFT GRAPH MAILER](https://wordpress.org/plugins/wpo365-msgraphmailer/) or configure the builtin Microsoft Graph mail function of the WPO365 | LOGIN plugin. Consult the [online migration guide](https://docs.wpo365.com/article/165-migrate-from-mail-integration-for-office-365-outlook-to-wpo365-microsoft-graph-mailer) for further details. [ALL]

= v2.5 =
* Feature: The (premium version of the) Microsoft Graph Mailer can now send attachments larger than 3 MB.
* Feature: The (premium version of the) Microsoft Graph Mailer can now send emails from a Shared Mailbox.
* Improvement: Some parts of the source code have been updated to improve compatibility with PHP 8.1.

= v2.4 =
* Fix: Mail authorization would fail with the error "Could not retrieve a tenant and application specific JSON Web Key Set and thus the JWT token cannot be verified successfully".

= v2.3 =
* Fix: The delegated mail authorization feature would - under circumstances - fail to get the mail specific tenant ID and as a result an attempt to refresh the access token may fail.

= v2.2 =
* Fix: The Redirect URL field for the mail authorization is no longer greyed out and can be changed by administrators. [LOGIN]

= 2.1 =
* Fix: Added missing files.

= 2.0 =
* Change: Sending WordPress emails using Microsoft Graph can now also be configured with **delegated** permissions. Administrators are urged to review the [documentation](https://docs.wpo365.com/article/141-send-email-using-microsoft-graph-mailer) and to update their configuration. [LOGIN, MICROSOFT GRAPH MAILER]
* Feature: Azure Active Directory secrets can now be stored in the website's **WP-Config.php** and removed from the database. [MAIL]

= 1.8 =
* Fix: If the plugin is configured to send WordPress emails using Microsoft Graph then it will now always replace the "from" email address if WordPress tries to sent emails from "wordpress@[sitename]". WordPress will propose this email address is no email is set by the plugin sending the email (e.g. Contact Form 7). This email may pass checks as a valid email address but in reality this email address most likely does not exist. The option to fix the "localhost" issue has been removed since this fix improves the behavior for all hosts (incl. localhost). [ALL]

= 1.7 =
* Improvement: When specified in - for example - an email form the "From" address will be used to send the email from (instead of the configured "From" address and if the address specified in the form appears to be valid). This behavior is a premium feature and not enabled by default.

= 1.6 =
* Change: Sending mail as HTML is no longer a premium feature.
* Change: Saving a sent mail in the Sent Items folder is no longer a premium feature.
* Improvement: The Graph Mailer components have been refactored for improved logging / auditing.
* Fix: Sending a test email with attachment is now supported by all versions.
* Fix: The plugin will not try and send attachments larger than 3 Mb (the prevent the mail being refused by the Microsoft Graph API).

= 1.5 =
* Fix: Several issues related to PHP 8.x have been fixed.

= 1.4 =
* Updated README.txt

= 1.3 =
* Improvement: The plugin will now honor a reply-to email address defined "externally" e.g. when using Contact Form 7.
* Fix: Activating the plugin would case a critical error due to a class-loading error.

= 1.2 =
* Fix: Compatibility update.

= 1.1 =
* Fix: Some minor code issues were fixed after review.

= 1.0 =
* Initial version.
