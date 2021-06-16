<?php
// Exit if accessed directly
if( !defined( 'ABSPATH' ) ) exit;

class wpForoAction {
	/**
	 * wpForoAction constructor.
	 */
	public function __construct() {
		$this->init_hooks();
	}

	/**
	 * method for initializing all necessary hooks
	 */
	public function init_hooks(){
		add_action( 'wpforo_before_init', function(){ if( !WPF()->is_installed() ) wpforo_activation(); }, 0 );
		add_action( 'wpforo_after_init', array( $this, 'do_actions' ), 999 );
		add_action( 'deleted_user', array( $this, 'user_delete' ) );
		add_action( 'wp_ajax_wpforo_deactivate', array($this, 'deactivate'));
		if( !wpforo_is_admin() ){
			add_action( 'wpforo_actions',                                array($this, 'init_default_attach_hooks') );
			add_action( 'wpforo_actions',                                array($this, 'init_wp_emoji_hooks') );

			add_action( 'wpforo_actions',                                array($this, 'feed_rss2') );
			add_action( 'wpforo_actions',                                array($this, 'ucf_file_delete') );
			add_action( 'wpforo_actions',                                array($this, 'mark_all_read') );
			add_action( 'wpforo_actions',                                array($this, 'mark_notification_read') );

			add_action( 'wpforo_action_wpforo_registration',             array($this, 'registration') );
			add_action( 'wpforo_action_wpforo_login',                    array($this, 'login') );
			add_action( 'wpforo_action_wpforo_profile_update',           array($this, 'profile_update') );

			add_action( 'wpforo_action_wpforo_topic_add',                array($this, 'topic_add') );
			add_action( 'wpforo_action_wpforo_topic_edit',               array($this, 'topic_edit') );
			add_action( 'wpforo_action_wpforo_topic_move',               array($this, 'topic_move') );
			add_action( 'wpforo_action_wpforo_topic_merge',              array($this, 'topic_merge') );
			add_action( 'wpforo_action_wpforo_topic_split',              array($this, 'topic_split') );

			add_action( 'wpforo_action_wpforo_post_add',                 array($this, 'post_add') );
			add_action( 'wpforo_action_wpforo_post_edit',                array($this, 'post_edit') );

			add_action( 'wpforo_action_sbscrbconfirm',                   array($this, 'subscription_confirmation') );
			add_action( 'wpforo_action_unsbscrb',                        array($this, 'subscription_delete') );
			add_action( 'wpforo_action_wpforo_subscribe_manager',        array($this, 'subscribe_manager') );

			## ajax actions ##
			add_action( 'wp_ajax_wpforo_dissmiss_recaptcha_note',        array($this, 'dissmiss_recaptcha_note') );
			add_action( 'wp_ajax_wpforo_acp_toggle',                     array($this, 'acp_toggle') );
			add_action( 'wp_ajax_wpforo_clear_all_notifications',        array($this, 'clear_all_notifications') );
		}else{
            add_action( 'wpforo_actions',                                array($this, 'check_dashboard_permissions'), 1 );
			add_action( 'wpforo_actions',                                array($this, 'repair_lost_main_shortcode_page') );

			add_action( 'wpforo_action_wpforo_synch_user_profiles',      array($this, 'synch_user_profiles') );
			add_action( 'wpforo_action_wpforo_reset_user_cache',         array($this, 'reset_user_cache') );
			add_action( 'wpforo_action_wpforo_reset_forums_stats',       array($this, 'reset_forums_stats') );
			add_action( 'wpforo_action_wpforo_reset_topics_stats',       array($this, 'reset_topics_stats') );
			add_action( 'wpforo_action_wpforo_reset_users_stats',        array($this, 'reset_users_stats') );
			add_action( 'wpforo_action_wpforo_rebuild_threads',          array($this, 'rebuild_threads') );
			add_action( 'wpforo_action_wpforo_reset_phrase_cache',       array($this, 'reset_phrase_cache') );
			add_action( 'wpforo_action_wpforo_recrawl_phrases',          array($this, 'recrawl_phrases') );
			add_action( 'wpforo_action_wpforo_clean_up',                 array($this, 'clean_up') );

			add_action( 'wpforo_action_wpforo_add_new_xml_translation',  array($this, 'add_new_xml_translation') );
			add_action( 'wpforo_action_wpforo_dashboard_options_save',   array($this, 'dashboard_options_save') );
			add_action( 'wpforo_action_wpforo_general_options_save',     array($this, 'general_options_save') );
			add_action( 'wpforo_action_wpforo_forum_options_save',       array($this, 'forum_options_save') );
			add_action( 'wpforo_action_wpforo_post_options_save',        array($this, 'post_options_save') );
			add_action( 'wpforo_action_wpforo_member_options_save',      array($this, 'member_options_save') );
			add_action( 'wpforo_action_wpforo_features_options_save',    array($this, 'features_options_save') );
			add_action( 'wpforo_action_wpforo_api_options_save',         array($this, 'api_options_save') );
			add_action( 'wpforo_action_wpforo_theme_style_options_save', array($this, 'theme_style_options_save') );
			add_action( 'wpforo_action_wpforo_colors_css_download',      array($this, 'colors_css_download') );
			add_action( 'wpforo_action_wpforo_email_options_save',       array($this, 'email_options_save') );

			add_action( 'wpforo_action_wpforo_antispam_options_save',    array($this, 'antispam_options_save') );
			add_action( 'wpforo_action_wpforo_cleanup_options_save',     array($this, 'cleanup_options_save') );
			add_action( 'wpforo_action_wpforo_misc_options_save',        array($this, 'misc_options_save') );
			add_action( 'wpforo_action_wpforo_legal_options_save',       array($this, 'legal_options_save') );
			add_action( 'wpforo_action_wpforo_delete_spam_file',         array($this, 'delete_spam_file') );
			add_action( 'wpforo_action_wpforo_delete_all_spam_files',    array($this, 'delete_all_spam_files') );
			add_action( 'wpforo_action_wpforo_database_update',          array($this, 'database_update') );

			add_action( 'wpforo_action_wpforo_forum_add',                array($this, 'forum_add') );
			add_action( 'wpforo_action_wpforo_forum_edit',               array($this, 'forum_edit') );
			add_action( 'wpforo_action_wpforo_forum_delete',             array($this, 'forum_delete') );
			add_action( 'wpforo_action_wpforo_forum_hierarchy_save',     array($this, 'forum_hierarchy_save') );

			add_action( 'wpforo_action_wpforo_dashboard_post_unapprove', array($this, 'dashboard_post_unapprove') );
			add_action( 'wpforo_action_wpforo_dashboard_post_approve',   array($this, 'dashboard_post_approve') );
			add_action( 'wpforo_action_wpforo_dashboard_post_delete',    array($this, 'dashboard_post_delete') );
			add_action( 'wpforo_action_wpforo_bulk_moderation',          array($this, 'bulk_moderation') );

			add_action( 'wpforo_action_wpforo_phrase_add',               array($this, 'phrase_add') );
			add_action( 'wpforo_action_wpforo_phrase_edit_form',         array($this, 'phrase_edit_form') );
			add_action( 'wpforo_action_wpforo_phrase_edit',              array($this, 'phrase_edit') );

			add_action( 'wpforo_action_wpforo_user_ban',                 array($this, 'user_ban') );
			add_action( 'wpforo_action_wpforo_user_unban',               array($this, 'user_unban') );
			add_action( 'wpforo_action_wpforo_bulk_members',             array($this, 'bulk_members') );

			add_action( 'wpforo_action_wpforo_usergroup_add',            array($this, 'usergroup_add') );
			add_action( 'wpforo_action_wpforo_usergroup_edit',           array($this, 'usergroup_edit') );
			add_action( 'wpforo_action_wpforo_usergroup_delete',         array($this, 'usergroup_delete') );
			add_action( 'wpforo_action_wpforo_default_groupid_change',   array($this, 'default_groupid_change') );
			add_action( 'wpforo_action_wpforo_usergroup_delete_form',    array($this, 'usergroup_delete_form') );

			add_action( 'wpforo_action_wpforo_access_add',               array($this, 'access_add') );
			add_action( 'wpforo_action_wpforo_access_edit',              array($this, 'access_edit') );
			add_action( 'wpforo_action_wpforo_access_delete',            array($this, 'access_delete') );

			add_action( 'wpforo_action_wpforo_theme_activate',           array($this, 'theme_activate') );
			add_action( 'wpforo_action_wpforo_theme_install',            array($this, 'theme_install') );
			add_action( 'wpforo_action_wpforo_theme_delete',             array($this, 'theme_delete') );
			add_action( 'wpforo_action_wpforo_theme_reset',              array($this, 'theme_reset') );

			add_action( 'wpforo_action_wpforo_update_addons_css',        array($this, 'update_addons_css') );
			add_action( 'wpforo_action_wpforo_dissmiss_poll_version_is_old', array($this, 'dissmiss_poll_version_is_old') );
		}
        add_action( 'wpforo_action_wpforo_reset_all_caches',             array($this, 'reset_all_caches') );
	}

	/**
	 * wpforo main actions doing place
	 */
	public function do_actions(){
		do_action( 'wpforo_actions' );
		$wpforo_actions = array_unique( array_merge((array) wpfval($_POST, 'wpfaction'), (array) wpfval(WPF()->GET, 'wpfaction')) );
		if( !empty($wpforo_actions) ){
			// Options cache must be cleared before any actions
			wpforo_clean_cache('option');
			foreach ($wpforo_actions as $wpforo_action){
				$wpforo_action = sanitize_title($wpforo_action);
				do_action("wpforo_action_{$wpforo_action}");
			}
		}
		do_action( 'wpforo_actions_end' );
	}

	/**
	 * init wpforo default attachments system when wpforo advanced attachments addon has not exists
	 */
	public function init_default_attach_hooks(){
		add_action( 'delete_attachment', 'wpforo_delete_attachment', 10 );
		if( has_action('wpforo_topic_form_extra_fields_after', array(WPF()->tpl, 'add_default_attach_input')) ){
			add_filter( 'wpforo_add_topic_data_filter', 'wpforo_add_default_attachment' );
			add_filter( 'wpforo_edit_topic_data_filter', 'wpforo_add_default_attachment' );
			add_filter( 'wpforo_add_post_data_filter', 'wpforo_add_default_attachment' );
			add_filter( 'wpforo_edit_post_data_filter', 'wpforo_add_default_attachment' );
			add_filter( 'wpforo_body_text_filter', 'wpforo_default_attachments_filter');
		}
	}

	/**
	 * init wp emojis when wpforo emoticons addon has not exists
	 */
	public function init_wp_emoji_hooks(){
		if( !class_exists('wpForoSmiles') ){
			add_filter('wpforo_body_text_filter', 'wp_encode_emoji', 9);
			add_filter('wpforo_body_text_filter', 'convert_smilies');
		}
	}

	/**
	 * get request_uri redirect to url with concatenation of &can_do=do
	 * @return bool true if you can do action now | false if you can not do action now
	 */
	private function can_do(){
		if( wpfval($_GET, 'can_do') === 'do' ) return true;

		$refresh_url = preg_replace('#&can_do=?[^=?&\r\n]*#isu', '', wpforo_get_request_uri() );
		$refresh_url .= '&can_do=do';
		header( "refresh:0.1;url=" . $refresh_url );

		add_filter('wpforo_admin_loading', '__return_true');
		return false;
	}

	/**
	 * @return string $u_action return union bulk action
	 */
	private function get_current_bulk_action(){
		$u_action = '';
		if( !empty($_GET['action']) && $_GET['action'] !== '-1' ){
			$u_action = sanitize_textarea_field($_GET['action']);
		}elseif( !empty($_GET['action2']) && $_GET['action2'] !== '-1' ){
			$u_action = sanitize_textarea_field($_GET['action2']);
		}
		return $u_action;
	}

