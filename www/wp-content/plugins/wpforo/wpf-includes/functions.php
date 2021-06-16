<?php
	// Exit if accessed directly
	if( !defined( 'ABSPATH' ) ) exit;

function wpforo_verify_form( $mode = 'full', $action = 'wpforo_verify_form', $nonce = 'wpforo_form' ){
	if( $mode == 'full' ){
		if (!isset($_REQUEST[$nonce]) || !wp_verify_nonce( $_REQUEST[$nonce], $action )) {
			wpforo_phrase('Sorry, something is wrong with your data.');
			exit();
		}
	}
	if( $mode == 'ref' || $mode == 'full'){
		if( !isset($_SERVER['HTTP_REFERER']) || !$_SERVER['HTTP_REFERER'] ) {
			exit('Error 2252 | Please contact the forum administrator.');
		}
		$ref = $_SERVER['HTTP_REFERER'];
		$url = get_bloginfo('url');
		$ref_domain = trim(strtolower(parse_url($ref, PHP_URL_HOST)));
		$web_domain = trim(strtolower(parse_url($url, PHP_URL_HOST)));
		if( $ref_domain != $web_domain ){
			exit('Error 2253 | Please contact the forum administrator.');
		}
	}
	do_action('wpforo_verify_form_end');
}

/**
 * @param string $str
 * @param bool $echo
 * @param bool $absolute
 *
 * @return string|void
 */
function wpforo_home_url($str = '', $echo = false, $absolute = true){
	if( strpos($str, 'http') === 0 ){
		$str = WPF()->user_trailingslashit($str);
		$str = wpforo_get_url_query_vars_str($str);
		$str = preg_replace( '#^/?'.preg_quote(trim(WPF()->permastruct, '/\\')).'#isu', '' , $str, 1 );
		$str = preg_replace('#index\.php/?#isu', '', $str, 1);
	}
	$str = trim(WPFORO_BASE_PERMASTRUCT, '/\\') . '/' . trim($str, '/\\');

    if( $absolute ){
		$url = WPF()->user_trailingslashit( home_url($str) );
		//-START- check is url maybe wordpress home
		$maybe_home_url = trim( preg_replace('#/?index\.php/?(\?.*)?$#isu', '', $url), '/\\' );
		$home_url = trim( home_url(), '/\\' );
		if( $maybe_home_url === $home_url ){
			$url = preg_replace('#index\.php/?#isu', '', $url, 1);
			$url = WPF()->user_trailingslashit($url);
		}
		//-END- check is url maybe wordpress home
	}
	else{
		echo $url = WPF()->user_trailingslashit( $str );
	}

    if(!$echo) return $url;
	echo $url;
}

function wpforo_is_ajax(){
	return defined('DOING_AJAX') && DOING_AJAX;
}

function wpforo_is_admin($url = ''){
	$url = trim($url);
    if($url) return strpos($url, trim(admin_url(), '/')) === 0 || strpos($url, trim(self_admin_url(), '/')) === 0;
	return is_admin() && !wpforo_is_ajax();
}

function is_wpforo_page($url = ''){
	$key = 'is_wpforo_page_' . $url;
	if( WPF()->ram_cache->is_exist($key) ) return WPF()->ram_cache->get($key);

	if(!$url) $url = wpforo_get_request_uri();
    $result = ( !(wpforo_is_admin($url) || (is_wpforo_exclude_url($url) && !is_wpforo_shortcode_page($url))) && (is_wpforo_url($url) || is_wpforo_shortcode_page($url)) );
	$result = apply_filters( 'is_wpforo_page', $result, $url );

	WPF()->ram_cache->set($key, $result);
    return $result;
}

function is_wpforo_exclude_url($url = ''){
	$key = 'is_wpforo_exclude_url_' . $url;
	if( WPF()->ram_cache->is_exist($key) ) return WPF()->ram_cache->get($key);

	$result = false;
	if(!$url) $url = wpforo_get_request_uri();
	$url = urldecode($url);
	$url = preg_replace('#/page/\d*/?$#isu', '', $url);
    if( !$current_url =  wpforo_get_url_query_vars_str($url) ){
    	$result = false;
    }elseif( preg_match('#^/?(?:([^\s/\?\&=<>:\'\"\*\:\\\|]*/)(?1)*)?[^\s/\?\&=<>:\'\"\*\:\\\|]+\.(?:php|js|css|jpe?g|png|gif)/?(?:\?[^/]*)?$#iu', $current_url) ){
    	$result = true;
    }elseif( WPF()->use_home_url && WPF()->excld_urls ){
        $expld = array_filter( array_map('trim', explode("\n", WPF()->excld_urls)) );
        foreach( $expld as $excld_url ){
	        $excld_url = urldecode($excld_url);
	        if( wpforo_is_url_internal($excld_url) ){
		        $excld_url = wpforo_get_url_query_vars_str($excld_url);
		        $pattern = preg_quote($excld_url);
		        $pattern = str_replace(array('/\*', '\*'), array('/?[^\r\n\t\s\0]*?', '[^\r\n\t\s\0]*?'), $pattern);
		        if( preg_match('#^'.$pattern.'$#iu', $current_url) ) $result = true;
	        }
		}
	}

    WPF()->ram_cache->set($key, $result);
	return $result;
}

function is_wpforo_url($url = ''){
	$key = 'is_wpforo_url_' . $url;
	if( WPF()->ram_cache->is_exist($key) ) return WPF()->ram_cache->get($key);

	$result = false;
	if( wpforo_is_admin($url) ){
		$result = false;
	}elseif( WPF()->use_home_url && !is_wpforo_exclude_url($url) ){
		$result = true;
	}elseif( WPF()->permastruct ){
		$current_url = wpforo_get_url_query_vars_str($url);
		$current_url = preg_replace('#/?\?.*$#isu', '', $current_url);
		if( $current_url === WPF()->permastruct ){
            $result = true;
		}elseif( strpos($current_url, WPF()->permastruct . '/' ) === 0 ){
			$result = true;
		}
	}

	WPF()->ram_cache->set($key, $result);
	return $result;
}

/**
 * @param string $url
 * @return bool
 */
function is_wpforo_shortcode_page($url = ''){
	$key = 'is_wpforo_shortcode_page_' . $url;
	if( WPF()->ram_cache->is_exist($key) ) return WPF()->ram_cache->get($key);

    $result = !wpforo_is_admin($url) && !is_wpforo_url($url) && has_shortcode( wpforo_get_wp_post_content($url), 'wpforo' );

    WPF()->ram_cache->set($key, $result);
    return $result;
}

function wpforo_get_wp_post_content($url = ''){
	if(!$url) $url = wpforo_get_request_uri();
	$key = 'wpforo_get_wp_post_content_' . $url;
	if( WPF()->ram_cache->is_exist($key) ) return WPF()->ram_cache->get($key);

	$post_content = '';
	global $post;
	if( $url === wpforo_get_request_uri() && is_a( $post, 'WP_Post' ) ){
		$post_content = $post->post_content;
	}elseif( $postid = wpforo_wp_url_to_postid($url) ){
		$post_content = get_post_field('post_content', $postid );
	}

	WPF()->ram_cache->set($key, $post_content);
    return $post_content;
}

/**
 * @param string $text
 * @param string $url
 * @return array|string
 */
function get_wpforo_shortcode_atts($text = '', $url = ''){
    if(!$text) $text = wpforo_get_wp_post_content($url);
    if( preg_match('#\[[\r\n\t\s\0]*wpforo[\r\n\t\s\0]*([^\[\]]*?)\]#isu', $text, $match) ){
        return shortcode_parse_atts($match[1]);
    }
    return '';
}

function wpforo_get_url_query_vars_str($url = ''){
	if(!$url) $url = wpforo_get_request_uri();
	$home_url = preg_replace( '#/?\?.*$#isu', '', home_url('/') );
	$current_url = preg_replace('#https?://[^/\?]+/?#isu', '', $url);
	$site_url    = preg_replace('#https?://[^/\?]+/?#isu', '', $home_url);
	if( strpos($current_url, '/') === false && $current_url === trim($site_url, '/') ) return '';
	$current_url = preg_replace( '#^/?'.preg_quote($site_url).'(?:/?index\.php/?)?#isu', '' , $current_url, 1 );
	$current_url = preg_replace('#^[\r\n\t\s\0/]*(.*?)[\r\n\t\s\0/]*$#isu', '$1', $current_url);
	$current_url = wpforo_fix_url($current_url);
	return $current_url;
}

function wpforo_feature($option){
    if( $option === 'html_cashe' ) return false;
    if (isset(WPF()->features[$option])) {
        return WPF()->features[$option];
    } else {
        return false;
    }
}

function wpforo_dir_size($directory) {
    $size = 0;
	if(class_exists('RecursiveIteratorIterator') && class_exists('RecursiveDirectoryIterator')){
		foreach(new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directory)) as $file) $size += $file->getSize();
    	return $size;
	}
	else{
		return 0;
	}
}

function wpforo_make_hidden_fields_from_url($url = '', $echo = true){
	if( !$url ) $url = wpforo_get_request_uri();
	$return = '';
	if( $url_query = parse_url($url, PHP_URL_QUERY) ){
		parse_str($url_query, $url_query_arr);
		if( !empty($url_query_arr) ){
			foreach ($url_query_arr as $key => $value) {
				$return .= '<input type="hidden" name="'.$key.'" value="'.$value.'">';
			}
		}
	}
	if( !$echo ) return $return;
	echo $return;
}

/**
 * Returns merged arguments array from defined and default arguments.
 * @param mixed $args
 * @param array $default
 * @return array
 */
function wpforo_parse_args( $args, $default = array() ) {

	if( is_object($args) ) {
		$defined = get_object_vars($args);
	}
	elseif( is_array($args) ) {
		$defined = $args;
	}
	elseif( preg_match('|^[\d\,\s]+$|', $args) ){
		$defined = explode(',', trim($args));
	}
	elseif( is_integer($args) || is_float($args) ){
		$defined[0] = $args;
	}
	elseif( is_serialized($args) ){
		$defined = unserialize($args);
	}
	elseif( strpos($args, '=') !== FALSE ) {
		parse_str($args, $defined);
	}
	else{
		$defined[0] = $args;
	}
	if(!empty($default))  {
		return array_merge( $default, $defined );
	}
	else {
		return $defined;
	}
}

/**
* Detects serialized data
*
* @since 	1.0.0
*
* @param	string
*
* @return	boolean
*/
if(!function_exists('is_serialized')){
	function is_serialized( $value ){
		if( $value == '' ) return false;
		if( $value === 0 ) return false;
		$value = trim($value);
		$chsd = @unserialize($value);
		if( $chsd !== false || $value === 'b:0;' ) {
			return true;
		}
		else{
			return false;
		}
	}
}

function wpforo_get_request_uri($with_port = FALSE, $get_referer_when_ajax = TRUE){
	if( $get_referer_when_ajax && wpforo_is_ajax() ){
	    if( $referer = wpfval($_REQUEST, 'referer') ) {
		    $referer = preg_replace('#\#[^\/\?\&]*$#isu', '', $referer);
	        return esc_url_raw($referer);
	    }
		if( $referer = wpfval($_SERVER, 'HTTP_REFERER') ){
			$url = preg_replace('#\#[^\/\?\&]*$#isu', '', $referer);
		    return esc_url_raw($url);
		}
	}
	$s = is_ssl() ? 's' : '';
    $sp = strtolower( wpfval($_SERVER, 'SERVER_PROTOCOL') );
    $protocol = substr($sp, 0, strpos($sp, "/")) . $s;
    $server_port = intval( wpfval($_SERVER, 'SERVER_PORT') );
    $port = $server_port === 80 ? "" : ":" . $server_port;
	$host = preg_replace('#^www\.#isu', '', (string) wpfval($_SERVER, 'HTTP_HOST'));
	if( strpos( home_url(), 'www.' ) !== false ) $host = 'www.' . $host;
	$url = $protocol . "://" . $host . ($with_port && $server_port ? $port : '') . wpfval($_SERVER, 'REQUEST_URI');
    $url = wpforo_fix_url($url);
    return esc_url_raw($url);
}

function wpforo_arr_group_by($array, $key_by){
	if(!empty($array)){
		$fltrd = array();
		foreach($array as $key => $arr){
			if(is_numeric($key)) $fltrd[] = $arr[$key_by];
		}
		$uniq_arr = array_unique($fltrd);
		asort($uniq_arr);
		return $uniq_arr;
	}
}

