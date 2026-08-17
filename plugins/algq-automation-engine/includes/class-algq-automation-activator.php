<?php

defined( 'ABSPATH' ) || exit;

final class ALGQ_Automation_Activator {
    public static function activate(): void {
        self::requirements();
        self::options();
        self::capabilities();
        ALGQ_Automation_DB::migrate();
        ALGQ_Automation_Pages::create_pages();
        ALGQ_Automation_Engine::schedule_next_run( 10 );
        update_option( 'algq_automation_version', ALGQ_AUTOMATION_VERSION, false );
        flush_rewrite_rules();
    }

    public static function deactivate(): void {
        wp_clear_scheduled_hook( 'algq_automation_process_queue' );
        flush_rewrite_rules();
    }

    public static function maybe_upgrade(): void {
        $version = (string) get_option( 'algq_automation_version', '0.0.0' );
        $schema  = (string) get_option( 'algq_automation_schema_version', '0.0.0' );

        if ( version_compare( $schema, ALGQ_AUTOMATION_SCHEMA_VERSION, '<' ) ) {
            ALGQ_Automation_DB::migrate();
        }

        if ( version_compare( $version, ALGQ_AUTOMATION_VERSION, '<' ) ) {
            self::options();
            self::capabilities();
            ALGQ_Automation_Pages::create_pages();
            update_option( 'algq_automation_version', ALGQ_AUTOMATION_VERSION, false );
        }
    }

    private static function requirements(): void {
        if ( version_compare( PHP_VERSION, '8.2', '<' ) ) {
            deactivate_plugins( ALGQ_AUTOMATION_BASENAME );
            wp_die( esc_html__( 'Algonquian Automation Engine 2.0.0 requires PHP 8.2 or newer.', 'algq-automation-engine' ) );
        }

        global $wp_version;
        if ( version_compare( $wp_version, '6.8', '<' ) ) {
            deactivate_plugins( ALGQ_AUTOMATION_BASENAME );
            wp_die( esc_html__( 'Algonquian Automation Engine 2.0.0 requires WordPress 6.8 or newer.', 'algq-automation-engine' ) );
        }
    }

    private static function options(): void {
        $existing = get_option( 'algq_automation_settings', array() );
        update_option(
            'algq_automation_settings',
            wp_parse_args(
                is_array( $existing ) ? $existing : array(),
                array(
                    'enabled'          => 1,
                    'logging_enabled'  => 1,
                    'queue_enabled'    => 1,
                    'batch_size'       => 10,
                    'default_attempts' => 3,
                    'dedupe_window'    => 300,
                    'delete_data_on_uninstall' => 0,
                )
            ),
            false
        );
    }

    private static function capabilities(): void {
        $role = get_role( 'administrator' );
        if ( ! $role ) {
            return;
        }

        foreach ( ALGQ_Automation_Security::capabilities() as $capability ) {
            $role->add_cap( $capability );
        }
    }
}
