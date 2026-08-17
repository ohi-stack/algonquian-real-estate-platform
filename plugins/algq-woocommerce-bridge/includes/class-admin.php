<?php
defined( 'ABSPATH' ) || exit;

final class ALGQ_WCB_Admin {
    public static function init(): void {
        add_action( 'admin_menu', array( __CLASS__, 'menu' ) );
        add_action( 'admin_post_algq_wcb_save', array( __CLASS__, 'save' ) );
        add_action( 'admin_enqueue_scripts', array( __CLASS__, 'assets' ) );
    }

    public static function menu(): void {
        add_submenu_page( 'woocommerce', 'Algonquian Bridge', 'Algonquian Bridge', ALGQ_WCB_Security::CAP_MANAGE, 'algq-woocommerce-bridge', array( __CLASS__, 'render' ) );
    }

    public static function assets( string $hook ): void {
        if ( false === strpos( $hook, 'algq-woocommerce-bridge' ) ) {
            return;
        }
        wp_enqueue_style( 'algq-wcb-admin', ALGQ_WCB_URL . 'assets/css/algq-woocommerce-bridge.css', array(), ALGQ_WCB_VERSION );
    }

    public static function render(): void {
        if ( ! ALGQ_WCB_Security::can_manage() ) {
            return;
        }
        $brand = esc_attr( get_option( 'algq_wcb_brand_name', 'Algonquian Real Estate' ) );
        $diagnostics = ALGQ_WCB_Diagnostics::get();
        echo '<div class="wrap algq-wcb-admin"><h1>Algonquian WooCommerce Bridge <small>2.0.0</small></h1>';
        echo '<p>WooCommerce remains authoritative for orders and payments. The Bridge owns only Algonquian entitlement records derived from confirmed commerce events.</p>';
        echo '<div class="algq-card"><h2>Diagnostics</h2><table class="widefat striped"><tbody>';
        foreach ( $diagnostics as $label => $value ) {
            echo '<tr><th>' . esc_html( ucwords( str_replace( '_', ' ', $label ) ) ) . '</th><td>' . esc_html( (string) $value ) . '</td></tr>';
        }
        echo '</tbody></table></div>';
        echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
        wp_nonce_field( 'algq_wcb_save' );
        echo '<input type="hidden" name="action" value="algq_wcb_save"><table class="form-table"><tr><th><label for="algq_wcb_brand_name">Brand Name</label></th><td><input class="regular-text" id="algq_wcb_brand_name" name="algq_wcb_brand_name" value="' . $brand . '"></td></tr></table>';
        submit_button( 'Save Bridge Settings' );
        echo '</form><hr><h2>Shortcodes</h2><p><code>[algq_commerce_access]</code> <code>[algq_purchased_products]</code> <code>[algq_buyer_entitlements]</code></p></div>';
    }

    public static function save(): void {
        ALGQ_WCB_Security::verify_admin( 'algq_wcb_save' );
        update_option( 'algq_wcb_brand_name', ALGQ_WCB_Security::clean_text( $_POST['algq_wcb_brand_name'] ?? 'Algonquian Real Estate' ) );
        do_action( 'algq_audit_event', array( 'event' => 'woocommerce_bridge.settings_updated', 'user_id' => get_current_user_id(), 'source_plugin' => 'algq-woocommerce-bridge' ) );
        wp_safe_redirect( admin_url( 'admin.php?page=algq-woocommerce-bridge&updated=1' ) );
        exit;
    }
}
