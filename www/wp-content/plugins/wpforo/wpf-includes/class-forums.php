<?php
	// Exit if accessed directly
	if( !defined( 'ABSPATH' ) ) exit;
 

class wpForoForum{
	public $default;
	public $options;
	public $cans;

	static $cache = array( 'forums' => array(), 'forum' => array(), 'item' => array() );
	
	public function __construct(){
		$this->init_defaults();
		$this->init_options();
		$this->init_hooks();
	}

    public function reset(){
        self::$cache = array( 'forums' => array(), 'forum' => array(), 'item' => array() );
    }

	private function init_defaults(){
        $this->default = new stdClass;

        $this->default->options = array(
	        'layout_extended_intro_topics_toggle' => 1,
	        'layout_extended_intro_topics_count' => 5,
	        'layout_extended_intro_topics_length' => 45,
	        'layout_qa_intro_topics_toggle' => 1,
	        'layout_qa_intro_topics_count' => 3,
	        'layout_qa_intro_topics_length' => 90,
	        'layout_threaded_intro_topics_toggle' => 0,
	        'layout_threaded_display_subforums' => 1,
	        'layout_threaded_intro_topics_count' => 10,
	        'layout_threaded_intro_topics_length' => 0,
	        'layout_threaded_filter_buttons' => 1,
	        'layout_threaded_add_topic_button' => 1,
            'display_current_viewers' => 1,
        );

        $this->default->cans = array (
            'vf' =>  __('Can view forum', 'wpforo'),
            'enf' => __('Can enter forum', 'wpforo'),
            'ct' =>  __('Can create topic', 'wpforo'),
            'vt' =>  __('Can view topic', 'wpforo'),
            'ent' => __('Can enter topic', 'wpforo'),
            'et' =>  __('Can edit topic', 'wpforo'),
            'dt' =>  __('Can delete topic', 'wpforo'),
            'cr' =>  __('Can post reply', 'wpforo'),
            'ocr' => __('Can reply to own topic', 'wpforo'),
            'vr' =>  __('Can view replies', 'wpforo'),
            'er' =>  __('Can edit replies', 'wpforo'),
            'dr' =>  __('Can delete replies', 'wpforo'),
            'eot' => __('Can edit own topic', 'wpforo'),
            'eor' => __('Can edit own reply', 'wpforo'),
            'dot' => __('Can delete own topic', 'wpforo'),
            'dor' => __('Can delete own reply', 'wpforo'),
            'tag' => __('Can add tags', 'wpforo'),
            'sb'  => __('Can subscribe', 'wpforo'),
            'l'   => __('Can like', 'wpforo'),
            'r'   => __('Can report', 'wpforo'),
            's'   => __('Can set topic sticky', 'wpforo'),
            'p'   => __('Can set topic private', 'wpforo'),
            'op'  => __('Can set own topic private', 'wpforo'),
            'vp'  => __('Can view private topic', 'wpforo'),
            'au'  => __('Can approve/unapprove content', 'wpforo'),
            'sv'  => __('Can set topic solved', 'wpforo'),
            'osv' => __('Can set own topic solved', 'wpforo'),
            'v'   => __('Can vote', 'wpforo'),
            'a'   => __('Can attach file', 'wpforo'),
            'va'  => __('Can view attached files', 'wpforo'),
            'at'  => __('Can set topic answered', 'wpforo'),
            'oat' => __('Can set own topic answered', 'wpforo'),
            'aot' => __('Can answer own question', 'wpforo'),
            'cot' => __('Can close topic', 'wpforo'),
            'mt'  => __('Can move topic', 'wpforo'),
			'ccp' => __('Can create poll', 'wpforo'),
			'cvp' => __('Can vote poll', 'wpforo'),
			'cvpr' => __('Can view poll result', 'wpforo'),
        );
    }

    private function init_options(){
        $this->options = get_wpf_option('wpforo_forum_options', $this->default->options);
        $this->cans = apply_filters('wpforo_forum_cans', $this->default->cans);
    }

    private function init_hooks(){
		add_action('wpforo_after_add_usergroup', array($this, 'after_add_usergroup'));
    }
	
	public function get_cache( $var ){
		if( isset(self::$cache[$var]) ) return self::$cache[$var];
	}

    public function manage( $groupid = NULL, $second_groupids = NULL ){
        if( WPF()->perm->usergroup_can( 'cf', $groupid, $second_groupids ) &&
            WPF()->perm->usergroup_can( 'ef', $groupid, $second_groupids ) &&
            WPF()->perm->usergroup_can( 'df', $groupid, $second_groupids ) ){
            return true;
        } else {
            return WPF()->perm->usergroup_can( 'mf', $groupid, $second_groupids );
        }
    }

 	private function unique_slug($slug, $parentid = 0, $forumid = 0){
		$new_slug = wpforo_text($slug, 250, false);
		$forumid = intval($forumid);
		$i = 2;
		while( !WPF()->can_use_this_slug($new_slug) || WPF()->db->get_var("SELECT `forumid` FROM ".WPF()->tables->forums." WHERE `slug` = '" . esc_sql($new_slug) . "'" . ($forumid ? ' AND `forumid` != '. intval($forumid) : '')) ){
			if( !isset($parent_slug) && $parentid = intval($parentid) ){
				$parent_slug = WPF()->db->get_var("SELECT `slug` FROM ".WPF()->tables->forums." WHERE `forumid` = " . intval($parentid) );
				$new_slug = $parent_slug . "-" . wpforo_text($slug, 250, false);
			}else{
				$new_slug = wpforo_text($slug, 250, false) . '-' . $i++;
			}
		}
		return $new_slug;
	}
 	
