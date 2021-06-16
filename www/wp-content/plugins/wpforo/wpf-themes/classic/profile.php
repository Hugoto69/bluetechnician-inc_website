<?php
	// Exit if accessed directly
	if( !defined( 'ABSPATH' ) ) exit;
	
	$user = wpforo_get_current_object_user();
?>

<div class="wpforo-profile-wrap">
	<?php if( $user && ( WPF()->current_userid == $user['userid'] || WPF()->perm->usergroup_can('vprf')) ) :
		$user = apply_filters('wpforo_profile_header_obj', $user);
	    $rating_enabled = wpforo_feature('rating') && wpfval(WPF()->member->options, 'rating_badge_ug', $user['groupid']);
        $secondary_groups = ($user['secondary_groups']) ? WPF()->usergroup->get_secondary_usergroup_names($user['secondary_groups']) : array();
    ?>
	    <div class="wpforo-profile-head-wrap">
            <?php $avatar_image_html = wpforo_user_avatar($user, 150, 'alt="' . esc_attr(wpforo_user_dname($user)) . '"', true);
            $avatar_image_url = wpforo_avatar_url($avatar_image_html);
            $bg = ($avatar_image_url) ? "background-image:url('" . esc_url($avatar_image_url) . "');" : ''; ?>
            <div class="wpforo-profile-head-bg" style="<?php echo $bg ?>">
                <div class="wpfx"></div>
            </div>
            <div id="m_" class="wpforo-profile-head">
                <?php do_action( 'wpforo_profile_plugin_menu_action', $user['userid'] ); ?>
                <div class="h-header">
                	<div class="wpfy" <?php if( !$rating_enabled ) echo ' style="height:140px;" ' ?>></div>
                    <div class="wpf-profile-info-wrap">
                    
                        <div class="h-picture">
							<?php if( WPF()->perm->usergroup_can('va') && wpforo_feature('avatars') ): ?>
                                <div class="wpf-profile-img-wrap">
									<?php echo $avatar_image_html; ?>
                                </div>
                            <?php endif; ?>
                            <div class="wpf-profile-data-wrap">
                                <div class="profile-display-name">
                                    <?php WPF()->member->show_online_indicator($user['userid']) ?>
                                    <?php wpforo_user_dname($user, true) ?>
                                    <div class="profile-stat-data-item"><?php wpforo_phrase('Group') ?>: <?php wpforo_phrase($user['groupname']) ?></div>
                                    <?php if(!empty($secondary_groups)): ?>
                                        <div class="profile-stat-data-item"><?php wpforo_phrase('Secondary Groups') ?>: <?php echo implode(', ', $secondary_groups); ?></div>
                                    <?php endif; ?>
                                	<div class="profile-stat-data-item"><?php wpforo_phrase('Joined') ?>: <?php wpforo_date($user['user_registered'], 'date') ?></div>
                                	<div class="profile-stat-data-item"><?php wpforo_member_title($user, true, wpforo_phrase('Title', false) . ': '); ?></div>
                                </div>
                            </div>
                            <div class="wpf-cl"></div>
						</div>
                    
                    <div class="h-header-info">
                        <div class="h-top">
                            <div class="profile-stat-data">
                                <?php do_action( 'wpforo_profile_data_item', WPF()->current_object ) ?>
                                <?php if( $rating_enabled ): ?>
                                    <div class="profile-rating-bar">
                                        <div class="profile-rating-bar-wrap" title="<?php wpforo_phrase('Member Rating') ?>">
                                            <?php $levels = WPF()->member->levels(); ?>
                                            <?php $rating_level = ( wpfval( $user['stat'], 'rating') ) ? $user['stat']['rating'] : WPF()->member->rating_level( $user['posts'], false ); ?>
                                            <?php for( $a=1; $a <= $rating_level; $a++ ): ?>
                                                <div class="rating-bar-cell" style="background-color:<?php echo esc_attr($user['stat']['color']); ?>;">
                                                    <i class="<?php echo WPF()->member->rating($a, 'icon') ?>"></i>
                                                </div>
                                            <?php endfor; ?>
                                            <?php for( $i = ($rating_level+1); $i <= (count($levels)-1); $i++ ): ?>
                                                <div class="wpfbg-7 rating-bar-cell" >
                                                    <i class="<?php echo WPF()->member->rating($i, 'icon') ?>"></i>
                                                </div>
                                            <?php endfor; ?>
                                        </div>
                                    </div>
                                    <div class="wpf-profile-badge" title="<?php wpforo_phrase('Rating Badge') ?>" style="background-color:<?php echo esc_attr($user['stat']['color']); ?>;">
                                        <?php echo WPF()->member->rating_badge($rating_level, 'short'); ?>
                                    </div>
                                <?php endif; ?>
                                <?php do_action('wpforo_after_member_badge', $user); ?>
                            </div>
                        </div>
                    </div>
                    <div class="wpf-clear"></div>
                </div>
                </div>
                <div class="h-footer">
                    <div class="h-bottom">
                        <?php wpforo_member_tabs() ?>
                        <div class="wpf-clear"></div>
                    </div>
                </div>
            </div>
        </div>
	    <div class="wpforo-profile-content">
	    	<?php wpforo_member_template() ?>
	    </div>
	<?php elseif( $user ) : ?>
		<div class="wpforo-profile-content wpfbg-7">
			<div class="wpfbg-7 wpf-page-message-wrap">
				<div class="wpf-page-message-text">
					<?php wpforo_phrase('You do not have permission to view this page') ?>
				</div>
			</div>
		</div>
	<?php else : ?>
		<div class="wpforo-profile-content wpfbg-7">
			<div class="wpfbg-7 wpf-page-message-wrap">
				<div class="wpf-page-message-text">
					<?php WPF()->tpl->member_error() ?>
				</div>
			</div>
		</div>
	<?php endif; ?>
</div>