<?php

defined( 'ABSPATH' ) || exit;

/**
 * Shared relationship CRM service.
 *
 * This service owns shared CRM identity/execution records only. Buyer criteria,
 * funding commitments and other specialized records remain in their canonical
 * plugins and are referenced through relationship links.
 */
final class ALGQ_Pipeline_CRM_Service {
    private static ?self $instance = null;
    private ALGQ_Pipeline_CRM_Repository $repository;

    public static function instance(): self {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        $this->repository = new ALGQ_Pipeline_CRM_Repository();
    }

    public function repository(): ALGQ_Pipeline_CRM_Repository {
        return $this->repository;
    }

    public function get_contact( $identifier ): ?array {
        return $this->repository->find_contact( $identifier );
    }

    public function upsert_contact( array $input ) {
        $input = wp_parse_args(
            $input,
            array(
                'first_name'            => '',
                'last_name'             => '',
                'display_name'          => '',
                'email'                 => '',
                'phone'                 => '',
                'preferred_contact'     => '',
                'owner_user_id'         => 0,
                'status'                => 'active',
                'priority'              => 'normal',
                'source'                => '',
                'relationship_strength' => 'new',
                'tags'                  => array(),
                'last_activity_at'      => null,
                'next_action'           => '',
                'next_action_at'        => null,
                'source_system'         => '',
                'source_record_id'      => '',
            )
        );

        $source_system    = sanitize_key( $input['source_system'] );
        $source_record_id = sanitize_text_field( (string) $input['source_record_id'] );
        $existing         = null;

        if ( $source_system && $source_record_id ) {
            $existing = $this->repository->find_contact_by_source( $source_system, $source_record_id );
        }

        $first_name   = sanitize_text_field( $input['first_name'] );
        $last_name    = sanitize_text_field( $input['last_name'] );
        $display_name = sanitize_text_field( $input['display_name'] );
        if ( '' === $display_name ) {
            $display_name = trim( $first_name . ' ' . $last_name );
        }
        if ( '' === $display_name && ! empty( $input['email'] ) ) {
            $display_name = sanitize_email( $input['email'] );
        }
        if ( '' === $display_name ) {
            return new WP_Error( 'algq_crm_contact_name_required', 'A contact name or email is required.', array( 'status' => 400 ) );
        }

        $now     = current_time( 'mysql', true );
        $user_id = get_current_user_id();
        $data    = array(
            'first_name'            => $first_name,
            'last_name'             => $last_name,
            'display_name'          => $display_name,
            'email'                 => sanitize_email( $input['email'] ),
            'phone'                 => sanitize_text_field( $input['phone'] ),
            'preferred_contact'     => sanitize_key( $input['preferred_contact'] ),
            'owner_user_id'         => absint( $input['owner_user_id'] ),
            'status'                => $this->sanitize_status( $input['status'] ),
            'priority'              => $this->sanitize_priority( $input['priority'] ),
            'source'                => sanitize_text_field( $input['source'] ),
            'relationship_strength' => $this->sanitize_strength( $input['relationship_strength'] ),
            'tags_json'             => wp_json_encode( $this->sanitize_tags( $input['tags'] ) ),
            'last_activity_at'      => $this->sanitize_datetime( $input['last_activity_at'] ),
            'next_action'           => sanitize_text_field( $input['next_action'] ),
            'next_action_at'        => $this->sanitize_datetime( $input['next_action_at'] ),
            'source_system'         => $source_system ?: null,
            'source_record_id'      => $source_record_id ?: null,
            'updated_at'            => $now,
            'updated_by'            => $user_id,
        );

        if ( $existing ) {
            if ( ! $this->repository->update_contact( (int) $existing['id'], $data ) ) {
                return new WP_Error( 'algq_crm_contact_update_failed', 'The CRM contact could not be updated.', array( 'status' => 500 ) );
            }
            $contact = $this->repository->find_contact( (int) $existing['id'] );
            $this->audit( 'pipeline.crm_contact_updated', (int) $existing['id'], array( 'source_system' => $source_system ) );
            do_action( 'algq_pipeline_crm_contact_updated', (int) $existing['id'], $contact );
            return $contact;
        }

        $data['uuid']       = wp_generate_uuid4();
        $data['created_at'] = $now;
        $data['created_by'] = $user_id;
        $id = $this->repository->insert_contact( $data );
        if ( ! $id ) {
            return new WP_Error( 'algq_crm_contact_create_failed', 'The CRM contact could not be created.', array( 'status' => 500 ) );
        }

        $contact = $this->repository->find_contact( $id );
        $this->audit( 'pipeline.crm_contact_created', $id, array( 'source_system' => $source_system ) );
        do_action( 'algq_pipeline_crm_contact_created', $id, $contact );
        return $contact;
    }

