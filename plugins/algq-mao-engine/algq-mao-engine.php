<?php
/**
 * Plugin Name: Algonquian MAO Engine
 * Plugin URI: https://algonquianrealestate.com/plugin/mao-engine/
 * Description: Versioned acquisition underwriting, MAO calculations, seller-financing analysis, sensitivity analysis, approval controls, and platform integration.
 * Version: 2.1.0
 * Author: Onegodian | Algonquian Real Estate Technology Division
 * Author URI: https://algonquianrealestate.com/
 * Text Domain: algq-mao-engine
 * Domain Path: /languages
 * Requires at least: 6.5
 * Requires PHP: 8.1
 * License: Proprietary
 */

defined( 'ABSPATH' ) || exit;

define( 'ALGQ_MAO_ENGINE_VERSION', '2.1.0' );
define( 'ALGQ_MAO_ENGINE_SCHEMA_VERSION', '2.1.0' );
define( 'ALGQ_MAO_ENGINE_FILE', __FILE__ );
define( 'ALGQ_MAO_ENGINE_PATH', plugin_dir_path( __FILE__ ) );
define( 'ALGQ_MAO_ENGINE_URL', plugin_dir_url( __FILE__ ) );

foreach ( array(
	'class-algq-mao-calculator.php',
	'class-algq-mao-database.php',
	'class-algq-mao-pages.php',
	'class-algq-mao-rest.php',
	'class-algq-mao-admin.php',
	'class-algq-mao-engine.php',
	'class-algq-mao-platform-bridge.php',
) as $file ) {
	require_once ALGQ_MAO_ENGINE_PATH . 'includes/' . $file;
}

register_activation_hook( __FILE__, array( 'ALGQ_MAO_Engine', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'ALGQ_MAO_Engine', 'deactivate' ) );

add_action( 'plugins_loaded', function () {
	load_plugin_textdomain( 'algq-mao-engine', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
	$engine = ALGQ_MAO_Engine::instance();
	new ALGQ_MAO_Platform_Bridge( $engine );
	do_action( 'algq_platform_register_plugin', $engine->registry_payload() );
}, 20 );
