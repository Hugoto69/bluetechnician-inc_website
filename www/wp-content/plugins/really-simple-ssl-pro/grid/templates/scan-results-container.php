<?php defined('ABSPATH') or die("you do not have access to this page!"); ?>

<table id="rsssl-scan-results" class="really-simple-ssl-table">
    <thead>
    <tr>
        <th class="rsssl-scan-status rsssl-scan-th"><?php _e("Status", "really-simple-ssl-pro"); ?></th>
        <th class="rsssl-scan-name rsssl-scan-th"><?php echo __("Description", "really-simple-ssl-pro") . RSSSL()->rsssl_help->get_help_tip(__("The file that's causing a potential mixed content issue.", "really-simple-ssl-pro"), $return=true );?></th>
        <th class="rsssl-scan-location rsssl-scan-th"><?php _e("Location", "really-simple-ssl-pro"); ?></th>
        <th class="rsssl-scan-action rsssl-scan-th"><?php _e("Action", "really-simple-ssl-pro"); ?></th>
    </tr>
    </thead>
    <tbody>
        {content}
    </tbody>
</table>