	/**
	 * catch if rss url show rss feed for given arguments
	 */
	public function feed_rss2() {
		if( wpfval(WPF()->GET, 'type') === 'rss2' ){
			$forum_rss_items = 10;
			$topic_rss_items = 10;

			$forumid = intval(wpfval(WPF()->GET, 'forum'));
			if (!$forumid) {
				$forum             = array();
				$forum['forumurl'] = wpforo_home_url();
				$forum['title']    = '';
			} else {
				$forum             = wpforo_forum( $forumid );
				$forum['forumurl'] = $forum['url'];
			}

			if ( wpfval(WPF()->GET, 'topic') ) {
				$topicid = intval(WPF()->GET['topic']);
				if (!$topicid) {
					$posts             = WPF()->post->get_posts( array(
						'row_count'     => $topic_rss_items,
						'orderby'       => '`created` DESC, `postid` DESC',
						'check_private' => true
					) );
					$topic['title']    = '';
					$topic['topicurl'] = wpforo_home_url();
				} else {
					$topic             = wpforo_topic( $topicid );
					$topic['topicurl'] = ( $topic['url'] ) ? $topic['url'] : WPF()->topic->get_topic_url( $topicid );
					$posts             = WPF()->post->get_posts( array(
						'topicid'       => $topicid,
						'row_count'     => $topic_rss_items,
						'orderby'       => '`created` DESC, `postid` DESC',
						'check_private' => true
					) );
				}
				foreach ( $posts as $key => $post ) {
					$member                       = wpforo_member( $post );
					$posts[ $key ]['description'] = wpforo_text( trim( strip_tags( $post['body'] ) ), 190, false );
					$posts[ $key ]['content']     = trim( $post['body'] );
					$posts[ $key ]['posturl']     = WPF()->post->get_post_url( $post['postid'] );
					$posts[ $key ]['author']      = $member['display_name'];
				}
				WPF()->feed->rss2_topic( $forum, $topic, $posts );
			} else {
				if ( !$forumid ) {
					$topics = WPF()->topic->get_topics( array(
						'row_count' => $forum_rss_items,
						'orderby'   => 'created',
						'order'     => 'DESC'
					) );
				} else {
					$topics = WPF()->topic->get_topics( array(
						'forumid'   => $forumid,
						'row_count' => $forum_rss_items,
						'orderby'   => 'created',
						'order'     => 'DESC'
					) );
				}
				foreach ( $topics as $key => $topic ) {
					$post                          = wpforo_post( $topic['first_postid'] );
					$member                        = wpforo_member( $topic );
					$topics[ $key ]['description'] = wpforo_text( trim( strip_tags( $post['body'] ) ), 190, false );
					$topics[ $key ]['content']     = trim( $post['body'] );
					$topics[ $key ]['topicurl']    = WPF()->topic->get_topic_url( $topic['topicid'] );
					$topics[ $key ]['author']      = $member['display_name'];
				}
				WPF()->feed->rss2_forum( $forum, $topics );
			}
			exit();
		}
	}

	/**
	 * ucf_file_delete delete /wp-content/uploads/UCFFILENAME
	 */
	public function ucf_file_delete() {
		if( wpfval(WPF()->GET, 'foro_f') && wpfval(WPF()->GET, 'foro_u') && wpfval(WPF()->GET, 'foro_n') ){
			if( wp_verify_nonce(WPF()->GET['foro_n'], 'wpforo_delete_profile_field') ){
				$userid = intval( WPF()->GET['foro_u'] );
				$field = sanitize_title( WPF()->GET['foro_f'] );
				$wpudir = wp_upload_dir();
				if( $file = WPF()->member->get_custom_field( $userid, $field ) ){
					$file = $wpudir['basedir'] . $file;
					$result = WPF()->member->update_custom_field( $userid, $field, '' );
					if( $result ){
						@unlink($file);
						WPF()->phrase->clear_cache();
						WPF()->notice->add('Deleted Successfully!', 'success');
					} else {
						WPF()->notice->clear();
						WPF()->notice->add('Sorry, this file cannot be deleted', 'error');
					}
				}
			}
		}
	}

	/**
	 * mark all bold forum topics as read
	 */
	public function mark_all_read() {
		if( wpfval(WPF()->GET, 'foro') === 'allread' ){
			if( wpfval(WPF()->GET, 'foro_n') && wp_verify_nonce(WPF()->GET['foro_n'], 'wpforo_mark_all_read') ){
				WPF()->log->mark_all_read();
				$current_url = wpforo_get_request_uri();
				$current_url = strtok( $current_url, '?');
				wp_redirect($current_url);
				exit();
			}
		}
	}

    /**
     * Open/Close Frontend Admin CPanel
     */
    public function acp_toggle() {
	    $toggle_status = wpfval($_POST, 'toggle_status');
    	if( in_array($toggle_status, array('open','close')) ){
		    update_user_meta(WPF()->current_userid, 'wpf-acp-toggle', $toggle_status);
		    wp_send_json_success();
	    }else{
    		wp_send_json_error();
	    }
    }

	/**
	 * set a notification read
	*/
	public function mark_notification_read(){
		if( wpfval(WPF()->GET, '_nread') && is_user_logged_in() ){
			if( wpfval(WPF()->GET, 'foro_n') && wp_verify_nonce(WPF()->GET['foro_n'], 'wpforo_mark_notification_read') ){
				$id = intval( WPF()->GET['_nread'] );
				WPF()->activity->read_notification( $id );
				$current_url = wpforo_get_request_uri();
				$current_url = strtok( $current_url, '?');
				wp_redirect($current_url);
				exit();
			}
		}
	}

	/**
	 * clear all notifications
	 */
	public function clear_all_notifications(){
		if( wpfval($_POST, 'foro_n') && wp_verify_nonce($_POST['foro_n'], 'wpforo_clear_notifications') ){
			WPF()->activity->clear_notifications();
			echo WPF()->activity->get_no_notifications_html();
		}
		exit();
	}

	/**
	 * registration form submit action
	 */
	public function registration(){
		if( !empty($_POST['wpfreg']) ){
			wpforo_verify_form('full', 'wpforo_user_register');
			if( $userid = WPF()->member->create($_POST) ){
				WPF()->member->reset($userid);
				if( WPF()->member->options['redirect_url_after_register'] ){
					$redirect_url = WPF()->member->options['redirect_url_after_register'];
				}elseif( $redirect_to = wpfval($_GET, 'redirect_to') ){
					$redirect_url = urldecode($redirect_to);
				}elseif( is_wpforo_url() ){
					$redirect_url = preg_replace('#\?.*$#is', '', wpforo_get_request_uri());
				}else{
					$redirect_url = ( wpforo_feature('user-register-email-confirm') ? wpforo_home_url() : WPF()->member->get_profile_url( $userid, 'account' ) );
				}

				wp_redirect($redirect_url);
				exit();
			}
		}
	}

	/**
	 * login form submit action
	 */
	public function login(){
		if(isset($_POST['wpforologin']) && isset($_POST['log']) && isset($_POST['pwd'])){
			wpforo_verify_form('ref');
			if ( !is_wp_error( $user = wp_signon() ) ) {
				$wpf_login_times = intval( get_user_meta($user->ID, '_wpf_login_times', true) );
				if( isset($user->ID) && $wpf_login_times >= 1) {
					$name = ( isset($user->data->display_name) ) ? $user->data->display_name : '';
					WPF()->notice->add( 'Welcome back %s!', 'success', $name);
				}
				else{
					WPF()->notice->add('Welcome to our Community!', 'success');
				}
				$wpf_login_times++;
				update_user_meta( $user->ID, '_wpf_login_times', $wpf_login_times );
				if( WPF()->member->options['redirect_url_after_login'] ){
					$redirect_url = WPF()->member->options['redirect_url_after_login'];
				}elseif( $redirect_to = wpfval($_GET, 'redirect_to') ){
					$redirect_url = urldecode($redirect_to);
				}elseif( is_wpforo_url() ){
					$redirect_url = preg_replace('#\?.*$#is', '', wpforo_get_request_uri());
				}else{
					$redirect_url = wpforo_home_url();
				}
				wp_redirect($redirect_url);
				exit();
			}else{
				$args = array();
				foreach($user->errors as $u_err) $args[] = $u_err[0];
				WPF()->notice->add($args, 'error');
				wp_redirect( wpforo_get_request_uri() );
				exit();
			}
		}
	}

	/**
	 * profile_update form submit action
	 */
	public function profile_update(){
		if( wpfval($_POST, 'member', 'userid') ){
			wpforo_verify_form();
			$uid = intval($_POST['member']['userid']);
			if( !( $uid === WPF()->current_userid ||
			       ( WPF()->perm->usergroup_can('em') && WPF()->perm->user_can_manage_user( WPF()->current_userid, $uid )) ) ){
				WPF()->notice->clear();
				WPF()->notice->add('Permission denied', 'error');
				wp_redirect(wpforo_get_request_uri());
				exit();
			}
			if( $user = WPF()->member->update($_POST) ){
				if( $profile_url = WPF()->member->get_profile_url( $uid, 'account') ){
					wp_redirect($profile_url);
					exit();
				}
			}
		}

		wp_redirect(wpforo_get_request_uri());
		exit();
	}

	/**
	 * topic_add form submit action
	 */
	public function topic_add(){
		wpforo_verify_form();
		$args = $_REQUEST['topic'];
		$args['postmetas'] = (array) wpfval($_REQUEST, 'data');
		if( $topicid = WPF()->topic->add($args) ){
			wp_redirect( WPF()->topic->get_topic_url($topicid) );
			exit();
		}
		wp_redirect( wpforo_get_request_uri() );
		exit();
	}

	/**
	 * topic_edit form submit action
	 */
	public function topic_edit(){
		wpforo_verify_form();
		$args = $_REQUEST['topic'];
		$args['postmetas'] = (array) wpfval($_REQUEST, 'data');
		if( $topicid = WPF()->topic->edit($args) ){
			wp_redirect( WPF()->topic->get_topic_url($topicid) );
			exit();
		}
		wp_redirect( wpforo_get_request_uri() );
		exit();
	}

	/**
	 * post_add form submit action
	 */
	public function post_add(){
		wpforo_verify_form();
		$args = $_REQUEST['post'];
		$args['postmetas'] = (array) wpfval($_REQUEST, 'data');
		if( $postid = WPF()->post->add($args) ){
			wp_redirect( WPF()->post->get_post_url( $postid ) );
			exit();
		}
		wp_redirect( wpforo_get_request_uri() );
		exit();
	}

	/**
	 * post_edit form submit action
	 */
	public function post_edit(){
		wpforo_verify_form();
		$args = $_REQUEST['post'];
		$args['postmetas'] = (array) wpfval($_REQUEST, 'data');
		if( $postid = WPF()->post->edit($args) ){
			wp_redirect( WPF()->post->get_post_url( $postid ) );
			exit();
		}
		wp_redirect( wpforo_get_request_uri() );
		exit();
	}

	/**
	 * topic_move form submit action
	 */
	public function topic_move(){
		if( !empty($_POST['topic_move']) ){
			wpforo_verify_form();
			$topicid = intval( wpfval($_POST['topic_move'], 'topicid') );
			$forumid = intval( wpfval($_POST['topic_move'], 'forumid') );
			if( $topicid && $forumid ){
				WPF()->topic->move($topicid, $forumid);
				wp_redirect( WPF()->topic->get_topic_url($topicid) );
				exit();
			}
		}

		wp_redirect( wpforo_get_request_uri() );
		exit();
	}

