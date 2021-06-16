<?php
	// Exit if accessed directly
	if( !defined( 'ABSPATH' ) ) exit;
	if( !WPF()->perm->usergroup_can('mp') ) exit;

	$wpfaction = wpfval($_GET, 'wpfaction');
?>

<div id="wpf-admin-wrap" class="wrap" style="margin-top: 0">
	<?php wpforo_screen_option() ?>
	<div id="icon-users" class="icon32"><br></div>
	<h2 style="padding:30px 0 0 0; line-height: 20px; margin-bottom:15px;">
		<?php _e('Front-end Phrases', 'wpforo'); ?> &nbsp;
		<a class="add-new-h2" href="<?php echo admin_url( 'admin.php?page=wpforo-phrases&wpfaction=wpforo_phrase_add_form' ) ?>">
			<?php _e('Add New', 'wpforo') ?>
		</a>
	</h2>
	<?php WPF()->notice->show() ?>
	<?php
		if( $wpfaction === 'wpforo_phrase_add_form' ){ ?>
			<form method="POST" id="phrases" class="validate">
				<?php wp_nonce_field( 'wpforo-phrase-add' ); ?>
				<input type="hidden" name="wpfaction" value="wpforo_phrase_add">
				<table class="form-table">
                    <tr>
                        <td><?php _e('Language', 'wpforo') ?></td>
                        <td>
                            <select name="phrase[langid]">
		                        <?php WPF()->phrase->show_lang_list(); ?>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <td><?php _e('Package', 'wpforo') ?></td>
                        <td>
                            <select name="phrase[package]">
	                            <?php
	                            if( $packages = WPF()->phrase->get_distinct_packages() ){
		                            foreach( $packages as $package ){
			                            printf(
				                            '<option value="%1$s" %2$s>%3$s</option>',
				                            esc_attr($package),
				                            ($package === 'wpforo') ? 'selected' : '',
				                            esc_html($package)
			                            );
		                            }
	                            }
	                            ?>
                            </select>
                        </td>
                    </tr>
					<tr class="form-field form-required">
						<td>
							<label for="phrase_key"> <?php _e('Original', 'wpforo') ?></label>
						</td>
						<td>
                            <textarea name="phrase[key]" id="phrase_key" required style="min-height: 30px; height: 30px; width: 100%;"></textarea>
                        </td>
					</tr>
					<tr>
						<td>
							<label for="phrase_value"> <?php _e('Translation', 'wpforo') ?></label>
						</td>
                        <td>
                            <textarea name="phrase[value]" id="phrase_value" required style="min-height: 30px; height: 30px; width: 100%;"></textarea>
                        </td>
					</tr>
					<tr>
						<td colspan="2" style="text-align: right">
							<input type="submit" id="createusersub" class="button button-primary" style="padding: 0 30px;" value="<?php _e('Save', 'wpforo') ?>">
						</td>
					</tr>
				</table>
			</form>
			<?php
		}elseif ( $wpfaction === 'wpforo_phrase_edit_form' ){
		    if( wpfval($_GET, 'phraseid') ){
			    check_admin_referer('wpforo-phrase-edit-' . wpfval($_GET, 'phraseid'));
		    }else{
			    check_admin_referer('bulk-phrases');
		    }
			$phraseids = array_merge((array) wpfval($_GET, 'phraseid'), (array) wpfval($_GET, 'phraseids'));
			?>
			<form method="POST" id="phrases" class="validate">
				<?php wp_nonce_field( 'wpforo-phrases-edit' ); ?>
                <input type="hidden" name="wpfaction" value="wpforo_phrase_edit">
				<table class="form-table">
					<?php foreach($phraseids as $phraseid) : ?>
						<tr class="form-field form-required">
							<th scope="row">
								<?php $phrase =  WPF()->phrase->get_phrase($phraseid); ?>
								<label for="phrase-<?php echo $phrase['phraseid']; ?>">
									<?php echo esc_html($phrase['phrase_key']); ?>
                                </label>
							</th>
							<td>
								<textarea
                                    name="phrases[<?php echo intval($phraseid) ?>]"
                                    id="phrase-<?php echo $phrase['phraseid']; ?>"
                                    required
                                    style="width:80%; min-height:30px; height:30px;"
                                ><?php wpfo($phrase['phrase_value'], true, 'esc_textarea'); ?></textarea>
							</td>
						</tr>
					<?php endforeach; ?>
                    <tr>
                        <td colspan="2" style="text-align: right;">
                            <input type="submit" id="createusersub" class="button button-primary" style="padding: 0 30px" value="<?php _e('Update', 'wpforo') ?>">
                        </td>
                    </tr>
				</table>
			</form>
			<?php
		}else{
			?>
			<form method="get">
				<input type="hidden" name="page" value="wpforo-phrases">
				<?php WPF()->phrase->list_table->languages_dropdown() ?>
				<?php WPF()->phrase->list_table->packages_dropdown() ?>
				<input type="submit" value="<?php _e('Filter', 'wpforo') ?>" class="button button-large">

				<?php WPF()->phrase->list_table->search_box('Search Phrases', 'wpf-phrase-search') ?>
			</form>
			<br>
			<hr>
			<!-- Forms are NOT created automatically, so you need to wrap the table in one to use features like bulk actions -->
			<form id="wpf-dashboard-phrase-page" method="GET">
				<!-- For plugins, we also need to ensure that the form posts back to our current page -->
				<input type="hidden" name="page" value="wpforo-phrases">
				<input type="hidden" name="wpfaction" value="wpforo_phrase_edit_form">

				<!-- Now we can render the completed list table -->
				<?php WPF()->phrase->list_table->display() ?>
			</form>
			<?php
		}
	?>
</div>