function wpforo_phrase($key, $echo = TRUE, $format = 'first-upper'){
	$locale = WPF()->locale;
	$phrase = (isset(WPF()->phrase->phrases[addslashes(strtolower($key))])) ? WPF()->phrase->phrases[addslashes(strtolower($key))] : $key;
    if( 'en_US' != $locale ){
		$native = $phrase;
	    $backtrace = wp_debug_backtrace_summary();
	    $mopo_domain = 'wpforo';
	    if( strpos($backtrace, '\plugins\wpforo-private-messages\\') !== false || strpos($backtrace, '/plugins/wpforo-private-messages/') !== false ) $mopo_domain = 'wpforo_pm';
	    if( strpos($backtrace, 'wpForoPolls') !== false ) $mopo_domain = 'wpforo_poll';
        $key = preg_replace("/(^\s+)|(\s+$)/u", "", $key);
        $phrase = preg_replace("/(^\s+)|(\s+$)/u", "", $phrase);
        if (strtolower($key) == strtolower($phrase)) {
			$phrase = __($key, $mopo_domain);
			if (strtolower($key) == strtolower($phrase)) {
				$key = strtolower($key);
				$phrase = __($key, $mopo_domain);
				if (strtolower($key) == strtolower($phrase)) {
					$phrase = __(ucfirst($key), $mopo_domain);
					if (strtolower($key) == strtolower($phrase)) {
						$phrase = __($native, $mopo_domain); //Try all, if no result pass the original text to translation again.
					}
				}
			}
		}
	}

	if( $format == 'first-upper' ){
		if( 'en_US' != $locale && function_exists('mb_strlen') && mb_strlen($phrase) != strlen($phrase) && function_exists('mb_strtoupper') ) {
			$phrase = mb_strtoupper(mb_substr($phrase, 0, 1)) . mb_substr($phrase, 1);
		}
		else{
			$phrase = ucfirst($phrase);
		}
	}
	elseif( $format == 'upper' ){
		if(function_exists('mb_strtoupper')){
			$phrase = mb_strtoupper($phrase);
		}
		else{
			$phrase = strtoupper($phrase);
		}
	}
	elseif( $format == 'lower' ){
		if(function_exists('mb_strtolower')){
			$phrase = mb_strtolower($phrase);
		}
		else{
			$phrase = strtolower($phrase);
		}
	}

	$phrase = str_replace('{number}', '', $phrase);

	if($echo){
		echo $phrase;
	}else{
		return $phrase;
	}
}

function wpforo_screen_option(){ ?>

	<div id="screen-meta" class="metabox-prefs" style="display: none; ">
		<div id="screen-options-wrap" class="hidden" tabindex="-1" aria-label="Screen Options Tab" style="display: none; ">
			<form id="adv-settings" action="" method="POST">
                <input type="hidden" name="wpfaction" value="wpforo_dashboard_options_save">
				<h5><?php _e('Show on screen', 'wpforo') ?></h5>

				<div class="screen-options">
					<input type="number" step="1" min="1" max="999" class="screen-per-page" name="wpforo_dashboard_count_per_page" id="edit_post_per_page" maxlength="3" value="<?php echo intval(get_option('wpforo_count_per_page', 10)) ?>">
					<label for="edit_post_per_page"><?php _e('Items', 'wpforo') ?></label>
					<input type="submit" id="screen-options-apply" class="button" value="<?php _e('Apply', 'wpforo') ?>">
				</div>
			</form>
		</div>
	</div>

	<div id="screen-meta-links">
		<div id="screen-options-link-wrap" class="hide-if-no-js screen-meta-toggle" style="">
			<a href="#screen-options-wrap" id="show-settings-link" class="show-settings screen-meta-active" aria-controls="screen-options-wrap" aria-expanded="true">
				<?php _e('Screen Options', 'wpforo') ?>
			</a>
		</div>
	</div>

  <?php
}

function wpforo_text( $text, $length = 0, $echo = true, $strip_tags = true, $strip_urls = true, $strip_shortcodes = true, $strip_quotes = true ){
    $text = str_replace('</p>', '</p> ', $text);
    $text = str_replace('</div>', '</div> ', $text);
    if($strip_quotes) $text = preg_replace('#<(blockquote)[^<>]*?data-userid[^<>]*?>(?:.*?(?R)*.*?)*?</\1>#isu', '', $text);
    if($strip_urls) $text = preg_replace('#(?:[^\'\"]|^)(https?://[^\s\'\"<>]+)(?:[^\'\"]|$)#isu', '', $text);
	if($strip_tags) $text = strip_tags($text);
    if($strip_shortcodes){
		$text = preg_replace('#\[attach[^\[\]]*\][^\[\]]*\[/attach\]#isu', '', $text);
		$text = strip_shortcodes( $text );
	}
    $text = apply_filters('wpforo_text', $text, $length, $echo, $strip_tags, $strip_urls, $strip_shortcodes, $strip_quotes);

	$text = trim( str_replace("\xc2\xa0", ' ', $text ) );

	if(!$length){
	    if($echo){
            echo trim($text);
            return '';
        }else{
            return trim($text);
        }
    }
	if(function_exists('mb_substr')){
		if($echo){
			echo trim( mb_substr( $text, 0, $length, get_option('blog_charset') ) . ( ( function_exists('mb_strlen') ? mb_strlen( $text, get_option('blog_charset') ) : strlen($text) ) > $length ? '...' : '' ) );
		}else{
			return trim( mb_substr( $text, 0, $length, get_option('blog_charset') ) . ( ( function_exists('mb_strlen') ? mb_strlen( $text, get_option('blog_charset') ) : strlen($text) ) > $length ? '...' : '' ) );
		}
	}else{
		if($echo){
			echo trim( substr( $text, 0, $length ) . ( strlen($text) > $length ? '...' : '' ) );
		}else{
			return trim( substr( $text, 0, $length ) . ( strlen($text) > $length ? '...' : '' ) );
		}
	}
}

function wpforo_admin_options_tabs( $tabs, $current = 'general', $subtab = FALSE, $sub_current = 'general' ) {
    if(!empty($tabs)){
    	$class_attr = $subtab ? 'vert_tab' : '';
	    echo '<h2 class="nav-tab-wrapper ' . esc_attr($class_attr) . '">';
	    foreach( $tabs as $tab => $name ){
	        $class = ( $tab === $current || ($subtab && $tab === $sub_current ) ) ? ' nav-tab-active' : '';
	        $sub = $subtab ? '&subtab='.esc_attr($tab) : '';
			$class = esc_attr($class);
			$current = esc_attr($current);
			$tab =  esc_attr($tab);
			$sub =  esc_attr($sub);
	        echo "<a class='nav-tab $class $class_attr' href='?page=". (string) wpfval($_GET, 'page') ."&tab=". ($subtab ? $current : $tab ) ."$sub'>$name</a>";
	    }
	    echo '</h2>';
	}
}

function wpforo_admin_tools_tabs( $tabs, $current = 'antispam', $subtab = FALSE, $sub_current = 'antispam' ) {
    if(!empty($tabs)){
    	$class_attr = $subtab ? 'vert_tab' : '';
	    echo '<h2 class="nav-tab-wrapper ' . esc_attr($class_attr) . '">';
	    foreach( $tabs as $tab => $name ){
	        $class = ( $tab == $current || ($subtab && $tab == $sub_current ) ) ? ' nav-tab-active' : '';
	        $sub = $subtab ? '&subtab='.esc_attr($tab) : '';
			$class = esc_attr($class);
			$current = esc_attr($current);
			$tab =  esc_attr($tab);
			$sub =  esc_attr($sub);
			$name = esc_html($name);
	        echo "<a class='nav-tab$class $class_attr' href='?page=wpforo-tools&tab=". ($subtab ? $current : $tab ) ."$sub' style='float:left'>$name</a>";
	    }
	    echo '</h2>';
	}
}

function wpforo_content_filter( $content ){
	if( strpos($content, '../') !== false ){
		$home_url = trim( preg_replace( array('#/?\?.*$#isu', '#index\.php/?#isu') , '', home_url() ), '/\\' ) . '/';
		$content = preg_replace('#((?:href|src)=[\'\"])(?:https?://)?(?:\.+/)+wp-content/#i', "$1" . $home_url . "wp-content/", $content);
	}
	$content = apply_filters('wpforo_body_text_filter', $content);
	if( apply_filters('wpforo_auto_embed_image', true) ) $content = preg_replace('#([^\'\"]|^)(https?://[^\s\'\"<>]+\.(?:jpg|jpeg|png|gif|ico|svg|bmp|tiff))([^\'\"]|$)#isu', '$1 <a class="wpforo-auto-embeded-link" href="$2" target="_blank"><img class="wpforo-auto-embeded-image" src="$2"/></a> $3', $content);
	if( apply_filters('wpforo_auto_embed_link', true ) ) $content = preg_replace('#([^\'\"]|^)(https?://[^\s\'\"<>]+)([^\'\"]|$)#isu', '$1 <a class="wpforo-auto-embeded-link" href="$2" target="_blank">$2</a> $3', $content);
	if(preg_match_all('#<pre([^<>]*)>(.*?class=[\'"]wpforo-auto-embeded[^\'"]*[\'"].*?)</pre>#isu', $content, $matches, PREG_SET_ORDER)){
		foreach($matches as $match){
			$match[2] = preg_replace('#<img[^<>]*class=[\'"]wpforo-auto-embeded-image[\'"][^<>]*src=[\'"]([^\'"]*)[\'"][^<>]*>#isu', '$1', $match[2]);
			$match[2] = preg_replace('#<a[^<>]*class=[\'"]wpforo-auto-embeded-link[\'"][^<>]*href=[\'"]([^\'"]*)[\'"][^<>]*>.*?</a>#isu', '$1', $match[2]);
			$content = str_replace($match[0], '<pre'.$match[1].'>'.$match[2].'</pre>', $content);
		}
	}
	$content = preg_replace('#(<a[^<>]*>[^<>]*)<a[^<>]*class=[\'"]wpforo-auto-embeded-link[\'"][^<>]*href=[\'"]([^\'"]*)[\'"][^<>]*>[^<>]*</a>([^<>]*</a>)#isu', '$1$2$3', $content);
	$content = apply_filters('wpforo_content_filter', $content);
	return wpautop($content);
}

function wpforo_remove_links( $content ){
	return preg_replace('#([^\'\"]|^)(https?://[^\s\'\"<>]+)([^\'\"]|$)#isu', '$1 [' . wpforo_phrase('removed link', false) . '] $3', $content);
}

add_filter('wpforo_content_filter', 'wpforo_nofollow_tag', 20);
function wpforo_nofollow_tag($content){
    $content = preg_replace_callback('#<a[^><]*?href=[\'\"]([^\'\"]+)[\'\"][^><]*?>#isu', 'wpforo_nofollow', $content);
    return $content;
}

function wpforo_nofollow($match){
    $link = $match[0];
    $nofollow = apply_filters('wpforo_external_link_nofollow', true);
    $dofollow = trim(WPF()->tools_misc['dofollow']);
    $dofollow = array_filter(array_map("trim", explode("\n", $dofollow)));
    $parse = parse_url( wpforo_get_request_uri() );
    $host = $parse['host'];
    $main_host = preg_replace('#^.*?([^\.]+?\.[^\.]+?)$#isu', '$1', $host);
    if( $nofollow ){
        if( strpos($link, 'rel=') === false && strpos($match[1], $main_host) === false ){
            $link_url = parse_url($match[1]);
            if( !(!empty($dofollow) && !empty($link_url['host']) && in_array($link_url['host'], $dofollow)) ){
                $link = str_replace('>', ' rel="nofollow">', $match[0]);
            }
        }
    }
    return $link;
}

add_action('wpforo_actions_end', 'wpforo_logs', 10);
function wpforo_logs(){
    if( !WPF()->current_object['is_404'] ){
	    WPF()->log->read();
	    WPF()->log->visit();
    }
}

