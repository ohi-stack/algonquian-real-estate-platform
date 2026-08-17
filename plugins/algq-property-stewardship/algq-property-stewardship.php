<?php
/**
 * Plugin Name: Algonquian Property Stewardship Services
 * Plugin URI: https://algonquianrealestate.com/property-stewardship-services/
 * Description: Owner-authorized property observation, visit reporting, vendor coordination, maintenance tracking, and secure client stewardship records.
 * Version: 1.0.0
 * Author: Onegodian | Algonquian Real Estate
 * Author URI: https://algonquianrealestate.com
 * Text Domain: algq-property-stewardship
 * Requires at least: 6.8
 * Requires PHP: 8.2
 * License: GPL-2.0-or-later
 */

defined( 'ABSPATH' ) || exit;

final class ALGQ_Property_Stewardship {
    public const VERSION = '1.0.0';
    public const CAP_MANAGE = 'manage_algq_stewardship';
    public const CAP_VIEW_PORTAL = 'view_algq_stewardship_portal';

    public static function init(): void {
        add_action( 'init', array( __CLASS__, 'register_post_types' ) );
        add_action( 'init', array( __CLASS__, 'register_meta' ) );
        add_action( 'init', array( __CLASS__, 'register_shortcodes' ) );
        add_action( 'admin_menu', array( __CLASS__, 'register_admin_menu' ) );
        add_action( 'plugins_loaded', array( __CLASS__, 'dependency_notice' ) );
    }

    public static function activate(): void {
        self::register_post_types();
        self::grant_capabilities();
        self::create_pages();
        update_option( 'algq_property_stewardship_version', self::VERSION );
        flush_rewrite_rules( false );
        do_action( 'algq_plugin_registered', 'algq-property-stewardship', self::VERSION );
    }

    public static function deactivate(): void {
        flush_rewrite_rules( false );
    }

    public static function dependency_notice(): void {
        if ( class_exists( 'ALGQ_Platform' ) || defined( 'ALGQ_PLATFORM_VERSION' ) ) {
            return;
        }
        add_action( 'admin_notices', static function (): void {
            if ( current_user_can( 'activate_plugins' ) ) {
                echo '<div class="notice notice-warning"><p>' . esc_html__( 'Property Stewardship is designed to operate with the Algonquian Real Estate Platform Plugin. Core WordPress record functions remain available, but shared audit, mail, and secure-document integrations may be unavailable.', 'algq-property-stewardship' ) . '</p></div>';
            }
        } );
    }

    public static function grant_capabilities(): void {
        $admin = get_role( 'administrator' );
        if ( $admin ) {
            $admin->add_cap( self::CAP_MANAGE );
            $admin->add_cap( self::CAP_VIEW_PORTAL );
        }
        foreach ( array( 'subscriber', 'customer', 'algq_buyer' ) as $role_name ) {
            $role = get_role( $role_name );
            if ( $role ) {
                $role->add_cap( self::CAP_VIEW_PORTAL );
            }
        }
    }

    private static function post_type_caps(): array {
        return array(
            'edit_post' => self::CAP_MANAGE,
            'read_post' => self::CAP_MANAGE,
            'delete_post' => self::CAP_MANAGE,
            'edit_posts' => self::CAP_MANAGE,
            'edit_others_posts' => self::CAP_MANAGE,
            'publish_posts' => self::CAP_MANAGE,
            'read_private_posts' => self::CAP_MANAGE,
            'delete_posts' => self::CAP_MANAGE,
            'delete_private_posts' => self::CAP_MANAGE,
            'delete_published_posts' => self::CAP_MANAGE,
            'delete_others_posts' => self::CAP_MANAGE,
            'edit_private_posts' => self::CAP_MANAGE,
            'edit_published_posts' => self::CAP_MANAGE,
            'create_posts' => self::CAP_MANAGE,
        );
    }

