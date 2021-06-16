<?php
	// Exit if accessed directly
	if( !defined( 'ABSPATH' ) ) exit;
 

class wpForoNotices{
	private $types   = array();
	private $notices = array();

	private function init_types() {
        $this->types = array_merge(
            array('neutral', 'error', 'success'),
            apply_filters( 'wpforo_notice_types', array() )
        );
		$this->types = array_map('strtolower', array_unique($this->types));
    }

	private function reset() {
        foreach ( $this->types as $type ) $this->notices[$type] = array();
    }

	public function is_empty(){
        foreach ( $this->notices as $notice ) if( !empty($notice) ) return false;
        return true;
    }

	public function __construct() {
	    $this->init_types();
	    $this->reset();
        add_action('wpforo_before_init', array($this, 'init'));
    }

	public function init() {
        if( WPF()->session_token ){
            $sql = "SELECT DISTINCT `key`, `value` FROM `" . WPF()->tables->logs . "` WHERE `sessionid` = %s AND `key` IN('". implode("','", $this->types) ."')";
            if( $notices = (array) WPF()->db->get_results( WPF()->db->prepare($sql, WPF()->session_token), ARRAY_A ) ){
                foreach ($notices as $notice){
                    if( trim( $notice['value'] ) ){
                        $this->notices[ $notice['key'] ] = array_merge( $this->notices[ $notice['key'] ], wpforo_is_json($notice['value']) ? json_decode($notice['value'], true) : (array) $notice['value'] );
                    }
                }
            }
        }
    }

 	/**
	 * 
	 * @param string|array $args
	 * @param string $type (e.g. success|error)
     * @param string|array $s
	 * 
	 * @return bool
	 */
	public function add( $args, $type = 'neutral', $s = array() ){
		if(!$args) return false;
        $args = (array) $args;
        if( $s && count($args) === 1 && is_array($s) && isset($s[0]) && !is_array($s[0]) ){
            $s = array($s);
        }else{
            $s = (array) $s;
        }

        if( WPF()->session_token ){
            $type = strtolower($type);
            foreach($args as $key => $arg){
                if( $s && isset($s[$key]) ){
                    $args[$key] = wpforo_sprintf_array( wpforo_phrase($arg, false), $s[$key] );
                }else{
                    $args[$key] = wpforo_phrase($arg, false);
                }
            }

            $this->notices[$type] = array_merge( (array) $this->notices[$type], (array) $args);
	        $this->notices[$type] = array_unique($this->notices[$type]);

	        if( !wpforo_is_ajax() ){
	            $insert_id_backup = WPF()->db->insert_id;
		        WPF()->db->insert(
			        WPF()->tables->logs,
			        array(
				        'sessionid' => WPF()->session_token,
				        'key'       => $type,
				        'value'     => json_encode( (array) $args )
			        ),
			        array( '%s', '%s', '%s' )
		        );
		        WPF()->db->insert_id = $insert_id_backup;
	        }

			return true;
		}
		
		return false;
	}
	
	/**
	* 
	* @return bool
	* 
	*/
	public function clear(){
		if( WPF()->session_token ){
            $this->reset();

            WPF()->db->delete(
                WPF()->tables->logs,
                array('sessionid' => WPF()->session_token),
                array('%s')
            );

			return true;
		}
		
		return false;
	}
	
	/**
	* <p class="success">success msg text</p><p class="error">error msg text</p>
	* 
	* @return string
	*/
	public function get_notices(){
	    $inner = '';
	    if( !$this->is_empty() ){
		    foreach( $this->notices as $type => $notice ){
			    $notice = (array) $notice;
			    foreach ($notice as $msg){
				    if( !is_array($msg) ){
					    if( $msg = trim($msg) ) $inner .= sprintf('<p class="%s">%s</p>', sanitize_html_class($type), $msg);
				    }
			    }
		    }

		    $this->clear();
	    }
		return $inner;
	}
	
	/**
	* 
	* show collected wpforo notices
	* 
	* @return void
	*/
	public function show(){
		if( $this->is_empty() ) return;
		if( wpforo_is_admin() ){
			$this->backend($this->notices);
		}else{
			$this->frontend($this->notices);
		}
		$this->clear();
	}

