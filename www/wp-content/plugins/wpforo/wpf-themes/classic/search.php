<?php
	// Exit if accessed directly
	if( !defined( 'ABSPATH' ) ) exit;

	$args = WPF()->current_object['args'];
	$is_tag = wpfval($args, 'type') === 'tag';
	$posts = WPF()->current_object['posts'];
?>
<p id="wpforo-search-title">
    <?php if($is_tag): ?>
        <i class="fas fa-tag"></i> &nbsp;<?php wpforo_phrase('Tag') ?>:&nbsp;
    <?php else: ?>
        <?php wpforo_phrase('Search result for') ?>:&nbsp;
    <?php endif; ?>
    <span class="wpfcl-5"><?php echo esc_html(wpfval($args, 'needle')) ?></span>
</p>
<div class="wpforo-search-wrap <?php if( $is_tag ) echo 'wpforo-search-tag'?>">
    <div class="wpf-search-bar"><?php wpforo_post_search_form($args) ?></div><hr>
    <div class="wpf-snavi"><?php wpforo_template_pagenavi('', false) ?></div>
    <div class="wpforo-search-content">
        <table style="width: 100%;">
            <tr class="wpf-htr">
                <td class="wpf-shead-icon">#</td>
                <td class="wpf-shead-title" <?php if($is_tag) echo 'style="width: 50%;"' ?>><?php wpforo_phrase('Post Title') ?></td>
			    <?php if(!$is_tag): ?>
                    <td class="wpf-shead-result"><?php wpforo_phrase('Result Info') ?></td>
			    <?php endif; ?>
                <td class="wpf-shead-date"><?php wpforo_phrase('Date') ?></td>
                <td class="wpf-shead-user"><?php wpforo_phrase('User') ?></td>
			    <?php if(!$is_tag): ?>
                    <td class="wpf-shead-forum"><?php wpforo_phrase('Forum') ?></td>
			    <?php endif; ?>
            </tr>
		    <?php if( !empty($posts) ) : ?>
			    <?php foreach( $posts as $post ) :
				    if( !$post['title'] ) $post['title'] = wpforo_topic($post['topicid'], 'title'); ?>
                    <tr class="wpf-ttr">
                        <td class="wpf-spost-icon"><i class="fas fa-comments fa-1x wpfcl-0"></i></td>
                        <td class="wpf-spost-title">
                            <a href="<?php echo esc_url(WPF()->post->get_post_url($post['postid'])) ?>" title="<?php wpforo_phrase('View entire post') ?>"><?php echo esc_html($post['title']) ?> &nbsp;<i class="fas fa-chevron-right" style="font-size:11px;"></i></a>
                        </td>
					    <?php if(!$is_tag): ?>
                            <td class="wpf-spost-result wpfcl-5"><?php echo ( isset($post['matches']) ? ceil($post['matches']) : '' ) ?> <?php wpforo_phrase('relevance') ?></td>
					    <?php endif; ?>
                        <td class="wpf-spost-date"><?php wpforo_date($post['created']); ?></td>
                        <td class="wpf-spost-user"><?php wpforo_user_dname( wpforo_member($post), true ) ?></td>
					    <?php if(!$is_tag): ?>
                            <td class="wpf-spost-forum"><?php echo wpforo_forum($post['forumid'], 'title') ?></td>
					    <?php endif; ?>
                    </tr>
				    <?php if( !$is_tag ): ?>
                        <tr class="wpf-ptr">
                            <td class="wpf-spost-icon">&nbsp;</td>
                            <td colspan="5" class="wpf-stext">
                                <?php echo wpforo_sanitize_search_body($post['body'], $args['needle']); ?>
                            </td>
                        </tr>
                    <?php else: ?>
                        <tr class="wpf-ptr">
                            <td colspan="4">
                                <div class="wpf-search-tags"><?php wpforo_tags( $post['topicid'], false, 'small' ); ?><div class="wpf-clear"></div></div>
                            </td>
                        </tr>
                    <?php endif ?>
			    <?php endforeach ?>
            <?php else : ?>
                <tr class="wpf-ptr">
                    <td colspan="6"><p class="wpf-p-error"> <?php wpforo_phrase('Posts not found') ?> </p></td>
                </tr>
		    <?php endif ?>
        </table>
    </div>
    <div class="wpf-snavi"><?php wpforo_template_pagenavi('', false) ?></div>
</div>