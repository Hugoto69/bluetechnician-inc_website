<?php
	// Exit if accessed directly
	if( !defined( 'ABSPATH' ) ) exit;
	if( !WPF()->perm->usergroup_can('ms') ) exit;
	$nocan = array( 	
		'no_access' => array('et', 'dt', 'dot', 'er', 'dr', 'dor', 'l', 's', 'at', 'cot', 'p', 'op', 'vp', 'au', 'sv', 'mt', 'ccp' ,'r', 'ct', 'cr', 'ocr', 'eot', 'eor', 'oat', 'osv', 'cvp', 'v', 'a'),
		'read_only' => array('et', 'dt', 'dot', 'er', 'dr', 'dor', 'l', 's', 'at', 'cot', 'p', 'op', 'vp', 'au', 'sv', 'mt', 'ccp' ,'r'),
		'standard' => array('et', 'dt', 'er', 'dr', 'at', 'cot', 'p', 'vp', 'au', 'sv', 'mt')
	);

    $wpfaction = wpfval($_GET, 'wpfaction');
?>

<?php if( !$wpfaction ): ?>
    <h2 style="margin-top:0; margin-bottom:20px;">
        <a href="<?php echo admin_url('admin.php?page=wpforo-settings&tab=accesses&wpfaction=wpforo_access_save_form') ?>" class="add-new-h2">
            <?php _e('Add New Forum Access', 'wpforo'); ?>
        </a>
    </h2>
    <table id="wpf-access-table" class="wp-list-table widefat fixed posts" style="max-width: 900px;">
		<thead>
			<tr>
                <th scope="col" id="title" class="manage-column column-title sorted desc" style="padding:10px 10px 10px 15px; font-size:14px; font-weight:bold;">
                    <label>
                        <?php _e('Access names', 'wpforo'); ?>
                        <a href="https://wpforo.com/docs/root/wpforo-settings/forum-accesses/" title="<?php _e('Read the documentation', 'wpforo') ?>" target="_blank" style="display: inline;">
                            <i class="far fa-question-circle"></i>
                        </a>
                    </label>
                    <p class="wpf-info"><?php _e('Forum Accesses are designed to do a Forum specific user permission control. These are set of permissions which are attached to certain Usergeoup in each forum. Thus users can have different permissions in different forums based on their Usergroup.', 'wpforo'); ?></p>
                </th>
            </tr>
		</thead>
		<tbody id="wpf-access-list">
			<?php foreach(WPF()->perm->get_accesses() as $access) : ?>
				<tr id="post-2" class="post-1 type-post status-publish format-standard hentry category-uncategorized alternate iedit author-self">
					<td class="post-title page-title column-title" style="border-bottom:1px dotted #CCCCCC; padding-left:20px;">
						<strong class="row-title">
							<a href="<?php echo admin_url('admin.php?page=wpforo-settings&tab=accesses&wpfaction=wpforo_access_save_form&accessid=' . intval($access['accessid'])) ?>" title="<?php echo esc_attr($access['title']) ?>">
								<?php _e( $access['title'], 'wpforo') ?>
							</a>
						</strong>
                        <p class="wpf-info">
							<?php if($access['access'] === 'read_only') { _e('This access is usually used for ', 'wpforo'); echo '<span style="color:#F45B00"><b>'; _e('Guests', 'wpforo'); echo '</b></span> ';  _e('usergroup', 'wpforo'); } ?>
							<?php if($access['access'] === 'standard') { _e('This access is usually used for ', 'wpforo'); echo '<span style="color:#F45B00"><b>'; _e('Registered', 'wpforo'); echo '</b></span> '; _e('usergroup', 'wpforo'); } ?>
							<?php if($access['access'] === 'full') { _e('This access is usually used for ', 'wpforo'); echo '<span style="color:#F45B00"><b>'; _e('Admin', 'wpforo'); echo '</b></span> '; _e('usergroup', 'wpforo'); } ?>
                        </p>
                        <div class="row-actions">
							<span class="edit">
                                <a href="<?php echo admin_url('admin.php?page=wpforo-settings&tab=accesses&wpfaction=wpforo_access_save_form&accessid=' . intval($access['accessid'])) ?>">
                                    <?php _e('edit', 'wpforo'); ?>
                                </a>
                            </span>
							<?php if( $access['accessid'] > 5 ): ?>
                            	<span class="trash"> |
                                    <a class="submitdelete" href="<?php echo wp_nonce_url( admin_url('admin.php?page=wpforo-settings&tab=accesses&wpfaction=wpforo_access_delete&accessid=' . intval($access['accessid'])), 'wpforo-access-delete-' . intval($access['accessid']) ) ?>"
                                       onclick="return confirm('<?php _e('Are you sure you want to remove this access set? Usergroups which attached to this access will lost all forum permissions.') ?>')">
                                        <?php _e('delete', 'wpforo') ?>
                                    </a>
                                </span>
							<?php endif; ?>
                        </div>
					</td>
				</tr>
			<?php endforeach ?>			
		</tbody>
	</table>
<?php elseif( $wpfaction === 'wpforo_access_save_form' ) :
	$access = WPF()->perm->fix_access( WPF()->perm->get_access( wpfval($_GET, 'accessid') ) );
	$disabled_cans = (array) wpfval($nocan, $access['access']); ?>
	<div class="form-wrap">
    	<div class="form-wrap">
            <form id="add_access" method="POST">
	            <?php
	                if($access['accessid']){
		                wp_nonce_field('wpforo-access-edit'); ?>
		                <input type="hidden" name="wpfaction" value="wpforo_access_edit">
		                <?php
	                }else{
		                wp_nonce_field('wpforo-access-add'); ?>
		                <input type="hidden" name="wpfaction" value="wpforo_access_add">
		                <?php
	                }
	            ?>
                <input type="hidden" name="access[accessid]" value="<?php echo esc_attr($access['accessid']) ?>">
                <input type="hidden" name="access[access]" value="<?php echo esc_attr($access['access']) ?>">
                <label for="access-name" class="wpf-label-big"><?php _e('Access name', 'wpforo'); ?></label>
                <input id="access-name" name="access[title]" type="text" value="<?php echo esc_attr($access['title']) ?>" size="40" required style="background:#FDFDFD; width:30%; min-width:320px;">
                <p>&nbsp;</p>
                <?php $n = 0; foreach( WPF()->forum->cans as $can => $name  ) :
                    $disabled = in_array($can, $disabled_cans);
                    if( !($n%4) ) : ?>
	                    </table>
	                    <table class="wpf-table-box-left" style="margin-right:15px; margin-bottom:15px;  min-width:320px;">
                    <?php endif; ?>
                    <tr>
                        <th class="wpf-dw-td-nowrap">
                            <label class="wpf-td-label" for="wpf-can-<?php echo esc_attr($can) ?>" <?php if($disabled) echo ' style="color: #aaa;" ' ?>>
                                <?php echo esc_html( __( $name, 'wpforo' ) ) ?>
                            </label>
                        </th>
                        <td class="wpf-dw-td-value" style="text-align:center;">
                            <input id="wpf-can-<?php echo esc_attr($can) ?>" type="checkbox" value="1" name="access[cans][<?php echo esc_attr($can) ?>]" <?php echo $access['cans'][$can] ? ' checked ' : '' ?> <?php if($disabled) echo ' disabled ' ?>>
                        </td>
                    </tr>
                <?php $n++; endforeach ?>
                </table>
                <div class="clear"></div>
                <input type="submit" class="button button-primary forum_submit" value="<?php _e('save', 'wpforo') ?>">
            </form>
        </div>
	</div>
<?php endif ?>