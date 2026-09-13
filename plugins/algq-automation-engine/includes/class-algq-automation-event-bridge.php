<?php
/**
 * Canonical transaction-event ingestion for Automation Engine 2.1.
 *
 * Domain plugins remain authoritative for their records. This bridge only
 * translates durable business milestones into automation triggers.
 *
 * @package AlgonquianAutomationEngine
 */

defined( 'ABSPATH' ) || exit;

final class ALGQ_Automation_Event_Bridge {
    /**
     * Register event adapters and trigger-catalog normalization.
     */
    public static function register(): void {
        add_filter( 'algq_automation_triggers', array( __CLASS__, 'register_triggers' ), 99 );

        // Cross-plugin generic event contract already emitted by MAO/Offer integrations.
        add_action( 'algq_automation_event', array( __CLASS__, 'generic_event' ), 10, 2 );

        // Pipeline is authoritative for the canonical deal lifecycle.
        add_action( 'algq_pipeline_stage_changed', array( __CLASS__, 'pipeline_stage_changed' ), 10, 4 );

        // Human-control and relationship/service event adapters.
        add_action( 'algq_approval_requested', array( __CLASS__, 'approval_requested' ), 10, 3 );
        add_action( 'algq_funding_required', array( __CLASS__, 'funding_required' ), 10, 2 );
        add_action( 'algq_closing_scheduled', array( __CLASS__, 'closing_scheduled' ), 10, 2 );
        add_action( 'algq_buyer_interest_received', array( __CLASS__, 'buyer_interest' ), 20, 2 );
        add_action( 'algq_buyer_activity', array( __CLASS__, 'buyer_activity' ), 10, 3 );
        add_action( 'algq_service_follow_up_due', array( __CLASS__, 'service_follow_up_due' ), 10, 3 );
        add_action( 'algq_customer_follow_up_due', array( __CLASS__, 'customer_follow_up_due' ), 10, 3 );
        add_action( 'algq_customer_service_activity', array( __CLASS__, 'customer_service_activity' ), 10, 3 );
    }

    /**
     * Canonical business events available to workflow rules.
     *
     * @return array<string,string>
     */
    public static function canonical_events(): array {
        return array(
            'deal.stage_changed'         => __( 'Deal Stage Changed', 'algq-automation-engine' ),
            'deal.qualified'             => __( 'Deal Qualified', 'algq-automation-engine' ),
            'underwriting.completed'     => __( 'Underwriting Completed', 'algq-automation-engine' ),
            'approval.requested'         => __( 'Human Approval Requested', 'algq-automation-engine' ),
            'offer.sent'                 => __( 'Offer Sent', 'algq-automation-engine' ),
            'funding.required'           => __( 'Funding Required', 'algq-automation-engine' ),
            'closing.scheduled'          => __( 'Closing Scheduled', 'algq-automation-engine' ),
            'service.follow_up_due'      => __( 'Property Service Follow-Up Due', 'algq-automation-engine' ),
            'buyer.activity'             => __( 'Buyer Activity', 'algq-automation-engine' ),
            'customer.follow_up_due'     => __( 'Customer Follow-Up Due', 'algq-automation-engine' ),
            'customer.service_activity'  => __( 'Customer Service Activity', 'algq-automation-engine' ),
            'offer.saved'                => __( 'Offer Saved', 'algq-automation-engine' ),
            'offer.document_generated'   => __( 'Offer Document Generated', 'algq-automation-engine' ),
        );
    }

    /**
     * Normalize the engine trigger registry to the storage-safe keys used by
     * the 2.0 rule table while exposing the dotted canonical name in the UI.
     *
     * Automation 2.0 used sanitize_key() when saving/capturing triggers. That
     * removes dots. Keeping this compatibility layer means existing 2.0 rules
     * continue to work while 2.1 presents the canonical event vocabulary.
     *
     * @param array<string,string> $triggers Existing trigger registry.
     * @return array<string,string>
     */
    public static function register_triggers( array $triggers ): array {
        $catalog = array_merge( $triggers, self::canonical_events() );
        $result  = array();

        foreach ( $catalog as $event_key => $label ) {
            $canonical = self::normalize_event_key( (string) $event_key );
            $storage   = self::storage_key( $canonical );

            if ( '' === $storage ) {
                continue;
            }

            $result[ $storage ] = sprintf(
                /* translators: 1: label, 2: canonical event key. */
                __( '%1$s — %2$s', 'algq-automation-engine' ),
                $label,
                $canonical
            );
        }

        return $result;
    }

