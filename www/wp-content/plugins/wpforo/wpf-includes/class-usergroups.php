<?php
	// Exit if accessed directly
	if( !defined( 'ABSPATH' ) ) exit;
 
class wpForoUsergroup{
    public $default;
    public $default_groupid;
    public $cans;
    public $current;
    private $post_flood_intervals;

	static $cache = array( 'usergroup' => array(), 'user' => array(), 'user_second' => array() );
	
	function __construct(){
        $this->init_defaults();
        $this->init_options();
        $this->init_hooks();
	}

    private function init_defaults(){
        $this->default = new stdClass;

        $this->default->default_groupid = 3;

	    $this->default->group = array(
		    'groupid'     => 0,
		    'name'        => '',
		    'cans'        => '',
		    'description' => '',
		    'utitle'      => '',
		    'role'        => '',
		    'access'      => '',
		    'color'       => '',
		    'visible'     => 0,
		    'secondary'   => 0
	    );

	    $this->default->post_flood_intervals = array(
	    	0 => 0,
		    1 => 0,
		    2 => 0,
		    3 => 0,
		    4 => 0,
		    5 => 0
	    );

	    $this->default->cans = array(
		    'mf'  => __( 'Dashboard - Manage Forums', 'wpforo' ),
		    'ms'  => __( 'Dashboard - Manage Settings', 'wpforo' ),
		    'mt'  => __( 'Dashboard - Manage Tools', 'wpforo' ),
		    'vm'  => __( 'Dashboard - Manage Members', 'wpforo' ),
		    'aum' => __( 'Dashboard - Moderate Topics & Posts', 'wpforo' ),
		    'vmg' => __( 'Dashboard - Manage Usergroups', 'wpforo' ),
		    'mp'  => __( 'Dashboard - Manage Phrases', 'wpforo' ),
		    'mth' => __( 'Dashboard - Manage Themes', 'wpforo' ),

		    'em' => __( 'Dashboard - Can edit member', 'wpforo' ),
		    'bm' => __( 'Dashboard - Can ban member', 'wpforo' ),
		    'dm' => __( 'Dashboard - Can delete member', 'wpforo' ),

		    'aup'       => __( 'Front - Can pass moderation', 'wpforo' ),
		    'view_stat' => __( 'Front - Can view statistic', 'wpforo' ),
		    'vmem'      => __( 'Front - Can view members', 'wpforo' ),
		    'vprf'      => __( 'Front - Can view profiles', 'wpforo' ),
		    'vpra'      => __( 'Front - Can view member activity', 'wpforo' ),
		    'vprs'      => __( 'Front - Can view member subscriptions', 'wpforo' ),

		    'upa' => __( 'Front - Can upload avatar', 'wpforo' ),
		    'ups' => __( 'Front - Can have signature', 'wpforo' ),
		    'va'  => __( 'Front - Can view avatars', 'wpforo' ),

		    'vmu'  => __( 'Front - Can view member username', 'wpforo' ),
		    'vmm'  => __( 'Front - Can view member email', 'wpforo' ),
		    'vmt'  => __( 'Front - Can view member title', 'wpforo' ),
		    'vmct' => __( 'Front - Can view member custom title', 'wpforo' ),
		    'vmr'  => __( 'Front - Can view member reputation', 'wpforo' ),
		    'vmw'  => __( 'Front - Can view member website', 'wpforo' ),
		    'vmsn' => __( 'Front - Can view member social networks', 'wpforo' ),
		    'vmrd' => __( 'Front - Can view member reg. date', 'wpforo' ),
		    'vml'  => __( 'Front - Can view member location', 'wpforo' ),
		    'vmo'  => __( 'Front - Can view member occupation', 'wpforo' ),
		    'vms'  => __( 'Front - Can view member signature', 'wpforo' ),
		    'vmam' => __( 'Front - Can view member about me', 'wpforo' ),
		    'vwpm' => __( 'Front - Can write PM', 'wpforo' ),
		    'caa'  => __( 'Front - Can access to attachments', 'wpforo' ),
		    'vt_add_topic'  => __( 'Front - Can access to add topic page', 'wpforo' )
	    );
    }