	/**
	 * topic_merge form submit action
	 */
	public function topic_merge(){
		wpforo_verify_form();
		$redirect_to = wpforo_get_request_uri();
		if( WPF()->current_object['topic'] && !empty($_POST['wpforo']) && !empty($_POST['wpforo']['target_topic_url']) ){
			$target_slug = wpforo_get_topic_slug_from_url( esc_url($_POST['wpforo']['target_topic_url']) );
			if ( !is_null($target_slug) && $target = WPF()->topic->get_topic($target_slug) ){
				$append = (empty($_POST['wpforo']['update_date_and_append']) ? 0 : 1);
				$to_target_title = (empty($_POST['wpforo']['to_target_title']) ? 0 : 1);

				if( WPF()->topic->merge( $target, WPF()->current_object['topic'], array(), $to_target_title, $append ) )
					$redirect_to = WPF()->topic->get_topic_url($target);
			}else{
				WPF()->notice->add('Target Topic not found', 'error');
			}
		}
		wp_redirect($redirect_to);
		exit();
	}

	/**
	 * topic_split form submit action
	 */
	public function topic_split(){
		wpforo_verify_form();
		$redirect_to = wpforo_get_request_uri();
		if( WPF()->current_object['topic'] && !empty($_POST['wpforo']) ){
			if( !empty($_POST['wpforo']['create_new']) ){
				$args = array(
					'title'     => sanitize_text_field( $_POST['wpforo']['new_topic_title']),
					'forumid'   => intval( $_POST['wpforo']['new_topic_forumid']),
					'postids'   => array_map( 'intval', $_POST['wpforo']['posts'] )
				);
				$to_target_title = (empty($_POST['wpforo']['to_target_title']) ? 0 : 1);
				if( $topicid = WPF()->topic->split($args, $to_target_title) )
					$redirect_to = WPF()->topic->get_topic_url($topicid);
			}else{
				if( !empty($_POST['wpforo']['target_topic_url']) && !empty($_POST['wpforo']['posts']) ){
					$target_slug = wpforo_get_topic_slug_from_url( esc_url( $_POST['wpforo']['target_topic_url'] ) );
					if ( !is_null($target_slug) && $target = WPF()->topic->get_topic($target_slug) ){
						$append = (empty($_POST['wpforo']['update_date_and_append']) ? 0 : 1);
						$to_target_title = (empty($_POST['wpforo']['to_target_title']) ? 0 : 1);
						$postids = array_map( 'intval', $_POST['wpforo']['posts']);
						if( WPF()->topic->merge( $target, WPF()->current_object['topic'], $postids, $to_target_title, $append ) )
							$redirect_to = WPF()->topic->get_topic_url($target);
					}else{
						WPF()->notice->add('Target Topic not found', 'error');
					}
				}
			}
		}
		wp_redirect($redirect_to);
		exit();
	}

	/**
	 * subscription_confirmation form submit action
	 */
	public function subscription_confirmation(){
		if( $sbs_key = wpfval(WPF()->GET, 'key') ){
			$sbs_key = sanitize_text_field($sbs_key);
			WPF()->sbscrb->edit($sbs_key);
			$redirect_url = wpforo_home_url( preg_replace('#\?.*$#is', '', WPF()->current_url) );
			if( WPF()->member->options['redirect_url_after_confirm_sbscrb'] ) $redirect_url = WPF()->member->options['redirect_url_after_confirm_sbscrb'];
			wp_redirect($redirect_url);
			exit();
		}
	}

	/**
	 * subscription_delete form submit action
	 */
	public function subscription_delete(){
		if( $sbs_key = wpfval(WPF()->GET, 'key') ){
			$sbs_key = sanitize_text_field($sbs_key);
			WPF()->sbscrb->delete($sbs_key);
			$redirect_url = wpforo_home_url( preg_replace('#\?.*$#is', '', WPF()->current_url) );
			if( WPF()->member->options['redirect_url_after_confirm_sbscrb'] ) $redirect_url = WPF()->member->options['redirect_url_after_confirm_sbscrb'];
			wp_redirect($redirect_url);
			exit();
		}
	}

	/**
	 * subscribe_manager form submit action
	 */
	public function subscribe_manager(){
		$data = ( !empty($_POST['wpforo']['forums'])    ? array_map( 'sanitize_title', $_POST['wpforo']['forums'] ) : array() );
		$all =  ( !empty($_POST['wpforo']['check_all']) ? sanitize_title($_POST['wpforo']['check_all'])                     : '' );

		WPF()->sbscrb->reset($data, $all);
		wp_redirect( wpforo_home_url(wpforo_get_template_slug('subscriptions')) );
		exit();
	}

	/**
	 * action to synchronize wp_users to wp_wpforo_profiles
	 */
	public function synch_user_profiles(){
		check_admin_referer( 'wpforo_synch_user_profiles' );

		if(!current_user_can('administrator')){
			WPF()->notice->add('Permission denied', 'error');
			wp_redirect(admin_url());
			exit();
		}

		if( $this->can_do() ){
			wpforo_set_max_execution_time();
			wp_raise_memory_limit();

			if( WPF()->member->synchronize_users( apply_filters('wpforo_rebuild_per_request', 200) ) ){
				WPF()->member->clear_db_cache();
				wpforo_clean_cache();
				WPF()->notice->add('Synched Successfully!', 'success');
				wp_redirect(admin_url('admin.php?page=wpforo-community'));
			}else{
				wp_redirect( htmlspecialchars_decode( wp_nonce_url( admin_url( 'admin.php?page=wpforo-community&wpfaction=wpforo_synch_user_profiles' ), 'wpforo_synch_user_profiles' ) ) );
			}
			exit();
		}
	}

	/**
	 * reset user caches
	 */
	public function reset_user_cache(){
		check_admin_referer('wpforo_reset_user_cache');

		if(!current_user_can('administrator')){
			WPF()->notice->add('Permission denied', 'error');
			wp_redirect(admin_url());
			exit();
		}

		wpforo_set_max_execution_time();
		wp_raise_memory_limit();

		WPF()->member->clear_db_cache();
		WPF()->notice->add('Deleted Successfully!', 'success');

		wp_redirect(admin_url('admin.php?page=wpforo-community'));
		exit();
	}

	/**
	 * rebuild forums statistics first|last posts etc.
	 */
	public function reset_forums_stats(){
		check_admin_referer('wpforo_reset_forums_stat');

		if(!current_user_can('administrator')){
			WPF()->notice->add('Permission denied', 'error');
			wp_redirect(admin_url());
			exit();
		}

		wpforo_set_max_execution_time();
		wp_raise_memory_limit();

		$forumids = WPF()->db->get_col("SELECT `forumid` FROM ".WPF()->tables->forums." WHERE `is_cat` = 0 ORDER BY `forumid` ASC");
		if(!empty($forumids)){
			foreach($forumids as $forumid){
				WPF()->forum->rebuild_stats($forumid);
			}
			WPF()->db->query("DELETE FROM `" . WPF()->db->options."` WHERE `option_name` LIKE 'wpforo_stat%'" );
			WPF()->forum->delete_tree_cache();
			WPF()->notice->add('Updated Successfully!', 'success');
		}

		wp_redirect(admin_url('admin.php?page=wpforo-community'));
		exit();
	}