    public static function register_post_types(): void {
        foreach ( array(
            'algq_stewardship' => array( 'Stewardship Clients', 'Stewardship Client', array( 'title', 'editor' ) ),
            'algq_steward_visit' => array( 'Property Visits', 'Property Visit', array( 'title', 'editor' ) ),
            'algq_steward_vendor' => array( 'Stewardship Vendors', 'Stewardship Vendor', array( 'title', 'editor' ) ),
        ) as $post_type => $definition ) {
            register_post_type( $post_type, array(
                'labels' => array( 'name' => __( $definition[0], 'algq-property-stewardship' ), 'singular_name' => __( $definition[1], 'algq-property-stewardship' ) ),
                'public' => false,
                'show_ui' => true,
                'show_in_menu' => false,
                'show_in_rest' => false,
                'supports' => $definition[2],
                'map_meta_cap' => false,
                'capabilities' => self::post_type_caps(),
                'exclude_from_search' => true,
                'publicly_queryable' => false,
            ) );
        }
    }

    public static function register_meta(): void {
        $admin_auth = static fn(): bool => current_user_can( self::CAP_MANAGE );
        foreach ( array( 'algq_stewardship', 'algq_steward_visit' ) as $post_type ) {
            register_post_meta( $post_type, '_algq_steward_owner_user_id', array( 'type' => 'integer', 'single' => true, 'show_in_rest' => false, 'sanitize_callback' => 'absint', 'auth_callback' => $admin_auth ) );
        }
        register_post_meta( 'algq_stewardship', '_algq_steward_property_address', array( 'type' => 'string', 'single' => true, 'show_in_rest' => false, 'sanitize_callback' => 'sanitize_text_field', 'auth_callback' => $admin_auth ) );
        register_post_meta( 'algq_stewardship', '_algq_steward_service_level', array( 'type' => 'string', 'single' => true, 'show_in_rest' => false, 'sanitize_callback' => array( __CLASS__, 'sanitize_service_level' ), 'auth_callback' => $admin_auth ) );
        register_post_meta( 'algq_stewardship', '_algq_steward_authorization_status', array( 'type' => 'string', 'single' => true, 'show_in_rest' => false, 'sanitize_callback' => array( __CLASS__, 'sanitize_authorization_status' ), 'auth_callback' => $admin_auth ) );
        register_post_meta( 'algq_steward_visit', '_algq_steward_client_id', array( 'type' => 'integer', 'single' => true, 'show_in_rest' => false, 'sanitize_callback' => 'absint', 'auth_callback' => $admin_auth ) );
        register_post_meta( 'algq_steward_visit', '_algq_steward_visit_date', array( 'type' => 'string', 'single' => true, 'show_in_rest' => false, 'sanitize_callback' => 'sanitize_text_field', 'auth_callback' => $admin_auth ) );
        register_post_meta( 'algq_steward_visit', '_algq_steward_document_ids', array( 'type' => 'array', 'single' => true, 'show_in_rest' => false, 'sanitize_callback' => array( __CLASS__, 'sanitize_id_array' ), 'auth_callback' => $admin_auth ) );
    }

    public static function sanitize_service_level( mixed $value ): string {
        $value = sanitize_key( (string) $value );
        return in_array( $value, array( 'property_watch', 'active_stewardship', 'transition_stewardship' ), true ) ? $value : 'property_watch';
    }

    public static function sanitize_authorization_status( mixed $value ): string {
        $value = sanitize_key( (string) $value );
        return in_array( $value, array( 'pending', 'active', 'paused', 'ended' ), true ) ? $value : 'pending';
    }

    public static function sanitize_id_array( mixed $value ): array {
        return array_values( array_filter( array_map( 'absint', (array) $value ) ) );
    }

    public static function register_shortcodes(): void {
        add_shortcode( 'algq_property_stewardship', array( __CLASS__, 'render_service_page' ) );
        add_shortcode( 'algq_stewardship_portal', array( __CLASS__, 'render_portal' ) );
    }