    /**
     * Receive an established generic plugin event and translate known aliases.
     */
    public static function generic_event( string $event_key, array $payload = array() ): void {
        $event_key = self::normalize_event_key( $event_key );
        $aliases   = array(
            'mao.underwriting_approved' => 'underwriting.completed',
            'underwriting.completed'    => 'underwriting.completed',
            'approval.requested'        => 'approval.requested',
            'offer.sent'                => 'offer.sent',
            'funding.required'          => 'funding.required',
            'closing.scheduled'         => 'closing.scheduled',
            'service.follow_up_due'     => 'service.follow_up_due',
            'buyer.activity'            => 'buyer.activity',
            'customer.follow_up_due'    => 'customer.follow_up_due',
            'customer.service_activity' => 'customer.service_activity',
            'offer_saved'               => 'offer.saved',
            'offer.saved'               => 'offer.saved',
            'offer_document_generated'  => 'offer.document_generated',
            'offer.document_generated'  => 'offer.document_generated',
        );

        if ( ! isset( $aliases[ $event_key ] ) ) {
            do_action( 'algq_automation_unmapped_event', $event_key, ALGQ_Automation_Security::redact( $payload ) );
            return;
        }

        $canonical = $aliases[ $event_key ];
        $deal_id   = absint( $payload['deal_id'] ?? 0 );
        $object_id = self::payload_object_id( $payload );
        $type      = self::event_object_type( $canonical );

        self::dispatch(
            $canonical,
            $type,
            $object_id ?: $deal_id,
            array_merge(
                $payload,
                array(
                    'deal_id'      => $deal_id,
                    'source_event' => $event_key,
                )
            )
        );
    }

    /**
     * Translate canonical Pipeline stage transitions into transaction events.
     *
     * @param mixed $context Pipeline transition context.
     */
    public static function pipeline_stage_changed( int $deal_id, string $old_stage, string $new_stage, $context = array() ): void {
        $old_stage = sanitize_key( $old_stage );
        $new_stage = sanitize_key( $new_stage );
        $context   = is_array( $context ) ? $context : array();
        $safe_ctx  = array_filter(
            array(
                'source'        => sanitize_key( (string) ( $context['source'] ?? 'algq-pipeline-crm' ) ),
                'reason'        => sanitize_text_field( (string) ( $context['reason'] ?? '' ) ),
                'automation_id' => absint( $context['automation_id'] ?? 0 ),
            ),
            static fn( $value ) => '' !== $value && 0 !== $value
        );

        $base = array(
            'deal_id'       => $deal_id,
            'old_stage'     => $old_stage,
            'new_stage'     => $new_stage,
            'source_plugin' => 'algq-pipeline-crm',
            'context'       => $safe_ctx,
        );

        self::dispatch( 'deal.stage_changed', 'deal', $deal_id, $base );

        $pre_underwriting = array( 'new_intake', 'contact_attempted', 'contact_established', 'preliminary_review' );
        if ( 'underwriting' === $new_stage && in_array( $old_stage, $pre_underwriting, true ) ) {
            self::dispatch( 'deal.qualified', 'deal', $deal_id, $base );
        }

        if ( 'offer_sent' === $new_stage ) {
            self::dispatch( 'offer.sent', 'deal', $deal_id, $base );
        }

        if ( 'funding' === $new_stage ) {
            self::dispatch( 'funding.required', 'deal', $deal_id, $base );
        }

        if ( 'closing_scheduled' === $new_stage ) {
            self::dispatch( 'closing.scheduled', 'deal', $deal_id, $base );
        }
    }

