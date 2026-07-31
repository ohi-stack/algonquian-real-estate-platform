<?php

defined( 'ABSPATH' ) || exit;

final class ALGQ_Automation_Engine {
    public static function register(): void {
        add_action( 'algq_deal_status_changed', array( __CLASS__, 'deal_status_changed' ), 10, 3 );
        add_action( 'algq_document_generated', array( __CLASS__, 'document_generated' ), 10, 2 );
        add_action( 'algq_offer_generated', array( __CLASS__, 'offer_generated' ), 10, 2 );
        add_action( 'algq_signature_completed', array( __CLASS__, 'signature_completed' ), 10, 2 );
        add_action( 'algq_buyer_interest_received', array( __CLASS__, 'buyer_interest_received' ), 10, 2 );
        add_action( 'algq_funding_status_changed', array( __CLASS__, 'funding_status_changed' ), 10, 3 );
        add_action( 'algq_stripe_event', array( __CLASS__, 'stripe_event' ), 10, 2 );
        add_action( 'algq_automation_process_queue', array( __CLASS__, 'process_queue' ) );
        add_filter( 'algq_platform_health_checks', array( __CLASS__, 'register_health_check' ) );

        self::schedule_next_run();
    }

    public static function triggers(): array {
        $triggers = array(
            'deal.status_changed'     => __( 'Deal Status Changed', 'algq-automation-engine' ),
            'document.generated'      => __( 'Document Generated', 'algq-automation-engine' ),
            'offer.generated'         => __( 'Offer Generated', 'algq-automation-engine' ),
            'signature.completed'     => __( 'Signature Completed', 'algq-automation-engine' ),
            'buyer.interest_received' => __( 'Buyer Interest Received', 'algq-automation-engine' ),
            'funding.status_changed'  => __( 'Funding Status Changed', 'algq-automation-engine' ),
            'stripe.event'            => __( 'Stripe Event Received', 'algq-automation-engine' ),
            'automation.manual_test'  => __( 'Manual Test Event', 'algq-automation-engine' ),
        );

        return apply_filters( 'algq_automation_triggers', $triggers );
    }

    public static function deal_status_changed( int $deal_id, string $old_status, string $new_status ): void {
        self::capture_event( 'deal.status_changed', 'deal', $deal_id, compact( 'old_status', 'new_status' ) );
    }

    public static function document_generated( int $document_id, int $deal_id = 0 ): void {
        self::capture_event( 'document.generated', 'document', $document_id, array( 'deal_id' => $deal_id ) );
    }

    public static function offer_generated( int $offer_id, int $deal_id = 0 ): void {
        self::capture_event( 'offer.generated', 'offer', $offer_id, array( 'deal_id' => $deal_id ) );
    }

    public static function signature_completed( int $request_id, int $document_id = 0 ): void {
        self::capture_event( 'signature.completed', 'signature_request', $request_id, array( 'document_id' => $document_id ) );
    }

    public static function buyer_interest_received( int $interest_id, int $deal_id = 0 ): void {
        self::capture_event( 'buyer.interest_received', 'buyer_interest', $interest_id, array( 'deal_id' => $deal_id ) );
    }

    public static function funding_status_changed( int $funding_id, string $old_status, string $new_status ): void {
        self::capture_event( 'funding.status_changed', 'funding', $funding_id, compact( 'old_status', 'new_status' ) );
    }

    public static function stripe_event( string $event_type, array $event = array() ): void {
        self::capture_event(
            'stripe.event',
            'stripe_event',
            0,
            array(
                'stripe_event_type' => sanitize_text_field( $event_type ),
                'event'             => ALGQ_Automation_Security::redact( $event ),
            )
        );
    }

