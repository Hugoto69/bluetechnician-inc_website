<?php
	// Exit if accessed directly
	if( !defined( 'ABSPATH' ) ) exit;

class wpForoPost{
	public $default;
	public $options;
	private $fields = array();

	public static $cache = array( 'posts' => array(), 'post' => array(), 'item' => array(), 'topic_slug' => array(), 'forum_slug' => array(), 'post_url' => array() );
	
	function __construct(){
		$this->init_defaults();
		$this->init_options();
		$this->init_hooks();
	}

	public function get_cache( $var ){
		if( isset(self::$cache[$var]) ) return self::$cache[$var];
	}

    public function reset(){
        self::$cache = array( 'posts' => array(), 'post' => array(), 'item' => array(), 'topic_slug' => array(), 'forum_slug' => array(), 'post_url' => array() );
    }

	private function init_defaults(){
	    $this->default = new stdClass;

        $upload_max_filesize = @ini_get('upload_max_filesize');
        $upload_max_filesize = wpforo_human_size_to_bytes($upload_max_filesize);
        if( !$upload_max_filesize || $upload_max_filesize > 10485760 ) $upload_max_filesize = 10485760;

		$this->default->options = array(
			'layout_extended_intro_posts_toggle' => 1,
			'layout_extended_intro_posts_count'  => 4,
			'layout_extended_intro_posts_length' => 50,
			'recent_posts_type'                  => 'topics',
			'tags'                               => 1,
			'max_tags'                           => 5,
			'tags_per_page'                      => 100,
			'topics_per_page'                    => 10,
			'edit_topic'                         => 1,
			'edit_post'                          => 1,
			'eot_durr'                           => 300,
			'dot_durr'                           => 300,
			'posts_per_page'                     => 15,
			'layout_threaded_posts_per_page'     => 5,
			'layout_qa_posts_per_page'           => 15,
			'layout_qa_comments_limit_count'     => 3,
			'layout_qa_first_post_reply'         => 1,
			'layout_threaded_nesting_level'      => 5,
			'layout_threaded_first_post_reply'   => 0,
			'eor_durr'                           => 300,
			'dor_durr'                           => 300,
			'max_upload_size'                    => $upload_max_filesize,
			'display_current_viewers'            => 1,
			'display_recent_viewers'             => 1,
			'display_admin_viewers'              => 1,
			'union_first_post'                   => array(
				1 => 0,
				2 => 0,
				3 => 1,
				4 => 0
			),
			'attach_cant_view_msg'               => __( "You are not permitted to view this attachment", 'wpforo' ),
			'search_max_results'                 => 100,
			'topic_body_min_length'              => 2,
			'topic_body_max_length'              => 0,
			'post_body_min_length'               => 2,
			'post_body_max_length'               => 0,
			'comment_body_min_length'            => 2,
			'comment_body_max_length'            => 0,
			'toolbar_location_topic'             => 'top',
			'toolbar_location_reply'             => 'top'
		);
    }

    private function init_options(){
        $this->options = get_wpf_option('wpforo_post_options', $this->default->options);
    }

	private function init_hooks(){
		add_filter('wpforo_content_after', array($this, 'print_custom_fields'), 99, 2);
    }

	/**
	 * @param int $layout
	 *
	 * @return int items_per_page
	 */
    public function get_option_items_per_page($layout = null){
	    switch ( $layout ) {
		    case 4:
			    $items_per_page = $this->options['layout_threaded_posts_per_page'];
			    break;
		    case 3:
			    $items_per_page = $this->options['layout_qa_posts_per_page'];
			    break;
		    default:
			    $items_per_page = $this->options['posts_per_page'];
			    break;
	    }

	    return (int) apply_filters('wpforo_post_get_option_items_per_page', $items_per_page, $layout);
    }
	
	/**
	 * @param int $layout
	 *
	 * @return bool
	 */
	public function get_option_union_first_post($layout){
		$layout = intval($layout);
		$union_first_post = (bool) wpfval($this->options['union_first_post'], $layout);
		return (bool) apply_filters('wpforo_post_options_get_union_first_post', $union_first_post, $layout);
	}
	
	public function add( $args = array() ){

		$guestposting = false;
        $root_exists = wpforo_root_exist();

		if( empty($args) && empty($_REQUEST['post']) ){ WPF()->notice->add('Reply request error', 'error'); return FALSE; }
		if( empty($args) && !empty($_REQUEST['post']) ) $args = $_REQUEST['post'];
		if( !isset($args['body']) || !$args['body'] ){ WPF()->notice->add('Post is empty', 'error'); return FALSE; }
        if( !wpfval($args, 'title') && wpfval($args, 'topicid') ){ $args['title'] = wpforo_phrase('RE', false) . ': ' . wpforo_topic($args['topicid'], 'title'); }
		$args['name'] = (isset($args['name']) ? strip_tags($args['name']) : '' );
		$args['email'] = (isset($args['email']) ? sanitize_email($args['email']) : '' );
		if( isset($args['userid']) && $args['userid'] == 0 && $args['name'] && $args['email'] ) $guestposting = true;
		
		extract($args);
		
		if( !isset($topicid) || !$topicid ){ WPF()->notice->add('Error: No topic selected', 'error'); return FALSE; }
		if( !$topic = WPF()->topic->get_topic(intval($topicid)) ){ WPF()->notice->add('Error: Topic is not found', 'error'); return FALSE; }
		if( !$forum = WPF()->forum->get_forum(intval($topic['forumid'])) ){ WPF()->notice->add('Error: Forum is not found', 'error'); return FALSE; }

		if( $topic['closed'] ){
			WPF()->notice->add('Can\'t write a post: This topic is closed', 'error');
			return FALSE;
		}
		
		if( !$guestposting && !(WPF()->perm->forum_can('cr', $topic['forumid']) || (wpforo_is_owner($topic['userid'],$topic['email']) && WPF()->perm->forum_can('ocr', $topic['forumid']))) ){
			WPF()->notice->add('You don\'t have permission to create post in this forum', 'error');
			return FALSE;
		}

		if( !WPF()->perm->can_post_now() ){
			WPF()->notice->add('You are posting too quickly. Slow down.', 'error');
			return FALSE;
		}
		
		if( !is_user_logged_in() ){
			if( !$args['name'] || !$args['email'] ){
				WPF()->notice->add('Please insert required fields!', 'error');
				return FALSE;
			}
			else{
				WPF()->member->set_guest_cookies( $args );
			}
		}
		
		do_action( 'wpforo_start_add_post', $args );
		
		$post = $args;
		$post['forumid'] = $forumid = (isset($topic['forumid']) ? intval($topic['forumid']) : 0);
		$post['parentid'] = $parentid = (isset($parentid) ? intval($parentid) : 0);
		$post['title'] = $title = (isset($title) ? wpforo_text( trim($title), 250, false ) : '');
		$post['body'] = $body = ( isset($body) ? preg_replace('#</pre>[\r\n\t\s\0]*<pre>#isu', "\r\n", $body) : '' );
		$post['created'] = $created = ( isset($created) ? $created : current_time( 'mysql', 1 ) );
		$post['userid'] = $userid = ( isset($userid) ? intval($userid) : WPF()->current_userid );
		if( $root_exists ){
            $post['root'] = ( $parentid ) ? ( isset($root) ? intval($root) : $this->get_root( $parentid ) ) : -1;
        } else {
            $root = NULL;
        }

		$post = apply_filters('wpforo_add_post_data_filter', $post);
		
		if(empty($post)) return FALSE;

		extract($post, EXTR_OVERWRITE);
		
		if(isset($forumid)) $forumid = intval($forumid);
		if(isset($topicid)) $topicid = intval($topicid);
		if(isset($parentid)) $parentid = intval($parentid);
		if(isset($title)) $title = sanitize_text_field(trim($title));
		if(isset($created)) $created = sanitize_text_field($created);
		if(isset($userid)) $userid = intval($userid);
		if(isset($body)) $body = wpforo_kses(trim($body), 'post');
        $status = ( isset($status) && $status ? 1 : 0 );
        $private = ( isset($topic['private']) && $topic['private'] ? 1 : 0 );
        if(isset($name)) $name = strip_tags(trim($name));
        if(isset($email)) $email = strip_tags(trim($email));

        do_action( 'wpforo_before_add_post', $post );

        $fields = array('forumid'	=> $forumid,
                        'topicid'	=> $topicid,
                        'parentid'	=> $parentid,
                        'userid' 	=> $userid,
                        'title'     => stripslashes($title),
                        'body'      => stripslashes($body),
                        'created'	=> $created,
                        'modified'	=> $created,
                        'status'	=> $status,
                        'private'	=> $private,
                        'name' 		=> $name,
                        'email' 	=> $email,
                        'root' 	    => $root );

		$values = array('%d','%d','%d','%d','%s','%s','%s','%s','%d','%d','%s','%s','%d');

		if(!$root_exists){ unset($fields['root']); unset($fields[12]); }

		if( WPF()->db->insert(
				WPF()->tables->posts,
                $fields,
				$values
			)
		){
			$postid = WPF()->db->insert_id;

			$post['postid'] = $postid;
			$post['status'] = $status;
			$post['private'] = $private;
			$post['posturl'] = $this->get_post_url($postid);

            if( $root_exists ) {
                WPF()->topic->rebuild_threads( $topic, $root );
            }

			if ( !$status ) {
				$answ_incr = '';
				$comm_incr = '';
				if ( WPF()->forum->get_layout($forum) == 3 ) {
					if ( $parentid ) {
						$comm_incr = ', `comments` = `comments` + 1 ';
					} else {
						$answ_incr = ', `answers` = `answers` + 1 ';
					}
				}
				WPF()->db->query( "UPDATE `" . WPF()->tables->profiles . "` SET `posts` = `posts` + 1 $answ_incr $comm_incr WHERE `userid` = " . wpforo_bigintval( $userid ) );
                WPF()->topic->rebuild_first_last( $topic );
				WPF()->topic->rebuild_stats( $topic );
				WPF()->forum->rebuild_last_infos( $forum['forumid'] );
				WPF()->forum->rebuild_stats( $forum['forumid'] );
			}

			do_action( 'wpforo_after_add_post', $post, $topic, $forum );
			
			wpforo_clean_cache('post', $postid, $post);
			WPF()->member->reset($userid);
			WPF()->notice->add('You successfully replied', 'success');
			return $postid;
		}
		
		WPF()->notice->add('Reply request error', 'error');
		return FALSE;
	}
	
