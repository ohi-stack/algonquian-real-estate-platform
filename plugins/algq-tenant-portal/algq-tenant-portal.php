<?php
/**
 * Plugin Name: Algonquian Tenant Portal
 * Plugin URI: https://algonquianrealestate.com/tenant-portal
 * Description: Tenant-facing portal for rent payments, lease access, rental applications, maintenance requests, tenant documents, and resident communication for Algonquian Real Estate.
 * Version: 1.0.0
 * Author: Onegodian | Algonquian Real Estate
 * Text Domain: algq-tenant-portal
 */

if (!defined('ABSPATH')) {
    exit;
}

define('ALGQ_TENANT_VERSION', '1.0.0');
define('ALGQ_TENANT_FILE', __FILE__);
define('ALGQ_TENANT_DIR', plugin_dir_path(__FILE__));
define('ALGQ_TENANT_URL', plugin_dir_url(__FILE__));

$algq_tenant_includes = array(
    'class-activator.php',
    'class-post-types.php',
    'class-shortcodes.php',
    'class-admin.php',
    'class-role-capabilities.php',
    'class-rent-payments.php',
    'class-lease-manager.php',
    'class-maintenance.php',
    'class-applications.php',
    'class-document-vault.php',
    'class-audit-log.php',
    'class-settings.php',
);

foreach ($algq_tenant_includes as $file) {
    $path = ALGQ_TENANT_DIR . 'includes/' . $file;
    if (file_exists($path)) {
        require_once $path;
    }
}

register_activation_hook(__FILE__, function () {
    if (class_exists('ALGQ_Tenant_Activator')) {
        ALGQ_Tenant_Activator::activate();
    }
    if (class_exists('ALGQ_Tenant_Role_Capabilities')) {
        ALGQ_Tenant_Role_Capabilities::install_roles();
    }
    if (class_exists('ALGQ_Tenant_Audit_Log')) {
        ALGQ_Tenant_Audit_Log::create_table();
    }
    flush_rewrite_rules();
});

register_deactivation_hook(__FILE__, function () {
    flush_rewrite_rules();
});

add_action('plugins_loaded', function () {
    $classes = array(
        'ALGQ_Tenant_Post_Types',
        'ALGQ_Tenant_Shortcodes',
        'ALGQ_Tenant_Admin',
        'ALGQ_Tenant_Role_Capabilities',
        'ALGQ_Tenant_Rent_Payments',
        'ALGQ_Tenant_Lease_Manager',
        'ALGQ_Tenant_Maintenance',
        'ALGQ_Tenant_Applications',
        'ALGQ_Tenant_Document_Vault',
        'ALGQ_Tenant_Audit_Log',
        'ALGQ_Tenant_Settings',
    );
    foreach ($classes as $class) {
        if (class_exists($class) && is_callable(array($class, 'init'))) {
            call_user_func(array($class, 'init'));
        }
    }
});

add_action('wp_enqueue_scripts', function () {
    wp_enqueue_style('algq-tenant-portal', ALGQ_TENANT_URL . 'assets/css/tenant-portal.css', array(), ALGQ_TENANT_VERSION);
    wp_enqueue_script('algq-tenant-portal', ALGQ_TENANT_URL . 'assets/js/tenant-portal.js', array(), ALGQ_TENANT_VERSION, true);
    wp_localize_script('algq-tenant-portal', 'algqTenantPortal', array(
        'ajaxUrl' => admin_url('admin-ajax.php'),
        'maintenanceNonce' => wp_create_nonce('algq_submit_maintenance'),
        'applicationNonce' => wp_create_nonce('algq_submit_application'),
    ));
});

add_action('admin_enqueue_scripts', function ($hook) {
    if (strpos((string) $hook, 'algq-tenant') === false) {
        return;
    }
    wp_enqueue_style('algq-tenant-admin', ALGQ_TENANT_URL . 'assets/css/tenant-admin.css', array(), ALGQ_TENANT_VERSION);
    wp_enqueue_script('algq-tenant-admin', ALGQ_TENANT_URL . 'assets/js/tenant-admin.js', array(), ALGQ_TENANT_VERSION, true);
});
