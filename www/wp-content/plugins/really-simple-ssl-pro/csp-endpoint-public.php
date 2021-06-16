<?php
defined('ABSPATH') or die("you do not have access to this page!");

/**
 *
 * API for Content Security Policy registration
 * @Since 2.5
 * @return JSON data with Content Security Policy violations
 *
 */

if ( rsssl_get_networkwide_option('rsssl_content_security_policy') === 'report-only' ) {
    add_action('rest_api_init', 'rsssl_pro_csp_rest_route');
}

if ( !function_exists('rsssl_pro_csp_rest_route')) {
	function rsssl_pro_csp_rest_route() {
		register_rest_route( 'rsssl/v1', 'csp/', array(
			'methods'             => 'POST',
			'callback'            => 'rsssl_track_csp',
			'permission_callback' => 'rsssl_pro_validate_api_call',
		) );
	}
}

/**
 * @param $request
 * @return bool
 *
 * Check if the requests comes from own server
 * @since 4.1.4
 *
 */

function rsssl_pro_validate_api_call ( $request ) {
	$origin = $request->get_header('origin');

	if ($request->get_param('rsssl_apitoken') != rsssl_get_networkwide_option('rsssl_csp_report_token') ) {
		return false;
	}

	if ( strpos(site_url(), $origin) !== false || strpos(home_url(), $origin) !== false ) {
		//we're about to give access to the backend for this api call 
		if ( !defined('RSSSL_DOING_CSP') ) define( 'RSSSL_DOING_CSP' , true );
		return true;
	} else {
		return false;
	}
}

/**
 * @param WP_REST_Request $request
 *
 * @Since 2.5
 *
 * Process Content Security Policy violations, add to DB
 *
 * $blockeduri: the domain which is blocked due to content security policy rules
 * $documenturi: the post/page where the violation occurred
 *
 */

function rsssl_track_csp(WP_REST_Request $request)
{
	//We have added a query parameter (?xx) after the API endpoint, get that value via get_param() and compare to the option value
	if ($request->get_param('rsssl_apitoken') != rsssl_get_networkwide_option('rsssl_csp_report_token') ) return;

    $request_count = rsssl_get_networkwide_option('rsssl_csp_request_count' );
	if ( !$request_count ) {
		$request_count = 0;
	}
	$request_count++;
	rsssl_update_networkwide_option('rsssl_csp_request_count', $request_count );

    if ( $request_count > 20 ) {
        rsssl_update_networkwide_option('rsssl_content_security_policy', 'report-paused' );
        // Reporting paused, remove report only rules from htaccess to stop requests to endpoint.
        // Only when Really Simple SSL free is version 4.0.8 or higher due to compatibility
        if ( version_compare( rsssl_version, '4.0.8', '>=' ) ) {
            RSSSL_PRO()->rsssl_premium_options->remove_htaccess_rules('Really_Simple_SSL_CSP_Report_Only');
        }
	    RSSSL_PRO()->rsssl_csp_backend->maybe_reset_csp_api_token( '', 'report-paused', '', $force=true );
    }

	global $wpdb;
	$table_name = $wpdb->base_prefix . "rsssl_csp_log";

	$row_count = $wpdb->get_var("SELECT count(*) FROM $table_name");
	if ($row_count >= 500) return;

	//CSP-report-only data is contained in php://input stream
	$json_data = file_get_contents('php://input');

	//Decode to associative array
	$json_data = json_decode($json_data, true);

	$blockeduri = rsssl_sanitize_uri_value($json_data['csp-report']['blocked-uri']);
	$violateddirective = rsssl_sanitize_csp_violated_directive($json_data['csp-report']['violated-directive']);
	$documenturi = esc_url_raw($json_data['csp-report']['document-uri']);
	// Remove query strings from $documenturi. strstr will return false if needle is not found. Therefore continue with $documenturi when strstr returns false
	$documenturi = strstr($documenturi, '?', true) ?: $documenturi;

	//If one of these is empty we cannot generate a CSP rule from it, return
	if (empty($violateddirective) || (empty($blockeduri) ) ) return;

	//Style-src-elem and script-src-elem are implemented behind a browser flag. Therefore save as style-src and script-src since these are used as a fallback. Results in console warnings otherwise
//    if ($violateddirective === 'style-src-elem') {
//        $violateddirective = str_replace('style-src-elem', 'style-src', $violateddirective);
//    }
//
//    if ($violateddirective === 'script-src-elem') {
//        $violateddirective = str_replace('script-src-elem', 'script-src', $violateddirective);
//    }

	//Check if the blockeduri and violatedirective already occur in DB. If so, we do not need to add them again.
	$count = $wpdb->get_var("SELECT count(*) FROM $table_name where blockeduri = '$blockeduri' AND violateddirective='$violateddirective'");
	if ($count == 0) {
		//Insert into table
		$wpdb->insert($table_name, array(
			'time' => current_time('mysql'),
			'documenturi' => $documenturi,
			//Violateddirective and blockeduri are already sanitized earlier in this function
			'violateddirective' => $violateddirective,
			'blockeduri' => $blockeduri,
		));
	}

	exit;
}

