<?php
/**
 * Cross-plugin health monitor.
 *
 * @package Algonquian_Command_Center
 */

defined( 'ABSPATH' ) || exit;

final class ALGQ_Command_Center_Health_Monitor {
    /**
     * Return normalized health checks for the protected platform stack.
     *
     * @return array<string,array<string,mixed>>
     */
    public static function checks(): array {
        global $wpdb;

        $checks = array(
            'wordpress' => array(
                'label'   => __( 'WordPress', 'algq-command-center' ),
                'status'  => version_compare( get_bloginfo( 'version' ), '6.8', '>=' ) ? 'operational' : 'warning',
                'message' => sprintf( __( 'Version %s', 'algq-command-center' ), get_bloginfo( 'version' ) ),
            ),
            'php' => array(
                'label'   => __( 'PHP', 'algq-command-center' ),
                'status'  => version_compare( PHP_VERSION, '8.2', '>=' ) ? 'operational' : 'warning',
                'message' => sprintf( __( 'Version %s', 'algq-command-center' ), PHP_VERSION ),
            ),
            'database' => array(
                'label'   => __( 'Database', 'algq-command-center' ),
                'status'  => empty( $wpdb->last_error ) ? 'operational' : 'failed',
                'message' => empty( $wpdb->last_error ) ? __( 'No current database error.', 'algq-command-center' ) : sanitize_text_field( $wpdb->last_error ),
            ),
            'cron' => array(
                'label'   => __( 'WP-Cron', 'algq-command-center' ),
                'status'  => defined( 'DISABLE_WP_CRON' ) && DISABLE_WP_CRON ? 'warning' : 'operational',
                'message' => defined( 'DISABLE_WP_CRON' ) && DISABLE_WP_CRON ? __( 'WP-Cron is disabled; confirm a server cron is configured.', 'algq-command-center' ) : __( 'WP-Cron is enabled.', 'algq-command-center' ),
            ),
        );

        $required_plugins = apply_filters(
            'algq_command_center_required_plugins',
            array(
                'algq-deal-intake'      => 'Algonquian Deal Intake',
                'algq-pipeline-crm'     => 'Algonquian Pipeline CRM',
                'algq-mao-engine'       => 'Algonquian MAO Engine',
                'algq-offer-generator'  => 'Algonquian Offer Generator',
                'algq-buyer-portal'     => 'Algonquian Buyer Portal',
                'algq-funding-tracker'  => 'Algonquian Funding Tracker',
                'algq-document-library' => 'Algonquian Document Library',
                'algq-pdf-signature'    => 'Algonquian PDF & Signature Engine',
                'algq-automation-engine'=> 'Algonquian Automation Engine',
            )
        );

        foreach ( $required_plugins as $slug => $label ) {
            $active = self::is_plugin_slug_active( $slug );
            $checks[ 'plugin_' . sanitize_key( $slug ) ] = array(
                'label'   => $label,
                'status'  => $active ? 'operational' : 'warning',
                'message' => $active ? __( 'Active', 'algq-command-center' ) : __( 'Not detected as active', 'algq-command-center' ),
            );
        }

        return apply_filters( 'algq_command_center_health_checks', $checks );
    }

    public static function summary(): array {
        $summary = array( 'operational' => 0, 'warning' => 0, 'failed' => 0 );

        foreach ( self::checks() as $check ) {
            $status = isset( $check['status'] ) ? sanitize_key( $check['status'] ) : 'warning';
            if ( isset( $summary[ $status ] ) ) {
                ++$summary[ $status ];
            }
        }

        return $summary;
    }

    private static function is_plugin_slug_active( string $slug ): bool {
        $active_plugins = (array) get_option( 'active_plugins', array() );

        if ( is_multisite() ) {
            $active_plugins = array_merge( $active_plugins, array_keys( (array) get_site_option( 'active_sitewide_plugins', array() ) ) );
        }

        foreach ( $active_plugins as $plugin_file ) {
            if ( str_contains( $plugin_file, $slug ) ) {
                return true;
            }
        }

        return false;
    }
}
