<?php
	// Exit if accessed directly
	if( !defined( 'ABSPATH' ) ) exit;

	$fields = wpforo_profile_fields();
    $stat_topics = wpfval(WPF()->current_object['user'], 'stat', 'topics');
    $stat_topics = $stat_topics ? (int) wpforo_print_number($stat_topics) : 0;

    $stat_rating = wpfval(WPF()->current_object['user'], 'stat', 'rating');
    $stat_rating = $stat_rating ? $stat_rating : WPF()->member->rating_level( wpfval(WPF()->current_object['user'], 'posts'), false );
?>

<div class="wpforo-profile-home">

    <div class="wpf-profile-section wpf-mi-section">
        <div class="wpf-table">
            <?php wpforo_fields( $fields ); ?>
        </div>
    </div>

	<?php if( WPF()->perm->usergroup_can('vmr') ): ?>
        <div class="wpf-profile-section wpf-ma-section">
            <div class="wpf-profile-section-head">
            	<i class="far fa-chart-bar"></i>
				<?php wpforo_phrase('Member Activity'); ?>
            </div>
            <div class="wpf-profile-section-body">
                <div class="wpf-statbox wpfbg-9">
                    <div class="wpf-statbox-body">
                        <div class="wpf-statbox-icon wpfcl-5"><i class="fas fa-pencil-alt"></i></div>
                        <div class="wpf-statbox-value"><?php wpforo_print_number(wpfval(WPF()->current_object['user'], 'posts'), true) ?></div>
                        <div class="wpf-statbox-title"><?php wpforo_phrase('Forum Posts') ?></div>
                    </div>
                </div>
                <div class="wpf-statbox wpfbg-9">
                    <div class="wpf-statbox-body">
                        <div class="wpf-statbox-icon wpfcl-5"><i class="fas fa-file-alt"></i></div>
                        <div class="wpf-statbox-value"><?php echo $stat_topics ?></div>
                        <div class="wpf-statbox-title"><?php wpforo_phrase('Topics') ?></div>
                    </div>
                </div>
                <div class="wpf-statbox wpfbg-9">
                    <div class="wpf-statbox-body">
                        <div class="wpf-statbox-icon wpfcl-5"><i class="fas fa-question"></i></div>
                        <div class="wpf-statbox-value"><?php wpforo_print_number(wpfval(WPF()->current_object['user'], 'questions'), true) ?></div>
                        <div class="wpf-statbox-title"><?php wpforo_phrase('Questions') ?></div>
                    </div>
                </div>
                <div class="wpf-statbox wpfbg-9">
                    <div class="wpf-statbox-body">
                        <div class="wpf-statbox-icon wpfcl-5"><i class="fas fa-check"></i></div>
                        <div class="wpf-statbox-value"><?php wpforo_print_number(wpfval(WPF()->current_object['user'], 'answers'), true) ?></div>
                        <div class="wpf-statbox-title"><?php wpforo_phrase('Answers') ?></div>
                    </div>
                </div>
                <div class="wpf-statbox wpfbg-9">
                    <div class="wpf-statbox-body">
                        <div class="wpf-statbox-icon wpfcl-5"><i class="fas fa-comment"></i></div>
                        <div class="wpf-statbox-value"><?php wpforo_print_number(wpfval(WPF()->current_object['user'], 'comments'), true) ?></div>
                        <div class="wpf-statbox-title"><?php wpforo_phrase('Question Comments') ?></div>
                    </div>
                </div>
                <div class="wpf-statbox wpfbg-9">
                    <div class="wpf-statbox-body">
                        <div class="wpf-statbox-icon wpfcl-5"><i class="fas fa-thumbs-up"></i> </div>
                        <div class="wpf-statbox-value"><?php wpforo_print_number(WPF()->member->get_votes_and_likes_count( WPF()->current_object['userid'] ), true);  ?></div>
                        <div class="wpf-statbox-title"><?php wpforo_phrase('Liked') ?></div>
                    </div>
                </div>
                <div class="wpf-statbox wpfbg-9">
                    <div class="wpf-statbox-body">
                        <div class="wpf-statbox-icon wpfcl-5"><i class="fas fa-thumbs-up fa-flip-horizontal"></i></div>
                        <div class="wpf-statbox-value"><?php wpforo_print_number(WPF()->member->get_user_votes_and_likes_count( WPF()->current_object['userid'] ), true);  ?></div>
                        <div class="wpf-statbox-title"><?php wpforo_phrase('Received Likes') ?></div>
                    </div>
                </div>
                <div class="wpf-statbox wpfbg-9">
                    <div class="wpf-statbox-body">
                        <div class="wpf-statbox-icon wpfcl-5"><i class="fas fa-star"></i></div>
                        <div class="wpf-statbox-value"><?php echo $stat_rating ?>/10</div>
                        <div class="wpf-statbox-title"><?php wpforo_phrase('Rating') ?></div>
                    </div>
                </div>
                <div class="wpf-statbox wpfbg-9">
                    <div class="wpf-statbox-body">
                        <div class="wpf-statbox-icon wpfcl-5"><i class="fas fa-pen-square"></i></div>
                        <div class="wpf-statbox-value"><?php echo WPF()->member->blog_posts(WPF()->current_object['userid']) ?></div>
                        <div class="wpf-statbox-title"><?php wpforo_phrase('Blog Posts') ?></div>
                    </div>
                </div>
                <div class="wpf-statbox wpfbg-9">
                    <div class="wpf-statbox-body">
                        <div class="wpf-statbox-icon wpfcl-5"><i class="fas fa-comments"></i></div>
                        <div class="wpf-statbox-value"><?php echo WPF()->member->blog_comments(WPF()->current_object['userid'], wpfval(WPF()->current_object['user'], 'user_email')) ?></div>
                        <div class="wpf-statbox-title"><?php wpforo_phrase('Blog Comments') ?></div>
                    </div>
                </div>
            	<div class="wpf-clear"></div>
             </div>
        </div>
    <?php endif; ?>

</div>