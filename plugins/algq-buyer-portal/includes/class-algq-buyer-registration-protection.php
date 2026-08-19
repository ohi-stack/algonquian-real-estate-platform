<?php
/**
 * Abuse protection for unauthenticated buyer registration.
 *
 * @package Algonquian_Buyer_Portal
 */

defined( 'ABSPATH' ) || exit;

final class ALGQ_Buyer_Registration_Protection {
    private const DEFAULT_RATE_LIMIT = 5;

    public static function init(): void {
        add_action( 'admin_post_nopriv_algq_buyer_register', array( __CLASS__, 'enforce_rate_limit' ), 1 );
    }

    public static function enforce_rate_limit(): void {
        $limit = max( 1, absint( apply_filters( 'algq_buyer_registration_rate_limit_per_hour', self::DEFAULT_RATE_LIMIT ) ) );
        $key   = self::rate_limit_key();
        $count = absint( get_transient( $key ) );

        if ( $count >= $limit ) {
            do_action(
                'algq_audit_event',
                'buyer.registration_rate_limited',
                array(
                    'plugin' => 'algq-buyer-portal',
                    'limit'  => $limit,
                )
            );
            $referer = wp_get_referer() ?: home_url( '/buyers-register/' );
            wp_safe_redirect( add_query_arg( 'algq_buyer_notice', 'rate-limited', $referer ) );
            exit;
        }

        set_transient( $key, $count + 1, HOUR_IN_SECONDS );
    }

    private static function rate_limit_key(): string {
        $ip = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';
        $ua = isset( $_SERVER['HTTP_USER_AGENT'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ) : '';
        return 'algq_buyer_reg_rate_' . hash_hmac( 'sha256', $ip . '|' . substr( $ua, 0, 300 ), wp_salt( 'nonce' ) );
    }
}
