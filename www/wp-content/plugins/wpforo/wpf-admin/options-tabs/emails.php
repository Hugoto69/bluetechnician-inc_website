<?php
	// Exit if accessed directly
	if( !defined( 'ABSPATH' ) ) exit;
	if( !WPF()->perm->usergroup_can('ms') ) exit;

$wpe_args = array(
	'teeny'          => false,
	'media_buttons'  => true,
	'textarea_rows'  => 8,
	'tinymce'        => array('wpautop' => false),
	'quicktags'      => array( 'buttons' => 'strong,em,link,block,del,ins,img,ul,ol,li,code,close' ),
	'default_editor' => 'tinymce'
);
?>
<style>.wpf-email-shortcodes{font-weight: normal; font-size: 12px; color: #666666; list-style: disc; margin-left: 20px;}  .wpf-email-shortcodes li{padding: 0; margin: 0; line-height: 18px;}</style>
<form action="" method="POST" class="validate">
    <?php wp_nonce_field( 'wpforo-settings-emails' ); ?>
    <input type="hidden" name="wpfaction" value="wpforo_email_options_save">
    <table class="wpforo_settings_table">
        <tbody>
            <tr>
                <th style="width:40%"><label for="from_name"><?php _e('FROM Name', 'wpforo'); ?> <a href="https://wpforo.com/docs/root/wpforo-settings/emails-settings/" title="<?php _e('Read the documentation', 'wpforo') ?>" target="_blank"><i class="far fa-question-circle"></i></a></label></th>
                <td><input id="from_name" name="wpforo_email_options[from_name]" type="text" value="<?php wpfo(WPF()->sbscrb->options['from_name']); ?>" required></td>
            </tr>
            <tr>
                <th><label for="from_email"><?php _e('FROM Email Address', 'wpforo'); ?> <a href="https://wpforo.com/docs/root/wpforo-settings/emails-settings/" title="<?php _e('Read the documentation', 'wpforo') ?>" target="_blank"><i class="far fa-question-circle"></i></a></label></th>
                <td><input id="from_email" name="wpforo_email_options[from_email]" type="text" value="<?php wpfo(WPF()->sbscrb->options['from_email']); ?>" required /></td>
            </tr>
            <tr>
                <th>
                    <label for="admin_emails"><?php _e('Forum Admins email addresses', 'wpforo'); ?> <a href="https://wpforo.com/docs/root/wpforo-settings/emails-settings/#admin-emails" title="<?php _e('Read the documentation', 'wpforo') ?>" target="_blank"><i class="far fa-question-circle"></i></a></label>
                    <p class="wpf-info"><?php _e('Comma separated email addresses of forum administrators to get forum notifications. For example post report messages.', 'wpforo') ?></p>
                </th>
                <td><input id="admin_emails" name="wpforo_email_options[admin_emails]" type="text" value="<?php wpfo(WPF()->sbscrb->options['admin_emails']); ?>" required /></td>
            </tr>
            <tr>
                <th>
                    <label><?php _e('Notify Admins via email on new Topic', 'wpforo'); ?> <a href="https://wpforo.com/docs/root/wpforo-settings/emails-settings/#admin-notification" title="<?php _e('Read the documentation', 'wpforo') ?>" target="_blank"><i class="far fa-question-circle"></i></a></label>
                    <p class="wpf-info"><?php _e('Send Notification emails to all email addresses (comma separated ) of forum administrators when a new Topic is created.', 'wpforo') ?></p>
                </th>
                <td>
                    <div class="wpf-switch-field">
                        <input type="radio" value="1" name="wpforo_email_options[new_topic_notify]" id="wpf_new_topic_notify_1" <?php wpfo_check(WPF()->sbscrb->options['new_topic_notify'], 1); ?>><label for="wpf_new_topic_notify_1"><?php _e('Yes', 'wpforo'); ?></label> &nbsp;
                        <input type="radio" value="0" name="wpforo_email_options[new_topic_notify]" id="wpf_new_topic_notify_0" <?php wpfo_check(WPF()->sbscrb->options['new_topic_notify'], 0); ?>><label for="wpf_new_topic_notify_0"><?php _e('No', 'wpforo'); ?></label>
                    </div>
                </td>
            </tr>
            <tr>
                <th>
                    <label><?php _e('Notify Admins via email on new Post', 'wpforo'); ?> <a href="https://wpforo.com/docs/root/wpforo-settings/emails-settings/#admin-notification" title="<?php _e('Read the documentation', 'wpforo') ?>" target="_blank"><i class="far fa-question-circle"></i></a></label>
                    <p class="wpf-info"><?php _e('Send Notification emails to all email addresses (comma separated ) of forum administrators when a new Reply is created.', 'wpforo') ?></p>
                </th>
                <td>
                    <div class="wpf-switch-field">
                        <input type="radio" value="1" name="wpforo_email_options[new_reply_notify]" id="wpf_new_reply_notify_1" <?php wpfo_check(WPF()->sbscrb->options['new_reply_notify'], 1); ?>><label for="wpf_new_reply_notify_1"><?php _e('Yes', 'wpforo'); ?></label> &nbsp;
                        <input type="radio" value="0" name="wpforo_email_options[new_reply_notify]" id="wpf_new_reply_notify_0" <?php wpfo_check(WPF()->sbscrb->options['new_reply_notify'], 0); ?>><label for="wpf_new_reply_notify_0"><?php _e('No', 'wpforo'); ?></label>
                    </div>
                </td>
            </tr>
            <tr>
                <td colspan="2" style="border-bottom:2px solid #ddd;">
                <h3 style="font-weight:400; padding:10px 0 0 0; margin:0;"><?php _e('Subscription Emails', 'wpforo'); ?> &nbsp;<a href="https://wpforo.com/docs/root/wpforo-settings/emails-settings/#email-templates" title="<?php _e('Read the documentation', 'wpforo') ?>" target="_blank" style="font-size: 14px;"><i class="far fa-question-circle"></i></a></h3>
                </td>
            </tr>
            <tr>
                <th>
                    <label for="confirmation_email_subject"><?php _e('Subscribe confirmation email subject', 'wpforo'); ?>:</label>
                </th>
                <td><input id="confirmation_email_subject" name="wpforo_email_options[confirmation_email_subject]" type="text"  value="<?php wpfo(WPF()->sbscrb->options['confirmation_email_subject']); ?>" required></td>
            </tr>
            <tr>
                <th>
                    <label for="confirmation_email_message"><?php _e('Subscribe confirmation email message', 'wpforo'); ?>:</label>
                    <ul class="wpf-email-shortcodes">
                        <li>[entry_title] - <?php _e('Subscribed forum or topic title', 'wpforo') ?></li>
                        <li>[member_name] - <?php _e('Subscriber display name', 'wpforo') ?></li>
                        <li>[confirm_link] - <?php _e('Link to confirm subscription', 'wpforo') ?></li>
                    </ul>
                </th>
                <td>
                    <?php $wpe_args['textarea_name'] = 'wpforo_email_options[confirmation_email_message]';
                    wp_editor( wpautop(wp_unslash( WPF()->sbscrb->options['confirmation_email_message'] )), 'confirmation_email_message', $wpe_args ); ?>
                </td>
            </tr>
            <tr>
                <th><label for="new_topic_notification_email_subject"><?php _e('New topic notification email subject', 'wpforo'); ?>:</label></th>
                <td><input id="new_topic_notification_email_subject" name="wpforo_email_options[new_topic_notification_email_subject]" type="text"  value="<?php wpfo(WPF()->sbscrb->options['new_topic_notification_email_subject']); ?>" required></td>
            </tr>
            <tr>
                <th>
                    <label for="new_topic_notification_email_message"><?php _e('New topic notification email message', 'wpforo'); ?>:</label>
                    <ul class="wpf-email-shortcodes">
                        <li>[member_name] - <?php _e('Subscriber display name', 'wpforo') ?></li>
                        <li>[post_author_name] - <?php _e('New topic author display name', 'wpforo') ?></li>
                        <li>[forum] - <?php _e('Forum title / link', 'wpforo') ?></li>
                        <li>[topic_title] - <?php _e('New topic title', 'wpforo') ?></li>
                        <li>[topic_desc] - <?php _e('New topic excerpt', 'wpforo') ?></li>
                        <li>[unsubscribe_link] - <?php _e('Link to unsubscribe', 'wpforo') ?></li>
                    </ul>
                </th>
                <td>
		            <?php $wpe_args['textarea_name'] = 'wpforo_email_options[new_topic_notification_email_message]';
		            wp_editor( wpautop(wp_unslash( WPF()->sbscrb->options['new_topic_notification_email_message'] )), 'new_topic_notification_email_message', $wpe_args ); ?>
                </td>
            </tr>
            <tr>
                <th><label for="new_post_notification_email_subject"><?php _e('New reply notification email subject', 'wpforo'); ?>:</label></th>
                <td><input id="new_post_notification_email_subject" name="wpforo_email_options[new_post_notification_email_subject]" type="text"  value="<?php wpfo(WPF()->sbscrb->options['new_post_notification_email_subject']); ?>" required></td>
            </tr>
            <tr>
                <th>
                    <label for="new_post_notification_email_message"><?php _e('New reply notification email message', 'wpforo'); ?>:</label>
                    <ul class="wpf-email-shortcodes">
                        <li>[member_name] - <?php _e('Subscriber display name', 'wpforo') ?></li>
                        <li>[post_author_name] - <?php _e('New reply author display name', 'wpforo') ?></li>
                        <li>[topic] - <?php _e('Topic title / link', 'wpforo') ?></li>
                        <li>[reply_title] - <?php _e('New reply title', 'wpforo') ?></li>
                        <li>[reply_desc] - <?php _e('New reply excerpt', 'wpforo') ?></li>
                        <li>[unsubscribe_link] - <?php _e('Link to unsubscribe', 'wpforo') ?></li>
                    </ul>
                </th>
                <td>
		            <?php $wpe_args['textarea_name'] = 'wpforo_email_options[new_post_notification_email_message]';
		            wp_editor( wpautop(wp_unslash( WPF()->sbscrb->options['new_post_notification_email_message'] )), 'new_post_notification_email_message', $wpe_args ); ?>
                </td>
            </tr>
            <tr>
                <td colspan="2" style="border-bottom:2px solid #ddd;">
                <h3 style="font-weight:400; padding:10px 0 0 0; margin:0;"><?php _e('Post Reporting Emails', 'wpforo'); ?></h3>
                <p class="wpf-info"><?php _e('This message comes from post reporting pop-up form.', 'wpforo') ?></p>
                </td>
            </tr>
            <tr>
                <th>
                    <label for="report_email_subject"><?php _e('Report message subject', 'wpforo'); ?>:</label>
                </th>
                <td><input id="report_email_subject" name="wpforo_email_options[report_email_subject]" type="text"  value="<?php wpfo(WPF()->sbscrb->options['report_email_subject']); ?>" required></td>
            </tr>
            <tr>
                <th>
                    <label for="report_email_message"><?php _e('Report message body', 'wpforo'); ?>:</label>
                    <ul class="wpf-email-shortcodes">
                        <li>[reporter] - <?php _e('Reporter user display name', 'wpforo') ?></li>
                        <li>[message] - <?php _e('Reporter user message', 'wpforo') ?></li>
                        <li>[post_url] - <?php _e('Reported post URL', 'wpforo') ?></li>
                    </ul>
                </th>
                <td>
		            <?php $wpe_args['textarea_name'] = 'wpforo_email_options[report_email_message]';
		            wp_editor( wpautop(wp_unslash( WPF()->sbscrb->options['report_email_message'] )), 'report_email_message', $wpe_args ); ?>
                </td>
            </tr>
            <tr>
                <td colspan="2" style="border-bottom:2px solid #ddd;">
                    <h3 style="font-weight:400; padding:10px 0 0 0; margin:0;"><?php _e('New User Registration Email for admins', 'wpforo'); ?></h3>
                    <p class="wpf-info"><?php _e('This message comes when new user registers to site', 'wpforo') ?></p>
                </td>
            </tr>
            <tr>
                <th>
                    <label><?php _e('Overwrite WordPress New User Registration Email for Admins', 'wpforo'); ?>:</label>
                </th>
                <td>
                    <div class="wpf-switch-field">
                        <input type="radio" value="1" name="wpforo_email_options[overwrite_new_user_notification_admin]" id="overwrite_new_user_notification_admin_1" <?php wpfo_check(WPF()->sbscrb->options['overwrite_new_user_notification_admin'], 1); ?>><label for="overwrite_new_user_notification_admin_1"><?php _e('Yes', 'wpforo'); ?></label> &nbsp;
                        <input type="radio" value="0" name="wpforo_email_options[overwrite_new_user_notification_admin]" id="overwrite_new_user_notification_admin_0" <?php wpfo_check(WPF()->sbscrb->options['overwrite_new_user_notification_admin'], 0); ?>><label for="overwrite_new_user_notification_admin_0"><?php _e('No', 'wpforo'); ?></label>
                    </div>
                </td>
            </tr>
            <tr>
                <th>
                    <label for="wp_new_user_notification_email_admin_subject"><?php _e('Message Subject', 'wpforo'); ?>:</label>
                </th>
                <td><input id="wp_new_user_notification_email_admin_subject" name="wpforo_email_options[wp_new_user_notification_email_admin_subject]" type="text"  value="<?php wpfo(WPF()->sbscrb->options['wp_new_user_notification_email_admin_subject']); ?>" required></td>
            </tr>
            <tr>
                <th>
                    <label for="wp_new_user_notification_email_admin_message"><?php _e('Message Body', 'wpforo'); ?>:</label>
                    <ul class="wpf-email-shortcodes">
                        <li>[blogname] - <?php _e('Website name', 'wpforo') ?></li>
                        <li>[user_login] - <?php _e('Registered user login', 'wpforo') ?></li>
                        <li>[user_email] - <?php _e('Registered user email', 'wpforo') ?></li>
                    </ul>
                </th>
                <td>
		            <?php $wpe_args['textarea_name'] = 'wpforo_email_options[wp_new_user_notification_email_admin_message]';
		            wp_editor( wpautop(wp_unslash( WPF()->sbscrb->options['wp_new_user_notification_email_admin_message'] )), 'wp_new_user_notification_email_admin_message', $wpe_args ); ?>
                </td>
            </tr>
            <tr>
                <td colspan="2" style="border-bottom:2px solid #ddd;">
                    <h3 style="font-weight:400; padding:10px 0 0 0; margin:0;"><?php _e('New User Registration Email for user', 'wpforo'); ?></h3>
                    <p class="wpf-info"><?php _e('This message comes when new user registers to site', 'wpforo') ?></p>
                </td>
            </tr>
            <tr>
                <th>
                    <label><?php _e('Overwrite WordPress New User Registration Email for Users', 'wpforo'); ?>:</label>
                </th>
                <td>
                    <div class="wpf-switch-field">
                        <input type="radio" value="1" name="wpforo_email_options[overwrite_new_user_notification]" id="overwrite_new_user_notification_1" <?php wpfo_check(WPF()->sbscrb->options['overwrite_new_user_notification'], 1); ?>><label for="overwrite_new_user_notification_1"><?php _e('Yes', 'wpforo'); ?></label> &nbsp;
                        <input type="radio" value="0" name="wpforo_email_options[overwrite_new_user_notification]" id="overwrite_new_user_notification_0" <?php wpfo_check(WPF()->sbscrb->options['overwrite_new_user_notification'], 0); ?>><label for="overwrite_new_user_notification_0"><?php _e('No', 'wpforo'); ?></label>
                    </div>
                </td>
            </tr>
            <tr>
                <th>
                    <label for="wp_new_user_notification_email_subject"><?php _e('Message Subject', 'wpforo'); ?>:</label>
                </th>
                <td><input id="wp_new_user_notification_email_subject" name="wpforo_email_options[wp_new_user_notification_email_subject]" type="text"  value="<?php wpfo(WPF()->sbscrb->options['wp_new_user_notification_email_subject']); ?>" required></td>
            </tr>
            <tr>
                <th>
                    <label for="wp_new_user_notification_email_message"><?php _e('Message Body', 'wpforo'); ?>:</label>
                    <ul class="wpf-email-shortcodes">
                        <li>[user_login] - <?php _e('Registered user login', 'wpforo') ?></li>
                        <li>[set_password_url] - <?php _e('Link to open password reset form', 'wpforo') ?></li>
                    </ul>
                </th>
                <td>
		            <?php $wpe_args['textarea_name'] = 'wpforo_email_options[wp_new_user_notification_email_message]';
		            wp_editor( wpautop(wp_unslash( WPF()->sbscrb->options['wp_new_user_notification_email_message'] )), 'wp_new_user_notification_email_message', $wpe_args ); ?>
                </td>
            </tr>
            <tr>
                <td colspan="2" style="border-bottom:2px solid #ddd;">
                    <h3 style="font-weight:400; padding:10px 0 0 0; margin:0;"><?php _e('Reset Password Emails', 'wpforo'); ?></h3>
                    <p class="wpf-info"><?php _e('This message comes from Reset Password form.', 'wpforo') ?></p>
                </td>
            </tr>
            <tr>
                <th>
                    <label><?php _e('Overwrite WordPress Reset Password Emails', 'wpforo'); ?>:</label>
                </th>
                <td>
                    <div class="wpf-switch-field">
                        <input type="radio" value="1" name="wpforo_email_options[overwrite_reset_password_email_message]" id="overwrite_reset_password_email_message_1" <?php wpfo_check(WPF()->sbscrb->options['overwrite_reset_password_email_message'], 1); ?>><label for="overwrite_reset_password_email_message_1"><?php _e('Yes', 'wpforo'); ?></label> &nbsp;
                        <input type="radio" value="0" name="wpforo_email_options[overwrite_reset_password_email_message]" id="overwrite_reset_password_email_message_0" <?php wpfo_check(WPF()->sbscrb->options['overwrite_reset_password_email_message'], 0); ?>><label for="overwrite_reset_password_email_message_0"><?php _e('No', 'wpforo'); ?></label>
                    </div>
                </td>
            </tr>
            <tr>
                <th>
                    <label for="reset_password_email_message"><?php _e('Reset Password message body', 'wpforo'); ?>:</label>
                    <ul class="wpf-email-shortcodes">
                        <li>[user_login] - <?php _e('Registered user login', 'wpforo') ?></li>
                        <li>[reset_password_url] - <?php _e('Link to open password reset form', 'wpforo') ?></li>
                    </ul>
                </th>
                <td>
		            <?php $wpe_args['textarea_name'] = 'wpforo_email_options[reset_password_email_message]';
		            wp_editor( wpautop(wp_unslash( WPF()->sbscrb->options['reset_password_email_message'] )), 'reset_password_email_message', $wpe_args ); ?>
                </td>
            </tr>
            <tr>
                <td colspan="2" style="border-bottom:2px solid #ddd;">
                    <h3 style="font-weight:400; padding:10px 0 0 0; margin:0;"><?php _e('User Mentioning Email', 'wpforo'); ?></h3>
                </td>
            </tr>
            <tr>
                <th>
                    <label><?php _e('Enable Email Notification', 'wpforo'); ?>:</label>
                </th>
                <td>
                    <div class="wpf-switch-field">
                        <input type="radio" value="1" name="wpforo_email_options[user_mention_notify]" id="user_mention_notify_1" <?php wpfo_check(WPF()->sbscrb->options['user_mention_notify'], 1); ?>><label for="user_mention_notify_1"><?php _e('Yes', 'wpforo'); ?></label> &nbsp;
                        <input type="radio" value="0" name="wpforo_email_options[user_mention_notify]" id="user_mention_notify_0" <?php wpfo_check(WPF()->sbscrb->options['user_mention_notify'], 0); ?>><label for="user_mention_notify_0"><?php _e('No', 'wpforo'); ?></label>
                    </div>
                </td>
            </tr>
            <tr>
                <th><label for="user_mention_email_subject"><?php _e('User Mention message subject', 'wpforo'); ?>:</label></th>
                <td><input id="user_mention_email_subject" name="wpforo_email_options[user_mention_email_subject]" type="text"  value="<?php wpfo(WPF()->sbscrb->options['user_mention_email_subject']); ?>" required></td>
            </tr>
            <tr>
                <th>
                    <label for="user_mention_email_message"><?php _e('User Mention message body', 'wpforo'); ?>:</label>
                    <ul class="wpf-email-shortcodes">
                        <li>[mentioned-user-name] - <?php _e('Mentioned user display name', 'wpforo') ?></li>
                        <li>[author-user-name] - <?php _e('Post author display name', 'wpforo') ?></li>
                        <li>[topic-title] - <?php _e('Topic title', 'wpforo') ?></li>
                        <li>[post-url] - <?php _e('Link to the post', 'wpforo') ?></li>
                        <li>[post-desc] - <?php _e('Mentioned post excerpt', 'wpforo') ?></li>
                    </ul>
                </th>
                <td>
		            <?php $wpe_args['textarea_name'] = 'wpforo_email_options[user_mention_email_message]';
		            wp_editor( wpautop(wp_unslash( WPF()->sbscrb->options['user_mention_email_message'] )), 'user_mention_email_message', $wpe_args ); ?>
                </td>
            </tr>
        </tbody>
    </table>
    <div class="wpforo_settings_foot">
        <input type="submit" class="button button-primary" value="<?php _e('Update Options', 'wpforo'); ?>" />
        <input type="submit" class="button" value="<?php _e('Reset Options', 'wpforo'); ?>" name="reset" onclick="return confirm('<?php wpforo_phrase('Do you really want to reset options?') ?>')" />
    </div>
</form>
