<?php
/**
 * Conservative uninstall handler.
 *
 * Operational records and generated pages are preserved by default.
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

if ( ! defined( 'ALGQ_COMMAND_CENTER_PURGE_ON_UNINSTALL' ) || true !== ALGQ_COMMAND_CENTER_PURGE_ON_UNINSTALL ) {
    return;
}

$options = array(
    'algq_command_center_version',
    'algq_command_center_release_status',
    'algq_command_center_enabled_widgets',
    'algq_command_center_refresh_interval',
    'algq_command_center_pipeline_value',
    'algq_command_center_funding_committed',
    'algq_command_center_funding_needed',
    'algq_command_center_last_health_check',
    'algq_command_center_last_metrics_refresh',
);
foreach ( $options as $option ) {
    delete_option( $option );
}
delete_transient( 'algq_cc_last_health_summary' );
delete_transient( 'algq_cc_last_metrics' );