 	public function add( $args = array(), $checkperm = true ){
 		if( $checkperm && !$this->manage() ){
			WPF()->notice->add('Permission denied for add forum', 'error');
			return false;
		}
 		
		if( empty($args) && empty($_REQUEST['forum']) ) return false;
		if( empty($args) && !empty($_REQUEST['forum']) ) $args = $_REQUEST['forum'];
		
	    $args['title'] = sanitize_text_field( stripslashes( wpfval($args, 'title') ) );
	    if( !$args['title'] ){
			WPF()->notice->add('Please insert required fields!', 'error');
			return false;
		}

	    $args['title'] = wpforo_text($args['title'], 250, false);
	    $args['parentid'] = (int) wpfval($args, 'parentid');

	    $args['slug'] = ($ss = trim(sanitize_title(wpfval($args, 'slug')))) ? $ss : ( ($st = trim(sanitize_title($args['title']))) ? $st : md5(time()) );
	    $args['slug'] = $this->unique_slug($args['slug'], $args['parentid']);

        $args['description'] = wpforo_kses( stripslashes( wpfval($args, 'description') ) );

	    $group_access_relation = WPF()->usergroup->get_usergroup_access_relation();
	    $args['permission']    = ( (array) wpfval( $args, 'permission' ) ) + $group_access_relation;
	    $args['permission']    = serialize( array_map( 'sanitize_text_field', $args['permission'] ) );

	    $args['meta_key']  = sanitize_text_field( stripslashes( wpfval( $args, 'meta_key' ) ) );
	    $args['meta_desc'] = sanitize_text_field( stripslashes( wpfval( $args, 'meta_desc' ) ) );
	    $args['icon']      = sanitize_text_field( wpfval( $args, 'icon' ) );
	    $args['topics']    = (int) wpfval( $args, 'topics' );
	    $args['posts']     = (int) wpfval( $args, 'posts' );
	    $args['order']     = (int) wpfval( $args, 'order' );

	    $args['status'] = (int) wpfval( $args, 'status' );
	    if( !$args['status'] ) $args['status'] = 1;

	    $args['color'] = sanitize_text_field( stripslashes(wpfval( $args, 'color' )) );
	    if( !$args['color'] ) $args['color'] = '#666666';

	    $args['is_cat'] = (int) wpfval( $args, 'is_cat' );
		if( !$args['parentid'] ) $args['is_cat'] = 1;

	    $args['cat_layout'] = (int) wpfval( $args, 'cat_layout' );
	    if( $args['parentid'] ) {
			$layout = (int) WPF()->db->get_var("SELECT `cat_layout` FROM `".WPF()->tables->forums."` WHERE `forumid` = " . $args['parentid'] );
			if( $layout ) $args['cat_layout'] = $layout;
		}
	    if( !$args['cat_layout'] ) $args['cat_layout'] = 1;

	    $args = apply_filters('wpforo_before_add_forum', $args, $checkperm);
	    if( !$args ) return false;

		if( WPF()->db->insert(
				WPF()->tables->forums,
                array(
                    'title'       => $args['title'],
                    'slug'        => $args['slug'],
                    'description' => $args['description'],
                    'parentid'    => $args['parentid'],
                    'icon'        => $args['icon'],
                    'topics'      => $args['topics'],
                    'posts'       => $args['posts'],
                    'permissions' => $args['permission'],
                    'meta_key'    => $args['meta_key'],
                    'meta_desc'   => $args['meta_desc'],
                    'status'      => $args['status'],
                    'is_cat'      => $args['is_cat'],
                    'cat_layout'  => $args['cat_layout'],
                    'order'       => $args['order'],
                    'color'       => $args['color']
                ),
                array('%s','%s','%s','%d','%s','%d','%d','%s','%s','%s','%d','%d','%d','%d','%s')
        ) ){
			$args['forumid'] = WPF()->db->insert_id;

			do_action('wpforo_after_add_forum', $args, $checkperm);

			$this->delete_tree_cache();
			wpforo_clean_cache();
			WPF()->notice->add('Your forum successfully added', 'success');
			return $args['forumid'];
		}
		
		WPF()->notice->add('Can\'t add forum', 'error');
		return false;
	}
 
 	public function edit( $args = array(), $checkperm = true ){
 		if( $checkperm && !$this->manage() ){
			WPF()->notice->add('Permission denied for edit forum', 'error');
			return false;
		}
 		
		if( empty($args) && empty($_REQUEST['forum']) ) return false;
		if( empty($args) && !empty($_REQUEST['forum']) ) $args = $_REQUEST['forum'];
		if( !isset($args['forumid']) && isset($_GET['id']) ) $args['forumid'] = $_GET['id'];
		
		$args['forumid'] = (int) wpfval($args, 'forumid');
        if ( !$forum = $this->get_forum($args['forumid']) ){
            WPF()->notice->add('Forum update error', 'error');
            return false;
        }

	    $args['title'] = sanitize_text_field( stripslashes( wpfval($args, 'title') ) );
	    if( !$args['title'] ){
		    WPF()->notice->add('Please insert required fields!', 'error');
		    return false;
	    }

	    $args['title'] = wpforo_text($args['title'], 250, false);
	    $args['parentid'] = (int) wpfval($args, 'parentid');
	    if( $args['forumid'] === $args['parentid'] ) $args['parentid'] = (int) $forum['parentid'];

	    $args['slug'] = ($ss = trim(sanitize_title(wpfval($args, 'slug')))) ? $ss : ( ($st = trim(sanitize_title($args['title']))) ? $st : md5(time()) );
	    $args['slug'] = $this->unique_slug($args['slug'], $args['parentid'], $args['forumid']);

	    $args['description'] = wpforo_kses( stripslashes( wpfval($args, 'description') ) );

	    $group_access_relation = WPF()->usergroup->get_usergroup_access_relation();
	    $args['permission']    = ( (array) wpfval( $args, 'permission' ) ) + $group_access_relation;
	    $args['permission']    = serialize( array_map( 'sanitize_text_field', $args['permission'] ) );

	    $args['meta_key']  = sanitize_text_field( stripslashes( wpfval( $args, 'meta_key' ) ) );
	    $args['meta_desc'] = sanitize_text_field( stripslashes( wpfval( $args, 'meta_desc' ) ) );
	    $args['icon']      = sanitize_text_field( wpfval( $args, 'icon' ) );
	    $args['topics']    = (int) wpfval( $args, 'topics' );
	    $args['posts']     = (int) wpfval( $args, 'posts' );
	    $args['order']     = (int) wpfval( $args, 'order' );

	    $args['status'] = (int) wpfval( $args, 'status' );
	    if( !$args['status'] ) $args['status'] = 1;

	    $args['color'] = sanitize_text_field( stripslashes(wpfval( $args, 'color' )) );
	    if( !$args['color'] ) $args['color'] = '#666666';

	    $args['is_cat'] = (int) wpfval( $args, 'is_cat' );
	    if( !$args['parentid'] ) $args['is_cat'] = 1;

	    $args['cat_layout'] = (int) wpfval( $args, 'cat_layout' );
	    if( $args['parentid'] ) {
		    $layout = (int) WPF()->db->get_var("SELECT `cat_layout` FROM `".WPF()->tables->forums."` WHERE `forumid` = " . $args['parentid'] );
		    if( $layout ) $args['cat_layout'] = $layout;
	    }
	    if( !$args['cat_layout'] ) $args['cat_layout'] = 1;

	    $args = apply_filters('wpforo_before_edit_forum', $args, $forum, $checkperm);
	    if( !$args ) return false;

		if( false !== WPF()->db->update(
				WPF()->tables->forums,
				array(
					'title'       => $args['title'],
					'slug'        => $args['slug'],
					'description' => $args['description'],
					'parentid'    => $args['parentid'],
					'icon'        => $args['icon'],
					'permissions' => $args['permission'],
					'meta_key'    => $args['meta_key'],
					'meta_desc'   => $args['meta_desc'],
					'status'      => $args['status'],
					'is_cat'      => $args['is_cat'],
					'cat_layout'  => $args['cat_layout'],
					'color'       => $args['color']
				),
				array('forumid' => $args['forumid']),
				array('%s','%s','%s','%d','%s','%s','%s','%s','%d','%d','%d','%s'),
				array('%d')
			)
		){
			$childs = array();
			$this->get_childs($args['forumid'], $childs);
			$sql = "UPDATE `".WPF()->tables->forums."` SET `cat_layout` = ". $args['cat_layout'] ." WHERE `forumid` IN(". implode(',', array_map('intval', $childs)).")";
			WPF()->db->query($sql);

			do_action('wpforo_after_edit_forum', $args, $forum, $checkperm);

			$this->delete_tree_cache();
			wpforo_clean_cache();
			WPF()->notice->add('Forum successfully updated', 'success');
			return $args['forumid'];
		}
		
		WPF()->notice->add('Forum update error', 'error');
		return false;
	}
	
