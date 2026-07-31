<?php

defined( 'ABSPATH' ) || exit;

final class ALGQ_Automation_Actions {
    public static function registry(): array {
        $actions = array(
            'log_only' => array(
                'label'       => __( 'Log Event Only', 'algq-automation-engine' ),
                'description' => __( 'Records the event without changing another record.', 'algq-automation-engine' ),
            ),
            'create_task' => array(
                'label'       => __( 'Create Task', 'algq-automation-engine' ),
                'description' => __( 'Creates an auditable platform task.', 'algq-automation-engine' ),
            ),
            'send_email' => array(
                'label'       => __( 'Send Transactional Email', 'algq-automation-engine' ),
                'description' => __( 'Uses the Algonquian Mail Gateway when available.', 'algq-automation-engine' ),
            ),
            'notify_admin' => array(
                'label'       => __( 'Notify Administrator', 'algq-automation-engine' ),
                'description' => __( 'Sends a concise administrative alert.', 'algq-automation-engine' ),
            ),
            'generate_document' => array(
                'label'       => __( 'Request Document Generation', 'algq-automation-engine' ),
                'description' => __( 'Dispatches a controlled document-generation request.', 'algq-automation-engine' ),
            ),
            'request_signature' => array(
                'label'       => __( 'Request Signature Workflow', 'algq-automation-engine' ),
                'description' => __( 'Dispatches a signature request to the signature engine.', 'algq-automation-engine' ),
            ),
            'archive_record' => array(
                'label'       => __( 'Request Record Archive', 'algq-automation-engine' ),
                'description' => __( 'Dispatches a controlled archive request.', 'algq-automation-engine' ),
            ),
            'platform_action' => array(
                'label'       => __( 'Dispatch Approved Platform Action', 'algq-automation-engine' ),
                'description' => __( 'Runs an approved algq-prefixed integration hook.', 'algq-automation-engine' ),
            ),
        );

        return apply_filters( 'algq_automation_actions', $actions );
    }

    public static function execute( string $action_key, array $payload, array $context ): true|WP_Error {
        $payload = self::replace_tokens( $payload, $context );

        return match ( $action_key ) {
            'log_only'          => true,
            'create_task'       => self::create_task( $payload, $context ),
            'send_email'        => self::send_email( $payload, $context ),
            'notify_admin'      => self::notify_admin( $payload, $context ),
            'generate_document' => self::dispatch( 'algq_automation_generate_document', $payload, $context ),
            'request_signature' => self::dispatch( 'algq_automation_request_signature', $payload, $context ),
            'archive_record'    => self::dispatch( 'algq_automation_archive_record', $payload, $context ),
            'platform_action'   => self::platform_action( $payload, $context ),
            default             => self::custom_action( $action_key, $payload, $context ),
        };
    }

    private static function create_task( array $payload, array $context ): true|WP_Error {
        global $wpdb;

        $title = sanitize_text_field( (string) ( $payload['title'] ?? '' ) );

        if ( '' === $title ) {
            return new WP_Error( 'algq_task_title_required', __( 'Task title is required.', 'algq-automation-engine' ) );
        }

        $tables = ALGQ_Automation_DB::tables();
        $result = $wpdb->insert(
            $tables['tasks'],
            array(
                'uuid'                => wp_generate_uuid4(),
                'rule_id'             => absint( $context['rule_id'] ?? 0 ) ?: null,
                'job_id'              => absint( $context['job_id'] ?? 0 ) ?: null,
                'task_title'          => $title,
                'task_description'    => sanitize_textarea_field( (string) ( $payload['description'] ?? '' ) ),
                'task_status'         => 'pending',
                'priority'            => sanitize_key( (string) ( $payload['priority'] ?? 'normal' ) ),
                'assigned_user'       => absint( $payload['assigned_user'] ?? 0 ) ?: null,
                'related_object_type' => sanitize_key( (string) ( $context['object_type'] ?? '' ) ),
                'related_object_id'   => absint( $context['object_id'] ?? 0 ) ?: null,
                'due_at'              => self::normalize_date( $payload['due_at'] ?? null ),
                'created_at'          => current_time( 'mysql', true ),
            ),
            array( '%s', '%d', '%d', '%s', '%s', '%s', '%s', '%d', '%s', '%d', '%s', '%s' )
        );

        if ( false === $result ) {
            return new WP_Error( 'algq_task_insert_failed', __( 'The automation task could not be created.', 'algq-automation-engine' ) );
        }

        do_action( 'algq_automation_task_created', $wpdb->insert_id, $context );

        return true;
    }

