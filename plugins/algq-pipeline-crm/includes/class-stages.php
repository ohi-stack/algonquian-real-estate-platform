<?php

defined( 'ABSPATH' ) || exit;

final class ALGQ_Pipeline_Stages {
    public static function all(): array {
        return apply_filters(
            'algq_pipeline_stages',
            array(
                'new_intake'          => 'New Intake',
                'contact_attempted'   => 'Contact Attempted',
                'contact_established' => 'Contact Established',
                'preliminary_review'  => 'Preliminary Review',
                'underwriting'        => 'Underwriting',
                'offer_preparation'   => 'Offer Preparation',
                'offer_sent'          => 'Offer Sent',
                'negotiation'         => 'Negotiation',
                'under_contract'      => 'Under Contract',
                'due_diligence'       => 'Due Diligence',
                'funding'             => 'Funding',
                'buyer_distribution'  => 'Buyer Distribution',
                'closing_scheduled'   => 'Closing Scheduled',
                'closed'              => 'Closed',
                'lost'                => 'Lost',
                'withdrawn'           => 'Withdrawn',
                'archived'            => 'Archived',
            )
        );
    }

    public static function normalize( string $stage ): string {
        $stage = sanitize_key( $stage );
        $legacy = array(
            'lead_captured'       => 'new_intake',
            'buyer_assigned'      => 'buyer_distribution',
            'offer_ready'         => 'offer_preparation',
            'underwriting_needed' => 'underwriting',
            'underwriting_review' => 'underwriting',
        );

        return $legacy[ $stage ] ?? $stage;
    }

    public static function is_valid( string $stage ): bool {
        return isset( self::all()[ self::normalize( $stage ) ] );
    }

    public static function label( string $stage ): string {
        $stage = self::normalize( $stage );
        return self::all()[ $stage ] ?? ucwords( str_replace( '_', ' ', $stage ) );
    }

    public static function allowed_from( string $current ): array {
        $current = self::normalize( $current );
        $map = array(
            'new_intake'          => array( 'contact_attempted', 'contact_established', 'preliminary_review', 'withdrawn', 'lost' ),
            'contact_attempted'   => array( 'contact_established', 'preliminary_review', 'lost', 'withdrawn' ),
            'contact_established' => array( 'preliminary_review', 'underwriting', 'lost', 'withdrawn' ),
            'preliminary_review'  => array( 'underwriting', 'contact_attempted', 'lost', 'withdrawn' ),
            'underwriting'        => array( 'offer_preparation', 'preliminary_review', 'lost', 'withdrawn' ),
            'offer_preparation'   => array( 'offer_sent', 'underwriting', 'lost', 'withdrawn' ),
            'offer_sent'          => array( 'negotiation', 'under_contract', 'offer_preparation', 'lost', 'withdrawn' ),
            'negotiation'         => array( 'offer_preparation', 'offer_sent', 'under_contract', 'lost', 'withdrawn' ),
            'under_contract'      => array( 'due_diligence', 'funding', 'closing_scheduled', 'lost', 'withdrawn' ),
            'due_diligence'       => array( 'funding', 'buyer_distribution', 'closing_scheduled', 'lost', 'withdrawn' ),
            'funding'             => array( 'buyer_distribution', 'closing_scheduled', 'due_diligence', 'lost', 'withdrawn' ),
            'buyer_distribution'  => array( 'funding', 'closing_scheduled', 'due_diligence', 'lost', 'withdrawn' ),
            'closing_scheduled'   => array( 'closed', 'funding', 'due_diligence', 'lost', 'withdrawn' ),
            'closed'              => array( 'archived' ),
            'lost'                => array( 'archived', 'preliminary_review' ),
            'withdrawn'           => array( 'archived', 'contact_attempted' ),
            'archived'            => array(),
        );

        return apply_filters( 'algq_pipeline_allowed_transitions', $map[ $current ] ?? array(), $current );
    }
}
