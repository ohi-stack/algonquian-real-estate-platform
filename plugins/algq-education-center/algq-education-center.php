<?php
/**
 * Plugin Name: Algonquian Education Center
 * Plugin URI: https://algonquianrealestate.com/education
 * Description: Enterprise LMS, education tracks, digital product library, certification, tenant training, and platform training for Algonquian Real Estate.
 * Version: 1.0.0-enterprise-rc1
 * Author: Onegodian | Algonquian Real Estate
 * Text Domain: algq-education-center
 */

if (!defined('ABSPATH')) {
    exit;
}

define('ALGQ_EDU_VERSION', '1.0.0-enterprise-rc1');
define('ALGQ_EDU_FILE', __FILE__);
define('ALGQ_EDU_DIR', plugin_dir_path(__FILE__));
define('ALGQ_EDU_URL', plugin_dir_url(__FILE__));

$algq_edu_includes = array(
    'class-activator.php',
    'class-post-types.php',
    'class-shortcodes.php',
    'class-admin.php',
    'class-progress.php',
    'class-access-control.php',
    'class-woocommerce.php',
    'class-enrollment.php',
    'class-lms-advanced.php',
    'class-lms-dashboards.php',
    'class-certificate-pdf.php',
    'class-certificate-verification.php',
    'class-pdf-certificate-generator.php',
    'class-badge-gallery.php',
    'class-quiz-builder.php',
    'class-course-builder.php',
    'class-gamification.php',
    'class-gradebook.php',
    'class-assignment-engine.php',
    'class-email-notifications.php',
    'class-revenue-analytics.php',
    'class-rest-api.php',
    'class-mobile-api.php',
    'class-scorm-xapi.php',
    'class-learning-paths.php',
    'class-command-center-integration.php',
    'class-command-center-sync.php',
    'class-ce-credits.php',
    'class-multi-instructor.php',
    'class-white-label.php',
    'class-corporate-accounts.php',
    'class-audit-log.php',
    'class-role-capabilities.php',
    'class-data-export.php',
    'class-privacy-tools.php',
    'class-admin-settings-framework.php',
    'class-saas-licensing.php',
    'class-tenant-manager.php',
    'class-performance.php',
);

foreach ($algq_edu_includes as $algq_edu_file) {
    $algq_edu_path = ALGQ_EDU_DIR . 'includes/' . $algq_edu_file;
    if (file_exists($algq_edu_path)) {
        require_once $algq_edu_path;
    }
}

register_activation_hook(__FILE__, function () {
    if (class_exists('ALGQ_Education_Activator')) {
        ALGQ_Education_Activator::activate();
    }
    if (class_exists('ALGQ_Education_Audit_Log')) {
        ALGQ_Education_Audit_Log::create_table();
    }
    if (class_exists('ALGQ_Education_Role_Capabilities')) {
        ALGQ_Education_Role_Capabilities::install_roles();
    }
    flush_rewrite_rules();
});

register_deactivation_hook(__FILE__, function () {
    flush_rewrite_rules();
});

add_action('plugins_loaded', function () {
    $classes = array(
        'ALGQ_Education_Post_Types',
        'ALGQ_Education_Shortcodes',
        'ALGQ_Education_Admin',
        'ALGQ_Education_Progress',
        'ALGQ_Education_Access_Control',
        'ALGQ_Education_WooCommerce',
        'ALGQ_Education_Enrollment',
        'ALGQ_Education_LMS_Advanced',
        'ALGQ_Education_LMS_Dashboards',
        'ALGQ_Education_Certificate_PDF',
        'ALGQ_Education_Certificate_Verification',
        'ALGQ_Education_PDF_Certificate_Generator',
        'ALGQ_Education_Badge_Gallery',
        'ALGQ_Education_Quiz_Builder',
        'ALGQ_Education_Course_Builder',
        'ALGQ_Education_Gamification',
        'ALGQ_Education_Gradebook',
        'ALGQ_Education_Assignment_Engine',
        'ALGQ_Education_Email_Notifications',
        'ALGQ_Education_Revenue_Analytics',
        'ALGQ_Education_REST_API',
        'ALGQ_Education_Mobile_API',
        'ALGQ_Education_SCORM_XAPI',
        'ALGQ_Education_Learning_Paths',
        'ALGQ_Education_Command_Center_Integration',
        'ALGQ_Education_Command_Center_Sync',
        'ALGQ_Education_CE_Credits',
        'ALGQ_Education_Multi_Instructor',
        'ALGQ_Education_White_Label',
        'ALGQ_Education_Corporate_Accounts',
        'ALGQ_Education_Audit_Log',
        'ALGQ_Education_Role_Capabilities',
        'ALGQ_Education_Data_Export',
        'ALGQ_Education_Privacy_Tools',
        'ALGQ_Education_Admin_Settings_Framework',
        'ALGQ_Education_SaaS_Licensing',
        'ALGQ_Education_Tenant_Manager',
        'ALGQ_Education_Performance',
    );

    foreach ($classes as $class) {
        if (class_exists($class) && is_callable(array($class, 'init'))) {
            call_user_func(array($class, 'init'));
        }
    }
});

add_action('wp_enqueue_scripts', function () {
    wp_enqueue_style('algq-education', ALGQ_EDU_URL . 'assets/css/algq-education.css', array(), ALGQ_EDU_VERSION);
    wp_enqueue_script('algq-education', ALGQ_EDU_URL . 'assets/js/algq-education.js', array(), ALGQ_EDU_VERSION, true);
    wp_localize_script('algq-education', 'algqEducation', array(
        'ajaxUrl' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('algq_education_progress'),
        'enrollmentNonce' => wp_create_nonce('algq_education_enrollment'),
    ));
});

add_action('admin_enqueue_scripts', function ($hook) {
    if (strpos((string) $hook, 'algq-education') === false) {
        return;
    }
    wp_enqueue_style('algq-education-admin', ALGQ_EDU_URL . 'assets/css/algq-admin.css', array(), ALGQ_EDU_VERSION);
    wp_enqueue_script('algq-education-admin', ALGQ_EDU_URL . 'assets/js/algq-admin.js', array(), ALGQ_EDU_VERSION, true);
});