	public function delete($forumid = 0, $checkperm = true){
		$forumid = intval($forumid);
		if(!$forumid && isset( $_REQUEST['id'] ) ) $forumid = intval($_REQUEST['id']);

		if( $checkperm && !$this->manage() ){
			WPF()->notice->add('Permission denied for delete forum', 'error');
			return false;
		}

		$forumids = array();
		$this->get_childs($forumid, $forumids);
		foreach( $forumids as $forumid ) $this->__delete($forumid);

		$this->delete_tree_cache();
		wpforo_clean_cache();
		WPF()->notice->add('Your forum successfully deleted', 'success');
		return true;
	}

	private function __delete( $forumid ){
		$forumid = intval($forumid);
		// START delete topic posts include first post
		if( $topicids = WPF()->db->get_col( "SELECT `topicid` FROM " . WPF()->tables->topics . " WHERE `forumid` = " . $forumid ) ){
			foreach($topicids as $topicid){
				WPF()->topic->delete($topicid, false);
			}
		}
		// END delete topic posts include first post

		WPF()->db->delete(
			WPF()->tables->forums,
            array( 'forumid' => $forumid ),
            array('%d')
        );

		do_action('wpforo_after_delete_forum', $forumid);
	}

	public function merge($forumid = 0, $mergeid = 0){
		$forumid = intval($forumid);
		$mergeid = intval($mergeid);
		
		if(!$forumid && isset( $_REQUEST['id'] ) ) $forumid = intval($_REQUEST['id']);
		if(!$mergeid && isset( $_REQUEST['forum']['mergeid'] ) ) $mergeid = intval($_REQUEST['forum']['mergeid']);

		if( !$forumid || !$mergeid ) return false;

		if( $child_forumids = $this->get_child_forums( $forumid ) ){
			$forumids = trim( implode(',', array_map('intval', $child_forumids)) );
			if( $forumids ){
                $merge_layout = $this->get_layout($mergeid);

				if(!WPF()->db->query( "UPDATE ".WPF()->tables->forums." SET `parentid` = " . intval($mergeid) . ", `cat_layout` = " . intval($merge_layout) . " WHERE `forumid` IN(". esc_sql($forumids) .")" )){
					WPF()->notice->add('Forum merging error', 'error');
					return FALSE;
				}
			}
		}
		
		WPF()->db->update(
			WPF()->tables->topics,
			array( 'forumid' => $mergeid ),
			array( 'forumid' => $forumid ),
			array( '%d' ),
			array( '%d' )
		);
		WPF()->db->update(
			WPF()->tables->posts,
			array( 'forumid' => $mergeid ),
			array( 'forumid' => $forumid ),
			array( '%d' ),
			array( '%d' )
		);

		$this->rebuild_last_infos($mergeid);
		$this->rebuild_stats($mergeid);
		
		if(WPF()->db->delete( WPF()->tables->forums, array( 'forumid' => $forumid ), array( '%d' ) )){
			$this->delete_tree_cache();
			wpforo_clean_cache('forum');
			WPF()->notice->add('Forum is successfully merged', 'success');
			return TRUE;
		}

		WPF()->notice->add('Forum merging error', 'error');
		return FALSE;
	}
	
	public function rebuild_last_infos($forumid){
        if( !$forumid = intval($forumid) ) return false;
		
		$last_topicid = 0;
		$last_postid = 0;
		$last_userid = 0;
		$last_post_date = '0000-00-00 00:00:00';

		if ( $last_topics = WPF()->topic->get_topics( array(
			'forumid'   => $forumid,
			'status'    => 0,
			'private'   => 0,
			'orderby'   => 'topicid',
			'order'     => 'DESC',
			'row_count' => 1
		) ) ) {
			$last_topic   = $last_topics[0];
			$last_topicid = $last_topic['topicid'];
		}

        $sql = "SELECT `postid` FROM `".WPF()->tables->posts."` WHERE `status` = 0 AND `private` = 0 AND `forumid` = %d ORDER BY `created` DESC, `postid` DESC LIMIT 1";
        if( $last_postid = WPF()->db->get_var( WPF()->db->prepare($sql, $forumid) ) ){
            if( $last_post_data = WPF()->post->get_post($last_postid) ){
                $last_postid = $last_post_data['postid'];
                $last_userid = $last_post_data['userid'];
                $last_post_date = $last_post_data['created'];
            }
        }else{
            $last_postid = 0;
        }

        $parent_ids = array();
        $this->get_parents($forumid, $parent_ids);
        $parent_ids = array_unique(array_filter(array_map('wpforo_bigintval', (array) $parent_ids)));

        if( $parent_ids ){
            $sql = "UPDATE `". WPF()->tables->forums ."` 
                SET `last_topicid` = %d,
                `last_postid` = %d,
                `last_userid` = %d,
                `last_post_date` = %s
                WHERE `forumid` IN(". implode(',', $parent_ids) .")";
            WPF()->db->query( WPF()->db->prepare($sql, $last_topicid, $last_postid, $last_userid, $last_post_date) );
            wpforo_clean_cache('forum');
        }
	}

