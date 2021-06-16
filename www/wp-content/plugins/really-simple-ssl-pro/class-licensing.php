<?php
/* 100% match ms */
defined('ABSPATH') or die("you do not have access to this page!");

update_site_option('rsssl_pro_license_key','Apanha');
update_site_option('rsssl_pro_license_activation_limit',0);
update_site_option('rsssl_pro_license_activations_left',9999 );
update_site_option('rsssl_pro_license_expires','01.01.2030' );

if (!class_exists('RSSSL_SL_Plugin_Updater')) {
	// load our custom updater
	include(dirname(__FILE__) . '/EDD_SL_Plugin_Updater.php');
}

if (!class_exists("rsssl_licensing")) {
	class rsssl_licensing
	{
		private static $_this;

		function __construct()
		{
			if (isset(self::$_this))
				wp_die(sprintf(__('%s is a singleton class and you cannot create a second instance.', 'really-simple-ssl'), get_class($this)));

			self::$_this = $this;

			add_action('admin_init', array($this, 'plugin_updater'), 0);
            add_action('admin_init', array($this, 'license_is_valid'), 5);
            add_action('admin_init', array($this, 'activate_license'), 10, 3);
			add_action('admin_init', array($this, 'register_option'), 20, 3);
			add_action('admin_init', array($this, 'deactivate_license'), 30, 3);
			add_action('admin_init', array($this, 'save_license'), 30, 3);

			add_action('admin_init', array($this, 'listen_for_license_user_switch'), 40);
			add_action('wp_ajax_maybe_update_disable_license', array($this, 'maybe_save_license_disabled_for_other_users') );
			add_action('rsssl_settings_link', array($this, 'get_settings_link_type'));

			if ( is_multisite() && defined('rsssl_pro_ms_version') ) {
				add_action( "network_admin_notices", array($this, 'ms_show_notice_license') );
				add_filter( 'rsssl_grid_items_ms',  array($this, 'ms_add_license_grid') );
			} else {
				add_filter('rsssl_grid_tabs', array($this, 'add_license_tab'), 20, 3);
				add_action( 'show_tab_license', array( $this, 'add_license_page' ) );
			}

		}

		static function this()
		{
			return self::$_this;
		}

		public function listen_for_license_user_switch() {
			if ( ! current_user_can( 'manage_options' ) ) {
				return;
			}

			if ( isset( $_GET['rsssl_license_unlock'] ) ) {
				if ( $_GET['rsssl_license_unlock'] == $this->maybe_decode( $this->license_key() ) ) {
					$user_id = get_current_user_id();
					update_option( 'rsssl_licensing_allowed_user_id', $user_id );
				}
			}
		}

		/**
		 * Get type of settings link, set premium for users with valid license
		 * @param $type
		 *
		 * @return string
		 */

		public function get_settings_link_type($type){
			
		$type = 'premium';
			
			return $type;
		}

		/**
		 * Get the license key
		 * @return string
		 */
		public function license_key(){
			return $this->encode( get_site_option('rsssl_pro_license_key') );
		}

		/**
		 * Add license block to grid
		 *
		 * @return array
		 */

		public function license_grid() {

			$grid_items = array(
				'license' => array(
					'title' => __("Really Simple SSL Pro license key", "really-simple-ssl-pro"),
					'header' => rsssl_template_path.'/header.php',
					'content' => rsssl_pro_template_path.'/license.php',
					'footer' => rsssl_pro_template_path . 'license-footer.php',
					'class' => 'regular rsssl-license-grid',
					'type' => 'settings',
					'can_hide' => true,
					'instructions' => false,
				),
			);

			$grid_items = apply_filters('rsssl_social_license_block', $grid_items);

			return $grid_items;
		}

		/**
		 * Add license page to grid block
		 */

		public function add_license_page() {
			if (!current_user_can('manage_options')) return;
			RSSSL()->really_simple_ssl->render_grid( $this->license_grid() );
		}

		/**
		 * Plugin updater
		 */

		public function plugin_updater()
		{
			$license_key = $this->maybe_decode(trim($this->license_key()));
			$edd_updater = new RSSSL_SL_Plugin_Updater(REALLY_SIMPLE_SSL_URL, rsssl_pro_plugin_file, array(
					'version' => rsssl_pro_version,
					'license' => $license_key,
					'author' => 'Really Simple Plugins',
					'item_id' => RSSSL_ITEM_ID,
				)
			);
		}

		/**
		 * Decode a license key
		 * @param string $string
		 *
		 * @return string
		 */

		public function maybe_decode( $string ) {
			if (strpos( $string , 'really_simple_ssl_') !== FALSE ) {
				$key = $this->get_key();
				$string = str_replace('really_simple_ssl_', '', $string);

				// To decrypt, split the encrypted data from our IV
				$ivlength = openssl_cipher_iv_length('aes-256-cbc');
				$iv = substr(base64_decode($string), 0, $ivlength);
				$encrypted_data = substr(base64_decode($string), $ivlength);

				$decrypted =  openssl_decrypt($encrypted_data, 'aes-256-cbc', $key, 0, $iv);
				return $decrypted;
			}

			//not encoded, return
			return $string;
		}

		/**
         * Get a decode/encode key
		 * @return false|string
		 */

		public function get_key() {

		    if ( !get_site_option('rsssl_key') ) {
		        //check if we're upgraded to network option already. If multisite, we need to upgrade
			    if ( is_multisite() && !get_site_option('rsssl_upgraded_license_key') ) {
                    //if this is the main site, set this option as network wide option
			        if ( is_main_site() ) {
				        update_site_option('rsssl_key', get_option( 'rsssl_key') );
                    } else {
                    //if we're on a subsite, try to get the key from the main site
				        switch_to_blog( get_main_site_id() );
				        update_site_option('rsssl_key', get_option( 'rsssl_key') );
				        restore_current_blog();
                    }
				    update_site_option('rsssl_upgraded_license_key', true );
                }
            }

			return get_site_option( 'rsssl_key' );
        }

		/**
         * Set a new key
		 * @return string
		 */

        public function set_key(){
	        update_site_option( 'rsssl_key' , time() );
	        return get_site_option('rsssl_key');
        }

		/**
		 * Encode a license key
		 * @param string $string
		 * @return string
		 */

		public function encode( $string ) {
			if ( strlen(trim($string)) === 0 ) return $string;

			if (strpos( $string , 'really_simple_ssl_') !== FALSE ) {
				return $string;
			}

			$key = $this->get_key();
			if ( !$key ) {
				$key = $this->set_key();
			}

			$ivlength = openssl_cipher_iv_length('aes-256-cbc');
			$iv = openssl_random_pseudo_bytes($ivlength);
			$ciphertext_raw = openssl_encrypt($string, 'aes-256-cbc', $key, 0, $iv);
			$key = base64_encode( $iv.$ciphertext_raw );

			return 'really_simple_ssl_'.$key;
		}

		/**
		 * Add a license tab
		 * @param $tabs
		 *
		 * @return mixed
		 */
		public function add_license_tab($tabs)
		{
			$tabs['license'] = __("License", "really-simple-ssl-pro");
			return $tabs;
		}

		/**
		 * Register the license as a setting
		 */

		public function register_option()
		{
			register_setting('rsssl_pro_license', 'rsssl_pro_license_key', array($this, 'sanitize_license'));
		}

		/**
		 * Sanitize the license
		 * @param $new
		 *
		 * @return mixed
		 */
		public function sanitize_license($new)
		{
			$old = $this->license_key();
			if ($old && $old != $new) {
				delete_site_transient('rsssl_pro_license_status'); // new license has been entered, so must reactivate
			}
			return $new;
		}

		/**
		 * Activate the license key
		 */

		public function activate_license()
		{
			if (!current_user_can('manage_options')) return;

			if (isset($_POST['rsssl_pro_license_activate'])) {
				if (!check_admin_referer('rsssl_pro_nonce', 'rsssl_pro_nonce'))
					return;

				$license = trim($_POST['rsssl_pro_license_key']);
				update_site_option('rsssl_pro_license_key', $this->encode($license) );
				$this->get_license_status('activate_license', true );
			}
		}

		/**
		 * Deactivate the license
		 * @return bool|void
		 */

		public function deactivate_license()
		{
			if (!current_user_can('manage_options')) return;
			if (isset($_POST['rsssl_pro_license_deactivate'])) {
				if (!check_admin_referer('rsssl_pro_nonce', 'rsssl_pro_nonce')) return;

				$this->get_license_status('deactivate_license', true);
			}
		}

		/**
		 * Save the license key
		 */

		public function save_license(){

			if (!current_user_can('manage_options')) return;

			if (!isset($_POST["rsssl_pro_license_save"]) || !isset($_POST["rsssl_pro_license_key"]) || !isset($_POST['rsssl_pro_nonce']) ) return;

			if( !wp_verify_nonce( $_POST['rsssl_pro_nonce'], 'rsssl_pro_nonce' ) ) return;

			$license = sanitize_text_field(trim($_POST['rsssl_pro_license_key']));
			$license = $this->sanitize_license($license);
			update_site_option('rsssl_pro_license_key', $this->encode($license) );
			$this->get_license_status('activate_license', true );

			if ( is_network_admin() ) {
				wp_redirect(add_query_arg(array('page' => "really-simple-ssl", 'tab' => 'license'), network_admin_url('settings.php')));
			} else {
				wp_redirect(add_query_arg(array('page' => "rlrsssl_really_simple_ssl", 'tab' => 'license'), admin_url('options-general.php')));
			}
			exit;
		}

		/**
		 * Update the 'disable for all users except yourself' checkbox in the license tab.
		 */
		public function maybe_save_license_disabled_for_other_users() {
			if ( ! current_user_can( 'manage_options' ) ) {
				return;
			}
			$current_user = get_current_user_id();
			$allowed_user = intval( get_option('rsssl_licensing_allowed_user_id') );
			$lock = get_option('rsssl_pro_disable_license_for_other_users') == 1;
			$disabled = $lock && ($current_user !== $allowed_user);

			if ( $disabled ) return;

			if ( isset( $_POST['value'] ) && $_POST['action'] == 'maybe_update_disable_license' ) {
				if ( $_POST['value'] == 'checked' ) {
					update_option( "rsssl_pro_disable_license_for_other_users", 1 );
					$user_id = get_current_user_id();
					update_option('rsssl_licensing_allowed_user_id', $user_id);
				} else {
					update_option( "rsssl_pro_disable_license_for_other_users", 0 );
				}
			}
		}

		/**
		 * Check if license is valid
		 * @return bool
		 */

		public function license_is_valid()
		{
			$status = $this->get_license_status();
			
				return true;
			
		}

		/**
		 * Get latest license data from license key
		 * @param string $action
		 * @param bool $clear_cache
		 * @return string
		 *   empty => no license key yet
		 *   invalid, disabled, deactivated
		 *   revoked, missing, invalid, site_inactive, item_name_mismatch, no_activations_left
		 *   inactive, expired, valid
		 */

		public function get_license_status($action = 'check_license', $clear_cache = false )
		{
		    $status = get_site_transient('rsssl_pro_license_status');
			if ($clear_cache) $status = false;

			if (!$status || get_site_option('rsssl_pro_license_activation_limit') === FALSE ){
				$status = 'invalid';
				$license = $this->maybe_decode( $this->license_key() );
				if ( strlen($license) ===0 ) return 'empty';

				$home_url = home_url();

				//the multisite plugin should activate for the main domain
                if ( defined('rsssl_pro_ms_version') ) {
                    $home_url = network_site_url();
                }

				// data to send in our API request
				$api_params = array(
					'edd_action' => $action,
					'license' => $license,
					'item_id' => RSSSL_ITEM_ID,
					'url' => $home_url,
				);

				$args = apply_filters('rsssl_license_verification_args', array('timeout' => 15, 'sslverify' => true, 'body' => $api_params) );
				$response = wp_remote_post(REALLY_SIMPLE_SSL_URL, $args);

				$license_data = json_decode(wp_remote_retrieve_body($response));
				$license_data->license='valid';
				$license_data->error='';
				$license_data->license_limit=0;
				$license_data->activations_left=9999;
				$license_data->success=true;
				$license_data->expires='lifetime';

				$status = $license_data->license; //inactive, expired, valid
				$date = $license_data->expires;
				if ( $date !== 'lifetime' ) {
					$date = strtotime($date);
					$date = date(get_option('date_format'), $date);
				}
				update_site_option('rsssl_pro_license_expires', $date);
				update_site_option('rsssl_pro_license_activation_limit', $license_data->license_limit);
				update_site_option('rsssl_pro_license_activations_left', $license_data->activations_left);

				set_site_transient('rsssl_pro_license_status', $status, WEEK_IN_SECONDS);
			}
            return 'valid';
		}

		/**
		 * Get license status label
		 * @return string
		 */

		public function get_license_label(){
			$status = $this->get_license_status();
			$support_link = '<a target="_blank" href="https://really-simple-ssl.com/support">';
			$account_link = '<a target="_blank" href="https://really-simple-ssl.com/account">';
			$agency_link = '<a target="_blank" href="https://really-simple-ssl.com/pro#multisite">';

			$activation_limit = get_site_option('rsssl_pro_license_activation_limit' ) === 0 ? __('unlimited', 'really-simple-ssl-pro') : get_site_option('rsssl_pro_license_activation_limit' );
			$activations_left = get_site_option('rsssl_pro_license_activations_left' );
			$expires_date = get_site_option('rsssl_pro_license_expires' );

			if ( !$expires_date ) {
				$expires_message = __("Not available");
            } else {
				$expires_message = $expires_date === 'lifetime' ? __( "You have a lifetime license.", 'really-simple-ssl-pro' ) : sprintf( __( "Valid until %s.", 'really-simple-ssl-pro' ), $expires_date );
			}
			$next_upsell = '';
			if ( $activations_left == 0 && $activation_limit !=0 ) {
				switch ( $activation_limit ) {
					case 1:
						$next_upsell = sprintf(__( "Upgrade to a %s5 sites or Agency%s license.", "really-simple-ssl-pro" ), $account_link, '</a>');
						break;
					case 5:
						$next_upsell = sprintf(__( "Upgrade to an %sAgency%s license.", "really-simple-ssl-pro" ), $account_link, '</a>');
						break;
					default:
						$next_upsell = sprintf(__( "You can renew your license on your %saccount%s.", "really-simple-ssl-pro" ), $account_link, '</a>');
				}
			}

			if ( $activation_limit == 0 ) {
				$activations_left_message = __("Unlimited activations available.", 'really-simple-ssl-pro').' '.$next_upsell;
			} else {
				$activations_left_message = sprintf(__("%s/%s activations available.", 'really-simple-ssl-pro'), $activations_left, $activation_limit ).' '.$next_upsell;
			}

			$messages = array();

			/**
			 * Some default messages, if the license is valid
			 */

			if ( $status === 'valid' || $status === 'inactive' || $status === 'deactivated' || $status === 'site_inactive' ) {

				$messages[] = array(
					'type' => 'success',
					'label' => __('Valid', 'really-simple-ssl-pro'),
					'message' => $expires_message,
				);

				$messages[] = array(
					'type' => 'premium',
					'label' => __('License', 'really-simple-ssl-pro'),
					'message' => sprintf(__("Valid license for %s.", 'really-simple-ssl-pro'), RSSSL_ITEM_NAME.' '.RSSSL_ITEM_VERSION),
				);

				$messages[] = array(
					'type' => 'premium',
					'label' => __('License', 'really-simple-ssl-pro'),
					'message' => $activations_left_message,
				);

				if ( is_multisite() && !defined('rsssl_pro_ms_version') ) {
					$messages[] = array(
						'type' => 'open',
						'label' => __('Multisite', 'really-simple-ssl-pro'),
						'message' => sprintf(__("Multisite detected. Please consider upgrading to %smultisite%s.", 'really-simple-ssl-pro'), $agency_link, '</a>' ),
					);
				}
			} else {
				//it is possible the site does not have an error status, and no activations left.
				//in this case the license is activated for this site, but it's the last one. In that case it's just a friendly reminder.
                //if it's unlimited, it's zero.
                //if the status is empty, we can't know the number of activations left. Just skip this then.
				if ( $status !== 'no_activations_left' && $status !== 'empty' && $activations_left == 0 ){
					$messages[] = array(
						'type' => 'open',
						'label' => __('License', 'really-simple-ssl-pro'),
						'message' => $activations_left_message,
					);
				}
            }

			switch ( $status ) {
				case 'error':
					$messages[] = array(
						'type' => 'open',
						'label' => __('No response', 'really-simple-ssl-pro'),
						'message' => sprintf(__("The license information could not be retrieved at this moment. Please try again at a later time.", 'really-simple-ssl-pro'), $account_link, '</a>'),
					);
					break;
				case 'empty':
					$messages[] = array(
						'type' => 'open',
						'label' => __('Open', 'really-simple-ssl-pro'),
						'message' => sprintf(__("Please enter your license key. Available in your %saccount%s.", 'really-simple-ssl-pro'), $account_link, '</a>'),
					);
					break;
				case 'inactive':
				case 'site_inactive':
				case 'deactivated':
					$messages[] = array(
						'type' => 'warning',
						'label' => __('Open', 'really-simple-ssl-pro'),
						'message' => sprintf(__("Please activate your license key.", 'really-simple-ssl-pro'), $account_link, '</a>'),
					);
					break;
				case 'revoked':
					$messages[] = array(
						'type' => 'warning',
						'label' => __('Warning', 'really-simple-ssl-pro'),
						'message' => sprintf(__("Your license has been revoked. Please contact %ssupport%s.", 'really-simple-ssl-pro'), $support_link, '</a>'),
					);
					break;
				case 'missing':
					$messages[] = array(
						'type' => 'warning',
						'label' => __('Warning', 'really-simple-ssl-pro'),
						'message' => sprintf(__("Your license could not be found in our system. Please contact %ssupport%s.", 'really-simple-ssl-pro'), $support_link, '</a>'),
					);
					break;
				case 'invalid':
				case 'disabled':
					$messages[] = array(
						'type' => 'warning',
						'label' => __('Warning', 'really-simple-ssl-pro'),
						'message' => sprintf(__("This license is not valid. Find out why on your %saccount%s.", 'really-simple-ssl-pro'), $account_link, '</a>'),
					);
					break;
				case 'item_name_mismatch':
					$messages[] = array(
						'type' => 'warning',
						'label' => __('Warning', 'really-simple-ssl-pro'),
						'message' => sprintf(__("This license is not valid for this product. Find out why on your %saccount%s.", 'really-simple-ssl-pro'), $account_link, '</a>'),
					);
					break;
				case 'no_activations_left':
				    //can never be unlimited, for obvious reasons
					$messages[] = array(
						'type' => 'warning',
						'label' => __('License', 'really-simple-ssl-pro'),
						'message' => sprintf(__("%s/%s activations available.", 'really-simple-ssl-pro'), 0, $activation_limit ).' '.$next_upsell,
					);
					break;
				case 'expired':
					$messages[] = array(
						'type' => 'warning',
						'label' => __('Warning', 'really-simple-ssl-pro'),
						'message' => sprintf(__("Your license key has expired. Please renew your license key on your %saccount%s.", 'really-simple-ssl-pro'), $account_link, '</a>'),
					);
					break;
			}

			$html = '';
			foreach ( $messages as $message ) {
				$html .= $this->rsssl_notice( $message );
			}

			return $html;
		}

		/**
		 * Show a notice regarding the license
		 * @param array $message
		 *
		 * @return string
		 */

		public function rsssl_notice($message)
		{
			if ( !isset($message['message']) || $message['message'] == '') return '';

			ob_start();
			?>

            <tr>
                <td>
	                    <span class="rsssl-progress-status rsssl-license-status rsssl-<?php echo $message['type'] ?>">
                            <?php echo $message['label'] ?>
                        </span>
                </td>
                <td class="rsssl-license-notice-text">
					<?php echo $message['message'] ?>
                </td>
            </tr>
			<?php

			$contents = ob_get_clean();
			echo $contents;
		}

		/**
		 * Multisite
		 * Show notice about the license
		 */

		public function ms_show_notice_license(){
			//prevent showing the review on edit screen, as gutenberg removes the class which makes it editable.
			$screen = get_current_screen();
			if ( $screen->base === 'post' ) return;

			if ( !is_network_admin() ) return;

			if ( !$this->license_is_valid() ) {
				$content = __("You haven't activated your Really Simple SSL Pro Multisite license yet. To get all future updates, enter your license on the network settings page.","really-simple-ssl-pro");
				$content .= ' '.sprintf(
						__("%sGo to the settings page%s or %spurchase a license%s.","really-simple-ssl-pro"),
						'<a href="'.network_admin_url("settings.php?page=really-simple-ssl&tab=license").'">',
						'</a>',
						'<a target="blank" href="https://really-simple-ssl.com/pro-multisite">',
						'</a>'
					);

				$content = '<p>'.$content.'</p>';
				$class = "error rsssl-pro-dismiss-notice";
				$title = __("License not activated", "really-simple-ssl-pro");
				echo RSSSL()->really_simple_ssl->notice_html( $class, $title, $content );
			} ?>
			<?php
		}

		/**
		 * Multisite
		 * Add the license grid
		 * @param array $grid_items
		 *
		 * @return mixed
		 */

		public function ms_add_license_grid($grid_items){
			$grid_items['license'] = array(
				'title' => __("Really Simple SSL Pro Multisite license key", "really-simple-ssl-pro"),
				'header' => rsssl_template_path . 'header.php',
				'content' => rsssl_pro_template_path . 'license.php',
				'footer' => rsssl_pro_template_path . 'license-footer.php',
				'class' => 'rsssl-license-grid',
				'type' => 'settings',
				'can_hide' => false,
			);
			return $grid_items;
		}
	}
} //class closure
