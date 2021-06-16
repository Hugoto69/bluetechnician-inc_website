<?php
	// Exit if accessed directly
	if( !defined( 'ABSPATH' ) ) exit;
 
/**
 * wpForo Classic Theme Functions
 * @hook: action - 'init'
 * @description: only for wpForo theme functions. For WordPress theme functions use functions-wp.php file.
 * @theme: Classic
 */

include_once( wpftpl('layouts/4/forum-thread.php') );
include_once( wpftpl('layouts/4/topic-thread.php') );
include_once( wpftpl('layouts/4/post-thread.php') );

function wpforo_thread_reply( $post, $topic = array(), $forum = array(), $level = 0, $parents = array() ){
    if( wpfval($post, 'postid') ){
        $post = wpforo_post( $post['postid'] );
        wpforo_thread_reply_template( $post, $topic, $forum, $level, $parents );
    }
}

function wpforo_classic_forum_options(){
    if(WPF()->tpl->layout_exists(1)): ?>
        <tr>
            <th><label><?php _e('Extended Layout - Recent topics','wpforo'); ?> <a href="https://wpforo.com/docs/root/categories-and-forums/forum-layouts/extended-layout/" title="<?php _e('Read the documentation', 'wpforo') ?>" target="_blank"><i class="far fa-question-circle"></i></a></label></th>
            <td>
                <div class="wpf-switch-field">
                    <input id="show-tte" type="radio" name="wpforo_forum_options[layout_extended_intro_topics_toggle]" value="1" <?php wpfo_check(WPF()->forum->options['layout_extended_intro_topics_toggle'], 1); ?>/><label for="show-tte"><?php _e('Expanded','wpforo'); ?></label> &nbsp;
                    <input id="hide-tte" type="radio" name="wpforo_forum_options[layout_extended_intro_topics_toggle]" value="0" <?php wpfo_check(WPF()->forum->options['layout_extended_intro_topics_toggle'], 0); ?>/><label for="hide-tte"><?php _e('Collapsed','wpforo'); ?></label>
                </div>
            </td>
        </tr>
        <tr>
            <th><label for="tdcs"><?php _e('Extended Layout - Number of Recent topics','wpforo'); ?>  <a href="https://wpforo.com/docs/root/categories-and-forums/forum-layouts/extended-layout/" title="<?php _e('Read the documentation', 'wpforo') ?>" target="_blank"><i class="far fa-question-circle"></i></a></label></th>
            <td>
                <input id="tdcs" name="wpforo_forum_options[layout_extended_intro_topics_count]" type="number" min="0" value="<?php wpfo( WPF()->forum->options['layout_extended_intro_topics_count'] ) ?>" class="wpf-field-small" />
            </td>
        </tr>
        <tr>
            <th>
                <label for="tdcsl"><?php _e('Extended Layout - Recent topic length','wpforo'); ?>  <a href="https://wpforo.com/docs/root/categories-and-forums/forum-layouts/extended-layout/" title="<?php _e('Read the documentation', 'wpforo') ?>" target="_blank"><i class="far fa-question-circle"></i></a></label>
                <p class="wpf-info"><?php _e('Set this option value 0 if you want to show the whole title in recent topic area.','wpforo'); ?></p>
            </th>
            <td>
                <input id="tdcsl" name="wpforo_forum_options[layout_extended_intro_topics_length]" type="number" min="0" value="<?php wpfo( WPF()->forum->options['layout_extended_intro_topics_length'] ) ?>" class="wpf-field-small" />
            </td>
        </tr>
    <?php endif;
    if(WPF()->tpl->layout_exists(3)): ?>
		<?php 
        if(!isset(WPF()->forum->options['layout_qa_intro_topics_toggle'])) WPF()->forum->options['layout_qa_intro_topics_toggle'] = 1;
        if(!isset(WPF()->forum->options['layout_qa_intro_topics_count'])) WPF()->forum->options['layout_qa_intro_topics_count'] = 3;
        ?>
        <tr>
            <th><label><?php _e('Q&A Layout - Recent topics','wpforo'); ?> <a href="https://wpforo.com/docs/root/categories-and-forums/forum-layouts/question-answer-layout/" title="<?php _e('Read the documentation', 'wpforo') ?>" target="_blank"><i class="far fa-question-circle"></i></a></label></th>
            <td>
                <div class="wpf-switch-field">
                    <input id="show-ttq" type="radio" name="wpforo_forum_options[layout_qa_intro_topics_toggle]" value="1" <?php wpfo_check(WPF()->forum->options['layout_qa_intro_topics_toggle'], 1); ?>/><label for="show-ttq"><?php _e('Expanded','wpforo'); ?></label> &nbsp;
                    <input id="hide-ttq" type="radio" name="wpforo_forum_options[layout_qa_intro_topics_toggle]" value="0" <?php wpfo_check(WPF()->forum->options['layout_qa_intro_topics_toggle'], 0); ?>/><label for="hide-ttq"><?php _e('Collapsed','wpforo'); ?></label>
                </div>
            </td>
        </tr>
        <tr>
            <th><label for="tdcq"><?php _e('Q&A Layout - Number of Recent topics','wpforo'); ?> <a href="https://wpforo.com/docs/root/categories-and-forums/forum-layouts/question-answer-layout/" title="<?php _e('Read the documentation', 'wpforo') ?>" target="_blank"><i class="far fa-question-circle"></i></a></label></th>
            <td>
                <input id="tdcq" name="wpforo_forum_options[layout_qa_intro_topics_count]" type="number" min="0" value="<?php wpfo( WPF()->forum->options['layout_qa_intro_topics_count'] ) ?>" class="wpf-field-small" />
            </td>
        </tr>
        <tr>
            <th>
                <label for="tdcql"><?php _e('Q&A Layout - Recent topic length','wpforo'); ?> <a href="https://wpforo.com/docs/root/categories-and-forums/forum-layouts/question-answer-layout/" title="<?php _e('Read the documentation', 'wpforo') ?>" target="_blank"><i class="far fa-question-circle"></i></a></label>
                <p class="wpf-info"><?php _e('Set this option value 0 if you want to show the whole title in recent topic area.','wpforo'); ?></p>
            </th>
            <td>
                <input id="tdcql" name="wpforo_forum_options[layout_qa_intro_topics_length]" type="number" min="0" value="<?php wpfo( WPF()->forum->options['layout_qa_intro_topics_length'] ) ?>" class="wpf-field-small" />
            </td>
        </tr>
    <?php endif;
	if(WPF()->tpl->layout_exists(4)): ?>
        <tr>
            <th><label><?php _e('Threaded Layout - Forums List','wpforo'); ?> <a href="https://wpforo.com/docs/root/categories-and-forums/forum-layouts/threaded-layout/" title="<?php _e('Read the documentation', 'wpforo') ?>" target="_blank"><i class="far fa-question-circle"></i></a></label></th>
            <td>
                <div class="wpf-switch-field">
                    <input id="show-tte4" type="radio" name="wpforo_forum_options[layout_threaded_intro_topics_toggle]" value="1" <?php wpfo_check(WPF()->forum->options['layout_threaded_intro_topics_toggle'], 1); ?>/><label for="show-tte4"><?php _e('Expanded','wpforo'); ?></label> &nbsp;
                    <input id="hide-tte4" type="radio" name="wpforo_forum_options[layout_threaded_intro_topics_toggle]" value="0" <?php wpfo_check(WPF()->forum->options['layout_threaded_intro_topics_toggle'], 0); ?>/><label for="hide-tte4"><?php _e('Collapsed','wpforo'); ?></label>
                </div>
            </td>
        </tr>
        <tr>
            <th><label><?php _e('Threaded Layout - Display Sub Forums with Parent Forums','wpforo'); ?> <a href="https://wpforo.com/docs/root/categories-and-forums/forum-layouts/threaded-layout/" title="<?php _e('Read the documentation', 'wpforo') ?>" target="_blank"><i class="far fa-question-circle"></i></a></label></th>
            <td>
                <div class="wpf-switch-field">
                    <input id="show-dsf4" type="radio" name="wpforo_forum_options[layout_threaded_display_subforums]" value="1" <?php wpfo_check(WPF()->forum->options['layout_threaded_display_subforums'], 1); ?>/><label for="show-dsf4"><?php _e('Yes','wpforo'); ?></label> &nbsp;
                    <input id="hide-dsf4" type="radio" name="wpforo_forum_options[layout_threaded_display_subforums]" value="0" <?php wpfo_check(WPF()->forum->options['layout_threaded_display_subforums'], 0); ?>/><label for="hide-dsf4"><?php _e('No','wpforo'); ?></label>
                </div>
            </td>
        </tr>
        <tr>
            <th><label><?php _e('Threaded Layout - Display Thread Filtering Buttons','wpforo'); ?> <a href="https://wpforo.com/docs/root/categories-and-forums/forum-layouts/threaded-layout/" title="<?php _e('Read the documentation', 'wpforo') ?>" target="_blank"><i class="far fa-question-circle"></i></a></label></th>
            <td>
                <div class="wpf-switch-field">
                    <input id="layout_threaded_filter_buttons_1" type="radio" name="wpforo_forum_options[layout_threaded_filter_buttons]" value="1" <?php wpfo_check(WPF()->forum->options['layout_threaded_filter_buttons'], 1); ?>/><label for="layout_threaded_filter_buttons_1"><?php _e('Yes','wpforo'); ?></label> &nbsp;
                    <input id="layout_threaded_filter_buttons_0" type="radio" name="wpforo_forum_options[layout_threaded_filter_buttons]" value="0" <?php wpfo_check(WPF()->forum->options['layout_threaded_filter_buttons'], 0); ?>/><label for="layout_threaded_filter_buttons_0"><?php _e('No','wpforo'); ?></label>
                </div>
            </td>
        </tr>
        <tr>
            <th><label><?php _e('Threaded Layout - Display Add Topic Button on Forum List','wpforo'); ?> <a href="https://wpforo.com/docs/root/categories-and-forums/forum-layouts/threaded-layout/" title="<?php _e('Read the documentation', 'wpforo') ?>" target="_blank"><i class="far fa-question-circle"></i></a></label></th>
            <td>
                <div class="wpf-switch-field">
                    <input id="layout_threaded_add_topic_button_1" type="radio" name="wpforo_forum_options[layout_threaded_add_topic_button]" value="1" <?php wpfo_check(WPF()->forum->options['layout_threaded_add_topic_button'], 1); ?>/><label for="layout_threaded_add_topic_button_1"><?php _e('Yes','wpforo'); ?></label> &nbsp;
                    <input id="layout_threaded_add_topic_button_0" type="radio" name="wpforo_forum_options[layout_threaded_add_topic_button]" value="0" <?php wpfo_check(WPF()->forum->options['layout_threaded_add_topic_button'], 0); ?>/><label for="layout_threaded_add_topic_button_0"><?php _e('No','wpforo'); ?></label>
                </div>
            </td>
        </tr>
        <tr>
            <th><label for="tdcs4"><?php _e('Threaded Layout - Number of Recent topics','wpforo'); ?>  <a href="https://wpforo.com/docs/root/categories-and-forums/forum-layouts/threaded-layout/" title="<?php _e('Read the documentation', 'wpforo') ?>" target="_blank"><i class="far fa-question-circle"></i></a></label></th>
            <td>
                <input id="tdcs4" name="wpforo_forum_options[layout_threaded_intro_topics_count]" type="number" min="1" value="<?php wpfo( WPF()->forum->options['layout_threaded_intro_topics_count'] ) ?>" class="wpf-field-small" />
            </td>
        </tr>
        <tr>
            <th>
                <label for="tdcsl4"><?php _e('Threaded Layout - Recent topic length','wpforo'); ?>  <a href="https://wpforo.com/docs/root/categories-and-forums/forum-layouts/threaded-layout/" title="<?php _e('Read the documentation', 'wpforo') ?>" target="_blank"><i class="far fa-question-circle"></i></a></label>
                <p class="wpf-info"><?php _e('Set this option value 0 if you want to show the whole title in recent topic area.','wpforo'); ?></p>
            </th>
            <td>
                <input id="tdcsl4" name="wpforo_forum_options[layout_threaded_intro_topics_length]" type="number" min="0" value="<?php wpfo( WPF()->forum->options['layout_threaded_intro_topics_length'] ) ?>" class="wpf-field-small" />
            </td>
        </tr>
	<?php endif;
}
add_action('wpforo_settings_forums', 'wpforo_classic_forum_options');