    /**
     * Capture a human-approval request without approving the underlying action.
     *
     * @param mixed $subject_id Related record identifier.
     * @param mixed $context    Approval context.
     */
    public static function approval_requested( string $approval_type = '', $subject_id = 0, $context = array() ): void {
        $context = is_array( $context ) ? $context : array();
        self::dispatch(
            'approval.requested',
            'approval',
            absint( $subject_id ),
            array(
                'approval_type' => sanitize_key( $approval_type ),
                'subject_id'    => absint( $subject_id ),
                'deal_id'       => absint( $context['deal_id'] ?? 0 ),
                'context'       => ALGQ_Automation_Security::redact( $context ),
            )
        );
    }

    /** @param mixed $context */
    public static function funding_required( int $deal_id, $context = array() ): void {
        self::dispatch(
            'funding.required',
            'deal',
            $deal_id,
            array(
                'deal_id' => $deal_id,
                'context' => ALGQ_Automation_Security::redact( is_array( $context ) ? $context : array() ),
            )
        );
    }

    /** @param mixed $context */
    public static function closing_scheduled( int $deal_id, $context = array() ): void {
        self::dispatch(
            'closing.scheduled',
            'deal',
            $deal_id,
            array(
                'deal_id' => $deal_id,
                'context' => ALGQ_Automation_Security::redact( is_array( $context ) ? $context : array() ),
            )
        );
    }

    public static function buyer_interest( int $interest_id, int $deal_id = 0 ): void {
        self::dispatch(
            'buyer.activity',
            'buyer_interest',
            $interest_id,
            array(
                'activity'    => 'interest_received',
                'interest_id' => $interest_id,
                'deal_id'     => $deal_id,
                'source_plugin' => 'algq-buyer-portal',
            )
        );
    }

    /** @param mixed $context */
    public static function buyer_activity( string $activity, int $buyer_id = 0, $context = array() ): void {
        $context = is_array( $context ) ? $context : array();
        self::dispatch(
            'buyer.activity',
            'buyer',
            $buyer_id,
            array(
                'activity' => sanitize_key( $activity ),
                'buyer_id' => $buyer_id,
                'deal_id'  => absint( $context['deal_id'] ?? 0 ),
                'context'  => ALGQ_Automation_Security::redact( $context ),
            )
        );
    }

    /** @param mixed $context */
    public static function service_follow_up_due( string $service_type, int $record_id = 0, $context = array() ): void {
        self::dispatch(
            'service.follow_up_due',
            'service_record',
            $record_id,
            array(
                'service_type' => sanitize_key( $service_type ),
                'record_id'    => $record_id,
                'context'      => ALGQ_Automation_Security::redact( is_array( $context ) ? $context : array() ),
            )
        );
    }

    /** @param mixed $context */
    public static function customer_follow_up_due( string $relationship_type, int $customer_id = 0, $context = array() ): void {
        self::dispatch(
            'customer.follow_up_due',
            'customer',
            $customer_id,
            array(
                'relationship_type' => sanitize_key( $relationship_type ),
                'customer_id'       => $customer_id,
                'context'           => ALGQ_Automation_Security::redact( is_array( $context ) ? $context : array() ),
            )
        );
    }

    /** @param mixed $context */
    public static function customer_service_activity( string $activity, int $customer_id = 0, $context = array() ): void {
        self::dispatch(
            'customer.service_activity',
            'customer',
            $customer_id,
            array(
                'activity'    => sanitize_key( $activity ),
                'customer_id' => $customer_id,
                'context'     => ALGQ_Automation_Security::redact( is_array( $context ) ? $context : array() ),
            )
        );
    }

    /**
     * Dispatch a canonical event into the existing durable rule/queue engine.
     *
     * The exact dotted event key is preserved inside payload.canonical_event.
     * Automation 2.0's trigger column remains storage-compatible through
     * storage_key().
     *
     * @return array<int,int> Queued job IDs.
     */
    public static function dispatch( string $event_key, string $object_type = '', int $object_id = 0, array $payload = array() ): array {
        $event_key = self::normalize_event_key( $event_key );

        if ( ! isset( self::canonical_events()[ $event_key ] ) ) {
            return array();
        }

        $payload['canonical_event'] = $event_key;
        $payload['source_plugin']   = sanitize_key( (string) ( $payload['source_plugin'] ?? self::event_source( $event_key ) ) );
        $payload                   = ALGQ_Automation_Security::redact( $payload );

        $jobs = ALGQ_Automation_Engine::capture_event(
            self::storage_key( $event_key ),
            $object_type,
            $object_id,
            $payload
        );

        do_action( 'algq_automation_canonical_event_dispatched', $event_key, $object_type, $object_id, $payload, $jobs );

        return $jobs;
    }

