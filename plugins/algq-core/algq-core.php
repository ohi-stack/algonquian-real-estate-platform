<?php
/**
 * Plugin Name: Algonquian Real Estate Core
 * Plugin URI: https://algonquianrealestate.com
 * Description: Platform core for Algonquian Real Estate modules. Provides roles, permissions, database services, REST framework, settings, activity logging, notifications, licensing, shared UI, and integration registry.
 * Version: 1.0.0
 * Author: Algonquian Real Estate, LLC
 * Text Domain: algq-core
 * Requires at least: 6.0
 * Requires PHP: 7.4
 */

if (!defined('ABSPATH')) { exit; }

define('ALGQ_CORE_VERSION', '1.0.0');
define('ALGQ_CORE_FILE', __FILE__);
define('ALGQ_CORE_PATH', plugin_dir_path(__FILE__));
define('ALGQ_CORE_URL', plugin_dir_url(__FILE__));
define('ALGQ_CORE_REST_NAMESPACE', 'algq/v1');

require_once ALGQ_CORE_PATH . 'includes/class-algq-activator.php';
require_once ALGQ_CORE_PATH . 'includes/class-algq-roles.php';
require_once ALGQ_CORE_PATH . 'includes/class-algq-db.php';
require_once ALGQ_CORE_PATH . 'includes/class-algq-settings.php';
require_once ALGQ_CORE_PATH . 'includes/class-algq-activity-log.php';
require_once ALGQ_CORE_PATH . 'includes/class-algq-notifications.php';
require_once ALGQ_CORE_PATH . 'includes/class-algq-integrations.php';
require_once ALGQ_CORE_PATH . 'includes/class-algq-licenses.php';
require_once ALGQ_CORE_PATH . 'includes/class-algq-rest.php';
require_once ALGQ_CORE_PATH . 'admin/class-algq-admin.php';
require_once ALGQ_CORE_PATH . 'includes/class-algq-core.php';

register_activation_hook(__FILE__, ['ALGQ_Activator', 'activate']);
register_deactivation_hook(__FILE__, ['ALGQ_Activator', 'deactivate']);

function algq_core(): ALGQ_Core { return ALGQ_Core::instance(); }
add_action('plugins_loaded', 'algq_core');

/**
 * Shared ARE admin presentation layer.
 * Loads only on Algonquian/ARE plugin screens so third-party WordPress admin UI is untouched.
 */
function algq_core_is_are_admin_screen(): bool {
    if (!is_admin()) { return false; }
    $screen = function_exists('get_current_screen') ? get_current_screen() : null;
    $id = $screen && isset($screen->id) ? strtolower((string) $screen->id) : '';
    $page = isset($_GET['page']) ? sanitize_key(wp_unslash($_GET['page'])) : '';
    $haystack = $id . ' ' . $page;
    return strpos($haystack, 'algq') !== false || strpos($haystack, 'algonquian') !== false || strpos($haystack, 'are-') !== false;
}

function algq_core_enqueue_are_admin_ui(): void {
    if (!algq_core_is_are_admin_screen()) { return; }
    wp_enqueue_style('algq-are-admin-ui', ALGQ_CORE_URL . 'assets/css/are-admin-ui.css', [], ALGQ_CORE_VERSION);
    wp_enqueue_script('algq-are-admin-ui', ALGQ_CORE_URL . 'assets/js/are-admin-ui.js', [], ALGQ_CORE_VERSION, true);
}
add_action('admin_enqueue_scripts', 'algq_core_enqueue_are_admin_ui', 100);

function algq_core_are_admin_body_class(string $classes): string {
    return algq_core_is_are_admin_screen() ? trim($classes . ' algq-are-admin') : $classes;
}
add_filter('admin_body_class', 'algq_core_are_admin_body_class');
