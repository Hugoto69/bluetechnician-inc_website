<?php
	// Exit if accessed directly
	if( !defined( 'ABSPATH' ) ) exit;
 

register_nav_menus( array(
	'wpforo-menu' => esc_html__( 'wpForo Menu', 'wpforo' ),
) );


function wpforo_login_url(){
	if(WPF()->member->options['login_url']){
		$wp_login_url = trim(get_bloginfo('url') , '/') . '/' . ltrim(WPF()->member->options['login_url'] , '/');
	}else{
		$wp_login_url = wpforo_home_url('?foro=signin&redirect_to=' . urlencode( preg_replace( '#/?\?.*$#isu', '', wpforo_get_request_uri() ) ) );
	}
	$wp_login_url = apply_filters('wpforo_login_url', $wp_login_url);
	return esc_url_raw($wp_login_url);
}


function wpforo_register_url(){
	if(WPF()->member->options['register_url']){
		$wp_register_url = trim(get_bloginfo('url') , '/') . '/' . ltrim(WPF()->member->options['register_url'] , '/');
	}else{
		$wp_register_url = wpforo_home_url('?foro=signup&redirect_to=' . urlencode( preg_replace( '#/?\?.*$#isu', '', wpforo_get_request_uri() ) ));
	}
	$wp_register_url = apply_filters('wpforo_register_url', $wp_register_url);
	return esc_url_raw($wp_register_url);
}


function wpforo_lostpass_url(){
	if( WPF()->member->options['lost_password_url'] ){
		$wp_lostpass_url = trim(get_bloginfo('url') , '/') . '/' . ltrim(WPF()->member->options['lost_password_url'] , '/');
	}
	else{
		if( wpforo_feature('resetpass-url') ){
			$wp_lostpass_url = wpforo_home_url( '?foro=lostpassword' );
		}else{
			$wp_lostpass_url = wp_lostpassword_url( wpforo_get_request_uri() );
		}
	}
	$wp_lostpass_url = apply_filters('wpforo_lostpass_url', $wp_lostpass_url);
	return esc_url($wp_lostpass_url);
}


function wpforo_menu_filter( $items, $menu ) {
	if ( !wpforo_is_admin() ) {
		foreach ( $items as $key => $item ) {
			if(isset($item->url)){
				if( strpos($item->url, '%wpforo-') !== FALSE ){
					$shortcode = trim(str_replace(array('https://', 'http://', '/', '%'), '', $item->url));
					if(isset(WPF()->menu) && isset(WPF()->menu[$shortcode])){
						if(isset(WPF()->menu[$shortcode]['href'])) $item->url = WPF()->menu[$shortcode]['href'];
						if( wpfval(WPF()->menu[$shortcode], 'is_active') || (isset(WPF()->menu[$shortcode]['attr']) && strpos(WPF()->menu[$shortcode]['attr'], 'wpforo-active') !== FALSE) ) $item->classes[] = 'wpforo-active';
					}
					else{
						unset($items[$key]);
					}	
				}
			}
		}
	}
    return $items;
}
add_filter( 'wp_get_nav_menu_items', 'wpforo_menu_filter', 1, 2 );

function wpforo_menu_nofollow_items($item_output, $item, $depth, $args) {
	//if( isset($item->url) && strpos($item->url, '?foro') !== FALSE ) {
		//$item_output = str_replace('<a ', '<a rel="nofollow" ', $item_output);
	//}
	return $item_output;
}
add_filter('walker_nav_menu_start_el', 'wpforo_menu_nofollow_items', 1, 4);

function wpforo_profile_plugin_menu( $userid = 0 ){
	$menu_html = '';
	
    $forum_profile = false;
	if($url = wpforo_has_shop_plugin($userid)){
        $forum_profile = true;
		$menu_html .= '<div id="wpf-pp-shop-menu" class="wpf-pp-menu">
                <a class="wpf-pp-menu-item" href="' . esc_url($url) . '">
                    <i class="fas fa-shopping-cart" title="'.wpforo_phrase('Shop Account', false).'"></i> <span>'.wpforo_phrase('Shop Account', false).'</span>
                </a>
			</div>';
	}
	if($url = wpforo_has_profile_plugin($userid)){
        $forum_profile = true;
        $menu_html .= '<div id="wpf-pp-site-menu" class="wpf-pp-menu">
            <a class="wpf-pp-menu-item" href="' . esc_url($url) . '">
                <i class="fas fa-user" title="'.wpforo_phrase('Site Profile', false).'"></i> <span>'.wpforo_phrase('Site Profile', false).'</span>
            </a>
        </div>';
	}
	if( $forum_profile ) {
        $menu_html .= '<div id="wpf-pp-forum-menu" class="wpf-pp-menu">
            <div class="wpf-pp-menu-item">
                <i class="fas fa-comments" title="' . wpforo_phrase('Forum Profile', false) . '"></i> <span>' . wpforo_phrase('Forum Profile', false) . '</span>
            </div>
        </div>';
        $menu_html = apply_filters( 'wpforo_profile_plugin_menu_filter', $menu_html, $userid );
    }

	$menu_html = apply_filters( 'wpforo_profile_top_bar', $menu_html, $userid );
	if($menu_html) echo '<div class="wpf-profile-plugin-menu">' . $menu_html . '<div class="wpf-clear"></div></div>';
}
add_action( 'wpforo_profile_plugin_menu_action', 'wpforo_profile_plugin_menu', 1 );

class wpforo_menu_walker extends Walker_Nav_Menu {
	public function start_lvl( &$output, $depth = 0, $args = array() ) {
		$indent = str_repeat("\t", $depth);
		$output .= "\n$indent<ul class=\"sub-menu\">\n";
	}
	public function end_lvl( &$output, $depth = 0, $args = array() ) {
		$indent = str_repeat("\t", $depth);
		$output .= "$indent</ul>\n";
	}
	public function start_el( &$output, $item, $depth = 0, $args = array(), $id = 0 ) {
		$indent = ( $depth ) ? str_repeat( "\t", $depth ) : '';
		$classes = empty( $item->classes ) ? array() : (array) $item->classes;
		$classes[] = 'menu-item-' . $item->ID;
		$args = apply_filters( 'wpforo_nav_menu_item_args', $args, $item, $depth );
		$class_names = implode( ' ', apply_filters( 'wpforo_nav_menu_css_class', array_filter( $classes ), $item, $args, $depth ) );
		$class_names = $class_names ? ' class="' . esc_attr( $class_names ) . '"' : '';
		$id = apply_filters( 'wpforo_nav_menu_item_id', 'menu-item-'. $item->ID, $item, $args, $depth );
		$id = $id ? ' id="' . esc_attr( $id ) . '"' : '';
		$output .= $indent . '<li' . $id . $class_names .'>';
		$atts = array();
		$atts['title']  = ! empty( $item->attr_title ) ? $item->attr_title : '';
		$atts['target'] = ! empty( $item->target )     ? $item->target     : '';
		$atts['rel']    = ! empty( $item->xfn )        ? $item->xfn        : '';
		$atts['href']   = ! empty( $item->url )        ? $item->url        : '';
		$atts = apply_filters( 'wpforo_nav_menu_link_attributes', $atts, $item, $args, $depth );
		$attributes = '';
		foreach ( $atts as $attr => $value ) {
			if ( ! empty( $value ) ) {
				$value = ( 'href' === $attr ) ? esc_url( $value ) : esc_attr( $value );
				$attributes .= ' ' . $attr . '="' . $value . '"';
			}
		}
		$title = apply_filters( 'wpforo_the_title', $item->title, $item->ID );
		$title = apply_filters( 'wpforo_nav_menu_item_title', $title, $item, $args, $depth );
		$item_output = $args->before;
		$item_output .= '<a'. $attributes .'>';
		$item_output .= $args->link_before . $title . $args->link_after;
		$item_output .= '</a>';
		$item_output .= $args->after;
		$output .= apply_filters( 'wpforo_walker_nav_menu_start_el', $item_output, $item, $depth, $args );
	}
	public function end_el( &$output, $item, $depth = 0, $args = array() ) {
		$output .= "</li>";
	}
}

function wpforo_widgets_init() {
	register_sidebar(array(
		'name' => __('wpForo Sidebar', 'wpforo'),
		'description' => __("NOTE: If you're going to add widgets in this sidebar, please use 'Full Width' template for wpForo index page to avoid sidebar duplication.", 'wpforo'),
		'id' => 'forum-sidebar',
		'before_widget' => '<aside id="%1$s" class="footer-widget-col %2$s clearfix">',
		'after_widget' => '</aside>',
		'before_title' => '<h3 class="widget-title">',
		'after_title' => '</h3>',
	));
}

add_action('widgets_init', 'wpforo_widgets_init', 11);

class wpForo_Widget_profile extends WP_Widget {
	function __construct() {
		parent::__construct(
			'wpForo_Widget_profile', // Base ID
			'wpForo User Profile & Notifications', // Name
			array( 'description' => 'wpForo profile data and notifications' ) // Args
		);
		$this->init_local_vars();
	}
	private function init_local_vars(){
		$this->default_instance = array(
			'title' => __('My Profile', 'wpforo'),
			'title_guest' => __('Join Us!', 'wpforo'),
			'hide_avatar' => false,
			'hide_name' => false,
			'hide_notification' => false,
			'hide_data' => false,
			'hide_buttons' => false,
			'hide_for_guests' => false
		);
	}
	public function widget( $args, $instance ) {
	    $display_widget = ( !is_user_logged_in() ) ? ( wpfval($instance, 'hide_for_guests') ? false : true ) : true;
		if ( $display_widget ) {
		    $class = ( isset(WPF()->tpl->options['style']) ) ? 'wpf-' . WPF()->tpl->options['style'] : '';
			echo $args['before_widget'];
			echo '<div id="wpf-widget-profile" class="wpforo-widget-wrap ' . esc_attr($class) . '">';
			if ( wpfval($instance, 'title') && is_user_logged_in() ) {
				echo $args['before_title'] . apply_filters( 'widget_title', $instance['title'] ). $args['after_title'];
			}
			elseif ( !is_user_logged_in() ) {
			    $title_guest = wpfval($instance, 'title_guest') ? wpfval($instance, 'title_guest') : apply_filters( 'wpforo_profile_widget_guest_title', __('Join Us!', 'wpforo'));
				echo $args['before_title'] . apply_filters( 'widget_title', $title_guest ). $args['after_title'];
			}
			echo '<div class="wpforo-widget-content">';
			$member = WPF()->current_user;
			?>
            <div class="wpf-prof-wrap">
				<?php if( is_user_logged_in() ):  wp_enqueue_script('wpforo-widgets-js'); ?>
                    <div class="wpf-prof-header">
						<?php if( !wpfval($instance, 'hide_avatar') ): ?>
                            <div class="wpf-prof-avatar">
								<?php echo wpforo_user_avatar( $member['userid'], 80 ); ?>
                            </div>
						<?php endif; ?>
						<?php if( !wpfval($instance, 'hide_name') ): ?>
                            <div class="wpf-prof-info">
                                <div class="wpf-prof-name">
									<?php WPF()->member->show_online_indicator( $member['userid'] ) ?>
									<?php echo wpfval($member, 'display_name') ? esc_html($member['display_name']) : esc_html(urldecode($member['nicename'])) ?>
									<?php wpforo_member_nicename($member, '@'); ?>
                                </div>
                            </div>
						<?php endif; ?>
						<?php if( !wpfval($instance, 'hide_notification') && wpforo_feature('notifications') ): ?>
                            <div class="wpf-prof-alerts">
								<?php WPF()->activity->bell('wpf-widget-alerts'); ?>
                            </div>
						<?php endif; ?>
                    </div>
					<?php if( !wpfval($instance, 'hide_notification') && wpforo_feature('notifications') ): ?>
                        <div class="wpf-prof-notifications" style="flex-basis: 100%;">
							<?php wpforo_notifications() ?>
                        </div>
					<?php endif; ?>
					<?php if( !wpfval($instance,'hide_data') ): ?>
                        <div class="wpf-prof-content">
							<?php do_action('wpforo_wiget_profile_content_before', $member ); ?>
                            <div class="wpf-prof-data">
                                <div class="wpf-prof-rating">
                                    <?php echo '<span class="wpf-member-title wpfrt">' . esc_html( wpfval($member, 'stat', 'title') ) . '</span>' ?>
									<?php wpforo_member_badge($member); ?>
                                </div>
								<?php wpforo_member_title($member, true, '', '', array('rating-title')); ?>
                            </div>
							<?php do_action('wpforo_wiget_profile_content_after', $member ); ?>
                        </div>
					<?php endif; ?>
				<?php endif; ?>
                <div class="wpf-prof-footer">
					<?php do_action('wpforo_wiget_profile_footer_before', $member ); ?>
					<?php if( is_user_logged_in() ): ?>
						<?php if( !wpfval($instance, 'hide_buttons') ): ?>
                            <div class="wpf-prof-buttons">
								<?php WPF()->tpl->member_buttons($member) ?>
                                <?php if( !wpforo_is_bot() ) : ?>
                                    <a href="<?php echo wpforo_home_url( '?foro=logout' ); ?>" class="wpf-logout" title="<?php wpforo_phrase('Logout') ?>"><i class="fas fa-sign-out-alt"></i></a>
                                <?php endif ?>
                            </div>
						<?php endif; ?>
					<?php elseif( !wpforo_is_bot() ): ?>
                        <div class="wpf-prof-loginout">
                            <a href="<?php echo wpforo_login_url(); ?>" class="wpf-button"><i class="fas fa-sign-in-alt"></i> <?php wpforo_phrase('Login') ?></a> &nbsp;
                            <a href="<?php echo wpforo_register_url(); ?>" class="wpf-button"><i class="fas fa-user-plus"></i> <?php wpforo_phrase('Register') ?></a>
                        </div>
					<?php endif; ?>
					<?php do_action('wpforo_wiget_profile_footer_after', $member ); ?>
                </div>
            </div>
			<?php
			echo '</div></div>';
			echo $args['after_widget'];
        }
	}
	public function form( $instance ) {
		$title = isset( $instance['title'] ) ? $instance['title'] : __('My Profile', 'wpforo');
		$title_guest = isset( $instance['title_guest'] ) ? $instance['title_guest'] : __('Join Us!', 'wpforo');
		$hide_avatar = isset( $instance['hide_avatar'] ) ? (bool) $instance['hide_avatar'] : false;
		$hide_name = isset( $instance['hide_name'] ) ? (bool) $instance['hide_name'] : false;
		$hide_notification = isset( $instance['hide_notification'] ) ? (bool) $instance['hide_notification'] : false;
		$hide_data = isset( $instance['hide_data'] ) ? (bool) $instance['hide_data'] : false;
		$hide_buttons = isset( $instance['hide_buttons'] ) ? (bool) $instance['hide_buttons'] : false;
		$hide_for_guests = isset( $instance['hide_for_guests'] ) ? (bool) $instance['hide_for_guests'] : false;
		?>
        <p>
            <label><?php _e('Title for Users', 'wpforo'); ?>:</label>
            <input class="widefat" name="<?php echo esc_attr($this->get_field_name( 'title' )); ?>" type="text" value="<?php echo esc_attr( $title ); ?>">
        </p>
        <p>
            <label><?php _e('Title for Guests', 'wpforo'); ?>:</label>
            <input class="widefat" name="<?php echo esc_attr($this->get_field_name( 'title_guest' )); ?>" type="text" value="<?php echo esc_attr( $title_guest ); ?>">
        </p>
        <p>
            <label for="<?php echo $this->get_field_id('hide_avatar') ?>"><?php _e('Hide avatar', 'wpforo'); ?>&nbsp;</label>
            <input id="<?php echo $this->get_field_id('hide_avatar') ?>" class="wpf_wdg_hide_avatar" name="<?php echo esc_attr( $this->get_field_name( 'hide_avatar' ) ); ?>" <?php checked( $hide_avatar ); ?> type="checkbox">
        </p>
        <p>
            <label for="<?php echo $this->get_field_id('hide_name') ?>"><?php _e('Hide user name', 'wpforo'); ?>&nbsp;</label>
            <input id="<?php echo $this->get_field_id('hide_name') ?>" class="wpf_wdg_hide_name" name="<?php echo esc_attr( $this->get_field_name( 'hide_name' ) ); ?>" <?php checked( $hide_name ); ?> type="checkbox">
        </p>
        <p>
            <label for="<?php echo $this->get_field_id('hide_notification') ?>"><?php _e('Hide notification bell', 'wpforo'); ?>&nbsp;</label>
            <input id="<?php echo $this->get_field_id('hide_notification') ?>" class="wpf_wdg_hide_notification" name="<?php echo esc_attr( $this->get_field_name( 'hide_notification' ) ); ?>" <?php checked( $hide_notification ); ?> type="checkbox">
        </p>
        <p>
            <label for="<?php echo $this->get_field_id('hide_data') ?>"><?php _e('Hide user data', 'wpforo'); ?>&nbsp;</label>
            <input id="<?php echo $this->get_field_id('hide_data') ?>" class="wpf_wdg_hide_data" name="<?php echo esc_attr( $this->get_field_name( 'hide_data' ) ); ?>" <?php checked( $hide_data ); ?> type="checkbox">
        </p>
        <p>
            <label for="<?php echo $this->get_field_id('hide_buttons') ?>"><?php _e('Hide buttons', 'wpforo'); ?>&nbsp;</label>
            <input id="<?php echo $this->get_field_id('hide_buttons') ?>" class="wpf_wdg_hide_buttons" name="<?php echo esc_attr( $this->get_field_name( 'hide_buttons' ) ); ?>" <?php checked( $hide_buttons ); ?> type="checkbox">
        </p>
        <p>
            <label for="<?php echo $this->get_field_id('hide_for_guests') ?>"><?php _e('Hide this widget for guests', 'wpforo'); ?>&nbsp;</label>
            <input id="<?php echo $this->get_field_id('hide_for_guests') ?>" class="wpf_wdg_hide_for_guests" name="<?php echo esc_attr( $this->get_field_name( 'hide_for_guests' ) ); ?>" <?php checked( $hide_for_guests ); ?> type="checkbox">
        </p>
		<?php
	}
	public function update( $new_instance, $old_instance ) {
		$instance = array();
		$instance['title'] = ( ! empty( $new_instance['title'] ) ) ? strip_tags( $new_instance['title'] ) : '';
		$instance['title_guest'] = ( ! empty( $new_instance['title_guest'] ) ) ? strip_tags( $new_instance['title_guest'] ) : '';
		$instance['hide_avatar'] = isset( $new_instance['hide_avatar'] ) ? (bool) $new_instance['hide_avatar'] : $this->default_instance['hide_avatar'];
		$instance['hide_name'] = isset( $new_instance['hide_name'] ) ? (bool) $new_instance['hide_name'] : $this->default_instance['hide_name'];
		$instance['hide_notification'] = isset( $new_instance['hide_notification'] ) ? (bool) $new_instance['hide_notification'] : $this->default_instance['hide_notification'];
		$instance['hide_data'] = isset( $new_instance['hide_data'] ) ? (bool) $new_instance['hide_data'] : $this->default_instance['hide_data'];
		$instance['hide_buttons'] = isset( $new_instance['hide_buttons'] ) ? (bool) $new_instance['hide_buttons'] : $this->default_instance['hide_buttons'];
		$instance['hide_for_guests'] = isset( $new_instance['hide_for_guests'] ) ? (bool) $new_instance['hide_for_guests'] : $this->default_instance['hide_for_guests'];
		return $instance;
	}
} // widget user profile

