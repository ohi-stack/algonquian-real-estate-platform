<?php
/**
 * Plugin Name: Algonquian Real Estate API Bridge
 * Plugin URI: https://algonquianrealestate.com/algonquian-api-bridge/
 * Description: Authenticated external API boundary connecting the ARE API Gateway to authoritative Algonquian Real Estate platform services.
 * Version: 1.0.0
 * Author: Algonquian Real Estate, LLC
 * Author URI: https://algonquianrealestate.com/technology/
 * Text Domain: algq-api-bridge
 * Requires at least: 6.8
 * Requires PHP: 8.2
 * License: GPL-2.0-or-later
 */

defined( 'ABSPATH' ) || exit;

define( 'ALGQ_API_BRIDGE_VERSION', '1.0.0' );
define( 'ALGQ_API_BRIDGE_FILE', __FILE__ );
define( 'ALGQ_API_BRIDGE_DIR', plugin_dir_path( __FILE__ ) );
define( 'ALGQ_API_BRIDGE_URL', plugin_dir_url( __FILE__ ) );

require_once ALGQ_API_BRIDGE_DIR . 'includes/class-auth.php';
require_once ALGQ_API_BRIDGE_DIR . 'includes/class-rest-api.php';
require_once ALGQ_API_BRIDGE_DIR . 'includes/class-admin.php';

final class ALGQ_API_Bridge {
	public static function init(): void {
		ALGQ_API_Bridge_REST_API::init();
		ALGQ_API_Bridge_Admin::init();
	}

	public static function activate(): void {
		update_option( 'algq_api_bridge_version', ALGQ_API_BRIDGE_VERSION, false );
		$role = get_role( 'administrator' );
		if ( $role ) {
			$role->add_cap( 'manage_algq_api_bridge' );
		}
	}

	public static function deactivate(): void {
		// Runtime-only bridge. No destructive deactivation behavior.
	}
}

register_activation_hook( __FILE__, array( 'ALGQ_API_Bridge', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'ALGQ_API_Bridge', 'deactivate' ) );
add_action( 'plugins_loaded', array( 'ALGQ_API_Bridge', 'init' ), 30 );
