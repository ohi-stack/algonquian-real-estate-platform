<?php
/**
 * REST API endpoints.
 *
 * @package Algonquian_Deal_Intake
 */

defined( 'ABSPATH' ) || exit;

final class ALGQ_Deal_Intake_REST {
	public static function register_hooks(): void {
		add_action( 'rest_api_init', array( __CLASS__, 'register_routes' ) );
	}

	public static function register_routes(): void {
		register_rest_route(
			'algq/v1',
			'/intake',
			array(
				array(
					'methods' => WP_REST_Server::READABLE,
					'callback' => array( __CLASS__, 'list_submissions' ),
					'permission_callback' => static fn(): bool => current_user_can( ALGQ_Deal_Intake_Security::CAP_VIEW_PRIVATE ),
					'args' => array(
						'page' => array( 'type' => 'integer', 'default' => 1, 'minimum' => 1 ),
						'per_page' => array( 'type' => 'integer', 'default' => 20, 'minimum' => 1, 'maximum' => 100 ),
						'status' => array( 'type' => 'string', 'sanitize_callback' => 'sanitize_key' ),
					),
				),
				array(
					'methods' => WP_REST_Server::CREATABLE,
					'callback' => array( __CLASS__, 'create_submission' ),
					'permission_callback' => static fn(): bool => current_user_can( ALGQ_Deal_Intake_Security::CAP_REVIEW ),
				),
			)
		);

		register_rest_route(
			'algq/v1',
			'/intake/(?P<id>\d+)/accept',
			array(
				'methods' => WP_REST_Server::CREATABLE,
				'callback' => array( __CLASS__, 'accept_submission' ),
				'permission_callback' => static fn(): bool => current_user_can( ALGQ_Deal_Intake_Security::CAP_REVIEW ),
				'args' => array(
					'id' => array( 'type' => 'integer', 'minimum' => 1 ),
				),
			)
		);

		register_rest_route(
			'algq/v1',
			'/intake/duplicate-check',
			array(
				'methods' => WP_REST_Server::CREATABLE,
				'callback' => array( __CLASS__, 'duplicate_check' ),
				'permission_callback' => static fn(): bool => current_user_can( ALGQ_Deal_Intake_Security::CAP_REVIEW ),
			)
		);
	}

	public static function list_submissions( WP_REST_Request $request ): WP_REST_Response {
		global $wpdb;
		$page = max( 1, (int) $request->get_param( 'page' ) );
		$per_page = min( 100, max( 1, (int) $request->get_param( 'per_page' ) ) );
		$offset = ( $page - 1 ) * $per_page;
		$status = sanitize_key( (string) $request->get_param( 'status' ) );
		$table = ALGQ_Deal_Intake_Database::table( 'submissions' );

		$where = 'deleted_at IS NULL';
		$params = array();
		if ( '' !== $status ) {
			$where .= ' AND status = %s';
			$params[] = $status;
		}

		$count_sql = "SELECT COUNT(*) FROM {$table} WHERE {$where}";
		$query_sql = "SELECT id, uuid, status, lead_source, motivation, timeline, asking_price, lead_score, duplicate_status, pipeline_deal_id, created_at, updated_at FROM {$table} WHERE {$where} ORDER BY created_at DESC LIMIT %d OFFSET %d";

		$count = empty( $params ) ? (int) $wpdb->get_var( $count_sql ) : (int) $wpdb->get_var( $wpdb->prepare( $count_sql, $params ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$query_params = array_merge( $params, array( $per_page, $offset ) );
		$rows = $wpdb->get_results( $wpdb->prepare( $query_sql, $query_params ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

		$response = new WP_REST_Response( $rows, 200 );
		$response->header( 'X-WP-Total', (string) $count );
		$response->header( 'X-WP-TotalPages', (string) (int) ceil( $count / $per_page ) );
		return $response;
	}

	public static function create_submission( WP_REST_Request $request ): WP_REST_Response|WP_Error {
		$result = ALGQ_Deal_Intake_Submissions::create_from_array( $request->get_json_params(), true );
		if ( is_wp_error( $result ) ) {
			return $result;
		}

		return new WP_REST_Response( array( 'id' => (int) $result, 'record' => ALGQ_Deal_Intake_Submissions::get( (int) $result ) ), 201 );
	}

	public static function accept_submission( WP_REST_Request $request ): WP_REST_Response|WP_Error {
		$result = ALGQ_Deal_Intake_Submissions::accept( (int) $request['id'] );
		if ( is_wp_error( $result ) ) {
			return $result;
		}

		return new WP_REST_Response( array( 'deal_id' => (int) $result ), 200 );
	}

	public static function duplicate_check( WP_REST_Request $request ): WP_REST_Response {
		$data = $request->get_json_params();
		$matches = ALGQ_Deal_Intake_Duplicate_Detector::find_matches(
			array(
				'email' => $data['seller_email'] ?? '',
				'phone' => $data['seller_phone'] ?? '',
			),
			array(
				'address' => $data['address'] ?? '',
				'city' => $data['city'] ?? '',
				'state' => $data['state'] ?? '',
				'postal_code' => $data['postal_code'] ?? '',
				'parcel' => $data['parcel'] ?? '',
			)
		);

		return new WP_REST_Response( array( 'matches' => $matches ), 200 );
	}
}
