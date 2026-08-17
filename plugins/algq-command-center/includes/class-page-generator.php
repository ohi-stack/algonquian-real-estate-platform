<?php
/**
 * Idempotent page generator.
 *
 * @package Algonquian_Command_Center
 */

defined( 'ABSPATH' ) || exit;

final class ALGQ_Command_Center_Page_Generator {
    public static function pages(): array {
        return array(
            'algq_command_center_dashboard_page_id' => array( 'title' => 'Command Center', 'slug' => 'command-center', 'shortcode' => '[algq_command_center]' ),
            'algq_command_center_overview_page_id' => array( 'title' => 'Algonquian Admin Command Center', 'slug' => 'plugin-command-center', 'shortcode' => '[algq_command_center_overview]' ),
            'algq_command_center_start_page_id' => array( 'title' => 'Command Center Getting Started', 'slug' => 'plugin-command-center-start', 'shortcode' => '[algq_command_center_start]' ),
            'algq_command_center_docs_page_id' => array( 'title' => 'Command Center Documentation', 'slug' => 'plugin-command-center-docs', 'shortcode' => '[algq_command_center_docs]' ),
        );
    }

    public static function create_required_pages(): array {
        $created = array();
        foreach ( self::pages() as $option_key => $page ) {
            $created[ $option_key ] = self::create_if_missing( $option_key, $page );
        }
        flush_rewrite_rules( false );
        return $created;
    }

    private static function create_if_missing( string $option_key, array $page ): int {
        $expected_slug = sanitize_title( (string) $page['slug'] );
        $existing_id   = absint( get_option( $option_key ) );
        if ( $existing_id && get_post( $existing_id ) && 'trash' !== get_post_status( $existing_id ) ) {
            $existing_post = get_post( $existing_id );
            if ( $existing_post instanceof WP_Post && $expected_slug === $existing_post->post_name ) {
                return $existing_id;
            }
            // Preserve legacy/admin-edited pages, but continue so the canonical 1.2.0 route can be created.
        }

        $existing = get_page_by_path( $expected_slug );
        if ( $existing instanceof WP_Post ) {
            update_option( $option_key, (int) $existing->ID );
            return (int) $existing->ID;
        }

        $content = "[vc_row][vc_column][vc_column_text]\n" . (string) $page['shortcode'] . "\n[/vc_column_text][/vc_column][/vc_row]";
        $page_id = wp_insert_post(
            array(
                'post_title' => sanitize_text_field( (string) $page['title'] ),
                'post_name' => $expected_slug,
                'post_content' => $content,
                'post_status' => 'publish',
                'post_type' => 'page',
            ),
            true
        );

        if ( is_wp_error( $page_id ) ) {
            return 0;
        }
        update_option( $option_key, (int) $page_id );
        return (int) $page_id;
    }
}
