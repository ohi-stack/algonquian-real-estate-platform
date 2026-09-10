<?php
/**
 * Asset loader.
 *
 * @package Algonquian_Command_Center
 */

defined( 'ABSPATH' ) || exit;

final class ALGQ_Command_Center_Assets {
    public function register(): void {
        add_action( 'admin_enqueue_scripts', array( $this, 'admin_assets' ) );
        add_action( 'wp_enqueue_scripts', array( $this, 'public_assets' ) );
    }

    public function admin_assets( string $hook ): void {
        if ( false === strpos( $hook, 'algq-command-center' ) ) {
            return;
        }
        $this->enqueue();
    }

    public function public_assets(): void {
        if ( ! is_singular() ) {
            return;
        }

        global $post;
        if ( ! $post instanceof WP_Post ) {
            return;
        }

        $shortcodes = array(
            'algq_command_center',
            'algq_admin_dashboard',
            'algq_command_center_kpis',
            'algq_command_center_pipeline',
            'algq_command_center_activity',
            'algq_command_center_health',
            'algq_command_center_overview',
            'algq_command_center_start',
            'algq_command_center_docs',
        );

        foreach ( $shortcodes as $shortcode ) {
            if ( has_shortcode( $post->post_content, $shortcode ) ) {
                $this->enqueue();
                return;
            }
        }
    }

    private function enqueue(): void {
        $shared_css = dirname( ALGQ_COMMAND_CENTER_DIR, 2 ) . '/shared-ui/are-admin-ui.css';
        $shared_js  = dirname( ALGQ_COMMAND_CENTER_DIR, 2 ) . '/shared-ui/are-admin-ui.js';
        $shared_url = plugins_url( '../../shared-ui/', ALGQ_COMMAND_CENTER_FILE );

        if ( file_exists( $shared_css ) ) {
            wp_enqueue_style( 'algq-are-admin-ui', $shared_url . 'are-admin-ui.css', array(), ALGQ_COMMAND_CENTER_VERSION );
        }
        wp_enqueue_style( 'algq-command-center', ALGQ_COMMAND_CENTER_URL . 'assets/css/command-center.css', array( 'algq-are-admin-ui' ), ALGQ_COMMAND_CENTER_VERSION );

        if ( file_exists( $shared_js ) ) {
            wp_enqueue_script( 'algq-are-admin-ui', $shared_url . 'are-admin-ui.js', array(), ALGQ_COMMAND_CENTER_VERSION, true );
        }
        wp_enqueue_script( 'algq-command-center', ALGQ_COMMAND_CENTER_URL . 'assets/js/command-center.js', array(), ALGQ_COMMAND_CENTER_VERSION, true );
        wp_localize_script(
            'algq-command-center',
            'ALGQCommandCenter',
            array(
                'refreshInterval' => max( 30, absint( get_option( 'algq_command_center_refresh_interval', 300 ) ) ),
            )
        );
    }
}