add_action('wpforo_bottom_hook', 'wpforo_user_logging');
function wpforo_user_logging(){
	$data = WPF()->current_object;
	$filter_views = apply_filters( 'wpforo_filter_topic_views', true );
	if( wpfval($data, 'template') && $data['template'] == 'post' && wpfval($data, 'topicid') ){
		if($filter_views){
			//to-do: don't increase views before all read point.
			if( WPF()->tools_legal['cookies'] ){
				$viwed_ids = wpforo_getcookie( 'wpf_read_topics', false );
				if( empty($viwed_ids) || !wpfval($viwed_ids, $data['topicid']) ){
					WPF()->db->query("UPDATE `".WPF()->tables->topics."` SET `views` = `views` + 1 WHERE `topicid` = " . intval($data['topicid']));
				}
			}
            elseif( is_user_logged_in() ){
				if( wpfval(WPF()->current_usermeta, 'wpf_read_topics') ) {
					$viwed_db_ids = wpforo_current_usermeta( 'wpf_read_topics' );
					if( empty( $viwed_db_ids ) || !wpfval($viwed_db_ids, $data['topicid']) ){
						WPF()->db->query("UPDATE `".WPF()->tables->topics."` SET `views` = `views` + 1 WHERE `topicid` = " . intval($data['topicid']));
					}
				} else{
					WPF()->db->query("UPDATE `".WPF()->tables->topics."` SET `views` = `views` + 1 WHERE `topicid` = " . intval($data['topicid']));
				}
			}
		} else {
			WPF()->db->query("UPDATE `".WPF()->tables->topics."` SET `views` = `views` + 1 WHERE `topicid` = " . intval($data['topicid']));
		}
	}
}

function wpforo_setcookie( $key, $args = array(), $implode = false ) {
    if( !WPF()->tools_legal['cookies'] ) return;
    if( !empty($args) && is_array($args) ){
        $num = count($args);
        if( $num > 3 ){
            $max = apply_filters( 'wpforo_cookie_max_logged_topics', 10 );
            $delta = $num - $max;
            if( $delta > 0 ) $args = array_slice($args, $delta, null, true);
        }
    }
    if( !empty($args) && is_array($args) && $implode ) {
		$value = trim( implode( ',', $args ), ',' );
	}
	elseif( !empty($args) && is_array($args) && !$implode ){
		$value = json_encode($args);
	}
	if( !isset($value) ) $value = '';
	if( $key ){
        $secure = is_ssl();
        $secure_logged_in_cookie = $secure && 'https' === parse_url( get_option( 'home' ), PHP_URL_SCHEME );
        if ( COOKIEPATH != SITECOOKIEPATH ){
            @setcookie( $key, $value , time() + 7776000, SITECOOKIEPATH, COOKIE_DOMAIN, $secure_logged_in_cookie, true );
        } else {
            @setcookie( $key, $value , time() + 7776000, COOKIEPATH, COOKIE_DOMAIN, $secure_logged_in_cookie, true );
        }
	}
}

function wpforo_getcookie( $key, $explode = false ) {
    if( !WPF()->tools_legal['cookies'] ) return false;
	if( $cookie = wpfval($_COOKIE, $key) ){
		if($explode){
			return explode(',', $cookie);
		}else{
			$cookie = wp_unslash($cookie);
			if( !$data = json_decode( $cookie, true) ) return $cookie;
			return $data;
		}
	}
	return false;
}

function wpforo_is_bot() {
	if( !$http_user_agent = wpfval($_SERVER, 'HTTP_USER_AGENT') ) $http_user_agent = wpfval($_SERVER, 'http_user_agent');
    if( $http_user_agent ){
        return preg_match('#(abot|dbot|ebot|hbot|kbot|lbot|mbot|nbot|obot|pbot|rbot|sbot|tbot|vbot|ybot|zbot|bot\.|bot\/|_bot|\.bot|\/bot|\-bot|\:bot|\(bot|crawl|slurp|spider|seek|accoona|acoon|adressendeutschland|ah\-ha\.com|ahoy|altavista|ananzi|anthill|appie|arachnophilia|arale|araneo|aranha|architext|aretha|arks|asterias|atlocal|atn|atomz|augurfind|backrub|bannana_bot|baypup|bdfetch|big brother|biglotron|bjaaland|blackwidow|blaiz|blog|blo\.|bloodhound|boitho|booch|bradley|butterfly|calif|cassandra|ccubee|cfetch|charlotte|churl|cienciaficcion|cmc|collective|comagent|combine|computingsite|csci|curl|cusco|daumoa|deepindex|delorie|depspid|deweb|die blinde kuh|digger|ditto|dmoz|docomo|download express|dtaagent|dwcp|ebiness|ebingbong|e\-collector|ejupiter|emacs\-w3 search engine|esther|evliya celebi|ezresult|falcon|felix ide|ferret|fetchrover|fido|findlinks|fireball|fish search|fouineur|funnelweb|gazz|gcreep|genieknows|getterroboplus|geturl|glx|goforit|golem|grabber|grapnel|gralon|griffon|gromit|grub|gulliver|hamahakki|harvest|havindex|helix|heritrix|hku www octopus|homerweb|htdig|html index|html_analyzer|htmlgobble|hubater|hyper\-decontextualizer|ia_archiver|ibm_planetwide|ichiro|iconsurf|iltrovatore|image\.kapsi\.net|imagelock|incywincy|indexer|infobee|informant|ingrid|inktomisearch\.com|inspector web|intelliagent|internet shinchakubin|ip3000|iron33|israeli\-search|ivia|jack|jakarta|javabee|jetbot|jumpstation|katipo|kdd\-explorer|kilroy|knowledge|kototoi|kretrieve|labelgrabber|lachesis|larbin|legs|libwww|linkalarm|link validator|linkscan|lockon|lwp|lycos|magpie|mantraagent|mapoftheinternet|marvin\/|mattie|mediafox|mediapartners|mercator|merzscope|microsoft url control|minirank|miva|mj12|mnogosearch|moget|monster|moose|motor|multitext|muncher|muscatferret|mwd\.search|myweb|najdi|nameprotect|nationaldirectory|nazilla|ncsa beta|nec\-meshexplorer|nederland\.zoek|netcarta webmap engine|netmechanic|netresearchserver|netscoop|newscan\-online|nhse|nokia6682\/|nomad|noyona|nutch|nzexplorer|objectssearch|occam|omni|open text|openfind|openintelligencedata|orb search|osis\-project|pack rat|pageboy|pagebull|page_verifier|panscient|parasite|partnersite|patric|pear\.|pegasus|peregrinator|pgp key agent|phantom|phpdig|picosearch|piltdownman|pimptrain|pinpoint|pioneer|piranha|plumtreewebaccessor|pogodak|poirot|pompos|poppelsdorf|poppi|popular iconoclast|psycheclone|publisher|python|rambler|raven search|roach|road runner|roadhouse|robbie|robofox|robozilla|rules|salty|sbider|scooter|scoutjet|scrubby|search\.|searchprocess|semanticdiscovery|senrigan|sg\-scout|shai\'hulud|shark|shopwiki|sidewinder|sift|silk|simmany|site searcher|site valet|sitetech\-rover|skymob\.com|sleek|smartwit|sna\-|snappy|snooper|sohu|speedfind|sphere|sphider|spinner|spyder|steeler\/|suke|suntek|supersnooper|surfnomore|sven|sygol|szukacz|tach black widow|tarantula|templeton|\/teoma|t\-h\-u\-n\-d\-e\-r\-s\-t\-o\-n\-e|theophrastus|titan|titin|tkwww|toutatis|t\-rex|tutorgig|twiceler|twisted|ucsd|udmsearch|url check|updated|vagabondo|valkyrie|verticrawl|victoria|vision\-search|volcano|voyager\/|voyager\-hc|w3c_validator|w3m2|w3mir|walker|wallpaper|wanderer|wauuu|wavefire|web core|web hopper|web wombat|webbandit|webcatcher|webcopy|webfoot|weblayers|weblinker|weblog monitor|webmirror|webmonkey|webquest|webreaper|websitepulse|websnarf|webstolperer|webvac|webwalk|webwatch|webwombat|webzinger|wget|whizbang|whowhere|wild ferret|wordpress|worldlight|wwwc|wwwster|xenu|xget|xift|xirq|yandex|yanga|yeti|yodao|zao\/|zippp|zyborg|\.\.\.\.)#i', $http_user_agent);
    }
    return true;
}

//Option value filter and output
function wpfo( $option = '', $echo = true, $esc = 'esc_attr' ){
	if( is_string($option) ){
		$option = stripslashes( $option );
		if( $esc == 'esc_attr' ){
			$option = esc_attr( $option );
		}
		elseif( $esc == 'esc_html' ){
			$option = esc_html( $option );
		}
		elseif( $esc == 'esc_url' ){
			$option = esc_url( $option );
		}
		elseif( $esc == 'esc_textarea' ){
			$option = esc_textarea( $option );
		}
	}

	if( $echo ){
		echo $option;
	}
	else{
		return $option;
	}
}

//Option maker for checkbox, radio and select
function wpfo_check( $option = '', $value = '', $type = 'checked' , $echo = true ){
	$option = (isset($option) && isset($value) && $option == $value ) ? $type : '';
	if( $echo ){
		echo $option;
	}
	else{
		return $option;
	}
}

/**
 * Validates keys of requested array.
 *
 * @param array $array
 * @param string|int $key
 * @param string|int ... $_, ... [optional] more keys
 *
 * @return bool
 */
function wpfkey(&$array, $key){
    $a = $array;
	foreach ( func_get_args() as $arg_num => $key ){
		if($arg_num === 0) continue;
		if( is_array($a) && (is_string($key) || is_int($key)) && array_key_exists($key, $a) ){
			$a = $a[$key];
		}else{
		    return false;
        }
	}
	return true;
}

/**
 * get values of requested array keys if found otherwise returns null to allow you cast to your desired type of variable.
 *
 * @param mixed $array
 * @param string|int ... $_, ... [optional] more keys
 *
 * @return mixed|null
 */
function wpfval(&$array){
	if( func_num_args() === 1 ) return $array;
	$a = $array;
	foreach ( func_get_args() as $arg_num => $key ){
		if($arg_num === 0) continue;
		if( is_array($a) && (is_string($key) || is_int($key)) && array_key_exists($key, $a) ){
			$a = $a[$key];
		}else{
			return null;
		}
	}
	return $a;
}

/**
 * Always return the preferred default value if the array, or array index is undefined.
 *
 * @param mixed         the default value if current index is undefined in the array
 * @param mixed         the $array to be checked
 * @param string|int    ... $_, ... [optional] more keys
 *
 * @return mixed|null
 */
function wpffix($return, $array, $a = null, $b = null, $c = null){
	$value = wpfval($array, $a, $b, $c);
	if( !is_null($value) ){
	    return $value;
    }
	return $return;
}

function wpforo_human_filesize($bytes, $decimals = 2) {
    $size = array('B','KB','MB','GB','TB','PB','EB','ZB','YB');
    $factor = floor((strlen($bytes) - 1) / 3);
    return sprintf("%.{$decimals}f", $bytes / pow(1024, $factor)) . '&nbsp;' . @$size[$factor];
}

if( !function_exists('wpforo_date_raw') ) {
	/**
	 * @param string $date
	 * @param string $type
	 * @param bool $echo
	 *
	 * @return string|void
	 */
	function wpforo_date_raw( $date, $type = 'ago', $echo = true ) {
		if ( ! $echo ) {
			return wpforo_date( $date, $type, $echo, false );
		}
		wpforo_date( $date, $type, $echo, false );
	}
}

function wpforo_date( $date, $type = 'ago', $echo = true, $wp_date_format = true ) {
	date_default_timezone_set('UTC');
	ini_set( 'date.timezone', 'UTC' );

	$timestamp0 = $timestamp = is_numeric($date) ? $date : strtotime($date);
	$diff = current_time( 'timestamp', 1 ) - $timestamp0;
	$timezone_string = get_option('timezone_string', '');
	$current_offset = get_option('gmt_offset', '');
	if(!is_string($type)) $type = 'ago';

	if( $current_user_timezone = wpfval(WPF()->current_user, 'timezone') ){
		if( preg_match('#UTC\s*([-+])\s*([\d.]+)#i', $current_user_timezone, $timezone_array) ){
			$timezone_string = '';
			$current_offset = $timezone_array[1] . $timezone_array[2];
		}else{
			if(in_array($current_user_timezone, timezone_identifiers_list())){
				$timezone_string = $current_user_timezone;
				$current_offset = '';
			}else{
				$timezone_string = '';
				$current_offset = '';
			}
		}
	}

	if( $timezone_string === '' && $current_offset !== '' ){
		$timestamp += $current_offset * 3600;
	}elseif( class_exists('DateTime') && class_exists('DateTimeZone') && $timezone_string ){
		try {
			$dt = new DateTime( date('Y-m-d H:i:s', $timestamp), new DateTimeZone($timezone_string) );
			$timestamp += $dt->getOffset();
		} catch (Exception $e) {}
	}

	if( $type === 'human' ){
		$d = human_time_diff($timestamp0);
	}elseif( $type === 'ago' ){
		$d = human_time_diff($timestamp0);
		$d = sprintf( wpforo_phrase('%s ago', false, false), $d );
	}elseif( $type === 'ago-date' ){
		if( $diff > 31536000 ){
			$format = apply_filters('wpforo_date_format_ago_date_year', 'M d, y');
			$d = date_i18n($format, $timestamp);
		}elseif( $diff > 518400 ){
			$format = apply_filters('wpforo_date_format_ago_date_month', 'M d');
			$d = date_i18n($format, $timestamp);
		}else {
			$d = human_time_diff($timestamp0);
			$d = sprintf( wpforo_phrase('%s ago', false, false), $d );
		}
	}else{
		$type = wpforo_date_format($type, $wp_date_format);
		$d = date_i18n($type, $timestamp);
	}

	if($echo) echo $d;
	return $d;
}

