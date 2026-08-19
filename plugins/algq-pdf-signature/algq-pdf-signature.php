<?php
/**
 * Plugin Name: Algonquian PDF & Signature Engine
 * Plugin URI: https://algonquianrealestate.com/technology/plugins/pdf-signature-engine/
 * Description: Generates protected transaction PDFs, maintains immutable document versions, and coordinates provider-neutral signature workflows for the Algonquian Real Estate platform.
 * Version: 2.0.1
 * Author: Onegodian | Algonquian Real Estate Technology Division
 * Author URI: https://algonquianrealestate.com/technology/
 * Text Domain: algq-pdf-signature
 * Domain Path: /languages
 * Requires at least: 6.8
 * Requires PHP: 8.2
 * License: Proprietary
 */

defined( 'ABSPATH' ) || exit;

define( 'ALGQ_PDF_SIGNATURE_VERSION', '2.0.1' );
define( 'ALGQ_PDF_SIGNATURE_SCHEMA_VERSION', '2.0.0' );
define( 'ALGQ_PDF_SIGNATURE_FILE', __FILE__ );
define( 'ALGQ_PDF_SIGNATURE_DIR', plugin_dir_path( __FILE__ ) );
define( 'ALGQ_PDF_SIGNATURE_URL', plugin_dir_url( __FILE__ ) );

require_once ALGQ_PDF_SIGNATURE_DIR . 'includes/class-algq-pdf-signature.php';
require_once ALGQ_PDF_SIGNATURE_DIR . 'includes/class-algq-pdf-archive.php';

register_activation_hook( __FILE__, array( 'ALGQ_PDF_Signature', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'ALGQ_PDF_Signature', 'deactivate' ) );
add_action( 'plugins_loaded', array( 'ALGQ_PDF_Signature', 'init' ), 20 );
add_action( 'plugins_loaded', array( 'ALGQ_PDF_Archive', 'init' ), 21 );
