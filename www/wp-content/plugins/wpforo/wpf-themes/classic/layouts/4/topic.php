<?php
	// Exit if accessed directly
	if( !defined( 'ABSPATH' ) ) exit;
?>

<div class="wpfl-4">
    <div class="wpf-threads">
        <div class="wpf-threads-head">
            <div class="wpf-head-box wpf-thead-status"><?php wpforo_phrase( 'Status' ) ?></div>
            <div class="wpf-head-box wpf-thead-title"><?php wpforo_phrase( 'Topics' ) ?></div>
            <div class="wpf-head-box wpf-thead-posts"><?php wpforo_phrase( 'Replies' ) ?></div>
            <div class="wpf-head-box wpf-thead-views"><?php wpforo_phrase( 'Views' ) ?></div>
            <div class="wpf-head-box wpf-thead-users"><?php wpforo_phrase( 'Users' ) ?></div>
            <div class="wpf-head-box wpf-thead-date"><?php wpforo_phrase( 'Date' ) ?>&nbsp;</div>
        </div>
        <div class="wpf-thread-list" data-forumid="<?php echo intval($forum['forumid']) ?>" data-filter="newest" data-paged="1">
            <?php foreach( $topics as $key => $topic ): ?>
                <?php wpforo_thread_topic_template( $topic['topicid'] ); ?>
                <?php do_action( 'wpforo_loop_hook', $key ) ?>
            <?php endforeach; ?>
        </div>
    </div>
</div> <!-- wpfl-4 -->
