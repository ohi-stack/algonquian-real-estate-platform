<?php
/**
 * Algonquian Digital Products uninstall routine.
 *
 * Catalog records and protected-delivery references are preserved unless an
 * administrator explicitly enables destructive cleanup before uninstalling.
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

$delete_data = (bool) get_option( 'algq_digital_products_delete_data_on_uninstall', false );
$roles = wp_roles();

if ( $roles ) {
    foreach ( array_keys( $roles->roles ) as $role_name ) {
        $role = get_role( $role_name );
        if ( $role ) {
            $role->remove_cap( 'manage_algq_digital_products' );
        }
    }
}

if ( ! $delete_data ) {
    return;
}

$page_ids = get_option( 'algq_digital_products_page_ids', array() );
if ( is_array( $page_ids ) ) {
    foreach ( array_unique( array_map( 'absint', $page_ids ) ) as $page_id ) {
        if ( $page_id ) {
            wp_delete_post( $page_id, true );
        }
    }
}

$products = get_posts(
    array(
        'post_type'      => 'algq_digital_product',
        'post_status'    => 'any',
        'posts_per_page' => -1,
        'fields'         => 'ids',
    )
);

foreach ( $products as $product_id ) {
    wp_delete_post( $product_id, true );
}

delete_option( 'algq_digital_products_version' );
delete_option( 'algq_digital_products_schema_version' );
delete_option( 'algq_digital_products_page_ids' );
delete_option( 'algq_digital_products_delete_data_on_uninstall' );
