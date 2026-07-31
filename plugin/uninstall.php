<?php
/**
 * Conservative uninstall routine for the Platform Plugin.
 *
 * @package AlgonquianRealEstatePlatform
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

if ( ! get_option( 'algq_platform_delete_data_on_uninstall', false ) ) {
	return;
}

global $wpdb;

$options = array(
	'algq_platform_version',
	'algq_platform_schema_version',
	'algq_platform_release_status',
	'algq_platform_capability_version',
	'algq_platform_generated_pages',
	'algq_platform_health_snapshot',
	'algq_platform_delete_data_on_uninstall',
	'algq_mail_settings',
);

foreach ( $options as $option ) {
	delete_option( $option );
	delete_site_option( $option );
}

$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}algq_audit_log" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}algq_mail_log" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared

$roles = wp_roles();
if ( $roles ) {
	$capabilities = array(
		'manage_algq_platform',
		'manage_algq_platform_plugins',
		'manage_algq_platform_pages',
		'manage_algq_platform_files',
		'manage_algq_email',
		'view_algq_audit_logs',
		'export_algq_reports',
		'view_algq_system_health',
	);
	foreach ( array_keys( $roles->roles ) as $role_name ) {
		$role = get_role( $role_name );
		if ( ! $role ) {
			continue;
		}
		foreach ( $capabilities as $capability ) {
			$role->remove_cap( $capability );
		}
	}
}

remove_role( 'algq_platform_manager' );

// Companion-plugin records, buyer roles, generated pages, private documents,
// deals, underwriting, offers, signatures, and automation data are preserved.
