<?php
/**
 * REST boundary mapping authenticated HTTP requests to authoritative Platform services.
 */

defined( 'ABSPATH' ) || exit;

final class ALGQ_API_Bridge_REST_API {
	private const NAMESPACE = 'algq/v1';
	private const IDEMPOTENCY_TTL = DAY_IN_SECONDS;

	public static function init(): void {
		add_action( 'rest_api_init', array( __CLASS__, 'register_routes' ) );
	}

	public static function register_routes(): void {
		register_rest_route(
			self::NAMESPACE,
			'/health',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( __CLASS__, 'health' ),
				'permission_callback' => '__return_true',
			)
		);

		register_rest_route(
			self::NAMESPACE,
			'/services',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( __CLASS__, 'services' ),
				'permission_callback' => static fn( WP_REST_Request $request ) => self::permission( $request, 'services:read' ),
			)
		);

		register_rest_route(
			self::NAMESPACE,
			'/deals',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( __CLASS__, 'query_deals' ),
					'permission_callback' => static fn( WP_REST_Request $request ) => self::permission( $request, 'deals:read' ),
				),
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( __CLASS__, 'create_deal' ),
					'permission_callback' => static fn( WP_REST_Request $request ) => self::permission( $request, 'deals:write' ),
				),
			)
		);

		register_rest_route(
			self::NAMESPACE,
			'/deals/(?P<deal_id>[A-Za-z0-9._-]+)',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( __CLASS__, 'get_deal' ),
					'permission_callback' => static fn( WP_REST_Request $request ) => self::permission( $request, 'deals:read' ),
				),
				array(
					'methods'             => WP_REST_Server::EDITABLE,
					'callback'            => array( __CLASS__, 'update_deal' ),
					'permission_callback' => static fn( WP_REST_Request $request ) => self::permission( $request, 'deals:write' ),
				),
			)
		);

		register_rest_route(
			self::NAMESPACE,
			'/deals/(?P<deal_id>[A-Za-z0-9._-]+)/transition',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( __CLASS__, 'transition_deal' ),
				'permission_callback' => static fn( WP_REST_Request $request ) => self::permission( $request, 'deals:transition' ),
			)
		);

		register_rest_route(
			self::NAMESPACE,
			'/deals/(?P<deal_id>[A-Za-z0-9._-]+)/activity',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( __CLASS__, 'deal_activity' ),
				'permission_callback' => static fn( WP_REST_Request $request ) => self::permission( $request, 'deals:read' ),
			)
		);
	}

	public static function health( WP_REST_Request $request ): WP_REST_Response {
		$config = ALGQ_API_Bridge_Auth::config();
		$platform_available = function_exists( 'algq_platform_service_call' ) && function_exists( 'algq_platform_service' );
		$pipeline_available = $platform_available && null !== algq_platform_service( 'pipeline.deals' );
		$healthy = $config['configured'] && $platform_available && $pipeline_available;

		return new WP_REST_Response(
			array(
				'service'                    => 'Algonquian Real Estate API Bridge',
				'version'                    => ALGQ_API_BRIDGE_VERSION,
				'status'                     => $healthy ? 'healthy' : 'degraded',
				'authentication_configured'  => (bool) $config['configured'],
				'platform_service_interface' => $platform_available,
				'pipeline_deals_service'      => $pipeline_available,
				'timestamp'                   => gmdate( 'c' ),
			),
			$healthy ? 200 : 503
		);
	}

	public static function services( WP_REST_Request $request ): WP_REST_Response {
		if ( ! function_exists( 'algq_platform_services' ) ) {
			return self::error_response( new WP_Error( 'algq_api_platform_unavailable', 'Platform Service Interface is unavailable.', array( 'status' => 503 ) ), $request );
		}

		$catalog = algq_platform_services( true );
		foreach ( $catalog as &$service ) {
			unset( $service['provider'] );
		}
		unset( $service );

		return self::success_response( $catalog, $request, 'platform.services', 'catalog' );
	}

	public static function query_deals( WP_REST_Request $request ): WP_REST_Response {
		$allowed = array( 'stage', 'stage_key', 'priority', 'search', 'assigned_user_id', 'acquisition_strategy', 'source_plugin', 'limit', 'offset' );
		$payload = array();
		foreach ( $allowed as $key ) {
			$value = $request->get_param( $key );
			if ( null === $value || '' === $value ) {
				continue;
			}
			if ( in_array( $key, array( 'limit', 'offset', 'assigned_user_id' ), true ) ) {
				$payload[ $key ] = absint( $value );
			} elseif ( in_array( $key, array( 'stage', 'stage_key', 'priority', 'acquisition_strategy', 'source_plugin' ), true ) ) {
				$payload[ $key ] = sanitize_key( (string) $value );
			} else {
				$payload[ $key ] = sanitize_text_field( (string) $value );
			}
		}
		if ( isset( $payload['limit'] ) ) {
			$payload['limit'] = min( 200, max( 1, $payload['limit'] ) );
		}
		return self::execute( $request, 'pipeline.deals', 'query', $payload );
	}

	public static function get_deal( WP_REST_Request $request ): WP_REST_Response {
		$identifier = sanitize_text_field( (string) $request['deal_id'] );
		return self::execute( $request, 'pipeline.deals', 'get', array( 'identifier' => $identifier ) );
	}

	public static function create_deal( WP_REST_Request $request ): WP_REST_Response {
		$replay = self::idempotency_replay( $request );
		if ( $replay instanceof WP_REST_Response ) {
			return $replay;
		}
		if ( is_wp_error( $replay ) ) {
			return self::error_response( $replay, $request );
		}

		$payload  = self::json_payload( $request );
		$response = self::execute( $request, 'pipeline.deals', 'create', $payload );
		self::store_idempotency( $request, $response );
		return $response;
	}

	public static function update_deal( WP_REST_Request $request ): WP_REST_Response {
		$replay = self::idempotency_replay( $request );
		if ( $replay instanceof WP_REST_Response ) {
			return $replay;
		}
		if ( is_wp_error( $replay ) ) {
			return self::error_response( $replay, $request );
		}

		$payload            = self::json_payload( $request );
		$payload['deal_id'] = absint( $request['deal_id'] );
		if ( ! $payload['deal_id'] ) {
			return self::error_response( new WP_Error( 'algq_api_numeric_deal_id_required', 'Updates currently require the canonical numeric deal_id.', array( 'status' => 400 ) ), $request );
		}
		$response = self::execute( $request, 'pipeline.deals', 'update', $payload );
		self::store_idempotency( $request, $response );
		return $response;
	}

	public static function transition_deal( WP_REST_Request $request ): WP_REST_Response {
		$replay = self::idempotency_replay( $request );
		if ( $replay instanceof WP_REST_Response ) {
			return $replay;
		}
		if ( is_wp_error( $replay ) ) {
			return self::error_response( $replay, $request );
		}

		$payload            = self::json_payload( $request );
		$payload['deal_id'] = absint( $request['deal_id'] );
		if ( ! $payload['deal_id'] ) {
			return self::error_response( new WP_Error( 'algq_api_numeric_deal_id_required', 'Transitions currently require the canonical numeric deal_id.', array( 'status' => 400 ) ), $request );
		}
		$response = self::execute( $request, 'pipeline.deals', 'transition', $payload );
		self::store_idempotency( $request, $response );
		return $response;
	}

	public static function deal_activity( WP_REST_Request $request ): WP_REST_Response {
		$deal_id = absint( $request['deal_id'] );
		if ( ! $deal_id ) {
			return self::error_response( new WP_Error( 'algq_api_numeric_deal_id_required', 'Activity currently requires the canonical numeric deal_id.', array( 'status' => 400 ) ), $request );
		}
		$limit = min( 200, max( 1, absint( $request->get_param( 'limit' ) ?: 50 ) ) );
		return self::execute( $request, 'pipeline.deals', 'activity', array( 'deal_id' => $deal_id, 'limit' => $limit ) );
	}

	/** @return true|WP_Error */
	private static function permission( WP_REST_Request $request, string $scope ) {
		$context = ALGQ_API_Bridge_Auth::authorize( $request, $scope );
		if ( is_wp_error( $context ) ) {
			self::audit( 'api_bridge_auth_failed', $request, array( 'scope' => $scope, 'error_code' => $context->get_error_code() ), 'warning' );
			return $context;
		}
		return true;
	}

	private static function execute( WP_REST_Request $request, string $service, string $operation, array $payload ): WP_REST_Response {
		if ( ! function_exists( 'algq_platform_service_call' ) ) {
			return self::error_response( new WP_Error( 'algq_api_platform_unavailable', 'Platform Service Interface is unavailable.', array( 'status' => 503 ) ), $request, $service, $operation );
		}

		$auth = ALGQ_API_Bridge_Auth::authenticate( $request );
		if ( is_wp_error( $auth ) ) {
			return self::error_response( $auth, $request, $service, $operation );
		}

		$context = array(
			'caller_plugin'  => 'algq-api-bridge',
			'request_id'     => $auth['request_id'],
			'correlation_id' => $auth['correlation_id'],
			'service_client' => $auth['client_id'],
			'api_key_id'     => $auth['key_id'],
			'api_scopes'     => $auth['scopes'],
		);

		$result = algq_platform_service_call( $service, $operation, $payload, $context );
		if ( null === $result && 'get' === $operation ) {
			$result = new WP_Error( 'algq_api_not_found', 'The requested record was not found.', array( 'status' => 404 ) );
		}

		if ( is_wp_error( $result ) ) {
			return self::error_response( $result, $request, $service, $operation );
		}

		self::audit(
			'api_bridge_service_call',
			$request,
			array( 'service' => $service, 'operation' => $operation, 'success' => true )
		);
		return self::success_response( $result, $request, $service, $operation );
	}

	private static function success_response( $data, WP_REST_Request $request, string $service, string $operation ): WP_REST_Response {
		$auth = ALGQ_API_Bridge_Auth::authenticate( $request );
		$meta = array(
			'request_id'     => is_array( $auth ) ? $auth['request_id'] : '',
			'correlation_id' => is_array( $auth ) ? $auth['correlation_id'] : '',
			'service'        => $service,
			'operation'      => $operation,
		);
		$response = new WP_REST_Response( array( 'data' => $data, 'meta' => $meta ), 200 );
		self::apply_response_headers( $response, $meta );
		return $response;
	}

	private static function error_response( WP_Error $error, WP_REST_Request $request, string $service = '', string $operation = '' ): WP_REST_Response {
		$data   = $error->get_error_data();
		$status = is_array( $data ) && isset( $data['status'] ) ? absint( $data['status'] ) : self::status_for_error( $error->get_error_code() );
		$auth   = ALGQ_API_Bridge_Auth::authenticate( $request );
		$meta   = array(
			'request_id'     => is_array( $auth ) ? $auth['request_id'] : sanitize_text_field( (string) $request->get_header( 'x-are-request-id' ) ),
			'correlation_id' => is_array( $auth ) ? $auth['correlation_id'] : sanitize_text_field( (string) $request->get_header( 'x-are-correlation-id' ) ),
			'service'        => $service,
			'operation'      => $operation,
		);
		$response = new WP_REST_Response(
			array(
				'error'   => $error->get_error_code(),
				'message' => $error->get_error_message(),
				'details' => self::redact( is_array( $data ) ? $data : array() ),
				'meta'    => $meta,
			),
			$status
		);
		self::apply_response_headers( $response, $meta );
		self::audit(
			'api_bridge_service_call',
			$request,
			array( 'service' => $service, 'operation' => $operation, 'success' => false, 'error_code' => $error->get_error_code() ),
			$status >= 500 ? 'error' : 'warning'
		);
		return $response;
	}

	/** @return array<string,mixed> */
	private static function json_payload( WP_REST_Request $request ): array {
		$payload = $request->get_json_params();
		return is_array( $payload ) ? $payload : array();
	}

	/** @return false|WP_Error|WP_REST_Response */
	private static function idempotency_replay( WP_REST_Request $request ) {
		$key = trim( (string) $request->get_header( 'idempotency-key' ) );
		if ( ! preg_match( '/^[A-Za-z0-9._:-]{8,128}$/', $key ) ) {
			return new WP_Error( 'algq_api_idempotency_required', 'A valid Idempotency-Key header is required for write operations.', array( 'status' => 400 ) );
		}
		$auth = ALGQ_API_Bridge_Auth::authenticate( $request );
		if ( is_wp_error( $auth ) ) {
			return $auth;
		}
		$cache_key = self::idempotency_cache_key( (string) $auth['key_id'], $key );
		$stored    = get_transient( $cache_key );
		if ( ! is_array( $stored ) || ! isset( $stored['status'], $stored['body'] ) ) {
			return false;
		}
		$body = is_array( $stored['body'] ) ? $stored['body'] : array();
		if ( isset( $body['meta'] ) && is_array( $body['meta'] ) ) {
			$body['meta']['idempotent_replay'] = true;
		}
		$response = new WP_REST_Response( $body, absint( $stored['status'] ) ?: 200 );
		self::apply_response_headers( $response, is_array( $body['meta'] ?? null ) ? $body['meta'] : array() );
		$response->header( 'X-ARE-Idempotent-Replay', 'true' );
		return $response;
	}

	private static function store_idempotency( WP_REST_Request $request, WP_REST_Response $response ): void {
		$key  = trim( (string) $request->get_header( 'idempotency-key' ) );
		$auth = ALGQ_API_Bridge_Auth::authenticate( $request );
		if ( '' === $key || is_wp_error( $auth ) ) {
			return;
		}
		set_transient(
			self::idempotency_cache_key( (string) $auth['key_id'], $key ),
			array( 'status' => $response->get_status(), 'body' => $response->get_data() ),
			self::IDEMPOTENCY_TTL
		);
	}

	private static function idempotency_cache_key( string $key_id, string $key ): string {
		return 'algq_api_idem_' . substr( hash( 'sha256', $key_id . '|' . $key ), 0, 40 );
	}

	private static function apply_response_headers( WP_REST_Response $response, array $meta ): void {
		if ( ! empty( $meta['request_id'] ) ) {
			$response->header( 'X-ARE-Request-ID', (string) $meta['request_id'] );
		}
		if ( ! empty( $meta['correlation_id'] ) ) {
			$response->header( 'X-ARE-Correlation-ID', (string) $meta['correlation_id'] );
		}
		$response->header( 'Cache-Control', 'no-store' );
	}

	private static function status_for_error( string $code ): int {
		if ( str_contains( $code, 'not_found' ) ) {
			return 404;
		}
		if ( str_contains( $code, 'conflict' ) || str_contains( $code, 'replay' ) ) {
			return 409;
		}
		if ( str_contains( $code, 'unavailable' ) || str_contains( $code, 'not_configured' ) ) {
			return 503;
		}
		if ( str_contains( $code, 'denied' ) ) {
			return 403;
		}
		if ( str_contains( $code, 'auth' ) || str_contains( $code, 'signature' ) || str_contains( $code, 'timestamp' ) || str_contains( $code, 'key_invalid' ) ) {
			return 401;
		}
		if ( str_contains( $code, 'rate' ) ) {
			return 429;
		}
		return 400;
	}

	/** @param mixed $value */
	private static function redact( $value ) {
		if ( ! is_array( $value ) ) {
			return is_scalar( $value ) || null === $value ? $value : '[unsupported]';
		}
		$sensitive = array( 'password', 'pass', 'secret', 'token', 'authorization', 'api_key', 'signature', 'account_number' );
		$clean = array();
		foreach ( $value as $key => $item ) {
			$normalized = strtolower( (string) $key );
			$clean[ $key ] = in_array( $normalized, $sensitive, true ) ? '[redacted]' : self::redact( $item );
		}
		return $clean;
	}

	private static function audit( string $event, WP_REST_Request $request, array $payload, string $severity = 'info' ): void {
		$auth = ALGQ_API_Bridge_Auth::authenticate( $request );
		if ( is_array( $auth ) ) {
			$payload['request_id']     = $auth['request_id'];
			$payload['correlation_id'] = $auth['correlation_id'];
			$payload['client_id']      = $auth['client_id'];
			$payload['key_id']         = $auth['key_id'];
		}
		$payload['method'] = strtoupper( (string) $request->get_method() );
		$payload['route']  = $request->get_route();

		if ( function_exists( 'algq_log_event' ) ) {
			algq_log_event(
				$event,
				$payload,
				array( 'plugin' => 'algq-api-bridge', 'severity' => $severity, 'object_type' => 'api_request', 'object_id' => sanitize_text_field( (string) ( $payload['request_id'] ?? '' ) ) )
			);
		} else {
			do_action( 'algq_audit_event', $event, $payload, array( 'plugin' => 'algq-api-bridge', 'severity' => $severity ) );
		}
	}
}