	public function edit( $args = array() ){
		
		//This variable will be based on according CAN of guest usergroup once Guest Posing is ready
		$guestposting = false;
		
		if( empty($args) && (!isset($_REQUEST['post']) || empty($_REQUEST['post'])) ) return FALSE;
		if( empty($args) && !empty($_REQUEST['post']) ) $args = $_REQUEST['post'];
		if( isset($args['name']) ){ $args['name'] = strip_tags($args['name']); }
		if( isset($args['email']) ){ $args['email'] = sanitize_email($args['email']); }
		
		do_action( 'wpforo_start_edit_post', $args );
		
		if( !isset($args['postid']) || !$args['postid'] || !is_numeric($args['postid']) ){
			WPF()->notice->add('Cannot update post data', 'error');
			return FALSE;
		}
		$args['postid'] = intval($args['postid']);
		if( !$post = $this->get_post($args['postid']) ){ WPF()->notice->add('No Posts found for update', 'error'); return FALSE; }
		
		if( !is_user_logged_in() ){
			if( !isset($post['email']) || !$post['email'] ){
				WPF()->notice->add('Permission denied', 'error');
				return FALSE;
			}
			elseif( !wpforo_current_guest( $post['email'] ) ){
				WPF()->notice->add('You are not allowed to edit this post', 'error');
				return FALSE;
			}
			if( !$args['name'] || !$args['email'] ){
				WPF()->notice->add('Please insert required fields!', 'error');
				return FALSE;
			}
			else{
				WPF()->member->set_guest_cookies( $args );
			}
		}
		
		$args['userid'] = $post['userid'];
		$args['status'] = $post['status'];

        if( isset($args['userid']) && $args['userid'] == 0 && isset($args['name']) && isset($args['email']) ) $guestposting = true;

		$args = apply_filters('wpforo_edit_post_data_filter', $args);
		if(empty($args)) return FALSE;
		
		extract($args, EXTR_OVERWRITE);
		
		if( !$guestposting ){
			$diff = current_time( 'timestamp', 1 ) - strtotime($post['created']);
			if( !(WPF()->perm->forum_can('er', $post['forumid']) ||
					(WPF()->current_userid == $post['userid'] && WPF()->perm->forum_can('eor', $post['forumid'])) )
            ){
				WPF()->notice->add('You don\'t have permission to edit post from this forum', 'error');
				return FALSE;
			}

            if(!WPF()->perm->forum_can('er', $post['forumid']) &&
                    $this->options['eor_durr'] !== 0 &&
                        $diff > $this->options['eor_durr']){
                WPF()->notice->add('The time to edit this post is expired.', 'error');
                return FALSE;
            }
		}

		$title = (isset($title) ? wpforo_text( trim($title), 250, false ) : '');
		$body = ( isset($body) ? preg_replace('#</pre>[\r\n\t\s\0]*<pre>#isu', "\r\n", $body) : '' );
		$body = wpforo_kses(trim($body), 'post');

		$topicid = wpforo_bigintval( (isset($topicid) ? $topicid : $post['topicid']) );
		$title   = trim($title) ?    stripslashes(sanitize_text_field(trim($title))) : stripslashes($post['title']);
		$body    = $body           ? stripslashes($body)                             : stripslashes($post['body']);
		$status  = isset($status)  ? intval($status)                                 : intval($post['status']);
        $private = isset($private) ? intval($private)                                : intval($post['private']);
		$name    = isset($name)    ? stripslashes(strip_tags(trim($name)))           : stripslashes($post['name']);
		$email   = isset($email)   ? stripslashes(strip_tags(trim($email)))          : stripslashes($post['email']);
		
		if( FALSE !== WPF()->db->update(
				WPF()->tables->posts,
				array(
					'title'    => $title,
					'body'     => $body,
					'modified' => current_time( 'mysql', 1 ),
					'status'   => $status,
					'name'     => $name,
					'email'    => $email,
				),
				array('postid' => $postid),
				array('%s','%s','%s','%d','%s','%s'),
				array('%d') 
			)
		){
			$post['topicid'] = $topicid;
			$post['title']   = $title;
			$post['body']    = $body;
			$post['status']  = $status;
			$post['private'] = $private;
			$post['name']    = $name;
			$post['email']   = $email;
			do_action( 'wpforo_after_edit_post', $post, $args );
			
			wpforo_clean_cache('post', $postid, $post);
			WPF()->notice->add('This post successfully edited', 'success');
			return $postid;
		}
		
		WPF()->notice->add('Reply request error', 'error');
		return FALSE;
	}
	
	#################################################################################
	/**
	 * Delete post from DB
	 * Returns true if successfully deleted or false.
	 *
	 * @since 1.0.0
	 *
	 * @param int $postid
	 * @param bool $delete_cache
	 * @param bool $rebuild_data
	 * @param array &$exclude
	 * @param bool $check_permissions
	 *
	 * @return bool
	 */

	function delete( $postid, $delete_cache = true, $rebuild_data = true, &$exclude = array(), $check_permissions = true ){
		$postid = intval($postid);
		$exclude = (array) $exclude;

		if( !$post = $this->get_post($postid) ) return true;

		do_action('wpforo_before_delete_post', $post);

		$diff = current_time( 'timestamp', 1 ) - strtotime($post['created']);
		if( $check_permissions && (!(WPF()->perm->forum_can('dr', $post['forumid']) ||
            (WPF()->current_userid == $post['userid'] &&
                WPF()->perm->forum_can('dor', $post['forumid'])  ))) ){
			WPF()->notice->add('You don\'t have permission to delete post from this forum', 'error');
			return FALSE;
		}

		if( $check_permissions && (!WPF()->perm->forum_can('dr', $post['forumid']) &&
                $this->options['dor_durr'] !== 0 &&
                    $diff > $this->options['dor_durr']) ){
            WPF()->notice->add('The time to delete this post is expired.', 'error');
            return FALSE;
        }
		//Find and delete default attachments before deleting post
		$this->delete_attachments( $postid );

		//Delete post
		if( WPF()->db->delete(WPF()->tables->posts,  array( 'postid' => $postid ), array( '%d' )) ){
			WPF()->db->delete(
				WPF()->tables->likes, array( 'postid' => $postid ), array( '%d' )
			);
			WPF()->db->delete(
				WPF()->tables->votes, array( 'postid' => $postid ), array( '%d' )
			);

			$answ_incr = '';
			$comm_incr = '';
			$layout = WPF()->forum->get_layout($post['forumid']);
			if($layout == 3){
				if($post['parentid']){
					$comm_incr = ', `comments` = IF( (`comments` - 1) < 0, 0, `comments` - 1 ) ';
				}else{
					$answ_incr = ', `answers` = IF( (`answers` - 1) < 0, 0, `answers` - 1 ) ';
				}
			}

			if( isset($post['parentid']) ){
			    if( !$post['is_first_post'] && $layout == 4 ){
			        if( $post['parentid'] == 0 ){
                        $replies = WPF()->db->get_results( "SELECT `postid` FROM `".WPF()->tables->posts."` WHERE `root` = " . wpforo_bigintval($postid), ARRAY_A );
                    } else {
                        $children = array();
                        $replies = $this->get_children( $postid, $children, true );
                    }
			        if( !empty( $replies ) ){
                        foreach( $replies as $reply ){
                            if( !in_array($reply['postid'], $exclude) ){
                                $exclude[] = $reply['postid'];
                                $this->delete( $reply['postid'], false, false , $exclude, false);
                            }
                        }
                    }
			    } elseif( $post['parentid'] != 0 ) {
                    WPF()->db->query("UPDATE `".WPF()->tables->posts."` SET `parentid` = " . wpforo_bigintval($post['parentid']) . " WHERE `parentid` = " . wpforo_bigintval($postid) );
                }
            }

			if( $rebuild_data ){
                if( !$post['is_first_post'] && $layout == 4 ){
                    WPF()->topic->rebuild_threads($post['topicid']);
                }
			    WPF()->topic->rebuild_first_last($post['topicid']);
                WPF()->topic->rebuild_stats($post['topicid']);
                WPF()->forum->rebuild_last_infos($post['forumid']);
                WPF()->forum->rebuild_stats($post['forumid']);
            }

			if( false !== WPF()->db->query( "UPDATE IGNORE `".WPF()->tables->profiles."` SET `posts` = IF( (`posts` - 1) < 0, 0, `posts` - 1 ) $answ_incr $comm_incr WHERE `userid` = " . wpforo_bigintval($post['userid']) ) ){
				WPF()->member->reset($post['userid']);
			}

			WPF()->notice->add('This post successfully deleted', 'success');

			do_action('wpforo_after_delete_post', $post);

			if( $post['is_first_post'] ) return WPF()->topic->delete($post['topicid']);
			if( $delete_cache ) wpforo_clean_cache('post', $postid, $post);
			return TRUE;
		}

		WPF()->notice->add('Post delete error', 'error');
		return FALSE;
	}

	#################################################################################
	/**
	 * @since 1.0.0
	 *
	 * @param int $postid
	 * @param bool $protect
	 *
	 * @return array
	 */
	function get_post( $postid, $protect = true ){
		$cache = WPF()->cache->on('memory_cashe');
		if( $cache && isset(self::$cache['post'][$postid]) ) return self::$cache['post'][$postid];

		$sql = "SELECT * FROM `".WPF()->tables->posts."` WHERE `postid` = " . wpforo_bigintval($postid);
		$post = (array) WPF()->db->get_row($sql, ARRAY_A);

		if(!empty($post)) $post['userid'] = wpforo_bigintval($post['userid']);

		if( $protect ){
            if( isset($post['forumid']) && $post['forumid'] && !WPF()->perm->forum_can('vf', $post['forumid']) ){
                return array();
            }
            if( isset($post['status']) && $post['status'] && !wpforo_is_owner($post['userid'], $post['email'])){
                if( isset($post['forumid']) && $post['forumid'] && !WPF()->perm->forum_can('au', $post['forumid']) ){
                    return array();
                }
            }
        }

		if($cache) self::$cache['post'][$postid] = $post;

		$post = apply_filters('wpforo_get_post', $post);
		return $post;
	}

