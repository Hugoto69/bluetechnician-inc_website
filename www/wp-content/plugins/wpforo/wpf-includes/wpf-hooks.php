<?php
	// Exit if accessed directly
	if( !defined( 'ABSPATH' ) ) exit;


add_action( 'wpforo_actions', 'wpforo_do_uninstall');
function wpforo_do_uninstall(){
	if( is_admin() && wpfval($_GET, 'action') === 'wpforo-uninstall' ){
		if( check_admin_referer( 'wpforo_uninstall' ) && current_user_can('administrator') ) wpforo_uninstall();
		wp_redirect( admin_url( 'plugins.php' ) );
		exit();
	}
}

add_filter( 'plugin_action_links_' . WPFORO_BASENAME, 'wpforo_action_link', 10, 2 );
function wpforo_action_link( $links, $file ) {
	
	$uninstall_url = wp_nonce_url( admin_url( 'plugins.php?action=wpforo-uninstall' ), 'wpforo_uninstall' );
	
	$links[] = '<a href="'.esc_url( $uninstall_url ).'" class="wpforo-uninstall" style="color:#a00;" onclick="return confirm(\'' . __('IMPORTANT! Uninstall is not a simple deactivation action. This action will permanently remove all forum data (forums, topics, replies, attachments...) from database. Please backup database before this action, you may need this forum data in future. If you are sure that you want to delete all forum data please confirm. If not, just cancel it, then you can deactivate this plugin, that will not remove forum data.', 'wpforo').'\')">' . __( 'Uninstall', 'wpforo' ) . '</a>';

	$settings_link = '<a href="'.esc_url( admin_url( 'admin.php?page=wpforo-community' ) ).'">' . __( 'Settings', 'wpforo' ) . '</a>';
	array_unshift( $links, $settings_link );

	return $links;
}

function wpforo_notice_show(){
	WPF()->notice->show();
}
add_action( 'wp_footer', 'wpforo_notice_show' );

function wpforo_show_admin_bar($show_admin_bar){
	if( !is_super_admin() && is_user_logged_in() && !array_intersect( array('editor', 'administrator', 'author'), (array) WPF()->wp_current_user->roles ) ){
	    $show_admin_bar = (bool) wpforo_feature('user-admin-bar');
    }
	return $show_admin_bar;
}
add_action('show_admin_bar', 'wpforo_show_admin_bar' );

function wpforo_admin_notice__menu_help(){
	if(strpos(wpforo_get_request_uri(), 'nav-menus.php') !== FALSE){

		$message = 'wpForo Menu Shortcodes<hr/><table>';
		foreach( WPF()->menu as $key => $value ){
			$message .= "<tr><td> " . $value['label'] . ": </td><td> /%$key%/ </td></tr>";
		}
		$message .= "<tr><td> " . wpforo_phrase('register', FALSE) . ": </td><td> /%wpforo-register%/ </td></tr>
			<tr><td> " . wpforo_phrase('login', FALSE) . ": </td><td> /%wpforo-login%/ </td></tr>
			</table>";
		
		$class = 'notice notice-warning';
		printf( '<div class="%1$s"><p>%2$s</p></div>', $class, $message ); 
	}
}
add_action( 'admin_notices', 'wpforo_admin_notice__menu_help' );

function wpforo_disable_comments( $open, $post_id ) {
	if(is_wpforo_page()) return FALSE; 
	return $open;
}
add_filter( 'comments_open', 'wpforo_disable_comments', 10, 2 );

function wpforo_disable_comments_hide_existing_comments($comments) {
	if(is_wpforo_page()) return array();
	return $comments;
}
add_filter('comments_array', 'wpforo_disable_comments_hide_existing_comments', 10, 2);

function wpforo_remove_comment_support() {
	if(is_wpforo_page()){
   	 	remove_post_type_support( 'post', 'comments' );
	    remove_post_type_support( 'page', 'comments' );
	}
}
add_action('wpforo_actions_end', 'wpforo_remove_comment_support', 100);

function wpforo_manage_current_post_query() {
	if ( !WPF()->use_home_url && !is_front_page() && !is_home() && is_wpforo_url() ) {
		global $wp_query, $post;
		if( is_a( $post, 'WP_Post' ) && $post->ID != WPF()->pageid ){
			$args = array(
				'post_count'    => 1,
				'found_posts'   => 1,
				'max_num_pages' => 0,
				'is_404'        => false,
				'is_page'       => true,
				'is_singular'   => true
			);

			$target_post = get_post( WPF()->pageid );
			if ( ! is_a( $target_post, 'WP_Post' ) ) {
				return;
			}

			$post = $target_post;

			$wp_query->posts             = array( $post );
			$wp_query->queried_object_id = $post->ID;
			$wp_query->queried_object    = $post;

			foreach ( $args as $key => $value ) {
				$wp_query->$key = $value;
			}

			setup_postdata( $post );
        }
	}
}
add_action( 'wpforo_actions_end', 'wpforo_manage_current_post_query' );

function wpforo_change_author_default_page( $link ){
	if( wpforo_feature('profile') === 2 ) return WPF()->member->get_profile_url($link);
	return $link;
}
function wpforo_change_comment_author_default_page( $link, $ID = 0, $object = NULL ){
	if( isset($object->user_id) && $object->user_id && wpforo_feature('profile') === 2 ){
		return WPF()->member->get_profile_url($object->user_id);
	}
	return $link;
}
add_filter( 'author_link', 'wpforo_change_author_default_page' );
add_filter( 'get_comment_author_url', 'wpforo_change_comment_author_default_page', 10, 3 );

function wpforo_change_default_register_page( $register_url ) {
    if(!wpforo_feature('register-url')) return $register_url;
	return wpforo_home_url('?foro=signup&redirect_to=' . urlencode( preg_replace( '#/?\?.*$#isu', '', wpforo_get_request_uri() ) ));
}
add_filter( 'register_url', 'wpforo_change_default_register_page' );

function  wpforo_change_default_login_page( $login_url, $redirect ) {
    if(!wpforo_feature('login-url')) return $login_url;
	return wpforo_home_url('?foro=signin&redirect_to=' . urlencode( preg_replace( '#/?\?.*$#isu', '', wpforo_get_request_uri() ) ));
}
add_filter( 'login_url', 'wpforo_change_default_login_page', 10, 2 );

function wpforo_restrict_trash_shortcode_page($check, $post){
    if( $post->ID == WPF()->pageid ) {
        $check = false;
        WPF()->notice->add('DO NOT DELETE WPFORO PAGE!!!', 'error');
    }
    return $check;
}
add_filter('pre_trash_post', 'wpforo_restrict_trash_shortcode_page', 10, 2);

function wpforo_restrict_front_page_dropdown($output, $r){
    if( $r['name'] == 'page_for_posts' || ($r['name'] == 'page_on_front' && wpforo_get_shortcode_pageid( WPF()->pageid )) ){
        $pattern = '#[\r\n\t\s]*<option[^<>]*?value=[\'"]'.wpforo_bigintval(WPF()->pageid).'[\'"][^<>]*?>[^<>]*?</option>#isu';
        $output = preg_replace($pattern, '', $output);
    }
    return $output;
}
add_filter('wp_dropdown_pages', 'wpforo_restrict_front_page_dropdown', 10, 2);

function wpforo_page_on_front_manager($value, $option, $old_value){
    if( $option === 'page_on_front' && $value == WPF()->pageid ){
        if( !$page_id = wpforo_get_shortcode_pageid( WPF()->pageid ) ){
            $wpforo_page = array(
                'post_date' => current_time( 'mysql', 1 ),
                'post_date_gmt' => current_time( 'mysql', 1 ),
                'post_content' => '[wpforo]',
                'post_title' => 'Forum page_on_front',
                'post_status' => 'publish',
                'comment_status' => 'close',
                'ping_status' => 'close',
                'post_name' => 'front-community',
                'post_modified' => current_time( 'mysql', 1 ),
                'post_modified_gmt' => current_time( 'mysql', 1 ),
                'post_parent' => 0,
                'menu_order' => 0,
                'post_type' => 'page'
            );
            $page_id = wp_insert_post( $wpforo_page );
        }
        $value = ( $page_id && !is_wp_error($page_id) ? $page_id : $old_value);
    }
    return $value;
}
add_filter('pre_update_option', 'wpforo_page_on_front_manager', 10, 3);

function wpftpl( $filename ){
	$find = array();
	if ( $filename ) {
		$find[] = 'wpforo/'. $filename;
		$template = locate_template( array_unique( $find ) );
		if ( !$template ) $template = WPFORO_THEME_DIR . '/'. WPF()->tpl->theme .'/' . $filename;

		return apply_filters('wpforo_wpftpl', $template);
	}
}

function wpforo_init_template(){
	include( wpftpl('index.php') );
}

add_shortcode( 'wpforo', 'wpforo_load' );
function wpforo_load($atts){
	if( defined( 'REST_REQUEST' ) && REST_REQUEST ) return '';

    if( !is_wpforo_url() ){
        if(!$atts) $atts = array('item' => 'forum');
        WPF()->init_current_url($atts);
        WPF()->init_current_object();
	    wpforo_frontend_enqueue_scripts();
    }
	if( apply_filters('on_wpforo_load_remove_the_content_all_filters', false) ) remove_all_filters('the_content');
	if(wpforo_feature('output-buffer') && function_exists('ob_start')){
		if( wpforo_feature('html_cashe') ){
			if( $html = WPF()->cache->get_html() ) return $html;
		}
		ob_start();
		wpforo_init_template();
		$output = ob_get_clean();
		$output = trim($output);
		WPF()->cache->html($output);

		if( !$output ) $output = wpforo_hook_usage('the_content');

		return $output;	
	}
	else{
		wpforo_init_template();
	}
	return '';
}

function wpforo_hook_usage( $hook = '' ) {
	global $wp_filter;

	$output = '<div style="color: #990000; font-size: 16px;">Notice: a plugin conflict has been detected. wpForo forums are affected by other plugin errors. 
        Please deactivate all plugins, delete all caches and test again. 
        Then activate all plugins back one by one and find the conflict maker plugin.</div>
        <pre style="display: none;">' . ( empty( $hook ) || ! isset( $wp_filter[ $hook ] ) ? 'No hook usage' : print_r( $wp_filter[ $hook ], true ) ) . '</pre>';

	return $output;
}

function wpforo_template_include($template){
	if( is_wpforo_page() && !is_wpforo_shortcode_page() && ($wpforo_template = wpftpl('index.php')) ){
		return $wpforo_template;
	}
	return $template;
}

add_action('wpforo_actions_end', 'wpforo_set_header_status');
function wpforo_set_header_status(){
	if( is_wpforo_page() ){
		global $wp_query;

        $status = ( WPF()->current_object['is_404'] ? 404 : 200 );
        status_header( $status );
        $wp_query->is_404 = FALSE;
    }
}

function wpforo_do_rewrite(){
    if( is_wpforo_page() ){
    	if( WPF()->use_home_url ){
			add_rewrite_rule( '(.*)', 'index.php?page_id=' . WPF()->pageid, 'top');
    		add_filter('template_include', 'wpforo_template_include');
    	}
    }
}
add_action('wpforo_actions_end', 'wpforo_do_rewrite');

function wpforo_rewrite_rules_array($rules){
	$permastruct = utf8_uri_encode( WPF()->permastruct );
	$permastruct = preg_replace('#^/?index\.php/?#isu', '', $permastruct);
	$permastruct = trim($permastruct, '/');
	$pattern = '('.preg_quote($permastruct).'(?:/|$).*)$';
	$to_url = 'index.php?page_id=' . WPF()->pageid;
	if( !WPF()->use_home_url && !in_array($to_url, $rules) ) $rules = array_merge( array($pattern => $to_url), $rules );

	return $rules;
}
add_filter( 'rewrite_rules_array', 'wpforo_rewrite_rules_array' );

function wpforo_checking_rewrite(){
	if( is_wpforo_url() && !WPF()->use_home_url && WPF()->pageid && WPF()->permastruct && get_the_ID() != WPF()->pageid ){
		flush_rewrite_rules(FALSE);
		nocache_headers();
	}
}
add_action('wpforo_actions_end', 'wpforo_checking_rewrite');

function wpforo_theme_functions(){
	$path = wpftpl('functions.php');
	if( file_exists($path) ){ 
		include_once($path);
	}
}
add_action('wpforo_after_init', 'wpforo_theme_functions');

function wpforo_theme_functions_wp(){
	$path = wpftpl('functions-wp.php');
	if( file_exists($path) ){ 
		include_once($path);
	}
}
add_action('wpforo_after_init', 'wpforo_theme_functions_wp');

function wpforo_meta_title($title) {
	$meta_title = array();
	
	if(!wpforo_feature('seo-title')) return $title;
	
	if(is_wpforo_page()){
		$template = WPF()->current_object['template'];
		if(!WPF()->current_object['is_404']){
			$paged = ( WPF()->current_object['paged'] > 1 ) ? ' - ' .  wpforo_phrase( 'page', false) . ' ' . WPF()->current_object['paged'] .' ' : '';
			if(!empty(WPF()->current_object['forum'])) $forum = WPF()->current_object['forum'];
			if(!empty(WPF()->current_object['topic'])) $topic = WPF()->current_object['topic'];
			if(!empty(WPF()->current_object['user'])) $user = WPF()->current_object['user'];
			if(isset($topic['title']) && isset($forum['title']) && isset(WPF()->general_options['title'])){
				$meta_title = array($topic['title'] . $paged, $forum['title'], WPF()->general_options['title']);
                $meta_title = apply_filters('wpforo_seo_topic_title', $meta_title);
			}
			elseif(!isset($topic['title']) && isset($forum['title']) && isset(WPF()->general_options['title'])){
				$meta_title = array($forum['title'] . $paged, WPF()->general_options['title']);
                $meta_title = apply_filters('wpforo_seo_forum_title', $meta_title);
			}
			elseif( $template != 'forum' && $template != 'topic' && $template != 'post' ){
				if( $template == 'profile' || $template == 'account' || $template == 'activity' || $template == 'subscriptions' ){
					if(isset($user['display_name'])){
						$meta_title = array($user['display_name'], wpforo_phrase( ucfirst($template), false), WPF()->general_options['title']);
					}
					elseif(isset(WPF()->current_object['user_nicename'])){
						$meta_title = array(WPF()->current_object['user_nicename'], wpforo_phrase( ucfirst($template), false), WPF()->general_options['title']);
					}
					else{
						$meta_title = array(wpforo_phrase( 'Member', false), wpforo_phrase( ucfirst($template), false), WPF()->general_options['title']);
					}
                    $meta_title = apply_filters('wpforo_seo_profile_title', $meta_title);
				}
				elseif( $template == 'recent' ){
					$wpfpaged = ( isset($_GET['wpfpaged']) && $_GET['wpfpaged'] > 1 ) ? ' - ' .  wpforo_phrase( 'page', false) . ' ' . $_GET['wpfpaged'] .' ' : '';
                    $view = wpfval($_GET, 'view');
					if( $view == 'unread'){
	                    $main_title  = wpforo_phrase( 'Unread Posts', false);
                    } elseif( $view == 'prefix' ){
	                    $main_title  = wpforo_phrase( 'Topic Prefix', false);
                    } else {
	                    $main_title  = wpforo_phrase( 'Recent Posts', false);
                    }
					$meta_title = array( $main_title . $wpfpaged, WPF()->general_options['title']);
                    $meta_title = apply_filters('wpforo_seo_recent_posts_title', $meta_title);
				}
                elseif( $template == 'tags' ){
                    $wpfpaged = ( isset($_GET['wpfpaged']) && $_GET['wpfpaged'] > 1 ) ? ' - ' .  wpforo_phrase( 'page', false) . ' ' . $_GET['wpfpaged'] .' ' : '';
                    $meta_title = array( wpforo_phrase( 'Tags', false) . $wpfpaged, WPF()->general_options['title']);
                    $meta_title = apply_filters('wpforo_seo_tags_title', $meta_title);
                }
                elseif( $template == 'search' && wpfval($_GET, 'wpfin') && wpfval($_GET, 'wpfs') && $_GET['wpfin'] == 'tag' ){
                    $wpfpaged = ( isset($_GET['wpfpaged']) && $_GET['wpfpaged'] > 1 ) ? ' - ' .  wpforo_phrase( 'page', false) . ' ' . $_GET['wpfpaged'] .' ' : '';
                    $meta_title = array( wpforo_phrase('Topic Tag:', false) . ' ' . esc_html($_GET['wpfs']), $wpfpaged, WPF()->general_options['title']);
                    $meta_title = apply_filters('wpforo_seo_tag_title', $meta_title);
				}
				elseif($template){
					$wpfpaged = ( isset($_GET['wpfpaged']) && $_GET['wpfpaged'] > 1 ) ? ' - ' .  wpforo_phrase( 'page', false) . ' ' . $_GET['wpfpaged'] .' ' : '';
					$meta_title = array(wpforo_phrase( ucfirst($template), false) . $wpfpaged, WPF()->general_options['title']);
                    $meta_title = apply_filters('wpforo_seo_template_title', $meta_title);
				}
				elseif($title){
					$meta_title = (is_array($title)) ? $title : array($title);
                    $meta_title = apply_filters('wpforo_seo_x_title', $meta_title);
				}
				else{
					$meta_title = array(wpforo_phrase('Forum', false), get_bloginfo('name'));
                    $meta_title = apply_filters('wpforo_seo_general_title', $meta_title);
				}
			}
			elseif( isset(WPF()->general_options['title']) && WPF()->general_options['title'] ){
				$meta_title = array(WPF()->general_options['title'], get_bloginfo('name'));
                $meta_title = apply_filters('wpforo_seo_main_title', $meta_title);
			}
			elseif($title){
				$meta_title = (is_array($title)) ? $title : array($title);
                $meta_title = apply_filters('wpforo_seo_x_title', $meta_title);
			}
			else{
				$meta_title = array(wpforo_phrase('Forum', false), get_bloginfo('name'));
                $meta_title = apply_filters('wpforo_seo_general_title', $meta_title);
			}
		}
		else{
			$meta_title = array(wpforo_phrase( '404 - Page not found', false), WPF()->general_options['title']);
            $meta_title = apply_filters('wpforo_seo_404_title', $meta_title);
		}
	}
	if(!empty($meta_title)) {
		return $meta_title;
	}
	else{
		return $title;
	}
}
add_filter('document_title_parts', 'wpforo_meta_title', 100);

function wpforo_meta_wp_title($title){
	if(!wpforo_feature('seo-title')) return $title;
	$meta_title = wpforo_meta_title($title);
	if(is_array($meta_title) && !empty($meta_title)){
		$title = implode(' &#8211; ', $meta_title);
	}
	return $title;
}
add_filter( 'wp_title', 'wpforo_meta_wp_title', 100);

