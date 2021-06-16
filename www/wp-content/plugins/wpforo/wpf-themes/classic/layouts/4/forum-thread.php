<?php
	// Exit if accessed directly
	if( !defined( 'ABSPATH' ) ) exit;

function wpforo_thread_forum_template( $topicid ){
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
                <div class="wpf-thread-forum-mobile"><i class="<?php echo $thread['forum']['icon'] ?>" style="color: <?php echo $thread['forum']['color'] ?>"></i>&nbsp; <?php echo esc_attr($thread['forum']['title'])?></div>
            </div>
            <div class="wpf-thread-box wpf-thread-forum">
                <span class="wpf-circle wpf-m" wpf-tooltip="<?php echo esc_attr($thread['forum']['title'])?>" wpf-tooltip-position="left" wpf-tooltip-size="long" style="border:1px dashed <?php echo $thread['forum']['color'] ?>"><i class="<?php echo $thread['forum']['icon'] ?>" style="color: <?php echo $thread['forum']['color'] ?>"></i></span>
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