class wpForo_Widget_search extends WP_Widget {
	function __construct() {
		parent::__construct(
			'wpForo_Widget_search', // Base ID
			'wpForo Search',        // Name
			array( 'description' => 'wpForo search form' ) // Args
		);
	}
	public function widget( $args, $instance ) {
		//wp_enqueue_script('wpforo-widgets-js');

		echo $args['before_widget']; //This is a HTML content//
		echo '<div id="wpf-widget-search" class="wpforo-widget-wrap">';
		if ( ! empty( $instance['title'] ) ) {
			echo $args['before_title'] . apply_filters( 'widget_title', $instance['title'] ). $args['after_title']; //This is a HTML content//
		}
		echo '<div class="wpforo-widget-content">';
		?>
        <form action="<?php echo wpforo_home_url() ?>" method="get">
        	<?php wpforo_make_hidden_fields_from_url( wpforo_home_url() ) ?>
            <input type="text" placeholder="<?php wpforo_phrase('Search...') ?>" name="wpfs" class="wpfw-70" value="<?php echo isset($_GET['wpfs']) ? esc_attr(sanitize_text_field($_GET['wpfs'])) : '' ?>" ><input type="submit" class="wpfw-20" value="&raquo;">
        </form>
		<?php
		echo '</div></div>';
		echo $args['after_widget']; //This is a HTML content//
	}
	public function form( $instance ) {
		$title = isset( $instance['title'] ) ? $instance['title'] : 'Forum Search';
		?>
		<p>
			<label><?php _e('Title', 'wpforo'); ?>:</label> 
			<input class="widefat" name="<?php echo esc_attr($this->get_field_name( 'title' )); ?>" type="text" value="<?php echo esc_attr( $title ); ?>">
		</p>
		<?php 
	}
	public function update( $new_instance, $old_instance ) {
		$instance = array();
		$instance['title'] = ( ! empty( $new_instance['title'] ) ) ? strip_tags( $new_instance['title'] ) : '';
		return $instance;
	}
} // widget wpforo search

class wpForo_Widget_login_form extends WP_Widget {
	function __construct() {
		parent::__construct(
			'wpForo_Widget_login_form', // Base ID
			'wpForo Login Form',        // Name
			array( 'description' => 'wpForo login form' ) // Args
		);
	}
	public function widget( $args, $instance ) {
		echo $args['before_widget']; //This is a HTML content//
		echo '<div id="wpf-widget-login" class="wpforo-widget-wrap">';
		if ( ! empty( $instance['title'] ) ) {
			echo $args['before_title'] . apply_filters( 'widget_title', $instance['title'] ). $args['after_title']; //This is a HTML content//
		}
		echo '<div class="wpforo-widget-content">';
		?>

		<?php
		echo '</div></div>';
		echo $args['after_widget'];
	}
	public function form( $instance ) {
		$title = isset( $instance['title'] ) ? $instance['title'] : 'Account';
		?>
		<p>
			<label><?php _e('Title', 'wpforo'); ?>:</label> 
			<input class="widefat" name="<?php echo esc_attr($this->get_field_name( 'title' )); ?>" type="text" value="<?php echo esc_attr( $title ); ?>">
		</p>
		<?php 
	}
	public function update( $new_instance, $old_instance ) {
		$instance = array();
		$instance['title'] = ( ! empty( $new_instance['title'] ) ) ? strip_tags( $new_instance['title'] ) : '';
		return $instance;
	}
} // widget wpforo login

class wpForo_Widget_online_members extends WP_Widget {
	function __construct() {
		parent::__construct(
			'wpForo_Widget_online_members', // Base ID
			'wpForo Online Members',        // Name
			array( 'description' => 'Online members.' ) // Args
		);
	}
	public function widget( $args, $instance ) {
//		wp_enqueue_script('wpforo-widgets-js');

		echo $args['before_widget']; //This is a HTML content//
		echo '<div id="wpf-widget-online-users" class="wpforo-widget-wrap">';
		if ( ! empty( $instance['title'] ) ) {
			echo $args['before_title'] . apply_filters( 'widget_title', $instance['title'] ). $args['after_title'];
		}
        $groupids = ( !empty($instance['groupids']) ? array_filter( wpforo_parse_args( json_decode($instance['groupids'], true) ) ) : WPF()->usergroup->get_visible_usergroup_ids() );
		// widget content from front end
		$online_members = WPF()->member->get_online_members($instance['count'], $groupids);
		echo '<div class="wpforo-widget-content">';
		if(!empty($online_members)){
			echo '<ul>
					 <li>
						<div class="wpforo-list-item">';
			foreach( $online_members as $member ){
				if( $instance['display_avatar'] ): ?>
						<a href="<?php echo esc_url(WPF()->member->get_profile_url( $member['ID'] )) ?>" class="onlineavatar">
							<?php echo WPF()->member->get_avatar( $member['ID'], 'style="width:95%;" class="avatar" title="'.esc_attr($member['display_name']).'"'); ?>
						</a>
					<?php else: ?>
						<a href="<?php echo esc_url(WPF()->member->get_profile_url( $member['ID'] )) ?>" class="onlineuser"><?php echo esc_html($member['display_name']) ?></a>
					<?php endif; ?>
				<?php
			}
			echo '<div class="wpf-clear"></div>
							</div>
						</li>
					</ul>
				</div>';
		}
		else{
			echo '<p class="wpf-widget-note">&nbsp;'.wpforo_phrase('No online members at the moment', false).'</p>';
		}
		echo '</div>';
		echo $args['after_widget'];//This is a HTML content//
	}
	public function form( $instance ) {
		$title = ! empty( $instance['title'] ) ? $instance['title'] : 'Online Members';
		$count = ! empty( $instance['count'] ) ? $instance['count'] : '15';
		$display_avatar = isset( $instance['display_avatar'] ) ? (bool) $instance['display_avatar'] : false;
		$groupids = ( !empty($instance['groupids']) ? array_filter( wpforo_parse_args( json_decode($instance['groupids'], true) ) ) : WPF()->usergroup->get_visible_usergroup_ids() );
		?>
		<p>
			<label><?php _e('Title', 'wpforo'); ?>:</label> 
			<input class="widefat" name="<?php echo esc_attr($this->get_field_name( 'title' )); ?>" type="text" value="<?php echo esc_attr( $title ); ?>">
		</p>
        <p>
			<label><?php _e('User Groups', 'wpforo'); ?></label>&nbsp;
            <select name="<?php echo esc_attr($this->get_field_name( 'groupids' )); ?>[]" multiple>
                <?php WPF()->usergroup->show_selectbox($groupids) ?>
            </select>
		</p>
        <p>
			<label><?php _e('Number of Items', 'wpforo'); ?></label>&nbsp;
			<input type="number" min="1" style="width: 53px;" name="<?php echo esc_attr($this->get_field_name( 'count' )); ?>" value="<?php echo esc_attr( $count ) ; ?>">
		</p>
        <p>
			<label>
            	<input<?php checked( $display_avatar ); ?> type="checkbox" value="1" name="<?php echo esc_attr( $this->get_field_name( 'display_avatar' )); ?>"/>
			 	<?php _e('Display Avatars', 'wpforo'); ?>
            </label>
		</p>
		<?php 
	}
	public function update( $new_instance, $old_instance ) {
		$instance = array();
		$instance['title'] = ( ! empty( $new_instance['title'] ) ) ? strip_tags( $new_instance['title'] ) : '';
		$instance['count'] = ( ! empty( $new_instance['count'] ) ) ? intval( $new_instance['count'] ) : '';
		$instance['display_avatar'] = isset( $new_instance['display_avatar'] ) ? (bool) $new_instance['display_avatar'] : false;
		$instance['groupids'] = ( !empty( $new_instance['groupids'] ) ? json_encode( array_filter( wpforo_parse_args($new_instance['groupids']) ) ) : json_encode( WPF()->usergroup->get_visible_usergroup_ids() ) );
		return $instance;
	}
} // widget online members

class wpForo_Widget_recent_topics extends WP_Widget {
	private $default_instance = array();
	private $orderby_fields = array();
	private $order_fields = array();
	function __construct() {
		parent::__construct(
			'wpForo_Widget_recent_topics', // Base ID
			'wpForo Recent Topics',        // Name
			array( 'description' => 'Your forum\'s recent topics.' ) // Args
		);
		$this->init_local_vars();
	}
	private function init_local_vars(){
		$this->default_instance = array(
			'title'                  => 'Recent Topics',
			'forumids'               => array(),
			'orderby'                => 'created',
			'order'                  => 'DESC',
			'count'                  => 9,
			'display_avatar'         => false,
			'forumids_filter'        => false,
			'current_forumid_filter' => false,
			'goto_unread'            => false
		);
        $this->orderby_fields = array(
            'created'   => __('Created Date',   'wpforo'),
            'modified'  => __('Modified Date',  'wpforo'),
            'posts'     => __('Posts Count',    'wpforo'),
            'views'     => __('Views Count',    'wpforo')
        );
        $this->order_fields = array(
            'DESC'  => __('DESC',   'wpforo'),
            'ASC'   => __('ASC',    'wpforo')
        );
    }
	public function widget( $args, $instance ) {
//		wp_enqueue_script('wpforo-widgets-js');

		$instance = wpforo_parse_args($instance, $this->default_instance);
		if( $instance['current_forumid_filter'] && $current_forumid = wpfval( WPF()->current_object, 'forumid' ) ){
			$instance['forumids'] = (array) $current_forumid;
        }
		echo $args['before_widget'];//This is a HTML content//
		echo '<div id="wpf-widget-recent-replies" class="wpforo-widget-wrap">';
		if ( ! empty( $instance['title'] ) ) {
			echo $args['before_title'] . apply_filters( 'widget_title', $instance['title'] ). $args['after_title'];//This is a HTML content//
		}
		// widget content from front end
		$private = (!is_user_logged_in() || !WPF()->perm->usergroup_can('aum')) ? 0 : NULL;
		$status = (!is_user_logged_in() || !WPF()->perm->usergroup_can('aum')) ? 0 : NULL;
		$topic_args = array(
			'forumids'  => ( $instance['forumids'] ? $instance['forumids'] : $this->default_instance['forumids'] ),
			'orderby'   => ( key_exists( $instance['orderby'], $this->orderby_fields ) ? $instance['orderby'] : $this->default_instance['orderby'] ),
			'order'     => ( key_exists( $instance['order'], $this->order_fields ) ? $instance['order'] : $this->default_instance['order'] ),
			'row_count' => ( ($count = intval($instance['count'])) ? $count : $this->default_instance['count'] ),
			'private'   => $private,
			'status'    => $status
		);
		$topics = WPF()->topic->get_topics_filtered($topic_args);
		$ug_can_va = WPF()->perm->usergroup_can('va');
		$is_avatar = wpforo_feature('avatars');
		echo '<div class="wpforo-widget-content"><ul>';
		foreach( $topics as $topic ){
			$topic_url = wpforo_topic($topic['topicid'], 'url');
			$member = wpforo_member($topic);
			?>
            <li>
                <div class="wpforo-list-item">
                    <?php if( $instance['display_avatar'] ): ?>
                    	<?php if( $ug_can_va && $is_avatar ): ?>
                            <div class="wpforo-list-item-left">
                                <?php echo WPF()->member->avatar($member); ?>
                            </div>
                    	<?php endif; ?>
					<?php endif; ?>
                    <div class="wpforo-list-item-right" <?php if( !$instance['display_avatar'] ): ?> style="width:100%"<?php endif; ?>>
                        <p class="posttitle">
                            <?php if( wpfval($instance, 'goto_unread') ): ?>
                                <?php wpforo_topic_title($topic, $topic_url, '{p}{au}{t}{/a}') ?>
                                <?php if( $topic['topicid'] != wpfval(WPF()->current_object, 'topicid') ): ?>
                                    <?php wpforo_unread_button($topic['topicid'], $topic_url); ?>
                                <?php endif; ?>
                            <?php else: ?>
                                <?php wpforo_topic_title($topic, $topic_url, '{p}{a}{t}{/a}') ?>
                            <?php endif; ?>
                        </p>
                        <p class="postuser"><?php wpforo_phrase('by') ?> <?php wpforo_member_link($member) ?>, <span style="white-space:nowrap;"><?php esc_html(wpforo_date($topic['created'])) ?></span></p>
                    </div>
                    <div class="wpf-clear"></div>
                </div>
            </li>
            <?php
		}
		echo '</ul></div>';
		echo '</div>';
		echo $args['after_widget'];//This is a HTML content//
	}
	public function form( $instance ) {
		$instance       = wpforo_parse_args( $instance, $this->default_instance );
		$title          = (string) $instance['title'];
		$selected       = array_unique( array_filter( array_map( 'intval', (array) $instance['forumids'] ) ) );
		$orderby        = (string) $instance['orderby'];
		$order          = (string) $instance['order'];
		$count          = (int) $instance['count'];
		$display_avatar = (bool) $instance['display_avatar'];
		$forumids_filter = (bool) $instance['forumids_filter'];
		$current_forumid_filter = (bool) $instance['current_forumid_filter'];
		$goto_unread =  (bool) $instance['goto_unread'];
		?>
        <style type="text/css">
            select.wpf_wdg_forumids {display: none;width: 100%;min-height: 170px;}
            input.wpf_wdg_forumids_filter:checked ~ select.wpf_wdg_forumids {display: block;}
        </style>
        <p>
			<label for="<?php echo $this->get_field_id('title') ?>"><?php _e('Title', 'wpforo'); ?>:</label>
			<input id="<?php echo $this->get_field_id('title') ?>" class="widefat" name="<?php echo esc_attr($this->get_field_name( 'title' )); ?>" type="text" value="<?php echo esc_attr( $title ); ?>">
		</p>
        <p>
            <label for="<?php echo $this->get_field_id('forumids_filter') ?>"><?php _e('Filter by forums', 'wpforo'); ?>:</label>
            <input id="<?php echo $this->get_field_id('forumids_filter') ?>" class="wpf_wdg_forumids_filter" name="<?php echo esc_attr( $this->get_field_name( 'forumids_filter' ) ); ?>" <?php checked( $forumids_filter ); ?> type="checkbox">
            <select id="<?php echo $this->get_field_id('forumids') ?>" class="wpf_wdg_forumids" name="<?php echo esc_attr( $this->get_field_name( 'forumids' ) ); ?>[]" multiple>
		        <?php WPF()->forum->tree( 'select_box', false, $selected ) ?>
            </select>
        </p>
        <p>
            <label for="<?php echo $this->get_field_id('current_forumid_filter') ?>"><?php _e('Autofilter by current forum', 'wpforo'); ?>:</label>
            <input id="<?php echo $this->get_field_id('current_forumid_filter') ?>" name="<?php echo esc_attr( $this->get_field_name( 'current_forumid_filter' ) ); ?>" <?php checked( $current_forumid_filter ); ?> type="checkbox">
        </p>
        <p>
            <label for="<?php echo $this->get_field_id('orderby') ?>"><?php _e('Order by', 'wpforo'); ?>:</label>
            <select name="<?php echo esc_attr($this->get_field_name( 'orderby' )); ?>" id="<?php echo $this->get_field_id('orderby') ?>">
                <?php foreach ($this->orderby_fields as $orderby_key => $orderby_field ) : ?>
                    <option value="<?php echo $orderby_key; ?>"<?php echo ( $orderby_key == $orderby ? ' selected' : '' ); ?>><?php echo $orderby_field; ?></option>
                <?php endforeach; ?>
            </select>
            <select name="<?php echo esc_attr($this->get_field_name( 'order' )); ?>">
                <?php foreach ($this->order_fields as $order_key => $order_field ) : ?>
                    <option value="<?php echo $order_key; ?>"<?php echo ( $order_key == $order ? ' selected' : '' ); ?>><?php echo $order_field; ?></option>
                <?php endforeach; ?>
            </select>
        </p>
        <p>
			<label for="<?php echo $this->get_field_id('count') ?>"><?php _e('Number of Items', 'wpforo'); ?></label>&nbsp;
			<input id="<?php echo $this->get_field_id('count') ?>" type="number" min="1" style="width: 53px;" name="<?php echo esc_attr($this->get_field_name( 'count' )); ?>"   value="<?php echo esc_attr($count) ; ?>">
		</p>
        <p>
			<label for="<?php echo $this->get_field_id('display_avatar') ?>">
				<?php _e('Display with avatars', 'wpforo'); ?>
                <input id="<?php echo $this->get_field_id('display_avatar') ?>" <?php checked( $display_avatar ); ?> type="checkbox"  name="<?php echo esc_attr($this->get_field_name( 'display_avatar' )); ?>" >
			</label>
		</p>
        <p>
            <label for="<?php echo $this->get_field_id('goto_unread') ?>"><?php _e('Refer topics to first unread post', 'wpforo'); ?></label>
            <input id="<?php echo $this->get_field_id('goto_unread') ?>" name="<?php echo esc_attr( $this->get_field_name( 'goto_unread' ) ); ?>" <?php checked( $goto_unread ); ?> type="checkbox">
        </p>
		<?php 
	}
	public function update( $new_instance, $old_instance ) {
	    $new_instance = wpforo_parse_args($new_instance, $this->default_instance);
		$instance = array();
		$instance['title'] = ( ! empty( $new_instance['title'] ) ) ? strip_tags( $new_instance['title'] ) : '';
		$instance['forumids_filter'] = isset( $new_instance['forumids_filter'] ) ? (bool) $new_instance['forumids_filter'] : $this->default_instance['forumids_filter'];
		$instance['forumids'] = ( $instance['forumids_filter'] ? array_unique( array_filter( array_map('intval', (array) wpfval($new_instance, 'forumids')) ) ) : array() );
		$instance['orderby'] = ( !empty($new_instance['orderby']) && key_exists($new_instance['orderby'], $this->orderby_fields) ) ? $new_instance['orderby'] : $this->default_instance['orderby'];
		$instance['order'] = ( !empty($new_instance['order']) && key_exists($new_instance['order'], $this->order_fields) ) ? $new_instance['order'] : $this->default_instance['order'];
		$instance['count'] = ( ! empty( $new_instance['count'] ) ) ? intval( $new_instance['count'] ) : $this->default_instance['count'];
		$instance['display_avatar'] = isset( $new_instance['display_avatar'] ) ? (bool) $new_instance['display_avatar'] : $this->default_instance['display_avatar'];
		$instance['current_forumid_filter'] = isset( $new_instance['current_forumid_filter'] ) ? (bool) $new_instance['current_forumid_filter'] : $this->default_instance['current_forumid_filter'];
		$instance['goto_unread'] = isset( $new_instance['goto_unread'] ) ? (bool) $new_instance['goto_unread'] : $this->default_instance['goto_unread'];
		return $instance;
	}
} // Recent topics

