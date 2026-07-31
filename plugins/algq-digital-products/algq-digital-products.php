<?php
/**
 * Plugin Name: Algonquian Digital Products
 * Plugin URI: https://algonquianrealestate.com/technology/digital-products/
 * Description: Authoritative digital-product catalog, metadata, presentation, and WooCommerce product-linking module for the Algonquian Real Estate platform.
 * Version: 1.0.0
 * Requires at least: 6.8
 * Requires PHP: 8.2
 * Author: Onegodian | Algonquian Real Estate Technology Division
 * Author URI: https://algonquianrealestate.com/
 * Text Domain: algq-digital-products
 * Domain Path: /languages
 * License: Proprietary
 */

defined( 'ABSPATH' ) || exit;

define( 'ALGQ_DIGITAL_PRODUCTS_VERSION', '1.0.0' );
define( 'ALGQ_DIGITAL_PRODUCTS_SCHEMA_VERSION', '1.0.0' );
define( 'ALGQ_DIGITAL_PRODUCTS_FILE', __FILE__ );
define( 'ALGQ_DIGITAL_PRODUCTS_PATH', plugin_dir_path( __FILE__ ) );
define( 'ALGQ_DIGITAL_PRODUCTS_URL', plugin_dir_url( __FILE__ ) );

require_once ALGQ_DIGITAL_PRODUCTS_PATH . 'includes/class-algq-digital-products.php';

register_activation_hook( __FILE__, array( 'ALGQ_Digital_Products', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'ALGQ_Digital_Products', 'deactivate' ) );

ALGQ_Digital_Products::instance()->boot();