function wpforo_date_format($type, $wp_date_format = true){
	$sep = ' ';
	$wp_format = $wp_date_format && wpforo_feature('wp-date-format');
	if( $wp_format ){
		$date_format = get_option('date_format');
		$time_format = get_option('time_format');
	} else {
		$date_format = 'Y-m-d';
		$time_format = 'H:i:s';
	}
	if( in_array($type, array('datetime', 'date', 'time')) ){
		if( $type == 'datetime' ){
			$type = $date_format . $sep . $time_format;
		}elseif( $type == 'date' ) {
			$type = $date_format;
		}elseif( $type = 'time' ){
			$type = $time_format;
		}
	}elseif( $wp_format ){
		$type = $date_format . $sep . $time_format;
	}
	return $type;
}

function wpforo_write_file( $new_file, $content ){
	$return = array( 'error' => false, 'file' => '' );
	$ifp = @fopen( $new_file, 'wb' );
	if ( ! $ifp ) {
	    @fclose( $ifp );
		$return = array( 'error' => sprintf( __( 'Could not write file %s' ), $new_file ) );
	}
	else{
		@fwrite( $ifp, $content );
		fclose( $ifp );
		clearstatcache();
		// Set correct file permissions
		$stat = @stat( dirname( $new_file ) );
		$perms = $stat['mode'] & 0007777;
		$perms = $perms & 0000666;
		@chmod( $new_file, $perms );
		clearstatcache();
		$return = array( 'file' => $new_file );
	}
	return $return;
}

function wpforo_get_file_content( $file ){
    if( is_file( $file ) ){
	    $fp = @fopen( $file, 'r' );
	    if( !$fp ){
		    if( is_resource($fp) ) @fclose( $fp );
		    return false;
	    }
	    else{
		    $size = @filesize($file);
		    if( isset($size) && $size > 0 ){
			    $file_data = fread( $fp, $size );
			    fclose( $fp );
			    return $file_data;
		    }
		    @fclose( $fp );
		    return false;
	    }
    }
	return false;
}

################################################################################
/**
 * Clears file basename and removes trailing slash
 *
 * @since 1.0.0
 *
 * @param	string		filename
 *
 * @return	string
 */
function wpforo_clear_basename( $file ) {
	$file = str_replace('\\','/',$file);
	$file = preg_replace('|/+|','/', $file);
	$file = trim($file, '/');
	return $file;
}

#################################################################################
/**
 * Removes directory with all files and folders
 *
 * @since 1.0.0
 *
 * @param	string		directory name
 *
 */
function wpforo_remove_directory( $directory ) {
	$directory_ns = trim( $directory, '/') . '/';
	$directory_ws = '/' . trim( $directory, '/') . '/';
	$glob = glob( $directory_ns . '/*' );
	if( empty($glob) ) $glob = glob( $directory_ws . '/*' );
    foreach( $glob as $item ) {
		if( is_dir( $item ) ){
			wpforo_remove_directory( $item );
		}
        else{
			unlink( $item );
		}
    }
    return rmdir( $directory );
}

#################################################################################
/**
 * Converts bytes to KB, MB, GB
 *
 * @since 1.0.0
 *
 * @param	integer		Bytes
 *
 * @return	string
 */
function wpforo_print_size($value, $points = true ){
	if($value < 1024){
		return $value . (($points) ? "B" : '' );
	}elseif($value >= 1024 && $value < (1024*1024)){
		$value = round(($value/1024)*10)/10;
		return $value . (($points) ? "KB" : '' );
	}elseif($value >= 1024*1024 && $value < 1024*1024*1024){
		$value = round(($value/(1024*1024))*10)/10;
		return $value . (($points) ? "MB" : '' );
	}elseif($value >= 1024*1024*1024 && $value <= 1024*1024*1024*1024){
		$value = round(($value/(1024*1024*1024))*10)/10;
		return $value . (($points) ? "GB" : '' );
	}else{
		$value = round(($value/(1024*1024*1024*1024))*10)/10;
		return $value . (($points) ? "TB" : '' );
	}
}

function wpforo_human_size_to_bytes($sSize){
    if (is_numeric($sSize)) return $sSize;

    $sSuffix = substr($sSize, -1);
    $iValue = substr($sSize, 0, -1);
    switch (strtoupper($sSuffix)) {
        case 'M':
            $iValue *= 1024*1024;
            break;
        case 'K':
            $iValue *= 1024;
            break;
        case 'G':
            $iValue *= 1024*1024*1024;
            break;
        case 'T':
            $iValue *= 1024*1024*1024*1024;
            break;
        case 'P':
            $iValue *= 1024*1024*1024*1024*1024;
            break;
    }
    return $iValue;
}

function wpforo_print_number($n, $echo = false) {
    $x = str_replace(",","",$n);
    $x = intval($x);
    $n = 0 + $x;
    $number = 0;
    if(!is_numeric($n)) return false;
    if($n>1000000000000) $number = round(($n/1000000000000),1).' '.str_replace('{number}', '', wpforo_phrase('{number}T',false));
    else if($n>1000000000) $number = round(($n/1000000000),1).' '.str_replace('{number}', '', wpforo_phrase('{number}B',false));
    else if($n>1000000) $number = round(($n/1000000),1).' '.str_replace('{number}', '', wpforo_phrase('{number}M',false));
    else if($n>10000) $number = round(($n/1000),1).' '.str_replace('{number}', '', wpforo_phrase('{number}K',false));

    $number = ( $number ) ? $number : number_format($n);

    if($echo){
        echo $number;
    }
    else{
        return $number;
    }
}

function wpforo_bigintval($value) {
	$value = wpforo_settype($value, 'string');
	if( is_string($value) ){
		$value = trim($value);
		if( !ctype_digit($value) ) {
			$value = preg_replace("#[^0-9](.*)$#s", '', $value);
			if( !ctype_digit($value) ) {
				$value = 0;
			}
		}
	}else{
		$value = 0;
	}

	$value = (strlen($value) < strlen(PHP_INT_MAX)) ? (int) $value : $value;
	return $value;
}

function wpforo_removebb($string){
	if(isset($string) && $string ){
		$string = preg_replace('|\[\/*[^\]\[]+\]|is', '', $string);
	}
	return $string;
}

function wpforo_file_upload_error($code){
	switch ($code) {
		case UPLOAD_ERR_INI_SIZE:
			$message = wpforo_phrase("The uploaded file exceeds the upload_max_filesize directive in php.ini", false);
			break;
		case UPLOAD_ERR_FORM_SIZE:
			$message = wpforo_phrase("The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form", false);
			break;
		case UPLOAD_ERR_PARTIAL:
			$message = wpforo_phrase("The uploaded file was only partially uploaded", false);
			break;
		case UPLOAD_ERR_NO_FILE:
			$message = wpforo_phrase("No file was uploaded", false);
			break;
		case UPLOAD_ERR_NO_TMP_DIR:
			$message = wpforo_phrase("Missing a temporary folder", false);
			break;
		case UPLOAD_ERR_CANT_WRITE:
			$message = wpforo_phrase("Failed to write file to disk", false);
			break;
		case UPLOAD_ERR_EXTENSION:
			$message = wpforo_phrase("File upload stopped by extension", false);
			break;
		default:
			$message = wpforo_phrase("Unknown upload error", false);
			break;
	}
	return $message;
}

//$key allowed values are post, strip, data, user_description entities or the name of a field filter such as pre_user_description.
//More info https://core.trac.wordpress.org/browser/tags/4.5.2/src/wp-includes/kses.php#L624
function wpforo_kses( $string = '', $key = 'post' ){
	if(!$string || !$key) return $string;
	if( $key === 'email' ){
	    if( !preg_match('#<p[^<>]*?>#iu', $string) ) $string = wpautop($string);
		$allowed_html = array( 'a' => array( 'href' => array(), 'title' => array(), 'style' => array()),
                               'img' => array( 'src' => array(), 'width' => array(), 'height' => array(), 'align' => array(), 'alt' => array(), 'style' => array() ),
							   'blockquote' => array('style' => array()),
							   'h1' => array('style' => array()), 'h2' => array('style' => array()), 'h3' => array('style' => array()), 'h4' => array('style' => array()), 'h5' => array('style' => array()), 'h6' => array('style' => array()),
							   'hr' => array(),
							   'br' => array(),
							   'p' => array('style' => array()),
							   'strong' => array('style' => array()),
							   'em' => array('style' => array()),
							   'del' => array('style' => array()),
                               'style' => array());
        $allowed_html = apply_filters('wpforo_kses_allowed_html_email', $allowed_html);
	}
	elseif( $key === 'user_description' ){
        $allowed_html = wp_kses_allowed_html( $key );
        $allowed_html['img'] = array( 'alt' => array(), 'align' => array(), 'border' => array(), 'height' => array(), 'hspace' => array(), 'longdesc' => array(), 'vspace' => array(), 'src' => array(), 'usemap' => array(), 'width' => array());
        $allowed_html['br'] = array();
        $allowed_html = apply_filters('wpforo_kses_allowed_html_user_description', $allowed_html);
    }
    else{
        global $allowedposttags;
        $allowed_html = $allowedposttags;
        if(wpforo_feature('content-do_shortcode')){
            $allowed_html = wp_kses_allowed_html( $key );
        }
        $extra_html = WPF()->tools_antispam['html'];
        $allowed_html = wpforo_extra_html_parser($extra_html, $allowed_html);
        $allowed_html['a']['data-gallery'] = array();
        $allowed_html['a']['download'] = array();
        $allowed_html['blockquote']['class'] = TRUE;
        $allowed_html['blockquote']['data-width'] = TRUE;
        $allowed_html['blockquote']['data-userid'] = TRUE;
        $allowed_html['blockquote']['data-postid'] = TRUE;
        $allowed_html['p']['lang'] = TRUE;
        $allowed_html['p']['dir'] = TRUE;
        $allowed_html['pre']['contenteditable'] = TRUE;
        if(!wpfval($allowed_html, 'iframe') && class_exists('wpForoEmbeds')){
            $allowed_html['iframe'] = array('width' => array(), 'height' => array(), 'src' => array(), 'frameborder' => array(), 'allowfullscreen' => array());
        }
        $allowed_html = apply_filters('wpforo_kses_allowed_html', $allowed_html);
    }
	return wp_kses( $string, $allowed_html );
}

function wpforo_deep_merge($default, $current = array()){
	foreach($default as $k => $v){
		if(!empty($v) && is_array($v)){
			foreach($v as $kk => $vv){
				if(!empty($vv) && is_array($vv)){
					foreach($vv as $kkk => $vvv){
						if(!empty($vvv) && is_array($vvv)){
							foreach($vvv as $kkkk => $vvvv){
								if(!empty($vvv) && is_array($vvv)){
									//Stop on 5th level
								}
								else{
									if(isset($current[$k][$kk][$kkk][$kkkk])) $default[$k][$kk][$kkk][$kkkk] = $current[$k][$kk][$kkk][$kkkk];
								}
							}
						}
						else{
							if(isset($current[$k][$kk][$kkk])) $default[$k][$kk][$kkk] = $current[$k][$kk][$kkk];
						}
					}
				}
				else{
					if(isset($current[$k][$kk])) $default[$k][$kk] = $current[$k][$kk];
				}
			}
		}
		else{
			if(isset($current[$k])) $default[$k] = $current[$k];
		}
	}
	return $default;
}

function wpforo_is_image($e){
	return (bool) preg_match('#^(jpe?g|png|gif|bmp|tiff?)$#i', $e);
}

