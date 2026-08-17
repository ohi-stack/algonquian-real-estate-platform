<?php
defined( 'ABSPATH' ) || exit;

final class ALGQ_WCB_Diagnostics {
    public static function get(): array {
        global $wpdb;
        $table = ALGQ_WCB_Entitlements::table();
        $table_exists = $table === $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) );
        $hpos = 'legacy';
        if ( class_exists( '\\Automattic\\WooCommerce\\Utilities\\OrderUtil' ) ) {
            $hpos = \\Automattic\\WooCommerce\\Utilities\\OrderUtil::custom_orders_table_usage_is_enabled() ? 'enabled' : 'legacy';
        }
        return array(
            'plugin_version' => ALGQ_WCB_VERSION,
            'schema_version' => (string) get_option( 'algq_wcb_schema_version', '' ),
            'woocommerce' => class_exists( 'WooCommerce' ) ? 'active' : 'missing',
            'woocommerce_version' => defined( 'WC_VERSION' ) ? WC_VERSION : '',
            'hpos' => $hpos,
            'subscriptions' => function_exists( 'wcs_get_subscription' ) ? 'detected' : 'not detected',
            'entitlements_table' => $table_exists ? 'present' : 'missing',
            'active_entitlements' => $table_exists ? (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table} WHERE status='active'" ) : 0,
        );
    }
}
