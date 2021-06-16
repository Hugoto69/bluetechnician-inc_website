<?php defined('ABSPATH') or die("you do not have access to this page!");
ob_start();

settings_fields('rlrsssl_permissions_policy_group');
do_settings_sections('rlrsssl_permissions_policy_page');
wp_nonce_field( 'submit_security_headers', 'security_headers_update' );

$contents = ob_get_clean();

$help_tip_default = RSSSL()->rsssl_help->get_help_tip(__("No restrictions for this feature.", "really-simple-ssl-pro"), $return=true );
$help_tip_self = RSSSL()->rsssl_help->get_help_tip(__("self means this is feature is only allowed for content on your own domain. External scripts won't be able to use it.", "really-simple-ssl-pro"), $return=true );
$help_tip_none = RSSSL()->rsssl_help->get_help_tip(__("This feature is not allowed. Enable this to disable the feature entirely on your site.", "really-simple-ssl-pro"), $return=true );

$table_html =
	"<table id='rsssl-permission-policy-table' class='really-simple-ssl-table'>
            <thead>
            <tr>
                <th>" . __('Feature', 'really-simple-ssl-pro') ."</th>
                <th>".__("allowed","really-simple-ssl-pro") . $help_tip_default ."</th>
                <th>".__("self","really-simple-ssl-pro") . $help_tip_self . "</th>
                <th>".__("not allowed","really-simple-ssl-pro") . $help_tip_none . "</th>
            </tr>
            </thead>
            <tbody>";

$contents = str_replace('<table class="form-table" role="presentation">', $table_html, $contents);
// We need to remove the first <tr>. As it hasn't got the same amount of <td>'s as other rows, DataTables will to initialize fail!
$contents = str_replace('<tr><th scope="row"></th><td>', '', $contents);

echo $contents;
