<?php

defined( 'ABSPATH' ) || exit;

final class ALGQ_Pipeline_Plugin {
    private static bool $booted = false;

    public static function boot(): void {
        if ( self::$booted ) { return; }
        self::$booted = true;
        ALGQ_Pipeline_Database::maybe_upgrade();
        ALGQ_Pipeline_Service::instance();
        ALGQ_Pipeline_REST::init();
        ALGQ_Pipeline_Shortcodes::init();
        if ( is_admin() ) { ALGQ_Pipeline_Admin::init(); }
        add_action( 'init', array( __CLASS__, 'load_textdomain' ) );
        add_action( 'admin_notices', array( __CLASS__, 'dependency_notice' ) );
        do_action( 'algq_pipeline_loaded', ALGQ_PIPELINE_VERSION );
    }

    public static function activate(): void {
        ALGQ_Pipeline_Database::install();
        ALGQ_Pipeline_Capabilities::install();
        self::defaults();
        self::create_pages();
        ALGQ_Pipeline_Service::instance();
        ALGQ_Pipeline_Migrator::run_legacy_import();
        update_option( 'algq_pipeline_version', ALGQ_PIPELINE_VERSION, false );
        flush_rewrite_rules();
    }

    public static function deactivate(): void { flush_rewrite_rules(); }
    public static function load_textdomain(): void { load_plugin_textdomain( 'algq-pipeline-crm', false, dirname( plugin_basename( ALGQ_PIPELINE_FILE ) ) . '/languages' ); }

    public static function dependency_notice(): void {
        if ( current_user_can( 'manage_options' ) && ! function_exists( 'algq_log_event' ) ) {
            echo '<div class="notice notice-warning"><p><strong>Algonquian Pipeline CRM:</strong> The shared Platform Plugin audit service was not detected. CRM operations remain available, but centralized audit integration is degraded.</p></div>';
        }
    }

    private static function defaults(): void {
        if ( false === get_option( 'algq_pipeline_settings', false ) ) {
            add_option( 'algq_pipeline_settings', array( 'cards_per_stage' => 50, 'delete_data_on_uninstall' => 'no' ), '', false );
        }
    }

    private static function create_pages(): void {
        $pages = array(
            'plugin/pipeline-crm' => array( 'Algonquian Pipeline CRM', self::page_content( 'Algonquian Pipeline CRM', 'Manage canonical deal records and the controlled acquisition lifecycle.', '[algq_pipeline_dashboard]' ) ),
            'plugin/pipeline-crm/start' => array( 'Getting Started With Pipeline CRM', self::page_content( 'Getting Started With the Algonquian Pipeline CRM', 'Configure assignments, review activity, and move opportunities through controlled stages.', '[algq_pipeline_dashboard]' ) ),
            'plugin/pipeline-crm/docs' => array( 'Pipeline CRM Documentation', self::page_content( 'Pipeline CRM Documentation', 'Administrator, workflow, security, REST API, and troubleshooting references.', '[algq_pipeline_activity]' ) ),
            'plugin/pipeline-crm/board' => array( 'Pipeline Board', self::page_content( 'Acquisition Pipeline Board', 'View and manage authorized opportunities from intake through closing.', '[algq_pipeline_board]' ) ),
        );
        $ids = get_option( 'algq_pipeline_page_ids', array() );
        foreach ( $pages as $path => $page ) {
            if ( get_page_by_path( $path ) ) { continue; }
            $segments = explode( '/', $path );
            $slug = array_pop( $segments );
            $parent = 0;
            $parent_path = '';
            foreach ( $segments as $segment ) {
                $parent_path = ltrim( $parent_path . '/' . $segment, '/' );
                $existing = get_page_by_path( $parent_path );
                if ( $existing ) { $parent = (int) $existing->ID; continue; }
                $parent = (int) wp_insert_post( array( 'post_title' => ucwords( str_replace( '-', ' ', $segment ) ), 'post_name' => $segment, 'post_parent' => $parent, 'post_status' => 'publish', 'post_type' => 'page', 'post_content' => '' ) );
            }
            $id = wp_insert_post( array( 'post_title' => $page[0], 'post_name' => $slug, 'post_parent' => $parent, 'post_status' => 'publish', 'post_type' => 'page', 'post_content' => $page[1] ) );
            if ( ! is_wp_error( $id ) ) { $ids[ $path ] = (int) $id; }
        }
        update_option( 'algq_pipeline_page_ids', $ids, false );
    }

    private static function page_content( string $title, string $description, string $shortcode ): string {
        return '[vc_row full_width="stretch_row_content"][vc_column][vc_column_text]<div class="algq-plugin-hero" style="max-width:1100px;margin:0 auto;padding:64px 24px;text-align:center;"><span style="letter-spacing:.08em;text-transform:uppercase;">Algonquian Real Estate Platform • Enterprise Operations</span><h1>' . esc_html( $title ) . '</h1><p>' . esc_html( $description ) . '</p></div>[/vc_column_text][/vc_column][/vc_row][vc_row][vc_column][vc_column_text]' . $shortcode . '[/vc_column_text][/vc_column][/vc_row]';
    }
}