function wpforo_classic_post_options(){
	if(WPF()->tpl->layout_exists(1)): ?>
        <tr>
            <th><label><?php _e('Extended Layout - Recent posts','wpforo'); ?> <a href="https://wpforo.com/docs/root/wpforo-settings/topic-post-settings/" title="<?php _e('Read the documentation', 'wpforo') ?>" target="_blank"><i class="far fa-question-circle"></i></a></label></th>
            <td>
                <div class="wpf-switch-field">
                    <input id="show-tte" type="radio" name="wpforo_post_options[layout_extended_intro_posts_toggle]" value="1" <?php wpfo_check(WPF()->post->options['layout_extended_intro_posts_toggle'], 1); ?>/><label for="show-tte"><?php _e('Expanded','wpforo'); ?></label> &nbsp;
                    <input id="hide-tte" type="radio" name="wpforo_post_options[layout_extended_intro_posts_toggle]" value="0" <?php wpfo_check(WPF()->post->options['layout_extended_intro_posts_toggle'], 0); ?>/><label for="hide-tte"><?php _e('Collapsed','wpforo'); ?></label>
                </div>
            </td>
        </tr>
        <tr>
            <th>
            	<label for="tdcs"><?php _e('Extended Layout - Number of Recent posts','wpforo'); ?> <a href="https://wpforo.com/docs/root/wpforo-settings/topic-post-settings/" title="<?php _e('Read the documentation', 'wpforo') ?>" target="_blank"><i class="far fa-question-circle"></i></a></label></label>
            	<p class="wpf-info"><?php _e('Set this option value 0 if you want to show all posts in recent posts area.','wpforo'); ?></p>
            </th>
            <td>
                <input id="tdcs" name="wpforo_post_options[layout_extended_intro_posts_count]" type="number" min="0" value="<?php wpfo( WPF()->post->options['layout_extended_intro_posts_count'] ) ?>" class="wpf-field-small" />
            </td>
        </tr>
        <tr>
            <th>
            	<label for="tdcsl"><?php _e('Extended Layout - Recent post length','wpforo'); ?></label>
            	<p class="wpf-info"><?php _e('Set this option value 0 if you want to show the whole post content in recent post area.','wpforo'); ?></p>
            </th>
            <td>
                <input id="tdcsl" name="wpforo_post_options[layout_extended_intro_posts_length]" type="number" min="0" value="<?php wpfo( WPF()->post->options['layout_extended_intro_posts_length'] ) ?>" class="wpf-field-small" />
            </td>
        </tr>
	<?php endif;
	if(WPF()->tpl->layout_exists(3)): ?>
        <tr>
            <th style="width:30%;"><label><?php _e('Q&A Layout - Comment Form Type', 'wpforo'); ?></label></th>
            <td>
                <div class="wpf-switch-field">
                    <input type="radio" value="1" name="wpforo_form_options[qa_comments_rich_editor]" id="qa_comments_rich_editor_1" <?php wpfo_check(WPF()->tpl->forms['qa_comments_rich_editor'], 1); ?>><label for="qa_comments_rich_editor_1"><?php _e('Visual Editor', 'wpforo'); ?></label> &nbsp;
                    <input type="radio" value="0" name="wpforo_form_options[qa_comments_rich_editor]" id="qa_comments_rich_editor_0" <?php wpfo_check(WPF()->tpl->forms['qa_comments_rich_editor'], 0); ?>><label for="qa_comments_rich_editor_0"><?php _e('Text Editor', 'wpforo'); ?></label>
                </div>
            </td>
        </tr>
        <tr>
            <th style="width:30%;"><label><?php _e('Q&A Layout - Display Answer Editor', 'wpforo'); ?></label></th>
            <td>
                <div class="wpf-switch-field">
                    <input type="radio" value="1" name="wpforo_form_options[qa_display_answer_editor]" id="qa_display_answer_editor_1" <?php wpfo_check(WPF()->tpl->forms['qa_display_answer_editor'], 1); ?>><label for="qa_display_answer_editor_1"><?php _e('Yes', 'wpforo'); ?></label> &nbsp;
                    <input type="radio" value="0" name="wpforo_form_options[qa_display_answer_editor]" id="qa_display_answer_editor_0" <?php wpfo_check(WPF()->tpl->forms['qa_display_answer_editor'], 0); ?>><label for="qa_display_answer_editor_0"><?php _e('No', 'wpforo'); ?></label>
                </div>
            </td>
        </tr>
        <tr>
            <th>
                <label for="layout_qa_posts_per_page"><?php _e('Q&A Layout - Number of Answers per Page','wpforo'); ?></label>
            </th>
            <td>
                <input id="layout_qa_posts_per_page" name="wpforo_post_options[layout_qa_posts_per_page]" type="number" min="3" value="<?php wpfo( WPF()->post->options['layout_qa_posts_per_page'] ) ?>" class="wpf-field-small" />
            </td>
        </tr>
        <tr>
            <th>
                <label for="lqaclc"><?php _e('Q&A Layout - Number of Comments per item in page load','wpforo'); ?></label>
                <p class="wpf-info"><?php _e('Set this option value 0 if you want to show all comments','wpforo'); ?></p>
            </th>
            <td>
                <input id="lqaclc" name="wpforo_post_options[layout_qa_comments_limit_count]" type="number" min="0" value="<?php wpfo( WPF()->post->options['layout_qa_comments_limit_count'] ) ?>" class="wpf-field-small" />
            </td>
        </tr>
        <tr>
            <th>
                <label for="layout_qa_first_post_reply"><?php _e('Q&A Layout - First Post Reply Button','wpforo'); ?></label>
            </th>
            <td>
                <div class="wpf-switch-field">
                    <input type="radio" value="1" name="wpforo_post_options[layout_qa_first_post_reply]" id="layout_qa_first_post_reply_1" <?php wpfo_check(WPF()->post->options['layout_qa_first_post_reply'], 1); ?>><label for="layout_qa_first_post_reply_1"><?php _e('Yes', 'wpforo'); ?></label> &nbsp;
                    <input type="radio" value="0" name="wpforo_post_options[layout_qa_first_post_reply]" id="layout_qa_first_post_reply_0" <?php wpfo_check(WPF()->post->options['layout_qa_first_post_reply'], 0); ?>><label for="layout_qa_first_post_reply_0"><?php _e('No', 'wpforo'); ?></label>
                </div>
            </td>
        </tr>
        <tr>
            <th>
                <label for="comment_body_min_length"><?php _e('Comment content minimum length', 'wpforo'); ?></label>
            </th>
            <td><input id="comment_body_min_length" type="number" min="2" name="wpforo_post_options[comment_body_min_length]" value="<?php wpfo(WPF()->post->options['comment_body_min_length']) ?>" class="wpf-field-small" /></td>
        </tr>
        <tr>
            <th>
                <label for="comment_body_max_length"><?php _e('Comment content maximum length', 'wpforo'); ?></label>
                <p class="wpf-info"><?php _e('Set this option value 0 if you want to remove this limit.', 'wpforo') ?></p>
            </th>
            <td><input id="comment_body_max_length" type="number" min="0" name="wpforo_post_options[comment_body_max_length]" value="<?php wpfo(WPF()->post->options['comment_body_max_length']) ?>" class="wpf-field-small" /></td>
        </tr>
    <?php endif;
    if(WPF()->tpl->layout_exists(4)): ?>
        <tr>
            <th style="width:30%;"><label><?php _e('Threaded Layout - Reply Form Type', 'wpforo'); ?></label></th>
            <td>
                <div class="wpf-switch-field">
                    <input type="radio" value="1" name="wpforo_form_options[threaded_reply_rich_editor]" id="threaded_reply_rich_editor_1" <?php wpfo_check(WPF()->tpl->forms['threaded_reply_rich_editor'], 1); ?>><label for="threaded_reply_rich_editor_1"><?php _e('Visual Editor', 'wpforo'); ?></label> &nbsp;
                    <input type="radio" value="0" name="wpforo_form_options[threaded_reply_rich_editor]" id="threaded_reply_rich_editor_0" <?php wpfo_check(WPF()->tpl->forms['threaded_reply_rich_editor'], 0); ?>><label for="threaded_reply_rich_editor_0"><?php _e('Text Editor', 'wpforo'); ?></label>
                </div>
            </td>
        </tr>
        <tr>
            <th>
                <label for="layout_threaded_posts_per_page"><?php _e('Threaded Layout - Number of Parent Posts per Page','wpforo'); ?></label>
            </th>
            <td>
                <input id="layout_threaded_posts_per_page" name="wpforo_post_options[layout_threaded_posts_per_page]" type="number" min="3" value="<?php wpfo( WPF()->post->options['layout_threaded_posts_per_page'] ) ?>" class="wpf-field-small" />
            </td>
        </tr>
        <tr>
            <th>
                <label for="layout_threaded_nesting_level"><?php _e('Threaded Layout - Replies Nesting Levels Deep','wpforo'); ?></label>
            </th>
            <td>
                <select name="wpforo_post_options[layout_threaded_nesting_level]" id="layout_threaded_nesting_level" style="min-width: 181px; margin-left: 1px;">
                    <?php for($i = 0; $i <= 7; $i++): ?>
                        <option value="<?php echo $i ?>" <?php wpfo_check(WPF()->post->options['layout_threaded_nesting_level'], $i, 'selected') ?>><?php echo $i ?></option>
                    <?php endfor; ?>
                </select>
            </td>
        </tr>
        <tr>
            <th>
                <label for="layout_threaded_first_post_reply"><?php _e('Threaded Layout - First Post Reply Button','wpforo'); ?></label>
            </th>
            <td>
                <div class="wpf-switch-field">
                    <input type="radio" value="1" name="wpforo_post_options[layout_threaded_first_post_reply]" id="layout_threaded_first_post_reply_1" <?php wpfo_check(WPF()->post->options['layout_threaded_first_post_reply'], 1); ?>><label for="layout_threaded_first_post_reply_1"><?php _e('Yes', 'wpforo'); ?></label> &nbsp;
                    <input type="radio" value="0" name="wpforo_post_options[layout_threaded_first_post_reply]" id="layout_threaded_first_post_reply_0" <?php wpfo_check(WPF()->post->options['layout_threaded_first_post_reply'], 0); ?>><label for="layout_threaded_first_post_reply_0"><?php _e('No', 'wpforo'); ?></label>
                </div>
            </td>
        </tr>
    <?php endif; ?>
    <?php if( $layouts = WPF()->tpl->find_layouts( WPFORO_THEME ) ) : ?>
        <tr>
            <th style="width:30%;">
                <label><?php _e('Stick Topic\'s First Post on Top for Certain Forum Layout', 'wpforo'); ?></label>
                <p class="wpf-info"><?php _e('This option keeps the first topic post on top when you navigate through pages of that topic. You can manage this option by forum layouts.','wpforo'); ?></p>
            </th>
            <td>
                <table style="margin-left: -1px;">
                    <?php foreach ($layouts as $layout) : ?>
                        <tr style="background-color: transparent;">
                            <td style="border-bottom: 1px dashed #aaaaaa; padding-left: 0px;">
                                <div class="wpf-switch-field">
                                    <input type="radio" value="1" name="wpforo_post_options[union_first_post][<?php echo $layout['id'] ?>]" id="<?php echo esc_attr($layout['name']) ?>-<?php echo $layout['id'] ?>_1" <?php wpfo_check(WPF()->post->get_option_union_first_post($layout['id']), true); ?>><label for="<?php echo esc_attr($layout['name']) ?>-<?php echo $layout['id'] ?>_1"><?php _e('Yes', 'wpforo'); ?></label> &nbsp;
                                    <input type="radio" value="0" name="wpforo_post_options[union_first_post][<?php echo $layout['id'] ?>]" id="<?php echo esc_attr($layout['name']) ?>-<?php echo $layout['id'] ?>_0" <?php wpfo_check(WPF()->post->get_option_union_first_post($layout['id']), false); ?>><label for="<?php echo esc_attr($layout['name']) ?>-<?php echo $layout['id'] ?>_0"><?php _e('No', 'wpforo'); ?></label>
                                </div>
                            </td>
                            <th style="border-bottom: 1px dashed #aaaaaa; width: auto;"><label style="font-weight: normal;"><?php echo $layout['name'] ?></label></th>
                        </tr>
                    <?php endforeach; ?>
                </table>
            </td>
        </tr>
        <?php
    endif;
}
add_action('wpforo_settings_post_top', 'wpforo_classic_post_options');

