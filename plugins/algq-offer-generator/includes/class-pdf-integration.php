<?php

defined( 'ABSPATH' ) || exit;

final class ALGQ_Offer_PDF_Integration {
    public static function init(): void {
        add_action( 'wp_ajax_algq_offer_request_pdf', array( __CLASS__, 'request_pdf' ) );
        add_action( 'wp_ajax_algq_offer_download_pdf', array( __CLASS__, 'request_pdf' ) );
    }

    public static function request_pdf(): void {
        check_ajax_referer( 'algq_offer_generator', 'nonce' );
        if ( ! current_user_can( 'generate_algq_offer_documents' ) ) {
            wp_send_json_error( array( 'message' => __( 'Permission denied.', 'algq-offer-generator' ) ), 403 );
        }

        $offer_id = absint( $_REQUEST['offer_id'] ?? 0 );
        if ( ! $offer_id || 'algq_offer' !== get_post_type( $offer_id ) ) {
            wp_send_json_error( array( 'message' => __( 'Invalid offer.', 'algq-offer-generator' ) ), 400 );
        }

        $html = get_post_meta( $offer_id, '_algq_offer_document_html', true );
        if ( ! $html ) {
            $html = ALGQ_Offer_Document_Generator::render_offer_html( $offer_id );
        }

        $result = apply_filters(
            'algq_pdf_signature_render_offer',
            array(),
            $offer_id,
            $html,
            array(
                'filename' => sanitize_file_name( (string) get_post_meta( $offer_id, '_algq_offer_offer_number', true ) . '.pdf' ),
                'private'  => true,
            )
        );

        do_action( 'algq_offer_pdf_requested', $offer_id, get_current_user_id(), $html );

        if ( empty( $result ) || ! is_array( $result ) ) {
            wp_send_json_error(
                array( 'message' => __( 'The PDF & Signature Engine did not return a rendered PDF. No HTML file was mislabeled as a PDF.', 'algq-offer-generator' ) ),
                501
            );
        }

        update_post_meta( $offer_id, '_algq_offer_pdf_reference', $result );
        wp_send_json_success( $result );
    }
}
