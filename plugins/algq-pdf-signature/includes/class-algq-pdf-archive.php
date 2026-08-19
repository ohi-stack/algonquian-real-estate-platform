<?php
/**
 * Media Library and operational email archive for generated PDFs.
 *
 * Generated PDFs remain in the protected Algonquian private storage directory.
 * This class registers the protected file as a WordPress attachment for Media
 * Library indexing and sends an archival copy through wp_mail(). Direct HTTP
 * access remains blocked by the PDF engine storage guards.
 *
 * @package Algonquian_PDF_Signature
 */

defined( 'ABSPATH' ) || exit;

final class ALGQ_PDF_Archive {
    private const DEFAULT_ARCHIVE_EMAIL = 'algonquianre@gmail.com';
    private const EMAIL_SENT_OPTION_PREFIX = 'algq_pdf_archive_email_sent_';
    private const EMAIL_ATTEMPT_OPTION_PREFIX = 'algq_pdf_archive_email_attempts_';
    private const MAX_EMAIL_ATTEMPTS = 3;

    public static function init(): void {
        add_action( 'algq_pdf_document_generated', array( __CLASS__, 'handle_generated_document' ), 20, 2 );
        add_action( 'algq_pdf_archive_retry_email', array( __CLASS__, 'retry_email' ), 10, 1 );
        add_filter( 'algq_pdf_signature_health_checks', array( __CLASS__, 'health_checks' ) );
    }

    /**
     * Archive a newly generated PDF.
     *
     * @param int                 $document_id PDF engine document ID.
     * @param array<string,mixed> $context     Generation context.
     */
    public static function handle_generated_document( int $document_id, array $context = array() ): void {
        $row = self::document( $document_id );
        if ( ! $row ) {
            self::audit( 'pdf_archive.document_not_found', array( 'document_id' => $document_id ) );
            return;
        }

        $path = self::document_path( $row );
        if ( ! self::valid_pdf( $path, (string) $row->file_hash ) ) {
            self::audit( 'pdf_archive.integrity_failed', array( 'document_id' => $document_id ) );
            return;
        }

        $attachment_id = 0;
        if ( self::archive_to_media_library_enabled() ) {
            $attachment_id = self::ensure_media_attachment( $row, $path );
        }

        if ( self::archive_email_enabled() ) {
            self::send_archive_email( $row, $path, $attachment_id, false );
        }

        self::audit(
            'pdf_archive.completed',
            array(
                'document_id'   => $document_id,
                'attachment_id' => $attachment_id,
                'email_enabled' => self::archive_email_enabled(),
                'media_enabled' => self::archive_to_media_library_enabled(),
            )
        );
    }

    public static function retry_email( int $document_id ): void {
        if ( ! self::archive_email_enabled() || get_option( self::EMAIL_SENT_OPTION_PREFIX . $document_id ) ) {
            return;
        }

        $row = self::document( $document_id );
        if ( ! $row ) {
            return;
        }

        $path = self::document_path( $row );
        if ( ! self::valid_pdf( $path, (string) $row->file_hash ) ) {
            return;
        }

        self::send_archive_email( $row, $path, self::existing_attachment_id( $document_id ), true );
    }

