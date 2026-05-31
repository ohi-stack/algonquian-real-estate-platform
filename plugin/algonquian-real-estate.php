<?php
/**
 * Plugin Name: Algonquian Real Estate Platform
 * Plugin URI: https://algonquianrealestate.com
 * Description: Acquisition, underwriting, buyer registration, offer generation, and command dashboard platform for Algonquian Real Estate LLC.
 * Version: 1.0.0-rc.1
 * Author: Onegodian | Algonquian Real Estate
 * Text Domain: algonquian-real-estate
 */

defined( 'ABSPATH' ) || exit;

define( 'ALGQ_RE_VERSION', '1.0.0-rc.1' );
define( 'ALGQ_RE_FILE', __FILE__ );
define( 'ALGQ_RE_PATH', plugin_dir_path( __FILE__ ) );
define( 'ALGQ_RE_URL', plugin_dir_url( __FILE__ ) );

require_once ALGQ_RE_PATH . 'includes/class-algq-database.php';
require_once ALGQ_RE_PATH . 'includes/class-algq-offer-engine.php';
require_once ALGQ_RE_PATH . 'includes/class-algq-shortcodes.php';
require_once ALGQ_RE_PATH . 'admin/class-algq-admin.php';

register_activation_hook( __FILE__, array( 'ALGQ_Database', 'activate' ) );

add_action( 'plugins_loaded', static function() {
	ALGQ_Shortcodes::init();
	ALGQ_Admin::init();
} );

add_action( 'wp_enqueue_scripts', static function() {
	wp_register_style( 'algq-re-frontend', ALGQ_RE_URL . 'assets/css/frontend.css', array(), ALGQ_RE_VERSION );
	wp_register_script( 'algq-re-frontend', ALGQ_RE_URL . 'assets/js/frontend.js', array(), ALGQ_RE_VERSION, true );
} );

add_action( 'admin_enqueue_scripts', static function() {
	wp_enqueue_style( 'algq-re-admin', ALGQ_RE_URL . 'assets/css/admin.css', array(), ALGQ_RE_VERSION );
} );
