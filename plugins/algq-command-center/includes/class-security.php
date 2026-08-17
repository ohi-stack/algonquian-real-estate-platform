<?php
/**
 * Security and authorization helpers.
 *
 * @package Algonquian_Command_Center
 */

defined( 'ABSPATH' ) || exit;

final class ALGQ_Command_Center_Security {
    public const CAP_VIEW    = 'view_algq_command_center';
    public const CAP_MANAGE  = 'manage_algq_command_center';
    public const CAP_EXPORT  = 'export_algq_reports';
    public const CAP_AUDIT   = 'view_algq_audit_logs';
    public const CAP_COMMAND = 'run_algq_system_commands';

    public const NONCE_ACTION = 'algq_command_center_action';
    public const NONCE_NAME   = 'algq_command_center_nonce';

    public static function can_view(): bool {
        return current_user_can( self::CAP_VIEW ) || current_user_can( self::CAP_MANAGE ) || current_user_can( 'manage_options' );
    }

    public static function can_manage(): bool {
        return current_user_can( self::CAP_MANAGE ) || current_user_can( 'manage_options' );
    }

    public static function can_export(): bool {
        return current_user_can( self::CAP_EXPORT ) || current_user_can( 'manage_options' );
    }

    public static function can_view_audit(): bool {
        return current_user_can( self::CAP_AUDIT ) || current_user_can( 'manage_options' );
    }

    public static function can_run_commands(): bool {
        return current_user_can( self::CAP_COMMAND ) || current_user_can( 'manage_options' );
    }

    public static function require_view(): void {
        if ( ! self::can_view() ) {
            wp_die( esc_html__( 'You do not have permission to view the Algonquian Admin Command Center.', 'algq-command-center' ), '', array( 'response' => 403 ) );
        }
    }

    public static function require_manage(): void {
        if ( ! self::can_manage() ) {
            wp_die( esc_html__( 'You do not have permission to manage the Algonquian Admin Command Center.', 'algq-command-center' ), '', array( 'response' => 403 ) );
        }
    }

    public static function nonce_field(): void {
        wp_nonce_field( self::NONCE_ACTION, self::NONCE_NAME );
    }

    public static function verify_nonce_from_request(): bool {
        $nonce = isset( $_REQUEST[ self::NONCE_NAME ] ) ? sanitize_text_field( wp_unslash( $_REQUEST[ self::NONCE_NAME ] ) ) : '';
        return (bool) wp_verify_nonce( $nonce, self::NONCE_ACTION );
    }

    public static function clean_text( mixed $value ): string {
        return sanitize_text_field( wp_unslash( (string) $value ) );
    }
}