class wpForo_Widget_recent_replies extends WP_Widget {
	private $default_instance = array();
	private $orderby_fields = array();
	private $order_fields = array();
	function __construct() {
		parent::__construct(
			'wpForo_Widget_recent_replies', // Base ID
			'wpForo Recent Posts',        // Name
			array( 'description' => 'Your forum\'s recent posts.' ) // Args
		);
		$this->init_local_vars();
	}
	private function init_local_vars(){
		$this->default_instance = array(
			'title'                  => 'Recent Posts',
			'forumids'               => array(),
			'orderby'                => 'created',
			'order'                  => 'DESC',
			'count'                  => 9,
			'limit_per_topic'        => 0,
			'display_avatar'         => false,
			'forumids_filter'        => false,
			'current_forumid_filter' => false,
            'display_only_unread'    => false,
            'display_new_indicator'  => false
		);
		$this->orderby_fields = array(
			'created'   => __('Created Date',   'wpforo'),
			'modified'  => __('Modified Date',  'wpforo')
		);
		$this->order_fields = array(
			'DESC'  => __('DESC',   'wpforo'),
			'ASC'   => __('ASC',    'wpforo')
		);
	}
	public function widget( $args, $instance ) {
//		wp_enqueue_script('wpforo-widgets-js');
	    $login = is_user_logged_in();
        $instance = wpforo_parse_args($instance, $this->default_instance);
        if( $instance['display_only_unread'] ){
            $display_widget = ( $login ) ? true : false;
            $display_widget = apply_filters('wpforo_widget_display_recent_posts', $display_widget);
        } else {
            $display_widget = true;
        }

	    if( $display_widget ){
            if( $instance['current_forumid_filter'] && $current_forumid = wpfval( WPF()->current_object, 'forumid' ) ){
                $instance['forumids'] = (array) $current_forumid;
            }

            echo $args['before_widget'];//This is a HTML content//
            echo '<div id="wpf-widget-recent-replies" class="wpforo-widget-wrap">';

            if ( ! empty( $instance['title'] ) ) {
                echo $args['before_title'] . apply_filters( 'widget_title', $instance['title'] ). $args['after_title'];//This is a HTML content//
            }
            $private = (!is_user_logged_in() || !WPF()->perm->usergroup_can('aum')) ? 0 : NULL;
            $status = (!is_user_logged_in() || !WPF()->perm->usergroup_can('aum')) ? 0 : NULL;
            // widget content from front end
            $ug_can_va = WPF()->perm->usergroup_can('va');
            $is_avatar = wpforo_feature('avatars');
            $posts_args = array(
                'forumids'        => ( $instance['forumids'] ? $instance['forumids'] : $this->default_instance['forumids'] ),
                'orderby'         => ( key_exists( $instance['orderby'], $this->orderby_fields ) ? $instance['orderby'] : $this->default_instance['orderby'] ),
                'order'           => ( key_exists( $instance['order'], $this->order_fields ) ? $instance['order'] : $this->default_instance['order'] ),
                'row_count'       => ( ( $count = intval( $instance['count'] ) ) ? $count : $this->default_instance['count'] ),
                'limit_per_topic' => ( ( $limit = intval( $instance['limit_per_topic'] ) ) ? $limit : $this->default_instance['limit_per_topic'] ),
                'private'         => $private,
                'status'          => $status,
                'check_private'   => true
            );
            echo '<div class="wpforo-widget-content"><ul>';
            if( $posts_args['limit_per_topic'] ) {
                if( $instance['display_only_unread'] && $login ){
                    $grouped_postids = WPF()->post->get_unread_posts($posts_args, $count);
                } else{
                    $grouped_postids = WPF()->post->get_posts($posts_args);
                }
                if( !empty($grouped_postids) ){
                    $grouped_postids = implode(',', $grouped_postids);
                    $postids = array_filter( array_map('wpforo_bigintval', explode(',', $grouped_postids)) );
                    rsort($postids);
                    foreach( $postids as $postid ){
                        $class = '';
                        $post = wpforo_post( $postid );
                        if( !WPF()->post->access( $post, WPF()->current_userid ) ) continue;
                        $current = $post['topicid'] == wpfval(WPF()->current_object, 'topicid');
                        if( !$current ) $class = 'class="' . ( $instance['display_only_unread'] ? 'wpf-unread-post' : wpforo_unread($post['topicid'], 'post', false, $post['postid']) ) . '"';
                        $member = wpforo_member( $post );
                        ?>
                        <li <?php echo $class; ?>>
                            <div class="wpforo-list-item ">
                                <?php if( $instance['display_avatar'] ): ?>
                                    <?php if( $ug_can_va && $is_avatar ): ?>
                                        <div class="wpforo-list-item-left">
                                            <?php echo WPF()->member->avatar($member); ?>
                                        </div>
                                    <?php endif; ?>
                                <?php endif; ?>
                                <div class="wpforo-list-item-right" <?php if( !$instance['display_avatar'] ): ?> style="width:100%"<?php endif; ?>>
                                    <p class="posttitle">
                                        <a href="<?php echo esc_url($post['url']) ?>"><?php
                                            if( $t = esc_html( trim($post['title']) ) ) {
	                                            echo $t;
                                            }else{
                                                echo wpforo_phrase('RE', false, 'default') . ': ' . esc_html( trim( wpforo_topic( $post['topicid'], 'title' ) ) );
                                            } ?>
                                        </a>
                                        <?php if( !$current && $instance['display_new_indicator'] ) wpforo_unread_button($post['topicid'], '', true, $post['postid']) ?>
                                    </p>
                                    <p class="posttext"><?php echo esc_html(wpforo_text($post['body'], 55)); ?></p>
                                    <p class="postuser"><?php wpforo_phrase('by') ?> <?php wpforo_member_link($member) ?>, <?php esc_html(wpforo_date($post['created'])) ?></p>
                                </div>
                                <div class="wpf-clear"></div>
                            </div>
                        </li>
                        <?php
                    }
                } else{
                    $error_message = ($instance['display_only_unread']) ? 'No new posts found' : 'No posts found';
                    echo '<li class="wpf-no-post-found">' . wpforo_phrase($error_message, false) . '</li>';
                }
            }else{
                if( $instance['display_only_unread'] && $login ){
                    $recent_posts = WPF()->post->get_unread_posts($posts_args, $count);
                } else{
                    $recent_posts = WPF()->post->get_posts($posts_args);
                }
                if( !empty($recent_posts) ){
                    foreach( $recent_posts as $post ){
                        $class = '';
                        $post_url = wpforo_post( $post['postid'], 'url' );
                        $member = wpforo_member( $post );
                        $current = $post['topicid'] == wpfval(WPF()->current_object, 'topicid');
                        if( !$current ) $class = 'class="' . ( $instance['display_only_unread'] ? 'wpf-unread-post' : wpforo_unread( $post['topicid'], 'post', false, $post['postid'] ) ) . '"';
                        ?>
                        <li <?php echo $class; ?>>
                            <div class="wpforo-list-item">
                                <?php if( $instance['display_avatar'] ): ?>
                                    <?php if( $ug_can_va && $is_avatar ): ?>
                                        <div class="wpforo-list-item-left">
                                            <?php echo WPF()->member->avatar($member); ?>
                                        </div>
                                    <?php endif; ?>
                                <?php endif; ?>
                                <div class="wpforo-list-item-right" <?php if( !$instance['display_avatar'] ): ?> style="width:100%"<?php endif; ?>>
                                    <p class="posttitle">
                                        <a href="<?php echo esc_url($post_url) ?>"><?php
		                                    if( $t = esc_html( trim($post['title']) ) ) {
			                                    echo $t;
		                                    }else{
			                                    echo wpforo_phrase('RE', false, 'default') . ': ' . esc_html( trim( wpforo_topic( $post['topicid'], 'title' ) ) );
		                                    } ?>
                                        </a>
                                        <?php if( !$current && $instance['display_new_indicator'] ) wpforo_unread_button($post['topicid'], '', true, $post['postid']) ?>
                                    </p>
                                    <p class="posttext"><?php echo esc_html(wpforo_text($post['body'], 55)); ?></p>
                                    <p class="postuser"><?php wpforo_phrase('by') ?> <?php wpforo_member_link($member) ?>, <?php esc_html(wpforo_date($post['created'])) ?></p>
                                </div>
                                <div class="wpf-clear"></div>
                            </div>
                        </li>
                        <?php
                    }
                } else{
                    $error_message = ($instance['display_only_unread']) ? 'No new posts found' : 'No posts found';
	                echo '<li class="wpf-no-post-found">' . wpforo_phrase($error_message, false) . '</li>';
                }
            }
            echo '</ul></div>';
            echo '</div>';
            echo $args['after_widget'];//This is a HTML content//
        }
	}
	public function form( $instance ) {
		$instance               = wpforo_parse_args( $instance, $this->default_instance );
		$title                  = (string) $instance['title'];
		$selected               = array_unique( array_filter( array_map( 'intval', (array) $instance['forumids'] ) ) );
		$orderby                = (string) $instance['orderby'];
		$order                  = (string) $instance['order'];
		$count                  = (int) $instance['count'];
		$limit_per_topic        = (int) $instance['limit_per_topic'];
		$display_avatar         = (bool) $instance['display_avatar'];
		$forumids_filter        = (bool) $instance['forumids_filter'];
		$current_forumid_filter = (bool) $instance['current_forumid_filter'];
		$display_only_unread    = (bool) $instance['display_only_unread'];
        $display_new_indicator    = (bool) $instance['display_new_indicator'];
		?>
        <style type="text/css">
            select.wpf_wdg_forumids {display: none; width: 100%; min-height: 170px; }
            input.wpf_wdg_forumids_filter:checked ~ select.wpf_wdg_forumids {display: block;}
            .wpf_wdg_limit_per_topic{width: 53px;}
        </style>
        <script type="text/javascript">
            jQuery(document).ready(function ($) {
                $('.wpf_wdg_limit_per_topic').change(function(){
                    var wrap = $(this).parents('.wpf_wdg_form_wrap');
                    var disabled = $(this).val() > 0;
                    $('.wpf_wdg_orderby', wrap).attr('disabled', disabled);
                    $('.wpf_wdg_order', wrap).attr('disabled', disabled);
                });
            });
        </script>
        <div class="wpf_wdg_form_wrap">
            <p>
                <label for="<?php echo $this->get_field_id('title') ?>"><?php _e('Title', 'wpforo'); ?>:</label>
                <input id="<?php echo $this->get_field_id('title') ?>" class="widefat" name="<?php echo esc_attr($this->get_field_name( 'title' )); ?>" type="text" value="<?php echo esc_attr( $title ); ?>">
            </p>
            <p>
                <label for="<?php echo $this->get_field_id('forumids_filter') ?>"><?php _e('Filter by forums', 'wpforo'); ?>:</label>
                <input id="<?php echo $this->get_field_id('forumids_filter') ?>" class="wpf_wdg_forumids_filter" name="<?php echo esc_attr( $this->get_field_name( 'forumids_filter' ) ); ?>" <?php checked( $forumids_filter ); ?> type="checkbox">
                <select id="<?php echo $this->get_field_id('forumids') ?>" class="wpf_wdg_forumids" name="<?php echo esc_attr( $this->get_field_name( 'forumids' ) ); ?>[]" multiple>
                    <?php WPF()->forum->tree( 'select_box', false, $selected ) ?>
                </select>
            </p>
            <p>
                <label for="<?php echo $this->get_field_id('current_forumid_filter') ?>"><?php _e('Autofilter by current forum', 'wpforo'); ?>:</label>
                <input id="<?php echo $this->get_field_id('current_forumid_filter') ?>" name="<?php echo esc_attr( $this->get_field_name( 'current_forumid_filter' ) ); ?>" <?php checked( $current_forumid_filter ); ?> type="checkbox">
            </p>
            <p>
                <label for="<?php echo $this->get_field_id('orderby') ?>"><?php _e('Order by', 'wpforo'); ?>:</label>
                <select class="wpf_wdg_orderby" name="<?php echo esc_attr($this->get_field_name( 'orderby' )); ?>" id="<?php echo $this->get_field_id('orderby') ?>" <?php echo ( $limit_per_topic ? 'disabled' : '' ) ?>>
                    <?php foreach ($this->orderby_fields as $orderby_key => $orderby_field ) : ?>
                        <option value="<?php echo $orderby_key; ?>"<?php echo ( $orderby_key == $orderby ? ' selected' : '' ); ?>><?php echo $orderby_field; ?></option>
                    <?php endforeach; ?>
                </select>
                <select class="wpf_wdg_order" name="<?php echo esc_attr($this->get_field_name( 'order' )); ?>" <?php echo ( $limit_per_topic ? 'disabled' : '' ) ?>>
                    <?php foreach ($this->order_fields as $order_key => $order_field ) : ?>
                        <option value="<?php echo $order_key; ?>"<?php echo ( $order_key == $order ? ' selected' : '' ); ?>><?php echo $order_field; ?></option>
                    <?php endforeach; ?>
                </select>
            </p>
            <p>
                <label for="<?php echo $this->get_field_id('limit_per_topic') ?>"><?php _e('Limit Per Topic', 'wpforo'); ?></label>&nbsp;
                <input id="<?php echo $this->get_field_id('limit_per_topic') ?>" class="wpf_wdg_limit_per_topic" type="number" min="0" name="<?php echo esc_attr($this->get_field_name( 'limit_per_topic' )); ?>"   value="<?php echo esc_attr($limit_per_topic) ; ?>">
                <span style="color: #aaa;"><?php _e('set 0 to remove this limit', 'wpforo') ?></span>
            </p>
            <p>
                <label for="<?php echo $this->get_field_id('count') ?>"><?php _e('Number of Items', 'wpforo'); ?></label>&nbsp;
                <input id="<?php echo $this->get_field_id('count') ?>" type="number" min="1" style="width: 53px;" name="<?php echo esc_attr($this->get_field_name( 'count' )); ?>"   value="<?php echo esc_attr($count) ; ?>">
            </p>
            <p>
                <label for="<?php echo $this->get_field_id('display_avatar') ?>">
                    <input id="<?php echo $this->get_field_id('display_avatar') ?>" <?php checked( $display_avatar ); ?> type="checkbox"  name="<?php echo esc_attr($this->get_field_name( 'display_avatar' )); ?>" >
                    <?php _e('Display with Avatars', 'wpforo'); ?></label>
            </p>
            <p>
                <label for="<?php echo $this->get_field_id('display_only_unread') ?>">
                    <input id="<?php echo $this->get_field_id('display_only_unread') ?>" <?php checked( $display_only_unread ); ?> type="checkbox"  name="<?php echo esc_attr($this->get_field_name( 'display_only_unread' )); ?>" >
                    <?php _e('Display Only Unread Posts', 'wpforo'); ?></label>
            </p>
            <p>
                <label for="<?php echo $this->get_field_id('display_new_indicator') ?>">
                    <input id="<?php echo $this->get_field_id('display_new_indicator') ?>" <?php checked( $display_new_indicator ); ?> type="checkbox"  name="<?php echo esc_attr($this->get_field_name( 'display_new_indicator' )); ?>" >
                    <?php _e('Display [new] indicator', 'wpforo'); ?></label>
            </p>
        </div>
		<?php
	}
	public function update( $new_instance, $old_instance ) {
		$new_instance = wpforo_parse_args($new_instance, $this->default_instance);
		$instance = array();
		$instance['title'] = ( ! empty( $new_instance['title'] ) ) ? strip_tags( $new_instance['title'] ) : '';
		$instance['forumids_filter'] = isset( $new_instance['forumids_filter'] ) ? (bool) $new_instance['forumids_filter'] : $this->default_instance['forumids_filter'];
		$instance['forumids'] = ( $instance['forumids_filter'] ? array_unique( array_filter( array_map('intval', (array) wpfval($new_instance, 'forumids')) ) ) : array() );
		$instance['orderby'] = ( !empty($new_instance['orderby']) && key_exists($new_instance['orderby'], $this->orderby_fields) ) ? $new_instance['orderby'] : $this->default_instance['orderby'];
		$instance['order'] = ( !empty($new_instance['order']) && key_exists($new_instance['order'], $this->order_fields) ) ? $new_instance['order'] : $this->default_instance['order'];
		$instance['count'] = ( ! empty( $new_instance['count'] ) ) ? intval( $new_instance['count'] ) : $this->default_instance['count'];
		$instance['limit_per_topic'] = ( ! empty( $new_instance['limit_per_topic'] ) ) ? intval( $new_instance['limit_per_topic'] ) : $this->default_instance['limit_per_topic'];
		$instance['display_avatar'] = isset( $new_instance['display_avatar'] ) ? (bool) $new_instance['display_avatar'] : $this->default_instance['display_avatar'];
		$instance['current_forumid_filter'] = isset( $new_instance['current_forumid_filter'] ) ? (bool) $new_instance['current_forumid_filter'] : $this->default_instance['current_forumid_filter'];
        $instance['display_only_unread'] = isset( $new_instance['display_only_unread'] ) ? (bool) $new_instance['display_only_unread'] : $this->default_instance['display_only_unread'];
        $instance['display_new_indicator'] = isset( $new_instance['display_new_indicator'] ) ? (bool) $new_instance['display_new_indicator'] : $this->default_instance['display_new_indicator'];
		return $instance;
	}
} // Recent replies

class wpforo_widget_forums extends WP_Widget {
	function __construct() {
		parent::__construct(
			'wpforo_widget_forums', // Base ID
			'wpForo Forums',        // Name
			array( 'description' => 'Forum tree.' ) // Args
		);
	}
	public function widget( $args, $instance ) {
//		wp_enqueue_script('wpforo-widgets-js');

		echo $args['before_widget'];//This is a HTML content//
		echo '<div id="wpf-widget-forums" class="wpforo-widget-wrap">';
		if ( ! empty( $instance['title'] ) ) {
			echo $args['before_title'] . apply_filters( 'widget_title', $instance['title'] ). $args['after_title'];//This is a HTML content//
		}
		echo '<div class="wpforo-widget-content">';
		WPF()->forum->tree('front_list');
		echo '</div>';
		echo '</div>';
		echo $args['after_widget'];//This is a HTML content//
	}
	public function form( $instance ) {
		$title = ! empty( $instance['title'] ) ? $instance['title'] : 'Forums';
		$display_avatar = isset( $instance['display_avatar'] ) ? (bool) $instance['display_avatar'] : false;
		?>
		<p>
			<label><?php _e('Title', 'wpforo'); ?>:</label> 
			<input class="widefat" name="<?php echo esc_attr($this->get_field_name( 'title' )); ?>" type="text" value="<?php echo esc_attr( $title ); ?>">
		</p>
		<?php 
	}
	public function update( $new_instance, $old_instance ) {
		$instance = array();
		$instance['title'] = ( ! empty( $new_instance['title'] ) ) ? strip_tags( $new_instance['title'] ) : '';
		return $instance;
	}
} // forums tree

class wpForo_Widget_tags extends WP_Widget {
    function __construct() {
        parent::__construct(
            'wpForo_Widget_tags',
            'wpForo Topic Tags',
            array( 'description' => 'List of most popular tags' )
        );
    }

    public function widget( $args, $instance ) {
//	    wp_enqueue_script('wpforo-widgets-js');

        echo $args['before_widget'];
        echo '<div id="wpf-widget-tags" class="wpforo-widget-wrap">';
        if ( !empty( $instance['title'] ) ) {
            echo $args['before_title'] . apply_filters( 'widget_title', $instance['title'] ). $args['after_title'];
        }
        $tag_args = array( 'row_count' => $instance['count'] );
        $tags = WPF()->topic->get_tags( $tag_args,$items_count );
        echo '<div class="wpforo-widget-content">';
        if( !empty( $tags ) ){
            echo '<ul class="wpf-widget-tags">';
            foreach( $tags as $tag ){
                $topic_count = ( $instance['topics'] ) ? '<span>' . $tag['count'] . '</span>' : '';
                echo '<li><a href="' . esc_url( wpforo_home_url() . '?wpfin=tag&wpfs=' . $tag['tag'] ) . '" title="' . esc_attr($tag['tag']) . '">' . wpforo_text($tag['tag'], 25, false) . '</a>' . $topic_count . '</li>';
            }
            echo '</ul>';
            if( $instance['count'] < $items_count ){
                echo '<div class="wpf-all-tags"><a href="' . esc_url(wpforo_home_url(wpforo_get_template_slug('tags'))) . '">'. sprintf( wpforo_phrase('View all tags (%d)', false), $items_count ) . '</a></div>';
            }
         } else {
            echo '<p style="text-align:center">' . wpforo_phrase('No tags found', false) . '</p>';
        }
        echo '</div>';
        echo '</div>';
        echo $args['after_widget'];
    }

