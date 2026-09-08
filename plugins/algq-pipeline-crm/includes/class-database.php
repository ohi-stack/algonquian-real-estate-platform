<?php

defined( 'ABSPATH' ) || exit;

final class ALGQ_Pipeline_Database {
    public static function tables(): array {
        global $wpdb;
        return array(
            'deals'             => $wpdb->prefix . 'algq_deals',
            'stage_history'     => $wpdb->prefix . 'algq_deal_stage_history',
            'notes'             => $wpdb->prefix . 'algq_deal_notes',
            'tasks'             => $wpdb->prefix . 'algq_deal_tasks',
            'activity'          => $wpdb->prefix . 'algq_deal_activity',
            'crm_contacts'      => $wpdb->prefix . 'algq_crm_contacts',
            'crm_organizations' => $wpdb->prefix . 'algq_crm_organizations',
            'crm_relationships' => $wpdb->prefix . 'algq_crm_relationships',
            'crm_activity'      => $wpdb->prefix . 'algq_crm_activity',
            'crm_tasks'         => $wpdb->prefix . 'algq_crm_tasks',
        );
    }

    public static function install(): void {
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
            primary_contact varchar(190) NOT NULL DEFAULT '',
            assigned_user_id bigint(20) unsigned NOT NULL DEFAULT 0,
            stage varchar(64) NOT NULL DEFAULT 'new_intake',
            priority varchar(20) NOT NULL DEFAULT 'normal',
            strategy varchar(64) NOT NULL DEFAULT '',
            source varchar(100) NOT NULL DEFAULT '',
            source_system varchar(64) DEFAULT NULL,
            source_record_id varchar(100) DEFAULT NULL,
            asking_price decimal(18,2) NOT NULL DEFAULT 0,
            offer_amount decimal(18,2) NOT NULL DEFAULT 0,
            contract_status varchar(64) NOT NULL DEFAULT '',
            buyer_status varchar(64) NOT NULL DEFAULT '',
            funding_status varchar(64) NOT NULL DEFAULT '',
            closing_status varchar(64) NOT NULL DEFAULT '',
            closing_date date DEFAULT NULL,
            loss_reason text DEFAULT NULL,
            disposition varchar(100) NOT NULL DEFAULT '',
            record_version bigint(20) unsigned NOT NULL DEFAULT 1,
            archived_at datetime DEFAULT NULL,
            created_at datetime NOT NULL,
            updated_at datetime NOT NULL,
            created_by bigint(20) unsigned NOT NULL DEFAULT 0,
            updated_by bigint(20) unsigned NOT NULL DEFAULT 0,
            PRIMARY KEY  (id),
            UNIQUE KEY uuid (uuid),
            UNIQUE KEY deal_number (deal_number),
            UNIQUE KEY source_identity (source_system,source_record_id),
            KEY stage (stage),
            KEY assigned_user_id (assigned_user_id),
            KEY updated_at (updated_at),
            KEY archived_at (archived_at)
        ) $charset;" );

        dbDelta( "CREATE TABLE {$t['stage_history']} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            deal_id bigint(20) unsigned NOT NULL,
            from_stage varchar(64) NOT NULL DEFAULT '',
            to_stage varchar(64) NOT NULL,
            reason varchar(190) NOT NULL DEFAULT '',
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
            updated_at datetime NOT NULL,
            PRIMARY KEY  (id),
            KEY deal_id (deal_id),
            KEY created_at (created_at)
        ) $charset;" );

        dbDelta( "CREATE TABLE {$t['tasks']} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            deal_id bigint(20) unsigned NOT NULL,
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
            KEY deal_id (deal_id),
            KEY assigned_user_id (assigned_user_id),
            KEY status (status),
            KEY due_at (due_at)
        ) $charset;" );

        dbDelta( "CREATE TABLE {$t['activity']} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            deal_id bigint(20) unsigned NOT NULL,
            event varchar(100) NOT NULL,
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

        update_option( 'algq_pipeline_schema_version', ALGQ_PIPELINE_SCHEMA_VERSION, false );
    }

    public static function maybe_upgrade(): void {
        if ( ALGQ_PIPELINE_SCHEMA_VERSION !== get_option( 'algq_pipeline_schema_version' ) ) {
            self::install();
        }
    }
}