    private static function send_email( array $payload, array $context ): true|WP_Error {
        $to      = sanitize_email( (string) ( $payload['to'] ?? '' ) );
        $subject = sanitize_text_field( (string) ( $payload['subject'] ?? '' ) );
        $message = wp_kses_post( (string) ( $payload['message'] ?? '' ) );

        if ( ! is_email( $to ) || '' === $subject || '' === $message ) {
            return new WP_Error( 'algq_invalid_email_action', __( 'A valid recipient, subject, and message are required.', 'algq-automation-engine' ) );
        }

        if ( function_exists( 'algq_send_mail' ) ) {
            $sent = algq_send_mail(
                array(
                    'to'         => $to,
                    'subject'    => $subject,
                    'message'    => $message,
                    'module'     => 'automation-engine',
                    'event'      => sanitize_key( (string) ( $context['event_key'] ?? 'automation' ) ),
                    'related_id' => absint( $context['object_id'] ?? 0 ),
                )
            );
        } else {
            $sent = wp_mail( $to, $subject, $message );
        }

        if ( is_wp_error( $sent ) ) {
            return $sent;
        }

        return $sent ? true : new WP_Error( 'algq_email_failed', __( 'The transactional email was not accepted for delivery.', 'algq-automation-engine' ) );
    }

    private static function notify_admin( array $payload, array $context ): true|WP_Error {
        $payload['to']      = get_option( 'admin_email' );
        $payload['subject'] = $payload['subject'] ?? __( 'Algonquian Automation Alert', 'algq-automation-engine' );
        $payload['message'] = $payload['message'] ?? sprintf(
            /* translators: %s event key. */
            __( 'Automation event received: %s', 'algq-automation-engine' ),
            sanitize_key( (string) ( $context['event_key'] ?? 'unknown' ) )
        );

        return self::send_email( $payload, $context );
    }

    private static function dispatch( string $hook, array $payload, array $context ): true {
        do_action( $hook, $payload, $context );
        return true;
    }

    private static function platform_action( array $payload, array $context ): true|WP_Error {
        $hook = sanitize_key( (string) ( $payload['hook'] ?? '' ) );

        if ( ! str_starts_with( $hook, 'algq_' ) ) {
            return new WP_Error( 'algq_disallowed_hook', __( 'Only approved algq-prefixed hooks may be dispatched.', 'algq-automation-engine' ) );
        }

        $allowed = apply_filters( 'algq_automation_allowed_platform_hooks', array() );

        if ( ! in_array( $hook, $allowed, true ) ) {
            return new WP_Error( 'algq_unregistered_hook', __( 'The requested platform hook is not registered for automation.', 'algq-automation-engine' ) );
        }

        do_action( $hook, $payload, $context );
        return true;
    }

    private static function custom_action( string $action_key, array $payload, array $context ): true|WP_Error {
        $result = apply_filters( 'algq_automation_execute_action', null, $action_key, $payload, $context );

        if ( true === $result || is_wp_error( $result ) ) {
            return $result;
        }

        return new WP_Error( 'algq_unknown_action', __( 'The automation action is not registered.', 'algq-automation-engine' ) );
    }

    private static function replace_tokens( mixed $value, array $context ): mixed {
        if ( is_array( $value ) ) {
            foreach ( $value as $key => $item ) {
                $value[ $key ] = self::replace_tokens( $item, $context );
            }
            return $value;
        }

        if ( ! is_string( $value ) ) {
            return $value;
        }

        $tokens = array(
            '{{event_key}}'   => (string) ( $context['event_key'] ?? '' ),
            '{{object_type}}' => (string) ( $context['object_type'] ?? '' ),
            '{{object_id}}'   => (string) ( $context['object_id'] ?? '' ),
            '{{rule_id}}'     => (string) ( $context['rule_id'] ?? '' ),
            '{{job_id}}'      => (string) ( $context['job_id'] ?? '' ),
        );

        return strtr( $value, $tokens );
    }

    private static function normalize_date( mixed $date ): ?string {
        if ( empty( $date ) ) {
            return null;
        }

        $timestamp = strtotime( (string) $date );
        return $timestamp ? gmdate( 'Y-m-d H:i:s', $timestamp ) : null;
    }
}