	private function backend($notices){
		$inner = '';
		foreach($notices as $type => $notice){
			$notice = (array) $notice;
			foreach ($notice as $msg){
				if( !is_array($msg) && ($msg = trim($msg)) ){
					$inner .= sprintf(
						'<div class="notice is-dismissible notice-%s">
                            <p>%s</p>
                            <button type="button" class="notice-dismiss">
                                <span class="screen-reader-text">%s</span>
                            </button>
                        </div>',
						sanitize_html_class($type), wpforo_kses($msg), __('Dismiss this notice.', 'wpforo'));
				}
			}
		}
		echo '<div class="wpf-backend-notices-wrap">' . $inner . '</div>';
	}

	private function frontend($notices){
		$inner = '';
		foreach($notices as $type => $notice){
			$notice = (array) $notice;
			foreach ($notice as $msg){
				if( !is_array($msg) && ($msg = trim($msg)) ){
					$inner .= sprintf('<p class="%s">%s</p>', sanitize_html_class($type), $msg);
				}
			}
		}
		?>
		<script type="text/javascript">
            window.jQuery(document).ready(function(){
                wpforo_notice_show("<?php echo addslashes(wpforo_kses($inner)) ?>");
            });
		</script>
		<?php
	}
	
	public function addonNote() {
        $lastHash = get_option('wpforo-addon-note-dismissed');
		$first = get_option('wpforo-addon-note-first');
		if( !$lastHash ){
			$hash = $this->addonHash();
        	update_option('wpforo-addon-note-dismissed', $hash);
			update_option('wpforo-addon-note-first', 'true');
			wpforo_clean_cache('option');
		}
		elseif( $lastHash || $first == 'false' ){
			$lastHashArray = explode(',', $lastHash);
			$currentHash = $this->addonHash();
			if ($lastHash != $currentHash) {
				?>
				<div class="updated notice wpforo_addon_note is-dismissible" style="margin-top:10px;">
					<p style="font-weight:normal; font-size:15px; border-bottom:1px dotted #DCDCDC; padding-bottom:10px; width:95%;"><strong><?php _e('New Addons for Your Forum!', 'wpforo'); ?></strong><br><span style="font-size:14px;"><?php _e('Extend your forum with wpForo addons', 'wpforo'); ?></span></p>
					<div style="font-size:14px;">
						<?php
						foreach (WPF()->addons as $key => $addon) {
							if (in_array($addon['title'], $lastHashArray))
								continue;
							?>
							<div style="display:inline-block; min-width:27%; padding-right:10px; margin-bottom:1px;border-bottom:1px dotted #DCDCDC; border-right:1px dotted #DCDCDC; padding-bottom:10px;"><img src="<?php echo $addon['thumb'] ?>" style="height:40px; width:auto; vertical-align:middle; margin:0 10px; text-decoration:none;" />  <a href="<?php echo $addon['url'] ?>" style="text-decoration:none;" target="_blank">wpForo <?php echo $addon['title']; ?></a></div>
							<?php
						}
						?>
						<div style="clear:both;"></div>
					</div>
					<p>&nbsp;&nbsp;&nbsp;<a href="<?php echo admin_url('admin.php?page=wpforo-addons') ?>"><?php _e('View all Addons', 'wpforo'); ?> &raquo;</a></p>
				</div>
				<script>jQuery(document).on( 'click', '.wpforo_addon_note .notice-dismiss', function() {jQuery.ajax({url: ajaxurl, data: { action: 'dismiss_wpforo_addon_note'}})})</script>
				<?php
			}
		}
    }

    public function dismissAddonNote() {
        $hash = $this->addonHash();
        update_option('wpforo-addon-note-dismissed', $hash);
	    wpforo_clean_cache('option');
        exit();
    }

    public function dismissAddonNoteOnPage() {
        $hash = $this->addonHash();
        update_option('wpforo-addon-note-dismissed', $hash);
	    wpforo_clean_cache('option');
    }

    public function addonHash() {
        $viewed = '';
        foreach (WPF()->addons as $key => $addon) {
            $viewed .= $addon['title'] . ',';
        }
        $hash = $viewed;
        return $hash;
    }

    public function refreshAddonPage() {
        $lastHash = get_option('wpforo-addon-note-dismissed');
        $currentHash = $this->addonHash();
        if ($lastHash != $currentHash) {
            ?>
            <script language="javascript">jQuery(document).ready(function () {
                    location.reload();
                });</script>
            <?php
        }
    }
}