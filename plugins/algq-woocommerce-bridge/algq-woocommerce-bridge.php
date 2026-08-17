<?php
/**
 * Plugin Name: Algonquian WooCommerce Bridge
 * Plugin URI: https://algonquianrealestate.com
 * Description: Connects WooCommerce orders, subscriptions, refunds, and product access to controlled Algonquian Real Estate platform entitlements.
 * Version: 2.0.0
 * Author: Onegodian | Algonquian Real Estate
 * Author URI: https://algonquianrealestate.com
 * Text Domain: algq-woocommerce-bridge
 * Requires at least: 6.8
 * Requires PHP: 8.2
 * WC requires at least: 9.0
 * License: GPL-2.0-or-later
 */

defined( 'ABSPATH' ) || exit;

define( 'ALGQ_WCB_VERSION', '2.0.0' );
define( 'ALGQ_WCB_SCHEMA_VERSION', '2.0.0' );
define( 'ALGQ_WCB_FILE', __FILE__ );
define( 'ALGQ_WCB_DIR', plugin_dir_path( __FILE__ ) );
define( 'ALGQ_WCB_URL', plugin_dir_url( __FILE__ ) );

require_once ALGQ_WCB_DIR . 'includes/class-security.php';
require_once ALGQ_WCB_DIR . 'includes/class-capabilities.php';
require_once ALGQ_WCB_DIR . 'includes/class-entitlements.php';
require_once ALGQ_WCB_DIR . 'includes/class-integrations.php';
require_once ALGQ_WCB_DIR . 'includes/class-product-settings.php';
require_once ALGQ_WCB_DIR . 'includes/class-diagnostics.php';
require_once ALGQ_WCB_DIR . 'includes/class-activator.php';
require_once ALGQ_WCB_DIR . 'includes/class-admin.php';
require_once ALGQ_WCB_DIR . 'includes/class-shortcodes.php';

register_activation_hook( __FILE__, array( 'ALGQ_WCB_Activator', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'ALGQ_WCB_Activator', 'deactivate' ) );

add_action(
    'before_woocommerce_init',
    static function (): void {
        if ( class_exists( '\Automattic\WooCommerce\Utilities\FeaturesUtil' ) ) {
            \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
        }
    }
);

add_action(
    'plugins_loaded',
    static function (): void {
        load_plugin_textdomain( 'algq-woocommerce-bridge', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
        if ( version_compare( PHP_VERSION, '8.2', '<' ) ) {
            add_action( 'admin_notices', static function (): void {
                echo '<div class="notice notice-error"><p>' . esc_html__( 'Algonquian WooCommerce Bridge requires PHP 8.2 or newer.', 'algq-woocommerce-bridge' ) . '</p></div>';
            } );
            return;
        }
        ALGQ_WCB_Activator::maybe_upgrade();
        ALGQ_WCB_Admin::init();
        ALGQ_WCB_Shortcodes::init();
        ALGQ_WCB_Integrations::init();
        ALGQ_WCB_Product_Settings::init();
    }
);
