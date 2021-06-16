<?php
	// Exit if accessed directly
	if( !defined( 'ABSPATH' ) ) exit;
	if( !WPF()->forum->manage() ) exit;

	$action = wpfval($_GET, 'action');
?>

<!-- Screen Options -->
<?php if( in_array($action, array('add','edit')) ) : ?>
	
	<div id="screen-meta" class="metabox-prefs" style="display: none; ">
		<div id="screen-options-wrap" class="hidden" tabindex="-1" aria-label="Screen Options Tab" style="display: none; ">
			<form id="adv-settings" action="" method="POST">
				<h5><?php _e('Show on screen', 'wpforo'); ?></h5>
				<div class="metabox-prefs">
					<label for="forum_cat-hide"><input class="hide-postbox-tog" name="forum_cat-hide" type="checkbox" id="forum_cat-hide" value="forum_cat" checked="checked"><?php _e('Forum Options', 'wpforo'); ?></label>
					<label for="forum_permissions-hide"><input class="hide-postbox-tog" name="forum_permissions-hide" type="checkbox" id="forum_permissions-hide" value="forum_permissions" checked="checked"><?php _e('Permissions', 'wpforo'); ?></label>
					<label for="forum_slug-hide"><input class="hide-postbox-tog" name="forum_slug-hide" type="checkbox" id="forum_slug-hide" value="forum_slug" checked="checked"><?php _e('Slug', 'wpforo'); ?></label>
					<label for="forum_meta-hide"><input class="hide-postbox-tog" name="forum_meta-hide" type="checkbox" id="forum_meta-hide" value="forum_meta" checked="checked"><?php _e('Forum Meta', 'wpforo'); ?></label>
					<br class="clear">
				</div>
				<h5 class="screen-layout"><?php _e('Screen Layout', 'wpforo'); ?></h5>
				<div class="columns-prefs"><?php _e('Number of Columns', 'wpforo'); ?>:				
					<label class="columns-prefs-1"><input type="radio" name="screen_columns" value="1">1</label>
					<label class="columns-prefs-2"><input type="radio" name="screen_columns" value="2" checked="checked">2</label>
				</div>
			</form>
		</div>
	</div>
	
	<div id="screen-meta-links">
		<div id="screen-options-link-wrap" class="hide-if-no-js screen-meta-toggle" style="">
			<button aria-expanded="true" aria-controls="screen-options-wrap" class="button show-settings screen-meta-active" id="show-settings-link" type="button"><?php _e('Screen Options', 'wpforo'); ?></button>
		</div>
	</div>
    
