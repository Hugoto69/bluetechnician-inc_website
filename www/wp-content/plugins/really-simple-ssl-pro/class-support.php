<?php
defined('ABSPATH') or die("you do not have access to this page!");

if ( ! class_exists( 'rsssl_support' ) ) {
	class rsssl_support
	{
		private static $_this;
		public $error_message = "";

		function __construct()
		{
			add_action('admin_init', array($this, 'process_support_request'));
			add_filter( 'allowed_redirect_hosts' , array($this, 'allow_really_simple_ssl_com_redirect') , 10 );
			if (isset(self::$_this)) wp_die(sprintf(__('%s is a singleton class and you cannot create a second instance.', 'really-simple-ssl'), get_class($this)));
			self::$_this = $this;
		}

		static function this()
		{
			return self::$_this;
		}

		/**
		 * post support request on really-simple-ssl.com
		 */

		public function process_support_request()
		{
			if (isset($_POST['rsssl_support_request']) ) {

				if (!wp_verify_nonce($_POST['rsssl_nonce'], 'rsssl_support')) return;
				$user_info = get_userdata(get_current_user_id());
				$email = urlencode($user_info->user_email);
				$name = urlencode($user_info->display_name);
				$support_request = urlencode(esc_html($_POST['rsssl_support_request']) );
				$htaccess = "";

				$debug_log_contents = RSSSL()->really_simple_ssl->debug_log;
				$debug_log_contents = str_replace("\n", '--br--', $debug_log_contents );
				$debug_log_contents = urlencode(strip_tags( $debug_log_contents ) );

				$htaccess_file = RSSSL()->really_simple_ssl->htaccess_file();
				if (file_exists($htaccess_file) ) {
					$htaccess = file_get_contents($htaccess_file);
					$htaccess = str_replace("\n", '--br--', $htaccess );
					$htaccess = urlencode($htaccess);
				}

				//Retrieve the domain
				$domain = site_url();
				//Retrieve active plugins
				$active_plugins = get_option('active_plugins');
				$active_plugins = print_r($active_plugins, true);

				//Get scan results from transient
				$scan_results = get_transient("rlrsssl_scan");

				$results = '';

				if (!empty($scan_results['posts_with_blocked_resources'])) {
					$results = print_r($scan_results['posts_with_blocked_resources'], true);
				}

				if (!empty($scan_results['css_js_with_mixed_content'])) {
					$results .= print_r($scan_results['css_js_with_mixed_content'], true);
				}

				if (!empty($scan_results['external_css_js_with_mixed_content'])) {
					$results .= print_r($scan_results['external_css_js_with_mixed_content'], true);
				}

				if (!empty($scan_results['postmeta_with_blocked_resources'])) {
					$results .= print_r($scan_results['postmeta_with_blocked_resources'], true);
				}

				if (!empty($scan_results['tables_with_blocked_resources'])) {
					$results .= print_r($scan_results['tables_with_blocked_resources'], true);
				}

				if (!empty($scan_results['widgets_with_blocked_resources'])) {
					$results .= print_r($scan_results['widgets_with_blocked_resources'], true);
				}

                $user_id = get_current_user_id();
				$license_key = RSSSL_PRO()->rsssl_licensing->license_key();

                if (get_option('rsssl_pro_disable_license_for_other_users') == 1 && get_option('rsssl_licensing_allowed_user_id') == $user_id) {
                    $license_key = RSSSL_PRO()->rsssl_licensing->maybe_decode( $license_key );
                } elseif (!get_option('rsssl_pro_disable_license_for_other_users') ) {
                    $license_key = RSSSL_PRO()->rsssl_licensing->maybe_decode( $license_key );
                } else {
                    $license_key = 'protected';
                }

				$url = "https://really-simple-ssl.com/support/?email=$email&customername=$name&domain=$domain&supportrequest=$support_request&debuglog=$debug_log_contents&scanresults=$results&activeplugins=$active_plugins&licensekey=$license_key&htaccesscontents=$htaccess";

				wp_redirect($url);
				exit;
			}
		}

		public function allow_really_simple_ssl_com_redirect($content){
			$content[] = 'really-simple-ssl.com';
			return $content;
		}

	} //class closure
}