function wpforo_add_meta_tags(){
	if(!wpforo_feature('seo-meta')) return;
	
	if(is_wpforo_page()){
		$title = '';
        $og_img = '';
        $tw_img = '';
		$noindex = '';
		$template = '';
		$description = '';
		$udata = array();
		if( preg_match('#\?.*$#is', WPF()->current_url, $requests) ){
			$canonical = wpforo_home_url( $requests[0] );
		} else{
			$canonical = WPF()->current_url;
		}
		$noindex_urls = WPF()->tools_misc['noindex'];
        $image = wpforo_find_image_urls('', true, 'og:image');
		if(!empty($noindex_urls)){
			$noindex_urls = explode("\n", $noindex_urls); 
			if(!empty($noindex_urls)){ 
				$noindex_urls = array_map("trim", $noindex_urls);
				foreach( $noindex_urls as $noindex_url){ 
					$noindex_url = strtok($noindex_url, "#");
					if( strpos( $noindex_url, '*' ) !== false ){
                        $noindex_url = strtok($noindex_url, "*");
                        if( preg_match('|^' . preg_quote($noindex_url) . '|is', $canonical) ){
                            $noindex = "<meta name=\"robots\" content=\"noindex\">\r\n"; break;
                        }
                    }
                    elseif( $canonical == $noindex_url ) {
						$noindex = "<meta name=\"robots\" content=\"noindex\">\r\n"; break;
					}
				}
			}
		}
		$paged = ( WPF()->current_object['paged'] > 1 ) ? wpforo_phrase( 'page', false) . ' ' . WPF()->current_object['paged'] .' | ' : '';
		if(isset(WPF()->current_object['template'])) $template = WPF()->current_object['template'];
		if(!empty(WPF()->current_object['forum'])) $forum = WPF()->current_object['forum'];
		if(!empty(WPF()->current_object['topic'])) $topic = WPF()->current_object['topic'];
		if(!empty(WPF()->current_object['user'])) $user = WPF()->current_object['user'];
		if(isset(WPF()->current_object)){
			if( wpfval(WPF()->current_object, 'forumid') && !wpfval(WPF()->current_object, 'topicid') ){
				if(isset($forum['title'])) $title = $forum['title'];
				if(isset(WPF()->current_object['forum_meta_desc']) && WPF()->current_object['forum_meta_desc'] !=''){
					$description = $paged . WPF()->current_object['forum_meta_desc'];
				}
				elseif(isset(WPF()->current_object['forum_desc']) && WPF()->current_object['forum_desc'] !=''){
					$description = $paged . WPF()->current_object['forum_desc'];
				}
			}elseif( isset(WPF()->current_object['topicid']) && isset($topic['first_postid']) ){
				$post = WPF()->post->get_post($topic['first_postid']);
				$content = wpforo_content($post, false);
				$image = wpforo_find_image_urls($content, true, 'og:image');
				if(isset($post['title'])) $title = wpforo_text($paged . $post['title'], 60, false);
				if(isset($post['body'])) $description = wpforo_text($paged . $post['body'], 150, false);
			}elseif( $template == 'profile' || $template == 'account' || $template == 'activity' || $template == 'subscriptions' ){
				if( isset(WPF()->general_options['title']) ) $title = $paged . WPF()->general_options['title'];
				$udata['name'] = (isset($user['display_name']) && $user['display_name']) ? wpforo_phrase( 'User', false ) . ': ' . $user['display_name'] : '';
				$udata['title'] = (isset($user['stat']['title']) && $user['stat']['title']) ?  wpforo_phrase( 'Title', false ) . ': ' . $user['stat']['title'] : '';
				$udata['about'] = (isset($user['about']) && $user['about']) ? wpforo_phrase( 'About', false ) . ': ' . wpforo_text($user['about'], 150, false) : '';
				$description =  $title . ' - ' . wpforo_phrase('Member Profile', false) . ' &gt; ' . wpforo_phrase( ucfirst($template), false ) . ' ' . wpforo_phrase( 'Page', false ) . '. ' . implode(', ', $udata);
				if(!wpforo_feature('seo-profile')){ $noindex = "<meta name=\"robots\" content=\"noindex\">\r\n"; }
			}elseif(isset(WPF()->current_object['template']) && WPF()->current_object['template'] == 'member'){
				$wpfpaged = ( isset($_GET['wpfpaged']) && $_GET['wpfpaged'] > 1 ) ? wpforo_phrase( 'Page', false) . ' ' . $_GET['wpfpaged'] .' | ' : '';
				$description = $wpfpaged . wpforo_phrase( 'Forum Members List', false);
			}elseif(isset(WPF()->current_object['template']) && WPF()->current_object['template'] == 'recent'){
				$wpfpaged = ( isset($_GET['wpfpaged']) && $_GET['wpfpaged'] > 1 ) ? wpforo_phrase( 'Page', false) . ' ' . $_GET['wpfpaged'] .' | ' : '';
				$description = $wpfpaged . wpforo_phrase( 'Recent Posts', false);
			}elseif(isset(WPF()->current_object['template']) && WPF()->current_object['template'] == 'tags'){
                $wpfpaged = ( isset($_GET['wpfpaged']) && $_GET['wpfpaged'] > 1 ) ? wpforo_phrase( 'Page', false) . ' ' . $_GET['wpfpaged'] .' | ' : '';
                $description = $wpfpaged . wpforo_phrase( 'Tags', false);
            }
			else{
				if( isset(WPF()->general_options['title']) ) $title = $paged . WPF()->general_options['title'];
				if( isset(WPF()->general_options['description']) ) $description = $paged . WPF()->general_options['description'];
				if(isset(WPF()->current_object['template']) && ( WPF()->current_object['template'] == 'login' || WPF()->current_object['template'] == 'register' ) ){ 
					$noindex = "<meta name=\"robots\" content=\"noindex\">\r\n"; 
				}
			}
			$description = preg_replace('#[\t\r\n]+#isu', ' ', $description);
            if($image) {
                $og_img = '<meta property="og:image" content="' . $image . '" />'. "\r\n";
                $tw_img = '<meta property="twitter:image" content="' . $image . '" />' . "\r\n";
            }
            $meta_tags = "\r\n<!-- wpForo SEO -->\r\n" . $noindex . "<link rel=\"canonical\" href=\"".$canonical."\" />\r\n<meta name=\"description\" content=\"" . esc_html($description) . "\" />\r\n<meta property=\"og:title\" content=\"" . esc_html($title) . "\" />\r\n<meta property=\"og:description\" content=\"" . esc_html($description) . "\" />\r\n<meta property=\"og:url\" content=\"" . $canonical . "\" />\r\n". $og_img . "<meta property=\"og:site_name\" content=\"" . get_bloginfo('name') . "\" />\r\n<meta property=\"og:type\" content=\"website\" />\r\n<meta name=\"twitter:description\" content=\"" . esc_html($description) . "\"/>\r\n<meta name=\"twitter:title\" content=\"" . esc_html($title) . "\" />\r\n<meta property=\"twitter:card\" content=\"summary_large_image\" />\r\n" . $tw_img . "<!-- wpForo SEO End -->\r\n\r\n";
			echo apply_filters('wpforo_seo_meta_tags', $meta_tags);
		}
	}
}
add_action('wp_head', 'wpforo_add_meta_tags', 1);


add_action('wp_ajax_wpforo_like_ajax', 'wpf_like');
function wpf_like(){
	$response = array('count' => 0, 'likers' => '', 'notice' => WPF()->notice->get_notices());
	if( !is_user_logged_in() ){
		WPF()->notice->add( wpforo_get_login_or_register_notice_text() );
		$response['notice'] = WPF()->notice->get_notices();
		wp_send_json_error($response);
	}
	if( !isset($_POST['likestatus']) || !isset($_POST['postid']) || !($postid = intval($_POST['postid'])) ){
		WPF()->notice->add('action error', 'error');
		$response['notice'] = WPF()->notice->get_notices();
		wp_send_json_error($response);
	}
	if( !$post = WPF()->post->get_post( $postid ) ){
		WPF()->notice->add('post not found', 'error');
		$response['notice'] = WPF()->notice->get_notices();
		wp_send_json_error($response);
	}
	if( !WPF()->perm->forum_can( 'l', $post['forumid']) ){
		WPF()->notice->add('You don\'t have permission to like posts from this forum', 'error');
		$response['notice'] = WPF()->notice->get_notices();
		wp_send_json_error($response);
	}
	$sql = "SELECT EXISTS( 
            SELECT * FROM `" . WPF()->tables->likes . "` 
                WHERE `postid` = %d 
                AND `userid` = %d 
        ) AS is_exists";
	$sql = WPF()->db->prepare($sql, $postid, WPF()->current_userid);
	$is_exists = WPF()->db->get_var($sql);
    if( $_POST['likestatus'] ){
        if( !$is_exists ){
            if( WPF()->db->insert(
                WPF()->tables->likes,
                array(
                    'postid'	=> $postid,
                    'userid' 	=>  WPF()->current_userid,
                    'post_userid' 	=> $post['userid']
                ),
                array('%d','%d','%d')
            ) ){
                wpforo_clean_cache('post-soft', $postid);
                do_action('wpforo_like', $post, WPF()->current_userid);
	            WPF()->member->reset($post['userid']);
	            WPF()->member->reset(WPF()->current_userid);
	            WPF()->notice->add('done', 'success');
	            $response['notice'] = WPF()->notice->get_notices();
	            $response['likers'] = WPF()->tpl->likers($postid);
	            $response['count'] = WPF()->post->get_post_likes_count($postid);
	            wp_send_json_success($response);
            }
        }else{
	        WPF()->member->reset($post['userid']);
	        WPF()->member->reset(WPF()->current_userid);
	        WPF()->notice->add('You\'re already liked this post', 'success');
	        $response['notice'] = WPF()->notice->get_notices();
	        $response['likers'] = WPF()->tpl->likers($postid);
	        $response['count'] = WPF()->post->get_post_likes_count($postid);
	        wp_send_json_success($response);
        }
    }else{
        if( $is_exists ){
            if( WPF()->db->delete(
                WPF()->tables->likes,
                array(
                    'postid'	=> $postid,
                    'userid' 	=>  WPF()->current_userid
                ),
                array('%d','%d')
            ) ){
                wpforo_clean_cache('post-soft', $postid);
                do_action('wpforo_dislike', $post, WPF()->current_userid);
	            WPF()->member->reset($post['userid']);
	            WPF()->member->reset(WPF()->current_userid);
	            WPF()->notice->add('done', 'success');
	            $response['notice'] = WPF()->notice->get_notices();
	            $response['likers'] = WPF()->tpl->likers($postid);
	            $response['count'] = WPF()->post->get_post_likes_count($postid);
	            wp_send_json_success($response);
            }
        }else{
	        WPF()->member->reset($post['userid']);
	        WPF()->member->reset(WPF()->current_userid);
	        WPF()->notice->add('You\'re already disliked this post', 'success');
	        $response['notice'] = WPF()->notice->get_notices();
	        $response['likers'] = WPF()->tpl->likers($postid);
	        $response['count'] = WPF()->post->get_post_likes_count($postid);
	        wp_send_json_success($response);
        }
    }

	wp_send_json_error($response);
}


add_action('wp_ajax_wpforo_vote_ajax', 'wpf_vote');
function wpf_vote(){
	if( !is_user_logged_in() ){
		WPF()->notice->add( wpforo_get_login_or_register_notice_text() );
		wp_send_json_error(array('notice' => WPF()->notice->get_notices()));
	}
	
	if( !$postid = wpforo_bigintval( wpfval($_POST, 'postid') ) ){
		WPF()->notice->add('Wrong post data', 'error');
		wp_send_json_error(array('notice' => WPF()->notice->get_notices()));
	}

    $reaction = wpfval($_POST, 'votestatus') === 'down' ? -1 : 1;

	$sql = "SELECT `voteid` FROM `".WPF()->tables->votes."` 
	    WHERE `postid` = %d 
	    AND `userid` = %d 
	    AND `reaction` = %d";
	$sql = WPF()->db->prepare($sql, $postid, WPF()->current_userid, $reaction);
	if( WPF()->db->get_var($sql) ){
		WPF()->notice->add('You are already voted this post');
		wp_send_json_error(array('notice' => WPF()->notice->get_notices()));
	}else{
	    WPF()->db->delete(
            WPF()->tables->votes,
            array('postid' => $postid, 'userid' => WPF()->current_userid),
            array('%d','%d')
        );
    }
	
	if( $post = WPF()->post->get_post( $postid ) ){
		$voted = WPF()->db->insert(
			WPF()->tables->votes,
			array(
				'postid'      => $postid,
				'userid'      => WPF()->current_userid,
				'reaction'    => $reaction,
				'post_userid' => $post['userid']
			),
			array('%d','%d','%d','%d')
		);

		WPF()->member->reset($post['userid']);
		WPF()->member->reset(WPF()->current_userid);

		if( $voted !== false ){
			$incr = $incr2 = true;

			$votes = (int) $post['votes'] + (int) $reaction;
			if( wpfval($_POST, 'itemtype') === 'topic' ){
				$incr = WPF()->db->query( "UPDATE ".WPF()->tables->topics." SET `votes` = `votes` + $reaction  WHERE topicid = " . wpforo_bigintval($post['topicid']) );
			}
			$incr2 = WPF()->db->query( "UPDATE ".WPF()->tables->posts." SET `votes` = {$votes}  WHERE postid = " . wpforo_bigintval($post['postid']) );

			if($incr !== false && $incr2 !== false){
				wpforo_clean_cache('post', $postid, $post);
				do_action('wpforo_vote', $reaction, $post, WPF()->current_userid );
				WPF()->notice->add('Successfully voted', 'success');
				wp_send_json_success( array('votes'=> $votes, 'notice' => WPF()->notice->get_notices()) );
			}
		}
    }

	WPF()->notice->add('Wrong post data', 'error');
	wp_send_json_error(array('notice' => WPF()->notice->get_notices()));
}

add_action('wp_ajax_wpforo_answer_ajax', 'wpf_answer');
function wpf_answer(){
	$response = array('notice' => WPF()->notice->get_notices());
	if(!is_user_logged_in()){
		WPF()->notice->add( wpforo_get_login_or_register_notice_text() );
		$response['notice'] = WPF()->notice->get_notices();
		wp_send_json_error($response);
	}
	if( !isset($_POST['answerstatus']) || !isset($_POST['postid']) || !$postid = intval($_POST['postid']) ){
		WPF()->notice->add('action error', 'error');
		$response['notice'] = WPF()->notice->get_notices();
		wp_send_json_error($response);
	}
	if( !$post = WPF()->post->get_post( $postid ) ){
		WPF()->notice->add('post not found', 'error');
		$response['notice'] = WPF()->notice->get_notices();
		wp_send_json_error($response);
	}
	if( !$topic = WPF()->topic->get_topic( $post['topicid'] ) ){
		WPF()->notice->add('topic not found', 'error');
		$response['notice'] = WPF()->notice->get_notices();
		wp_send_json_error($response);
	}
	if( !(WPF()->perm->forum_can( 'at', $post['forumid'] ) ||  ( WPF()->perm->forum_can( 'oat', $post['forumid']) && WPF()->current_userid == $topic['userid'] ) ) ){
		WPF()->notice->add('You don\'t have permission to make topic answered', 'error');
		$response['notice'] = WPF()->notice->get_notices();
		wp_send_json_error($response);
	}
	if( intval($_POST['answerstatus']) && WPF()->topic->has_is_answer_post($topic['topicid']) ){
		WPF()->notice->add('You don\'t have permission to make two best answers for one topic', 'error');
		$response['notice'] = WPF()->notice->get_notices();
		wp_send_json_error($response);
    }
	if( false !== WPF()->db->query( "UPDATE ".WPF()->tables->posts." SET is_answer = ".intval($_POST['answerstatus'])." WHERE postid = " . intval($postid) ) ){
		wpforo_clean_cache('post', $postid, $post);
		WPF()->db->query( "UPDATE ".WPF()->tables->topics." SET `solved` = " . intval($_POST['answerstatus']) . " WHERE `topicid` = " . intval($post['topicid']) );
		do_action('wpforo_answer', intval($_POST['answerstatus']), $post);
		WPF()->notice->add('done', 'success');
		$response['notice'] = WPF()->notice->get_notices();
		wp_send_json_success($response);
	}
	wp_send_json_error($response);
}

add_action('wp_ajax_wpforo_quote_ajax', 'wpf_quote');
add_action('wp_ajax_nopriv_wpforo_quote_ajax', 'wpf_quote' );
function wpf_quote(){
    if( !$current_topicid = wpforo_bigintval( WPF()->current_object['topicid'] ) ) exit();
	$post  = WPF()->db->get_row('SELECT * FROM '.WPF()->tables->posts.' WHERE topicid = ' . $current_topicid . ' AND postid =' . wpforo_bigintval($_POST['postid']), ARRAY_A);
	if( !(WPF()->perm->forum_can( 'cr', $post['forumid']) || ( wpforo_is_owner( wpfval(WPF()->current_object['topic'], 'userid'), wpfval(WPF()->current_object['topic'], 'email') ) && WPF()->perm->forum_can( 'ocr', $post['forumid']) )) ) return;
	$post = apply_filters('wpforo_quote_post_ajax', $post);
	$poster = wpforo_member( $post );
	$response = '<blockquote data-userid="' . $post['userid'] . '" data-postid="'. $post['postid'] .'" data-mention="'. esc_attr($poster['user_nicename']) .'"><div class="wpforo-post-quote-author"><strong> ' . wpforo_phrase('Posted by', FALSE) . ': ' . esc_textarea( '@' . $poster['user_nicename'] ) . ' </strong></div>' . wpautop($post['body']) . '</blockquote><br />';
	wp_send_json_success($response);
}

