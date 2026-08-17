<?php

defined( 'ABSPATH' ) || exit;

final class ALGQ_Pipeline_Service {
    private static ?self $instance = null;
    private ALGQ_Pipeline_Repository $repository;

    public static function instance(): self {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        $this->repository = new ALGQ_Pipeline_Repository();
        add_action( 'algq_pipeline_stage_change_requested', array( $this, 'handle_stage_request' ), 10, 3 );
        add_filter( 'algq_pipeline_deal_stage_payload', array( $this, 'filter_deal_payload' ), 10, 2 );
    }

    public function get_deal( $identifier ): ?array {
        return $this->repository->find( $identifier );
    }

    public function repository(): ALGQ_Pipeline_Repository {
        return $this->repository;
    }

    public function create_deal( array $input ) {
        $input = wp_parse_args(
            $input,
            array(
                'title' => '', 'property_address' => '', 'primary_contact' => '', 'assigned_user_id' => 0,
                'stage' => 'new_intake', 'priority' => 'normal', 'strategy' => '', 'source' => '',
                'source_system' => '', 'source_record_id' => '', 'asking_price' => 0, 'offer_amount' => 0,
            )
        );
        $source_system = sanitize_key( $input['source_system'] );
        $source_record_id = sanitize_text_field( (string) $input['source_record_id'] );
        if ( $source_system && $source_record_id ) {
            $existing = $this->repository->find_by_source( $source_system, $source_record_id );
            if ( $existing ) {
                return $existing;
            }
        }
        $title = sanitize_text_field( $input['title'] );
        if ( '' === $title ) {
            $title = sanitize_text_field( $input['property_address'] );
        }
        if ( '' === $title ) {
            return new WP_Error( 'algq_pipeline_title_required', 'A deal title or property address is required.', array( 'status' => 400 ) );
        }
        $stage = ALGQ_Pipeline_Stages::normalize( sanitize_key( $input['stage'] ) );
        if ( ! ALGQ_Pipeline_Stages::is_valid( $stage ) ) {
            $stage = 'new_intake';
        }
        $now = current_time( 'mysql', true );
        $user_id = get_current_user_id();
        $uuid = wp_generate_uuid4();
        $deal_number = $this->generate_deal_number();
        $data = array(
            'uuid' => $uuid,
            'deal_number' => $deal_number,
            'title' => $title,
            'property_address' => sanitize_text_field( $input['property_address'] ),
            'primary_contact' => sanitize_text_field( $input['primary_contact'] ),
            'assigned_user_id' => absint( $input['assigned_user_id'] ),
            'stage' => $stage,
            'priority' => $this->sanitize_priority( $input['priority'] ),
            'strategy' => sanitize_key( $input['strategy'] ),
            'source' => sanitize_text_field( $input['source'] ),
            'source_system' => $source_system ?: null,
            'source_record_id' => $source_record_id ?: null,
            'asking_price' => round( (float) $input['asking_price'], 2 ),
            'offer_amount' => round( (float) $input['offer_amount'], 2 ),
            'record_version' => 1,
            'created_at' => $now,
            'updated_at' => $now,
            'created_by' => $user_id,
            'updated_by' => $user_id,
        );
        $id = $this->repository->insert( $data );
        if ( ! $id ) {
            return new WP_Error( 'algq_pipeline_create_failed', 'The deal could not be created.', array( 'status' => 500 ) );
        }
        $this->repository->add_stage_history( $id, '', $stage, 'deal_created', array( 'source' => $source_system ), $user_id );
        $this->repository->add_activity( $id, 'deal_created', sprintf( 'Deal %s was created.', $deal_number ), array( 'stage' => $stage ), $user_id );
        $deal = $this->repository->find( $id );
        $this->audit( 'pipeline.deal_created', $id, array( 'deal_number' => $deal_number, 'stage' => $stage ) );
        do_action( 'algq_pipeline_deal_created', $id, $deal );
        return $deal;
    }

