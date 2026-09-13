<?php

defined( 'ABSPATH' ) || exit;

final class ALGQ_Pipeline_Database {
    public static function tables(): array {
        global $wpdb;
        return array(
            'deals'                => $wpdb->prefix . 'algq_deals',
            'stage_history'        => $wpdb->prefix . 'algq_deal_stage_history',
            'notes'                => $wpdb->prefix . 'algq_deal_notes',
            'tasks'                => $wpdb->prefix . 'algq_deal_tasks',
            'activity'             => $wpdb->prefix . 'algq_deal_activity',
            'legacy_relationships' => $wpdb->prefix . 'algq_deal_relationships',
            'crm_contacts'         => $wpdb->prefix . 'algq_crm_contacts',
            'crm_organizations'    => $wpdb->prefix . 'algq_crm_organizations',
            'crm_relationships'    => $wpdb->prefix . 'algq_crm_relationships',
            'crm_activity'         => $wpdb->prefix . 'algq_crm_activity',
            'crm_tasks'            => $wpdb->prefix . 'algq_crm_tasks',
        );
    }

    public static function install( bool $record_schema_version = true ): void {
        global $wpdb;
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        $charset = $wpdb->get_charset_collate();
        $t = self::tables();

        dbDelta( "CREATE TABLE {$t['deals']} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            uuid char(36) NOT NULL,
            deal_number varchar(40) NOT NULL,
            title varchar(255) NOT NULL,
            property_address varchar(255) NOT NULL DEFAULT '',
            municipality varchar(120) NOT NULL DEFAULT '',
            state char(2) NOT NULL DEFAULT 'CT',
            postal_code varchar(12) NOT NULL DEFAULT '',
            primary_contact varchar(190) NOT NULL DEFAULT '',
            primary_contact_email varchar(190) NOT NULL DEFAULT '',
            primary_contact_phone varchar(64) NOT NULL DEFAULT '',
            assigned_user_id bigint(20) unsigned DEFAULT NULL,
            stage varchar(64) NOT NULL DEFAULT 'new_intake',
            priority varchar(20) NOT NULL DEFAULT 'normal',
            strategy varchar(64) NOT NULL DEFAULT '',
            source varchar(120) NOT NULL DEFAULT '',
            source_system varchar(100) DEFAULT NULL,
            source_record_id varchar(190) DEFAULT NULL,
            intake_submission_id bigint(20) unsigned DEFAULT NULL,
            asking_price decimal(18,2) DEFAULT NULL,
            offer_amount decimal(18,2) DEFAULT NULL,
            underwriting_status varchar(32) NOT NULL DEFAULT 'not_started',
            offer_status varchar(32) NOT NULL DEFAULT 'none',
            contract_status varchar(64) NOT NULL DEFAULT '',
            buyer_status varchar(64) NOT NULL DEFAULT '',
            funding_status varchar(64) NOT NULL DEFAULT '',
            closing_status varchar(64) NOT NULL DEFAULT '',
            closing_date date DEFAULT NULL,
            loss_reason text DEFAULT NULL,
            disposition varchar(100) NOT NULL DEFAULT '',
            next_action varchar(255) NOT NULL DEFAULT '',
            next_action_due_at datetime DEFAULT NULL,
            record_version bigint(20) unsigned NOT NULL DEFAULT 1,
            archived_at datetime DEFAULT NULL,
            deleted_at datetime DEFAULT NULL,
            created_at datetime NOT NULL,
            updated_at datetime NOT NULL,
            last_activity_at datetime DEFAULT NULL,
            created_by bigint(20) unsigned NOT NULL DEFAULT 0,
            updated_by bigint(20) unsigned NOT NULL DEFAULT 0,
            PRIMARY KEY  (id),
            UNIQUE KEY uuid (uuid),
            UNIQUE KEY deal_number (deal_number),
            UNIQUE KEY source_identity (source_system,source_record_id),
            KEY stage (stage),
            KEY assigned_user_id (assigned_user_id),
            KEY intake_submission_id (intake_submission_id),
            KEY next_action_due_at (next_action_due_at),
            KEY updated_at (updated_at),
            KEY last_activity_at (last_activity_at),
            KEY archived_at (archived_at),
            KEY deleted_at (deleted_at)
        ) $charset;" );

        dbDelta( "CREATE TABLE {$t['stage_history']} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            deal_id bigint(20) unsigned NOT NULL,
            from_stage varchar(64) DEFAULT NULL,
            to_stage varchar(64) NOT NULL,
            reason text DEFAULT NULL,
            context_json longtext DEFAULT NULL,
            changed_by bigint(20) unsigned NOT NULL DEFAULT 0,
            changed_at datetime NOT NULL,
            PRIMARY KEY  (id),
            KEY deal_id (deal_id),
            KEY changed_at (changed_at)
        ) $charset;" );

        dbDelta( "CREATE TABLE {$t['notes']} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            deal_id bigint(20) unsigned NOT NULL,
            note longtext NOT NULL,
            visibility varchar(20) NOT NULL DEFAULT 'internal',
            created_by bigint(20) unsigned NOT NULL DEFAULT 0,
            created_at datetime NOT NULL,
            updated_at datetime DEFAULT NULL,
            deleted_at datetime DEFAULT NULL,
            PRIMARY KEY  (id),
            KEY deal_id (deal_id),
            KEY created_at (created_at),
            KEY deleted_at (deleted_at)
        ) $charset;" );

        dbDelta( "CREATE TABLE {$t['tasks']} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            deal_id bigint(20) unsigned NOT NULL,
            title varchar(255) NOT NULL,
            description text DEFAULT NULL,
            assigned_user_id bigint(20) unsigned DEFAULT NULL,
            due_at datetime DEFAULT NULL,
            status varchar(30) NOT NULL DEFAULT 'open',
            priority varchar(20) NOT NULL DEFAULT 'normal',
            completed_at datetime DEFAULT NULL,
            created_by bigint(20) unsigned NOT NULL DEFAULT 0,
            created_at datetime NOT NULL,
            updated_at datetime NOT NULL,
            PRIMARY KEY  (id),
            KEY deal_id (deal_id),
            KEY assigned_user_id (assigned_user_id),
            KEY status (status),
            KEY due_at (due_at)
        ) $charset;" );

        dbDelta( "CREATE TABLE {$t['activity']} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            deal_id bigint(20) unsigned NOT NULL,
            event varchar(100) NOT NULL DEFAULT '',
            message text NOT NULL,
            metadata_json longtext DEFAULT NULL,
            actor_user_id bigint(20) unsigned NOT NULL DEFAULT 0,
            created_at datetime NOT NULL,
            PRIMARY KEY  (id),
            KEY deal_id (deal_id),
            KEY event (event),
            KEY created_at (created_at)
        ) $charset;" );

        dbDelta( "CREATE TABLE {$t['crm_contacts']} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            uuid char(36) NOT NULL,
            first_name varchar(100) NOT NULL DEFAULT '',
            last_name varchar(100) NOT NULL DEFAULT '',
            display_name varchar(190) NOT NULL,
            email varchar(190) NOT NULL DEFAULT '',
            phone varchar(64) NOT NULL DEFAULT '',
            preferred_contact varchar(32) NOT NULL DEFAULT '',
            owner_user_id bigint(20) unsigned NOT NULL DEFAULT 0,
            status varchar(32) NOT NULL DEFAULT 'active',
            priority varchar(20) NOT NULL DEFAULT 'normal',
            source varchar(100) NOT NULL DEFAULT '',
            relationship_strength varchar(32) NOT NULL DEFAULT 'new',
            tags_json longtext DEFAULT NULL,
            last_activity_at datetime DEFAULT NULL,
            next_action varchar(255) NOT NULL DEFAULT '',
            next_action_at datetime DEFAULT NULL,
            source_system varchar(64) DEFAULT NULL,
            source_record_id varchar(100) DEFAULT NULL,
            created_at datetime NOT NULL,
            updated_at datetime NOT NULL,
            created_by bigint(20) unsigned NOT NULL DEFAULT 0,
            updated_by bigint(20) unsigned NOT NULL DEFAULT 0,
            PRIMARY KEY  (id),
            UNIQUE KEY uuid (uuid),
            UNIQUE KEY source_identity (source_system,source_record_id),
            KEY email (email),
            KEY phone (phone),
            KEY owner_user_id (owner_user_id),
            KEY status (status),
            KEY priority (priority),
            KEY next_action_at (next_action_at),
            KEY updated_at (updated_at)
        ) $charset;" );

        dbDelta( "CREATE TABLE {$t['crm_organizations']} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            uuid char(36) NOT NULL,
            name varchar(190) NOT NULL,
            organization_type varchar(64) NOT NULL DEFAULT 'other',
            website varchar(255) NOT NULL DEFAULT '',
            email varchar(190) NOT NULL DEFAULT '',
            phone varchar(64) NOT NULL DEFAULT '',
            owner_user_id bigint(20) unsigned NOT NULL DEFAULT 0,
            status varchar(32) NOT NULL DEFAULT 'active',
            source varchar(100) NOT NULL DEFAULT '',
            tags_json longtext DEFAULT NULL,
            last_activity_at datetime DEFAULT NULL,
            next_action varchar(255) NOT NULL DEFAULT '',
            next_action_at datetime DEFAULT NULL,
            created_at datetime NOT NULL,
            updated_at datetime NOT NULL,
            created_by bigint(20) unsigned NOT NULL DEFAULT 0,
            updated_by bigint(20) unsigned NOT NULL DEFAULT 0,
            PRIMARY KEY  (id),
            UNIQUE KEY uuid (uuid),
            KEY name (name),
            KEY organization_type (organization_type),
            KEY owner_user_id (owner_user_id),
            KEY status (status),
            KEY next_action_at (next_action_at)
        ) $charset;" );

        dbDelta( "CREATE TABLE {$t['crm_relationships']} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            contact_id bigint(20) unsigned NOT NULL DEFAULT 0,
            organization_id bigint(20) unsigned NOT NULL DEFAULT 0,
            relationship_type varchar(64) NOT NULL DEFAULT 'other',
            deal_id bigint(20) unsigned NOT NULL DEFAULT 0,
            related_record_type varchar(64) NOT NULL DEFAULT '',
            related_record_id bigint(20) unsigned NOT NULL DEFAULT 0,
            source_system varchar(64) DEFAULT NULL,
            source_record_id varchar(100) DEFAULT NULL,
            status varchar(32) NOT NULL DEFAULT 'active',
            metadata_json longtext DEFAULT NULL,
            created_at datetime NOT NULL,
            updated_at datetime NOT NULL,
            created_by bigint(20) unsigned NOT NULL DEFAULT 0,
            updated_by bigint(20) unsigned NOT NULL DEFAULT 0,
            PRIMARY KEY  (id),
            UNIQUE KEY source_identity (source_system,source_record_id),
            KEY contact_id (contact_id),
            KEY organization_id (organization_id),
            KEY relationship_type (relationship_type),
            KEY deal_id (deal_id),
            KEY related_record (related_record_type,related_record_id),
            KEY status (status)
        ) $charset;" );

        dbDelta( "CREATE TABLE {$t['crm_activity']} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            contact_id bigint(20) unsigned NOT NULL DEFAULT 0,
            organization_id bigint(20) unsigned NOT NULL DEFAULT 0,
            deal_id bigint(20) unsigned NOT NULL DEFAULT 0,
            event varchar(100) NOT NULL DEFAULT 'note',
            channel varchar(32) NOT NULL DEFAULT 'internal',
            message text NOT NULL,
            metadata_json longtext DEFAULT NULL,
            actor_user_id bigint(20) unsigned NOT NULL DEFAULT 0,
            created_at datetime NOT NULL,
            PRIMARY KEY  (id),
            KEY contact_id (contact_id),
            KEY organization_id (organization_id),
            KEY deal_id (deal_id),
            KEY event (event),
            KEY created_at (created_at)
        ) $charset;" );

        dbDelta( "CREATE TABLE {$t['crm_tasks']} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            contact_id bigint(20) unsigned NOT NULL DEFAULT 0,
            organization_id bigint(20) unsigned NOT NULL DEFAULT 0,
            deal_id bigint(20) unsigned NOT NULL DEFAULT 0,
            title varchar(255) NOT NULL,
            description text DEFAULT NULL,
            assigned_user_id bigint(20) unsigned NOT NULL DEFAULT 0,
            due_at datetime DEFAULT NULL,
            status varchar(30) NOT NULL DEFAULT 'open',
            priority varchar(20) NOT NULL DEFAULT 'normal',
            completed_at datetime DEFAULT NULL,
            created_by bigint(20) unsigned NOT NULL DEFAULT 0,
            created_at datetime NOT NULL,
            updated_at datetime NOT NULL,
            PRIMARY KEY  (id),
            KEY contact_id (contact_id),
            KEY organization_id (organization_id),
            KEY assigned_user_id (assigned_user_id),
            KEY status (status),
            KEY due_at (due_at)
        ) $charset;" );

        if ( $record_schema_version ) {
            update_option( 'algq_pipeline_schema_version', ALGQ_PIPELINE_SCHEMA_VERSION, false );
        }
    }

    public static function maybe_upgrade() {
        $installed = (string) get_option( 'algq_pipeline_schema_version', '' );
        if ( ALGQ_PIPELINE_SCHEMA_VERSION === $installed ) {
            return true;
        }
        if ( '' !== $installed && version_compare( $installed, ALGQ_PIPELINE_SCHEMA_VERSION, '>' ) ) {
            return new WP_Error( 'algq_pipeline_schema_newer_than_code', sprintf( 'Installed Pipeline CRM schema %s is newer than this plugin supports (%s).', $installed, ALGQ_PIPELINE_SCHEMA_VERSION ) );
        }
        if ( '2.1.0' === $installed ) {
            $preflight = ALGQ_Pipeline_Migrator::preflight_210_to_220();
            if ( is_wp_error( $preflight ) ) {
                return $preflight;
            }
            self::install( false );
            $migration = ALGQ_Pipeline_Migrator::upgrade_210_to_220( $preflight );
            if ( is_wp_error( $migration ) ) {
                return $migration;
            }
            update_option( 'algq_pipeline_schema_version', ALGQ_PIPELINE_SCHEMA_VERSION, false );
            return true;
        }
        self::install( true );
        return true;
    }
}
