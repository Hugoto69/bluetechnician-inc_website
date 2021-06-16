<?php defined('ABSPATH') or die("you do not have access to this page!"); ?>
<div id="rsssl-scan-container">
	<?php wp_nonce_field( 'rsssl_nonce', 'rsssl_nonce' );?>
	<div id="rsssl-scan-list">
		<div class="rsssl progress">
			<div class="rsssl bar progress-bar <?php echo (get_option('rsssl_progress')>=100) ? 'progress-bar-success' : ''?>" role="progressbar" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100" style="width:<?php echo intval(get_option('rsssl_progress'))?>%"></div>
		</div>
		<div id="rsssl-scan-output">
			<?php echo RSSSL_PRO()->rsssl_scan->generate_output(); ?>
		</div>
	</div>
	<?php
	do_action("rsssl_scan_modals");
	?>
</div><!-- end rsssl wrapper -->