    private static function ensure_media_attachment( object $row, string $path ): int {
        $existing = self::existing_attachment_id( (int) $row->id );
        if ( $existing ) {
            return $existing;
        }

        require_once ABSPATH . 'wp-admin/includes/image.php';
        require_once ABSPATH . 'wp-admin/includes/file.php';
        require_once ABSPATH . 'wp-admin/includes/media.php';

        $attachment = array(
            'post_mime_type' => 'application/pdf',
            'post_title'     => sanitize_text_field( (string) $row->document_title ),
            'post_content'   => '',
            'post_excerpt'   => sprintf(
                'Protected Algonquian PDF document %s, version %d.',
                sanitize_text_field( (string) $row->uuid ),
                absint( $row->version_number )
            ),
            'post_status'    => 'inherit',
        );

        $attachment_id = wp_insert_attachment( $attachment, $path, 0, true );
        if ( is_wp_error( $attachment_id ) ) {
            self::audit(
                'pdf_archive.media_insert_failed',
                array(
                    'document_id' => (int) $row->id,
                    'error'       => $attachment_id->get_error_code(),
                )
            );
            return 0;
        }

        $attachment_id = (int) $attachment_id;
        update_attached_file( $attachment_id, $path );
        update_post_meta( $attachment_id, '_algq_private_attachment', 1 );
        update_post_meta( $attachment_id, '_algq_pdf_document_id', (int) $row->id );
        update_post_meta( $attachment_id, '_algq_pdf_document_uuid', sanitize_text_field( (string) $row->uuid ) );
        update_post_meta( $attachment_id, '_algq_pdf_document_version', absint( $row->version_number ) );
        update_post_meta( $attachment_id, '_algq_pdf_sha256', sanitize_text_field( (string) $row->file_hash ) );
        update_post_meta( $attachment_id, '_algq_pdf_deal_id', absint( $row->deal_id ) );
        update_post_meta( $attachment_id, '_algq_source_plugin', sanitize_key( (string) $row->source_plugin ) );
        update_post_meta( $attachment_id, '_algq_source_record_id', sanitize_text_field( (string) $row->source_record_id ) );

        $metadata = wp_generate_attachment_metadata( $attachment_id, $path );
        if ( is_array( $metadata ) && $metadata ) {
            wp_update_attachment_metadata( $attachment_id, $metadata );
        }

        self::audit(
            'pdf_archive.media_registered',
            array(
                'document_id'   => (int) $row->id,
                'attachment_id' => $attachment_id,
                'sha256'        => sanitize_text_field( (string) $row->file_hash ),
            )
        );

        return $attachment_id;
    }

    private static function send_archive_email( object $row, string $path, int $attachment_id, bool $is_retry ): bool {
        $document_id = (int) $row->id;
        if ( get_option( self::EMAIL_SENT_OPTION_PREFIX . $document_id ) ) {
            return true;
        }

        $attempts = absint( get_option( self::EMAIL_ATTEMPT_OPTION_PREFIX . $document_id, 0 ) );
        if ( $attempts >= self::MAX_EMAIL_ATTEMPTS ) {
            return false;
        }
        update_option( self::EMAIL_ATTEMPT_OPTION_PREFIX . $document_id, $attempts + 1, false );

        $email = self::archive_email();
        if ( ! is_email( $email ) ) {
            self::audit( 'pdf_archive.email_invalid', array( 'document_id' => $document_id ) );
            return false;
        }

        $max_bytes = max( 1048576, absint( apply_filters( 'algq_pdf_archive_max_email_bytes', 10485760 ) ) );
        if ( filesize( $path ) > $max_bytes ) {
            self::audit(
                'pdf_archive.email_skipped_size',
                array(
                    'document_id' => $document_id,
                    'file_size'   => (int) filesize( $path ),
                    'max_bytes'   => $max_bytes,
                )
            );
            return false;
        }

        $subject = sprintf(
            'ARE PDF Archive: %s v%d',
            sanitize_text_field( (string) $row->document_title ),
            absint( $row->version_number )
        );
        $message = implode(
            "\n",
            array(
                'A PDF was generated by the Algonquian Real Estate Platform and archived.',
                '',
                'Document ID: ' . $document_id,
                'Document UUID: ' . sanitize_text_field( (string) $row->uuid ),
                'Document Type: ' . sanitize_key( (string) $row->document_type ),
                'Version: ' . absint( $row->version_number ),
                'Deal ID: ' . absint( $row->deal_id ),
                'Source: ' . sanitize_key( (string) $row->source_plugin ),
                'Source Record: ' . sanitize_text_field( (string) $row->source_record_id ),
                'SHA-256: ' . sanitize_text_field( (string) $row->file_hash ),
                'Media Attachment ID: ' . $attachment_id,
                '',
                'The attached copy is for Algonquian Real Estate operational records.',
            )
        );

        $sent = wp_mail(
            $email,
            $subject,
            $message,
            array( 'Content-Type: text/plain; charset=UTF-8' ),
            array( $path )
        );

        if ( $sent ) {
            update_option( self::EMAIL_SENT_OPTION_PREFIX . $document_id, gmdate( 'c' ), false );
            delete_option( self::EMAIL_ATTEMPT_OPTION_PREFIX . $document_id );
            if ( $attachment_id ) {
                update_post_meta( $attachment_id, '_algq_pdf_archive_emailed_at', current_time( 'mysql', true ) );
                update_post_meta( $attachment_id, '_algq_pdf_archive_email_hash', hash_hmac( 'sha256', strtolower( $email ), wp_salt( 'auth' ) ) );
            }
            self::audit(
                'pdf_archive.email_sent',
                array(
                    'document_id'   => $document_id,
                    'attachment_id' => $attachment_id,
                    'retry'         => $is_retry,
                )
            );
            return true;
        }

        self::audit(
            'pdf_archive.email_failed',
            array(
                'document_id' => $document_id,
                'attempt'     => $attempts + 1,
                'retry'       => $is_retry,
            )
        );

        if ( $attempts + 1 < self::MAX_EMAIL_ATTEMPTS && ! wp_next_scheduled( 'algq_pdf_archive_retry_email', array( $document_id ) ) ) {
            wp_schedule_single_event( time() + ( 5 * MINUTE_IN_SECONDS ), 'algq_pdf_archive_retry_email', array( $document_id ) );
        }

        return false;
    }

