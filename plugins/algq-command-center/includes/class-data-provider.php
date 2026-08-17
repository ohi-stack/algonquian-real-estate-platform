<?php
/**
 * Read-only cross-plugin dashboard data provider.
 *
 * @package Algonquian_Command_Center
 */

defined( 'ABSPATH' ) || exit;

final class ALGQ_Command_Center_Data_Provider {
    public static function metrics(): array {
        $metrics = array(
            'total_deals' => self::count_posts( array( 'algq_deal', 'algq_pipeline_deal' ) ),
            'new_leads' => self::count_posts( array( 'algq_lead', 'algq_intake_submission', 'algq_intake' ) ),
            'active_deals' => self::count_posts_by_meta_any( array( 'algq_deal', 'algq_pipeline_deal' ), array( 'algq_stage', '_algq_stage', 'stage' ), array( 'lead_captured', 'underwriting', 'offer_sent', 'under_contract', 'buyer_assigned' ) ),
            'underwriting_queue' => self::count_posts_by_meta_any( array( 'algq_deal', 'algq_pipeline_deal', 'algq_underwriting' ), array( 'algq_stage', '_algq_stage', 'status' ), array( 'underwriting', 'pending', 'in_review' ) ),
            'offers_pending' => self::count_posts_by_meta_any( array( 'algq_offer' ), array( 'algq_status', '_algq_status', 'status' ), array( 'draft', 'pending', 'sent', 'delivered' ) ),
            'contracts_pending' => self::count_posts_by_meta_any( array( 'algq_deal', 'algq_pipeline_deal', 'algq_contract' ), array( 'algq_stage', 'algq_status', '_algq_status' ), array( 'under_contract', 'pending_signature', 'pending' ) ),
            'closings' => self::count_posts_by_meta_any( array( 'algq_deal', 'algq_pipeline_deal' ), array( 'algq_stage', 'algq_status' ), array( 'closed', 'closing' ) ),
            'archived_deals' => self::count_posts_by_meta_any( array( 'algq_deal', 'algq_pipeline_deal' ), array( 'algq_stage', 'algq_status' ), array( 'archived', 'lost', 'withdrawn' ) ),
            'buyers_registered' => self::count_users_by_role( 'algq_buyer' ),
            'approved_buyers' => self::count_users_by_meta( array( 'algq_buyer_status', '_algq_buyer_status' ), array( 'approved', 'active' ) ),
            'nda_activity' => self::count_posts( array( 'algq_nda_acceptance', 'algq_buyer_nda' ) ),
            'buyer_interest' => self::count_posts( array( 'algq_buyer_interest', 'algq_interest' ) ),
            'deal_access_activity' => self::count_posts( array( 'algq_deal_access', 'algq_download_log' ) ),
            'funding_status' => self::funding_summary(),
            'pipeline_value' => self::pipeline_value(),
            'documents_generated' => self::count_posts( array( 'algq_document', 'algq_generated_document', 'algq_signature_document' ) ),
            'signatures_pending' => self::count_posts_by_meta_any( array( 'algq_signature_request', 'algq_signature_document' ), array( 'algq_status', '_algq_status', 'status' ), array( 'pending', 'sent', 'viewed' ) ),
            'signatures_completed' => self::count_posts_by_meta_any( array( 'algq_signature_request', 'algq_signature_document' ), array( 'algq_status', '_algq_status', 'status' ), array( 'signed', 'completed' ) ),
            'automation_active' => self::count_posts_by_meta_any( array( 'algq_automation', 'algq_automation_rule' ), array( 'algq_status', '_algq_status', 'status' ), array( 'active', 'enabled' ) ),
            'automation_failed' => self::count_posts_by_meta_any( array( 'algq_automation_job', 'algq_automation_run' ), array( 'algq_status', '_algq_status', 'status' ), array( 'failed', 'error' ) ),
            'system_health' => ALGQ_Command_Center_Health_Monitor::summary(),
        );

        return apply_filters( 'algq_command_center_metrics', $metrics );
    }

    public static function activity(): array {
        $filtered = apply_filters( 'algq_command_center_activity', array() );
        if ( ! empty( $filtered ) ) {
            return array_values( $filtered );
        }

        $post_types = self::existing_post_types(
            array(
                'algq_deal',
                'algq_pipeline_deal',
                'algq_intake_submission',
                'algq_offer',
                'algq_document',
                'algq_signature_request',
                'algq_automation_job',
            )
        );

        if ( empty( $post_types ) ) {
            return array(
                array( 'type' => 'Platform', 'message' => __( 'Connected plugin activity will appear here as operational records are created.', 'algq-command-center' ), 'time' => __( 'Live', 'algq-command-center' ) ),
            );
        }

        $query = new WP_Query(
            array(
                'post_type' => $post_types,
                'post_status' => self::countable_statuses(),
                'posts_per_page' => 10,
                'orderby' => 'modified',
                'order' => 'DESC',
                'no_found_rows' => true,
                'update_post_meta_cache' => false,
                'update_post_term_cache' => false,
            )
        );

        $items = array();
        foreach ( $query->posts as $post ) {
            if ( ! $post instanceof WP_Post ) {
                continue;
            }
            $object = get_post_type_object( $post->post_type );
            $label = $object && isset( $object->labels->singular_name ) ? $object->labels->singular_name : $post->post_type;
            $items[] = array(
                'type' => sanitize_text_field( (string) $label ),
                'message' => sanitize_text_field( get_the_title( $post ) ?: sprintf( __( 'Record #%d updated', 'algq-command-center' ), $post->ID ) ),
                'time' => human_time_diff( strtotime( $post->post_modified_gmt . ' UTC' ), current_time( 'timestamp', true ) ) . ' ' . __( 'ago', 'algq-command-center' ),
            );
        }
        return $items;
    }

