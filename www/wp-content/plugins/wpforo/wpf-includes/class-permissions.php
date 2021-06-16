<?php
	// Exit if accessed directly
	if( !defined( 'ABSPATH' ) ) exit;
 

class wpForoPermissions{
	public $default;
	public $accesses;

	static $cache = array();
	
	function __construct(){
		$this->init_defaults();
	}

	private function init_defaults() {
		$this->default = new stdClass;

		$this->default->access = array(
			'accessid' => 0,
			'access'   => '',
			'title'    => '',
			'cans'     => ''
		);
	}

	public function init(){
        if( WPF()->is_installed() ){
            if( $accesses = $this->get_accesses() ){
                foreach( $accesses as $access ) {
                    $this->accesses[intval($access['accessid'])] = $this->accesses[$access['access']] = $access;
                }
            }
        }
    }

    public function init_current_user_accesses(){
	    WPF()->current_user_accesses = $this->get_forum_accesses_by_usergroup();
    }

	public function fix_access($access){
		$access         = wpforo_array_args_cast_and_merge((array) $access, $this->default->access);
		$cans           = array_map('__return_zero', WPF()->forum->cans);
		$access['cans'] = maybe_unserialize($access['cans']);
		if( is_array($access['cans']) ){
			$access['cans'] = wpforo_array_args_cast_and_merge($access['cans'], $cans);
		}else{
			$access['cans'] = $cans;
		}
		return $access;
	}
 	
 	/**
	 * 
	 * @param string|int $access
	 * 
	 * @return array access row by access key
	 */
 	function get_access($access){
 		if( is_numeric($access) ){
		    $access = intval($access);
	    }else{
		    $access = sanitize_text_field($access);
	    }
		if( !empty($this->accesses[$access]) ){
			return $this->accesses[$access];
		}else{
			$sql = "SELECT * FROM " . WPF()->tables->accesses;
			if( is_int($access) ){
				$sql .= " WHERE `accessid` = %d";
			}else{
				$sql .= " WHERE `access` = %s";
			}
			return WPF()->db->get_row(WPF()->db->prepare($sql, $access), ARRAY_A);
		}
	}
	
	
 	/**
	* get all accesses from accesses table
	* 
	* @return array|null
	*/
 	function get_accesses(){
		$sql = "SELECT * FROM ".WPF()->tables->accesses . " ORDER BY `accessid`";
		return WPF()->db->get_results($sql, ARRAY_A);
	}
 	
	/**
	 * @param array $access
	 *
	 * @return int|bool inserted id or false
	 */
	function add($access){
		if(!$access['access']) $access['access'] = sanitize_title($access['title']);
		
		$i = 2;
		$slug = $access['access'];
		while( WPF()->db->get_var(WPF()->db->prepare("SELECT `access` FROM ".WPF()->tables->accesses." WHERE `access` = %s", sanitize_text_field($slug))) ){
			$slug = $access['access'] . '-' . $i;
			$i++;
		}
		
		if(WPF()->db->insert(
			WPF()->tables->accesses,
			array(
				'title'  => sanitize_text_field( $access['title'] ),
				'access' => sanitize_text_field( $slug ),
				'cans'   => serialize( $access['cans'] )
			),
			array('%s','%s','%s')
		)){
			$access['accessid'] = WPF()->db->insert_id;
			WPF()->notice->add('Access successfully added', 'success');
			return $access['accessid'];
		}
		
		WPF()->notice->add('Access add error', 'error');
		return false;
	}

	/**
	 * @param array $access
	 *
	 * @return bool|int edited id or false
	 */
	function edit($access){
		if( false !== WPF()->db->update(
			WPF()->tables->accesses,
			array(
				'title' => sanitize_text_field($access['title']),
				'cans'  => serialize($access['cans']),
			),
			array(
				'accessid' => $access['accessid']
			),
			array('%s','%s'),
			array('%d')
		)){
			WPF()->notice->add('Access successfully edited', 'success');
			return $access['accessid'];
		}
		
		WPF()->notice->add('Access edit error', 'error');
		return false;
	}

	/**
	 * @param int $accessid
	 *
	 * @return bool|int deleted id or false
	 */
	function delete($accessid){
		$accessid = intval($accessid);
		if(!$accessid){
			WPF()->notice->add('Access delete error', 'error');
			return false;
		}
		
		if( false !== WPF()->db->delete( WPF()->tables->accesses, array( 'accessid' => $accessid ), array('%d') ) ){
			WPF()->notice->add('Access successfully deleted', 'success');
			return $accessid;
		}
		
		WPF()->notice->add('Access delete error', 'error');
		return false;
	}
	
