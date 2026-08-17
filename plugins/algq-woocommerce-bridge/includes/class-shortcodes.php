<?php
defined( 'ABSPATH' ) || exit;

final class ALGQ_WCB_Shortcodes {
    public static function init(): void {
        add_shortcode( 'algq_commerce_access', array( __CLASS__, 'commerce_access' ) );
        add_shortcode( 'algq_purchased_products', array( __CLASS__, 'purchased_products' ) );
        add_shortcode( 'algq_buyer_entitlements', array( __CLASS__, 'buyer_entitlements' ) );
        add_action( 'wp_enqueue_scripts', array( __CLASS__, 'assets' ) );
    }

    public static function assets(): void {
        wp_enqueue_style( 'algq-wcb', ALGQ_WCB_URL . 'assets/css/algq-wcb.css', array(), ALGQ_WCB_VERSION );
    }

    public static function commerce_access(): string {
        ob_start(); include ALGQ_WCB_DIR . 'templates/commerce-access.php'; return (string) ob_get_clean();
    }
    public static function purchased_products(): string {
        ob_start(); include ALGQ_WCB_DIR . 'templates/purchased-products.php'; return (string) ob_get_clean();
    }
    public static function buyer_entitlements(): string {
        ob_start(); include ALGQ_WCB_DIR . 'templates/buyer-entitlements.php'; return (string) ob_get_clean();
    }
}