    public static function pipeline_stages(): array {
        $stages = apply_filters(
            'algq_command_center_pipeline_stages',
            array(
                'Lead Captured' => 'lead_captured',
                'Underwriting' => 'underwriting',
                'Offer Sent' => 'offer_sent',
                'Under Contract' => 'under_contract',
                'Buyer Assigned' => 'buyer_assigned',
                'Closed' => 'closed',
                'Archived' => 'archived',
            )
        );

        $output = array();
        foreach ( $stages as $label => $key ) {
            $output[] = array(
                'label' => sanitize_text_field( (string) $label ),
                'key' => sanitize_key( (string) $key ),
                'count' => self::count_posts_by_meta_any( array( 'algq_deal', 'algq_pipeline_deal' ), array( 'algq_stage', '_algq_stage', 'stage' ), array( (string) $key ) ),
            );
        }
        return $output;
    }

    public static function funding_summary(): array {
        $fallback = array(
            'committed' => max( 0, (float) get_option( 'algq_command_center_funding_committed', 0 ) ),
            'needed' => max( 0, (float) get_option( 'algq_command_center_funding_needed', 0 ) ),
        );
        $summary = apply_filters( 'algq_command_center_funding_summary', $fallback );
        $summary = is_array( $summary ) ? $summary : $fallback;
        $committed = max( 0, (float) ( $summary['committed'] ?? 0 ) );
        $needed = max( 0, (float) ( $summary['needed'] ?? 0 ) );
        return array(
            'committed' => $committed,
            'needed' => $needed,
            'gap' => max( 0, $needed - $committed ),
            'percent' => $needed > 0 ? min( 100, (int) round( ( $committed / $needed ) * 100 ) ) : 0,
        );
    }

    public static function pipeline_value(): float {
        return max( 0, (float) apply_filters( 'algq_command_center_pipeline_value', get_option( 'algq_command_center_pipeline_value', 0 ) ) );
    }

    private static function count_posts( array $post_types ): int {
        $count = 0;
        foreach ( self::existing_post_types( $post_types ) as $post_type ) {
            $counts = wp_count_posts( $post_type );
            if ( ! $counts ) {
                continue;
            }
            foreach ( self::countable_statuses() as $status ) {
                $count += isset( $counts->{$status} ) ? (int) $counts->{$status} : 0;
            }
        }
        return $count;
    }

    private static function count_posts_by_meta_any( array $post_types, array $meta_keys, array $meta_values ): int {
        $available = self::existing_post_types( $post_types );
        if ( empty( $available ) ) {
            return 0;
        }

        $clauses = array( 'relation' => 'OR' );
        foreach ( $meta_keys as $meta_key ) {
            foreach ( $meta_values as $meta_value ) {
                $clauses[] = array(
                    'key' => sanitize_key( (string) $meta_key ),
                    'value' => sanitize_text_field( (string) $meta_value ),
                    'compare' => '=',
                );
            }
        }

        $query = new WP_Query(
            array(
                'post_type' => $available,
                'post_status' => self::countable_statuses(),
                'posts_per_page' => 1,
                'fields' => 'ids',
                'no_found_rows' => false,
                'update_post_meta_cache' => false,
                'update_post_term_cache' => false,
                'meta_query' => $clauses,
            )
        );
        return (int) $query->found_posts;
    }

    private static function count_users_by_role( string $role ): int {
        $users = count_users();
        return isset( $users['avail_roles'][ $role ] ) ? (int) $users['avail_roles'][ $role ] : 0;
    }

    private static function count_users_by_meta( array $meta_keys, array $values ): int {
        $clauses = array( 'relation' => 'OR' );
        foreach ( $meta_keys as $key ) {
            foreach ( $values as $value ) {
                $clauses[] = array( 'key' => sanitize_key( (string) $key ), 'value' => sanitize_text_field( (string) $value ), 'compare' => '=' );
            }
        }
        $query = new WP_User_Query( array( 'number' => 1, 'count_total' => true, 'fields' => 'ID', 'meta_query' => $clauses ) );
        return (int) $query->get_total();
    }

    private static function existing_post_types( array $post_types ): array {
        return array_values( array_filter( array_unique( array_map( 'sanitize_key', $post_types ) ), 'post_type_exists' ) );
    }

    private static function countable_statuses(): array {
        $statuses = array_keys( get_post_stati() );
        return array_values( array_diff( $statuses, array( 'trash', 'auto-draft', 'inherit' ) ) );
    }
}
