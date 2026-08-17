<?php
/**
 * Plugin Name: Algonquian Admin Command Center
 * Plugin URI: https://algonquianrealestate.com
 * Description: Executive KPI dashboard, cross-plugin operational intelligence, platform health monitoring, audit visibility, secured reporting, administrative alerts, and system command controls for the Algonquian Real Estate Platform.
 * Version: 1.2.0
 * Author: Onegodian
 * Author URI: https://algonquianrealestate.com
 * Text Domain: algq-command-center
 * Domain Path: /languages
 * Requires at least: 6.8
 * Requires PHP: 8.2
 * License: GPL-2.0-or-later
 *
 * @package Algonquian_Command_Center
 */

defined( 'ABSPATH' ) || exit;

define( 'ALGQ_COMMAND_CENTER_VERSION', '1.2.0' );
define( 'ALGQ_COMMAND_CENTER_FILE', __FILE__ );
define( 'ALGQ_COMMAND_CENTER_DIR', plugin_dir_path( __FILE__ ) );
define( 'ALGQ_COMMAND_CENTER_URL', plugin_dir_url( __FILE__ ) );

define( 'ALGQ_COMMAND_CENTER_MIN_WP', '6.8' );
define( 'ALGQ_COMMAND_CENTER_MIN_PHP', '8.2' );

require_once ALGQ_COMMAND_CENTER_DIR . 'includes/class-security.php';
require_once ALGQ_COMMAND_CENTER_DIR . 'includes/class-activator.php';
require_once ALGQ_COMMAND_CENTER_DIR . 'includes/class-assets.php';
require_once ALGQ_COMMAND_CENTER_DIR . 'includes/class-health-monitor.php';
require_once ALGQ_COMMAND_CENTER_DIR . 'includes/class-data-provider.php';
require_once ALGQ_COMMAND_CENTER_DIR . 'includes/class-audit-provider.php';
require_once ALGQ_COMMAND_CENTER_DIR . 'includes/class-widgets.php';
require_once ALGQ_COMMAND_CENTER_DIR . 'includes/class-shortcodes.php';
require_once ALGQ_COMMAND_CENTER_DIR . 'includes/class-page-generator.php';
require_once ALGQ_COMMAND_CENTER_DIR . 'includes/class-report-controller.php';
require_once ALGQ_COMMAND_CENTER_DIR . 'includes/class-command-controller.php';
require_once ALGQ_COMMAND_CENTER_DIR . 'includes/class-admin.php';
require_once ALGQ_COMMAND_CENTER_DIR . 'includes/class-algq-command-center.php';

register_activation_hook( __FILE__, array( 'ALGQ_Command_Center_Activator', 'activate' ) );

add_action(
    'plugins_loaded',
    static function (): void {
        load_plugin_textdomain(
            'algq-command-center',
            false,
            dirname( plugin_basename( __FILE__ ) ) . '/languages'
        );

        if ( version_compare( PHP_VERSION, ALGQ_COMMAND_CENTER_MIN_PHP, '<' ) ) {
            add_action(
                'admin_notices',
                static function (): void {
                    echo '<div class="notice notice-error"><p>' . esc_html__( 'Algonquian Admin Command Center requires PHP 8.2 or newer.', 'algq-command-center' ) . '</p></div>';
                }
            );
            return;
        }

        $plugin = new ALGQ_Command_Center();
        $plugin->run();
    }
);