	function forum_can( $do, $forumid = NULL, $groupid = NULL, $second_usergroupids = NULL ){
		/**
		 * filter for other add-ons to manage can_attach bool value.
		 * e.g. PM add-on attachment function.
		 */
		$filter_forum_can = apply_filters('wpforo_permissions_forum_can', null, $do, $forumid, $groupid, $second_usergroupids);
		if( !is_null($filter_forum_can) ) return (int) (bool) $filter_forum_can;

		$can = 0;
        $second_can = 0;
        $secondary_can = 0;
		if( !WPF()->current_user_groupid && is_null($groupid) || !$do ) return 0;

		//User Forum accesses from Current Object of Current user
        if( !empty(WPF()->current_user_accesses) && is_null($groupid) ){
            if( is_null($forumid) && wpfval(WPF()->current_object, 'forum', 'forumid') ) {
                $forum_id = WPF()->current_object['forum']['forumid'];
            } else {
            	$forum_id = ( is_array($forumid) && wpfkey($forumid, 'forumid') ) ? $forumid['forumid'] : $forumid;
            }
            if( $forum_id ){
                //Primary Usergroup
                $primary_can = wpfval( WPF()->current_user_accesses, 'primary', $forum_id, $do );
                //Secondary Usergroup
                if( wpfval(WPF()->current_user_accesses, 'secondary') ) {
                    $secondary_accesses = wpfval( WPF()->current_user_accesses, 'secondary' );
                    foreach( $secondary_accesses as $secondary_access ){
                        $secondary_can = wpfval($secondary_access, $forum_id, $do); if( $secondary_can ) break;
                    }
                }
                //Return Access
                if( !$primary_can && $secondary_can ){
                    return $secondary_can;
                } else {
                    return $primary_can;
                }
            }
        }

        //Use Custom User Forum Accesses
        if( is_null($forumid) ) {
            $forum = WPF()->current_object['forum'];
        }else{
            if( is_array($forumid) && wpfkey($forumid, 'forumid') ){
                $forum = $forumid;
            }else{
                $forum = WPF()->forum->get_forum($forumid);
            }
        }

        if( is_null($groupid) ) {
            $groupid = WPF()->current_user_groupid;
        }
        if( is_null($second_usergroupids) && WPF()->current_user_secondary_groupids ) {
            $second_usergroupids = explode(',', WPF()->current_user_secondary_groupids );
        }
        if( !is_null($second_usergroupids) && is_string($second_usergroupids) ) {
            $second_usergroupids = explode(',', $second_usergroupids);
        }

        if( $forum ){
            $permissions = unserialize($forum['permissions']);
            //Primary Usergroup
            if( isset($permissions[$groupid]) ){
	            $access = $this->fix_access( $this->get_access($permissions[$groupid]) );
	            $can = intval( wpfval($access['cans'], $do) );
            }
            //Secondary Usergroup
            if( !empty($second_usergroupids) && is_array($second_usergroupids) ){
                $second_usergroupids = array_map('intval', $second_usergroupids );
                foreach( $second_usergroupids as $second_usergroupid ){
                    if( isset($permissions[$second_usergroupid]) ){
	                    $access = $this->fix_access( $this->get_access($permissions[$second_usergroupid]) );
	                    if( $second_can = intval( wpfval($access['cans'], $do) ) ) break;
                    }
                }
            }
        }

        if( !$can && $second_can ){
            return $second_can;
        } else {
            return $can;
        }
	}
	
	function usergroup_can( $do, $groupid = NULL, $second_groupids = NULL ){
		if( is_null($groupid) && is_null($second_groupids) && WPF()->current_user_secondary_groupids ) {
			$second_groupids = explode(',', WPF()->current_user_secondary_groupids );
		}
	    if( is_null($groupid) ) {
	        if( current_user_can('administrator') ) return 1;
	        $groupid = WPF()->current_user_groupid;
        }
        $groupid   = intval($groupid);
        $usergroup = WPF()->usergroup->get_usergroup( $groupid );
        $cans      = unserialize($usergroup['cans']);
        $can       = ( isset($cans[$do]) ? $cans[$do] : 0 );

        $second_can = 0;
        if( !is_null($second_groupids) && is_string($second_groupids) ) {
            $second_groupids = explode(',', $second_groupids);
        }
        if( !empty($second_groupids) && is_array($second_groupids) ){
            $second_groupids = array_map('intval', $second_groupids );
            foreach( $second_groupids as $second_usergroupid ){
                if( $second_usergroupid ){
                    $second_usergroup = WPF()->usergroup->get_usergroup( $second_usergroupid );
                    $second_cans = unserialize($second_usergroup['cans']);
                    $second_can = ( isset($second_cans[$do]) ? $second_cans[$do] : 0 );
                    if( $second_can ) break;
                }
            }
        }

        if( !$can && $second_can ){
            return $second_can;
        } else {
            return $can;
        }
	}
	
