<?php
/**
 * Conservative uninstall routine.
 *
 * Data is retained unless an administrator explicitly enabled deletion before uninstall.
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

if ( ! get_option( 'algq_pdf_signature_delete_on_uninstall', false ) ) {
    return;
}

global $wpdb;
foreach ( array( 'algq_signature_events', 'algq_signature_signers', 'algq_signature_requests', 'algq_pdf_documents' ) as $table ) {
    $wpdb->query( 'DROP TABLE IF EXISTS ' . $wpdb->prefix . $table ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
}

$uploads = wp_upload_dir( null, false );
$directory = trailingslashit( $uploads['basedir'] ) . 'algq-private/pdf-signature';
if ( is_dir( $directory ) ) {
    $iterator = new RecursiveIteratorIterator( new RecursiveDirectoryIterator( $directory, FilesystemIterator::SKIP_DOTS ), RecursiveIteratorIterator::CHILD_FIRST );
    foreach ( $iterator as $item ) {
        $item->isDir() ? rmdir( $item->getPathname() ) : unlink( $item->getPathname() );
    }
    rmdir( $directory );
}

foreach ( array( 'algq_pdf_signature_version', 'algq_pdf_signature_schema_version', 'algq_pdf_signature_pages', 'algq_pdf_signature_settings', 'algq_pdf_signature_delete_on_uninstall' ) as $option ) {
    delete_option( $option );
}
