<?php
/**
 * Conservative uninstall routine.
 *
 * @package Algonquian_Deal_Marketplace
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

if ( 'yes' !== get_option( 'algq_dm_delete_data_on_uninstall', 'no' ) ) {
	return;
}

global $wpdb;
foreach ( array( 'activity', 'nda_acceptances', 'offers', 'access_grants' ) as $suffix ) {
	$table = $wpdb->prefix . 'algq_dm_' . $suffix;
	$wpdb->query( "DROP TABLE IF EXISTS {$table}" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
}

$page_options = array( 'algq_dm_marketplace_page_id', 'algq_dm_dashboard_page_id', 'algq_dm_nda_page_id', 'algq_dm_offer_page_id' );
foreach ( $page_options as $option_name ) {
	$page_id = absint( get_option( $option_name ) );
	if ( $page_id > 0 ) {
		wp_trash_post( $page_id );
	}
}

$options = array(
	'algq_dm_schema_version',
	'algq_dm_release_status',
	'algq_dm_nda_required',
	'algq_dm_default_nda_version',
	'algq_dm_delete_data_on_uninstall',
	'algq_dm_legacy_nda_migrated',
	'algq_dm_marketplace_page_id',
	'algq_dm_dashboard_page_id',
	'algq_dm_nda_page_id',
	'algq_dm_offer_page_id',
);
foreach ( $options as $option_name ) {
	delete_option( $option_name );
	delete_site_option( $option_name );
}

$posts = get_posts( array( 'post_type' => 'algq_market_deal', 'post_status' => 'any', 'numberposts' => -1, 'fields' => 'ids' ) );
foreach ( $posts as $post_id ) {
	wp_delete_post( (int) $post_id, true );
}

$marketplace_caps = array(
	'view_algq_marketplace',
	'view_algq_marketplace_deals',
	'accept_algq_marketplace_nda',
	'submit_algq_marketplace_offer',
	'download_algq_marketplace_packages',
	'manage_algq_marketplace',
	'grant_algq_marketplace_access',
	'review_algq_marketplace_offers',
	'export_algq_marketplace_reports',
);
foreach ( wp_roles()->roles as $role_name => $role_data ) {
	unset( $role_data );
	$role = get_role( $role_name );
	if ( $role ) {
		foreach ( $marketplace_caps as $capability ) {
			$role->remove_cap( $capability );
		}
	}
}

// Never delete the shared algq_buyer role or Buyer Portal, Platform, Stripe,
// document, deal, user, or transaction records owned by another module.
