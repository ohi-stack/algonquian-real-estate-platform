<?php

defined( 'ABSPATH' ) || exit;

final class ALGQ_Offer_Deal_Integration {
    public static function init(): void {
        add_action( 'save_post_algq_offer', array( __CLASS__, 'sync_offer_to_deal' ), 20, 3 );
        add_filter( 'algq_pipeline_deal_payload', array( __CLASS__, 'add_offer_summary' ), 20, 2 );
    }

    public static function sync_offer_to_deal( $post_id, $post, $update ): void {
        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) { return; }
        $deal_id = absint( get_post_meta( $post_id, '_algq_offer_deal_id', true ) );
        if ( ! $deal_id ) { return; }

        update_post_meta( $deal_id, '_algq_latest_offer_id', $post_id );
        update_post_meta( $deal_id, '_algq_latest_offer_status', get_post_meta( $post_id, '_algq_offer_offer_status', true ) ?: get_post_status( $post_id ) );
        update_post_meta( $deal_id, '_algq_latest_offer_strategy', get_post_meta( $post_id, '_algq_offer_strategy', true ) );
        update_post_meta( $deal_id, '_algq_latest_offer_proposal_type', get_post_meta( $post_id, '_algq_offer_proposal_type', true ) );
        do_action( 'algq_offer_synced_to_deal', $post_id, $deal_id );
    }

    public static function add_offer_summary( $payload, $deal_id ) {
        $payload = is_array( $payload ) ? $payload : array();
        $offer_id = absint( get_post_meta( absint( $deal_id ), '_algq_latest_offer_id', true ) );
        if ( $offer_id && 'algq_offer' === get_post_type( $offer_id ) ) {
            $offer = ALGQ_Offer_Service::get( $offer_id );
            $payload['latest_offer'] = array(
                'id' => $offer_id,
                'offer_number' => $offer['offer_number'],
                'strategy' => $offer['strategy'],
                'proposal_type' => $offer['proposal_type'],
                'status' => $offer['offer_status'],
                'purchase_price' => (float) $offer['purchase_price'],
                'underwriting_id' => absint( $offer['underwriting_id'] ),
            );
        }
        return $payload;
    }

    public static function get_deal_summary( $deal_id ): array {
        $deal_id = absint( $deal_id );
        if ( ! $deal_id ) { return array(); }
        if ( function_exists( 'algq_get_deal' ) ) {
            $deal = algq_get_deal( $deal_id );
            if ( is_array( $deal ) ) { return $deal; }
        }
        return array(
            'id' => $deal_id,
            'title' => get_the_title( $deal_id ),
            'address' => get_post_meta( $deal_id, '_algq_property_address', true ),
            'seller' => get_post_meta( $deal_id, '_algq_seller_name', true ),
            'asking_price' => get_post_meta( $deal_id, '_algq_asking_price', true ),
        );
    }
}
