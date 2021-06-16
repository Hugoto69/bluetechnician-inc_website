<?php defined('ABSPATH') or die("you do not have access to this page!");

    $status = RSSSL_PRO()->rsssl_licensing->get_license_status();
    $current_user = get_current_user_id();
    $allowed_user = intval( get_option('rsssl_licensing_allowed_user_id') );
    $lock = get_option('rsssl_pro_disable_license_for_other_users') ==1;
    $disabled = $lock && ($current_user !== $allowed_user);
    ?>
    <input type="submit" class="button button-secondary" name="rsssl_pro_license_save" value="<?php _e('Save', 'really-simple-ssl-pro'); ?>"/>
<?php
    if ($status && $status == 'valid') { ?>
        <input type="submit" class="button button-rsssl-tertiary" name="rsssl_pro_license_deactivate" value="<?php _e('Deactivate license', 'really-simple-ssl-pro'); ?>"/>
	<?php } else { ?>
        <input type="submit" class="button-secondary" name="rsssl_pro_license_activate" value="<?php _e('Activate license', 'really-simple-ssl-pro'); ?>"/>
	<?php } ?>
    <div class="rsssl-disable-for-other-users">
        <span class="rsssl-tooltip-left tooltip-left " data-rsssl-tooltip="<?php _e("Disable access to the license page for all other accounts except your own.","really-simple-ssl-pro")?>">
           <input type="checkbox" <?php echo $disabled ? 'disabled' : ''?> name="rsssl_pro_disable_for_other_users" id="rsssl_pro_disable_for_other_users"  <?php echo $lock ? 'checked="checked"' : "";?>>
           <?php _e("Disable for all users, except yourself", "really-simple-ssl-pro");?>
        </span>
    </div>
    <?php

    if ( $disabled ) {
        $user = get_user_by('id', $allowed_user);
        if ($user) {
            $email = $user->display_name;
        } else {
            $email = __("User not found", "really-simple-ssl-pro");
        }
        $string = sprintf(__("The license key is only visible to %s. Add &rsssl_unlock_license={your license key} behind the URL to unlock the license block.","really-simple-ssl-pro"), $email);
        ?>
        <div class="rsssl-networksettings-overlay"><div class="rsssl-disabled-settings-overlay"><span class="rsssl-progress-status rsssl-open"><?php echo $string ?></span>
    <?php }