    public static function register_admin_menu(): void {
        add_menu_page( __( 'Property Stewardship', 'algq-property-stewardship' ), __( 'Stewardship', 'algq-property-stewardship' ), self::CAP_MANAGE, 'algq-property-stewardship', array( __CLASS__, 'render_admin_dashboard' ), 'dashicons-shield-alt', 27 );
        add_submenu_page( 'algq-property-stewardship', __( 'Clients', 'algq-property-stewardship' ), __( 'Clients', 'algq-property-stewardship' ), self::CAP_MANAGE, 'edit.php?post_type=algq_stewardship' );
        add_submenu_page( 'algq-property-stewardship', __( 'Property Visits', 'algq-property-stewardship' ), __( 'Property Visits', 'algq-property-stewardship' ), self::CAP_MANAGE, 'edit.php?post_type=algq_steward_visit' );
        add_submenu_page( 'algq-property-stewardship', __( 'Vendors', 'algq-property-stewardship' ), __( 'Vendors', 'algq-property-stewardship' ), self::CAP_MANAGE, 'edit.php?post_type=algq_steward_vendor' );
    }

    private static function service_cards(): array {
        return array(
            'Property Watch' => 'Scheduled exterior observations, documented condition summaries, and notification of visible concerns.',
            'Active Stewardship' => 'Maintenance scheduling, vendor coordination, seasonal oversight, storm observations, and documented follow-up.',
            'Transition Stewardship' => 'Property-readiness planning, clean-out and repair coordination, document organization, and owner-directed professional referrals.',
        );
    }

    public static function render_service_page(): string {
        ob_start(); ?>
        <section class="algq-stewardship-service"><p><strong><?php esc_html_e( 'Property Coordination • Owner-Directed Services', 'algq-property-stewardship' ); ?></strong></p><h2><?php esc_html_e( 'Property Stewardship Services', 'algq-property-stewardship' ); ?></h2><p><?php esc_html_e( 'Algonquian Real Estate provides structured property observation, communication, and service coordination under written owner authorization.', 'algq-property-stewardship' ); ?></p><div><?php foreach ( self::service_cards() as $title => $copy ) : ?><article><h3><?php echo esc_html( $title ); ?></h3><p><?php echo esc_html( $copy ); ?></p></article><?php endforeach; ?></div><h3><?php esc_html_e( 'Defined Service Boundaries', 'algq-property-stewardship' ); ?></h3><p><?php esc_html_e( 'These services do not constitute legal advice, estate planning, fiduciary decision-making, guardianship, insurance adjustment, licensed inspection, security services, caregiving, or guaranteed prevention of loss.', 'algq-property-stewardship' ); ?></p></section>
        <?php return (string) ob_get_clean();
    }

    public static function render_portal(): string {
        if ( ! is_user_logged_in() ) {
            return '<p>' . esc_html__( 'Please sign in to access stewardship records.', 'algq-property-stewardship' ) . '</p>';
        }
        if ( ! current_user_can( self::CAP_VIEW_PORTAL ) && ! current_user_can( self::CAP_MANAGE ) ) {
            return '<p>' . esc_html__( 'This account is not authorized for the stewardship portal.', 'algq-property-stewardship' ) . '</p>';
        }
        $user_id = get_current_user_id();
        $clients = get_posts( array(
            'post_type' => 'algq_stewardship',
            'post_status' => array( 'publish', 'private' ),
            'numberposts' => 100,
            'meta_query' => array( array( 'key' => '_algq_steward_owner_user_id', 'value' => $user_id, 'compare' => '=', 'type' => 'NUMERIC' ) ),
        ) );
        ob_start();
        echo '<section class="algq-stewardship-portal"><h2>' . esc_html__( 'Property Stewardship Portal', 'algq-property-stewardship' ) . '</h2>';
        if ( empty( $clients ) ) {
            echo '<p>' . esc_html__( 'No stewardship properties are authorized for this account.', 'algq-property-stewardship' ) . '</p></section>';
            return (string) ob_get_clean();
        }
        foreach ( $clients as $client ) {
            $address = (string) get_post_meta( $client->ID, '_algq_steward_property_address', true );
            $service = (string) get_post_meta( $client->ID, '_algq_steward_service_level', true );
            $status  = (string) get_post_meta( $client->ID, '_algq_steward_authorization_status', true );
            echo '<article><h3>' . esc_html( $address ?: get_the_title( $client ) ) . '</h3><p>' . esc_html( ucwords( str_replace( '_', ' ', $service ) ) ) . ' • ' . esc_html( ucfirst( $status ) ) . '</p>';
            self::render_client_visits( $client->ID, $user_id );
            echo '</article>';
        }
        echo '</section>';
        return (string) ob_get_clean();
    }