<?php endif; ?>
<!-- end Screen Options -->

	<div id="icon-edit" class="icon32 icon32-posts-post"></div>
	<div id="wpf-admin-wrap" class="wrap">

	<h2 style="padding:30px 0 10px; line-height: 20px;">
		<?php _e('Categories and Forums', 'wpforo'); ?> &nbsp;
		<a href="<?php echo admin_url( 'admin.php?page=wpforo-forums&action=add' ) ?>" class="add-new-h2"><?php _e('Add New', 'wpforo'); ?></a>
	</h2>

	<?php WPF()->notice->show() ?>
	
	<!-- Forum Hierarchy -->
	<?php if( !$action ) : ?>
		<?php if( WPF()->forum->manage() ): ?>
			
            <div class="wpf-info-bar" style="line-height: 1em; clear:both; padding: 5px 50px; box-sizing: border-box; font-size:15px; display:block; box-shadow:none; margin: 20px 0 10px 0; font-style: italic; background: #FFFFFF; width:100%; position: relative;">
                <a href="https://wpforo.com/docs/root/categories-and-forums/forum-manager/" title="<?php _e('Read the documentation', 'wpforo') ?>" target="_blank" style="font-size: 16px; position: absolute; right: 15px; top: 15px;"><i class="far fa-question-circle"></i></a>
            	<ul style="list-style-type: disc; line-height:18px;">
            		<li style="list-style:none; margin-left:-17px; font-style:normal; font-weight:bold; padding-bottom: 5px;"><i class="fas fa-info-circle" aria-hidden="true"></i>&nbsp; <?php _e('Important Tips', 'wpforo'); ?></li>
                    <li><?php _e('Please drag and drop forum panels to set parent-child hierarchy.', 'wpforo'); ?></li>
            		<li><?php _e('If a category (blue panels) does not have forums (grey panels) it will not be displayed on front-end. Each category should contain at least one forum.', 'wpforo'); ?></li>
                    <li><?php _e('Forums can be displayed with different layouts (Extended, Simplified, Q&A, Threaded), just edit the top (blue panels) category and set the layout you want. Child forums\' layout depends on the top category (blue panels) layout. They cannot have a different layout.', 'wpforo'); ?></li>
            	</ul>
            </div>
            <br style="clear: both;" />
            
            <form id="forum-hierarchy" encType="multipart/form-data" method="POST">
            	<?php wp_nonce_field( 'wpforo-forums-hierarchy' ); ?>
	            <input type="hidden" name="wpfaction" value="wpforo_forum_hierarchy_save">
				<div id="post-body">
					<ul id="menu-to-edit" class="menu">
						
						<?php WPF()->forum->tree('drag_menu'); ?>
						
					</ul>
				</div><br />
				<div class="major-publishing-actions">
					<div class="publishing-action"><input id="save_menu_footer" class="button button-primary menu-save" name="save_menu" value="<?php _e('Save forums order and hierarchy', 'wpforo'); ?>" onclick="get_forums_hierarchy()" type="button"></div>
				</div>
			</form>	
            <script>
            var menus = false;
			navMenuL10n.saveAlert = null;
			window.onbeforeunload=function(){if(a.menusChanged){return navMenuL10n.saveAlert}}
            </script>
		<?php endif; ?><!--checking edit forum permission-->
	<?php endif; ?>
	<!-- end Forum Hierarchy -->
	<br style="clear: both;"/>
	<!-- Forum Add || Edit -->
	<?php if( in_array($action, array('add','edit')) ) : ?>
		<?php if( WPF()->forum->manage() ): ?>
			<?php
                $data = array();
                $disabled_forumid = 0;
				$selected_forumid = 0;
                if(!empty($_GET['id'])){
                    $disabled_forumid = array($_GET['id']);
                    if( $data = WPF()->forum->get_forum( array('forumid' => $_GET['id']) ) ) $selected_forumid = $data['parentid'];
                }
                if(!empty($_GET['parentid'])) $selected_forumid = $_GET['parentid'];
                $color = wpfval($data, 'color') ? $data['color'] : wpforo_random_colors();
            ?>
			<style type="text/css">
				#forum_layout .wpf-fl-box a:not(.wpf-lightbox){ cursor: zoom-in; display: inline-block; }
				.wpf-lightbox{ display: none; cursor: zoom-out; position: fixed; z-index: 999; width: 100%; height: 100%; text-align: center; top: 0; left: 0; background: rgba(0,0,0,0.8); }
				.wpf-lightbox img{ max-width: 90%; max-height: 90%; margin-top: 3%; }
				.wpf-lightbox:target{ outline: none; display: block; }
				.wpf-fl-wrap{display: flex; flex-wrap: wrap; flex-direction: row; justify-content: space-evenly; align-items: stretch;}
				.wpf-fl-box{margin: 6px; border: 1px solid #cccccc; padding: 5px; text-align: center;}
				.wpf-fl-box h4{ cursor: pointer; text-align: center; margin: 1px; font-size: 14px; background: #bbbbbb; color: #ffffff; padding: 2px 1px 4px 1px;}
				.wpf-fl-box.wpf-fl-active{border-color: #128403;}
				.wpf-fl-box.wpf-fl-active h4{background-color: #128403;}
			</style>
			<script type="text/javascript">
	            jQuery(document).ready(function($){
                    $('.wpforo-color-field').wpColorPicker();

	                $( "#forum_layout" ).on('click', ".wpf-fl-box:not(.wpf-fl-active) h4", function () {
	                    if( $('#use_us_cat').is(':checked') ){
	                        var wrap = $(this).parents('.wpf-fl-box');
	                        if( wrap ){
	                            var layout = wrap.data('layout');
	                            if( layout ){
	                                $('#cat_layout option[value="' + layout + '"]').attr('selected', true);
	                                $(".wpf-fl-box").removeClass("wpf-fl-active");
	                                wrap.addClass("wpf-fl-active");
	                            }
	                        }
	                    }
	                });
	                $('#cat_layout').on('change', function () {
	                    $(".wpf-fl-box").removeClass("wpf-fl-active");
	                    $("#wpfl-" + this.value).addClass("wpf-fl-active");
	                });
	                $('#use_us_cat').on('change', function () {
	                    if (!$(this).is(':checked')) {
							<?php if( wpfkey( $data, 'is_cat' ) && ! wpfval( $data, 'is_cat' ) ): ?>
	                        $(".wpf-fl-box").removeClass("wpf-fl-active");
	                        var wpf_layout = $('#wpf-current-layout').val();
	                        $("#wpfl-" + wpf_layout).addClass("wpf-fl-active");
							<?php else: ?>
	                        $(".wpf-fl-box").removeClass("wpf-fl-active");
	                        $("#forum_layout").hide();
							<?php endif; ?>
	                    } else {
	                        $(".wpf-fl-box").removeClass("wpf-fl-active");
	                        var wpf_layout = $('#cat_layout').find('option:selected').val();
	                        $("#wpfl-" + wpf_layout).addClass("wpf-fl-active");
	                        $("#forum_layout").show();
	                    }
	                });
	            });
			</script>
			<div id="poststuff">
				<form name="forum" method="POST">
					<?php if( $action === 'add' ) : ?>
                	    <?php wp_nonce_field( 'wpforo-forum-add' ); ?>
						<input type="hidden" name="wpfaction" value="wpforo_forum_add">
					<?php elseif ( $action === 'edit' ) : ?>
						<?php wp_nonce_field( 'wpforo-forum-edit' ); ?>
						<input type="hidden" name="wpfaction" value="wpforo_forum_edit">
						<input type="hidden" name="forum[forumid]" value="<?php echo (int) wpfval($_GET, 'id') ?>">
					<?php endif ?>

					<div id="post-body" class="metabox-holder columns-2">
						<div id="post-body-content">
							<input type="hidden" name="wpforo_submit" value="1"/>
							<input type="hidden" name="forum[order]" value="<?php echo esc_attr(isset($data['order']) ? $data['order'] : '') ?>"/>
							<div class="form-wrap">
								<div class="form-field form-required" style="margin-bottom:0px; padding-bottom:0px;">
									<div id="titlediv">
										<div id="titlewrap">
											<input id="title" name="forum[title]"  type="text" value="<?php echo esc_attr(isset($data['title']) ? $data['title'] : '') ?>" size="40" autocomplete="off" required="TRUE" placeholder="<?php _e('Enter forum title here', 'wpforo'); ?>" />
										</div>
									</div>
									<p>&nbsp;</p>
									<div class="form-field">
										<textarea placeholder="<?php _e('Enter description here . . .', 'wpforo'); ?>" name="forum[description]" rows="2" cols="40" style="padding:10px;"><?php echo esc_textarea(isset($data['description']) ? $data['description'] : '') ?></textarea>
										<p><?php _e('This is a forum description. This content will be displayed under forum title on the forum list.', 'wpforo'); ?></p>
									</div>
								</div>
							</div>	
						</div>
						
						<div id="postbox-container-1" class="postbox-container">
							<div id="side-sortables" class="meta-box-sortables ui-sortable">
								<div id="forum_cat" class="postbox">
									<h3 class="wpf-box-header"><span><?php _e('Forum Options', 'wpforo'); ?> &nbsp;<a href="https://wpforo.com/docs/root/categories-and-forums/forum-manager/add-new-forum/#forum-options" title="<?php _e('Read the documentation', 'wpforo') ?>" target="_blank" style="font-size: 14px;"><i class="far fa-question-circle"></i></a></span></h3>
									<div class="inside">
										<div class="form-field">
											<p><strong><?php _e('Parent Forum', 'wpforo'); ?></strong></p>
											<p>
                                            <select id="parent" name="forum[parentid]" class="postform" <?php echo (isset($data['is_cat']) && $data['is_cat'] == 1 ? 'disabled' : '') ?>>
												<option value="0"><?php _e('No parent', 'wpforo'); ?></option>
												<?php WPF()->forum->tree('select_box', true, $selected_forumid, false, $disabled_forumid); ?>
											</select>
											</p>
											<p class="form-field">
												<label for="use_us_cat"><?php _e('Use as Category', 'wpforo'); ?> &nbsp;<input id="use_us_cat" onclick="document.getElementById('parent').disabled = this.checked; document.getElementById('cat_layout').disabled = !this.checked;" type="checkbox" name="forum[is_cat]" value="1" <?php echo (isset($data['is_cat']) && $data['is_cat'] == 1 ? 'checked' : '') ?>/> </label>
											</p>
											<p><strong><?php _e('Category Layout', 'wpforo'); ?></strong></p>
											<p>
                                            <?php $layouts = WPF()->tpl->find_layouts( WPFORO_THEME ); ?>
                                            <?php if(!empty($layouts)): ?>
                                                <select id="cat_layout" name="forum[cat_layout]" class="postform" <?php $data['cat_layout'] = ( isset($data['cat_layout']) ? $data['cat_layout'] : 1 ); echo ( isset($data['is_cat']) && $data['is_cat'] == 1  ? '' : 'disabled="TRUE"' ); ?> >
                                                    <?php WPF()->tpl->show_layout_selectbox($data['cat_layout']); ?>
                                                </select>
                                            <?php else: ?>
                                            	<p><?php _e('No layout found.', 'wpforo'); ?></p>
                                            <?php endif; ?>
                                            </p>
										</div>
									</div>
								</div>
								
                                <div id="submitdiv" class="postbox">
									<h3 class="wpf-box-header"><span><?php _e('Publish', 'wpforo'); ?></span></h3>
									<div class="inside">
										<div id="major-publishing-actions" style="text-align:right;">
											<?php if( $action === 'edit' ) : ?>
												<a class="wpf-delete button" href="?page=wpforo-forums&id=<?php echo intval($data['forumid']) ?>&action=del" onclick="if (!confirm('<?php _e('Are you sure you want to delete this forum?', 'wpforo'); ?>')) { return false; }"><?php _e('Delete', 'wpforo'); ?></a> &nbsp; 
												<a class="preview button" href="<?php echo wpforo_home_url( (isset($data['slug']) ? $data['slug'] : '') ) ?>" target="wp-preview" id="post-preview"  style="display:inline-block;float:none;"><?php _e('View', 'wpforo'); ?></a> &nbsp;
                                                <input type="submit" name="forum[save_edit]" class="button button-primary forum_submit" style="display:inline-block;float:none;" value="<?php _e('Update', 'wpforo'); ?>">
                                            <?php else: ?>
                                                <input type="submit" name="forum[save_edit]" class="button button-primary forum_submit" style="display:inline-block;float:none;" value="<?php _e('Publish', 'wpforo'); ?>">
                                            <?php endif; ?>
                                            <div class="clear"></div>
										</div>
									</div>
								</div>
                                
                                
                                <div id="forum_permissions" class="postbox">
									<h3 class="wpf-box-header"><span><?php _e('Forum Permissions', 'wpforo'); ?> &nbsp;<a href="https://wpforo.com/docs/root/categories-and-forums/forum-manager/add-new-forum/#forum-permissions" title="<?php _e('Read the documentation', 'wpforo') ?>" target="_blank" style="font-size: 14px;"><i class="far fa-question-circle"></i></a></span></h3>
									<div class="inside">
										<table>
											<?php WPF()->forum->permissions(); ?>
										</table>
									</div>
								</div>

                                <div id="forum_color" class="postbox">
                                    <h3 class="wpf-box-header"><span><?php _e('Additional Options', 'wpforo'); ?> &nbsp;</span></h3>
                                    <div class="inside">
                                        <div class="wpf-forum-additional-options">
                                            <div style="float: left; padding: 2px 10px 0 0;"><?php _e('Forum Color', 'wpforo') ?></div>
                                            <div class="wpforo-style-field">
                                                <input class="wpforo-color-field" name="forum[color]" type="text" value="<?php wpfo(strtoupper($color)); ?>" title="<?php wpfo(strtoupper($color)); ?>" />
                                            </div>
                                            <div style="clear: both"></div>
                                        </div>
                                    </div>
                                </div>

								<?php do_action('wpforo_dashboard_forum_form_side', $data); ?>

                            </div>
                        </div>
						<div id="postbox-container-2" class="postbox-container">
							<div id="normal-sortables" class="meta-box-sortables ui-sortable">

                                <div id="forum_layout" class="postbox"  <?php if(!wpfkey($data, 'is_cat')) echo 'style="display: none"' ;?>>
                                    <h3 class="wpf-box-header">
                                        <span>
                                            <?php if( wpfkey($data, 'is_cat') ): ?>
                                                <?php if( wpfval($data, 'is_cat') ): ?>
                                                    <?php _e('Current Layout of this Category', 'wpforo'); ?>
                                                <?php else: ?>
                                                    <?php _e('Current Layout of this Forum (inherited from parent category)', 'wpforo'); ?>
                                                <?php endif; ?>
                                            <?php else: ?>
                                                <?php _e('Category Layout', 'wpforo'); ?>
                                            <?php endif; ?>
                                            &nbsp;<a href="https://wpforo.com/docs/root/categories-and-forums/forum-layouts/" title="<?php _e('Read the documentation', 'wpforo') ?>" target="_blank" style="font-size: 14px;"><i class="far fa-question-circle"></i></a>
                                        </span>
                                    </h3>
                                    <div class="inside" style="padding: 5px 7px;">
                                        <div class="wpf-fl-wrap">
                                            <input type="hidden" id="wpf-current-layout" name="wpf-current-layout" value="<?php if( wpfval($data, 'cat_layout') ) echo intval($data['cat_layout']) ?>">
                                        <?php $layouts = WPF()->tpl->find_layouts( WPFORO_THEME );
                                              if( !empty($layouts) ): foreach( $layouts as $layout ) : ?>
                                                    <div id="wpfl-<?php echo intval($layout['id']) ?>" data-layout="<?php echo intval($layout['id']) ?>" class="wpf-fl-box <?php if( wpfkey($data, 'is_cat') && $layout['id'] == $data['cat_layout'] ) echo 'wpf-fl-active' ?>">
                                                        <a href="#img1<?php echo intval($layout['id']); ?>"><img src="<?php echo WPFORO_URL ?>/wpf-themes/classic/layouts/<?php echo intval($layout['id']); ?>/view-forums.png" style="height: 100px;"/></a>
                                                        <a href="#_" class="wpf-lightbox" id="img1<?php echo intval($layout['id']); ?>"><img src="<?php echo WPFORO_URL ?>/wpf-themes/classic/layouts/<?php echo intval($layout['id']); ?>/view-forums.png"/></a>
                                                        <a href="#img2<?php echo intval($layout['id']); ?>"><img src="<?php echo WPFORO_URL ?>/wpf-themes/classic/layouts/<?php echo intval($layout['id']); ?>/view-posts.png" style="height: 100px;"/></a>
                                                        <a href="#_" class="wpf-lightbox" id="img2<?php echo intval($layout['id']); ?>"><img src="<?php echo WPFORO_URL ?>/wpf-themes/classic/layouts/<?php echo intval($layout['id']); ?>/view-posts.png"/></a>
                                                        <h4><?php echo esc_html($layout['name']) ?><!-- <input type="radio" name="forum[cat_layout]" value="<?php echo esc_attr(trim($layout['id'])) ?>" <?php echo ( $layout['id'] == $data['cat_layout'] ? ' checked="checked" ' : '' ); ?> /> --></h4>
                                                    </div>
                                            <?php endforeach; endif; ?>
                                        </div>
                                        <p style="font-style: italic; padding: 0 10px 5px; margin: 10px 0;"><i class="fas fa-info-circle" style="color: #777777; font-size: 14px; padding-right: 3px;"></i> <?php _e('Forums can be displayed with different layouts (Extended, Simplified, Q&A, Threaded). Use the "Category Layout" dropdown located on the top right section to set the layout you want. This option is only available for Categories (top parent forums). Other forums and sub-forums inherit it. They cannot have a different layout, therefore the layout dropdown option becomes disabled if this forum is not a Category.', 'wpforo'); ?></p>
                                    </div>
                                </div>

                                <div id="forum_slug" class="postbox">
									<h3 class="wpf-box-header"><span><?php _e('Forum Slug', 'wpforo'); ?> &nbsp;<a href="https://wpforo.com/docs/root/categories-and-forums/forum-manager/add-new-forum/#forum-slug" title="<?php _e('Read the documentation', 'wpforo') ?>" target="_blank" style="font-size: 14px;"><i class="far fa-question-circle"></i></a></span></h3>
									<div class="inside">
										<input name="forum[slug]"  type="text" value="<?php echo esc_attr(isset($data['slug']) ? $data['slug'] : '') ?>" size="40" />
										<p><?php _e('The "slug" is the URL-friendly version of the name. It is usually all lowercase and contains only letters, numbers, and hyphens.', 'wpforo'); ?> </p><br /> 
									</div>
								</div>
								
                                <div id="forum_icon" class="postbox">
									<h3 class="wpf-box-header"><span><?php _e('Forum Icon', 'wpforo'); ?> &nbsp;<a href="https://wpforo.com/docs/root/categories-and-forums/forum-manager/add-new-forum/#forum-icon" title="<?php _e('Read the documentation', 'wpforo') ?>" target="_blank" style="font-size: 14px;"><i class="far fa-question-circle"></i></a></span></h3>
									<div class="inside" style="padding-top:10px;">
										<div class="form-field">
											<label for="tag-icon" style="display:block; padding-bottom:5px;"><?php _e('Font-awesome Icon', 'wpforo'); ?>:</label>
											<input name="forum[icon]" value="<?php echo (isset($data['icon']) && $data['icon']) ? esc_attr($data['icon']) : 'fas fa-comments'; ?>" type="text"/>
                                            <p style="margin-bottom:0; margin-top:5px;"><?php _e('You can find all icons', 'wpforo'); ?> <a href="http://fontawesome.io/icons/" target="_blank"><?php _e('here', 'wpforo'); ?>.</a> <?php _e('Make sure you insert a class of font-awesome icon, it should start with fa- prefix like &quot;fas fa-comments&quot;.', '') ?></p>
										</div>
									</div>
								</div>
                                
								<div id="forum_meta" class="postbox">
									<h3 class="wpf-box-header"><span><?php _e('Forum SEO Description', 'wpforo'); ?></span></h3>
									<div class="inside" style="padding-top:10px;">
										<div class="form-field">
											<label for="tag-description" style="display:block; padding-bottom:5px;"><?php _e('Meta Description', 'wpforo'); ?>:</label>
											<textarea name="forum[meta_desc]" rows="3" cols="40"><?php echo esc_html(isset($data['meta_desc']) ? $data['meta_desc'] : '') ?></textarea>
										</div>
									</div>
								</div>

                                <?php do_action('wpforo_dashboard_forum_form_main', $data); ?>

							</div>
							<div id="advanced-sortables" class="meta-box-sortables ui-sortable"></div>
						</div>
						
					</div>
				</form>
			</div>
		<?php endif; ?><!-- chekcing creat forum permission-->
	<?php endif; ?>
	<!-- end Forum Add || Edit -->
	
	<!-- Forum Delete -->
	<?php if( $action === 'del') : ?>
		<form method="POST">
        	<?php wp_nonce_field( 'wpforo-forum-delete' ); ?>
			<input type="hidden" name="wpfaction" value="wpforo_forum_delete">
			<div class="form-wrap">
				<div class="form-field form-required">			
					<div class="form-field wpf-info-bar" style="padding:25px 20px 15px 20px; margin-top:20px;">
						<table class="wpforo_settings_table">
							<tr>
								<td style="width:50%;">
									<label for="delete_forum" class="menu_delete" style="color: red; font-size:13px; line-height:18px;"><?php _e('This action will also delete all sub-forums, topics and replies.', 'wpforo'); ?></label>
								</td>
								<td>
									<input id="delete_forum" type="radio" name="forum[delete]" value="1" checked="" onchange="mode_changer(false);">
								</td>
							</tr>
							<tr>
								<td>
									<label for="marge"  style="font-size:13px; line-height:18px;"><?php _e('If you want to delete this forum and keep its sub-forums, topics and replies, please select a new target forum in dropdown below', 'wpforo'); ?></label>
								</td>
								<td><input id="marge" type="radio" name="forum[delete]" value="0" onchange="mode_changer(true);"> </td>
							</tr>
							<tr>
								<td colspan="2">
                                    <select id="forum_select" name="forum[mergeid]" class="postform" disabled="" >
                                        <?php WPF()->forum->tree('select_box', false); ?>
                                    </select>
                                    <p><?php _e('All sub-forums, topics and replies will be attached to selected forum. Layout will be inherited from this forum.', 'wpforo'); ?></p>
                                </td>
							</tr>
                            <tr>
								<td colspan="2">
                                    <input id="forum_submit"  type="submit" name="forum[submit]" class="button button-primary" value="Delete">
                                </td>
							</tr>
						</table>
					</div>
				</div>
			</div>	
		</form>
	<?php endif; ?>
	<!-- end Forum Delete -->
	
</div><!-- wpwrap -->