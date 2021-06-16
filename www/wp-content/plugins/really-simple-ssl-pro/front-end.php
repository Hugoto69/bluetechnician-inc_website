<?php
defined('ABSPATH') or die("you do not have access to this page!");

if (!function_exists('rsssl_send_php_security_headers')) {

	/**
	 * Set the security headers using PHP header() function.
	 *
	 * @since 4.1
     *
	 **/

	function rsssl_send_php_security_headers()
    {
        if (rsssl_get_networkwide_option('rsssl_enable_php_headers') ) {

            $hsts = rsssl_get_networkwide_option('rsssl_hsts');
            $upgrade_insecure_requests = rsssl_get_networkwide_option('rsssl_upgrade_insecure_requests');
            $x_xss_protection = rsssl_get_networkwide_option('rsssl_x_xss_protection');
            $x_content_type_options = rsssl_get_networkwide_option('rsssl_x_content_type_options');
            $no_referrer_when_downgrade = rsssl_get_networkwide_option('rsssl_no_referrer_when_downgrade');
            $expect_ct = rsssl_get_networkwide_option('rsssl_expect_ct');
            $x_frame_options = rsssl_get_networkwide_option('rsssl_x_frame_options');
            $permissions_policy = rsssl_get_networkwide_option('rsssl_turn_on_permissions_policy');

            if ($hsts) {
                $hsts_preload = rsssl_get_networkwide_option("rsssl_hsts_preload");
                if ($hsts_preload) {
                    if ( is_ssl() ) header('Strict-Transport-Security: max-age=63072000; includeSubDomains; preload');
                } else {
	                if ( is_ssl() ) header('Strict-Transport-Security: max-age=31536000');
                }
            }

            // Do not add the upgrade-insecure-requests header here when CSP is enforced, CSP will include this option when it is enabled
            if ($upgrade_insecure_requests
                && rsssl_get_networkwide_option('rsssl_content_security_policy') !== 'enforce'
                && rsssl_get_networkwide_option('rsssl_enable_csp_reporting') !== 'report-only'
                && rsssl_get_networkwide_option('rsssl_enable_csp_reporting') !== 'report-paused')
            {
                header('Content-Security-Policy: "upgrade-insecure-requests"');
            }

            if ($x_xss_protection) {
                header('X-XSS-Protection: 1; mode=block');
            }

            if ($x_content_type_options) {
                header('X-Content-Type-Options: nosniff');
            }

            if ($no_referrer_when_downgrade) {
                header('Referrer-Policy: no-referrer-when-downgrade');
            }

            if ($expect_ct) {
                header('Expect-CT: max-age=7776000, enforce');
            }

            if ($x_frame_options) {
                header('X-Frame-Options: sameorigin');
            }

	        if ( $permissions_policy && rsssl_get_networkwide_option('rsssl_pro_permissions_policy_headers_for_php' ) ) {
		        $rule = rsssl_get_networkwide_option( 'rsssl_pro_permissions_policy_headers_for_php' );
		        header( $rule );
	        }

	        if ( rsssl_get_networkwide_option('rsssl_content_security_policy' ) === 'report-only' && rsssl_get_networkwide_option('rsssl_content_security_policy') !== 'enforce' ) {
	            $rule = rsssl_get_networkwide_option( 'rsssl_pro_csp_report_only_rules_for_php' );
                header( $rule );
            } elseif (rsssl_get_networkwide_option( 'rsssl_add_csp_rules_to_htaccess' ) ) {
                $rule = rsssl_get_networkwide_option( 'rsssl_pro_csp_enforce_rules_for_php' );
                header( $rule );
            }


        }
    }

    add_action('send_headers', 'rsssl_send_php_security_headers');
}


if ( !function_exists('rsssl_secure_logged_in_cookie')) {

	/**
	 * Set a secure cookie, but only if the site is enabled for SSL, not per page.
	 * This setting can be used on multisite as well, as it will decide per site what setting to use.
	 * @since 2.0.10
	 * */

	function rsssl_secure_logged_in_cookie($secure_logged_in_cookie, $user_id, $secure){
		$options = get_option("rsssl_options");
		$ssl_enabled = isset($options['ssl_enabled']) ? $options['ssl_enabled'] : false;

		if (!defined('rsssl_pp_version') && $ssl_enabled) {
			return true;
		}
		return $secure_logged_in_cookie;
	}
	add_filter( 'secure_logged_in_cookie', 'rsssl_secure_logged_in_cookie' , 10, 3);
}

if ( !function_exists('is_rsssl_plugin_active')) {

	/**
	 *
	 * Check the current free plugin is active
	 *
	 * @since 3.1
	 *
	 * @return boolean
	 *
	 */

	function is_rsssl_plugin_active()
	{
		if (defined('rsssl_plugin')) {
			return true;
		} else {
			return false;
		}
	}
}

if ( !function_exists('rsssl_get_networkwide_option') ) {

    /**
     * Get option, network or single site
     * @param string name
     * @return mixed
     *
     * @since 4.1
     *
     */

    function rsssl_get_networkwide_option($name)
    {
        if (is_multisite()) {
            return get_site_option($name);
        } else {
            return get_option($name);
        }
    }
}

if ( !function_exists('rsssl_update_networkwide_option') ) {

    /**
     * Update option, network or single site
     * @param string name
     * @return mixed
     *
     * @since 4.1.1
     *
     */

    function rsssl_update_networkwide_option($name, $value)
    {
        if (is_multisite()) {
            return update_site_option($name, $value);
        } else {
            return update_option($name, $value);
        }
    }
}