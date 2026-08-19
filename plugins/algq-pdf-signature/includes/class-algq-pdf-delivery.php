<?php
/**
 * Protected PDF archival and company delivery service.
 *
 * Generated PDFs remain authoritative in the PDF Engine's protected storage.
 * This service registers the protected file in the WordPress Media Library
 * without copying it into a public directory, and emails a copy to the
 * configured Algonquian Real Estate operational address.
 *
 * @package Algonquian_PDF_Signature
 */

defined( 'ABSPATH' ) || exit;

final class ALGQ_PDF_Delivery {
    private const COMPANY_EMAIL = 'algonquianre@gmail.com';

    public static function init(): void {
        add_action( 'algq_pdf_document_generated', array( __CLASS__, 'handle_generated_document' ), 20, 2 );
        add_filter( 'algq_pdf_signature_render_offer', array( __CLASS__, 'render_offer_pdf' ), 20, 4 );
        add_filter( 'wp_get_attachment_url', array( __CLASS__, 'protect_attachment_url' ), 20, 2 );
        add_filter( 'pre_delete_attachment', array( __CLASS__, 'prevent_authoritative_file_delete' ), 20, 3 );
    }

    /**
     * Register a generated PDF in Media Library and send the protected copy to
     * the company operations mailbox. Delivery failures never delete or
     * invalidate the authoritative PDF record.
     *
     * @param int                 $document_id PDF Engine document ID.
     * @param array<string,mixed> $context Generation context.
     */
    public static function handle_generated_document( int $document_id, array $context ): void {
        $record = self::document_record( $document_id );
        if ( ! $record ) {
            self::audit( 'pdf.delivery_record_missing', array( 'document_id' => $document_id ) );
            return;
        }

        $path = self::document_path( (string) $record->file_path );
        if ( ! is_file( $path ) || ! is_readable( $path ) ) {
            self::audit( 'pdf.delivery_file_missing', array( 'document_id' => $document_id ) );
            return;
        }

        $actual_hash = hash_file( 'sha256', $path );
        if ( ! is_string( $actual_hash ) || ! hash_equals( (string) $record->file_hash, $actual_hash ) ) {
            self::audit( 'pdf.delivery_integrity_failed', array( 'document_id' => $document_id ) );
            return;
        }

        $attachment_id = 0;
        if ( self::media_library_enabled() ) {
            $attachment_id = self::register_media_attachment( $document_id, $record, $path );
        }

        $sent = false;
        if ( self::email_delivery_enabled() ) {
            $sent = self::send_company_copy( $document_id, $record, $path, $context );
        }

        self::audit(
            'pdf.delivery_completed',
            array(
                'document_id'   => $document_id,
                'attachment_id' => $attachment_id,
                'email_sent'    => $sent,
            )
        );
    }

    /**
     * Bridge Offer Generator requests into the authoritative PDF Engine. This
     * prevents HTML from ever being mislabeled as a PDF and ensures offer PDFs
     * receive protected storage, Media Library, hashing, and company delivery.
     *
     * @param mixed               $result Existing integration result.
     * @param int                 $offer_id Offer post ID.
     * @param string              $html Approved offer HTML.
     * @param array<string,mixed> $args Integration arguments.
     * @return array<string,mixed>
     */
    public static function render_offer_pdf( $result, int $offer_id, string $html, array $args ): array {
        unset( $result );

        if ( ! class_exists( 'ALGQ_PDF_Signature' ) || ! method_exists( 'ALGQ_PDF_Signature', 'generate' ) || $offer_id <= 0 ) {
            return array();
        }

        $title = sanitize_text_field( get_the_title( $offer_id ) );
        if ( '' === $title && ! empty( $args['filename'] ) ) {
            $title = sanitize_text_field( pathinfo( sanitize_file_name( (string) $args['filename'] ), PATHINFO_FILENAME ) );
        }
        if ( '' === $title ) {
            $title = sprintf( __( 'Offer %d', 'algq-pdf-signature' ), $offer_id );
        }

        $document_id = ALGQ_PDF_Signature::generate(
            array(
                'document_title'   => $title,
                'document_type'    => 'offer',
                'document_content' => wp_kses_post( $html ),
                'deal_id'          => absint( get_post_meta( $offer_id, '_algq_offer_deal_id', true ) ?: get_post_meta( $offer_id, '_algq_deal_id', true ) ),
                'source_plugin'     => 'algq-offer-generator',
                'source_record_id'  => (string) $offer_id,
            )
        );

        if ( is_wp_error( $document_id ) ) {
            self::audit(
                'pdf.offer_generation_failed',
                array(
                    'offer_id' => $offer_id,
                    'error'    => $document_id->get_error_code(),
                )
            );
            return array();
        }

        $attachment_id = self::attachment_id_for_document( (int) $document_id );
        self::audit( 'pdf.offer_generated', array( 'offer_id' => $offer_id, 'document_id' => (int) $document_id, 'attachment_id' => $attachment_id ) );

        return array(
            'document_id'   => (int) $document_id,
            'attachment_id' => $attachment_id,
            'status'        => 'generated',
            'private'       => true,
        );
    }