function wpforo_classic_reply_form_head($string, $args){
	if( $args['layout'] === 3 && WPF()->tpl->layout_exists(3) ){
	    $string = wpforo_phrase('Your Answer', false, 'default');
	}
	return $string;
}
add_filter('wpforo_reply_form_head', 'wpforo_classic_reply_form_head', 1, 2);

function wpforo_classic_reply_form_field_title($string, $args){
	if( WPF()->tpl->layout_exists(3) ){
        $can_answer = true;
		if( $args['layout'] === 3 ){
		    if( wpfval($args, 'topicid') ){
                $can_answer = WPF()->topic->can_answer( $args['topicid'] );
            }
            if( $can_answer ){
                $string = preg_replace('|[^\:]+\:|is', wpforo_phrase('Answer to', false, 'default') . ':', $string);
            }
		}
	}
	return $string;
}
add_filter('wpforo_reply_form_field_title', 'wpforo_classic_reply_form_field_title', 1, 2);

function wpforo_classic_get_body_class(){
    $class = '';
	if(isset(WPF()->tpl->options['style'])) $class = 'wpf-' . esc_attr(WPF()->tpl->options['style']);
	if(isset(WPF()->current_object['template'])) $class .= ' wpft-' . esc_attr(WPF()->current_object['template']);
	return $class;
}

function wpforo_classic_wrap_class(){
	echo wpforo_classic_get_body_class();
}
add_action('wpforo_wrap_class', 'wpforo_classic_wrap_class');

function wpforo_classic_body_class($classes){
    if( $body_class = wpforo_classic_get_body_class() ) $classes[] = $body_class;
    return $classes;
}
add_filter('body_class', 'wpforo_classic_body_class');

function wpforo_login_status_class(){
    echo ( WPF()->current_userid ) ? ' wpf-auth' : ' wpf-guest';
}
add_action('wpforo_wrap_class', 'wpforo_login_status_class');

function wpforo_forum_layout_editors( $settings, $editor ){
    if( $editor == 'post' && 4 == wpfval(WPF()->current_object, 'layout')){
	    $settings['tinymce']['toolbar1'] = 'fontsizeselect,bold,italic,underline,forecolor,bullist,numlist,alignleft,aligncenter,alignright,link,unlink,blockquote,pre,wpf_spoil,pastetext,source_code,emoticons';
	    $settings['editor_height'] = 100;
    }
	return $settings;
}
add_filter('wpforo_editor_settings', 'wpforo_forum_layout_editors', 1, 2);