	public function rebuild_stats($forumid){
		if( !$forumid = intval($forumid) ) return false;
		$topics = WPF()->topic->get_count( array('forumid' => $forumid, 'status' => 0, 'private' => 0) );
		$posts = WPF()->post->get_count( array('forumid' => $forumid, 'status' => 0, 'private' => 0) );

		if( false !== WPF()->db->update(
			WPF()->tables->forums,
			array('topics' => $topics, 'posts' => $posts ),
			array('forumid' => $forumid),
			array('%d', '%d'),
			array('%d')
		) ) {
			wpforo_clean_cache('forum');
			return true;
		}
		return false;
	}

	function get_forum( $args ){
		$forum = array();
		if( !$args ) return $forum;

		$default = array(
			'forumid' => NULL,
			'slug' => '',
			'status' => NULL,
			'type' => 'all'
		);
		if( !is_array($args) ){
		    if( is_numeric($args) ){
			    $default['forumid'] = intval($args);
		    }elseif ( is_string($args) ){
			    $default['slug'] = $args;
		    }
        }
		$args = wpforo_parse_args( $args, $default );

		$cache = WPF()->cache->on('memory_cashe');
		if( $cache ){
            if( $args['forumid'] && $forum = wpfval(self::$cache, 'forum', $args['forumid']) ) return $forum;
            if( $args['slug']    && $forum = wpfval(self::$cache, 'forum', addslashes($args['slug'])) ) return $forum;
		}

        $wheres = array();
        if( $args['forumid'] )          $wheres[] = "`forumid` = " . intval($args['forumid']);
		if( $args['slug'] )             $wheres[] = "`slug` = '" . esc_sql($args['slug']) . "'";
        if( !is_null($args['status']) ) $wheres[] = "`status` = " . intval($args['status']);
        switch ($args['type']){
            case 'category':
	            $wheres[] = "`is_cat` = 1";
                break;
            case 'forum':
	            $wheres[] = "`is_cat` = 0";
	            break;
        }

		if($wheres){
			$sql = "SELECT * FROM `".WPF()->tables->forums."` WHERE " . implode( " AND ", $wheres );
			if( $forum = WPF()->db->get_row($sql, ARRAY_A) ){
				if( !$forum['cat_layout'] ) $forum['cat_layout'] = 1;
				$forum['url'] = $this->get_forum_url( $forum );
			}
        }
        $forum = apply_filters('wpforo_get_forum', $forum, $args);

        if($cache && $forum){
            self::$cache['forum'][addslashes($forum['slug'])] = $forum;
            self::$cache['forum'][$forum['forumid']] = $forum;
        }

		return $forum;
	}
	
	function get_forums($args = array(), &$items_count = 0, $count = false ){
		
		$cache = WPF()->cache->on('object_cashe');
		
		$default = array( 
		  'include' => array(), // array( 2, 10, 25 )
		  'exclude' => array(),  // array( 2, 10, 25 )
		  'parent_include' => array(), // array( 2, 10, 25 )
		  'parent_exclude' => array(),  // array( 2, 10, 25 )
		  'parentid' => NULL,
		  'parent_slug' => '',
		  'status' => NULL,
		  'type' => 'all', // category, forum
		  'orderby' => 'order', // order by `field`
		  'order' => 'ASC', // ASC DESC
		  'offset' => NULL, // OFFSET
		  'row_count' => NULL, // ROW COUNT
          'layout' => NULL, // 1, 2, 3, 4
		);
		
		$args = wpforo_parse_args( $args, $default );
		if(is_array($args) && !empty($args)){
			
			extract($args, EXTR_OVERWRITE);
			
			$include = wpforo_parse_args( $include );
			$exclude = wpforo_parse_args( $exclude );
			$parent_include = wpforo_parse_args( $parent_include );
			$parent_exclude = wpforo_parse_args( $parent_exclude );
			
			$sql = "SELECT * FROM `".WPF()->tables->forums."`";
			$wheres = array();
			
			if(!empty($include))        $wheres[] = "`forumid` IN(" . implode(', ', array_map('intval', $include)) . ")";
			if(!empty($exclude))        $wheres[] = "`forumid` NOT IN(" . implode(', ', array_map('intval', $exclude)) . ")";
			if(!empty($parent_include)) $wheres[] = "`parentid` IN(" . implode(', ', array_map('intval', $parent_include)) . ")";
			if(!empty($parent_exclude)) $wheres[] = "`parentid` NOT IN(" . implode(', ', array_map('intval', $parent_exclude)) . ")";
			if($parentid != NULL) $wheres[] = " `parentid` = " . intval($parentid);
            if($layout != NULL)   $wheres[] = " `cat_layout` = "   . intval($layout);
			if($status != NULL)   $wheres[] = " `status` = "   . intval($status);
			
			if($type == 'category'){
				$wheres[] = " `is_cat` = 1";
			}elseif($type == 'forum'){
				$wheres[] = " `is_cat` = 0";
			}
			
			if($parent_slug != '') $wheres[] = "`slug` = '" . esc_sql($parent_slug) . "'";
			
			if(!empty($wheres)) $sql .= " WHERE " . implode( " AND ", $wheres );

			if( $count ){
				$item_count_sql = preg_replace('#SELECT.+?FROM#isu', 'SELECT count(*) FROM', $sql);
//				$item_count_sql = preg_replace('#ORDER.+$#is', '', $item_count_sql);
				if( $item_count_sql ) $items_count = WPF()->db->get_var($item_count_sql);
			}

			$sql .= esc_sql(" ORDER BY `$orderby` " . $order);
			
			if($row_count != NULL){
				if($offset != NULL){
					$sql .= esc_sql(" LIMIT $offset,$row_count");
				}else{
					$sql .= esc_sql(" LIMIT $row_count");
				}
			}
			
			if( $cache ){ $object_key = md5( $sql . WPF()->current_user_groupid ); $object_cache = WPF()->cache->get( $object_key ); if(!empty($object_cache)){$items_count = $object_cache['items_count']; return $object_cache['items'];}}
			
			$forums = WPF()->db->get_results($sql, ARRAY_A);
			$forums = apply_filters('wpforo_get_topics', $forums);
			
			if($cache && isset($object_key) && !empty($forums)){ 
				self::$cache['forums'][$object_key]['items'] = $forums; 
				self::$cache['forums'][$object_key]['items_count'] = $items_count;
			}
			return $forums; 
		}
	}
	