    private function init_options(){
        $this->default_groupid = get_wpf_option('wpforo_default_groupid', $this->default->default_groupid);
        $this->post_flood_intervals = get_wpf_option('wpforo_post_flood_intervals', $this->default->post_flood_intervals);
        $this->cans = apply_filters('wpforo_usergroup_cans', $this->default->cans);
    }

    public function init_current(){
		if( !$this->current = $this->get_usergroup( WPF()->current_user_groupid ) ){
			$this->current = $this->get_usergroup();
		}
    }

    private function init_hooks(){
//		add_action('wpforo_after_add_usergroup', array($this, 'after_add_edit_usergroup'));
//		add_action('wpforo_after_edit_usergroup', array($this, 'after_add_edit_usergroup'));
    }

    public function get_flood_interval($groupid, $obj = 'post'){
		$flood_interval = ( wpfkey($this->post_flood_intervals, $groupid) ? $this->post_flood_intervals[$groupid] : 3 );
		return apply_filters('wpforo_usergroup_get_flood_interval', intval($flood_interval), $groupid, $obj);
    }

    public function fix_group($group){
	    $group = wpforo_array_args_cast_and_merge((array) $group, $this->default->group);
	    $cans = array_map('__return_zero', $this->cans);
	    $group['cans'] = maybe_unserialize($group['cans']);
		if( is_array($group['cans']) ){
			$group['cans'] = wpforo_array_args_cast_and_merge($group['cans'], $cans);
		}else{
			$group['cans'] = $cans;
		}
	    return $group;
    }
	
	function usergroup_list_data(){
		$ugdata = array();
		$groups = WPF()->db->get_results('SELECT * FROM '.WPF()->tables->usergroups.' ORDER BY `name` ', ARRAY_A);
		foreach($groups as $group){
			$user_count = WPF()->db->get_var("SELECT COUNT(*) FROM ".WPF()->tables->profiles." WHERE `groupid` = " . intval($group['groupid']) . " OR FIND_IN_SET(" . intval($group['groupid']) . ", `secondary_groups`)");
			$ugdata[$group['groupid']]['groupid'] = intval($group['groupid']);
			$ugdata[$group['groupid']]['name'] = wpforo_phrase($group['name'], FALSE);
            $ugdata[$group['groupid']]['role'] = $group['role'];
			$ugdata[$group['groupid']]['count'] = intval($user_count);
			$ugdata[$group['groupid']]['access'] = $group['access'];
			$ugdata[$group['groupid']]['color'] = $group['color'];
            $ugdata[$group['groupid']]['secondary'] = $group['secondary'];
		}
		return $ugdata;
	}
	
	function add($title, $cans = array(), $description = '', $role = 'subscriber', $access = 'standard', $color = '', $visible = 1, $secondary = 0 ){
		$i = 2;
		$real_title = $title;
		while( WPF()->db->get_var(
				WPF()->db->prepare(
					"SELECT `groupid` FROM `".WPF()->tables->usergroups."` WHERE `name` = %s",
					sanitize_text_field($title)
				)
			)
		){
			$title = $real_title . '-' . $i;
			$i++;
		}

		$group = array(
			'name'        => sanitize_text_field( $title ),
			'cans'        => serialize( wpforo_parse_args( $cans, array_map('__return_zero', $this->cans) ) ),
			'description' => $description,
			'utitle'      => sanitize_text_field( $real_title ),
			'role'        => $role,
			'access'      => $access,
			'color'       => $color,
			'visible'     => $visible,
			'secondary'   => $secondary
		);

		if(	WPF()->db->insert(
			WPF()->tables->usergroups,
			$group,
			array('%s','%s','%s','%s','%s','%s','%s','%d','%d')
		)){
			$group['groupid'] = WPF()->db->insert_id;

			do_action('wpforo_after_add_usergroup', $group);

			WPF()->notice->add('User group successfully added', 'success');
			return $group['groupid'];
		}
		
		WPF()->notice->add('User group add error', 'error');
		return FALSE;
	}
	
