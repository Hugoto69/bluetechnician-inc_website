<?php
/**
 * Plugin Name: WP Activity Log for Yoast SEO
 * Plugin URI: https://wpactivitylog.com/extensions/
 * Description: A WP Activity Log plugin extension for Yoast SEO
 * Text Domain: activity-log-wp-seo
 * Author: WP White Security
 * Author URI: https://www.wpwhitesecurity.com/
 * Version: 1.1.0
 * License: GPL2
 * Network: true
 *
 * @package Wsal
 * @subpackage Wsal Custom Events Loader
 */

/*
	Copyright(c) 2021  WP White Security  (email : info@wpwhitesecurity.com)

	This program is free software; you can redistribute it and/or modify
	it under the terms of the GNU General Public License, version 2, as
	published by the Free Software Foundation.

	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	GNU General Public License for more details.

	You should have received a copy of the GNU General Public License
	along with this program; if not, write to the Free Software
	Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

/*
	REQUIRED. Here we include and fire up the main core class. This will be needed regardless so be sure to leave line 37-39 in tact.
*/
require_once plugin_dir_path( __FILE__ ) . 'core/class-extension-core.php';
$wsal_extension = new WPWhiteSecurity\ActivityLog\Extensions\Common\Core( __FILE__, 'activity-log-wp-seo' );


/**
 * Adds new custom event objects for our plugin
 *
 * @method wsal_yoast_seo_extension_add_custom_event_objects
 * @since  1.0.0
 * @param  array $objects An array of default objects.
 * @return array
 */
function wsal_yoast_seo_extension_add_custom_event_objects( $objects ) {
	$new_objects = array(
		'yoast-seo'         => __( 'Yoast SEO', 'wp-security-audit-log' ),
		'yoast-seo-metabox' => __( 'Yoast SEO Meta Box', 'wp-security-audit-log' ),
	);

	// combine the two arrays.
	$objects = array_merge( $objects, $new_objects );

	return $objects;
}

/**
 * Add specific events so we can use them for category titles.
 */
function wsal_yoast_seo_extension_togglealerts_sub_category_events( $sub_category_events ) {
	$new_events          = array( 8813, 8815, 8838 );
	$sub_category_events = array_merge( $sub_category_events, $new_events );
	return $sub_category_events;
}

/**
 * Add sub cateogry titles to ToggleView page in WSAL.
 */
function wsal_yoast_seo_extension_togglealerts_sub_category_titles( $subcat_title, $alert_id ) {
	if ( 8815 === $alert_id ) {
		$subcat_title = esc_html_e( 'Features:', 'wp-security-audit-log' );
	}
	else if ( 8813 === $alert_id ) {
		$subcat_title = esc_html_e( 'Search Appearance', 'wp-security-audit-log' );
	}
	else if ( 8838 === $alert_id ) {
		$subcat_title = esc_html_e( 'Multisite network', 'wp-security-audit-log' );
	}
	return $subcat_title;
}

/**
 * If a user is running an older version of WSAL, they will see a "duplicate event" error.
 * This function checks and runs a filter to replace that notice. Its done via JS as we cant
 * currently give this notice a neat ID/class.
 */
function wsal_yoast_seo_extension_replace_duplicate_event_notice() {
	$wsal_version = get_site_option( 'wsal_version' );
	if ( version_compare( $wsal_version, '4.1.3.2', '<=' ) ) {
		add_action( 'admin_footer', 'wsal_yoast_seo_extension_replacement_duplicate_event_notice' );
	}
	if ( isset( $_REQUEST['page'] ) && 'wsal-togglealerts' === $_REQUEST['page'] ) {
		add_action( 'admin_footer', 'wsal_yoast_seo_extension_hide_obsolete_events' );
	}
}

/**
 * Temporary function to hide events using CSS, to be removed once WSAL 4.3 is released.
 */
function wsal_yoast_seo_extension_hide_obsolete_events() {
	?>
	<style type="text/css">
		#tab-yoast-seo #tab-website-changes tr:nth-of-type(2), #tab-yoast-seo #tab-website-changes tr:nth-of-type(2) {
			display: none;
		}
	</style>
	<?php
}

/**
 * Add obsolete events to the togglealerts view.
 */
function wsal_yoast_seo_extension_togglealerts_obsolete_events( $obsolete_events ) {
	$new_events      = [ 8810, 8811 ];
	$obsolete_events = array_merge( $obsolete_events, $new_events );
	return $obsolete_events;
}

/**
 * Replacement "duplicate event" notice text.
 */
function wsal_yoast_seo_extension_replacement_duplicate_event_notice() {
	$replacement_text = __( 'You are running an old version of WP Activity Log. Please update the plugin to run it alongside this extension: Yoast SEO', 'wp-security-audit-log' );
	?>
	<script type="text/javascript">
		if ( jQuery( '.notice.notice-error span[style="color:#dc3232; font-weight:bold;"]' ).length ) {
			jQuery( '.notice.notice-error span[style="color:#dc3232; font-weight:bold;"]' ).parent().text( '<?php echo esc_html( $replacement_text ); ?>' );
		}
	</script>
<?php
}


/**
 * Add our filters.
 */
add_filter( 'wsal_event_objects', 'wsal_yoast_seo_extension_add_custom_event_objects' );
add_filter( 'wsal_togglealerts_sub_category_events', 'wsal_yoast_seo_extension_togglealerts_sub_category_events' );
add_filter( 'wsal_togglealerts_sub_category_titles', 'wsal_yoast_seo_extension_togglealerts_sub_category_titles', 10, 2 );
add_filter( 'admin_init', 'wsal_yoast_seo_extension_replace_duplicate_event_notice' );
add_filter( 'wsal_togglealerts_obsolete_events', 'wsal_yoast_seo_extension_togglealerts_obsolete_events' );
