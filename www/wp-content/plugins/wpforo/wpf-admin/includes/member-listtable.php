<?php
if(!class_exists('WP_List_Table')){
	require_once( ABSPATH . 'wp-admin/includes/class-wp-screen.php' );
	require_once( ABSPATH . 'wp-admin/includes/screen.php' );
	require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
	require_once( ABSPATH . 'wp-admin/includes/template.php' );
}

class wpForoMembersListTable extends WP_List_Table {

    public $wpfitems_count;

	/** ************************************************************************
	 * REQUIRED. Set up a constructor that references the parent constructor. We
	 * use the parent reference to set some default configs.
	 ***************************************************************************/
	function __construct(){
		//Set parent defaults
		parent::__construct( array(
			'singular'  => 'member',     //singular name of the listed records
			'plural'    => 'members',    //plural name of the listed records
			'ajax'      => false        //does this table support ajax?
		) );

	}


	/** ************************************************************************
	 * Recommended. This method is called when the parent class can't find a method
	 * specifically build for a given column. Generally, it's recommended to include
	 * one method for each column you want to render, keeping your package class
	 * neat and organized. For example, if the class needs to process a column
	 * named 'title', it would first see if a method named $this->column_title()
	 * exists - if it does, that method will be used. If it doesn't, this one will
	 * be used. Generally, you should try to use custom column methods as much as
	 * possible.
	 *
	 * Since we have defined a column_title() method later on, this method doesn't
	 * need to concern itself with any column with a name of 'title'. Instead, it
	 * needs to handle everything else.
	 *
	 * For more detailed insight into how columns are handled, take a look at
	 * WP_List_Table::single_row_columns()
	 *
	 * @param array $item A singular item (one full row's worth of data)
	 * @param string $column_name The name/slug of the column to be processed
	 * @return string Text or HTML to be placed inside the column <td>
	 **************************************************************************/
	function column_default( $item, $column_name ) {
		switch ( $column_name ) {
			case 'groupid':
				$usergroup = WPF()->usergroup->get_usergroup($item[ $column_name ]);
				return !empty( $usergroup['name'] ) ? esc_html($usergroup['name']) : __('default', 'wpforo');
            case 'blog_posts':
                return WPF()->member->blog_posts($item['userid']);
            case 'blog_comments':
                return WPF()->member->blog_comments($item['userid'], $item['user_email']);
			default:
				return $item[ $column_name ];
		}
	}


	/** ************************************************************************
	 * Recommended. This is a custom column method and is responsible for what
	 * is rendered in any column with a name/slug of 'title'. Every time the class
	 * needs to render a column, it first looks for a method named
	 * column_{$column_title} - if it exists, that method is run. If it doesn't
	 * exist, column_default() is called instead.
	 *
	 * This example also illustrates how to implement rollover actions. Actions
	 * should be an associative array formatted as 'slug'=>'link html' - and you
	 * will need to generate the URLs yourself. You could even ensure the links
	 *
	 *
	 * @see WP_List_Table::::single_row_columns()
	 * @param array $item A singular item (one full row's worth of data)
	 * @return string Text to be placed inside the column <td> (movie title only)
	 **************************************************************************/
	function column_userid($item){
		$edit_user = admin_url( 'user-edit.php?user_id=' . intval($item['userid']));
		$edit_profile = WPF()->member->get_profile_url($item['userid'], 'account');
		$ban = wp_nonce_url(admin_url( sprintf('admin.php?page=%1$s&wpfaction=%2$s&userid=%3$s','wpforo-members', 'wpforo_user_ban', $item['userid']) ), 'wpforo-user-ban-' . $item['userid']);
		$unban = wp_nonce_url(admin_url( sprintf('admin.php?page=%1$s&wpfaction=%2$s&userid=%3$s','wpforo-members', 'wpforo_user_unban', $item['userid']) ), 'wpforo-user-unban-' . $item['userid']);
		$delete = wp_nonce_url( "users.php?action=delete&user=".intval($item['userid']), 'bulk-users' );
		$actions = array(
			'edit_user'    => '<a href="' . esc_url($edit_user) . '">' . __( 'Edit User', 'wpforo' ) . '</a>',
			'edit_profile' => '<a href="' . esc_url($edit_profile) . '">' . __( 'Edit Profile', 'wpforo' ) . '</a>',
			'ban'          => '<a href="' . esc_url($ban) . '" style="color: orange;" title="'. __('Ban User', 'wpforo') .'" onclick="return confirm(\'' . __('Are you sure, you want to BAN this user?', 'wpforo') . '\')">' . __( 'Ban', 'wpforo' ) . '</a>',
			'unban'        => '<a href="' . esc_url($unban) . '" style="color: orange;" title="'. __('Unban User', 'wpforo') .'" onclick="return confirm(\'' . __('Are you sure, you want to UNBAN this user?', 'wpforo') . '\')">' . __( 'Unban', 'wpforo' ) . '</a>',
			'delete'       => '<a href="' . esc_url($delete) . '" title="'. __('Delete User', 'wpforo') .'">' . __( 'Delete', 'wpforo' ) . '</a>',
		);

		if( !WPF()->perm->usergroup_can('em') ){
		    unset($actions['edit_user']);
		    unset($actions['edit_profile']);
		}
		if( !WPF()->perm->usergroup_can('bm') || intval($item['userid']) === intval(WPF()->current_userid) ){
			unset($actions['ban']);
			unset($actions['unban']);
		}
		if( $item['status'] === 'banned' ){
			unset($actions['ban']);
        }elseif ( $item['status'] !== 'banned' ){
			unset($actions['unban']);
        }
		if( !WPF()->perm->usergroup_can('dm') || intval($item['userid']) === intval(WPF()->current_userid) ){
			unset($actions['delete']);
        }

		//Return the title contents
		return sprintf('%1$s %2$s',
			/*$1%s*/ $item['userid'],
			/*$2%s*/ $this->row_actions($actions)
		);
	}