    public function form( $instance ) {
        $title = !empty( $instance['title'] ) ? $instance['title'] : 'Topic Tags';
        $topics = !empty( $instance['topics'] ) ? $instance['topics'] : 1;
        $count = !empty( $instance['count'] ) ? $instance['count'] : '20';
        ?>
        <p>
            <label><?php _e('Title', 'wpforo'); ?>:</label>
            <input class="widefat" name="<?php echo esc_attr($this->get_field_name( 'title' )); ?>" type="text" value="<?php echo esc_attr( $title ); ?>">
        </p>
        <p>
            <label><?php _e('Topic Counts', 'wpforo'); ?>:</label>&nbsp;&nbsp;&nbsp;&nbsp;
            <label><?php _e('Yes', 'wpforo'); ?> <input type="radio" name="<?php echo esc_attr($this->get_field_name( 'topics' )); ?>" value="1" <?php if($topics) echo 'checked="checked"'?>></label>&nbsp;&nbsp;
            <label><?php _e('No', 'wpforo'); ?> <input type="radio" name="<?php echo esc_attr($this->get_field_name( 'topics' )); ?>" value="0" <?php if(!$topics) echo 'checked="checked"'?>></label>
        </p>
        <p>
            <label><?php _e('Number of Items', 'wpforo'); ?>:</label>&nbsp;
            <input type="number" min="1" style="width: 53px;" name="<?php echo esc_attr($this->get_field_name( 'count' )); ?>"   value="<?php echo esc_attr($count) ; ?>">
        </p>
        <?php
    }

    public function update( $new_instance, $old_instance ) {
        $instance = array();
        $instance['title'] = ( ! empty( $new_instance['title'] ) ) ? strip_tags( $new_instance['title'] ) : '';
        $instance['topics'] = ( ! empty( $new_instance['topics'] ) ) ? intval( $new_instance['topics'] ) : 0;
        $instance['count'] = ( ! empty( $new_instance['count'] ) ) ? intval( $new_instance['count'] ) : 0;
        return $instance;
    }
} // Recent replies

function wpforo_widget_profile() {
	register_widget( 'wpForo_Widget_profile' );
}
add_action( 'widgets_init', 'wpforo_widget_profile' );

function wpforo_widget_search() {
    register_widget( 'wpForo_Widget_search' );
}
add_action( 'widgets_init', 'wpforo_widget_search' );

function wpforo_widget_login() {
	//Under development....
    //register_widget( 'wpForo_Widget_login_form' );
}
add_action( 'widgets_init', 'wpforo_widget_login' );

function wpforo_widget_online_members() {
    register_widget( 'wpForo_Widget_online_members' );
}
add_action( 'widgets_init', 'wpforo_widget_online_members' );

function wpforo_widget_recent_topics() {
    register_widget( 'wpForo_Widget_recent_topics' );
}
add_action( 'widgets_init', 'wpforo_widget_recent_topics' );

function wpforo_widget_recent_replies() {
    register_widget( 'wpForo_Widget_recent_replies' );
}
add_action( 'widgets_init', 'wpforo_widget_recent_replies' );

function wpforo_widget_forums() {
	//Under Development
    //register_widget( 'wpforo_widget_forums' );
}
add_action( 'widgets_init', 'wpforo_widget_forums' );

function wpforo_widget_tags() {
    register_widget( 'wpForo_Widget_tags' );
}
add_action( 'widgets_init', 'wpforo_widget_tags' );

function wpforo_post_edited($post, $echo = true){
	$edit_html = '';
	if(!empty($post)){
        $created = wpforo_date($post['created'], 'd/m/Y g:i a', false);
        $modified = wpforo_date($post['modified'], 'd/m/Y g:i a', false);
        if( isset($modified) && $created != $modified ){
            if( $post['is_first_post'] && WPF()->activity->options['edit_topic'] ){
                $edit_html = WPF()->activity->build('topic', $post['topicid'], 'edit_topic');
            }
            elseif( WPF()->activity->options['edit_post'] ){
                $edit_html = WPF()->activity->build('post', $post['postid'], 'edit_post');
            }
            $edit_html = ( $edit_html ) ? sprintf( '<div class="wpf-post-edit-wrap">%s</div>', $edit_html ) : '';
        }
	}
	if( $echo ) { 
		echo $edit_html;
	}
	else{ 
		return $edit_html;
	}
}

function wpforo_hide_title($title, $id = 0) {
	if( !wpforo_feature('page-title') && is_wpforo_page() && $id == WPF()->pageid && in_the_loop() && is_page($id) ) $title = '';
	return $title;
}
add_filter('the_title', 'wpforo_hide_title', 10, 2);

function wpforo_validate_gravatar( $email ) {
	$hashkey = md5(strtolower(trim($email)));
	$uri = 'http://www.gravatar.com/avatar/' . $hashkey . '?d=404';
	$data = wp_cache_get($hashkey);
	if (false === $data) {
		$response = wp_remote_head($uri);
		if( is_wp_error($response) ) {
			$data = 'not200';
		} else {
			$data = $response['response']['code'];
		}
	    wp_cache_set($hashkey, $data, $group = '', $expire = 60*5);
	}		
	if ($data == '200'){
		return true;
	} else {
		return false;
	}
}

function wpforo_member_title( $member = array(), $echo = true, $before = '', $after = '', $exclude = array() ){
	$title = array();
	
	if(empty($member) || !$member['groupid']) return '';
	$rating_title_ug_enabled = ( isset(WPF()->member->options['rating_title_ug'][$member['groupid']]) && WPF()->member->options['rating_title_ug'][$member['groupid']] ) ? true : false ;
	$usergroup_title_ug_enabled = ( isset(WPF()->member->options['title_usergroup'][$member['groupid']]) && WPF()->member->options['title_usergroup'][$member['groupid']] ) ? true : false ;
    $usergroup_title_sug_enabled = ( isset(WPF()->member->options['title_second_usergroup'][$member['groupid']]) && WPF()->member->options['title_second_usergroup'][$member['groupid']] ) ? true : false ;

    if( !in_array('rating-title', $exclude) && wpforo_feature('rating_title') && $rating_title_ug_enabled && isset($member['stat']['title']) ){
		$title[] = '<span class="wpf-member-title wpfrt" title="' . wpforo_phrase('Rating Title', false) . '">' . esc_html($member['stat']['title']) . '</span>';
	}  
	if( !in_array('custom-title', $exclude) && empty($title) && WPF()->member->options['custom_title_is_on'] ){
        $title[] = '<span class="wpf-member-title wpfct" title="' . wpforo_phrase('User Title', false) . '">' . wpforo_phrase($member['title'], false) . '</span>';
	}else{
	    $before = $after = '';
    }
	if( !in_array('usergroup', $exclude) && $usergroup_title_ug_enabled  ){
		$class = '';
		if( $member['groupid'] == 1 ) $class = ' wpfbg-6 wpfcl-3';
		if( $member['groupid'] == 2 ) $class = ' wpfbg-5 wpfcl-3';
		if( $member['groupid'] == 4 ) $class = ' wpfbg-2 wpfcl-3';
        $title[] = '<span class="wpf-member-title wpfut wpfug-' . intval($member['groupid']) . $class . '" title="' . wpforo_phrase('Usergroup', false) . '">' . esc_html($member['groupname']) . '</span>';
    }
    if( !in_array('usergroup', $exclude) && $usergroup_title_sug_enabled ){
        $secondary_groups = ( wpfval($member, 'secondary_groups') ) ? WPF()->usergroup->get_secondary_usergroup_names($member['secondary_groups']) : array();
        if( $secondary_groups ){
            $title[] = '<span class="wpf-member-title wpfut wpfsut" title="' . wpforo_phrase('Secondary Usergroup', false) . '">' . esc_html(implode(', ', $secondary_groups)) . '</span>';
        }
    }
	if( !empty($title) ){
		$title_html = $before . implode(' ', $title) . $after;
		$title_html = apply_filters('wpforo_member_title', $title_html, $member);
		if( $echo ) { 
			echo $title_html;
		}
		else{ 
			return $title_html;
		}
	}
}

function wpforo_member_badge( $member = array(), $sep = '', $type = 'full' ){
	$rating_badge_ug_enabled = ( isset(WPF()->member->options['rating_badge_ug'][$member['groupid']]) && WPF()->member->options['rating_badge_ug'][$member['groupid']] ) ? true : false ;
	if( wpforo_feature('rating') && $rating_badge_ug_enabled && isset($member['stat']['rating']) ): ?>
        <div class="author-rating-<?php echo esc_attr($type) ?>" style="color:<?php echo esc_attr($member['stat']['color']) ?>" title="<?php wpforo_phrase('Member Rating Badge') ?>">
            <?php echo WPF()->member->rating_badge($member['stat']['rating'], $type); ?>
        </div><?php if($sep): ?><span class="author-rating-sep"><?php echo esc_html($sep); ?></span><?php endif; ?>
    <?php endif;
    
    do_action('wpforo_after_member_badge', $member);
}

function wpforo_member_nicename( $member = array(), $prefix = '', $bracket = true, $wrap = true, $class = 'wpf-author-nicename', $echo = true ){
	if( !wpforo_feature('mention-nicknames') || empty($member) || !isset($member['user_nicename']) ) return '';
	$nicename = '';
	if( $wrap ){ $nicename .= '<div class="' . $class . '" title="' . wpforo_phrase('You can mention a person using @nicename in post content to send that person an email message. When you post a topic or reply, forum sends an email message to the user letting them know that they have been mentioned on the post.', false) . '">';}
	if( $bracket ) $nicename .= '(';
	$nicename .= $prefix . urldecode($member['user_nicename']);
	if( $bracket ) $nicename .= ')';
	if( $wrap ){ $nicename .= '</div>';}
	$nicename = apply_filters('wpforo_member_nicename', $nicename, $member);
	if( $echo ){ echo $nicename; } else{ return $nicename; }
}

add_filter( 'body_class', 'wpforo_page_class', 1, 10 );
function wpforo_page_class( $classes ) {
	if(!empty($classes)){
    	if( function_exists('is_wpforo_page') ){
			if ( is_wpforo_page() ) {
				return array_merge( $classes, array( 'wpforo' ) );
			}
		}
	}
	return (array)$classes;
}

###############################################################################
########################## THEME API FUNCTIONS ################################
###############################################################################

function wpforo_post( $postid, $var = 'item', $echo = false ){
	$post = ( $var == 'item' ) ? array() : '';
	if( !$postid ) return $post;
	$cache = WPF()->cache->on('object_cashe');
	if( $cache ){
		 $post = WPF()->cache->get_item( $postid, 'post' );
	}
	if( empty($post) ){
		$post = array();
		if( !$cache && $var == 'url' ){
			$post['url'] = WPF()->post->get_post_url($postid);
		}
		elseif( !$cache && $var == 'is_answered' ){
			$post['is_answered'] = WPF()->post->is_answered($postid);
		}
		elseif( !$cache && $var == 'votes_sum' ){
            $post['votes_sum'] = 0; //deprecate
		}
		elseif( !$cache && $var == 'likes_count' ){
			$post['likes_count'] = WPF()->post->get_post_likes_count($postid);
		}
		elseif( !$cache && $var == 'likers_usernames' ){
			$post['likers_usernames'] = WPF()->post->get_likers_usernames($postid);
		}
		else{
			$post = WPF()->post->get_post($postid);
			if( !empty($post) ){
				$post['url'] = WPF()->post->get_post_url($post);
                $post['is_answered'] = WPF()->post->is_answered($postid);
                $post['votes_sum'] = $post['votes']; //deprecate
                $post['likes_count'] = WPF()->post->get_post_likes_count($postid);
                $post['likers_usernames'] = WPF()->post->get_likers_usernames($postid);
				if(!empty($post)){ 
					$cache_item = array( $postid => $post );
					WPF()->cache->create('item', $cache_item, 'post');
				}
			}
		}
	}
	
	if( $var != 'item' && $var ){
		$post = ( isset($post[$var]) ) ? $post[$var] : '';
	}
	
	if( $echo ){
		echo $post;
	}
	else{
		return $post;
	}
}

function wpforo_topic( $topicid, $var = 'item', $echo = false ){
	$topic = ( $var == 'item' ) ? array() : '';
	if( !$topicid ) return $topic;
	$cache = WPF()->cache->on('object_cashe');
	if( $cache ) $topic = WPF()->cache->get_item( $topicid, 'topic' );
	
	if( empty($topic) ){
		$topic = array();
		if( !$cache && $var == 'url' ){
			$topic['url'] = WPF()->topic->get_topic_url( $topicid );
		}else{
			$topic = WPF()->topic->get_topic($topicid);
			if( !empty($topic) ){
				$topic['url'] = WPF()->topic->get_topic_url($topic);
				if(!empty($topic)){
					$cache_item = array( $topicid => $topic );
					WPF()->cache->create('item', $cache_item, 'topic');
				}
			}
		}
	}
	
	if( $var != 'item' && $var ){
		$topic = ( isset($topic[$var]) ) ? $topic[$var] : '';
	}
	
	if( $echo ){
		echo $topic;
	}
	else{
		return $topic;
	}
}

function wpforo_forum( $forumid, $var = 'item', $echo = false ){
	$data = array();
	$forum = ( $var == 'item' ) ? array() : '';
	$cache = WPF()->cache->on('object_cashe');
	if( !$forumid ) return $forum;
	if( $cache ) $forum = WPF()->cache->get_item( $forumid, 'forum' );
	
	if( empty($forum) ){
		$forum = array();
		if( !$cache && ($var == 'childs' || $var == 'counts') ){
			if( $var == 'childs' ) { 
				WPF()->forum->get_childs($forumid, $data);
				$forum['childs'] = $data;
			}
			else{ 
				WPF()->forum->get_childs($forumid, $data);
				$forum['childs'] = $data;
				$forum['counts'] = WPF()->forum->get_counts( $data );
			}
		}
		else{
			$forum = WPF()->forum->get_forum($forumid);
			if( !empty($forum) ){
				if( $cache ){
					WPF()->forum->get_childs($forum['forumid'], $data);
					$forum['childs'] = $data;
					$forum['counts'] = WPF()->forum->get_counts( $data );
				}
				if(!empty($forum)){ 
					$cache_item = array( $forumid => $forum );
					WPF()->cache->create('item', $cache_item, 'forum');
				}
			}
		}
	}
	
	if( $var != 'item' && $var ){
		$forum = ( isset($forum[$var]) ) ? $forum[$var] : '';
	}
	
	if( $echo ){
		echo $forum;
	}
	else{
		return $forum;
	}
}

function wpforo_member( $object, $var = 'item', $echo = false ){
	$member = null;
	if( empty( $object ) ) return $member;
	
	if( is_array( $object ) && isset($object['userid']) && !$object['userid'] ){ 
		$member = WPF()->member->get_guest( $object );
	}
	else{
		$userid = ( is_array( $object ) && isset($object['userid']) ) ? intval($object['userid']) : intval($object);
		$member = WPF()->member->get_member( $userid );
		if( isset($member['fields']) && $member['fields'] && is_string($member['fields']) ){
			$member['fields'] = json_decode($member['fields'], true);
		}
	}
	if( $var != 'item' && $var ) $member = wpfval($member, $var);
	if($echo) echo $member;
	return $member;
}

function wpforo_current_usermeta( $key ){
    if( wpfkey( WPF()->current_usermeta, $key ) ){
        if( wpfkey( WPF()->current_usermeta[ $key ], 0) ){
            $meta = maybe_unserialize( WPF()->current_usermeta[$key][0] );
            return $meta;
        }
    }
}

function wpforo_tag( $tagid, $var = 'item', $echo = false ){
    $tag = ( $var == 'item' ) ? array() : '';
    if( !$tagid ) return $tag;
    $cache = WPF()->cache->on('object_cashe');

    if( $cache ) $tag = WPF()->cache->get_item( md5($tagid), 'tag' );

    if( empty($tag) ){
        $tag = array();
        if( !$cache && $var == 'url' && wpfval($tag, 'tag') ){
            $tag['url'] = wpforo_home_url() . '?wpfin=tag&wpfs=' . $tag['tag'];
        }
        else{
            $tag = WPF()->topic->get_tag($tagid);
            if( !empty($tag) ){
                $tag['url'] = wpforo_home_url() . '?wpfin=tag&wpfs=' . $tag['tag'];
                if(!empty($tag)){
                    $cache_item = array( md5($tagid) => $tag );
                    WPF()->cache->create('item', $cache_item, 'tag');
                }
            }
        }
    }

    if( $var != 'item' && $var ){
        $tag = ( isset($tag[$var]) ) ? $tag[$var] : '';
    }

    if( $echo ){
        echo $tag;
    }
    else{
        return $tag;
    }
}

function wpforo_member_link( $member, $prefix = '', $length = 30, $class = '', $echo = true ){
	$display_name = ( isset($member['display_name']) && $member['display_name'] ) ? $member['display_name'] : wpforo_phrase('Anonymous', false);
	$color = (isset($member['color']) && $member['color'] ) ? 'style="color:' . $member['color'] . '"' : '';
	$class = ($class) ? 'class="' . $class . '"' : '';
    $title = ($display_name) ? 'title="' . esc_attr($display_name) . '"' : '';
	if( wpfval($member, 'profile_url') ){
	    $link = '<a href="' . esc_url($member['profile_url']) . '" ' . $color . ' ' . $class . ' ' . $title . '>' . ( strpos($prefix, '%s') !== FALSE ? sprintf( wpforo_phrase($prefix, FALSE), esc_html( wpforo_text($display_name, $length, FALSE) ) ) : ( $prefix ? wpforo_phrase($prefix, false) . ' ' : '') . ( $length ? esc_html( wpforo_text($display_name, $length, false) ) : esc_html($display_name) ) ) . '</a>';
	}
	else{
        $link = ( strpos($prefix, '%s') !== FALSE ? sprintf( wpforo_phrase($prefix, FALSE), esc_html( wpforo_text($display_name, $length, FALSE))) : ( ( $prefix ? wpforo_phrase( $prefix, false) . ' ' : '' ) . ( $length ? esc_html( wpforo_text($display_name, $length, false) ) : esc_html($display_name) ) ) );
	}
    if( $echo ){
	    echo $link;
    } else {
	    return $link;
    }
}