    public function update_deal( int $deal_id, array $input, int $expected_version ) {
        $deal = $this->repository->find( $deal_id );
        if ( ! $deal ) {
            return new WP_Error( 'algq_pipeline_not_found', 'Deal not found.', array( 'status' => 404 ) );
        }
        $allowed = array( 'title', 'property_address', 'primary_contact', 'assigned_user_id', 'priority', 'strategy', 'source', 'asking_price', 'offer_amount', 'contract_status', 'buyer_status', 'funding_status', 'closing_status', 'closing_date', 'loss_reason', 'disposition' );
        $data = array();
        foreach ( $allowed as $key ) {
            if ( ! array_key_exists( $key, $input ) ) {
                continue;
            }
            if ( 'assigned_user_id' === $key ) {
                $data[ $key ] = absint( $input[ $key ] );
            } elseif ( in_array( $key, array( 'asking_price', 'offer_amount' ), true ) ) {
                $data[ $key ] = round( (float) $input[ $key ], 2 );
            } elseif ( 'priority' === $key ) {
                $data[ $key ] = $this->sanitize_priority( $input[ $key ] );
            } elseif ( 'closing_date' === $key ) {
                $data[ $key ] = preg_match( '/^\d{4}-\d{2}-\d{2}$/', (string) $input[ $key ] ) ? $input[ $key ] : null;
            } elseif ( in_array( $key, array( 'strategy', 'contract_status', 'buyer_status', 'funding_status', 'closing_status' ), true ) ) {
                $data[ $key ] = sanitize_key( $input[ $key ] );
            } else {
                $data[ $key ] = sanitize_textarea_field( $input[ $key ] );
            }
        }
        if ( ! $data ) {
            return $deal;
        }
        $data['record_version'] = (int) $deal['record_version'] + 1;
        $data['updated_at'] = current_time( 'mysql', true );
        $data['updated_by'] = get_current_user_id();
        if ( ! $this->repository->update( $deal_id, $data, $expected_version ) ) {
            return new WP_Error( 'algq_pipeline_conflict', 'This deal was changed by another request. Refresh before saving.', array( 'status' => 409 ) );
        }
        $this->repository->add_activity( $deal_id, 'deal_updated', 'Deal details were updated.', array( 'fields' => array_keys( $data ) ), get_current_user_id() );
        $updated = $this->repository->find( $deal_id );
        do_action( 'algq_pipeline_deal_updated', $deal_id, $deal, $updated );
        return $updated;
    }

    public function transition( int $deal_id, string $target, array $context = array() ) {
        $deal = $this->repository->find( $deal_id );
        if ( ! $deal ) {
            return new WP_Error( 'algq_pipeline_not_found', 'Deal not found.', array( 'status' => 404 ) );
        }
        $target = ALGQ_Pipeline_Stages::normalize( $target );
        if ( ! ALGQ_Pipeline_Stages::is_valid( $target ) ) {
            return new WP_Error( 'algq_pipeline_invalid_stage', 'The requested stage is invalid.', array( 'status' => 400 ) );
        }
        $current = ALGQ_Pipeline_Stages::normalize( $deal['stage'] );
        if ( $current === $target ) {
            return $deal;
        }
        $force = ! empty( $context['force'] ) && current_user_can( 'manage_algq_pipeline' );
        if ( ! $force && ! in_array( $target, ALGQ_Pipeline_Stages::allowed_from( $current ), true ) ) {
            return new WP_Error( 'algq_pipeline_transition_not_allowed', sprintf( 'A deal cannot move directly from %s to %s.', ALGQ_Pipeline_Stages::label( $current ), ALGQ_Pipeline_Stages::label( $target ) ), array( 'status' => 409 ) );
        }
        $requirements = $this->validate_requirements( $deal, $target, $context );
        if ( is_wp_error( $requirements ) ) {
            return $requirements;
        }
        $expected_version = isset( $context['record_version'] ) ? absint( $context['record_version'] ) : (int) $deal['record_version'];
        $data = array(
            'stage' => $target,
            'record_version' => (int) $deal['record_version'] + 1,
            'updated_at' => current_time( 'mysql', true ),
            'updated_by' => get_current_user_id(),
        );
        if ( 'archived' === $target ) {
            $data['archived_at'] = current_time( 'mysql', true );
        } elseif ( 'archived' === $current ) {
            $data['archived_at'] = null;
        }
        if ( ! $this->repository->update( $deal_id, $data, $expected_version ) ) {
            return new WP_Error( 'algq_pipeline_conflict', 'The deal changed before this transition completed. Refresh the pipeline and try again.', array( 'status' => 409 ) );
        }
        $reason = sanitize_text_field( $context['reason'] ?? '' );
        $safe_context = array_intersect_key( $context, array_flip( array( 'source', 'reason', 'automation_id', 'request_id' ) ) );
        $this->repository->add_stage_history( $deal_id, $current, $target, $reason, $safe_context, get_current_user_id() );
        $this->repository->add_activity( $deal_id, 'stage_changed', sprintf( 'Stage changed from %s to %s.', ALGQ_Pipeline_Stages::label( $current ), ALGQ_Pipeline_Stages::label( $target ) ), $safe_context, get_current_user_id() );
        $updated = $this->repository->find( $deal_id );
        $this->audit( 'pipeline.stage_changed', $deal_id, array( 'from' => $current, 'to' => $target, 'reason' => $reason ) );
        do_action( 'algq_pipeline_stage_changed', $deal_id, $current, $target, $safe_context );
        return $updated;
    }