	function search($needle, $fields = array()){
	
		if($needle){
			$needle = sanitize_text_field($needle);
			if(empty($fields)){
				$fields = array( 
				  'title',
				  'description',
				  'meta_key',
				  'meta_desc'
				);
			}
			
			$sql = "SELECT `forumid` FROM `".WPF()->tables->forums."`";
			$wheres = array();
			
			foreach($fields as $field){
				$wheres[] = "`" . esc_sql($field) . "` LIKE '%" . esc_sql($needle) . "%'";
			}
			
			$sql .= " WHERE " . implode(" OR ", $wheres);
			return WPF()->db->get_col($sql);
		} 
		
		return array();
	}
	
	function update_hierarchy(){
		if(is_array($_REQUEST['forum']) && !empty($_REQUEST['forum'])){
			$i = 0;
			foreach($_REQUEST['forum'] as $hierarchy){
				
				extract($hierarchy);
				
				if(!isset($forumid) || !$forumid = intval($forumid) ) continue;
				
				if(FALSE !== WPF()->db->update(
					WPF()->tables->forums,
					array( 
						'parentid' => (isset($parentid) ? intval($parentid) : 0), 
						'order' => (isset($order) ? intval($order) : 0), 
					),
					array( 'forumid' => intval($forumid) ),
					array( 
						'%d',
						'%d'
					),
					array( '%d' )
				)) $i++;
				
				if(isset($parentid) && $parentid = intval($parentid) ){
					$cat_layout = WPF()->db->get_var("SELECT `cat_layout` FROM `".WPF()->tables->forums."` WHERE `forumid` = " . intval($parentid));
					WPF()->db->query("UPDATE `".WPF()->tables->forums."` SET `cat_layout` = " . intval($cat_layout) . " WHERE `forumid` = " . intval($forumid));
				}
				
			}
			
			WPF()->db->query("UPDATE `".WPF()->tables->forums."` SET `is_cat` = 0");
			WPF()->db->query("UPDATE `".WPF()->tables->forums."` SET `is_cat` = 1 WHERE `parentid` = 0");
			
			if($i){
                $this->delete_tree_cache();
				WPF()->notice->add('Forum hierarchy successfully updated', 'success');
			}else{
				WPF()->notice->add('Cannot update forum hierarchy', 'error');
			}
			
		}
	}

