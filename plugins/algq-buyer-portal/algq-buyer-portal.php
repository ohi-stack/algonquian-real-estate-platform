<?php
/**
 * Plugin Name: Algonquian Buyer Portal
 * Plugin URI: https://algonquianrealestate.com/technology/plugin-suite/
 * Description: Secure buyer registration, deal authorization, NDA evidence, buyer interest, and protected deal-package delivery for Algonquian Real Estate.
 * Version: 1.1.0
 * Requires at least: 6.5
 * Requires PHP: 8.2
 * Author: Onegodian | Algonquian Real Estate Technology Division
 * Text Domain: algq-buyer-portal
 * License: GPL-2.0-or-later
 */

defined( 'ABSPATH' ) || exit;

define( 'ALGQ_BUYER_PORTAL_VERSION', '1.1.0' );
define( 'ALGQ_BUYER_PORTAL_FILE', __FILE__ );
define( 'ALGQ_BUYER_PORTAL_DIR', plugin_dir_path( __FILE__ ) );
define( 'ALGQ_BUYER_PORTAL_URL', plugin_dir_url( __FILE__ ) );

require_once ALGQ_BUYER_PORTAL_DIR . 'includes/class-algq-buyer-portal-activator.php';
require_once ALGQ_BUYER_PORTAL_DIR . 'includes/class-algq-buyer-registration-protection.php';
require_once ALGQ_BUYER_PORTAL_DIR . 'includes/class-algq-buyer-portal.php';

register_activation_hook( __FILE__, array( 'ALGQ_Buyer_Portal_Activator', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'ALGQ_Buyer_Portal_Activator', 'deactivate' ) );

add_action(
    'plugins_loaded',
    static function (): void {
        ALGQ_Buyer_Registration_Protection::init();
        ALGQ_Buyer_Portal::instance()->init();
    }
);
