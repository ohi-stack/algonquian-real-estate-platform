<?php
/**
 * Conservative uninstall routine.
 *
 * Documents, requests, audit records, and private files are preserved unless an
 * administrator explicitly enables complete cleanup before uninstalling.
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

if ( ! (bool) get_option( 'algq_document_library_delete_data_on_uninstall', false ) ) {
    return;
}

global $wpdb;

$document_ids = get_posts(
    array(
        'post_type'      => array( 'algq_document', 'algq_doc_package' ),
        'post_status'    => 'any',
        'posts_per_page' => -1,
        'fields'         => 'ids',
    )
);

foreach ( $document_ids as $document_id ) {
    wp_delete_post( $document_id, true );
}

$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}algq_document_requests" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.SchemaChange,WordPress.DB.PreparedSQL.InterpolatedNotPrepared
$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}algq_document_downloads" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.SchemaChange,WordPress.DB.PreparedSQL.InterpolatedNotPrepared

delete_option( 'algq_document_library_schema_version' );
delete_option( 'algq_document_library_version' );
delete_option( 'algq_document_library_delete_data_on_uninstall' );

foreach ( array( 'administrator' ) as $role_name ) {
    $role = get_role( $role_name );
    if ( ! $role ) {
        continue;
    }
    foreach ( array( 'manage_algq_documents', 'view_algq_documents', 'upload_algq_documents', 'download_algq_documents', 'manage_algq_document_requests', 'assemble_algq_document_packages', 'view_algq_document_audit' ) as $capability ) {
        $role->remove_cap( $capability );
    }
}

// Private files are intentionally not deleted automatically. Removal should be
// performed as a separately authorized records-retention action.
