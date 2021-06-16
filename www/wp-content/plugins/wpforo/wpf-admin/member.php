<?php
	// Exit if accessed directly
	if( !defined( 'ABSPATH' ) ) exit;
	if( !WPF()->perm->usergroup_can('vm') ) exit;
?>

<div id="wpf-admin-wrap" class="wrap">
	<?php wpforo_screen_option() ?>
	<div id="icon-users" class="icon32"><br></div>
	<h2 style="padding:30px 0px 0px 0px;line-height: 20px; margin-bottom:15px;"><?php _e('Members', 'wpforo'); ?></h2>
	<?php WPF()->notice->show() ?>

	<form method="get">
		<input type="hidden" name="page" value="wpforo-members">
		<?php WPF()->member->list_table->groups_dropdown() ?>
		<?php WPF()->member->list_table->status_dropdown() ?>
		<input type="submit" value="<?php _e('Filter', 'wpforo') ?>" class="button button-large">

		<?php WPF()->member->list_table->search_box('Search Members', 'wpf-member-search') ?>
	</form>
	<br>
	<hr>
	<?php
	$all_count      = WPF()->member->get_count();
	$active_count   = WPF()->member->get_count( array('p.status' => 'active') );
	$inactive_count = WPF()->member->get_count( array('p.status' => 'inactive') );
	$banned_count   = WPF()->member->get_count( array('p.status' => 'banned') );
	$all_href       = admin_url( 'admin.php?page=wpforo-members' );
	$active_href    = admin_url( 'admin.php?page=wpforo-members&filter_by_status=active' );
	$inactive_href  = admin_url( 'admin.php?page=wpforo-members&filter_by_status=inactive' );
	$banned_href    = admin_url( 'admin.php?page=wpforo-members&filter_by_status=banned' );
	$all_attr       = '';
	$active_attr    = '';
	$inactive_attr  = '';
	$banned_attr    = '';
	$filter_by_status = WPF()->member->list_table->get_filter_by_status_var();
	if( $filter_by_status === 'active' ){
		$active_attr = ' class="current"';
	}elseif( $filter_by_status === 'inactive' ){
		$inactive_attr = ' class="current"';
	}elseif( $filter_by_status === 'banned' ){
		$banned_attr = ' class="current"';
	}else{
		$all_attr = ' class="current"';
	}

	?>
	<ul class="subsubsub">
		<li>
			<a href="<?php echo $all_href ?>" <?php echo $all_attr ?>>
				<?php _e('All', 'wpforo'); ?>
				<span class="count">(<?php echo $all_count; ?>)</span>
			</a> |
		</li>
		<li>
			<a href="<?php echo $active_href ?>" <?php echo $active_attr ?>>
				<?php _e('Active', 'wpforo'); ?>
				<span class="count">(<?php echo $active_count; ?>)</span>
			</a> |
		</li>
		<li>
			<a href="<?php echo $inactive_href ?>" <?php echo $inactive_attr ?>>
				<?php _e('Inactive', 'wpforo'); ?>
				<span class="count">(<?php echo $inactive_count; ?>)</span>
			</a> |
		</li>
		<li>
			<a href="<?php echo $banned_href ?>" <?php echo $banned_attr ?>>
				<?php _e('Banned', 'wpforo'); ?>
				<span class="count">(<?php echo $banned_count; ?>)</span>
			</a>
		</li>
	</ul>
	<!-- Forms are NOT created automatically, so you need to wrap the table in one to use features like bulk actions -->
	<form id="wpf-dashboard-members-page" method="GET">
		<!-- For plugins, we also need to ensure that the form posts back to our current page -->
		<input type="hidden" name="page" value="wpforo-members">
		<input type="hidden" name="wpfaction" value="wpforo_bulk_members">

		<!-- Now we can render the completed list table -->
		<?php WPF()->member->list_table->display() ?>
	</form>
</div>