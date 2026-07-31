<?php
/**
 * Data provider for dashboard metrics.
 *
 * @package Algonquian_Command_Center
 */

defined( 'ABSPATH' ) || exit;

final class ALGQ_Command_Center_Data_Provider {
    public static function metrics(): array {
        $metrics = array(
            'total_deals'       => self::count_posts( array( 'algq_deal', 'algq_pipeline_deal' ) ),
            'leads_captured'    => self::count_posts( array( 'algq_lead', 'algq_intake_submission', 'algq_deal' ) ),
            'offers_sent'       => self::count_posts( array( 'algq_offer' ) ),
            'contracts_pending' => self::count_posts_by_meta( array( 'algq_deal', 'algq_pipeline_deal' ), 'algq_status', 'under_contract' ),
            'buyers_registered' => self::count_users_by_role( 'algq_buyer' ),
            'funding_status'    => self::funding_summary(),
            'pipeline_value'    => self::pipeline_value(),
            'recent_documents'  => self::count_posts( array( 'algq_document', 'algq_signature_document' ) ),
            'system_health'     => class_exists( 'ALGQ_Command_Center_Health_Monitor' ) ? ALGQ_Command_Center_Health_Monitor::summary() : array(),
        );

        return apply_filters( 'algq_command_center_metrics', $metrics );
    }

    public static function activity(): array {
        $activity = apply_filters( 'algq_command_center_activity', array() );

        if ( ! empty( $activity ) ) {
            return array_values( $activity );
        }

        return array(
            array( 'type' => 'Deal', 'message' => 'Recent acquisitions and pipeline updates appear here when connected plugins publish activity events.', 'time' => 'Live' ),
            array( 'type' => 'Funding', 'message' => 'Funding commitments and lender updates are summarized from the Funding Tracker.', 'time' => 'Live' ),
            array( 'type' => 'Documents', 'message' => 'Generated offers, agreements, PDFs, and signature activity are surfaced through platform integrations.', 'time' => 'Live' ),
        );
    }

    public static function pipeline_stages(): array {
        $stages = apply_filters(
            'algq_command_center_pipeline_stages',
            array(
                'Lead Captured'  => 'lead_captured',
                'Underwriting'   => 'underwriting',
                'Offer Sent'     => 'offer_sent',
                'Under Contract' => 'under_contract',
                'Buyer Assigned' => 'buyer_assigned',
                'Closed'         => 'closed',
            )
        );

        $output = array();
        foreach ( $stages as $label => $key ) {
            $output[] = array(
                'label' => sanitize_text_field( (string) $label ),
                'key'   => sanitize_key( (string) $key ),
                'count' => self::count_posts_by_meta( array( 'algq_deal', 'algq_pipeline_deal' ), 'algq_stage', (string) $key ),
            );
        }

        return $output;
    }

    private static function count_posts( array $post_types ): int {
        $count = 0;
        foreach ( array_unique( array_map( 'sanitize_key', $post_types ) ) as $post_type ) {
            if ( ! post_type_exists( $post_type ) ) {
                continue;
            }
            $object = wp_count_posts( $post_type );
            if ( $object ) {
                foreach ( array( 'publish', 'private' ) as $status ) {
                    $count += isset( $object->{$status} ) ? (int) $object->{$status} : 0;
                }
            }
        }
        return $count;
    }

    private static function count_posts_by_meta( array $post_types, string $meta_key, string $meta_value ): int {
        $available = array_values( array_filter( array_map( 'sanitize_key', $post_types ), 'post_type_exists' ) );
        if ( empty( $available ) ) {
            return 0;
        }

        $query = new WP_Query(
            array(
                'post_type'              => $available,
                'post_status'            => array( 'publish', 'private' ),
                'posts_per_page'         => 1,
                'fields'                 => 'ids',
                'no_found_rows'          => false,
                'update_post_meta_cache' => false,
                'update_post_term_cache' => false,
                'meta_query'             => array(
                    array(
                        'key'     => sanitize_key( $meta_key ),
                        'value'   => sanitize_text_field( $meta_value ),
                        'compare' => '=',
                    ),
                ),
            )
        );

        return (int) $query->found_posts;
    }

    private static function count_users_by_role( string $role ): int {
        $users = count_users();
        return isset( $users['avail_roles'][ $role ] ) ? (int) $users['avail_roles'][ $role ] : 0;
    }

    private static function funding_summary(): array {
        $committed = max( 0, (float) get_option( 'algq_command_center_funding_committed', 0 ) );
        $needed    = max( 0, (float) get_option( 'algq_command_center_funding_needed', 0 ) );

        return array(
            'committed' => $committed,
            'needed'    => $needed,
            'gap'       => max( 0, $needed - $committed ),
            'percent'   => $needed > 0 ? min( 100, round( ( $committed / $needed ) * 100 ) ) : 0,
        );
    }

    private static function pipeline_value(): float {
        return max( 0, (float) get_option( 'algq_command_center_pipeline_value', 0 ) );
    }
}
