<?php

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

$settings = get_option( 'algq_pipeline_settings', array() );
if ( 'yes' !== ( $settings['delete_data_on_uninstall'] ?? 'no' ) ) {
    return;
}

global $wpdb;
$tables = array(
    $wpdb->prefix . 'algq_deal_activity',
    $wpdb->prefix . 'algq_deal_tasks',
    $wpdb->prefix . 'algq_deal_notes',
    $wpdb->prefix . 'algq_deal_stage_history',
    $wpdb->prefix . 'algq_deals',
);
foreach ( $tables as $table ) {
    $wpdb->query( "DROP TABLE IF EXISTS `{$table}`" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
}
foreach ( array( 'algq_pipeline_settings', 'algq_pipeline_version', 'algq_pipeline_schema_version', 'algq_pipeline_page_ids', 'algq_pipeline_legacy_cpt_migrated' ) as $option ) {
    delete_option( $option );
    delete_site_option( $option );
}