    /**
     * Production-safe workflow templates. They are seeded as drafts so an
     * administrator must intentionally activate them after reviewing routing,
     * recipients, deadlines, and transaction-specific requirements.
     *
     * @return array<string,array<string,mixed>>
     */
    public static function workflow_templates(): array {
        return array(
            'deal.qualified' => array(
                'name'        => 'Qualified Deal → Underwriting Review',
                'description' => '[ARE 2.1 template: deal.qualified] Create the next-action task when a deal enters underwriting.',
                'action_key'  => 'create_task',
                'payload'     => array( 'title' => 'Review qualified deal for underwriting', 'description' => 'Confirm required underwriting inputs and the next acquisition action.', 'priority' => 'high', 'due_at' => '+1 day' ),
            ),
            'underwriting.completed' => array(
                'name'        => 'Underwriting Complete → Strategy Review',
                'description' => '[ARE 2.1 template: underwriting.completed] Route completed underwriting to human acquisition-strategy review.',
                'action_key'  => 'create_task',
                'payload'     => array( 'title' => 'Review underwriting and acquisition strategy', 'description' => 'Review assumptions, risks, proposed structure, and required human decision.', 'priority' => 'high', 'due_at' => '+1 day' ),
            ),
            'approval.requested' => array(
                'name'        => 'Approval Request → Administrator Alert',
                'description' => '[ARE 2.1 template: approval.requested] Notify an administrator that a consequential action is awaiting human approval.',
                'action_key'  => 'notify_admin',
                'payload'     => array( 'subject' => 'ARE approval requested', 'message' => 'A controlled ARE workflow is awaiting human approval. Review the related record before any consequential action proceeds.' ),
            ),
            'offer.sent' => array(
                'name'        => 'Offer Sent → Seller Follow-Up',
                'description' => '[ARE 2.1 template: offer.sent] Create a follow-up task after an authorized offer is sent.',
                'action_key'  => 'create_task',
                'payload'     => array( 'title' => 'Follow up on sent offer', 'description' => 'Record seller response, questions, expiration, and the next negotiation action.', 'priority' => 'high', 'due_at' => '+2 days' ),
            ),
            'funding.required' => array(
                'name'        => 'Funding Required → Capital Action',
                'description' => '[ARE 2.1 template: funding.required] Create a capital-resolution task without committing funds.',
                'action_key'  => 'create_task',
                'payload'     => array( 'title' => 'Resolve deal funding requirement', 'description' => 'Review funding need, candidate capital sources, deadlines, and required approvals.', 'priority' => 'high', 'due_at' => '+1 day' ),
            ),
            'closing.scheduled' => array(
                'name'        => 'Closing Scheduled → Readiness Review',
                'description' => '[ARE 2.1 template: closing.scheduled] Create a closing-readiness task.',
                'action_key'  => 'create_task',
                'payload'     => array( 'title' => 'Complete closing readiness review', 'description' => 'Confirm outstanding due diligence, documents, funding, approvals, insurance, title, and closing responsibilities.', 'priority' => 'high', 'due_at' => '+1 day' ),
            ),
            'service.follow_up_due' => array(
                'name'        => 'Property Service → Follow-Up',
                'description' => '[ARE 2.1 template: service.follow_up_due] Create a property-service follow-up task.',
                'action_key'  => 'create_task',
                'payload'     => array( 'title' => 'Complete property-service follow-up', 'description' => 'Review the service record, owner communication, vendor activity, and required next step.', 'priority' => 'normal', 'due_at' => '+1 day' ),
            ),
            'buyer.activity' => array(
                'name'        => 'Buyer Activity → Relationship Follow-Up',
                'description' => '[ARE 2.1 template: buyer.activity] Route meaningful buyer activity into an accountable next action.',
                'action_key'  => 'create_task',
                'payload'     => array( 'title' => 'Review buyer activity', 'description' => 'Review buyer interest, access status, criteria fit, and required response.', 'priority' => 'normal', 'due_at' => '+1 day' ),
            ),
            'customer.follow_up_due' => array(
                'name'        => 'Customer Relationship → Follow-Up',
                'description' => '[ARE 2.1 template: customer.follow_up_due] Create a customer-service follow-up task.',
                'action_key'  => 'create_task',
                'payload'     => array( 'title' => 'Complete customer follow-up', 'description' => 'Review the customer relationship, open service items, communications, and next action.', 'priority' => 'normal', 'due_at' => '+1 day' ),
            ),
        );
    }

