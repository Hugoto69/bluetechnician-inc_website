<?php
	// Exit if accessed directly
	if( !defined( 'ABSPATH' ) ) exit;
	if( !WPF()->perm->usergroup_can('ms') ) exit;
?>

<?php
$options = array(
    'user-admin-bar' => array( 'label' => __('Show Admin Bar for Members', 'wpforo'),  'type' => '',  'required' => '', 'value' => 0, 'description' => __('This option doesn\'t affect website admins.', 'wpforo'), 'help' => 'https://wpforo.com/docs/root/wpforo-settings/features/' ),
    'page-title' => array( 'label' => __('Show Forum Page Title', 'wpforo'),  'type' => '',  'required' => '', 'value' => 1, 'help' => 'https://wpforo.com/docs/root/wpforo-settings/features/' ),
    'top-bar' => array( 'label' => __('Show Top/Menu Bar', 'wpforo'),  'type' => '',  'required' => '', 'value' => 1, 'help' => 'https://wpforo.com/docs/root/wpforo-settings/features/'),
    'top-bar-search' => array( 'label' => __('Show Top Search', 'wpforo'),  'type' => '',  'required' => '', 'value' => 1, 'help' => 'https://wpforo.com/docs/root/wpforo-settings/features/'),
    'breadcrumb' => array( 'label' => __('Show Breadcrumb', 'wpforo'),  'type' => '',  'required' => '', 'value' => 1, 'help' => 'https://wpforo.com/docs/root/wpforo-settings/features/'),
    'footer-stat' => array( 'label' => __('Show Forum Statistic', 'wpforo'),  'type' => '',  'required' => '', 'value' => 1, 'help' => 'https://wpforo.com/docs/root/wpforo-settings/features/'),
    'mention-nicknames' => array( 'label' => __('Show Member Mention Nicknames', 'wpforo'),  'type' => '',  'required' => '', 'value' => 1),
    'content-do_shortcode' => array( 'label' => __('Enable WordPress Shortcodes in Post Content', 'wpforo'),  'type' => '',  'required' => '', 'value' => 1),
    'view-logging' => array( 'label' => __('Log Viewed Forums and Topics', 'wpforo'),  'type' => '',  'required' => '', 'value' => 1),
    'track-logging' => array( 'label' => __('Track Forum and Topic Current Viewers', 'wpforo'),  'type' => '',  'required' => '', 'value' => 1),
    'goto-unread' => array( 'label' => __('Topic Links Jump to First Unread Post', 'wpforo'),  'type' => '',  'required' => '', 'value' => 1, 'description' => __('If this option is enabled, all topic links on forums page and on topics page will refer to the first unread post. This behavior is only enabled for logged-in users.', 'wpforo')),
    'goto-unread-button' => array( 'label' => __('Jump to First Unread Post with [new] Button in Topic Link', 'wpforo'),  'type' => '',  'required' => '', 'value' => 1, 'description' => __('Adds [new] button at the and of topic links, which jumps to the first unread post.', 'wpforo')),
    'profile' => array( 'label' => __('Profile Page', 'wpforo'),  'type' => '',  'required' => '', 'value' => 0, 'help' => '' ),
    'user-register' => array( 'label' => __('Enable User Registration', 'wpforo'),  'type' => '',  'required' => '', 'value' => 1, 'description' => __('This option is not synced with WordPress "Anyone can register" option in Dashboard > Settings > General admin page. If this option is enabled new users will always be able to register.', 'wpforo'), 'help' => 'https://wpforo.com/docs/root/wpforo-settings/features/#user-registration' ),
    'user-register-email-confirm' => array( 'label' => __('Enable User Registration email confirmation', 'wpforo'),  'type' => '',  'required' => '', 'value' => 1, 'description' => __('If you have enabled this option, after registering, user can not login without confirming the email.', 'wpforo'), 'help' => 'https://wpforo.com/docs/root/wpforo-settings/features/#registration-email-confirmation' ),
    'disable_new_user_admin_notification' => array( 'label' => __('Stop Sending Emails to Admins on New User Registration', 'wpforo'),  'type' => '',  'required' => '', 'value' => 0, 'description' => __('If you have enabled this option, after registering, the admin will not receive an email notification for newly registered user.', 'wpforo'), 'help' => '' ),
    'register-url' => array( 'label' => __('Replace Registration Page URL to Forum Registration Page URL', 'wpforo'),  'type' => '',  'required' => '', 'value' => 0, 'help' => 'https://wpforo.com/docs/root/wpforo-settings/features/#url-replace' ),
    'login-url' => array( 'label' => __('Replace Login Page URL to Forum Login Page URL', 'wpforo'),  'type' => '',  'required' => '', 'value' => 0, 'help' => 'https://wpforo.com/docs/root/wpforo-settings/features/#url-replace' ),
    'resetpass-url' => array( 'label' => __('Replace Reset Password Page URL to Forum Reset Password Page URL', 'wpforo'),  'type' => '',  'required' => '', 'value' => 1 ),
    'replace-avatar' => array( 'label' => __('Replace Author Avatar with Forum Profile Avatar', 'wpforo'),  'type' => '',  'required' => '', 'value' => 1, 'help' => 'https://wpforo.com/docs/root/wpforo-settings/features/#replace-author-data'),
    'avatars' => array( 'label' => __('Enable Avatars', 'wpforo'),  'type' => '',  'required' => '', 'value' => 1, 'help' => 'https://wpforo.com/docs/root/wpforo-settings/features/#avatars'),
    'custom-avatars' => array( 'label' => __('Enable Custom Avatars', 'wpforo'),  'type' => '',  'required' => '', 'value' => 1, 'help' => 'https://wpforo.com/docs/root/wpforo-settings/features/#custom-avatars'),
    'signature' => array( 'label' => __('Allow Member Signature', 'wpforo'),  'type' => '',  'required' => '', 'value' => 1, 'help' => 'https://wpforo.com/docs/root/wpforo-settings/features/#signature'),
    'rating' => array( 'label' => __('Enable Member Rating', 'wpforo'),  'type' => '',  'required' => '', 'value' => 1, 'help' => 'https://wpforo.com/docs/root/wpforo-settings/features/#member-rating'),
    'rating_title' => array( 'label' => __('Enable Member Rating Titles', 'wpforo'),  'type' => '',  'required' => '', 'value' => 1, 'help' => 'https://wpforo.com/docs/root/wpforo-settings/features/#member-rating'),
    'member_cashe' => array( 'label' => __('Enable Member Cache', 'wpforo'),  'type' => '',  'required' => '', 'value' => 1, 'help' => 'https://wpforo.com/docs/root/wpforo-settings/features/#member-cache'),
    'object_cashe' => array( 'label' => __('Enable Object Cache', 'wpforo'),  'type' => '',  'required' => '', 'value' => 1),
    'option_cache' => array( 'label' => __('Enable Options Cache', 'wpforo'),  'type' => '',  'required' => '', 'value' => 1),
    'html_cashe' => array( 'label' => __('Enable HTML Cache', 'wpforo'),  'type' => '',  'required' => '', 'value' => 1),
    'memory_cashe' => array( 'label' => __('Enable Memory Cache', 'wpforo'),  'type' => '',  'required' => '', 'value' => 1),
    'seo-title' => array( 'label' => __('Enable wpForo SEO for Meta Titles', 'wpforo'),  'type' => '',  'required' => '', 'value' => 1, 'help' => 'https://wpforo.com/docs/root/wpforo-settings/features/#seo-meta'),
    'seo-meta' => array( 'label' => __('Enable wpForo SEO for Meta Tags', 'wpforo'),  'type' => '',  'required' => '', 'value' => 1, 'help' => 'https://wpforo.com/docs/root/wpforo-settings/features/#seo-meta'),
    'seo-profile' => array( 'label' => __('Enable User Profile Page indexing', 'wpforo'),  'type' => '',  'required' => '', 'value' => 1),
    'rss-feed' => array( 'label' => __('Enable RSS Feed', 'wpforo'),  'type' => '',  'required' => '', 'value' => 1),
	'user-synch' => array( 'label' => __('Turn Off User Syncing Note', 'wpforo'),  'type' => '',  'required' => '', 'value' => 1),
    'notifications' => array( 'label' => __('Enable User Notification', 'wpforo'),  'type' => '',  'required' => '', 'value' => 1),
    'notifications-live' => array( 'label' => __('Enable User Notification Live Update', 'wpforo'),  'type' => '',  'required' => '', 'value' => 1),
    'notifications-bar' => array( 'label' => __('Display User Notification Bell on Menu Bar', 'wpforo'),  'type' => '',  'required' => '', 'value' => 1),
    'bp_activity' => array( 'label' => __('BuddyPress Activity Integration', 'wpforo'),  'type' => '',  'required' => '', 'value' => 1, 'description' => __('Posts members activity (new topic, new reply, post like) to BuddyPress Profile Activity page.', 'wpforo')),
    'bp_notification' => array( 'label' => __('BuddyPress Notification Integration', 'wpforo'),  'type' => '',  'required' => '', 'value' => 1, 'description' => __('Creates notification on new forum reply in BuddyPress Profile Notification page.', 'wpforo')),
    'bp_forum_tab' => array( 'label' => __('BuddyPress Profile Forum Tab Integration', 'wpforo'),  'type' => '',  'required' => '', 'value' => 1, 'description' => __('Adds "Forums" tab with "Created Topics", "Posted Replies", "Liked Posts" and "Subscriptions" sub-tabs to BuddyPress Profile page.', 'wpforo')),
    'um_notification' => array( 'label' => __('Ultimate Member Notification Integration', 'wpforo'),  'type' => '',  'required' => '', 'value' => 1, 'description' => __('Creates notification on new forum reply in Ultimate Member Real-time Notification system.', 'wpforo')),
    'um_forum_tab' => array( 'label' => __('Ultimate Member Profile Forum Tab Integration', 'wpforo'),  'type' => '',  'required' => '', 'value' => 1, 'description' => __('Adds "Forums" tab with "Created Topics", "Posted Replies", "Liked Posts" and "Subscriptions" sub-tabs to Ultimate Member Profile page.', 'wpforo')),
    'font-awesome' => array( 'label' => __('Enable wpForo Font-Awesome Lib', 'wpforo'),  'type' => '',  'required' => '', 'value' => 1, 'help' => 'https://wpforo.com/docs/root/wpforo-settings/features/#font-awesome-lib'),
    'output-buffer' => array( 'label' => __('Enable Output Buffer', 'wpforo'),  'type' => '',  'required' => '', 'value' => 1, 'description' => __('This feature is useful if you\'re adding content before or after [wpforo] shortcode in page content. Also it useful if forum is loaded before website header, on top of the front-end.', 'wpforo'), 'help' => 'https://wpforo.com/docs/root/wpforo-settings/features/#output-buffer'),
	'wp-date-format' => array( 'label' => __('Enable WordPress Date/Time Format', 'wpforo'),  'type' => '',  'required' => '', 'value' => 0, 'description' => __('You can manage WordPress date and time format in WordPress Settings > General admin page.', 'wpforo'), 'help' => 'https://wpforo.com/docs/root/wpforo-settings/features/#wp-date'),
	'subscribe_conf' => array( 'label' => __('Enable Subscription Confirmation', 'wpforo'),  'type' => '',  'required' => '', 'value' => 1, 'description' => __('Forum and Topic subscription with double opt-in/confirmation system.', 'wpforo'), 'help' => 'https://wpforo.com/docs/root/wpforo-settings/features/#double-opt-in' ),
	'subscribe_checkbox_on_post_editor' => array( 'label' => __('Topic subscription option on post editor', 'wpforo'),  'type' => '',  'required' => '', 'value' => 1, 'description' => __('This option adds topic subscription checkbox next to new topic and post submit button.', 'wpforo'), 'help' => 'https://wpforo.com/docs/root/wpforo-settings/features/#topic-subscription' ),
	'subscribe_checkbox_default_status' => array( 'label' => __('Topic subscription option on post editor - checked/enabled', 'wpforo'),  'type' => '',  'required' => '', 'value' => 1, 'description' => __('Enable this option if you want the topic subscription checkbox to be checked by default.', 'wpforo'), 'help' => 'https://wpforo.com/docs/root/wpforo-settings/features/#topic-subscription' ),
    'role-synch' => array( 'label' => __('Role-Usergroup Synchronization', 'wpforo'),  'type' => '',  'required' => '', 'value' => 1, 'description' => __('Keep enabled this option to synch WordPress User Roles with Forum Usergroups. This connection allows to automatically change Usergroup of a user when his/her User Role is changed by administrators or by membership plugins. In other words this option allows to manage Usergroups based on Users Roles, thus you can directly control users forum accesses based on Users Roles. If this option is turned off, User Roles don\'t have any affection to users forum accesses, they are only controlled by forum Usergroups.', 'wpforo') ),
    'attach-media-lib' => array( 'label' => __('Insert Forum Attachments to Media Library', 'wpforo'),  'type' => '',  'required' => '', 'value' => 1, 'description' => __('Enable this option to be able manage forum attachments in Dashboard > Media > Library admin page.', 'wpforo'), 'help' => 'https://wpforo.com/docs/root/wpforo-settings/features/#media-library' ),
    'admin-cp' => array( 'label' => __('Frontend Admin Control Panel', 'wpforo'),  'type' => '',  'required' => '', 'value' => 0, 'description' => ''),
    'debug-mode' => array( 'label' => __('Enable Debug Mode', 'wpforo'),  'type' => '',  'required' => '', 'value' => 0, 'description' => __('If you got some issue with wpForo, please enable this option before asking for support, this outputs hidden important information to help us debug your issue.', 'wpforo')),
	'copyright' => array( 'label' => __('Help wpForo to grow, show plugin info', 'wpforo'),  'type' => '',  'required' => '', 'value' => 1, 'description' => __('Please enable this option to help wpForo get more popularity as your thank to the hard work we do for you totally free. This option adds a very small icon in forum footer, which will allow your site visitors recognize the name of forum solution you use.', 'wpforo')),
);

