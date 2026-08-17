<?php
/**
 * Front-end and protected operational shortcodes.
 *
 * @package Algonquian_Command_Center
 */

defined( 'ABSPATH' ) || exit;

final class ALGQ_Command_Center_Shortcodes {
    public function register(): void {
        add_shortcode( 'algq_command_center', array( $this, 'dashboard' ) );
        add_shortcode( 'algq_admin_dashboard', array( $this, 'dashboard' ) );
        add_shortcode( 'algq_command_center_overview', array( $this, 'overview' ) );
        add_shortcode( 'algq_command_center_start', array( $this, 'getting_started' ) );
        add_shortcode( 'algq_command_center_docs', array( $this, 'documentation' ) );
        add_shortcode( 'algq_command_center_kpis', array( $this, 'kpis' ) );
        add_shortcode( 'algq_command_center_pipeline', array( $this, 'pipeline' ) );
        add_shortcode( 'algq_command_center_activity', array( $this, 'activity' ) );
        add_shortcode( 'algq_command_center_health', array( $this, 'health' ) );
    }

    public function dashboard(): string {
        if ( ! ALGQ_Command_Center_Security::can_view() ) {
            return $this->access_notice();
        }
        return $this->render_template( 'dashboard-shortcode.php' );
    }

    public function overview(): string {
        return $this->render_template( 'plugin-overview.php' );
    }

    public function getting_started(): string {
        return $this->render_template( 'getting-started.php' );
    }

    public function documentation(): string {
        return $this->render_template( 'documentation.php' );
    }

    public function kpis(): string {
        if ( ! ALGQ_Command_Center_Security::can_view() ) {
            return $this->access_notice();
        }
        ob_start();
        ALGQ_Command_Center_Widgets::render_kpi_cards();
        return (string) ob_get_clean();
    }

    public function pipeline(): string {
        if ( ! ALGQ_Command_Center_Security::can_view() ) {
            return $this->access_notice();
        }
        ob_start();
        ALGQ_Command_Center_Widgets::render_pipeline();
        return (string) ob_get_clean();
    }

    public function activity(): string {
        if ( ! ALGQ_Command_Center_Security::can_view() ) {
            return $this->access_notice();
        }
        ob_start();
        ALGQ_Command_Center_Widgets::render_activity_feed();
        return (string) ob_get_clean();
    }

    public function health(): string {
        if ( ! ALGQ_Command_Center_Security::can_view() ) {
            return $this->access_notice();
        }
        ob_start();
        ALGQ_Command_Center_Widgets::render_health();
        return (string) ob_get_clean();
    }

    private function render_template( string $template ): string {
        $file = ALGQ_COMMAND_CENTER_DIR . 'templates/' . sanitize_file_name( $template );
        if ( ! file_exists( $file ) ) {
            return '';
        }
        ob_start();
        include $file;
        return (string) ob_get_clean();
    }

    private function access_notice(): string {
        if ( ! is_user_logged_in() ) {
            return '<div class="algq-command-center algq-notice">' . esc_html__( 'Please log in to access the Command Center.', 'algq-command-center' ) . '</div>';
        }
        return '<div class="algq-command-center algq-notice">' . esc_html__( 'Your account is not authorized to access the Command Center.', 'algq-command-center' ) . '</div>';
    }
}
