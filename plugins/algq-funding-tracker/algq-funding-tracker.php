<?php
/**
 * Plugin Name: Algonquian Funding Tracker
 * Plugin URI: https://algonquianrealestate.com/algonquian-funding-tracker/
 * Description: Tracks capital sources, lender and investor relationships, deal-level funding requests, commitments, funding progress, and activity for Algonquian Real Estate.
 * Version: 1.0.0
 * Author: Algonquian Real Estate, LLC
 * Author URI: https://algonquianrealestate.com/technology/
 * Text Domain: algq-funding-tracker
 * Domain Path: /languages
 * Requires at least: 6.0
 * Requires PHP: 7.4
 * License: Proprietary
 *
 * @package Algonquian_Funding_Tracker
 */

defined( 'ABSPATH' ) || exit;

define( 'ALGQ_FUNDING_TRACKER_VERSION', '1.0.0' );
define( 'ALGQ_FUNDING_TRACKER_FILE', __FILE__ );
define( 'ALGQ_FUNDING_TRACKER_DIR', plugin_dir_path( __FILE__ ) );
define( 'ALGQ_FUNDING_TRACKER_URL', plugin_dir_url( __FILE__ ) );

require_once ALGQ_FUNDING_TRACKER_DIR . 'includes/class-activator.php';
require_once ALGQ_FUNDING_TRACKER_DIR . 'includes/class-repository.php';
require_once ALGQ_FUNDING_TRACKER_DIR . 'includes/class-admin.php';
require_once ALGQ_FUNDING_TRACKER_DIR . 'includes/class-shortcodes.php';
require_once ALGQ_FUNDING_TRACKER_DIR . 'includes/class-rest.php';

register_activation_hook( ALGQ_FUNDING_TRACKER_FILE, array( 'ALGQ_Funding_Tracker_Activator', 'activate' ) );

/**
 * Boot the plugin after WordPress has loaded active plugins.
 */
function algq_funding_tracker_boot() {
	ALGQ_Funding_Tracker_Activator::maybe_upgrade();

	load_plugin_textdomain(
		'algq-funding-tracker',
		false,
		dirname( plugin_basename( ALGQ_FUNDING_TRACKER_FILE ) ) . '/languages'
	);

	$repository = new ALGQ_Funding_Tracker_Repository();

	( new ALGQ_Funding_Tracker_Admin( $repository ) )->register();
	( new ALGQ_Funding_Tracker_Shortcodes( $repository ) )->register();
	( new ALGQ_Funding_Tracker_REST( $repository ) )->register();

	add_action( 'admin_notices', 'algq_funding_tracker_dependency_notice' );
}
add_action( 'plugins_loaded', 'algq_funding_tracker_boot' );

/**
 * Display a non-blocking notice when the shared platform plugin is unavailable.
 */
function algq_funding_tracker_dependency_notice() {
	if ( defined( 'ALGQ_PLATFORM_VERSION' ) || function_exists( 'algq_platform' ) ) {
		return;
	}

	if ( ! current_user_can( 'activate_plugins' ) ) {
		return;
	}

	printf(
		'<div class="notice notice-warning"><p>%s</p></div>',
		esc_html__( 'Algonquian Funding Tracker is operating in standalone compatibility mode. Activate the Algonquian Real Estate Platform Plugin to enable centralized navigation, audit, mail, and health services.', 'algq-funding-tracker' )
	);
}
