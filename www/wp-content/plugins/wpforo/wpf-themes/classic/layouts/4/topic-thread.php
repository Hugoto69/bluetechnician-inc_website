<?php
	// Exit if accessed directly
	if( !defined( 'ABSPATH' ) ) exit;

function wpforo_thread_topic_template( $topicid ){
    $thread = wpforo_thread( $topicid );
    if(empty($thread)) return;
    ?>
    <div class="wpf-thread <?php wpforo_unread($topicid, 'topic'); ?>">
        <div class="wpf-thread-body">
            <div class="wpf-thread-box wpf-thread-status">
                <div class="wpf-thread-statuses" <?php echo $thread['wrap']; ?>><?php echo $thread['icons_html']; ?></div>
            </div>
            <div class="wpf-thread-box wpf-thread-title">
                <span class="wpf-thread-status-mobile"><?php wpforo_topic_icon($thread); ?> </span>
                <?php wpforo_topic_title($thread, $thread['url'], '{p}{au}{tc}{/a}{n}{v}', true, '', WPF()->forum->options['layout_threaded_intro_topics_length']) ?>
                <?php wpforo_tags($thread, true, 'text') ?>
            </div>
            <div class="wpf-thread-box wpf-thread-posts">
                <?php echo wpforo_print_number((intval($thread['posts']) - 1)) ?>
            </div>
            <div class="wpf-thread-box wpf-thread-views">
                <?php echo wpforo_print_number($thread['views']) ?>
            </div>
            <div class="wpf-thread-box wpf-thread-users">
                <?php echo $thread['users_html']; ?>
                <div class="wpf-thread-date-mobile"><?php echo $thread['last_post_date'] ?></div>
            </div>
            <div class="wpf-thread-box wpf-thread-date">
                <?php echo $thread['last_post_date'] ?>
            </div>
        </div>
    </div>
    <?php
}
?>