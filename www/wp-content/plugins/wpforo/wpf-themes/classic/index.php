<?php
	// Exit if accessed directly
	if( !defined( 'ABSPATH' ) ) exit;

/**
 * Template Name:  Forum Index (Forums List)
 */

if( WPF()->use_home_url ) get_header(); ?>
<?php extract(WPF()->current_object, EXTR_OVERWRITE); ?>

<div id="wpforo">

    <?php include( wpftpl('header.php') ); ?>

    <div class="wpforo-main">
        <div class="wpforo-content <?php if(WPF()->api->options['sb_location_toggle'] === 'right') echo 'wpfrt' ?>" <?php echo is_active_sidebar('forum-sidebar') ? '' : 'style="width:100%"' ?>>
            <?php do_action( 'wpforo_content_start' ); ?>
            <?php if( !in_array( WPF()->current_user_status, array('banned', 'trashed')) ) :

                if( !WPF()->current_object['is_404'] ){
                    if(WPF()->current_object['template'] === 'lostpassword'){
                        echo do_shortcode('[wpforo-lostpassword]');
                    }elseif(WPF()->current_object['template'] === 'resetpassword'){
                        echo do_shortcode('[wpforo-resetpassword]');
                    }elseif(WPF()->current_object['template'] === 'page'){
                        wpforo_page();
                    }elseif( wpforo_is_member_template() ) {
                        wpforo_template('profile');
                    }elseif( in_array(WPF()->current_object['template'], array('forum', 'topic')) ){
                        wpforo_template('forum');
                        if( WPF()->current_object['template'] === 'topic' ){
                            wpforo_template('topic');
                        } else {
                            wpforo_admin_cpanel();
                        }
                    }else{
                        wpforo_template();
                    }
                }else{
                    wpforo_template('404');
                }

            else : ?>
                <p class="wpf-p-error">
                    <?php wpforo_phrase('You have been banned. Please contact the forum administratoristrators for more information.') ?>
                </p>
            <?php endif; ?>
        </div>
        <?php if (is_active_sidebar('forum-sidebar')) : ?>
            <div class="wpforo-right-sidebar">
                <?php dynamic_sidebar('forum-sidebar') ?>
            </div>
        <?php endif; ?>
       <div class="wpf-clear"></div>
    </div>

    <?php include( wpftpl('footer.php') ); ?>

</div>

<?php if( WPF()->use_home_url ) get_footer();  ?>