	function edit( $groupid, $title, $cans, $description = '', $role = NULL, $access = NULL, $color = '', $visible = 1, $secondary = 0 ){
		if( !WPF()->perm->usergroup_can('vmg') ){
			WPF()->notice->add('Permission denied', 'error');
			return FALSE;	
		}

		if( $groupid = intval($groupid) ){
			$old_group = $this->get_usergroup($groupid);
			$group = array(
				'name'        => sanitize_text_field( $title ),
				'cans'        => serialize( wpforo_parse_args( $cans, array_map( '__return_zero', $this->cans ) ) ),
				'description' => $description,
				'utitle'      => $old_group['utitle'],
				'role'        => is_null($role)   ? $old_group['role']   : $role,
				'access'      => is_null($access) ? $old_group['access'] : $access,
				'color'       => $color,
				'visible'     => $visible,
				'secondary'   => $secondary
			);

			if( FALSE !== WPF()->db->update(
				WPF()->tables->usergroups,
				$group,
				array('groupid' => $groupid),
				array('%s','%s','%s','%s','%s','%s','%s','%d','%d'),
				array('%d')
			)){
				$group['groupid'] = $groupid;

				do_action('wpforo_after_edit_usergroup', $group);

				WPF()->notice->add('User group successfully edited', 'success');
				return $groupid;
			}
		}
		
		WPF()->notice->add('User group edit error', 'error');
		return FALSE;
	}
	
	function delete($groupid, $mergeid){
		if( !WPF()->perm->usergroup_can('vmg') ){
			WPF()->notice->add('Permission denied', 'error');
			return FALSE;	
		}

		if( ($groupid = intval($groupid)) && !in_array($groupid, array(1,4)) ){
			if( $mergeid = intval($mergeid) ){
				$sql = "UPDATE `".WPF()->tables->profiles."` SET `groupid` = %d WHERE `groupid` = %d";
				WPF()->db->query( WPF()->db->prepare($sql, $mergeid, $groupid) );
			}

			if( false !== WPF()->db->delete(
					WPF()->tables->usergroups,
					array('groupid' => $groupid),
					array('%d')
				)){
				WPF()->notice->add(wpforo_phrase('Usergroup has been successfully deleted. All users of this usergroup have been moved to the usergroup you\'ve chosen', false), 'success');
				return $groupid;
			}
		}

		WPF()->notice->add('Can\'t delete this Usergroup', 'error');
		return false;
	}
	
	function get_usergroup( $groupid = 4 ){
		// Guest UsergroupID = 4
		$cache = WPF()->cache->on('memory_cashe');
		if( $cache && isset(self::$cache['usergroup'][$groupid]) ){
			return self::$cache['usergroup'][$groupid];
		}
		$usergroup = WPF()->db->get_row("SELECT * FROM `".WPF()->tables->usergroups."` WHERE `groupid` = ".intval($groupid), ARRAY_A);
		if($cache && isset($groupid)){
			self::$cache['usergroup'][$groupid] = $usergroup;
		}
		return $usergroup;
	}
	
	function get_usergroups( $field = 'full' ){
        $cache = WPF()->cache->on('memory_cashe');
        if( $cache && isset(self::$cache['usergroups'][$field])  ) return self::$cache['usergroups'][$field];

		if( $field == 'full' ){
            $results = WPF()->db->get_results("SELECT * FROM `".WPF()->tables->usergroups."`", ARRAY_A);
		}else{
            $results = WPF()->db->get_col("SELECT `$field` FROM `".WPF()->tables->usergroups."`");
		}

        if( $cache ) self::$cache['usergroups'][$field] = $results;
        return $results;
	}
	
	function get_groupid_by_userid( $userid ){
		$cache = WPF()->cache->on('memory_cashe');
		if( $cache && isset(self::$cache['user'][$userid]) ){
			return self::$cache['user'][$userid];
		}
		$groupid = WPF()->db->get_var("SELECT `groupid` FROM `".WPF()->tables->profiles."` WHERE `userid` = " . intval($userid));
		if($cache && isset($groupid)){
			self::$cache['user'][$userid] = $groupid;
		}
		return $groupid;
	}

    function get_second_groupid_by_userid( $userid ){
        $cache = WPF()->cache->on('memory_cashe');
        if( $cache && isset(self::$cache['user_second'][$userid]) ){
            return self::$cache['user_second'][$userid];
        }
        $second_groupid = WPF()->db->get_var("SELECT `secondary_groups` FROM `".WPF()->tables->profiles."` WHERE `userid` = " . intval($userid));
        if($cache && isset($second_groupid)){
            self::$cache['user_second'][$userid] = $second_groupid;
        }
        return $second_groupid;
    }