	/** ************************************************************************
	 * REQUIRED if displaying checkboxes or using bulk actions! The 'cb' column
	 * is given special treatment when columns are processed. It ALWAYS needs to
	 * have it's own method.
	 *
	 * @see WP_List_Table::::single_row_columns()
	 * @param array $item A singular item (one full row's worth of data)
	 * @return string Text to be placed inside the column <td> (movie title only)
	 **************************************************************************/
	function column_cb($item){
		return sprintf(
			'<input type="checkbox" name="%1$s[]" value="%2$s" %3$s />',
			/*$1%s*/ 'userids',  //Let's simply repurpose the table's singular label ("movie")
			/*$2%s*/ $item['userid'],         //The value of the checkbox should be the record's id
            ( intval($item['userid']) === intval(WPF()->current_userid) ) ? 'disabled' : ''
		);
	}


	/** ************************************************************************
	 * REQUIRED! This method dictates the table's columns and titles. This should
	 * return an array where the key is the column slug (and class) and the value
	 * is the column's title text. If you need a checkbox for bulk actions, refer
	 * to the $columns array below.
	 *
	 * The 'cb' column is treated differently than the rest. If including a checkbox
	 * column in your table you must create a column_cb() method. If you don't need
	 * bulk actions or checkboxes, simply leave the 'cb' entry out of your array.
	 *
	 * @see WP_List_Table::::single_row_columns()
	 * @return array An associative array containing column information: 'slugs'=>'Visible Titles'
	 **************************************************************************/
	function get_columns(){
		$columns = array(
			'cb'            => '<input type="checkbox" />', //Render a checkbox instead of text
			'userid'        => __( 'ID', 'wpforo' ),
			'display_name'  => __( 'Display Name', 'wpforo' ),
			'user_login'    => __( 'Login', 'wpforo' ),
			'user_email'    => __( 'Email', 'wpforo' ),
			'groupid'       => __( 'Group', 'wpforo' ),
			'status'        => __( 'Status', 'wpforo' ),
			'last_login'    => __( 'Last Login', 'wpforo' ),
			'posts'         => __( 'Forum Posts', 'wpforo' ),
			'blog_posts'    => __( 'Blog Posts', 'wpforo' ),
			'blog_comments' => __( 'Blog Comments', 'wpforo' )
		);
		return $this->unset_not_permitted_cols($columns);
	}


