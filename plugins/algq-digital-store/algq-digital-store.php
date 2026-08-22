<?php
/**
 * Plugin Name: Algonquian Digital Store
 * Plugin URI: https://algonquianrealestate.com/algonquian-digital-store/
 * Description: Secure WooCommerce-backed catalog, checkout bridge, customer product vault, and entitlement events for Algonquian Real Estate digital products.
 * Version: 1.1.0
 * Requires at least: 6.5
 * Requires PHP: 8.1
 * Author: Algonquian Real Estate, LLC
 * Author URI: https://algonquianrealestate.com/technology/
 * Text Domain: algq-digital-store
 * License: GPL-2.0-or-later
 */

defined( 'ABSPATH' ) || exit;

define( 'ALGQ_DIGITAL_STORE_VERSION', '1.1.0' );
define( 'ALGQ_DIGITAL_STORE_FILE', __FILE__ );
define( 'ALGQ_DIGITAL_STORE_DIR', plugin_dir_path( __FILE__ ) );
define( 'ALGQ_DIGITAL_STORE_URL', plugin_dir_url( __FILE__ ) );

require_once ALGQ_DIGITAL_STORE_DIR . 'includes/class-algq-digital-store-activator.php';
require_once ALGQ_DIGITAL_STORE_DIR . 'includes/class-algq-digital-store.php';

register_activation_hook( __FILE__, array( 'ALGQ_Digital_Store_Activator', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'ALGQ_Digital_Store_Activator', 'deactivate' ) );

add_action(
    'plugins_loaded',
    static function (): void {
        ALGQ_Digital_Store::instance()->init();
    }
);
