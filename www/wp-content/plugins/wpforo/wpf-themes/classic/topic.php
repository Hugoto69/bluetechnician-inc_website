<?php
if( WPF()->perm->forum_can('vf') && WPF()->perm->forum_can('enf') ):

	if( $forum = WPF()->current_object['forum'] ) : ?>
		<div class="wpf-subforum-sep" style="height:20px;"></div>
		<div class="wpf-head-bar">
			<div class="wpf-head-bar-left">
				<?php wpforo_forum_title($forum, '<h1 id="wpforo-title">', '</h1>') ?>
				<?php wpforo_forum_description($forum, '<div id="wpforo-description">', '</div>') ?>
				<div class="wpf-action-link">
					<?php WPF()->tpl->forum_subscribe_link() ?>
					<?php wpforo_feed_link('forum'); ?>
				</div>
			</div>
			<div class="wpf-head-bar-right">
				<?php wpforo_template_add_topic_button() ?>
			</div>
			<div class="wpf-clear"></div>
		</div>

		<?php if( WPF()->perm->forum_can('ct') ) WPF()->tpl->topic_form($forum);

		if( $topics = WPF()->current_object['topics'] ) {
			wpforo_template_pagenavi('wpf-navi-topic-top');
			include( wpftpl('layouts/' . $forum['cat_layout'] . '/topic.php') );
			wpforo_template_pagenavi('wpf-navi-topic-bottom');
			do_action( 'wpforo_topic_list_footer' );
		}else{ ?>
			<p class="wpf-p-error">
				<?php wpforo_phrase('No topics were found here') ?>
			</p>
			<?php
		}
	endif;

else : ?>
	<p class="wpf-p-error">
        <?php if(is_user_logged_in()): ?>
            <?php echo apply_filters('wpforo_no_forum_access_message_for_users', wpforo_phrase("Your user level does not have appropriate permission to view the content", false)); ?>
        <?php else: ?>
            <?php echo apply_filters('wpforo_no_forum_access_message_for_guests', wpforo_phrase("You do not have permission to view the content", false) . '<br>'. wpforo_get_login_or_register_notice_text()); ?>
        <?php endif; ?>
	</p>
<?php endif;