	function usergroups_can( $do ){
		$usergroupids = array();
		$usergroups = WPF()->usergroup->get_usergroups();
		foreach( $usergroups as $usergroup ){
			$cans = unserialize( $usergroup['cans'] );
			if( isset($cans[$do]) && $cans[$do] ){
				$usergroupids[] = $usergroup['groupid'];
			}
		}
		return $usergroupids;
	}
	
	function user_can_manage_user( $user_id, $managing_user_id ){
		
		if( !$user_id || !$managing_user_id ) return false;
		if( $user_id == $managing_user_id ) return true;
		
		$user = new WP_User( $user_id ); 
		$user_level = $this->user_wp_level( $user );
		if( !empty($user->roles) && is_array($user->roles) ) $user_role = array_shift($user->roles);
		
		$managing_user = new WP_User( $managing_user_id );  
		$managing_user_level = $this->user_wp_level( $managing_user );
		if( !empty($managing_user->roles) && is_array($managing_user->roles) ) $managing_user_role = array_shift($managing_user->roles);
		
		if( (int)$user_level > (int)$managing_user_level ){
			return true;
		}
		elseif( $user_id == 1 && $user_role == 'administrator' ){
			return true;
		}
		elseif( (int)$user_level == (int)$managing_user_level ){
			$member = WPF()->member->get_member( $user_id );
			$managing_member = WPF()->member->get_member( $managing_user_id );
			$user_wpforo_can = $this->usergroup_can( 'em', $member['groupid'] );
			$managing_user_wpforo_can = $this->usergroup_can( 'em', $managing_member['groupid'] );
			if( $user_wpforo_can && !$managing_user_wpforo_can ){
				return true;
			}
			else{
				return false;
			}
		}
		elseif( $user_id != 1 && $managing_user_id == 1 && $managing_user_role == 'administrator' ){
			return false;
		}
		else{
			return false;
		}
	}

    function user_wp_level( $user_object ){
        $level = 0;
        $levels = array();
        if( is_int($user_object) ){
            $user_object = new WP_User( $user_object );
        }
        if( isset($user_object->allcaps) && is_array($user_object->allcaps) && !empty($user_object->allcaps) ){
            foreach($user_object->allcaps as $level_key => $level_value){
                if( strpos($level_key, 'level_') !== FALSE && $level_value == 1 ){
                    $levels[] = intval(str_replace('level_', '', $level_key));
                }
            }
            if(!empty($levels)){
                $level = max($levels);
            }
        }
        return $level;
    }

	function can_edit_user( $userid ){

	    if( !$userid ) return false;

        if( !( $userid == WPF()->current_userid ||
            ( WPF()->perm->usergroup_can('em') &&
                WPF()->perm->user_can_manage_user( WPF()->current_userid, $userid )
            )
        )
        ){
            WPF()->notice->clear();
            WPF()->notice->add('Permission denied', 'error');
            wp_redirect(wpforo_get_request_uri());
            exit();
        }

        return true;
    }
	
	public function can_link(){
		if( !WPF()->perm->usergroup_can( 'em' ) ){
			$posts = WPF()->member->member_approved_posts( WPF()->current_userid );
			$posts = intval($posts);
			if( isset(WPF()->tools_antispam['min_number_post_to_link']) ){
				$min_posts = intval(WPF()->tools_antispam['min_number_post_to_link']);
				if( $min_posts !== 0 ){
					if ( $posts <= $min_posts ) {
						return false;
					}
				}
			}
		}
		return true;
	}
	
	public function can_attach($forumid = null){
		if( !$forumid ) $forumid = null;

		/**
		 * filter for other add-ons to manage can_attach bool value.
		 * e.g. PM add-on attachment function.
		 */
		$filter_wpforo_can_attach = apply_filters('wpforo_can_attach', null, $forumid);
		if( !is_null($filter_wpforo_can_attach) ) return (bool) $filter_wpforo_can_attach;

		if( !$this->forum_can('a', $forumid) ) return false;
		if( !$this->usergroup_can( 'em' ) ){
			$posts = WPF()->member->member_approved_posts( WPF()->current_userid );
			$posts = intval($posts);
			if( isset(WPF()->tools_antispam['min_number_post_to_attach']) ){
				$min_posts = intval(WPF()->tools_antispam['min_number_post_to_attach']);
				if( $min_posts != 0 ){
					if ( $posts <= $min_posts  ) {
						return false;
					}
				}
			}
		}
		return true;
	}
	
