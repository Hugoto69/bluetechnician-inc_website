<?php defined('ABSPATH') or die("you do not have access to this page!");

	class rsssl_csp_backend
	{
		private static $_this;

		private $directives = array();

		function __construct()
		{

			if (isset(self::$_this))
				wp_die(sprintf(__('%s is a singleton class and you cannot create a second instance.', 'really-simple-ssl-pro'), get_class($this)));

			self::$_this = $this;

			add_action('admin_init', array($this, 'update_db_check'), 9);

			//Only add CSP tab if reporting has been enabled
			$csp_setting = RSSSL_PRO()->rsssl_premium_options->get_networkwide_option('rsssl_content_security_policy');
			if ( $csp_setting === 'report-only' || $csp_setting === 'enforce' || $csp_setting === 'disabled' || $csp_setting === 'report-paused' ) {
			    add_action('admin_init', array( $this, 'add_rules_to_htaccess') );
			}

			//Remove report only rules on option update
            add_action( "update_option_rsssl_content_security_policy", array( $this, "remove_csp_from_htaccess" ), 30,3);
            add_action( "update_option_rsssl_content_security_policy", array( $this, "maybe_reset_csp_count" ), 30,4);
            add_action( "update_option_rsssl_content_security_policy", array( $this, "maybe_reset_csp_api_token" ), 30,4);
            add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ));
			add_action( 'wp_ajax_rsssl_update_in_policy_value', array( $this, 'update_in_policy_value' ) );
            add_action( 'wp_ajax_rsssl_delete_from_csp', array( $this, 'delete_from_csp' ) );
            add_action( 'wp_ajax_rsssl_update_csp_toggle_option', array( $this, 'update_csp_toggle_value' ) );
            add_action( 'wp_ajax_rsssl_load_csp_table', array( $this, 'get_csp_table' ) );
            add_action('rsssl_csp_modals', array( $this , 'revoke_csp_modal' ), 99 );

            $this->directives = array(
				'child-src'         => "child-src 'self' {uri}; ",
				'connect-src'       => "connect-src 'self' {uri}; ",
				'font-src'          => "font-src 'self' {uri}; ",
				'frame-src'         => "frame-src 'self' {uri}; ",
				'img-src'           => "img-src 'self' data: {uri}; ",
				'manifest-src'      => "manifest-src 'self' {uri}; ",
				'media-src'         => "media-src 'self' {uri}; ",
				'prefetch-src'      => "prefetch-src 'self' {uri}; ",
				'object-src'        => "object-src 'self' {uri}; ",
				'script-src'        => "script-src 'self' 'unsafe-inline' {uri}; ",
				'script-src-elem'   => "script-src-elem 'self' 'unsafe-inline' {uri}; ",
				'script-src-attr'   => "script-src-attr 'self' {uri}; ",
				'style-src'         => "style-src 'self' 'unsafe-inline' {uri}; ",
				'style-src-elem'    => "style-src-elem 'self' 'unsafe-inline' {uri}; ",
				'style-src-attr'    => "style-src-attr 'self' {uri}; ",
				'worker-src'        => "worker-src 'self' {uri}; ",
			);
		}

		static function this()
		{
			return self::$_this;
		}

		/**
		 *
		 * Update the 'inpolicy' database value to true after 'Add to policy' button is clicked in Content Security Policy tab
		 *
		 * @since 2.5
		 */

		public function update_in_policy_value()
		{
			if (!current_user_can('manage_options')) return;

			global $wpdb;
			$table_name = $wpdb->base_prefix . "rsssl_csp_log";
			$value = '';

			if (isset($_POST['id'])) {
				//Sanitize, id should always be an int
				$id = intval($_POST['id']);

				if (isset($_POST['add_revoke']) && $_POST['add_revoke'] === 'add') {
                    $value = 'true';
				}
                if (isset($_POST['add_revoke']) && $_POST['add_revoke'] === 'revoke') {
				    $value = 'false';
                }

				$wpdb->update(
					$table_name,
					//Value to update
					array(
						'inpolicy' => $value,
					),
					//Update value where ID is
					array(
						'ID' => $id,
					)
				);
			}
            wp_die();
		}

        /**
         * Update CSP toggle value
         * @since 4.1.5
         *
         */
		public function update_csp_toggle_value() {

            if ( !current_user_can('manage_options') ) return;
            $allowed_values = array(
                'everything',
                'allowed',
                'blocked',
            );

            if ( isset($_POST['rsssl_csp_toggle_value'] )
                && $_POST['action'] === 'rsssl_update_csp_toggle_option'
                && in_array($_POST['rsssl_csp_toggle_value'], $allowed_values) ) {
                RSSSL_PRO()->rsssl_premium_options->update_networkwide_option('rsssl_content_security_policy_toggle', $_POST['rsssl_csp_toggle_value']);
            }

            wp_die();
		}

		/**
		 *
		 * Add CSP rules to the htaccess file
		 *
		 * @since 2.5
		 *
		 */

		public function add_rules_to_htaccess()
		{
			if ( !current_user_can('manage_options') ) return;
			if ( !RSSSL_PRO()->rsssl_premium_options->is_settings_page() || wp_doing_ajax() ) return;

			//Update CSP-Report-Only rules
			if ( RSSSL_PRO()->rsssl_premium_options->get_networkwide_option('rsssl_content_security_policy') === 'report-only' ) {
				$rules =  $this->get_csp_rules();
				RSSSL_PRO()->rsssl_premium_options->write_to_htaccess($rules, 'Really_Simple_SSL_CSP_Report_Only');
				$php_rules = $this->get_csp_rules('php' );
				RSSSL_PRO()->rsssl_premium_options->update_networkwide_option('rsssl_pro_csp_report_only_rules_for_php', $php_rules);
			} else {
				RSSSL_PRO()->rsssl_premium_options->delete_networkwide_option('rsssl_pro_csp_report_only_rules_for_php' );
				RSSSL_PRO()->rsssl_premium_options->remove_htaccess_rules( 'Really_Simple_SSL_CSP_Report_Only');
			}

			//Update CSP rules when policy is enforced.
            if ( RSSSL_PRO()->rsssl_premium_options->get_networkwide_option('rsssl_content_security_policy') === 'enforce' ) {
	            $rules =  $this->get_csp_rules();
	            RSSSL_PRO()->rsssl_premium_options->write_to_htaccess($rules, 'Really_Simple_SSL_Content_Security_Policy');
	            $php_rules = $this->get_csp_rules('php');
	            RSSSL_PRO()->rsssl_premium_options->update_networkwide_option('rsssl_pro_csp_enforce_rules_for_php', $php_rules);
            } else {
	            RSSSL_PRO()->rsssl_premium_options->delete_networkwide_option('rsssl_pro_csp_enforce_rules_for_php' );
	            RSSSL_PRO()->rsssl_premium_options->remove_htaccess_rules( 'Really_Simple_SSL_Content_Security_Policy');
			}

			if ( RSSSL_PRO()->rsssl_premium_options->get_networkwide_option('rsssl_content_security_policy') !== 'enforce' &&
			     RSSSL_PRO()->rsssl_premium_options->get_networkwide_option('rsssl_content_security_policy') !== 'report-only') {
				RSSSL_PRO()->rsssl_premium_options->remove_htaccess_rules( 'Really_Simple_SSL_Content_Security_Policy');
				RSSSL_PRO()->rsssl_premium_options->remove_htaccess_rules( 'Really_Simple_SSL_CSP_Report_Only');
			}
		}

		/**
         * Get CSP rules for any type or output type
		 * @param string $type
		 * @param false  $html_output
		 *
		 * @return string
		 */
		public function get_csp_rules($type = 'apache', $html_output = false ){
		    //script-src-elem 'self' 'unsafe-inline' https://goingtoamerica.nl http://pvcsd.org; style-src 'self' https://fonts.googleapis.com 'unsafe-inline';
			$rules = array();
			global $wpdb;
			$header = false;
			//The base content security policy rules, used in later functions to generate the Content Security Policy
			$rules['default-src'] = "default-src 'self' ;";
			$rules['script-src'] = "script-src 'self' 'unsafe-inline' ;";
			$rules['script-src-elem'] = "script-src-elem 'self' 'unsafe-inline' ;";
			$rules['style-src'] = "style-src 'self' 'unsafe-inline' ;";
			$rules['style-src-elem'] = "style-src-elem 'self' 'unsafe-inline' ;";

			$table_name = $wpdb->base_prefix . "rsssl_csp_log";
			$rows = $wpdb->get_results("SELECT * FROM $table_name ORDER BY time DESC");
			if (!empty($rows)) {
				foreach ($rows as $row) {
					if ( $row->inpolicy === 'true' ) {
						$violatedirective = $row->violateddirective;
						$blockeduri = $row->blockeduri;
						//Get uri value
						$uri = rsssl_sanitize_uri_value($blockeduri);
						//Generate CSP rule based on input
						$rules = $this->generate_csp_rule($violatedirective, $uri, $rules);
					}
				}
			}
			$rules = implode(" ", $rules);

			//Update CSP-Report-Only rules
			if ( RSSSL_PRO()->rsssl_premium_options->get_networkwide_option('rsssl_content_security_policy') === 'report-only' ) {
				$csp_violation_endpoint = home_url('wp-json/rsssl/v1/csp');
				if (RSSSL_PRO()->rsssl_premium_options->get_networkwide_option('rsssl_csp_report_token')) {
					$token = RSSSL_PRO()->rsssl_premium_options->get_networkwide_option('rsssl_csp_report_token');
				} else {
					$token = time();
					RSSSL_PRO()->rsssl_premium_options->update_networkwide_option('rsssl_csp_report_token', $token);
				}
				$header = 'Content-Security-Policy-Report-Only';
				$rules =  "$rules report-uri $csp_violation_endpoint?rsssl_apitoken=$token";
			}

			//Update CSP rules when policy is enforced.
			if ( RSSSL_PRO()->rsssl_premium_options->get_networkwide_option('rsssl_content_security_policy') === 'enforce' ) {
				// If the upgrade-insecure-requests header has been enabled, add it to this CSP.
                $header = 'Content-Security-Policy';
			}

			if ( $header ) {
				if ( RSSSL_PRO()->rsssl_premium_options->get_networkwide_option('rsssl_upgrade_insecure_requests') ) {
					$rules = "upgrade-insecure-requests; $rules";
				}
				return RSSSL_PRO()->rsssl_premium_options->wrap_header($header, $rules, $type, $html_output);
			} else {
			    return '';
            }
		}

		/**
		 * Remove Content Security Policy rules from .htaccess when Add Content Security Policy to .htaccess option is not enabled.
		 * @param $old_value
		 * @param $new_value
		 * @param $fieldname
		 *
		 * @since 2.5
		 */

		public function remove_csp_from_htaccess($old_value, $new_value, $fieldname)
		{
            if (!current_user_can('manage_options')) return;
            if ($old_value === $new_value) return;

			if ($new_value === 'report-only' || $new_value === 'disabled' ) {
				RSSSL_PRO()->rsssl_premium_options->remove_htaccess_rules( 'Really_Simple_SSL_Content_Security_Policy');
			}

			if ($new_value === 'enforce' || $new_value === 'report-paused' || $new_value === 'disabled' ) {
				RSSSL_PRO()->rsssl_premium_options->remove_htaccess_rules( 'Really_Simple_SSL_CSP_Report_Only');
			}
		}

        /**
         *
         * Delete the CSP track count when switching from report-paused to report-only
         * @since 4.1.1
         *
         */

        public function maybe_reset_csp_count($old_value, $new_value, $fieldname) {
            if ($old_value === 'report-paused' && $new_value === 'report-only') {
                RSSSL_PRO()->rsssl_premium_options->delete_networkwide_option('rsssl_csp_request_count');
            }
        }

        /**
         * @param $old_value
         * @param $new_value
         * @param $fieldname
         * @param $force
         *
         * Delete the CSP endpoint API token
         *
         * @since 4.1.3
         */
        public function maybe_reset_csp_api_token($old_value, $new_value, $fieldname, $force=false) {

            if ($new_value === 'report-paused' || $new_value === 'enforce' || $new_value === 'disabled' || $force===true) {
                RSSSL_PRO()->rsssl_premium_options->delete_networkwide_option('rsssl_csp_report_token');
            }
        }

		/**
		 * Generate CSP rules
		 *
		 * @param string $violateddirective
		 * @param string $uri
		 * @param array  $rules //previously detected rules
		 *
		 * @return array
		 */

		public function generate_csp_rule( $violateddirective, $uri, $rules )
		{
			// Check the violateddirective is valid
			if (isset($this->directives[$violateddirective])){
				// If the violated directive has an existing rule, update it
				if (isset($rules[$violateddirective])) {
					$rule_template = $this->directives[$violateddirective]; //'script-src-elem'   => "script-src-elem 'self' {uri}; ",

					//get existing rule
					$existing_rule = $rules[$violateddirective]; //'script-src-elem'   => "script-src-elem 'self' 'unsafe-inline';";
					//get part of directive before {uri}
					$rule_part = substr($rule_template, 0, strpos($rule_template, '{uri}')); //"script-src-elem 'self' "
					// URI can be both URL or a directive (for example script-src)
					// Check if the current rule already contains the URI
					if (strpos($existing_rule, $uri) !== false) {
						// If it contains the uri, do not add it again. Keep existing rule
						$new_rule = $existing_rule;
					} else {
					    //does not contain the uri, add it.
						$new_rule = str_replace($rule_part, $rule_part . $uri . " ", $existing_rule);
					}
					//insert in array
					$rules[$violateddirective] = $new_rule;
				} else {
					$rules[$violateddirective] = str_replace('{uri}', $uri, $this->directives[$violateddirective]);
				}
			}

			return $rules;
		}

		/**
		 *
		 * @since 2.5
		 *
		 * Enqueue DataTables scripts and CSS
		 *
		 */

		public function enqueue_scripts($hook)
		{
			if ( $hook !== 'settings_page_really-simple-ssl' && $hook !== 'settings_page_rlrsssl_really_simple_ssl' ) return;

			wp_register_style('rsssl-pro-csp-datatables', rsssl_pro_url . 'css/datatables.min.css', "", rsssl_pro_version);
			wp_enqueue_style('rsssl-pro-csp-datatables');
			wp_register_style('rsssl-pro-csp-table-css', rsssl_pro_url . 'css/jquery-table.css', "", rsssl_pro_version);
			wp_enqueue_style('rsssl-pro-csp-table-css');
			wp_enqueue_script('rsssl-pro-csp-datatables', rsssl_pro_url . "js/datatables.min.js", array('jquery'), rsssl_pro_version, false);
		}

		/**
		 * Check if db should be updated
		 */
		public function update_db_check()
		{
			if (!current_user_can('manage_options')) return;

			if (RSSSL_PRO()->rsssl_premium_options->get_networkwide_option('rsssl_csp_db_version') !== rsssl_pro_version) {
				require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

				global $wpdb;
				$table_name = $wpdb->base_prefix . "rsssl_csp_log";
				$charset_collate = $wpdb->get_charset_collate();

				$sql = "CREATE TABLE $table_name (
                  id mediumint(9) NOT NULL AUTO_INCREMENT,
                  time datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
                  documenturi text  NOT NULL,
                  violateddirective text  NOT NULL,
                  blockeduri text  NOT NULL,
                  inpolicy text NOT NULL,
                  PRIMARY KEY  (id)
                ) $charset_collate";

				dbDelta($sql);
				RSSSL_PRO()->rsssl_premium_options->update_networkwide_option('rsssl_csp_db_version', rsssl_pro_version);
			}
		}

        /**
         * Generate the CSP table
         * @since 4.1.5
         */

		public function get_csp_table() {
			if (!current_user_can('manage_options')) return;

			$container = RSSSL()->really_simple_ssl->get_template( 'csp-container.php',rsssl_pro_path . 'grid/' );
            $element = RSSSL()->really_simple_ssl->get_template( 'csp-element.php',rsssl_pro_path . 'grid/' );
            $output = '';

            global $wpdb;
            $table_name = $wpdb->base_prefix . "rsssl_csp_log";

            if ( RSSSL_PRO()->rsssl_premium_options->get_networkwide_option('rsssl_content_security_policy_toggle') === 'blocked' ) {
                $rows = $wpdb->get_results("SELECT * FROM $table_name WHERE `inpolicy` != 'true'  ORDER BY time DESC LIMIT 100");
            } elseif ( RSSSL_PRO()->rsssl_premium_options->get_networkwide_option('rsssl_content_security_policy_toggle') === 'allowed' ) {
                $rows = $wpdb->get_results("SELECT * FROM $table_name WHERE `inpolicy` = 'true'  ORDER BY time DESC LIMIT 100");
            } else {
                $rows = $wpdb->get_results("SELECT * FROM $table_name ORDER BY time DESC LIMIT 100");
            }

            foreach ( $rows as $row ) {
                $uri = substr(str_replace(site_url(), "", $row->documenturi), 0, 40);
                if ($uri === '/' || $uri === '') $uri = 'Home';

                if (  empty($row->inpolicy) || $row->inpolicy === 'false' ) {
                    $id = 'button-secondary start-add-to-csp';
                    $button_text = __("Allow", "really-simple-ssl-pro");
                    $modal = '';
                } else {
                    $id = 'button-rsssl-tertiary revoke-from-csp';
                    $button_text = __("Revoke", "really-simple-ssl-pro");
                    $modal = "data-toggle='modal' data-target='#revoke-csp-modal'";
                }

                // Check if date is today
                if (date('Ymd') == date('Ymd', strtotime($row->time))) {
                    $date = __("Today", "really-simple-ssl-pro");
                } else {
                    $date = human_time_diff(strtotime($row->time), current_time('timestamp')) . " " . __("ago", "really-simple-ssl-pro");
                }

                $output .= str_replace(array(
                    '{date}',
                    '{documenturi}',
                    '{uri}',
                    '{violateddirective}',
                    '{blockeduri}',
                    '{data_id}',
                    '{id}',
                    '{button_text}',
                    '{modal}'

                ), array(
                    $date,
                    $row->documenturi,
                    $uri,
                    $row->violateddirective,
                    $row->blockeduri,
                    $row->id,
                    $id,
                    $button_text,
                    $modal,
                ), $element);
            }

            $html = str_replace(
                array(
                    '{content}'
                ),
                array(
                    $output)
                , $container);

            if ( wp_doing_ajax() ) {
                wp_die($html);
            } else {
                return $html;
            }
        }

        /**
         * Delete entry from CSP table
         * @since 4.1.6
         *
         */
        public function delete_from_csp() {
            if (!current_user_can('manage_options')) return;

            if ( isset($_POST['id'] )
                && $_POST['action'] == 'delete_from_csp'
                && isset($_POST["token"])
                && wp_verify_nonce($_POST["token"], "rsssl_revoke_from_csp" ) ) {

                global $wpdb;
                $table_name = $wpdb->base_prefix . "rsssl_csp_log";

                $id = intval($_POST['id']);

                $wpdb->delete(
                    $table_name, // table to delete from
                    array('id' => $id // value in column to target for deletion
                    )
                );

                wp_die();
            }
        }

        /**
         * Show revoke CSP modal
         * @since 4.1.5
         */

        public function revoke_csp_modal() {
		    ?>
            <div class="modal fade rsssl-modal-dialog" id="revoke-csp-modal" tabindex="-1" role="dialog"
                 aria-labelledby="revoke-csp-modal">
                <div class="modal-dialog" role="document">
                    <div class="modal-content">
                        <div class="modal-header">
                            <button type="button" class="close notice-dismiss" data-dismiss="modal"
                                    aria-label="Close"><span aria-hidden="true"></span>
                            </button>
                            <h3 class="modal-title" id="revoke-csp-title">
                                <?php _e("Revoking a Rule from the Content Security Policy", "really-simple-ssl-pro"); ?>
                            </h3>
                        </div>
                        <div class="modal-body">
                            <div id='revoke-csp-modal-description'>
                                <?php _e("If you revoke a rule from the content security policy,  the rule will be deleted from the resource list that is considered safe to load.  This might affect your website,  or specific functions.  You can always allow the resource if needed.", "really-simple-ssl-pro"); ?>
                            </div>
                            <div id='revoke-csp-modal-type'></div>
                        </div>
                        <div class="modal-footer">
                            <button type="button"
                                    class="button button-primary" data-dismiss="modal" aria-label="Close">
                                    <?php _e( "Close",
                                    "really-simple-ssl-pro" ) ?>
                            </button>
                            <button type="button" data-results_id=0 data-id=0
                                    data-path=0 data-url=0
                                    data-token="<?php echo wp_create_nonce( 'rsssl_revoke_from_csp' ); ?>"
                                    class="button button-rsssl-primary rsssl-start-action"
                                    id="start-revoke-from-csp"><?php _e( "Revoke rule",
                                    "really-simple-ssl-pro" ) ?>
                            </button>
                            <button type="button" data-results_id=0 data-id=0
                                    data-path=0 data-url=0
                                    data-token="<?php echo wp_create_nonce( 'rsssl_revoke_from_csp' ); ?>"
                                    class="button button-rsssl-tertiary rsssl-start-action"
                                    id="start-revoke-delete-from-csp"><?php _e( "Revoke and delete rule",
                                    "really-simple-ssl-pro" ) ?>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
		    <?php
    }
}