	/**
	 * @param array|int $selected
	 * @param array|int $exclude
	 *
	 * @return string
	 */
	public function get_selectbox($selected = array(), $exclude = array() ){
        $selected = array_map('intval', (array) $selected );
        $exclude  = array_map('intval', (array) $exclude );
		$html     = '';
		foreach($this->usergroup_list_data() as $group){
			if( in_array($group['groupid'], $exclude) ) continue;
			$html .= sprintf('<option value="%1$s" %2$s>%3$s</option>',
				intval($group['groupid']),
				in_array($group['groupid'], $selected) ? ' selected ' : '',
				esc_html($group['name']) . "\t(". $group['count'] .")"
			);
		}
		return $html;
	}

	/**
	 * @param array|int $selected
	 * @param array|int $exclude
	 */
	public function show_selectbox($selected = array(), $exclude = array()){
		echo $this->get_selectbox($selected, $exclude);
	}
	
	function get_visible_usergroup_ids(){
		return (array) WPF()->db->get_col("SELECT `groupid` FROM `".WPF()->tables->usergroups."` WHERE `visible` = 1");
	}

    function get_secondary_usergroup_ids(){
        return (array) WPF()->db->get_col("SELECT `groupid` FROM `".WPF()->tables->usergroups."` WHERE `groupid` NOT IN(1,2,4) AND `secondary` = 1");
    }

    function get_secondary_usergroup_names( $ids ){
	    if( !is_array($ids) ) $ids = explode( ',', $ids );
        $ids = array_map('intval', $ids);
	    $ids = array_diff($ids, array(1,2,4));
	    if( $ids ){
		    $ids = implode(',', $ids);
		    return (array) WPF()->db->get_col("SELECT `name` FROM `".WPF()->tables->usergroups."` WHERE `secondary` = 1 AND `groupid` IN (" . esc_sql( $ids ) . ")");
	    }
	    return array();
    }

    function get_secondary_usergroups(){
        return (array) WPF()->db->get_results("SELECT * FROM `".WPF()->tables->usergroups."` WHERE `groupid` NOT IN(1,2,4) AND `secondary` = 1", ARRAY_A);
    }

	function get_usergroups_by_role( $role ){
        if( $role ){
            $ugids = WPF()->db->get_col("SELECT `groupid` FROM `" . WPF()->tables->usergroups . "` WHERE `role` = '" . esc_sql($role) . "' ORDER BY `groupid` ASC");
            if( !empty($ugids) ){
                return $ugids;
            }
        }
        return NULL;
    }

	function get_roles(){
        $roles = wp_roles();
        $roles = $roles->get_names();
        return $roles;
    }

    function get_roles_ug(){
        $roles_ug = WPF()->db->get_results("SELECT `name`, `role` FROM `" . WPF()->tables->usergroups . "`", ARRAY_A);
        $roles = wp_roles();
        $roles = $roles->get_names();
        if(!empty( $roles )){
            foreach($roles as $role => $name){
                foreach($roles_ug as $ug){
                    if( wpfval($ug, 'role') && $role == $ug['role'] ){
                        $roles_ug[$role][] = $ug['name'];
                    }
                }
            }
        }
        return $roles_ug;
    }

    function get_roles_woug(){
        $roles_woug = array();
        $roles_ug = WPF()->db->get_col("SELECT `role` FROM `" . WPF()->tables->usergroups . "` GROUP BY `role`");
        $roles = wp_roles();
        $roles = $roles->get_names();
        if(!empty( $roles )){
            foreach($roles as $role => $name){
                if( !in_array($role, $roles_ug) ){
                    $roles_woug[$role] = $name;
                }
            }
        }
        return $roles_woug;
    }