	/**
	 * rebuild topics statistics first|last posts etc.
	 */
	public function reset_topics_stats(){
		check_admin_referer( 'wpforo_reset_topics_stat' );

		if(!current_user_can('administrator')){
			WPF()->notice->add('Permission denied', 'error');
			wp_redirect(admin_url());
			exit();
		}

		if( $this->can_do() ){
			wpforo_set_max_execution_time();
			wp_raise_memory_limit();

			$lastid = (int) wpfval($_GET, 'topic_lastid');
			$sql = "SELECT `topicid` FROM ".WPF()->tables->topics." WHERE `topicid` > %d ORDER BY `topicid` ASC LIMIT %d";
			$topicids = WPF()->db->get_col( WPF()->db->prepare($sql, $lastid, apply_filters('wpforo_rebuild_per_request', 200)) );
			if( $topicids ){
				foreach($topicids as $topicid){
					$topic = WPF()->topic->get_topic($topicid);
					WPF()->topic->rebuild_first_last($topic);
					WPF()->topic->rebuild_stats($topic);
				}
				wp_redirect( htmlspecialchars_decode( wp_nonce_url( admin_url( 'admin.php?page=wpforo-community&wpfaction=wpforo_reset_topics_stats&topic_lastid=' . end($topicids) ), 'wpforo_reset_topics_stat' ) ) );
			}else{
				@WPF()->db->query( "UPDATE `".WPF()->tables->topics."` t
								INNER JOIN `".WPF()->tables->posts."` p ON p.`topicid` = t.`topicid` AND p.`is_answer` = 1
								SET t.`solved` = 1
								WHERE t.`solved` = 0" );
				WPF()->db->query("DELETE FROM `" . WPF()->db->options."` WHERE `option_name` LIKE 'wpforo_stat%'" );
				WPF()->notice->add('Updated Successfully!', 'success');
				wp_redirect(admin_url('admin.php?page=wpforo-community'));
			}
			exit();
		}
	}

	/**
	 * rebuild users statistics etc.
	 */
	public function reset_users_stats(){
		check_admin_referer('wpforo_reset_users_stat');

		if(!current_user_can('administrator')){
			WPF()->notice->add('Permission denied', 'error');
			wp_redirect(admin_url());
			exit();
		}

		if( $this->can_do() ){
			wpforo_set_max_execution_time();
			wp_raise_memory_limit();

			$lastid = (int) wpfval($_GET, 'user_lastid');
			$sql = "SELECT `userid` FROM ".WPF()->tables->profiles." WHERE `userid` > %d ORDER BY `userid` ASC LIMIT %d";
			$userids = WPF()->db->get_col( WPF()->db->prepare($sql, $lastid, apply_filters('wpforo_rebuild_per_request', 200)) );
			if($userids){
				foreach($userids as $userid){
					$posts = WPF()->member->get_replies_count( $userid );
					$answers = WPF()->member->get_answers_count( $userid );
					$questions = WPF()->member->get_questions_count( $userid );
					$comments = WPF()->member->get_question_comments_count( $userid );
					WPF()->db->update(
						WPF()->tables->profiles,
						array(
							'posts'     => $posts,
							'answers'   => $answers,
							'questions' => $questions,
							'comments'  => $comments
						),
						array('userid' => $userid),
						array('%d','%d','%d','%d'),
						array('%d')
					);
				}

				wp_redirect( htmlspecialchars_decode( wp_nonce_url( admin_url( 'admin.php?page=wpforo-community&wpfaction=wpforo_reset_users_stats&user_lastid=' . end($userids) ), 'wpforo_reset_users_stat' ) ) );
			}else{
				WPF()->notice->add('Updated Successfully!', 'success');
				wp_redirect(admin_url('admin.php?page=wpforo-community'));
			}
			exit();
		}
	}

	/**
	 * rebuild 4 layout forum topics threads root
	 */
	public function rebuild_threads(){
		check_admin_referer('wpforo_rebuild_threads');

		if(!current_user_can('administrator')){
			WPF()->notice->add('Permission denied', 'error');
			wp_redirect(admin_url());
			exit();
		}

		wpforo_set_max_execution_time(3600);
		wp_raise_memory_limit();

		WPF()->topic->rebuild_forum_threads();
		wpforo_clean_cache();
		WPF()->notice->add('Threads rebuilt successfully', 'success');

		wp_redirect(admin_url('admin.php?page=wpforo-community'));
		exit();
	}

	/**
	 * reset phrases cache from db
	 */
	public function reset_phrase_cache(){
		check_admin_referer('wpforo_reset_phrase_cache');

		if(!current_user_can('administrator')){
			WPF()->notice->add('Permission denied', 'error');
			wp_redirect(admin_url());
			exit();
		}

		wpforo_set_max_execution_time();
		wp_raise_memory_limit();

		WPF()->phrase->clear_cache();
		WPF()->notice->add('Deleted Successfully!', 'success');

		wp_redirect(admin_url('admin.php?page=wpforo-community'));
		exit();
	}

	/**
	 * recrawling phrases from all wpforo, wpforo-addons code files
	 */
	public function recrawl_phrases(){
		check_admin_referer('wpforo_recrawl_phrases');

		if(!current_user_can('administrator')){
			WPF()->notice->add('Permission denied', 'error');
			wp_redirect(admin_url());
			exit();
		}

		wpforo_set_max_execution_time();
		wp_raise_memory_limit();

		WPF()->phrase->crawl_phrases();
		WPF()->phrase->clear_cache();
		WPF()->notice->clear();
		WPF()->notice->add('Rebuilt Successfully!', 'success');

		wp_redirect(admin_url('admin.php?page=wpforo-community'));
		exit();
	}

	/**
	 * reset wpforo all caches (phrase, user, forum, post, stats) etc.
	 */
	public function reset_all_caches(){
		check_admin_referer('wpforo_reset_cache');

		if(!current_user_can('administrator')){
			WPF()->notice->add('Permission denied', 'error');
			wp_redirect(admin_url());
			exit();
		}

		wpforo_set_max_execution_time();
		wp_raise_memory_limit();

		WPF()->phrase->clear_cache();
		WPF()->member->clear_db_cache();
		wpforo_clean_cache();
		WPF()->notice->add('Deleted Successfully!', 'success');

		$redirect = ( is_admin() ) ? admin_url('admin.php?page=wpforo-community') : wpforo_home_url();
		wp_redirect( $redirect );
		exit();
	}

	/**
	 * Clean Up damaged content in database
	 */
	public function clean_up(){
		check_admin_referer('wpforo_clean_up');

		if(!current_user_can('administrator')){
			WPF()->notice->add('Permission denied', 'error');
			wp_redirect(admin_url());
			exit();
		}

		wpforo_set_max_execution_time();
		wp_raise_memory_limit();

		wpforo_clean_up();
		WPF()->notice->add('Cleaned Up!', 'success');

		wp_redirect(admin_url('admin.php?page=wpforo-community'));
		exit();
	}

	/**
	 * dashboard_options_save form submit action
	 */
	public function dashboard_options_save(){
		if(!current_user_can('administrator')){
			WPF()->notice->add('Permission denied', 'error');
			wp_redirect(admin_url());
			exit();
		}
		if( $dashboard_count_per_page = (int) wpfval($_POST, 'wpforo_dashboard_count_per_page') ){
			update_option('wpforo_count_per_page', $dashboard_count_per_page );
		}
		wp_redirect( wpforo_get_request_uri() );
		exit();
	}

	/**
	 * checking accesses to forum admin menu pages settings etc...
	 */
	public function check_dashboard_permissions(){
		$page = wpfval(WPF()->GET, 'page');
		if( $page === 'wpforo-settings' ){
			if(!WPF()->perm->usergroup_can('ms')){
				WPF()->notice->add('Permission denied', 'error');
				wp_redirect(admin_url());
				exit();
			}
		}
	}

	/**
	 * check if [wpforo] page has been deleted, restore or create new [wpforo] page
	 */
	public function repair_lost_main_shortcode_page(){
		if( in_array( wpfval(WPF()->GET, 'page'), array('wpforo-community', 'wpforo-settings') ) ){
			$sql = "SELECT `ID` FROM `".WPF()->db->posts."` 
				WHERE `ID` = ".intval(WPF()->pageid)." 
				AND `post_content` LIKE '%[wpforo%' 
				AND `post_status` LIKE 'publish' 
				AND `post_type` IN('". implode("','", wpforo_get_blog_content_types()) ."')";
			if( !WPF()->pageid || !WPF()->db->get_var($sql) ){
				wpforo_create_forum_page();
			}
		}
	}

	/**
	 * general_options_save from submit action
	 */
	public function general_options_save(){
		check_admin_referer( 'wpforo-settings-general' );

		if(!WPF()->perm->usergroup_can('ms')){
			WPF()->notice->add('Permission denied', 'error');
			wp_redirect(admin_url());
			exit();
		}

		if ( !wpfkey( $_POST, 'reset' ) ) {

			if ( isset( $_POST['wpforo_use_home_url'] ) && $_POST['wpforo_use_home_url'] ) {
				$wpforo_use_home_url = 1;
				if ( isset($_POST['wpforo_excld_urls']) ) {
					update_option( 'wpforo_excld_urls', sanitize_textarea_field( $_POST['wpforo_excld_urls'] ) );
				}
			} else {
				$wpforo_use_home_url = 0;
			}
			update_option( 'wpforo_use_home_url', $wpforo_use_home_url );

			if ( isset( $_POST['wpforo_url'] ) && $permastruct = utf8_uri_encode( $_POST['wpforo_url'] ) ) {
				$permastruct = preg_replace( '#^/?index\.php/?#isu', '', trim($permastruct) );
				if( $permastruct = trim( $permastruct, '/' ) ){
					if ( update_option( 'wpforo_url', esc_url( home_url( $permastruct ) ) )
					     && update_option( 'wpforo_permastruct', $permastruct ) ) {
						WPF()->notice->add( 'Forum Base URL successfully updated', 'success' );
					} else {
						WPF()->notice->add( 'Successfully updated', 'success' );
					}

					WPF()->permastruct = $permastruct;
					flush_rewrite_rules( false );
					nocache_headers();
				}
			}

			if ( $wpforo_use_home_url == 0 && ! isset( $_POST['wpforo_url'] ) ) {
				WPF()->permastruct = trim( get_wpf_option( 'wpforo_permastruct' ), '/\\' );
				WPF()->permastruct = preg_replace( '#^/?index\.php/?#isu', '', WPF()->permastruct );
				WPF()->permastruct = trim( WPF()->permastruct, '/\\' );
				WPF()->pageid      = get_wpf_option( 'wpforo_pageid' );
				flush_rewrite_rules( false );
				nocache_headers();
			}

			$general_options = array(
				'title'         => sanitize_text_field( stripslashes($_POST['wpforo_general_options']['title']) ),
				'description'   => sanitize_text_field( stripslashes($_POST['wpforo_general_options']['description']) ),
				'menu_position' => intval( $_POST['wpforo_general_options']['menu_position'] ),
				'lang'          => intval( $_POST['wpforo_general_options']['lang'] )
			);

			if ( update_option( 'wpforo_general_options', $general_options ) ) {
				WPF()->notice->add( 'General options successfully updated', 'success' );
			} else {
				WPF()->notice->add( 'Successfully updated', 'success' );
			}

			if ( ! empty( $_POST['wpforo_tpl_slugs'] ) ) {
				$wpforo_tpl_slugs = array_filter( array_map( 'sanitize_title', $_POST['wpforo_tpl_slugs'] ) );
				$wpforo_tpl_slugs = array_merge( WPF()->tpl->slugs, $wpforo_tpl_slugs );
				$wpforo_tpl_slugs = array_intersect_key($wpforo_tpl_slugs, WPF()->tpl->default->slugs);
				if ( $wpforo_tpl_slugs == array_unique( $wpforo_tpl_slugs ) ) {
					update_option( 'wpforo_tpl_slugs', $wpforo_tpl_slugs );
				} else {
					WPF()->notice->add( 'Please save "Forum template slugs" uniqueness', 'error' );
				}
			}
		}else{
			delete_option( 'wpforo_excld_urls' );
			delete_option( 'wpforo_use_home_url' );
			delete_option( 'wpforo_url' );
			delete_option( 'wpforo_permastruct' );
			delete_option( 'wpforo_general_options' );
			delete_option( 'wpforo_tpl_slugs' );
			WPF()->notice->add( 'General options reset successfully', 'success' );
		}

		WPF()->member->clear_db_cache();
		wpforo_clean_cache();
		wp_redirect( admin_url( 'admin.php?page=wpforo-settings&tab=general' ) );
		exit();
	}

	/**
	 * add_new_xml_translation form submit action
	 */
	public function add_new_xml_translation(){
		check_admin_referer( 'wpforo-settings-language' );

		if(!WPF()->perm->usergroup_can('ms')){
			WPF()->notice->add('Permission denied', 'error');
			wp_redirect(admin_url());
			exit();
		}

		if( !empty($_FILES['add_lang']) ){
			WPF()->phrase->add_lang();
			wpforo_clean_cache();
		}
		wp_redirect( admin_url( 'admin.php?page=wpforo-settings&tab=general' ) );
		exit();
	}

	/**
	 * forum_options_save form submit action
	 */
	public function forum_options_save(){
		check_admin_referer( 'wpforo-settings-forums' );

		if(!WPF()->perm->usergroup_can('ms')){
			WPF()->notice->add('Permission denied', 'error');
			wp_redirect(admin_url());
			exit();
		}

		if( !empty($_POST['wpforo_forum_options']) ){
			if ( !wpfkey( $_POST, 'reset' ) ) {
				if ( update_option( 'wpforo_forum_options', array_map( 'intval', $_POST['wpforo_forum_options'] ) ) ) {
					WPF()->notice->add( 'Forum options successfully updated', 'success' );
				} else {
					WPF()->notice->add( 'Forum options successfully updated, but previous value not changed', 'success' );
				}
			} else {
				delete_option( 'wpforo_forum_options' );
				WPF()->notice->add( 'Forum options reset successfully', 'success' );
			}

			wpforo_clean_cache();
		}
		wp_redirect( admin_url( 'admin.php?page=wpforo-settings&tab=forums' ) );
		exit();
	}

	/**
	 * post_options_save form submit action
	 */
	public function post_options_save(){
		check_admin_referer( 'wpforo-settings-posts' );

		if( !WPF()->perm->usergroup_can('ms') ){
			WPF()->notice->add('Permission denied', 'error');
			wp_redirect(admin_url());
			exit();
		}

		if( isset($_POST['wpforo_post_options']) &&  isset($_POST['wpforo_form_options']) &&  isset($_POST['wpforo_revision_options']) ){
			if( !wpfkey( $_POST, 'reset' ) ){
				$_POST['wpforo_post_options']['eot_durr']        = intval( $_POST['wpforo_post_options']['eot_durr'] ) * 60;
				$_POST['wpforo_post_options']['dot_durr']        = intval( $_POST['wpforo_post_options']['dot_durr'] ) * 60;
				$_POST['wpforo_post_options']['eor_durr']        = intval( $_POST['wpforo_post_options']['eor_durr'] ) * 60;
				$_POST['wpforo_post_options']['dor_durr']        = intval( $_POST['wpforo_post_options']['dor_durr'] ) * 60;
				$_POST['wpforo_post_options']['max_upload_size'] = intval( wpforo_human_size_to_bytes( $_POST['wpforo_post_options']['max_upload_size'] . 'M' ) );
				$_POST['wpforo_post_options']['attach_cant_view_msg'] = wp_unslash( $_POST['wpforo_post_options']['attach_cant_view_msg'] );
				update_option( 'wpforo_post_options', $_POST['wpforo_post_options'] );
				update_option('wpforo_form_options', $_POST['wpforo_form_options']);
				update_option('wpforo_revision_options', $_POST['wpforo_revision_options']);
				WPF()->notice->add( 'Post options successfully updated', 'success' );
			}else{
				delete_option( 'wpforo_post_options' );
				delete_option( 'wpforo_form_options' );
				delete_option( 'wpforo_revision_options' );
				WPF()->notice->add( 'Post options reset successfully', 'success' );
			}

			wpforo_clean_cache();
		}

		wp_redirect( admin_url( 'admin.php?page=wpforo-settings&tab=posts' ) );
		exit();
	}


	/**
	 * member_options_save form submit action
	 */
	public function member_options_save(){
		check_admin_referer( 'wpforo-settings-members' );

		if(!WPF()->perm->usergroup_can('ms')){
			WPF()->notice->add('Permission denied', 'error');
			wp_redirect(admin_url());
			exit();
		}

		if( isset($_POST['wpforo_member_options']) ){
			if( !wpfkey( $_POST, 'reset' ) ){
				$_POST['wpforo_member_options']['online_status_timeout']             = intval( wpfval($_POST['wpforo_member_options'], 'online_status_timeout') ) * 60;
				$_POST['wpforo_member_options']['url_structure']                     = sanitize_title( (string) wpfval($_POST['wpforo_member_options'], 'url_structure') );
				$_POST['wpforo_member_options']['search_type']                       = sanitize_title( (string) wpfval($_POST['wpforo_member_options'], 'search_type') );
				$_POST['wpforo_member_options']['login_url']                         = esc_url_raw( (string) wpfval($_POST['wpforo_member_options'], 'login_url') );
				$_POST['wpforo_member_options']['register_url']                      = esc_url_raw( (string) wpfval($_POST['wpforo_member_options'], 'register_url') );
				$_POST['wpforo_member_options']['lost_password_url']                 = esc_url_raw( (string) wpfval($_POST['wpforo_member_options'], 'lost_password_url') );
				$_POST['wpforo_member_options']['redirect_url_after_login']          = esc_url_raw( (string) wpfval($_POST['wpforo_member_options'], 'redirect_url_after_login') );
				$_POST['wpforo_member_options']['redirect_url_after_register']       = esc_url_raw( (string) wpfval($_POST['wpforo_member_options'], 'redirect_url_after_register') );
				$_POST['wpforo_member_options']['redirect_url_after_confirm_sbscrb'] = esc_url_raw( (string) wpfval($_POST['wpforo_member_options'], 'redirect_url_after_confirm_sbscrb') );
				$_POST['wpforo_member_options']['custom_title_is_on']                = intval( wpfval($_POST['wpforo_member_options'], 'custom_title_is_on') );
				$_POST['wpforo_member_options']['default_title']                     = sanitize_text_field( (string) wpfval($_POST['wpforo_member_options'], 'default_title') );
				$_POST['wpforo_member_options']['rating_title_ug']                   = array_map( 'intval', (array) wpfval($_POST['wpforo_member_options'], 'rating_title_ug') );
				$_POST['wpforo_member_options']['rating_badge_ug']                   = array_map( 'intval', (array) wpfval($_POST['wpforo_member_options'], 'rating_badge_ug') );
				$_POST['wpforo_member_options']['title_usergroup']                   = array_map( 'intval', (array) wpfval($_POST['wpforo_member_options'], 'title_usergroup') );
				$_POST['wpforo_member_options']['title_second_usergroup']            = array_map( 'intval', (array) wpfval($_POST['wpforo_member_options'], 'title_second_usergroup') );
				if( !empty($_POST['wpforo_member_options']['rating']) ){
					foreach ( $_POST['wpforo_member_options']['rating'] as $key => $rating ) {
						$_POST['wpforo_member_options']['rating'][ $key ]           = array_map( 'sanitize_text_field', (array) $rating );
						$_POST['wpforo_member_options']['rating'][ $key ]['points'] = intval( wpfval($rating, 'points') );
					}
				}
				if ( update_option( 'wpforo_member_options', $_POST['wpforo_member_options'] ) ) {
					WPF()->notice->add( 'Member options successfully updated', 'success' );
				} else {
					WPF()->notice->add( 'Member options successfully updated, but previous value not changed', 'success' );
				}
			}else{
				delete_option( 'wpforo_member_options' );
				$exlude = array( 'rating_title_ug', 'rating_badge_ug' );
				wpforo_update_options( 'wpforo_member_options', WPF()->member->default->options, $exlude );

				WPF()->notice->add( 'Member options reset successfully', 'success' );
			}

			wpforo_clean_cache();
		}

		wp_redirect( admin_url( 'admin.php?page=wpforo-settings&tab=members' ) );
		exit();
	}

	/**
	 * features_options_save form submit action
	 */
	public function features_options_save(){
		check_admin_referer( 'wpforo-features' );

		if(!WPF()->perm->usergroup_can('ms')){
			WPF()->notice->add('Permission denied', 'error');
			wp_redirect(admin_url());
			exit();
		}

		if( isset($_POST['wpforo_features']) ){
			if ( !wpfkey( $_POST, 'reset' ) ) {
				if ( update_option( 'wpforo_features', array_map( 'intval', $_POST['wpforo_features'] ) ) ) {
					WPF()->notice->add( 'Features successfully updated', 'success' );
				} else {
					WPF()->notice->add( 'Features successfully updated, but previous value not changed', 'success' );
				}
			} else {
				delete_option( 'wpforo_features' );
				WPF()->notice->add( 'Features reset successfully', 'success' );
			}
			wpforo_clean_cache();
			WPF()->seo->clear_cache();
		}

		wp_redirect( admin_url( 'admin.php?page=wpforo-settings&tab=features' ) );
		exit();
	}

	/**
	 * api_options_save form submit action
	 */
	public function api_options_save(){
		check_admin_referer( 'wpforo-settings-api' );

		if(!WPF()->perm->usergroup_can('ms')){
			WPF()->notice->add('Permission denied', 'error');
			wp_redirect(admin_url());
			exit();
		}

		if( isset($_POST['wpforo_api_options']) ){
			if( !wpfkey( $_POST, 'reset' ) ){
				if ( update_option( 'wpforo_api_options', $_POST['wpforo_api_options'] ) ) {
					WPF()->notice->add( 'API options successfully updated', 'success' );
				} else {
					WPF()->notice->add( 'API options successfully updated, but previous value not changed', 'success' );
				}
			}else{
				delete_option( 'wpforo_api_options' );
				WPF()->notice->add( 'API options reset successfully', 'success' );
			}
		}

		wp_redirect( admin_url( 'admin.php?page=wpforo-settings&tab=api' ) );
		exit();
	}

	/**
	 * theme_style_options_save form submit action
	 */
	public function theme_style_options_save(){
		check_admin_referer( 'wpforo-settings-styles' );

		if(!WPF()->perm->usergroup_can('ms')){
			WPF()->notice->add('Permission denied', 'error');
			wp_redirect(admin_url());
			exit();
		}

		if( isset($_POST['wpforo_theme_options']) && isset($_POST['wpforo_style_options']) ){
			if( !wpfkey( $_POST, 'reset' ) ){
				//Theme Options//////////////////////////////////////////////////////////////////////
				$_POST['wpforo_theme_options']['style'] = sanitize_title($_POST['wpforo_theme_options']['style']);
				foreach($_POST['wpforo_theme_options']['styles'] as $key => $rating){
					$_POST['wpforo_theme_options']['styles'][$key] = array_map('sanitize_text_field', $rating);
				}
				WPF()->tpl->options['style'] = sanitize_text_field($_POST['wpforo_theme_options']['style']);
				WPF()->tpl->options['styles'] = $_POST['wpforo_theme_options']['styles'];
				//Style Options/////////////////////////////////////////////////////////////////////
				$_POST['wpforo_style_options']['font_size_forum'] = intval($_POST['wpforo_style_options']['font_size_forum']);
				$_POST['wpforo_style_options']['font_size_topic'] = intval($_POST['wpforo_style_options']['font_size_topic']);
				$_POST['wpforo_style_options']['font_size_post_content'] = intval($_POST['wpforo_style_options']['font_size_post_content']);
				$_POST['wpforo_style_options']['custom_css'] = sanitize_textarea_field($_POST['wpforo_style_options']['custom_css']);
				////////////////////////////////////////////////////////////////////////////////////
				update_option('wpforo_style_options', $_POST['wpforo_style_options']);
				update_option('wpforo_theme_options', WPF()->tpl->options);
				WPF()->notice->add('Theme options successfully updated', 'success');
			}else{
				delete_option( 'wpforo_style_options' );
				delete_option( 'wpforo_theme_options' );
				WPF()->notice->add( 'Theme options reset successfully', 'success' );
			}
			wpforo_clean_cache();
		}

		wp_redirect( admin_url( 'admin.php?page=wpforo-settings&tab=styles' ) );
		exit();
	}

	/**
	 * colors.css download action
	 */
	public function colors_css_download(){
		check_admin_referer('dynamic_css_download');

		if(!WPF()->perm->usergroup_can('ms')){
			WPF()->notice->add('Permission denied', 'error');
			wp_redirect(admin_url());
			exit();
		}

		$dynamic_css = WPF()->tpl->generate_dynamic_css();
		header('Content-Type: application/download');
		header('Content-Disposition: attachment; filename="colors.css"');
		header('Content-Transfer-Encoding: binary');
		header("Content-Length: " . strlen($dynamic_css));
		echo $dynamic_css;
		exit();
	}

	/**
	 * email_options_save form submit action
	 */
	public function email_options_save(){
		check_admin_referer( 'wpforo-settings-emails' );

		if(!WPF()->perm->usergroup_can('ms')){
			WPF()->notice->add('Permission denied', 'error');
			wp_redirect(admin_url());
			exit();
		}

		if( $options = wpfval($_POST, 'wpforo_email_options') ){
			if( !wpfkey( $_POST, 'reset' ) ){
				$options['from_name']                                    = sanitize_text_field( $options['from_name'] );
				$options['from_email']                                   = sanitize_text_field( $options['from_email'] );
				$options['admin_emails']                                 = sanitize_text_field( $options['admin_emails'] );
				$options['new_topic_notify']                             = intval( $options['new_topic_notify'] );
				$options['new_reply_notify']                             = intval( $options['new_reply_notify'] );
				$options['confirmation_email_subject']                   = sanitize_text_field( $options['confirmation_email_subject'] );
				$options['confirmation_email_message']                   = wpforo_kses( $options['confirmation_email_message'], 'email' );
				$options['new_topic_notification_email_subject']         = sanitize_text_field( $options['new_topic_notification_email_subject'] );
				$options['new_topic_notification_email_message']         = wpforo_kses( $options['new_topic_notification_email_message'], 'email' );
				$options['new_post_notification_email_subject']          = sanitize_text_field( $options['new_post_notification_email_subject'] );
				$options['new_post_notification_email_message']          = wpforo_kses( $options['new_post_notification_email_message'], 'email' );
				$options['report_email_subject']                         = sanitize_text_field( $options['report_email_subject'] );
				$options['report_email_message']                         = wpforo_kses( $options['report_email_message'], 'email' );
				$options['overwrite_new_user_notification_admin']        = intval( $options['overwrite_new_user_notification_admin'] );
				$options['wp_new_user_notification_email_admin_subject'] = sanitize_text_field( $options['wp_new_user_notification_email_admin_subject'] );
				$options['wp_new_user_notification_email_admin_message'] = wpforo_kses( $options['wp_new_user_notification_email_admin_message'], 'email' );
				$options['overwrite_new_user_notification']              = intval( $options['overwrite_new_user_notification'] );
				$options['wp_new_user_notification_email_subject']       = sanitize_text_field( $options['wp_new_user_notification_email_subject'] );
				$options['wp_new_user_notification_email_message']       = wpforo_kses( $options['wp_new_user_notification_email_message'], 'email' );
				$options['overwrite_reset_password_email_message']       = intval( $options['overwrite_reset_password_email_message'] );
				$options['reset_password_email_message']                 = wpforo_kses( $options['reset_password_email_message'], 'email' );
				$options['user_mention_notify']                          = intval( $options['user_mention_notify'] );
				$options['user_mention_email_subject']                   = sanitize_text_field( $options['user_mention_email_subject'] );
				$options['user_mention_email_message']                   = wpforo_kses( $options['user_mention_email_message'], 'email' );
				if ( update_option( 'wpforo_subscribe_options', $options ) ) {
					WPF()->notice->add( 'Email options successfully updated', 'success' );
				} else {
					WPF()->notice->add( 'Email options successfully updated, but previous value not changed', 'success' );
				}
			}else{
				delete_option( 'wpforo_subscribe_options' );
				WPF()->notice->add( 'Email options reset successfully', 'success' );
			}
			wpforo_clean_cache();
		}

		wp_redirect( admin_url( 'admin.php?page=wpforo-settings&tab=emails' ) );
		exit();
	}

	/**
	 * antispam_options_save form submit action
	 */
	public function antispam_options_save(){
		check_admin_referer( 'wpforo-tools-antispam' );

		if(!WPF()->perm->usergroup_can('mt')){
			WPF()->notice->add('Permission denied', 'error');
			wp_redirect(admin_url());
			exit();
		}

		if ( ! wpfkey( $_POST, 'reset' ) ) {
			if ( $options = wpfval( $_POST, 'wpforo_tools_antispam' ) ) {
				$options['spam_filter']                = intval( $options['spam_filter'] );
				$options['spam_user_ban']              = intval( $options['spam_user_ban'] );
				$options['spam_user_ban_notification'] = intval( $options['spam_user_ban_notification'] );
				$options['spam_filter_level_topic']    = intval( $options['spam_filter_level_topic'] );
				$options['spam_filter_level_post']     = intval( $options['spam_filter_level_post'] );
				$options['new_user_max_posts']         = intval( $options['new_user_max_posts'] );
				$options['min_number_post_to_attach']  = intval( $options['min_number_post_to_attach'] );
				$options['min_number_post_to_link']    = intval( $options['min_number_post_to_link'] );
				$options['limited_file_ext']           = sanitize_textarea_field( $options['limited_file_ext'] );
				$options['rc_site_key']                = sanitize_text_field( $options['rc_site_key'] );
				$options['rc_secret_key']              = sanitize_text_field( $options['rc_secret_key'] );
				$options['rc_theme']                   = sanitize_text_field( $options['rc_theme'] );
				$options['rc_topic_editor']            = intval( $options['rc_topic_editor'] );
				$options['rc_post_editor']             = intval( $options['rc_post_editor'] );
				$options['rc_wpf_login_form']          = intval( $options['rc_wpf_login_form'] );
				$options['rc_wpf_reg_form']            = intval( $options['rc_wpf_reg_form'] );
				$options['rc_wpf_lostpass_form']       = intval( $options['rc_wpf_lostpass_form'] );
				$options['rc_login_form']              = intval( $options['rc_login_form'] );
				$options['rc_reg_form']                = intval( $options['rc_reg_form'] );
				$options['rc_lostpass_form']           = intval( $options['rc_lostpass_form'] );
				$options['html']                       = sanitize_textarea_field( $options['html'] );
				$options['spam_file_scanner']          = intval( $options['spam_file_scanner'] );
				$options['exclude_file_ext']           = sanitize_textarea_field( $options['exclude_file_ext'] );
				if ( update_option( 'wpforo_tools_antispam', $options ) ) {
					WPF()->notice->add( 'Settings successfully updated', 'success' );
				}
			}
		} else {
			delete_option( 'wpforo_tools_antispam' );
			WPF()->notice->add( 'Antispam options reset successfully', 'success' );
		}

		wp_redirect( admin_url( 'admin.php?page=wpforo-tools&tab=antispam' ) );
		exit();
	}

	/**
	 * cleanup_options_save form submit action
	 */
	public function cleanup_options_save(){
		check_admin_referer( 'wpforo-tools-cleanup' );

		if(!WPF()->perm->usergroup_can('mt')){
			WPF()->notice->add('Permission denied', 'error');
			wp_redirect(admin_url());
			exit();
		}

		if ( ! wpfkey( $_POST, 'reset' ) ) {
			if ( $options = wpfval( $_POST, 'wpforo_tools_cleanup' ) ) {
				if ( update_option( 'wpforo_tools_cleanup', $options ) ) {
					WPF()->notice->add( 'Settings successfully updated', 'success' );
				}
			}
		} else {
			delete_option( 'wpforo_tools_cleanup' );
			WPF()->notice->add( 'Cleanup options reset successfully', 'success' );
		}

		wp_redirect( admin_url( 'admin.php?page=wpforo-tools&tab=cleanup' ) );
		exit();
	}

	/**
	 * misc_options_save form submit action
	 */
	public function misc_options_save(){
		check_admin_referer( 'wpforo-tools-misc' );

		if(!WPF()->perm->usergroup_can('mt')){
			WPF()->notice->add('Permission denied', 'error');
			wp_redirect(admin_url());
			exit();
		}

		if ( ! wpfkey( $_POST, 'reset' ) ) {
			if ( $options = wpfval( $_POST, 'wpforo_tools_misc' ) ) {
				$options['dofollow']          = sanitize_textarea_field( $options['dofollow'] );
				$options['noindex']           = sanitize_textarea_field( $options['noindex'] );
				$options['admin_note']        = wpforo_kses( $options['admin_note'] );
				$options['admin_note_groups'] = ( wpfval( $_POST, 'wpforo_tools_misc', 'admin_note_groups' ) ) ? array_map( 'intval', $options['admin_note_groups'] ) : array();
				$options['admin_note_pages']  = ( wpfval( $_POST, 'wpforo_tools_misc', 'admin_note_pages' ) ) ? array_map( 'sanitize_textarea_field', $options['admin_note_pages'] ) : array();
				if ( update_option( 'wpforo_tools_misc', $options ) ) {
					wpforo_clean_cache( 'forum-soft' );
					WPF()->notice->add( 'Settings successfully updated', 'success' );
				}
			}
		} else {
			delete_option( 'wpforo_tools_misc' );
			WPF()->notice->add( 'Misc options reset successfully', 'success' );
		}

		wp_redirect( admin_url( 'admin.php?page=wpforo-tools&tab=misc' ) );
		exit();
	}

	/**
	 * legal_options_save form submit action
	 */
	public function legal_options_save(){
		check_admin_referer( 'wpforo-tools-legal' );

		if(!WPF()->perm->usergroup_can('mt')){
			WPF()->notice->add('Permission denied', 'error');
			wp_redirect(admin_url());
			exit();
		}

		if ( ! wpfkey( $_POST, 'reset' ) ) {
			if ( $options = wpfval( $_POST, 'wpforo_tools_legal' ) ) {
				$options['contact_page_url']        = esc_url( $options['contact_page_url'] );
				$options['checkbox_terms_privacy']  = intval( $options['checkbox_terms_privacy'] );
				$options['checkbox_email_password'] = intval( $options['checkbox_email_password'] );
				$options['page_terms']              = esc_url( $options['page_terms'] );
				$options['page_privacy']            = esc_url( $options['page_privacy'] );
				$options['checkbox_forum_privacy']  = intval( $options['checkbox_forum_privacy'] );
				$options['forum_privacy_text']      = wpforo_kses( $options['forum_privacy_text'], 'post' );
				$options['checkbox_fb_login']       = intval( $options['checkbox_fb_login'] );
				$options['cookies']                 = intval( $options['cookies'] );
				$options['rules_checkbox']          = intval( $options['rules_checkbox'] );
				$options['rules_text']              = wpforo_kses( $options['rules_text'], 'post' );
				if ( update_option( 'wpforo_tools_legal', $options ) ) {
					WPF()->notice->add( 'Settings successfully updated', 'success' );
				}
			}
		} else {
			delete_option( 'wpforo_tools_legal' );
			WPF()->notice->add( 'Settings reset successfully', 'success' );
		}

		wp_redirect( admin_url( 'admin.php?page=wpforo-tools&tab=legal' ) );
		exit();
	}

	/**
	 * delete detected spam file
	 */
	public function delete_spam_file(){
		check_admin_referer( 'wpforo_tools_antispam_files');

		if(!WPF()->perm->usergroup_can('mt')){
			WPF()->notice->add('Permission denied', 'error');
			wp_redirect(admin_url());
			exit();
		}

		if( $filename = trim(wpfval($_GET, 'sfname')) ){
			$filename = str_replace( array('../', './', '/'), '', sanitize_file_name($filename) );
			$filename = urldecode( $filename );
			if($filename){
				$upload_dir = wp_upload_dir();
				$default_attachments_dir =  $upload_dir['basedir'] . '/wpforo/default_attachments/';
				$file = $default_attachments_dir . $filename;
				$attachmentid = WPF()->post->get_attachment_id( '/' . $filename );
				if ( !wp_delete_attachment( $attachmentid ) ){
					@unlink($file);
				}
				WPF()->notice->add( 'Deleted', 'success' );
			}
		}

		wp_redirect( admin_url( 'admin.php?page=wpforo-tools&tab=antispam' ) );
		exit();
	}

	/**
	 * delete_all_spam_files all detected spam file using level attribute
	 */
	public function delete_all_spam_files(){
		check_admin_referer( 'wpforo_tools_antispam_files');

		if(!WPF()->perm->usergroup_can('mt')){
			WPF()->notice->add('Permission denied', 'error');
			wp_redirect(admin_url());
			exit();
		}

		if( $delete_level = intval(wpfval($_GET, 'level')) ){
			$upload_dir = wp_upload_dir();
			$default_attachments_dir =  $upload_dir['basedir'] . '/wpforo/default_attachments/';
			if(is_dir($default_attachments_dir)){
				if ($handle = opendir($default_attachments_dir)){
					while (false !== ($filename = readdir($handle))){
						if( $filename === '.' ||  $filename === '..') continue;
						if( !$level = WPF()->moderation->spam_file($filename) ) continue;
						if( $delete_level === $level ){
							$attachmentid = WPF()->post->get_attachment_id( '/' . $filename );
							if ( !wp_delete_attachment( $attachmentid ) ){
								$file = $default_attachments_dir . $filename;
								@unlink($file);
							}
						}
					}
					closedir($handle);
					WPF()->notice->add( 'Deleted', 'success' );
				}
			}
		}

		wp_redirect( admin_url( 'admin.php?page=wpforo-tools&tab=antispam' ) );
		exit();
	}

	/**
	 * do database alter fixing using install.sql db-strukture
	 */
	public function database_update(){
		check_admin_referer( 'wpforo_update_database' );

		if(!WPF()->perm->usergroup_can('mt')){
			WPF()->notice->add('Permission denied', 'error');
			wp_redirect(admin_url());
			exit();
		}

		wpforo_set_max_execution_time(3600);

		wpforo_update_db();

		wp_redirect( admin_url( 'admin.php?page=wpforo-tools&tab=debug&view=tables' ) );
		exit();
	}

	/**
	 * forum_add form submit action
	 */
	public function forum_add(){
		check_admin_referer( 'wpforo-forum-add' );

		if( !WPF()->forum->manage() ){
			WPF()->notice->add('Permission denied', 'error');
			wp_redirect(admin_url());
			exit();
		}

		if( !empty($_REQUEST['forum']) ){
			WPF()->forum->add();
		}

		wp_redirect( admin_url( 'admin.php?page=wpforo-forums' ) );
		exit();
	}

	/**
	 * forum_edit form submit action
	 */
	public function forum_edit(){
		check_admin_referer( 'wpforo-forum-edit' );

		if( !WPF()->forum->manage() ){
			WPF()->notice->add('Permission denied', 'error');
			wp_redirect(admin_url());
			exit();
		}

		if( !empty($_REQUEST['forum']) ){
			WPF()->forum->edit();
		}

		wp_redirect( wpforo_get_request_uri() );
		exit();
	}

	/**
	 * forum_delete form submit action
	 */
	public function forum_delete(){
		check_admin_referer( 'wpforo-forum-delete' );

		if( !WPF()->forum->manage() ){
			WPF()->notice->add('Permission denied', 'error');
			wp_redirect(admin_url());
			exit();
		}

		$delete = (int) wpfval($_REQUEST, 'forum', 'delete');
		if( $delete === 1 ){
			WPF()->forum->delete();
		}elseif( $delete === 0 ){
			WPF()->forum->merge();
		}

		wp_redirect( admin_url( 'admin.php?page=wpforo-forums' ) );
		exit();
	}

	/**
	 * forum_hierarchy_save form submit action
	 */
	public function forum_hierarchy_save(){
		check_admin_referer( 'wpforo-forums-hierarchy' );

		if( !WPF()->forum->manage() ){
			WPF()->notice->add('Permission denied', 'error');
			wp_redirect(admin_url());
			exit();
		}

		if( !empty($_REQUEST['forum']) ){
			WPF()->forum->update_hierarchy();
			wpforo_clean_cache('forum');
		}

		wp_redirect( admin_url( 'admin.php?page=wpforo-forums' ) );
		exit();
	}

	/**
	 * dashboard_post_unapprove action
	 */
	public function dashboard_post_unapprove(){
		$postid = wpfval($_GET, 'postid');
		check_admin_referer( "wpforo-unapprove-post-{$postid}" );

		if(!WPF()->perm->usergroup_can('aum')){
			WPF()->notice->add('Permission denied', 'error');
			wp_redirect(admin_url());
			exit();
		}

		WPF()->moderation->post_unapprove($postid);
		wpforo_clean_cache('post', $postid);

		wp_safe_redirect( wp_get_referer() );
		exit();
	}

	/**
	 * dashboard_post_approve action
	 */
	public function dashboard_post_approve(){
		$postid = wpfval($_GET, 'postid');
		check_admin_referer( "wpforo-approve-post-{$postid}" );

		if(!WPF()->perm->usergroup_can('aum')){
			WPF()->notice->add('Permission denied', 'error');
			wp_redirect(admin_url());
			exit();
		}

		WPF()->moderation->post_approve($postid);
		wpforo_clean_cache('post', $postid);

		wp_safe_redirect( wp_get_referer() );
		exit();
	}

	/**
	 * dashboard_post_delete action
	 */
	public function dashboard_post_delete(){
		$postid = wpfval($_GET, 'postid');
		check_admin_referer( "wpforo-delete-post-{$postid}" );

		if(!WPF()->perm->usergroup_can('aum')){
			WPF()->notice->add('Permission denied', 'error');
			wp_redirect(admin_url());
			exit();
		}

		WPF()->post->delete($postid);

		wp_safe_redirect( wp_get_referer() );
		exit();
	}

	/**
	 * doing bulk moderation actions ( approve, unapprove, delete )
	 */
	public  function bulk_moderation(){
		check_admin_referer('bulk-moderations');

		if(!WPF()->perm->usergroup_can('aum')){
			WPF()->notice->add('Permission denied', 'error');
			wp_redirect(admin_url());
			exit();
		}

		$u_action = $this->get_current_bulk_action();
		$postids = (array) wpfval($_GET, 'postids');
		if( $u_action && !empty($postids) ) {
			if ($u_action === 'delete') {
				foreach($postids as $postid) WPF()->post->delete($postid);
			} elseif ($u_action === 'approve') {
				foreach($postids as $postid) {
					if( $postid ){
						WPF()->moderation->post_approve($postid);
						//Email Notification ////////////////////////////////////////////////////////////
						$post = WPF()->post->get_post($postid);
						wpforo_clean_cache('post', $postid, $post);
						if( !empty($post) && isset($post['is_first_post']) && $post['is_first_post'] ){
							wpforo_send_mail_to_mentioned_users( $post );
							if( isset($post['topicid']) && $post['topicid'] ){
								$topic = WPF()->topic->get_topic($post['topicid']);
								if( !empty($topic) ){
									wpforo_forum_subscribers_mail_sender( $topic );
								}
							}
						}
						/////////////////////////////////////////////////////////////////////////////////
					}
				}
			} elseif ($u_action === 'unapprove') {
				foreach ($postids as $postid) {
					WPF()->moderation->post_unapprove($postid);
					wpforo_clean_cache('post', $postid);
				}

			}
		}

		wp_safe_redirect( wp_get_referer() );
		exit();
	}

	/**
	 * phrase_add form submit action
	 */
	public function phrase_add(){
		check_admin_referer( 'wpforo-phrase-add' );

		if(!WPF()->perm->usergroup_can('mp')){
			WPF()->notice->add('Permission denied', 'error');
			wp_redirect(admin_url());
			exit();
		}

		if( !empty($_POST['phrase']) ){
			WPF()->phrase->add();
		}

		wp_redirect( admin_url( 'admin.php?page=wpforo-phrases' ) );
		exit();
	}

	/**
	 * phrase_edit_form action redirect to phrases list page when phraseid(s) not choosed
	 */
	public function phrase_edit_form(){
		$phraseids = array_filter( array_map('intval', array_merge((array) wpfval($_GET, 'phraseid'), (array) wpfval($_GET, 'phraseids')) ) );
		if( !$phraseids ){
			wp_redirect( admin_url( 'admin.php?page=wpforo-phrases' ) );
			exit();
		}
	}

	/**
	 * phrase_edit form submit action
	 */
	public function phrase_edit(){
		check_admin_referer( 'wpforo-phrases-edit' );

		if(!WPF()->perm->usergroup_can('mp')){
			WPF()->notice->add('Permission denied', 'error');
			wp_redirect(admin_url());
			exit();
		}

		if( !empty($_POST['phrases']) ){
			WPF()->phrase->edit();
		}

		wp_redirect( admin_url( 'admin.php?page=wpforo-phrases' ) );
		exit();
	}

	/**
	 * user_ban action
	 */
	public function user_ban(){
		$userid = intval( wpfval($_GET, 'userid') );
		check_admin_referer( 'wpforo-user-ban-' . $userid );

		if( !WPF()->perm->usergroup_can('vm') || !WPF()->perm->usergroup_can('bm') || intval($userid) === intval(WPF()->current_userid) ){
			WPF()->notice->add('Permission denied', 'error');
			wp_redirect(admin_url());
			exit();
		}

		WPF()->member->ban($userid);
		wpforo_clean_cache('user');

		wp_safe_redirect( wp_get_referer() );
		exit();
	}

	/**
	 * user_unban action
	 */
	public function user_unban(){
		$userid = intval( wpfval($_GET, 'userid') );
		check_admin_referer( 'wpforo-user-unban-' . $userid );

		if( !WPF()->perm->usergroup_can('vm') || !WPF()->perm->usergroup_can('bm') || intval($userid) === intval(WPF()->current_userid) ){
			WPF()->notice->add('Permission denied', 'error');
			wp_redirect(admin_url());
			exit();
		}

		WPF()->member->unban($userid);
		wpforo_clean_cache('user');

		wp_safe_redirect( wp_get_referer() );
		exit();
	}

	/**
	 * action after wordpress native deleted_user hook
	 * @param int $userid already deleted user ID
	 */
	public function user_delete($userid){
		$reassign_userid = wpforo_bigintval( wpfval($_REQUEST, 'wpforo_reassign_user') );
		if( wpfval($_REQUEST, 'wpforo_user_delete_option') === 'reassign' && $reassign_userid ){
			WPF()->member->delete( $userid, $reassign_userid );
		}else{
			WPF()->member->delete( $userid );
		}
		WPF()->notice->clear();
	}

	/**
	 * doing bulk member actions ( ban, unban, delete )
	 */
	public  function bulk_members(){
		check_admin_referer('bulk-members');

		if( !WPF()->perm->usergroup_can('vm') ){
			WPF()->notice->add('Permission denied', 'error');
			wp_redirect(admin_url());
			exit();
		}

		$new_groupid = -1;
		if( !empty($_GET['new_groupid']) && $_GET['new_groupid'] !== '-1' ){
			$new_groupid = intval($_GET['new_groupid']);
		}elseif( !empty($_GET['new_groupid2']) && $_GET['new_groupid2'] !== '-1' ){
			$new_groupid = intval($_GET['new_groupid2']);
		}

		$u_action = $this->get_current_bulk_action();
		if( in_array($u_action, array('ban', 'unban')) && !WPF()->perm->usergroup_can('bm') ){
			WPF()->notice->add('Permission denied', 'error');
			wp_redirect(admin_url());
			exit();
		}elseif ( $u_action === 'delete' && !WPF()->perm->usergroup_can('dm') ){
			WPF()->notice->add('Permission denied', 'error');
			wp_redirect(admin_url());
			exit();
		}

		$userids = (array) wpfval($_GET, 'userids');
		$userids = array_filter( array_map('wpforo_bigintval', $userids) );
		$userids = array_diff($userids, (array) WPF()->current_userid);
		if( $u_action && !empty($userids) ) {
			if ($u_action === 'delete') {
				$url = self_admin_url( 'users.php?action=delete&users[]=' . implode( '&users[]=', $userids ) );
				$url = str_replace( '&amp;', '&', wp_nonce_url( $url, 'bulk-users' ) );
				wp_redirect( $url );
				exit();
			} elseif ($u_action === 'ban') {
				foreach($userids as $userid) {
					WPF()->member->ban($userid);
				}
			} elseif ($u_action === 'unban') {
				foreach ($userids as $userid) {
					WPF()->member->unban($userid);
				}

			}
		}elseif( !$u_action && wpfkey($_GET, 'change_group') ){
			if( !empty($userids) && $new_groupid !== -1 ){
				$status = WPF()->usergroup->set_users_groupid(array($new_groupid => $userids));
				if( $status['success'] ) WPF()->notice->add('Usergroup is successfully changed for selected users', 'success');
			}else{
				WPF()->notice->add('Please select users and usergroup', 'error');
			}
		}
		wpforo_clean_cache('user');

		wp_safe_redirect( wp_get_referer() );
		exit();
	}

	/**
	 * usergroup_add form submit action
	 */
	public function usergroup_add(){
		check_admin_referer('wpforo-usergroup-add');

		if(!WPF()->perm->usergroup_can('vmg')){
			WPF()->notice->add('Permission denied', 'error');
			wp_redirect(admin_url());
			exit();
		}

		if( !empty($_POST['usergroup']) ){
			$group = WPF()->usergroup->fix_group($_POST['usergroup']);
			$color = wpfval( $group, 'wpfugc' ) ? '' : sanitize_text_field( $group['color'] );
			$groupid   = WPF()->usergroup->add( $group['name'], $group['cans'], $group['description'], $group['role'], $group['access'], $color, $group['visible'], $group['secondary'] );
			if($groupid) wpforo_clean_cache( 'loop', $groupid );
		}

		wp_redirect( admin_url( 'admin.php?page=wpforo-usergroups' ) );
		exit();
	}

	/**
	 * usergroup_edit form submit action
	 */
	public function usergroup_edit(){
		check_admin_referer('wpforo-usergroup-edit');

		if(!WPF()->perm->usergroup_can('vmg')){
			WPF()->notice->add('Permission denied', 'error');
			wp_redirect(admin_url());
			exit();
		}

		if( !empty($_POST['usergroup']) ){
			$group = WPF()->usergroup->fix_group($_POST['usergroup']);
			$color = wpfval( $group, 'wpfugc' ) ? '' : sanitize_text_field( $group['color'] );
			WPF()->usergroup->edit( $group['groupid'], $group['name'], $group['cans'], $group['description'], $group['role'], NULL, $color, $group['visible'], $group['secondary'] );
			wpforo_clean_cache( 'loop', $group['groupid'] );
		}

		wp_redirect( admin_url( 'admin.php?page=wpforo-usergroups' ) );
		exit();
	}

	/**
	 * usergroup_delete form submit action
	 */
	public function usergroup_delete(){
		check_admin_referer('wpforo-usergroup-delete');

		if(!WPF()->perm->usergroup_can('vmg')){
			WPF()->notice->add('Permission denied', 'error');
			wp_redirect(admin_url());
			exit();
		}

		if( wpfval($_POST, 'usergroup', 'delete') ){
			$args = array('groupid' => wpfval($_POST, 'usergroup', 'groupid'));
			if( $userids = WPF()->member->get_userids($args) ){
				$redirect_to = self_admin_url( 'users.php?action=delete&users[]=' . implode( '&users[]=', $userids ) );
				$redirect_to = str_replace( '&amp;', '&', wp_nonce_url($redirect_to, 'bulk-users') );
				wp_redirect($redirect_to);
				exit();
			}
		}

		if( !empty($_POST['usergroup']) ){
			WPF()->usergroup->delete(wpfval($_POST['usergroup'], 'groupid'), wpfval($_POST['usergroup'], 'mergeid'));
			wpforo_clean_cache('user');
		}

		wp_redirect( admin_url( 'admin.php?page=wpforo-usergroups' ) );
		exit();
	}

	/**
	 * default_groupid_change action
	 */
	public function default_groupid_change(){
		$default_groupid = intval( wpfval($_GET, 'default_groupid') );
		check_admin_referer('wpforo-default-groupid-change-' . $default_groupid);

		if(!WPF()->perm->usergroup_can('vmg')){
			WPF()->notice->add('Permission denied', 'error');
			wp_redirect(admin_url());
			exit();
		}

		if($default_groupid) update_option('wpforo_default_groupid', $default_groupid);

		wp_redirect( admin_url( 'admin.php?page=wpforo-usergroups' ) );
		exit();
	}

	/**
	 * prevent to show usergroup delete form when !$groupid || $groupid <= 5
	 */
	public function usergroup_delete_form() {
		if( intval(wpfval($_GET, 'groupid')) <= 5 ){
			wp_redirect( admin_url( 'admin.php?page=wpforo-usergroups' ) );
			exit();
		}
	}

	/**
	 * access_add form submit action
	 */
	public function access_add(){
		check_admin_referer('wpforo-access-add');

		if(!WPF()->perm->usergroup_can('ms')){
			WPF()->notice->add('Permission denied', 'error');
			wp_redirect(admin_url());
			exit();
		}

		if( !empty($_POST['access']) ){
			WPF()->perm->add( WPF()->perm->fix_access($_POST['access']) );
		}

		wp_redirect( admin_url( 'admin.php?page=wpforo-settings&tab=accesses' ) );
		exit();
	}

	/**
	 * access_edit form submit action
	 */
	public function access_edit(){
		check_admin_referer('wpforo-access-edit');

		if(!WPF()->perm->usergroup_can('ms')){
			WPF()->notice->add('Permission denied', 'error');
			wp_redirect(admin_url());
			exit();
		}

		if( !empty($_POST['access']) ){
			WPF()->perm->edit( WPF()->perm->fix_access($_POST['access']) );
			wpforo_clean_cache('loop');
		}

		wp_redirect( admin_url( 'admin.php?page=wpforo-settings&tab=accesses' ) );
		exit();
	}

	/**
	 * access_delete form submit action
	 */
	public function access_delete(){
		$accessid = intval(wpfval($_GET, 'accessid'));
		check_admin_referer('wpforo-access-delete-' . $accessid);

		if(!WPF()->perm->usergroup_can('ms')){
			WPF()->notice->add('Permission denied', 'error');
			wp_redirect(admin_url());
			exit();
		}

		WPF()->perm->delete($accessid);
		wpforo_clean_cache( 'loop' );

		wp_redirect( admin_url( 'admin.php?page=wpforo-settings&tab=accesses' ) );
		exit();
	}

	/**
	 * theme_activate action
	 */
	public function theme_activate(){
		check_admin_referer('wpforo-theme-activate');

		if(!WPF()->perm->usergroup_can('mth')){
			WPF()->notice->add('Permission denied', 'error');
			wp_redirect(admin_url());
			exit();
		}

		$notice = __('Theme activate error', 'wpforo');
		$notice_type = 'error';
		if( $theme = wpfval($_GET, 'theme') ){
			$theme = trim(sanitize_text_field($theme));
			if( $theme && WPF()->tpl->theme !== $theme ){
				$new_theme = get_option( 'wpforo_theme_archive_' . $theme );
				if( !empty($new_theme) ){
					update_option( 'wpforo_theme_options', $new_theme );
					update_option( 'wpforo_theme_archive_' . WPF()->tpl->theme, WPF()->tpl->options );
					$notice = __('Theme activate success', 'wpforo');
					$notice_type = 'success';
				}
			}
		}

		WPF()->notice->add($notice, $notice_type);
		wp_redirect( admin_url( 'admin.php?page=wpforo-themes' ) );
		exit();
	}

	/**
	 * theme_install action
	 */
	public function theme_install(){
		check_admin_referer('wpforo-theme-install');

		if(!WPF()->perm->usergroup_can('mth')){
			WPF()->notice->add('Permission denied', 'error');
			wp_redirect(admin_url());
			exit();
		}

		$notice = __('Theme install error', 'wpforo');
		$notice_type = 'error';
		if( $theme = wpfval($_GET, 'theme') ){
			$theme = trim(sanitize_text_field($theme));
			if( $theme && WPF()->tpl->theme !== $theme ){
				$new_theme = WPF()->tpl->find_theme($theme);
				if( !empty($new_theme) ){
					update_option( 'wpforo_theme_options', $new_theme );
					update_option( 'wpforo_theme_archive_' . WPF()->tpl->theme, WPF()->tpl->options );
					$notice = __('Theme install success', 'wpforo');
					$notice_type = 'success';
				}
			}
		}

		WPF()->notice->add($notice, $notice_type);
		wp_redirect( admin_url( 'admin.php?page=wpforo-themes' ) );
		exit();
	}

	/**
	 * theme_delete action
	 */
	public function theme_delete(){
		check_admin_referer('wpforo-theme-delete');

		if(!WPF()->perm->usergroup_can('mth')){
			WPF()->notice->add('Permission denied', 'error');
			wp_redirect(admin_url());
			exit();
		}

		$notice = __('Theme delete error', 'wpforo');
		$notice_type = 'error';
		if( $theme = wpfval($_GET, 'theme') ){
			$theme = trim(sanitize_text_field($theme));
			if( $theme && WPF()->tpl->theme !== $theme ){
				$remove_dir = WPFORO_THEME_DIR . '/' . $theme;
				if( is_dir($remove_dir) ){
					wpforo_remove_directory($remove_dir);
					$notice = __('Theme delete success', 'wpforo');
					$notice_type = 'success';
				}
			}
		}

		WPF()->notice->add($notice, $notice_type);
		wp_redirect( admin_url( 'admin.php?page=wpforo-themes' ) );
		exit();
	}

	/**
	 * theme_reset action
	 */
	public function theme_reset(){
		check_admin_referer('wpforo-theme-reset');

		if(!WPF()->perm->usergroup_can('mth')){
			WPF()->notice->add('Permission denied', 'error');
			wp_redirect(admin_url());
			exit();
		}

		$notice = __('Theme reset error', 'wpforo');
		$notice_type = 'error';
		if( $theme = wpfval($_GET, 'theme') ){
			$theme = trim(sanitize_text_field($theme));
			if( $theme && WPF()->tpl->theme === $theme ){
				$new_theme = WPF()->tpl->find_theme($theme);
				if( !empty($new_theme) ) update_option( 'wpforo_theme_options', $new_theme );
				delete_option( 'wpforo_theme_archive_' . $theme );
				$notice = __('Theme reset success', 'wpforo');
				$notice_type = 'success';
			}
		}

		WPF()->notice->add($notice, $notice_type);
		wp_redirect( admin_url( 'admin.php?page=wpforo-themes' ) );
		exit();
	}

	/**
	 * update wpForo addons CSS style to make compatible with the current version of wpForo
	 */
	function update_addons_css(){
		check_admin_referer('wpforo-update-addons-css');
		wpforo_wrap_in_all_addons_css();
		wp_redirect( admin_url( 'admin.php?page=wpforo-community' ) );
		exit();
	}

	/**
	 * dissmiss the poll version is old notification for admins
	 */
	public function dissmiss_poll_version_is_old() {
		check_admin_referer('wpforo-dissmiss-poll-version-is-old');
		WPF()->dissmissed['poll_version_is_old'] = 1;
		update_option('wpforo_dissmissed', WPF()->dissmissed);
		wpforo_clean_cache('option');
		wp_redirect( admin_url( 'admin.php?page=wpforo-community' ) );
		exit();
	}

	/**
	 * dissmiss the recaptcha not configured notification for admins
	 */
	public function dissmiss_recaptcha_note() {
		if( wpfval($_POST, 'backend') ){
			WPF()->dissmissed['recaptcha_backend_note'] = 1;
		}else{
			WPF()->dissmissed['recaptcha_note'] = 1;
		}
		$response = update_option('wpforo_dissmissed', WPF()->dissmissed);
		wpforo_clean_cache('option');
		if( $response ){
			wp_send_json_success();
		}else{
			wp_send_json_error();
		}
	}

	/**
	 * wpforo before deactivate action
	 */
	public function deactivate(){
		$response = array('code' => 0);
		$json = filter_input(INPUT_POST, 'deactivateData');
		if ($json) {
			parse_str($json, $data);

			$blogTitle = get_option('blogname');
			$to = 'feedback@wpforo.com';
			$subject = '[wpForo Feedback - ' . WPFORO_VERSION . ']';
			$headers = array();
			$contentType = 'text/html';
			$fromName = apply_filters('wp_mail_from_name', $blogTitle);
			$fromName = html_entity_decode($fromName, ENT_QUOTES);
			$siteUrl = get_site_url();
			$parsedUrl = parse_url($siteUrl);
			$domain = isset($parsedUrl['host']) ? $parsedUrl['host'] : '';
			$fromEmail = 'no-reply@' . $domain;
			$headers[] = "Content-Type:  $contentType; charset=UTF-8";
			$headers[] = "From: " . $fromName . " <" . $fromEmail . "> \r\n";
			$message = "Dismiss and never show again";

			if(isset($data['never_show']) && ($v = intval($data['never_show']))){
				update_option('wpforo_deactivation_dialog_never_show', $v);
				$response['code'] = 'dismiss_and_deactivate';
			}elseif(isset($data['deactivation_reason']) && ($reason = trim($data['deactivation_reason']))){
				$subject .= ' - ' . $reason;
				$message = "<strong>Deactivation reason:</strong> " . $reason . "\r\n" . "<br/>";
				if (isset($data['deactivation_reason_desc']) && ($reasonDesc = trim($data['deactivation_reason_desc']))) {
					$message .= "<strong>Deactivation reason description:</strong> " . $reasonDesc . "\r\n" . "<br/>";
				}
				if (isset($data['deactivation_feedback_email']) && ($feedback_email = trim($data['deactivation_feedback_email']))) {
					$to = 'support@wpforo.com';
					$message .= "<strong>Feedback Email:</strong> " . $feedback_email . "\r\n" . "<br/>";
				}
				$subject = html_entity_decode($subject, ENT_QUOTES);
				$message = html_entity_decode($message, ENT_QUOTES);
				$response['code'] = 'send_and_deactivate';
			}

			wp_mail($to, $subject, $message, $headers);
		}
		wp_die(json_encode($response));
	}
}