<?php
/**
 * Plugin Name: Algonquian Command Center
 * Plugin URI: https://algonquianrealestate.com
 * Description: Executive dashboard, KPI cards, pipeline value, funding status, document activity, and operational visibility for Algonquian Real Estate.
 * Version: 1.0.0-rc.1
 * Author: Onegodian | Algonquian Real Estate
 * Text Domain: algq-command-center
 * Domain Path: /languages
 * Requires at least: 6.0
 * Requires PHP: 7.4
 *
 * @package Algonquian_Command_Center
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'ALGQ_COMMAND_CENTER_VERSION', '1.0.0-rc.1' );
define( 'ALGQ_COMMAND_CENTER_FILE', __FILE__ );
define( 'ALGQ_COMMAND_CENTER_DIR', plugin_dir_path( __FILE__ ) );
define( 'ALGQ_COMMAND_CENTER_URL', plugin_dir_url( __FILE__ ) );

require_once ALGQ_COMMAND_CENTER_DIR . 'includes/class-security.php';
require_once ALGQ_COMMAND_CENTER_DIR . 'includes/class-activator.php';
require_once ALGQ_COMMAND_CENTER_DIR . 'includes/class-assets.php';
require_once ALGQ_COMMAND_CENTER_DIR . 'includes/class-data-provider.php';
require_once ALGQ_COMMAND_CENTER_DIR . 'includes/class-widgets.php';
require_once ALGQ_COMMAND_CENTER_DIR . 'includes/class-shortcodes.php';
require_once ALGQ_COMMAND_CENTER_DIR . 'includes/class-page-generator.php';
require_once ALGQ_COMMAND_CENTER_DIR . 'includes/class-admin.php';
require_once ALGQ_COMMAND_CENTER_DIR . 'includes/class-algq-command-center.php';

register_activation_hook( __FILE__, array( 'ALGQ_Command_Center_Activator', 'activate' ) );

add_action(
	'plugins_loaded',
	static function () {
		$plugin = new ALGQ_Command_Center();
		$plugin->run();
	}
);
