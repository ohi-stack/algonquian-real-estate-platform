<?php

defined( 'ABSPATH' ) || exit;

final class ALGQ_Automation_DB {
    public static function tables(): array {
        global $wpdb;

        return array(
            'rules' => $wpdb->prefix . 'algq_automation_rules',
            'jobs'  => $wpdb->prefix . 'algq_automation_jobs',
            'logs'  => $wpdb->prefix . 'algq_automation_logs',
            'tasks' => $wpdb->prefix . 'algq_automation_tasks',
        );
    }

    public static function migrate(): void {
        global $wpdb;

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        $tables  = self::tables();
        $collate = $wpdb->get_charset_collate();

        dbDelta(
            "CREATE TABLE {$tables['rules']} (
                id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                uuid CHAR(36) NULL,
                rule_name VARCHAR(191) NOT NULL,
                description TEXT NULL,
                trigger_key VARCHAR(191) NOT NULL,
                conditions LONGTEXT NULL,
                action_key VARCHAR(191) NOT NULL,
                action_payload LONGTEXT NULL,
                status VARCHAR(32) NOT NULL DEFAULT 'draft',
                priority SMALLINT UNSIGNED NOT NULL DEFAULT 100,
                max_attempts SMALLINT UNSIGNED NOT NULL DEFAULT 3,
                last_run_at DATETIME NULL,
                created_by BIGINT UNSIGNED NULL,
                updated_by BIGINT UNSIGNED NULL,
                created_at DATETIME NOT NULL,
                updated_at DATETIME NOT NULL,
                PRIMARY KEY  (id),
                UNIQUE KEY uuid (uuid),
                KEY trigger_status (trigger_key, status),
                KEY action_key (action_key),
                KEY priority (priority)
            ) $collate;"
        );

        dbDelta(
            "CREATE TABLE {$tables['jobs']} (
                id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                uuid CHAR(36) NULL,
                rule_id BIGINT UNSIGNED NOT NULL,
                event_key VARCHAR(191) NOT NULL,
                object_type VARCHAR(100) NULL,
                object_id BIGINT UNSIGNED NULL,
                idempotency_key VARCHAR(191) NOT NULL,
                payload LONGTEXT NULL,
                status VARCHAR(32) NOT NULL DEFAULT 'pending',
                attempts SMALLINT UNSIGNED NOT NULL DEFAULT 0,
                max_attempts SMALLINT UNSIGNED NOT NULL DEFAULT 3,
                available_at DATETIME NOT NULL,
                locked_at DATETIME NULL,
                locked_by VARCHAR(191) NULL,
                last_error TEXT NULL,
                created_at DATETIME NOT NULL,
                updated_at DATETIME NOT NULL,
                completed_at DATETIME NULL,
                PRIMARY KEY  (id),
                UNIQUE KEY uuid (uuid),
                UNIQUE KEY idempotency_key (idempotency_key),
                KEY queue_lookup (status, available_at),
                KEY rule_id (rule_id),
                KEY object_lookup (object_type, object_id)
            ) $collate;"
        );

        dbDelta(
            "CREATE TABLE {$tables['logs']} (
                id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                uuid CHAR(36) NULL,
                rule_id BIGINT UNSIGNED NULL,
                job_id BIGINT UNSIGNED NULL,
                event_key VARCHAR(191) NOT NULL,
                object_type VARCHAR(100) NULL,
                object_id BIGINT UNSIGNED NULL,
                level VARCHAR(20) NOT NULL DEFAULT 'info',
                status VARCHAR(32) NOT NULL DEFAULT 'logged',
                message TEXT NULL,
                context LONGTEXT NULL,
                user_id BIGINT UNSIGNED NULL,
                created_at DATETIME NOT NULL,
                PRIMARY KEY  (id),
                UNIQUE KEY uuid (uuid),
                KEY event_key (event_key),
                KEY status (status),
                KEY job_id (job_id),
                KEY created_at (created_at)
            ) $collate;"
        );

        dbDelta(
            "CREATE TABLE {$tables['tasks']} (
                id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                uuid CHAR(36) NULL,
                rule_id BIGINT UNSIGNED NULL,
                job_id BIGINT UNSIGNED NULL,
                task_title VARCHAR(191) NOT NULL,
                task_description TEXT NULL,
                task_status VARCHAR(32) NOT NULL DEFAULT 'pending',
                priority VARCHAR(20) NOT NULL DEFAULT 'normal',
                assigned_user BIGINT UNSIGNED NULL,
                related_object_type VARCHAR(100) NULL,
                related_object_id BIGINT UNSIGNED NULL,
                due_at DATETIME NULL,
                created_at DATETIME NOT NULL,
                completed_at DATETIME NULL,
                PRIMARY KEY  (id),
                UNIQUE KEY uuid (uuid),
                KEY task_status (task_status),
                KEY assigned_user (assigned_user),
                KEY related_object (related_object_type, related_object_id)
            ) $collate;"
        );

        self::backfill_legacy_data();
        update_option( 'algq_automation_schema_version', ALGQ_AUTOMATION_SCHEMA_VERSION, false );
    }

    private static function backfill_legacy_data(): void {
        global $wpdb;

        $tables = self::tables();

        foreach ( $tables as $table ) {
            if ( ! self::table_exists( $table ) ) {
                continue;
            }

            $ids = $wpdb->get_col( "SELECT id FROM {$table} WHERE uuid IS NULL OR uuid = ''" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
            foreach ( $ids as $id ) {
                $wpdb->update( $table, array( 'uuid' => wp_generate_uuid4() ), array( 'id' => absint( $id ) ), array( '%s' ), array( '%d' ) );
            }
        }

        $legacy_column = $wpdb->get_var( "SHOW COLUMNS FROM {$tables['rules']} LIKE 'is_active'" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        if ( $legacy_column ) {
            $wpdb->query( "UPDATE {$tables['rules']} SET status = CASE WHEN is_active = 1 THEN 'active' ELSE 'paused' END WHERE status = 'draft'" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        }
    }

    public static function table_exists( string $table ): bool {
        global $wpdb;

        $pattern = $wpdb->esc_like( $table );
        return $table === $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $pattern ) );
    }
}
