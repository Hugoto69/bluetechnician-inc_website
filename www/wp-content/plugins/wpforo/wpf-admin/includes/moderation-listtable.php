<?php
if(!class_exists('WP_List_Table')){
	require_once( ABSPATH . 'wp-admin/includes/class-wp-screen.php' );
	require_once( ABSPATH . 'wp-admin/includes/screen.php' );
	require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
	require_once( ABSPATH . 'wp-admin/includes/template.php' );
}

class wpForoModeratonsListTable extends WP_List_Table {

    public $wpfitems_count;

	/** ************************************************************************
	 * REQUIRED. Set up a constructor that references the parent constructor. We
	 * use the parent reference to set some default configs.
	 ***************************************************************************/
	function __construct(){
		//Set parent defaults
		parent::__construct( array(
			'singular'  => 'moderation',     //singular name of the listed records
			'plural'    => 'moderations',    //plural name of the listed records
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
			case 'userid':
				$userdata = get_userdata( $item[ $column_name ] );
				return ( ! empty( $userdata->user_nicename ) ? urldecode( $userdata->user_nicename ) : $item[ $column_name ] );
			case 'is_first_post':
				return ( $item[ $column_name ] ) ? __( 'TOPIC', 'wpforo' ) : __( 'POST', 'wpforo' );
			case 'private':
				return ( $item[ $column_name ] ) ? __( 'YES', 'wpforo' ) : __( 'NO', 'wpforo' );
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
	function column_postid($item){
		$vhref = WPF()->moderation->get_view_url($item['postid']);
		//Build row actions
		$actions = array( 'view'      => '<a href="' . $vhref . '" target="_blank">'.__('View', 'wpforo').'</a>' );
		if( $this->get_filter_by_status_var() ){
			$ahref = wp_nonce_url(admin_url( sprintf('admin.php?page=%1$s&wpfaction=%2$s&postid=%3$s','wpforo-moderations', 'wpforo_dashboard_post_approve', $item['postid']) ), 'wpforo-approve-post-' . $item['postid']);
			$actions['wpfapprove'] = '<a href="' . $ahref . '">'. __('Approve', 'wpforo') .'</a>';
		}else{
			$uhref = wp_nonce_url(admin_url( sprintf('admin.php?page=%1$s&wpfaction=%2$s&postid=%3$s','wpforo-moderations', 'wpforo_dashboard_post_unapprove', $item['postid']) ), 'wpforo-unapprove-post-' . $item['postid']);
			$actions['wpfunapprove'] = '<a href="' . $uhref . '">'. __('Unapprove', 'wpforo') .'</a>';
		}
		$dhref = wp_nonce_url(admin_url( sprintf('admin.php?page=%1$s&wpfaction=%2$s&postid=%3$s','wpforo-moderations', 'wpforo_dashboard_post_delete', $item['postid']) ), 'wpforo-delete-post-' . $item['postid']);
		$actions['delete'] = '<a onclick="return confirm(\'' . __( "Are you sure you want to DELETE this item?", 'wpforo' ) . '\');" href="' . $dhref . '">' . __('Delete', 'wpforo') . '</a>';

		//Return the title contents
		return sprintf('%1$s %2$s',
			/*$1%s*/ $item['postid'],
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
			'<input type="checkbox" name="%1$s[]" value="%2$s" />',
			/*$1%s*/ 'postids',  //Let's simply repurpose the table's singular label ("movie")
			/*$2%s*/ $item['postid']         //The value of the checkbox should be the record's id
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
		return array(
			'cb'            => '<input type="checkbox" />', //Render a checkbox instead of text
			'postid'        => __('ID',         'wpforo'),
			'title'         => __('Title',      'wpforo'),
			'is_first_post' => __('Type',       'wpforo'),
			'userid'        => __('Created By', 'wpforo'),
			'created'       => __('Created',    'wpforo'),
			'private'       => __('Private',    'wpforo')
		);
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
		return array(
			'postid'        => array( 'postid',        false ),     //true means it's already sorted
			'title'         => array( 'title',         false ),
			'is_first_post' => array( 'is_first_post', false ),
			'userid'        => array( 'userid',        false ),
			'created'       => array( 'created',       false ),
			'private'       => array( 'private',       false )
		);
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
		$bulk_actions = array();
		if( $this->get_filter_by_status_var() ){
			$bulk_actions['approve'] = __('Approve', 'wpforo');
		}else{
			$bulk_actions['unapprove'] = __('Unapprove', 'wpforo');
		}
		$bulk_actions['delete'] = __('Delete', 'wpforo');
		return $bulk_actions;
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

		/**
		 * REQUIRED. Now we can add our *sorted* data to the items property, where
		 * it can be used by the rest of the class.
		 */
		$args = array('status' => $this->get_filter_by_status_var(), 'orderby' => '`created` DESC, `postid` DESC');
		if( $s = wpfval($_REQUEST, 's') ){
			$args['include'] = WPF()->moderation->search($s);
		}
		$filter_by_userid = $this->get_filter_by_userid_var();
		$orderby = wpfval($_REQUEST, 'orderby');
		$order = strtoupper( wpfval($_REQUEST, 'order') );
		if( $filter_by_userid !== -1 )                 $args['userid']  = $filter_by_userid;
		if( array_key_exists($orderby, $sortable) )    $args['orderby'] = sanitize_text_field($orderby);
		if( in_array( $order, array('ASC', 'DESC') ) ) $args['order']   = sanitize_text_field($order);

		$paged = $this->get_pagenum();
		$args['offset'] = ($paged - 1) * $per_page;
		$args['row_count'] = $per_page;

		$items_count = 0;
		$this->items = ( isset($args['include']) && empty($args['include']) ? array() : WPF()->post->get_posts($args, $items_count) );

		$this->wpfitems_count = $items_count;

		$this->set_pagination_args( array(
			'total_items' => $items_count,                  //WE have to calculate the total number of items
			'per_page'    => $per_page,                     //WE have to determine how many items to show on a page
			'total_pages' => ceil($items_count/$per_page)   //WE have to calculate the total number of pages
		) );
	}

	public function get_filter_by_status_var(){
		$filter_by_status = wpfval($_REQUEST, 'filter_by_status');
		if( !is_null($filter_by_status) && $filter_by_status !== '-1' ){
			$status = intval($filter_by_status);
		}else{
			$status = 1;
		}

		return $status;
    }

    public function get_filter_by_userid_var(){
	    $filter_by_userid = wpfval($_REQUEST, 'filter_by_userid');
	    if( !is_null($filter_by_userid) && $filter_by_userid !== '-1' ){
	    	$userid = wpforo_bigintval($filter_by_userid);
	    }else{
		    $userid = -1;
	    }
	    return $userid;
    }

	public function users_dropdown(){
		?>
		<select name="filter_by_userid">
			<option value="-1">-- <?php _e('All Users', 'wpforo'); ?> --</option>

			<?php
			if( $userids = WPF()->moderation->get_distinct_userids( $this->get_filter_by_status_var() ) ){
				$current_userid = $this->get_filter_by_userid_var();
				foreach ($userids as $userid){
				    $userid = wpforo_bigintval($userid);
					$userdata = get_userdata($userid);
					?>
					<option value="<?php echo $userid ?>" <?php echo ($current_userid === $userid ? 'selected' : '') ?> > <?php echo (!empty($userdata->user_nicename) ? urldecode($userdata->user_nicename) : $userid) ?> </option>
					<?php
				}
			}
			?>
		</select>
		<?php
	}

	public function status_dropdown(){
		$filter_by_status = $this->get_filter_by_status_var();
		if( $statuses = WPF()->moderation->post_statuses ) :  ?>
            <select name="filter_by_status">
				<?php  foreach ($statuses as $key => $status) : ?>
                    <option value="<?php echo esc_attr($key) ?>" <?php echo ( $filter_by_status === $key ? 'selected' : '' ) ?>><?php echo esc_html( $status ) ?></option>
				<?php endforeach; ?>
            </select>
		<?php
		endif;
	}
}