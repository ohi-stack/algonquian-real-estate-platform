<?php
defined( 'ABSPATH' ) || exit;

final class ALGQ_WCB_Integrations {
    public static function init(): void {
        if ( ! class_exists( 'WooCommerce' ) ) {
            add_action( 'admin_notices', array( __CLASS__, 'woocommerce_notice' ) );
            return;
        }
        add_action( 'woocommerce_payment_complete', array( __CLASS__, 'grant_access' ) );
        add_action( 'woocommerce_order_status_processing', array( __CLASS__, 'grant_access' ) );
        add_action( 'woocommerce_order_status_completed', array( __CLASS__, 'grant_access' ) );
        add_action( 'woocommerce_order_status_cancelled', array( __CLASS__, 'revoke_access' ) );
        add_action( 'woocommerce_order_status_refunded', array( __CLASS__, 'revoke_access' ) );
        add_action( 'woocommerce_order_status_failed', array( __CLASS__, 'revoke_access' ) );
        add_action( 'woocommerce_refund_created', array( __CLASS__, 'refund_created' ), 10, 2 );
        add_action( 'woocommerce_subscription_status_active', array( __CLASS__, 'subscription_grant' ) );
        add_action( 'woocommerce_subscription_status_cancelled', array( __CLASS__, 'subscription_revoke' ) );
        add_action( 'woocommerce_subscription_status_expired', array( __CLASS__, 'subscription_revoke' ) );
        add_action( 'woocommerce_subscription_status_on-hold', array( __CLASS__, 'subscription_revoke' ) );
    }

    public static function woocommerce_notice(): void {
        if ( current_user_can( 'activate_plugins' ) ) {
            echo '<div class="notice notice-warning"><p>' . esc_html__( 'Algonquian WooCommerce Bridge is installed but WooCommerce is not active.', 'algq-woocommerce-bridge' ) . '</p></div>';
        }
    }

    public static function grant_access( int $order_id ): void {
        $order = wc_get_order( $order_id );
        if ( $order instanceof WC_Order ) {
            ALGQ_WCB_Entitlements::grant_from_order( $order, 'order' );
        }
    }

    public static function revoke_access( int $order_id ): void {
        ALGQ_WCB_Entitlements::revoke_order( $order_id, 'order_status' );
    }

    public static function refund_created( int $refund_id, array $args ): void {
        $refund = wc_get_order( $refund_id );
        if ( ! $refund instanceof WC_Order_Refund ) {
            return;
        }
        $parent_id = absint( $refund->get_parent_id() );
        $refunded_items = $refund->get_items( 'line_item' );
        if ( empty( $refunded_items ) ) {
            ALGQ_WCB_Entitlements::revoke_order( $parent_id, 'refund' );
            return;
        }
        foreach ( $refunded_items as $refund_item ) {
            $refunded_item_id = absint( $refund_item->get_meta( '_refunded_item_id', true ) );
            if ( $refunded_item_id ) {
                ALGQ_WCB_Entitlements::revoke_order_item( $parent_id, $refunded_item_id, 'refund' );
            }
        }
    }

    public static function subscription_grant( mixed $subscription ): void {
        if ( is_numeric( $subscription ) && function_exists( 'wcs_get_subscription' ) ) {
            $subscription = wcs_get_subscription( absint( $subscription ) );
        }
        if ( ! $subscription || ! is_callable( array( $subscription, 'get_parent_id' ) ) ) {
            return;
        }
        $order = wc_get_order( absint( $subscription->get_parent_id() ) );
        if ( $order instanceof WC_Order ) {
            ALGQ_WCB_Entitlements::grant_from_order( $order, 'subscription_active' );
        }
    }

    public static function subscription_revoke( mixed $subscription ): void {
        if ( is_numeric( $subscription ) && function_exists( 'wcs_get_subscription' ) ) {
            $subscription = wcs_get_subscription( absint( $subscription ) );
        }
        if ( ! $subscription || ! is_callable( array( $subscription, 'get_parent_id' ) ) ) {
            return;
        }
        ALGQ_WCB_Entitlements::revoke_order( absint( $subscription->get_parent_id() ), 'subscription_status' );
    }

    public static function user_has_access( int $user_id, string $access_key ): bool {
        return ALGQ_WCB_Entitlements::user_has_access( $user_id, $access_key );
    }
}
