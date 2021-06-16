<?php
	// Exit if accessed directly
	if( !defined( 'ABSPATH' ) ) exit;
	if( !WPF()->perm->usergroup_can('ms') ) exit;
?>


<form action="" method="POST" class="validate">
	<?php wp_nonce_field( 'wpforo-settings-styles' ); ?>
    <input type="hidden" name="wpfaction" value="wpforo_theme_style_options_save">
	<table class="wpforo_settings_table">
		<tbody>
			<tr>
				<th style="width:30%;"><label><?php _e('Font Sizes', 'wpforo'); ?>:</label></th>
				<td>
                <label style="display:inline-block; text-align:center; font-size:14px;">
                	<span><?php _e('Forums','wpforo'); ?>:</span> 
                	<select name="wpforo_style_options[font_size_forum]" style="min-width:80px;">
                    	<?php for( $a=11; $a < 28; $a++ ): ?><option value="<?php echo intval($a) ?>" <?php wpfo_check( WPF()->tpl->style['font_size_forum'], $a, 'selected') ?>><?php echo intval($a); ?>px</option><?php endfor; ?>
                    </select>
                </label> &nbsp; 
                <label style="display:inline-block; text-align:center; font-size:14px;">
                	<span><?php _e('Topics','wpforo'); ?>:</span> 
                	<select name="wpforo_style_options[font_size_topic]" style="min-width:80px;">
                    	<?php for( $a=11; $a < 28; $a++ ): ?><option value="<?php echo intval($a) ?>" <?php wpfo_check( WPF()->tpl->style['font_size_topic'], $a, 'selected') ?>><?php echo intval($a); ?>px</option><?php endfor; ?>
                    </select>
                </label> &nbsp; 
                <label style="display:inline-block; text-align:center; font-size:14px;">
                	<span><?php _e('Post Content','wpforo'); ?>:</span> 
                	<select name="wpforo_style_options[font_size_post_content]" style="min-width:80px;">
                    	<?php for( $a=11; $a < 28; $a++ ): ?><option value="<?php echo intval($a) ?>" <?php wpfo_check( WPF()->tpl->style['font_size_post_content'], $a, 'selected') ?>><?php echo intval($a); ?>px</option><?php endfor; ?>
                    </select>
                </label>
				</td>
			</tr>
            <tr>
				<th style="width:30%;"><label><?php _e('Custom CSS Code', 'wpforo'); ?>:</label></th>
				<td>
               		<textarea name="wpforo_style_options[custom_css]" style="width:90%; height:130px; font-family:Consolas, 'Andale Mono', 'Lucida Console'; color:#666666; background:#fdfdfd;"><?php echo esc_textarea(stripslashes(WPF()->tpl->style['custom_css'])); ?></textarea>
				</td>
			</tr>
		</tbody>
	</table>
   <h3 style="margin:0 20px 0 20px; padding:10px 0; border-bottom:3px solid #F5F5F5;"><?php _e('Forum Color Styles', 'wpforo'); ?> &nbsp;<a href="https://wpforo.com/docs/root/wpforo-settings/style-settings/" title="<?php _e('Read the documentation', 'wpforo') ?>" target="_blank" style="font-size: 14px;"><i class="far fa-question-circle"></i></a> &nbsp;|&nbsp; <a href="https://wpforo.com/docs/root/forum-themes/theme-styles/" target="_blank" style="font-size:13px; text-decoration:none;"><?php _e('Colors Documentation', 'wpforo'); ?> &raquo;</a></h3>
    <table style="width:95%; border:none; padding:5px; margin-left:10px; margin-top:15px;">
        <tbody>
            <tr class="form-field form-required">
                <td class="wpf-dw-td-value-p">
                	<table class="wpforo-style-color-wrapper" style="border-right:2px solid #eee; margin-right:10px; padding-left:5px; width:40px;">
                        <tr>
                            <td style="border-bottom:2px solid #EEEEEE; margin-bottom:5px;">
                                     #<div style="clear:both;"></div>
                            </td>
                        </tr>
                        <?php foreach( WPF()->tpl->options['styles']['default'] as $color_key => $color_value ): ?>
                            <tr>
                               <td>
                                    <div style="line-height:38px; min-height:32px; font-size:14px; font-weight:bold;"><?php wpfo($color_key); ?></div>
                               </td>
                            </tr>
                        <?php endforeach; ?>
                        </table>
                	<?php foreach( WPF()->tpl->options['styles'] as $style => $colors ): ?>
                        <table class="wpforo-style-color-wrapper" style="border-right:2px solid #eee; margin-right:10px; padding-left:5px; <?php  echo ( $style == WPF()->tpl->options['style'] ) ? 'background: #E8FFE5; width: 200px; text-align: center;' : 'background: transparent'; ?>">
                                <tr>
                                    <td>
                                    <div style="float:left; text-align:center; width:27px;"><input style="margin:0;" <?php if( $style == WPF()->tpl->options['style'] ) echo ' checked="checked"'; ?> type="radio" name="wpforo_theme_options[style]" value="<?php wpfo($style) ?>" id="wpforo_stle_<?php wpfo($style) ?>" /></div>
                                    <div style="text-transform:uppercase; text-align:left;float:left; font-weight:bold; font-size:14px;"><label for="wpforo_stle_<?php wpfo($style) ?>">&nbsp;<?php _e( wpfo($style, false), 'wpforo'); ?></label></div>
                                    <div style="clear:both;"></div>
                                	</td>
                                </tr>
                                <?php foreach( $colors as $color_key => $color_value ): ?>
                                <tr>
                                    <td style="border-bottom:1px solid #ddd;">
                                        <div class="wpforo-style-field">
                                        <?php if( $style == WPF()->tpl->options['style'] ): ?>
                                        	<input class="wpforo-color-field" name="wpforo_theme_options[styles][<?php wpfo($style) ?>][<?php wpfo($color_key); ?>]" type="text" value="<?php wpfo(strtoupper($color_value)); ?>" title="<?php wpfo(strtoupper($color_value)); ?>" />
										<?php else: ?>
                                        	<input style="width:90%;height: 22.5px; box-sizing:border-box; padding:0px;" name="wpforo_theme_options[styles][<?php wpfo($style) ?>][<?php wpfo($color_key); ?>]" type="color" value="<?php wpfo(strtoupper($color_value)); ?>" title="<?php wpfo(strtoupper($color_value)); ?>" />
                                        <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                        </table>    
                	<?php endforeach; ?>
                    <div style="clear:both;"></div>
                </td>
            </tr>
        </tbody>
    </table>
	<div style="clear:both;"></div>
    <hr>

    <?php if( $dynamic_css = WPF()->tpl->generate_dynamic_css() ) : ?>
        <script type="text/javascript">
            function wpforo_input_select_all_and_copy(t){
                t.select();
                if(document.execCommand('copy')){
                    jQuery('#dynamic-css-code-wrap').addClass('wpf_copy_animate');
                    setTimeout(function () {
                        jQuery('#dynamic-css-code-wrap').removeClass('wpf_copy_animate');
                    }, 1000);
                }
            }
        </script>
        <div id="dynamic-css-notice-wrap">
            <div id="dynamic-css-help-steps-wrap">
                <p style="font-size: 15px;">
                    <b><i class="fas fa-info-circle" aria-hidden="true"></i> <?php _e('Problems with colors?', 'wpforo');?></b><br>
		            <?php printf(__('After changing and saving colors, go to the forum front-end and press %s twice. If you don\'t see any change, please follow to the instruction below.', 'wpforo'), '<b>CTRL+F5</b>'); ?>
                </p>
                <p style="font-size: 15px;"><?php _e('In most cases, this problem comes from your server file writing permissions. Files are not permitted to change, thus the forum color provider colors.css file is not updated with your changes. If you cannot fix this issue in hosting server, then the following easy steps can solve your problem:', 'wpforo') ?></p>
                <ol>
                <li style="font-size: 14px; margin-bottom: 1px; line-height: 1.5"><?php printf(__('Create colors.css file or simply download %s file with the CSS code provided in the textarea below,', 'wpforo'), '<code>colors.css</code>') ?></li>
                <li style="font-size: 14px; margin-bottom: 1px; line-height: 1.5"><?php printf(__('Upload and replace %s file in %s directory,', 'wpforo'), '<code>colors.css</code>','<code>' . wpforo_fix_directory(WPFORO_TEMPLATE_DIR) . DIRECTORY_SEPARATOR . '</code>') ?></li>
                <li style="font-size: 14px; margin-bottom: 1px; line-height: 1.5"><?php printf(__('Delete website cache, reset CSS file optimizer and minifier plugins caches, purge CDN data (if you have), then go to the forum front-end and press %s twice.', 'wpforo'), '<b>CTRL+F5</b>') ?></li>
                </ol>
            </div>
            <div id="dynamic-css-code-wrap">
                <label for="colors_css" class="dynamic-css-fname"><i class="fas fa-file-code"></i>&nbsp;colors.css</label>
                <textarea id="colors_css" readonly class="dynamic-css-code" rows="10" onclick="wpforo_input_select_all_and_copy(this)"><?php echo $dynamic_css ?></textarea>
                <div class="wpf_copied_txt"><span><?php _e('Copied', 'wpforo') ?></span></div>
            </div>
            <a style="font-size: 14px; text-decoration: none;" href="<?php echo wp_nonce_url( admin_url('admin.php?page=wpforo-settings&tab=styles&wpfaction=wpforo_colors_css_download'), 'dynamic_css_download' ) ?>"><i class="fas fa-file-download"></i> <?php _e('Download', 'wpforo') ?> colors.css</a>
            <br style="clear: both">
        </div>
    <?php endif; ?>

    <div class="wpforo_settings_foot">
        <input type="submit" class="button button-primary" value="<?php _e('Update Options', 'wpforo'); ?>" />
        <input type="submit" class="button" value="<?php _e('Reset Options', 'wpforo'); ?>" name="reset" onclick="return confirm('<?php wpforo_phrase('Do you really want to reset options?') ?>')" />
    </div>
</form>
<script language="javascript">jQuery(document).ready(function($){$(function () { $('.wpforo-color-field').wpColorPicker(); });});</script>