    public function create_organization( array $input ) {
        $name = sanitize_text_field( $input['name'] ?? '' );
        if ( '' === $name ) {
            return new WP_Error( 'algq_crm_organization_name_required', 'An organization name is required.', array( 'status' => 400 ) );
        }

        $now     = current_time( 'mysql', true );
        $user_id = get_current_user_id();
        $data = array(
            'uuid'             => wp_generate_uuid4(),
            'name'             => $name,
            'organization_type'=> sanitize_key( $input['organization_type'] ?? 'other' ),
            'website'          => esc_url_raw( $input['website'] ?? '' ),
            'email'            => sanitize_email( $input['email'] ?? '' ),
            'phone'            => sanitize_text_field( $input['phone'] ?? '' ),
            'owner_user_id'    => absint( $input['owner_user_id'] ?? 0 ),
            'status'           => $this->sanitize_status( $input['status'] ?? 'active' ),
            'source'           => sanitize_text_field( $input['source'] ?? '' ),
            'tags_json'        => wp_json_encode( $this->sanitize_tags( $input['tags'] ?? array() ) ),
            'last_activity_at' => $this->sanitize_datetime( $input['last_activity_at'] ?? null ),
            'next_action'      => sanitize_text_field( $input['next_action'] ?? '' ),
            'next_action_at'   => $this->sanitize_datetime( $input['next_action_at'] ?? null ),
            'created_at'       => $now,
            'updated_at'       => $now,
            'created_by'       => $user_id,
            'updated_by'       => $user_id,
        );

        $id = $this->repository->insert_organization( $data );
        if ( ! $id ) {
            return new WP_Error( 'algq_crm_organization_create_failed', 'The organization could not be created.', array( 'status' => 500 ) );
        }

        $organization = $this->repository->find_organization( $id );
        $this->audit( 'pipeline.crm_organization_created', $id, array() );
        do_action( 'algq_pipeline_crm_organization_created', $id, $organization );
        return $organization;
    }

    public function link_relationship( array $input ) {
        $input = wp_parse_args(
            $input,
            array(
                'contact_id'          => 0,
                'organization_id'     => 0,
                'relationship_type'   => 'other',
                'deal_id'             => 0,
                'related_record_type' => '',
                'related_record_id'   => 0,
                'source_system'       => '',
                'source_record_id'    => '',
                'status'              => 'active',
                'metadata'            => array(),
            )
        );

        if ( ! $input['contact_id'] && ! $input['organization_id'] ) {
            return new WP_Error( 'algq_crm_relationship_party_required', 'A contact or organization is required.', array( 'status' => 400 ) );
        }

        $source_system    = sanitize_key( $input['source_system'] );
        $source_record_id = sanitize_text_field( (string) $input['source_record_id'] );
        if ( $source_system && $source_record_id ) {
            $existing = $this->repository->find_relationship_by_source( $source_system, $source_record_id );
            if ( $existing ) {
                return $existing;
            }
        }

        $deal_id = absint( $input['deal_id'] );
        if ( $deal_id && ! algq_get_deal( $deal_id ) ) {
            return new WP_Error( 'algq_crm_deal_not_found', 'The linked canonical Deal does not exist.', array( 'status' => 404 ) );
        }

        $now = current_time( 'mysql', true );
        $data = array(
            'contact_id'          => absint( $input['contact_id'] ),
            'organization_id'     => absint( $input['organization_id'] ),
            'relationship_type'   => $this->sanitize_relationship_type( $input['relationship_type'] ),
            'deal_id'             => $deal_id,
            'related_record_type' => sanitize_key( $input['related_record_type'] ),
            'related_record_id'   => absint( $input['related_record_id'] ),
            'source_system'       => $source_system ?: null,
            'source_record_id'    => $source_record_id ?: null,
            'status'              => $this->sanitize_status( $input['status'] ),
            'metadata_json'       => wp_json_encode( $this->sanitize_metadata( $input['metadata'] ) ),
            'created_at'          => $now,
            'updated_at'          => $now,
            'created_by'          => get_current_user_id(),
            'updated_by'          => get_current_user_id(),
        );

        $id = $this->repository->insert_relationship( $data );
        if ( ! $id ) {
            return new WP_Error( 'algq_crm_relationship_create_failed', 'The CRM relationship could not be created.', array( 'status' => 500 ) );
        }

        $this->audit( 'pipeline.crm_relationship_created', $id, array( 'deal_id' => $deal_id, 'relationship_type' => $data['relationship_type'] ) );
        do_action( 'algq_pipeline_crm_relationship_created', $id, $data );
        return array_merge( array( 'id' => $id ), $data );
    }