add_shortcode('wpforo-lostpassword', 'wpforo_lostpassword');
function wpforo_lostpassword(){
    $ob_exists = function_exists('ob_start') && function_exists('ob_get_clean');
    if($ob_exists) ob_start();
    ?>
    <p id="wpforo-title"><?php wpforo_phrase('Reset Password') ?></p>
    <form name="wpflogin" action="<?php echo esc_url( site_url( 'wp-login.php?action=lostpassword', 'login_post' ) ); ?>" method="POST">
        <div class="wpforo-login-wrap wpfbg-9">
            <div class="wpforo-login-content">
                <h3><?php wpforo_phrase('Forgot Your Password?') ?></h3>
				<div class="wpforo-table wpforo-login-table">
				  <div class="wpf-tr row-0">
					<div class="wpf-td wpfw-1 row_0-col_0" style="padding-top:10px;">
					  <div class="wpf-field wpf-field-type-text">
						<div class="wpf-field-wrap">
							<label for="userlogin" style="display: block; text-align: center; font-size: 14px; padding-bottom: 10px;"><?php wpforo_phrase('Please Insert Your Email or Username') ?></label>
							<input id="userlogin" autofocus required type="text" name="user_login" class="wpf-login-text" />
							<div style="text-align: center; font-size: 13px; padding-top: 10px; line-height: 18px;"><?php wpforo_phrase('Enter your email address or username and we\'ll send you a link you can use to pick a new password.') ?></div>
						</div>
                		<div class="wpf-field-cl"></div>
              		  </div>
					  <div class="wpf-field wpf-field-type-text wpf-field-hook">
						<div class="wpf-field-wrap">
							<?php do_action('lostpassword_form') ?><div class="wpf-field-cl"></div>
						</div>
						<div class="wpf-field-cl"></div>
					  </div>
					  <div class="wpf-field">
						<div class="wpf-field-wrap" style="text-align:center; width:100%;">
							<input type="submit" name="wpfororp" value="<?php wpforo_phrase('Reset Password') ?>" />
						</div>
						<div class="wpf-field-cl"></div>
					  </div>
					  <div class="wpf-field wpf-extra-field-end">
						<div class="wpf-field-wrap" style="text-align:center; width:100%;">
							<?php do_action('wpforo_lostpass_form_end') ?>
							<div class="wpf-field-cl"></div>
						</div>
					  </div>
			          <div class="wpf-cl"></div>
				    </div>
			      </div>
			    </div>
            </div>
        </div>
    </form>
    <?php
	return ($ob_exists) ? trim( ob_get_clean() ) : '';
}

add_shortcode('wpforo-resetpassword', 'wpforo_resetpassword');
function wpforo_resetpassword(){
	$ob_exists = function_exists('ob_start') && function_exists('ob_get_clean');
	if($ob_exists) ob_start();
	?>
    <p id="wpforo-title"><?php wpforo_phrase('Reset Password') ?></p>

    <form name="wpflogin" action="<?php echo esc_url( site_url( 'wp-login.php?action=resetpass', 'login_post' ) ); ?>" method="POST" autocomplete="off">
        <input type="hidden" name="rp_key" value="<?php echo esc_attr($_REQUEST['rp_key']) ?>">
        <input type="hidden" name="rp_login" value="<?php echo esc_attr($_REQUEST['rp_login']) ?>">
        <div class="wpforo-login-wrap">
            <div class="wpforo-login-content">
				<div class="wpforo-table wpforo-login-table">
				  <div class="wpf-tr row-0">
					<div class="wpf-td wpfw-1 row_0-col_0" style="padding-top:10px;">
					  <div class="wpf-field wpf-field-type-text">
						<div class="wpf-field-wrap">
							<label for="userlogin" style="display: block; text-align: center; font-size: 14px; padding-bottom: 10px;"><?php wpforo_phrase('New password') ?></label>
							<input type="password" name="pass1" id="pass1" class="input" size="20" value="" autocomplete="off" required autofocus />
						</div>
						<div class="wpf-field-cl"></div>
					  </div>
					  <div class="wpf-field wpf-field-type-text">
						<div class="wpf-field-wrap">
							<label for="userlogin" style="display: block; text-align: center; font-size: 14px; padding-bottom: 10px;"><?php wpforo_phrase('Repeat new password') ?></label>
							<input type="password" name="pass2" id="pass2" class="input" size="20" value="" autocomplete="off" required />
						</div>
						<div class="wpf-field-cl"></div>
					  </div>
					  <div class="wpf-field wpf-field-type-text">
						<div class="wpf-field-wrap">
							<?php echo wp_get_password_hint(); ?>
						</div>
						<div class="wpf-field-cl"></div>
					  </div>
					  <div class="wpf-field">
						<div class="wpf-field-wrap" style="text-align:center; width:100%;">
							<input type="submit" name="submit" value="<?php wpforo_phrase('Reset Password'); ?>" />
						</div>
						<div class="wpf-field-cl"></div>
					  </div>
					  <div class="wpf-field wpf-extra-field-end">
						<div class="wpf-field-wrap" style="text-align:center; width:100%;">
							<?php do_action('wpforo_resetpass_form_end') ?>
							<div class="wpf-field-cl"></div>
						</div>
					  </div>
					  <div class="wpf-cl"></div>
					</div>
				  </div>
				</div>
            </div>
        </div>
    </form>
    <?php
	return ($ob_exists) ? trim( ob_get_clean() ) : '';
}

add_shortcode('wpforo-login-form', 'wpforo_login_form');
function wpforo_login_form(){
	$ob_exists = function_exists('ob_start') && function_exists('ob_get_clean');
	if($ob_exists) ob_start();
	if( $path = WPF()->tpl->get_template_path('login') ) include( $path );
	return ($ob_exists) ? trim( ob_get_clean() ) : '';
}

#############################################################################################
/**
 * Generates according page form fields using tpl->form_fields() function
 *
 * @since 1.4.0
 *
 * @param	array		$fields arguments
 * @param	boolean		$echo
 *
 * @return	string		form fields HTML
 */
function wpforo_fields( $fields, $echo = true ){
    if( empty($fields) ) return '';
	$fields = apply_filters( 'wpforo_form_fields', $fields );
	$html = WPF()->form->build( $fields );
	if( $echo ){
		echo $html;
	}
	else{
		return $html;
	}
}

function wpforo_user_avatar( $user, $size, $attr = '', $lastmod = false ){
	$avatar_html = '';
	if( is_numeric($user) && $user ){
		$avatar_html = ($size) ? get_avatar($user, $size) : get_avatar($user);
		if($attr) $avatar_html = str_replace('<img', '<img ' . $attr, $avatar_html);
	}
	elseif( is_array($user) && !empty($user) ){
		$avatar_html = WPF()->member->avatar($user, $attr, $size);
	}
	
	if( $lastmod ){
		$url = wpforo_avatar_url( $avatar_html );
		if($url){
			if( strpos($url, '?') === FALSE ){
				$avatar_html = str_replace($url, $url . '?lm=' . time(), $avatar_html);
			}
		}
	}
	return $avatar_html;
}

function wpforo_signature( $member, $args = array() ){

	if( is_numeric($member) ) $member = wpforo_member( $member );
	if( WPF()->current_userid != wpfval($member, 'userid') && !WPF()->perm->usergroup_can('vms') ) return '';

	$signature = '';
	$default = array('nofollow' => 1, 'kses' => 1, 'echo' => 1);
	if( empty($args) ){
		$args = $default;
	}else{
		$args = wpforo_parse_args( $args, $default );	
	}
	
	if( is_array($member) && !empty($member) ){
		$signature = ( isset($member['signature']) ) ? $member['signature'] : '';
	}
	elseif( is_string($member) ){
		$signature = $member;
	}

	$signature = stripslashes($signature);
	
	if(!empty($args)){
		extract($args, EXTR_OVERWRITE);
		if(isset($kses) && $kses) $signature = wpforo_kses($signature, 'user_description');
		if(isset($nofollow) && $nofollow) $signature = wpforo_nofollow_tag($signature);
	}
	else{
		$signature = wpforo_nofollow(wpforo_kses($signature, 'user_description'));
	}

	$length = apply_filters('wpforo_signature_length', 0);
    $signature = wpforo_text($signature, $length, false, false, false, false);
	$signature = wpautop($signature);
	
	if($echo){
		echo $signature;
	}else{
		return $signature;
	}
}

function wpforo_register_fields(){
    $fields = WPF()->member->get_register_fields();
    do_action( 'wpforo_register_page_start', $fields );

    return $fields;
}

function wpforo_account_fields(){
    $fields = WPF()->member->get_account_fields();
    do_action( 'wpforo_account_page_start', $fields );
    return $fields;
}

function wpforo_profile_fields(){
    $fields = WPF()->member->get_profile_fields();
    do_action( 'wpforo_profile_page_start', $fields );

    return $fields;
}

function wpforo_search_fields(){
    $fields = WPF()->member->get_search_fields();
    do_action( 'wpforo_search_page_start', $fields );

    if( WPF()->member->options['search_type'] === 'search' ){
        $fields = array(
            array(
                array(
                    array(
                        'type' => 'search',
                        'isDefault' => 1,
                        'isRemovable' => 0,
                        'isRequired' => 0,
                        'isEditable' => 1,
                        'class' => 'wpf-member-search-field',
                        'label' => wpforo_phrase('Find a member', false),
                        'title' => wpforo_phrase('Find a member', false),
                        'placeholder' => wpforo_phrase('Display Name or Nicename', false),
                        'faIcon' => 'fas fa-search',
                        'name' => 'wpfms',
                        'cantBeInactive' => array('search'),
                        'can' => '',
                        'isSearchable' => 1
                    )
                )
            )
        );
    }

    return $fields;
}

function wpforo_unread( $itemid, $item, $echo = true, $postid = 0 ){
    $class = '';
    $unread = false;
    $login = is_user_logged_in();
    $login = apply_filters( 'wpforo_unread_logging_for_guests', $login );
    if( $login ){
        if( $item == 'forum' ){
            $class = 'wpf-unread-forum';
            $unread = WPF()->log->unread( $itemid, 'forum' );
            $unread = apply_filters( 'wpforo_unread_forum', $unread, $itemid );
        } elseif( $item == 'topic' ) {
            $class = 'wpf-unread-topic';
            $unread = WPF()->log->unread( $itemid, 'topic' );
            $unread = apply_filters( 'wpforo_unread_topic', $unread, $itemid );
        } elseif( $item == 'post' ) {
            $class = 'wpf-unread-post';
            $unread = WPF()->log->unread( $itemid, 'post', $postid );
            $unread = apply_filters( 'wpforo_unread_post', $unread, $itemid, $postid );
        }
    }
    $class = ( $unread ) ? apply_filters( 'wpforo_unread_class', $class, $itemid, $item ) : '';
    if( $echo ){ echo $class; } else { return $class; }
}

function wpforo_unread_forum( $logid, $return = 'class', $echo = true ){
    $unread = WPF()->log->unread( $logid, 'forum' );
	if( $unread ){
	    if( $return == 'class' ){
	        $log = 'wpf_forum_unread';
	    } else {
	        $log = true;
	    }
	    if( $echo ){ echo $log; } else { return $log; }
	}
}

function wpforo_unread_topic( $logid, $return = 'class', $echo = true ){
    $unread = WPF()->log->unread( $logid, 'topic' );
	if( $unread ){
	    if( $return == 'class' ){ $log = 'wpf_topic_unread'; } else{ $log = true; }
	    if( $echo ){ echo $log; } else { return $log; }
	}
}

if( !function_exists('custom_wpforo_get_account_fields') ){
    function custom_wpforo_get_account_fields($fields){
        $hide = array(
            'user_email',
            'user_nicename'
        );

        foreach ( $fields as $row_key => $row ){
            foreach ( $row as $col_key => $col ){
                foreach ( $col as $key => $field ){
                    if( in_array($field['fieldKey'], $hide) ){
                        unset($fields[$row_key][$col_key][$key]);
                    }
                }
            }
        }

        return $fields;
    }
}

function wpforo_moderation_tools(){
    if( empty(WPF()->current_object['forumid']) || empty(WPF()->current_object['topicid']) ) return;
	?>
	<div id="wpf_moderation_tools" class="wpf-tools">
        <?php
			$tabs = array();
			if( is_user_logged_in() && WPF()->perm->forum_can('mt') ){
                $posts = (int) wpfval(WPF()->current_object, 'topic', 'posts');
				$tabs[] = array('title' => wpforo_phrase('Move Topic', false),  'id' => 'topic_move_form',  'class' => 'wpft-move',  'icon' => 'far fa-share-square');
            	if( $posts > 1 ) {
					$tabs[] = array('title' => wpforo_phrase('Move Reply', false), 'id' => 'reply_move_form', 'class' => 'wpft-reply-move', 'icon' => 'far fa-share-square');
				}
				$tabs[] = array('title' => wpforo_phrase('Merge Topics', false), 'id' => 'topic_merge_form', 'class' => 'wpft-merge', 'icon' => 'fas fa-code-branch');
				if( $posts > 1 ) {
					$tabs[] = array('title' => wpforo_phrase('Split Topic', false), 'id' => 'topic_split_form', 'class' => 'wpft-split', 'icon' => 'fas fa-cut');
				}
			}
			WPF()->tpl->topic_moderation_tabs($tabs);
        ?>
	</div>
	<?php
}

function wpforo_subscription_tools(){

    if ( !WPF()->current_object['user_is_same_current_user'] || ( wpforo_feature('subscribe_conf') && !wpforo_current_user_is('admin') && !WPF()->sbscrb->is_email_confirmed() ) ) return;
    $sbs = array();
    $allposts_checked = '';
    $alltopics_checked = '';

    if( WPF()->sbscrb->get_subscribes(array('type' => 'forums-topics', 'userid' => WPF()->current_userid)) )
        $allposts_checked = ' checked';
    if( WPF()->sbscrb->get_subscribes(array('type' => 'forums', 'userid' => WPF()->current_userid)) )
        $alltopics_checked = ' checked';

    if( !$allposts_checked && !$alltopics_checked ){
        if( $sbs_forum = WPF()->sbscrb->get_subscribes(array('type' => 'forum', 'userid' => WPF()->current_userid)) )
            foreach ($sbs_forum as $s) $sbs[$s['itemid']] = $s['type'];
        if( $sbs_forum_topic = WPF()->sbscrb->get_subscribes(array('type' => 'forum-topic', 'userid' => WPF()->current_userid)) )
            foreach ($sbs_forum_topic as $s) $sbs[$s['itemid']] = $s['type'];
    }
    ?>
    <script type="text/javascript">
        jQuery(document).ready(function ($) {
            if( $('#wpf_subscription_tools input[id^="wpf_sbs_allposts_"]').length === $('#wpf_subscription_tools input[id^="wpf_sbs_allposts_"]:checked').length ){
                $('#wpf_sbs_allposts').prop('checked', true);
            }
            if( $('#wpf_subscription_tools input[id^="wpf_sbs_alltopics_"]').length === $('#wpf_subscription_tools input[id^="wpf_sbs_alltopics_"]:checked').length ){
                $('#wpf_sbs_alltopics').prop('checked', true);
            }
            if( $('#wpf_sbs_allposts').is(':checked') ){
                $('#wpf_subscription_tools input[id^="wpf_sbs_allposts_"]').prop('checked', true);
            }
            if( $('#wpf_sbs_alltopics').is(':checked') ){
                $('#wpf_subscription_tools input[id^="wpf_sbs_alltopics_"]').prop('checked', true);
            }
            var wpforo_wrap = $('#wpforo-wrap');
            wpforo_wrap.on('change', '#wpf_sbs_allposts', function () {
                var stat = $(this).is(':checked');
                $('#wpf_sbs_alltopics').prop('checked', false);
                $('#wpf_subscription_tools input[id^="wpf_sbs_allposts_"]').prop('checked', stat);
                if(stat) $('#wpf_subscription_tools input[id^="wpf_sbs_alltopics_"]').prop('checked', !stat);
            });
            wpforo_wrap.on('change', '#wpf_sbs_alltopics', function () {
                var stat = $(this).is(':checked');
                $('#wpf_sbs_allposts').prop('checked', false);
                $('#wpf_subscription_tools input[id^="wpf_sbs_alltopics_"]').prop('checked', stat);
                if(stat) $('#wpf_subscription_tools input[id^="wpf_sbs_allposts_"]').prop('checked', !stat);
            });
            wpforo_wrap.on('change', '#wpf_subscription_tools input[id^="wpf_sbs_allposts_"]', function () {
                var stat = $(this).is(':checked');
                $('#wpf_sbs_allposts,#wpf_sbs_alltopics').prop('checked', false);
                if( stat ) {
                    if( $('#wpf_subscription_tools input[id^="wpf_sbs_allposts_"]').length === $('#wpf_subscription_tools input[id^="wpf_sbs_allposts_"]:checked').length ){
                        $('#wpf_sbs_allposts').prop('checked', true);
                    }
                    $(this).siblings('input[id^="wpf_sbs_alltopics_"]').prop('checked', false);
                }
            });
            wpforo_wrap.on('change', '#wpf_subscription_tools input[id^="wpf_sbs_alltopics_"]', function () {
                var stat = $(this).is(':checked');
                $('#wpf_sbs_allposts,#wpf_sbs_alltopics').prop('checked', false);
                if( stat ) {
                    if( $('#wpf_subscription_tools input[id^="wpf_sbs_alltopics_"]').length === $('#wpf_subscription_tools input[id^="wpf_sbs_alltopics_"]:checked').length ){
                        $('#wpf_sbs_alltopics').prop('checked', true);
                    }
                    $(this).siblings('input[id^="wpf_sbs_allposts_"]').prop('checked', false);
                }
            });
        });
    </script>
    <div id="wpf_subscription_tools" class="wpf-tools">
        <p class="wpf-sbs-head"><?php wpforo_phrase('Subscription Manager') ?></p>
        <form id="wpf_sbs_form" method="post" enctype="multipart/form-data" action="">
            <input type="hidden" name="wpfaction" value="wpforo_subscribe_manager">
            <div class="wpf-sbs-bulk">
                <div class="wpf-sbs-bulk-posts"><input id="wpf_sbs_allposts" type="checkbox" name="wpforo[check_all]" value="forums-topics" <?php echo $allposts_checked ?>><label for="wpf_sbs_allposts"><?php wpforo_phrase('Subscribe to all new topics and posts') ?></label></div>
                <div class="wpf-sbs-bulk-topics"><input id="wpf_sbs_alltopics" type="checkbox" name="wpforo[check_all]" value="forums" <?php echo $alltopics_checked ?>><label for="wpf_sbs_alltopics"><?php wpforo_phrase('Subscribe to all new topics') ?></label></div>
            </div>
            <div class="wpf-sbs-bulk-options">
                <ul>
                    <?php WPF()->forum->tree('subscribe_manager_form', false, $sbs); ?>
                </ul>
            </div>
            <div class="wpf-sbs-tool-foot"><input type="submit" name="wpforo_subscribe_manager" value="<?php wpforo_phrase('Update Subscriptions') ?>"></div>
        </form>
    </div>
    <?php
}


/**
 * Add an activity item.
 * @since 1.4.6
 * @param 	array	$args {
 *     An array of arguments.
 *     @type string   $action            Optional. The activity action/description, typically something like "Joe posted an update". 
 *     @type string   $title           	  Optional. The title of the activity item.
 *     @type string   $content           Optional. The content of the activity item.
 *     @type string   $component         The unique name of the component associated with the activity item - 'activity', etc.
 *     @type string   $type              The specific activity type, used for directory filtering. 'wpforo_topic', 'wpforo_post', etc.
 *     @type string   $primary_link      Optional. The URL for this item, as used in RSS feeds. Defaults to the URL for this activity item's permalink page.
 *     @type int|bool $user_id           Optional. The ID of the user associated with the activity item. May be set to false or 0 if the item is not related to any user. Default: the ID of the currently logged-in user.
 *     @type string   $date_recorded     Optional. The GMT time, in Y-m-d h:i:s format, when the item was recorded. Defaults to the current time.
 * }
 * @return NULL
 */
function wpforo_activity( $args = array() ){
	
	$default = array( 'action' => '', 'title' => '', 'content' => '', 'component' => 'community', 'type' => '', 'primary_link' => '', 'user_id' => '', 'item_id'=> '', 'date_recorded' => '');
	$args = wpforo_parse_args( $args, $default );
	
	//BuddyPress Member Activity 
	if( wpforo_feature('bp_activity') && function_exists('wpforo_bp_activity') ){
		wpforo_bp_activity( $args );
	}
}

