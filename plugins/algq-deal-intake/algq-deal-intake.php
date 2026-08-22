<?php
/**
 * Plugin Name: Algonquian Deal Intake
 * Plugin URI: https://algonquianrealestate.com/algonquian-deal-intake/
 * Description: Authoritative seller-lead and property-submission intake, consent evidence, duplicate review, lead scoring, and controlled Pipeline CRM handoff for Algonquian Real Estate LLC.
 * Version: 2.0.0
 * Author: Algonquian Real Estate, LLC
 * Author URI: https://algonquianrealestate.com/technology/
 * Text Domain: algq-deal-intake
 * Domain Path: /languages
 * Requires at least: 6.8
 * Requires PHP: 8.2
 * Requires Plugins: algonquian-real-estate-platform
 * License: Proprietary / Internal Use
 *
 * @package Algonquian_Deal_Intake
 */

defined( 'ABSPATH' ) || exit;

define( 'ALGQ_DI_VERSION', '2.0.0' );
define( 'ALGQ_DI_SCHEMA_VERSION', '2.0.0' );
define( 'ALGQ_DI_FILE', __FILE__ );
define( 'ALGQ_DI_DIR', plugin_dir_path( __FILE__ ) );
define( 'ALGQ_DI_URL', plugin_dir_url( __FILE__ ) );

require_once ALGQ_DI_DIR . 'includes/class-security.php';
require_once ALGQ_DI_DIR . 'includes/class-intake.php';
require_once ALGQ_DI_DIR . 'includes/class-admin-api.php';

register_activation_hook( ALGQ_DI_FILE, array( 'ALGQ_Deal_Intake_Plugin', 'activate' ) );
register_deactivation_hook( ALGQ_DI_FILE, array( 'ALGQ_Deal_Intake_Plugin', 'deactivate' ) );

add_action(
	'plugins_loaded',
	static function (): void {
		ALGQ_Deal_Intake_Plugin::instance()->init();
	},
	20
);
