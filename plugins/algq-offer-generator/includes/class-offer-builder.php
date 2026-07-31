<?php

defined( 'ABSPATH' ) || exit;

final class ALGQ_Offer_Builder {
    public static function init(): void {
        add_action( 'wp_ajax_algq_save_offer_terms', array( __CLASS__, 'save_offer_terms' ) );
    }

    public static function save_offer_terms(): void {
        check_ajax_referer( 'algq_offer_generator', 'nonce' );
        $offer_id = isset( $_POST['offer_id'] ) ? absint( $_POST['offer_id'] ) : 0;
        if ( ! $offer_id || 'algq_offer' !== get_post_type( $offer_id ) ) {
            wp_send_json_error( array( 'message' => __( 'Invalid offer.', 'algq-offer-generator' ) ), 400 );
        }

        $result = ALGQ_Offer_Service::update( $offer_id, wp_unslash( $_POST ), get_current_user_id() );
        if ( is_wp_error( $result ) ) {
            $data   = $result->get_error_data();
            $status = is_array( $data ) && isset( $data['status'] ) ? absint( $data['status'] ) : 400;
            wp_send_json_error( array( 'message' => $result->get_error_message() ), $status );
        }

        wp_send_json_success(
            array(
                'message' => __( 'Offer terms saved and versioned.', 'algq-offer-generator' ),
                'offerId' => $offer_id,
                'version' => absint( get_post_meta( $offer_id, '_algq_offer_version_number', true ) ),
            )
        );
    }
}