function wpforo_activity_delete( $args = array() ){
	
	$default = array( 'action' => '', 'title' => '', 'content' => '', 'component' => 'community', 'type' => '', 'primary_link' => '', 'user_id' => '', 'item_id'=> '', 'date_recorded' => '');
	$args = wpforo_parse_args( $args, $default );
	
	//Delete BuddyPress Member Activity 
	if( wpforo_feature('bp_activity') && function_exists('wpforo_bp_activity_delete') ){
		wpforo_bp_activity_delete( $args );
	}
}

function wpforo_activity_content( $item = array() ){
	$args = array();
	if( empty($item) ) return false;
	if((isset($item['status']) && $item['status']) || (isset($item['private']) && $item['private']))  return false;
	if( isset($item['forumid']) && $item['forumid'] ){
		$private_for_usergroups = array(3, 4, 5);
		$private_for_usergroups = apply_filters( 'wpforo_activity_private_for_usergroups', $private_for_usergroups );
		if( !empty($private_for_usergroups) && WPF()->forum->private_forum($item['forumid'], $private_for_usergroups) ){
			return false;
		}
	}

    if( isset($item['first_postid']) && $item['first_postid'] ) {
		$args['item_id'] = $item['first_postid'];
	}
	elseif( isset($item['postid']) && $item['postid'] ){
		$args['item_id'] = $item['postid'];
	}
	$args['user_id'] = ( isset($item['userid']) && $item['userid'] ) ? $item['userid'] : $args['user_id'] = WPF()->current_userid;
	$member = wpforo_member( $args['user_id'] );
	if( isset($item['topicurl']) ){
		$args['type'] = 'wpforo_topic';
		$args['content'] = ( wpfval($item, 'body') ) ? $item['body'] : '';
		$args['primary_link'] = $item['topicurl'];
		if( isset($item['title']) ) $args['title'] = $item['title'];
		if( $args['title'] ) $args['title'] = ' "' . esc_html($args['title']) . '"';
		$args['action'] = sprintf( wpforo_phrase('%s posted a new topic %s', false), '', '');
	}
	elseif( isset($item['posturl']) ){
		$args['type'] = 'wpforo_post';
		$args['content'] = ( wpfval($item, 'body') ) ? $item['body'] : '';
		$args['primary_link'] = $item['posturl'];
		if( isset($item['title']) ) $args['title'] = preg_replace('|^.+?\:\s*|is', '', $item['title']);
		if( $args['title'] ) $args['title'] = ' "' . esc_html($args['title']) . '"';
		$args['action'] = sprintf( wpforo_phrase('%s replied to the topic %s', false), '', '');
	}
	if( $args['content'] ) {
		$content_words = explode(' ', $args['content']);
		$content_words = count($content_words);
		$content_words_cut = apply_filters( 'wpforo_activity_content_words', '40' );
		if( (int)$content_words_cut < (int)$content_words && $args['primary_link'] ){
			$more = '... &nbsp; <a href="' . $args['primary_link'] . '">' . wpforo_phrase('read more', false) . '&raquo;</a>';
			$args['content'] = wp_trim_words( $args['content'], 40, $more );
		}
	}
	wpforo_activity( $args );
}

function wpforo_activity_content_delete( $item = array() ){
	$args = array();
	if( empty($item) ) return false;
    if( wpfval($item, 'first_postid') ){
        $args['item_id'] = $item['first_postid'];
        $args['type'] = 'wpforo_topic';
    }
    elseif( wpfval($item, 'is_first_post') ) {
		$args['item_id'] = $item['postid'];
		$args['type'] = 'wpforo_topic';
	}
	elseif( wpfval($item, 'postid') ){
		$args['item_id'] = $item['postid'];
		$args['type'] = 'wpforo_post';
	}
	if( wpfval($args, 'item_id') && wpfval($args, 'type') ) wpforo_activity_delete( $args );
}

function wpforo_activity_content_on_post_status_change( $postid, $status = 0 ) {
    if( !$postid ) return;
    $post = WPF()->post->get_post($postid);
    if(!empty($post)){
        $post['status'] = $status;
        $post['posturl'] = WPF()->post->get_post_url($postid);
        if( !wpfval($post, 'is_first_post') ){
            if( $status ){
                wpforo_activity_content_delete( $post );
            }
            else{
                wpforo_activity_content( $post );
            }
        }
    }
}
add_action( 'wpforo_post_status_update', 'wpforo_activity_content_on_post_status_change', 9, 2 );

function wpforo_activity_content_on_topic_status_change( $topicid, $status = 0 ) {
    if( !$topicid ) return;
    $topic = WPF()->topic->get_topic($topicid);
    if(!empty($topic)){
        $topic['status'] = $status;
        $topic['topicurl'] = WPF()->topic->get_topic_url($topicid);
        if( $status ){
            wpforo_activity_content_delete( $topic );
        }
        else{
            wpforo_activity_content( $topic );
        }
    }
}
add_action( 'wpforo_topic_status_update', 'wpforo_activity_content_on_topic_status_change', 9, 2 );

function wpforo_activity_like( $item = array() ){
	$args = array();
	if( empty($item) ) return false;
	if((isset($item['status']) && $item['status']) || (isset($item['private']) && $item['private']))  return false;
	if( isset($item['forumid']) && $item['forumid'] ){
		$private_for_usergroups = array(3, 4, 5);
		$private_for_usergroups = apply_filters( 'wpforo_activity_private_for_usergroups', $private_for_usergroups );
		if( !empty($private_for_usergroups) && WPF()->forum->private_forum($item['forumid'], $private_for_usergroups) ){
			return false;
		}
	}
	if( isset($item['postid']) && $item['postid'] ) $args['item_id'] = $item['postid'];
	$args['user_id'] = WPF()->current_userid;
	$member = wpforo_member( $args['user_id'] );
	$args['type'] = 'wpforo_like';
	$item = wpforo_post($item['postid']);
	if( isset($item['url']) && $item['url'] ) $args['primary_link'] = $item['url'];
	if( isset($item['title']) ) $args['title'] = preg_replace('|^.+?\:\s*|is', '', $item['title']);
	if( $args['title'] ) $args['title'] = ' "' . esc_html($args['title']) . '"';
	$args['action'] = sprintf( wpforo_phrase('%s liked forum post %s', false), '', '');
	wpforo_activity( $args );
}

function wpforo_activity_like_delete( $item = array() ){
	$args = array();
	if( empty($item) ) return false;
	if( isset($item['postid']) && $item['postid'] ){
		$args['item_id'] = $item['postid'];
		$args['type'] = 'wpforo_like';
	}
	if($args['item_id'] && $args['type']) wpforo_activity_delete( $args );
}

add_action( 'wpforo_after_add_topic', 'wpforo_activity_content', 9 );
add_action( 'wpforo_after_add_post', 'wpforo_activity_content', 9 );
add_action( 'wpforo_like', 'wpforo_activity_like', 9 );
add_action( 'wpforo_after_delete_post', 'wpforo_activity_content_delete', 9 );
add_action( 'wpforo_after_delete_post', 'wpforo_activity_like_delete', 9 );

function wpforo_user_field( $field = '', $userid = 0, $echo = true ){
	$userid = ( !$userid ) ? WPF()->current_userid : $userid;
	if( !$field || !$userid ) return false;
	$field = wpforo_member( $userid, $field );
	$field = apply_filters( 'wpforo_user_field', $field, $userid );
	if( !is_array($field) && $field ){
		if( $echo ){
			echo $field;
		}
		else{
			return $field;
		}
	}
}

/**
 * @param array $post
 * @param bool $echo
 *
 * @return string|void
 */
function wpforo_content( $post, $echo = true ){
	$content = '';
	if(is_array($post) && isset($post['body'])){
		$content = apply_filters('wpforo_content_before', $post['body'], $post);
		$content = wpforo_kses( $content, 'post' );
		$content = apply_filters('wpforo_content', $content, $post);
		$content = wpforo_content_filter( $content );
		$content = apply_filters('wpforo_content_after', $content, $post);
	}
	if( !$echo ) return $content;
	echo $content;
}

function wpforo_share_toggle( $url = '', $text = '', $location = 'side', $custom = false ){
    $set = WPF()->api->options;
    $position = (($set['sb_location_toggle'] == 'left' || $set['sb_location_toggle'] == 'right')) ? 'side' : $set['sb_location_toggle'];
    if( !$set['sb_toggle_on'] || ( $position != $location && !$custom ) ) return false;
    $location_class = ( $custom ) ? $location : $set['sb_location_toggle'];
    ?>
    <div class="wpf-sb wpf-sb-<?php echo esc_attr($location_class) ?> wpf-sb-<?php echo esc_attr($set['sb_toggle']) ?> sb-tt-<?php echo esc_attr($set['sb_toggle_type']) ?>">
        <div class="wpf-sb-toggle"><i class="fas fa-share-alt" title="<?php wpforo_phrase('Share this post') ?>"></i></div>
        <div class="wpf-sb-buttons" style="display: <?php if( $set['sb_toggle_type'] == 'collapsed' ) echo 'none'; ?>;">
            <?php do_action('wpforo_share_toggle_before', $url, $text, $location, $custom) ?>
            <?php WPF()->api->share_toggle($url, $text); ?>
            <?php do_action('wpforo_share_toggle_after', $url, $text, $location, $custom) ?>
        </div>
    </div>
    <?php
}

function wpforo_share_buttons( $location = 'bottom', $url = '', $custom = false ){
    $set = WPF()->api->options;
    if( !$set['sb_on'] || (!wpfval($set, 'sb_location', $location) && !$custom) ) return false;
    ?>
    <div class="wpf-sbtn wpf-sb-<?php echo esc_attr($location) ?> wpf-sb-style-<?php echo esc_attr($set['sb_style']) ?>" style="display: block">
        <div class="wpf-sbtn-title"><i class="fas fa-share-alt"></i> <span><?php wpforo_phrase('Share:') ?></span></div>
        <div class="wpf-sbtn-wrap">
            <?php do_action('wpforo_share_buttons_before', $location, $url, $custom ) ?>
            <?php WPF()->api->share_buttons($url); ?>
            <?php do_action('wpforo_share_buttons_after', $location, $url, $custom ) ?>
        </div>
        <div class="wpf-clear"></div>
    </div>
    <?php
}

function wpforo_page(){
    $page_template = ( wpfval($_GET, 'view') ) ? sanitize_title($_GET['view']) : false;
    do_action('wpforo_page', $page_template );
}

function wpforo_admin_note(){

    $display = false;
    $templates = WPF()->tools_misc['admin_note_pages'];
    $usergroups = WPF()->tools_misc['admin_note_groups'];

    if( !wpfval(WPF()->tools_misc, 'admin_note_pages') ) return false;
    if( !wpfval(WPF()->tools_misc, 'admin_note_groups') ) return false;

    if( in_array(WPF()->current_user_groupid, $usergroups) ){
        if( wpfval(WPF()->current_object, 'template') && in_array(WPF()->current_object['template'], $templates) ){
            $display = true;
        } else {
            $display = false;
        }
    }
    if( $display ){
        $note = wpforo_kses( wpforo_unslashe( trim( WPF()->tools_misc['admin_note'] ) ) );
        $note = apply_filters( 'wpforo_admin_note', $note );
        if( $note ){
            ?><div class="wpforo-admin-note"><?php echo wpautop( $note ) ?><div class="wpf-clear"></div></div><?php
        }
    }

}
add_action('wpforo_header_hook', 'wpforo_admin_note', 1 );

function wpforo_topic_icon( $topic, $type = 'all', $color = true, $echo = true, $wrap = '%s' ){
    $html = '';
    if( is_numeric($topic) ) $topic = wpforo_topic($topic);
    if( $type == 'mixed' ) {
        if( !$icon = WPF()->tpl->icon('topic', $topic, false) ){
            $icon = WPF()->tpl->icon_base( $topic['posts'] );
            $icon = implode(' ', $icon);
        }
        $html = sprintf( $wrap,'<i class="fa-1x ' . esc_attr( $icon ) . '"></i>');
    }
    else {
        if( ($type == 'all' || $type == 'base') && wpfkey($topic, 'posts') ){
            $icon = WPF()->tpl->icon_base( $topic['posts'] );
            $icon = implode(' ', $icon);
            $html .= sprintf( $wrap, '<i class="fa-1x ' . esc_attr( $icon ) . '"></i>');
        }
        if( $type == 'all' || $type == 'status' ) {
            $icon = WPF()->tpl->icon_status( $topic );
            if( !empty($icon) ){
                $html = '';
                foreach( $icon as $i ){
                    if( !$color ) $i['color'] = '';
                    $classes = $i['class'] . ' ' . $i['color'];
                    $html .= sprintf( $wrap,'<i class="fa-1x ' . esc_attr( $classes ) . '" title="' . esc_attr($i['title']) . '"></i>');
                }
            }
        }
    }

    if( $echo ) echo $html; else return $html;
}

function wpforo_topic_icons( $topic, $type = 'all' ){
    $icon = array();
    if( is_numeric($topic) ) $topic = wpforo_topic($topic);
    if( $type == 'mixed' ) {
        $icon = WPF()->tpl->icon('topic', $topic, false);
    }
    else {
        if( ($type == 'all' || $type == 'base') && wpfkey($topic, 'posts') ){
            $icon_base = WPF()->tpl->icon_base( $topic['posts'] );
            if( !empty($icon_base) && is_array($icon_base) ) $icon = array( 'base' => $icon_base );
        }
        if( $type == 'all' || $type == 'status' ) {
            $icon_status = WPF()->tpl->icon_status( $topic );
            if( !empty($icon_status) && is_array($icon_status) ) $icon = $icon_status;
        }
        $icon = array_filter($icon);
    }
    return $icon;
}

function wpforo_tags( $topic, $wrap = true, $type = 'medium', $count = false ){
    if( is_numeric($topic) && $topic > 0) {
        $topic = wpforo_topic($topic);
    }
    if( wpfval($topic, 'tags') ){
        $tags = WPF()->topic->sanitize_tags($topic['tags'],true);
        if( !empty($tags) ){
            if( $wrap ){
                ?>
                <div class="wpforo-post wpforo-tags wpfcl-1">
                    <?php if($type != 'text'): ?>
                        <div class="wpf-tags-title">
                            <i class="fas fa-tag"></i> <span class="wpf-ttt"><?php wpforo_phrase('Topic Tags'); ?></span>
                        </div>
                    <?php endif; ?>
                    <div class="<?php if( $type != 'text' ) echo 'wpf-tags' ; ?> wpf-tags-<?php echo esc_attr($type) ?>">
                        <?php if($type == 'text'): ?><i class="fas fa-tag"></i> <span class="wpf-ttt"><?php wpforo_phrase('Topic Tags'); ?>:&nbsp; <?php endif; ?></span>
                        <?php foreach( $tags as $tag ): ?>
                            <?php $item = wpforo_tag($tag) ?>
                            <tag wpf-tooltip="<?php echo esc_attr(wpforo_phrase('Topic Tag',false)); ?>"><a href="<?php echo wpforo_home_url() . '?wpfin=tag&wpfs=' . $tag ?>"><?php echo esc_html($tag); ?><?php if($count && wpfval($item, 'count') && !$topic['status']) echo ' (' . $item['count'] . ')'; ?></a></tag><?php if($type == 'text') echo '<sep>,</sep> '; ?>
                        <?php endforeach; ?>
                    </div>
                    <div class="wpf-clear"></div>
                </div>
                <?php
            } else {
                ?>
                <div class="<?php if( $type != 'text' ) echo 'wpf-tags'; ?> wpf-tags-<?php echo esc_attr($type) ?>">
                    <?php foreach( $tags as $tag ): ?>
                        <?php $item = wpforo_tag($tag) ?>
                        <tag wpf-tooltip="<?php echo esc_attr(wpforo_phrase('Topic Tag',false)); ?>"><a href="<?php echo wpforo_home_url() . '?wpfin=tag&wpfs=' . $tag ?>"><?php echo esc_html($tag); ?><?php if($count && wpfval($item, 'count') && !$topic['status']) echo ' (' . $item['count'] . ')'; ?></a></tag>
                    <?php endforeach; ?>
                </div>
                <?php
            }
        }
    }
}

function wpforo_topic_rel( $topic ){
    if( !empty($topic) ){
        if( wpfval($topic, 'tags') ){
            $html = '';
            $args = array();
            $wheres = array();
            $tags = WPF()->topic->sanitize_tags($topic['tags'],true);
            if( !empty($tags) ){
                foreach( $tags as $tag ){
	                if($tag) $wheres[] = "FIND_IN_SET('" . esc_sql($tag) . "', tags)";
                }
	            if($wheres) $args['where'] = '(' . implode(' OR ', $wheres) . ')';

	            $args['order'] = 'DESC';
                $args['row_count'] = 5;
                $args['orderby'] = 'modified';
                $args['forumid'] = apply_filters('wpforo_related_topics_by_forums', $topic['forumid']);
                $args['exclude'] = array( $topic['topicid'] );
                $args = apply_filters('wpforo_related_topics_args', $args);
                $topics = WPF()->topic->get_topics($args);
                if( !empty($topics) ){
                    $html .= '<div class="wpf-rel-title"><i class="fas fa-clone"></i> '.wpforo_phrase('Related Topics', false).'</div><ul class="wpf-rel-topics">';
                    foreach( $topics as $item ){
                        $data = wpforo_topic($item['topicid']);
                        $html .= '<li>' . wpforo_topic_icon( $item, 'all', true, false ) . ' 
                                <a href="' . esc_url( $data['url'] ) . '" title="'.esc_attr($item['title']).'">' . esc_html($item['title']) . '</a> 
                                <div class="wpf-rel-date">' . wpforo_date( $item['modified'], 'ago', false ) . '</div>
                                <div class="wpf-clear"></div>
                             </li>';
                    }
                    $html .= '</ul>';
                    echo '<div class="wpf-rel-wrap">' . $html . '</div>';
                } else {
                    echo '<div class="wpf-no-rel"></div>';
                }
            }
        }
    }
}

function wpforo_topic_navi( $topic ){
    if( !empty($topic) ){
        if( wpfval($topic, 'topicid') && wpfval($topic, 'forumid') ){
            $all_html = ''; $prev_html = ''; $next_html = '';
            $navi_topics = WPF()->db->get_col("SELECT `topicid` FROM `". WPF()->tables->topics ."` WHERE ( `topicid` = IFNULL((SELECT min(`topicid`) FROM `". WPF()->tables->topics ."` WHERE `topicid` > " . intval($topic['topicid']) . " AND `forumid` = " . intval($topic['forumid']) . " AND `status` = 0 AND `private` = 0),0) OR `topicid` = IFNULL((SELECT max(`topicid`) FROM `". WPF()->tables->topics ."` WHERE `topicid` < " . intval($topic['topicid']) . " AND `forumid` = " . intval($topic['forumid']) . " AND `status` = 0 AND `private` = 0),0) ) ");
            if( !empty($navi_topics) ){
                $prev = ( wpfkey($navi_topics, 0) ) ? $navi_topics[0] : false;
                $next = ( wpfkey($navi_topics, 1) ) ? $navi_topics[1] : false;
                if( $prev && !$next ){
                    if( $topic['topicid'] < $prev ){
                        $next = $prev; $prev = false;
                    }
                }
                if( $prev ){
                    $prev_data = wpforo_topic( $prev );
                    if( !empty($prev_data) ){
                        $prev_html = '<a href="' . esc_url( $prev_data['url'] ) . '" title="'.esc_attr($prev_data['title']).'"><i class="fas fa-chevron-left"></i>&nbsp; ' . wpforo_phrase('Previous Topic', false) . '</a>';
                    }
                }
                if( $next ){
                    $next_data = wpforo_topic( $next );
                    if( !empty($next_data) ){
                        $next_html = '<a href="' . esc_url( $next_data['url'] ) . '" title="'.esc_attr($next_data['title']).'">' . wpforo_phrase('Next Topic', false) . ' &nbsp;<i class="fas fa-chevron-right"></i></a>';
                    }
                }
                if( wpfval( WPF()->current_object, 'forum') && wpfval( WPF()->current_object, 'forum', 'url') ){
                    $forum_url = WPF()->current_object['forum']['url'];
                    $forum_title = WPF()->current_object['forum']['title'];
                } else {
                    $forum_url = wpforo_forum( $topic['forumid'], 'url' );
                    $forum_title = wpforo_forum( $topic['forumid'], 'title' );
                }
                if( $forum_url ){
                    $all_html = '<a href="' . esc_url( $forum_url ) . '" title="' . esc_attr($forum_title) . '"><i class="fas fa-list"></i>&nbsp; ' . wpforo_phrase('All forum topics', false) . '</a>';
                }
                if( $prev_html || $next_html ){
                    $html = '<div class="wpf-topic-all wpf-navi-item">' . $all_html . '</div>
                                  <div class="wpf-topic-prnx">
                                        <div class="wpf-topic-prev wpf-navi-item">' . $prev_html . '</div>
                                        <div class="wpf-topic-next wpf-navi-item">' . $next_html . '</div>
                             </div><div class="wpf-clear"></div>';
                    echo '<div class="wpf-navi-wrap">' . $html . '</div>';
                }
            }
        }
    }
}