	/**
	 * @param int|array $forumids
	 * @param array $data
	 * @param bool $merge_forumids_with_data
	 */
	public function get_childs( $forumids, &$data, $merge_forumids_with_data = true ){
	    if( $forumids = array_map('intval', (array) $forumids) ){
	        $sql = "SELECT @forumids := GROUP_CONCAT(
                        @id :=  ( 
                        SELECT GROUP_CONCAT(`forumid` ORDER BY `order` ASC) 
                            FROM  `".WPF()->tables->forums."` 
                            WHERE FIND_IN_SET( `parentid`, @id )
                        )
                    ) AS forumids
                    FROM ( SELECT  @id := %s ) vars 
                    STRAIGHT_JOIN `".WPF()->tables->forums."` 
                    WHERE @id IS NOT NULL";
	        $sql = WPF()->db->prepare($sql, implode(',', $forumids));

		    if( WPF()->ram_cache->is_exist($sql) ){
			    $grouped_forumids = WPF()->ram_cache->get($sql);
		    }else{
			    $grouped_forumids = WPF()->db->get_var($sql);
			    WPF()->ram_cache->set($sql, $grouped_forumids);
		    }

		    if($grouped_forumids){
			    $data = explode(',', $grouped_forumids);
            }
		    if($merge_forumids_with_data) $data = array_merge($forumids, $data);
	    }
    }

	// get forums tree for drop down menu
	
	/**
	 * Returns depth for this item.
	 *
	 * @since 1.0.0
	 *
	 * @param	int		item id 
	 *
	 * @param	int		before calling the function $depth = 0
	 */
	function count_depth($forumid, &$depth){
	    $sql = "SELECT `parentid` FROM `".WPF()->tables->forums."` WHERE `forumid` = %d AND `parentid` <> %d";
	    $sql = WPF()->db->prepare($sql, intval($forumid), intval($forumid));

	    if( WPF()->ram_cache->is_exist($sql) ){
	        $parentid = WPF()->ram_cache->get($sql);
        }else{
		    $parentid = WPF()->db->get_var($sql);
		    WPF()->ram_cache->set($sql, $parentid);
	    }

		if($parentid){
			$depth++;
			$this->count_depth($parentid, $depth);
		}
	}

	/**
	 * @param int $parent
	 *
	 * @return array
	 */
	function get_child_forums($parent){
	    $sql = "SELECT `forumid` 
                  FROM `".WPF()->tables->forums."` 
                  WHERE `parentid` = ".intval($parent)." 
                  AND `forumid` <> ".intval($parent)." 
                  ORDER BY `order`";
		return (array) WPF()->db->get_col($sql);
	}

	function forum_list( $forumids, $type = 'select_box', $selected = array(), $cats = TRUE, $disabled = array() ){
		static $old_depth;
		$disabled = (array) $disabled;
        $selected = (array) $selected;

		foreach ( $forumids as $forumid ) {
			if( !$forumid || ( !wpforo_is_admin() && !WPF()->perm->forum_can('vf', $forumid) ) || ( wpforo_is_admin() && !WPF()->forum->manage() ) ) continue;

			$depth = 0;
			$this->count_depth($forumid, $depth);
			$name = WPF()->db->get_var("SELECT `title` FROM `".WPF()->tables->forums."` WHERE `forumid` = ".intval($forumid));
			if($type == 'select_box'){ ?>
                <option value="<?php echo intval($forumid) ?>" <?php echo( (!$cats && $depth == 0 || (!empty($disabled) && in_array($forumid, $disabled)) ) ? ' disabled ': ''); echo ( in_array($forumid, $selected) ? ' selected ' : '' ) ?> > <?php echo esc_html(str_repeat( '— ', $depth ) . trim($name)) ?></option><?php
			}elseif($type == 'drag_menu'){ 
				$cur_forum = WPF()->db->get_row("SELECT `cat_layout`, `topics`, `posts` FROM `".WPF()->tables->forums."` WHERE `forumid` = ".intval($forumid), ARRAY_A);
                switch( $cur_forum['cat_layout'] ){
                    case 2:
                        $cat_layout_name = 'Simplified Layout';
                        break;
                    case 3:
                        $cat_layout_name = 'Q&A Layout';
                        break;
                    case 4:
                        $cat_layout_name = 'Threaded Layout';
                        break;
                    default:
                        $cat_layout_name = 'Extended Layout';
                }
				?>
				
				<li id="menu-item-<?php echo intval($forumid) ?>" class="menu-item menu-item-depth-<?php echo esc_attr($depth) ?>">
					<input id="forumid-<?php echo intval($forumid) ?>" type="hidden" name="forum[<?php echo intval($forumid) ?>][forumid]"/>
					<input id="parentid-<?php echo intval($forumid) ?>" type="hidden" name="forum[<?php echo intval($forumid) ?>][parentid]"/>
					<input id="order-<?php echo intval($forumid) ?>" type="hidden" name="forum[<?php echo intval($forumid) ?>][order]"/>
					<dl class="menu-item-bar">
						<dt class="menu-item-handle forum_width">
							<span class="item-title forumtitle"><span style="font-weight:400; cursor:help;" title="Forum ID"><?php echo $forumid; ?> &nbsp;|&nbsp;</span> <?php echo esc_html($name) ?></span>
							<span class="item-controls">
                            	<span class="wpforo-cat-layout"><?php echo ( $depth != 0 ? __('Topics', 'wpforo') . '&nbsp;(' . intval($cur_forum['topics']) . ')&nbsp;,&nbsp;' . __('Posts', 'wpforo') . '&nbsp;(' . intval($cur_forum['posts']) . ')&nbsp; | &nbsp;' : '' ) ?><?php echo ( $depth == 0 ? '(&nbsp;<i>' . esc_html($cat_layout_name) . '</i>&nbsp;)&nbsp; | &nbsp;' : '' ); ?></span>
								<span class="menu_add">
                                    <a href="<?php echo admin_url( 'admin.php?page=wpforo-forums&action=add&parentid=' . intval($forumid) ) ?>" >
                                        <span class="dashicons dashicons-plus" title="<?php if( $depth ) : _e('Add a new Subforum', 'wpforo'); else: _e('Add a new Forum in this Category', 'wpforo'); endif; ?>"></span>
                                    </a>
                                </span> &nbsp;|&nbsp;
                                <span class="menu_edit">
                                    <a href="<?php echo admin_url( 'admin.php?page=wpforo-forums&id=' . intval($forumid) . '&action=edit' ) ?>">
                                        <span class="dashicons dashicons-edit" title="<?php _e('edit', 'wpforo') ?>"></span>
                                    </a>
                                </span>&nbsp;|&nbsp;
                                <?php if( WPF()->forum->manage() ): ?>
                                    <span class="menu_delete">
                                        <a href="<?php echo admin_url( 'admin.php?page=wpforo-forums&id=' . intval($forumid) . '&action=del' ) ?>">
                                            <span class="dashicons dashicons-trash" title="<?php _e('delete', 'wpforo') ?>"></span>
                                        </a>
                                    </span>&nbsp;|&nbsp;
                                <?php endif; ?>
								<span class="menu_view">
                                    <a href="<?php echo esc_url(wpforo_forum($forumid, 'url')); ?>" >
                                        <span class="dashicons dashicons-visibility" title="<?php _e('View', 'wpforo') ?>"></span>
                                    </a>
                                </span>
                            
                            </span>	
						</dt>
					</dl>
					<ul class="menu-item-transport"></ul>
				</li>
				
			 <?php
			}elseif($type == 'front_list'){
				if(isset($old_depth) && $old_depth == $depth) echo '</dd><dd>';
				if(isset($old_depth) && $old_depth < $depth) echo '<dl><dd>';
				if(isset($old_depth) && $old_depth > $depth) echo '</dd></dl>';
				$old_depth = $depth;
				if(isset(WPF()->current_object) && isset(WPF()->current_object['forumid'])){
					if( $forumid == WPF()->current_object['forumid'] ){
						echo'<span class="wpf-dl-item wpf-dl-current"><i class="far fa-comments"></i><strong>'.esc_html($name).'</strong><a href="' . esc_url( wpforo_forum($forumid, 'url') ) . '" >&nbsp;&raquo;</a></span>';
					}
					else{
						echo'<span class="wpf-dl-item"><a href="'.esc_url( wpforo_forum($forumid, 'url') ).'" ><i class="far fa-comments"></i>'.esc_html($name).'</a></span>';
					}
				}
				else{
					echo'<span class="wpf-dl-item"><a href="'.esc_url( wpforo_forum($forumid, 'url') ).'" ><i class="far fa-comments"></i>'.esc_html($name).'</a></span>';
				}
			}elseif($type == 'subscribe_manager_form'){
			    ?>
                <li>
                    <?php if($depth > 0) :
                        $forum_topic_attr = '';
                        $forum_attr = '';
                       if ( key_exists($forumid, $selected) ){
                           if( $selected[$forumid] == 'forum-topic' ){
                               $forum_topic_attr = ' checked ';
                           }elseif ( $selected[$forumid] == 'forum' ){
                               $forum_attr = ' checked ';
                           }
                       }
                    ?>
                    <div class="wpf-sbs-div wpf-sbs-checkbox">
                        <input id="wpf_sbs_allposts_<?php echo $forumid ?>" type="checkbox" name="wpforo[forums][<?php echo $forumid ?>]" value="forum-topic" <?php echo $forum_topic_attr ?>><label class="wpf-sbsp" for="wpf_sbs_allposts_<?php echo $forumid ?>"><?php wpforo_phrase('topics and posts') ?></label>
                        <input id="wpf_sbs_alltopics_<?php echo $forumid ?>" type="checkbox" name="wpforo[forums][<?php echo $forumid ?>]" value="forum" <?php echo $forum_attr ?>><label class="wpf-sbst" for="wpf_sbs_alltopics_<?php echo $forumid ?>"><?php wpforo_phrase('topics') ?></label>
                    </div>
                    <?php endif; ?>
                    <div class="wpf-sbs-div wpf-sbs-form-title<?php echo ($depth > 0) ? ' wpf-sbs-forum' : ' wpf-sbs-cat'; ?>"><?php echo esc_html(str_repeat( '— ', $depth )) . trim($name) ?></div>
                </li>
                <?php
            }
			$subforums = $this->get_child_forums($forumid);
			if( !empty($subforums) ){
				$this->forum_list($subforums, $type, $selected, true, $disabled);
			}
		}
	}

    function tree($type = 'select_box', $cats = TRUE, $selected = array(), $cache = true, $disabled = array(), $parentids = array())
    {
		$disabled = (array)$disabled;
        $selected = (array)$selected;
        $parentids = (array)$parentids;
        if( !$parentids ) $parentids = WPF()->db->get_col("SELECT `forumid` FROM `".WPF()->tables->forums."` WHERE `parentid` = 0 ORDER BY `order`");
        if (!empty($parentids)) {
            if ($cache && !wpforo_is_admin()) {
                $key = md5(serialize($parentids) . $type . (int)$cats . WPF()->current_user_groupid);
                $html = get_option('wpforo_forum_tree_' . $key);
                $pattern_strip_selected = '#(<(?:option|input)[^<>]*?)[\r\n\t\s]*(?:selected|checked)[^\r\n\t\s]*?((?:[\r\n\t\s][^<>]*)?>)#isu';

                if ($html) {
                    if( $type == 'select_box' || $type == 'subscribe_manager_form' ) $html = preg_replace($pattern_strip_selected, '$1$2', $html);
                    if($selected){
                        if($type == 'select_box'){
                            foreach ($selected as $sfid){
                                $html = str_replace('value="'.$sfid.'"', 'value="'.$sfid.'" selected ', $html);
                            }
                        }elseif ($type == 'subscribe_manager_form'){
                            foreach ($selected as $forumid => $stype){
                                $html = preg_replace('#(name=[\'"]wpforo\[forums\]\['.intval($forumid).'\][\'"][^<>]*?value=[\'"]'.preg_quote($stype).'[\'"]|value=[\'"]'.preg_quote($stype).'[\'"][^<>]*?name=[\'"]wpforo\[forums\]\['.intval($forumid).'\][\'"])#isu', '$1 checked', $html);
                            }
                        }
                    }
                    echo $html;
                } elseif (function_exists('ob_start')) {
                    ob_start();
                    $this->forum_list($parentids, $type, $selected, $cats, $disabled);
                    $html = ob_get_clean();
                    $cache_html = ( $type == 'select_box' ? preg_replace($pattern_strip_selected, '$1$2', $html) : $html );
                    if ($type != 'drag_menu') update_option('wpforo_forum_tree_' . $key, $cache_html);
                    echo $html;
                }
            } else {
                $this->forum_list($parentids, $type, $selected, $cats, $disabled);
            }
        }
    }
	
	public function delete_tree_cache() {
		WPF()->db->query("DELETE FROM " . WPF()->db->options . " WHERE `option_name` LIKE 'wpforo_forum_tree_%'");
	}
	
	function parentid( $topicid = 0 ){
		if(isset($_GET['page']) && $_GET['page'] == 'wpforo-forums'){
			if( isset($_GET['id'])) return WPF()->db->get_var("SELECT `parentid` FROM `".WPF()->tables->forums."` WHERE `forumid` = ".intval($_GET['id']));
		}
		elseif( isset($_GET['page']) && $_GET['page'] == 'wpforo-topics' ){
			if( isset($_GET['id'])) return WPF()->db->get_var( "SELECT `forumid` FROM `".WPF()->tables->topics."` WHERE `topicid` = ".wpforo_bigintval($_GET['id']));
		}else{
			if( $topicid ) return WPF()->db->get_var( "SELECT `forumid` FROM `".WPF()->tables->topics."` WHERE `topicid` = ".wpforo_bigintval($topicid));
		}
	}
	
	function permissions(){
		$access_arr = WPF()->perm->get_accesses();
		if(!empty( $access_arr )){
			
			if(isset($_GET['id'])){
				if($permissions_srlz = WPF()->db->get_var("SELECT `permissions` FROM `".WPF()->tables->forums."` WHERE `forumid` = ".intval($_GET['id']))){
					$permissions_arr = unserialize($permissions_srlz);
				}
			}
			
			if($usergroups = WPF()->db->get_results("SELECT `groupid`, `name` FROM `".WPF()->tables->usergroups."`", ARRAY_A)){
				foreach($usergroups as $usergroup){
					extract($usergroup, EXTR_OVERWRITE);
					echo '
						<tr>
							<td>'.esc_html($name).'</td>
							<td>
								<select name="forum[permission]['.intval($groupid).']">';
									foreach($access_arr as $value){
										
										echo '<option value="'.esc_attr($value['access']).'" '.
												((isset($permissions_arr[$groupid]) && $value['access'] == $permissions_arr[$groupid]) 
													|| (!isset($permissions_arr[$groupid]) 
															&& (($name == 'Guest' && $value['access'] == 'read_only') 
																	|| ($name == 'Registered' && $value['access'] == 'standard') 
																		|| ($name == 'Customer' && $value['access'] == 'standard') 
																			|| ($name == 'Moderator' && $value['access'] == 'moderator') 
																				|| ($name == 'Admin' && $value['access'] == 'full')
																					|| ($name != 'Guest' && $name != 'Registered' && $name != 'Customer' && $name != 'Moderator' && $name != 'Admin' && $value['access'] == 'standard') )) ? ' selected ' : '').'>'.esc_html( __( $value['title'], 'wpforo') ).'</option>';
									}
									echo'
								</select>
							</td>
						</tr>
					';
				}
				
				
				
			}
		}
	}
	
	/**
	 * array get_counts(array or id(num)) 
	 * 
	 * @since 1.0.0
	 *
	 * @param	array defined arguments array for returning
	 *
	 * @return int count topics
	 */
	function get_counts($forumids){
		
		if( !empty($forumids) ){
			
			$wheres = '';
			
			if(!is_array($forumids)){
				$wheres = "`forumid` = " . intval($forumids);
			}else{
				$wheres = "`forumid` IN(" . implode(', ', array_map('intval', $forumids)) . ")";
			}
			
			$sql = "SELECT SUM(`topics`) as topics, SUM(`posts`) as posts FROM `".WPF()->tables->forums."` WHERE " . $wheres;
			return WPF()->db->get_row($sql, ARRAY_A);
			
		}
		
		return 0;
	}
	
	/**
	 * array get_layout(array) 
	 * 
	 * @since 1.0.0
	 *
	 * @param	array defined arguments array for returning
	 *
	 * @return int layout id
	 */
	function get_layout($args = NULL){
	    if( is_null($args) ) $args = WPF()->current_object['forum'];
	    if(!$args) return 1;
	    if( $cat_layout = (int) wpfval($args, 'cat_layout') ) return $cat_layout;
		if(is_array($args)){
			$default = array(
			  'forumid' => NULL, // forum id
			  'topicid' => NULL, // topic id
			  'postid'  => NULL, // post id
			);
		}else{
			$default = array(
			  'forumid' => $args,    // forumid
			  'topicid' => NULL,    // topic id
			  'postid'  => NULL,    // post id
			);
		}
		$args = wpforo_parse_args( $args, $default );
		if(!empty($args)){
			extract($args, EXTR_OVERWRITE);
			
			if( $args['forumid'] ){
				$cat_layout = (int) wpforo_forum($args['forumid'], 'cat_layout');
				return ( $cat_layout ? $cat_layout : 1 );
			}elseif( $args['topicid'] ){
				$sql = "SELECT `forumid` FROM `".WPF()->tables->topics."` WHERE `topicid` = " . intval($args['topicid']);
				$forumid = WPF()->db->get_var($sql);
				return $this->get_layout(array( 'forumid' => $forumid ));
			}elseif( $args['postid'] ){
				$sql = "SELECT `forumid` FROM `".WPF()->tables->posts."` WHERE `postid` = " .  intval($args['postid']);
				$forumid = WPF()->db->get_var($sql);
				return $this->get_layout(array( 'forumid' => $forumid ));
			}
		}

		return 1;
	}

	function get_forum_url($forum){
		
		if( !is_array($forum) ){
			if(is_numeric($forum)){
				$forum = $this->get_forum($forum);
			}else{
				$forum = array('slug' => $forum);
			}
		}
		
		if( is_array($forum) && !empty($forum) ){
			return wpforo_home_url( utf8_uri_encode($forum['slug']) );
		}else{
			return wpforo_home_url();
		}
	}
	
	function get_parents( $forumid, &$relative_ids){
	    $sql = "SELECT `parentid`, `forumid` FROM `".WPF()->tables->forums."` WHERE `forumid` = %d";
		$forum = WPF()->db->get_row(WPF()->db->prepare($sql, $forumid), ARRAY_A);
		if($forum){
			if($forum['parentid']){
				$relative_ids[] = $forum['forumid'];
				$this->get_parents($forum['parentid'], $relative_ids);
			}else{
				$relative_ids[] = $forum['forumid'];
				$relative_ids = array_reverse($relative_ids);
			}
		}
	}

	function get_count( $args = array() ){
		$sql = "SELECT SQL_NO_CACHE COUNT(*) FROM `".WPF()->tables->forums."`";
		if( !empty($args) ){
			$wheres = array();
			foreach ($args as $key => $value)  $wheres[] = "`$key` = " . intval($value);
			if($wheres) $sql .= " WHERE " . implode(' AND ', $wheres);
		}
		return WPF()->db->get_var($sql);
	}
	
	function get_lastinfo( $ids = array() ){
		$lastinfo = array();
		if(!empty($ids)){
			$ids = implode(',', array_map('intval', $ids));
			$lastinfo = WPF()->db->get_row( "SELECT `userid` as last_userid, `topicid` as last_topicid, `postid` as last_postid, `created` as last_post_date FROM `".WPF()->tables->posts."` WHERE `status` = 0 AND `private` = 0 AND forumid IN(" . $ids  .") ORDER BY `created` DESC LIMIT 1", ARRAY_A);
		}
		return $lastinfo;
	}
	
	function forums(){
		$forums = $this->get_forums( array('parentid' => 0) );
		return $this->children($forums);
	}
	
	function children($forums, $parentId = 0, $level = 0) {
		if(empty($forums) || !is_array($forums)) return;
		$items = array();
		$level = $level + 1;
		foreach ($forums as $forum) {
			if ( !isset($forum['forumid']) || !WPF()->perm->forum_can('vf', $forum['forumid'])) continue;
			$forum['level'] = $level + 1;
			if ($forum['parentid'] == $parentId) {
				$children = $this->children($forums, $forum['forumid'], $level);
				if ($children) {
					$forum['children'] = $children;
				}
				$items[] = $forum;
			}
		}
		return $items;
	}
	
	function dropdown( $forums = array() ){
		if( empty($forums) ){
			$forums = $this->forums(); 
		}
		foreach( $forums as $forum ){
			if( isset($forum['level']) ) $forum['level'] = $forum['level'] - 2;
			$prefix = ( $forum['level'] == 0 ) ? '' : str_repeat( '&mdash;', $forum['level']);
			echo '<option value="' . esc_attr( $forum['forumid'] ) . '"> ' . $prefix . '&nbsp;' . esc_html($forum['title']) . '</option>';
			if( !empty($forum['children']) ){
				$this->dropdown( $forum['children'] );
			}
		}
	}
	
	function private_forum( $forumid, $usergroups = array() ){
		if( $forumid ){
			if( empty($usergroups) ) {
				$groupid = WPF()->current_user_groupid;
				$usergroups = array( $groupid );
			}
			elseif( is_numeric($usergroups) ){
				$usergroups = array( $usergroups );
			}
			if( !empty($usergroups) && is_array($usergroups) ){
				foreach( $usergroups as $usergroup ){
					if( !WPF()->perm->forum_can('vf', $forumid, $usergroup) ){
						return true;
					}
				}
			}
		}
		return false;
	}

	public function after_add_usergroup($group){
		if($group['groupid']){
			if( $forums = $this->get_forums() ){
				foreach($forums as $forum){
					if( $permissions = (string) wpfval($forum, 'permissions') ){
						if( $permissions = maybe_unserialize($permissions) ){
							$permissions[$group['groupid']] = $group['access'];
							WPF()->db->update(
								WPF()->tables->forums,
								array('permissions' => serialize($permissions)),
								array('forumid'     => $forum['forumid']),
								array('%s'),
								array('%d')
							);
						}
					}
				}
			}
		}
	}
}