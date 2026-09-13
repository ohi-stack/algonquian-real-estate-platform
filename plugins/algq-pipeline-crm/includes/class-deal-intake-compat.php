<?php

defined( 'ABSPATH' ) || exit;

final class ALGQ_Pipeline_Deal_Intake_Compat {
    public static function boot(): void {
        add_filter( 'algq_pipeline_create_deal', array( __CLASS__, 'handoff' ), 5, 3 );
    }

    public static function handoff( $deal_id, $payload, $submission_id ) {
        if ( ! is_array( $payload ) || ! isset( $payload['property'] ) ) {
            return $deal_id;
        }
        $seller = is_array( $payload['seller'] ?? null ) ? $payload['seller'] : array();
        $property = is_array( $payload['property'] ?? null ) ? $payload['property'] : array();
        $source_id = sanitize_text_field( (string) ( $payload['intake_uuid'] ?? $submission_id ) );
        $existing = ALGQ_Pipeline_Service::instance()->repository()->find_by_source( 'algq-deal-intake', $source_id );
        if ( $existing ) {
            return (int) $existing['id'];
        }
        $created = ALGQ_Pipeline_Service::instance()->create_deal( array(
            'title' => sanitize_text_field( (string) ( $property['address'] ?? '' ) ),
            'property_address' => sanitize_text_field( (string) ( $property['address'] ?? '' ) ),
            'primary_contact' => sanitize_text_field( (string) ( $seller['name'] ?? '' ) ),
            'stage' => ALGQ_Pipeline_Stages::normalize( sanitize_key( (string) ( $payload['initial_stage'] ?? 'new_intake' ) ) ),
            'source' => sanitize_text_field( (string) ( $payload['lead_source'] ?? 'Deal Intake' ) ),
            'source_system' => 'algq-deal-intake',
            'source_record_id' => $source_id,
            'asking_price' => $payload['asking_price'] ?? 0,
        ) );
        return is_wp_error( $created ) ? 0 : (int) ( $created['id'] ?? 0 );
    }
}