    private static function existing_attachment_id( int $document_id ): int {
        $ids = get_posts(
            array(
                'post_type'      => 'attachment',
                'post_status'    => 'inherit',
                'posts_per_page' => 1,
                'fields'         => 'ids',
                'meta_key'       => '_algq_pdf_document_id',
                'meta_value'     => $document_id,
                'no_found_rows'  => true,
            )
        );
        return $ids ? absint( $ids[0] ) : 0;
    }

    private static function document( int $document_id ): ?object {
        global $wpdb;
        if ( $document_id <= 0 ) {
            return null;
        }
        $table = $wpdb->prefix . 'algq_pdf_documents';
        $row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d AND deleted_at IS NULL LIMIT 1", $document_id ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        return is_object( $row ) ? $row : null;
    }

    private static function document_path( object $row ): string {
        $uploads = wp_upload_dir( null, false );
        $default = trailingslashit( (string) $uploads['basedir'] ) . 'algq-private/pdf-signature';
        $directory = untrailingslashit( (string) apply_filters( 'algq_pdf_signature_storage_directory', $default ) );
        return trailingslashit( $directory ) . basename( (string) $row->file_path );
    }

    private static function valid_pdf( string $path, string $expected_hash ): bool {
        if ( ! is_file( $path ) || ! is_readable( $path ) || 'application/pdf' !== (string) wp_check_filetype( $path )['type'] ) {
            return false;
        }
        $actual_hash = hash_file( 'sha256', $path );
        return '' !== $actual_hash && '' !== $expected_hash && hash_equals( $expected_hash, $actual_hash );
    }

    private static function archive_email(): string {
        $email = defined( 'ALGQ_PDF_ARCHIVE_EMAIL' )
            ? (string) ALGQ_PDF_ARCHIVE_EMAIL
            : (string) get_option( 'algq_pdf_archive_email', self::DEFAULT_ARCHIVE_EMAIL );
        return sanitize_email( (string) apply_filters( 'algq_pdf_archive_email', $email ) );
    }

    private static function archive_email_enabled(): bool {
        $enabled = defined( 'ALGQ_PDF_ARCHIVE_SEND_EMAIL' ) ? (bool) ALGQ_PDF_ARCHIVE_SEND_EMAIL : true;
        return (bool) apply_filters( 'algq_pdf_archive_send_email', $enabled );
    }

    private static function archive_to_media_library_enabled(): bool {
        $enabled = defined( 'ALGQ_PDF_ARCHIVE_TO_MEDIA' ) ? (bool) ALGQ_PDF_ARCHIVE_TO_MEDIA : true;
        return (bool) apply_filters( 'algq_pdf_archive_to_media_library', $enabled );
    }

    /** @param array<string,mixed> $checks */
    public static function health_checks( array $checks ): array {
        $checks['pdf_archive_email'] = array(
            'label'  => 'PDF archive email',
            'status' => ! self::archive_email_enabled() || is_email( self::archive_email() ) ? 'healthy' : 'failed',
        );
        $checks['pdf_archive_media_library'] = array(
            'label'  => 'PDF Media Library archive',
            'status' => ! self::archive_to_media_library_enabled() || function_exists( 'wp_insert_attachment' ) ? 'healthy' : 'failed',
        );
        return $checks;
    }

    /** @param array<string,mixed> $context */
    private static function audit( string $event, array $context ): void {
        if ( function_exists( 'algq_log_event' ) ) {
            algq_log_event( $event, array_merge( array( 'plugin' => 'algq-pdf-signature' ), $context ) );
            return;
        }
        do_action( 'algq_audit_event', $event, array_merge( array( 'plugin' => 'algq-pdf-signature' ), $context ) );
    }
}
