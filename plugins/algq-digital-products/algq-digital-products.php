<?php
/**
 * Plugin Name: Algonquian Digital Products
 * Plugin URI: https://algonquianrealestate.com/algonquian-digital-products/
 * Description: Authoritative digital-product catalog, metadata, presentation, and WooCommerce product-linking module for the Algonquian Real Estate platform.
 * Version: 1.1.0
 * Requires at least: 6.8
 * Requires PHP: 8.2
 * Author: Algonquian Real Estate, LLC
 * Author URI: https://algonquianrealestate.com/technology/
 * Text Domain: algq-digital-products
 * Domain Path: /languages
 * License: Proprietary
 */

defined( 'ABSPATH' ) || exit;

define( 'ALGQ_DIGITAL_PRODUCTS_VERSION', '1.1.0' );
define( 'ALGQ_DIGITAL_PRODUCTS_SCHEMA_VERSION', '1.0.0' );
define( 'ALGQ_DIGITAL_PRODUCTS_FILE', __FILE__ );
define( 'ALGQ_DIGITAL_PRODUCTS_PATH', plugin_dir_path( __FILE__ ) );
define( 'ALGQ_DIGITAL_PRODUCTS_URL', plugin_dir_url( __FILE__ ) );

require_once ALGQ_DIGITAL_PRODUCTS_PATH . 'includes/class-algq-digital-products.php';

add_filter(
    'register_taxonomy_args',
    static function ( array $args, string $taxonomy, array $object_type ): array {
        if ( 'algq_product_category' !== $taxonomy || ! in_array( 'algq_digital_product', $object_type, true ) ) {
            return $args;
        }

        $args['capabilities'] = array(
            'manage_terms' => 'manage_algq_digital_products',
            'edit_terms'   => 'manage_algq_digital_products',
            'delete_terms' => 'manage_algq_digital_products',
            'assign_terms' => 'manage_algq_digital_products',
        );

        return $args;
    },
    10,
    3
);

register_activation_hook( __FILE__, array( 'ALGQ_Digital_Products', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'ALGQ_Digital_Products', 'deactivate' ) );

ALGQ_Digital_Products::instance()->boot();