    private static function render_client_visits( int $client_id, int $user_id ): void {
        $visits = get_posts( array(
            'post_type' => 'algq_steward_visit',
            'post_status' => array( 'publish', 'private' ),
            'numberposts' => 50,
            'meta_query' => array(
                'relation' => 'AND',
                array( 'key' => '_algq_steward_client_id', 'value' => $client_id, 'compare' => '=', 'type' => 'NUMERIC' ),
                array( 'key' => '_algq_steward_owner_user_id', 'value' => $user_id, 'compare' => '=', 'type' => 'NUMERIC' ),
            ),
        ) );
        if ( empty( $visits ) ) {
            echo '<p>' . esc_html__( 'No visit reports are available yet.', 'algq-property-stewardship' ) . '</p>';
            return;
        }
        echo '<ul>';
        foreach ( $visits as $visit ) {
            $date = (string) get_post_meta( $visit->ID, '_algq_steward_visit_date', true );
            echo '<li><strong>' . esc_html( $date ?: get_the_date( '', $visit ) ) . '</strong> — ' . esc_html( wp_trim_words( wp_strip_all_tags( $visit->post_content ), 35 ) );
            self::render_secure_documents( $visit->ID, $user_id );
            echo '</li>';
        }
        echo '</ul>';
    }

    private static function render_secure_documents( int $visit_id, int $user_id ): void {
        $document_ids = self::sanitize_id_array( get_post_meta( $visit_id, '_algq_steward_document_ids', true ) );
        if ( empty( $document_ids ) ) {
            return;
        }
        echo '<ul>';
        foreach ( $document_ids as $document_id ) {
            $url = apply_filters( 'algq_secure_document_url', '', $document_id, $user_id, 'property_stewardship' );
            if ( $url ) {
                echo '<li><a href="' . esc_url( $url ) . '">' . esc_html( sprintf( __( 'Protected document #%d', 'algq-property-stewardship' ), $document_id ) ) . '</a></li>';
            }
        }
        echo '</ul>';
    }

    public static function render_admin_dashboard(): void {
        if ( ! current_user_can( self::CAP_MANAGE ) ) {
            wp_die( esc_html__( 'Insufficient permissions.', 'algq-property-stewardship' ), '', array( 'response' => 403 ) );
        }
        echo '<div class="wrap"><h1>' . esc_html__( 'Property Stewardship Services', 'algq-property-stewardship' ) . '</h1><p>' . esc_html__( 'Owner-authorized property observations, visit reports, vendor coordination, maintenance scheduling, and transition support.', 'algq-property-stewardship' ) . '</p><ul>';
        foreach ( array( 'Clients' => 'algq_stewardship', 'Visits' => 'algq_steward_visit', 'Vendors' => 'algq_steward_vendor' ) as $label => $post_type ) {
            $count = wp_count_posts( $post_type );
            echo '<li><strong>' . esc_html( $label ) . ':</strong> ' . esc_html( (string) ( $count->publish ?? 0 ) ) . '</li>';
        }
        echo '</ul></div>';
    }

    private static function create_pages(): void {
        $pages = array(
            'property-stewardship-services' => array( 'Property Stewardship Services', '[algq_property_stewardship]' ),
            'property-stewardship-portal' => array( 'Property Stewardship Portal', '[algq_stewardship_portal]' ),
        );
        foreach ( $pages as $slug => $definition ) {
            if ( get_page_by_path( $slug, OBJECT, 'page' ) ) {
                continue;
            }
            wp_insert_post( array(
                'post_title' => $definition[0], 'post_name' => $slug,
                'post_content' => "[vc_row][vc_column][vc_column_text]\n{$definition[1]}\n[/vc_column_text][/vc_column][/vc_row]",
                'post_status' => 'publish', 'post_type' => 'page',
            ) );
        }
    }
}

register_activation_hook( __FILE__, array( 'ALGQ_Property_Stewardship', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'ALGQ_Property_Stewardship', 'deactivate' ) );
ALGQ_Property_Stewardship::init();
