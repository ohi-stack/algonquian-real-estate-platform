<?php
/**
 * Plugin Name: Algonquian Document Library
 * Plugin URI: https://algonquianrealestate.com/algonquian-document-library/
 * Description: Secure institutional document repository, version control, access requests, protected downloads, and transaction package assembly for the Algonquian Real Estate platform.
 * Version: 2.0.0
 * Requires at least: 6.8
 * Requires PHP: 8.2
 * Author: Algonquian Real Estate, LLC
 * Author URI: https://algonquianrealestate.com/technology/
 * Text Domain: algq-document-library
 * Domain Path: /languages
 * License: Proprietary
 */

defined( 'ABSPATH' ) || exit;

define( 'ALGQ_DOC_LIB_VERSION', '2.0.0' );
define( 'ALGQ_DOC_LIB_SCHEMA_VERSION', '2.0.0' );
define( 'ALGQ_DOC_LIB_FILE', __FILE__ );
define( 'ALGQ_DOC_LIB_DIR', plugin_dir_path( __FILE__ ) );
define( 'ALGQ_DOC_LIB_URL', plugin_dir_url( __FILE__ ) );
define( 'ALGQ_DOC_LIB_BASENAME', plugin_basename( __FILE__ ) );

require_once ALGQ_DOC_LIB_DIR . 'includes/class-algq-document-library-activator.php';
require_once ALGQ_DOC_LIB_DIR . 'includes/class-algq-document-library-storage.php';
require_once ALGQ_DOC_LIB_DIR . 'includes/class-algq-document-library-requests.php';
require_once ALGQ_DOC_LIB_DIR . 'includes/class-algq-document-library.php';

register_activation_hook( __FILE__, array( 'ALGQ_Document_Library_Activator', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'ALGQ_Document_Library_Activator', 'deactivate' ) );

add_action( 'plugins_loaded', array( 'ALGQ_Document_Library', 'boot' ) );
