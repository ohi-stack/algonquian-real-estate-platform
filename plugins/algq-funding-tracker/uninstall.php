<?php
/**
 * Algonquian Funding Tracker uninstall routine.
 *
 * Operational records are preserved unless an administrator explicitly enables
 * complete cleanup before uninstalling the plugin.
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

if ( ! get_option( 'algq_funding_tracker_delete_data_on_uninstall', false ) ) {
	return;
}

global $wpdb;

foreach ( array( 'algq_capital_sources', 'algq_funding_commitments', 'algq_funding_activity' ) as $table_suffix ) {
	$table = $wpdb->prefix . $table_suffix;
	$wpdb->query( "DROP TABLE IF EXISTS `{$table}`" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
}

foreach ( array( 'algq_funding_tracker_schema_version', 'algq_funding_tracker_delete_data_on_uninstall' ) as $option_name ) {
	delete_option( $option_name );
	delete_site_option( $option_name );
}

$capabilities = array( 'manage_algq_funding', 'view_algq_funding', 'edit_algq_funding', 'export_algq_funding' );
$roles        = wp_roles();

if ( $roles ) {
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
