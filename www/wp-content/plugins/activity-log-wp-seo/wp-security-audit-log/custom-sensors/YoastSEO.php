<?php // phpcs:ignore
/**
 * Sensor: Yoast SEO
 *
 * Yoast SEO sensor file.
 *
 * @package Wsal
 * @since 3.2.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Support for Yoast SEO Plugin.
 *
 * @package Wsal
 * @subpackage Sensors
 */
if ( ! class_exists( 'WSAL_Sensors_YoastSEO' ) ) {
	class WSAL_Sensors_YoastSEO extends WSAL_AbstractSensor {

		/**
		 * Post ID.
		 *
		 * @var int
		 */
		private $post_id = 0;

		/**
		 * Post Object.
		 *
		 * @var WP_Post
		 */
		private $post;

		/**
		 * SEO Post Data.
		 *
		 * @var array
		 */
		private $post_seo_data = array(
			'_yoast_wpseo_title'                => '',
			'_yoast_wpseo_metadesc'             => '',
			'_yoast_wpseo_focuskw'              => '',
			'_yoast_wpseo_is_cornerstone'       => '',
			'_yoast_wpseo_meta-robots-noindex'  => '',
			'_yoast_wpseo_meta-robots-nofollow' => '',
			'_yoast_wpseo_meta-robots-adv'      => '',
			'_yoast_wpseo_canonical'            => '',
		);

		/**
		 * Listening to events using hooks.
		 */
		public function HookEvents() {
			// If user can edit post then hook this function.
			if ( current_user_can( 'edit_posts' ) ) {
				add_action( 'admin_init', array( $this, 'event_admin_init' ) );
			}

			// Yoast SEO option alerts.
			add_action( 'updated_option', array( $this, 'yoast_options_trigger' ), 10, 3 );

			// Yoast SEO Site option alerts.
			add_action( 'update_site_option', array( $this, 'yoast_site_options_trigger' ), 10, 3 );

			// Yoast SEO blog option default change alerts.
			add_action( 'add_option_wpseo', array( $this, 'yoast_blog_options_trigger' ), 10, 2 );
		}

		/**
		 * Method: Admin Init Event.
		 */
		public function event_admin_init() {
			// Load old data, if applicable.
			$this->retrieve_post_data();

			// Check for settings change.
			$this->check_seo_data_change();
		}

		/**
		 * Method: Retrieve Post ID.
		 */
		protected function retrieve_post_data() {
			// Filter POST global array.
			$post_array = filter_input_array( INPUT_POST );

			if ( isset( $post_array['post_ID'] )
				&& isset( $post_array['_wpnonce'] )
				&& ! wp_verify_nonce( $post_array['_wpnonce'], 'update-post_' . $post_array['post_ID'] ) ) {
				return false;
			}

			if ( isset( $post_array ) && isset( $post_array['post_ID'] )
				&& ! ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE )
				&& ! ( isset( $post_array['action'] ) && 'autosave' === $post_array['action'] )
			) {
				$this->post_id = intval( $post_array['post_ID'] );
				$this->post    = get_post( $this->post_id );
				$this->set_post_seo_data();
			}
		}

		/**
		 * Method: Set Post SEO Data.
		 */
		private function set_post_seo_data() {
			// Set post SEO meta data.
			$this->post_seo_data = array(
				'_yoast_wpseo_title'                => get_post_meta( $this->post_id, '_yoast_wpseo_title', true ),
				'_yoast_wpseo_metadesc'             => get_post_meta( $this->post_id, '_yoast_wpseo_metadesc', true ),
				'_yoast_wpseo_focuskw'              => get_post_meta( $this->post_id, '_yoast_wpseo_focuskw', true ),
				'_yoast_wpseo_is_cornerstone'       => get_post_meta( $this->post_id, '_yoast_wpseo_is_cornerstone', true ),
				'_yoast_wpseo_meta-robots-noindex'  => get_post_meta( $this->post_id, '_yoast_wpseo_meta-robots-noindex', true ),
				'_yoast_wpseo_meta-robots-nofollow' => get_post_meta( $this->post_id, '_yoast_wpseo_meta-robots-nofollow', true ),
				'_yoast_wpseo_meta-robots-adv'      => get_post_meta( $this->post_id, '_yoast_wpseo_meta-robots-adv', true ),
				'_yoast_wpseo_canonical'            => get_post_meta( $this->post_id, '_yoast_wpseo_canonical', true ),
			);
		}

		/**
		 * Method: Get Post SEO Data.
		 *
		 * @param string $key – Meta Key.
		 * @return mixed
		 */
		protected function get_post_seo_data( $key = '' ) {
			// If empty key then return false.
			if ( empty( $key ) ) {
				return false;
			}

			// Set prefix of meta data.
			$prefix = '_yoast_wpseo_';

			// Option to retrieve.
			$option = $prefix . $key;

			// If key exists and is not empty then return value.
			if ( isset( $this->post_seo_data[ $option ] ) && ! empty( $this->post_seo_data[ $option ] ) ) {
				return $this->post_seo_data[ $option ];
			} else {
				// Return false if key doesn't exists or its value is empty.
				return false;
			}
		}

		/**
		 * Get editor link.
		 *
		 * @param stdClass $post_id - Post id.
		 * @return array $editor_link - Name and value link.
		 */
		private function get_editor_link( $post_id ) {
			$value       = get_edit_post_link( $post_id );
			$editor_link = array(
				'name'  => 'EditorLinkPost',
				'value' => $value,
			);
			return $editor_link;
		}

		/**
		 * Method: Detect Post SEO Data Change.
		 */
		protected function check_seo_data_change() {
			// Set filter input args.
			$filter_input_args = array(
				'post_ID'                           => FILTER_VALIDATE_INT,
				'_wpnonce'                          => FILTER_SANITIZE_STRING,
				'action'                            => FILTER_SANITIZE_STRING,
				'yoast_wpseo_title'                 => FILTER_SANITIZE_STRING,
				'yoast_wpseo_metadesc'              => FILTER_SANITIZE_STRING,
				'yoast_wpseo_focuskw'               => FILTER_SANITIZE_STRING,
				'yoast_wpseo_is_cornerstone'        => FILTER_VALIDATE_BOOLEAN,
				'yoast_wpseo_meta-robots-noindex'   => FILTER_VALIDATE_INT,
				'yoast_wpseo_meta-robots-nofollow'  => FILTER_VALIDATE_INT,
				'yoast_wpseo_meta-robots-adv-react' => array(
					'flags' => FILTER_REQUIRE_ARRAY,
				),
				'yoast_wpseo_canonical'             => FILTER_VALIDATE_URL,
			);

			// Filter POST global array.
			$post_array = filter_input_array( INPUT_POST, $filter_input_args );

			if ( isset( $post_array['post_ID'] )
				&& 'editpost' === $post_array['action']
				&& isset( $post_array['_wpnonce'] )
				&& wp_verify_nonce( $post_array['_wpnonce'], 'update-post_' . $post_array['post_ID'] ) ) {
				// Check SEO data changes and alert if changed.
				$this->check_title_change( $post_array['yoast_wpseo_title'] ); // Title.
				$this->check_desc_change( $post_array['yoast_wpseo_metadesc'] ); // Meta description.
				$this->check_robots_index_change( $post_array['yoast_wpseo_meta-robots-noindex'] ); // Meta Robots Index.
				$this->check_robots_follow_change( $post_array['yoast_wpseo_meta-robots-nofollow'] ); // Meta Robots Follow.
				$this->check_robots_advanced_change( $post_array['yoast_wpseo_meta-robots-adv-react'] ); // Meta Robots Advanced.
				$this->check_canonical_url_change( $post_array['yoast_wpseo_canonical'] ); // Canonical URL.
				$this->check_focus_keys_change( $post_array['yoast_wpseo_focuskw'] ); // Focus keywords.
				$this->check_cornerstone_change( $post_array['yoast_wpseo_is_cornerstone'] ); // Cornerstone.
			}
		}

		/**
		 * Method: Check SEO Title Change.
		 *
		 * @param string $title – Changed SEO Title.
		 */
		protected function check_title_change( $title ) {
			// Get old title value.
			$old_title = $this->get_post_seo_data( 'title' );

			// If old and new values are empty then don't log the alert.
			if ( empty( $old_title ) && empty( $title ) ) {
				return;
			}

			// Remove whitespaces at the ends of the titles.
			$old_title = trim( $old_title );
			$title     = trim( $title );

			// If title is changed then log alert.
			if ( $old_title !== $title ) {

				// Ensure default value is not passed as NULL.
				if ( ! empty( $old_title ) && empty( $title ) ) {
					if ( strpos( $old_title, '%%title%% %%page%% %%sep%% %%sitename%%' ) !== false ) {
						$title = '%%title%% %%page%% %%sep%% %%sitename%%';
					}
				}
				if ( empty( $old_title ) && ! empty( $title ) ) {
					if ( strpos( $title, '%%title%% %%page%% %%sep%% %%sitename%%' ) !== false ) {
						$old_title = '%%title%% %%page%% %%sep%% %%sitename%%';
					}
				}

				$editor_link = $this->get_editor_link( $this->post_id );
				$this->plugin->alerts->Trigger(
					8801,
					array(
						'PostID'             => $this->post->ID,
						'PostType'           => $this->post->post_type,
						'PostTitle'          => $this->post->post_title,
						'PostStatus'         => $this->post->post_status,
						'PostDate'           => $this->post->post_date,
						'PostUrl'            => get_permalink( $this->post->ID ),
						'OldSEOTitle'        => $old_title,
						'NewSEOTitle'        => $title,
						$editor_link['name'] => $editor_link['value'],
					)
				);
			}
		}

		/**
		 * Method: Check SEO Meta Description Change.
		 *
		 * @param string $desc – Changed SEO Meta Description.
		 */
		protected function check_desc_change( $desc ) {
			// Get old desc value.
			$old_desc = esc_html( $this->get_post_seo_data( 'metadesc' ) );
			$desc     = esc_html( $desc );

			// If old and new values are empty then don't log the alert.
			if ( empty( $old_desc ) && empty( $desc ) ) {
				return;
			}

			// If desc is changed then log alert.
			if ( trim( $old_desc ) !== trim( $desc ) ) {

				// Replace NULL with a nicer string.
				if ( empty( $old_desc ) ) {
					$old_desc = __( 'Not provided', 'activity-log-wp-seo' );
				}

				$editor_link = $this->get_editor_link( $this->post_id );
				$this->plugin->alerts->Trigger(
					8802,
					array(
						'PostID'             => $this->post->ID,
						'PostType'           => $this->post->post_type,
						'PostTitle'          => $this->post->post_title,
						'PostStatus'         => $this->post->post_status,
						'PostDate'           => $this->post->post_date,
						'PostUrl'            => get_permalink( $this->post->ID ),
						'old_desc'           => $old_desc,
						'new_desc'           => $desc,
						$editor_link['name'] => $editor_link['value'],
					)
				);
			}
		}

		/**
		 * Method: Check Meta Robots Index Change.
		 *
		 * @param string $index – Changed Meta Robots Index.
		 */
		protected function check_robots_index_change( $index ) {
			// Get old title value.
			$old_index = (int) $this->get_post_seo_data( 'meta-robots-noindex' );

			if ( 1 === $old_index ) {
				$old_index = 'No';
			} else {
				$old_index = 'Yes';
			}

			if ( 1 === $index ) {
				$index = 'No';
			} else {
				$index = 'Yes';
			}

			// If setting is changed then log alert.
			if ( $old_index !== $index ) {
				$editor_link = $this->get_editor_link( $this->post_id );
				$this->plugin->alerts->Trigger(
					8803,
					array(
						'PostID'             => $this->post->ID,
						'PostType'           => $this->post->post_type,
						'PostTitle'          => $this->post->post_title,
						'PostStatus'         => $this->post->post_status,
						'PostDate'           => $this->post->post_date,
						'PostUrl'            => get_permalink( $this->post->ID ),
						'OldStatus'          => $old_index,
						'NewStatus'          => $index,
						$editor_link['name'] => $editor_link['value'],
					)
				);
			}
		}

		/**
		 * Method: Check Meta Robots Follow Change.
		 *
		 * @param string $follow – Changed Meta Robots Follow.
		 */
		protected function check_robots_follow_change( $follow ) {
			// Get old title value.
			$old_follow = (int) $this->get_post_seo_data( 'meta-robots-nofollow' );

			if ( 1 === $old_follow ) {
				$old_follow = 'disabled';
			} else {
				$old_follow = 'enabled';
			}

			if ( 1 === $follow ) {
				$follow = 'disabled';
			} else {
				$follow = 'enabled';
			}

			// If setting is changed then log alert.
			if ( $old_follow !== $follow ) {
				$editor_link = $this->get_editor_link( $this->post_id );
				$this->plugin->alerts->Trigger(
					8804,
					array(
						'PostID'             => $this->post->ID,
						'PostType'           => $this->post->post_type,
						'PostTitle'          => $this->post->post_title,
						'PostStatus'         => $this->post->post_status,
						'PostDate'           => $this->post->post_date,
						'PostUrl'            => get_permalink( $this->post->ID ),
						'EventType'          => $follow,
						$editor_link['name'] => $editor_link['value'],
					)
				);
			}
		}

		/**
		 * Method: Check Meta Robots Advanced Change.
		 *
		 * @param array $advanced – Advanced array.
		 */
		protected function check_robots_advanced_change( $advanced ) {
			// Convert to string.
			if ( is_array( $advanced ) ) {
				$advanced = implode( ',', $advanced );
			}

			// Get old title value.
			$old_adv = $this->get_post_seo_data( 'meta-robots-adv' );

			// If old and new values are empty then don't log the alert.
			if ( empty( $old_adv ) && ( empty( $advanced ) || '-' === $advanced ) ) {
				return;
			}

			// If setting is changed then log alert.
			if ( $old_adv !== $advanced ) {
				$editor_link = $this->get_editor_link( $this->post_id );
				$this->plugin->alerts->Trigger(
					8805,
					array(
						'PostID'             => $this->post->ID,
						'PostType'           => $this->post->post_type,
						'PostTitle'          => $this->post->post_title,
						'PostStatus'         => $this->post->post_status,
						'PostDate'           => $this->post->post_date,
						'PostUrl'            => get_permalink( $this->post->ID ),
						'OldStatus'          => $old_adv,
						'NewStatus'          => $advanced,
						$editor_link['name'] => $editor_link['value'],
					)
				);
			}
		}

		/**
		 * Method: Check Canonical URL Change.
		 *
		 * @param string $canonical_url – Changed Canonical URL.
		 */
		protected function check_canonical_url_change( $canonical_url ) {
			// Get old title value.
			$old_url = $this->get_post_seo_data( 'canonical' );

			// Check to see if both change value are empty.
			if ( empty( $old_url ) && empty( $canonical_url ) ) {
				return; // Return if both are empty.
			}

			// If title is changed then log alert.
			if ( $old_url !== $canonical_url ) {
				$editor_link = $this->get_editor_link( $this->post_id );
				$this->plugin->alerts->Trigger(
					8806,
					array(
						'PostID'             => $this->post->ID,
						'PostType'           => $this->post->post_type,
						'PostTitle'          => $this->post->post_title,
						'PostStatus'         => $this->post->post_status,
						'PostDate'           => $this->post->post_date,
						'PostUrl'            => get_permalink( $this->post->ID ),
						'OldCanonicalUrl'    => $old_url,
						'NewCanonicalUrl'    => $canonical_url,
						$editor_link['name'] => $editor_link['value'],
					)
				);
			}
		}

		/**
		 * Method: Check Focus Keywords Change.
		 *
		 * @param string $focus_keys – Changed Focus Keywords.
		 */
		protected function check_focus_keys_change( $focus_keys ) {
			// Get old title value.
			$old_focus_keys = $this->get_post_seo_data( 'focuskw' );

			// If old and new values are empty then don't log the alert.
			if ( empty( $old_focus_keys ) && empty( $focus_keys ) ) {
				return;
			}

			// If title is changed then log alert.
			if ( $old_focus_keys !== $focus_keys ) {

				// Replace NULL with a nicer string.
				if ( empty( $old_desc ) ) {
					$old_focus_keys = __( 'Not provided', 'activity-log-wp-seo' );
				}

				$editor_link = $this->get_editor_link( $this->post_id );
				$this->plugin->alerts->Trigger(
					8807,
					array(
						'PostID'             => $this->post->ID,
						'PostType'           => $this->post->post_type,
						'PostTitle'          => $this->post->post_title,
						'PostStatus'         => $this->post->post_status,
						'PostDate'           => $this->post->post_date,
						'PostUrl'            => get_permalink( $this->post->ID ),
						'old_keywords'       => $old_focus_keys,
						'new_keywords'       => $focus_keys,
						$editor_link['name'] => $editor_link['value'],
					)
				);
			}
		}

		/**
		 * Method: Check Cornerstone Change.
		 *
		 * @param string $cornerstone – Changed Cornerstone.
		 */
		protected function check_cornerstone_change( $cornerstone ) {
			// Get old title value.
			$old_cornerstone = (int) $this->get_post_seo_data( 'is_cornerstone' );
			$cornerstone     = (int) $cornerstone;

			if ( 1 === $cornerstone ) {
				$alert_status = 'enabled';
			} else {
				$alert_status = 'disabled';
			}

			// If setting is changed then log alert.
			if ( $old_cornerstone !== $cornerstone ) {
				$editor_link = $this->get_editor_link( $this->post_id );
				$this->plugin->alerts->Trigger(
					8808,
					array(
						'PostID'             => $this->post->ID,
						'PostType'           => $this->post->post_type,
						'PostTitle'          => $this->post->post_title,
						'PostStatus'         => $this->post->post_status,
						'PostDate'           => $this->post->post_date,
						'PostUrl'            => get_permalink( $this->post->ID ),
						'EventType'          => $alert_status,
						$editor_link['name'] => $editor_link['value'],
					)
				);
			}
		}

		/**
		 * Method: Yoast default blog options change trigger.
		 * Notes:
		 * 	- To accomplish that, Yoast is taking the site, removes option (wpseo_ms) and sets the new one
		 * @see Yoast-Network-Admin::handle_restore_site_request
		 * 	- wp functions used do not triggering events @see WPSEO_Options::reset_ms_blog :
		 * 		- delete_blog_option, update_blog_option
		 * Logic used here is - if add_option_wpseo is triggered (this method is called only then), and global $_POST is set with valid 'site_id' value and 'ms_defaults_set' (in $value parameter) == true - we know which site has been preset with the default options
		 *
		 * @param string $option – Option name.
		 * @param mixed  $value – Option old value.
		 */
		public function yoast_blog_options_trigger( $option, $value ) {
			$site_id = ( isset( $_POST[ 'wpseo_ms' ] ) && ! empty( $_POST[ 'wpseo_ms' ]['site_id'] ) ) ? (int) $_POST[ 'wpseo_ms' ]['site_id'] : 0;
			if ( $site_id && isset($value['ms_defaults_set']) && true === $value['ms_defaults_set']) {
				$this->yoast_setting_change_alert( 'site-default-options-change', $site_id, '' );
			}
		}

		/**
		 * Method: Yoast SEO options trigger.
		 *
		 * @param string $option – Option name.
		 * @param mixed  $old_value – Option old value.
		 * @param mixed  $new_value – Option new value.
		 */
		public function yoast_site_options_trigger( $option, $old_value, $new_value ) {

			if ( 'wpseo_ms' === $option ) {

				$prefixNetwork = 'network-';
				$prefixYoast = 'allow_';

				$eventNames = [
					'keyword_analysis_active', // SEO analysis.
					'content_analysis_active', // Readability analysis.
					'enable_cornerstone_content', // Cornerstone Content.
					'enable_text_link_counter', // Text Link Counter.
					'enable_xml_sitemap', // XML Sitemaps.
					'enable_admin_bar_menu', // Admin bar menu.
					'disableadvanced_meta', // Advanced settings for authors.
					'tracking', // Usage tracking.
					'enable_headless_rest_endpoints', // REST enpoint.
					'enable_enhanced_slack_sharing', // Slack sharing.
				];

				foreach ( $eventNames as $eventName ) {
					$yoastName = $prefixYoast.$eventName; // Yoast event names - starting with active-[YoastEventName]
					$wsalName = $prefixNetwork.$eventName; // internal use name - network-[YoastEventName]

					if ( isset( $old_value[ $yoastName ] ) && isset( $new_value[ $yoastName ] ) ) {
						if ( $old_value[ $yoastName ] !== $new_value[ $yoastName ] ) {

							$suffix = '-inactive';
							if ( ! $new_value[ $yoastName ] ) {
								$suffix = '-active';
							}

							$this->yoast_setting_switch_alert( $wsalName.$suffix, $new_value[ $yoastName ] );
						}
					}
				}
				if ( $old_value['access'] !== $new_value['access'] ) {
					$this->yoast_setting_change_alert( 'site-access-change', $old_value['access'], $new_value['access'] );
				}
				// We are aware the variables are being fed in backwards here, however this appears to be the only way.
				// to get a reliable outcome. Issue https://github.com/WPWhiteSecurity/activity-log-yoast-seo/issues/63.
				if ( $old_value['defaultblog'] !== $new_value['defaultblog'] ) {
					$this->yoast_setting_change_alert( 'site-default-seo-inherit-change', $new_value['defaultblog'], $old_value['defaultblog'] );
				}
			}
		}

		/**
		 * Method: Yoast SEO options trigger.
		 *
		 * @param string $option – Option name.
		 * @param mixed  $old_value – Option old value.
		 * @param mixed  $new_value – Option new value.
		 */
		public function yoast_options_trigger( $option, $old_value, $new_value ) {

			// Detect the SEO option.
			if ( 'wpseo_titles' === $option || 'wpseo' === $option || 'wpseo_social' === $option ) {
				// WPSEO Title Alerts.
				if ( 'wpseo_titles' === $option ) {
					// Redirect attachment URLs to the attachment itself.
					if ( $old_value['disable-attachment'] !== $new_value['disable-attachment'] ) {
						$this->yoast_setting_change_alert( 'disable-attachment', $old_value['disable-attachment'], $new_value['disable-attachment'] );
					}
					// Title Separator.
					if ( $old_value['separator'] !== $new_value['separator'] ) {
						$this->yoast_setting_change_alert( 'separator', $old_value['separator'], $new_value['separator'] );
					}

					// Homepage Title.
					if ( $old_value['title-home-wpseo'] !== $new_value['title-home-wpseo'] ) {
						$this->yoast_setting_change_alert( 'title-home-wpseo', $old_value['title-home-wpseo'], $new_value['title-home-wpseo'] );
					}

					// Homepage Meta Description.
					if ( $old_value['metadesc-home-wpseo'] !== $new_value['metadesc-home-wpseo'] ) {
						$this->yoast_setting_change_alert( 'metadesc-home-wpseo', $old_value['metadesc-home-wpseo'], $new_value['metadesc-home-wpseo'] );
					}

					// Company or Person.
					if ( $old_value['company_or_person'] !== $new_value['company_or_person'] ) {
						$this->yoast_setting_change_alert( 'company_or_person', $old_value['company_or_person'], $new_value['company_or_person'] );
					}

					// Author Archives.
					if ( $old_value['disable-author'] !== $new_value['disable-author'] ) {
						$this->yoast_setting_switch_alert( 'disable-author', $new_value['disable-author'] );
					}

					if ( $old_value['noindex-author-wpseo'] !== $new_value['noindex-author-wpseo'] ) {
						$this->yoast_setting_switch_alert( 'noindex-author-wpseo', $new_value['noindex-author-wpseo'] );
					}

					if ( $old_value['title-author-wpseo'] !== $new_value['title-author-wpseo'] ) {
						$this->yoast_setting_change_alert( 'title-author-wpseo', $old_value['title-author-wpseo'], $new_value['title-author-wpseo'] );
					}

					if ( $old_value['metadesc-author-wpseo'] !== $new_value['metadesc-author-wpseo'] ) {
						$this->yoast_setting_change_alert( 'metadesc-author-wpseo', $old_value['metadesc-author-wpseo'], $new_value['metadesc-author-wpseo'] );
					}

					// Date Archives.
					if ( $old_value['disable-date'] !== $new_value['disable-date'] ) {
						$this->yoast_setting_switch_alert( 'disable-date', $new_value['disable-date'] );
					}

					if ( $old_value['noindex-archive-wpseo'] !== $new_value['noindex-archive-wpseo'] ) {
						$this->yoast_setting_switch_alert( 'noindex-archive-wpseo', $new_value['noindex-archive-wpseo'] );
					}

					if ( $old_value['title-archive-wpseo'] !== $new_value['title-archive-wpseo'] ) {
						$this->yoast_setting_change_alert( 'title-archive-wpseo', $old_value['title-archive-wpseo'], $new_value['title-archive-wpseo'] );
					}

					if ( $old_value['metadesc-archive-wpseo'] !== $new_value['metadesc-archive-wpseo'] ) {
						$this->yoast_setting_change_alert( 'metadesc-archive-wpseo', $old_value['metadesc-archive-wpseo'], $new_value['metadesc-archive-wpseo'] );
					}

					// Get public post types.
					$post_types = get_post_types( array( 'public' => true ) );

					// For each post type check show, title, and description changes.
					foreach ( $post_types as $type ) {
						if ( isset( $old_value[ "noindex-$type" ] ) ) {
							// Show Post Type in search results.
							if ( $old_value[ "noindex-$type" ] !== $new_value[ "noindex-$type" ] ) {
								$this->yoast_setting_switch_alert( "noindex-$type", $new_value[ "noindex-$type" ] );
							}

							// Post Type Title Template.
							if ( $old_value[ "title-$type" ] !== $new_value[ "title-$type" ] ) {
								$this->yoast_setting_change_alert( "title-$type", $old_value[ "title-$type" ], $new_value[ "title-$type" ] );
							}

							// Post Type Meta Description Template.
							if ( $old_value[ "metadesc-$type" ] !== $new_value[ "metadesc-$type" ] ) {
								$this->yoast_setting_change_alert( "metadesc-$type", $old_value[ "metadesc-$type" ], $new_value[ "metadesc-$type" ] );
							}

							// Show Meta box.
							if ( $old_value[ "display-metabox-pt-$type" ] !== $new_value[ "display-metabox-pt-$type" ] ) {
								$this->yoast_setting_switch_alert( "display-metabox-pt-$type", $new_value[ "display-metabox-pt-$type" ] );
							}
						}
					}

					// Get taxonomy types.
					$taxonomy_types = get_taxonomies( array( 'public' => true ) );

					// Lets check each and see if anything has been changes.
					foreach ( $taxonomy_types as $type ) {
						if ( isset( $old_value[ "noindex-tax-$type" ] ) ) {
							// Show Post Type in search results.
							if ( $old_value[ "noindex-tax-$type" ] !== $new_value[ "noindex-tax-$type" ] ) {
								$this->yoast_setting_switch_alert( "noindex-tax-$type", $new_value[ "noindex-tax-$type" ] );
							}

							// Post Type Title Template.
							if ( $old_value[ "title-tax-$type" ] !== $new_value[ "title-tax-$type" ] ) {
								$this->yoast_setting_change_alert( "title-tax-$type", $old_value[ "title-tax-$type" ], $new_value[ "title-tax-$type" ] );
							}

							// Post Type Meta Description Template.
							if ( $old_value[ "metadesc-tax-$type" ] !== $new_value[ "metadesc-tax-$type" ] ) {
								$this->yoast_setting_change_alert( "metadesc-tax-$type", $old_value[ "metadesc-tax-$type" ], $new_value[ "metadesc-tax-$type" ] );
							}

							// Show Meta box.
							if ( $old_value[ "display-metabox-tax-$type" ] !== $new_value[ "display-metabox-tax-$type" ] ) {
								$this->yoast_setting_switch_alert( "display-metabox-tax-$type", $new_value[ "display-metabox-tax-$type" ] );
							}
						}
					}
				}

				// Webmaster URL alerts.
				if ( 'wpseo' === $option ) {
					// SEO analysis.
					if ( isset( $old_value['keyword_analysis_active'] ) && isset( $new_value['keyword_analysis_active'] ) ) {
						if ( $old_value['keyword_analysis_active'] !== $new_value['keyword_analysis_active'] ) {
							$this->yoast_setting_switch_alert( 'keyword_analysis_active', $new_value['keyword_analysis_active'] );
						}
					}

					// Readability analysis.
					if ( isset( $old_value['content_analysis_active'] ) && isset( $new_value['content_analysis_active'] ) ) {
						if ( $old_value['content_analysis_active'] !== $new_value['content_analysis_active'] ) {
							$this->yoast_setting_switch_alert( 'content_analysis_active', $new_value['content_analysis_active'] );
						}
					}

					// Cornerstone Content.
					if ( isset( $old_value['enable_cornerstone_content'] ) && isset( $new_value['enable_cornerstone_content'] ) ) {
						if ( $old_value['enable_cornerstone_content'] !== $new_value['enable_cornerstone_content'] ) {
							$this->yoast_setting_switch_alert( 'enable_cornerstone_content', $new_value['enable_cornerstone_content'] );
						}
					}

					// Text Link Counter.
					if ( isset( $old_value['enable_text_link_counter'] ) && isset( $new_value['enable_text_link_counter'] ) ) {
						if ( $old_value['enable_text_link_counter'] !== $new_value['enable_text_link_counter'] ) {
							$this->yoast_setting_switch_alert( 'enable_text_link_counter', $new_value['enable_text_link_counter'] );
						}
					}

					// XML Sitemaps.
					if ( isset( $old_value['enable_xml_sitemap'] ) && isset( $new_value['enable_xml_sitemap'] ) ) {
						if ( $old_value['enable_xml_sitemap'] !== $new_value['enable_xml_sitemap'] ) {
							$this->yoast_setting_switch_alert( 'enable_xml_sitemap', $new_value['enable_xml_sitemap'] );
						}
					}

					/**
					 * Ryte integration.
					 *
					 * NOTE: Reenamed in yoast plugin v13.2.
					 *
					 * @see: https://github.com/Yoast/wordpress-seo/pull/14123
					 */
					if ( isset( $old_value['ryte_indexability'] ) && isset( $new_value['ryte_indexability'] ) ) {
						if ( $old_value['ryte_indexability'] !== $new_value['ryte_indexability'] ) {
							$this->yoast_setting_switch_alert( 'ryte_indexability', $new_value['ryte_indexability'] );
						}
					}

					// Admin bar menu.
					if ( isset( $old_value['enable_admin_bar_menu'] ) && isset( $new_value['enable_admin_bar_menu'] ) ) {
						if ( $old_value['enable_admin_bar_menu'] !== $new_value['enable_admin_bar_menu'] ) {
							$this->yoast_setting_switch_alert( 'enable_admin_bar_menu', $new_value['enable_admin_bar_menu'] );
						}
					}

					// Advanced settings for authors.
					if ( isset( $old_value['disableadvanced_meta'] ) && isset( $new_value['disableadvanced_meta'] ) ) {
						if ( $old_value['disableadvanced_meta'] !== $new_value['disableadvanced_meta'] ) {
							$this->yoast_setting_switch_alert( 'disableadvanced_meta', $new_value['disableadvanced_meta'] );
						}
					}

					// Usage tracking.
					if ( isset( $old_value['tracking'] ) && isset( $new_value['tracking'] ) ) {
						if ( $old_value['tracking'] !== $new_value['tracking'] ) {
							$this->yoast_setting_switch_alert( 'tracking', $new_value['tracking'] );
						}
					}

					// REST enpoint.
					if ( isset( $old_value['enable_headless_rest_endpoints'] ) && isset( $new_value['enable_headless_rest_endpoints'] ) ) {
						if ( $old_value['enable_headless_rest_endpoints'] !== $new_value['enable_headless_rest_endpoints'] ) {
							$this->yoast_setting_switch_alert( 'enable_headless_rest_endpoints', $new_value['enable_headless_rest_endpoints'] );
						}
					}

					$search_engines = [ 'baiduverify', 'googleverify', 'msverify', 'yandexverify' ];

					foreach ( $search_engines as $search_engine ) {
						if ( $old_value[ $search_engine ] !== $new_value[ $search_engine ] ) {
							$this->yoast_setting_change_alert( $search_engine, $old_value[ $search_engine ], $new_value[ $search_engine ] );
						}
					}
				}

				// Social profile alerts.
				if ( 'wpseo_social' === $option ) {
					$this->yoast_social_profile_setting_change_alert( $old_value, $new_value );
				}
			}
		}

		/**
		 * Method: Trigger Yoast Setting Change Alerts.
		 *
		 * @param string $key – Setting key.
		 * @param string $old_value – Old setting value.
		 * @param string $new_value – New setting value.
		 */
		private function yoast_setting_change_alert( $key, $old_value, $new_value ) {
			// Return if key is empty.
			if ( empty( $key ) ) {
				return;
			}

			// Return if both old and new values are empty.
			if ( empty( $old_value ) && empty( $new_value ) ) {
				return;
			}

			// Alert arguments.
			$alert_args = array(
				'old' => $old_value, // Old value.
				'new' => $new_value, // New value.
			);

			// Find title-* in the key.
			if ( false !== strpos( $key, 'title-' ) ) {
				// Confirm if this is a taxonomy or not.
				if ( false !== strpos( $key, 'title-tax-' ) ) {
					$seo_post_type = $this->create_tidy_name( $key );

					// Set alert meta data.
					$alert_args['SEOPostType'] = $seo_post_type;
				} elseif ( false !== strpos( $key, 'title-author-' ) || false !== strpos( $key, 'title-archive-' ) ) {
					$seo_post_type = $this->create_tidy_name( $key );

					// Set alert meta data.
					$alert_args['archive_type'] = $seo_post_type;
				} else {
					$seo_post_type  = $this->create_tidy_name( $key );
					$seo_post_type .= 's';

					// Set alert meta data.
					$alert_args['SEOPostType'] = $seo_post_type;
				}
			}

			// Find metadesc-* in the key.
			if ( false !== strpos( $key, 'metadesc-' ) ) {
				// Confirm if this is a taxonomy or not.
				if ( false !== strpos( $key, 'metadesc-tax-' ) ) {
					$seo_post_type = $this->create_tidy_name( $key );
					// Set alert meta data.
					$alert_args['SEOPostType'] = $seo_post_type;
				} elseif ( false !== strpos( $key, 'metadesc-author-' ) || false !== strpos( $key, 'metadesc-archive-' ) ) {
					$seo_post_type  = $this->create_tidy_name( $key );
					$seo_post_type .= 's';
					// Set alert meta data.
					$alert_args['archive_type'] = $seo_post_type;
				} else {
					$seo_post_type  = $this->create_tidy_name( $key );
					$seo_post_type .= 's';

					// Set alert meta data.
					$alert_args['SEOPostType'] = $seo_post_type;
				}
			}

			// Set alert code to null initially.
			$alert_code = null;

			// Detect alert code for setting.
			switch ( $key ) {
				case 'separator':
					$alert_code = 8809;
					if ( class_exists( '\WPSEO_Option_Titles' ) ) {
						$titles = \WPSEO_Option_Titles::get_instance();
						$available_seperators = $titles->get_separator_options();
						$alert_args['old'] = $available_seperators[$alert_args['old']];
						$alert_args['new'] = $available_seperators[$alert_args['new']];
					}
					break;

				case 'metadesc-archive-wpseo':
				case 'metadesc-author-wpseo':
					$alert_code = 8836;
					break;

				case 'company_or_person':
					$alert_code = 8812;
					$alert_args['old'] = ucwords( $alert_args['old'] );
					$alert_args['new'] = ucwords( $alert_args['new'] );
					break;

				case strpos( $key, 'title-archive-' ):
				case strpos( $key, 'title-author-' ):
					$alert_code = 8835;
					break;

				case strpos( $key, 'title-tax-' ):
					$alert_code = 8831;
					break;

				case strpos( $key, 'title-' ):
					$alert_code = 8814;
					break;

				case strpos( $key, 'metadesc-tax-' ):
					$alert_code = 8832;
					$alert_args['old'] = ( ! empty( $alert_args['old'] ) ) ? $alert_args['old'] : __( 'Not provided', 'activity-log-wp-seo' );
					$alert_args['new'] = ( ! empty( $alert_args['new'] ) ) ? $alert_args['new'] : __( 'Not provided', 'activity-log-wp-seo' );
					break;

				case strpos( $key, 'metadesc-' ):
					$alert_code = 8822;
					$alert_args['old'] = ( ! empty( $alert_args['old'] ) ) ? $alert_args['old'] : __( 'Not provided', 'activity-log-wp-seo' );
					$alert_args['new'] = ( ! empty( $alert_args['new'] ) ) ? $alert_args['new'] : __( 'Not provided', 'activity-log-wp-seo' );
					break;

				case 'disable-attachment':
					$alert_code              = 8826;
					$alert_args['EventType'] = $new_value ? 'enabled' : 'disabled';
					break;

				case 'site-access-change' :
					$alert_code              = 8838;
					$alert_args['old'] = ucwords( $alert_args['old'] );
					$alert_args['new'] = ucwords( $alert_args['new'] );
					break;

				case 'site-default-seo-inherit-change' :
					$alert_code              = 8839;
					$alert_args['old'] = ( ! empty( $alert_args['old'] ) ) ? get_blog_details( $alert_args['old'] )->blogname : __( 'None', 'activity-log-wp-seo' );
					$alert_args['new'] = ( ! empty( $alert_args['new'] ) ) ? get_blog_details( $alert_args['new'] )->blogname : __( 'None', 'activity-log-wp-seo' );
					break;

				case 'site-default-options-change' :
					$alert_code              = 8840;
					$alert_args['old'] = get_blog_details( $alert_args['old'] )->blogname.' / '.$alert_args['old'];
					$alert_args['new'] = '';
					break;

				case 'baiduverify':
				case 'googleverify':
				case 'msverify':
				case 'yandexverify':
					$alert_code        = 8841;
					$alert_args['search_engine_type'] = ucwords( str_replace( 'verify', '', $key ) );

					if ( empty( $alert_args['old'] ) && ! empty( $alert_args['new'] ) ) {
						$event_type = 'added';
					} elseif ( empty( $alert_args['new'] ) && ! empty( $alert_args['old'] ) ) {
						$event_type = 'removed';
					} else {
						$event_type = 'modified';
					}

					$alert_args['EventType'] = $event_type;
					$alert_args['old']       = ( empty( $alert_args['old'] ) ) ? __( 'Not provided', 'activity-log-wp-seo' ) : $alert_args['old'];
					$alert_args['new']       = ( empty( $alert_args['new'] ) ) ? __( 'Not provided', 'activity-log-wp-seo' ) : $alert_args['new'];
					break;

				default:
					break;
			}

			// Trigger the alert.
			if ( ! empty( $alert_code ) ) {
				$this->plugin->alerts->Trigger( $alert_code, $alert_args );
			}
		}

		/**
		 * Method: Trigger Yoast Enable/Disable Setting Alerts.
		 *
		 * @param string $key – Setting index to alert.
		 * @param mixed  $new_value – Setting new value.
		 */
		private function yoast_setting_switch_alert( $key, $new_value ) {
			// If key is empty, then return.
			if ( empty( $key ) ) {
				return;
			}

			// Check and set status.
			$status = (int) $new_value;

			// Alert arguments.
			$alert_args = array();

			// Find noindex-* in the key.
			if ( false !== strpos( $key, 'noindex-' ) ) {
				// Check if its a taxonomy setting.
				if ( false !== strpos( $key, 'noindex-tax-' ) ) {
					$seo_post_type = $this->create_tidy_name( $key );
					// Set alert meta data.
					$alert_args['SEOPostType'] = $seo_post_type;
					$status                    = 1 === $status ? 0 : 1;
				} else {
					$seo_post_type  = $this->create_tidy_name( $key );

					// Set alert meta data.
					$alert_args['SEOPostType'] = $seo_post_type;
					$status                    = 1 === $status ? 0 : 1;
				}
			}

			// Find display-metabox-pt-* in the key.
			if ( false !== strpos( $key, 'display-metabox-tax-' ) ) {
				$seo_post_type = $this->create_tidy_name( $key );

				// Set alert meta data.
				$alert_args['SEOPostType'] = $seo_post_type;
			} else {
				$seo_post_type  = $this->create_tidy_name( $key );

				// Set alert meta data.
				$alert_args['SEOPostType'] = $seo_post_type;
			}

			$alert_args['EventType'] = 1 === $status ? 'enabled' : 'disabled';

			// Find network-* in the key.
			if ( false !== strpos( $key, 'network-' ) ) {
				$eventKey  = substr($key, strpos( $key, '-') +1 );

				$eventKey = substr($eventKey, 0, strrpos( $eventKey, '-'));

				switch ( $eventKey ) {
					default:
					case 'keyword_analysis_active':
						$featureName = __( 'SEO Analysis', 'activity-log-wp-seo' );
						break;
					case 'content_analysis_active':
						$featureName = __( 'Readability Analysis', 'activity-log-wp-seo' );
						break;
					case 'enable_cornerstone_content':
						$featureName = __( 'Cornerstone content', 'activity-log-wp-seo' );
						break;
					case 'enable_text_link_counter':
						$featureName = __( 'Text link counter', 'activity-log-wp-seo' );
						break;
					case 'enable_xml_sitemap':
						$featureName = __( 'XML sitemap', 'activity-log-wp-seo' );
						break;
					case 'enable_admin_bar_menu':
						$featureName = __( 'Admin bar menu', 'activity-log-wp-seo' );
						break;
					case 'disableadvanced_meta':
						$featureName = __( 'Security: advanced or schema settings for authors', 'activity-log-wp-seo' );
						break;
					case 'tracking':
						$featureName = __( 'Usage tracking', 'activity-log-wp-seo' );
						break;
					case 'enable_headless_rest_endpoints':
						$featureName = __( 'REST API: Head endpoint', 'activity-log-wp-seo' );
						break;
					case 'enable_enhanced_slack_sharing':
						$featureName = __( 'Slack sharing', 'activity-log-wp-seo' );
						break;
				}

				// Set alert meta data.
				$alert_args['feature_name'] = $featureName;
				$alert_args['EventType'] = 1 === $status ? 'disabled' : 'enabled';
			}

			// Set alert code to NULL initially.
			$alert_code = null;

			// Add switch case to set the alert code.
			switch ( $key ) {
				case 'noindex-author-wpseo':
				case 'noindex-archive-wpseo':
					$alert_code   = 8834;
					$archive_type = $this->create_tidy_name( $key );
					// If this is the "date archive" setting, update archive type to something more descriptive.
					if ( 'Archive' === $archive_type ) {
						$archive_type = __( 'Date', 'activity-log-wp-seo' );
					}
					// Set alert meta data.
					$alert_args['archive_type'] = $archive_type;
					break;

				case strpos( $key, 'noindex-tax-' ):
					$alert_code = 8830;
					break;

				case strpos( $key, 'noindex-' ):
					$alert_code = 8813;
					break;

				case 'keyword_analysis_active':
					$alert_code = 8815;
					break;

				case 'content_analysis_active':
					$alert_code = 8816;
					break;

				case 'enable_cornerstone_content':
					$alert_code = 8817;
					break;

				case 'enable_text_link_counter':
					$alert_code = 8818;
					break;

				case 'enable_xml_sitemap':
					$alert_code = 8819;
					break;

				case (false !== strpos( $key, 'network-' ) && false !== strpos( $key, '-inactive' )) :
					$alert_code = 8842;
					break;

				case (false !== strpos( $key, 'network-' ) && false !== strpos( $key, '-active' )) :
					$alert_code = 8843;
					break;

				// renamed to ryte_integration. see: https://github.com/Yoast/wordpress-seo/pull/14123.
				case 'onpage_indexability':
				case 'ryte_indexability':
					$alert_code = 8820;
					break;

				case 'enable_admin_bar_menu':
					$alert_code = 8821;
					break;

				case strpos( $key, 'display-metabox-pt-' ):
					$alert_code = 8824;
					break;

				case strpos( $key, 'display-metabox-tax-' ):
					$alert_code = 8837;
					// Avoid false reporting for post_format metabox.
					if ( 'display-metabox-tax-post_format' === $key ) {
						$alert_code = null;
					}
					break;

				case strpos( $key, 'disableadvanced_meta' ):
					$alert_code = 8825;
					break;

				case strpos( $key, 'tracking' ):
					$alert_code = 8827;
					break;

				case strpos( $key, 'enable_headless_rest_endpoints' ):
					$alert_code = 8828;
					break;

				case 'disable-author':
				case 'disable-date':
					$alert_code   = 8833;
					$archive_type = str_replace( 'disable-', '', $key );
					$archive_type = ucfirst( $archive_type );
					// Set alert meta data.
					$alert_args['archive_type'] = $archive_type;
					// Reverse logic for enabled/disabled.
					$alert_args['EventType'] = $new_value ? 'disabled' : 'enabled';
					break;

				default:
					break;
			}

			// Trigger the alert.
			if ( ! empty( $alert_code ) ) {
				$this->plugin->alerts->Trigger( $alert_code, $alert_args );
			}
		}

		/**
		 * Method: Trigger Yoast Social profile settings alerts.
		 *
		 * @param string $old_value – Setting old value.
		 * @param mixed  $new_value – Setting new value.
		 */
		private function yoast_social_profile_setting_change_alert( $old_value, $new_value ) {

			$alert_code = 8829;

			// Array of keys we want to look for.
			$profiles_to_monitor = array(
				'facebook_site',
				'instagram_url',
				'linkedin_url',
				'pinterest_url',
				'twitter_site',
				'youtube_url',
				'wikipedia_url',
			);

			foreach ( $old_value as $social_profile => $value ) {

				if ( in_array( $social_profile, $profiles_to_monitor, true ) && $old_value[ $social_profile ] !== $new_value[ $social_profile ] ) {
					$event_type = $this->determine_social_event_type( $old_value[ $social_profile ], $new_value[ $social_profile ] );
					$alert_args = array(
						'social_profile' => ucwords( substr( $social_profile, 0, strpos( $social_profile, '_' ) ) ),
						'old_url'        => empty( $old_value[ $social_profile ] ) ? ' ' : $old_value[ $social_profile ], // The empty string is intentional.
						'new_url'        => empty( $new_value[ $social_profile ] ) ? ' ' : $new_value[ $social_profile ], // The empty string is intentional.
						'EventType'      => $event_type,
					);
					$this->plugin->alerts->Trigger( $alert_code, $alert_args );
				}
			}
		}

		/**
		 * Helper function to check if a profile was added, removed or modified.
		 *
		 * @param  string $old_value Old profile value.
		 * @param  string $new_value New profile value.
		 * @return string            Our determination of whats happened
		 */
		private function determine_social_event_type( $old_value, $new_value ) {
			if ( ! empty( $old_value ) && empty( $new_value ) ) {
				return 'removed';
			} elseif ( empty( $old_value ) && ! empty( $new_value ) ) {
				return 'added';
			} else {
				return 'modified';
			}
		}

		/**
		 * Helper function to strip a string of any unwanted compontent.
		 *
		 * @param  string $text_to_strip String we want to work on.
		 * @return string $tidied_text   The actual string we want.
		 */
		private function create_tidy_name( $text_to_strip ) {
			$tidied_text = null;

			// Array of string we want to look for.
			$strings_to_remove = array(
				'title-',
				'tax-',
				'-wpseo',
				'metadesc-',
				'noindex-',
				'display-',
				'metabox-',
				'pt-',
				'disable-',
			);

			if ( ! empty( $text_to_strip ) ) {
				$tidied_text = str_replace( $strings_to_remove, '', $text_to_strip );
				$tidied_text = ucfirst( $tidied_text );

				// If this is the "date archive" setting, update archive type to something more descriptive.
				if ( 'Archive' === $tidied_text ) {
					$tidied_text = __( 'Date', 'activity-log-wp-seo' );
				}

				// If left unchanged, the alert reads "Categorys". The 's' is missing as its added later.
				if ( 'Category' === $tidied_text ) {
					$tidied_text = __( 'Categories', 'activity-log-wp-seo' );
				}

				return $tidied_text;
			}
		}
	}
}
