<?php
/**
 * WordPress administration interface.
 *
 * @package Algonquian_Command_Center
 */

defined( 'ABSPATH' ) || exit;

final class ALGQ_Command_Center_Admin {
    public function register(): void {
        add_action( 'admin_menu', array( $this, 'menu' ) );
        add_action( 'admin_init', array( $this, 'register_settings' ) );
        add_action( 'admin_post_algq_command_center_save_settings', array( $this, 'save_settings' ) );
    }

    public function menu(): void {
        add_menu_page( __( 'Algonquian Admin Command Center', 'algq-command-center' ), __( 'Command Center', 'algq-command-center' ), ALGQ_Command_Center_Security::CAP_VIEW, 'algq-command-center', array( $this, 'render_dashboard' ), 'dashicons-chart-area', 3 );
        $items = array(
            'Dashboard' => 'algq-command-center',
            'Deals' => 'algq-command-center-deals',
            'Pipeline' => 'algq-command-center-pipeline',
            'Underwriting' => 'algq-command-center-underwriting',
            'Offers' => 'algq-command-center-offers',
            'Funding' => 'algq-command-center-funding',
            'Buyers' => 'algq-command-center-buyers',
            'Documents' => 'algq-command-center-documents',
            'Automation' => 'algq-command-center-automation',
            'Activity & Audit' => 'algq-command-center-audit',
            'Reports' => 'algq-command-center-reports',
            'Plugins' => 'algq-command-center-plugins',
            'System Health' => 'algq-command-center-system-health',
            'Settings' => 'algq-command-center-settings',
        );
        foreach ( $items as $label => $slug ) {
            $cap = 'Activity & Audit' === $label ? ALGQ_Command_Center_Security::CAP_AUDIT : ALGQ_Command_Center_Security::CAP_VIEW;
            add_submenu_page( 'algq-command-center', esc_html( $label ), esc_html( $label ), $cap, $slug, array( $this, 'render_router' ) );
        }
    }

    public function register_settings(): void {
        register_setting( 'algq_command_center_settings', 'algq_command_center_enabled_widgets', array( 'type' => 'array', 'sanitize_callback' => array( $this, 'sanitize_widgets' ), 'default' => array_keys( ALGQ_Command_Center_Widgets::registry() ) ) );
        register_setting( 'algq_command_center_settings', 'algq_command_center_refresh_interval', array( 'type' => 'integer', 'sanitize_callback' => array( $this, 'sanitize_refresh_interval' ), 'default' => 300 ) );
        register_setting( 'algq_command_center_settings', 'algq_command_center_pipeline_value', array( 'sanitize_callback' => 'floatval' ) );
        register_setting( 'algq_command_center_settings', 'algq_command_center_funding_committed', array( 'sanitize_callback' => 'floatval' ) );
        register_setting( 'algq_command_center_settings', 'algq_command_center_funding_needed', array( 'sanitize_callback' => 'floatval' ) );
    }

    public function sanitize_widgets( mixed $widgets ): array {
        return array_values( array_intersect( array_map( 'sanitize_key', (array) $widgets ), array_keys( ALGQ_Command_Center_Widgets::registry() ) ) );
    }

    public function sanitize_refresh_interval( mixed $value ): int {
        return max( 30, min( DAY_IN_SECONDS, absint( $value ) ) );
    }

    public function save_settings(): void {
        ALGQ_Command_Center_Security::require_manage();
        check_admin_referer( ALGQ_Command_Center_Security::NONCE_ACTION, ALGQ_Command_Center_Security::NONCE_NAME );
        update_option( 'algq_command_center_enabled_widgets', $this->sanitize_widgets( isset( $_POST['algq_command_center_enabled_widgets'] ) ? wp_unslash( $_POST['algq_command_center_enabled_widgets'] ) : array() ) );
        update_option( 'algq_command_center_refresh_interval', $this->sanitize_refresh_interval( $_POST['algq_command_center_refresh_interval'] ?? 300 ) );
        update_option( 'algq_command_center_pipeline_value', max( 0, (float) ( $_POST['algq_command_center_pipeline_value'] ?? 0 ) ) );
        update_option( 'algq_command_center_funding_committed', max( 0, (float) ( $_POST['algq_command_center_funding_committed'] ?? 0 ) ) );
        update_option( 'algq_command_center_funding_needed', max( 0, (float) ( $_POST['algq_command_center_funding_needed'] ?? 0 ) ) );
        wp_safe_redirect( add_query_arg( array( 'page' => 'algq-command-center-settings', 'updated' => 1 ), admin_url( 'admin.php' ) ) );
        exit;
    }

    public function render_dashboard(): void {
        ALGQ_Command_Center_Security::require_view();
        include ALGQ_COMMAND_CENTER_DIR . 'templates/admin-dashboard.php';
    }

    public function render_router(): void {
        ALGQ_Command_Center_Security::require_view();
        $page = isset( $_GET['page'] ) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : 'algq-command-center';
        switch ( $page ) {
            case 'algq-command-center-settings':
                ALGQ_Command_Center_Security::require_manage();
                $registry = ALGQ_Command_Center_Widgets::registry();
                $enabled = ALGQ_Command_Center_Widgets::enabled_widgets();
                include ALGQ_COMMAND_CENTER_DIR . 'templates/settings.php';
                break;
            case 'algq-command-center-system-health':
                include ALGQ_COMMAND_CENTER_DIR . 'templates/system-health.php';
                break;
            case 'algq-command-center-plugins':
                include ALGQ_COMMAND_CENTER_DIR . 'templates/plugin-library.php';
                break;
            case 'algq-command-center-audit':
                if ( ! ALGQ_Command_Center_Security::can_view_audit() ) {
                    wp_die( esc_html__( 'You are not authorized to view audit activity.', 'algq-command-center' ), '', array( 'response' => 403 ) );
                }
                $section = 'audit';
                include ALGQ_COMMAND_CENTER_DIR . 'templates/section.php';
                break;
            default:
                $section = str_replace( 'algq-command-center-', '', $page );
                $section = 'algq-command-center' === $page ? 'dashboard' : $section;
                include ALGQ_COMMAND_CENTER_DIR . 'templates/section.php';
        }
    }
}
