<?php
/**
 * Plugin Name: Algonquian Deal Marketplace
 * Plugin URI: https://algonquianrealestate.com/plugin/deal-marketplace/
 * Description: Controlled buyer marketplace for curated real estate opportunities, versioned NDA acceptance, record-level access, secure package delivery, buyer offers, and platform automation events.
 * Version: 2.0.0
 * Author: Onegodian | Algonquian Real Estate
 * Author URI: https://algonquianrealestate.com/
 * Text Domain: algq-deal-marketplace
 * Domain Path: /languages
 * Requires at least: 6.8
 * Requires PHP: 8.2
 * License: Proprietary
 *
 * @package Algonquian_Deal_Marketplace
 */

defined( 'ABSPATH' ) || exit;

define( 'ALGQ_DM_VERSION', '2.0.0' );
define( 'ALGQ_DM_SCHEMA_VERSION', '2.0.0' );
define( 'ALGQ_DM_FILE', __FILE__ );
define( 'ALGQ_DM_DIR', plugin_dir_path( __FILE__ ) );
define( 'ALGQ_DM_URL', plugin_dir_url( __FILE__ ) );

require_once ALGQ_DM_DIR . 'includes/class-algq-dm-support.php';
require_once ALGQ_DM_DIR . 'includes/class-algq-dm-activator.php';
require_once ALGQ_DM_DIR . 'includes/class-algq-dm-marketplace.php';
require_once ALGQ_DM_DIR . 'includes/class-algq-dm-access.php';
require_once ALGQ_DM_DIR . 'includes/class-algq-dm-nda.php';
require_once ALGQ_DM_DIR . 'includes/class-algq-dm-offers.php';
require_once ALGQ_DM_DIR . 'includes/class-algq-dm-shortcodes.php';
require_once ALGQ_DM_DIR . 'includes/class-algq-dm-admin.php';
require_once ALGQ_DM_DIR . 'includes/class-algq-dm-rest.php';
require_once ALGQ_DM_DIR . 'includes/class-algq-dm-plugin.php';

register_activation_hook( __FILE__, array( 'ALGQ_DM_Activator', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'ALGQ_DM_Activator', 'deactivate' ) );

add_action(
	'plugins_loaded',
	static function (): void {
		if ( version_compare( PHP_VERSION, '8.2', '<' ) ) {
			add_action(
				'admin_notices',
				static function (): void {
					echo '<div class="notice notice-error"><p>' . esc_html__( 'Algonquian Deal Marketplace requires PHP 8.2 or newer.', 'algq-deal-marketplace' ) . '</p></div>';
				}
			);
			return;
		}

		ALGQ_DM_Plugin::instance()->run();
	}
);
