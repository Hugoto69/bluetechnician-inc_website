<?php
// If uninstall is not called from WordPress, exit
if ( !defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit();
}

rsssl_delete_all_options(
  array(
      'rsssl_scan_progress',
      'rlrsssl_scan',
      'rsssl_scan_type',
      'autoreplace_insecure_links_on_admin',
      'rsssl_hsts_preload',
      'rsssl_cert_expiration_warning',
    )
  );

function rsssl_delete_all_options($options) {
  foreach($options as $option_name) {
    delete_option( $option_name );
    // For site options in Multisite
    delete_site_option( $option_name );
  }
}
