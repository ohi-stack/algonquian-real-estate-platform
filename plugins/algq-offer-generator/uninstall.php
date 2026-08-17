<?php
/**
 * Algonquian Offer Generator uninstall routine.
 *
 * Offer records, versions, and audit evidence are preserved by default.
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

$capabilities = array(
    'manage_algq_offers',
    'create_algq_offers',
    'edit_algq_offer',
    'read_algq_offer',
    'delete_algq_offer',
    'edit_algq_offers',
    'edit_others_algq_offers',
    'publish_algq_offers',
    'read_private_algq_offers',
    'delete_algq_offers',
    'delete_private_algq_offers',
    'delete_published_algq_offers',
    'delete_others_algq_offers',
    'edit_private_algq_offers',
    'edit_published_algq_offers',
    'approve_algq_offers',
    'send_algq_offers',
    'generate_algq_offer_documents',
    'view_algq_offer_history',
    'manage_algq_offer_templates',
);

$roles = wp_roles();
if ( $roles ) {
    foreach ( $roles->roles as $role_name => $role_data ) {
        $role = get_role( $role_name );
        if ( ! $role ) {
            continue;
        }
        foreach ( $capabilities as $capability ) {
            $role->remove_cap( $capability );
        }
    }
}
remove_role( 'algq_offer_manager' );

$delete_data = (bool) get_option( 'algq_offer_delete_data_on_uninstall', false );
if ( ! $delete_data ) {
    return;
}

foreach ( array( 'algq_offer', 'algq_offer_template' ) as $post_type ) {
    $ids = get_posts( array( 'post_type' => $post_type, 'post_status' => 'any', 'posts_per_page' => -1, 'fields' => 'ids' ) );
    foreach ( $ids as $id ) {
        wp_delete_post( $id, true );
    }
}

foreach ( array( 'algq_offer_db_version', 'algq_offer_default_strategy', 'algq_offer_company_name', 'algq_offer_delete_data_on_uninstall', 'algq_offer_page_offer_generator', 'algq_offer_page_generate_offer', 'algq_offer_page_offer_history' ) as $option ) {
    delete_option( $option );
    delete_site_option( $option );
}

global $wpdb;
$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}algq_offer_audit_log" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.SchemaChange
