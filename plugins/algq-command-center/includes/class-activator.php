<?php
/**
 * Activation and upgrade routines.
 *
 * @package Algonquian_Command_Center
 */

defined( 'ABSPATH' ) || exit;

final class ALGQ_Command_Center_Activator {
    public static function activate(): void {
        self::grant_capabilities();

        update_option( 'algq_command_center_version', ALGQ_COMMAND_CENTER_VERSION );
        update_option( 'algq_command_center_release_status', 'Production' );

        if ( false === get_option( 'algq_command_center_enabled_widgets', false ) ) {
            update_option( 'algq_command_center_enabled_widgets', self::default_widgets() );
        }

        if ( false === get_option( 'algq_command_center_refresh_interval', false ) ) {
            update_option( 'algq_command_center_refresh_interval', 300 );
        }

        if ( class_exists( 'ALGQ_Command_Center_Page_Generator' ) ) {
            ALGQ_Command_Center_Page_Generator::create_required_pages();
        }
    }

    public static function maybe_upgrade(): void {
        $installed = (string) get_option( 'algq_command_center_version', '0.0.0' );
        if ( version_compare( $installed, ALGQ_COMMAND_CENTER_VERSION, '<' ) ) {
            self::activate();
            do_action( 'algq_command_center_upgraded', $installed, ALGQ_COMMAND_CENTER_VERSION );
        }
    }

    public static function grant_capabilities(): void {
        $role = get_role( 'administrator' );
        if ( ! $role ) {
            return;
        }

        foreach ( self::capabilities() as $capability ) {
            $role->add_cap( $capability );
        }
    }

    public static function capabilities(): array {
        return array(
            ALGQ_Command_Center_Security::CAP_VIEW,
            ALGQ_Command_Center_Security::CAP_MANAGE,
            ALGQ_Command_Center_Security::CAP_EXPORT,
            ALGQ_Command_Center_Security::CAP_AUDIT,
            ALGQ_Command_Center_Security::CAP_COMMAND,
        );
    }

    private static function default_widgets(): array {
        return array(
            'new_leads',
            'active_deals',
            'underwriting_queue',
            'offers_pending',
            'contracts_pending',
            'closings',
            'buyers_registered',
            'funding_status',
            'pipeline_value',
            'documents_generated',
            'signatures_pending',
            'automation_failed',
            'system_health_score',
        );
    }
}
