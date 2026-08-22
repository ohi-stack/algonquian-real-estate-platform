<?php
/**
 * Plugin Name: Algonquian Pipeline CRM
 * Plugin URI: https://algonquianrealestate.com/algonquian-pipeline-crm/
 * Description: Canonical deal records, controlled acquisition stages, Kanban workflow, assignments, notes, tasks, activity history, and closing status for the Algonquian Real Estate platform.
 * Version: 2.0.0
 * Requires at least: 6.5
 * Requires PHP: 8.1
 * Author: Algonquian Real Estate, LLC
 * Author URI: https://algonquianrealestate.com/technology/
 * Text Domain: algq-pipeline-crm
 * License: Proprietary
 */

defined( 'ABSPATH' ) || exit;

define( 'ALGQ_PIPELINE_VERSION', '2.0.0' );
define( 'ALGQ_PIPELINE_SCHEMA_VERSION', '2.0.0' );
define( 'ALGQ_PIPELINE_FILE', __FILE__ );
define( 'ALGQ_PIPELINE_DIR', plugin_dir_path( __FILE__ ) );
define( 'ALGQ_PIPELINE_URL', plugin_dir_url( __FILE__ ) );

require_once ALGQ_PIPELINE_DIR . 'includes/class-stages.php';
require_once ALGQ_PIPELINE_DIR . 'includes/class-database.php';
require_once ALGQ_PIPELINE_DIR . 'includes/class-capabilities.php';
require_once ALGQ_PIPELINE_DIR . 'includes/class-repository.php';
require_once ALGQ_PIPELINE_DIR . 'includes/class-service.php';
require_once ALGQ_PIPELINE_DIR . 'includes/class-migrator.php';
require_once ALGQ_PIPELINE_DIR . 'includes/class-rest.php';
require_once ALGQ_PIPELINE_DIR . 'includes/class-shortcodes.php';
require_once ALGQ_PIPELINE_DIR . 'includes/class-admin.php';
require_once ALGQ_PIPELINE_DIR . 'includes/class-plugin.php';

register_activation_hook( __FILE__, array( 'ALGQ_Pipeline_Plugin', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'ALGQ_Pipeline_Plugin', 'deactivate' ) );

add_action( 'plugins_loaded', array( 'ALGQ_Pipeline_Plugin', 'boot' ), 20 );
