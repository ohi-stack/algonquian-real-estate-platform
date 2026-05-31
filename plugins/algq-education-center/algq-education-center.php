<?php
/**
 * Plugin Name: Algonquian Education Center
 * Plugin URI: https://algonquianrealestate.com/education
 * Description: LMS, education tracks, digital product library, and platform training for Algonquian Real Estate.
 * Version: 1.0.0-rc1
 * Author: Onegodian | Algonquian Real Estate
 * Text Domain: algq-education-center
 */

if (!defined('ABSPATH')) {
    exit;
}

define('ALGQ_EDU_VERSION', '1.0.0-rc1');
define('ALGQ_EDU_FILE', __FILE__);
define('ALGQ_EDU_DIR', plugin_dir_path(__FILE__));
define('ALGQ_EDU_URL', plugin_dir_url(__FILE__));

require_once ALGQ_EDU_DIR . 'includes/class-activator.php';
require_once ALGQ_EDU_DIR . 'includes/class-post-types.php';
require_once ALGQ_EDU_DIR . 'includes/class-shortcodes.php';
require_once ALGQ_EDU_DIR . 'includes/class-admin.php';

register_activation_hook(__FILE__, array('ALGQ_Education_Activator', 'activate'));

add_action('plugins_loaded', function () {
    ALGQ_Education_Post_Types::init();
    ALGQ_Education_Shortcodes::init();
    ALGQ_Education_Admin::init();
});

add_action('wp_enqueue_scripts', function () {
    wp_enqueue_style('algq-education', ALGQ_EDU_URL . 'assets/css/algq-education.css', array(), ALGQ_EDU_VERSION);
    wp_enqueue_script('algq-education', ALGQ_EDU_URL . 'assets/js/algq-education.js', array(), ALGQ_EDU_VERSION, true);
});

add_action('admin_enqueue_scripts', function ($hook) {
    if (strpos((string) $hook, 'algq-education') === false) {
        return;
    }
    wp_enqueue_style('algq-education-admin', ALGQ_EDU_URL . 'assets/css/algq-admin.css', array(), ALGQ_EDU_VERSION);
    wp_enqueue_script('algq-education-admin', ALGQ_EDU_URL . 'assets/js/algq-admin.js', array(), ALGQ_EDU_VERSION, true);
});
