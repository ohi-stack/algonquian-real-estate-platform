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
        add_shortcode( 'algq_command_center_brief', array( $this, 'brief' ) );
        add_shortcode( 'algq_command_center_decisions', array( $this, 'decisions' ) );
        add_shortcode( 'algq_command_center_risk', array( $this, 'risk' ) );
        add_shortcode( 'algq_command_center_deadlines', array( $this, 'deadlines' ) );
        add_shortcode( 'algq_command_center_revenue', array( $this, 'revenue' ) );
        add_shortcode( 'algq_command_center_agents', array( $this, 'agents' ) );
        add_shortcode( 'algq_command_center_approvals', array( $this, 'approvals' ) );
        add_shortcode( 'algq_command_center_capital', array( $this, 'capital' ) );
    }

    public function dashboard(): string {
        return $this->protected_template( 'dashboard-shortcode.php' );
    }

    public function overview(): string { return $this->render_template( 'plugin-overview.php' ); }
    public function getting_started(): string { return $this->render_template( 'getting-started.php' ); }
    public function documentation(): string { return $this->render_template( 'documentation.php' ); }

    public function kpis(): string { return $this->protected_widget( 'render_kpi_cards' ); }
    public function pipeline(): string { return $this->protected_widget( 'render_pipeline' ); }
    public function activity(): string { return $this->protected_widget( 'render_activity_feed' ); }
    public function health(): string { return $this->protected_widget( 'render_health' ); }
    public function brief(): string { return $this->protected_widget( 'render_executive_brief' ); }
    public function decisions(): string { return $this->protected_widget( 'render_decisions' ); }
    public function risk(): string { return $this->protected_widget( 'render_risk' ); }
    public function deadlines(): string { return $this->protected_widget( 'render_deadlines' ); }
    public function revenue(): string { return $this->protected_widget( 'render_revenue' ); }
    public function agents(): string { return $this->protected_widget( 'render_agents' ); }
    public function approvals(): string { return $this->protected_widget( 'render_approvals' ); }
    public function capital(): string { return $this->protected_widget( 'render_capital' ); }

    private function protected_widget( string $method ): string {
        if ( ! ALGQ_Command_Center_Security::can_view() ) {
            return $this->access_notice();
        }
        if ( ! is_callable( array( 'ALGQ_Command_Center_Widgets', $method ) ) ) {
            return '';
        }
        ob_start();
        call_user_func( array( 'ALGQ_Command_Center_Widgets', $method ) );
        return (string) ob_get_clean();
    }

    private function protected_template( string $template ): string {
        if ( ! ALGQ_Command_Center_Security::can_view() ) {
            return $this->access_notice();
        }
        return $this->render_template( $template );
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