    private static function register_media_attachment( int $document_id, object $record, string $path ): int {
        $existing_id = self::attachment_id_for_document( $document_id );
        if ( $existing_id ) {
            return $existing_id;
        }

        $title = sanitize_text_field( (string) $record->document_title );
        $version = absint( $record->version_number );

        $attachment_id = wp_insert_attachment(
            array(
                'post_mime_type' => 'application/pdf',
                'post_title'     => trim( $title . ' v' . $version ),
                'post_content'   => __( 'Protected Algonquian PDF record. Access the file through the authorized PDF & Signature Engine workflow.', 'algq-pdf-signature' ),
                'post_status'    => 'inherit',
                'post_parent'    => 0,
            ),
            $path,
            0,
            true
        );

        if ( is_wp_error( $attachment_id ) ) {
            self::audit(
                'pdf.media_registration_failed',
                array(
                    'document_id' => $document_id,
                    'error'       => $attachment_id->get_error_code(),
                )
            );
            return 0;
        }

        $attachment_id = (int) $attachment_id;
        update_attached_file( $attachment_id, $path );
        update_post_meta( $attachment_id, '_algq_pdf_document_id', $document_id );
        update_post_meta( $attachment_id, '_algq_pdf_document_uuid', sanitize_text_field( (string) $record->uuid ) );
        update_post_meta( $attachment_id, '_algq_pdf_file_hash', sanitize_text_field( (string) $record->file_hash ) );
        update_post_meta( $attachment_id, '_algq_protected_attachment', '1' );
        update_post_meta( $attachment_id, '_algq_media_authority', 'algq-pdf-signature' );

        require_once ABSPATH . 'wp-admin/includes/image.php';
        $metadata = wp_generate_attachment_metadata( $attachment_id, $path );
        if ( is_array( $metadata ) && ! empty( $metadata ) ) {
            wp_update_attachment_metadata( $attachment_id, $metadata );
        }

        self::audit(
            'pdf.media_registered',
            array(
                'document_id'   => $document_id,
                'attachment_id' => $attachment_id,
            )
        );

        return $attachment_id;
    }

    private static function attachment_id_for_document( int $document_id ): int {
        $existing = get_posts(
            array(
                'post_type'      => 'attachment',
                'post_status'    => 'inherit',
                'posts_per_page' => 1,
                'fields'         => 'ids',
                'meta_key'       => '_algq_pdf_document_id',
                'meta_value'     => $document_id,
            )
        );
        return $existing ? (int) $existing[0] : 0;
    }

    /**
     * Replace the direct uploads URL with the PDF Engine's nonce-protected
     * download controller for protected Media Library records.
     */
    public static function protect_attachment_url( string $url, int $attachment_id ): string {
        if ( '1' !== (string) get_post_meta( $attachment_id, '_algq_protected_attachment', true ) ) {
            return $url;
        }

        $document_id = absint( get_post_meta( $attachment_id, '_algq_pdf_document_id', true ) );
        if ( ! $document_id || ! is_user_logged_in() || ! current_user_can( 'view_algq_documents' ) ) {
            return '';
        }

        return wp_nonce_url(
            admin_url( 'admin-post.php?action=algq_pdf_download&document_id=' . $document_id ),
            'algq_pdf_download_' . $document_id
        );
    }

    /**
     * Media Library is a secondary index only. Prevent deleting the canonical
     * protected PDF file through attachment deletion.
     *
     * @param mixed   $delete       Short-circuit value.
     * @param WP_Post $post         Attachment post.
     * @param bool    $force_delete Whether permanent deletion was requested.
     * @return mixed
     */
    public static function prevent_authoritative_file_delete( $delete, WP_Post $post, bool $force_delete ) {
        unset( $force_delete );

        if ( '1' !== (string) get_post_meta( $post->ID, '_algq_protected_attachment', true ) ) {
            return $delete;
        }

        self::audit(
            'pdf.media_delete_blocked',
            array(
                'attachment_id' => $post->ID,
                'document_id'   => absint( get_post_meta( $post->ID, '_algq_pdf_document_id', true ) ),
            )
        );

        return false;
    }

