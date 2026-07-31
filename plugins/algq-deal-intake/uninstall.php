<?php
/**
 * Algonquian Deal Intake uninstall routine.
 *
 * Operational records are preserved unless an administrator explicitly enabled
 * complete Deal Intake cleanup before uninstalling the plugin.
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

if ( ! (bool) get_option( 'algq_di_delete_data_on_uninstall', false ) ) {
	return;
}

global $wpdb;

$tables = array(
	$wpdb->prefix . 'algq_intake_duplicate_queue',
	$wpdb->prefix . 'algq_intake_attachments',
	$wpdb->prefix . 'algq_intake_consents',
	$wpdb->prefix . 'algq_intake_submissions',
	$wpdb->prefix . 'algq_intake_properties',
	$wpdb->prefix . 'algq_intake_sellers',
);

foreach ( $tables as $table ) {
	$wpdb->query( "DROP TABLE IF EXISTS `{$table}`" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
}

$options = array(
	'algq_di_version',
	'algq_di_schema_version',
	'algq_di_caps_version',
	'algq_di_notification_email',
	'algq_di_privacy_version',
	'algq_di_terms_version',
	'algq_di_consent_version',
	'algq_di_rate_limit_per_hour',
	'algq_di_delete_data_on_uninstall',
	'algq_di_submit_property_page_id',
	'algq_di_sell_property_page_id',
	'algq_di_homeowner_options_page_id',
	'algq_di_seller_portal_page_id',
	'algq_di_thank_you_page_id',
	'algq_di_plugin_page_id',
	'algq_di_start_page_id',
	'algq_di_docs_page_id',
);

foreach ( $options as $option ) {
	delete_option( $option );
	delete_site_option( $option );
}

foreach ( array( 'administrator', 'algq_acquisition_manager', 'algq_lead_coordinator' ) as $role_name ) {
	$role = get_role( $role_name );
	if ( ! $role ) {
		continue;
	}
	foreach ( array( 'manage_algq_intake', 'review_algq_intake', 'export_algq_intake', 'view_algq_intake_private' ) as $capability ) {
		$role->remove_cap( $capability );
	}
}