	/**
	 * get all posts based on provided arguments
	 *
	 * @since 1.0.0
	 *
	 * @param array $args
	 * @param int $items_count
	 * @param bool $count
	 *
	 * @return 	array
	 */
	function get_posts($args = array(), &$items_count = 0, $count = true ){

		$cache = WPF()->cache->on('object_cashe');

		$default = array(
			'include'          => array(),        // array( 2, 10, 25 )
			'exclude'          => array(),        // array( 2, 10, 25 )
			'forumids'         => array(),
			'topicid'          => null,        // topic id in DB
			'forumid'          => null,        // forum id in DB
			'parentid'         => null,        // parent post id
			'root'             => null,        // root postid
			'userid'           => null,        // user id in DB
			'orderby'          => '`is_first_post` DESC, `created` ASC, `postid` ASC',    // forumid, order, parentid
			'order'            => '',            // ASC DESC
			'offset'           => null,        // this use when you give row_count
			'row_count'        => null,        // 4 or 1 ...
			'status'           => null,        // 0 or 1 ...
			'private'          => null,        // 0 or 1 ...
			'email'            => null,        // example@example.com ...
			'check_private'    => true,
			'where'            => null,
			'owner'            => null,
			'cache_type'       => 'sql',       // sql or args
			'limit_per_topic'  => null,
			'union_first_post' => false,
			'is_first_post'    => null,
			'is_answer'        => null,
            'threaded'         => false,
		);

        $request = $args;
		if( empty($args['orderby']) ) $args['order'] = '';

		$args = wpforo_parse_args( $args, $default );

        if( $args['row_count'] === 0 ) return array();
        if( $args['forumid'] && $args['check_private'] && !WPF()->perm->forum_can('vf', $args['forumid']) ) return array();
        if( strtoupper( $args['order'] ) != 'DESC' && strtoupper( $args['order'] ) != 'ASC' ) $args['order'] = '';
        if(!wpforo_root_exist() && !is_null($args['root']) ) { $args['parentid'] = $args['root']; $args['root'] = NULL; }

        $wheres = $this->get_posts_conditions( $args );

		$ordering = ( $args['orderby'] ? " ORDER BY " . esc_sql( $args['orderby'] . ' ' . $args['order'] ) : '' );
		$limiting = ( $args['row_count'] ? " LIMIT " . intval( $args['offset'] ) . "," . intval( $args['row_count'] ) : '' );

		if( $limit_per_topic = intval($args['limit_per_topic']) ){
			$sql = "SELECT SUBSTRING_INDEX( GROUP_CONCAT(`postid` ORDER BY `created` DESC), ',', " . $limit_per_topic . " ) postids
					FROM `".WPF()->tables->posts."` ".
			       ($wheres ? " WHERE " . implode(" AND ", $wheres) : '')
					." GROUP BY `topicid` ORDER BY MAX(`postid`) DESC " . $limiting;

            if( $cache ){
                if( $args['cache_type'] == 'sql' ){
                    $object_key = md5( $sql . WPF()->current_user_groupid );
                    $object_cache = WPF()->cache->get($object_key);
                    if(!empty($object_cache)){
                        return $object_cache['items'];
                    }
                }
            }

            // Returns an array of post IDs ////////////////////////////////
            $posts = WPF()->db->get_col($sql);
            ////////////////////////////////////////////////////////////////

		} else {

			$sql = "SELECT * FROM `".WPF()->tables->posts."`";
			if(!empty($wheres)){
				$sql .= " WHERE " . implode(" AND ", $wheres);
			}
			if( $count ){
				$item_count_sql = preg_replace('#SELECT.+?FROM#isu', 'SELECT count(*) FROM', $sql, 1);
				if( $item_count_sql ) $items_count = WPF()->db->get_var($item_count_sql);
			}

			$sql .= $ordering . $limiting;

			if( $args['union_first_post'] && $args['topicid'] && !$args['parentid'] && $items_count > intval( $args['offset'] ) ){
				$sql = "( SELECT * FROM `".WPF()->tables->posts."` 
				WHERE `topicid` = ".wpforo_bigintval($args['topicid'])." 
				AND `is_first_post` = 1 ) 
				UNION 
				( " . $sql . " )";
			}

			if( $cache ){
                if( $args['cache_type'] == 'sql' ){
                    $object_key = md5( $sql . WPF()->current_user_groupid );
                    $object_cache = WPF()->cache->get($object_key);
                    if(!empty($object_cache)){
                        return $object_cache['items'];
                    }
                }
				if( $args['cache_type'] == 'args' ){
                    $hach = serialize($request);
                    $cache_args_key = md5( $hach . WPF()->current_user_groupid );
                    $object_cache = WPF()->cache->get($cache_args_key, 'loop', 'post');
                    if(!empty($object_cache)){
                        return $object_cache['items'];
                    }
                }
			}

            // Returns an array of posts /////////////////////////////////
			$posts = WPF()->db->get_results($sql, ARRAY_A);
			//////////////////////////////////////////////////////////////

            $posts = apply_filters('wpforo_get_posts', $posts);

			if( $args['check_private'] && !$args['forumid'] ){
				$posts = $this->access_filter( $posts, $args['owner'] );
			}
		}