function wpforo_topic_visitors( $topic ){
    if( !empty($topic) ){
        $html = '';
        $users = '';
        $guests = '';
        $users_visited = '';
        $visitors = WPF()->log->visitors( $topic );
        if( !empty( $visitors ) ){
            if( wpfval( $visitors, 'users') ){
                if( wpfval( $visitors, 'users', 'viewing') && WPF()->post->options['display_current_viewers'] ){
                    $count = count($visitors['users']['viewing']);
                    if( $count ){
                        if( $count > 1 ){
                            $users = wpforo_phrase('%d users ( %s )', false);
                        } elseif( $count == 1 ) {
                            $users = wpforo_phrase('%d user ( %s )', false);
                        }
                        $user_html = array();
                        foreach( $visitors['users']['viewing'] as $user ){
                            if( wpfval( $user, 'userid' ) ){
                                $member = wpforo_member( $user['userid'] );
                                if( wpfval($member, 'display_name') ) {
                                    $user_html[] = wpforo_member_link($member, '', 30, 'wpf-topic-visitor-link', false );
                                }
                            }
                        }
                        $user_html = implode(', ', $user_html );
                        $users = sprintf( $users, $count, $user_html );
                    }
                    $users = apply_filters( 'wpforo_topic_viewing_users', $users, $visitors['users']['viewing'] );
                }
                if( wpfval( $visitors, 'users', 'viewed') && WPF()->post->options['display_recent_viewers'] ){
                    $users_visited = wpforo_phrase( 'Recently viewed by users: %s.', false );
                    $track_users_link = array();
                    foreach( $visitors['users']['viewed'] as $user ){
                        if( wpfval( $user, 'userid' ) ){
                            $member = wpforo_member( $user['userid'] );
                            if( wpfval($member, 'display_name') ) {
                                $track_users_link[] = wpforo_member_link($member, '', 30, 'wpf-topic-visitor-link', false ) . ' ' . wpforo_date( $user['time'], 'ago', false );
                            }
                        }
                    }
                    $track_users_link = implode(', ', $track_users_link );
                    $users_visited = sprintf( $users_visited, $track_users_link );
                    $users_visited = '<p class="wpf-viewed-users"><i class="fas fa-walking"></i> ' . $users_visited . '</p>';
                    $users_visited = apply_filters( 'wpforo_topic_viewed_users', $users_visited, $visitors['users']['viewed'] );
                }
            }
            if( wpfval( $visitors, 'guests') && WPF()->post->options['display_current_viewers'] ){
                $count = count($visitors['guests']);
                if( $count > 1 ){
                    $guests = sprintf( wpforo_phrase('%s guests', false), $count );
                } elseif( $count == 1 ) {
                    $guests = sprintf( wpforo_phrase('%s guest', false), $count );
                }
            }
            if( $users || $guests ){
                $and = ( $users && $guests ) ? wpforo_phrase('and', false, 'lower') : '' ;
                $html .= '<p class="wpf-viewing-users"><i class="fas fa-male"></i> ' . sprintf( wpforo_phrase('Currently viewing this topic %s %s %s.', false), $users, $and, $guests) . '</p>';
            }
            $html .= $users_visited;
            $html = apply_filters( 'wpforo_topic_visitors_info', $html, $visitors );
        }
        echo $html;
    }
}

function wpforo_viewing( $item, $echo = true ){
    if( !empty($item) && WPF()->forum->options['display_current_viewers'] ){
        $phrase = wpforo_phrase('(%d viewing)', false, 'lower');
        $visitors = WPF()->log->visitors( $item );
        $users = ( wpfval($visitors, 'users', 'viewing') ) ? count($visitors['users']['viewing']) : 0;
        $guests = ( wpfval($visitors, 'guests') ) ? count($visitors['guests']) : 0;
        $viewing = (int) $users + (int) $guests;
        if( $viewing > 0 ){
            $phrase = '<span class="wpf-viewing">' . sprintf( $phrase, $viewing ) . '</span>';
            if( $echo ){
                echo $phrase;
            } else {
                return $phrase;
            }
        }
    }
}

function wpforo_topic_footer(){
    if( wpfval( WPF()->current_object, 'topic') && wpfval( WPF()->current_object, 'template') && WPF()->current_object['template'] == 'post' ){
        $topic = WPF()->current_object['topic'];
        ?>
        <div class="wpforo-topic-footer wpfbg-9">
            <div class="wpf-topic-navi">
                <?php wpforo_topic_navi( $topic ) ?>
            </div>
            <div class="wpf-topic-rel">
                <?php wpforo_topic_rel( $topic ) ?>
            </div>
            <div class="wpf-tag-list">
                <?php wpforo_tags( $topic, true, 'text', true ) ?>
            </div>
            <div class="wpf-topic-visitors">
                <?php wpforo_topic_visitors( $topic ) ?>
            </div>
        </div>
        <?php
    }
}

add_action('wpforo_post_list_footer', 'wpforo_topic_footer' );

function wpforo_thread( $topicid ){

    $thread = wpforo_topic($topicid);

    $thread['icons'] = '';
    $thread['icons_html'] = '';
    $thread['users_html'] = '';
    $thread['forum'] = wpforo_forum( $thread['forumid'] );
    $thread['user'] = wpforo_member($thread);
    $thread['last_post'] = (wpfval($thread, 'last_post')) ? wpforo_post($thread['last_post']) : [];
    $thread['last_user'] = (wpfval($thread, 'last_post')) ? wpforo_member($thread['last_post']) : [];
    $thread['replies'] = (intval($thread['posts']) - 1);
    $thread['last_post_date'] = wpforo_date($thread['modified'],'ago-date', false);
	$thread['last_post_url'] = wpfval($thread, 'last_post', 'url');
    $thread['user_info'] = esc_attr(sprintf(wpforo_phrase('Created by %s', false), wpfval($thread['user'], 'display_name')));
    $thread['reply_user_info'] = (wpfval($thread, 'last_user', 'display_name')) ? esc_attr(sprintf(wpforo_phrase('Last reply by %s', false), $thread['last_user']['display_name'])) : '';
    $thread['icons'] = wpforo_topic_icons($thread, 'all');
    $thread['wrap'] = (count($thread['icons']) > 3) ? ' style="flex-wrap: wrap;"' : '';

    if(!empty($thread['icons'])){
        foreach( $thread['icons'] as $icon ){
            $thread['icons_html'] .= '<span class="wpf-circle wpf-s wpfcl-3 ' . str_replace('wpfcl-', 'wpfbg-', $icon['color']) .'" wpf-tooltip="'. esc_attr(wpforo_phrase($icon['title'], false)) .'" wpf-tooltip-position="top" wpf-tooltip-size="small"><i class="'. esc_attr(str_replace( '-circle', '', $icon['class']) ).'"></i></span>';
        }
    }

    if(!empty($thread['user']) || !empty($thread['last_user'])) {
        $thread['usergroup_can_va'] = WPF()->perm->usergroup_can('va');
        $thread['feature_avatars'] = wpforo_feature('avatars');
        $thread['users_html'] .= '<div class="wpf-thread-users-avatars">';
        if( $thread['usergroup_can_va'] && $thread['feature_avatars'] ) {
            if( !empty( $thread['user'] ) ) {
                $thread['user_avatar'] = WPF()->member->avatar($thread['user'], 'alt="'.esc_attr($thread['user']['display_name']).'"', 40);
                $thread['users_html'] .= '<div class="wpf-circle wpf-m">
                        <a href="' . esc_url( $thread['url'] ) . '" wpf-tooltip="' . $thread['user_info'] . '" wpf-tooltip-position="top" wpf-tooltip-size="medium">' . $thread['user_avatar'] . '</a>
                    </div>';
            }
            if( $thread['replies'] && !empty( $thread['last_user'] ) ) {
                $thread['last_user_avatar'] = WPF()->member->avatar($thread['last_user'], 'alt="'.esc_attr($thread['last_user']['display_name']).'"', 24);
                $thread['users_html'] .= '<div class="wpf-circle wpf-s">
                        <a href="' . esc_url( $thread['last_post_url'] ) . '" wpf-tooltip="' . $thread['reply_user_info'] . '" wpf-tooltip-position="top" wpf-tooltip-size="medium">' . $thread['last_user_avatar'] . '</a>
                    </div>';
            }
        } else {
            $thread['users_html'] .= wpforo_member_link( $thread['user'], 'by', 9, '', false );
        }
        $thread['users_html'] .= '</div>';
    }

    return $thread;

}

/**
 * @param string $type
 * @param array $buttons
 * @param array $forum
 * @param array $topic
 * @param array $post
 * @param bool $echo
 *
 * @return string
 */
function wpforo_post_buttons($type = 'icon-text', $buttons = array(), $forum = array(), $topic = array(), $post = array(), $echo = true){
    $buttons = WPF()->tpl->buttons( $buttons, $forum, $topic, $post, false );
    if( $type === 'icon' ){
        $buttons = preg_replace('#</i>[\r\n\t\s\0]*<span[^<>]*>.*?</span>#isu', '</i>', $buttons);
    }elseif( $type === 'text' ){
        $buttons = preg_replace('#<i[^<>]*>[\r\n\t\s\0]*</i>[\r\n\t\s\0]*#isu', '', $buttons);
        $buttons = preg_replace('#\swpf-tooltip=[\'\"][^\'\"]+?[\'\"]#isu', '', $buttons);
    }else{
        $buttons = preg_replace('#\swpf-tooltip=[\'\"][^\'\"]+?[\'\"]#isu', '', $buttons);
    }
    if($echo) echo $buttons;
    return $buttons;
}

function wpforo_like_button( $post = array(), $type = 'icon-count', $echo = true ){
    $login = is_user_logged_in();
    $button_html = '';
    $forumid = (isset($post['forumid'])) ? $post['forumid'] : 0;
    $postid = (isset($post['postid'])) ? $post['postid'] : 0;
    if( WPF()->perm->forum_can('l', $forumid) && $login && WPF()->current_userid != $post['userid'] ) {
        $like_status = ( WPF()->post->is_liked( $postid, WPF()->current_userid ) === FALSE ? 'wpforo-like' : 'wpforo-unlike' );
        $like_icon = ( $like_status == 'wpforo-like') ? 'far' : 'fas';
        $icon = ( $type == 'icon' || $type == 'icon-text' || $type == 'icon-count' ) ? '<i class="'. esc_attr($like_icon) .' fa-thumbs-up wpfsx wpforo-like-ico"></i>' : '';
        $number = ( $type == 'icon-count' ) ? '<span class="wpf-like-count">' . intval($post['likes_count']) . '</span>' : '';
        $phrase = ( $type == 'text' || $type == 'icon-text' ) ? '<span class="wpforo-like-txt">' . wpforo_phrase( str_replace('wpforo-', '', $like_status), false) . '</span>' : '';
        $button_html = '<span class="wpf-action '. $like_status .'" data-postid="'. wpforo_bigintval($postid) .'">' .'<span class="wpf-like-icon" wpf-tooltip="'.esc_attr(wpforo_phrase( str_replace('wpforo-', '', $like_status), false)).'">'. $icon .'</span>'. $phrase . $number . '</span>';
    }
    if(!$echo) return $button_html; echo $button_html;
}

function wpforo_post_likers( $postid ){
    if( $postid ){
        $likers = '<div class="bleft">' . WPF()->tpl->likers( $postid ) . '</div>';
        $likers = apply_filters('wpforo_post_likers', $likers, $postid);
    }
    echo $likers;
}

function wpforo_thread_breadcrumb( $post = array(), $parents = array() ){
    $html = ''; $gab = false;
    if( wpfval($post, 'parentid') ){
        $html .= '<i class="fas fa-reply fa-rotate-180"></i>';
        $parent = wpforo_post($post['parentid']);
        $member = wpforo_member($parent);
        $parent_url = ( wpfval($parent, 'url') ) ? $parent['url'] : '#post-' . $parent['parentid'] ;
        $avatar = WPF()->member->avatar( $member, 'alt="'.esc_attr($member['display_name']).'"', 18 );
        $member_name = ( wpfval($member, 'display_name') ) ? $member['display_name'] : wpforo_phrase('Guest', false);
        $html .= '<div class="wpf-reply-to wpf-tree-item"><a href="' . esc_url($parent_url) . '"><em>' . wpforo_phrase('Reply to', false ) .'</em>'. $avatar . '<span>' . $member_name . '</span></a></div>';
        if( wpfval($parents, $post['parentid']) ){
            $topic = wpforo_topic( $post['topicid'] );
            $limit = apply_filters('wpforo_thread_breadcrumb_limit', 3);
            $items = array_reverse( $parents[ $post['parentid'] ] );
            $last = key( array_slice( $items, -1, 1, TRUE ) );
            foreach( $items as $key => $parentid ){
               if( $key < $limit || $key == $last ){
                    $parent = wpforo_post($parentid);
                    $starter = ( $topic['userid'] == $parent['userid'] ) ? true : false;
                    $class = ( $starter ) ? ' wpf-starter' : '';
                    if( !empty($parent) ){
                        $member = wpforo_member($parent);
                        $name = (wpfval($member, 'display_name')) ? $member['display_name'] : '';
                        $tooltip = ( $starter ) ? ' wpf-tooltip="' . esc_attr(wpforo_phrase('Topic Author', false) . ' - ' . $name ) . '" wpf-tooltip-size="medium"' : ' wpf-tooltip="' . esc_attr( wpforo_phrase('Reply by', false) . ' ' . $name ) . '" wpf-tooltip-size="medium"';
                        $parent_url = ( wpfval($parent, 'url') ) ? $parent['url'] : '#post-' . $parent['parentid'] ;
                        $avatar = WPF()->member->avatar( $member, 'alt="'.esc_attr($member['display_name']).'"', 18 );
                        if(!$gab) $html .= '<i class="fas fa-angle-right wpf-tree-sep"></i>';
                        $html .= '<div class="wpf-tree-item' . $class . '" ' . $tooltip . '><a href="' . esc_url($parent_url) . '">'. $avatar .'</a></div>';
                    }
                } else{
                    if(!$gab) $html .= '<i class="fas fa-ellipsis-h"></i>'; $gab = true;
                }
            }
        }
    }
    echo $html;
}

function wpforo_check_threads( $posts = array() ){
    $post = array_shift($posts );
    if( wpfkey($post, 'root') ){
        if( is_null($post['root']) && wpfval($post, 'topicid') ){
            WPF()->topic->rebuild_threads( $post['topicid'] );
            wpforo_clean_cache();
        }
    }
}

function wpforo_posts_ordering_dropdown($orderby = null, $topicid = null){
	WPF()->tpl->posts_ordering_dropdown($orderby, $topicid);
}

function wpforo_template_pagenavi( $class = '', $permalink = true, $paged = null, $items_count = null, $items_per_page = null ) {
	WPF()->tpl->pagenavi( $paged, $items_count, $items_per_page, $permalink, $class );
}

function wpforo_template_add_topic_button($forumid = null){
    if( !wpforo_is_bot() ) WPF()->tpl->add_topic_button($forumid);
}

function wpforo_feed_rss2_url($echo = true, $general = false){
	WPF()->feed->rss2_url($echo, $general);
}

function wpforo_feed_link( $type = 'topic' ){

    $nofollow = apply_filters('wpforo_feed_link_nofollow', false );
	$nofollow = ( $nofollow ) ? ' rel="nofollow" ' : '';

	if( wpforo_feature('rss-feed') ): ?>

        <?php if($type == 'topic'): ?>
            <a href="<?php WPF()->feed->rss2_url(); ?>" <?php echo $nofollow ?> class="wpfcl-2" title="<?php wpforo_phrase('Topic RSS Feed') ?>" target="_blank">
                <span class="wpfcl-3">RSS</span> <i class="fas fa-rss wpfsx wpfcl-3"></i>
            </a>
        <?php elseif( $type == 'forum' ): ?>
            <span class="wpf-feed">|
                <a href="<?php wpforo_feed_rss2_url() ?>" <?php echo $nofollow ?> title="<?php wpforo_phrase('Forum RSS Feed') ?>" target="_blank">
                    <span style="text-transform: uppercase;">
                        <?php wpforo_phrase('RSS') ?>
                    </span>
                    <i class="fas fa-rss wpfsx"></i>
                </a>
            </span>
	    <?php elseif( $type == 'home' ): ?>
            <sep> &nbsp;|&nbsp; </sep>
            <span class="wpf-feed-forums">
                <a href="<?php wpforo_feed_rss2_url(true, 'forum') ?>" <?php echo $nofollow ?> title="<?php wpforo_phrase('Forums RSS Feed') ?>" target="_blank">
                    <span><?php wpforo_phrase('Forums') ?></span> <i class="fas fa-rss wpfsx"></i>
                </a>
                </span><sep> &nbsp;|&nbsp; </sep>
            <span class="wpf-feed-topics">
                <a href="<?php wpforo_feed_rss2_url(true, 'topic') ?>" <?php echo $nofollow ?> title="<?php wpforo_phrase('Topics RSS Feed') ?>" target="_blank">
                    <span><?php wpforo_phrase('Topics') ?></span> <i class="fas fa-rss wpfsx"></i>
                </a>
            </span>
	    <?php endif; ?>
    <?php endif;
}

function wpforo_template_topic_portable_form($forumid = null){
	WPF()->tpl->topic_form_forums_selectbox($forumid);
}

function wpforo_notifications(){
    WPF()->activity->notifications();
}

