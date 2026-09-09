<?php
/**
 * HMAC authentication, scopes, replay protection and request context.
 */

defined( 'ABSPATH' ) || exit;

final class ALGQ_API_Bridge_Auth {
	private const DEFAULT_WINDOW_SECONDS = 300;
	private const DEFAULT_RATE_LIMIT     = 120;
	private const NONCE_TTL_SECONDS      = 600;

	/** @var array<int,array<string,mixed>|WP_Error> */
	private static array $request_cache = array();

	/**
	 * Authenticate the request and require a scope.
	 *
	 * @return array<string,mixed>|WP_Error
	 */
	public static function authorize( WP_REST_Request $request, string $required_scope ) {
		$context = self::authenticate( $request );
		if ( is_wp_error( $context ) ) {
			return $context;
		}

		$required_scope = sanitize_text_field( strtolower( trim( $required_scope ) ) );
		$scopes         = (array) ( $context['scopes'] ?? array() );
		if ( '' !== $required_scope && ! in_array( $required_scope, $scopes, true ) && ! in_array( '*', $scopes, true ) ) {
			return new WP_Error(
				'algq_api_scope_denied',
				__( 'The API client is not authorized for this operation.', 'algq-api-bridge' ),
				array( 'status' => 403, 'required_scope' => $required_scope )
			);
		}

		return $context;
	}

	/**
	 * Return a previously verified request context when available.
	 *
	 * @return array<string,mixed>|WP_Error
	 */
	public static function authenticate( WP_REST_Request $request ) {
		$request_key = spl_object_id( $request );
		if ( array_key_exists( $request_key, self::$request_cache ) ) {
			return self::$request_cache[ $request_key ];
		}

		$config = self::config();
		if ( ! $config['configured'] ) {
			return self::cache_error(
				$request_key,
				new WP_Error(
					'algq_api_auth_not_configured',
					__( 'The API Bridge signing credentials are not configured.', 'algq-api-bridge' ),
					array( 'status' => 503 )
				)
			);
		}

		$key_id     = sanitize_text_field( (string) $request->get_header( 'x-are-key-id' ) );
		$timestamp  = sanitize_text_field( (string) $request->get_header( 'x-are-timestamp' ) );
		$nonce      = sanitize_text_field( (string) $request->get_header( 'x-are-nonce' ) );
		$request_id = sanitize_text_field( (string) $request->get_header( 'x-are-request-id' ) );
		$signature  = strtolower( trim( (string) $request->get_header( 'x-are-signature' ) ) );

		if ( '' === $key_id || '' === $timestamp || '' === $nonce || '' === $request_id || '' === $signature ) {
			return self::cache_error(
				$request_key,
				new WP_Error(
					'algq_api_auth_headers_required',
					__( 'Required API authentication headers are missing.', 'algq-api-bridge' ),
					array( 'status' => 401 )
				)
			);
		}

		if ( ! hash_equals( (string) $config['key_id'], $key_id ) ) {
			return self::cache_error(
				$request_key,
				new WP_Error( 'algq_api_key_invalid', __( 'Invalid API key identifier.', 'algq-api-bridge' ), array( 'status' => 401 ) )
			);
		}

		if ( ! ctype_digit( $timestamp ) ) {
			return self::cache_error(
				$request_key,
				new WP_Error( 'algq_api_timestamp_invalid', __( 'Invalid API timestamp.', 'algq-api-bridge' ), array( 'status' => 401 ) )
			);
		}

		$window = (int) $config['window_seconds'];
		if ( abs( time() - (int) $timestamp ) > $window ) {
			return self::cache_error(
				$request_key,
				new WP_Error( 'algq_api_timestamp_expired', __( 'The API request timestamp is outside the accepted window.', 'algq-api-bridge' ), array( 'status' => 401 ) )
			);
		}

		if ( ! preg_match( '/^[A-Za-z0-9._:-]{16,128}$/', $nonce ) ) {
			return self::cache_error(
				$request_key,
				new WP_Error( 'algq_api_nonce_invalid', __( 'Invalid API request nonce.', 'algq-api-bridge' ), array( 'status' => 401 ) )
			);
		}

		if ( ! preg_match( '/^[A-Fa-f0-9]{64}$/', $signature ) ) {
			return self::cache_error(
				$request_key,
				new WP_Error( 'algq_api_signature_invalid', __( 'Invalid API request signature.', 'algq-api-bridge' ), array( 'status' => 401 ) )
			);
		}

		$canonical = self::canonical_string( $request, $timestamp, $nonce, $request_id );
		$expected  = hash_hmac( 'sha256', $canonical, (string) $config['secret'] );
		if ( ! hash_equals( $expected, $signature ) ) {
			return self::cache_error(
				$request_key,
				new WP_Error( 'algq_api_signature_mismatch', __( 'API request signature verification failed.', 'algq-api-bridge' ), array( 'status' => 401 ) )
			);
		}

		$nonce_key = 'algq_api_nonce_' . hash( 'sha256', $key_id . '|' . $nonce );
		if ( false !== get_transient( $nonce_key ) ) {
			return self::cache_error(
				$request_key,
				new WP_Error( 'algq_api_replay_detected', __( 'This signed API request has already been used.', 'algq-api-bridge' ), array( 'status' => 409 ) )
			);
		}

		$rate_error = self::enforce_rate_limit( $key_id, (int) $config['rate_limit'] );
		if ( is_wp_error( $rate_error ) ) {
			return self::cache_error( $request_key, $rate_error );
		}

		set_transient( $nonce_key, 1, self::NONCE_TTL_SECONDS );

		$correlation_id = sanitize_text_field( (string) $request->get_header( 'x-are-correlation-id' ) );
		$client_id      = sanitize_text_field( (string) $request->get_header( 'x-are-client-id' ) );
		$context        = array(
			'key_id'         => $key_id,
			'client_id'      => $client_id ?: $key_id,
			'scopes'         => $config['scopes'],
			'request_id'     => $request_id,
			'correlation_id' => $correlation_id ?: $request_id,
			'timestamp'      => (int) $timestamp,
		);

		self::$request_cache[ $request_key ] = $context;
		return $context;
	}