    public function add_activity( int $contact_id, array $input ) {
        $contact = $this->repository->find_contact( $contact_id );
        if ( ! $contact ) {
            return new WP_Error( 'algq_crm_contact_not_found', 'CRM contact not found.', array( 'status' => 404 ) );
        }

        $message = sanitize_textarea_field( $input['message'] ?? '' );
        if ( '' === $message ) {
            return new WP_Error( 'algq_crm_activity_message_required', 'An activity message is required.', array( 'status' => 400 ) );
        }

        $now = current_time( 'mysql', true );
        $data = array(
            'contact_id'      => $contact_id,
            'organization_id' => absint( $input['organization_id'] ?? 0 ),
            'deal_id'         => absint( $input['deal_id'] ?? 0 ),
            'event'           => sanitize_key( $input['event'] ?? 'note' ),
            'channel'         => sanitize_key( $input['channel'] ?? 'internal' ),
            'message'         => $message,
            'metadata_json'   => wp_json_encode( $this->sanitize_metadata( $input['metadata'] ?? array() ) ),
            'actor_user_id'   => get_current_user_id(),
            'created_at'      => $now,
        );
        $id = $this->repository->add_activity( $data );
        if ( ! $id ) {
            return new WP_Error( 'algq_crm_activity_create_failed', 'The CRM activity could not be recorded.', array( 'status' => 500 ) );
        }

        $this->repository->update_contact(
            $contact_id,
            array(
                'last_activity_at' => $now,
                'updated_at'       => $now,
                'updated_by'       => get_current_user_id(),
            )
        );
        do_action( 'algq_pipeline_crm_activity_created', $id, $data );
        return array_merge( array( 'id' => $id ), $data );
    }

    public function create_task( array $input ) {
        $title = sanitize_text_field( $input['title'] ?? '' );
        if ( '' === $title ) {
            return new WP_Error( 'algq_crm_task_title_required', 'A task title is required.', array( 'status' => 400 ) );
        }

        $deal_id = absint( $input['deal_id'] ?? 0 );
        if ( $deal_id ) {
            return new WP_Error(
                'algq_crm_use_deal_task',
                'Deal-specific work must use the canonical Pipeline Deal task system rather than a relationship task.',
                array( 'status' => 409 )
            );
        }

        $now = current_time( 'mysql', true );
        $data = array(
            'contact_id'      => absint( $input['contact_id'] ?? 0 ),
            'organization_id' => absint( $input['organization_id'] ?? 0 ),
            'deal_id'         => 0,
            'title'           => $title,
            'description'     => sanitize_textarea_field( $input['description'] ?? '' ),
            'assigned_user_id'=> absint( $input['assigned_user_id'] ?? 0 ),
            'due_at'          => $this->sanitize_datetime( $input['due_at'] ?? null ),
            'status'          => sanitize_key( $input['status'] ?? 'open' ),
            'priority'        => $this->sanitize_priority( $input['priority'] ?? 'normal' ),
            'completed_at'    => null,
            'created_by'      => get_current_user_id(),
            'created_at'      => $now,
            'updated_at'      => $now,
        );
        $id = $this->repository->insert_task( $data );
        if ( ! $id ) {
            return new WP_Error( 'algq_crm_task_create_failed', 'The CRM task could not be created.', array( 'status' => 500 ) );
        }
        do_action( 'algq_pipeline_crm_task_created', $id, $data );
        return array_merge( array( 'id' => $id ), $data );
    }

    private function sanitize_priority( $value ): string {
        $value = sanitize_key( $value );
        return in_array( $value, array( 'low', 'normal', 'high', 'critical' ), true ) ? $value : 'normal';
    }

    private function sanitize_status( $value ): string {
        $value = sanitize_key( $value );
        return in_array( $value, array( 'new', 'active', 'qualified', 'nurture', 'inactive', 'closed', 'do_not_contact', 'archived' ), true ) ? $value : 'active';
    }

    private function sanitize_strength( $value ): string {
        $value = sanitize_key( $value );
        return in_array( $value, array( 'new', 'developing', 'active', 'trusted' ), true ) ? $value : 'new';
    }

    private function sanitize_relationship_type( $value ): string {
        $value = sanitize_key( $value );
        $allowed = array( 'seller', 'property_owner', 'buyer', 'capital', 'lender', 'equity_partner', 'joint_venture_partner', 'professional', 'vendor', 'referral_source', 'stewardship_client', 'other' );
        $allowed = apply_filters( 'algq_pipeline_crm_relationship_types', $allowed );
        return in_array( $value, $allowed, true ) ? $value : 'other';
    }

    private function sanitize_tags( $tags ): array {
        $tags = is_array( $tags ) ? $tags : preg_split( '/[,\n]+/', (string) $tags );
        return array_values( array_unique( array_filter( array_map( 'sanitize_key', $tags ?: array() ) ) ) );
    }

    private function sanitize_datetime( $value ): ?string {
        if ( empty( $value ) ) {
            return null;
        }
        $timestamp = strtotime( (string) $value );
        return false === $timestamp ? null : gmdate( 'Y-m-d H:i:s', $timestamp );
    }

    private function sanitize_metadata( $metadata ): array {
        if ( ! is_array( $metadata ) ) {
            return array();
        }
        $safe = array();
        foreach ( $metadata as $key => $value ) {
            $key = sanitize_key( (string) $key );
            if ( is_scalar( $value ) || null === $value ) {
                $safe[ $key ] = is_string( $value ) ? sanitize_text_field( $value ) : $value;
            }
        }
        return $safe;
    }

    private function audit( string $event, int $record_id, array $data ): void {
        $payload = array( 'plugin' => 'algq-pipeline-crm', 'crm_record_id' => $record_id, 'data' => $data );
        if ( function_exists( 'algq_log_event' ) ) {
            algq_log_event( $event, $payload );
        } else {
            do_action( 'algq_audit_event', $event, $payload );
        }
    }
}
