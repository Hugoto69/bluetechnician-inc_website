<?php
if(!class_exists('WP_List_Table')){
	require_once( ABSPATH . 'wp-admin/includes/class-wp-screen.php' );
	require_once( ABSPATH . 'wp-admin/includes/screen.php' );
	require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
	require_once( ABSPATH . 'wp-admin/includes/template.php' );
}

class wpForoPhrasesListTable extends WP_List_Table {

    public $wpfitems_count;

	/** ************************************************************************
	 * REQUIRED. Set up a constructor that references the parent constructor. We
	 * use the parent reference to set some default configs.
	 ***************************************************************************/
	function __construct(){
		//Set parent defaults
		parent::__construct( array(
			'singular'  => 'phrase',     //singular name of the listed records
			'plural'    => 'phrases',    //plural name of the listed records
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
			case 'langid':
				$language = WPF()->phrase->get_language($item[ $column_name ]);
				return ( ! empty( $language['name'] ) ? esc_html($language['name']) : __('default', 'wpforo') );
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
	function column_phraseid($item){
		$ehref = wp_nonce_url(admin_url( sprintf('admin.php?page=%1$s&wpfaction=%2$s&phraseid=%3$s','wpforo-phrases', 'wpforo_phrase_edit_form', $item['phraseid']) ), 'wpforo-phrase-edit-' . $item['phraseid']);
		$actions = array(
			'edit' => '<a href="' . $ehref . '">' . __('Edit', 'wpforo') . '</a>'
		);

		//Return the title contents
		return sprintf('%1$s %2$s',
			/*$1%s*/ $item['phraseid'],
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
			/*$1%s*/ 'phraseids',  //Let's simply repurpose the table's singular label ("movie")
			/*$2%s*/ $item['phraseid']         //The value of the checkbox should be the record's id
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
			'cb'           => '<input type="checkbox" />', //Render a checkbox instead of text
			'phraseid'     => __( 'ID',          'wpforo' ),
			'phrase_key'   => __( 'Original',    'wpforo' ),
			'phrase_value' => __( 'Translation', 'wpforo' ),
			'package'      => __( 'Package',     'wpforo' ),
			'langid'       => __( 'Language',    'wpforo' )
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
			'phraseid'     => array( 'phraseid',     false ),     //true means it's already sorted
			'phrase_key'   => array( 'phrase_key',   false ),
			'phrase_value' => array( 'phrase_value', false ),
			'package'      => array( 'package',      false ),
			'langid'       => array( 'langid',       false )
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
		return array( 'edit' => __( 'Edit', 'wpforo' ) );
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
		$args = array('langid' => $this->get_filter_by_langid_var());
		if( $s = wpfval($_REQUEST, 's') ) $args['include'] = WPF()->phrase->search($s);
		$orderby = wpfval($_REQUEST, 'orderby');
		$order   = strtoupper( wpfval($_REQUEST, 'order') );
		$filter_by_package = $this->get_filter_by_package_var();
		if( $filter_by_package !== -1 )                $args['package']  = (array) $filter_by_package;
		if( in_array( $order, array('ASC', 'DESC') ) ) $args['order']   = sanitize_text_field($order);
		if( array_key_exists($orderby, $sortable) )    $args['orderby'] = sanitize_text_field($orderby);

		$paged = $this->get_pagenum();
		$args['offset'] = ($paged - 1) * $per_page;
		$args['row_count'] = $per_page;

		$items_count = 0;
		$this->items = ( isset($args['include']) && empty($args['include']) ? array() : WPF()->phrase->get_phrases($args, $items_count, true) );

		$this->wpfitems_count = $items_count;

		$this->set_pagination_args( array(
			'total_items' => $items_count,                  //WE have to calculate the total number of items
			'per_page'    => $per_page,                     //WE have to determine how many items to show on a page
			'total_pages' => ceil($items_count/$per_page)   //WE have to calculate the total number of pages
		) );
	}

	public function get_filter_by_langid_var(){
		$filter_by_langid = wpfval($_REQUEST, 'filter_by_langid');
		if( !is_null($filter_by_langid) && $filter_by_langid !== '-1' ){
			$langid = $filter_by_langid;
		}else{
			$langid = WPF()->general_options['lang'];
		}
		return intval($langid);
    }

    public function get_filter_by_package_var(){
	    $filter_by_package = wpfval($_REQUEST, 'filter_by_package');
	    if( !is_null($filter_by_package) && $filter_by_package !== '-1' ){
	    	$package = $filter_by_package;
	    }else{
		    $package = -1;
	    }
	    return $package;
    }

	public function packages_dropdown(){ ?>
		<select name="filter_by_package">
			<option value="-1">-- <?php _e('filter by package', 'wpforo') ?> --</option>
			<?php
			if( $packages = WPF()->phrase->get_distinct_packages() ){
				$selected = $this->get_filter_by_package_var();
				foreach( $packages as $package ){
					printf(
						'<option value="%1$s" %2$s>%3$s</option>',
						esc_attr($package),
						($package === $selected) ? 'selected' : '',
						esc_html($package)
					);
				}
			}
			?>
		</select>
		<?php
	}

	public function languages_dropdown(){ ?>
        <select name="filter_by_langid">
			<?php WPF()->phrase->show_lang_list( $this->get_filter_by_langid_var() ); ?>
        </select>
		<?php
	}
}