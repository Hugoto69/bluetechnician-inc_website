<?php defined('ABSPATH') or die("you do not have access to this page!"); ?>
<?php
if (!current_user_can('manage_options')) return;

settings_fields('rlrsssl_security_headers');
do_settings_sections('rlrsssl_security_headers_page');

wp_nonce_field( 'submit_security_headers', 'security_headers_update' );