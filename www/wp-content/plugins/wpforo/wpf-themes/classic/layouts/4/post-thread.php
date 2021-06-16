<?php

// Exit if accessed directly
if( !defined( 'ABSPATH' ) ) exit;

function wpforo_thread_reply_template( $post, $topic = array(), $forum = array(), $level = 0, $parents = array() ){
    $member = wpforo_member($post);
    ?>
    <div id="post-<?php echo wpforo_bigintval($post['postid']) ?>" data-postid="<?php echo wpforo_bigintval($post['postid']) ?>" data-userid="<?php echo wpforo_bigintval($member['userid']) ?>" data-mention="<?php echo esc_attr( $member['user_nicename'] ) ?>" data-isowner="<?php echo esc_attr( (int) (bool) wpforo_is_owner($member['userid']) ) ?>" class="reply-wrap">
        <div class="wpforo-post wpf-child-post wpfcl-1">
            <div class="wpf-reply-head">
                <?php if( WPF()->perm->usergroup_can('va') && wpforo_feature('avatars') ): ?>
                    <div class="wpf-author-avatar"><?php echo WPF()->member->avatar( $member, 'alt="'.esc_attr($member['display_name']).'"', 80 ) ?></div>
                <?php endif ?>
                <div class="wpf-author">
                    <div class="wpf-author-head">
                        <div class="wpf-author-name"><span><?php WPF()->member->show_online_indicator($member['userid']) ?></span>&nbsp;<?php wpforo_member_link($member); ?></div>
	                    <?php wpforo_topic_starter($topic, $post, 'icon') ?>
                        <div class="wpf-author-title"><?php wpforo_member_title($member, true, '', '', array('custom-title', 'rating-title')); ?></div>
                        <div id="wpforo-memberinfo-toggle-<?php echo intval($post['postid']) ?>" class="wpforo-membertoggle" title="<?php wpforo_phrase('More') ?>">
                            <i class="fas fa-caret-down" aria-hidden="true"></i>
                        </div>
                    </div>
                    <div id="wpforo-memberinfo-<?php echo intval($post['postid']) ?>" class="wpforo-memberinfo">
                        <?php wpforo_member_nicename($member, '@'); ?>
                        <div class="wpf-author-joined"><i class="fas fa-calendar-alt"></i> <?php wpforo_phrase('Joined') ?>: <?php wpforo_date($member['user_registered']); ?></div><br />
                        <?php wpforo_member_badge($member) ?>
                        <div class="wpf-author-title"><?php wpforo_member_title($member,true, '', '', array('usergroup')) ?></div>
                        <div class="wpf-author-posts"><?php wpforo_phrase('Posts') ?>: <?php echo intval($member['posts']) ?></div>
                        <div class="wpf-member-profile-buttons">
                            <?php WPF()->tpl->member_buttons($member) ?>
                            <?php WPF()->tpl->member_social_buttons($member) ?>
                        </div>
                    </div>
                </div>
                <div class="wpf-post-date"><?php wpforo_date($post['created'], 'M d, Y g:i a'); ?></div>
                <div class="wpf-post-btns">
                    <?php wpforo_post_buttons( 'icon', array('report', 'link'), $forum, $topic, $post ); ?>
                    <?php wpforo_share_toggle($post['url'], $post['body'], 'top'); ?>
                </div>
            </div>
            <div class="wpf-reply-content wpf-content">
                <div class="wpf-reply-tree">
                    <?php wpforo_thread_breadcrumb($post, $parents); ?>
                </div>
                <?php if($post['status']): ?>
                    <div class="wpf-mod">
                        <span class="wpf-mod-message"><i class="fas fa-exclamation-circle" aria-hidden="true"></i> <?php wpforo_phrase('Awaiting moderation') ?></span>
                    </div>
                <?php endif; ?>
                <div class="wpforo-post-content">
                    <?php wpforo_content($post); ?>
                </div>
                <div class="wpforo-post-meta">
                    <?php do_action( 'wpforo_tpl_post_loop_after_content', $post, $member ) ?>
                    <?php wpforo_post_edited($post); ?>
                    <?php if( wpforo_feature('signature') ): ?>
                        <?php if($member['signature']): ?><div class="wpforo-post-signature"><?php wpforo_signature( $member ) ?></div><?php endif; ?>
                    <?php endif; ?>
                </div>
                <div class="wpf-content-foot">
                    <div class="wpf-like">
                        <?php wpforo_like_button($post); ?>
                    </div>
                    <div class="wpf-reply">
                        <?php wpforo_post_buttons( 'icon-text', 'reply', $forum, $topic, $post ); ?>
                    </div>
                    <div class="wpf-buttons">
                        <?php if( $post['is_first_post'] ){
                            wpforo_post_buttons( 'icon', array( 'quote', 'solved', 'sticky', 'close', 'approved', 'private', 'edit', 'delete' ), $forum, $topic, $post );
                        }else{
                            wpforo_post_buttons( 'icon', array( 'quote', 'approved', 'edit', 'delete' ), $forum, $topic, $post );
                        } ?>
                    </div>
                </div>
                <div class="wpforo-post-footer">
                    <?php wpforo_post_likers($post['postid']); ?>
                </div>
                <div class="wpforo-portable-form-wrap"></div>
            </div>

        </div><!-- wpforo-post -->
    </div><!-- reply-wrap -->
    <?php
}