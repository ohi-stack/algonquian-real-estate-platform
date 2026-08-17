<?php
/**
 * Conservative Automation Engine uninstall routine.
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

$settings = get_option( 'algq_automation_settings', array() );

if ( empty( $settings['delete_data_on_uninstall'] ) ) {
    return;
}

global $wpdb;

foreach ( array( 'rules', 'jobs', 'logs', 'tasks' ) as $suffix ) {
    $table = $wpdb->prefix . 'algq_automation_' . $suffix;
    $wpdb->query( "DROP TABLE IF EXISTS `{$table}`" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
}

foreach ( array( 'algq_automation_settings', 'algq_automation_version', 'algq_automation_schema_version', 'algq_automation_page_ids', 'algq_automation_engine_version' ) as $option ) {
    delete_option( $option );
    delete_site_option( $option );
}

$roles = wp_roles();

if ( $roles ) {
    foreach ( array_keys( $roles->roles ) as $role_name ) {
        $role = get_role( $role_name );
        if ( ! $role ) {
            continue;
        }
        foreach ( array( 'manage_algq_automation', 'view_algq_automation', 'edit_algq_automation_rules', 'delete_algq_automation_rules', 'view_algq_automation_logs', 'run_algq_automation', 'algq_manage_automation', 'algq_view_automation', 'algq_edit_automation_rules', 'algq_delete_automation_rules', 'algq_view_automation_logs', 'algq_run_automation_tests' ) as $capability ) {
            $role->remove_cap( $capability );
        }
    }
}