/**
 * @param string $str
 * @return string
 *
 * @Since 2.5
 *
 * Only allow known directives to be returned, otherwise return empty string
 *
 */

function rsssl_sanitize_csp_violated_directive( $str ){

	//Style-src-elem and script-src-elem are implemented behind a browser flag. Therefore save as style-src and script-src since these are used as a fallback
	if ($str==='style-src-elem') {
		//$str = str_replace('style-src-elem', 'style-src', $str);
	}

	if ($str==='script-src-elem') {
		//$str = str_replace('script-src-elem', 'script-src', $str);
	}

	//https://www.w3.org/TR/CSP3/#directives-fetch
	$directives = array(
		//Fetch directives
		'child-src',
		'connect-src',
		'default-src',
		'font-src',
		'frame-src',
		'img-src',
		'manifest-src',
		'media-src',
		'prefetch-src',
		'object-src',
		'script-src',
		'script-src-elem',
		'script-src-attr',
		'style-src',
		'style-src-elem',
		'style-src-attr',
		'worker-src',
		//Document directives
		'base-uri',
		'plugin-types',
		'sandbox',
		'form-action',
		'frame-ancestors',
		'navigate-to',
	);

	if (in_array($str, $directives)) {
		return $str;
	}

	return '';
}

/**
 * Only allow known directives to be returned, otherwise return empty string
 *
 * @param string $str
 * @return string
 *
 * @Since 2.5
 *
 */

function rsssl_sanitize_csp_blocked_uri($str){

	//Data: needs an : which isn't included automatically, add here
	if ($str==='data') {
		$str = str_replace('data', 'data:', $str);
	}

	//Inline should be unsafe-inline
	if ($str==='inline') {
		$str = str_replace('inline', 'unsafe-inline', $str);
	}

	//Eval should be unsafe-eval
	if ($str==='eval') {
		$str = str_replace('eval', 'unsafe-eval', $str);
	}

	if ($str==='unsafe-inline') {
		$str = str_replace('unsafe-inline', "'unsafe-inline'", $str);
	}

	if ($str==='unsafe-eval') {
		$str = str_replace('unsafe-eval', "'unsafe-eval'", $str);
	}

	$directives = array(
		//Fetch directives
		"self",
		'data:',
		"'unsafe-inline'",
		"'unsafe-eval'",
		"about",
	);

	if (in_array($str, $directives)) {
		return $str;
	}

	return '';
}

/**
 * URI can be a domain or a value (e.g. data:). If it's a domain, return the main domain (https://example.com).
 * Otherwise return one of the other known uri value.
 *
 * @param string $blockeduri
 * @return string
 *
 * @since 2.5
 *
 */

function rsssl_sanitize_uri_value($blockeduri)
{
	$uri = '';

	//Check if uri starts with http(s)
	if (substr($blockeduri, 0, 4) === 'http') {
		$url = parse_url($blockeduri);
		if ( (isset($url['scheme'])) && isset($url['host']) ) {
			$uri = esc_url_raw($url['scheme'] . "://" . $url['host']);
		}
	} else {
		$uri = rsssl_sanitize_csp_blocked_uri($blockeduri);
	}

	return $uri;
}