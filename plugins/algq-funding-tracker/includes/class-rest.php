<?php
/**
 * REST API endpoints.
 *
 * @package Algonquian_Funding_Tracker
 */

defined( 'ABSPATH' ) || exit;

final class ALGQ_Funding_Tracker_REST {
	private $repository;

	public function __construct( ALGQ_Funding_Tracker_Repository $repository ) {
		$this->repository = $repository;
	}

	public function register() {
		add_action( 'rest_api_init', array( $this, 'register_routes' ) );
	}

	public function register_routes() {
		register_rest_route(
			'algq/v1',
			'/funding/summary',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'summary' ),
				'permission_callback' => array( $this, 'can_view' ),
			)
		);

		register_rest_route(
			'algq/v1',
			'/funding/sources',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'sources' ),
					'permission_callback' => array( $this, 'can_view' ),
					'args'                => array( 'limit' => array( 'default' => 100, 'sanitize_callback' => 'absint' ) ),
				),
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'create_source' ),
					'permission_callback' => array( $this, 'can_edit' ),
				),
			)
		);

		register_rest_route(
			'algq/v1',
			'/funding/commitments',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'commitments' ),
					'permission_callback' => array( $this, 'can_view' ),
					'args'                => array( 'limit' => array( 'default' => 100, 'sanitize_callback' => 'absint' ) ),
				),
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'create_commitment' ),
					'permission_callback' => array( $this, 'can_edit' ),
				),
			)
		);
	}

	public function can_view() {
		return current_user_can( 'view_algq_funding' );
	}

	public function can_edit() {
		return current_user_can( 'edit_algq_funding' );
	}

	public function summary() {
		return rest_ensure_response( $this->repository->get_summary() );
	}

	public function sources( WP_REST_Request $request ) {
		return rest_ensure_response( $this->repository->get_sources( $request->get_param( 'limit' ) ) );
	}

	public function commitments( WP_REST_Request $request ) {
		return rest_ensure_response( $this->repository->get_commitments( $request->get_param( 'limit' ) ) );
	}

	public function create_source( WP_REST_Request $request ) {
		$result = $this->repository->create_source( $request->get_json_params() ?: $request->get_params() );
		if ( is_wp_error( $result ) ) {
			return $result;
		}
		return new WP_REST_Response( array( 'id' => $result ), 201 );
	}

	public function create_commitment( WP_REST_Request $request ) {
		$result = $this->repository->create_commitment( $request->get_json_params() ?: $request->get_params() );
		if ( is_wp_error( $result ) ) {
			return $result;
		}
		return new WP_REST_Response( array( 'id' => $result ), 201 );
	}
}
