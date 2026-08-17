<?php
defined( 'ABSPATH' ) || exit;

final class ALGQ_WCB_Product_Settings {
    public static function init(): void {
        add_action( 'woocommerce_product_options_general_product_data', array( __CLASS__, 'fields' ) );
        add_action( 'woocommerce_admin_process_product_object', array( __CLASS__, 'save' ) );
    }

    public static function fields(): void {
        echo '<div class="options_group">';
        woocommerce_wp_checkbox( array(
            'id' => '_algq_entitlement_enabled',
            'label' => __( 'Algonquian entitlement', 'algq-woocommerce-bridge' ),
            'description' => __( 'Grant platform access when this product is paid.', 'algq-woocommerce-bridge' ),
        ) );
        woocommerce_wp_text_input( array(
            'id' => '_algq_access_key',
            'label' => __( 'Access key', 'algq-woocommerce-bridge' ),
            'description' => __( 'Stable key used by Algonquian access checks. Leave blank to use product_ID.', 'algq-woocommerce-bridge' ),
            'desc_tip' => true,
        ) );
        woocommerce_wp_text_input( array(
            'id' => '_algq_access_duration_days',
            'label' => __( 'Access duration (days)', 'algq-woocommerce-bridge' ),
            'type' => 'number',
            'custom_attributes' => array( 'min' => '0', 'step' => '1' ),
            'description' => __( '0 means no automatic expiration.', 'algq-woocommerce-bridge' ),
        ) );
        echo '</div>';
    }

    public static function save( WC_Product $product ): void {
        $product->update_meta_data( '_algq_entitlement_enabled', isset( $_POST['_algq_entitlement_enabled'] ) ? 'yes' : 'no' );
        $product->update_meta_data( '_algq_access_key', isset( $_POST['_algq_access_key'] ) ? sanitize_key( wp_unslash( $_POST['_algq_access_key'] ) ) : '' );
        $product->update_meta_data( '_algq_access_duration_days', isset( $_POST['_algq_access_duration_days'] ) ? absint( $_POST['_algq_access_duration_days'] ) : 0 );
    }
}
