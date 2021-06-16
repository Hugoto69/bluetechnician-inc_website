<div class="rsssl-csp-table-container">
    <table id="rsssl-csp-table" class="really-simple-ssl-table">
        <thead>
            <tr class="rsssl-csp-tr">
                <th class="rsssl-csp-th"><?php echo __("Found", "really-simple-ssl-pro")?></th>
                <!--Document-uri-->
                <th class="rsssl-csp-th"><?php echo __("On page", "really-simple-ssl-pro")?></th>
                <!--Violated Directive-->
                <th class="rsssl-csp-th"><?php echo __("Directive", "really-simple-ssl-pro")?></th>
                <!--Blocked-uri-->
                <th class="rsssl-csp-th"><?php echo __("Domain/protocol", "really-simple-ssl-pro")?></th>
                <th class="rsssl-csp-th"><?php echo __("Allow/revoke", "really-simple-ssl-pro")?></th>
            </tr>
        </thead>
        <tbody>
        {content}
        </tbody>
    </table>
</div>
<?php
do_action("rsssl_csp_modals");
?>
