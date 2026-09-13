<?php

defined( 'ABSPATH' ) || exit;

final class ALGQ_Pipeline_Migrator {
    private const MIGRATION_OPTION = 'algq_pipeline_migration_210_220';

    public static function preflight_210_to_220() {
        global $wpdb;
        $t = ALGQ_Pipeline_Database::tables();

        $required_columns = array(
            'id', 'uuid', 'deal_number', 'title', 'property_address', 'municipality', 'state', 'postal_code',
            'primary_contact_name', 'primary_contact_email', 'primary_contact_phone', 'assigned_user_id',
            'stage_key', 'priority', 'acquisition_strategy', 'source', 'source_plugin', 'external_source_id',
            'intake_submission_id', 'asking_price', 'offer_amount', 'underwriting_status', 'offer_status',
            'contract_status', 'buyer_status', 'funding_status', 'closing_status', 'closing_date', 'loss_reason',
            'disposition', 'next_action', 'next_action_due_at', 'record_version', 'archived_at', 'deleted_at',
            'created_at', 'updated_at', 'last_activity_at', 'created_by', 'updated_by',
        );

        $columns = $wpdb->get_col( "SHOW COLUMNS FROM {$t['deals']}", 0 );
        if ( ! is_array( $columns ) ) {
            return new WP_Error( 'algq_pipeline_210_table_missing', 'Pipeline CRM 2.1.0 Deal table could not be inspected.' );
        }

        $missing = array_values( array_diff( $required_columns, $columns ) );
        if ( $missing ) {
            return new WP_Error( 'algq_pipeline_210_columns_missing', 'Pipeline CRM 2.1.0 migration baseline does not match the installed Deal table.', array( 'missing_columns' => $missing ) );
        }

        $duplicate_source = $wpdb->get_row(
            "SELECT source_plugin, external_source_id, COUNT(*) AS total
             FROM {$t['deals']}
             WHERE external_source_id IS NOT NULL AND external_source_id <> ''
             GROUP BY source_plugin, external_source_id
             HAVING COUNT(*) > 1
             LIMIT 1",
            ARRAY_A
        );
        if ( $duplicate_source ) {
            return new WP_Error( 'algq_pipeline_duplicate_source_identity', 'Pipeline CRM 2.1.0 contains duplicate source identities. Resolve them before the 2.2 migration.', $duplicate_source );
        }

        $stages = $wpdb->get_col( "SELECT DISTINCT stage_key FROM {$t['deals']} WHERE stage_key IS NOT NULL AND stage_key <> ''" );
        $invalid_stages = array();
        foreach ( $stages ?: array() as $stage ) {
            if ( ! ALGQ_Pipeline_Stages::is_valid( (string) $stage ) ) {
                $invalid_stages[] = (string) $stage;
            }
        }
        if ( $invalid_stages ) {
            return new WP_Error( 'algq_pipeline_invalid_210_stage', 'Pipeline CRM 2.1.0 contains stage values that are not recognized by the 2.2 lifecycle.', array( 'invalid_stages' => $invalid_stages ) );
        }

        return array(
            'schema_from'          => '2.1.0',
            'schema_to'            => ALGQ_PIPELINE_SCHEMA_VERSION,
            'deal_count'           => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$t['deals']}" ),
            'activity_count'       => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$t['activity']}" ),
            'legacy_relationships' => self::table_exists( $t['legacy_relationships'] ) ? (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$t['legacy_relationships']}" ) : 0,
            'captured_at'          => current_time( 'mysql', true ),
        );
    }

    public static function upgrade_210_to_220( array $preflight ) {
        global $wpdb;
        $t = ALGQ_Pipeline_Database::tables();

        $existing = get_option( self::MIGRATION_OPTION, array() );
        if ( is_array( $existing ) && ! empty( $existing['completed_at'] ) ) {
            return $existing;
        }

        $wpdb->query( 'START TRANSACTION' );

        $deal_copy = $wpdb->query(
            "UPDATE {$t['deals']}
             SET primary_contact = COALESCE(primary_contact_name, ''),
                 stage = COALESCE(NULLIF(stage_key, ''), 'new_intake'),
                 strategy = COALESCE(acquisition_strategy, ''),
                 source_system = NULLIF(source_plugin, ''),
                 source_record_id = NULLIF(external_source_id, ''),
                 last_activity_at = COALESCE(last_activity_at, updated_at, created_at)"
        );
        if ( false === $deal_copy ) {
            return self::rollback_error( 'algq_pipeline_deal_mapping_failed', 'Deal field mapping failed.', $wpdb->last_error );
        }

        $activity_columns = $wpdb->get_col( "SHOW COLUMNS FROM {$t['activity']}", 0 );
        if ( in_array( 'event_key', $activity_columns ?: array(), true ) ) {
            $activity_copy = $wpdb->query(
                "UPDATE {$t['activity']}
                 SET event = COALESCE(NULLIF(event, ''), event_key),
                     metadata_json = COALESCE(metadata_json, context)"
            );
            if ( false === $activity_copy ) {
                return self::rollback_error( 'algq_pipeline_activity_mapping_failed', 'Activity field mapping failed.', $wpdb->last_error );
            }
        }

        $contact_result = self::migrate_primary_contacts( $t );
        if ( is_wp_error( $contact_result ) ) {
            $wpdb->query( 'ROLLBACK' );
            return $contact_result;
        }

        $verification = self::verify_210_to_220( $preflight, $contact_result );
        if ( is_wp_error( $verification ) ) {
            $wpdb->query( 'ROLLBACK' );
            return $verification;
        }

        $record = array(
            'from_schema'                   => '2.1.0',
            'to_schema'                     => ALGQ_PIPELINE_SCHEMA_VERSION,
            'completed_at'                  => current_time( 'mysql', true ),
            'deal_count'                    => $verification['deal_count'],
            'activity_count'                => $verification['activity_count'],
            'primary_contacts_created'      => $contact_result['contacts_created'],
            'seller_relationships_created'  => $contact_result['relationships_created'],
            'legacy_relationships_retained' => $preflight['legacy_relationships'],
            'legacy_relationship_policy'    => 'retained_read_only_until_companion_plugins_reassert_authoritative_links',
        );

        update_option( self::MIGRATION_OPTION, $record, false );
        delete_option( 'algq_pipeline_migration_error' );
        $wpdb->query( 'COMMIT' );
        do_action( 'algq_audit_event', 'pipeline.migration_210_220_completed', $record );
        return $record;
    }

    private static function migrate_primary_contacts( array $t ) {
        global $wpdb;

        $deals = $wpdb->get_results(
            "SELECT id, deal_number, primary_contact_name, primary_contact_email, primary_contact_phone,
                    assigned_user_id, priority, source, created_at, updated_at
             FROM {$t['deals']}
             WHERE primary_contact_name <> '' OR primary_contact_email <> '' OR primary_contact_phone <> ''
             ORDER BY id ASC",
            ARRAY_A
        ) ?: array();

        $contacts_created = 0;
        $relationships_created = 0;

        foreach ( $deals as $deal ) {
            $source_system = 'pipeline_2_1_primary_contact';
            $source_record_id = (string) $deal['id'];
            $contact_id = (int) $wpdb->get_var(
                $wpdb->prepare( "SELECT id FROM {$t['crm_contacts']} WHERE source_system = %s AND source_record_id = %s LIMIT 1", $source_system, $source_record_id )
            );

            if ( ! $contact_id ) {
                $display_name = trim( (string) $deal['primary_contact_name'] );
                if ( '' === $display_name ) {
                    $display_name = sanitize_email( (string) $deal['primary_contact_email'] );
                }
                if ( '' === $display_name ) {
                    $display_name = 'Property Contact — ' . sanitize_text_field( (string) $deal['deal_number'] );
                }
                $priority = sanitize_key( (string) $deal['priority'] );
                if ( 'urgent' === $priority ) {
                    $priority = 'critical';
                }
                if ( ! in_array( $priority, array( 'low', 'normal', 'high', 'critical' ), true ) ) {
                    $priority = 'normal';
                }
                $now = current_time( 'mysql', true );
                $inserted = $wpdb->insert(
                    $t['crm_contacts'],
                    array(
                        'uuid'                  => wp_generate_uuid4(),
                        'first_name'            => '',
                        'last_name'             => '',
                        'display_name'          => sanitize_text_field( $display_name ),
                        'email'                 => sanitize_email( (string) $deal['primary_contact_email'] ),
                        'phone'                 => sanitize_text_field( (string) $deal['primary_contact_phone'] ),
                        'preferred_contact'     => '',
                        'owner_user_id'         => absint( $deal['assigned_user_id'] ),
                        'status'                => 'active',
                        'priority'              => $priority,
                        'source'                => sanitize_text_field( (string) $deal['source'] ),
                        'relationship_strength' => 'new',
                        'tags_json'             => wp_json_encode( array( 'migrated_from_pipeline_2_1' ) ),
                        'last_activity_at'      => null,
                        'next_action'           => '',
                        'next_action_at'        => null,
                        'source_system'         => $source_system,
                        'source_record_id'      => $source_record_id,
                        'created_at'            => $deal['created_at'] ?: $now,
                        'updated_at'            => $deal['updated_at'] ?: $now,
                        'created_by'            => 0,
                        'updated_by'            => 0,
                    )
                );
                if ( false === $inserted ) {
                    return new WP_Error( 'algq_pipeline_contact_migration_failed', 'Unable to migrate a Pipeline 2.1 primary contact.', array( 'deal_id' => $deal['id'], 'db_error' => $wpdb->last_error ) );
                }
                $contact_id = (int) $wpdb->insert_id;
                ++$contacts_created;
            }

            $relationship_source = 'pipeline_2_1_seller_relationship';
            $relationship_id = (int) $wpdb->get_var(
                $wpdb->prepare( "SELECT id FROM {$t['crm_relationships']} WHERE source_system = %s AND source_record_id = %s LIMIT 1", $relationship_source, $source_record_id )
            );
            if ( ! $relationship_id ) {
                $now = current_time( 'mysql', true );
                $inserted = $wpdb->insert(
                    $t['crm_relationships'],
                    array(
                        'contact_id'          => $contact_id,
                        'organization_id'     => 0,
                        'relationship_type'   => 'seller_property_owner',
                        'deal_id'             => absint( $deal['id'] ),
                        'related_record_type' => 'deal',
                        'related_record_id'   => absint( $deal['id'] ),
                        'source_system'       => $relationship_source,
                        'source_record_id'    => $source_record_id,
                        'status'              => 'active',
                        'metadata_json'       => wp_json_encode( array( 'migration' => '2.1.0-to-2.2.0' ) ),
                        'created_at'          => $now,
                        'updated_at'          => $now,
                        'created_by'          => 0,
                        'updated_by'          => 0,
                    )
                );
                if ( false === $inserted ) {
                    return new WP_Error( 'algq_pipeline_relationship_migration_failed', 'Unable to create the migrated seller relationship.', array( 'deal_id' => $deal['id'], 'db_error' => $wpdb->last_error ) );
                }
                ++$relationships_created;
            }
        }

        return array(
            'eligible_contacts'     => count( $deals ),
            'contacts_created'      => $contacts_created,
            'relationships_created' => $relationships_created,
        );
    }

    private static function verify_210_to_220( array $preflight, array $contact_result ) {
        global $wpdb;
        $t = ALGQ_Pipeline_Database::tables();

        $deal_count = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$t['deals']}" );
        if ( $deal_count !== (int) $preflight['deal_count'] ) {
            return new WP_Error( 'algq_pipeline_migration_deal_count_mismatch', 'Deal count changed during Pipeline CRM migration.' );
        }
        $stage_mismatch = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$t['deals']} WHERE COALESCE(stage, '') <> COALESCE(stage_key, '')" );
        if ( $stage_mismatch > 0 ) {
            return new WP_Error( 'algq_pipeline_migration_stage_mismatch', 'One or more Deal stages were not preserved during migration.', array( 'count' => $stage_mismatch ) );
        }
        $contact_mismatch = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$t['deals']} WHERE COALESCE(primary_contact, '') <> COALESCE(primary_contact_name, '')" );
        if ( $contact_mismatch > 0 ) {
            return new WP_Error( 'algq_pipeline_migration_contact_mismatch', 'One or more primary contact names were not preserved during migration.', array( 'count' => $contact_mismatch ) );
        }
        $source_mismatch = (int) $wpdb->get_var(
            "SELECT COUNT(*) FROM {$t['deals']}
             WHERE external_source_id IS NOT NULL AND external_source_id <> ''
               AND (COALESCE(source_system, '') <> COALESCE(source_plugin, '') OR COALESCE(source_record_id, '') <> COALESCE(external_source_id, ''))"
        );
        if ( $source_mismatch > 0 ) {
            return new WP_Error( 'algq_pipeline_migration_source_mismatch', 'One or more Deal source identities were not preserved during migration.', array( 'count' => $source_mismatch ) );
        }
        $activity_count = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$t['activity']}" );
        if ( $activity_count !== (int) $preflight['activity_count'] ) {
            return new WP_Error( 'algq_pipeline_migration_activity_count_mismatch', 'Activity count changed during Pipeline CRM migration.' );
        }
        $activity_columns = $wpdb->get_col( "SHOW COLUMNS FROM {$t['activity']}", 0 );
        if ( in_array( 'event_key', $activity_columns ?: array(), true ) ) {
            $activity_mismatch = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$t['activity']} WHERE COALESCE(event, '') <> COALESCE(event_key, '')" );
            if ( $activity_mismatch > 0 ) {
                return new WP_Error( 'algq_pipeline_migration_activity_mismatch', 'One or more activity event keys were not preserved during migration.', array( 'count' => $activity_mismatch ) );
            }
        }
        $expected_contacts = (int) $contact_result['eligible_contacts'];
        $migrated_contacts = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$t['crm_contacts']} WHERE source_system = %s", 'pipeline_2_1_primary_contact' ) );
        if ( $migrated_contacts < $expected_contacts ) {
            return new WP_Error( 'algq_pipeline_migration_contact_count_mismatch', 'Not all 2.1 Deal contacts were represented in the shared CRM layer.' );
        }
        $seller_relationships = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$t['crm_relationships']} WHERE source_system = %s", 'pipeline_2_1_seller_relationship' ) );
        if ( $seller_relationships < $expected_contacts ) {
            return new WP_Error( 'algq_pipeline_migration_relationship_count_mismatch', 'Not all migrated Deal contacts were linked back to their canonical Deal.' );
        }
        return array( 'deal_count' => $deal_count, 'activity_count' => $activity_count );
    }

    private static function rollback_error( string $code, string $message, string $db_error ): WP_Error {
        global $wpdb;
        $wpdb->query( 'ROLLBACK' );
        return new WP_Error( $code, $message, array( 'db_error' => $db_error ) );
    }

    private static function table_exists( string $table ): bool {
        global $wpdb;
        return $table === $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) );
    }

    public static function run_legacy_import(): int {
        if ( get_option( 'algq_pipeline_legacy_cpt_migrated' ) ) {
            return 0;
        }
        $posts = get_posts(
            array(
                'post_type' => 'algq_deal',
                'post_status' => array( 'publish', 'private', 'draft', 'pending' ),
                'numberposts' => -1,
                'orderby' => 'ID',
                'order' => 'ASC',
            )
        );
        $imported = 0;
        foreach ( $posts as $post ) {
            $result = ALGQ_Pipeline_Service::instance()->create_deal(
                array(
                    'title' => $post->post_title,
                    'property_address' => (string) get_post_meta( $post->ID, 'property_address', true ),
                    'primary_contact' => (string) get_post_meta( $post->ID, 'seller_name', true ),
                    'assigned_user_id' => (int) $post->post_author,
                    'stage' => ALGQ_Pipeline_Stages::normalize( (string) get_post_meta( $post->ID, 'pipeline_stage', true ) ?: 'new_intake' ),
                    'priority' => strtolower( (string) get_post_meta( $post->ID, 'deal_priority', true ) ?: 'normal' ),
                    'asking_price' => (float) get_post_meta( $post->ID, 'deal_value', true ),
                    'source' => 'Legacy Pipeline CRM',
                    'source_system' => 'legacy_cpt',
                    'source_record_id' => (string) $post->ID,
                )
            );
            if ( ! is_wp_error( $result ) ) {
                ++$imported;
            }
        }
        update_option( 'algq_pipeline_legacy_cpt_migrated', array( 'completed_at' => current_time( 'mysql', true ), 'imported' => $imported ), false );
        return $imported;
    }
}
