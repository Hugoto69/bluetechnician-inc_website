<?php
defined('ABSPATH') or die("you do not have access to this page!");


//closing form is in footer
$placeholder = __("When you send this form we will attach the following information: license key, scan results, your domain, .htaccess file, debug log and a list of active plugins", "really-simple-ssl-pro");
?>
<form action="" method="POST">
<div class="support">
	<?php wp_nonce_field('rsssl_support', 'rsssl_nonce') ?>
	<textarea name="rsssl_support_request" required placeholder="<?php echo $placeholder?>"></textarea>
</div>
