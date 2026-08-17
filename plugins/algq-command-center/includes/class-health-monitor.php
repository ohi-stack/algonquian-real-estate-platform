<?php
/**
 * Cross-plugin platform health monitor.
 *
 * @package Algonquian_Command_Center
 */

defined( 'ABSPATH' ) || exit;

final class ALGQ_Command_Center_Health_Monitor {
    public static function checks(): array {
        global $wpdb;

        $uploads = wp_upload_dir();
        $checks  = array(
            'wordpress' => array(
                'label' => __( 'WordPress', 'algq-command-center' ),
                'status' => version_compare( get_bloginfo( 'version' ), ALGQ_COMMAND_CENTER_MIN_WP, '>=' ) ? 'operational' : 'warning',
                'message' => sprintf( __( 'Version %s', 'algq-command-center' ), get_bloginfo( 'version' ) ),
                'required' => true,
            ),
            'php' => array(
                'label' => __( 'PHP', 'algq-command-center' ),
                'status' => version_compare( PHP_VERSION, ALGQ_COMMAND_CENTER_MIN_PHP, '>=' ) ? 'operational' : 'failed',
                'message' => sprintf( __( 'Version %s', 'algq-command-center' ), PHP_VERSION ),
                'required' => true,
            ),
            'database' => array(
                'label' => __( 'Database', 'algq-command-center' ),
                'status' => empty( $wpdb->last_error ) ? 'operational' : 'failed',
                'message' => empty( $wpdb->last_error ) ? __( 'No current database error.', 'algq-command-center' ) : sanitize_text_field( $wpdb->last_error ),
                'required' => true,
            ),
            'cron' => array(
                'label' => __( 'Scheduled Processing', 'algq-command-center' ),
                'status' => defined( 'DISABLE_WP_CRON' ) && DISABLE_WP_CRON ? 'warning' : 'operational',
                'message' => defined( 'DISABLE_WP_CRON' ) && DISABLE_WP_CRON ? __( 'WP-Cron is disabled. Confirm a server cron is configured.', 'algq-command-center' ) : __( 'WP-Cron is enabled.', 'algq-command-center' ),
                'required' => true,
            ),
            'rest_api' => array(
                'label' => __( 'REST API', 'algq-command-center' ),
                'status' => function_exists( 'rest_get_server' ) ? 'operational' : 'failed',
                'message' => function_exists( 'rest_get_server' ) ? __( 'WordPress REST infrastructure is available.', 'algq-command-center' ) : __( 'REST infrastructure is unavailable.', 'algq-command-center' ),
                'required' => true,
            ),
            'mail' => array(
                'label' => __( 'Email Delivery', 'algq-command-center' ),
                'status' => function_exists( 'wp_mail' ) ? 'operational' : 'failed',
                'message' => self::mail_message(),
                'required' => true,
            ),
            'storage' => array(
                'label' => __( 'File Storage', 'algq-command-center' ),
                'status' => empty( $uploads['error'] ) && ! empty( $uploads['basedir'] ) ? 'operational' : 'failed',
                'message' => empty( $uploads['error'] ) ? __( 'WordPress upload storage is available.', 'algq-command-center' ) : sanitize_text_field( (string) $uploads['error'] ),
                'required' => true,
            ),
        );

        foreach ( self::plugin_registry() as $key => $plugin ) {
            $active = self::is_any_plugin_slug_active( $plugin['slugs'] );
            $checks[ 'plugin_' . sanitize_key( $key ) ] = array(
                'label' => $plugin['label'],
                'status' => $active ? 'operational' : ( $plugin['required'] ? 'warning' : 'optional' ),
                'message' => $active ? __( 'Active', 'algq-command-center' ) : ( $plugin['required'] ? __( 'Not detected as active', 'algq-command-center' ) : __( 'Optional integration not detected', 'algq-command-center' ) ),
                'required' => (bool) $plugin['required'],
            );
        }

        return apply_filters( 'algq_command_center_health_checks', $checks );
    }