function wpforo_is_video($e){
    return (bool) preg_match('#^(mp4|webm|ogg)$#i', $e);
}

function wpforo_is_audio($e){
    return (bool) preg_match('#^(mp3|wav|ogg)$#i', $e);
}

function get_wpf_option( $option, $default = null ){
	$cache = get_option('wpforo_features');
    $cache = wpfval($cache, 'option_cache');
    $option_hash = ( defined('AUTH_KEY') ) ? md5($option . AUTH_KEY ) : md5($option);       //protect option files with site specific hashed file names + .htaccess control
    $option_file = WPFORO_CACHE_DIR . '/item/option/' . $option_hash;
	if ( $cache ) {
	    $value = maybe_unserialize( wpforo_get_file_content($option_file) );
	    if( !empty($value) && $value ) {
	        return apply_filters('get_wpf_option', $value, $option, $default);                               //don't cache falsy values like NULL, '', 0, array() to avoid cache existence detection issues
	    }
	}
	$value = get_option($option, $default);
	if( $value ){
		$value = maybe_unserialize( $value );
		if(is_serialized( $value )) {
			$check = @unserialize($value);
			if( !$check ) $value = wpforo_fixSerializedArray($value);
		}
	}
	$value = wpforo_settype($value, gettype($default));
	if( $default && is_array($default) && is_array($value) ) $value = wpforo_array_args_cast_and_merge($value, $default);
	if( $cache ){
		if( !file_exists( $option_file ) ){
			wpforo_write_file( $option_file , maybe_serialize($value) );
		}
	}
    return apply_filters('get_wpf_option', $value, $option, $default);
}

/**
* Extract what remains from an unintentionally truncated serialized string
* $data contains your original array (or what remains of it).
* @param string The serialized array
*/
function wpforo_fixSerializedArray($serialized){
    $tmp = preg_replace('/^a:\d+:\{/', '', $serialized);
    return wpforo_fixSerializedArray_R($tmp);
}
/**
* The recursive function that does all of the heavy lifing. Do not call directly.
* @param string The broken serialzized array
* @return string Returns the repaired string
*/
function wpforo_fixSerializedArray_R(&$broken){
    $data       = array();
    $index      = NULL;
    $len        = strlen($broken);
    $i          = 0;
    while(strlen($broken)) {
        $i++;
        if ($i > $len) { break; }
        if (substr($broken, 0, 1) == '}') {
            $broken = substr($broken, 1); return $data;
        }
		else{
            $bite = substr($broken, 0, 2);
            switch($bite) {
                case 's:':
                    $re = '/^s:\d+:"([^\"]*)";/';
                    if (preg_match($re, $broken, $m)){
                        if ($index === NULL){ $index = $m[1]; }
                        else{$data[$index] = $m[1]; $index = NULL;}
                        $broken = preg_replace($re, '', $broken);
                    }
                break;
                case 'i:':
                    $re = '/^i:(\d+);/';
                    if (preg_match($re, $broken, $m)){
                        if ($index === NULL){$index = (int) $m[1]; }
                        else{$data[$index] = (int) $m[1]; $index = NULL; }
                        $broken = preg_replace($re, '', $broken);
                    }
                break;
                case 'b:':
                    $re = '/^b:[01];/';
                    if (preg_match($re, $broken, $m)){
                        $data[$index] = (bool) $m[1]; $index = NULL; $broken = preg_replace($re, '', $broken);
                    }
                break;
                case 'a:':
                    $re = '/^a:\d+:\{/';
                    if (preg_match($re, $broken, $m)){
                        $broken = preg_replace('/^a:\d+:\{/', '', $broken); $data[$index] = wpforo_fixSerializedArray_R($broken); $index = NULL;
                    }
                break;
                case 'N;':
                    $broken = substr($broken, 2); $data[$index] = NULL; $index = NULL;
                break;
            }
        }
    }
    return $data;
}

function wpforo_insert_to_media_library( $attach_path, $title = '' ){
	if( wpforo_feature('attach-media-lib') ){
		if(!$attach_path ) return 0;
		$attach_fname = basename($attach_path);
		if(!$title) $title = $attach_fname;
		require_once(ABSPATH . 'wp-admin/includes/media.php');
		require_once(ABSPATH . 'wp-admin/includes/file.php');
		require_once(ABSPATH . 'wp-admin/includes/image.php');
		$wp_upload_dir = wp_upload_dir();
		$filetype = wp_check_filetype( $attach_fname, NULL );
		$attachment = array( 'guid' => $attach_path, 'post_mime_type' => $filetype['type'], 'post_title' => $title, 'post_content' => '', 'post_status' => 'inherit');
		$attach_id = wp_insert_attachment( $attachment, $attach_path );
		add_filter( 'intermediate_image_sizes', 'wpforo_attachment_sizes' );
		$attach_data = wp_generate_attachment_metadata( $attach_id, $attach_path );
		wp_update_attachment_metadata( $attach_id, $attach_data );
		remove_filter( 'intermediate_image_sizes', 'wpforo_attachment_sizes' );
		return $attach_id;
	}

	return 0;
}

function wpforo_attachment_sizes( $sizes ){
	return array('thumbnail');
}

function wpforo_debug(){
	if( wpforo_feature('debug-mode') ) : ?>
		<div id="wpforo-debug" style="display:none">
	        <h4>Super Globals</h4>
	        <p>Requests: <?php print_r($_REQUEST); ?></p>
	        <p>Server: <?php print_r($_REQUEST); ?></p>
	        <h4>Options and Features</h4>
	        <textarea style="width:500px; height:300px;"><?php echo @ 'permastruct: ' . WPF()->permastruct . "\r\n";
	        echo @ 'use_home_url: ' . WPF()->use_home_url . "\r\n";
	        echo @ 'url: ' . wpforo_home_url() . "\r\n";
	        @print_r(WPF()->general_options) . "\r\n";
	        echo @ 'pageid:' . WPF()->pageid . "\r\n";
	        echo @ 'default_groupid: ' . WPF()->usergroup->default_groupid . "\r\n";
	        @print_r(WPF()->forum->options) . "\r\n";
	        @print_r(WPF()->post->options) . "\r\n";
	        @print_r(WPF()->member->options) . "\r\n";
	        @print_r(WPF()->sbscrb->options) . "\r\n";
	        @print_r(WPF()->features) . "\r\n";
	        @print_r(WPF()->tpl->style) . "\r\n";
	        @print_r(WPF()->tpl->options) . "\r\n";
	        @print_r(WPF()->tpl->theme) . "\r\n";
	        ?>
	        </textarea>
	    </div>
    	<?php
    endif;
}

function wpforo_hook( $name, $args = array() ){
	do_action( $name, $args );
}

#################################################################################
/**
 * Cleans forum cache
 *
 * @since 1.2.1
 *
 * @param	string		Item View / Template	(e.g.: 'forum', 'topic', 'post', 'user', 'widget', etc...)
 * @param	integer		Item ID					(e.g.: $topicid or $postid) | (!) ID is 0 on dome actions (e.g.: delete actions)
 * @param   array		Item data as array
 *
 */
function wpforo_clean_cache( $template = 'all', $id = 0, $item = array() ){
	do_action( 'wpforo_clean_cache_start', $id, $template );

	if( !$pageid = WPF()->pageid ) $pageid = wpforo_wp_url_to_postid( $_SERVER['REQUEST_URI'] );
	if( intval($pageid) ) clean_post_cache( $pageid );

	WPF()->statistic('update', $template);
	do_action( 'wpforo_clean_cache', $id, $template );
	WPF()->cache->clean( $id, $template, $item );
	do_action( 'wpforo_clean_cache_end', $id, $template );
}

// wpforo  database checker fixer tools
function wpforo_update_db(){
	$problems = wpforo_database_check();
	if( !empty($problems) ){
		$SQL = wpforo_database_fixer( $problems );
		if(wpfval($SQL, 'fields')){
			foreach( $SQL['fields'] as $query ) WPF()->db->query( $query );
		}
		if(wpfval($SQL, 'keys')){
			foreach( $SQL['keys'] as $query ) WPF()->db->query( $query );
		}
		if(wpfval($SQL, 'tables')){
			foreach( $SQL['tables'] as $query ){
				if( FALSE === WPF()->db->query( $query ) ) {
					WPF()->db->query( preg_replace('#)[\r\n\t\s]*ENGINE.*$#isu', ')', $query) );
				}
			}
		}
	}
	update_option('wpforo_version_db', WPFORO_VERSION);
}

/**
 * @param array $args
 *
 * @return bool|string|null
 */
function wpforo_db_check( $args = array() ){
    $key = array('wpforo_db_check', $args);
    if( WPF()->ram_cache->is_exist($key) ) return WPF()->ram_cache->get($key);

	global $wpdb;

	$col = esc_sql(trim( wpfval($args, 'col') ));
	$table = esc_sql(trim( wpfval($args, 'table') ));

	$result = null;
	switch( trim( wpfval($args, 'check') ) ){
        case 'table_exists':
	        $result = (bool) $wpdb->get_var("SHOW TABLES LIKE '{$table}'");
        break;
        case 'col_exists':
	        $result = (bool) $wpdb->get_var("SHOW COLUMNS FROM `{$table}` LIKE '{$col}'");
        break;
		case 'key_exists':
			$result = (bool) $wpdb->get_var("SHOW KEYS FROM `{$table}` WHERE `Key_name` = '{$col}'");
        break;
        case 'default_value':
	        $c = (array) $wpdb->get_row("SHOW COLUMNS FROM `{$table}` LIKE '{$col}'", ARRAY_A);
	        $result = wpfval($c, 'Default');
        break;
        case 'col_type':
	        $c = (array) $wpdb->get_row("SHOW COLUMNS FROM `{$table}` LIKE '{$col}'", ARRAY_A);
	        $result = wpfval($c, 'Type');
        break;
	}

	WPF()->ram_cache->set($key, $result);
	return $result;
}


function wpforo_database_check(){
    $_tables = array(); $_table_diffs = array();
	require_once( WPFORO_DIR . '/wpf-includes/install-sql.php' );
	$wpforo_sql = wpforo_get_install_sqls();
    if( !empty($wpforo_sql) ) {
        foreach( $wpforo_sql as $sql ) {
            if( preg_match('|EXISTS \`([^\(]+)\`\s*\((.+)(PRIMARY.+)\)\s*ENGINE|is', $sql, $table)){
                if( wpfval($table, 1) ){
                    if( wpfval($table, 2) ){
                        if( preg_match_all('|\`([^\`]+)\`|is', $table[2], $fields, PREG_SET_ORDER) ){
                            foreach( $fields as $field ){
                                if(wpfval($field, 1)) $_tables[ $table[1] ]['fields'][] = $field[1];
                            }
                        }
                    }
                    if( wpfval($table, 3) ){
                        if( preg_match('|PRIMARY KEY \(\`([^\`]+)\`\)|is', $table[3], $primary_key) ){
                            $_tables[ $table[1] ]['keys'][] = $primary_key[1];
                        }
                        if( preg_match_all('|KEY \`([^\`]+)\`|is', $table[3], $keys, PREG_SET_ORDER) ){
                            foreach( $keys as $key ){
                                if(wpfval($key, 1))$_tables[ $table[1] ]['keys'][] = $key[1];
                            }
                        }
                    }
                }
            }
        }
        if( !empty($_tables) ){
            foreach( $_tables as $_name => $_structure ){
                $_table_fields = array(); $_table_keys = array();
                $_table_exists = WPF()->db->get_var("SHOW TABLES LIKE '" . esc_sql($_name) ."'" );
                if( $_table_exists ){
                    //Problems - Missing Field
                    if(wpfval($_structure, 'fields')){
                        $_fields = WPF()->db->get_results("SHOW FULL COLUMNS FROM " . esc_sql($_name), ARRAY_A);
                        foreach( $_fields as $_field ) $_table_fields[] = $_field['Field'];
                        $_count_orig = count($_structure['fields']);
                        $_count_curr = count($_table_fields);
                        if( (int) $_count_curr < (int) $_count_orig ){
                            $diff = array_diff($_structure['fields'], $_table_fields);
                            if( !empty($diff) ) $_table_diffs[$_name]['fields'][$_name] = $diff;
                        }
                    }
                    //Problems - Missing Key
                    if(wpfval($_structure, 'keys')){
                        $_keys = WPF()->db->get_results("SHOW KEYS FROM " . esc_sql($_name), ARRAY_A);
                        foreach( $_keys as $_key ) {
                            if( strpos($_key['Key_name'], 'PRIMARY') !== FALSE ){
                                $_table_keys[] = $_key['Column_name'];
                            } else {
                                $_table_keys[] = $_key['Key_name'];
                            }
                        }
                        $_table_keys = array_unique($_table_keys);
                        $_table_keys = array_values($_table_keys);
                        $_count_orig = count($_structure['keys']);
                        $_count_curr = count($_table_keys);
                        if( (int) $_count_curr < (int) $_count_orig ){
                            $diff_keys = array_diff($_structure['keys'], $_table_keys);
                            if( !empty($diff_keys) ) $_table_diffs[$_name]['keys'][$_name] = $diff_keys;
                        }
                    }
                } else {
                    //Problems - Missing Table
                    $_table_diffs[$_name]['exists'] = 'no';
                }
            }
        }
    }
    return $_table_diffs;
}

