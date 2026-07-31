<?php

defined( 'ABSPATH' ) || exit;

final class ALGQ_Pipeline_Migrator {
    public static function run_legacy_import(): int {
        if ( get_option( 'algq_pipeline_legacy_cpt_migrated' ) ) {
            return 0;
        }
        $posts = get_posts(
            array(
                'post_type' => 'algq_deal',
                'post_status' => array( 'publish', 'private', 'draft', 'pending' ),
                'numberposts' => -1,
                'orderby' => 'ID',
                'order' => 'ASC',
            )
        );
        $imported = 0;
        foreach ( $posts as $post ) {
            $result = ALGQ_Pipeline_Service::instance()->create_deal(
                array(
                    'title' => $post->post_title,
                    'property_address' => (string) get_post_meta( $post->ID, 'property_address', true ),
                    'primary_contact' => (string) get_post_meta( $post->ID, 'seller_name', true ),
                    'assigned_user_id' => (int) $post->post_author,
                    'stage' => ALGQ_Pipeline_Stages::normalize( (string) get_post_meta( $post->ID, 'pipeline_stage', true ) ?: 'new_intake' ),
                    'priority' => strtolower( (string) get_post_meta( $post->ID, 'deal_priority', true ) ?: 'normal' ),
                    'asking_price' => (float) get_post_meta( $post->ID, 'deal_value', true ),
                    'source' => 'Legacy Pipeline CRM',
                    'source_system' => 'legacy_cpt',
                    'source_record_id' => (string) $post->ID,
                )
            );
            if ( ! is_wp_error( $result ) ) {
                ++$imported;
            }
        }
        update_option( 'algq_pipeline_legacy_cpt_migrated', array( 'completed_at' => current_time( 'mysql', true ), 'imported' => $imported ), false );
        return $imported;
    }
}