    public static function plugin_registry(): array {
        $plugins = array(
            'platform' => array( 'label' => 'Algonquian Real Estate Platform Plugin', 'slugs' => array( 'algonquian-real-estate-platform', 'algq-core' ), 'required' => true ),
            'deal_intake' => array( 'label' => 'Algonquian Deal Intake', 'slugs' => array( 'algq-deal-intake' ), 'required' => true ),
            'pipeline_crm' => array( 'label' => 'Algonquian Pipeline CRM', 'slugs' => array( 'algq-pipeline-crm' ), 'required' => true ),
            'mao_engine' => array( 'label' => 'Algonquian MAO Engine', 'slugs' => array( 'algq-mao-engine' ), 'required' => true ),
            'offer_generator' => array( 'label' => 'Algonquian Offer Generator', 'slugs' => array( 'algq-offer-generator' ), 'required' => true ),
            'buyer_portal' => array( 'label' => 'Algonquian Buyer Portal', 'slugs' => array( 'algq-buyer-portal' ), 'required' => true ),
            'deal_marketplace' => array( 'label' => 'Algonquian Deal Marketplace', 'slugs' => array( 'algq-deal-marketplace' ), 'required' => false ),
            'funding_tracker' => array( 'label' => 'Algonquian Funding Tracker', 'slugs' => array( 'algq-funding-tracker' ), 'required' => true ),
            'document_library' => array( 'label' => 'Algonquian Document Library', 'slugs' => array( 'algq-document-library' ), 'required' => true ),
            'pdf_signature' => array( 'label' => 'Algonquian PDF & Signature Engine', 'slugs' => array( 'algq-pdf-signature' ), 'required' => true ),
            'automation_engine' => array( 'label' => 'Algonquian Automation Engine', 'slugs' => array( 'algq-automation-engine' ), 'required' => true ),
            'digital_products' => array( 'label' => 'Algonquian Digital Products', 'slugs' => array( 'algq-digital-products' ), 'required' => false ),
            'digital_store' => array( 'label' => 'Algonquian Digital Store', 'slugs' => array( 'algq-digital-store' ), 'required' => false ),
            'woocommerce_bridge' => array( 'label' => 'ALGQ WooCommerce Bridge', 'slugs' => array( 'algq-woocommerce-bridge' ), 'required' => false ),
        );

        return apply_filters( 'algq_command_center_plugin_registry', $plugins );
    }

    public static function summary(): array {
        $summary = array( 'operational' => 0, 'warning' => 0, 'failed' => 0, 'optional' => 0, 'total' => 0 );
        foreach ( self::checks() as $check ) {
            $status = sanitize_key( (string) ( $check['status'] ?? 'warning' ) );
            if ( isset( $summary[ $status ] ) ) {
                ++$summary[ $status ];
            }
            ++$summary['total'];
        }
        $summary['score'] = $summary['total'] > 0 ? (int) round( ( $summary['operational'] / $summary['total'] ) * 100 ) : 0;
        return $summary;
    }

    private static function mail_message(): string {
        if ( class_exists( 'ALGQ_Mail_Module' ) || class_exists( 'ALGQ_Mail_Transport' ) || function_exists( 'algq_mail' ) ) {
            return __( 'Algonquian platform mail service detected.', 'algq-command-center' );
        }
        return function_exists( 'wp_mail' ) ? __( 'WordPress mail API is available; no Algonquian mail service was detected.', 'algq-command-center' ) : __( 'WordPress mail API is unavailable.', 'algq-command-center' );
    }

    private static function is_any_plugin_slug_active( array $slugs ): bool {
        $active_plugins = (array) get_option( 'active_plugins', array() );
        if ( is_multisite() ) {
            $active_plugins = array_merge( $active_plugins, array_keys( (array) get_site_option( 'active_sitewide_plugins', array() ) ) );
        }

        foreach ( $active_plugins as $plugin_file ) {
            foreach ( $slugs as $slug ) {
                if ( str_contains( (string) $plugin_file, (string) $slug ) ) {
                    return true;
                }
            }
        }
        return false;
    }
}
