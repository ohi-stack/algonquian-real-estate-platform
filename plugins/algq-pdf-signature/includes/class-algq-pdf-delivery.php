<?php
/**
 * Production delivery bridge for generated Algonquian PDFs.
 *
 * Registers each generated PDF as a private WordPress Media Library record
 * without moving the protected file into a public uploads location, and sends
 * an archival copy to the configured ARE operations mailbox.
 *
 * @package Algonquian_PDF_Signature
 */

defined( 'ABSPATH' ) || exit;

final class ALGQ_PDF_Delivery {
    private const DEFAULT_RECIPIENT = 'algonquianre@gmail.com';

    public static function init(): void {
        add_action( 'algq_pdf_document_generated', array( __CLASS__, 'deliver_generated_pdf' ), 20, 2 );
        add_filter( 'wp_get_attachment_url', array( __CLASS__, 'private_attachment_url' ), 20, 2 );
    }

    /**
     * Register the protected file in Media Library and email an archival copy.
     *
     * @param int   $document_id PDF Engine document record ID.
     * @param array $context     Generation context.
     */
    public static function deliver_generated_pdf( int $document_id, array $context ): void {
        $record = self::document_record( $document_id );
        if ( ! $record ) {
            self::audit( 'pdf.delivery_record_missing', $document_id, array() );
            return;
        }

        $path = self::document_path( $record );
        if ( ! is_file( $path ) ) {
            self::audit( 'pdf.delivery_file_missing', $document_id, array() );
            return;
        }

        $actual_hash = (string) hash_file( 'sha256', $path );
        if ( empty( $record->file_hash ) || ! hash_equals( (string) $record->file_hash, $actual_hash ) ) {
            self::audit( 'pdf.delivery_integrity_failed', $document_id, array() );
            return;
        }

        $attachment_id = self::register_private_media_attachment( $record, $path, $actual_hash );
        if ( is_wp_error( $attachment_id ) ) {
            self::audit( 'pdf.media_registration_failed', $document_id, array( 'error' => $attachment_id->get_error_code() ) );
        } else {
            self::audit( 'pdf.media_registered', $document_id, array( 'attachment_id' => (int) $attachment_id ) );
        }

        $mail_result = self::email_archival_copy( $record, $path, $context, is_wp_error( $attachment_id ) ? 0 : (int) $attachment_id );
        self::audit(
            $mail_result ? 'pdf.archive_email_sent' : 'pdf.archive_email_failed',
            $document_id,
            array( 'attachment_id' => is_wp_error( $attachment_id ) ? 0 : (int) $attachment_id )
        );
    }

    private static function register_private_media_attachment( object $record, string $path, string $hash ) {
        $existing = self::find_attachment( (int) $record->id );
        if ( $existing ) {
            update_post_meta( $existing, '_algq_private_file_path', $path );
            update_post_meta( $existing, '_algq_private_file_hash', $hash );
            return $existing;
        }

        $attachment_id = wp_insert_attachment(
            array(
                'post_mime_type' => 'application/pdf',
                'post_title'     => sanitize_text_field( (string) $record->document_title ),
                'post_content'   => '',
                'post_excerpt'   => sprintf(
                    'Protected Algonquian PDF record %s, version %d.',
                    sanitize_text_field( (string) $record->uuid ),
                    absint( $record->version_number )
                ),
                'post_status'    => 'inherit',
                'guid'           => self::protected_download_url( (int) $record->id ),
            ),
            '',
            0,
            true
        );

        if ( is_wp_error( $attachment_id ) ) {
            return $attachment_id;
        }

        update_post_meta( $attachment_id, '_algq_private_media', 1 );
        update_post_meta( $attachment_id, '_algq_pdf_document_id', (int) $record->id );
        update_post_meta( $attachment_id, '_algq_pdf_document_uuid', sanitize_text_field( (string) $record->uuid ) );
        update_post_meta( $attachment_id, '_algq_private_file_path', $path );
        update_post_meta( $attachment_id, '_algq_private_file_hash', $hash );
        update_post_meta( $attachment_id, '_algq_source_plugin', sanitize_key( (string) $record->source_plugin ) );
        update_post_meta( $attachment_id, '_algq_source_record_id', sanitize_text_field( (string) $record->source_record_id ) );
        update_post_meta( $attachment_id, '_algq_deal_id', absint( $record->deal_id ) );

        return (int) $attachment_id;
    }