function wpforo_database_parse(){
    $_tables = array(); $_table_diffs = array();
	require_once( WPFORO_DIR . '/wpf-includes/install-sql.php' );
	$wpforo_sql = wpforo_get_install_sqls();
    if( !empty($wpforo_sql) ) {
        foreach( $wpforo_sql as $sql ) {
            if( preg_match('|EXISTS \`([^\(]+)\`\s*\((.+)(PRIMARY.+)\)\s*ENGINE|is', $sql, $table)){
                if( wpfval($table, 1) ){
                    if( wpfval($table, 2) ){
                        $_tables[ $table[1] ]['fields'] = array_map('trim', explode(',', $table[2] ));
                    }
                    if( wpfval($table, 3) ){
                        $_tables[ $table[1] ]['keys'] = array_map('trim', explode(PHP_EOL, $table[3] ));
                    }
                }
            }
        }
    }
    return $_tables;
}

function wpforo_database_fixer( $problems ){
    if( !empty($problems) ){
        $SQL = array();
	    require_once( WPFORO_DIR . '/wpf-includes/install-sql.php' );
        $table_structure = wpforo_database_parse();
        if( !empty($table_structure) ){
            foreach( $problems as $table_name => $problem ){
                if( wpfval($problem, 'fields') ){
                    foreach( $problem['fields'] as $problem_fields ){
                        if( !empty($problem_fields) ){
                            foreach( $problem_fields as $problem_field ){
                                if( wpfval($table_structure, $table_name, 'fields') ){
                                    foreach( $table_structure[$table_name]['fields'] as $field_sql ){
                                        if( strpos($field_sql, '`'. $problem_field .'`') !== FALSE ){
                                            $SQL['fields'][] = 'ALTER TABLE `' . $table_name . '` ADD ' . $field_sql . ';';
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
                if( wpfval($problem, 'keys') ){
                    foreach( $problem['keys'] as $problem_keys ){
                        if( !empty($problem_keys) ){
                            foreach( $problem_keys as $problem_key ){
                                if( wpfval($table_structure, $table_name, 'keys') ){
                                    foreach( $table_structure[$table_name]['keys'] as $key_sql ){
                                        if( preg_match('|KEY \`'. $problem_key .'\`|is', $key_sql ) ){
                                            $SQL['keys'][] = 'ALTER TABLE `' . $table_name . '` ADD ' . trim( $key_sql, ',' ) . ';';
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
                if( wpfval($problem, 'exists') ){
                    $wpforo_sql = wpforo_get_install_sqls();
                    if( wpfval($wpforo_sql, $table_name) ) {
                        $SQL['tables'][] = preg_replace('|\t+|', ' ', $wpforo_sql[$table_name]);
                    }
                }
            }
        }
    }
    return $SQL;
}

function wpforo_add_unique_key($table, $primary_key, $unique_key_name = '', $unique_fields = ''){

    $table = esc_sql(trim($table));
    $primary_key = esc_sql(trim($primary_key));
    $unique_fields = esc_sql(trim($unique_fields, ','));
    $unique_fields_clean = preg_replace('|\([^\(\)]+\)|', '', $unique_fields);
    $remove_rows = '';
    $sql = "SELECT GROUP_CONCAT(`$primary_key`) duplicated_row_ids, 
                COUNT(*) duplication_count FROM 
                    `$table` GROUP BY $unique_fields_clean HAVING  duplication_count > 1";

    $rows = WPF()->db->get_results($sql, ARRAY_A);
    if(!empty($rows)){
        foreach($rows as $row){
            $ids = explode(',', $row['duplicated_row_ids']);
            $ids = array_reverse($ids);
            $ids = array_slice($ids, 1);
            $remove_rows .= trim(implode(',', $ids), ',') . ',';
        }
        $remove_rows = esc_sql(trim($remove_rows, ','));
        if( $remove_rows ) {
            WPF()->db->query("DELETE FROM `$table` WHERE `$primary_key` IN($remove_rows)");
        }
    }
    $sql = "ALTER TABLE `$table` ADD UNIQUE KEY `$unique_key_name`( $unique_fields )";
    WPF()->db->query($sql);
}

function wpforo_is_owner( $userid, $email = '' ) {
	if ( WPF()->current_userid ) {
		return WPF()->current_userid === wpforo_bigintval($userid);
	} elseif ( $email && WPF()->current_user_email ) {
		return WPF()->current_user_email === $email;
	}

	return false;
}

/**
 * @deprecated since 1.6.6
 *
 * @param string $display_name
 * @param string $user_nicename
 *
 * @return string
 */
function wpforo_make_dname($display_name, $user_nicename) {
	$display_name  = trim( $display_name );
	$user_nicename = trim( $user_nicename );

	return ( $display_name ? esc_html( $display_name ) : esc_html( urldecode( $user_nicename ) ) );
}

/**
 * @param array $user wpforo user array
 * @param bool $echo
 *
 * @return string|void
 */
function wpforo_user_dname($user, $echo = false){
	$display_name  = trim(wpfval($user, 'display_name'));
	$user_nicename = trim(wpfval($user, 'user_nicename'));
	$dname = $display_name ? esc_html($display_name) : esc_html( urldecode($user_nicename) );
	if(!$echo) return $dname;
	echo $dname;
}

function wpforo_strlen($string ){
	if(!$string) return 0;
	if(function_exists('mb_strlen')){
		return mb_strlen($string);
	}
	else{
		return strlen($string);
	}
}

function wpforo_string2array( $string, $regexp = '' ){
	if( !$regexp ) $regexp = '#' . preg_quote(PHP_EOL) . '#isu';
	$array = preg_split($regexp, $string);
    return array_filter($array);
}

function wpforo_array_ordered_intersect_key($array1, $array2){
    $new_array = array();
    foreach ($array2 as $key => $value) if( wpfkey($array1, $key) ) $new_array[$key] = $array1[$key];
    return $new_array;
}

function wpforo_get_upload_dir_folders(){
    if( !WPF()->upload_dir_folders ){
	    $wp_upload = wp_upload_dir();
	    WPF()->upload_dir_folders = (array) array_diff(scandir($wp_upload['basedir'] . '/wpforo'), array('.', '..'));
    }
    return WPF()->upload_dir_folders;
}

/**
 * @deprecated since wpforo 1.6.5
 * @param $urlpath
 *
 * @return string|null
 */
function wpforo_urlpath_to_dirpath($urlpath){
    return wpforo_fix_upload_dir($urlpath);
}

function wpforo_fix_upload_dir($upload_dir){
	$folders = wpforo_get_upload_dir_folders();
	$folders = array_map('preg_quote', $folders);
	if( $folders && preg_match('#[/\\\]wpforo[/\\\](?:'.implode('|', $folders).')[/\\\].+?$#iu', $upload_dir, $match) ){
		$wp_upload = wp_upload_dir();
		$upload_dir = wpforo_fix_directory( $wp_upload['basedir'] . $match[0] );
		$upload_dir = urldecode($upload_dir);
	}
	return $upload_dir;
}

function wpforo_fix_upload_url($upload_url){
	$folders = wpforo_get_upload_dir_folders();
	$folders = array_map('preg_quote', $folders);
	if( preg_match('#[/\\\]wpforo[/\\\](?:'.implode('|', $folders).')[/\\\].+?$#iu', $upload_url, $match) ){
		$wp_upload = wp_upload_dir();
		$upload_url = wpforo_fix_url_sep( $wp_upload['baseurl'] . $match[0] );
	}
	return $upload_url;
}

function wpforo_xcopy( $source, $dest ) {
	// Check for symlinks
	if ( is_link( $source ) ) {
		return symlink( readlink( $source ), $dest );
	}

	// Simple copy for a file
	if ( is_file( $source ) ) {
		return copy( $source, $dest );
	}

	// Make destination directory
	if ( ! is_dir( $dest ) ) {
		wp_mkdir_p( $dest );
	}

	// Loop through the folder
	$dir = dir( $source );
	while ( false !== $entry = $dir->read() ) {
		// Skip pointers
		if ( $entry == '.' || $entry == '..' ) {
			continue;
		}

		// Deep copy directories
		wpforo_xcopy( rtrim( $source, '/' ) . "/$entry", rtrim( $dest, '/' ) . "/$entry" );
	}

	// Clean up
	$dir->close();

	return true;
}

function wpforo_printf_array($format, $arr){
    return call_user_func_array('printf', array_merge((array)$format, (array)$arr));
}

function wpforo_sprintf_array($format, $arr){
    return call_user_func_array('sprintf', array_merge((array)$format, (array)$arr));
}

function wpforo_avatar_url($avatar_html){
    if( preg_match('#src=[\'"]([^\'"]+?)[\'"]#isu', $avatar_html, $matches) ){
		return $matches[1];
    }
    return '';
}

/**
 * @param string $content
 * @param bool $first
 * @param string $type
 *
 * @return string|array
 */
function wpforo_find_image_urls( $content, $first = true, $type = 'general' ){
	$images = array();
	$content = trim( (string) $content );

	if( $content ){
        if( preg_match_all('#<img[^<>]*?src=[\'\"]([^\'\"]+\.(?:jpe?g|png|gif))[\'\"][^<>]*?>#isu', $content, $matches, PREG_SET_ORDER) ){
            foreach ( $matches as $match ){
                if( preg_match('#class=[\'\"]wpfem[^\'\"]*[\'\"][^<>]*?data-code=#isu', $match[0]) ) continue;
                if( strpos($match[1], 'http') === 0 ){
	                $images[] = $match[1];
                }else{
	                $images[] = 'http' . (is_ssl() ? 's' : '') . ':' . $match[1];
                }
            }
        }elseif( preg_match_all('#https?://[^\r\n\t\s\'\"<>]+?\.(?:jpe?g|png|gif)#isu', $content, $matches, PREG_SET_ORDER) ){
            foreach ( $matches as $match ){
                $images[] = $match[0];
            }
        }elseif( preg_match_all('#//[^\r\n\t\s\'\"<>]+?\.(?:jpe?g|png|gif)#isu', $content, $matches, PREG_SET_ORDER) ){
            foreach ( $matches as $match ){
                $images[] = 'http' . (is_ssl() ? 's' : '') . ':' . $match[0];
            }
        }
	}

	if( $first && $images ) $images = wpfval($images, 0);

	return apply_filters('wpforo_find_image_url', $images, $type, $first);
}

function wpforo_is_json($string) {
    if( is_scalar($string) ){
	    json_decode($string);
	    return json_last_error() === JSON_ERROR_NONE;
    }
    return false;
}

function wpforo_ajax_response( $message ) {
	wp_send_json( $message );
	die();
}

function wpforo_get_fb_user( $user ) {
	if( is_user_logged_in() ) return wp_get_current_user();
	$user_data = get_user_by('email', $user['user_email']);
	if( !$user_data ) {
		$users = get_users( array( 'meta_key' => '_fb_user_id', 'meta_value' => $user['fb_user_id'], 'number' => 1, 'count_total' => false ) );
		if( is_array( $users ) ) $user_data = reset( $users );
	}
	return $user_data;
}

function wpforo_unique_username( $username ) {
	static $i;
	if( !$username ) $username = 'user_' . uniqid();
	if( strpos($username, '@') !== FALSE ){
		$parts = explode( "@", $username );
		if( !empty($parts) && isset($parts[0]) && $parts[0] ) {
			$username = $parts[0];
		} else {
			$username = str_replace( '@', '', $username);
		}
	}
	if ( null === $i ) { $i = 1; } else { $i++; }
	if ( !username_exists($username) ) { return $username; }
	$new_username = sprintf( '%s-%s', $username, $i );
	if ( ! username_exists( $new_username ) ) {
		return $new_username;
	} else {
		return call_user_func( __FUNCTION__, $username );
	}
}

function wpforo_is_session_started(){
    if ( php_sapi_name() !== 'cli' ) {
        if ( version_compare(phpversion(), '5.4.0', '>=') ) {
            return session_status() === PHP_SESSION_ACTIVE ? TRUE : FALSE;
        } else {
            return session_id() === '' ? FALSE : TRUE;
        }
    }
    return FALSE;
}

function wpforo_current_guest( $email ){
	$guest = WPF()->member->get_guest_cookies();
	if(!wpfval($guest, 'email') || !$guest['email']) return false;
	if( $email == $guest['email']){
		return true;
	}else{
		return false;
	}
}

function wpforo_extra_html_parser( $extra_html = '', $allowed_html = array() ){
    if( $extra_html ){
        $extra_html = explode(',', $extra_html);
        $extra_html = array_filter($extra_html);
        if(!empty($extra_html)){
            foreach( $extra_html as $html ){
                $html = trim($html);
                if( preg_match('|([^\(\)]+)\((.+)\)|', $html, $item) ){
                    if(wpfval($item, 1) && wpfval($item, 2)) {
                        $attrs = explode(' ', $item[2]);
                        $attrs = array_map('trim', $attrs);
                        foreach( $attrs as $attr ){
                            $allowed_html[$item[1]][$attr] = array();
                        }
                    }
                }
                else{
                    $allowed_html[$html] = array();
                }
            }
        }
    }
    return $allowed_html;
}

function wpforo_clear_array($array, $clear = array(), $by = 'value' ){
    if( is_array($clear) && !empty($clear) ){
        foreach( $clear as $ext ){
            if( $by == 'value' ){
                if (($key = array_search($ext, $array)) !== false) {
                    unset($array[$key]);
                }
            }
            elseif( $by == 'key' ){
                if( wpfkey($array, $ext) ) unset($array[$ext]);
            }
        }
    }
    elseif( is_string($clear) || is_numeric($clear) ){
        if( wpfval($array, $clear) ) unset( $array[$clear] );
    }
    return $array;
}

function wpforo_key($array = array(), $value = '', $type = 'default'){
    $keys = array();
    if( is_array($array) && !empty($array) ){
        foreach($array as $k => $v){
            if($v == $value){
                $keys[] = $k;
            }
        }
    }
    if( $type == 'sort' ){
        sort($keys);
        return $keys;
    }
    else{
        return $keys;
    }
}

function wpforo_unslashe( $data){
    $data = is_array($data) ? array_map( 'wpforo_unslashe', $data) : stripslashes($data);
    return $data;
}

function wpforo_encode($data) {
    $data = is_array($data) ? array_map('wpforo_encode', $data) : htmlspecialchars($data, ENT_QUOTES);
    return $data;
}

function wpforo_decode($data) {
    $data = is_array($data) ? array_map('wpforo_decode', $data) : htmlspecialchars_decode($data, ENT_QUOTES);
    return $data;
}

function wpforo_trim($data){
    $data = is_array($data) ? array_map('wpforo_trim', $data) : trim($data);
    return $data;
}

function wpforo_sanitize_int($data) {
    $data = is_array($data) ? array_map( 'wpforo_sanitize_int', $data) : intval($data);
    return $data;
}

function wpforo_sanitize_text($data) {
    $data = is_array($data) ? array_map( 'wpforo_sanitize_text', $data) : sanitize_text_field($data);
    return $data;
}


if( !function_exists('sanitize_textarea_field') && !function_exists('_sanitize_text_fields') ){
    function sanitize_textarea_field( $str ) {
        $filtered = _sanitize_text_fields( $str, true );
        return apply_filters( 'sanitize_textarea_field', $filtered, $str );
    }
    function _sanitize_text_fields( $str, $keep_newlines = false ) {
        $filtered = wp_check_invalid_utf8( $str );
        if ( strpos($filtered, '<') !== false ) {
            $filtered = wp_pre_kses_less_than( $filtered );
            $filtered = wp_strip_all_tags( $filtered, false );
            $filtered = str_replace("<\n", "&lt;\n", $filtered);
        }
        if ( ! $keep_newlines ) {
            $filtered = preg_replace( '/[\r\n\t ]+/', ' ', $filtered );
        }
        $filtered = trim( $filtered );
        $found = false;
        while ( preg_match('/%[a-f0-9]{2}/i', $filtered, $match) ) {
            $filtered = str_replace($match[0], '', $filtered);
            $found = true;
        }
        if ( $found ) {
            $filtered = trim( preg_replace('/ +/', ' ', $filtered) );
        }
        return $filtered;
    }
}

/**
 * @param string $role
 *
 * @return bool
 */
function wpforo_current_user_is( $role ) {
	$role = strtolower( $role );

	$filter_result = apply_filters('wpforo_current_user_is', NULL, $role);
	if( !is_null($filter_result) ) return (bool) $filter_result;

	switch ( $role ) {
		case 'admin':
		    if ( current_user_can( 'activate_plugins' ) ) {
				return true;
			}
			if ( current_user_can( 'install_plugins' ) ) {
				return true;
			}
			if ( current_user_can( 'create_sites' ) ) {
				return true;
			}
			break;
		case 'moderator':
			if ( WPF()->perm->usergroup_can( 'aum' ) ) {
				return true;
			}
			if ( current_user_can( 'moderate_comments' ) ) {
				return true;
			}
			if ( current_user_can( 'edit_published_posts' ) ) {
				return true;
			}
			if ( current_user_can( 'manage_categories' ) ) {
				return true;
			}
			break;
	}

	return false;
}

/**
 * @param int $userid
 * @param string $role
 *
 * @return bool
 */
function wpforo_user_is( $userid, $role ) {
	$userid = wpforo_bigintval( $userid );
	$role   = strtolower( $role );

	$filter_result = apply_filters('wpforo_user_is', NULL, $userid, $role);
	if( !is_null($filter_result) ) return (bool) $filter_result;

	switch( $role ){
		case 'admin':
		    if ( user_can( $userid, 'activate_plugins' ) ) {
				return true;
			}
			if ( user_can( $userid, 'install_plugins' ) ) {
				return true;
			}
			if ( user_can( $userid, 'create_sites' ) ) {
				return true;
			}
			break;
		case 'moderator':
			if ( user_can( $userid, 'moderate_comments' ) ) {
				return true;
			}
			if ( user_can( $userid, 'edit_published_posts' ) ) {
				return true;
			}
			if ( user_can( $userid,'manage_categories' ) ) {
				return true;
			}
			if( $user = WPF()->member->get_member($userid) ){
				$groupid         = (int) wpfval($user, 'groupid');
				$second_groupids = wpfval($user, 'secondary_groups');
                if( WPF()->perm->usergroup_can( 'aum', $groupid, $second_groupids ) ){
                    return true;
                }
            }
			break;
	}

	return false;
}

function wpforo_random_colors(){
    mt_srand((double)microtime()*1000000); $color = '';
    while(strlen($color)<6){$color .= sprintf("%02X", mt_rand(0, 255));}
    return '#' . $color;
}

/**
 * @param string $dir
 * @return string
 */
function wpforo_fix_directory($dir){
	$dir = str_replace( array('/', '\\', '\\\\'), DIRECTORY_SEPARATOR, $dir );
	$dir = rtrim( trim($dir), DIRECTORY_SEPARATOR );
	return  $dir;
}

/**
 * @param string $url
 * @return string
 */
function wpforo_fix_url_sep($url){
	return trim( str_replace( array('/', '\\', '\\\\'), '/', $url ) );
}

function wpforo_root_exist(){
    $args = array( 'table' => WPF()->tables->posts, 'col' => 'root', 'check' => 'col_exists' );
    return wpforo_db_check( $args );
}

function wpforo_urlencode($str){
	if( !preg_match('#^(\#post-\d+|https?:)$#isu', $str)
        && !preg_match('#([\?\&][^\?\&/=\r\n]*=?[^\?\&/=\r\n]*)(?1)*$#isu', $str)
	    && strpos($str, '~') === false
	    && strpos($str, '*') === false
        && $str === urldecode($str) )
		$str = urlencode($str);
	return $str;
}

function wpforo_fix_url($url){
	$url_expld = explode('/', $url);
	$url_expld = array_map('wpforo_urlencode', $url_expld);
	$url = implode('/', $url_expld);
	return $url;
}

function wpforo_is_domains_equal($url1, $url2){
	$domain1 = strtolower( str_replace('www.', '', parse_url($url1, PHP_URL_HOST)) );
	$domain2 = strtolower( str_replace('www.', '', parse_url($url2, PHP_URL_HOST)) );
	return $domain1 === $domain2;
}

function wpforo_is_url_internal($url, $home_url = null){
	$url = trim($url);
	if( !preg_match('#^(?:https?:)?//#isu', $url) ) return true;
	if( !$home_url ) $home_url = home_url();
	$home_url = trim($home_url);
	$url = preg_replace( '#^(https?\://)?(www\.)?#isu', '', $url );
	$home_url = preg_replace( array('#^(https?\://)?(www\.)?#isu', '#/?\?.*$#isu', '#index\.php/?#isu') , '', $home_url );
	return strpos($url, $home_url) === 0;
}

function wpforo_is_url_external($url, $home_url = null){
	return !wpforo_is_url_internal($url, $home_url);
}

function wpforo_settype($var, $type){
	$var_type = strtolower( gettype($var) );
	$type = strtolower($type);
	$allowed_types = array('bool', 'boolean', 'int', 'integer', 'double', 'real', 'float', 'string', 'array', 'object', 'null');
	if( $var_type !== $type
	    && in_array($var_type,  $allowed_types)
	    && in_array($type, $allowed_types)
	    && $type !== 'null'
	    && !( $var_type === 'object' && !in_array($type, array('boolean', 'array')) )
	    && !( $var_type === 'array'  && $type === 'string' )
	) settype($var, $type);
	return $var;
}

function wpforo_array_args_cast($array, $type){
	$array = array_map(function($var) use($type){
		return wpforo_settype($var, $type);
	}, $array);
	return $array;
}

function wpforo_array_args_cast_and_merge($array, $default){
	foreach( $array as $key => $value ){
		if( array_key_exists($key, $default) ){
			$array[$key] = wpforo_settype($value, gettype($default[$key]));
//			if( is_array($array[$key]) ) $array[$key] = wpforo_array_args_cast_and_merge($array[$key], $default[$key]); #### do not open this comment until you have checkboxes on options settings pages
		}
	}
	$array += $default;
	return $array;
}

/**
 * @param int $seconds execution time in seconds , by default 0 (unlimited)
 */
function wpforo_set_max_execution_time($seconds = 0){
	if( function_exists('set_time_limit') ) set_time_limit($seconds);
	if( function_exists('ini_set') )        ini_set('max_execution_time', $seconds);
}

/**
 * @param string $pattern
 * @param array  $array
 *
 * @return array
 */
function wpforo_preg_grep_recursive($pattern, $array){
	$m = array();
	if( is_array($array) ){
        foreach ( $array as $key => $value ){
	        if( is_string($value) || is_numeric($value) ){
		        if( preg_match($pattern, $value) ) $m[$key] = $value;
	        }elseif( is_array($value = wpforo_settype($value, 'array')) ){
		        if( $_m = wpforo_preg_grep_recursive($pattern, $value) ) $m[$key] = $_m;
	        }
        }
	}
	return $m;
}

function wpforo_send_new_user_notifications($userid, $notify = 'both'){
    if( wpforo_feature( 'disable_new_user_admin_notification' ) ){
	    if( $notify !== 'admin' ) wp_send_new_user_notifications( $userid, 'user' );
    }else{
	    wp_send_new_user_notifications( $userid, $notify );
    }
}

function wpforo_get_callbacks_for_action( $hook = '' ) {
	global $wp_filter;
	if( empty( $hook ) || !isset( $wp_filter[$hook] ) ) return array();
	return $wp_filter[$hook]->callbacks;
}

/**
 * The optimized version of wordpress url_to_postid($url) function
 *
 * Examine a URL and try to determine the post ID it represents.
 *
 * Checks are supposedly from the hosted site blog.
 *
 * @since 1.7.4
 *
 * @global WP_Rewrite $wp_rewrite WordPress rewrite component.
 * @global WP         $wp         Current WordPress environment instance.
 *
 * @param string $url Permalink to check.
 * @return int Post ID, or 0 on failure.
 */
function wpforo_wp_url_to_postid($url) {
	$key = 'wpforo_wp_url_to_postid_' . $url;
	if( WPF()->ram_cache->is_exist($key) ) return WPF()->ram_cache->get($key);

	if( strpos($url, admin_url()) !== false ){
		WPF()->ram_cache->set($key, 0);
		return 0;
    }

	$url_host      = str_replace( 'www.', '', parse_url( $url, PHP_URL_HOST ) );
	$home_url_host = str_replace( 'www.', '', parse_url( home_url(), PHP_URL_HOST ) );

	// Bail early if the URL does not belong to this site.
	if ( $url_host && $url_host !== $home_url_host ) {
		WPF()->ram_cache->set($key, 0);
		return 0;
	}

	// First, check to see if there is a 'p=N' or 'page_id=N' to match against.
	if ( preg_match( '#[?&](p|page_id|post_id|post|attachment_id)=(\d+)#', $url, $values ) ) {
		$id = absint( $values[2] );
		if ( $id ) {
			WPF()->ram_cache->set($key, $id);
			return $id;
		}
	}

	global $wp_rewrite;
	if($wp_rewrite){
		// Get rid of the #anchor.
		$url_split = explode( '#', $url );
		$url       = $url_split[0];

		// Get rid of URL ?query=string.
		$url_split = explode( '?', $url );
		$url       = $url_split[0];

		// Set the correct URL scheme.
		$scheme = parse_url( home_url(), PHP_URL_SCHEME );
		$url    = set_url_scheme( $url, $scheme );

		// Add 'www.' if it is absent and should be there.
		if ( false !== strpos( home_url(), '://www.' ) && false === strpos( $url, '://www.' ) ) {
			$url = str_replace( '://', '://www.', $url );
		}

		// Strip 'www.' if it is present and shouldn't be.
		if ( false === strpos( home_url(), '://www.' ) ) {
			$url = str_replace( '://www.', '://', $url );
		}

		if ( trim( $url, '/' ) === home_url() && 'page' == get_option( 'show_on_front' ) ) {
			$page_on_front = get_option( 'page_on_front' );

			if ( $page_on_front && get_post( $page_on_front ) instanceof WP_Post ) {
				WPF()->ram_cache->set($key, (int) $page_on_front);
				return (int) $page_on_front;
			}
		}

		// Check to see if we are using rewrite rules.
		$rewrite = $wp_rewrite->wp_rewrite_rules();

		// Not using rewrite rules, and 'p=N' and 'page_id=N' methods failed, so we're out of options.
		if ( empty( $rewrite ) ) {
			WPF()->ram_cache->set($key, 0);
			return 0;
		}

		// Strip 'index.php/' if we're not using path info permalinks.
		if ( ! $wp_rewrite->using_index_permalinks() ) {
			$url = str_replace( $wp_rewrite->index . '/', '', $url );
		}

		if ( false !== strpos( trailingslashit( $url ), home_url( '/' ) ) ) {
			// Chop off http://domain.com/[path].
			$url = str_replace( home_url(), '', $url );
		} else {
			// Chop off /path/to/blog.
			$home_path = parse_url( home_url( '/' ) );
			$home_path = isset( $home_path['path'] ) ? $home_path['path'] : '';
			$url       = preg_replace( sprintf( '#^%s#', preg_quote( $home_path ) ), '', trailingslashit( $url ) );
		}

		// Trim leading and lagging slashes.
		$url = trim( $url, '/' );

		$request              = $url;
		$post_type_query_vars = array();

		foreach ( get_post_types( array(), 'objects' ) as $post_type => $t ) {
			if ( ! empty( $t->query_var ) ) {
				$post_type_query_vars[ $t->query_var ] = $post_type;
			}
		}

		// Look for matches.
		$request_match = $request;
		foreach ( (array) $rewrite as $match => $query ) {

			// If the requesting file is the anchor of the match,
			// prepend it to the path info.
			if ( ! empty( $url ) && ( $url != $request ) && ( strpos( $match, $url ) === 0 ) ) {
				$request_match = $url . '/' . $request;
			}

			if ( preg_match( "#^$match#", $request_match, $matches ) ) {

				if ( $wp_rewrite->use_verbose_page_rules && preg_match( '/pagename=\$matches\[([0-9]+)\]/', $query, $varmatch ) ) {
					// This is a verbose page match, let's check to be sure about it.
					$page = get_page_by_path( $matches[ $varmatch[1] ] );
					if ( ! $page ) {
						continue;
					}

					$post_status_obj = get_post_status_object( $page->post_status );
					if ( ! $post_status_obj->public && ! $post_status_obj->protected
					     && ! $post_status_obj->private && $post_status_obj->exclude_from_search ) {
						continue;
					}
				}

				// Got a match.
				// Trim the query of everything up to the '?'.
				$query = preg_replace( '!^.+\?!', '', $query );

				// Substitute the substring matches into the query.
				$query = addslashes( WP_MatchesMapRegex::apply( $query, $matches ) );

				// Filter out non-public query vars.
				global $wp;
				parse_str( $query, $query_vars );
				$query = array();
				foreach ( (array) $query_vars as $key => $value ) {
					if ( in_array( $key, $wp->public_query_vars ) ) {
						$query[ $key ] = $value;
						if ( isset( $post_type_query_vars[ $key ] ) ) {
							$query['post_type'] = $post_type_query_vars[ $key ];
							$query['name']      = $value;
						}
					}
				}

				// Resolve conflicts between posts with numeric slugs and date archive queries.
				$query = wp_resolve_numeric_slug_conflicts( $query );

				// Do the query.
				if( $p = wpforo_bigintval( wpfval($query, 'p') ) ){
					WPF()->ram_cache->set($key, $p);
					return $p;
				}
				if( $page_id = wpforo_bigintval( wpfval($query, 'page_id') ) ){
					WPF()->ram_cache->set($key, $page_id);
					return $page_id;
				}

				$pagename = wpfval($query, 'pagename');
				$name = wpfval($query, 'name');
				if( $pagename || $name ){
					if( !$slug = $pagename) $slug = $name;
					$sql = "SELECT `ID` FROM `" . WPF()->db->posts . "` 
			        WHERE `post_status` = 'publish' 
			        AND `post_name` = %s";
					$post_types = (array) apply_filters('wpforo_wp_url_to_postid_post_types', array('page', 'post'));
					if( $post_types ) $sql .= " AND `post_type` IN('". implode("','", $post_types) ."')";
					$sql = WPF()->db->prepare($sql, $slug);
					$postid = WPF()->db->get_var($sql);
					if( $postid = wpforo_bigintval($postid) ) {
						WPF()->ram_cache->set($key, $postid);
						return $postid;
					}else{
						WPF()->ram_cache->set($key, 0);
						return 0;
					}
				}else{
					WPF()->ram_cache->set($key, 0);
					return 0;
				}

			}
		}
    }

	WPF()->ram_cache->set($key, 0);
	return 0;
}

function wpforo_get_blog_content_types(){
	$post_types = (array) get_post_types(array(
		'public' => true,
		'publicly_queryable' => true,
		'show_ui' => true,
		'capability_type' => 'post'
	));
	$post_types['post'] = 'post';
	$post_types['page'] = 'page';
	unset($post_types['attachment']);
	$post_types = array_filter( array_keys($post_types) );
	$post_types = apply_filters('wpforo_get_blog_content_types', $post_types);
	return $post_types;
}

/**
 * @param string $css
 *
 * @return string
 */
function wpforo_add_wrapper($css){
	$css = preg_replace('@(#(?:wpforo-wrap|wpfa_dialog|wpfa_dialog_wrap)[\s.#{:>+\[~,])@um', '#wpforo $1', $css);
	$css = preg_replace('@(#(?:wpforo|wpforo-wrap|wpfa_dialog|wpfa_dialog_wrap)\s*)#wpforo[\s.#{:>+\[~,]@um', '$1', $css);
	return $css;
}

/**
 * @param string $css
 *
 * @return string
 */
function wpforo_wrap_fix_in_css($css){
	if( (strpos($css, '#wpforo-wrap') !== false && strpos($css, '#wpforo #wpforo-wrap') === false) ||
	    (strpos($css, '#wpfa_dialog') !== false && strpos($css, '#wpforo #wpfa_dialog') === false)
	){
		$css = wpforo_add_wrapper($css);
	}
	return $css;
}

/**
 * @param string $csspath
 */
function wpforo_wrap_fix_in_cssfile($csspath){
	$csspath = wpforo_fix_directory($csspath);
	if( is_file($csspath) ){
		$css = wpforo_get_file_content( $csspath );
		$css = wpforo_wrap_fix_in_css( $css );
		if( md5($css) !== md5_file($csspath) ) wpforo_write_file($csspath, $css);
	}
}

function wpforo_wrap_in_all_addons_css(){
    $csspaths = array(
        '/wpforo-ad-manager/assets/css/style.css',
        '/wpforo-advanced-attachments/assets/css/style.css',
        '/wpforo-cross-posting/assets/css/wpf-wpdiscuz-uploader.css',
        '/wpforo-cross-posting/assets/css/wpforo-cross-rtl.css',
        '/wpforo-cross-posting/assets/css/wpforo-cross.css',
        '/wpforo-embeds/assets/css/embed.css',
        '/wpforo-emoticons/assets/emoticons.css',
        '/wpforo-mycred/css/wpf-mycread.css',
        '/wpforo-polls/assets/css/poll.css',
        '/wpforo-private-messages/assets/css/style-rtl.css',
        '/wpforo-private-messages/assets/css/style.css',
        '/wpforo-user-custom-fields/assets/css/frontend.css'
    );
    foreach ( $csspaths as $csspath ) wpforo_wrap_fix_in_cssfile( WP_PLUGIN_DIR . $csspath );

	WPF()->dissmissed['addons_css_update'] = 1;
	update_option('wpforo_dissmissed', WPF()->dissmissed);
	wpforo_clean_cache('option');
}

add_action( 'admin_notices', function(){
    if( !(int) wpfval(WPF()->dissmissed, 'addons_css_update') ){
	    $has_addon = false;
	    foreach ( WPF()->addons as $addon ){
		    if( class_exists( $addon['class'] ) ) {
			    $has_addon = true;
			    break;
		    }
	    }
	    if( !$has_addon ) return;
	    $class = 'notice notice-warning';
	    $message = '<h3>' . __( 'Action Required!', 'wpforo' ) . '<span style="display: inline-block;font-size: 14px; padding: 0 7px; font-weight: normal;">' . __( 'Please update wpForo addons CSS style to make compatible with the current version of wpForo.', 'wpforo' ) . '</span>' . '</h3>';
	    $message .= '<a href="'. admin_url( wp_nonce_url('admin.php?page=wpforo-community&wpfaction=wpforo_update_addons_css', 'wpforo-update-addons-css') ) .'" class="button button-primary">'. __('Update CSS >>', 'wpforo') .'</a>';
	    printf( '<div class="%1$s"><p>%2$s</p></div>', $class, $message );
    }
} );

/**
 * @param string $url
 *
 * @return string|null
 */
function wpforo_get_topic_slug_from_url($url = ''){
    $url = trim($url);
    if( !$url ) $url = wpforo_get_request_uri();

    if( is_wpforo_url($url) ){
        if( preg_match('#/'.preg_quote(wpforo_get_template_slug('postid')).'/(\d+)/?$#isu', strtok($url, '?'), $match) ){
	        $url = WPF()->post->get_post_url($match[1]);
        }
	    if( preg_match('#^[\r\n\t\s]*https?://[^\r\n\t\s]+?/[^/]+?/([^/]+?)(?:/'. wpforo_get_template_slug('paged') .'/\d+/?)?(?:/?\#post-\d+)?/?[\r\n\t\s]*$#isu', $url, $match) ){
		    return $match[1];
	    }
    }

    return null;
}

function wpforo_clean_folder( $directory ) {
    $directory_ns = trim( $directory, '/') . '/*';
    $directory_ws = '/' . trim( $directory, '/') . '/*';
    $glob = glob( $directory_ns ); if( empty($glob) ) $glob = glob( $directory_ws );
    foreach( $glob as $item ) {
        if( strpos($item, 'index.html') !== FALSE || strpos($item, '.htaccess') !== FALSE ) continue;
        if( !is_dir($item) && file_exists($item) ) {
            @unlink( $item );
        }
    }
}

/**
 * @param string $basename
 *
 * @return string
 */
function wpforo_fix_table_name($basename){
    return WPF()->fix_table_name($basename);
}