add_action('wp_ajax_wpforo_report_ajax', 'wpf_report');
function wpf_report(){
	if( !is_user_logged_in() ){
		WPF()->notice->add( wpforo_get_login_or_register_notice_text() );
		wp_send_json_error( WPF()->notice->get_notices() );
	}
	
	if( !isset($_POST['reportmsg']) || !$_POST['reportmsg'] || !isset($_POST['postid']) || !$_POST['postid'] ){
		WPF()->notice->add('Error: please insert some text to report.', 'error');
		wp_send_json_error( WPF()->notice->get_notices() );
	}
	
	############### Sending Email  ##################
		$report_text = substr($_POST['reportmsg'], 0, 1000);
		$postid = intval($_POST['postid']);
		$reporter = '<a href="'.WPF()->current_user['profile_url'].'">'.(WPF()->current_user['display_name'] ? WPF()->current_user['display_name'] : urldecode(WPF()->current_user['user_nicename'])).'</a>';
		$reportmsg = wpforo_kses($report_text, 'email');
		$post_url = WPF()->post->get_post_url($postid);
		
		$subject = WPF()->sbscrb->options['report_email_subject'];
		$message = WPF()->sbscrb->options['report_email_message'];
		
		$from_tags = array("[reporter]", "[message]", "[post_url]");
		$to_words   = array(sanitize_text_field($reporter), $reportmsg, '<a target="_blank" href="'. esc_url($post_url) .'">'. esc_url($post_url) .'</a>');
		
		$subject = stripslashes(strip_tags(str_replace($from_tags, $to_words, $subject)));
		$message = stripslashes(str_replace($from_tags, $to_words, $message));
		
		$admin_email = get_option( 'admin_email' );
		$admin_emails = WPF()->sbscrb->options['admin_emails'];
		$admin_emails = trim($admin_emails);
		$admin_emails = explode(',', $admin_emails);
		$admin_emails = array_map('sanitize_email', $admin_emails);
		$admin_email = (isset($admin_emails[0]) && $admin_emails[0]) ? $admin_emails[0] : $admin_email;
		$headers = wpforo_admin_mail_headers();
		
		add_filter( 'wp_mail_content_type', 'wpforo_set_html_content_type', 999 );
		if( @wp_mail( $admin_email, $subject, $message, $headers ) ){
			remove_filter( 'wp_mail_content_type', 'wpforo_set_html_content_type' );
		}else{
			remove_filter( 'wp_mail_content_type', 'wpforo_set_html_content_type' );
			WPF()->notice->add('Can\'t send report email', 'error');
			wp_send_json_error( WPF()->notice->get_notices() );
		}
		
	############### Sending Email end  ##############
	WPF()->notice->add('Message has been sent', 'success');
	wp_send_json_success( WPF()->notice->get_notices() );
}

add_action('wp_ajax_wpforo_sticky_ajax', 'wpf_sticky');
add_action('wp_ajax_nopriv_wpforo_sticky_ajax', 'wpf_sticky' );
function wpf_sticky(){
    WPF()->notice->add('wrong data', 'error');
    $response = array( 'notice' => WPF()->notice->get_notices() );
	if( !$topicid = wpforo_bigintval( wpfval($_POST, 'topicid') ) ) wp_send_json_error($response);

	$sql = "SELECT `forumid` FROM `".WPF()->tables->topics."` WHERE `topicid` = $topicid";
	$forumid  = WPF()->db->get_var($sql);
	if( !WPF()->perm->forum_can( 's', $forumid) ){
        WPF()->notice->add('You don\'t have permission to do this action from this forum', 'error');
        $response['notice'] = WPF()->notice->get_notices();
        wp_send_json_error($response);
    }
	$status = wpfval($_POST, 'status');
	if( $status === 'sticky' ){
		$sql = "UPDATE `".WPF()->tables->topics."` SET `type` = 1 WHERE `topicid` = $topicid";
		if( false !== WPF()->db->query($sql) ){
			wpforo_clean_cache('topic', $topicid);
            WPF()->notice->add('Done!', 'success');
            $response['notice'] = WPF()->notice->get_notices();
            wp_send_json_success($response);
        }
	}elseif( $status === 'unsticky' ){
		$sql = "UPDATE `".WPF()->tables->topics."` SET `type` = 0 WHERE `topicid` = $topicid";
		if( false !== WPF()->db->query($sql) ){
			wpforo_clean_cache('topic', $topicid);
            WPF()->notice->add('Done!', 'success');
            $response['notice'] = WPF()->notice->get_notices();
			wp_send_json_success($response);
        }
	}
	wp_send_json_error($response);
}

add_action('wp_ajax_wpforo_private_ajax', 'wpf_private');
function wpf_private(){
    if( !WPF()->current_userid ) wp_send_json_error();
	if( !($topicid = wpforo_bigintval( wpfval($_POST, 'topicid') )) ) wp_send_json_error();
	$status = wpfval($_POST, 'status');
	if( $status === 'private' ){
	    WPF()->topic->wprivate($topicid, 1);
	    wp_send_json_success();
	}elseif( $status === 'public' ){
		WPF()->topic->wprivate($topicid, 0);
		wp_send_json_success();
	}
	wp_send_json_error();
}

add_action('wp_ajax_wpforo_solved_ajax', 'wpf_solved');
add_action('wp_ajax_nopriv_wpforo_solved_ajax', 'wpf_solved' );
function wpf_solved(){
	if( !$postid = wpforo_bigintval(wpfval($_POST, 'postid')) ) wp_send_json_error();
	if( $post  = WPF()->post->get_post($postid) ){
		if( WPF()->perm->forum_can( 'sv', $post['forumid']) || WPF()->perm->forum_can( 'osv', $post['forumid']) ){
			$solved = ( wpfval($_POST, 'status') === 'solved' ? 1 : 0 );
			$sql = "UPDATE ".WPF()->tables->topics." SET `solved` = %d WHERE `topicid` = %d";
			WPF()->db->query( WPF()->db->prepare($sql, $solved, $post['topicid']) );
			wpforo_clean_cache('topic', $post['topicid']);
			wp_send_json_success();
		}
    }
    wp_send_json_error();
}

add_action('wp_ajax_wpforo_approve_ajax', 'wpf_approve');
function wpf_approve(){
	if( !WPF()->current_userid ){
		WPF()->notice->add( wpforo_get_login_or_register_notice_text() );
		wp_send_json_error();
	}
    if( !$postid = wpforo_bigintval(wpfval($_POST, 'postid')) ) wp_send_json_error();
    $status = wpfval($_POST, 'status');
	if( $status === 'approve' ){
        WPF()->post->status($postid, 0);
		wp_send_json_success();
    }elseif( $status === 'unapprove' ){
        WPF()->post->status($postid, 1);
		wp_send_json_success();
    }
    wp_send_json_error();
}

add_action('wp_ajax_wpforo_close_ajax', 'wpf_close');
function wpf_close(){
	if( !WPF()->current_userid ) wp_send_json_error();
	
	if( !$topicid = wpforo_bigintval(wpfval($_POST, 'topicid')) ) wp_send_json_error();
	$status = wpfval($_POST, 'status');
	if( $status === 'closed' ){
		$sql = "UPDATE ".WPF()->tables->topics." SET closed = 0 WHERE topicid = " . $topicid;
		WPF()->db->query( $sql );
		wpforo_clean_cache('topic', $topicid);
		wp_send_json_success();
	}elseif( $status === 'close' ){
		$sql = "UPDATE ".WPF()->tables->topics." SET closed = 1 WHERE topicid = " . $topicid;
		WPF()->db->query( $sql );
		wpforo_clean_cache('topic', $topicid);
		wp_send_json_success();
	}
	wp_send_json_error();
}

add_action('wp_ajax_wpforo_post_edit', 'wpforo_post_edit');
add_action('wp_ajax_nopriv_wpforo_post_edit', 'wpforo_post_edit' );
function wpforo_post_edit(){
    $r = array('html' => '');
    if( $postid = wpforo_bigintval( wpfval($_POST, 'postid') ) ){
        if( $post = WPF()->post->get_post($postid, false) ){
	        if( WPF()->perm->forum_can('eor', $post['forumid']) || WPF()->perm->forum_can('eot', $post['forumid']) ) {
		        if ( $topic = WPF()->topic->get_topic( $post['topicid'] ) ) {
			        $postmetas = (array) WPF()->postmeta->get_postmeta( $postid, '', true );
			        $post['body'] = htmlspecialchars($post['body']);
			        $values = array_merge( $post, $topic, $postmetas );
			        $values = apply_filters('wpforo_edit_post_ajax', $values, $post, $topic, $postmetas);
			        ob_start();
			        if ( intval( $post['is_first_post'] ) ) {
				        WPF()->data['varname'] = 'topic';
				        WPF()->tpl->topic_form( $post['forumid'], $values );
			        } else {
				        WPF()->data['varname'] = 'post';
				        if( trim($post['title']) ) $values['title'] = $post['title'];
				        WPF()->tpl->reply_form( $topic, $values );
			        }
			        $r['html']  = ob_get_clean();
			        wp_send_json_success($r);
		        }
	        }
        }
    }
    wp_send_json_error($r);
}

add_action('wp_ajax_wpforo_delete_ajax', 'wpf_delete');
function wpf_delete(){
	$resp = array();
	$status = (string) wpfval($_POST, 'status');
	$postid = (int) wpfval($_POST, 'postid');
	if( $status === 'topic' ){
		if( WPF()->topic->delete($postid) ){
			$forumid = (int) wpfval($_POST, 'forumid');
			$resp = array(
				'postid' => $postid,
				'location' => $forumid ? WPF()->forum->get_forum_url($forumid) : wpforo_home_url()
			);
			$resp['notice'] = WPF()->notice->get_notices();
			wp_send_json_success($resp);
		}
	}elseif($status === 'reply'){
		if( WPF()->post->delete($postid) ){
			$root = (int) WPF()->post->get_root($postid);
			$root_replies_count = (int) WPF()->post->get_root_replies_count( $root );
			$resp = array(
				'postid' => $postid,
                'root' => $root,
                'root_count' => $root_replies_count,
			);
			$resp['notice'] = WPF()->notice->get_notices();
			wp_send_json_success($resp);
		}
	}

	$resp['notice'] = WPF()->notice->get_notices();
	wp_send_json_error($resp);
}

add_action('wp_ajax_wpforo_subscribe_ajax', 'wpf_subscribe');
add_action('wp_ajax_nopriv_wpforo_subscribe_ajax', 'wpf_subscribe');
function wpf_subscribe(){
	$return = 0;
    $resp = array('notice' => WPF()->notice->get_notices());
	$args = array(
		'itemid' => wpforo_bigintval(wpfval($_POST, 'itemid')),
		'type'   => sanitize_text_field(wpfval($_POST, 'type')),
		'userid' => WPF()->current_userid,
        'active' => 0,
        'user_name' => '',
        'user_email' => ''
	);

    if( !WPF()->current_userid ){
        if( WPF()->current_user_email ) $args['user_email'] = WPF()->current_user_email;
        if( WPF()->current_user_display_name ) $args['user_name'] = WPF()->current_user_display_name;
    }
    if( !$args['userid'] && !$args['user_email'] ) wp_send_json_error($resp);
	
	if(isset($_POST['status']) && $_POST['status'] == 'subscribe'){
		
		if($_POST['type'] == 'forum'){
			$forum = WPF()->forum->get_forum(wpforo_bigintval($_POST['itemid']));
			if( isset($forum['forumid']) && $forum['forumid'] ){
				if( !WPF()->perm->forum_can('vf', $forum['forumid']) ){
					WPF()->notice->add('You are not permitted to subscribe here', 'error');
					$resp['notice'] = WPF()->notice->get_notices();
                    wp_send_json_error($resp);
				}
			}
		}elseif($_POST['type'] == 'topic'){
		    $topic = WPF()->topic->get_topic($_POST['itemid'], false);
			if( isset($topic['forumid']) && $topic['forumid'] ){
				if( isset($topic['private']) && $topic['private'] && !wpforo_is_owner($topic['userid'], $topic['email']) ){
					if( !WPF()->perm->forum_can('vp', $topic['forumid']) ){
						WPF()->notice->add('You are not permitted to subscribe here', 'error');
                        $resp['notice'] = WPF()->notice->get_notices();
                        wp_send_json_error($resp);
					}
				}
			}
		}
		
		$args['confirmkey'] = WPF()->sbscrb->get_confirm_key();
		
		if( wpforo_feature('subscribe_conf') ){
			############### Sending Email  ##################
			$confirmlink = WPF()->sbscrb->get_confirm_link($args);
            $member_name = ( WPF()->current_userid ? wpforo_user_dname( WPF()->current_user ) : ( $args['user_name'] ? $args['user_name'] : $args['user_email'] ) );
			if($_POST['type'] == 'forum'){
				$item_title = $forum['title'];
			}elseif($_POST['type'] == 'topic'){
				$item_title = $topic['title'];
			}
			$subject = WPF()->sbscrb->options['confirmation_email_subject'];
			$message = WPF()->sbscrb->options['confirmation_email_message'];
			$from_tags = array("[member_name]", "[entry_title]", "[confirm_link]");
			$to_words   = array(sanitize_text_field($member_name),  '<strong>' . sanitize_text_field($item_title) . '</strong>', '<br><br><a target="_blank" href="' . esc_url($confirmlink) . '"> ' . wpforo_phrase('Confirm my subscription', false) . ' </a>');
			$subject = stripslashes(strip_tags(str_replace($from_tags, $to_words, $subject)));
			$message = stripslashes(str_replace($from_tags, $to_words, $message));
			$message = wpforo_kses($message, 'email');
			
			add_filter( 'wp_mail_content_type', 'wpforo_set_html_content_type', 999 );
			$headers = wpforo_mail_headers();
			
			if( wp_mail( WPF()->current_user_email , sanitize_text_field($subject), $message, $headers ) ){
				if( WPF()->sbscrb->add($args) ){
					$return = 1;
				}else{
					$return = 0;
				}
			}else{
				WPF()->notice->add('Can\'t send confirmation email', 'error');
				$return = 0;
			}
			remove_filter( 'wp_mail_content_type', 'wpforo_set_html_content_type' );
			############### Sending Email end  ##############
		}
		else{
			$args['active'] = 1;
			if( WPF()->sbscrb->add($args) ){
				$return = 1;
			}else{
				$return = 0;
			}
		}
		
	}elseif(wpfval($_POST, 'status') === 'unsubscribe'){
		$subscribe = WPF()->sbscrb->get_subscribe( $args );
		$return = (int) WPF()->sbscrb->delete( $subscribe['confirmkey'] );
	}

	$resp['notice'] = WPF()->notice->get_notices();
	if($return){
	    wp_send_json_success($resp);
	}else{
		wp_send_json_error($resp);
	}
}

add_action('wp_ajax_wpforo_layout4_loadmore', 'wpfl4_loadmore');
add_action('wp_ajax_nopriv_wpforo_layout4_loadmore', 'wpfl4_loadmore' );
function wpfl4_loadmore(){
    $success = false;
	WPF()->notice->add('wrong data', 'error');
	$response = array('no_more' => 0, 'output_html' => '', 'notice' => WPF()->notice->get_notices());
    $request = array(
            'forumid' => 0,
            'filter' => 'newest',
            'paged' => 1
    );
    $request = array_merge($request, $_POST);

	if( $forumid = intval($request['forumid']) ){
		$items_count = 0;
		$child_forums = array();
		WPF()->forum->get_childs( $forumid, $child_forums );
		$args = array(
			'offset'    => ( $request['paged'] - 1 ) * WPF()->forum->options['layout_threaded_intro_topics_count'],
			'row_count' => WPF()->forum->options['layout_threaded_intro_topics_count'],
			'forumids'  => $child_forums,
			'orderby'   => 'type, modified',
			'order'     => 'DESC'
		);

		switch( $request['filter'] ){
            case 'solved':
	            $args['solved'] = 1;
                $args['type'] = 0;
                break;
            case 'unsolved':
	            $args['solved'] = 0;
                $args['type'] = 0;
                $args['closed'] = 0;
	            break;
            case 'hottest':
	            $args['orderby'] = 'posts';
            break;
        }

		$topics = WPF()->topic->get_topics( $args, $items_count );
		if( $topics ){
			ob_start();
            if( function_exists('wpforo_thread_forum_template') ) {
                foreach( $topics as $topic ) {
                    wpforo_thread_forum_template( $topic['topicid'] );
                }
            }
			$response['output_html'] = ob_get_clean();
			$success = true;
			$response['notice'] = '';
			if( count($topics) <  WPF()->forum->options['layout_threaded_intro_topics_count']) $response['no_more'] = 1;
		}else{
			$response['no_more'] = 1;
        }

		if( $response['no_more'] ){
		    WPF()->notice->add('all topics has been loaded in this list', 'success');
		    $response['notice'] = WPF()->notice->get_notices();
        }
	}

	if( $success ){
	    wp_send_json_success($response);
    }else{
	    wp_send_json_error($response);
    }
}

add_action('wp_ajax_wpforo_topic_portable_form', 'wpforo_topic_portable_form');
add_action('wp_ajax_nopriv_wpforo_topic_portable_form', 'wpforo_topic_portable_form');
function wpforo_topic_portable_form(){
    $html = '';
    if( $forumid = wpfval($_POST, 'forumid') ){
        WPF()->data['varname'] = 'topic';
        ob_start();
        WPF()->tpl->topic_form($forumid);
        $html = trim( ob_get_clean() );
    }
    if( $html ){
        wp_send_json_success($html);
    }else{
        wp_send_json_error();
    }
}

add_action('wp_ajax_wpforo_qa_comment_loadrest', 'wpforo_qa_comment_loadrest');
add_action('wp_ajax_nopriv_wpforo_qa_comment_loadrest', 'wpforo_qa_comment_loadrest' );
function wpforo_qa_comment_loadrest(){
	WPF()->notice->add('wrong data', 'error');
	$response = array('output_html' => '', 'notice' => WPF()->notice->get_notices());
    if( $parentid = wpfval($_POST, 'parentid') ){
	    $args = array(
		    'root'      => $parentid,
		    'offset'    => WPF()->post->options['layout_qa_comments_limit_count'],
		    'row_count' => PHP_INT_MAX
	    );
	    if( !wpforo_root_exist() ) { unset($args['root']); $args['parentid'] = $parentid; }
	    if( $comments = WPF()->post->get_posts( $args ) ){
		    include_once( wpftpl('layouts/3/comment.php') );

		    ob_start();
		    foreach($comments as $comment) wpforo_qa_comment_template($comment);

		    $response['output_html'] = ob_get_clean();
		    $response['notice'] = '';
		    wp_send_json_success($response);
	    }
    }
	wp_send_json_error($response);
}

add_action('wp_ajax_wpforo_post_url_fixer', 'wpforo_post_url_fixer');
add_action('wp_ajax_nopriv_wpforo_post_url_fixer', 'wpforo_post_url_fixer');
function wpforo_post_url_fixer(){
    if( ($postid = wpforo_bigintval( wpfval($_POST, 'postid') )) && ($referer = wpfval($_POST, 'referer')) ){
        if( is_wpforo_url($referer) ){
	        $diff = 100;
	        $referer_hash = md5( $referer . $postid );
	        $nowtime  = current_time( 'timestamp', 1 );
	        if( $lasttime = (int) get_transient( 'wpforo_post_url_fixer' . $referer_hash  ) ) $diff = $nowtime - $lasttime;
	        if( $diff <= 8 ) exit();
	        set_transient( 'wpforo_post_url_fixer' . $referer_hash, $nowtime, 1000 );
            if ( $post = WPF()->post->get_post( $postid ) ) echo WPF()->post->get_post_url($post);
        }
    }
    exit();
}