    /**
     * Seed 2.1 templates as draft rules. No business action is auto-enabled.
     */
    public static function seed_templates(): void {
        global $wpdb;

        $tables = ALGQ_Automation_DB::tables();
        if ( ! ALGQ_Automation_DB::table_exists( $tables['rules'] ) ) {
            return;
        }

        $now = current_time( 'mysql', true );

        foreach ( self::workflow_templates() as $event_key => $template ) {
            $exists = (int) $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT COUNT(*) FROM {$tables['rules']} WHERE rule_name = %s",
                    $template['name']
                )
            );

            if ( $exists ) {
                continue;
            }

            $wpdb->insert(
                $tables['rules'],
                array(
                    'uuid'           => wp_generate_uuid4(),
                    'rule_name'      => sanitize_text_field( $template['name'] ),
                    'description'    => sanitize_textarea_field( $template['description'] ),
                    'trigger_key'    => self::storage_key( $event_key ),
                    'conditions'     => wp_json_encode( array() ),
                    'action_key'     => sanitize_key( $template['action_key'] ),
                    'action_payload' => wp_json_encode( $template['payload'] ),
                    'status'         => 'draft',
                    'priority'       => 100,
                    'max_attempts'   => 3,
                    'created_by'     => get_current_user_id() ?: null,
                    'updated_by'     => get_current_user_id() ?: null,
                    'created_at'     => $now,
                    'updated_at'     => $now,
                ),
                array( '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%d', '%d', '%d', '%d', '%s', '%s' )
            );
        }
    }

    public static function storage_key( string $event_key ): string {
        return sanitize_key( self::normalize_event_key( $event_key ) );
    }

    public static function normalize_event_key( string $event_key ): string {
        $event_key = strtolower( trim( $event_key ) );
        return (string) preg_replace( '/[^a-z0-9._-]/', '', $event_key );
    }

    public static function is_supported( string $event_key ): bool {
        return isset( self::canonical_events()[ self::normalize_event_key( $event_key ) ] );
    }

    private static function payload_object_id( array $payload ): int {
        foreach ( array( 'offer_id', 'underwriting_id', 'funding_id', 'interest_id', 'document_id', 'customer_id', 'buyer_id', 'deal_id' ) as $key ) {
            if ( ! empty( $payload[ $key ] ) ) {
                return absint( $payload[ $key ] );
            }
        }

        return 0;
    }

    private static function event_object_type( string $event_key ): string {
        $prefix = strstr( $event_key, '.', true );
        return sanitize_key( $prefix ?: 'automation_event' );
    }

    private static function event_source( string $event_key ): string {
        return match ( true ) {
            str_starts_with( $event_key, 'deal.' )        => 'algq-pipeline-crm',
            str_starts_with( $event_key, 'underwriting.' ) => 'algq-mao-engine',
            str_starts_with( $event_key, 'offer.' )       => 'algq-offer-generator',
            str_starts_with( $event_key, 'funding.' )     => 'algq-funding-tracker',
            str_starts_with( $event_key, 'closing.' )     => 'algq-pipeline-crm',
            str_starts_with( $event_key, 'buyer.' )       => 'algq-buyer-portal',
            str_starts_with( $event_key, 'service.' )     => 'algq-property-stewardship',
            str_starts_with( $event_key, 'customer.' )    => 'algq-pipeline-crm',
            default                                       => 'algq-automation-engine',
        };
    }
}