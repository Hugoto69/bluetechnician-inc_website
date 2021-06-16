<?php
/* 100% match ms */
defined('ABSPATH') or die("you do not have access to this page!");

/**
 * Get nice expiration date
 * @return string
 */
function rsssl_pro_expiration_date_nice()
{
    $date = __("Not detected", "really-simple-ssl-pro");
    $expiration_date = get_transient('rsssl_cert_expiration_date');
    if ($expiration_date) $date = date(get_option('date_format'), $expiration_date);
    return $date;
}

/**
 * Check if certificate is almost expired
 * @return bool
 */

function rsssl_pro_almost_expired()
{
    $expiration_date = get_transient('rsssl_cert_expiration_date');

    if (!$expiration_date) {
        rsssl_pro_check_certificate_expiration();
        $expiration_date = get_transient('rsssl_cert_expiration_date');
    }

    if (!$expiration_date) {
        return false;
    }

    $dateInTwoWeeks = strtotime('+2 weeks');
    if ($expiration_date && ($dateInTwoWeeks < $expiration_date)) {
        return false;
    } else {
        return true;
    }
}

/**
 * Add pro schedule
 */
function rsssl_pro_schedule_cron()
{
    if (!is_ssl()) return;
    if (!get_option('rsssl_cert_expiration_warning') && !(is_multisite() && RSSSL()->rsssl_multisite->cert_expiration_warning)) return;

    $useCron = true;

    if ($useCron) {
        if (!wp_next_scheduled('rsssl_pro_daily_hook')) {
            wp_schedule_event(time(), 'daily', 'rsssl_pro_daily_hook');
        }
        //link function to this custom cron hook
        add_action('rsssl_pro_daily_hook', 'rsssl_pro_check_certificate_expiration');
    } else {
        add_action('shutdown', 'rsssl_pro_check_certificate_expiration');
    }
}
add_action('init', 'rsssl_pro_schedule_cron');

/**
 * Add our own schedule
 * @param $schedules
 *
 * @return mixed
 */
function rsssl_pro_add_schedule($schedules)
{

    $schedules['daily'] = array(
        'interval' => DAY_IN_SECONDS,
        'display' => __('Once Daily')
    );

    $schedules['ten_minutes'] = array(
        'interval' => 600,
        'display' => __('Once 10 minutes')
    );

    $schedules['one_minute'] = array(
        'interval' => 60,
        'display' => __('Once 1 minutes')
    );

    return $schedules;
}
add_filter('cron_schedules', 'rsssl_pro_add_schedule', 10, 1);

/**
 * Check expiration
 * @return bool|void
 */

function rsssl_pro_check_certificate_expiration()
{

    if (!is_ssl()) return;

	if (get_transient('rsssl_stop_certificate_expiration_check')) return;

    if (!get_option('rsssl_cert_expiration_warning') && !(is_multisite() && RSSSL()->rsssl_multisite->cert_expiration_warning)) return false;

    //older versions of PHP do not support this
    if (!function_exists('stream_context_get_params')) return;

    $end_date = false;

    //check if the certificate is still valid, and send an email to the administrator of this is not the case.
    $url = home_url();
    $original_parse = parse_url($url, PHP_URL_HOST);

    if ($original_parse) {

        $get = stream_context_create(array("ssl" => array("capture_peer_cert" => TRUE)));
        if ($get) {
            set_error_handler('rsssl_pro_custom_error_handling');
            $read = stream_socket_client("ssl://" . $original_parse . ":443", $errno, $errstr, 5, STREAM_CLIENT_CONNECT, $get);
            restore_error_handler();

            if (!$read){
            	//stop checking for a while
	            set_transient('rsssl_stop_certificate_expiration_check', true, WEEK_IN_SECONDS );
            }
            if ($errno == 0 && $read) {

                $cert = stream_context_get_params($read);
                $certinfo = openssl_x509_parse($cert['options']['ssl']['peer_certificate']);

                $end_date = $certinfo["validTo_time_t"];

            }
        }
    }

    //save valid to date for use in config page
    set_transient('rsssl_cert_expiration_date', $end_date, DAY_IN_SECONDS);

    $dateInTwoWeeks = strtotime('+2 weeks');
    //if the time in two weeks is past the end date, we need to send a warning.
    if ($end_date && $dateInTwoWeeks > $end_date) {
        //send warning if not sent before.
        if (!get_transient('rsssl_sent_cert_expiration_warning')) {
            $success = rsssl_pro_send_cert_expiration_email();
            if ($success) set_transient('rsssl_sent_cert_expiration_warning', TRUE, 14 * DAY_IN_SECONDS );
        }
    }

}

/**
 * Set custom error handling
 * @param       $errno
 * @param       $errstr
 * @param       $errfile
 * @param       $errline
 * @param array $errcontext
 *
 * @return bool
 */
function rsssl_pro_custom_error_handling($errno, $errstr, $errfile, $errline, $errcontext = array() )
{
    return true;
}

/**
 * Send expiration email
 * @return bool
 */

function rsssl_pro_send_cert_expiration_email()
{
    //only proceed if we hav a valid end date
    if (!get_transient('rsssl_cert_expiration_date')) return false;

    $headers = array();
    $to = get_option('admin_email');
    $subject = __("SSL certificate expiration warning", "really-simple-ssl-pro");
    $body = __("According to the check that was performed just now on ", "really-simple-ssl-pro") . home_url();
    $body .= __(" the SSL certificate is expiring soon. The ValidTo date is:", "really-simple-ssl-pro") . "<br><br>";
    $body .= rsssl_pro_expiration_date_nice() . "<br><br>";
    $body .= __("Please renew your certificate before the expiry date.", "really-simple-ssl-pro") . "<br><br>";

    add_filter('wp_mail_content_type', "rsssl_pro_set_mail_content_type");

    $success = true;

    if (wp_mail($to, $subject, $body) === false) $success = false;

    // Reset content-type to avoid conflicts -- http://core.trac.wordpress.org/ticket/23578
	add_filter( 'wp_mail_content_type', function ( $content_type ) {
		return 'text/html';
	} );
	return $success;
}