add_action('wp_ajax_wpforo_update_database', 'wpforo_ajax_update_database');
function wpforo_ajax_update_database(){
	check_admin_referer( 'wpforo_update_database' );
	wpforo_set_max_execution_time();
	wpforo_update_db();
	exit();
}

############### Sending Email  ##################
function wpforo_set_html_content_type(){
	return apply_filters('wpforo_emails_content_type', 'text/html');
}

function __wpforo_set_html_content_type(){
	return 'text/html';
}

function wpforo_wp_mail_from_name($name){
	if(isset(WPF()->sbscrb->options['from_name']) && WPF()->sbscrb->options['from_name']){
		return WPF()->sbscrb->options['from_name'];
	}
	else{
		return $name;
	}
}

function wpforo_wp_mail_from_email($email){
	if(isset(WPF()->sbscrb->options['from_email']) && WPF()->sbscrb->options['from_email']){
		return WPF()->sbscrb->options['from_email'];
	}
	else{
		return $email;
	}
}

function wpforo_mail_from_name(){
	if(isset(WPF()->sbscrb->options['from_name']) && WPF()->sbscrb->options['from_name']){ return WPF()->sbscrb->options['from_name']; } else {return get_option('blogname');}
}

function wpforo_mail_from_email(){
	if(isset(WPF()->sbscrb->options['from_email']) && WPF()->sbscrb->options['from_email']){return WPF()->sbscrb->options['from_email'];} else {return get_option( 'admin_email' );}
}

function wpforo_mail_headers($from_name = '', $from_email = '', $cc = array(), $bcc = array()){
	$H = array();
	if(!$from_name) $from_name = wpforo_mail_from_name();
	if(!$from_email) $from_email = wpforo_mail_from_email();
	$H[] = 'From: ' . $from_name . ' <' . $from_email . '>';
	if(!empty($cc)){
		foreach($cc as $c){ $c = sanitize_email($c); $H[] = 'CC: ' . $c; }
	}
	if(!empty($bcc)){
		foreach($bcc as $b){ $b = sanitize_email($b); $H[] = 'BCC: ' . $b; }
	}
	return $H;
}

function wpforo_admin_mail_headers($from_name = '', $from_email = '', $cc = array(), $bcc = array()){
	$H = array();
	if(!$from_name) $from_name = wpforo_mail_from_name();
	if(!$from_email) $from_email = wpforo_mail_from_email();
	$H[] = 'From: ' . $from_name . ' <' . $from_email . '>';
	if(empty($cc)){
		$cc = trim(WPF()->sbscrb->options['admin_emails']);
		$cc = explode(',', $cc);
		$cc = array_map('trim', $cc);
	}
	if(!empty($cc)){
		foreach($cc as $c){ $c = sanitize_email($c); $H[] = 'CC: ' . $c; }
	}
	if(!empty($bcc)){
		foreach($bcc as $b){ $b = sanitize_email($b); $H[] = 'BCC: ' . $b; }
	}
	return $H;
}

function wpforo_get_login_or_register_notice_text(){
	$popup_html = '';
	if( !wpforo_is_bot() ){
		$popup_html = sprintf(
			wpforo_phrase('Please %s or %s', FALSE),
			'<a href="' . wpforo_login_url() . '">'.wpforo_phrase('Login', FALSE).'</a>',
			'<a href="' . wpforo_register_url() . '">'.wpforo_phrase('Register', FALSE).'</a>'
		);
	}
	$popup_html = apply_filters( 'wpforo_login_or_register_popup_message', $popup_html );
	return $popup_html;
}

function wpforo_dynamic_phrases_register() {
	if( !$js = WPF()->phrase->get_wpforo_phrases_inline_js() ) return;
	$md5_js = md5($js);

	$inline = false;
	$dynamic_js_file = WPFORO_TEMPLATE_DIR . '/phrases.js';
	if( !file_exists($dynamic_js_file) || $md5_js !== md5_file($dynamic_js_file) ){
		$result = wpforo_write_file( $dynamic_js_file, $js );
		if( wpfval($result, 'error') ) $inline = true;
	}

	wp_register_script( 'wpforo-dynamic-phrases', WPFORO_TEMPLATE_URL . '/phrases.js', false, WPFORO_VERSION . '.' . $md5_js );
	if( $inline ){
		$js = preg_replace('|[\r\n\t]+|', '', $js );
		wp_add_inline_script( 'wpforo-dynamic-phrases', $js );
	}
}

function wpforo_dynamic_style_enqueue() {
	if( !$css = WPF()->tpl->generate_dynamic_css() ) return;
	$md5_css = md5($css);

	$inline = false;
	$dynamic_css_file = WPFORO_TEMPLATE_DIR . '/colors.css';
	if( !file_exists($dynamic_css_file) || $md5_css !== md5_file($dynamic_css_file) ){
		$result = wpforo_write_file( $dynamic_css_file, $css );
		if( wpfval($result, 'error') ) $inline = true;
	}

	wp_register_style( 'wpforo-dynamic-style', WPFORO_TEMPLATE_URL . '/colors.css', false, WPFORO_VERSION . '.' . $md5_css );
	if( $inline ){
		$css = preg_replace('|[\r\n\t]+|', '', $css );
		wp_add_inline_style( 'wpforo-dynamic-style', $css );
	}

	wp_enqueue_style('wpforo-dynamic-style');
}
add_action('wp_enqueue_scripts', 'wpforo_dynamic_style_enqueue', 999);

function wpforo_frontend_register_scripts(){
	wp_register_style('wpforo-font-awesome', WPFORO_URL . '/wpf-assets/css/font-awesome/css/fontawesome-all.min.css', false, '5.11.2' );
	wp_register_style('wpforo-font-awesome-rtl', WPFORO_URL . '/wpf-assets/css/font-awesome/css/font-awesome-rtl.css', array('wpforo-font-awesome'), WPFORO_VERSION );
	wpforo_dynamic_phrases_register();
	wp_register_script( 'wpforo-frontend-js', WPFORO_URL . '/wpf-assets/js/frontend.js', array('jquery', 'wpforo-dynamic-phrases'), WPFORO_VERSION, true );
	wp_localize_script('wpforo-frontend-js', 'wpforo',
		array(
			'ajax_url'         => admin_url( 'admin-ajax.php' ),
			'template_slugs'   => WPF()->tpl->slugs,
			'editor_settings'  => WPF()->tpl->editor_buttons(),
			'revision_options' => WPF()->revision->options,
			'notice'           => array(
				'login_or_register' => wpforo_get_login_or_register_notice_text()
			)
		)
    );
	wp_register_script('wpforo-ajax', WPFORO_URL . '/wpf-assets/js/ajax.js', array('suggest', 'wpforo-frontend-js'), WPFORO_VERSION, true);
	wp_register_style('wpforo-style', WPFORO_TEMPLATE_URL . '/style.css', false, WPFORO_VERSION );
	wp_register_style('wpforo-style-rtl', WPFORO_TEMPLATE_URL . '/style-rtl.css', false, WPFORO_VERSION );
	wp_register_style('wpforo-widgets', WPFORO_TEMPLATE_URL . '/widgets.css', array(), WPFORO_VERSION );
	wp_register_style('wpforo-widgets-rtl', WPFORO_TEMPLATE_URL . '/widgets-rtl.css', array(), WPFORO_VERSION );
	wp_register_script('wpforo-widgets-js', WPFORO_URL . '/wpf-assets/js/widgets.js', array('jquery'), WPFORO_VERSION, true);
	$wpforo_widgets = array(
		'ajax_url'                    => admin_url( 'admin-ajax.php' ),
		'is_live_notifications_on'    => 0,
		'live_notifications_start'    => 30000,
		'live_notifications_interval' => 60000
	);
	if( WPF()->current_userid && wpforo_feature('notifications') && wpforo_feature('notifications-live') ){
		$start    = apply_filters('wpforo_notifications_list', 30000 );
		$interval = apply_filters('wpforo_notifications_list', 60000 );
        if( $interval < 10000 ) $interval = 10000;
		$wpforo_widgets['is_live_notifications_on']    = 1;
		$wpforo_widgets['live_notifications_start']    = $start;
		$wpforo_widgets['live_notifications_interval'] = $interval;
	}
	wp_localize_script('wpforo-widgets-js', 'wpforo_widgets', $wpforo_widgets);

	if( !WPF()->perm->forum_can('va') || !WPF()->perm->usergroup_can('caa') ){
		wp_add_inline_script('wpforo-frontend-js', "jQuery(document).ready(function($){
            $('#wpforo-wrap').on('click', '.attach_cant_view', function(){
               wpforo_notice_show(
                    '<p>" .
                        addslashes(
                            ( is_user_logged_in() ?
                                WPF()->post->options['attach_cant_view_msg'] :
	                            wpforo_get_login_or_register_notice_text()
                            )
                        ) .
                   "</p>'
               );
            });
        })");
	}

	wpforo_frontend_enqueue_scripts();
}
add_action('wp_enqueue_scripts', 'wpforo_frontend_register_scripts');

function wpforo_frontend_enqueue_scripts(){
	if( (is_wpforo_page() && wpforo_feature('font-awesome') == 1) || wpforo_feature('font-awesome') == 2 ){
		wp_enqueue_style('wpforo-font-awesome');
		if (is_rtl()) wp_enqueue_style('wpforo-font-awesome-rtl');
	}

	if( is_wpforo_page() ){
		wp_enqueue_script('wpforo-dynamic-phrases');
		wp_enqueue_script('wpforo-frontend-js');
		wp_enqueue_script('wpforo-ajax');
		if ( is_rtl() ) {
			wp_enqueue_style( 'wpforo-style-rtl' );
		} else {
			wp_enqueue_style( 'wpforo-style' );
		}
	}

	if ( is_rtl() ) {
		wp_enqueue_style( 'wpforo-widgets-rtl' );
	} else {
		wp_enqueue_style( 'wpforo-widgets' );
	}
}

function wpforo_style_options($css){
	if( isset(WPF()->tpl->style['font_size_forum']) && WPF()->tpl->style['font_size_forum'] != 17 ){
		$css .= "\r\n#wpforo-wrap .wpforo-forum-title{font-size: " . intval(WPF()->tpl->style['font_size_forum']) . "px!important; line-height: " . (intval(WPF()->tpl->style['font_size_forum']) + 1) . "px!important;}";
	}
	if( isset(WPF()->tpl->style['font_size_topic']) && WPF()->tpl->style['font_size_topic'] != 16 ){
		$css .= "\r\n#wpforo-wrap .wpforo-topic-title a { font-size: " . intval(WPF()->tpl->style['font_size_topic']) . "px!important; line-height: " . (intval(WPF()->tpl->style['font_size_topic']) + 4) . "px!important; }";
	}
	if( isset(WPF()->tpl->style['font_size_post_content']) && WPF()->tpl->style['font_size_post_content'] != 14 ){
		$css .= "\r\n#wpforo-wrap .wpforo-post .wpf-right .wpforo-post-content {font-size: " . intval(WPF()->tpl->style['font_size_post_content']) . "px!important; line-height: " . (intval(WPF()->tpl->style['font_size_post_content']) + 4) . "px!important;}\r\n#wpforo-wrap .wpforo-post .wpf-right .wpforo-post-content p {font-size: " . intval(WPF()->tpl->style['font_size_post_content']) . "px;}";
	}
	if( 'bottom' === wpfval(WPF()->post->options, 'toolbar_location_topic') ){
		$css .= "\r\n
	    #wpforo #wpforo-wrap .wpf-topic-create .mce-top-part{position:absolute!important;bottom:-40px !important;z-index: 100; width: 100% !important;}
        #wpforo #wpforo-wrap .wpf-topic-create .wp-editor-wrap {margin-bottom: 40px;}
        #wpforo #wpforo-wrap .wpf-topic-create .mce-toolbar .mce-btn-group {padding-top: 8px;}";
	}
	if( 'bottom' === wpfval(WPF()->post->options, 'toolbar_location_reply') ){
		$css .= "\r\n
	    #wpforo #wpforo-wrap .wpf-post-create .mce-top-part{position:absolute!important;bottom:-40px !important;z-index: 100; width: 100% !important;}
        #wpforo #wpforo-wrap .wpf-post-create .wp-editor-wrap {margin-bottom: 40px;}
        #wpforo #wpforo-wrap .wpf-post-create .mce-toolbar .mce-btn-group {padding-top: 8px;}";
	}
	if( isset(WPF()->tpl->style['custom_css']) ){
		$css .= "\r\n" . stripslashes(WPF()->tpl->style['custom_css']);
	}
	return $css;
}
add_filter( 'wpforo_dynamic_css_filter' , 'wpforo_style_options' );

function wpforo_admin_enqueue(){
	wp_register_style('wpforo-font-awesome', WPFORO_URL . '/wpf-assets/css/font-awesome/css/fontawesome-all.min.css', false, '5.7.0' );
	wp_register_style('wpforo-admin', WPFORO_URL . '/wpf-admin/css/admin.css', false, WPFORO_VERSION );
	wp_localize_script( 'wp-color-picker', 'wpColorPickerL10n', array('clear' => __('Clear'), 'defaultString' => __('Default'), 'pick' => __('Select Color')) );
	wp_register_script( 'wpforo-contenthover-addons', WPFORO_URL . '/wpf-admin/js/contenthover/jquery.contenthover.min.js', array('jquery'), WPFORO_VERSION, false );
	wp_register_script( 'wpforo-backend-js', WPFORO_URL . '/wpf-admin/js/backend.js', array( 'jquery' ), WPFORO_VERSION, false );
	wp_localize_script( 'wpforo-backend-js', 'wpforo_admin', array(
        'phrases' => array(
		    'move' => __('Move', 'wpforo'),
		    'delete' => __('Delete', 'wpforo')
	    )
    ));
	wp_register_style('wpforo-deactivation-css', WPFORO_URL . '/wpf-admin/css/deactivation-dialog.css', array(), WPFORO_VERSION);
	wp_register_script('wpforo-deactivation-js', WPFORO_URL . '/wpf-admin/js/deactivation-dialog.js', array('jquery'), WPFORO_VERSION);
	wp_localize_script('wpforo-deactivation-js', 'wpforo_deactivation_obj', array(
		'msgReasonRequired' => __('Please choose one reasons before sending a feedback!', 'wpforo'),
		'msgReasonDescRequired' => __('Please provide more information', 'wpforo'),
		'msgFeedbackHasEmailNoCheckbox' => __('With the email address, please check the "I agree to receive email" checkbox to proceed.', 'wpforo'),
		'msgFeedbackHasCheckboxNoEmail' => __('Please fill your email address for feedback', 'wpforo'),
		'msgFeedbackNotValidEmail' => __('Your email address is not valid', 'wpforo'),
		'adminUrl' => get_admin_url()
	));
	wp_register_script( 'wpforo-iris', admin_url( 'js/iris.min.js' ), array( 'jquery-ui-draggable', 'jquery-ui-slider', 'jquery-touch-punch' ), false, true );

	if ( ! empty( $_GET['page'] ) && false !== strpos( $_GET['page'], 'wpforo' ) ) {
		if ( wpforo_feature( 'font-awesome' ) ) wp_enqueue_style( 'wpforo-font-awesome' );
		wp_enqueue_style( 'wpforo-admin' );
		wp_enqueue_script( 'jquery' );
		wp_enqueue_script( 'jquery-ui-core' );
		wp_enqueue_script( 'jquery-ui-widget' );
		wp_enqueue_script( 'jquery-ui-mouse' );
		wp_enqueue_script( 'jquery-ui-position' );
		wp_enqueue_script( 'jquery-ui-draggable' );
		wp_enqueue_script( 'jquery-ui-droppable' );
		wp_enqueue_script( 'jquery-ui-menu' );
		wp_enqueue_script( 'jquery-ui-sortable' );
		wp_enqueue_script( 'jquery-color' );
		wp_enqueue_script( 'wp-lists' );
		if ( $_GET['page'] === 'wpforo-forums' ) {
			if ( !empty( $_GET['action'] ) ) {
				//Just for excluding 'nav-menu' js loading//
				wp_enqueue_script( 'postbox' );
				wp_enqueue_script( 'link' );
				wp_enqueue_style( 'wp-color-picker' );
				wp_enqueue_script( 'wp-color-picker' );
				wp_enqueue_script( 'wp-color-picker-script-handle');
			} else {
				wp_enqueue_script( 'nav-menu' );
			}
		} elseif ( $_GET['page'] === 'wpforo-settings' && !empty( $_GET['tab'] ) && $_GET['tab'] === 'styles' ) {
			wp_enqueue_style( 'wp-color-picker' );
			wp_enqueue_script( 'wpforo-iris');
			wp_enqueue_script( 'wp-color-picker');
		} elseif ( $_GET['page'] === 'wpforo-community' ) {
			wp_enqueue_script( 'postbox' );
			wp_enqueue_script( 'link' );
		} elseif ( $_GET['page'] === 'wpforo-addons' ) {
			wp_enqueue_script( 'wpforo-contenthover-addons' );
		}
		wp_enqueue_script( 'wpforo-backend-js' );
	}
    if( !get_option('wpforo_deactivation_dialog_never_show') && (strpos( wpforo_get_request_uri(), '/plugins.php' ) !== false) ){
        wp_enqueue_style('wpforo-deactivation-css');
        wp_enqueue_script('wpforo-deactivation-js');
    }

    $screen = get_current_screen();
    if( $screen->id && ('user-edit' === $screen->id || 'profile' === $screen->id) ){
        if( wpforo_feature( 'font-awesome') ) wp_enqueue_style('wpforo-font-awesome');
    }
}
add_action( 'admin_enqueue_scripts', 'wpforo_admin_enqueue' );

function wpforo_admin_permalink_notice() {
    $permalink_structure = get_option( 'permalink_structure' );
	if( !$permalink_structure ){
		$class = 'notice notice-warning';
		$message = __( 'IMPORTANT: wpForo can\'t work with default permalink, please change permalink structure', 'wpforo' );
		printf( '<div class="%1$s"><p>%2$s</p></div>', $class, $message ); 
	}
}
add_action( 'admin_notices', 'wpforo_admin_permalink_notice' );