	/** @return array<string,mixed> */
	public static function config(): array {
		$key_id = self::config_value( 'ALGQ_API_BRIDGE_KEY_ID' );
		$secret = self::config_value( 'ALGQ_API_BRIDGE_SIGNING_SECRET' );
		$scope_string = self::config_value( 'ALGQ_API_BRIDGE_SCOPES' );

		$scopes = array_values(
			array_unique(
				array_filter(
					array_map(
						static fn( $scope ) => sanitize_text_field( strtolower( trim( (string) $scope ) ) ),
						explode( ',', $scope_string )
					)
				)
			)
		);

		$window = (int) self::config_value( 'ALGQ_API_BRIDGE_WINDOW_SECONDS' );
		$rate   = (int) self::config_value( 'ALGQ_API_BRIDGE_RATE_LIMIT' );

		$config = array(
			'key_id'         => $key_id,
			'secret'         => $secret,
			'scopes'         => $scopes,
			'window_seconds' => $window > 0 ? min( 900, max( 60, $window ) ) : self::DEFAULT_WINDOW_SECONDS,
			'rate_limit'     => $rate > 0 ? min( 2000, max( 10, $rate ) ) : self::DEFAULT_RATE_LIMIT,
			'configured'     => '' !== $key_id && strlen( $secret ) >= 32 && ! empty( $scopes ),
		);

		/**
		 * Filter API Bridge authentication configuration. Do not log or expose secret values.
		 */
		return apply_filters( 'algq_api_bridge_auth_config', $config );
	}

	public static function canonical_target( WP_REST_Request $request ): string {
		$route = '/' . ltrim( $request->get_route(), '/' );
		$query = $request->get_query_params();
		if ( empty( $query ) ) {
			return $route;
		}

		$flat = array();
		foreach ( $query as $key => $value ) {
			if ( is_scalar( $value ) || null === $value ) {
				$flat[ sanitize_key( (string) $key ) ] = (string) $value;
			}
		}
		ksort( $flat, SORT_STRING );
		return $route . ( empty( $flat ) ? '' : '?' . http_build_query( $flat, '', '&', PHP_QUERY_RFC3986 ) );
	}

	private static function canonical_string( WP_REST_Request $request, string $timestamp, string $nonce, string $request_id ): string {
		$body_hash = hash( 'sha256', (string) $request->get_body() );
		return implode(
			"\n",
			array(
				strtoupper( (string) $request->get_method() ),
				self::canonical_target( $request ),
				$timestamp,
				$nonce,
				$request_id,
				$body_hash,
			)
		);
	}

	/** @return string */
	private static function config_value( string $name ) {
		if ( defined( $name ) ) {
			return trim( (string) constant( $name ) );
		}
		$value = getenv( $name );
		return false === $value ? '' : trim( (string) $value );
	}

	/** @return true|WP_Error */
	private static function enforce_rate_limit( string $key_id, int $limit ) {
		$bucket = gmdate( 'YmdHi' );
		$key    = 'algq_api_rate_' . substr( hash( 'sha256', $key_id . '|' . $bucket ), 0, 40 );
		$count  = (int) get_transient( $key );
		if ( $count >= $limit ) {
			return new WP_Error(
				'algq_api_rate_limited',
				__( 'The API client has exceeded the current request limit.', 'algq-api-bridge' ),
				array( 'status' => 429, 'retry_after' => 60 )
			);
		}
		set_transient( $key, $count + 1, 90 );
		return true;
	}

	/** @return WP_Error */
	private static function cache_error( int $request_key, WP_Error $error ) {
		self::$request_cache[ $request_key ] = $error;
		return $error;
	}
}
