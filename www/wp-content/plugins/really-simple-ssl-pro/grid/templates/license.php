<?php defined('ABSPATH') or die("you do not have access to this page!");
$license = RSSSL_PRO()->rsssl_licensing->license_key();
$status = get_site_transient('rsssl_pro_license_status');

wp_nonce_field('rsssl_pro_nonce', 'rsssl_pro_nonce');
if (!is_network_admin()) {
	settings_fields('rsssl_pro_license');
} else { ?>
    <input type="hidden" name="option_page" value="rsssl_network_options">
<?php } ?>
<table class="form-table rsssl-license-table">
    <tbody>
    <tr>
        <td class="rsssl-license-field">
            <input id="rsssl_pro_license_key" class="rsssl_license_key" placeholder="<?php _e("Enter your license key", "really-simple-ssl-pro")?>" name="rsssl_pro_license_key" type="password" class="regular-text rsssl-text-input" value="<?php esc_attr_e($license); ?>"/>
        </td>
    </tr>
	<?php echo RSSSL_PRO()->rsssl_licensing->get_license_label() ?>
    </tbody>
</table>