    public function handle_stage_request( $deal_id, $stage, $context = array() ): void {
        $result = $this->transition( absint( $deal_id ), sanitize_key( $stage ), is_array( $context ) ? $context : array() );
        if ( is_wp_error( $result ) ) {
            do_action( 'algq_pipeline_stage_change_failed', absint( $deal_id ), $stage, $result );
        }
    }

    public function filter_deal_payload( $payload, $deal_id ) {
        $payload = is_array( $payload ) ? $payload : array();
        $deal = $this->get_deal( $deal_id );
        if ( $deal ) {
            $payload['pipeline'] = $deal;
        }
        return $payload;
    }

    private function validate_requirements( array $deal, string $target, array $context ) {
        if ( 'lost' === $target && empty( $context['loss_reason'] ) && empty( $deal['loss_reason'] ) ) {
            return new WP_Error( 'algq_pipeline_loss_reason_required', 'A loss reason is required before marking a deal Lost.', array( 'status' => 400 ) );
        }
        if ( 'closed' === $target && ( empty( $deal['closing_date'] ) || empty( $deal['disposition'] ) ) ) {
            return new WP_Error( 'algq_pipeline_closing_data_required', 'Closing date and disposition are required before marking a deal Closed.', array( 'status' => 400 ) );
        }
        if ( 'offer_sent' === $target && function_exists( 'algq_offer_generator_has_approved_offer' ) && ! algq_offer_generator_has_approved_offer( (int) $deal['id'] ) ) {
            return new WP_Error( 'algq_pipeline_offer_required', 'An approved offer is required before moving to Offer Sent.', array( 'status' => 400 ) );
        }
        if ( 'under_contract' === $target && function_exists( 'algq_documents_has_executed_contract' ) && ! algq_documents_has_executed_contract( (int) $deal['id'] ) ) {
            return new WP_Error( 'algq_pipeline_contract_required', 'An executed or acknowledged contract is required before moving to Under Contract.', array( 'status' => 400 ) );
        }
        return apply_filters( 'algq_pipeline_validate_transition', true, $deal, $target, $context );
    }

    private function generate_deal_number(): string {
        global $wpdb;
        $table = ALGQ_Pipeline_Database::tables()['deals'];
        $prefix = 'ARE-' . gmdate( 'Y' ) . '-';
        $last = $wpdb->get_var( $wpdb->prepare( "SELECT deal_number FROM {$table} WHERE deal_number LIKE %s ORDER BY id DESC LIMIT 1", $prefix . '%' ) );
        $sequence = $last ? (int) substr( $last, -6 ) + 1 : 1;
        return $prefix . str_pad( (string) $sequence, 6, '0', STR_PAD_LEFT );
    }

    private function sanitize_priority( $priority ): string {
        $priority = sanitize_key( $priority );
        return in_array( $priority, array( 'low', 'normal', 'high', 'critical' ), true ) ? $priority : 'normal';
    }

    private function audit( string $event, int $deal_id, array $data ): void {
        if ( function_exists( 'algq_log_event' ) ) {
            algq_log_event( $event, array( 'plugin' => 'algq-pipeline-crm', 'deal_id' => $deal_id, 'data' => $data ) );
        } else {
            do_action( 'algq_audit_event', $event, array( 'plugin' => 'algq-pipeline-crm', 'deal_id' => $deal_id, 'data' => $data ) );
        }
    }
}