    private static function find_attachment( int $document_id ): int {
        $ids = get_posts(
            array(
                'post_type'      => 'attachment',
                'post_status'    => 'inherit',
                'fields'         => 'ids',
                'posts_per_page' => 1,
                'meta_key'       => '_algq_pdf_document_id',
                'meta_value'     => $document_id,
                'no_found_rows'  => true,
            )
        );

        return $ids ? (int) $ids[0] : 0;
    }

    public static function private_attachment_url( $url, int $attachment_id ) {
        if ( ! get_post_meta( $attachment_id, '_algq_private_media', true ) ) {
            return $url;
        }

        $document_id = absint( get_post_meta( $attachment_id, '_algq_pdf_document_id', true ) );
        return $document_id ? self::protected_download_url( $document_id ) : $url;
    }

    private static function protected_download_url( int $document_id ): string {
        $url = add_query_arg(
            array(
                'action'      => 'algq_pdf_download',
                'document_id' => $document_id,
            ),
            admin_url( 'admin-post.php' )
        );

        return wp_nonce_url( $url, 'algq_pdf_download_' . $document_id );
    }

    private static function email_archival_copy( object $record, string $path, array $context, int $attachment_id ): bool {
        $recipient = sanitize_email(
            (string) apply_filters( 'algq_pdf_delivery_recipient', get_option( 'algq_pdf_delivery_recipient', self::DEFAULT_RECIPIENT ), $record, $context )
        );
        if ( ! is_email( $recipient ) ) {
            return false;
        }

        $subject = sprintf(
            '[ARE] PDF Generated: %s (v%d)',
            sanitize_text_field( (string) $record->document_title ),
            absint( $record->version_number )
        );

        $message = implode(
            "\n",
            array(
                'A PDF was generated by the Algonquian Real Estate Platform.',
                '',
                'Document: ' . sanitize_text_field( (string) $record->document_title ),
                'Type: ' . sanitize_text_field( (string) $record->document_type ),
                'Version: ' . absint( $record->version_number ),
                'Document UUID: ' . sanitize_text_field( (string) $record->uuid ),
                'Deal ID: ' . absint( $record->deal_id ),
                'Source Plugin: ' . sanitize_text_field( (string) $record->source_plugin ),
                'Source Record: ' . sanitize_text_field( (string) $record->source_record_id ),
                'SHA-256: ' . sanitize_text_field( (string) $record->file_hash ),
                'Media Library Attachment ID: ' . absint( $attachment_id ),
                '',
                'The attached PDF is an archival copy. The authoritative protected record remains in the Algonquian PDF & Signature Engine.',
            )
        );

        return (bool) wp_mail(
            $recipient,
            $subject,
            $message,
            array( 'Content-Type: text/plain; charset=UTF-8' ),
            array( $path )
        );
    }

    private static function document_record( int $document_id ): ?object {
        global $wpdb;
        $table = $wpdb->prefix . 'algq_pdf_documents';
        $row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d AND deleted_at IS NULL", $document_id ) );
        return $row ?: null;
    }

    private static function document_path( object $record ): string {
        $uploads = wp_upload_dir( null, false );
        $directory = untrailingslashit(
            (string) apply_filters(
                'algq_pdf_signature_storage_directory',
                trailingslashit( (string) $uploads['basedir'] ) . 'algq-private/pdf-signature'
            )
        );
        return trailingslashit( $directory ) . basename( (string) $record->file_path );
    }

    private static function audit( string $event, int $document_id, array $context ): void {
        $payload = array_merge(
            array(
                'plugin'      => 'algq-pdf-signature',
                'document_id' => $document_id,
            ),
            $context
        );

        if ( function_exists( 'algq_log_event' ) ) {
            algq_log_event( $event, $payload );
            return;
        }

        do_action( 'algq_audit_event', $event, $payload );
    }
}
