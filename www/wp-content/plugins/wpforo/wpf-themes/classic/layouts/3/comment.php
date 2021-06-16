<?php
// Exit if accessed directly
if( !defined( 'ABSPATH' ) ) exit;
?>

<?php function wpforo_qa_comment_template($comment, $forum = array(), $topic = array()){
	if( !$forum ) $forum = (array) wpfval(WPF()->current_object, 'forum');
	$comment_member = wpforo_member($comment); ?>
	<div id="post-<?php echo wpforo_bigintval($comment['postid']) ?>" data-postid="<?php echo wpforo_bigintval($comment['postid']) ?>" data-userid="<?php echo wpforo_bigintval($comment_member['userid']) ?>" data-mention="<?php echo esc_attr( $comment_member['user_nicename'] ) ?>" data-isowner="<?php echo esc_attr( (int) (bool) wpforo_is_owner($comment_member['userid']) ) ?>" class="comment-wrap">
		<div class="wpforo-comment wpfcl-1">
			<div class="wpf-left">
				<div class="wpf-comment-icon wpfcl-0"><i class="fas fa-reply fa-rotate-180"></i></div>
			</div>
			<div class="wpf-right">
				<div class="wpforo-comment-content">
					<?php if($comment['status']): ?>
                        <div class="wpf-mod-message">
                            <i class="fas fa-exclamation-circle" aria-hidden="true"></i> <?php wpforo_phrase('Awaiting moderation') ?>
                        </div>
                    <?php endif; ?>
					<div class="wpforo-comment-text"><?php wpforo_content($comment); ?></div>
					<div class="wpforo-comment-footer">
	                      <span class="wpfcl-0">
                              <?php wpforo_member_link($comment_member, 'by'); ?>
                              <?php wpforo_topic_starter($topic, $comment) ?>
                              <?php wpforo_date($comment['created'], 'd/m/Y g:i a'); ?>
                          </span>
						<?php do_action( 'wpforo_tpl_post_loop_after_content', $comment, $comment_member ) ?>
						<?php wpforo_post_edited($comment); ?>
					</div>
					<div class="wpforo-comment-action-links">&nbsp;
						<?php
						$buttons = array( 'report', 'approved', 'edit', 'delete', 'link' );
						WPF()->tpl->buttons( $buttons, $forum, $comment, $comment );
						?>
					</div>
				</div>
			</div><!-- right -->
			<div class="wpf-clear"></div>
		</div><!-- wpforo-post -->
	</div><!-- comment-wrap -->
	<?php
}