function wpforo_userform_to_wpuser_html_form($wp_user){
	if( is_super_admin() ){
		$userid = 0;
		$groupid = 0;
		$timezone = '';
		$secondary_groups = array();
		if( is_object($wp_user) ){
			$userid = $wp_user->ID;
			$user = WPF()->member->get_member($wp_user->ID);
			$groupid = intval($user['groupid']);
			$timezone = sanitize_text_field($user['timezone']);
            if(wpfval($user, 'secondary_groups')){
                $secondary_groups = explode(',', $user['secondary_groups']);
                $secondary_groups = array_map('intval', $secondary_groups);
            }
		}
		?>
        <style>
            #wpf-rating-table{ width:100%; font-size:12px; }
            #wpf-rating-table h4{ margin:0; padding:0; font-size:14px; font-weight:bold; white-space:nowrap; }
            #wpf-rating-table .wpf-badge-full{ color:#FFFFFF; white-space:nowrap; font-size:15px; line-height:16px; font-weight:bold; text-align:center; display:inline-block; padding:2px 8px; min-width:30px;}
            #wpf-rating-table .wpf-badge-full.wpf-badge-level-6, #wpf-rating-table .wpf-badge-full.wpf-badge-level-7, #wpf-rating-table .wpf-badge-full.wpf-badge-level-8 { font-size:18px!important; }
            #wpf-rating-table .wpf-badge-full.wpf-badge-level-9, #wpf-rating-table .wpf-badge-full.wpf-badge-level-10 { font-size:22px!important; }
            #wpf-rating-table th, #wpf-rating-table td { padding:5px 10px; text-align:left; vertical-align:top; }
            #wpf-rating-table tr:nth-child(odd) { background: #f5f5f5; }
            #wpf-rating-table tr:nth-child(even) { background: #FFFFFF; }
            #wpf-rating-table th{ text-transform:uppercase; font-size:12px; padding:10px;}
            .wpforo-profile-table input[type=checkbox] { width: auto !important; }
            @media screen and (max-width:700px) { #wpf-rating-table th, #wpf-rating-table td{ display: table-cell!important; }}
        </style>
        <h2 style="margin-bottom: 30px; margin-top: 50px;"><?php _e('Forum Profile Fields - wpForo') ;?></h2>
        <table class="form-table wpforo-profile-table" style="box-shadow: 1px 1px 6px #cccccc; background: #f7f7f7; margin-bottom: 30px; width: 97%;">
            <tr>
                <td colspan="2" style="padding: 5px;"></td>
            </tr>
            <tr class="form-field">
                <th scope="row" style="padding: 10px 20px 10px 20px; width: 30%;">
                    <label for="wpforo_usergroup">
                        <?php _e('Forum - Usergroup', 'wpforo'); ?>
                    </label>
                    <?php if( wpforo_feature('role-synch') ): ?>
                        <p class="description" style="font-weight: normal;">
                            <?php $wpforo_synch_table = admin_url( 'admin.php?page=wpforo-usergroups') ?>
                            <?php echo sprintf( __('Forum Usergroups are synched with User Roles based on the %s. When you change this user Role the Usergroup is automatically changed according to that table.', 'wpforo'), '<a href="' . $wpforo_synch_table . '" target="_blank">Role-Usergroup synchronization table</a>'); ?>
                        </p>
                    <?php endif; ?>
                </th>
                <td style="padding: 15px 20px 10px 20px; vertical-align: top;">
                    <?php if( wpforo_feature('role-synch') ): ?>
                        <select id="wpforo_usergroup" disabled="disabled">
                            <option value="0"><?php _e('Synced with user role', 'wpforo'); ?></option>
	                        <?php WPF()->usergroup->show_selectbox($groupid); ?>
                        </select>
                        <input type="hidden" name="wpforo_usergroup" value="<?php echo intval($groupid); ?>">
                        &nbsp; <span style="color: green"><?php _e('Role-Usergroup Synchronization is Turned ON!', 'wpforo'); ?></span><br />
                        <p class="description" style="font-weight: normal; font-size: 13px; line-height: 18px;"><?php _e('This user Usergroup is automatically changed according to current Role. If you want to disable Role-Usergroup synchronization and manage Usergroups and User Roles independently, please navigate to <b>Forums > Settings > Features</b> admin page and disable "Role-Usergroup Synchronization" option.', 'wpforo'); ?></p>
                    <?php else: ?>
                        <select id="wpforo_usergroup" name="wpforo_usergroup"<?php if( wpforo_is_owner( $userid ) || !current_user_can('administrator') ) echo ' disabled="disabled"'; ?>>
                            <?php WPF()->usergroup->show_selectbox($groupid ? $groupid : WPF()->usergroup->default_groupid); ?>
                        </select>
                    <?php endif; ?>
                </td>
            </tr>
            <tr class="form-field">
                <th scope="row" style="padding: 10px 20px 10px 20px;">
                    <label for="wpforo_usergroup">
                        <?php _e('Forum - Secondary Usergroups', 'wpforo'); ?>
                    </label>
                </th>
                <td style="padding: 15px 20px 10px 20px; vertical-align: top;">
                    <?php $usergroups = WPF()->usergroup->get_secondary_usergroups(); ?>
                    <?php if( !empty($usergroups) ): ?>
                        <?php foreach( $usergroups as $usergroup ): ?>
                            <?php if($usergroup['groupid'] == 1 || $usergroup['groupid'] == 4 ) continue; //|| $usergroup['groupid'] == $groupid ?>
                            <label style="min-width: 20%; display: inline-block; padding-bottom: 5px;">
                                <input type="checkbox"
                                       name="wpforo_secondary_usergroup[]"
                                            value="<?php echo intval($usergroup['groupid']) ?>"
                                                <?php echo (!empty($secondary_groups) && in_array($usergroup['groupid'], $secondary_groups)) ? 'checked="checked"' : ''; ?>>&nbsp;
                                <?php echo esc_html($usergroup['name']); ?>
                            </label>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    <input name="wpforo_secondary_usergroup[]" value="0" type="hidden">
                </td>
            </tr>
            <tr class="form-field">
                <th scope="row" style="padding: 10px 20px 10px 20px;">
                    <label for="wpforo_usertimezone"><?php _e('Forum - User Timezone', 'wpforo'); ?></label>
                </th>
                <td style="padding: 15px 20px 10px 20px; vertical-align: top;">
                    <select name="wpforo_usertimezone" id="wpforo_usertimezone">
                        <?php echo wp_timezone_choice($timezone); ?>
                    </select>
                </td>
            </tr>
            <?php if( !empty( $user ) ) : ?>
                <tr class="form-field">
                    <th scope="row" style="padding: 10px 20px 10px 20px;">
                        <label><?php _e('User Reputation', 'wpforo'); ?></label>
                        <p class="description" style="font-weight: normal;"><?php _e('By default all members get rating badges and titles based on number of posts. However, using this option you can grant lower or higher rating to certain user (this user). The default member reputation badges, titles and points can be managed in Forums > Settings > Members Tab.', 'wpforo') ?></p>
                    </th>
                    <td style="padding: 15px 20px 10px 20px; vertical-align: top;">
                        <div style="padding-bottom: 10px; margin-bottom: 10px; border-bottom: 1px dashed #cccccc;">
                            <label><input type="radio" id="wpf-user-rating-default" name="wpforo_customrank" value="0" <?php if( !wpfval($user, 'stat', 'rank') ) echo ' checked="checked"' ?> /> <?php _e('Default Rating', 'wpforo') ?></label> &nbsp;&nbsp;
                            <label><input type="radio" id="wpf-user-rating-custom" name="wpforo_customrank" value="1" <?php if( wpfval($user, 'stat', 'rank') ) echo ' checked="checked"' ?> /> <?php _e('Custom Rating', 'wpforo') ?></label>
                        </div>

                        <table id="wpf-rating-table" cellspacing="0" cellpadding="0" border="0" style="display: block; width: 100%; height: 150px; overflow-y: scroll;  hidden; border: 1px solid #ccc; border-top: none; border-bottom: none; <?php if( !wpfval($user, 'stat', 'rank') ) echo 'opacity:0.5;' ?>">
                            <tr>
                                <th style="text-align: center; width: 5%;">#</th>
                                <th style="width: 30%"><?php _e('Rating Level', 'wpforo'); ?></th>
                                <th style="width: 35%"><?php _e('Rating Title', 'wpforo'); ?></th>
                                <th style="width: 30%;text-align:center;"><?php _e('Rating Badge', 'wpforo'); ?></th>
                            </tr>
                            <?php $levels = WPF()->member->levels(); ?>
                            <?php foreach( $levels as $level ): $points = WPF()->member->rating($level, 'points'); $bgx = ( $level == wpfval($user, 'stat', 'rating') ) ? 'background-color: #feff88' : ''; ?>
                                <tr>
                                    <td style="text-align: center;<?php echo $bgx ?>"><input type="radio" name="wpforo_userrank" id="wpf-user-rating-<?php echo intval($points) ?>" value="<?php echo $points ?>" <?php if( $level == wpfval($user, 'stat', 'rating') ) echo 'checked="checked"' ?>></td>
                                    <td style="<?php echo $bgx ?>"><h4><label for="wpf-user-rating-<?php echo intval($points) ?>"><?php _e('Level', 'wpforo'); ?> <?php echo esc_html($level) ?></label></h4></td>
                                    <td style="<?php echo $bgx ?>"><?php echo WPF()->member->rating($level, 'title') ?></td>
                                    <td style="text-align:center;<?php echo $bgx ?>"><div class="wpf-badge-full wpf-badge-level-<?php echo esc_attr($level) ?>" style="color:<?php echo WPF()->member->rating($level, 'color') ?>;"><?php echo WPF()->member->rating_badge($level, 'full'); ?></div></td>
                                </tr>
                            <?php endforeach; ?>
                        </table>
                    </td>
                </tr>
            <?php endif; ?>
            <tr>
                <td colspan="2" style="padding: 5px;"></td>
            </tr>
        </table>
        <script>
            jQuery(document).ready(function ($) {
                $("#wpf-user-rating-default").change(function() {if(this.checked) {$("#wpf-rating-table").css('opacity', 0.5);}});
                $("#wpf-user-rating-custom").change(function() {if(this.checked) { $("#wpf-rating-table").css('opacity', 1);}});
            });
        </script>
        <?php
	}
}
add_action( 'user_new_form', 'wpforo_userform_to_wpuser_html_form' );
add_action( 'show_user_profile', 'wpforo_userform_to_wpuser_html_form' );
add_action( 'edit_user_profile', 'wpforo_userform_to_wpuser_html_form' );

function wpforo_do_hook_user_register_nonce(){
	wp_nonce_field('wpforo_user_register', 'wpforo_form');
}
add_action( 'register_form', 'wpforo_do_hook_user_register_nonce', 21 );

function wpforo_do_hook_user_register($userid){
	WPF()->member->synchronize_user($userid);
	if( wpfval($_POST, 'wpfreg') ){
	    $data = $_POST;
		$data['userid'] = $userid;
		$data['wpfreg'] = wpforo_clear_array( $data['wpfreg'], array( 'user_login', 'user_email', 'user_pass1', 'user_pass2' ), 'key' );
		WPF()->member->update( $data, 'full', false );
	}
}
add_action( 'user_register', 'wpforo_do_hook_user_register', 10, 1 );

function wpforo_do_hook_update_profile($userid){
    if( $userid ){
        if( current_user_can( 'create_users' ) || current_user_can( 'edit_user' ) ){
            if( wpfval($_POST, 'wpforo_usergroup') || wpfkey($_POST, 'wpforo_usertimezone')  ){
                $member = wpforo_member($userid);
                $can_change_own_rating = true;
                if( wpfval($member, 'userid') ){
                    $groupid = $member['groupid'];
                    $secondary_groups = $member['secondary_groups'];
                    if( current_user_can('administrator') ){
                        if( wpfval($_POST, 'wpforo_usergroup') ){
                            if( $userid != 1 && !wpforo_is_owner( $userid ) && current_user_can('administrator') ){
                                $groupid = intval($_POST['wpforo_usergroup']);
                            }
                        }
                        if( wpfval($_POST, 'wpforo_secondary_usergroup') ){
                            if( !empty($_POST['wpforo_secondary_usergroup']) ){
                                $secondary_groups = array_map('intval', $_POST['wpforo_secondary_usergroup']);
                            }
                        }
                    } else {
                        if( wpforo_is_owner( $userid ) ){
                            $can_change_own_rating = false;
                        }
                    }

                    $rank = ( $can_change_own_rating && wpfval($_POST, 'wpforo_customrank') ) ? wpfval($_POST, 'wpforo_userrank') : 0;

                    $args = array(  'groupid' => intval($groupid),
                                    'site' => esc_url_raw($_POST['url']),
                                    'about' => wpforo_kses($_POST['description'], 'user_description'),
                                    'timezone' => ( isset($_POST['wpforo_usertimezone']) ? sanitize_text_field($_POST['wpforo_usertimezone']) : '' ),
                                    'secondary_groups' => $secondary_groups,
                                    'rank' => intval($rank) );
                    WPF()->member->update_profile_fields($userid, $args, false);
                    WPF()->member->reset($userid);

	                if( !wpforo_is_owner( $userid ) ) WPF()->member->inactive_to_active($userid);
                }
            }
        }
    }
}
add_action('personal_options_update', 'wpforo_do_hook_update_profile');
add_action('edit_user_profile_update', 'wpforo_do_hook_update_profile');

function wpforo_actions_on_wp_login($user_login, $user = array()){
	if(empty($user)) return;
	$userid = wpforo_bigintval( $user->ID );
	WPF()->member->inactive_to_active($userid);
}
add_action('wp_login', 'wpforo_actions_on_wp_login', 10, 2);

function wpforo_avatar( $avatar, $id_or_email, $size, $default, $alt ) {
	if(!wpforo_feature('replace-avatar')) return $avatar;
    $user = false;
    if ( is_numeric( $id_or_email ) ) {
        $id = (int) $id_or_email;
        $user = get_user_by( 'id' , $id );
    }elseif( is_object( $id_or_email ) ) {
        if ( ! empty( $id_or_email->user_id ) ) {
            $id = (int) $id_or_email->user_id;
            $user = get_user_by( 'id' , $id );
        }
		elseif ( ! empty( $id_or_email->ID ) ) {
			$id = (int) $id_or_email->ID;
            $user = get_user_by( 'id' , $id );
		}
    }else{
        $user = get_user_by( 'email', $id_or_email );	
    }

    if( $user && is_object( $user ) ){
        if( $src = WPF()->member->get_avatar_url($user->data->ID) ){
            $avatar = "<img alt='" . esc_attr($alt) . "' src='" . esc_url($src) . "' class='avatar avatar-" . esc_attr($size) . " photo' height='" . esc_attr($size) . "' width='" . esc_attr($size) . "' />";
        }
    }
    return $avatar;
}
add_filter( 'get_avatar' , 'wpforo_avatar' , 10, 5 );

function wpforo_pre_get_avatar_data($args, $id_or_email){
	if( wpforo_feature('replace-avatar') ){
	    $key = array('wpforo_pre_get_avatar_data', $id_or_email);
	    if( WPF()->ram_cache->is_exist($key) ){
		    $args['url'] = WPF()->ram_cache->get($key);
        }else{
		    $user       = false;
		    if ( is_object( $id_or_email ) && isset( $id_or_email->comment_ID ) ) {
			    $id_or_email = get_comment( $id_or_email );
		    }
		    // Process the user identifier.
		    if ( is_numeric( $id_or_email ) ) {
			    $user = get_user_by( 'id', absint( $id_or_email ) );
		    } elseif ( is_string( $id_or_email ) ) {
			    if ( !strpos( $id_or_email, '@md5.gravatar.com' ) ) {
				    $user = get_user_by( 'email', $id_or_email );
			    }
		    } elseif ( $id_or_email instanceof WP_User ) {
			    // User Object
			    $user = $id_or_email;
		    } elseif ( $id_or_email instanceof WP_Post ) {
			    // Post Object
			    $user = get_user_by( 'id', (int) $id_or_email->post_author );
		    } elseif ( function_exists('is_avatar_comment_type') && $id_or_email instanceof WP_Comment ) {
			    if ( is_avatar_comment_type( get_comment_type( $id_or_email ) ) ) {
				    if ( ! empty( $id_or_email->user_id ) ) {
					    $user = get_user_by( 'id', (int) $id_or_email->user_id );
				    }
				    if ( ( ! $user || is_wp_error( $user ) ) && ! empty( $id_or_email->comment_author_email ) ) {
					    $user = get_user_by( 'email', $id_or_email->comment_author_email );
				    }
			    }
		    }

		    if( $user && is_object( $user ) ){
			    if( $avatar_url = WPF()->member->get_avatar_url($user->data->ID) ) {
			        WPF()->ram_cache->set($key, $avatar_url);
				    $args['url'] = $avatar_url;
			    }
		    }
        }
	}
	return $args;
}
add_filter('pre_get_avatar_data', 'wpforo_pre_get_avatar_data', 10, 2);

function wpforo_mention_nickname_to_link($match){
    $return = $match[0];
    if( $member = WPF()->member->get_member($match[1]) ){
        $href = WPF()->member->profile_url($member);
        $dname   = wpforo_user_dname($member);
        $return = sprintf('<a href="%s" title="%s">@%s</a>%s', $href, $dname, $match[1], $match[2]);
    }

    return  $return;
}

function wpforo_mentioned_code_to_link($text){
    $text = preg_replace_callback('#@([^\r\n\t\s\0<>\[\]!,\.\(\)\'\"\|\?\@]+)($|[\r\n\t\s\0<>\[\]!,\.\(\)\'\"\|\?\@])#isu', 'wpforo_mention_nickname_to_link', $text);
    return $text;
}
add_filter('wpforo_body_text_filter', 'wpforo_mentioned_code_to_link');

function wpforo_send_mail_to_mentioned_users($item){
    $return = false;
    if( preg_match_all('#@([^\r\n\t\s\0<>\[\]!,\.\(\)\'\"\|\?\@]+)(?:$|[\r\n\t\s\0<>\[\]!,\.\(\)\'\"\|\?\@])#isu', $item['body'], $matches, PREG_SET_ORDER) ){

        $dname = wpforo_user_dname(WPF()->current_user);
        $topic = wpforo_topic($item['topicid']);
        $_to_words = array($dname);
        if( array_key_exists('first_postid', $item) ){
            $_to_words[] = $item['title'];
            $_to_words[] = '<a target="_blank" href="'. esc_url($item['topicurl']) .'">'. esc_url($item['topicurl']) .'</a>';
        }else{
            $_to_words[] = $topic['title'];
            $_to_words[] = '<a target="_blank" href="'. esc_url($item['posturl']) .'">'. esc_url($item['posturl']) .'</a>';
        }

        $_subject = WPF()->sbscrb->options['user_mention_email_subject'];
	    $_from_tags = array("[author-user-name]", "[topic-title]", "[post-url]");
	    $_subject = str_replace($_from_tags, $_to_words, $_subject);

	    $post_body_length = apply_filters('wpforo_email_notification_post_body_length', 0);
	    $_from_tags[] = "[post-desc]";
	    $_to_words[] = wpforo_text( wpforo_kses($item['body'], 'email'), $post_body_length, false, false, false, true, false );
	    $_message = WPF()->sbscrb->options['user_mention_email_message'];
	    $_message = str_replace($_from_tags, $_to_words, $_message);

	    add_filter( 'wp_mail_content_type', 'wpforo_set_html_content_type', 999 );
        $headers = wpforo_mail_headers();

        foreach ( $matches as $match ){
            $member = WPF()->member->get_member($match[1]);
            if( !empty($member['user_email']) ){

                if( WPF()->current_userid == $member['userid'] ) continue;

                if( in_array($member['user_email'], WPF()->sbscrb->already_sent_emails) ) continue;

                if( isset($item['private']) && $item['private'] ){
                    if( !WPF()->perm->forum_can('vp', $item['forumid'], $member['groupid'], false) ) continue;
                }
                if( isset($item['status']) && $item['status'] ){
                    if( !WPF()->perm->forum_can('au', $item['forumid'], $member['groupid'], false) ) continue;
                }
                if( !WPF()->perm->forum_can('vf', $item['forumid'], $member['groupid'], $member['secondary_groups']) ) continue;

                $notification = apply_filters('wpforo_user_mentioning_notification', true);

                //Adding notification if user doesn't mention yourself
                if( $notification && $member['userid'] != wpfval( $item, 'userid' ) ){
                    $notify = false;
                    //If current post is a reply check the parent post
                    if( wpfval($item,'parentid') ){
                        $parent_post = wpforo_post($item['parentid']);
                        //Parent post author has already received a "new_reply" notification,
                        //so the mentioning is not neccessary. It only adds a new notification
                        //if the parent post author is not the mentioned user.
                        if( !empty($parent_post) && $member['userid'] != wpfval($parent_post, 'userid')){
                            $notify = true;
                        }
                    } else {
                        //This post is probably a first topic post or a single post (not a reply)
                        //The topic author has already received a "new_reply" notification,
                        //so the mentioning is not neccessary. It only adds a new notification
                        //if the topic author is not the mentioned user.
                        if( !empty($topic) && $member['userid'] != wpfval( $topic, 'userid' ) ) {
                            $notify = true;
                        }
                    }
                    if( $notify ){
                        $args = array(
                            'itemid' => (wpfval($item, 'postid') ? $item['postid'] : $item['first_postid']),
                            'userid' => $member['userid'],
                            'content' => (wpfval($item, 'body') ? $item['body'] : $item['title']),
                            'permalink' => (wpfval($item, 'posturl') ? $item['posturl'] : $item['topicurl']),
                        );
                        WPF()->activity->add_notification( 'new_mention', $args );
                    }
                }

                //Sending Email Notification
                if( WPF()->sbscrb->options['user_mention_notify'] ){
                    $dname   = wpforo_user_dname($member);
                    $subject = stripslashes(str_replace('[mentioned-user-name]', $dname, $_subject));
                    $message = stripslashes(str_replace('[mentioned-user-name]', $dname, $_message));
                    $message = wpforo_kses($message, 'email');
                    if( $return = wp_mail( $member['user_email'], sanitize_text_field($subject), $message, $headers ) ){
                        WPF()->sbscrb->already_sent_emails[] = $member['user_email'];
                    }
                }
            }
        }
        remove_filter( 'wp_mail_content_type', 'wpforo_set_html_content_type' );

    }

    return $return;
}
add_action( 'wpforo_after_add_topic', 'wpforo_send_mail_to_mentioned_users' );
add_action( 'wpforo_after_add_post', 'wpforo_send_mail_to_mentioned_users' );

function wpforo_topic_auto_subscribe($item){
	if(!isset($_POST['wpforo_topic_subs']) || !$_POST['wpforo_topic_subs'] ) return FALSE;
	
	if( isset($item['forumid']) && $item['forumid'] ){
		if( isset($item['private']) && $item['private'] && !wpforo_is_owner($item['userid']) ){
			if( !WPF()->perm->forum_can('vp', $item['forumid']) ){
				WPF()->notice->add('You are not permitted to subscribe here', 'error');
		 		return FALSE;
			}
		}
		else{
			//This is not a Private Topic or Current User is the owner. 
		}
	}
	else{
		 WPF()->notice->add('Forum ID is not detected', 'error');
		 return FALSE;
	}
	
	$args = array(
		'itemid' => wpforo_bigintval($item['topicid']),
		'type'   => 'topic',
		'userid' => WPF()->current_userid,
        'user_name' => '',
        'user_email' => ''
	);

    if( !WPF()->current_userid ){
        if( WPF()->current_user_email ) $args['user_email'] = WPF()->current_user_email;
        if( WPF()->current_user_display_name ) $args['user_name'] = WPF()->current_user_display_name;
    }
    if( !$args['userid'] && !$args['user_email'] ) return false;
	
	$args['confirmkey'] = WPF()->sbscrb->get_confirm_key();
	
	if( wpforo_feature('subscribe_conf') ){
		############### Sending Email  ##################
		$confirmlink = WPF()->sbscrb->get_confirm_link($args);
        $member_name = ( WPF()->current_userid ? wpforo_user_dname(WPF()->current_user) : ( $args['user_name'] ? $args['user_name'] : $args['user_email'] ) );
        $subject = WPF()->sbscrb->options['confirmation_email_subject'];
		$message = WPF()->sbscrb->options['confirmation_email_message'];
		$topic = WPF()->db->get_row("SELECT * FROM `".WPF()->tables->topics."` WHERE `topicid` = " . intval($item['topicid']), ARRAY_A);
		$from_tags = array("[member_name]", "[entry_title]", "[confirm_link]");
		$to_words   = array(sanitize_text_field($member_name),  '<strong>' . sanitize_text_field($topic['title']) . '</strong>', '<br><br><a target="_blank" href="' . esc_url($confirmlink) . '"> ' . wpforo_phrase('Confirm my subscription', false) . ' </a>');
		$subject = stripslashes(str_replace($from_tags, $to_words, $subject));
		$message = stripslashes(str_replace($from_tags, $to_words, $message));
		$message = wpforo_kses($message, 'email');
		
		add_filter( 'wp_mail_content_type', 'wpforo_set_html_content_type', 999 );
		$headers = wpforo_mail_headers();

		if( wp_mail( WPF()->current_user_email, sanitize_text_field($subject), $message, $headers ) ){
			if( $response = WPF()->sbscrb->add($args) ) return $response;
		}else{
			WPF()->notice->add('Can\'t send confirmation email', 'error');
			return FALSE;
		}
		remove_filter( 'wp_mail_content_type', 'wpforo_set_html_content_type' );
		############### Sending Email end  ##############
	}else{
		$args['active'] = 1;
		if( $response = WPF()->sbscrb->add($args) ) return $response;
	}
	return FALSE;
}
add_action( 'wpforo_after_add_topic', 'wpforo_topic_auto_subscribe' );
add_action( 'wpforo_after_add_post', 'wpforo_topic_auto_subscribe' );

function wpforo_forum_subscribers_mail_sender( $topic ){
    if( !wpfval($topic, 'topicurl') ) $topic['topicurl'] = wpforo_topic($topic['topicid'], 'url');
    if( !wpfkey($topic, 'body') ) $topic['body'] = wpforo_post($topic['first_postid'], 'body');

	if( defined('IS_GO2WPFORO') && IS_GO2WPFORO ) return;

    $forums_sbs = WPF()->sbscrb->get_subscribes( array( 'itemid' => 0, 'type' => array('forums', 'forums-topics') ) );
    $forum_sbs = WPF()->sbscrb->get_subscribes( array( 'itemid' => $topic['forumid'], 'type' => array('forum', 'forum-topic') ) );
    $subscribers = array_merge($forums_sbs, $forum_sbs);
	if( WPF()->sbscrb->options['new_topic_notify'] ){
		$admin_emails = explode(',', WPF()->sbscrb->options['admin_emails']);
		foreach( $admin_emails as $admin_email ) $subscribers[] = sanitize_email( $admin_email );
	}
	
	$subscribers = apply_filters('wpforo_forum_subscribers', $subscribers);
	
	foreach($subscribers as $subscriber){
		
		if( is_array($subscriber) ){
		    if( $subscriber['userid'] ){
                $member = WPF()->member->get_member( $subscriber['userid'] );
            }elseif( $subscriber['user_email'] ){
                $member = array('groupid' => 4, 'display_name' => ($subscriber['user_name'] ? $subscriber['user_name'] : $subscriber['user_email']), 'user_email' => $subscriber['user_email']);
            }else{
		        continue;
            }
			$unsubscribe_link = WPF()->sbscrb->get_unsubscribe_link($subscriber['confirmkey']);
		}else{
			$member = array('display_name' => $subscriber, 'user_email' => $subscriber);
			$unsubscribe_link = '#';
		}

        if( isset($topic['forumid']) && $topic['forumid'] ){

		    $subscriber_groupid = ( wpfval($member, 'groupid') ) ? $member['groupid'] : ( wpfval($subscriber, 'userid') ? WPF()->usergroup->get_groupid_by_userid($subscriber['userid']) : 0 );
            $subscriber_secondary_groups = ( wpfval($member, 'secondary_groups') ) ? $member['secondary_groups'] : ( wpfval($subscriber, 'userid') ? WPF()->usergroup->get_second_groupid_by_userid($subscriber['userid']) : 0 );

            if( isset($topic['private']) && $topic['private'] && isset($subscriber['userid'])
                && ( ( $topic['userid'] && $subscriber['userid'] && $topic['userid'] != $subscriber['userid'] )
                    || ( $topic['email'] && $subscriber['user_email'] && $topic['email'] != $subscriber['user_email'] ) ) ){
				if( !WPF()->perm->forum_can('vp', $topic['forumid'], $subscriber_groupid, false) ){
					continue;
				}
			}
			if( isset($topic['status']) && $topic['status'] == 1 && isset($subscriber['userid']) ){
				if( !WPF()->perm->forum_can('au', $topic['forumid'], $subscriber_groupid, false) ){
					continue;
				}
			}
            if( $subscriber_groupid && !WPF()->perm->forum_can('vf', $topic['forumid'], $subscriber_groupid, $subscriber_secondary_groups) ){
                continue;
            }
		}
		
		$owner = wpforo_member( $topic );
		
		if($owner['user_email'] == $member['user_email']) continue;
        if( in_array($member['user_email'], WPF()->sbscrb->already_sent_emails) ) continue;
		
		$forum  = WPF()->forum->get_forum( $topic['forumid'] );
		
		############### Sending Email  ##################
			
			if( isset($topic['status']) && $topic['status'] ){
				$subject_prefix = __('Please Moderate: ', 'wpforo');
				$mod_text = '<br /><br /><p style="color:#DD0000">' . __('This topic is currently unapproved. You can approve topics in Dashboard &raquo; Forums &raquo; Moderation admin page.', 'wpforo') . '</p>';
			}
			else{
				$subject_prefix = '';
				$mod_text = '';
			}

			$subject = WPF()->sbscrb->options['new_topic_notification_email_subject'];
		 	$message = WPF()->sbscrb->options['new_topic_notification_email_message'];
		 	$message_length = apply_filters('wpforo_email_notification_post_body_length', 0);


			$from_tags = array( "[member_name]", "[post_author_name]", "[forum]", "[unsubscribe_link]", "[topic_title]", "[topic_desc]");
			$to_words  = array( sanitize_text_field($member['display_name']),
                                    sanitize_text_field($owner['display_name']),
                                        '<a href="' . esc_url($forum['url']) . '">' . sanitize_text_field($forum['title']) . '</a>',
										    '<br><a target="_blank" href="' . esc_url($unsubscribe_link) . '">' . wpforo_phrase('Unsubscribe', false) . '</a>' ,
											    '<a target="_blank" href="' . esc_url($topic['topicurl']) . '">' . sanitize_text_field($topic['title']) . '</a>' ,
												    wpforo_text( wpforo_kses( $topic['body'], 'email'), $message_length, FALSE, false, false, true, false ) );
			
			$subject = stripslashes(strip_tags(str_replace($from_tags, $to_words, $subject)));
			$message = stripslashes(str_replace($from_tags, $to_words, $message));
			
			add_filter( 'wp_mail_content_type', 'wpforo_set_html_content_type', 999 );
			$headers = wpforo_mail_headers();
			$subject = $subject_prefix . $subject;
			$message = $message . $mod_text;
	 		if( $email_status = wp_mail( $member['user_email'], $subject, $message, $headers ) ){
                WPF()->sbscrb->already_sent_emails[] = $member['user_email'];
            }
	 		remove_filter( 'wp_mail_content_type', 'wpforo_set_html_content_type' );
	 		
	 	############### Sending Email end  ##############
		
	}
}
add_action( 'wpforo_after_add_topic', 'wpforo_forum_subscribers_mail_sender', 12);

function wpforo_topic_subscribers_mail_sender( $post ){

	if( defined('IS_GO2WPFORO') && IS_GO2WPFORO ) return;

    $forums_sbs = WPF()->sbscrb->get_subscribes( array( 'itemid' => 0, 'type' => 'forums-topics' ) );
    $forum_sbs = WPF()->sbscrb->get_subscribes( array( 'itemid' => $post['forumid'], 'type' => 'forum-topic' ) );
    $topic_sbs = WPF()->sbscrb->get_subscribes( array( 'itemid' => $post['topicid'], 'type' => 'topic' ) );
    $subscribers = array_merge($forums_sbs, $forum_sbs, $topic_sbs);

	if( WPF()->sbscrb->options['new_reply_notify'] ){
		$admin_emails = explode(',', WPF()->sbscrb->options['admin_emails']);
		foreach( $admin_emails as $admin_email ) $subscribers[] = sanitize_email( $admin_email );
	}
	$topic = WPF()->db->get_row("SELECT * FROM `".WPF()->tables->topics."` WHERE `topicid` = " . intval($post['topicid']), ARRAY_A);
	$subscribers = apply_filters('wpforo_topic_subscribers', $subscribers);

	foreach($subscribers as $subscriber){
		
		if( is_array($subscriber) ){
            if( $subscriber['userid'] ){
                $member = WPF()->member->get_member( $subscriber['userid'] );
            }elseif( $subscriber['user_email'] ){
                $member = array('groupid' => 4, 'display_name' => ($subscriber['user_name'] ? $subscriber['user_name'] : $subscriber['user_email']), 'user_email' => $subscriber['user_email']);
            }else{
                continue;
            }
			$unsubscribe_link = WPF()->sbscrb->get_unsubscribe_link($subscriber['confirmkey']);
		}else{
			$member = array('display_name' => $subscriber, 'user_email' => $subscriber);
			$unsubscribe_link = '#';
		}
		
		$owner = wpforo_member( $post );
		if($owner['user_email'] == $member['user_email']) continue;
		if( in_array($member['user_email'], WPF()->sbscrb->already_sent_emails) ) continue;

		if( isset($topic['forumid']) && $topic['forumid'] && isset($subscriber['userid']) ){

            $subscriber_groupid = ( wpfval($member, 'groupid') ) ? $member['groupid'] : ( wpfval($subscriber, 'userid') ? WPF()->usergroup->get_groupid_by_userid($subscriber['userid']) : 0 );
            $subscriber_secondary_groups = ( wpfval($member, 'secondary_groups') ) ? $member['secondary_groups'] : ( wpfval($subscriber, 'userid') ? WPF()->usergroup->get_second_groupid_by_userid($subscriber['userid']) : NULL );

            if( isset($topic['private']) && $topic['private']
                && ( ( $topic['userid'] && $subscriber['userid'] && $topic['userid'] != $subscriber['userid'] )
                    || ( $topic['email'] && $subscriber['user_email'] && $topic['email'] != $subscriber['user_email'] ) ) ){
				if( !WPF()->perm->forum_can('vp', $topic['forumid'], $subscriber_groupid, false) ){
					continue;
				}
			}
			if( (isset($topic['status']) && $topic['status'] == 1) || (isset($post['status']) && $post['status'] == 1) ){
				if( !WPF()->perm->forum_can('au', $topic['forumid'], $subscriber_groupid, false) ){
					continue;
				}
			}
            if( $subscriber_groupid && !WPF()->perm->forum_can('vf', $topic['forumid'], $subscriber_groupid, $subscriber_secondary_groups) ){
                continue;
            }
		}
		
		############### Sending Email  ##################
			
			if( isset($post['status']) && $post['status'] ){
				$subject_prefix = __('Please Moderate: ', 'wpforo');
				$mod_text = '<br /><br /><p style="color:#DD0000">' . __('This post is currently unapproved. You can approve posts in Dashboard &raquo; Forums &raquo; Moderation admin page.', 'wpforo') . '</p>';
			}
			else{
				$subject_prefix = '';
				$mod_text = '';
			}
			
			$subject = WPF()->sbscrb->options['new_post_notification_email_subject'];
		 	$message = WPF()->sbscrb->options['new_post_notification_email_message'];
            $message_length = apply_filters('wpforo_email_notification_post_body_length', 0);
			
			$from_tags = array( "[member_name]", "[post_author_name]", "[topic]", "[unsubscribe_link]", "[reply_title]", "[reply_desc]");
			$to_words  = array( sanitize_text_field($member['display_name']),
                                    sanitize_text_field($owner['display_name']),
									    '<a href="' . esc_url($post['posturl']) . '">' . sanitize_text_field($topic['title']) . '</a>',
										    '<br><a target="_blank" href="' . esc_url($unsubscribe_link) . '">'.wpforo_phrase('Unsubscribe', false).'</a>' ,
											    '<a target="_blank" href="' . esc_url($post['posturl']) . '">' . sanitize_text_field($post['title']) . '</a>' ,
												    wpforo_text( wpforo_kses($post['body'], 'email'), $message_length, FALSE, false, false, true, false ) );
			
			$subject = stripslashes(strip_tags(str_replace($from_tags, $to_words, $subject)));
			$message = stripslashes(str_replace($from_tags, $to_words, $message));
			
			add_filter( 'wp_mail_content_type', 'wpforo_set_html_content_type', 999 );
			$headers = wpforo_mail_headers();
			$subject = $subject_prefix . $subject;
			$message = $message . $mod_text;
	 		if( $email_status = wp_mail( $member['user_email'], $subject, $message, $headers ) ){
                WPF()->sbscrb->already_sent_emails[] = $member['user_email'];
            }
	 		remove_filter( 'wp_mail_content_type', 'wpforo_set_html_content_type' );
	 		
	 	############### Sending Email end  ##############
		
	}
}
add_action( 'wpforo_after_add_post', 'wpforo_topic_subscribers_mail_sender', 13, 1 );

function wpforo_move_uploded_default_attach($argname, $return = 'html'){
	if( !empty($_FILES[$argname]) && !empty($_FILES[$argname]['name']) ){
        $name = sanitize_file_name($_FILES[$argname]['name']); //myimg.png
        $type = sanitize_mime_type($_FILES[$argname]['type']); //image/png
        $tmp_name = sanitize_text_field($_FILES[$argname]['tmp_name']); //D:\wamp\tmp\php986B.tmp
        $error = intval($_FILES[$argname]['error']); //0
        $size = intval($_FILES[$argname]['size']); //6112

        $phpFileUploadErrors = array(
            0 => 'There is no error, the file uploaded with success',
            1 => 'The uploaded file size is too big',
            2 => 'The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form',
            3 => 'The uploaded file was only partially uploaded',
            4 => 'No file was uploaded',
            6 => 'Missing a temporary folder',
            7 => 'Failed to write file to disk.',
            8 => 'A PHP extension stopped the file upload.',
        );

        if( $error ){
            WPF()->notice->add($phpFileUploadErrors[$error], 'error');
            return '';
        }elseif( $size > WPF()->post->options['max_upload_size'] ){
            WPF()->notice->add('The uploaded file size is too big', 'error');
            return '';
        }

        if(function_exists('pathinfo')){
            $ext = pathinfo($name, PATHINFO_EXTENSION);
        }else{
            $ext = substr(strrchr($name, '.'), 1);
        }
        $ext = strtolower($ext);
        $mime_types = get_allowed_mime_types();
        $mime_types = array_flip($mime_types);
        if(!empty($mime_types)){
            $allowed_types = implode('|', $mime_types);
            $expld = explode('|', $allowed_types);
            if( !in_array($ext, $expld) ){
                WPF()->notice->add('File type is not allowed', 'error');
                return '';
            }
            if( !WPF()->perm->can_attach_file_type($ext) ){
                WPF()->notice->add('You are not allowed to attach this file type', 'error');
                return '';
            }
        }

        $wp_upload_dir = wp_upload_dir();
        $uplds_dir = $wp_upload_dir['basedir']."/wpforo";
        $attach_dir = $wp_upload_dir['basedir']."/wpforo/default_attachments";
        $attach_url = preg_replace('#^https?\:#is', '', $wp_upload_dir['baseurl'])."/wpforo/default_attachments";
        if(!is_dir($uplds_dir)) wp_mkdir_p($uplds_dir);
        if(!is_dir($attach_dir)) wp_mkdir_p($attach_dir);

        $fnm = pathinfo($name, PATHINFO_FILENAME);
        $fnm = str_replace(' ', '-', $fnm);
        while(strpos($fnm, '--') !== FALSE) $fnm = str_replace('--', '-', $fnm);
        $fnm = preg_replace("/[^-a-zA-Z0-9_]/", "", $fnm);
        $fnm = trim($fnm, "-");
        $fnm_empty = ( $fnm ? FALSE : TRUE );

        $file_name = $fnm . "." . $ext;

        $attach_fname = current_time( 'timestamp', 1 ).( !$fnm_empty ? '-' : '' ) . $file_name;
        $attach_path = $attach_dir . "/" . $attach_fname;

        if( is_dir($attach_dir) && move_uploaded_file($tmp_name, $attach_path) ){
            $attach_id = wpforo_insert_to_media_library( $attach_path, $fnm );
            if( $return === 'html' ){
	            return "\r\n" . '<div id="wpfa-' . $attach_id . '" class="wpforo-attached-file"><a class="wpforo-default-attachment" href="' . esc_url($attach_url.'/'.$attach_fname) . '" target="_blank"><i class="fas fa-paperclip"></i>&nbsp;' . esc_html(basename($name)) . '</a></div>';
            }else{
	            return array('fileurl' => $attach_url.'/'.$attach_fname, 'filename' => basename($name), 'mediaid' => $attach_id);
            }
        }else{
            WPF()->notice->add('Can\'t upload file', 'error');
        }
	}
	return '';
}

function wpforo_add_default_attachment($args){
	if( !empty($_FILES['attachfile']) && !empty($_FILES['attachfile']['name']) ){
        if( WPF()->perm->can_attach( wpfval($args, 'forumid') ) ){
            if( $default_attach = wpforo_move_uploded_default_attach('attachfile') ){
                $args['body'] .= $default_attach;
                $args['has_attach'] = 1;
            }
        }
	}
	return $args;
}

function wpforo_delete_attachment( $attach_post_id ){
	if(!$attach_post_id) return;
	$posts = WPF()->db->get_results("SELECT `postid`, `body` FROM `" . WPF()->tables->posts . "` WHERE `body` LIKE '%wpfa-" . intval( $attach_post_id ) . "%'", ARRAY_A );
	if(!empty($posts) || is_array($posts)){
		foreach( $posts as $post ){
			$body = preg_replace('|<div[^><]*id=[\'\"]+wpfa-' . $attach_post_id . '[\'\"]+[^><]*>.+?</div>|is', '<div class="wpforo-attached-file wpfa-deleted">' . wpforo_phrase('Attachment removed', FALSE) . '</div>', $post['body'] );
			if( $body ) WPF()->db->query("UPDATE `" . WPF()->tables->posts . "` SET `body` = '" . esc_sql( $body ) . "' WHERE `postid` = " . intval($post['postid']));
		}
	}
}

function wpforo_default_attachments_filter($text){
	if( preg_match_all('#<a[^<>]*class=[\'"]wpforo-default-attachment[\'"][^<>]*href=[\'"]([^\'"]+)[\'"][^<>]*>[\r\n\t\s\0]*(?:<i[^<>]*>[\r\n\t\s\0]*</i>[\r\n\t\s\0]*)?([^<>]*)</a>#isu', $text, $matches, PREG_SET_ORDER) ){
		foreach( $matches as $match ){
			$attach_html = '';
			$fileurl = preg_replace('#^https?\:#is', '', $match[1]);
			$filename = $match[2];

			$upload_array = wp_upload_dir();
			$filedir = preg_replace('#^https?\:#is', '', str_replace( preg_replace('#^https?\:#is', '', $upload_array['baseurl']), $upload_array['basedir'], $fileurl ) );
			$filedir = str_replace( basename($filedir), urldecode( basename($filedir) ), $filedir );

			if(file_exists($filedir)){
				if( !WPF()->perm->forum_can('va') || !WPF()->perm->usergroup_can('caa') ){
					$attach_html .= '<br/><div class="wpfa-item wpfa-file"><a class="attach_cant_view" style="cursor:pointer;"><span style="color:#666;">' . wpforo_phrase('Attachment', FALSE) . ':</span> ' . urldecode( basename($filename) ) . '</a></div>';
				}
			}

			if($attach_html){
				$attach_html .= '<br/>';
				$text = str_replace($match[0], $attach_html, $text);
			}
		}
	}

	return $text;
}

function wpforo_content_enable_do_shortcode(){
    if (wpforo_feature('content-do_shortcode')) {
        add_filter('wpforo_content_after', 'do_shortcode', 20);
    }
}
add_action('wpforo_after_init', 'wpforo_content_enable_do_shortcode');

add_filter( 'wpforo_body_text_filter', function($text){
    if( apply_filters('wpforo_allow_replace_3asterisk', true) ) $text = preg_replace('#\*{3}([^*]+?)\*{3}#', '<span style="color: red; font-weight: bold;">$1</span>', $text);
    return $text;
});

function wpforo_create_cache(){
	WPF()->cache->create();
}
add_action( 'wp_footer', 'wpforo_create_cache', 10 );

function wpforo_redirect_to_custom_lostpassword() {
    if( !wpforo_feature('resetpass-url') ) return;

    if ( 'GET' == $_SERVER['REQUEST_METHOD'] ) {
        if ( is_user_logged_in() ) {
            wp_redirect( wpforo_home_url() );
            exit;
        }

        wp_redirect( wpforo_lostpass_url() );
        exit;
    }
}
add_action('login_form_lostpassword', 'wpforo_redirect_to_custom_lostpassword');

function wpforo_redirect_to_custom_password_reset(){
    if( !wpforo_feature('resetpass-url') ) return;

    if ( 'GET' == $_SERVER['REQUEST_METHOD'] ) {
        // Verify key / login combo
        $_REQUEST['key'] = sanitize_text_field($_REQUEST['key']);
        $_REQUEST['login'] = sanitize_user($_REQUEST['login']);
        $user = check_password_reset_key( $_REQUEST['key'], $_REQUEST['login'] );
        if ( ! $user || is_wp_error( $user ) ) {
            if ( $user && $user->get_error_code() === 'expired_key' ) {
                WPF()->notice->add('The key is expired', 'error');
            } else {
                WPF()->notice->add('The key is invalid', 'error');
            }
            wp_redirect( wpforo_login_url() );
            exit;
        }

        $redirect_url = wpforo_home_url( '?foro=resetpassword&rp_key='.esc_attr( urlencode($_REQUEST['key']) ).'&rp_login='.esc_attr( urlencode($_REQUEST['login']) ) );

        wp_redirect( $redirect_url );
        exit;
    }
}
add_action( 'login_form_rp', 'wpforo_redirect_to_custom_password_reset' );
add_action( 'login_form_resetpass', 'wpforo_redirect_to_custom_password_reset' );

function wpforo_do_lostpass(){
    if( !wpforo_feature('resetpass-url') ) return;

    if( isset($_POST['user_login']) && $_POST['user_login'] ){
        $errors = retrieve_password();
        if ( is_wp_error( $errors ) ) {
            // Errors found
            $redirect_url = wpforo_lostpass_url();
            WPF()->notice->add( implode( ',', $errors->get_error_codes() ), 'error');
        } else {
            // Email sent
            $redirect_url = wpforo_login_url();
            WPF()->notice->add('Email has been sent', 'success');
        }

        wp_safe_redirect( $redirect_url );
        exit();
    }
}
add_action('login_form_lostpassword', 'wpforo_do_lostpass');

function wpforo_do_password_reset() {
    if( !wpforo_feature('resetpass-url') ) return;

    if ( 'POST' == $_SERVER['REQUEST_METHOD'] ) {
        $rp_key = sanitize_textarea_field($_REQUEST['rp_key']);
        $rp_login = sanitize_textarea_field($_REQUEST['rp_login']);

        $user = check_password_reset_key( $rp_key, $rp_login );

        if ( ! $user || is_wp_error( $user ) ) {
            if ( $user && $user->get_error_code() === 'expired_key' ) {
                WPF()->notice->add('The key is expired', 'error');
            } else {
                WPF()->notice->add('The key is invalid', 'error');
            }
            wp_redirect( wpforo_login_url() );
            exit();
        }

        if ( isset( $_POST['pass1'] ) ) {
	        $redirect_url = wpforo_home_url( '?foro=resetpassword&rp_key='.esc_attr( $rp_key ).'&rp_login='.esc_attr( $rp_login ) );
            if ( $_POST['pass1'] != $_POST['pass2'] ) {
                // Passwords don't match
                WPF()->notice->add('The password reset mismatch', 'error');
                wp_redirect( $redirect_url );
                exit();
            }

	        if( strlen($_POST['pass1']) < WPF()->member->pass_min_length || strlen($_POST['pass1']) > WPF()->member->pass_max_length ){
		        WPF()->notice->add( 'Password length must be between %d characters and %d characters.', 'error', array(WPF()->member->pass_min_length, WPF()->member->pass_max_length) );
		        wp_redirect( $redirect_url );
		        exit();
	        }

            if ( empty( $_POST['pass1'] ) ) {
                // Password is empty
                WPF()->notice->add('The password reset empty', 'error');
                wp_redirect( $redirect_url );
                exit();
            }

            // Parameter checks OK, reset password
            reset_password( $user, $_POST['pass1'] );

            $creds = array('user_login' => sanitize_user($rp_login), 'user_password' => $_POST['pass1'] );
            wp_signon($creds);

            WPF()->notice->add('The password has been changed', 'success');
            wp_redirect( wpforo_login_url() );
            exit();
        }

        WPF()->notice->add('Invalid request.', 'error');
        wp_redirect( wpforo_login_url() );
        exit();

    }
}
add_action( 'login_form_rp', 'wpforo_do_password_reset' );
add_action( 'login_form_resetpass', 'wpforo_do_password_reset' );

function wpforo_replace_retrieve_password_message( $message, $key, $user_login = '', $user_data = array() ) {
	$reset_password_url = '';
	if( preg_match(wpforo_get_wprp_url_pattern(), $message, $match) ){
		if( wpforo_feature('resetpass-url') ){
			$reset_password_url = wpforo_home_url( '?foro=resetpassword&rp_key='.esc_attr( $key ).'&rp_login='.rawurlencode( $user_login ) );
			$message = str_replace($match[0], $reset_password_url, $message);
		}else{
			$reset_password_url = $match[0];
		}
	}

	if( WPF()->sbscrb->options['overwrite_reset_password_email_message'] && $reset_password_url ){
		$message = str_replace(
			array('[user_login]', '[reset_password_url]'),
			array($user_login, $reset_password_url),
			WPF()->sbscrb->options['reset_password_email_message']
		);
		add_filter( 'wp_mail_content_type', '__wpforo_set_html_content_type', 999 );
	}

	return $message;
}
add_filter( 'retrieve_password_message', 'wpforo_replace_retrieve_password_message', 999, 4 );

function wpforo_user_field_shortcode_to_value($shortcode, $userid = NULL){
    $value = NULL;
    if( $shortcode ){
	    if( $field = preg_replace('#^[\r\n\t\s]*\[?[\r\n\t\s]*(?:fields_)?([^\[\]]+?)[\r\n\t\s]*\]?[\r\n\t\s]*$#isu', '$1', $shortcode) ){
		    if( !$userid ) $userid = WPF()->current_userid;
		    $value = wpforo_member($userid, $field);
	    }
    }
    return $value;
}

function wpforo_new_user_notification_email_admin($wp_new_user_notification_email_admin, $user, $blogname){
    if( WPF()->sbscrb->options['overwrite_new_user_notification_admin'] ){
	    $wp_new_user_notification_email_admin['subject'] = str_replace('[blogname]', '['.$blogname.']', WPF()->sbscrb->options['wp_new_user_notification_email_admin_subject']);
	    $wp_new_user_notification_email_admin['message'] = str_replace('[blogname]', $blogname, WPF()->sbscrb->options['wp_new_user_notification_email_admin_message'] );
	    add_filter( 'wp_mail_content_type', '__wpforo_set_html_content_type', 999 );
    }
    $userid = $user->ID;
    $wp_new_user_notification_email_admin['message'] = preg_replace_callback(
        '#\[[^\[\]]+?\]#isu',
        function ($match) use($userid){
            $value = wpforo_user_field_shortcode_to_value($match[0], $userid);
            if( !$value || !( is_string($value) || is_numeric($value) ) ) $value = '';
            return $value;
        },
        $wp_new_user_notification_email_admin['message']
    );

    return $wp_new_user_notification_email_admin;
}
add_filter('wp_new_user_notification_email_admin', 'wpforo_new_user_notification_email_admin', 999, 3);

function wpforo_new_user_notification_email($wp_new_user_notification_email, $user, $blogname){
    $set_password_url = '';
	if( preg_match(wpforo_get_wprp_url_pattern(), $wp_new_user_notification_email['message'], $match) ){
		if( wpforo_feature('resetpass-url') ){
			$set_password_url = wpforo_home_url( '?foro=resetpassword&rp_key='.esc_attr( $match['key'] ).'&rp_login='.rawurlencode( $user->user_login ) );
			$wp_new_user_notification_email['message'] = str_replace($match[0], $set_password_url, $wp_new_user_notification_email['message']);
		}else{
			$set_password_url = $match[0];
		}
	}

    if( WPF()->sbscrb->options['overwrite_new_user_notification'] && $set_password_url ){
        $wp_new_user_notification_email['subject'] = str_replace('[blogname]', '['.$blogname.']', WPF()->sbscrb->options['wp_new_user_notification_email_subject']);
        $wp_new_user_notification_email['message'] = str_replace(
                array('[user_login]', '[set_password_url]'),
                array($user->user_login, $set_password_url),
                WPF()->sbscrb->options['wp_new_user_notification_email_message']
        );
	    add_filter( 'wp_mail_content_type', '__wpforo_set_html_content_type', 999 );
    }

    return $wp_new_user_notification_email;
}
add_filter('wp_new_user_notification_email', 'wpforo_new_user_notification_email', 999, 3);

function wpforo_get_wprp_url_pattern(){
    return '#(?:<\s*)?https?://\S+?wp-login\.php\?action=rp&key=(?<key>[^?&=\s]+)\S+(?:\s*>)?#isu';
}

function wpforo_synch_role( $ug_role = array(), $users = array() ){
    if( !empty($ug_role) ){
        $status = array();
        WPF()->usergroup->set_ug_roles( $ug_role );
        $usergroups_roles = WPF()->usergroup->get_usergroup_role_relation();
        if( empty($users) ){
            foreach( $ug_role as $ug => $role ){
                $args = array( 'role' => $role );
                $users = get_users( $args );
                $array = WPF()->usergroup->build_users_groupid_array( $usergroups_roles, $users );
                if( wpfval($array, 'group_users') && !empty($array['group_users']) ){
                    $status = WPF()->usergroup->set_users_groupid($array['group_users']);
                }
            }
        }
        else{
            $array = WPF()->usergroup->build_users_groupid_array( $usergroups_roles, $users );
            if( wpfval($array, 'group_users') && !empty($array['group_users']) ){
                $status = WPF()->usergroup->set_users_groupid($array['group_users']);
            }
        }
        return $status;
    }
}


function wpforo_synch_roles() {

    $status = array(
        'progress' => 0,
        'error' => 0,
        'start' => 0,
        'step' => 1,
        'left' => 0,
        'total' => 0,
        'id' => 0
    );

    $wpforo_synch_roles_data = isset($_POST['wpforo_synch_roles_data']) ? $_POST['wpforo_synch_roles_data'] : '';

    if ($wpforo_synch_roles_data) {
        parse_str($wpforo_synch_roles_data, $data);
        check_ajax_referer( 'wpforo_synch_roles', 'checkthis' );
        $limit = apply_filters('wpforo_synch_roles_step_limit', 50);
        $success = false;
        $group_users = array();
        $user_prime_group = array();
        $user_second_groups = array();
        $options = get_option('wpforo-synch-roles');
        $step = isset($data['wpf-step']) ? intval($data['wpf-step']) : 1;
        $left = isset($data['wpf-left-users']) ? intval($data['wpf-left-users']) : 0;
        $id = isset($data['wpf-start-id']) ? intval($data['wpf-start-id']) : 0;
        $start = isset($data['wpf-start']) ? intval($data['wpf-start']) : 0;
        $ug_role = isset($data['wpf_synch_roles']) ? $data['wpf_synch_roles'] : array();
        if( !empty($ug_role) ){
            /////////////////////////////////////////////////////
            /// Update Roles of Usergroups in usergroups table
            WPF()->usergroup->set_ug_roles( $ug_role );
            /////////////////////////////////////////////////////
            if( !is_array($options) || $left > 0 ) {
                $args = array( 'orderby' => 'ID', 'order' => 'ASC', 'offset' => $start, 'number' => $limit );
                $users = get_users( $args );
                ////////////////////////////////////////////////////////////////////////////////////
                /// Builds associative array of Usergroup ID => Users ID array()
                $ug_users_array = WPF()->usergroup->build_users_groupid_array( $ug_role, $users );
                ////////////////////////////////////////////////////////////////////////////////////
                if( wpfval($ug_users_array, 'group_users')) $group_users = $ug_users_array['group_users'];
                if( wpfval($ug_users_array, 'user_prime_group')) $user_prime_group = $ug_users_array['user_prime_group']; /* to-do */
                if( wpfval($ug_users_array, 'user_second_groups')) $user_second_groups = $ug_users_array['user_second_groups']; /* to-do */
                if( !empty($group_users) ){
                    /////////////////////////////////////////////////////////////////
                    /// Updates users Usergroup Ids in profiles table
                    $return = WPF()->usergroup->set_users_groupid( $group_users );
                    /////////////////////////////////////////////////////////////////
                    $success = (wpfval($return, 'success')) ? $return['success'] : $success;
                    $status['error'] = (wpfval($return, 'error')) ? $return['error'] : $status['error'];
                    if( $success ){
                        end($users);
                        $key = key($users);
                        $status['id'] = $users[$key]->ID;
                        $result = count_users();
                        $status['total'] = ( wpfval( $result, 'total_users') ) ? intval($result['total_users']) : 0;
                        $status['start'] = $step * $limit;
                        $status['left'] = ( $status['total'] > $status['start'] ) ? ($status['total'] - $status['start']) : 0;
                        $status['step'] = $step + 1;
                        $progress = ( $status['total'] > $status['start'] ) ? ($status['start'] * 100) / $status['total'] : 100;
                        $status['progress'] = round($progress);
	                    if( $progress == 100 ){
	                        delete_option( 'wpforo-synch-roles' );
                        }
                        else{
                            update_option('wpforo-synch-roles', $status);
                        }
                    }
                }
                else{
                    $status['total'] = 0; $status['start'] = 0; $status['left'] = 0; $status['step'] = 1; $status['progress'] = 100;
                    delete_option( 'wpforo-synch-roles' );
                }
            }
            else{
                $result = count_users(); $status['total'] = ( wpfval( $result, 'total_users') ) ? intval($result['total_users']) : 0; $status['start'] = $step * $limit; $status['left'] = 0; $status['step'] = 1; $status['progress'] = 100;
                delete_option( 'wpforo-synch-roles' );
            }
        }
    }

    if( intval(wpfval($status, 'progress')) === 100 ){
	    WPF()->notice->add('Role-Usergroup synchronization is complete!', 'success');
    }

    wp_die(json_encode($status));
}
add_action('wp_ajax_wpforo_synch_roles', 'wpforo_synch_roles');


function wpforo_update_usergroup_on_role_change( $userid, $new_role, $old_roles = array() ){
    if( wpforo_feature('role-synch') ) {
        $user_ug_id =  WPF()->member->get_usergroup( $userid );
        $role_ug_ids = WPF()->usergroup->get_usergroups_by_role( $new_role );
        if( !empty($role_ug_ids) && is_array($role_ug_ids) ){
            if( count($role_ug_ids) > 1 ){
                $prime_ugid = array_shift($role_ug_ids);
                if( !in_array($user_ug_id, $role_ug_ids) ){
                    WPF()->member->set_usergroup( $userid, $prime_ugid );
                    WPF()->member->set_usergroups_secondary( $userid, $role_ug_ids );
                }
            }
            else{
                $groupid = current($role_ug_ids);
                if( $groupid != $user_ug_id ){
                    WPF()->member->set_usergroup( $userid, $groupid );
                }
            }
        }
    }
}
add_action( 'set_user_role', 'wpforo_update_usergroup_on_role_change', 10, 3 );
add_action( 'add_user_role', 'wpforo_update_usergroup_on_role_change', 10, 2 );

function wpforo_add_adminbar_links($wp_admin_bar) {
    if ( wpforo_current_user_is('admin') ){
        $args = array( 'id' => 'new-forum', 'title' => __('New Forum', 'wpforo'), 'href' => admin_url('admin.php?page=wpforo-forums&action=add'), 'parent' => 'new-content' );
        $wp_admin_bar->add_node($args);
        $args = array( 'id' => 'new-ugroup', 'title' => __('New User Group', 'wpforo'), 'href' => admin_url('admin.php?page=wpforo-usergroups&wpfaction=wpforo_usergroup_save_form'), 'parent' => 'new-content' );
        $wp_admin_bar->add_node($args);
        $args = array( 'id' => 'new-phrase', 'title' => __('New Phrase', 'wpforo'), 'href' => admin_url('admin.php?page=wpforo-phrases&wpfaction=wpforo_phrase_add_form'), 'parent' => 'new-content' );
        $wp_admin_bar->add_node($args);
    }

    $args = array( 'id' => 'wpforo-home', 'title' => __('Visit Forum', 'wpforo'), 'href' => wpforo_home_url(), 'parent' => 'wpf-community' );
    $wp_admin_bar->add_node($args);

    if ( wpforo_current_user_is( 'admin' ) || WPF()->perm->usergroup_can( 'mf' )  ||
         WPF()->perm->usergroup_can( 'ms' )  || WPF()->perm->usergroup_can( 'vm' )  ||
         WPF()->perm->usergroup_can( 'mp' )  || WPF()->perm->usergroup_can( 'aum' ) ||
         WPF()->perm->usergroup_can( 'vmg' ) || WPF()->perm->usergroup_can( 'mth' )
    ) {
        $args = array( 'id' => 'wpf-community', 'title' => __('Forum Dashboard', 'wpforo'), 'href' => admin_url('admin.php?page=wpforo-community') );
        $wp_admin_bar->add_node($args);
    }
    if ( WPF()->perm->usergroup_can( 'mf' ) || wpforo_current_user_is( 'admin' ) ) {
        $args = array( 'id' => 'wpf-forums', 'title' => __('Forums', 'wpforo'), 'href' => admin_url('admin.php?page=wpforo-forums'), 'parent' => 'wpf-community' );
        $wp_admin_bar->add_node($args);
        $args = array( 'id' => 'wpf-new-forum', 'title' => __('New Forum', 'wpforo'), 'href' => admin_url('admin.php?page=wpforo-forums&action=add'), 'parent' => 'wpf-forums' );
        $wp_admin_bar->add_node($args);
    }
    if ( WPF()->perm->usergroup_can( 'ms' ) || wpforo_current_user_is( 'admin' ) ) {
        $args = array( 'id' => 'wpf-settings', 'title' => __('Settings', 'wpforo'), 'href' => admin_url('admin.php?page=wpforo-settings'), 'parent' => 'wpf-community' );
        $wp_admin_bar->add_node($args);
    }
    if ( WPF()->perm->usergroup_can( 'mt' ) || wpforo_current_user_is( 'admin' ) ) {
        $args = array( 'id' => 'wpf-tools', 'title' => __('Tools', 'wpforo'), 'href' => admin_url('admin.php?page=wpforo-tools'), 'parent' => 'wpf-community' );
        $wp_admin_bar->add_node($args);
    }
    if ( WPF()->perm->usergroup_can( 'aum' ) || wpforo_current_user_is( 'admin' ) ) {
        $args = array( 'id' => 'wpf-moderation', 'title' => __('Moderation', 'wpforo'), 'href' => admin_url('admin.php?page=wpforo-moderations'), 'parent' => 'wpf-community' );
        $wp_admin_bar->add_node($args);
    }
    if ( WPF()->perm->usergroup_can( 'vm' ) || wpforo_current_user_is( 'admin' ) ) {
        $args = array( 'id' => 'wpf-members', 'title' => __('Members', 'wpforo'), 'href' => admin_url('admin.php?page=wpforo-members'), 'parent' => 'wpf-community' );
        $wp_admin_bar->add_node($args);
    }
    if ( WPF()->perm->usergroup_can( 'vmg' ) || wpforo_current_user_is( 'admin' ) ) {
        $args = array( 'id' => 'wpf-usergroups', 'title' => __('Usergroups', 'wpforo'), 'href' => admin_url('admin.php?page=wpforo-usergroups'), 'parent' => 'wpf-community' );
        $wp_admin_bar->add_node($args);
        $args = array( 'id' => 'wpf-new-ugroup', 'title' => __('New Usergroup', 'wpforo'), 'href' => admin_url('admin.php?page=wpforo-usergroups&wpfaction=wpforo_usergroup_save_form'), 'parent' => 'wpf-usergroups' );
        $wp_admin_bar->add_node($args);
    }
    if ( WPF()->perm->usergroup_can( 'mp' ) || wpforo_current_user_is( 'admin' ) ) {
        $args = array( 'id' => 'wpf-phrases', 'title' => __('Phrases', 'wpforo'), 'href' => admin_url('admin.php?page=wpforo-phrases'), 'parent' => 'wpf-community' );
        $wp_admin_bar->add_node($args);
        $args = array( 'id' => 'wpf-new-phrase', 'title' => __('New Phrase', 'wpforo'), 'href' => admin_url('admin.php?page=wpforo-phrases&wpfaction=wpforo_phrase_add_form'), 'parent' => 'wpf-phrases' );
        $wp_admin_bar->add_node($args);
    }
    if ( WPF()->perm->usergroup_can( 'mth' ) || wpforo_current_user_is( 'admin' ) ) {
        $args = array( 'id' => 'wpf-themes', 'title' => __('Themes', 'wpforo'), 'href' => admin_url('admin.php?page=wpforo-themes'), 'parent' => 'wpf-community' );
        $wp_admin_bar->add_node($args);
    }
    if ( wpforo_current_user_is('admin') ) {
        $args = array( 'id' => 'wpf-addons', 'title' => __('Addons', 'wpforo'), 'href' => admin_url('admin.php?page=wpforo-addons'), 'parent' => 'wpf-community' );
        $wp_admin_bar->add_node($args);
    }
}
add_action('admin_bar_menu', 'wpforo_add_adminbar_links', 999);

function wpforo_tag_search() {
    $s = wp_unslash( $_GET['q'] );
    if ( false !== strpos( $s, ',' ) ) {
        $s = explode( ',', $s );
        $s = $s[count( $s ) - 1];
    }
    $s = trim( $s );
    $tag_search_min_chars = apply_filters('wpforo_tag_search_min_chars', 2);
    if( wpforo_strlen($s) >= $tag_search_min_chars ){
	    $limit = intval( apply_filters('wpforo_tag_suggest_limit', 5) );
	    $results = WPF()->db->get_col("SELECT `tag` FROM `" . WPF()->tables->tags . "` WHERE `tag` LIKE '" . esc_sql( $s ) . "%' LIMIT " . $limit);
        if( !empty($results) ) echo implode("\n", $results );
    }
    wp_die();
}
add_action( 'wp_ajax_wpforo_tag_search', 'wpforo_tag_search' );
add_action( 'wp_ajax_nopriv_wpforo_tag_search', 'wpforo_tag_search' );

function wpforo_add_to_footer(){
	if( WPF()->current_object['load_tinymce'] ) wp_enqueue_editor();
}
add_action('wpforo_bottom_hook', 'wpforo_add_to_footer');

function wpforo_check_notifications(){
	$data = array( 'alerts' => 0, 'notifications' => '' );
	if( is_user_logged_in() && wpforo_feature('notifications') ){
		$data['alerts'] = count( WPF()->activity->notifications );
        if( wpfval($_POST, 'getdata') ) $data['notifications'] = WPF()->activity->notifications_list( false );
		wp_send_json_success($data);
	}
	wp_send_json_error($data);
}
add_action('wp_ajax_wpforo_notifications', 'wpforo_check_notifications');

function wpforo_can_display_recaptcha_note(){
    $d = wpforo_is_admin() ? 'recaptcha_backend_note' : 'recaptcha_note';
	return !WPF()->dissmissed[$d]
		&& current_user_can('administrator')
        && !wp_is_mobile()
        && wpforo_feature('user-register')
        && !WPF()->member->options['register_url']
        && !wpforo_is_recaptcha_configured();
}

add_action('wpforo_header_hook', 'wpforo_recaptcha_note');
function wpforo_recaptcha_note(){
	if( wpforo_can_display_recaptcha_note() ){
		?>
		<div class="wpforo-rcn-wrap">
			<div class="wpforo-rcn-body">
                <span class="wpforo-rcn-head"><i class="fas fa-user-secret"></i> <?php wpforo_phrase('Protect your forum from spam user registration!'); ?></span>
                <?php printf(
                        wpforo_phrase('wpForo has not found any protection solution against spam user registration on the forum registration form. Please %1$s and enable the %2$s antibot protection in %3$s or install other alternative %4$s to avoid registration of spam users.', false, 'native'),
                        '<a class="wpf-rcnl" href="'. admin_url('admin.php?page=wpforo-tools&tab=antispam') .'" target="_blank">'. wpforo_phrase('configure', false, 'lower') .'</a>',
                        '<a class="wpf-rcngl" href="https://developers.google.com/recaptcha" target="_blank">Google reCAPTCHA</a>',
                        '<a href="'. admin_url('admin.php?page=wpforo-tools&tab=antispam#wpf-recaptcha') .'">'. wpforo_phrase('Tools &gt; Antispam Tab', false) .'</a>',
                        '<a class="wpf-rcnwl" href="https://wordpress.org/plugins/search/spam+users/" target="_blank">'. wpforo_phrase('WordPress plugin', false) .'</a>'
                    )
                ?>
            </div>
            <div class="wpforo-rcn-footer">
                <div class="wpforo-rcn-info">
	                <?php wpforo_phrase('This notification is only visible for the website administrators. It will be automatically disabled once some antispam solution is enabled. If you don\'t use wpForo registration form or you\'re sure, that you have an antispam solution just click the [dismiss] button.'); ?>
                </div>
                <div class="wpforo-rcn-dismiss">
                    <span class="wpforo-rcn-dismiss-button" wpf-tooltip="<?php wpforo_phrase('I got it, please dismiss this message') ?>" wpf-tooltip-size="long"><?php wpforo_phrase('Dismiss') ?></span>
                </div>
            </div>
		</div>
		<?php
	}
}

//add_action('admin_notices', 'wpforo_admin_notice_recaptcha');
function wpforo_admin_notice_recaptcha(){
	if( wpforo_can_display_recaptcha_note() ){
	    wp_enqueue_script( 'wpforo-backend-js' );
        $class = 'notice notice-error is-dismissible';
		$message = __( 'IMPORTANT! The forum registration form is probably under risk of spam attacks. Please configure wpForo built-in %s antibot for registration form to avoid spam registrations. If you don\'t use the forum registration form or you are sure that your registration forms are secured, just click on (x) button to dismiss this message.', 'wpforo' );
		$message = sprintf($message, '<a href="'. admin_url('admin.php?page=wpforo-tools&tab=antispam#wpf-recaptcha') .'" target="_blank" style="text-decoration:none;">'. __('Google reCAPTCHA', 'wpforo') .'</a>');
		printf( '<div id="wpforo-admin-notice-recaptcha" class="%1$s"><p>%2$s</p></div>', $class, $message );
	}
}

function wpforo_is_recaptcha_configured(){
	return WPF()->tools_antispam['rc_wpf_reg_form']
	    && WPF()->tools_antispam['rc_site_key']
	    && WPF()->tools_antispam['rc_secret_key'];
}

add_action( 'plugins_loaded', function(){
    if( !(int)wpfval(WPF()->dissmissed, 'poll_version_is_old') && function_exists('WPF_POLL') ){
        if( version_compare(WPFORO_VERSION, '1.7.7', '>') && version_compare(WPFOROPOLL_VERSION, '1.0.5', '<=') ){
	        remove_action('widgets_init', array(WPF_POLL(), 'init_widgets'));
	        remove_action('wpforo_before_init', array(WPF_POLL(), 'init'));

	        WPF()->dissmissed['poll_version_is_old'] = 0;
	        update_option('wpforo_dissmissed', WPF()->dissmissed);
        } else {
	        WPF()->dissmissed['poll_version_is_old'] = 1;
	        update_option('wpforo_dissmissed', WPF()->dissmissed);
        }
	    wpforo_clean_cache('option');
    }
});

add_action('admin_notices', function(){
	if( wpfkey(WPF()->dissmissed, 'poll_version_is_old') && !(int) WPF()->dissmissed['poll_version_is_old'] ){
		$class = 'notice notice-error';
		$message = '<div style="font-size: 16px; padding: 10px 0;"><strong>'. __( 'wpForo Polls addon is disabled!', 'wpforo' ) . '</strong><p style="font-size:15px; margin-bottom:0px;">' . __(' Your addon version is not compatible with the current version of wpForo. Please update the addon or downgrade wpForo to 1.7.7', 'wpforo') . '</p></div>';
		$message .= ' <a href="'. admin_url( wp_nonce_url('admin.php?page=wpforo-community&wpfaction=wpforo_dissmiss_poll_version_is_old', 'wpforo-dissmiss-poll-version-is-old') ) .'">['. __('dismiss', 'wpforo') .']</a>';
		printf( '<div class="%1$s"><p>%2$s</p></div>', $class, $message );
	}
});

add_filter('wpforo_profile_top_bar', function($menuhtml, $userid){
	$userid = intval($userid);
    if( $userid && current_user_can('administrator') ){
	    $menuhtml .= sprintf( '<select id="wpf-profile-action"><option value="0">-- '. wpforo_phrase('Dashboard Actions', false) .' --</option>>%1$s%2$s</select>',
		    '<option value="'. admin_url( "user-edit.php?user_id=" . $userid ) .'">'. wpforo_phrase('Edit in Dashboard', false) .'</option>',
		    $userid !== WPF()->current_userid ? '<option value="'. admin_url( wp_nonce_url( "users.php?action=delete&user=" . $userid, 'bulk-users' ) ) .'">'. wpforo_phrase('Delete in Dashboard', false) .'</option>' : ''
	    );
    }
    return $menuhtml;
}, 10, 2);



add_action( 'update_option', function( $option ){
    if( $option &&
        ( strpos($option, 'wpforo') !== false
            || strpos($option, 'wpftcf') !== false
                || strpos($option, 'wpfucf') !== false )
    ){
        $cache = get_option('wpforo_features');
        if( wpfval($cache, 'option_cache') ){
            wpforo_clean_folder( WPFORO_CACHE_DIR . '/item/option/' );
        }
    }
});