	/** ************************************************************************
	 * Optional. If you want one or more columns to be sortable (ASC/DESC toggle),
	 * you will need to register it here. This should return an array where the
	 * key is the column that needs to be sortable, and the value is db column to
	 * sort by. Often, the key and value will be the same, but this is not always
	 * the case (as the value is a column name from the database, not the list table).
	 *
	 * This method merely defines which columns should be sortable and makes them
	 * clickable - it does not handle the actual sorting. You still need to detect
	 * the ORDERBY and ORDER querystring variables within prepare_items() and sort
	 * your data accordingly (usually by modifying your query).
	 *
	 * @return array An associative array containing all the columns that should be sortable: 'slugs'=>array('data_values',bool)
	 **************************************************************************/
	function get_sortable_columns() {
		$columns = array(
			'userid'       => array( 'userid', false ),     //true means it's already sorted
			'display_name' => array( 'display_name', false ),
			'user_login'   => array( 'user_login', false ),
			'user_email'   => array( 'user_email', false ),
			'groupid'      => array( 'groupid', false ),
			'status'       => array( 'status', false ),
			'last_login'   => array( 'last_login', false ),
			'posts'        => array( 'posts', false )
		);
		return $this->unset_not_permitted_cols($columns);
	}

	private function unset_not_permitted_cols($columns){
		if( !WPF()->perm->usergroup_can('vmu') ){
			unset($columns['user_login']);
		}
		if( !WPF()->perm->usergroup_can('vmm') ){
			unset($columns['user_email']);
		}
		if( !WPF()->perm->usergroup_can('vmg') ){
			unset($columns['groupid']);
		}
		if( !WPF()->perm->usergroup_can('bm') ){
			unset($columns['status']);
		}
		if( !WPF()->perm->usergroup_can('vms') ){
			unset($columns['signature']);
		}
	    return $columns;
    }


	/** ************************************************************************
	 * Optional. If you need to include bulk actions in your list table, this is
	 * the place to define them. Bulk actions are an associative array in the format
	 * 'slug'=>'Visible Title'
	 *
	 * If this method returns an empty value, no bulk action will be rendered. If
	 * you specify any bulk actions, the bulk actions box will be rendered with
	 * the table automatically on display().
	 *
	 * Also note that list tables are not automatically wrapped in <form> elements,
	 * so you will need to create those manually in order for bulk actions to function.
	 *
	 * @return array An associative array containing all the bulk actions: 'slugs'=>'Visible Titles'
	 **************************************************************************/
	function get_bulk_actions() {
		$actions = array(
			'ban'    => __( 'Ban', 'wpforo' ),
			'unban'  => __( 'Unban', 'wpforo' ),
			'delete' => __( 'Delete', 'wpforo' )
		);
		if( !WPF()->perm->usergroup_can('bm') ){
			unset($actions['ban']);
			unset($actions['unban']);
		}
		if( !WPF()->perm->usergroup_can('dm') ){
			unset($actions['delete']);
		}
		return $actions;
	}

	function extra_tablenav( $which ) {
	    $name = 'new_groupid' . ( $which === 'bottom' ? '2' : '' );
		printf(
        '<div class="alignleft actions">
                    <label class="screen-reader-text" for="new_role">%1$s</label>
                    <select name="%2$s" id="new_role">
                        <option value="-1">%1$s</option>
                        %3$s
                    </select>
                    <input type="submit" name="change_group" class="button" value="%4$s">
                </div>',
            __('Change usergroup to…', 'wpforo'),
			$name,
            WPF()->usergroup->get_selectbox(array(), 4),
            __('Change', 'wpforo')
        );
    }


