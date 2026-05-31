<?php
/**
 * Plugin Name: Algonquian MAO Engine
 * Plugin URI: https://algonquianrealestate.com/plugin/mao-engine
 * Description: Maximum Allowable Offer underwriting engine for Algonquian Real Estate deal analysis, pipeline underwriting, and offer preparation.
 * Version: 1.0.0
 * Author: Onegodian | Algonquian Real Estate
 * Text Domain: algq-mao-engine
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'ALGQ_MAO_ENGINE_VERSION', '1.0.0' );
define( 'ALGQ_MAO_ENGINE_FILE', __FILE__ );
define( 'ALGQ_MAO_ENGINE_PATH', plugin_dir_path( __FILE__ ) );
define( 'ALGQ_MAO_ENGINE_URL', plugin_dir_url( __FILE__ ) );

require_once ALGQ_MAO_ENGINE_PATH . 'includes/class-algq-mao-engine.php';

register_activation_hook( __FILE__, array( 'ALGQ_MAO_Engine', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'ALGQ_MAO_Engine', 'deactivate' ) );

add_action(
	'plugins_loaded',
	static function () {
		ALGQ_MAO_Engine::instance();
	}
);