	public function can_attach_file_type( $ext = '' ){
		if( !$this->usergroup_can( 'em' ) ){
			if( isset(WPF()->tools_antispam['limited_file_ext']) && WPF()->member->current_user_is_new() ){
				$expld = explode('|', WPF()->tools_antispam['limited_file_ext'] );
				if( in_array($ext, $expld) ){
					return false;
				}
			}
		}
		return true;
	}

	/**
	 * @return bool
	 */
	public function can_post_now() {
		date_default_timezone_set('UTC');
		ini_set( 'date.timezone', 'UTC' );

		if ( wpforo_is_admin() || ( defined( 'IS_GO2WPFORO' ) && IS_GO2WPFORO ) ) {
			return true;
		}

		$email   = ( ( $userid = WPF()->current_userid ) ? '' : WPF()->current_user_email );
		$groupid = WPF()->current_user_groupid;
		if ( WPF()->member->current_user_is_new() ) {
			$groupid = 0;
		}
		if ( ! $flood_interval = WPF()->usergroup->get_flood_interval( $groupid ) ) {
			return true;
		}
		$hour_ago = gmdate( 'Y-m-d H:i:s', time() - HOUR_IN_SECONDS );

		$args        = array(
			'userid'    => $userid,
			'email'     => $email,
			'orderby'   => '`created` DESC, `postid` DESC',
			'row_count' => 1,
			'where'     => "`created` >= '$hour_ago'"
		);
		$items_count = 0;
		$lastpost    = WPF()->post->get_posts( $args, $items_count, false );
		if ( $lasttime = wpfval($lastpost, 0, 'created' ) ) {
		     $lasttime = strtotime( $lasttime );
			 $nowtime  = current_time( 'timestamp', 1 );
			 $diff     = $nowtime - $lasttime;
			 if ( $diff < $flood_interval ) {
				return false;
			 }
		}
		return true;
	}

    public function get_forum_accesses_by_usergroup( $usergroup = 0, $secondary_usergroups = '' ){

	    $user_accesses = array('primary' => array(), 'secondary' => array() );
	    $forums = WPF()->forum->get_forums();
        $usergroup = ( $usergroup ) ? $usergroup : WPF()->current_user_groupid;
        $secondary_usergroups = ( $secondary_usergroups ) ? $secondary_usergroups : WPF()->current_user_secondary_groupids;
        $secondary_usergroups = ( $secondary_usergroups ) ? explode( ',', $secondary_usergroups ) : array();
        if(!empty($forums)){
            foreach( $forums as $forum ){
                if( wpfval( $forum, 'permissions' ) ){
                    $permissions = unserialize( $forum['permissions'] );
                    if( !empty($permissions) ){
                        //Primary Usergroup Access
                        if( wpfval( $permissions, $usergroup ) ){
                            if(wpfval($this->accesses, $permissions[ $usergroup ], 'cans')){
                                $user_accesses['primary'][ $forum['forumid'] ] = unserialize($this->accesses[ $permissions[ $usergroup ] ]['cans']);
                            }
                        }
                        //Secondary Usergroup Access
                        if( !empty($secondary_usergroups) ){
                            foreach( $secondary_usergroups as $secondary_usergroup ){
                                if( wpfval( $permissions, $secondary_usergroup ) ){
                                    if(wpfval($this->accesses, $permissions[ $secondary_usergroup ], 'cans')){
                                        $user_accesses['secondary'][ $secondary_usergroup ][ $forum['forumid'] ] = unserialize($this->accesses[ $permissions[ $secondary_usergroup ] ]['cans']);
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
        return $user_accesses;
    }

    public function show_accesses_selectbox($selected = array(), $exclude = array()) {
	    $accesses = $this->get_accesses();
	    foreach($accesses as $accesse){
	    	if(in_array($accesse['access'], (array) $exclude)) continue;
		    printf(
			    '<option value="%1$s" %2$s>%3$s</option>',
			    esc_attr( $accesse['access'] ),
			    in_array( $accesse['access'], (array) $selected ) ? 'selected' : '',
			    esc_html( $accesse['title'] )
		    );
	    }
    }
}