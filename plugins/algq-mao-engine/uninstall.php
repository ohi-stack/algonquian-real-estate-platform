<?php
/**
 * Conservative uninstall routine.
 *
 * @package Algonquian_MAO_Engine
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

$remove_data = defined( 'ALGQ_MAO_REMOVE_DATA' ) && true === ALGQ_MAO_REMOVE_DATA;
$remove_data = (bool) apply_filters( 'algq_mao_remove_data_on_uninstall', $remove_data );

if ( ! $remove_data ) {
	return;
}

global $wpdb;
$table = $wpdb->prefix . 'algq_underwriting';
$wpdb->query( "DROP TABLE IF EXISTS {$table}" ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

delete_option( 'algq_mao_assumptions' );
delete_option( 'algq_mao_schema_version' );

foreach ( array( 'administrator', 'algq_acquisition_manager', 'algq_analyst', 'algq_auditor' ) as $role_name ) {
	$role = get_role( $role_name );
	if ( ! $role ) {
		continue;
	}
	foreach ( array( 'view_algq_underwriting', 'manage_algq_underwriting', 'approve_algq_underwriting', 'manage_algq_mao_settings' ) as $capability ) {
		$role->remove_cap( $capability );
	}
}