?>
<form action="" method="POST" class="validate">
	<?php wp_nonce_field( 'wpforo-features' ); ?>
    <input type="hidden" name="wpfaction" value="wpforo_features_options_save">
    <table class="wpforo_settings_table">
        <tbody>
            <?php foreach($options as $key => $option): if( $key === 'html_cashe' ) continue; ?>
            	<?php if( !isset(WPF()->features[$key]) ){ WPF()->features[$key] = ''; } ?>
                <tr>
                    <th>
                    	<label><?php echo esc_html($option['label']); ?> <?php if(wpfval($option, 'help')): ?><a href="<?php echo esc_url($option['help']) ?>" title="<?php _e('Read the documentation', 'wpforo') ?>" target="_blank"><i class="far fa-question-circle"></i></a><?php endif; ?></label>
                    	<p class="wpf-info"><?php if(isset($option['description'])) echo esc_html($option['description']); ?></p>
                    </th>
                    <td>
                        <div class="wpf-switch-field">
                            <?php if( $key === 'profile' ) : ?>
                                <input type="radio" value="1" name="wpforo_features[<?php echo esc_attr($key); ?>]" id="wpf_<?php echo esc_attr($key); ?>_1" <?php wpfo_check(WPF()->features[$key], 1); ?>><label for="wpf_<?php echo esc_attr($key); ?>_1" title="<?php _e('wpForo Profile only for the forum, WordPress Profile for others, including comment authors, post authors and etc...', 'wpforo') ?>"><?php _e('Default', 'wpforo'); ?></label> &nbsp;
                                <input type="radio" value="2" name="wpforo_features[<?php echo esc_attr($key); ?>]" id="wpf_<?php echo esc_attr($key); ?>_2" <?php wpfo_check(WPF()->features[$key], 2); ?>><label for="wpf_<?php echo esc_attr($key); ?>_2" title="<?php _e('wpForo Profile for the whole website including forum, comment authors, post authors and etc...', 'wpforo') ?>"><?php _e('wpForo', 'wpforo'); ?></label> &nbsp;
                                <input type="radio" value="3" <?php echo !class_exists('BP_Component') ? ' disabled ' : '' ?> name="wpforo_features[<?php echo esc_attr($key); ?>]" id="wpf_<?php echo esc_attr($key); ?>_3" <?php wpfo_check(WPF()->features[$key], 3); ?>><label for="wpf_<?php echo esc_attr($key); ?>_3" title="<?php _e('BuddyPress Profile for the whole website including forum.', 'wpforo') ?>"><?php _e('BuddyPress', 'wpforo'); ?></label> &nbsp;
                                <input type="radio" value="4" <?php echo !function_exists('UM') ? ' disabled ' : '' ?> name="wpforo_features[<?php echo esc_attr($key); ?>]" id="wpf_<?php echo esc_attr($key); ?>_4" <?php wpfo_check(WPF()->features[$key], 4); ?>><label for="wpf_<?php echo esc_attr($key); ?>_4" title="<?php _e('Ultimate Member Profile for the whole website including forum.', 'wpforo') ?>"><?php _e('Ultimate Member', 'wpforo'); ?></label> &nbsp;
                            <?php else : ?>
                                <?php if( $key === 'font-awesome' ) : ?>
                                    <input type="radio" value="2" name="wpforo_features[<?php echo esc_attr($key); ?>]" id="wpf_<?php echo esc_attr($key); ?>_2" <?php wpfo_check(WPF()->features[$key], 2); ?>><label for="wpf_<?php echo esc_attr($key); ?>_2"><?php _e('Sitewide', 'wpforo'); ?></label> &nbsp;
                                    <input type="radio" value="1" name="wpforo_features[<?php echo esc_attr($key); ?>]" id="wpf_<?php echo esc_attr($key); ?>_1" <?php wpfo_check(WPF()->features[$key], 1); ?>><label for="wpf_<?php echo esc_attr($key); ?>_1"><?php _e('Forum', 'wpforo'); ?></label> &nbsp;
                                <?php else : ?>
                                    <input type="radio" value="1" name="wpforo_features[<?php echo esc_attr($key); ?>]" id="wpf_<?php echo esc_attr($key); ?>_1" <?php wpfo_check(WPF()->features[$key], 1); ?>><label for="wpf_<?php echo esc_attr($key); ?>_1"><?php _e('Yes', 'wpforo'); ?></label> &nbsp;
                                <?php endif; ?>
                                <input type="radio" value="0" name="wpforo_features[<?php echo esc_attr($key); ?>]" id="wpf_<?php echo esc_attr($key); ?>_0" <?php wpfo_check(WPF()->features[$key], 0); ?>><label for="wpf_<?php echo esc_attr($key); ?>_0"><?php _e('No', 'wpforo'); ?></label>
                                <?php if($key === 'copyright') echo '<span style="color:#009900; font-weight:400; font-size:14px;">&nbsp;'. __('Thank you!', 'wpforo') . '</span>'; ?>
                            <?php endif ?>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php do_action('wpforo_settings_theme'); ?>
        </tbody>
    </table>
    <div class="wpforo_settings_foot">
        <input type="submit" class="button button-primary" value="<?php _e('Update Options', 'wpforo'); ?>" />
        <input type="submit" class="button" value="<?php _e('Reset Options', 'wpforo'); ?>" name="reset" onclick="return confirm('<?php wpforo_phrase('Do you really want to reset options?') ?>')" />
    </div>
</form>