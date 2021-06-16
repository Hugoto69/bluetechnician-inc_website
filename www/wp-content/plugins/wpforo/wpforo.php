<?php
/*
* Plugin Name: wpForo
* Plugin URI: https://wpforo.com
* Description: WordPress Forum plugin. wpForo is a full-fledged forum solution for your community. Comes with multiple modern forum layouts.
* Author: gVectors Team
* Author URI: https://gvectors.com/
* Version: 1.9.6
* Text Domain: wpforo
* Domain Path: /wpf-languages
*/

//Exit if accessed directly
if( !defined( 'ABSPATH' ) ) exit;
if( !defined( 'WPFORO_VERSION' ) ) define('WPFORO_VERSION', '1.9.6');

function wpforo_load_plugin_textdomain() { load_plugin_textdomain( 'wpforo', FALSE, basename( dirname( __FILE__ ) ) . '/wpf-languages/' ); }
add_action( 'plugins_loaded', 'wpforo_load_plugin_textdomain' );

if( !class_exists( 'wpForo' ) ) {
	$wp_upload_dir = wp_upload_dir();
	define('WPFORO_DIR', rtrim( plugin_dir_path( __FILE__ ), '/' ));
	define('WPFORO_URL', rtrim( plugins_url( '', __FILE__ ), '/' ));
	define('WPFORO_FOLDER', rtrim( plugin_basename(dirname(__FILE__)), '/'));
	define('WPFORO_BASENAME', plugin_basename(__FILE__)); //wpforo/wpforo.php
	define('WPFORO_UPLOAD_DIR', $wp_upload_dir['basedir'] . "/wpforo" );
	define('WPFORO_CACHE_DIR', $wp_upload_dir['basedir'] . "/wpforo/cache" );

	final class wpForo{
	    private static $_instance = NULL;

	    public $file;
	    public $basename;
	    public $error;
		public $locale;

        public $tables;
        public $blog_prefix;
        public $prefix = "wpforo_";
        private $_tables = array( 'accesses', 'activity', 'forums', 'languages', 'likes', 'phrases', 'postmeta', 'posts', 'post_revisions', 'profiles',
            'subscribes', 'topics', 'tags', 'usergroups', 'views', 'visits', 'votes', 'logs' );
        public $upload_dir_folders = array();

        public $db;
		public $addons = array();
		public $current_url;
		public $GET;
		public $current_object;
		public $menu = array();
		public $data = array();
		public $default;

		public $wp_current_user = array();
		public $current_user = array();
		public $current_usermeta = array();
		public $current_user_groupid = 4;
		public $current_user_groupids = array(4);
		public $current_user_secondary_groupids = '';
		public $current_userid = 0;
		public $current_username = '';
		public $current_user_email = '';
		public $current_user_display_name = '';
		public $current_user_status = '';
		public $current_user_accesses = array();
		public $session_token = '';

		public $use_trailing_slashes;
		public $use_home_url;
		public $excld_urls;
        public $base_permastruct;
        public $permastruct;
        public $url;
        public $pageid;

        public $general_options;
        public $features;
        public $tools_antispam;
        public $tools_cleanup;
		public $tools_misc;
        public $tools_legal;

        public $sql_cache;
        public $ram_cache;
        public $action;
        public $cache;
        public $phrase;
        public $forum;
        public $topic;
        public $postmeta;
        public $post;
        public $usergroup;
        public $member;
        public $perm;
        public $sbscrb;
        public $tpl;
        public $notice;
		public $api;
        public $log;
        public $feed;
        public $form;
        public $moderation;
        public $activity;
        public $revision;
        public $seo;
        public $add;
        public $dissmissed;

        public $member_tpls;

        public static function instance(){
            if ( is_null(self::$_instance) ) self::$_instance = new self();
            return self::$_instance;
        }

		private	function __construct(){
            global $wpdb;
            $this->db = $wpdb;
            $this->file = __FILE__;
            $this->error = NULL;
            $this->locale = get_locale();
            $this->basename = plugin_basename($this->file);

            $this->init_db_tables();
            $this->includes();
            $this->init_defaults();
			$this->reset_current_object();
            $this->init_options();
            $this->setup();
            $this->init_hooks();

			$this->sql_cache  =
			$this->ram_cache  = new wpForoRamCache();
			$this->action     = new wpForoAction();
			$this->cache      = new wpForoCache();
			$this->phrase     = new wpForoPhrase();
			$this->forum      = new wpForoForum();
			$this->topic      = new wpForoTopic();
			$this->postmeta   = new wpForoPostMeta();
			$this->post       = new wpForoPost();
			$this->usergroup  = new wpForoUsergroup();
			$this->member     = new wpForoMember();
			$this->perm       = new wpForoPermissions();
			$this->sbscrb     = new wpForoSubscribe();
			$this->tpl        = new wpForoTemplate();
			$this->notice     = new wpForoNotices();
			$this->api        = new wpForoAPI();
			$this->log        = new wpForoLogs();
			$this->feed       = new wpForoFeed();
			$this->form       = new wpForoForm();
			$this->moderation = new wpForoModeration();
			$this->activity   = new wpForoActivity();
			$this->revision   = new wpForoRevision();
			$this->seo        = new wpForoSEO();
			$this->add        = new stdClass(); // Integrations
        }

		private function init_hooks(){
        	add_action('plugins_loaded', array($this, 'plugins_loaded'));
			if( is_admin() ){
				add_action('admin_init', array($this, 'admin_init'));
				add_action('admin_init', array($this, 'init'), 99);
			}else{
                add_action('init', array($this, 'init_hook'), 99);
				add_action('wp', array($this, 'init'), 99);
			}
			add_action('switch_blog', array($this, 'after_switch_blog'), 10, 2);
		}

		public function after_switch_blog($new_blog_id, $prev_blog_id){
			if( intval($new_blog_id) !== intval($prev_blog_id) ){
				$this->init_db_tables();
			}
		}

        public function init_db_tables($blog_id = 0){
        	$this->db->query("SET SESSION group_concat_max_len = 1000000");
            $blog_id = apply_filters('wpforo_current_blog_id', $blog_id);
            $this->tables = new stdClass;
            if(!$blog_id) $blog_id = $this->db->blogid;
            $this->blog_prefix = $this->db->get_blog_prefix( $blog_id );
            $this->_tables = apply_filters('wpforo_init_db_tables', $this->_tables);
            foreach ( $this->_tables as $table )
                $this->tables->$table = $this->fix_table_name($table);
        }

		/**
		 * @param string $basename
		 *
		 * @return string
		 */
		public function fix_table_name($basename){
        	return $this->blog_prefix . $this->prefix . $basename;
        }

        private function includes(){
            require_once( WPFORO_DIR . '/wpf-includes/wpf-hooks.php' );
            require_once( WPFORO_DIR . '/wpf-includes/functions.php' );
            require_once( WPFORO_DIR . '/wpf-includes/functions-integration.php' );
            require_once( WPFORO_DIR . '/wpf-includes/functions-template.php' );
	        require_once( WPFORO_DIR . '/wpf-includes/functions-installation.php' );

	        if( wpforo_is_admin() )  require_once( WPFORO_DIR .'/wpf-admin/admin.php' );

            require_once( WPFORO_DIR . '/wpf-includes/class-ramcache.php' );
            require_once( WPFORO_DIR . '/wpf-includes/class-actions.php' );
            require_once( WPFORO_DIR . '/wpf-includes/class-cache.php' );
            require_once( WPFORO_DIR . '/wpf-includes/class-forums.php' );
            require_once( WPFORO_DIR . '/wpf-includes/class-topics.php' );
            require_once( WPFORO_DIR . '/wpf-includes/class-postmeta.php' );
            require_once( WPFORO_DIR . '/wpf-includes/class-posts.php' );
            require_once( WPFORO_DIR . '/wpf-includes/class-usergroups.php' );
            require_once( WPFORO_DIR . '/wpf-includes/class-members.php' );
            require_once( WPFORO_DIR . '/wpf-includes/class-permissions.php' );
            require_once( WPFORO_DIR . '/wpf-includes/class-phrases.php');
            require_once( WPFORO_DIR . '/wpf-includes/class-subscribes.php' );
            require_once( WPFORO_DIR . '/wpf-includes/class-template.php' );
            require_once( WPFORO_DIR . '/wpf-includes/class-notices.php' );
            require_once( WPFORO_DIR . '/wpf-includes/class-logs.php' );
            require_once( WPFORO_DIR . '/wpf-includes/class-api.php' );
            require_once( WPFORO_DIR . '/wpf-includes/class-feed.php' );
            require_once( WPFORO_DIR . '/wpf-includes/class-forms.php' );
            require_once( WPFORO_DIR . '/wpf-includes/class-moderation.php' );
            require_once( WPFORO_DIR . '/wpf-includes/class-activity.php' );
            require_once( WPFORO_DIR . '/wpf-includes/class-revisions.php' );
            require_once( WPFORO_DIR . '/wpf-includes/class-seo.php' );
        }

        public function plugins_loaded(){
	        if ( wpforo_feature( 'disable_new_user_admin_notification' ) ) {
		        remove_action( 'after_password_reset', 'wp_password_change_notification' );

		        remove_action( 'register_new_user', 'wp_send_new_user_notifications' );
		        add_action( 'register_new_user', 'wpforo_send_new_user_notifications');

		        remove_action( 'edit_user_created_user', 'wp_send_new_user_notifications' );
		        add_action( 'edit_user_created_user', 'wpforo_send_new_user_notifications', 10, 2);

		        remove_action( 'network_site_new_created_user', 'wp_send_new_user_notifications' );
		        add_action( 'network_site_new_created_user', 'wpforo_send_new_user_notifications');

		        remove_action( 'network_site_users_created_user', 'wp_send_new_user_notifications' );
		        add_action( 'network_site_new_created_user', 'wpforo_send_new_user_notifications');

		        remove_action( 'network_user_new_created_user', 'wp_send_new_user_notifications' );
		        add_action( 'network_user_new_created_user', 'wpforo_send_new_user_notifications');
	        }
        }

        public function admin_init(){
            if( wpforo_is_admin() ){

                $this->check_database();

                if( strpos( wpforo_get_request_uri(), 'user-new.php' ) === false ){
	                $sql = "SELECT `groupid` FROM ". $this->tables->profiles ." WHERE `userid` = " . wpforo_bigintval($this->current_userid);
	                if( !$current_groupid = $this->db->get_var($sql) ){
		                $this->member->synchronize_user($this->current_userid);
	                }
                }

                if( !$this->forum->manage() && wpforo_current_user_is('admin') ){
                	$this->member->set_usergroup($this->current_userid, 1);
                }
            }
        }

        public function check_database(){

            //Make sure all users profiles are created
            if( !wpforo_feature('user-synch') && get_option('wpforo_version') ){
                $users = $this->db->get_var("SELECT COUNT(*) FROM `".$this->db->users."`");
                $profiles = $this->db->get_var("SELECT COUNT(*) FROM `" . $this->tables->profiles."`");
                $delta = $users - $profiles;
                if( $users > 100 && $delta > 2 ){ add_action( 'admin_notices', 'wpforo_profile_notice', 10 ); }
            }
            //Make sure tables structures are correct for current version
            $wpforo_version_db = get_option('wpforo_version_db');
            if( !$wpforo_version_db || version_compare( $wpforo_version_db, WPFORO_VERSION, '<') ){
                if( 'tables' != wpfval($_GET, 'view') ){
                    $db_note = false;
                    $problems = wpforo_database_check();
                    if( !empty($problems) ) {
                        foreach( $problems as $table_name => $problem ){
                            if( wpfval($problem, 'fields') ) $db_note = true;
                            if( wpfval($problem, 'exists') ) $db_note = true;
                        }
                        if( $db_note ) {
                            add_action( 'admin_notices', 'wpforo_database_notice', 10 );
                        }
                    } else {
                        update_option('wpforo_version_db', WPFORO_VERSION );
                    }
                }
            }
        }

		public	function init(){
			do_action( 'wpforo_before_init' );

			$this->phrase->init();

			$this->perm->init();
			$this->perm->init_current_user_accesses();

			$this->init_current_url();
			$this->init_current_object();

            $this->moderation->init();
            $this->tpl->init();
            //reCAPTCHA on wpForo pages
            $this->api->hooks();

			do_action( 'wpforo_after_init' );
        }

        public function init_hook(){
            //reCAPTCHA on wp-login.php page
            $this->api->init_wp_recaptcha();
        }

		public function shortcode_atts_to_url($atts){
			$url = wpforo_home_url();

			$args = shortcode_atts( array(
				'item' => 'forum',
				'id'   => 0,
				'slug' => '',
			), (array) $atts );

			if( $args['item'] === 'profile' && !$args['id'] ) $args['id'] = $this->current_userid;

			if( $args['item'] === 'add-topic' ){
				$forum = $this->forum->get_forum( ( $args['slug'] ? $args['slug'] : $args['id'] ) );
				$forumid = (int) wpfval($forum, 'is_cat') ? 0 : (int) wpfval($forum, 'forumid');
				$url = wpforo_home_url( wpforo_get_template_slug('add-topic') . '/' . (int) $forumid );
			}elseif( $args['item'] === 'recent' ){
				$url = wpforo_get_template_slug('recent') . '/';
				if( trim($args['slug']) ) $url .= '?view=' . wpforo_get_template_slug($args['slug']);
				$url = wpforo_home_url($url);
			}elseif( $args['id'] || $args['slug'] ){
				$getid = ( $args['slug'] ? $args['slug'] : $args['id'] );
				if( $args['item'] === 'topic' ){
					$url = $this->topic->get_topic_url($getid);
				}elseif( $args['item'] === 'profile' ){
					$url = $this->member->get_profile_url($getid);
				}else{
					$url = $this->forum->get_forum_url($getid);
				}
			}elseif( $args['item'] === 'signin' ){
				$url = wpforo_home_url('?foro=signin');
			}elseif( $args['item'] === 'signup' ){
				$url = wpforo_home_url('?foro=signup');
			}elseif( $args['item'] === 'lostpassword' ){
				$url = wpforo_home_url('?foro=lostpassword');
			}

			return $url;
        }

        public function init_current_url($atts = array()){
	        $url = wpforo_get_request_uri();
            if( $atts || is_wpforo_shortcode_page($url) ){
                if( $atts || ($atts = get_wpforo_shortcode_atts('', $url)) ){
	                $url = $this->shortcode_atts_to_url($atts);
                }else{
	                $url = wpforo_home_url();
                }
            }elseif( is_wpforo_url($url) && preg_match('#/'.preg_quote(wpforo_get_template_slug('postid')).'/(\d+)/?$#isu', strtok($url, '?'), $matches) ){
            	$post_url = $this->post->get_post_url($matches[1]);
            	if( $post_url !== wpforo_home_url() ) $url = $post_url;
            }

	        $url = wpforo_fix_url($url);
	        $url = preg_replace('#\#[^/?&]*$#isu', '', $url);
	        parse_str( parse_url($url, PHP_URL_QUERY), $get );
	        $get = array_merge( (array) $get, (array) $_GET );
	        $get = wp_unslash($get);

	        $this->current_url = apply_filters('wpforo_init_current_url', $url);
	        $this->GET         = apply_filters('wpforo_init_current_url_GET', $get);
        }

		private function init_defaults() {
			date_default_timezone_set( 'UTC' );
			ini_set( 'date.timezone', 'UTC' );

			$this->default = new stdClass;

			$this->default->use_home_url = 0;
			$this->default->excld_urls   = '';
			$this->default->permastruct  = 'community';

			$this->default->current_object = array(
				'template'                  => '',
				'qvars'                     => array(),
				'args'                      => array(),
				'layout'                    => 1,
				'og_text'                   => '',
				'paged'                     => 1,
				'items_count'               => 0,
				'items_per_page'            => 15,
				'is_404'                    => false,
				'user_is_same_current_user' => false,
				'orderby'                   => null,
				'members'                   => array(),
				'categories'                => array(),
				'topics'                    => array(),
				'posts'                     => array(),
				'user'                      => array(),
				'userid'                    => 0,
				'user_nicename'             => '',
				'forum'                     => array(),
				'forumid'                   => 0,
				'forum_slug'                => '',
				'forum_desc'                => '',
				'forum_meta_key'            => '',
				'forum_meta_desc'           => '',
				'topic'                     => array(),
				'topicid'                   => 0,
				'topic_slug'                => '',
				'tags'                      => array(),
				'load_tinymce'              => false
			);

			$blogname                       = get_option( 'blogname', '' );
			$this->default->general_options = array(
				'title'       => $blogname . ' ' . __( 'Forum', 'wpforo' ),
				'description' => $blogname . ' ' . __( 'Discussion Board', 'wpforo' ),
				'lang'        => 1
			);

			$this->default->features = array(
				'user-admin-bar'                      => 0,
				'page-title'                          => 1,
				'top-bar'                             => 1,
				'top-bar-search'                      => 1,
				'breadcrumb'                          => 1,
				'footer-stat'                         => 1,
				'notifications'                       => 1,
				'notifications-live'                  => 0,
				'notifications-bar'                   => 1,
				'mention-nicknames'                   => 1,
				'content-do_shortcode'                => 0,
				'view-logging'                        => 1,
				'track-logging'                       => 1,
				'goto-unread'                         => 1,
				'goto-unread-button'                  => 0,
				'profile'                             => 1,
				'user-register'                       => 1,
				'user-register-email-confirm'         => 1,
				'disable_new_user_admin_notification' => 1,
				'register-url'                        => 0,
				'login-url'                           => 0,
				'resetpass-url'                       => 1,
				//In most cases incompatible with security and antispam plugins
				'replace-avatar'                      => 1,
				'avatars'                             => 1,
				'custom-avatars'                      => 1,
				'signature'                           => 1,
				'rating'                              => 1,
				'rating_title'                        => 1,
				'member_cashe'                        => 1,
				'object_cashe'                        => 1,
                'option_cache'                        => 1,
				'html_cashe'                          => 0,
				'memory_cashe'                        => 1,
				'seo-title'                           => 1,
				'seo-meta'                            => 1,
				'seo-profile'                         => 1,
				'rss-feed'                            => 1,
				'font-awesome'                        => 1,
				'bp_activity'                         => 1,
				'bp_notification'                     => 1,
				'bp_forum_tab'                        => 1,
				'um_forum_tab'                        => 1,
				'um_notification'                     => 1,
				'user-synch'                          => 0,
				'role-synch'                          => 1,
				'output-buffer'                       => 1,
				'wp-date-format'                      => 0,
				'subscribe_conf'                      => 1,
				'subscribe_checkbox_on_post_editor'   => 1,
				'subscribe_checkbox_default_status'   => 0,
				'attach-media-lib'                    => 1,
				'admin-cp'                            => 1,
				'debug-mode'                          => 0,
				'copyright'                           => 1
			);

			$this->default->tools_antispam = array(
				'spam_filter'                   => 1,
				'spam_filter_level_topic'       => mt_rand( 30, 60 ),
				'spam_filter_level_post'        => mt_rand( 30, 60 ),
				'spam_user_ban'                 => 0,
				'new_user_max_posts'            => 3,
				'unapprove_post_if_user_is_new' => 0,
				'spam_user_ban_notification'    => 1,
				'min_number_post_to_attach'     => 0,
				'min_number_post_to_link'       => 0,
				'spam_file_scanner'             => 1,
				'limited_file_ext'              => 'pdf|doc|docx|txt|htm|html|rtf|xml|xls|xlsx|zip|rar|tar|gz|bzip|7z',
				'exclude_file_ext'              => 'pdf|doc|docx|txt',
				'rc_site_key'                   => '',
				'rc_secret_key'                 => '',
				'rc_theme'                      => 'light',
				'rc_login_form'                 => 0,
				'rc_reg_form'                   => 0,
				'rc_lostpass_form'              => 0,
				'rc_wpf_login_form'             => 1,
				'rc_wpf_reg_form'               => 1,
				'rc_wpf_lostpass_form'          => 1,
				'rc_topic_editor'               => 1,
				'rc_post_editor'                => 1,
				'html'                          => 'embed(src width height name pluginspage type wmode allowFullScreen allowScriptAccess flashVars),'
			);

			$this->default->tools_cleanup = array(
				'user_reg_days_ago'  => 7,
				'auto_cleanup_users' => 0,
				'usergroup'          => array( 1 => '0', 5 => '0', 2 => '1', 3 => '0' )
			);

			$this->default->tools_misc = array(
				'dofollow'          => '',
				'noindex'           => '',
				'admin_note'        => '',
				'admin_note_groups' => array( 1, 2, 3, 4, 5 ),
				'admin_note_pages'  => array( 'forum' )
			);

			$this->default->tools_legal = array(
				'rules_checkbox'          => 0,
				'rules_text'              => null,
				'page_terms'              => '',
				'page_privacy'            => '',
				'forum_privacy_text'      => null,
				'checkbox_terms_privacy'  => 0,
				'checkbox_email_password' => 1,
				'checkbox_forum_privacy'  => 0,
				'checkbox_fb_login'       => 1,
				'contact_page_url'        => null,
				'cookies'                 => 1
			);

			$this->default->stats = array(
				'forums'                    => 0,
				'topics'                    => 0,
				'posts'                     => 0,
				'members'                   => 0,
				'online_members_count'      => 0,
				'last_post_title'           => '',
				'last_post_url'             => '',
                'newest_member'             => array(),
                'newest_member_dname'       => '',
				'newest_member_profile_url' => ''
			);

			$this->default->dissmissed = array(
				'recaptcha_backend_note' => 0,
				'recaptcha_note'         => 0,
				'addons_css_update'      => 0
			);
		}

		private function reset_current_object(){
			$this->current_object = $this->default->current_object;
		}
		
		private function init_options(){
			$permalink_structure = get_option('permalink_structure');
			
			$this->use_trailing_slashes = ( '/' == substr($permalink_structure, -1, 1) );

			//OPTIONS
			$this->use_home_url = get_wpf_option('wpforo_use_home_url', $this->default->use_home_url);
			$this->excld_urls = get_wpf_option('wpforo_excld_urls', $this->default->excld_urls);

			$this->permastruct = trim( get_wpf_option('wpforo_permastruct', $this->default->permastruct), '/' );
			$this->permastruct = preg_replace('#^/?index\.php/?#isu', '', $this->permastruct);
			$this->permastruct = trim($this->permastruct, '/');

			$this->base_permastruct = (!$this->use_home_url ? $this->permastruct : '');
			$this->base_permastruct = rtrim( ( strpos($permalink_structure, 'index.php') !== FALSE ? '/index.php/' . $this->base_permastruct : '/' . $this->base_permastruct ), '/\\' );
			$this->url = esc_url( home_url( $this->user_trailingslashit($this->base_permastruct) ) );
            $this->pageid = get_wpf_option( 'wpforo_pageid', 0);

            $this->general_options = get_wpf_option( 'wpforo_general_options', $this->default->general_options);
            $this->features = get_wpf_option('wpforo_features', $this->default->features);
            $fp = intval($this->features['profile']);
			if( ($fp === 3 && !class_exists('BP_Component')) || ($fp === 4 && !function_exists('UM')) ){
				$this->features['profile'] = 1;
				update_option('wpforo_features', array_map( 'intval', $this->features));
				wpforo_clean_cache('option');
			}
            $this->tools_antispam = get_wpf_option('wpforo_tools_antispam', $this->default->tools_antispam);
            $this->tools_cleanup = get_wpf_option('wpforo_tools_cleanup', $this->default->tools_cleanup);
			$this->tools_misc = get_wpf_option('wpforo_tools_misc', $this->default->tools_misc);
            $this->tools_legal = get_wpf_option('wpforo_tools_legal', $this->default->tools_legal);

            $this->dissmissed = get_wpf_option('wpforo_dissmissed', $this->default->dissmissed);

            //CONSTANTS
            define('WPFORO_BASE_PERMASTRUCT', $this->base_permastruct );
            define('WPFORO_BASE_URL', $this->url );
        }

        private function setup(){
	        register_activation_hook($this->basename, 'do_wpforo_activation');
	        register_deactivation_hook($this->basename, 'do_wpforo_deactivation');
        }
		
		public function user_trailingslashit($url) {
			$rtrimed_url = '';
			$url_append_vars = '';
			if( preg_match('#^(.+?)(/?[?&].*)?$#isu', $url, $match) ){
				if( wpfval($match, 1) ) $rtrimed_url = rtrim($match[1], '/\\');
				if( wpfval($match, 2) ) $url_append_vars = '?' . trim($match[2], '?&/\\');
				if( $rtrimed_url ) {
					$home_url = rtrim( preg_replace('#/?\?.*$#isu', '', home_url()), '/\\' );
					if( $rtrimed_url == $home_url ){
						$url = $rtrimed_url . '/';
					}else{
						$url = ( $this->use_trailing_slashes ? $rtrimed_url . '/' : $rtrimed_url );
					}
				}
			}
			return $url . $url_append_vars;
		}

		public function strip_url_paged_var($url){
        	$patterns = array(
        		'#/'. preg_quote( wpforo_get_template_slug('paged') ) .'/?\d*/?#isu',
		        '#[\&\?]wpfpaged=\d*#isu'
	        );
        	$url = preg_replace($patterns, '', $url);
			$url = $this->user_trailingslashit($url);
			return $url;
		}
		
		public function statistic( $mode = 'get', $template = 'all' ){
        	$key = 'wpforo_stat_' . $this->current_user_groupid;
			if( $mode === 'get' ){
				if( $cached_stat = get_option($key) ){
                    $cached_stat['online_members_count'] = $this->member->online_members_count();
                    if( wpfval($cached_stat, 'forums') && wpfval($cached_stat, 'topics') && wpfval($cached_stat, 'posts') ){
	                    $cached_stat = wpforo_array_args_cast_and_merge($cached_stat, $this->default->stats);
                        return $cached_stat;
                    }
				}
			}

			if( $mode === 'get' || $template === 'all' ) {
                $stats['forums'] = $this->forum->get_count( array('is_cat' => 0) );
                $stats['topics'] = $this->topic->get_count();
                $stats['posts'] = $this->post->get_count();
                $member_status = array( 'p.`status`' => apply_filters('wpforo_display_members_status', array('active')));
                $stats['members'] = $this->member->get_count( $member_status );
                $stats['online_members_count'] = $this->member->online_members_count();
                $row_count = apply_filters('wpforo_get_statistic_row_count', 20);

                $posts = $this->topic->get_topics(array('orderby' => 'modified', 'order' => 'DESC', 'row_count' => $row_count, 'private' => 0, 'status' => 0, 'permgroup' => 4 ));
				$first = key($posts);
                if ( isset($posts[$first]) && !empty($posts[$first]) && $this->perm->forum_can('vf', $posts[$first]['forumid']) ) {
                    $stats['last_post_title'] = $posts[$first]['title'];
                    $stats['last_post_url'] = $this->post->get_post_url($posts[$first]['last_post']);
                }

                $members = $this->member->get_members(array('orderby' => 'userid', 'status' => array('active'), 'order' => 'DESC', 'row_count' => 1, 'groupids' => $this->usergroup->get_visible_usergroup_ids()));
                if (isset($members[0]) && !empty($members[0])) {
                    $members[0]['profile_url'] = $this->member->profile_url($members[0]);
                    $stats['newest_member'] = $members[0];
                    $stats['newest_member_dname'] = wpforo_user_dname($members[0]);
                    $stats['newest_member_profile_url'] = $members[0]['profile_url'];
                }
            }else{
                $stats = get_wpf_option($key, $this->default->stats);
                switch ($template){
                    case 'forum':
                        $stats['forums'] = $this->forum->get_count( array('is_cat' => 0) );
                    break;
                    case 'topic':
                        $stats['topics'] = $this->topic->get_count();
                        $posts = $this->topic->get_topics(array('orderby' => 'modified', 'order' => 'DESC', 'row_count' => 1));
                        if ( isset($posts[0]) && !empty($posts[0]) && $this->perm->forum_can('vf', $posts[0]['forumid']) ) {
                            $stats['last_post_title'] = $posts[0]['title'];
                            $stats['last_post_url'] = $this->post->get_post_url($posts[0]['last_post']);
                        }
                    break;
                    case 'post':
                        $stats['posts'] = $this->post->get_count();
                        $posts = $this->topic->get_topics(array('orderby' => 'modified', 'order' => 'DESC', 'row_count' => 1));
                        if ( isset($posts[0]) && !empty($posts[0]) && $this->perm->forum_can('vf', $posts[0]['forumid']) ) {
                            $stats['last_post_title'] = $posts[0]['title'];
                            $stats['last_post_url'] = $this->post->get_post_url($posts[0]['last_post']);
                        }
                    break;
                    case 'user':
	                    $member_status = array( 'p.`status`' => apply_filters('wpforo_display_members_status', array('active')));
	                    $stats['members'] = $this->member->get_count( $member_status );
                        $stats['online_members_count'] = $this->member->online_members_count();

                        $members = $this->member->get_members(array('orderby' => 'userid', 'order' => 'DESC', 'row_count' => 1, 'groupids' => $this->usergroup->get_visible_usergroup_ids()));
                        if (isset($members[0]) && !empty($members[0])) {
                            $members[0]['profile_url'] = WPF()->member->profile_url($members[0]);
                            $stats['newest_member'] = $members[0];
                            $stats['newest_member_dname'] = wpforo_user_dname($members[0]);
                            $stats['newest_member_profile_url'] = $members[0]['profile_url'];
                        }
                    break;
                }
            }

			$stats = apply_filters('wpforo_get_statistic_array_filter', $stats);
			$stats = wpforo_array_args_cast_and_merge($stats, $this->default->stats);
			update_option( $key, $stats );
            return $stats;
        }

		public function init_current_template(){

        }
		
		public function init_current_object(){
			$this->current_object['items_per_page'] = $this->post->get_option_items_per_page();
			$url = $this->current_url;
			$get = $this->GET;

			if( !is_wpforo_page($url) ) return;

			$current_url = wpforo_get_url_query_vars_str($url);
			
			if( $this->use_home_url ) $this->permastruct = '';

			$current_object = array();
			if( wpfkey($get, 'wpfs') || wpfval($get, 'foro') === 'search' ) $current_object['template'] = 'search';
			if( wpfval($get, 'wpforo') || wpfval($get, 'foro') ){
				$request = ( wpfval($get, 'wpforo') ) ? wpfval($get, 'wpforo') : wpfval($get, 'foro');
				switch( $request ){
					case 'signup':
						if(!is_user_logged_in()) {
							$this->data['template'] = $current_object['template'] = 'register';
							$this->data['value']['user_login'] = sanitize_user((string) wpfval($_POST, 'wpfreg', 'user_login'));
							$this->data['value']['user_email'] = sanitize_email((string) wpfval($_POST, 'wpfreg', 'user_email'));
							$this->data['varname'] = 'wpfreg';
						}
					break;
					case 'signin':
						if(!is_user_logged_in()) $current_object['template'] = 'login';
					break;
					case 'lostpassword':
						if(!is_user_logged_in()) $current_object['template'] = 'lostpassword';
					break;
					case 'resetpassword':
                        $current_object['template'] = 'resetpassword';
					break;
                    case 'page':
                        $current_object['template'] = 'page';
                        break;
					case 'logout':
						wp_logout();
						wp_redirect( wpforo_home_url( preg_replace('#\?.*$#is', '', wpforo_get_request_uri()) ) );
						exit();
					break;
				}
			}
			
			$wpf_url = preg_replace( '#^/?'.preg_quote($this->permastruct).'#isu', '' , $current_url, 1 );
			$wpf_url = preg_replace('#/?\?.*$#isu', '', $wpf_url);
			$wpf_url_parse = array_filter( explode('/', trim($wpf_url, '/')) );
			$wpf_url_parse = array_reverse($wpf_url_parse);

			if(in_array(wpforo_get_template_slug('paged'), $wpf_url_parse)){
				foreach($wpf_url_parse as $key => $value){
					if( $value === wpforo_get_template_slug('paged')){
						unset($wpf_url_parse[$key]);
						break;
					}
					if(is_numeric($value)) $paged = intval($value);
					
					unset($wpf_url_parse[$key]);
				}
			}
			if( $_paged = intval( wpfval($get, 'wpfpaged') ) ) $paged = $_paged;
			$current_object['paged'] = (isset($paged) && $paged > 0) ? $paged : 1;
			$current_object['orderby'] = wpfval($get, 'orderby');

			$wpf_url_parse = array_values($wpf_url_parse);

			if( !wpfval($current_object, 'template') ){
				$current_object = apply_filters('wpforo_before_init_current_object', $current_object, $wpf_url_parse);
			}

			if( !wpfval($current_object, 'template') ){
				if( $templates = $this->tpl->get_templates_list() ){
					$__slug = end($wpf_url_parse);
					foreach ( $templates as $template ){
						if( $__slug === wpforo_get_template_slug($template) ){
							$current_object['template'] = $template;
							$current_object['qvars'] = $wpf_url_parse;
							array_pop($current_object['qvars']);
							$current_object['qvars'] = array_reverse($current_object['qvars']);
							break;
						}
					}
				}
			}

			if( !wpfval($current_object, 'template') ){
				$current_object['template'] = 'forum';
				$this->data['varname'] = 'topic';
				if( isset($wpf_url_parse[0]) ){
					if( isset($wpf_url_parse[1]) ){
						$current_object['topic_slug'] = $wpf_url_parse[0];
						$current_object['forum_slug'] = $wpf_url_parse[1];
						$current_object['template'] = 'post';
						$this->data['varname'] = 'post';
					}else{
						$current_object['forum_slug'] = $wpf_url_parse[0];
						$current_object['template'] = 'topic';
						$this->data['varname'] = 'topic';
					}
				}
			}

			$current_object = apply_filters('wpforo_after_init_current_template', $current_object, $wpf_url_parse, $get);

			if( wpfval($current_object, 'template') ){
				if( !wpfval($current_object, 'userid') && !wpfval($current_object, 'user_nicename') && wpforo_is_member_template($current_object['template']) ){
					if( $qvar0 = wpfval($current_object['qvars'], 0) ){
						if( $this->member->options['url_structure'] === 'id' ) {
							$current_object['userid'] = $qvar0;
						}else {
							$current_object['user_nicename'] = $qvar0;
						}
					}else{
						if($this->current_userid){
							$current_object['userid'] = $this->current_userid;
						}else{
							wp_redirect( wpforo_login_url() );
							exit();
						}
					}
				}elseif($current_object['template'] === 'search'){
					$args = array(
						'needle' => sanitize_text_field( wpfval($get, 'wpfs') ),
						'type' => sanitize_text_field( wpfval($get, 'wpfin') ),
						'date_period' => intval( wpfval($get, 'wpfd') ),
						'forumids' => (array) wpfval($get, 'wpff'),
						'offset' => ($current_object['paged'] - 1) * $this->current_object['items_per_page'],
						'row_count' => $this->current_object['items_per_page'],
						'orderby' => 'relevancy',
						'order' => 'desc',
						'postids' => array()
					);
					if( !empty($get['wpfob']) ){
						$args['orderby'] = sanitize_text_field( $get['wpfob'] );
					}elseif( in_array(wpfval( $args, 'type' ), array('tag','user-posts','user-topics'), true) ) {
						$args['orderby'] = 'date';
					}
					$wpfo = strtolower(wpfval($get, 'wpfo'));
					if( in_array($wpfo, array('asc','desc'), true) ) $args['order'] = $wpfo;
					$sdata = array_filter( (array) wpfval($get, 'data') );
					$args['postids'] = WPF()->postmeta->search($sdata);
					$current_object['args'] = $args;
					if( $sdata && !$args['postids'] ){
						$current_object['items_count'] = 0;
						$current_object['posts'] = array();
					}else{
						$current_object['posts'] = WPF()->post->search($args, $current_object['items_count']);
					}
				}elseif($current_object['template'] === 'recent'){
					$current_object['items_per_page'] = $this->post->options['topics_per_page'];
				}elseif($current_object['template'] === 'tags'){
					$current_object['items_per_page'] = $this->post->options['tags_per_page'];
					$args = array(
						'offset' => ($current_object['paged'] - 1) * $current_object['items_per_page'],
						'row_count' => $current_object['items_per_page']
					);
					$current_object['tags'] = $this->topic->get_tags($args, $current_object['items_count']);
				}elseif($current_object['template'] === 'members'){
					$current_object['items_per_page'] = $this->member->options['members_per_page'];

					$this->data['template'] = 'members';
					$this->data['value'] = $get;
					$this->data['varname'] = '';

					if( !empty($get['_wpfms']) ){
						$users_include = array();
						$search_fields_names = $this->member->get_search_fields_names(false);

						$wpfms = (isset($get['wpfms'])) ? sanitize_text_field($get['wpfms']) : '';
						if($wpfms){
							$users_include = $this->member->search($wpfms, $search_fields_names);
						}else{
							if( $filters = array_filter($get, function($v){return !( is_null($v) || $v === false || $v === '' );}) ){
								$filters = array_merge( array_filter((array) wpfval($get, 'data'), function($v){return !( is_null($v) || $v === false || $v === '' );}), $filters );
								unset($filters['data']);
								$args = array();
								foreach ($filters as $filter_key => $filter){
									if( in_array($filter_key, (array) $search_fields_names) ){
										$args[$filter_key] = $filter;
									}
								}
								$users_include = $this->member->filter($args);
							}
						}

						$users_include = apply_filters('wpforo_member_search_users_include', $users_include);
					}
					$member_status = apply_filters('wpforo_display_members_status', array('active'));
					$args = array(
						'offset' => ($current_object['paged'] - 1) * $current_object['items_per_page'],
						'row_count' => $current_object['items_per_page'],
						'orderby' => 'posts',
						'order' => 'DESC',
						'status' => $member_status,
						'groupids' => $this->usergroup->get_visible_usergroup_ids()
					);
					if(!empty($users_include)) $args['include'] = $users_include;
					$current_object['members'] = $this->member->get_members($args, $current_object['items_count']);
					if(isset($users_include) && empty($users_include)){ $current_object['members'] = array(); $current_object['items_count'] = 0; }
				}elseif($current_object['template'] === 'add-topic'){
					if( $qvar0 = (int) wpfval($current_object['qvars'], 0) ){
						$forum = (array) $this->forum->get_forum($qvar0);
						if( !$forum || (int) wpfval($forum, 'is_cat') ){
							wp_redirect( wpforo_home_url( wpforo_get_template_slug('add-topic') ), 301 );
							exit();
						}
					}
				}
			}

			if( wpfval($current_object, 'userid') || wpfval($current_object, 'user_nicename') ){
				$args = array();
				if(isset($current_object['userid'])) $args['userid'] = $current_object['userid'];
				if(isset($current_object['user_nicename'])) $args['user_nicename'] = $current_object['user_nicename'];
				$selected_user = $this->member->get_member($args);
				if(isset($current_object['userid']) && empty($selected_user)) $selected_user = $this->member->get_member(array('user_nicename' => $current_object['userid']));
				if(!empty($selected_user)){
					$current_object['user'] = $selected_user;
					$current_object['userid'] = $selected_user['ID'];
					$current_object['user_nicename'] = $selected_user['user_nicename'];
                    $current_object['user_is_same_current_user'] = !empty($this->current_userid) && $selected_user['ID'] == $this->current_userid;

                    if( $this->tpl->can_view_template($current_object['template'], $current_object['user']) ){
	                    switch($current_object['template']){
		                    case 'activity':
			                    $args = array(
				                    'offset' => ($current_object['paged'] - 1) * $this->current_object['items_per_page'],
				                    'row_count' => $this->current_object['items_per_page'],
				                    'userid' => $current_object['userid'],
				                    'orderby' => '`created` DESC, `postid` DESC',
				                    'check_private' => true
			                    );
			                    $current_object['items_count'] = 0;
			                    $current_object['activities'] = $this->post->get_posts( $args, $current_object['items_count']);
			                    break;
		                    case 'subscriptions':
			                    $args = array(
				                    'offset' => ($current_object['paged'] - 1) * $this->current_object['items_per_page'],
				                    'row_count' => $this->current_object['items_per_page'],
				                    'userid' => $current_object['userid'],
				                    'order' => 'DESC'
			                    );
			                    $current_object['items_count'] = 0;
			                    $current_object['subscribes'] = $this->sbscrb->get_subscribes( $args, $current_object['items_count']);
			                    break;
		                    case 'account':
			                    $this->data['template'] = 'account';
			                    $this->data['varname'] = 'member';
			                    $this->data['value'] = array_merge($current_object['user'], (array) wpfval($_POST, 'member'));
			                    break;
		                    default:
			                    $this->data['template'] = 'profile';
			                    $this->data['value'] = $current_object['user'];
			                    break;
	                    }
                    }else{
	                    if( !$this->current_userid ){
		                    wp_redirect( wpforo_login_url() );
		                    exit();
	                    }
	                    $current_object['is_404'] = true;
                    }

				}else{
					$current_object['is_404'] = true;
				}
			}
			
			if( wpfval($current_object, 'topic_slug') ){
				$topic = $this->topic->get_topic(array('slug' => $current_object['topic_slug']), false);
				if(!empty($topic)){

					$topic_forumid = intval( wpfval($topic, 'forumid') );
					$is_owner = wpforo_is_owner($topic['userid'], $topic['email']);

					if( $topic_forumid && (
						!WPF()->perm->forum_can('vf', $topic_forumid) ||
						( !$is_owner && !WPF()->perm->forum_can('vt', $topic_forumid) ) ||
						( wpfval($topic, 'private') && !$is_owner && !WPF()->perm->forum_can('vp', $topic_forumid) ) ||
						( wpfval($topic, 'status') &&  !$is_owner && !WPF()->perm->forum_can('au', $topic_forumid) )
					) ){
						if( !$this->current_userid ){
							wp_redirect( wpforo_login_url() );
							exit();
						}
						$current_object['is_404'] = true;
					}else{
						$current_object['topic'] = $topic;
						$current_object['topicid'] = $topic['topicid'];
						$current_object['og_text'] = (string) wpfval($topic, 'title');
					}

				}else{
					$current_object['is_404'] = true;
				}
			}

			if( wpfval($current_object, 'forum_slug') ){
				$args = ( empty($topic) ? array('slug' => $current_object['forum_slug']) : $topic['forumid'] );
				if( $forum = $this->forum->get_forum( $args ) ){
					if( !empty($topic) && strtolower($current_object['forum_slug']) !== strtolower($forum['slug']) ){
						wp_redirect( $this->topic->get_topic_url($topic, $forum), 301 );
						exit();
					}
					if( $forum['is_cat'] ) $current_object['template'] = 'forum';
					$current_object['forum'] = $forum;
					$current_object['forumid'] = $forum['forumid'];
					$current_object['forum_desc'] = $forum['description'];
					$current_object['forum_meta_key'] = $forum['meta_key'];
					$current_object['forum_meta_desc'] = $forum['meta_desc'];
					$current_object['og_text'] = $forum['title'];
					$current_object['layout'] = $this->forum->get_layout( $forum );

					if( $current_object['template'] === 'topic' ){
						$current_object['items_per_page'] = $this->post->options['topics_per_page'];
						$args = array(
							'offset' => ($current_object['paged'] - 1) * $current_object['items_per_page'],
							'row_count' => $current_object['items_per_page'],
							'forumid' => $current_object['forumid'],
							'orderby' => 'type, modified',
							'order' => 'DESC'
						);
						$args = apply_filters('wpforo_topic_list_args', $args);
						$current_object['topics'] = $this->topic->get_topics( $args, $current_object['items_count'] );
					}
				}else{
					$current_object['is_404'] = true;
				}
			}

			if( in_array($current_object['template'], array('forum', 'topic')) ){
				if( !empty($forum) ){
					$current_object['categories'] = array( $forum );
				}else{
					$current_object['categories'] = $this->forum->get_forums( array( "type" => 'category' ) );
				}
			}

			if ( $current_object['template'] === 'post' && ! empty( $forum ) && ! empty( $topic ) ) {
				$current_object['items_per_page'] = $this->post->get_option_items_per_page($current_object['layout']);

				$args = array(
					'forumid'   => $forum['forumid'],
					'topicid'   => $topic['topicid'],
					'offset'    => ( $current_object['paged'] - 1 ) * $current_object['items_per_page'],
					'row_count' => $current_object['items_per_page']
				);
				if( $current_object['layout'] == 4 ){
					$args['parentid'] = 0;
				}elseif( $current_object['layout'] == 3 ){
					$args['parentid'] = 0;
					switch ( $current_object['orderby'] ) {
						case 'oldest':
							$args['orderby'] = '`is_first_post` DESC, `is_answer` DESC, `created` ASC, `postid` ASC';
							break;
						case 'newest':
							$args['orderby'] = '`is_first_post` DESC, `is_answer` DESC, `modified` DESC, `postid` DESC';
							break;
						default:
							$args['orderby'] = '`is_first_post` DESC, `is_answer` DESC, `votes` DESC, `created` ASC, `postid` ASC';
							break;
					}
				}
				if( $this->post->get_option_union_first_post($current_object['layout']) ) $args['union_first_post'] = true;
				$args = apply_filters('wpforo_post_list_args', $args);
				$current_object['posts'] = $this->post->get_posts( $args, $current_object['items_count']);
			}
			
			$this->current_object = wpforo_parse_args($current_object, $this->current_object);
			
			$this->current_object = apply_filters('wpforo_after_init_current_object', $this->current_object, $wpf_url_parse);

			if( $this->current_object['template'] ){
				/**
				 * redirect not logged-in users to login page when that user no access to this page
				 */
				if( !$this->current_userid && $this->current_object['forumid'] && (
						   (in_array($this->current_object['template'], array('forum', 'topic')) && !$this->perm->forum_can('vf', $this->current_object['forumid']))
						|| ($this->current_object['template'] === 'post' && !$this->perm->forum_can('vt', $this->current_object['forumid']))
					)
				){
					wp_redirect( wpforo_login_url() );
					exit();
				}

				/**
				 * redirect to the first page when paged var is greater items_count
				 */
				if( $this->current_object['items_count']
				    &&  $this->current_object['paged'] > 1
				    && (($this->current_object['paged'] - 1) * $this->current_object['items_per_page']) >= $this->current_object['items_count']
				){
					wp_redirect( $this->strip_url_paged_var( $this->current_url ), 301 );
					exit();
				}
			}else{
				$this->current_object['is_404'] = true;
			}
		}

		public function is_installed(){
		    return WPFORO_VERSION === get_option('wpforo_version');
        }

		public function can_use_this_slug($slug){
        	$return = !in_array($slug, $this->tpl->slugs, true) && !in_array($slug, array_keys($this->tpl->slugs), true);
			return apply_filters('wpforo_can_use_this_slug', $return, $slug);
        }
	}

    /**
     * Main instance of wpForo.
     *
     * Returns the main instance of WPF to prevent the need to use globals.
     *
     * @since  1.4.3
     * @return wpForo
     */
    if ( !function_exists('WPF') ){
        function WPF() {
            return wpForo::instance();
        }
    }

    // Global for backwards compatibility.
    $GLOBALS['wpforo'] = WPF();

    //ADDONS/////////////////////////////////////////////////////
	WPF()->addons = array(
		'prefix' => array('version' => '1.0.0', 'requires' => '1.9.4', 'class' => 'wpForoTopicPrefix', 'title' => 'Topic Prefix & Tag Manager', 'thumb' => WPFORO_URL . '/wpf-assets/addons/prefix/header.png', 'desc' => __('Allows you to create topic prefixes and prefix groups to categorize topics. Also, it allows you to add, edit, delete topic tags and convert them to prefixes.', 'wpforo'), 'url' => 'https://gvectors.com/product/wpforo-topic-prefix/'),
		'syntax' => array('version' => '1.0.0', 'requires' => '1.9.0', 'class' => 'wpForoSyntaxHighlighter', 'title' => 'Syntax Highlighter', 'thumb' => WPFORO_URL . '/wpf-assets/addons/syntax/header.png', 'desc' => __('Syntax highlighting for forum posts, automatic language detection and multi-language code highlighting.', 'wpforo'), 'url' => 'https://gvectors.com/product/wpforo-syntax-highlighter/'),
		'embeds' => array('version' => '2.0.7', 'requires' => '1.8.0', 'class' => 'wpForoEmbeds', 'title' => 'Embeds', 'thumb' => WPFORO_URL . '/wpf-assets/addons/embeds/header.png', 'desc' => __('Allows to embed hundreds of video, social network, audio and photo content providers in forum topics and posts.', 'wpforo'), 'url' => 'https://gvectors.com/product/wpforo-embeds/'),
		'polls' => array('version' => '1.0.6', 'requires' => '1.8.0', 'class' => 'wpForoPoll', 'title' => 'Polls', 'thumb' => WPFORO_URL . '/wpf-assets/addons/polls/header.png', 'desc' => __('wpForo Polls is a complete addon to help forum members create, vote and manage polls effectively. Comes with poll specific permissions and settings.', 'wpforo'), 'url' => 'https://gvectors.com/product/wpforo-polls/'),
		'tcf' => array('version' => '1.0.0', 'requires' => '1.8.0', 'class' => 'wpForoTcf', 'title' => 'Topic Custom Fields', 'thumb' => WPFORO_URL . '/wpf-assets/addons/tcf/header.png', 'desc' => __('Allows to create topic custom fields and manage topic form layout with a form builder. Adds topic search options by custom fields', 'wpforo'), 'url' => 'https://gvectors.com/product/wpforo-topic-custom-fields/'),
		'mycred' => array('version' => '1.1.2', 'requires' => '1.8.0', 'class' => 'myCRED_Hook_wpForo', 'title' => 'MyCRED Integration', 'thumb' => WPFORO_URL . '/wpf-assets/addons/mycred/header.png', 'desc' => __('Awards myCRED points for forum activity. Integrates myCRED Badges and Ranks. Converts wpForo topic and posts, likes to myCRED points.', 'wpforo'), 'url' => 'https://gvectors.com/product/wpforo-mycred/'),
		'ucf' => array('version' => '2.0.2', 'requires' => '1.8.0', 'class' => 'WpforoUcf', 'title' => 'User Custom Fields', 'thumb' => WPFORO_URL . '/wpf-assets/addons/ucf/header.png', 'desc' => __('Advanced user profile builder system. Allows to add new fields and manage profile page. Creates custom Registration, Account, Member Search forms.', 'wpforo'), 'url' => 'https://gvectors.com/product/wpforo-user-custom-fields/'),
		'attachments' => array('version' => '2.0.4', 'requires' => '1.8.0', 'class' => 'wpForoAttachments', 'title' => 'Advanced Attachments', 'thumb' => WPFORO_URL . '/wpf-assets/addons/attachments/header.png', 'desc' => __('Adds an advanced file attachment system to forum topics and posts. AJAX powered media uploading and displaying system with user specific library.', 'wpforo'), 'url' => 'https://gvectors.com/product/wpforo-advanced-attachments/'),
		'pm' => array('version' => '1.3.3', 'requires' => '1.8.0', 'class' => 'wpForoPMs', 'title' => 'Private Messages', 'thumb' => WPFORO_URL . '/wpf-assets/addons/pm/header.png', 'desc' => __('Provides a safe way to communicate directly with other members. Messages are private and can only be viewed by conversation participants.', 'wpforo'), 'url' => 'https://gvectors.com/product/wpforo-private-messages/'),
		'cross' => array('version' => '2.1.5', 'requires' => '1.8.0', 'class' => 'wpForoCrossPosting', 'title' => '"Forum - Blog" Cross Posting', 'thumb' => WPFORO_URL . '/wpf-assets/addons/cross/header.png', 'desc' => __('Blog to Forum and Forum to Blog content synchronization. Blog posts with Forum topics and Blog comments with Forum replies.', 'wpforo'), 'url' => 'https://gvectors.com/product/wpforo-cross-posting/'),
		'ad-manager' => array('version' => '1.1.3', 'requires' => '1.8.0', 'class' => 'wpForoAD', 'title' => 'Ads Manager', 'thumb' => WPFORO_URL . '/wpf-assets/addons/ad-manager/header.png', 'desc' => __('Ads Manager is a powerful yet simple advertisement management system, that allows you to add adverting banners between forums, topics and posts.', 'wpforo'), 'url' => 'https://gvectors.com/product/wpforo-ad-manager/'),
		'emoticons' => array('version' => '1.0.5', 'requires' => '1.8.0', 'class' => 'wpForoSmiles', 'title' => 'wpForo Emoticons', 'thumb' => WPFORO_URL . '/wpf-assets/addons/wpforo-emoticons/header.png', 'desc' => __('Adds awesome Sticker and Emoticons packs to editor. Allows to create new custom emoticons packs.', 'wpforo'), 'url' => 'https://gvectors.com/product/wpforo-emoticons/'),
    );
	$wp_version = get_bloginfo('version'); if (version_compare($wp_version, '4.2.0', '>=')) { add_action('wp_ajax_dismiss_wpforo_addon_note', array(WPF()->notice, 'dismissAddonNote')); add_action('admin_notices', array(WPF()->notice, 'addonNote'));}
	/////////////////////////////////////////////////////////////
}