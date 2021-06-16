<?php
	// Exit if accessed directly
	if( !defined( 'ABSPATH' ) ) exit;
?>
<div class="wpforo-profile-account">

        <?php if( WPF()->current_object['user_is_same_current_user'] || ( WPF()->perm->usergroup_can('em') && WPF()->perm->user_can_manage_user( WPF()->current_userid, WPF()->current_object['userid'] ) ) ) :
            $fields = wpforo_account_fields();
        ?>

		<form id="wpf-profile-account-form" action="" enctype="multipart/form-data" method="POST" autocomplete="off">
            <input id="for-stupid-mozilla" type="password" style="display: none">

          <?php wp_nonce_field( 'wpforo_verify_form', 'wpforo_form' ); ?>
          <input type="hidden" name="member[userid]" value="<?php echo esc_attr(WPF()->current_object['userid']) ?>">
          <input type="hidden" name="member[username]" value="<?php echo esc_attr(wpfval(WPF()->current_object['user'], 'user_login')) ?>">
          <div class="wpf-table">

              <?php wpforo_fields( $fields ); ?>
            
              <div class="wpf-tr">
                    <div class="wpf-td wpfw-1">
                        <div class="wpf-field wpf-field-type-submit">
                            <?php do_action('wpforo_profile_account_bottom'); ?>
                            <input type="submit" value="<?php wpforo_phrase('Save Changes') ?>">
                        </div>
                        <div class="wpf-field-cl"></div>
                    </div>
                    <div class="wpf-cl"></div>
              </div>
           </div>
        </form>
        
    <?php else: ?>
    	<?php if( isset(WPF()->current_object['user']['user_activation_key']) && WPF()->current_object['user']['user_activation_key'] ): ?>
    		<p class="wpf-p-error"><?php wpforo_phrase('Success! Please check your mail for confirmation.') ?></p>
		<?php else: ?>
        	<p class="wpf-p-error"><?php wpforo_phrase('Permission denied') ?></p>
        <?php endif; ?>
	<?php endif; ?>
</div>