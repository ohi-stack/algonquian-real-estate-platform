<?php
/** Conservative uninstall handler. */
defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

if ( ! defined( 'ALGQ_WCB_PURGE_ON_UNINSTALL' ) || true !== ALGQ_WCB_PURGE_ON_UNINSTALL ) {
    return;
}

global $wpdb;
foreach ( array( 'algq_wcb_version', 'algq_wcb_schema_version', 'algq_wcb_brand_name', 'algq_wcb_legacy_migrated' ) as $option ) {
    delete_option( $option );
}
$wpdb->query( 'DROP TABLE IF EXISTS ' . $wpdb->prefix . 'algq_wcb_entitlements' ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
