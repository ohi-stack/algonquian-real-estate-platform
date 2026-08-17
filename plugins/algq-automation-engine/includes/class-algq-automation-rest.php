<?php

defined( 'ABSPATH' ) || exit;

final class ALGQ_Automation_REST {
    public static function register(): void {
        add_action( 'rest_api_init', array( __CLASS__, 'routes' ) );
    }

    public static function routes(): void {
        register_rest_route(
            'algq/v1',
            '/automation/rules',
            array(
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => array( __CLASS__, 'rules' ),
                'permission_callback' => static fn(): bool => ALGQ_Automation_Security::can( 'view_algq_automation' ),
                'args'                => self::pagination_args(),
            )
        );

        register_rest_route(
            'algq/v1',
            '/automation/jobs',
            array(
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => array( __CLASS__, 'jobs' ),
                'permission_callback' => static fn(): bool => ALGQ_Automation_Security::can( 'view_algq_automation_logs' ),
                'args'                => array_merge(
                    self::pagination_args(),
                    array(
                        'status' => array( 'sanitize_callback' => 'sanitize_key' ),
                    )
                ),
            )
        );

        register_rest_route(
            'algq/v1',
            '/automation/jobs/(?P<id>\d+)/retry',
            array(
                'methods'             => WP_REST_Server::CREATABLE,
                'callback'            => array( __CLASS__, 'retry' ),
                'permission_callback' => static fn(): bool => ALGQ_Automation_Security::can( 'run_algq_automation' ),
                'args'                => array(
                    'id' => array( 'validate_callback' => static fn( $value ): bool => absint( $value ) > 0 ),
                ),
            )
        );

        register_rest_route(
            'algq/v1',
            '/automation/events/test',
            array(
                'methods'             => WP_REST_Server::CREATABLE,
                'callback'            => array( __CLASS__, 'test_event' ),
                'permission_callback' => static fn(): bool => ALGQ_Automation_Security::can( 'run_algq_automation' ),
            )
        );
    }

    public static function rules( WP_REST_Request $request ): WP_REST_Response {
        global $wpdb;

        $tables   = ALGQ_Automation_DB::tables();
        $per_page = min( 100, max( 1, (int) $request['per_page'] ) );
        $offset   = max( 0, ( (int) $request['page'] - 1 ) * $per_page );
        $rows     = $wpdb->get_results(
            $wpdb->prepare( "SELECT * FROM {$tables['rules']} ORDER BY priority ASC, id DESC LIMIT %d OFFSET %d", $per_page, $offset ),
            ARRAY_A
        );

        foreach ( $rows as &$row ) {
            $row['conditions']     = json_decode( (string) $row['conditions'], true ) ?: array();
            $row['action_payload'] = ALGQ_Automation_Security::redact( json_decode( (string) $row['action_payload'], true ) ?: array() );
        }

        return rest_ensure_response( $rows );
    }

    public static function jobs( WP_REST_Request $request ): WP_REST_Response {
        global $wpdb;

        $tables   = ALGQ_Automation_DB::tables();
        $per_page = min( 100, max( 1, (int) $request['per_page'] ) );
        $offset   = max( 0, ( (int) $request['page'] - 1 ) * $per_page );
        $status   = sanitize_key( (string) $request['status'] );

        if ( $status ) {
            $rows = $wpdb->get_results(
                $wpdb->prepare( "SELECT * FROM {$tables['jobs']} WHERE status = %s ORDER BY id DESC LIMIT %d OFFSET %d", $status, $per_page, $offset ),
                ARRAY_A
            );
        } else {
            $rows = $wpdb->get_results(
                $wpdb->prepare( "SELECT * FROM {$tables['jobs']} ORDER BY id DESC LIMIT %d OFFSET %d", $per_page, $offset ),
                ARRAY_A
            );
        }

        foreach ( $rows as &$row ) {
            $row['payload'] = ALGQ_Automation_Security::redact( json_decode( (string) $row['payload'], true ) ?: array() );
        }

        return rest_ensure_response( $rows );
    }

    public static function retry( WP_REST_Request $request ): WP_REST_Response|WP_Error {
        if ( ! ALGQ_Automation_Engine::retry_job( absint( $request['id'] ) ) ) {
            return new WP_Error( 'algq_retry_failed', __( 'The automation job could not be retried.', 'algq-automation-engine' ), array( 'status' => 409 ) );
        }

        return rest_ensure_response( array( 'success' => true ) );
    }

    public static function test_event( WP_REST_Request $request ): WP_REST_Response {
        $payload = $request->get_json_params();
        $payload = is_array( $payload ) ? ALGQ_Automation_Security::redact( $payload ) : array();
        $jobs    = ALGQ_Automation_Engine::capture_event( 'automation.manual_test', 'manual_test', 0, $payload );

        return rest_ensure_response( array( 'queued_jobs' => $jobs ) );
    }

    private static function pagination_args(): array {
        return array(
            'page' => array(
                'default'           => 1,
                'sanitize_callback' => 'absint',
                'validate_callback' => static fn( $value ): bool => absint( $value ) >= 1,
            ),
            'per_page' => array(
                'default'           => 20,
                'sanitize_callback' => 'absint',
                'validate_callback' => static fn( $value ): bool => absint( $value ) >= 1 && absint( $value ) <= 100,
            ),
        );
    }
}
