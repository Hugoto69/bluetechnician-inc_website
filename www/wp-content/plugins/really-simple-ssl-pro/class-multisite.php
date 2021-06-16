<?php
/* 100% match ms */
defined('ABSPATH') or die("you do not have access to this page!");

if (!class_exists('rsssl_pro_multisite')) {

	class rsssl_pro_multisite
	{
		private static $_this;
		public $hsts;
		public $hsts_preload;
		public $upgrade_insecure_requests;
		public $content_security_policy;
		public $enable_csp_reporting;
		public $add_csp_rules_to_htaccess;
		public $x_xss_protection;
		public $x_content_type_options;
		public $no_referrer_when_downgrade;
		public $expect_ct;
		public $x_frame_options;
		public $turn_on_permissions_policy;

		function __construct()
		{
			$this->load_security_header_options();

			if (isset(self::$_this))
				wp_die(sprintf(__('%s is a singleton class and you cannot create a second instance.', 'really-simple-ssl'), get_class($this)));

			self::$_this = $this;
			if (is_network_admin()) {

				if (RSSSL()->rsssl_multisite->ssl_enabled_networkwide) {
					add_action( 'network_admin_menu', array($this, 'add_pro_settings'), 9);
				}

				add_action('rsssl_process_network_options', array($this, 'update_network_options') );
				add_action('rsssl_process_network_options', array($this, 'update_security_headers') );
			}
			add_action('wp_ajax_dismiss_success_message_nginx', array($this, 'dismiss_nginx_message_callback'));

			add_action('admin_print_footer_scripts-settings_page_really-simple-ssl', array($this, 'inline_scripts'));
			add_action('admin_init', array($this, 'check_upgrade'));
			add_action('wp_ajax_rsssl_site_switch', array($this, 'ajax_site_switch'));
			add_filter( 'rsssl_grid_items_ms', array($this, 'add_pro_grid_items'));

		}

		/**
		 * Run upgrade process
		 */

		public function check_upgrade(){
			if (!current_user_can('manage_options')) return;

			$prev_version = get_option( 'rsssl-pro-current-version', false );

			if ( $prev_version === rsssl_pro_version ) return;

			if ( $prev_version && version_compare( $prev_version, '4.0.0', '<' ) ) {
				$options = get_site_option('rlrsssl_security_headers');
				$this->content_security_policy    = isset( $options["rsssl_content_security_policy"] ) ? $options["rsssl_content_security_policy"] : false;
				$this->enable_csp_reporting         = isset( $options["rsssl_enable_csp_reporting"] ) ? $options["rsssl_enable_csp_reporting"] : false;
				$this->upgrade_insecure_requests  = isset( $options["rsssl_upgrade_insecure_requests"] ) ? $options["rsssl_upgrade_insecure_requests"] : false;
				$this->add_csp_rules_to_htaccess  = isset( $options["rsssl_add_csp_rules_to_htaccess"] ) ? $options["rsssl_add_csp_rules_to_htaccess"] : false;
				$this->x_xss_protection           = isset( $options["rsssl_x_xss_protection"] ) ? $options["rsssl_x_xss_protection"] : false;
				$this->x_content_type_options     = isset( $options["rsssl_x_content_type_options"] ) ? $options["rsssl_x_content_type_options"] : false;
				$this->no_referrer_when_downgrade = isset( $options["rsssl_no_referrer_when_downgrade"] ) ? $options["rsssl_no_referrer_when_downgrade"] : false;
				$this->expect_ct                  = isset( $options["rsssl_expect_ct"] ) ? $options["rsssl_expect_ct"] : false;
				$this->x_frame_options            = isset( $options["rsssl_x_frame_options"] ) ? $options["rsssl_x_frame_options"] : false;
				$this->turn_on_permissions_policy     = isset( $options["rsssl_turn_on_permissions_policy"] ) ? $options["rsssl_turn_on_permissions_policy"] : false;
				$this->hsts                       = isset( $options["rsssl_hsts"] ) ? $options["rsssl_hsts"] : false;

				update_site_option("rsssl_content_security_policy", $this->content_security_policy);
				update_site_option("rsssl_enable_csp_reporting", $this->enable_csp_reporting);
				update_site_option("rsssl_upgrade_insecure_requests", $this->upgrade_insecure_requests);
				update_site_option("rsssl_add_csp_rules_to_htaccess", $this->add_csp_rules_to_htaccess);
				update_site_option("rsssl_x_xss_protection",  $this->x_xss_protection);
				update_site_option("rsssl_x_content_type_options", $this->x_content_type_options);
				update_site_option("rsssl_no_referrer_when_downgrade", $this->no_referrer_when_downgrade);
				update_site_option("rsssl_expect_ct", $this->expect_ct);
				update_site_option("rsssl_x_frame_options", $this->x_frame_options);
				update_site_option("rsssl_turn_on_permissions_policy",$this->turn_on_permissions_policy);
				update_site_option("rsssl_hsts",$this->hsts);
			}

			if ( $prev_version && version_compare( $prev_version, '4.0.2', '<' ) ) {
				$options = get_site_option('rlrsssl_security_headers');
				$this->hsts_preload               = isset( $options["rsssl_hsts_preload"] ) ? $options["rsssl_hsts_preload"] : false;
				update_site_option("rsssl_hsts_preload", $this->hsts_preload);
			}

			update_option( 'rsssl-pro-current-version', rsssl_pro_version );
		}

		/**
		 * Replace the free support block with the support form and add the premium settings block
		 *
		 * @param $items
		 */
		public function add_pro_grid_items($items) {
			// Replace free blocks with pro if Really Simple SSL Pro is active
			if (!current_user_can('manage_options')) return;
			unset($items['plugins']);
			unset($items['support']);

			$items['sites_overview'] = array(
				'title' => __("Sites", "really-simple-ssl-pro"),
				'header' => rsssl_template_path . 'header.php',
				'content' => rsssl_pro_ms_template_path . 'sites.php',
				'footer' => '',
				'class' => 'rsssl_sites_overview',
				'type' => 'sites_overview',
				'can_hide' => true,
			);

			//get pro grid blocks
			$pro_grid = RSSSL_PRO()->rsssl_premium_options->premium_grid();
			unset($pro_grid['scan']);
			return $items + $pro_grid;
		}


		static function this()
		{
			return self::$_this;
		}

		public function load_security_header_options()
		{
			$this->upgrade_insecure_requests = get_site_option("rsssl_upgrade_insecure_requests");
			$this->content_security_policy = get_site_option("rsssl_content_security_policy");
			$this->x_xss_protection = get_site_option("rsssl_x_xss_protection");
			$this->x_content_type_options = get_site_option("rsssl_x_content_type_options");
			$this->no_referrer_when_downgrade = get_site_option("rsssl_no_referrer_when_downgrade");
			$this->expect_ct = get_site_option("rsssl_expect_ct");
			$this->x_frame_options = get_site_option("rsssl_x_frame_options");
			$this->turn_on_permissions_policy = get_site_option("rsssl_turn_on_permissions_policy");
			$this->enable_csp_reporting = get_site_option("rsssl_enable_csp_reporting");
			$this->add_csp_rules_to_htaccess = get_site_option("rsssl_add_csp_rules_to_htaccess");
			$this->hsts = get_site_option("rsssl_hsts");
			$this->hsts_preload = get_site_option("rsssl_hsts_preload");
		}

		public function inline_scripts()
		{
			?>
            <script>
                jQuery(document).ready(function($) {

                    function rsssl_switch(row, action){
                        var label = row.find('.rsssl-progress-status');
                        var btn = row.find('button');
                        var name = row.find('.rsssl-name').html();
                        if (action==='deactivate'){
                            label.removeClass('rsssl-success').addClass('rsssl-warning');
                            label.html('HTTP');
                            btn.html('activate');
                            btn.data('action', 'activate');

                            name = name.replace('https://', 'http://');
                        }

                        if (action === 'activate'){
                            label.removeClass('rsssl-warning').addClass('rsssl-success');
                            label.html('SSL');
                            btn.html('deactivate');
                            btn.data('action', 'deactivate');
                            name = name.replace('http://', 'https://');
                        }
                        row.find('.rsssl-name').html(name);
                    }

                    //handle ajax site switch
                    $(document).on("click", ".rsssl-switch-btn", function () {
                        var btn = $(this);
                        var blog_id = btn.data('blog_id');
                        var nonce = btn.data('nonce');
                        var switch_action = btn.data('action');
                        $.post(
                            '<?php echo admin_url( 'admin-ajax.php')?>',
                            {
                                blog_id: blog_id,
                                nonce: nonce,
                                switch_action: switch_action,
                                action : 'rsssl_site_switch'
                            },
                            function (response) {
                                var row = btn.closest('tr');
                                rsssl_switch(row, switch_action);
                            }
                        );
                    });

                    var sites_table = $('#rsssl_sites_overview').DataTable({
                        "pageLength": 7,
                        "info": false,

                        language: {
                            search: "<?php _e("Search", "really-simple-ssl-pro")?>&nbsp;",
                            sLengthMenu: "<?php printf(__("Show %s results", "really-simple-ssl-pro"), '_MENU_')?>",
                            sZeroRecords: "<?php _e("No results found", "really-simple-ssl-pro")?>",
                            sInfo:  "<?php printf(__("%s to %s of %s results", "really-simple-ssl-pro"), '_START_', '_END_', '_TOTAL_')?>",
                            sInfoEmpty: "<?php _e("No results to show", "really-simple-ssl-pro")?>",
                            sInfoFiltered: "<?php printf(__("(filtered from %s results)", "really-simple-ssl-pro"), '_MAX_')?>",
                            InfoPostFix: "",
                            EmptyTable: "<?php _e("No results found in the table", "really-simple-ssl-pro")?>",
                            InfoThousands: ".",
                            paginate: {
                                first: "<?php _e("First", "really-simple-ssl-pro")?>",
                                previous: "<?php _e("Previous", "really-simple-ssl-pro")?>",
                                next: "<?php _e("Next", "really-simple-ssl-pro")?>",
                                last: "<?php _e("Last", "really-simple-ssl-pro")?>",
                            },
                        },
                    });

                    var sites_overview_search = $("#rsssl_sites_overview_filter").detach();
                    $(".rsssl_sites_overview .rsssl-secondary-header-item").append(sites_overview_search);
                    var datatable_sites_paginate = $("#rsssl_sites_overview_paginate").detach();
                    $(".rsssl_sites_overview .rsssl-grid-item-footer").append(datatable_sites_paginate);
                });
            </script>
			<?php
		}


		public function ajax_site_switch(){
			$error = false;
			$response = json_encode(array('success' => false));
			if ( isset($_POST['action']) && isset($_POST["blog_id"]) && isset($_POST["nonce"]) && isset($_POST["nonce"]) && wp_verify_nonce($_POST["nonce"], "rsssl_switch_blog")) {
				if (!current_user_can("manage_network_plugins")) $error = true;
				if (($_POST['switch_action'] !== "activate") && ($_POST['switch_action'] != "deactivate")) $error =true;

				$action = $_POST['switch_action'];
				$blog_id = intval($_POST['blog_id']);

				if (!$error) {
					switch_to_blog($blog_id);
					if ($action == "deactivate") {
						RSSSL()->really_simple_ssl->deactivate_ssl();
					} else {
						RSSSL()->really_simple_ssl->activate_ssl();
						$site = get_site($blog_id);
						RSSSL_PRO()->rsssl_premium_options->replace_elementor_url( $site );
					}
					restore_current_blog();
				}

				$response = json_encode(array('success' => !$error));
			}
			header("Content-Type: application/json");
			echo $response;
			exit;
		}

		/**
		 *      Save network settings
		 */

		public
		function update_network_options()
		{
			if (!current_user_can('manage_options')) return;

			if (isset($_POST["rlrsssl_network_options"])) {
				$saved_options = array_map(array($this, "sanitize_boolean"), $_POST["rlrsssl_network_options"]);
				$db_options = get_site_option("rlrsssl_network_options");
				if (!is_array($saved_options)) $saved_options = array();

				$hsts_preload_new = isset($db_options["hsts_preload"]) ? $db_options["hsts_preload"] : FALSE;
				$hsts_preload_old = isset($saved_options['hsts_preload']) ? $saved_options['hsts_preload'] : FALSE;

				if ($hsts_preload_new != $hsts_preload_old) update_site_option("rsssl_nginx_message_shown", false);

				$hsts_new = isset($db_options['hsts']) ? $db_options['hsts'] : FALSE;
				$hsts_old = isset($saved_options['hsts']) ? $saved_options['hsts'] : FALSE;

				if ($hsts_new != $hsts_old) update_site_option("rsssl_nginx_message_shown", false);

				if (isset($saved_options["hsts_preload"])) $db_options["hsts_preload"] = $saved_options["hsts_preload"];

				update_site_option("rlrsssl_network_options", $db_options);
			}
		}

		/**
		 * Save settings for MS network security headers
		 */

		public function update_security_headers() {

			if (!current_user_can('manage_options')) return;

			if (isset($_POST["option_page"]) && $_POST["option_page"] === "rlrsssl_security_headers" ) {
				$options = array_map(  "sanitize_title", $_POST);
				$this->content_security_policy    = sanitize_title($options["rsssl_content_security_policy"] );
				$this->upgrade_insecure_requests  = isset( $options["rsssl_upgrade_insecure_requests"] ) ? $options["rsssl_upgrade_insecure_requests"] : false;
				$this->add_csp_rules_to_htaccess  = isset( $options["rsssl_add_csp_rules_to_htaccess"] ) ? $options["rsssl_add_csp_rules_to_htaccess"] : false;
				$this->x_xss_protection           = isset( $options["rsssl_x_xss_protection"] ) ? $options["rsssl_x_xss_protection"] : false;
				$this->x_content_type_options     = isset( $options["rsssl_x_content_type_options"] ) ? $options["rsssl_x_content_type_options"] : false;
				$this->no_referrer_when_downgrade = isset( $options["rsssl_no_referrer_when_downgrade"] ) ? $options["rsssl_no_referrer_when_downgrade"] : false;
				$this->expect_ct                  = isset( $options["rsssl_expect_ct"] ) ? $options["rsssl_expect_ct"] : false;
				$this->x_frame_options            = isset( $options["rsssl_x_frame_options"] ) ? $options["rsssl_x_frame_options"] : false;
				$this->turn_on_permissions_policy     = isset( $options["rsssl_turn_on_permissions_policy"] ) ? $options["rsssl_turn_on_permissions_policy"] : false;
				$this->hsts                       = isset( $options["rsssl_hsts"] ) ? $options["rsssl_hsts"] : false;
				$this->hsts_preload               = isset( $options["rsssl_hsts_preload"] ) ? $options["rsssl_hsts_preload"] : false;

				update_site_option("rsssl_content_security_policy", $this->content_security_policy);
				update_site_option("rsssl_enable_csp_reporting", $this->enable_csp_reporting);
				update_site_option("rsssl_upgrade_insecure_requests", $this->upgrade_insecure_requests);
				update_site_option("rsssl_add_csp_rules_to_htaccess", $this->add_csp_rules_to_htaccess);
				update_site_option("rsssl_x_xss_protection",  $this->x_xss_protection);
				update_site_option("rsssl_x_content_type_options", $this->x_content_type_options);
				update_site_option("rsssl_no_referrer_when_downgrade", $this->no_referrer_when_downgrade);
				update_site_option("rsssl_expect_ct", $this->expect_ct);
				update_site_option("rsssl_x_frame_options", $this->x_frame_options);
				update_site_option("rsssl_turn_on_permissions_policy",$this->turn_on_permissions_policy);
				update_site_option("rsssl_hsts",$this->hsts);
				update_site_option("rsssl_hsts_preload", $this->hsts_preload);
			}

			RSSSL_PRO()->rsssl_premium_options->insert_security_headers();
		}

		/**
		 * @param $site
		 * @param $enabled
		 * @param $disabled
		 * @param $snippet
		 *
		 * @return string|string[]
		 */

		public function generate_sites_overview_row($site, $enabled, $disabled, $snippet) {
			switch_to_blog( $site->blog_id );

			$options = get_option( 'rlrsssl_options' );
			if ( isset( $options ) ) {
				$ssl_enabled = isset( $options['ssl_enabled'] ) ? $options['ssl_enabled'] : false;
			}
			$active = $ssl_enabled ? $enabled : $disabled;
			$action = $ssl_enabled ? "deactivate" : "activate";
			$switch = $ssl_enabled ? __( "deactivate", "really-simple-ssl-pro" ) : __( "activate", "really-simple-ssl-pro" );
			$nonce  = wp_create_nonce( "rsssl_switch_blog" );
			$html   = str_replace( array(
				"[STATUS]",
				"[NAME]",
				"[BLOG_ID]",
				"[ACTION]",
				"[SWITCH]",
				"[NONCE]"
			), array( $active, home_url(), $site->blog_id, $action, $switch, $nonce ), $snippet );
			restore_current_blog(); //switches back to previous blog, not current, so we have to do it each loop
			return $html;
		}


		public
		function add_pro_settings()
		{
			add_settings_section('rsssl_network_settings', '', '', 'really-simple-ssl');
			$help = rsssl_help::this()->get_help_tip(__("Enable this if you want to automatically replace mixed content.", "really-simple-ssl-pro"), true );
			add_settings_field('id_autoreplace_mixed_content', $help . __("Auto replace mixed content", "really-simple-ssl-pro"), array($this, 'get_option_autoreplace_mixed_content'), 'really-simple-ssl', 'rsssl_network_settings');
			$help = rsssl_help::this()->get_help_tip(__("Enable this if you want to hide the SSL menu on subsites.", "really-simple-ssl-pro"), true );
			add_settings_field('id_hide_menu_for_subsites', $help . __("Hide menu for subsites", "really-simple-ssl-pro"), array($this, 'get_option_hide_menu_for_subsites'), 'really-simple-ssl', 'rsssl_network_settings');
			$help = rsssl_help::this()->get_help_tip(__("Enable this if you want to use the internal WordPress 301 redirect for all SSL websites. Needed on NGINX servers, or if the .htaccess redirect cannot be used.", "really-simple-ssl-pro"), true );
			add_settings_field('id_301_redirect', $help . __("WordPress 301 redirection to SSL for all SSL sites", "really-simple-ssl-pro"), array($this, 'get_option_wp_redirect'), 'really-simple-ssl', 'rsssl_network_settings');
			$help = rsssl_help::this()->get_help_tip(__("Enable this if you want to enable certificate expiration notices.", "really-simple-ssl-pro" ), true);
			add_settings_field('id_cert_expiration_warning', $help . __("Receive an email when your certificate is about to expire", "really-simple-ssl-pro"), array($this, 'get_option_cert_expiration_warning'), 'really-simple-ssl', 'rsssl_network_settings');
			$help = rsssl_help::this()->get_help_tip( __("Enable this if you want the mixed content fixer for admin.", "really-simple-ssl-pro"), true );
			add_settings_field('id_mixed_content_admin', $help . __("Mixed content fixer on the WordPress back-end", "really-simple-ssl-pro"), array($this, 'get_option_mixed_content_admin'), 'really-simple-ssl', 'rsssl_network_settings');

			if (RSSSL()->rsssl_multisite->ssl_enabled_networkwide) {
				$help = rsssl_help::this()->get_help_tip(__("Enable this if you want to redirect ALL websites to SSL using .htaccess", "really-simple-ssl-pro"), true );
			} else {
				$help = rsssl_help::this()->get_help_tip(__("Enable this if you want to redirect SSL websites using .htaccess. ", "really-simple-ssl-pro"), true );
			}
			add_settings_field('id_htaccess_redirect', $help . __("htaccess redirection to SSL on the network", "really-simple-ssl-pro"), array($this, 'get_option_htaccess_redirect'), 'really-simple-ssl', 'rsssl_network_settings');
			$help = rsssl_help::this()->get_help_tip(__("Enable this if you want to block the htaccess file from being edited.", "really-simple-ssl-pro"), true );
			add_settings_field('id_do_not_edit_htaccess', $help . __("Stop editing the .htaccess file", "really-simple-ssl-pro"), array($this, 'get_option_do_not_edit_htaccess'), 'really-simple-ssl', 'rsssl_network_settings');

		}

		/**
		 * Show the .htaccess redirect option
		 */

		public function get_option_htaccess_redirect()
		{
			?>
            <label class="rsssl-switch">
                <input id="rlrsssl_options" name="rlrsssl_network_options[htaccess_redirect]" size="40" value="1"
                       type="checkbox" <?php checked(1, RSSSL()->rsssl_multisite->htaccess_redirect, true) ?> />
                <span class="rsssl-slider rsssl-round"></span>
            </label>
			<?php
		}

		/**
		 * Show the wp redirect option
		 */

		public
		function get_option_wp_redirect()
		{
			?>
            <label class="rsssl-switch">
                <input id="rlrsssl_options" name="rlrsssl_network_options[wp_redirect]" size="40" value="1"
                       type="checkbox" <?php checked(1, RSSSL()->rsssl_multisite->wp_redirect, true) ?> />
                <span class="rsssl-slider rsssl-round"></span>
            </label>
			<?php
		}

		/**
		 * Get the mixed content option
		 */
		public
		function get_option_autoreplace_mixed_content()
		{
			?>
            <label class="rsssl-switch">
                <input id="rlrsssl_options" name="rlrsssl_network_options[autoreplace_mixed_content]" size="40" value="1"
                       type="checkbox" <?php checked(1, RSSSL()->rsssl_multisite->autoreplace_mixed_content, true) ?> />
                <span class="rsssl-slider rsssl-round"></span>
            </label>
			<?php
		}

		/**
		 * Mixed content on admin option
		 */

		public
		function get_option_mixed_content_admin()
		{
			?>
            <label class="rsssl-switch">
                <input id="rlrsssl_options" name="rlrsssl_network_options[mixed_content_admin]" size="40" value="1"
                       type="checkbox" <?php checked(1, RSSSL()->rsssl_multisite->mixed_content_admin, true) ?> />
                <span class="rsssl-slider rsssl-round"></span>
            </label>
			<?php
		}

		/**
		 * Certificate expiration warning
		 */
		public
		function get_option_cert_expiration_warning()
		{
			?>
            <label class="rsssl-switch">
                <input id="rlrsssl_options" name="rlrsssl_network_options[cert_expiration_warning]" size="40" value="1"
                       type="checkbox" <?php checked(1, RSSSL()->rsssl_multisite->cert_expiration_warning, true) ?> />
                <span class="rsssl-slider rsssl-round"></span>
            </label>
			<?php
		}

		/**
		 * Get hide menu hide option
		 */
		public
		function get_option_hide_menu_for_subsites()
		{
			?>
            <label class="rsssl-switch">
                <input id="rlrsssl_options" name="rlrsssl_network_options[hide_menu_for_subsites]" size="40" value="1"
                       type="checkbox" <?php checked(1, RSSSL()->rsssl_multisite->hide_menu_for_subsites, true) ?> />
                <span class="rsssl-slider rsssl-round"></span>
            </label>
			<?php
		}

		/**
		 * Get option do not edit .htaccess
		 */
		public
		function get_option_do_not_edit_htaccess()
		{
			?>
            <label class="rsssl-switch">
                <input id="rlrsssl_options" name="rlrsssl_network_options[do_not_edit_htaccess]" size="40" value="1"
                       type="checkbox" <?php checked(1, RSSSL()->rsssl_multisite->do_not_edit_htaccess, true) ?> />
                <span class="rsssl-slider rsssl-round"></span>
            </label>
			<?php
		}

		public
		function sanitize_boolean($value)
		{
			if ($value == true) {
				return true;
			} else {
				return false;
			}
		}

	} //class closure
}
