<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class wpForoRevision {
	public $options;
	public $revision;
	private $default;

	public function __construct() {
		add_action( 'wpforo_after_init', array( $this, 'init' ) );
	}

	public function init() {
		$this->init_defaults();
		$this->init_options();
		$this->revision = $this->default->revision;
		$this->init_hooks();
	}

	private function init_defaults() {
		$this->default = new stdClass();
		$this->default->options = array(
			'auto_draft_interval' => 60000,
			'max_drafts_per_page' => 3,
			'is_preview_on'       => 1,
			'is_draft_on'         => 1
		);
		$this->default->revision = array(
			'revisionid' => 0,
			'userid'     => 0,
			'textareaid' => '',
			'postid'     => 0,
			'body'       => '',
			'created'    => 0,
			'version'    => 0,
			'email'      => '',
			'url'        => '',
		);
		$this->default->revision_format = array(
			'revisionid' => '%d',
			'userid'     => '%d',
			'textareaid' => '%s',
			'postid'     => '%d',
			'body'       => '%s',
			'created'    => '%d',
			'version'    => '%d',
			'email'      => '%s',
			'url'        => '%s'
		);
		$this->default->sql_select_args = array(
			'include'             => array(),
			'exclude'             => array(),
			'userids_include'     => array(),
			'userids_exclude'     => array(),
			'textareaids_include' => array(),
			'textareaids_exclude' => array(),
			'postids_include'     => array(),
			'postids_exclude'     => array(),
			'urls_include'        => array(),
			'urls_exclude'        => array(),
			'emails_include'      => array(),
			'emails_exclude'      => array(),
			'orderby'             => 'revisionid',
			'order'               => 'DESC',
			'offset'              => null,
			'row_count'           => null
		);
	}

	private function init_options() {
		$this->options = get_wpf_option( 'wpforo_revision_options', $this->default->options );
	}

	private function init_hooks() {
		if( $this->options['is_preview_on'] || $this->options['is_draft_on'] ){
		    add_action('wpforo_editor_topic_submit_after', array($this, 'show_html_into_form'));
		    add_action('wpforo_editor_post_submit_after', array($this, 'show_html_into_form'));
		    add_action('wpforo_portable_editor_post_submit_after', array($this, 'show_html_into_form'));

            if( $this->options['is_preview_on'] ) add_action('wp_ajax_wpforo_post_preview', array($this, 'ajax_post_preview'));

            if( $this->options['is_draft_on'] ){
                add_action( 'wpforo_after_add_topic',   array( $this, 'after_submit' ) );
                add_action( 'wpforo_after_add_post',    array( $this, 'after_submit' ) );
                add_action( 'wpforo_after_edit_topic',  array( $this, 'after_submit' ) );
                add_action( 'wpforo_after_edit_post',   array( $this, 'after_submit' ) );

                add_action('wp_ajax_wpforo_save_revision', array($this, 'ajax_save_revision'));
                add_action('wp_ajax_wpforo_get_revisions_history', array($this, 'ajax_get_revisions_history'));
                add_action('wp_ajax_wpforo_get_revision', array($this, 'ajax_get_revision'));
                add_action('wp_ajax_wpforo_delete_revision', array($this, 'ajax_delete_revision'));
            }
		}
	}

	private function get_current_url_query_vars_str(){
		$url_query_vars_str = wpforo_get_url_query_vars_str();
		$url_query_vars_str = preg_replace( '#^/?'.preg_quote(WPF()->permastruct).'#isu', '' , $url_query_vars_str, 1 );
		$url_query_vars_str = preg_replace('#/?\?.*$#isu', '', $url_query_vars_str);

		$wpf_url_parse = array_filter( explode('/', trim($url_query_vars_str, '/')) );
		$wpf_url_parse = array_reverse($wpf_url_parse);
		if(in_array(wpforo_get_template_slug('paged'), $wpf_url_parse)){
			foreach($wpf_url_parse as $key => $value){
				unset($wpf_url_parse[$key]);
				if( $value === wpforo_get_template_slug('paged')) break;
			}
			$wpf_url_parse = array_values($wpf_url_parse);
			$wpf_url_parse = array_reverse($wpf_url_parse);
			$url_query_vars_str = implode('/', $wpf_url_parse);
		}

		if( !$url_query_vars_str ) $url_query_vars_str = 'wpforo_home_url';
		return  $url_query_vars_str;
    }

	private function parse_revision( $revision ) {
		$revision = array_merge( $this->default->revision, (array) $revision );
		if( $revision['body'] ){
			$revision['body'] = preg_replace('#</pre>[\r\n\t\s\0]*<pre>#isu', "\r\n", $revision['body']);
			$revision['body'] = wpforo_kses(trim($revision['body']), 'post');
			$revision['body'] = stripslashes($revision['body']);
		}
		return $revision;
	}

	private function parse_args( $args ) {
		$args = wpforo_parse_args( $args, $this->default->sql_select_args );

		$args['include'] = wpforo_parse_args( $args['include'] );
		$args['exclude'] = wpforo_parse_args( $args['exclude'] );

		$args['userids_include'] = wpforo_parse_args( $args['userids_include'] );
		$args['userids_exclude'] = wpforo_parse_args( $args['userids_exclude'] );

		$args['textareaids_include'] = wpforo_parse_args( $args['textareaids_include'] );
		$args['textareaids_exclude'] = wpforo_parse_args( $args['textareaids_exclude'] );

		$args['postids_include'] = wpforo_parse_args( $args['postids_include'] );
		$args['postids_exclude'] = wpforo_parse_args( $args['postids_exclude'] );

		$args['urls_include'] = wpforo_parse_args( $args['urls_include'] );
		$args['urls_exclude'] = wpforo_parse_args( $args['urls_exclude'] );

		$args['emails_include'] = wpforo_parse_args( $args['emails_include'] );
		$args['emails_exclude'] = wpforo_parse_args( $args['emails_exclude'] );

		return $args;
	}

	private function build_sql_where ( $args ){
	    $where = '';
		$args = $this->parse_args( $args );

		$wheres = array();
		if ( ! empty( $args['include'] ) ) {
			$wheres[] = "`revisionid` IN(" . implode( ',', array_map( 'wpforo_bigintval', $args['include'] ) ) . ")";
		}
		if ( ! empty( $args['exclude'] ) ) {
			$wheres[] = "`revisionid` NOT IN(" . implode( ',', array_map( 'wpforo_bigintval', $args['exclude'] ) ) . ")";
		}

		if ( ! empty( $args['userids_include'] ) ) {
			$wheres[] = "`userid` IN(" . implode( ',', array_map( 'wpforo_bigintval', $args['userids_include'] ) ) . ")";
		}
		if ( ! empty( $args['userids_exclude'] ) ) {
			$wheres[] = "`userid` NOT IN(" . implode( ',', array_map( 'wpforo_bigintval', $args['userids_exclude'] ) ) . ")";
		}

		if ( ! empty( $args['textareaids_include'] ) ) {
			$wheres[] = "`textareaid` IN('" . implode( "','", array_map( 'trim', $args['textareaids_include'] ) ) . "')";
		}
		if ( ! empty( $args['textareaids_exclude'] ) ) {
			$wheres[] = "`textareaid` IN('" . implode( "','", array_map( 'trim', $args['textareaids_exclude'] ) ) . "')";
		}

		if ( ! empty( $args['postids_include'] ) ) {
			$wheres[] = "`postid` IN(" . implode( ',', array_map( 'wpforo_bigintval', $args['postids_include'] ) ) . ")";
		}
		if ( ! empty( $args['postids_exclude'] ) ) {
			$wheres[] = "`postid` NOT IN(" . implode( ',', array_map( 'wpforo_bigintval', $args['postids_exclude'] ) ) . ")";
		}

		if ( ! empty( $args['urls_include'] ) ) {
			$wheres[] = "`url` IN('" . implode( "','", array_map( 'trim', $args['urls_include'] ) ) . "')";
		}
		if ( ! empty( $args['urls_exclude'] ) ) {
			$wheres[] = "`url` IN('" . implode( "','", array_map( 'trim', $args['urls_exclude'] ) ) . "')";
		}

		if ( ! empty( $args['emails_include'] ) ) {
			$wheres[] = "`email` IN('" . implode( "','", array_map( 'trim', $args['emails_include'] ) ) . "')";
		}
		if ( ! empty( $args['emails_exclude'] ) ) {
			$wheres[] = "`email` IN('" . implode( "','", array_map( 'trim', $args['emails_exclude'] ) ) . "')";
		}

		if ( $wheres ) {
			$where = " WHERE " . implode( " AND ", $wheres );
		}

		return $where;
    }

	private function build_sql_select( $args ) {
		$args = $this->parse_args( $args );
		$sql = "SELECT * FROM " . WPF()->tables->post_revisions;
		$sql .= $this->build_sql_where($args);
		$sql .= " ORDER BY " . $args['orderby'] . " " . $args['order'];
		if ( $args['row_count'] ) $sql .= " LIMIT " . wpforo_bigintval( $args['offset'] ) . "," . wpforo_bigintval( $args['row_count'] );
		return $sql;
	}

	private function add( $data ) {
		if ( empty( $data ) ) return false;
		$revision = $this->parse_revision($data);
		unset( $revision['revisionid'] );

		if ( !$revision['created'] ) $revision['created'] = current_time( 'timestamp', 1 );
		if ( !$revision['url'] )     $revision['url']     = $this->get_current_url_query_vars_str();
		if ( !$revision['userid'] )  $revision['userid']  = WPF()->current_userid;
		if ( !$revision['email'] )   $revision['email']   = WPF()->current_user_email;
		if ( !$revision['textareaid'] || !$revision['url'] || !$revision['body'] || !($revision['userid'] || $revision['email']) ) return false;

		$revision = wpforo_array_ordered_intersect_key( $revision, $this->default->revision_format );
		if ( WPF()->db->insert(
			WPF()->tables->post_revisions,
			$revision,
			wpforo_array_ordered_intersect_key( $this->default->revision_format, $revision )
			)
		) {
			return WPF()->db->insert_id;
		}

		return false;
	}

	private function edit( $data, $where ) {
		if ( empty( $data ) || empty( $where ) ) return false;
		if ( is_numeric( $where ) ) $where = array( 'revisionid' => $where );
		$data  = (array) $data;
		$where = (array) $where;

		$data  = wpforo_array_ordered_intersect_key( $data,  $this->default->revision_format );
		$where = wpforo_array_ordered_intersect_key( $where, $this->default->revision_format );
		if ( false !== WPF()->db->update(
				WPF()->tables->post_revisions,
				$data,
				$where,
				wpforo_array_ordered_intersect_key( $this->default->revision_format, $data ),
				wpforo_array_ordered_intersect_key( $this->default->revision_format, $where )
			)
		) {
			return true;
		}

		return false;
	}

	private function delete( $where ) {
		if( empty($where) ) return false;
		if ( is_numeric( $where ) ) $where = array( 'revisionid' => $where );
		$where = (array) $where;

		$where = wpforo_array_ordered_intersect_key( $where, $this->default->revision_format );
		if ( false !== WPF()->db->delete(
				WPF()->tables->post_revisions,
				$where,
				wpforo_array_ordered_intersect_key( $this->default->revision_format, $where )
			)
		) {
			return true;
		}

		return false;
	}

	public function get_revision( $args ) {
		if ( empty( $args ) ) return false;

		return $this->parse_revision( WPF()->db->get_row( $this->build_sql_select( $args ), ARRAY_A ) );
	}

	public function get_revisions( $args ) {
		if ( empty($args) ) return false;

		return array_map( array( $this, 'parse_revision' ), WPF()->db->get_results( $this->build_sql_select( $args ), ARRAY_A ) );
	}

	/**
	 * @param array $args
	 *
	 * @return int
	 */
	private function get_count( $args ){
		$sql = "SELECT SQL_NO_CACHE COUNT(*) FROM " . WPF()->tables->post_revisions;
		$sql .= $this->build_sql_where($args);
		return intval( WPF()->db->get_var($sql) );
	}

	public function show_html_into_form(){
	    if( $this->options['is_draft_on'] ){
		    $args = array(
//			'textareaids_include' => (string) wpfval( $_POST, 'textareaid' ),
			    'postids_include' => wpforo_bigintval( wpfval( $_POST, 'postid' ) ),
			    'userids_include' => WPF()->current_userid,
			    'emails_include'  => WPF()->current_user_email,
			    'urls_include'    => $this->get_current_url_query_vars_str()
		    );
		    $revisions_count = $this->get_count($args);
        }else{
		    $revisions_count = null;
        }

		?>
		<div class="wpf-clear"></div>
		<div class="wpforo-revisions-wrap"><?php $this->show_wrap_inner_html($revisions_count); ?></div>
		<?php
	}

	private function build_wrap_inner_html($revisions_count = null){
		$buttons = '';
		if( $this->options['is_preview_on'] ){
			$buttons .= sprintf('<span class="wpforo-revision-action-button wpforo_post_preview wpf-disabled"> <i class="fas fa-eye wpf-rev-preview-ico"></i> %1$s </span>', wpforo_phrase('Preview', false) );
		}
		if( $this->options['is_draft_on'] ){
			$revisions_count = intval($revisions_count);
			$buttons .= sprintf('<span class="wpforo-revision-action-button wpforo_revisions_history"><i class="fas fa-history wpf-rev-ico"></i> %1$s </span>', sprintf( wpforo_phrase('%1$s Revisions', false), '<span class="wpf-rev-history-count">'.$revisions_count.'</span>' )) .
                sprintf('<span class="wpforo-revision-action-button wpforo_save_revision" style="display: none;"><i class="fas fa-save wpf-rev-save-ico"></i> %1$s </span>', wpforo_phrase('Save Draft', false)) .
                sprintf('<span class="wpforo-revision-action-button wpforo_revision_saved wpf-disabled"><i class="fas fa-check wpf-rev-saved-ico"></i> %1$s </span>', wpforo_phrase('Saved', false));
        }
		$html = sprintf('<div class="wpforo-revisions-action-buttons">%1$s</div><div class="wpforo-revisions-preview-wrap"></div>', $buttons );
        return $html;
    }

	private function show_wrap_inner_html($revisions_count = null){
		echo $this->build_wrap_inner_html($revisions_count);
    }

	private function build_preview($revision){
	    $html = sprintf('<div class="wpforo-revision" data-revisionid="%1$d" data-created="%2$d"> 
	            <div class="wpforo-revision-top">
                    <div class="wpforo-revision-created"><i class="fas fa-eye wpf-rev-ico"></i> %3$s</div>
                </div>
                <div class="wpforo-revision-body">%4$s</div> 
            </div>',
            $revision['revisionid'],
            $revision['created'],
		    wpforo_phrase('Preview', false),
            wpforo_content($revision, false)
        );
	    return $html;
	}

	private function show_preview($revision){
		echo $this->build_preview($revision);
    }

    private function build_revision($revision){
	    $html = sprintf( '
            <div class="wpforo-revision" data-revisionid="%1$d" data-created="%2$s">
                <div class="wpforo-revision-top">
                    <div class="wpforo-revision-created"><i class="fas fa-clock wpf-rev-ico"></i> %3$s %4$s</div>
                    <div class="wpforo-revision-actions">
                        <span class="wpforo-revision-action-restore" style="cursor: pointer;"><i class="fas fa-history wpf-rev-ico"></i> %5$s</span>
                        &nbsp;|&nbsp;
                        <span class="wpforo-revision-action-delete" style="cursor: pointer;"><i class="fas fa-trash wpf-rev-ico"></i> %6$s</span>
                    </div>
                </div>
                <div class="wpforo-revision-body">%7$s</div>
            </div>',
		    $revision['revisionid'],
		    $revision['created'],
		    wpforo_phrase('Revision', false),
		    wpforo_date($revision['created'], 'ago', false),
		    wpforo_phrase('Restore', false),
		    wpforo_phrase('Delete', false),
		    wpforo_content($revision, false)
        );
	    return $html;
    }

    private function show_revision($revision){
		echo $this->build_revision($revision);
    }

	public function ajax_save_revision() {
		$args = array(
			'textareaid' => (string) wpfval($_POST, 'textareaid'),
			'postid'     => wpforo_bigintval( wpfval($_POST, 'postid') ),
			'body'       => (string) wpfval($_POST, 'body')
		);

		$revision = $this->parse_revision($args);
		$revision['created'] = current_time( 'timestamp', 1 );
		$revision['url'] = $this->get_current_url_query_vars_str();
		$revision['userid'] = WPF()->current_userid;
		$revision['email'] = WPF()->current_user_email;

		if( $revisionid = (int) $this->add($revision) ){
			$args = array(
//			    'textareaids_include' => $revision['textareaid'],
				'postids_include' => $revision['postid'],
				'userids_include' => $revision['userid'],
				'emails_include'  => $revision['email'],
				'urls_include'    => $revision['url']
			);
			$revisions_count = $this->get_count($args);
			if( $revisions_count > $this->options['max_drafts_per_page'] ){
			    $sql = "DELETE FROM " . WPF()->tables->post_revisions . $this->build_sql_where($args) . " 
			        ORDER BY `revisionid` ASC LIMIT %d";
			    $sql = WPF()->db->prepare($sql, ($revisions_count - $this->options['max_drafts_per_page']) );
			    if( WPF()->db->query($sql) !== false ) $revisions_count = $this->options['max_drafts_per_page'];
            }
        }else{
		    $revisions_count = 0;
			$revisionid = 0;
        }

		$revision['revisionid'] = $revisionid;

		$response = array(
            'revisionid' => $revisionid,
            'revisions_count' => $revisions_count,
            'revisionhtml' => $this->build_revision($revision)
        );
		if( $revisionid ){
		    wp_send_json_success($response);
		}else{
		    wp_send_json_error($response);
		}
	}

	public function ajax_post_preview(){
		$revision = $this->parse_revision($_POST);
		ob_start();
		$this->show_preview($revision);
        $html = trim( ob_get_clean() );
		if( $html ){
		    wp_send_json_success($html);
		}else{
			wp_send_json_error($html);
		}
    }

	public function ajax_get_revisions_history(){
		$args = array(
//			'textareaids_include' => (string) wpfval( $_POST, 'textareaid' ),
			'postids_include' => wpforo_bigintval( wpfval( $_POST, 'postid' ) ),
			'userids_include' => WPF()->current_userid,
			'emails_include'  => WPF()->current_user_email,
			'urls_include'    => $this->get_current_url_query_vars_str()
		);

		$revisionhtml = '';
		if( $revisions = $this->get_revisions($args) ){
			foreach ($revisions as $revision) $revisionhtml .= $this->build_revision($revision);
		}

		$revisions_count = count($revisions);
		$response = array(
			'revisions_count' => $revisions_count,
			'revisionhtml' => $revisionhtml
		);
		if( $revisions_count ){
		    wp_send_json_success($response);
		}else{
			wp_send_json_error($response);
		}
	}

	public function ajax_get_revision(){
		if( $revisionid = wpforo_bigintval( wpfval($_POST, 'revisionid') ) ){
			if( $revision = $this->get_revision( array('include' => $revisionid) ) ) wp_send_json_success($revision);
        }
		wp_send_json_error();
    }

	public function ajax_delete_revision(){
		if( $revisionid = wpforo_bigintval( wpfval($_POST, 'revisionid') ) ){
			if( $this->delete( $revisionid ) ){
				$args = array(
//        			'textareaids_include' => (string) wpfval( $_POST, 'textareaid' ),
					'postids_include' => wpforo_bigintval( wpfval( $_POST, 'postid' ) ),
					'userids_include' => WPF()->current_userid,
					'emails_include'  => WPF()->current_user_email,
					'urls_include'    => $this->get_current_url_query_vars_str()
				);
				$revisions_count = $this->get_count($args);
				wp_send_json_success(compact('revisions_count'));
			}
        }
        wp_send_json_error();
    }

    public function after_submit(){
	    $this->delete( array( 'userid' => WPF()->current_userid, 'email' => WPF()->current_user_email, 'url' => $this->get_current_url_query_vars_str() ) );
	    $sql = "SELECT EXISTS( SELECT * FROM ". WPF()->tables->post_revisions ." ) AS is_exists";
	    if( !WPF()->db->get_var($sql) ) WPF()->db->query("TRUNCATE " . WPF()->tables->post_revisions );
    }
}