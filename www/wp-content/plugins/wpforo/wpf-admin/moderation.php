<?php
	// Exit if accessed directly
	if( !defined( 'ABSPATH' ) ) exit;
	if( !WPF()->perm->usergroup_can('aum') ) exit;
?>

<div id="wpf-admin-wrap" class="wrap" style="margin-top: 0">
	<?php wpforo_screen_option() ?>
	<div id="icon-users" class="icon32"><br></div>
	<h2 style="padding: 30px 0 0 0; line-height: 20px; margin-bottom: 15px;"><?php _e('Topic and Post Moderation', 'wpforo'); ?></h2>
	<?php WPF()->notice->show() ?>

	<form method="get">
		<input type="hidden" name="page" value="wpforo-moderations">
		<?php WPF()->moderation->list_table->users_dropdown() ?>
		<?php WPF()->moderation->list_table->status_dropdown() ?>
		<input type="submit" value="<?php _e('Filter', 'wpforo') ?>" class="button button-large">

		<?php WPF()->moderation->list_table->search_box('Search Posts', 'wpf-post-search') ?>
	</form>
	<br>
	<hr>
	<?php
	$unapproveds = WPF()->post->get_count(array('status' => 1));
	$approveds   = WPF()->post->get_count(array('status' => 0));
	$uhref = admin_url( 'admin.php?page=wpforo-moderations&filter_by_status=1' );
	$ahref = admin_url( 'admin.php?page=wpforo-moderations&filter_by_status=0' );
	if( WPF()->moderation->list_table->get_filter_by_status_var() ){
		$uattr = ' class="current"';
		$aattr = '';
	}else{
		$uattr = '';
		$aattr = ' class="current"';
	}

	?>
	<ul class="subsubsub">
		<li>
			<a href="<?php echo $uhref ?>" <?php echo $uattr ?>>
				<?php _e('Unapproved', 'wpforo'); ?>
				<span class="count">(<?php echo $unapproveds; ?>)</span>
			</a> |
		</li>
		<li>
			<a href="<?php echo $ahref ?>" <?php echo $aattr ?>>
				<?php _e('Published', 'wpforo'); ?>
				<span class="count">(<?php echo $approveds; ?>)</span>
			</a>
		</li>
	</ul>
    <!-- Forms are NOT created automatically, so you need to wrap the table in one to use features like bulk actions -->
    <form id="wpf-dashboard-moderation-page" method="GET">
        <!-- For plugins, we also need to ensure that the form posts back to our current page -->
	    <input type="hidden" name="page" value="wpforo-moderations">
	    <input type="hidden" name="wpfaction" value="wpforo_bulk_moderation">

        <!-- Now we can render the completed list table -->
		<?php WPF()->moderation->list_table->display() ?>
    </form>
</div>