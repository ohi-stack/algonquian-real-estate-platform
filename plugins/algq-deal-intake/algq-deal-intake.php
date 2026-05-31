<?php
/**
 * Plugin Name: Algonquian Deal Intake
 * Plugin URI: https://algonquianrealestate.com
 * Description: Captures seller leads, property submissions, and acquisition opportunities for Algonquian Real Estate.
 * Version: 1.0.0-rc.1
 * Author: Onegodian | Algonquian Real Estate
 * Text Domain: algq-deal-intake
 * Domain Path: /languages
 * Requires at least: 6.0
 * Requires PHP: 7.4
 *
 * @package Algonquian_Deal_Intake
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'ALGQ_DEAL_INTAKE_VERSION', '1.0.0-rc.1' );
define( 'ALGQ_DEAL_INTAKE_FILE', __FILE__ );
define( 'ALGQ_DEAL_INTAKE_DIR', plugin_dir_path( __FILE__ ) );
define( 'ALGQ_DEAL_INTAKE_URL', plugin_dir_url( __FILE__ ) );

require_once ALGQ_DEAL_INTAKE_DIR . 'includes/class-security.php';
require_once ALGQ_DEAL_INTAKE_DIR . 'includes/class-activator.php';
require_once ALGQ_DEAL_INTAKE_DIR . 'includes/class-page-generator.php';
require_once ALGQ_DEAL_INTAKE_DIR . 'includes/class-assets.php';
require_once ALGQ_DEAL_INTAKE_DIR . 'includes/class-admin.php';
require_once ALGQ_DEAL_INTAKE_DIR . 'includes/class-shortcodes.php';
require_once ALGQ_DEAL_INTAKE_DIR . 'includes/class-submissions.php';
require_once ALGQ_DEAL_INTAKE_DIR . 'includes/class-algq-deal-intake.php';

register_activation_hook( __FILE__, array( 'ALGQ_Deal_Intake_Activator', 'activate' ) );
register_uninstall_hook( __FILE__, array( 'ALGQ_Deal_Intake_Activator', 'uninstall_cleanup' ) );

add_action(
	'plugins_loaded',
	static function () {
		$plugin = new ALGQ_Deal_Intake();
		$plugin->run();
	}
);