	/** ************************************************************************
	 * REQUIRED! This is where you prepare your data for display. This method will
	 * usually be used to query the database, sort and filter the data, and generally
	 * get it ready to be displayed. At a minimum, we should set $this->items and
	 * $this->set_pagination_args(), although the following properties and methods
	 * are frequently interacted with here...
	 *
	 * @global WPDB $wpdb
	 * @uses $this->_column_headers
	 * @uses $this->items
	 * @uses $this->get_columns()
	 * @uses $this->get_sortable_columns()
	 * @uses $this->get_pagenum()
	 * @uses $this->set_pagination_args()
	 **************************************************************************/
	function prepare_items() {
		/**
		 * First, lets decide how many records per page to show
		 */
		$per_page = get_option('wpforo_count_per_page', 10);


		/**
		 * REQUIRED. Now we need to define our column headers. This includes a complete
		 * array of columns to be displayed (slugs & titles), a list of columns
		 * to keep hidden, and a list of columns that are sortable. Each of these
		 * can be defined in another method (as we've done here) before being
		 * used to build the value for our _column_headers property.
		 */
		$columns = $this->get_columns();
		$hidden = array();
		$sortable = $this->get_sortable_columns();


		/**
		 * REQUIRED. Finally, we build an array to be used by the class for column
		 * headers. The $this->_column_headers property takes an array which contains
		 * 3 other arrays. One for all columns, one for hidden columns, and one
		 * for sortable columns.
		 */
		$this->_column_headers = array($columns, $hidden, $sortable);

		$search_fields = array(
			'title'        => 'title',
			'display_name' => 'display_name',
			'user_login'   => 'user_login',
			'user_email'   => 'user_email',
			'signature'    => 'signature'
		);
		$search_fields = $this->unset_not_permitted_cols($search_fields);

		/**
		 * REQUIRED. Now we can add our *sorted* data to the items property, where
		 * it can be used by the rest of the class.
		 */
		$args = array();
		if( $s = wpfval($_REQUEST, 's') ) $args['include'] = WPF()->member->search($s, $search_fields);
		$orderby = wpfval($_REQUEST, 'orderby');
		$order   = strtoupper( wpfval($_REQUEST, 'order') );
		$filter_by_group  = $this->get_filter_by_group_var();
		$filter_by_status = $this->get_filter_by_status_var();
		if( $filter_by_group  !== -1 )                 $args['groupid']  = $filter_by_group;
		if( $filter_by_status !== -1 )                 $args['status']  = (array) sanitize_text_field($filter_by_status);
		if( array_key_exists($orderby, $sortable) )    $args['orderby'] = sanitize_text_field($orderby);
		if( in_array( $order, array('ASC', 'DESC') ) ) $args['order']   = sanitize_text_field($order);

		$paged = $this->get_pagenum();
		$args['offset'] = ($paged - 1) * $per_page;
		$args['row_count'] = $per_page;

		$items_count = 0;
		$this->items = ( isset($args['include']) && empty($args['include']) ? array() : WPF()->member->get_members($args, $items_count) );

		$this->wpfitems_count = $items_count;

		$this->set_pagination_args( array(
			'total_items' => $items_count,                  //WE have to calculate the total number of items
			'per_page'    => $per_page,                     //WE have to determine how many items to show on a page
			'total_pages' => ceil($items_count/$per_page)   //WE have to calculate the total number of pages
		) );
	}

	public function get_filter_by_group_var(){
		$filter_by_group = wpfval($_REQUEST, 'filter_by_group');
		if( !is_null($filter_by_group) && $filter_by_group !== '-1' ){
			$groupid = $filter_by_group;
		}else{
			$groupid = -1;
		}
		return intval($groupid);
    }

    public function get_filter_by_status_var(){
	    $filter_by_status = wpfval($_REQUEST, 'filter_by_status');
	    if( !is_null($filter_by_status) && $filter_by_status !== '-1' ){
	    	$status = $filter_by_status;
	    }else{
		    $status = -1;
	    }
	    return $status;
    }

	public function groups_dropdown(){ ?>
		<select name="filter_by_group">
			<option value="-1">-- <?php _e('filter by group', 'wpforo') ?> --</option>
			<?php
            $selected = $this->get_filter_by_group_var();
			foreach(WPF()->usergroup->get_usergroups() as $group){
				$group['groupid'] = intval($group['groupid']);
				if( $group['groupid'] !== 4 ){
					printf(
						'<option value="%1$s" %2$s>%3$s</option>',
						$group['groupid'],
						($group['groupid'] === $selected) ? 'selected' : '',
						esc_html($group['name'])
					);
                }
			}
			?>
		</select>
		<?php
	}

	public function status_dropdown(){ ?>
        <select name="filter_by_status">
            <option value="-1">-- <?php _e('filter by status', 'wpforo') ?> --</option>
	        <?php
	        if( $statuses = WPF()->member->get_distinct_status() ){
		        $selected = $this->get_filter_by_status_var();
		        foreach( $statuses as $status ){
			        printf(
				        '<option value="%1$s" %2$s>%3$s</option>',
				        esc_attr($status),
				        ($status === $selected) ? 'selected' : '',
				        esc_html($status)
			        );
		        }
	        }
	        ?>
        </select>
		<?php
	}
}