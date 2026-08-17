<?php
defined( 'ABSPATH' ) || exit;

final class ALGQ_WCB_Security {
    public const CAP_MANAGE = 'manage_algq_woocommerce_bridge';
    public const CAP_VIEW   = 'view_algq_commerce_entitlements';

    public static function can_manage(): bool {
        return current_user_can( self::CAP_MANAGE ) || current_user_can( 'manage_woocommerce' ) || current_user_can( 'manage_options' );
    }

    public static function can_view(): bool {
        return is_user_logged_in() && ( current_user_can( self::CAP_VIEW ) || current_user_can( 'read' ) );
    }

    public static function verify_admin( string $action ): void {
        if ( ! self::can_manage() ) {
            wp_die( esc_html__( 'Insufficient permissions.', 'algq-woocommerce-bridge' ), '', array( 'response' => 403 ) );
        }
        check_admin_referer( $action );
    }

    public static function clean_text( mixed $value ): string {
        return sanitize_text_field( wp_unslash( (string) $value ) );
    }
}