        if($cache && isset($object_key) && !empty($posts)){
            self::$cache['posts'][$object_key]['items'] = $posts;
            self::$cache['posts'][$object_key]['items_count'] = $items_count;
            if(isset($cache_args_key) && $args['cache_type'] == 'args' ){
                WPF()->cache->create_custom( $request, $posts, 'post', $items_count );
            }
        }
        return $posts;
	}

	function get_posts_conditions( $args = array() ){

        $wheres = array();
        $table_as_prefix = '`'.WPF()->tables->posts.'`.';

        $args['include'] = wpforo_parse_args( $args['include'] );
        $args['exclude'] = wpforo_parse_args( $args['exclude'] );
        $args['forumids'] = wpforo_parse_args( $args['forumids'] );

        if(!empty($args['include'])) $wheres[] = $table_as_prefix . "`postid` IN(" . implode(',', array_map('wpforo_bigintval', $args['include'])) . ")";
        if(!empty($args['exclude'])) $wheres[] = $table_as_prefix . "`postid` NOT IN(" . implode(',', array_map('wpforo_bigintval', $args['exclude'])) . ")";
        if(!empty($args['forumids'])) $wheres[] = $table_as_prefix . "`forumid` IN(" . implode(',', array_map('intval', $args['forumids'])) . ")";

        if(!is_null($args['topicid']))       $wheres[] = $table_as_prefix . "`topicid` = " . wpforo_bigintval($args['topicid']);
        if(!is_null($args['parentid']))      $wheres[] = $table_as_prefix . "`parentid` = " . wpforo_bigintval($args['parentid']);
        if(!is_null($args['root']))          $wheres[] = $table_as_prefix . "`root` = " . wpforo_bigintval($args['root']);
        if(!is_null($args['userid']))        $wheres[] = $table_as_prefix . "`userid` = " . wpforo_bigintval($args['userid']);
        if(!is_null($args['status']))        $wheres[] = $table_as_prefix . "`status` = " . intval( (bool) $args['status']);
        if(!is_null($args['private']))       $wheres[] = $table_as_prefix . "`private` = " . intval( (bool) $args['private']);
        if(!is_null($args['is_first_post'])) $wheres[] = $table_as_prefix . "`is_first_post` = " . intval( (bool) $args['is_first_post']);
        if(!is_null($args['is_answer']))     $wheres[] = $table_as_prefix . "`is_answer` = " . intval( (bool) $args['is_answer']);
        if(!is_null($args['email']))         $wheres[] = $table_as_prefix . "`email` = '" . esc_sql($args['email']) . "' ";
        if(!is_null($args['where']))         $wheres[] = $table_as_prefix . $args['where'];

        if(wpfval($args, 'forumid') && $args['check_private']){

            /////Check "View Reply" Access//////////////////////////////
            if( !WPF()->perm->forum_can('vr', $args['forumid']) ){
                $wheres[] = $table_as_prefix . " `is_first_post` = 1";
            }

            /////Check Unapproved Post Access////////////////////////////
            if( WPF()->perm->forum_can('au', $args['forumid']) ){
                //Check "Can Approve/Unapprove Posts" Access (View Unapproved Posts)
                if(!is_null($args['status'])) $wheres[] = $table_as_prefix . " `status` = " . intval($args['status']);
            }
            elseif( WPF()->current_userid ){
                //Allow Users see own unapproved posts
                $wheres[] = " ( " . $table_as_prefix .  "`status` = 0 OR (" . $table_as_prefix .  "`status` = 1 AND " . $table_as_prefix .  "`userid` = " .intval(WPF()->current_userid). ") )";
            }
            elseif( WPF()->current_user_email ){
                //Allow Guests see own unapproved posts
                $wheres[] = " ( " . $table_as_prefix .  "`status` = 0 OR (" . $table_as_prefix .  "`status` = 1 AND " . $table_as_prefix .  "`email` = '" . sanitize_email(WPF()->current_user_email) . "') )";
            }
            else{
                //If doesn't have "Can Approve/Unapprove Posts" access and not Owner, only return approved posts
                $wheres[] = " " . $table_as_prefix .  "`status` = 0";
            }
        }
        return $wheres;
    }

    function access_filter( $posts, $owner = NULL ){
	    if( !empty($posts) ){
            foreach($posts as $key => $post){
                if(!$this->access($post, $owner)) unset($posts[$key]);
            }
        }
        return $posts;
    }

	function access( $post, $owner = NULL ){
		if( isset($post['forumid']) && !WPF()->perm->forum_can('vf', $post['forumid']) ){
			return false;
		}
		if( isset($post['forumid']) && !WPF()->perm->forum_can('vt', $post['forumid']) ){
			return false;
		}
		if( isset($post['forumid']) && !wpfval($post, 'is_first_post') && !WPF()->perm->forum_can('vr', $post['forumid']) ){
			return false;
		}
		if( isset($post['forumid']) && isset($post['private']) && $post['private'] && !$owner ){
			if(!WPF()->perm->forum_can('vp', $post['forumid'])){
				if(is_null($owner)){
					$topic_userid = wpforo_topic($post['topicid'], 'userid');
					if(!wpforo_is_owner($topic_userid) && !wpforo_is_owner($post['userid'])){
						return false;
					}
				}else{
					return false;
				}
			}
		}
		if( isset($post['forumid']) && isset($post['status']) && $post['status'] && !wpforo_is_owner($post['userid'], $post['email']) ){
			if( !WPF()->perm->forum_can('au', $post['forumid']) ){
				return false;
			}
		}
		return true;
	}

    function replies( array $posts, $topic = array(), $forum = array(), $level = 0 ) {
        $level++;
        if( function_exists('wpforo_thread_reply') ){
            if( wpfval($posts, 'posts') ){
                $key = key($posts['posts']);
                $parentid = (wpfval($posts, 'posts', $key,'parentid')) ? $posts['posts'][$key]['parentid'] : 0;
                $max_level = $this->options['layout_threaded_nesting_level'];
                if( !$max_level ){
                    $class = ( $level > 1 ) ? '' : ' level-1';
                } elseif( $level > ( $max_level + 1 ) ) {
                    $class = '';
                } else {
                    $class = ' level-' . $level;
                }
                echo '<div id="wpf-post-replies-'.intval($parentid).'" class="wpf-post-replies '. $class .'">';
                foreach ( $posts['posts'] as $post ) {
                    $parents = ( wpfval($posts, 'parents') ) ? $posts['parents'] : array();
                    wpforo_thread_reply( $post, $topic, $forum, $level, $parents );
                    if ( !empty($post['children']) ) {
                        $posts['posts'] = $post['children'];
                        $this->replies($posts, $topic, $forum, $level );
                    }
                }
                echo '</div>';
            }
        } else{
            wpforo_phrase('Function wpforo_thread_reply() not found.');
        }
    }

	function get_thread_tree( $post, $parents = true ){

        if(!wpfval($post, 'postid') || (wpfkey($post, 'root') && $post['root'] == -1) ) {
            return array('posts' => array(), 'parents' => array(), 'count' => 0, 'children' => '' );
        }

        $items = array();
        $thread = array();
        $parentid = $post['postid'];
        $type = apply_filters('wpforo_thread_builder_type', 'topic-query'); //'topic-query', 'inside-mysql', 'multi-query'
        if( $type == 'topic-query' ) {
            if( wpfval($post, 'topicid') ){
                $args = array( 'root' => $post['postid'], 'orderby' => '`created` ASC' );
                $posts = $this->get_posts( $args, $items_count, false);
                if( empty($posts) ){
                    $args = array( 'topicid' => $post['topicid'], 'orderby' => '`created` ASC' );
                    $posts = $this->get_posts( $args, $items_count, false);
                }
                if( !empty($posts) ){
                    foreach( $posts as $post ){
                        $items[$post['postid']] = $post;
                    }
                    $thread = $this->build_thread_data( $parentid, $items );
                }
            }
        }
        elseif( $type == 'inside-mysql' ){
            $mod = ( wpforo_current_user_is('admin') || wpforo_current_user_is('moderator') ) ? true : false;
            $sql = "SELECT GROUP_CONCAT( @id :=  ( SELECT  GROUP_CONCAT(postid,'-', parentid, '-', userid, '-', status, '-', email)  FROM  `" . WPF()->tables->posts . "` WHERE   parentid = @id ) ) AS tree
                          FROM ( SELECT  @id := " . intval($parentid) . " ) vars STRAIGHT_JOIN `" . WPF()->tables->posts . "` WHERE @id IS NOT NULL";
            if( $posts = WPF()->db->get_var($sql) ){
                $posts = explode(',', $posts);
                if(!empty($posts)){
                    foreach($posts as $post) {
                        $post = explode('-', $post);
                        if( !$mod && isset($post[3]) && $post[3] ){
                            if( isset($post[2]) && isset($post[4]) && ( isset(WPF()->current_user['ID']) || isset(WPF()->current_user['user_email']) ) ){
                                if( WPF()->current_user['ID'] != $post[2] && WPF()->current_user['user_email'] != $post[4] ) continue;
                            }
                        }
                        if( isset($post[0]) && isset($post[1]) ){
                            $items[$post[0]] = array('postid' => $post[0], 'parentid' => $post[1]);
                        }
                    }
                    $thread = $this->build_thread_data( $parentid, $items );
                }
            }
        }
        elseif( $type == 'multi-query' ) {
            $mod = ( wpforo_current_user_is('admin') || wpforo_current_user_is('moderator') ) ? true : false;
            $items = $this->get_children( $parentid, $children, $mod );
            $thread = $this->build_thread_data( $parentid, $items );
        }
        return $thread;
    }

    function build_thread_data( $parentid, $items = array(), $count = 0 ){

        $parents = array();
        $thread = array('posts' => array(), 'parents' => array(), 'count' => 0, 'children' => '' );

        if( !empty($items) ){
            foreach( $items as $item ){
                $parents[$item['postid']] = $this->parents( $item['postid'], $items );
            }
            if( !empty($parents) ) $thread['parents'] = $parents;
            $thread['posts'] = $this->build_thread_tree( $items, $parentid );
            $children = $this->children( $parentid, $thread['posts']);
            $thread['count'] = count($children);
            $thread['children'] = array_keys($children);
        }
        return $thread;
    }

    function build_thread_tree( array $posts, $parentid = 0 ) {
        $tree = array();
        foreach ( $posts as $post ) {
            if ($post['parentid'] == $parentid) {
                $children = $this->build_thread_tree( $posts, $post['postid'] );
                if ($children) {
                    $post['children'] = $children;
                }
                $tree[] = $post;
            }
        }
        return $tree;
    }

    function root( $postid, $parentid = NULL ){
        if( !$postid ) return 0;
        $parents = $this->get_parents( $postid, $parentid );
        $root = array_pop($parents);
        return intval($root);
    }

    function get_root( $postid ){
	    if( !$postid || !wpforo_root_exist() ) return $postid;
        $root = WPF()->db->get_var("SELECT `root` FROM `" . WPF()->tables->posts . "` WHERE  `postid` = " . intval($postid) );
        if( !is_null($root) && ( $root <= 0 || $root == $postid ) ){
            $root = $postid;
        } else {
            $root = $this->root( $postid );
        }
        return $root;
	}

    function parents( $postid, $posts, $parents = array() ) {
	    if( !empty($posts) ){
	        if( isset($posts[$postid]) ){
                $parentid = wpfval($posts[$postid], 'parentid') ? $posts[$postid]['parentid'] : 0;
                if ($parentid > 0) {
                    array_unshift($parents, $parentid);
                    return $this->parents($parentid, $posts, $parents);
                }
            }
        }
        return $parents;
    }

    function get_parents( $postid, $parentid = NULL, &$parents = array(), $mod = false ) {
        if( $postid ){
            $status = ( !$mod ) ? ' AND `status` = 0 ': '';
            if( is_null($parentid) ){
                $where = "`postid` = " . intval($postid);
            } else {
                $where = "`postid` = " . intval($parentid);
            }
            if( $parentid === 0 ) {
                return $parents;
            } else {
                $post = WPF()->db->get_row("SELECT `postid`, `parentid` FROM `" . WPF()->tables->posts . "` WHERE  " . $where .  $status, ARRAY_A );
                if( wpfval($post, 'parentid') ){
                    $parents[ $post['postid'] ] = $post['parentid'];
                    $this->get_parents( $post['postid'], $post['parentid'], $parents, $mod );
                }
            }
        }
        return $parents;
    }

    function children( $parentid, $posts, &$children = array() ){
        if( $parentid ){
            if( !empty($posts) ){
                foreach( $posts as $post ){
                    $children[ $post['postid'] ] = array('postid' => $post['postid'], 'parentid' => $post['parentid']);
                    if( isset($post['children']) ) $this->children($post['postid'], $post['children'], $children);
                }
            }
        }
        return $children;
    }

    function get_children( $parentid, &$children = array(), $mod = false ){
	    if( $parentid ){
            $status = ( !$mod ) ? ' AND `status` = 0 ': '';
            $posts = WPF()->db->get_results("SELECT `postid`, `parentid` FROM `" . WPF()->tables->posts . "` FORCE INDEX (PRIMARY) WHERE `parentid` = " . intval($parentid) ." " . $status, ARRAY_A );
            if( !empty($posts) ){
                foreach( $posts as $post ){
                    $children[ $post['postid'] ] = array('postid' => $post['postid'], 'parentid' => $post['parentid']) ;
                    $this->get_children( $post['postid'], $children, $mod );
                }
            }
	    }
	    return $children;
    }

    function get_root_replies_count( $postid ){
	    $postid = intval($postid);
	    if( $postid && wpforo_root_exist() ) {
	        return (int) WPF()->db->get_var("SELECT COUNT(*) FROM `" . WPF()->tables->posts . "` WHERE `root` = " . $postid );
        } else {
            return (int) WPF()->db->get_var("SELECT COUNT(*) FROM `" . WPF()->tables->posts . "` WHERE `parentid` = " . $postid );
        }
    }

	function get_posts_filtered( $args = array() ){
		$posts = $this->get_posts( $args, $items_count, false );
		if( !empty($posts) ){
			foreach($posts as $key => $post){
				if( isset($post['forumid']) && !WPF()->perm->forum_can('vf', $post['forumid']) ){
					unset($posts[$key]);
				}
				if( isset($posts[$key]) && isset($post['forumid']) && isset($post['private']) && $post['private'] && !wpforo_is_owner($post['userid'], $post['email']) ){
					if( !WPF()->perm->forum_can('vp', $post['forumid']) ){
						unset($posts[$key]);
					}
				}
				if( isset($posts[$key]) && isset($post['forumid']) && isset($post['status']) && $post['status'] && !wpforo_is_owner($post['userid'], $post['email']) ){
					if( !WPF()->perm->forum_can('au', $post['forumid']) ){
						unset($posts[$key]);
					}
				}
			}
		}
		return $posts;
	}
	
	function search( $args = array(), &$items_count = 0 ){
		if(!is_array($args)) $args = array('needle' => $args);
		if(!wpfval($args, 'needle') && !wpfval($args, 'postids')) return array();

		$args = array_filter($args);

		$default = array( 
		  'needle'		=> '', 		 		 // search needle
		  'forumids' 	=> array(), 		 // array( 2, 10, 25 )
		  'postids' 	=> array(), 		 // array( 2, 10, 25 )
		  'date_period'	=> 0,				 // topic id in DB
		  'type'		=> 'entire-posts',	 // search type ( entire-posts | titles-only | user-posts | user-topics | tag )
		  'orderby'		=> 'relevancy',      // Sort Search Results by ( relevancy | date | user | forum )
		  'order'		=> 'DESC', 			 // Sort Search Results ( ASC | DESC )
		  'offset' 		=> NULL,			 // this use when you give row_count
		  'row_count'	=> NULL 			 // 4 or 1 ...
		);
		
		$args = wpforo_parse_args( $args, $default );
		$args['postids'] = wpforo_parse_args($args['postids']);
		$args['postids'] = array_filter(array_map('wpforo_bigintval', $args['postids']));

		$args['order'] = strtoupper($args['order']);
		if( !in_array($args['order'], array('ASC', 'DESC')) ) $args['order'] = 'DESC';

		$date_period = intval($args['date_period']);

		$fa = "p";
		$from = "`".WPF()->tables->posts."` " . $fa;
		$selects = array($fa.'.`postid`', $fa.'.`topicid`', $fa.'.`private`', $fa.'.`status`', $fa.'.`forumid`', $fa.'.`userid`', $fa.'.`title`', $fa.'.`created`', $fa.'.`body`', $fa.'.`is_first_post`' );
		$innerjoins = array();
		$wheres = array();
		$orders = array();

		if($args['forumids']) $wheres[] = $fa.".`forumid` IN(" . implode(', ', array_map('intval', $args['forumids'])) . ")";
		if( $date_period != 0 ){
			$date = date( 'Y-m-d H:i:s', current_time( 'timestamp', 1 ) - ($date_period * 24 * 60 * 60) );
			if($date) $wheres[] = $fa.".`created` > '".esc_sql($date)."'";
		}

		if($args['needle']){
			if( in_array($args['type'], array('entire-posts', 'titles-only')) ){
				$words = preg_split('#[^\p{L}\p{N}\'`]+#u', $args['needle']);
				$words = array_slice(array_filter($words), 0, 10);
				$words = array_map(function($w){
					return '+' . esc_sql( str_replace(array('(', ')', '*', '+', '-', '~', '<', '>', '@', '"'), '', $w) ) . '*';
				}, $words);
				$needle = implode(' ', $words);
			}else{
				$needle = esc_sql( $args['needle'] );
			}

			if($args['type'] === 'entire-posts'){
				$selects[] = "MATCH(".$fa.".`title`) AGAINST('$needle' IN BOOLEAN MODE) + MATCH(".$fa.".`body`) AGAINST('$needle' IN BOOLEAN MODE) AS matches";
				$wheres[] = "( MATCH(".$fa.".`title`, ".$fa.".`body`) AGAINST('$needle' IN BOOLEAN MODE) OR ".$fa.".`title` LIKE '%". esc_sql( $args['needle'] ) ."%' OR ".$fa.".`body` LIKE '%". esc_sql( $args['needle'] ) ."%' )";
				$orders[] = "matches";
				$orders[] = "`created`";
			}elseif($args['type'] === 'titles-only'){
				$selects[] = "MATCH(".$fa.".`title`) AGAINST('$needle' IN BOOLEAN MODE) AS matches";
				$wheres[] = "( MATCH(".$fa.".`title`) AGAINST('$needle' IN BOOLEAN MODE) OR ".$fa.".`title` LIKE '%". esc_sql( $args['needle'] ) ."%' )";
				$orders[] = "matches";
				$orders[] = "`created`";
			}elseif($args['type'] === 'user-posts' || $args['type'] === 'user-topics'){
			    $innerjoins[] = "INNER JOIN `".WPF()->db->users."` u ON u.`ID` = ".$fa.".`userid`";
				$wheres[] = "( u.`user_nicename` LIKE '%{$needle}%' OR u.`display_name` LIKE '%{$needle}%' )";
				if($args['type'] === 'user-topics') $wheres[] = "".$fa.".`is_first_post` = 1";
			}elseif($args['type'] === 'tag'){
                $fa = "t";
				$from = "`".WPF()->tables->topics."` " . $fa;
				$selects = array($fa.'.`first_postid` AS postid', $fa.'.`topicid`', $fa.'.`private`', $fa.'.`status`', $fa.'.`forumid`', $fa.'.`userid`', $fa.'.`title`', $fa.'.`created`', '1 AS `is_first_post`');
				$innerjoins = array();
				$wheres = array( "( ".$fa.".`tags` LIKE '%{$needle}%' )" );
//              $wheres = array( "( FIND_IN_SET('{$needle}', ".$fa.".`tags`) )" ); //exact version
            }
		}

		if( $args['postids'] ) $wheres[] = "(".$fa.".`postid` IN(". implode(',', $args['postids']) ."))";

		if($args['orderby'] === 'date'){
			$orders = array($fa.'.`created`');
		}elseif($args['orderby'] === 'user'){
			$orders = array($fa.'.`userid`');
		}elseif($args['orderby'] === 'forum'){
			$orders = array($fa.'.`forumid`');
		}

		$sql = "SELECT COUNT(*) FROM " . $from . " " . implode(' ', $innerjoins);
		if($wheres) $sql .= " WHERE " . implode( " AND ", $wheres );
		$items_count = (int) WPF()->db->get_var($sql);
		if( $this->options['search_max_results'] && $items_count > $this->options['search_max_results'] ) $items_count = (int) $this->options['search_max_results'];

		$sql = "SELECT " . implode(', ', $selects) . " FROM " . $from . " " . implode(' ', $innerjoins);
		if($wheres) $sql .= " WHERE " . implode( " AND ", $wheres );
		if($orders) $sql .= " ORDER BY " . implode(' ' . $args['order'] . ', ', $orders) . " " . $args['order'];

		if( $this->options['search_max_results'] ) $sql = "SELECT * FROM (" . $sql . " LIMIT ". $this->options['search_max_results'] .") AS p";

		if( $args['row_count'] ) $sql .= " LIMIT " . intval($args['offset']) . "," . intval($args['row_count']);

		$posts = WPF()->db->get_results($sql, ARRAY_A);

		do_action( 'wpforo_search_result_after', $args, $items_count, $posts, $sql );

		foreach($posts as $key => $post){
			if( !WPF()->perm->forum_can( 'vf', $post['forumid'] ) ) unset($posts[$key]);
			if( !WPF()->perm->forum_can( 'vt', $post['forumid'] ) ) unset($posts[$key]);
			if( !$post['is_first_post'] && !WPF()->perm->forum_can( 'vr', $post['forumid'] ) ) unset($posts[$key]);
			if( $post['private'] && !WPF()->perm->forum_can( 'vp', $post['forumid'] ) ) unset($posts[$key]);
			if( $post['status'] && !WPF()->perm->forum_can( 'au', $post['forumid'] ) ) unset($posts[$key]);
		}
		return $posts;
	}
	
	/**
	 *  return likes count by post id
	 * 
	 * Return likes count 
	 *
	 * @since 1.0.0
	 *
	 * @param	int 
	 *
	 * @return	int
	 */
	function get_post_likes_count($postid){
		return WPF()->db->get_var( WPF()->db->prepare( "SELECT COUNT(`likeid`) FROM `".WPF()->tables->likes."` WHERE `postid` = %d", $postid ) );
	}
	
	/**
	 *  return usernames who likes this post
	 * 
	 * Return array with username
	 *
	 * @since 1.0.0
	 *
	 * @param	int
	 *
	 * @return	array
	 */
	function get_likers_usernames($postid){
		return WPF()->db->get_results("SELECT u.ID, u.display_name FROM `".WPF()->tables->likes."` l, `".WPF()->db->users."` u WHERE `l`.`userid` = `u`.ID AND `l`.`postid` = ".intval($postid)." ORDER BY l.`userid` = " . intval(WPF()->current_userid) . " DESC, l.`likeid` DESC LIMIT 3", ARRAY_A);
	}
	
	/**
	 *  return like ID or null
	 * 
	 * @since 1.0.0
	 *
	 * @param	int int
	 *
	 * @return null or like id
	 */
	function is_liked($postid, $userid){
		$returned_value = WPF()->db->get_var("SELECT likeid FROM `".WPF()->tables->likes."` WHERE `postid` = ".intval($postid)." AND `userid` = ".intval($userid) );
		if(is_null($returned_value)){
			return FALSE;	
		}else{
			return $returned_value;
		}
	}
	
	/**
	 *  return votes sum by post id
	 * 
	 * Return votes count 
	 *
	 * @since 1.0.0
	 *
	 * @param	int 
	 *
	 * @return	int
	 */
	function get_post_votes_sum($postid){
		$sum = WPF()->db->get_var("SELECT sum(`reaction`) FROM `".WPF()->tables->votes."` WHERE `postid` = ".intval($postid) );
		if($sum == null){
			$sum = 0;
		}
		return $sum;
	}
	
	/**
	 *  return forum slug
	 * 
	 * string (slug)
	 *
	 * @since 1.0.0
	 *
	 * @param	int
	 *
	 * @return	string or false
	 */
	function get_forumslug_byid($postid){
		
		$cache = WPF()->cache->on('memory_cashe');
		
		if( $cache && isset(self::$cache['forum_slug'][$postid]) ){
			return self::$cache['forum_slug'][$postid];
		}
		
		$slug = WPF()->db->get_var("SELECT `slug` FROM ".WPF()->tables->forums." WHERE `forumid` =(SELECT forumid FROM `".WPF()->tables->topics."` WHERE `topicid` =(SELECT `topicid` FROM `".WPF()->tables->posts."` WHERE postid = ".intval($postid)."))");
		
		if($cache && isset($postid)){
			self::$cache['forum_slug'][$postid] = $slug;
		}
		
		if($slug){
			return $slug;
		}else{
			return FALSE;
		}
	}
	
	/**
	 *  return topic slug
	 * 
	 * string (slug)
	 *
	 * @since 1.0.0
	 *
	 * @param	int
	 *
	 * @return	string or false
	 */
	function get_topicslug_byid( $postid ){
		
		$cache = WPF()->cache->on('memory_cashe');
		
		if( $cache && isset(self::$cache['topic_slug'][$postid]) ){
			return self::$cache['topic_slug'][$postid];
		}
		
		$slug = WPF()->db->get_var("SELECT `slug` FROM ".WPF()->tables->topics." WHERE `topicid` =(SELECT `topicid` FROM `".WPF()->tables->posts."` WHERE postid = ".intval($postid).")");
		
		if($cache && isset($postid)){
			self::$cache['topic_slug'][$postid] = $slug;
		}
		
		if($slug){
			return $slug;
		}else{
			return FALSE;
		}
	}

	/**
	 * return post full url by id
	 *
	 * @since 1.0.0
	 *
	 * @param mixed $arg
	 * @param bool $absolute
	 *
	 * @return string $url
	 */
	function get_post_url( $arg, $absolute = true ) {
		if ( isset( $arg ) && ! is_array( $arg ) ) {
			$postid = wpforo_bigintval( $arg );
			$post   = $this->get_post( $postid, false );
		} elseif ( ! empty( $arg ) && isset( $arg['postid'] ) ) {
			$post   = $arg;
			$postid = $post['postid'];
		}

		if ( ! empty( $post ) && is_array( $post ) && !empty($postid) ) {

		    $forum_slug = (wpfval($post, 'forumid')) ? wpforo_forum($post['forumid'], 'slug') : $this->get_forumslug_byid( $postid );
			$topic_slug = (wpfval($post, 'topicid')) ? wpforo_topic($post['topicid'], 'slug') : $this->get_topicslug_byid( $postid );

			$url = $forum_slug . '/' . $topic_slug;

			if ( $post['topicid'] ) {
				$layout = WPF()->forum->get_layout( $post['forumid'] );
				$pid = $postid;
				if ( $post['parentid'] ) {
					switch ( $layout ) {
						case 3:
							$pid = $post['parentid'];
							break;
						case 4:
							$pid = $post['root'];
							break;
					}
				}

				$where = "";
				$orderby = "`is_first_post` DESC, `created` ASC, `postid` ASC";
				if ( $layout == 3 ) {
					$where   .= " AND NOT p.`parentid` ";
					$orderby = "`is_first_post` DESC, `is_answer` DESC, `votes` DESC, `created` ASC, `postid` ASC";
				} elseif ( $layout == 4 ) {
					$where   .= " AND NOT p.`parentid` ";
				}

				if ( ! wpforo_current_user_is( 'admin' ) && ! wpforo_current_user_is( 'moderator' ) && ! WPF()->perm->forum_can( 'au', $post['forumid'] ) ) {
					if ( WPF()->current_userid ) {
						$where .= " AND ( p.`status` = 0 OR (p.`status` = 1 AND p.`userid` = %d) ) ";
						$where = WPF()->db->prepare( $where, WPF()->current_userid );
					} elseif ( WPF()->current_user_email ) {
						$where .= " AND ( p.`status` = 0 OR (p.`status` = 1 AND p.`email` = %s) ) ";
						$where = WPF()->db->prepare( $where, sanitize_email( WPF()->current_user_email ) );
					} else {
						$where .= " AND NOT p.`status` ";
					}
				}

				$sql = "SELECT tmp_view.`rownum` FROM
							(SELECT @rownum := @rownum + 1 AS rownum, p.`postid`
								FROM `" . WPF()->tables->posts . "` p
								CROSS JOIN ( SELECT @rownum := 0 ) AS init_var
								WHERE p.`topicid` = %d
								" . $where . "
								ORDER BY " . $orderby . ") AS tmp_view
						WHERE tmp_view.`postid` = %d";
				$position = wpforo_bigintval( WPF()->db->get_var( WPF()->db->prepare($sql, $post['topicid'], $pid) ) );

				$items_per_page = $this->get_option_items_per_page($layout);

				if ( $position <= $items_per_page ) {
					return wpforo_home_url( $url, false, $absolute ) . "#post-" . wpforo_bigintval( $postid );
				}
				if ( $position && $items_per_page ) {
					$paged = ceil( $position / $items_per_page );
				} else {
					$paged = 1;
				}

				return wpforo_home_url( $url . "/" . wpforo_get_template_slug('paged') . "/" . $paged, false, $absolute ) . "#post-" . wpforo_bigintval( $postid );
			}
		}

		return wpforo_home_url();
	}


	/**
	 *
	 * @since 1.0.0
	 *
	 * @param int $postid
	 *
	 * @return int
	 */
	function is_answered( $postid ) {
		$is_answered = WPF()->db->get_var( WPF()->db->prepare(
			" SELECT is_answer 
				FROM `" . WPF()->tables->posts . "`
				WHERE postid = %d
			",
			$postid
		) );

		return $is_answered;
	}

    function is_approved( $postid ){
        $post = WPF()->db->get_var( "SELECT `status` FROM ".WPF()->tables->posts." WHERE `postid` = " . intval($postid) );
        if( $post ) return FALSE;
        return TRUE;
    }

	function get_count( $args = array() ){
		$sql = "SELECT SQL_NO_CACHE COUNT(*) FROM `".WPF()->tables->posts."`";
		if($args && is_array($args)){
			$wheres = array();
			foreach ($args as $key => $value)  $wheres[] = "`$key` = '" . esc_sql($value) . "'";
			if($wheres) $sql .= " WHERE " . implode(' AND ', $wheres);
		}
		return WPF()->db->get_var($sql);
	}
	
	function unapproved_count(){
		return WPF()->db->get_var( "SELECT COUNT(*) FROM `".WPF()->tables->posts."` WHERE `status` = 1" );
	}
	
	function get_attachment_id( $filename ){
		$attach_id =  WPF()->db->get_var( "SELECT `post_id` FROM `".WPF()->db->postmeta."` WHERE `meta_key` = '_wp_attached_file' AND `meta_value` LIKE '%" . esc_sql($filename) . "' LIMIT 1");
		return $attach_id;
	}
	
	function delete_attachments( $postid ){
		$post = $this->get_post($postid);
		if( isset($post['body']) && $post['body'] ){
			if( preg_match_all('|\/wpforo\/default_attachments\/([^\s\"\]]+)|is', $post['body'], $attachments, PREG_SET_ORDER) ){
				$upload_dir = wp_upload_dir();
                $default_attachments_dir = $upload_dir['basedir'] . '/wpforo/default_attachments/';
				foreach( $attachments as $attachment ){
					$filename = trim($attachment[1]);
					$file = $default_attachments_dir . $filename;
					if( file_exists($file) ){
						$posts = WPF()->db->get_var( "SELECT COUNT(*) as posts FROM `".WPF()->tables->posts."` WHERE `body` LIKE '%" . esc_sql( $attachment[0] ) . "%'" );
						if( is_numeric($posts) && $posts == 1 ){
							$attachmentid = $this->get_attachment_id( '/' . $filename  );
							if ( !wp_delete_attachment( $attachmentid ) ){
								@unlink($file); 
							}
						}
					}
				}
			}
		}
	}

	public function status( $postid, $status ){

	    if( !$postid = wpforo_bigintval($postid) ) return false;
        if( !$post = $this->get_post( $postid, false ) ) return false;

        if( $post['is_first_post'] ) {
            WPF()->topic->status($post['topicid'], $status);
        }

        if( false !== WPF()->db->update(
            WPF()->tables->posts,
            array( 'status' => intval($status) ),
            array( 'postid' => $postid ),
            array( '%d' ),
            array( '%d' )
        )){
            if( $status ){
                $this->last_post($post, 'remove');
                do_action( 'wpforo_post_unapprove', $post );
			} else {
                $this->last_post($post, 'add');
                do_action( 'wpforo_post_approve', $post );
			}

            do_action( 'wpforo_post_status_update', $postid, $status );
            wpforo_clean_cache('post', $postid, $post);
			WPF()->notice->add('Done!', 'success');
            return true;
        }

        WPF()->notice->add('error: Change Status action', 'error');
        return false;
    }

	public function last_post($post, $action = 'add'){
	    if( is_numeric($post) ) $post = $this->get_post($post, false);
		if( !empty($post) && isset($post['postid']) && isset($post['topicid']) && isset($post['forumid']) && isset($post['userid']) ) {
            if( $action == 'add' ){
	            $answ_incr = '';
	            $comm_incr = '';
	            if ( WPF()->forum->get_layout($post) == 3 ) {
		            if ( $post['parentid'] ) {
			            $comm_incr = ', `comments` = `comments` + 1 ';
		            } else {
			            $answ_incr = ', `answers` = `answers` + 1 ';
		            }
	            }
	            WPF()->db->query( "UPDATE `" . WPF()->tables->profiles . "` SET `posts` = `posts` + 1 $answ_incr $comm_incr WHERE `userid` = " . wpforo_bigintval( $post['userid'] ) );

	            $topic = WPF()->topic->get_topic($post['topicid']);
                WPF()->topic->rebuild_first_last($topic);
                WPF()->topic->rebuild_stats($topic);
                WPF()->forum->rebuild_last_infos($post['forumid']);
                WPF()->forum->rebuild_stats($post['forumid']);
            } elseif( $action == 'remove' ) {
	            $comm_incr = '';
	            $answ_incr = '';
	            $layout = WPF()->forum->get_layout($post);
	            if($layout == 3){
		            if($post['parentid']){
			            $comm_incr = ', `comments` = IF( (`comments` - 1) < 0, 0, `comments` - 1 ) ';
		            }else{
			            $answ_incr = ', `answers` = IF( (`answers` - 1) < 0, 0, `answers` - 1 ) ';
		            }
	            }
	            WPF()->db->query( "UPDATE IGNORE `".WPF()->tables->profiles."` SET `posts` = IF( (`posts` - 1) < 0, 0, `posts` - 1 ) $answ_incr $comm_incr WHERE `userid` = " . wpforo_bigintval($post['userid']) );

                $excerpt = ($post['is_first_post']) ? ' AND `topicid` != ' . intval($post['topicid']) . ' ' : ' AND `postid` != ' . intval($post['postid']) . ' ';
                $last_topic_post = WPF()->db->get_row("SELECT * FROM `".WPF()->tables->posts."` WHERE `topicid` = " . intval($post['topicid']) . " AND `postid` != " . intval($post['postid']) . " AND `status` = 0 AND `private` = 0 ORDER BY `created` DESC, `postid` DESC LIMIT 1", ARRAY_A);
                $last_forum_post = WPF()->db->get_row("SELECT * FROM `".WPF()->tables->posts."` WHERE `forumid` = " . intval($post['forumid']) . " " . $excerpt . " AND `status` = 0 AND `private` = 0 ORDER BY `created` DESC, `postid` DESC LIMIT 1", ARRAY_A);
                if( !empty($last_topic_post) && !$last_topic_post['is_first_post'] ) {
                    $answers = ( !$last_topic_post['parentid'] ) ? ', `answers` = `answers` - 1 ' : '';
                    WPF()->db->query("UPDATE `".WPF()->tables->topics."` SET `last_post` = " . intval($last_topic_post['postid']) . ", `modified` = '" . esc_sql($last_topic_post['created']) . "', `posts` = `posts` - 1 " . $answers . " WHERE `topicid` = " . intval($post['topicid']) );
                }
                if( !empty($last_forum_post) ) {
                    $topics =  ( $last_forum_post['is_first_post'] ) ? ', `topics` = `topics` - 1 ' : '';
                    WPF()->db->query("UPDATE `".WPF()->tables->forums."` SET `last_post_date` = '" . esc_sql($last_forum_post['created']) . "', `last_userid` = " . intval($last_forum_post['userid']). ", `last_topicid` = " . intval($last_forum_post['topicid']) . ", `last_postid` = " . intval($last_forum_post['postid']) . ", `posts` = `posts` - 1 " . $topics . " WHERE `forumid` = " . intval($last_forum_post['forumid']) );
                } else {
                    WPF()->db->query("UPDATE `".WPF()->tables->forums."` SET `last_post_date` = '0000-00-00 00:00:00', `last_userid` = 0, `last_topicid` = 0, `last_postid` = 0, `posts` = 0, `topics` = 0 WHERE `forumid` = " . intval($post['forumid']) );
                }
            }

			WPF()->member->reset($post['userid']);
		}
	}

	public function next_post( $postid, $topicid = 0 ){
		if( !$topicid ) $topicid = wpforo_post($postid, 'topicid');
		if( $topicid ) $next_postid = WPF()->db->get_var("SELECT `postid` FROM `". WPF()->tables->posts ."` WHERE `topicid` = " . intval( $topicid ) ." AND `postid` > " . intval( $postid ) . " AND `status` = 0 ORDER BY `created` ASC LIMIT 1" );
		return intval($next_postid);
	}

	public function get_liked_posts( $args, &$items_count ){

	    $default = array(
            'userid'		=> NULL,
            'order'		    => 'DESC',
            'offset' 		=> NULL,
            'row_count'	    => NULL,
            'where'		    => NULL,
            'var'           => NULL
        );

        $posts = array();
        if(!wpfval($args, 'userid')) return array();
        $args = wpforo_parse_args( $args, $default );
        if(is_array($args) && !empty($args)){
            extract($args, EXTR_OVERWRITE);
            if( $row_count === 0 ) return array();
            $items_count = WPF()->db->get_var("SELECT COUNT(*) FROM `".WPF()->tables->likes."` WHERE `userid` = " . intval($userid) );
            $liked_posts = WPF()->db->get_col("SELECT `postid` FROM `".WPF()->tables->likes."` WHERE `userid` = " . intval($userid) ." ORDER BY `likeid` " . esc_sql($order) . " LIMIT " . intval($offset) . ", " . intval($row_count));
            if(empty($liked_posts)){
                $items_count = WPF()->db->get_var("SELECT COUNT(*) FROM `".WPF()->tables->votes."` WHERE `userid` = " . intval($userid) );
                $liked_posts = WPF()->db->get_col("SELECT `postid` FROM `".WPF()->tables->votes."` WHERE `userid` = " . intval($userid) ." AND `reaction` = 1 ORDER BY `voteid` " . esc_sql($order) . " LIMIT " . intval($offset) . ", " . intval($row_count));
            }
            if(!empty($liked_posts)){
                if($var == 'postid'){
                    return $liked_posts;
                }
                else{
                    $liked_posts = implode(',', $liked_posts);
                    $post_args = array( 'include' => $liked_posts, 'status' => 0, 'private' => 0 );
                    $posts = $this->get_posts( $post_args );
                }
            }
        }
        return $posts;
	}

    public function get_unread_posts($args, $limit = 10){

	    $unread_posts = array();

	    //If the unread post logging is disabled return an empty array.
        if( !wpforo_feature('view-logging') ) return $unread_posts;

        //If there is no information about last read post.
        //Max number recent posts to search unread posts in.
        $args['row_count'] = apply_filters( 'wpforo_max_number_of_unread_posts', 100 );

        //Find the last unread postid, if so, add 'where' condition.
        $last_read_postid = WPF()->log->get_all_read( 'post' );
        if( $last_read_postid ){
            $args['where'] = '`postid` > ' . intval($last_read_postid);
        }

        //Find unread posts based on last read postid's in topics
        $posts = $this->get_posts($args);
        $read_topics = WPF()->log->get_read_topics();
        if( !empty($posts) ){
            if( !empty($read_topics) ){
                foreach( $posts as $key => $post ){
                    if( $key == $limit ) break;
                    if( !wpfkey($post, 'topicid') && $post ){
                        $post_ids = explode(',', $post);
                        if(!empty($post_ids)){
                            foreach( $post_ids as $post_id ){
                                $topicid = wpforo_post($post_id, 'topicid');
                                if( $topicid == wpfval(WPF()->current_object, 'topicid') ) continue;
                                if( wpfkey($read_topics, $topicid) ){
                                    $last_read_postid = $read_topics[ $topicid ];
                                    if( (int) $post_id > (int) $last_read_postid ){
                                        $unread_posts[] = $post_id;
                                    }
                                } else {
                                    $unread_posts[] = $post_id;
                                }
                            }
                        }
                    }
                    elseif( wpfkey($post, 'topicid') && wpfkey($read_topics, $post['topicid']) ){
                        $last_read_postid = $read_topics[ $post['topicid'] ];
                        if( (int) $post['postid'] > (int) $last_read_postid ){
                            $unread_posts[] = $post;
                        }
                    }
                    else {
                        $unread_posts[] = $post;
                    }
                }
            } else {
                $unread_posts = $posts;
            }
        }
        return $unread_posts;
    }

	public function reset_fields(){
		$this->fields = array();
    }

	private function init_fields( $forum = array() ){
		if( $this->fields ) return;
		$all_groupids = WPF()->usergroup->get_usergroups('groupid');
		$all_groupids = array_map('intval', $all_groupids);

		$this->fields = apply_filters( 'wpforo_post_before_init_fields', $this->fields );

		$this->fields['title'] = array(
			'fieldKey'       => 'title',
			'type'           => 'text',
			'isDefault'      => 1,
			'isRemovable'    => 0,
			'isRequired'     => 1,
			'isEditable'     => 1,
			'label'          => wpforo_phrase( 'Topic Title', false ),
			'title'          => wpforo_phrase( 'Title', false ),
			'placeholder'    => wpforo_phrase( 'Enter title here', false ),
			'minLength'      => 0,
			'maxLength'      => 0,
			'faIcon'         => 'fas fa-pen-alt',
			'name'           => 'title',
			'cantBeInactive' => array('topic'),
			'canEdit'        => $all_groupids,
			'canView'        => $all_groupids,
			'can'            => '',
			'isSearchable'   => 1
		);

		/*$this->fields['slug'] = array(
			'fieldKey'       => 'slug',
			'type'           => 'text',
			'isDefault'      => 1,
			'isRemovable'    => 0,
			'isRequired'     => 0,
			'isEditable'     => 1,
			'label'          => wpforo_phrase( 'Slug', false ),
			'title'          => wpforo_phrase( 'Slug', false ),
			'placeholder'    => wpforo_phrase( 'Slug', false ),
			'minLength'      => 0,
			'maxLength'      => 0,
			'faIcon'         => 'fas fa-link',
			'name'           => 'slug',
			'cantBeInactive' => array(),
			'canEdit'        => $all_groupids,
			'canView'        => $all_groupids,
			'can'            => '',
			'isSearchable'   => 1
		);*/

		$this->fields['body'] = array(
			'fieldKey'       => 'body',
			'type'           => 'tinymce',
			'isDefault'      => 1,
			'isRemovable'    => 0,
			'isRequired'     => 1,
			'isEditable'     => 1,
			'title'          => wpforo_phrase( 'Body', false ),
			'placeholder'    => wpforo_phrase( 'Body', false ),
			'minLength'      => 0,
			'maxLength'      => 0,
			'faIcon'         => '',
			'name'           => 'body',
			'cantBeInactive' => array('topic', 'post', 'comment', 'reply'),
			'canEdit'        => $all_groupids,
			'canView'        => $all_groupids,
			'can'            => '',
			'isSearchable'   => 1
		);

		$this->fields['name'] = array(
			'fieldKey'        => 'name',
			'type'            => 'text',
			'isDefault'       => 1,
			'isRemovable'     => 0,
			'isRequired'      => 0,
			'isEditable'      => 1,
			'label'           => wpforo_phrase( 'Author Name', false ),
			'title'           => wpforo_phrase( 'Author Name', false ),
			'placeholder'     => wpforo_phrase( 'Your name', false ),
			'minLength'       => 0,
			'maxLength'       => 0,
			'faIcon'          => 'fas fa-id-card',
			'name'            => 'name',
			'cantBeInactive'  => array(),
			'canEdit'         => $all_groupids,
			'canView'         => $all_groupids,
			'can'             => '',
			'isSearchable'    => 1,
			'isOnlyForGuests' => 1
		);

		$this->fields['email'] = array(
			'fieldKey'        => 'email',
			'type'            => 'text',
			'isDefault'       => 1,
			'isRemovable'     => 0,
			'isRequired'      => 0,
			'isEditable'      => 1,
			'label'           => wpforo_phrase( 'Author Email', false ),
			'title'           => wpforo_phrase( 'Author Email', false ),
			'placeholder'     => wpforo_phrase( 'Your email', false ),
			'minLength'       => 0,
			'maxLength'       => 0,
			'faIcon'          => 'fas fa-at',
			'name'            => 'email',
			'cantBeInactive'  => array(),
			'canEdit'         => $all_groupids,
			'canView'         => $all_groupids,
			'can'             => '',
			'isSearchable'    => 1,
			'isOnlyForGuests' => 1
		);

		/*$this->fields['tags'] = array(
			'fieldKey'       => 'tags',
			'type'           => 'text',
			'isDefault'      => 1,
			'isRemovable'    => 0,
			'isRequired'     => 0,
			'isEditable'     => 1,
			'label'          => wpforo_phrase( 'Topic Tags', false ) . ' ' . wpforo_phrase( 'Separate tags using a comma', false ),
			'title'          => wpforo_phrase( 'Tags', false ),
			'placeholder'    => wpforo_phrase( 'Start typing tags here (maximum %d tags are allowed)...', false ),
			'minLength'      => 0,
			'maxLength'      => 0,
			'faIcon'         => 'fas fa-tag',
			'name'           => 'tags',
			'cantBeInactive' => array(),
			'canEdit'        => $all_groupids,
			'canView'        => $all_groupids,
			'can'            => '',
			'isSearchable'   => 1
		);

		$this->fields['sticky'] = array(
			'fieldKey'       => 'sticky',
			'type'           => 'checkbox',
			'isDefault'      => 1,
			'isRemovable'    => 0,
			'isRequired'     => 0,
			'isEditable'     => 1,
			'label'          => wpforo_phrase( 'Set Topic Sticky', false ),
			'title'          => wpforo_phrase( 'Set Topic Sticky', false ),
			'placeholder'    => wpforo_phrase( 'Set Topic Sticky', false ),
			'faIcon'         => 'fas fa-exclamation',
			'name'           => 'type',
			'values'         => '1 => ' . wpforo_phrase( 'Set Topic Sticky', false ),
			'cantBeInactive' => array(),
			'canEdit'        => $all_groupids,
			'canView'        => $all_groupids,
			'can'            => '',
			'isSearchable'   => 1
		);

		$this->fields['private'] = array(
			'fieldKey'       => 'private',
			'type'           => 'checkbox',
			'isDefault'      => 1,
			'isRemovable'    => 0,
			'isRequired'     => 0,
			'isEditable'     => 1,
			'label'          => wpforo_phrase( 'Private Topic', false ),
			'title'          => wpforo_phrase( 'Only Admins and Moderators can see your private topics.', false ),
			'placeholder'    => wpforo_phrase( 'Private Topic', false ),
			'faIcon'         => 'fas fa-eye-slash',
			'name'           => 'private',
			'values'         => '1 => ' . wpforo_phrase( 'Private Topic', false ),
			'cantBeInactive' => array(),
			'canEdit'        => $all_groupids,
			'canView'        => $all_groupids,
			'can'            => '',
			'isSearchable'   => 1
		);

		$this->fields['subscribe'] = array(
			'fieldKey'       => 'subscribe',
			'type'           => 'checkbox',
			'isDefault'      => 1,
			'isRemovable'    => 0,
			'isRequired'     => 0,
			'isEditable'     => 1,
			'label'          => wpforo_phrase( 'Subscribe to this topic', false ),
			'title'          => wpforo_phrase( 'Subscribe to this topic', false ),
			'placeholder'    => wpforo_phrase( 'Subscribe to this topic', false ),
			'faIcon'         => 'fas fa-eye-slash',
			'name'           => 'wpforo_topic_subs',
			'values'         => '1 => ' . wpforo_phrase( 'Subscribe to this topic', false ),
			'cantBeInactive' => array(),
			'canEdit'        => $all_groupids,
			'canView'        => $all_groupids,
			'can'            => '',
			'isSearchable'   => 0
		);*/

		$this->fields = (array) apply_filters( 'wpforo_post_after_init_fields', $this->fields, $forum );

		$this->fields = array_map(array($this, 'fix_field'), $this->fields);
    }

    public function fix_field($field){
	    $field = array_merge(WPF()->form->default, (array) $field);
	    if( is_scalar($field['values']) ) $field['values'] = explode("\n", $field['values']);
	    return $field;
    }

	public function get_fields($only_defaults = false, $forum = array()){
		$this->init_fields($forum);
		$fields = $this->fields;
		if($only_defaults) foreach ($fields as $k => $v) if( !wpfval($v, 'isDefault') ) unset($fields[$k]);
		return $fields;
	}

	public function get_field($key, $forum = array()){
		if( is_string($key) ){
			$this->init_fields($forum);
			return (array) wpfval($this->fields, $key);
		}elseif( $this->is_field($key) ){
			return $key;
		}
		return array();
	}

	public function is_field($field){
		return wpfval($field, 'fieldKey') && wpfval($field, 'type') && wpfkey($field, 'isDefault');
	}

	public function get_field_key($field) {
		return is_string($field) ? $field : (string) wpfval($field, 'fieldKey');
	}

	public function fields_structure_full_array($fields, &$used_fields = array(), $forum = array() ){
		if(is_string($fields)) $fields = maybe_unserialize($fields);
		$fs = array(array(array()));
		if(!is_array($fields)) return $fs;
		foreach( $fields as $kr => $row ){
			if( is_array($row) ){
				foreach( $row as $kc => $cols ){
					if( is_array($cols) ){
						foreach( $cols as $kf => $field ){
							$used_fields[] = $field_key = $this->get_field_key($field);
							$fs[$kr][$kc][$field_key] = $this->get_field($field, $forum);
						}
					}
				}
			}
		}
		return $fs;
	}

	// -- START -- get topic fields
	public function get_topic_fields_structure($only_defaults = false, $forum = array(), $guest = false){
		if( $guest ) {
			$fields[0][0][0] = 'name';
			$fields[0][1][0] = 'email';
		}
		$fields[][] = array('title','body');
		if(!$only_defaults) $fields = apply_filters('wpforo_get_topic_fields_structure', $fields, $forum, $guest);
		return $fields;
	}

	public function get_topic_fields($forum, $values = array(), $guest = false ){
		$fields = $this->fields_structure_full_array( $this->get_topic_fields_structure(false, $forum, $guest), $used_fields, $forum );
		if( !in_array('title', $used_fields, true) ) $fields[][][] = $this->get_field('title', $forum);
		if( !in_array('body',  $used_fields, true) ) $fields[][][] = $this->get_field('body', $forum);

		/**
		 * apply old options to fields
		 */
		$values = wp_slash($values);
		foreach( $fields as $kr => $row ){
			foreach( $row as $kc => $cols ){
				foreach( $cols as $kf => $field ){
					if( $field ){
						$field['value'] = wpfval($values, $kf);
						switch ($kf){
							case 'body':
								if( $field['type'] === 'tinymce' ) {
									$field['wp_editor_settings'] = WPF()->tpl->editor_buttons( 'topic' );
								}
								$field['textareaid'] = uniqid('wpf_topic_body_');
								$field['minLength'] = $this->options['topic_body_min_length'];
								$field['maxLength'] = $this->options['topic_body_max_length'];
								$field['form_type'] = 'topic';
								$field['meta'] = array( 'forum' => $forum, 'values' => $values );
							break;
							case 'title':
								if( intval($forum['cat_layout']) === 3 ) $field['label'] = wpforo_phrase('Your question', false);
							break;
						}
						if( $field && intval($field['isOnlyForGuests']) || in_array($kf, array('name', 'email'), true) ){
							if( $guest ){
								if( !$values ){
									$g = WPF()->member->get_guest_cookies();
									if( $kf === 'name' ){
										$field['value'] = $g['name'];
									}elseif( $kf === 'email' ){
										$field['value'] = $g['email'];
									}
								}
							}else{
								$field = array();
							}
						}
						$field = apply_filters( 'wpforo_topic_field', $field, $forum, $values, $guest );
					}

					if($field){
						$fields[$kr][$kc][$kf] = $field;
					}else{
						unset($fields[$kr][$kc][$kf]);
						if( !$fields[$kr][$kc] ) {
							unset( $fields[$kr][$kc] );
							if( !$fields[$kr] ) unset($fields[$kr]);
						}
					}

				}
			}
		}

		return $fields;
	}

	public function get_topic_fields_list($only_defaults = false, $forum = array(), $guest = false){
		$fields_list = array('title', 'body');
		$fields_structure = $this->get_topic_fields_structure($only_defaults, $forum, $guest);
		foreach ( $fields_structure as $r ){
			foreach ( $r as $c ){
				foreach ($c as $f){
					$fields_list[] = $f;
				}
			}
		}
		return array_values(array_unique($fields_list));
	}
	// -- END -- get topic fields

	// -- START -- get post fields
	public function get_post_fields_structure($only_defaults = false, $layout = 1, $guest = false){
		if( $guest ) {
			$fields[0][0][0] = 'name';
			$fields[0][1][0] = 'email';
		}
		$fields[][] = array('title','body');
		if(!$only_defaults) $fields = apply_filters('wpforo_get_post_fields_structure', $fields, $layout, $guest);
		return $fields;
	}

	public function get_post_fields($topic, $values = array(), $guest = false){
		$fields = $this->fields_structure_full_array( $this->get_post_fields_structure(false, $topic['layout'], $guest), $used_fields );
		if( !in_array('body',  $used_fields, true) ) $fields[][][] = $this->get_field('body');

		/**
		 * apply old options to fields
		 */
		$values = wp_slash($values);
		foreach( $fields as $kr => $row ){
			foreach( $row as $kc => $cols ){
				foreach( $cols as $kf => $field ){
					if( $field ){
						$field['value'] = wpfval($values, $kf);
						switch ($kf){
							case 'body':
								if( $field['type'] === 'tinymce' ) {
									$field['wp_editor_settings'] = WPF()->tpl->editor_buttons( 'post' );
								}
								$field['textareaid'] = uniqid('wpf_post_body_');
								$field['minLength'] = $this->options['post_body_min_length'];
								$field['maxLength'] = $this->options['post_body_max_length'];
								$field['form_type'] = 'reply';
								$field['meta'] = array('topic' => $topic, 'values' => $values);
							break;
							case 'title':
								$prefix_answer = wpforo_phrase('Answer to', false, 'default');
								$prefix_re = wpforo_phrase('RE', false, 'default');
								$prefix_patterns = array($prefix_answer,$prefix_re);
								$pattern = array_map('preg_quote', $prefix_patterns);
								$pattern = implode('|', $pattern);
								$title = preg_replace('#^\s*(?:'. $pattern .')\s*: #isu', '', trim($field['value']), 1);
								if( intval($topic['layout']) === 3 ) {
									$field['label'] = wpforo_phrase( 'Your question', false );
									if($title) $field['value'] = $prefix_answer . ': ' . $title;
								}else{
									$field['label'] = wpforo_phrase( 'Title', false );
									if($title) $field['value'] = $prefix_re . ': ' . $title;
								}
							break;
						}
						if( $field && intval($field['isOnlyForGuests']) || in_array($kf, array('name', 'email'), true) ){
							if( $guest ){
								if( !$values || (count($values) === 1 && wpfkey($values, 'title')) ){
									$g = WPF()->member->get_guest_cookies();
									if( $kf === 'name' ){
										$field['value'] = $g['name'];
									}elseif( $kf === 'email' ){
										$field['value'] = $g['email'];
									}
								}
							}else{
								$field = array();
							}
						}
						$fields[$kr][$kc][$kf] = apply_filters( 'wpforo_post_field', $field, $topic, $values, $guest );
					}
				}
			}
		}

		return $fields;
	}

	public function get_post_fields_list($only_defaults = false, $layout = 1, $guest = false){
		$fields_list = array('body');
		$fields_structure = $this->get_post_fields_structure($only_defaults, $layout, $guest);
		foreach ( $fields_structure as $r ){
			foreach ( $r as $c ){
				foreach ($c as $f){
					$fields_list[] = $f;
				}
			}
		}
		return array_values(array_unique($fields_list));
	}
	// -- END -- get post fields

	// -- START -- get QA comment fields
	public function get_comment_fields_structure($only_defaults = false, $guest = false){
		if( $guest ) {
			$fields[0][0][0] = 'name';
			$fields[0][1][0] = 'email';
		}
		$fields[][] = array('body');
		if(!$only_defaults) $fields = apply_filters('wpforo_get_comment_fields_structure', $fields, $guest);
		return $fields;
	}

	public function get_comment_fields($only_defaults = false, $guest = false){
		$fields = $this->fields_structure_full_array( $this->get_comment_fields_structure($only_defaults, $guest), $used_fields );
		if( !$only_defaults ){
			if( !in_array('body',  $used_fields, true) ) $fields[][][] = $this->get_field('body');
		}

		/**
		 * apply old options to fields
		 */
		foreach( $fields as $kr => $row ){
			foreach( $row as $kc => $cols ){
				foreach( $cols as $kf => $field ){
					if( $kf === 'body' && $field ){
						if( $field['type'] === 'tinymce' ) $field['wp_editor_settings'] = WPF()->tpl->editor_buttons('post');
						$field['type'] = WPF()->tpl->forms['qa_comments_rich_editor'] ? 'tinymce' : 'textarea';
						$field['textareaid'] = uniqid('wpf_post_body_');
						$field['minLength'] = $this->options['comment_body_min_length'];
						$field['maxLength'] = $this->options['comment_body_max_length'];
						$fields[$kr][$kc][$kf] = $field;
					}
				}
			}
		}

		return $fields;
	}

	public function get_comment_fields_list($only_defaults = false, $guest = false){
		$fields_list = array('body');
		$fields_structure = $this->get_comment_fields_structure($only_defaults, $guest);
		foreach ( $fields_structure as $r ){
			foreach ( $r as $c ){
				foreach ($c as $f){
					$fields_list[] = $f;
				}
			}
		}
		return array_values(array_unique($fields_list));
	}
	// -- END -- get QA comment fields

	// -- START -- get threaded reply fields
	public function get_reply_fields_structure($only_defaults = false, $guest = false){
		if( $guest ) {
			$fields[0][0][0] = 'name';
			$fields[0][1][0] = 'email';
		}
		$fields[][] = array('body');
		if(!$only_defaults) $fields = apply_filters('wpforo_get_reply_fields_structure', $fields, $guest);
		return $fields;
	}

	public function get_reply_fields($only_defaults = false, $guest = false){
		$fields = $this->fields_structure_full_array( $this->get_reply_fields_structure($only_defaults, $guest), $used_fields );
		if( !$only_defaults ){
			if( !in_array('body',  $used_fields, true) ) $fields[][][] = $this->get_field('body');
		}

		/**
		 * apply old options to fields
		 */
		foreach( $fields as $kr => $row ){
			foreach( $row as $kc => $cols ){
				foreach( $cols as $kf => $field ){
					if( $kf === 'body' && $field ){
						if( $field['type'] === 'tinymce' ) $field['wp_editor_settings'] = WPF()->tpl->editor_buttons('post');
						$field['type'] = WPF()->tpl->forms['threaded_reply_rich_editor'] ? 'tinymce' : 'textarea';
						$field['textareaid'] = uniqid('wpf_post_body_');
						$field['minLength'] = $this->options['post_body_min_length'];
						$field['maxLength'] = $this->options['post_body_max_length'];
						$fields[$kr][$kc][$kf] = $field;
					}
				}
			}
		}

		return $fields;
	}

	public function get_reply_fields_list($only_defaults = false, $guest = false){
		$fields_list = array('body');
		$fields_structure = $this->get_reply_fields_structure($only_defaults, $guest);
		foreach ( $fields_structure as $r ){
			foreach ( $r as $c ){
				foreach ($c as $f){
					$fields_list[] = $f;
				}
			}
		}
		return array_values(array_unique($fields_list));
	}
	// -- END -- get threaded reply fields

	public function get_search_fields($values){
		$values = (array) $values;
		$values = wp_slash($values);

		$topic_fields = WPF()->post->get_topic_fields_list();
		$topic_fields = array_flip($topic_fields);
		$fields = $this->get_fields();
		$fields = array_intersect_key($fields, $topic_fields);

		$search_fields = array();
		foreach ( $fields as $kf => $field ){
			if( !$field['isDefault'] && (int) wpfval($field, 'isSearchable') && !(int) wpfval($field, 'isOnlyForGuests') && wpfval($field, 'type') !== 'file' ){
				$field['value'] = wpfval($values, $kf);
				if( in_array( $field['type'], array( 'text', 'textarea', 'email', 'url' ), true ) ) $field['type'] = 'search';
				$search_fields[0][0][$field['fieldKey']] = $field;
			}
		}
		return $search_fields;
	}

	public function print_custom_fields($content, $post){
		if( (int) wpfval($post, 'is_first_post') && ($postmetas = WPF()->postmeta->get_postmeta($post['postid'], '', true)) ){
			$content .= '<div class="wpf-topic-fields">';
			$content .= apply_filters('wpforo_topic_fields_before', '', $post);
			$forum = WPF()->forum->get_forum($post['forumid']);
			$fields = WPF()->post->get_topic_fields_list(false, $forum);
			foreach ( $fields as $field ){
				if( $postmeta = wpfval($postmetas, $field) ){
					$field = WPF()->post->get_field($field, $forum);
					if( !(int) wpfval($field, 'isDefault') ){
						$field['value'] = $postmeta;
						$field = WPF()->form->prepare_values( WPF()->form->esc_field($field) );
						$content .= sprintf(
							'<div class="wpf-topic-field"><div class="wpf-topic-field-label"> <i class="%1$s"></i> %2$s</div><div class="wpf-topic-field-value">%3$s</div></div>',
							(string) wpfval($field, 'faIcon'),
							(string) wpfval($field, 'label'),
							(string) wpfval($field, 'value')
						);
					}
				}
			}
			$content .= '</div>';
		}
		return $content;
	}
}