    public static function capture_event( string $event_key, string $object_type = '', int $object_id = 0, array $payload = array() ): array {
        global $wpdb;

        $settings = self::settings();

        if ( empty( $settings['enabled'] ) ) {
            return array();
        }

        $tables = ALGQ_Automation_DB::tables();
        $rules  = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$tables['rules']} WHERE trigger_key = %s AND status = 'active' ORDER BY priority ASC, id ASC",
                sanitize_key( $event_key )
            ),
            ARRAY_A
        );

        $queued = array();

        foreach ( $rules as $rule ) {
            $conditions = json_decode( (string) $rule['conditions'], true );
            $conditions = is_array( $conditions ) ? $conditions : array();
            $context    = array(
                'event_key'   => sanitize_key( $event_key ),
                'object_type' => sanitize_key( $object_type ),
                'object_id'   => absint( $object_id ),
                'payload'     => ALGQ_Automation_Security::redact( $payload ),
            );

            if ( ! self::conditions_match( $conditions, $context ) ) {
                self::log( 'info', 'skipped', __( 'Automation conditions did not match.', 'algq-automation-engine' ), $context, (int) $rule['id'] );
                continue;
            }

            $job_id = self::enqueue( $rule, $context );

            if ( $job_id ) {
                $queued[] = $job_id;
            }
        }

        self::schedule_next_run( 5 );
        do_action( 'algq_automation_event_captured', $event_key, $object_type, $object_id, $queued );

        return $queued;
    }

    private static function enqueue( array $rule, array $context ): int {
        global $wpdb;

        $tables      = ALGQ_Automation_DB::tables();
        $settings    = self::settings();
        $window      = max( 1, absint( $settings['dedupe_window'] ?? 300 ) );
        $bucket      = (int) floor( time() / $window );
        $fingerprint = wp_json_encode( array( $context['payload'], $bucket ) );
        $key         = hash( 'sha256', $rule['id'] . '|' . $context['event_key'] . '|' . $context['object_type'] . '|' . $context['object_id'] . '|' . $fingerprint );
        $now         = current_time( 'mysql', true );

        $result = $wpdb->insert(
            $tables['jobs'],
            array(
                'uuid'            => wp_generate_uuid4(),
                'rule_id'         => absint( $rule['id'] ),
                'event_key'       => $context['event_key'],
                'object_type'     => $context['object_type'],
                'object_id'       => $context['object_id'] ?: null,
                'idempotency_key' => $key,
                'payload'         => wp_json_encode( $context ),
                'status'          => 'pending',
                'attempts'        => 0,
                'max_attempts'    => max( 1, absint( $rule['max_attempts'] ) ),
                'available_at'    => $now,
                'created_at'      => $now,
                'updated_at'      => $now,
            ),
            array( '%s', '%d', '%s', '%s', '%d', '%s', '%s', '%s', '%d', '%d', '%s', '%s', '%s' )
        );

        if ( false === $result ) {
            if ( str_contains( strtolower( (string) $wpdb->last_error ), 'duplicate' ) ) {
                self::log( 'info', 'duplicate', __( 'Duplicate automation job suppressed.', 'algq-automation-engine' ), $context, (int) $rule['id'] );
                return 0;
            }

            self::log( 'error', 'enqueue_failed', $wpdb->last_error ?: __( 'Automation job could not be queued.', 'algq-automation-engine' ), $context, (int) $rule['id'] );
            return 0;
        }

        self::log( 'info', 'queued', __( 'Automation job queued.', 'algq-automation-engine' ), $context, (int) $rule['id'], (int) $wpdb->insert_id );
        return (int) $wpdb->insert_id;
    }

    public static function process_queue(): void {
        global $wpdb;

        $settings = self::settings();

        if ( empty( $settings['enabled'] ) || empty( $settings['queue_enabled'] ) ) {
            self::schedule_next_run();
            return;
        }

        $tables = ALGQ_Automation_DB::tables();
        $batch  = min( 100, max( 1, absint( $settings['batch_size'] ?? 10 ) ) );
        $now    = current_time( 'mysql', true );
        $jobs   = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$tables['jobs']} WHERE status IN ('pending','retry') AND available_at <= %s AND locked_at IS NULL ORDER BY id ASC LIMIT %d",
                $now,
                $batch
            ),
            ARRAY_A
        );

        foreach ( $jobs as $job ) {
            self::process_job( $job );
        }

        self::schedule_next_run();
    }

    public static function process_job( array $job ): bool {
        global $wpdb;

        $tables = ALGQ_Automation_DB::tables();
        $lock   = wp_generate_uuid4();
        $now    = current_time( 'mysql', true );
        $locked = $wpdb->query(
            $wpdb->prepare(
                "UPDATE {$tables['jobs']} SET status = 'running', locked_at = %s, locked_by = %s, updated_at = %s WHERE id = %d AND status IN ('pending','retry') AND locked_at IS NULL",
                $now,
                $lock,
                $now,
                absint( $job['id'] )
            )
        );

        if ( 1 !== $locked ) {
            return false;
        }

        $job  = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$tables['jobs']} WHERE id = %d", absint( $job['id'] ) ), ARRAY_A );
        $rule = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$tables['rules']} WHERE id = %d", absint( $job['rule_id'] ) ), ARRAY_A );

        if ( ! $rule || 'active' !== $rule['status'] ) {
            self::fail_job( $job, new WP_Error( 'algq_rule_unavailable', __( 'The automation rule is no longer active.', 'algq-automation-engine' ) ), true );
            return false;
        }

        $context = json_decode( (string) $job['payload'], true );
        $context = is_array( $context ) ? $context : array();
        $context['job_id']  = (int) $job['id'];
        $context['rule_id'] = (int) $rule['id'];
        $payload = json_decode( (string) $rule['action_payload'], true );
        $payload = is_array( $payload ) ? $payload : array();

        try {
            $result = ALGQ_Automation_Actions::execute( sanitize_key( $rule['action_key'] ), $payload, $context );
        } catch ( Throwable $throwable ) {
            $result = new WP_Error( 'algq_action_exception', $throwable->getMessage() );
        }

        if ( is_wp_error( $result ) ) {
            self::fail_job( $job, $result );
            return false;
        }

        $wpdb->update(
            $tables['jobs'],
            array(
                'status'       => 'completed',
                'attempts'     => (int) $job['attempts'] + 1,
                'locked_at'    => null,
                'locked_by'    => null,
                'last_error'   => null,
                'updated_at'   => $now,
                'completed_at' => $now,
            ),
            array( 'id' => (int) $job['id'] ),
            array( '%s', '%d', '%s', '%s', '%s', '%s', '%s' ),
            array( '%d' )
        );

        $wpdb->update(
            $tables['rules'],
            array( 'last_run_at' => $now, 'updated_at' => $now ),
            array( 'id' => (int) $rule['id'] ),
            array( '%s', '%s' ),
            array( '%d' )
        );

        self::log( 'info', 'completed', __( 'Automation job completed.', 'algq-automation-engine' ), $context, (int) $rule['id'], (int) $job['id'] );
        do_action( 'algq_automation_job_completed', (int) $job['id'], $context );
        return true;
    }

    private static function fail_job( array $job, WP_Error $error, bool $terminal = false ): void {
        global $wpdb;

        $tables   = ALGQ_Automation_DB::tables();
        $attempts = (int) $job['attempts'] + 1;
        $max      = max( 1, (int) $job['max_attempts'] );
        $dead     = $terminal || $attempts >= $max;
        $delay    = min( HOUR_IN_SECONDS, 60 * ( 2 ** max( 0, $attempts - 1 ) ) );
        $status   = $dead ? 'dead' : 'retry';
        $now      = current_time( 'mysql', true );

        $wpdb->update(
            $tables['jobs'],
            array(
                'status'       => $status,
                'attempts'     => $attempts,
                'available_at' => gmdate( 'Y-m-d H:i:s', time() + $delay ),
                'locked_at'    => null,
                'locked_by'    => null,
                'last_error'   => sanitize_textarea_field( $error->get_error_message() ),
                'updated_at'   => $now,
            ),
            array( 'id' => (int) $job['id'] ),
            array( '%s', '%d', '%s', '%s', '%s', '%s', '%s' ),
            array( '%d' )
        );

        $context = json_decode( (string) $job['payload'], true );
        $context = is_array( $context ) ? $context : array();
        self::log( 'error', $status, $error->get_error_message(), $context, (int) $job['rule_id'], (int) $job['id'] );
        do_action( $dead ? 'algq_automation_job_dead' : 'algq_automation_job_retrying', (int) $job['id'], $error );
    }

    public static function retry_job( int $job_id ): bool {
        global $wpdb;

        $tables = ALGQ_Automation_DB::tables();
        $result = $wpdb->update(
            $tables['jobs'],
            array(
                'status'       => 'retry',
                'attempts'     => 0,
                'available_at' => current_time( 'mysql', true ),
                'locked_at'    => null,
                'locked_by'    => null,
                'last_error'   => null,
                'updated_at'   => current_time( 'mysql', true ),
            ),
            array( 'id' => absint( $job_id ) ),
            array( '%s', '%d', '%s', '%s', '%s', '%s', '%s' ),
            array( '%d' )
        );

        if ( false !== $result ) {
            self::schedule_next_run( 5 );
        }

        return false !== $result;
    }

    private static function conditions_match( array $conditions, array $context ): bool {
        foreach ( $conditions as $condition ) {
            if ( ! is_array( $condition ) ) {
                continue;
            }

            $field    = sanitize_text_field( (string) ( $condition['field'] ?? '' ) );
            $operator = sanitize_key( (string) ( $condition['operator'] ?? 'equals' ) );
            $expected = $condition['value'] ?? null;
            $actual   = self::value_from_path( $context, $field );

            $matches = match ( $operator ) {
                'equals'     => $actual == $expected,
                'not_equals' => $actual != $expected,
                'in'         => is_array( $expected ) && in_array( $actual, $expected, true ),
                'not_in'     => is_array( $expected ) && ! in_array( $actual, $expected, true ),
                'exists'     => null !== $actual,
                'empty'      => empty( $actual ),
                'contains'   => is_string( $actual ) && str_contains( $actual, (string) $expected ),
                'gt'         => is_numeric( $actual ) && $actual > $expected,
                'gte'        => is_numeric( $actual ) && $actual >= $expected,
                'lt'         => is_numeric( $actual ) && $actual < $expected,
                'lte'        => is_numeric( $actual ) && $actual <= $expected,
                default      => false,
            };

            if ( ! $matches ) {
                return false;
            }
        }

        return true;
    }

    private static function value_from_path( array $context, string $path ): mixed {
        if ( '' === $path ) {
            return null;
        }

        $value = $context;

        foreach ( explode( '.', $path ) as $segment ) {
            if ( ! is_array( $value ) || ! array_key_exists( $segment, $value ) ) {
                return null;
            }
            $value = $value[ $segment ];
        }

        return $value;
    }

    public static function log( string $level, string $status, string $message, array $context = array(), int $rule_id = 0, int $job_id = 0 ): void {
        global $wpdb;

        $settings = self::settings();
        if ( empty( $settings['logging_enabled'] ) ) {
            return;
        }

        $tables = ALGQ_Automation_DB::tables();
        $clean  = ALGQ_Automation_Security::redact( $context );

        $wpdb->insert(
            $tables['logs'],
            array(
                'uuid'        => wp_generate_uuid4(),
                'rule_id'     => $rule_id ?: null,
                'job_id'      => $job_id ?: null,
                'event_key'   => sanitize_key( (string) ( $context['event_key'] ?? 'automation.system' ) ),
                'object_type' => sanitize_key( (string) ( $context['object_type'] ?? '' ) ),
                'object_id'   => absint( $context['object_id'] ?? 0 ) ?: null,
                'level'       => sanitize_key( $level ),
                'status'      => sanitize_key( $status ),
                'message'     => sanitize_textarea_field( $message ),
                'context'     => wp_json_encode( $clean ),
                'user_id'     => get_current_user_id() ?: null,
                'created_at'  => current_time( 'mysql', true ),
            ),
            array( '%s', '%d', '%d', '%s', '%s', '%d', '%s', '%s', '%s', '%s', '%d', '%s' )
        );

        if ( function_exists( 'algq_log_event' ) ) {
            algq_log_event(
                array(
                    'event'   => 'automation.' . sanitize_key( $status ),
                    'plugin'  => 'algq-automation-engine',
                    'status'  => sanitize_key( $status ),
                    'message' => sanitize_textarea_field( $message ),
                    'context' => $clean,
                )
            );
        }
    }

    public static function settings(): array {
        return wp_parse_args(
            get_option( 'algq_automation_settings', array() ),
            array(
                'enabled'          => 1,
                'logging_enabled'  => 1,
                'queue_enabled'    => 1,
                'batch_size'       => 10,
                'default_attempts' => 3,
                'dedupe_window'    => 300,
                'delete_data_on_uninstall' => 0,
            )
        );
    }

    public static function schedule_next_run( int $delay = 60 ): void {
        if ( ! wp_next_scheduled( 'algq_automation_process_queue' ) ) {
            wp_schedule_single_event( time() + max( 5, $delay ), 'algq_automation_process_queue' );
        }
    }

    public static function health(): array {
        global $wpdb;

        $tables = ALGQ_Automation_DB::tables();
        $status = 'healthy';
        $issues = array();

        foreach ( $tables as $name => $table ) {
            if ( ! ALGQ_Automation_DB::table_exists( $table ) ) {
                $status   = 'failed';
                $issues[] = sprintf( '%s table missing', $name );
            }
        }

        $dead = 0;
        if ( ALGQ_Automation_DB::table_exists( $tables['jobs'] ) ) {
            $dead = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$tables['jobs']} WHERE status = 'dead'" );
            if ( $dead > 0 && 'failed' !== $status ) {
                $status   = 'degraded';
                $issues[] = sprintf( '%d dead-letter job(s)', $dead );
            }
        }

        if ( ! wp_next_scheduled( 'algq_automation_process_queue' ) ) {
            $status   = 'failed' === $status ? 'failed' : 'degraded';
            $issues[] = 'queue processor not scheduled';
        }

        return array(
            'plugin'  => 'algq-automation-engine',
            'status'  => $status,
            'version' => ALGQ_AUTOMATION_VERSION,
            'issues'  => $issues,
            'metrics' => array( 'dead_jobs' => $dead ),
        );
    }

    public static function register_health_check( array $checks ): array {
        $checks['automation_engine'] = array( __CLASS__, 'health' );
        return $checks;
    }
}