    function get_role_usergroup_relation(){
        $roles = array();
	    $data = WPF()->db->get_results("SELECT `groupid`, `role` FROM `" . WPF()->tables->usergroups . "` ORDER BY `groupid` DESC", ARRAY_A);
        if(!empty( $data )){
            foreach($data as $rel){
	            if( $rel['groupid'] == 1 && in_array($rel['role'], array('subscriber', 'contributor') ) ){
		            $roles['administrator'] = $rel['groupid'];
	            } elseif( $rel['groupid'] == 2 && $rel['role'] == 'subscriber' ) {
		            $roles['editor'] = $rel['groupid'];
	            } elseif( $rel['role'] ) {
		            $roles[ $rel['role'] ] = $rel['groupid'];
	            }
            }
        }
        return $roles;
    }

    function get_usergroup_role_relation(){
        $usergroups = array();
        $data = WPF()->db->get_results("SELECT `groupid`, `role` FROM `" . WPF()->tables->usergroups . "`", ARRAY_A);
        if(!empty( $data )){
            foreach($data as $rel){
                $usergroups[ $rel['groupid'] ] = $rel['role'];
            }
        }
        return $usergroups;
    }

	function get_usergroup_access_relation(){
		$usergroups = array();
		$data = WPF()->db->get_results("SELECT `groupid`, `access` FROM `" . WPF()->tables->usergroups . "`", ARRAY_A);
		if(!empty( $data )){
			foreach($data as $rel){
				$usergroups[ (int) $rel['groupid'] ] = $rel['access'];
			}
		}
		return $usergroups;
	}

    function set_ug_roles( $ug_role ){
        if( !empty($ug_role) ){
            foreach( $ug_role as $usergroupid => $role ){
                $role = sanitize_text_field($role);
                WPF()->db->query("UPDATE " . WPF()->tables->usergroups . " SET `role` = '" . esc_sql($role) . "' WHERE `groupid` = " . intval($usergroupid) );
            }
        }
    }

    function set_users_groupid( $groupid_userids ){
        $status = array('error' => 0, 'success' => false );
        if( !empty($groupid_userids) ){
            foreach( $groupid_userids as $group_id => $user_ids ){
                if( $group_id && !empty($user_ids) ){
                    $userids = implode(',', $user_ids);
                    $sql = "UPDATE " . WPF()->tables->profiles ." SET `groupid` = " . intval($group_id) . " WHERE `userid` IN(" . esc_sql($userids) . ")";
                    if( FALSE === WPF()->db->query($sql) ){
                        $status['error'] = WPF()->db->last_error;
                        $status['success'] = false;
                        break;
                    }
                    else{
                        $status['success'] = true;
                    }
                }
            }
        }
	    do_action('wpforo_set_users_groupid', $groupid_userids, $status);
        return $status;
    }

    function build_users_groupid_array( $usergroupid_role, $users ){
	    $array = array();
        $group_users = array();
        $user_prime_group = array();
        $user_second_groups = array();
        if( !empty($users) ){
            foreach( $users as $user ){
                if( !empty($user->roles) ){
                    foreach( $user->roles as $role ) {
                        $ugids = wpforo_key($usergroupid_role, $role, 'sort');
                        $ug_count = count($ugids);
                        if(!empty($ugids)){
                            foreach($ugids as $ugid){
                                if( $ug_count == 1 ){
                                    if( !isset($user_prime_group[$user->ID]) ) {
                                        $user_prime_group[$user->ID][] = $ugid;
                                        $group_users[$ugid][] = intval($user->ID);
                                    }
                                    else{
                                        $user_second_groups[$user->ID][] = $ugid;
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
        $array['group_users'] = $group_users;
        $array['user_prime_group'] = $user_prime_group;
        $array['user_second_groups'] = $user_second_groups;
        return $array;
    }

    public function after_add_edit_usergroup($group){
	    if( wpforo_feature('role-synch') ){
		    $limit = apply_filters('wpforo_synch_roles_users_limit', 5000);
		    $users = get_users( array('role' => $group['role'], 'number' => $limit) );
		    if( !empty($users) ){
			    if( count($users) <= $limit ){
				    $status = wpforo_synch_role( array( $group['groupid'] => $group['role']), $users );
				    wpforo_clean_cache('user');
				    if( $error = wpfval($status, 'error') ){
					    WPF()->notice->add($error, 'error');
				    }
			    }else{
				    WPF()->notice->add('Please make sure you don\'t have not-synched Roles in the "User Roles" table below, then click on the [Synchronize] button to update users Usergroup IDs.' , 'error');
			    }
		    }
	    }
    }
}