function wpforo_unread_url( $topicid = 0, $url = '', $echo = true, $force = false ){
    //Only for loggedin users
	$enabled = true;
    if( !$force ){
	    $enabled = wpforo_feature('goto-unread') ? true : false;
    }
    if( WPF()->current_userid && $enabled && $topicid && $url ){
        //Get read topics
	    $topics = WPF()->log->get_read_topics();
	    if( $last_read_id = wpfval($topics, $topicid) ){
	        //Make sure the post still located in current topic. When admin split a topic
            //and move the last reply to other topic, the users unread topic array isn't updated,
            //It's not reasonable to update usermeta of each user, so it's better to check
            //the post topic ID here. if the reply is split and moved to other topic use the
            //topic last post id.
		    $last_read_post = wpforo_post($last_read_id);
		    if( $topicid == wpfval($last_read_post, 'topicid') ){
			    $first_unread_id = 0;
			    $jump_to_first_unread = apply_filters('wpforo_jump_to_first_unread', true);
			    if( $jump_to_first_unread ) {
				    //Get first unread postid
				    $first_unread_id = WPF()->post->next_post( $last_read_id, $topicid );
			    }
			    //Decide to whether execute more SQLs and create direct URLs or not
			    $direct_url = apply_filters('wpforo_build_direct_unread_post_url', false);
			    //Create new URLs
			    if( $first_unread_id ){
				    //Change URL to first unread post
				    if( $direct_url ) {
					    $url = wpforo_post($first_unread_id, 'url');
				    } else {
					    $url = (strpos($url, '#') !== FALSE) ? preg_replace('|\#.+$|', '#post-' . intval($first_unread_id) , $url) : $url . '#post-' . intval($first_unread_id);
				    }
			    } else{
				    //Change URL to last read post
				    if( $direct_url ) {
					    $url = wpforo_post($last_read_id, 'url');
				    } else {
					    $url = (strpos($url, '#') !== FALSE) ? preg_replace('|\#.+$|', '#post-' . intval($last_read_id) , $url) : $url . '#post-' . intval($last_read_id);
				    }
			    }
            }
		    elseif( $last_postid = wpforo_topic($topicid, 'last_post') ){
			    $url = wpforo_post($last_postid, 'url');
            }
	    }
    } else {
        $direct_topic_url = apply_filters( 'wpforo_direct_topic_url', true );
        if( $direct_topic_url ){
            $url = preg_replace( '|\#post\-\d+|i', '', $url);
            $url = WPF()->strip_url_paged_var($url);
        }
    }
    if( !$echo ) return esc_url($url);
    echo esc_url($url);
}

function wpforo_unread_button( $topicid = 0, $url = '', $echo = true, $postid = 0 ){
	$button = '';
	if( !$postid && $topicid && $url && WPF()->current_userid && wpforo_feature('goto-unread-button') ){
        $unread = wpforo_unread( $topicid, 'topic', false );
        if( $unread ){
            $button_link = apply_filters('wpforo_jump_to_unread_button_link', false);
            $button_text = str_replace(array('{','}'), '', wpforo_phrase('{new}', false) );
            if( wpforo_feature('goto-unread') ){
                if( $button_link ){
                    $url = wpforo_unread_url( $topicid, $url, false, true );
                }
            } else {
                $url = wpforo_unread_url( $topicid, $url, false, true );
                $button_link = true;
            }
            $button = ( $button_link ) ? '<a href="' . $url . '" class="wpf-new-button" title="'. esc_attr( wpforo_phrase('Go to first unread post', false) ).'">' . $button_text . '</a>' : '<span class="wpf-new-button">' . $button_text . '</span>';
        }
    }
	elseif( $postid && $topicid && WPF()->current_userid ){
        $unread = wpforo_unread( $topicid, 'post', false, $postid );
        if( $unread ){
            $button_text = str_replace(array('{','}'), '', wpforo_phrase('{new}', false) );
            $button =  '<span class="wpf-new-button">' . $button_text . '</span>';
        }
    }
	if( !$echo ) return $button;
	echo $button;
}


/**
 * @param array|string $forum
 * @param string $before
 * @param string $after
 * @param bool $echo
 *
 * @return string
 */
function wpforo_forum_title($forum, $before = '', $after = '', $echo = true){
	$title = is_array($forum) ? (string) wpfval($forum, 'title') : $forum;
	$title = esc_html($title);
    $title = apply_filters('wpforo_forum_title', $title, $forum);
	if($title) $title = $before . $title . $after;
	if($echo) echo $title;
	return $title;
}

/**
 * @param array|string $forum
 * @param string $before
 * @param string $after
 * @param bool $echo
 *
 * @return string
 */
function wpforo_forum_description($forum, $before = '', $after = '', $echo = true){
	$description = is_array($forum) ? (string) wpfval($forum, 'description') : $forum;
    $description = apply_filters('wpforo_forum_description', $description, $forum);
    if($description) $description = $before . $description . $after;
	if($echo) echo $description;
	return $description;
}

function wpforo_admin_cpanel(){
    if( WPF()->current_object['template'] === 'forum' && wpforo_feature('admin-cp') && wpforo_current_user_is( 'admin' ) ){
        $toggle = get_user_meta( WPF()->current_userid, 'wpf-acp-toggle', true );
	    if( !$toggle || $toggle === 'open' ){
		    $display = 'block';
		    $title = wpforo_phrase('Close', false);
		    $icon = '<i class="fas fa-minus-square"></i>';
	    } else {
		    $display = 'none';
		    $title = wpforo_phrase('Open', false);
		    $icon = '<i class="fas fa-plus-square"></i>';
        }
        ?>
        <div class="wpf-admincp">
            <div class="wpf-acp-header">
                <div class="wpf-acp-title"><i class="fas fa-cog"></i> <?php wpforo_phrase('Admin Control Panel') ?></div>
                <div class="wpf-acp-toggle" wpf-tooltip="<?php echo esc_attr($title) ?>"><?php echo $icon ?></div>
            </div>
            <div class="wpf-acp-body" style="display: <?php echo $display ?>;">
                <div class="wpf-acp-content">
                    <a href="<?php echo admin_url('admin.php?page=wpforo-forums'); ?>" class="wpf-button-secondary"><i class="fas fa-plus"></i>&nbsp; <?php wpforo_phrase('Add New Category or Forum') ?></a>
                    <p class="wpf-acp-forum-info">
				        <?php $layouts = '(<a href="https://wpforo.com/docs/categories-and-forums/forum-layouts/extended-layout/" target="_blank">Extended</a>, 
                                                <a href="https://wpforo.com/docs/categories-and-forums/forum-layouts/simplified-layout/" target="_blank">Simplified</a>, 
                                                  <a href="https://wpforo.com/docs/categories-and-forums/forum-layouts/question-answer-layout/" target="_blank">Q&A</a>, 
                                                    <a href="https://wpforo.com/docs/root/categories-and-forums/forum-layouts/threaded-layout/" target="_blank">Threaded</a>)';
				        $layout = '<a href="https://wpforo.com/docs/root/categories-and-forums/forum-layouts/" target="_blank">' . wpforo_phrase('the layout you want', false, 'lower') . '</a>';
				        ?>
				        <?php echo sprintf( wpforo_phrase('Please note, that forums can be displayed with different layouts %1$s, just edit the top category (blue panel) and set %2$s. Child forums inherit the top category (blue panel) layout.', false), $layouts, $layout ); ?>
                    </p>
                </div>
                <div class="wpf-acp-footer">
                    <a href="<?php echo admin_url('admin.php?page=wpforo-settings&tab=styles'); ?>" class="wpf-button-secondary"><i class="fas fa-paint-brush"></i> <?php wpforo_phrase('Change Color Style') ?></a>
                    <a href="<?php echo admin_url('admin.php?page=wpforo-moderations'); ?>" class="wpf-button-secondary"><i class="fas fa-tasks"></i> <?php wpforo_phrase('Post Moderation') ?></a>
                    <a href="<?php echo admin_url('admin.php?page=wpforo-tools'); ?>" class="wpf-button-secondary"><i class="fas fa-shield-alt"></i> <?php wpforo_phrase('Antispam') ?></a>
			        <?php $menu = wp_get_nav_menu_object('wpforo-navigation'); ?>
			        <?php if(!empty($menu) && isset($menu->term_id)): ?>
                        <a href="<?php echo admin_url('nav-menus.php?action=edit&menu=' . intval($menu->term_id) ); ?>" class="wpf-button-secondary"><i class="fas fa-bars"></i> <?php wpforo_phrase('Forum Menu') ?></a>
			        <?php endif; ?>
                    <a href="<?php echo admin_url('widgets.php'); ?>" class="wpf-button-secondary"><i class="far fa-window-restore"></i> <?php wpforo_phrase('Forum Widgets') ?></a>
                    <a href="<?php echo wp_nonce_url(wpforo_home_url('?wpfaction=wpforo_reset_all_caches'), 'wpforo_reset_cache') ?>" class="wpf-button-secondary" style="color: #e22d00 !important;"><i class="fas fa-times"></i> <?php wpforo_phrase('Delete Forum Cache') ?></a>
                </div>
            </div>
        </div>
        <?php
    }
}

/**
 * show member template
 */
function wpforo_member_template(){
	WPF()->tpl->member_template();
}

/**
 * show member menu
 */
function wpforo_member_tabs(){
	WPF()->tpl->member_menu();
}

/**
 * get current object user or empty array
 *
 * @return array
 */
function wpforo_get_current_object_user(){
    return WPF()->current_object['user'];
}

/**
 * @param array|string $template
 *
 * @return bool
 */
function wpforo_is_member_template($template = null){
    WPF()->tpl->init_member_tpls();
	if( $template = WPF()->tpl->get_template($template) ) return wpfkey(WPF()->member_tpls, $template['key']);
	return false;
}

/**
 * show wpforo template
 *
 * @param array|string $template
 */
function wpforo_template($template = null){
    $template = apply_filters('wpforo_template', $template);
    if( !$template ) $template = WPF()->current_object['template'] ? WPF()->current_object['template'] : '404';
	if( WPF()->tpl->can_view_template($template, WPF()->current_object['user']) ){
        if( $path = WPF()->tpl->get_template_path($template) ){
            /** -START- to be removed in next versions */
	        extract(WPF()->current_object, EXTR_OVERWRITE);
	        extract(WPF()->current_object['user'], EXTR_OVERWRITE);
	        /** -END- to be removed in next versions */
            include($path);
        }else{
	        WPF()->tpl->show_template($template);
        }
	}else{
		WPF()->tpl->show_msg( wpforo_phrase('You do not have permission to view this page', false) );
	}
}

/**
 * @param array|string $template
 *
 * @return string
 */
function wpforo_get_template_slug($template = null){
	$key = WPF()->tpl->get_key($template);
	$slug = wpfval(WPF()->tpl->slugs, $key);
	if(!$slug) $slug = $key;
	return $slug;
}

/**
 * @param string $html
 *
 * @return string
 */
function wpforo_apply_ucf_shortcode($html){
	return preg_replace_callback('#\[wpfucf\s([^\[\]]+?)\]#iu', function($shortcode){
	    if( preg_match('#(?:^|\s)field=[\'\"]([^\'\"]+?)[\'\"]#iu', $shortcode[1], $field) ){
		    if( $field_key = trim($field[1]) ){
			    $userid = 0;
			    if( preg_match('#(?:^|\s)id=[\'\"]([^\'\"]+?)[\'\"]#iu', $shortcode[1], $id) ) $userid = wpforo_bigintval($id[1]);
			    if(!$userid) $userid = WPF()->current_object['userid'] ? WPF()->current_object['userid'] : WPF()->current_userid;
			    if($f = WPF()->member->get_field($field_key)){
				    if( wpfval($f, 'type') === 'html' && ($html = trim(wpfval($f, 'html'))) ){
					    return (string) WPF()->form->field_html($f);
				    }elseif( WPF()->form->can_view($f) && WPF()->form->can_show_value($f) ){
				        $f['value'] = wpforo_member($userid, $field_key);
					    $f = WPF()->form->prepare_values( WPF()->form->esc_field($f), $userid );
					    return $f['value'];
				    }
			    }
		    }
        }
	    return ''; // if that field has not found
    }, $html);
}

/**
 * @param array|string $template
 * @param array|int $member
 * @param string $default
 *
 * @return string
 */
function wpforo_member_url($template = null, $member = null, $default = ''){
	$member_url = $default;
	$template = WPF()->tpl->get_template($template);
	if( $template && wpforo_is_member_template($template) ){
		if( !$member ) $member = WPF()->current_object['user'] ? WPF()->current_object['user'] : WPF()->current_user;
		$profile_url = is_array($member) ? WPF()->member->profile_url($member, $template['key']) : WPF()->member->get_profile_url($member, $template['key']);
		if($profile_url) $member_url = $profile_url;
	}
	return $member_url;
}

function wpforo_mark_all_read_link(){
    if( !wpforo_is_bot() ) : ?>
        <a href="<?php echo wp_nonce_url( '?foro=allread' , 'wpforo_mark_all_read', 'foro_n' ); ?>" rel="nofollow"><i class="fas fa-check"></i> <?php wpforo_phrase('Mark all read') ?></a>
        <?php
    endif;
}

function wpforo_login_link(){
    if( !wpforo_is_bot() ) : ?>
        <a href="<?php echo wpforo_login_url(); ?>"><i class="fas fa-sign-in-alt"></i> <?php wpforo_phrase('Login'); ?></a>
        <?php
    endif;
}

function wpforo_register_link(){
    if( !wpforo_is_bot() ) : ?>
        <a href="<?php echo wpforo_register_url(); ?>"><i class="fas fa-user-plus"></i> <?php wpforo_phrase('Create Account'); ?></a>
        <?php
    endif;
}

function wpforo_topic_form_extra($forumid, $values){ ?>
    <div class="wpf-extra-fields">
        <?php do_action('wpforo_topic_form_extra_fields_before', $forumid, $values) ?>
        <div class="wpf-main-fields">
            <?php do_action('wpforo_topic_form_buttons_hook', $forumid, $values); ?>
        </div>
        <?php do_action('wpforo_topic_form_extra_fields_after', $forumid, $values) ?>
    </div>
    <?php
}

function wpforo_reply_form_extra($topic, $values){ ?>
    <div class="wpf-extra-fields">
		<?php do_action('wpforo_reply_form_extra_fields_before', $topic, $values) ?>
		<?php do_action('wpforo_reply_form_buttons_hook', $topic, $values); ?>
		<?php do_action('wpforo_reply_form_extra_fields_after', $topic, $values) ?>
    </div>
	<?php
}

function wpforo_post_search_form($values){
	WPF()->tpl->post_search_form($values);
}

function wpforo_member_search_form(){
	WPF()->tpl->member_search_form();
}

function wpforo_member_buttons($member){
	WPF()->tpl->member_buttons($member);
}

function wpforo_sanitize_search_body($body, $needle){
	$body = wpforo_content_filter($body);
	$body = wpforo_text($body, 0, false);
	$words = explode(' ', trim($needle));
	if(!empty($words)){
		$body_len = apply_filters('wpforo_search_results_body_length', 564);
		$pos = mb_stripos( $body, " ".trim($words[0]), 0, get_option('blog_charset') );
		if( strlen($body) > $body_len && $pos !== FALSE ){
			if($pos > ($body_len/2)){
				$bef_body = "... ";
				$start = mb_stripos( $body, " ", ($body_len/2), get_option('blog_charset') );
			}else{
				$bef_body = "";
				$start = 0;
			}
			if( (mb_strlen($body, get_option('blog_charset')) - $start) > $body_len ){
				$aft_body = " ...";
			}else{
				$aft_body = "";
			}
			$body = $bef_body . mb_substr( $body, $start, $body_len, get_option('blog_charset') ) . $aft_body;
		}
		foreach($words as $word){
			$word = trim($word);
			$body = preg_replace('#(?:^|\s+)'. preg_quote( esc_html($word) ) .'#iu', ' <span class="wpf-sword wpfcl-b">' . esc_html($word) . '</span>', $body);
		}
	}
	return $body;
}

function wpforo_topic_starter($topic, $post, $type = 'full'){

    $starter = false;

    $topic_user = (int)wpfval($topic, 'userid');
    $post_user = (int)wpfval($post, 'userid');
    $topic_guest = wpfval($topic, 'email');
    $post_guest = wpfval($post, 'email');

    if( $topic_user && $topic_user === $post_user ){
        $starter = true;
    } elseif( $topic_guest && $topic_guest === $post_guest ) {
        $starter = true;
    }

    if( $starter ){
        if( $type == 'full' ){
	        echo '<span class="wpf-post-starter"><i class="fas fa-feather-alt"></i> ' . wpforo_phrase('Topic starter', false) . '</span>';
        } elseif( $type == 'icon' ){
	        echo '<span class="wpf-post-starter" wpf-tooltip="' . wpforo_phrase('Topic starter', false) . '"><i class="fas fa-feather-alt"></i></span>';
        } elseif( $type == 'label' ){
	        echo '<span class="wpf-post-starter">' . wpforo_phrase('Topic starter', false) . '</span>';
        }
    }
}

function wpforo_get_not_replied_topicids(){
    $sql = "SELECT `topicid`, COUNT(DISTINCT userid) AS userid_count FROM `". WPF()->tables->posts ."` GROUP BY `topicid` HAVING userid_count = 1";
    return (array) WPF()->db->get_col($sql, 0);
}

function wpforo_topic_title($topic, $url, $structure = '{i}{au}{t}{/a}{n}{v}', $echo = true, $class = '', $length = 70){
	$html = __('No Title', 'wpforo');
    if( $title = trim(esc_html(wpfval($topic, 'title'))) ){
	    $class = ($class) ? ' class="' . esc_attr($class) . '"' : '';
	    $a = '<a href="' . esc_url($url) . '"' . $class . ' title="' . esc_attr($topic['title']) .'">';
	    $topic['url'] = $url;

	    // For better performance, make sure the component in demand before getting it.
	    $title_cut = (strpos($structure, '{tc}') !== false) ? esc_html(wpforo_text( $topic['title'], $length, false )) : '';
	    $icon_all = (strpos($structure, '{i}') !== false) ? wpforo_topic_icon($topic, 'all', true, false ) : '';
	    $icon_status = (strpos($structure, '{is}') !== false) ? wpforo_topic_icon($topic, 'status', true, false ) : '';
	    $viewing = (strpos($structure, '{v}') !== false) ? wpforo_viewing( $topic, false ) : '';
	    $new = (strpos($structure, '{n}') !== false) ? wpforo_unread_button($topic['topicid'], $url, false) : '';
	    $au = (strpos($structure, '{au}') !== false) ? '<a href="' . wpforo_unread_url($topic['topicid'], $url, false) . '" title="' . esc_attr($topic['title']) .'"' . $class . '>' : '';


	    $structure = apply_filters('wpforo_topic_title_structure', $structure, $topic);
	    $components = array( '{a}'   => $a,
	                         '{au}'  => $au,
	                         '{/a}'  => '</a>',
	                         '{i}'   => $icon_all,
	                         '{is}'  => $icon_status,
	                         '{t}'   => $title,
	                         '{tc}'  => $title_cut,
	                         '{v}'   => ' ' . $viewing,
	                         '{n}'   => ' ' . $new,
	                         '{p}'   => '',
	                         '{pt}'  => '');
	    $components = apply_filters('wpforo_topic_title_components', $components, $topic);
	    $html = strtr($structure, $components);
	    $html = apply_filters('wpforo_topic_title_html', $html);
    }
	if( !$echo ) return $html;
	echo $html;
}

function wpforo_single_title( $type = 'post', $item = array(), $before = '', $after = '', $echo = true ){
    $title = '';
    if( $type === 'post' && wpfval($item, 'title')){
	    $icon_title = WPF()->tpl->icon('topic', $item, false, 'title');
	    if( $icon_title ) {
	        $title .= '<span class="wpf-status-title">[' . esc_html($icon_title) . ']</span> ';
	    }
	    $title = apply_filters("wpforo_single_{$type}_pre_title", $title, $item);
	    $title .= esc_html( wpforo_text($item['title'], 0, false) );
    }
	$title = apply_filters("wpforo_single_{$type}_title", $title);
	if($title) $title = $before . $title . $after;
	if($echo) echo $title;
	return $title;
}