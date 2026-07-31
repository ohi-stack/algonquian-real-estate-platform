<?php
/**
 * Activation and lifecycle routines.
 */

defined( 'ABSPATH' ) || exit;

final class ALGQ_Document_Library_Activator {

    /**
     * Run activation and idempotent upgrades.
     */
    public static function activate(): void {
        if ( version_compare( PHP_VERSION, '8.2', '<' ) ) {
            deactivate_plugins( ALGQ_DOC_LIB_BASENAME );
            wp_die( esc_html__( 'Algonquian Document Library requires PHP 8.2 or newer.', 'algq-document-library' ) );
        }

        self::create_tables();
        self::grant_capabilities();
        ALGQ_Document_Library_Storage::ensure_private_directory();

        ALGQ_Document_Library::register_content_types();
        ALGQ_Document_Library::create_generated_pages();
        ALGQ_Document_Library::seed_document_records();

        update_option( 'algq_document_library_schema_version', ALGQ_DOC_LIB_SCHEMA_VERSION, false );
        update_option( 'algq_document_library_version', ALGQ_DOC_LIB_VERSION, false );
        flush_rewrite_rules();
    }

    /**
     * Flush routes only. Operational records remain intact.
     */
    public static function deactivate(): void {
        flush_rewrite_rules();
    }

    /**
     * Create durable request and download-audit tables.
     */
    public static function create_tables(): void {
        global $wpdb;

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        $charset_collate = $wpdb->get_charset_collate();
        $requests_table  = $wpdb->prefix . 'algq_document_requests';
        $downloads_table = $wpdb->prefix . 'algq_document_downloads';

        $requests_sql = "CREATE TABLE {$requests_table} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            request_uuid char(36) NOT NULL,
            requester_user_id bigint(20) unsigned NOT NULL DEFAULT 0,
            requester_name varchar(190) NOT NULL,
            requester_email varchar(190) NOT NULL,
            requester_company varchar(190) NOT NULL DEFAULT '',
            package_key varchar(100) NOT NULL,
            requested_document_id bigint(20) unsigned NOT NULL DEFAULT 0,
            reason text NOT NULL,
            consent_version varchar(50) NOT NULL,
            status varchar(30) NOT NULL DEFAULT 'pending',
            reviewer_user_id bigint(20) unsigned NOT NULL DEFAULT 0,
            reviewer_note text NOT NULL,
            ip_hash char(64) NOT NULL DEFAULT '',
            user_agent_hash char(64) NOT NULL DEFAULT '',
            created_at datetime NOT NULL,
            updated_at datetime NOT NULL,
            PRIMARY KEY  (id),
            UNIQUE KEY request_uuid (request_uuid),
            KEY requester_email (requester_email),
            KEY package_key (package_key),
            KEY status (status),
            KEY requested_document_id (requested_document_id),
            KEY created_at (created_at)
        ) {$charset_collate};";

        $downloads_sql = "CREATE TABLE {$downloads_table} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            document_id bigint(20) unsigned NOT NULL,
            user_id bigint(20) unsigned NOT NULL DEFAULT 0,
            request_uuid char(36) NOT NULL DEFAULT '',
            file_hash char(64) NOT NULL DEFAULT '',
            outcome varchar(30) NOT NULL,
            denial_reason varchar(190) NOT NULL DEFAULT '',
            ip_hash char(64) NOT NULL DEFAULT '',
            created_at datetime NOT NULL,
            PRIMARY KEY  (id),
            KEY document_id (document_id),
            KEY user_id (user_id),
            KEY request_uuid (request_uuid),
            KEY outcome (outcome),
            KEY created_at (created_at)
        ) {$charset_collate};";

        dbDelta( $requests_sql );
        dbDelta( $downloads_sql );
    }

    /**
     * Add granular capabilities to administrators without replacing unrelated permissions.
     */
    public static function grant_capabilities(): void {
        $administrator = get_role( 'administrator' );

        if ( ! $administrator ) {
            return;
        }

        foreach ( self::capabilities() as $capability ) {
            $administrator->add_cap( $capability );
        }
    }

    /**
     * Plugin capabilities.
     *
     * @return string[]
     */
    public static function capabilities(): array {
        return array(
            'manage_algq_documents',
            'view_algq_documents',
            'upload_algq_documents',
            'download_algq_documents',
            'manage_algq_document_requests',
            'assemble_algq_document_packages',
            'view_algq_document_audit',
        );
    }
}