    /** @param array<string,mixed> $context */
    private static function send_company_copy( int $document_id, object $record, string $path, array $context ): bool {
        $email = self::company_email();
        if ( ! is_email( $email ) ) {
            return false;
        }

        $size = is_file( $path ) ? (int) filesize( $path ) : 0;
        $max_bytes = max( 0, (int) apply_filters( 'algq_pdf_company_email_max_bytes', 15 * MB_IN_BYTES ) );
        if ( $max_bytes > 0 && $size > $max_bytes ) {
            self::audit( 'pdf.company_email_skipped_size', array( 'document_id' => $document_id, 'file_size' => $size, 'max_bytes' => $max_bytes ) );
            return false;
        }

        $subject = sprintf(
            '[ARE PDF] %s — v%d',
            sanitize_text_field( (string) $record->document_title ),
            absint( $record->version_number )
        );

        $message = sprintf(
            "A protected PDF was generated by the Algonquian PDF & Signature Engine.\n\nDocument ID: %d\nUUID: %s\nTitle: %s\nType: %s\nVersion: %d\nDeal ID: %d\nSHA-256: %s\n\nThe authoritative copy remains in protected platform storage. The attached copy is provided to the Algonquian Real Estate operations mailbox for controlled business records.",
            $document_id,
            sanitize_text_field( (string) $record->uuid ),
            sanitize_text_field( (string) $record->document_title ),
            sanitize_key( (string) $record->document_type ),
            absint( $record->version_number ),
            absint( $context['deal_id'] ?? $record->deal_id ),
            sanitize_text_field( (string) $record->file_hash )
        );

        if ( function_exists( 'algq_send_mail' ) ) {
            $sent = (bool) algq_send_mail(
                array(
                    'to'           => $email,
                    'subject'      => $subject,
                    'message'      => $message,
                    'headers'      => array( 'Content-Type: text/plain; charset=UTF-8' ),
                    'attachments'  => array( $path ),
                    'module'       => 'pdf-signature',
                    'event'        => 'pdf_company_copy',
                    'related_id'   => $document_id,
                    'company_copy' => true,
                )
            );
        } else {
            $sent = (bool) wp_mail(
                $email,
                $subject,
                $message,
                array( 'Content-Type: text/plain; charset=UTF-8' ),
                array( $path )
            );
        }

        self::audit(
            $sent ? 'pdf.company_email_sent' : 'pdf.company_email_failed',
            array(
                'document_id' => $document_id,
                'recipient_domain' => self::email_domain( $email ),
            )
        );

        return $sent;
    }

    private static function media_library_enabled(): bool {
        $enabled = get_option( 'algq_pdf_register_media_library', 'yes' );
        return (bool) apply_filters( 'algq_pdf_register_media_library', 'yes' === $enabled );
    }

    private static function email_delivery_enabled(): bool {
        $enabled = get_option( 'algq_pdf_company_email_enabled', 'yes' );
        return (bool) apply_filters( 'algq_pdf_company_email_enabled', 'yes' === $enabled );
    }

    private static function company_email(): string {
        if ( function_exists( 'algq_company_notification_email' ) ) {
            return algq_company_notification_email();
        }

        $email = sanitize_email(
            (string) apply_filters(
                'algq_company_notification_email',
                get_option( 'algq_company_notification_email', self::COMPANY_EMAIL )
            )
        );

        return is_email( $email ) ? $email : self::COMPANY_EMAIL;
    }

    private static function document_record( int $document_id ): ?object {
        global $wpdb;
        $table = $wpdb->prefix . 'algq_pdf_documents';
        $record = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d AND deleted_at IS NULL LIMIT 1", $document_id ) );
        return is_object( $record ) ? $record : null;
    }

    private static function document_path( string $file_name ): string {
        $uploads = wp_upload_dir( null, false );
        $default = trailingslashit( (string) $uploads['basedir'] ) . 'algq-private/pdf-signature';
        $dir = untrailingslashit( (string) apply_filters( 'algq_pdf_signature_storage_directory', $default ) );
        return trailingslashit( $dir ) . basename( $file_name );
    }

    /** @param array<string,mixed> $context */
    private static function audit( string $event, array $context ): void {
        if ( function_exists( 'algq_log_event' ) ) {
            algq_log_event( $event, array_merge( array( 'plugin' => 'algq-pdf-signature' ), $context ) );
            return;
        }

        do_action( 'algq_audit_event', $event, array_merge( array( 'plugin' => 'algq-pdf-signature' ), $context ) );
    }

    private static function email_domain( string $email ): string {
        $parts = explode( '@', $email );
        return 2 === count( $parts ) ? sanitize_text_field( $parts[1] ) : '';
    }
}
