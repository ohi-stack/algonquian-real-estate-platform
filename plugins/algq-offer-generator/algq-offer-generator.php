<?php
/**
 * Plugin Name: Algonquian Offer Generator
 * Plugin URI: https://algonquianrealestate.com/plugin/offer-generator
 * Description: Creates versioned acquisition offers from approved deal and underwriting data, manages review and approval, and delegates document and PDF execution to the Algonquian platform services.
 * Version: 2.0.0
 * Author: Onegodian | Algonquian Real Estate
 * Author URI: https://algonquianrealestate.com
 * Text Domain: algq-offer-generator
 * Requires at least: 6.4
 * Requires PHP: 8.1
 * License: Proprietary
 */

defined( 'ABSPATH' ) || exit;

define( 'ALGQ_OFFER_VERSION', '2.0.0' );
define( 'ALGQ_OFFER_DB_VERSION', '2.0.0' );
define( 'ALGQ_OFFER_FILE', __FILE__ );
define( 'ALGQ_OFFER_DIR', plugin_dir_path( __FILE__ ) );
define( 'ALGQ_OFFER_URL', plugin_dir_url( __FILE__ ) );

$algq_offer_includes = array(
    'class-role-capabilities.php',
    'class-activator.php',
    'class-post-types.php',
    'class-offer-service.php',
    'class-shortcodes.php',
    'class-offer-builder.php',
    'class-template-manager.php',
    'class-document-generator.php',
    'class-pdf-integration.php',
    'class-deal-integration.php',
    'class-automation-hooks.php',
    'class-admin.php',
    'class-audit-log.php',
    'class-rest-api.php',
    'class-settings.php',
);

foreach ( $algq_offer_includes as $file ) {
    $path = ALGQ_OFFER_DIR . 'includes/' . $file;
    if ( is_readable( $path ) ) {
        require_once $path;
    }
}

function algq_offer_activate(): void {
    if ( version_compare( PHP_VERSION, '8.1', '<' ) ) {
        deactivate_plugins( plugin_basename( ALGQ_OFFER_FILE ) );
        wp_die( esc_html__( 'Algonquian Offer Generator requires PHP 8.1 or newer.', 'algq-offer-generator' ) );
    }

    if ( class_exists( 'ALGQ_Offer_Activator' ) ) {
        ALGQ_Offer_Activator::activate();
    }

    flush_rewrite_rules();
}
register_activation_hook( __FILE__, 'algq_offer_activate' );

function algq_offer_deactivate(): void {
    flush_rewrite_rules();
}
register_deactivation_hook( __FILE__, 'algq_offer_deactivate' );

function algq_offer_init(): void {
    load_plugin_textdomain( 'algq-offer-generator', false, dirname( plugin_basename( ALGQ_OFFER_FILE ) ) . '/languages' );

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

    foreach ( $classes as $class ) {
        if ( class_exists( $class ) && is_callable( array( $class, 'init' ) ) ) {
            call_user_func( array( $class, 'init' ) );
        }
    }

    if ( get_option( 'algq_offer_db_version' ) !== ALGQ_OFFER_DB_VERSION && class_exists( 'ALGQ_Offer_Activator' ) ) {
        ALGQ_Offer_Activator::upgrade();
    }
}
add_action( 'plugins_loaded', 'algq_offer_init', 20 );

function algq_offer_dependency_notice(): void {
    if ( ! current_user_can( 'activate_plugins' ) ) {
        return;
    }

    $missing = array();
    if ( ! defined( 'ALGQ_PLATFORM_VERSION' ) && ! class_exists( 'ALGQ_Platform' ) && ! function_exists( 'algq_log_event' ) ) {
        $missing[] = __( 'Algonquian Real Estate Platform Plugin', 'algq-offer-generator' );
    }
    if ( ! post_type_exists( 'algq_deal' ) && ! function_exists( 'algq_get_deal' ) ) {
        $missing[] = __( 'Algonquian Pipeline CRM', 'algq-offer-generator' );
    }

    if ( $missing ) {
        printf(
            '<div class="notice notice-warning"><p><strong>%1$s</strong> %2$s</p></div>',
            esc_html__( 'Algonquian Offer Generator:', 'algq-offer-generator' ),
            esc_html( sprintf( __( 'limited mode is active because these dependencies were not detected: %s.', 'algq-offer-generator' ), implode( ', ', $missing ) ) )
        );
    }
}
add_action( 'admin_notices', 'algq_offer_dependency_notice' );

function algq_offer_enqueue_front_assets(): void {
    wp_enqueue_style( 'algq-offer-generator', ALGQ_OFFER_URL . 'assets/css/offer-generator.css', array(), ALGQ_OFFER_VERSION );
    wp_enqueue_script( 'algq-offer-generator', ALGQ_OFFER_URL . 'assets/js/offer-generator.js', array(), ALGQ_OFFER_VERSION, true );
    wp_localize_script(
        'algq-offer-generator',
        'algqOfferGenerator',
        array(
            'ajaxUrl' => admin_url( 'admin-ajax.php' ),
            'nonce'   => wp_create_nonce( 'algq_offer_generator' ),
        )
    );
}
add_action( 'wp_enqueue_scripts', 'algq_offer_enqueue_front_assets' );

function algq_offer_enqueue_admin_assets( string $hook ): void {
    if ( false === strpos( $hook, 'algq-offer' ) && false === strpos( $hook, 'algq_offer' ) ) {
        return;
    }

    wp_enqueue_style( 'algq-offer-admin', ALGQ_OFFER_URL . 'assets/css/offer-admin.css', array(), ALGQ_OFFER_VERSION );
    wp_enqueue_script( 'algq-offer-admin', ALGQ_OFFER_URL . 'assets/js/offer-admin.js', array(), ALGQ_OFFER_VERSION, true );
}
add_action( 'admin_enqueue_scripts', 'algq_offer_enqueue_admin_assets' );
