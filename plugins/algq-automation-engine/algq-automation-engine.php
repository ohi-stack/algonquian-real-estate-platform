<?php
/**
 * Plugin Name: Algonquian Automation Engine
 * Plugin URI: https://algonquianrealestate.com/technology/plugin-suite/
 * Description: Executes auditable trigger, condition, and action workflows across the Algonquian Real Estate platform.
 * Version: 2.0.0
 * Author: Onegodian
 * Author URI: https://algonquianrealestate.com
 * Text Domain: algq-automation-engine
 * Domain Path: /languages
 * Requires at least: 6.8
 * Requires PHP: 8.2
 * License: Proprietary
 */

defined( 'ABSPATH' ) || exit;

define( 'ALGQ_AUTOMATION_VERSION', '2.0.0' );
define( 'ALGQ_AUTOMATION_SCHEMA_VERSION', '2.0.0' );
define( 'ALGQ_AUTOMATION_FILE', __FILE__ );
define( 'ALGQ_AUTOMATION_PATH', plugin_dir_path( __FILE__ ) );
define( 'ALGQ_AUTOMATION_URL', plugin_dir_url( __FILE__ ) );
define( 'ALGQ_AUTOMATION_BASENAME', plugin_basename( __FILE__ ) );

$algq_automation_files = array(
    'includes/class-algq-automation-security.php',
    'includes/class-algq-automation-db.php',
    'includes/class-algq-automation-actions.php',
    'includes/class-algq-automation-engine.php',
    'includes/class-algq-automation-rest.php',
    'includes/class-algq-automation-pages.php',
    'includes/class-algq-automation-admin.php',
    'includes/class-algq-automation-activator.php',
);

foreach ( $algq_automation_files as $algq_automation_file ) {
    require_once ALGQ_AUTOMATION_PATH . $algq_automation_file;
}

register_activation_hook( __FILE__, array( 'ALGQ_Automation_Activator', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'ALGQ_Automation_Activator', 'deactivate' ) );

final class ALGQ_Automation_Plugin {
    private static ?self $instance = null;

    public static function instance(): self {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    private function __construct() {
        add_action( 'plugins_loaded', array( $this, 'bootstrap' ), 20 );
    }

    public function bootstrap(): void {
        load_plugin_textdomain(
            'algq-automation-engine',
            false,
            dirname( ALGQ_AUTOMATION_BASENAME ) . '/languages'
        );

        ALGQ_Automation_Activator::maybe_upgrade();
        ALGQ_Automation_Engine::register();
        ALGQ_Automation_REST::register();
        ALGQ_Automation_Pages::register_shortcodes();

        if ( is_admin() ) {
            ALGQ_Automation_Admin::register();
        }

        do_action(
            'algq_platform_register_plugin',
            array(
                'slug'               => 'algq-automation-engine',
                'version'            => ALGQ_AUTOMATION_VERSION,
                'schema_version'     => ALGQ_AUTOMATION_SCHEMA_VERSION,
                'capabilities'       => ALGQ_Automation_Security::capabilities(),
                'scheduled_jobs'     => array( 'algq_automation_process_queue' ),
                'rest_namespaces'    => array( 'algq/v1/automation' ),
                'health_callback'    => array( 'ALGQ_Automation_Engine', 'health' ),
                'administrative_url' => admin_url( 'admin.php?page=algq-automation' ),
            )
        );
    }
}

ALGQ_Automation_Plugin::instance();
