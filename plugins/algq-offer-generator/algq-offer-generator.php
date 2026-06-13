<?php
/**
 * Plugin Name: Algonquian Offer Generator
 * Plugin URI: https://algonquianrealestate.com/plugin/offer-generator
 * Description: Generates acquisition offers and transaction documents from deal data, including purchase agreements, letters of intent, seller-financing offers, subject-to offers, and cash-offer summaries for Algonquian Real Estate acquisitions.
 * Version: 1.0.0
 * Author: Onegodian | Algonquian Real Estate
 * Text Domain: algq-offer-generator
 */

if (!defined('ABSPATH')) {
    exit;
}

define('ALGQ_OFFER_VERSION', '1.0.0');
define('ALGQ_OFFER_FILE', __FILE__);
define('ALGQ_OFFER_DIR', plugin_dir_path(__FILE__));
define('ALGQ_OFFER_URL', plugin_dir_url(__FILE__));

$algq_offer_includes = array(
    'class-activator.php',
    'class-post-types.php',
    'class-shortcodes.php',
    'class-offer-builder.php',
    'class-template-manager.php',
    'class-document-generator.php',
    'class-pdf-integration.php',
    'class-deal-integration.php',
    'class-automation-hooks.php',
    'class-admin.php',
    'class-audit-log.php',
    'class-role-capabilities.php',
    'class-rest-api.php',
    'class-settings.php',
);

foreach ($algq_offer_includes as $file) {
    $path = ALGQ_OFFER_DIR . 'includes/' . $file;
    if (file_exists($path)) {
        require_once $path;
    }
}

register_activation_hook(__FILE__, function () {
    if (class_exists('ALGQ_Offer_Activator')) {
        ALGQ_Offer_Activator::activate();
    }
    if (class_exists('ALGQ_Offer_Role_Capabilities')) {
        ALGQ_Offer_Role_Capabilities::install_roles();
    }
    if (class_exists('ALGQ_Offer_Audit_Log')) {
        ALGQ_Offer_Audit_Log::create_table();
    }
    flush_rewrite_rules();
});

register_deactivation_hook(__FILE__, function () {
    flush_rewrite_rules();
});

add_action('plugins_loaded', function () {
    $classes = array(
        'ALGQ_Offer_Post_Types',
        'ALGQ_Offer_Shortcodes',
        'ALGQ_Offer_Builder',
        'ALGQ_Offer_Template_Manager',
        'ALGQ_Offer_Document_Generator',
        'ALGQ_Offer_PDF_Integration',
        'ALGQ_Offer_Deal_Integration',
        'ALGQ_Offer_Automation_Hooks',
        'ALGQ_Offer_Admin',
        'ALGQ_Offer_Audit_Log',
        'ALGQ_Offer_Role_Capabilities',
        'ALGQ_Offer_REST_API',
        'ALGQ_Offer_Settings',
    );

    foreach ($classes as $class) {
        if (class_exists($class) && is_callable(array($class, 'init'))) {
            call_user_func(array($class, 'init'));
        }
    }
});

add_action('wp_enqueue_scripts', function () {
    wp_enqueue_style('algq-offer-generator', ALGQ_OFFER_URL . 'assets/css/offer-generator.css', array(), ALGQ_OFFER_VERSION);
    wp_enqueue_script('algq-offer-generator', ALGQ_OFFER_URL . 'assets/js/offer-generator.js', array(), ALGQ_OFFER_VERSION, true);
    wp_localize_script('algq-offer-generator', 'algqOfferGenerator', array(
        'ajaxUrl' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('algq_offer_generator'),
    ));
});

add_action('admin_enqueue_scripts', function ($hook) {
    if (strpos((string) $hook, 'algq-offer') === false) {
        return;
    }
    wp_enqueue_style('algq-offer-admin', ALGQ_OFFER_URL . 'assets/css/offer-admin.css', array(), ALGQ_OFFER_VERSION);
    wp_enqueue_script('algq-offer-admin', ALGQ_OFFER_URL . 'assets/js/offer-admin.js', array(), ALGQ_OFFER_VERSION, true);
});
