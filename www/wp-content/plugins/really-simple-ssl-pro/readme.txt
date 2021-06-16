=== Really Simple SSL pro ===
Contributors: RogierLankhorst
Tags: mixed content, insecure content, secure website, website security, ssl, https, tls, security, secure socket layers, hsts
Requires at least: 4.9
License: GPL2
Tested up to: 5.8
Requires PHP: 5.6
Stable tag: 4.1.9

Premium support and extra features for Really Simple SSL

== Description ==
Really Simple SSL Pro offers premium support, HTTP Strict Transport Security, an extensive scan, and more detailed feedback on the configuration page.

= Installation =
* Install the Really Simple SSL plugin, which is needed with this one.
* Go to “plugins” in your Wordpress Dashboard, and click “add new”
* Click “upload”, and select the zip file you downloaded after the purchase.
* Activate
* Navigate to “settings”, “SSL”.
* Click “license”
* Enter your license key, and activate.

For more information: go to the [website](https://www.really-simple-ssl.com/), or
[contact](https://www.really-simple-ssl.com/contact/) me if you have any questions or suggestions.

== Frequently Asked Questions ==

== Change log ==
= 4.1.9 =
* Improvement: 'none' or '*' attribute deprecated for Permissions Policy
* Improvement: Content Security Policy for unsafe inline
* Improvement: Interest Cohort added to permissions policy

= 4.1.8 =
* Fix: PHP headers were set with quotes, causing issues on some servers.
* Fix: When switching from "enforce" to "paused", CSP headers were not removed
* Improvement: generalized wrap_header function for more robust header generation
* Improvement: disable remove .htaccess headers on activation of PHP headers.

= 4.1.7 =
* Moved secure cookies to free

= 4.1.6 =
* Improved WP Engine compatibility
* Improved Content Security Policy, added option to revoke result from overview
* Lowered Content Security Policy request limit

= 4.1.5 =
* Fix: set defaults firing even when dependency check not validated
* Fix: Constant RSSSL_DOING_CSP not set when running the CSP API

= 4.1.4 =
* Improvement: Added report-paused mode to Content Security Policy generator. Reporting will now automatically pause after 200 requests to prevent high server load
* Improvement: Further restricted access to Content Security Policy generator REST API calls
* Fix: Default options won't be reconfigured after plugin deactivation/activation

= 4.1.3 =
* Made PHP header option available on all configurations
* Improvement: wrap example code in comments

= 4.1.2 =
* Fix: File name in upgrade function

= 4.1.1 =
* Fix: condition for .htaccess writing in combination with do not edit .htaccess option

= 4.1.0 =
* New: PHP headers support for all security headers, autodetecting necessity for NGINX.
* New: Changed feature policy into permissions policy.

= 4.0.7 =
* Fix: always activate multisite license for main site url

= 4.0.6 =
* Improvement: moved renamed free folder notice to free plugin
* Improvement: PHP 8 compatibility

= 4.0.5 =
* Improvement: 404 redirect check expiration set to one year when test is successful

= 4.0.4 =
* Fix: hsts header on multisite not saving

= 4.0.3 =
* Fix: network settings link
* Fix: multisite hsts preload option not saving
* Improvement: License notifications. Differ between remaining activations when current site is activated, and when not.

= 4.0.2 =
* Improvement: exclude .htaccess from edits outside settings page

= 4.0.1 =
* Fix: decode license for support ticket
* Fix: made some untranslatable strings translatable

= 4.0.0 =
* New user interface
* Fix: transient stored with 'WEEK_IN_SECONDS' as string instead of constant
* Improvement: notices dashboard, with dismissable notices
* Improvement: improved naming of settings, and instructions
* Improvement: new translations
* Improvement: new fix options in scan
* Fix: scan mixed content detection false positive
* Improvement: removal of .htaccess lines and wpconfig lines on deactivation
* Fix: CSP generation bug for some types
* Fix: is_writable check on secure cookies write
* Improvement: do not insert default security headers if SSL not enabled yet

= 2.1.21 =
* Fix: return after htaccess is not writable

= 2.1.20 =
* Improvement: add env=https based on apache version, if available.
* Improvement: fix both www and non www URL's in elementor

= 2.1.19 =
* Added upgrade script to move HSTS header to security headers block
* Prefixed Elementor URL replace option
* Updated NGINX/PHP security header notice
* Added function_exists() checks
* Add style-src-elem and script-src-elem to Content Security Policy
* Improved TLS version check to prevent cURL errors on some setups

= 2.1.18 =
* Fix: check if uses_elementor function exists

= 2.1.17 =
* Fix: check 404 homepage redirect issue only on settings page

= 2.1.16.1 =
* Fixed a bug where having allow_url_fopen = 0 in the PHP configuration could cause a PHP warning on the Really Simple SSL dashboard

= 2.1.16 =
* Added a TLS version check
* Added a check for a redirect to homepage which can cause mixed content errors on image

= 2.1.15=
* Automatically fix mixed content in Elementor by calling the Elementor replace_urls() function

= 2.1.14 =
* Added Permissions Policy security header

= 2.1.13 =
* Removed env=HTTPS from HSTS preload header as it's not required anymore

= 2.1.12 =
* Fixed a bug where the SSL page could not be opened on multisite installations

= 2.1.11 =
* Fixed some untranslatable strings in security headers help tips
* Added German formal translations thanks to Thorsten Wollenhöfer

= 2.1.10 =
* Removed error logging from CSP class

= 2.1.9 =
* Fixed a bug where the rest route wouldn't initialize

= 2.1.8 =
* Fixed a bug which could prevent the Content Security Policy generator endpoint from working on specific setups with a site in a subfolder

= 2.1.7 =
* Tested with WordPress 5.3

= 2.1.6 =
* Enabled some security headers by default.
* Show HSTS preload option after enabling HSTS option without reloading page
* Updated support tab to pre-fill fields on really-simple-ssl.com, directly sending from support tab resulted in some undelivered e-mails

= 2.1.5 =
* Improved license request caching on settings page

= 2.1.4 =
* Improved support tab request processing

= 2.1.3 =
* Fix: fixed a bug where an empty regex matching group produced false positive http:// scan results

= 2.1.2 =
* Added right-to-left text support
* Scan error is now dismissible
* Fix: certificate valid to date now only shows when the corresponding option has been enabled

= 2.1.1 =
* Fix: no check for CSP violations when CSP not enabled.

= 2.1 =
* Added Content Security Policy Generator
* Added security headers
* Fix: moved emptying of results in blocked url's outside of loop
* Added Danish, Hungarian and Polish languages

= 2.0.24 =
* Tweak: added support for Bitnami/AWS htaccess.conf
* Tweak: updated support notice to send an e-mail manually after no response for 24 hours

= 2.0.23 =
* Tweak: added notices in license tab
* Tweak: admin notices will no longer show in Gutenberg edit screen
* Tweak: added visual feedback when fixing a scan results item
* Tweak: added notice when free plugin has been renamed
* Tweak: removed untraceable results from scan results
* Tweak: blog count fix for multi-networks

= 2.0.22 =
* Tweak: added caching to redirect_to_http() function to prevent timeouts.
* Tweak: updated Portuguese translations.

= 2.0.21 =
* Fix: fixed a bug in the redirect_to_http() function.

= 2.0.20 =
* Added support tab
* Tweak: improved mixed content scan regexes
* Tweak: added a redirect to http:// check before activating SSL

= 2.0.19 =
* Fix: fixed a bug where ignored url's were being added to the ignored url's array, even if they were already present.
* Tweak: the /plugins/, /wp-admin/ and /wp-includes/ directories have been excluded from the scan to increase performance.

= 2.0.18 =
* Fix: html closure in activation message

= 2.0.17 =
* Tweak: removed background running of scan
* Tweak: removed HTML from translatable strings

= 2.0.16 =
* Tweak: updated notices to be in line with free notices

= 2.0.15 =
* Tweak: when the scan is finished it shows the text Scan Finished and the progress bar turns green.
* Fix: improved external css and js detection regex

= 2.0.14 =
* Fix: run javascript only on own settings page
* Fix: ajax url on some functions not correct
* Fix: scan results only outputting after a refresh

= 2.0.13 =
* Tweak: updated the scan cron to prevent it from running without first starting the scan manually

= 2.0.12 =
* Updated the scan functionality to use cron

= 2.0.11 =
* Added scan support for the wp_postmeta table
* Fix: the show ignored URL's option now works correctly

= 2.0.10 =
* Fix: certificate expiration warning showed even when cert is still a long period valid if no valid response is received.
* Fix: config screen still showing error while not blocked URL's were left, because a 0 == 'string' comparison return true.

= 2.0.9 =
* Fix: the plugin will no longer scan its own folder for mixed content
* Fix: the scan now contains a number of common false positives in the $safe_domains array to prevent them showing up in the scan

= 2.0.8 =
* Fix: added a notice when the HSTS header is set using PHP on NGINX servers

= 2.0.7 =
* Fix: fixed a bug where the $has_result in the external CSS and JS scan was set to true when there were no results

= 2.0.6 =
* Tweak: added a warning when inserting HSTS headers via PHP

= 2.0.5 =
* Fix: deleted unnecessary transient delete

= 2.0.4 =
* Added support for Widgets in the mixed content scan
* Tweak: changed icon classnames from icon to rsssl-icons

= 2.0.3 =
* Tweak: option to disable flushing of rewrite rules on activation

= 2.0.2 =
* Tweak: added secure cookie settings
* Tweak: added notice if secure cookie settings couldn't be applied automatically

= 2.0.1 =
* Fix: made the HSTS option available when using the pro plugin on a multisite installation
* Tweak: updated the Easy Digital Downloads plugin updater to version 1.6.14

= 2.0.0 =
* Fix: moved scan data to transients, so large scan data arrays won't clutter the database
* Updated the 'Scan for issues tab'
* Fix: scan results are now shown in a responsive layout
* Fix: fixed a bug where protocol independent (//) files and/or stylesheet were not being scanned

= 1.0.33 =
* Fix: adjusted the HSTS header so it will also work in three redirects
* Fix: not all hot linked images were matched correctly in the scan

= 1.0.32 =
* Fix: When mixed content fixer is activated, urls are replaced to https, which prevented the scanner from finding these urls. A replace to http fixes this.
* Fix: Regex pattern updated to match the pattern in the free version, to prevent cross elemen matches.
* Fix: Changed priority of main class instantiation to make sure it instantiates after the core plugin

= 1.0.31 =
* Removed direct redirect to primary URL, as preload list requires a two step redirect, first to https.

= 1.0.30 =
* Fixed issue where preload HSTS setting wasn't saved when HSTS header was already in place.
* limited .htaccess edit to settings save action.

= 1.0.28 =
* Added certificate expiration date admin notice
* Bypass redirect chain
* Fixed SSL per page compatibility issue

= 1.0.27 =
* Added dutch translation
* Fixed typo in copyright warning box
* Fixed spacing between buttons in copyright warning box
* Tweak: added settings link to pro plugin in plugins overview page
* Fix: fixed a bug where curl function did not increment iteration, so redirect loops were not circumvented. Use wp_remote_get instead.

= 1.0.26 =
* Added expiration warning functionality: enable it in the settings to get an email if your ssl certificate expires.
* Fixed a bug that detected if the HSTS should be added in the .htaccess or in the code
* HSTS now available for NGINX as well
* Fixed a bug where the last js and css files were not parsed for http urls.

= 1.0.25 =
* css styling fixes
= 1.0.24 =
* Bug fix in importer.php

= 1.0.23 =
* Bug fixes
= 1.0.22 =
* Added functionality to fix mixed content issues
* several bug fixes

= 1.0.21 =
* improve license validation

= 1.0.20 =
* Extended safe list of domains for scan
* fixed a bug in licensing for multisite.
* fixed a bug where the scan freezed because of not loading external url's

= 1.0.19 =
Tweak: added better feedback on license not being activated
Tweak: added HSTS preload list option
Tweak: added mixed content fixer for the back-end

= 1.0.18 =
Tweak: added possibility to deactivate license, for migration purposes
= 1.0.17 =
Tweak: also checking for http links in form actions
Fix: bug caused empty scan results to show, even when no scan had been executed
Fix: last queue item was shown as nr 0, instead of the last nr

= 1.0.16 =
Fix: bug where not all found blocked urls would show up
Fix: also searching for the escaped values of a url in the database.

= 1.0.15 =
Tweak: added option to enable or disable the curl function for file requests

= 1.0.14 =
Added option to disable brute force database scan

== Upgrade notice ==
On settings page load, the .htaccess file is no rewritten. If you have made .htaccess customizations to the RSSSL block and have not blocked the plugin from editing it, do so before upgrading.
Always back up before any upgrade. Especially .htaccess, wp-config.php and the plugin folder. This way you can easily roll back.

== Screenshots ==
* If SSL is detected you can enable SSL after activation.
* The Really Simple SSL configuration tab.

== Frequently asked questions ==
* Really Simple SSL maintains an extensive knowledge-base at https://really-simple-ssl.com/knowledge-base-overview/
