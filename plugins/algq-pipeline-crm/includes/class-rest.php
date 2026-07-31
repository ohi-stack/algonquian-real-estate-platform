<?php

defined( 'ABSPATH' ) || exit;

final class ALGQ_Pipeline_REST {
    public static function init(): void {
        add_action( 'rest_api_init', array( __CLASS__, 'routes' ) );
    }

    public static function routes(): void {
        register_rest_route( 'algq/v1', '/deals', array(
            array( 'methods' => WP_REST_Server::READABLE, 'callback' => array( __CLASS__, 'index' ), 'permission_callback' => array( __CLASS__, 'can_view' ) ),
            array( 'methods' => WP_REST_Server::CREATABLE, 'callback' => array( __CLASS__, 'create' ), 'permission_callback' => array( __CLASS__, 'can_create' ) ),
        ) );
        register_rest_route( 'algq/v1', '/deals/(?P<id>\d+)', array(
            array( 'methods' => WP_REST_Server::READABLE, 'callback' => array( __CLASS__, 'show' ), 'permission_callback' => array( __CLASS__, 'can_view' ) ),
            array( 'methods' => WP_REST_Server::EDITABLE, 'callback' => array( __CLASS__, 'update' ), 'permission_callback' => array( __CLASS__, 'can_edit' ) ),
        ) );
        register_rest_route( 'algq/v1', '/deals/(?P<id>\d+)/stage', array(
            'methods' => WP_REST_Server::CREATABLE,
            'callback' => array( __CLASS__, 'stage' ),
            'permission_callback' => array( __CLASS__, 'can_transition' ),
            'args' => array(
                'stage' => array( 'required' => true, 'sanitize_callback' => 'sanitize_key' ),
                'record_version' => array( 'required' => true, 'sanitize_callback' => 'absint' ),
                'reason' => array( 'required' => false, 'sanitize_callback' => 'sanitize_text_field' ),
            ),
        ) );
    }

    public static function can_view(): bool { return current_user_can( 'view_algq_deals' ); }
    public static function can_create(): bool { return current_user_can( 'create_algq_deals' ); }
    public static function can_edit(): bool { return current_user_can( 'edit_algq_deals' ); }
    public static function can_transition(): bool { return current_user_can( 'transition_algq_deals' ); }

    public static function index( WP_REST_Request $request ): WP_REST_Response {
        $repo = ALGQ_Pipeline_Service::instance()->repository();
        $args = array(
            'stage' => $request->get_param( 'stage' ),
            'search' => $request->get_param( 'search' ),
            'assigned_user_id' => absint( $request->get_param( 'assigned_user_id' ) ),
            'include_archived' => rest_sanitize_boolean( $request->get_param( 'include_archived' ) ),
            'page' => absint( $request->get_param( 'page' ) ?: 1 ),
            'per_page' => absint( $request->get_param( 'per_page' ) ?: 25 ),
        );
        return new WP_REST_Response( array( 'items' => $repo->list( $args ), 'total' => $repo->count( $args ) ) );
    }

    public static function show( WP_REST_Request $request ) {
        $deal = ALGQ_Pipeline_Service::instance()->get_deal( absint( $request['id'] ) );
        return $deal ? new WP_REST_Response( $deal ) : new WP_Error( 'algq_pipeline_not_found', 'Deal not found.', array( 'status' => 404 ) );
    }

    public static function create( WP_REST_Request $request ) {
        $result = ALGQ_Pipeline_Service::instance()->create_deal( $request->get_json_params() ?: $request->get_params() );
        return is_wp_error( $result ) ? $result : new WP_REST_Response( $result, 201 );
    }

    public static function update( WP_REST_Request $request ) {
        $data = $request->get_json_params() ?: $request->get_params();
        if ( empty( $data['record_version'] ) ) {
            return new WP_Error( 'algq_pipeline_version_required', 'record_version is required.', array( 'status' => 400 ) );
        }
        return ALGQ_Pipeline_Service::instance()->update_deal( absint( $request['id'] ), $data, absint( $data['record_version'] ) );
    }

    public static function stage( WP_REST_Request $request ) {
        return ALGQ_Pipeline_Service::instance()->transition(
            absint( $request['id'] ),
            sanitize_key( $request['stage'] ),
            array(
                'record_version' => absint( $request['record_version'] ),
                'reason' => sanitize_text_field( $request['reason'] ),
                'source' => 'rest',
            )
        );
    }
}
