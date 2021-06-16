<?php
	// Exit if accessed directly
	if( !defined( 'ABSPATH' ) ) exit;
?>

<div class="wpfl-3">
    <div class="wpforo-post-head">
        <div class="wpf-left">&nbsp;
            <a href="<?php echo esc_url( wpforo_post($topic['last_post'], 'url') ); ?>" class="wpfcl-2"><i class="far fa-caret-square-down wpfsx wpfcl-3"></i> &nbsp; <span class="wpfcl-3"><?php wpforo_phrase('Last Post'); ?></span></a>
            <?php do_action( 'wpforo_topic_head_left', $forum, $topic ) ?>
        </div>
        <div class="wpf-right">
            <?php do_action( 'wpforo_topic_head_right', $forum, $topic ) ?>
            <?php wpforo_post_buttons( 'icon-text', 'tools', $forum ); ?>&nbsp;&nbsp;
	        <?php wpforo_feed_link(); ?>
		</div>
        <br class="wpf-clear" />
    </div>
    
    <?php wpforo_moderation_tools(); ?>
    <?php wpforo_check_threads($posts); ?>

	<?php foreach($posts as $key => $post ) : $is_topic = ( $key ? FALSE : TRUE ); ?>

		<div class="wpforo-qa-item-wrap">
            <?php if($post['parentid'] == 0): ?>
                <?php $member = wpforo_member($post); $post_url = wpforo_post($post['postid'],'url'); ?>
                <div id="post-<?php echo wpforo_bigintval($post['postid']) ?>" data-postid="<?php echo wpforo_bigintval($post['postid']) ?>" data-userid="<?php echo wpforo_bigintval($member['userid']) ?>" data-mention="<?php echo esc_attr( $member['user_nicename'] ) ?>" data-isowner="<?php echo esc_attr( (int) (bool) wpforo_is_owner($member['userid']) ) ?>" class="post-wrap wpfn-<?php echo ($key+1); ?><?php if( !$post['is_first_post'] ) echo ' wpf-answer-wrap'; else echo ' wpfp-first'; ?>" <?php echo ($key == 1) ? ' style="border-top:none;" ' : ''; ?>>
                    <?php wpforo_share_toggle($post_url, $post['body']); ?>
                    <div class="wpforo-post wpfcl-1">
                        <div class="wpf-left">
                            <div class="wpforo-post-voting">
                                <div class="wpf-positive">
                                    <?php wpforo_post_buttons( 'icon-text', 'positivevote', $forum, $topic, $post ); ?>
                                </div>
                                    <div class="wpf-vote-number">
                                        <?php WPF()->tpl->vote_count($post) ?>
                                    </div>
                                <div class="wpf-negative">
                                    <?php wpforo_post_buttons( 'icon-text', 'negativevote', $forum, $topic, $post ); ?>
                                </div>

                                <?php wpforo_post_buttons( 'icon-text', 'isanswer', $forum, $topic, $post ); ?>
                            </div>
                        </div><!-- left -->
                        <div class="wpf-right">
                            <div class="wpforo-post-content-top">
                                <?php if($post['status']): ?><span class="wpf-mod-message"><i class="fas fa-exclamation-circle" aria-hidden="true"></i> <?php wpforo_phrase('Awaiting moderation') ?></span><?php endif; ?>
                                <?php wpforo_share_toggle($post_url, $post['body'], 'top'); ?>
                                <div class="wpforo-post-link wpf-post-link">
                                    <?php wpforo_post_buttons( 'icon-text', 'link', $forum, $topic, $post ); ?>
                                </div>
                                <div class="wpforo-post-date"><?php wpforo_date($post['created'], 'd/m/Y g:i a'); ?></div>
	                            <?php wpforo_topic_starter($topic, $post) ?>
                                <div class="wpf-clear-right"></div>
                            </div>
                            <div class="wpforo-post-content">
                                <?php wpforo_content($post); ?>
                                <?php wpforo_post_edited($post); ?>
                                <?php do_action( 'wpforo_tpl_post_loop_after_content', $post, $member ) ?>
                                <?php if( wpforo_feature('signature') ): ?>
                                    <?php if( trim($member['signature'])): ?><div class="wpforo-post-signature"><?php wpforo_signature( $member ) ?></div><?php endif ?>
                                <?php endif; ?>
                            </div>
                            <div class="wpforo-post-author">
                                <div class="wpforo-post-lb-box">
                                    <?php
                                        if( $post['is_first_post'] ){
                                            $answer_button = ( WPF()->tpl->forms['qa_display_answer_editor'] ) ? 'style="display:none;"' : '';
                                            echo '<div class="wpf-answer-button" ' . $answer_button . '>';
                                            wpforo_post_buttons( 'icon-text', 'answer', $forum, $topic, $post );
                                            echo '</div>';
                                        }
                                        echo '<div class="wpf-add-comment-button" style="display:none;">';
                                        wpforo_post_buttons( 'icon-text', 'comment', $forum, $topic, $post );
                                        echo '</div>';
                                    ?>
                                </div>
                                <div class="wpforo-post-author-data">
                                    <div class="wpforo-box-l3a-wrap wpforo-post-author-data-content">
                                        <div class="wpforo-box-l3a-top"></div>
                                        <div class="wpforo-box-l3a-lr">
                                            <?php if(  WPF()->perm->usergroup_can('va') && wpforo_feature('avatars') ): $rsz =''; ?>
                                                <div class="wpforo-box-l3a-left"><?php echo WPF()->member->avatar($member, 'alt="'.esc_attr($member['display_name']).'"', 96) ?></div>
                                            <?php else: $rsz = 'style="margin-left:10px;"'; endif; ?>
                                            <div class="wpforo-box-l3a-right" <?php echo $rsz; //This is a HTML content// ?>>
                                                <span class="author-name"><?php wpforo_member_link($member); ?></span>&nbsp;<span><?php WPF()->member->show_online_indicator($member['userid']) ?></span><br />
                                                <?php wpforo_member_nicename($member, '@'); ?>
                                                <span class="author-title">
                                                    <?php wpforo_member_title($member) ?>
                                                </span><br />
                                                <?php wpforo_member_badge($member, ' | ') ?>
                                                <span class="author-posts"><?php echo intval($member['posts']) ?> <?php wpforo_phrase('Posts') ?></span><br />
                                                <span class="author-stat-item"><i class="fas fa-question-circle wpfcl-6" title="<?php wpforo_phrase('Questions') ?>"></i><?php echo intval($member['questions']) ?></span>
                                                <span class="author-stat-item"><i class="fas fa-check-square wpfcl-5" title="<?php wpforo_phrase('Answers') ?>"></i><?php echo intval($member['answers']) ?></span>
                                                <span class="author-stat-item"><i class="fas fa-comment wpfcl-0" title="<?php wpforo_phrase('Comments') ?>"></i><?php echo intval($member['comments']) ?></span>
                                            </div>
                                            <div class="wpf-clear"></div>
                                        </div>
                                    </div>
                                </div>
                            </div><!-- wpforo-post-author -->
                            <div class="wpforo-post-tool-bar">
                                <?php if( $post['is_first_post'] ){
                                    $buttons = array( 'sticky', 'private', 'close', 'approved', 'edit', 'delete', 'report' );
                                    wpforo_post_buttons( 'icon-text', $buttons, $forum, $topic, $post );
                                }else{
                                    $buttons = array( 'edit', 'approved', 'delete', 'report' );
                                    wpforo_post_buttons( 'icon-text', $buttons, $forum, $topic, $post );
                                } ?>
                            </div>
                        </div><!-- right -->
                        <div class="wpf-clear"></div>
                    </div><!-- wpforo-post -->
                </div><!-- post-wrap -->
                <?php
                $comment_count = 0;
                $row_count = ( WPF()->post->options['layout_qa_comments_limit_count'] ? WPF()->post->options['layout_qa_comments_limit_count'] : null );
	            $args = array(
		            'root'      => $post['postid'],
		            'row_count' => $row_count
	            );
                $comments = WPF()->post->get_posts( $args, $comment_count );
                if(is_array($comments) && !empty($comments)): ?>
                    <div class="wpforo-qa-comments">
                        <?php foreach($comments as $comment) wpforo_qa_comment_template($comment, $forum, $topic); ?>
                    </div>
                <?php endif; ?>

                <div class="wpforo-comment">
                    <div class="wpf-left"></div>
                    <div class="wpf-right" style="background: none;">
                        <div class="wpforo-qa-comments-footer">
                            <div class="wpforo-qa-comment-loadrest">
					            <?php if( $comment_count > ($count_comments = count($comments)) ) : ?>
                                    <a class="wpforo-qa-show-rest-comments" title="<?php wpforo_phrase('expand to show all comments on this post') ?>">
							            <?php printf( wpforo_phrase('show %d more comments', false), ($comment_count - $count_comments ) ) ?>
                                    </a>
					            <?php endif; ?>
                            </div>
                            <div class="wpf-add-comment-button">
					            <?php wpforo_post_buttons( 'icon-text', 'comment', $forum, $topic, $post ); ?>
                            </div>
                        </div>
                    </div>
                    <div class="wpf-clear"></div>
                </div>

                <div class="wpforo-comment">
                    <div class="wpf-left"></div>
                    <div class="wpf-right" style="background: none;">
                        <div class="wpforo-portable-form-wrap"></div>
                    </div>
                    <div class="wpf-clear"></div>
                </div>

                <?php if( $post['is_first_post'] ): ?>
                    <div class="wpforo-topic-meta">
                        <?php wpforo_tags( $topic ); ?>
                    </div>
                    <?php if($topic['answers']): ?>
                        <div class="wpf-answer-sep">
                            <div class="wpf-answer-title">
                                <?php ( $topic['answers'] > 1 ) ? printf( wpforo_phrase('%d Answers', false), $topic['answers'] ) : printf( wpforo_phrase('%d Answer', false), $topic['answers'] ) ; ?>
                            </div>
                            <div class="wpf-answer-filter">
                                <?php wpforo_posts_ordering_dropdown() ?>
                            </div>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>

                <?php do_action( 'wpforo_loop_hook', $key ) ?>
            <?php endif; ?>
        </div>
   <?php endforeach; ?>

    <?php if( $topic['answers'] && !WPF()->tpl->forms['qa_display_answer_editor'] ): ?>
    <div class="wpf-bottom-bar">
        <div class="wpf-answer-button">
            <?php wpforo_post_buttons( 'icon-text', 'answer', $forum, $topic ); ?>
        </div>
    </div>
    <?php endif; ?>
</div><!-- wpfl-3 -->