<?php
/**
 * Algonquian Admin Command Center uninstall routine.
 *
 * Operational records are preserved by default. Administrators must explicitly
 * enable complete cleanup before uninstalling the plugin.
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

$cleanup_enabled = (bool) get_option( 'algq_command_center_delete_data_on_uninstall', false );

if ( ! $cleanup_enabled ) {
    return;
}

$options = array(
    'algq_command_center_settings',
    'algq_command_center_widget_preferences',
    'algq_command_center_dashboard_layout',
    'algq_command_center_delete_data_on_uninstall',
);

foreach ( $options as $option_name ) {
    delete_option( $option_name );
    delete_site_option( $option_name );
}

$roles = wp_roles();

if ( $roles ) {
    foreach ( $roles->roles as $role_name => $role_data ) {
        $role = get_role( $role_name );

        if ( ! $role ) {
            continue;
        }

        $role->remove_cap( 'manage_algq_command_center' );
        $role->remove_cap( 'view_algq_command_center' );
        $role->remove_cap( 'export_algq_reports' );
        $role->remove_cap( 'view_algq_audit_logs' );
    }
}

// Do not delete shared platform tables, audit records, deal records, documents,
// reports owned by other plugins, or any companion-plugin data.
