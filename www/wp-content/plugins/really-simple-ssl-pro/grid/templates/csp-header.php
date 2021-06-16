<?php defined('ABSPATH') or die("you do not have access to this page!"); ?>
<div class="rsssl-secondary-header-item">
        <?php
        //$_POST option, save setting
        $csp_toggle_option = RSSSL_PRO()->rsssl_premium_options->get_networkwide_option('rsssl_content_security_policy_toggle');
        $options = array(
            'everything' => __('Everything', 'really-simple-ssl-pro'),
            'blocked' => __('Blocked', 'really-simple-ssl-pro'),
            'allowed' => __('Allowed', 'really-simple-ssl-pro'),
        );
        ?>
        <select name="rsssl_content_security_policy_toggle" id="rsssl_content_security_policy_toggle">
            <?php foreach($options as $key => $name) {?>
                <option value=<?php echo $key?> <?php if ($csp_toggle_option == $key) echo "selected" ?>><?php echo $name ?>
            <?php }?>
        </select>
</div>