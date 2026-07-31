<?php

defined( 'ABSPATH' ) || exit;

final class ALGQ_Automation_Security {
    public static function capabilities(): array {
        return array(
            'manage_algq_automation',
            'view_algq_automation',
            'edit_algq_automation_rules',
            'delete_algq_automation_rules',
            'view_algq_automation_logs',
            'run_algq_automation',
        );
    }

    public static function can( string $capability ): bool {
        return current_user_can( $capability ) || current_user_can( 'manage_options' );
    }

    public static function require_capability( string $capability ): void {
        if ( ! self::can( $capability ) ) {
            wp_die( esc_html__( 'You do not have permission to perform this automation action.', 'algq-automation-engine' ) );
        }
    }

    public static function verify_admin_request( string $action, string $capability ): void {
        self::require_capability( $capability );
        check_admin_referer( $action );
    }

    public static function decode_json_object( mixed $value ): array|WP_Error {
        if ( is_array( $value ) ) {
            return $value;
        }

        $value = trim( (string) $value );

        if ( '' === $value ) {
            return array();
        }

        $decoded = json_decode( wp_unslash( $value ), true );

        if ( JSON_ERROR_NONE !== json_last_error() || ! is_array( $decoded ) ) {
            return new WP_Error( 'algq_invalid_json', __( 'The JSON configuration is invalid.', 'algq-automation-engine' ) );
        }

        return $decoded;
    }

    public static function redact( mixed $value ): mixed {
        if ( ! is_array( $value ) ) {
            return $value;
        }

        $sensitive = array( 'password', 'pass', 'token', 'secret', 'signature', 'authorization', 'api_key', 'private_key' );
        $clean     = array();

        foreach ( $value as $key => $item ) {
            $normalized = strtolower( (string) $key );
            $is_secret  = false;

            foreach ( $sensitive as $needle ) {
                if ( str_contains( $normalized, $needle ) ) {
                    $is_secret = true;
                    break;
                }
            }

            $clean[ $key ] = $is_secret ? '[redacted]' : self::redact( $item );
        }

        return $clean;
    }
}
