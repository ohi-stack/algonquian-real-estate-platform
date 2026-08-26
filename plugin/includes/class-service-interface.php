<?php
/**
 * Shared service contract and registry for authoritative ARE plugin operations.
 *
 * The Platform owns the contract and discovery layer only. Domain plugins retain
 * ownership of their records, validation rules, state transitions, and audits.
 *
 * @package AlgonquianRealEstatePlatform
 */

defined( 'ABSPATH' ) || exit;

interface ARE_Platform_Service_Interface {
	/**
	 * Stable namespaced service identifier, e.g. pipeline.deals.
	 */
	public function id(): string;

	/**
	 * Provider/service version exposed for diagnostics and compatibility checks.
	 */
	public function version(): string;

	/**
	 * Supported operation names.
	 *
	 * @return array<int,string>
	 */
	public function operations(): array;

	/**
	 * Execute one authoritative provider operation.
	 *
	 * @param string              $operation Operation name.
	 * @param array<string,mixed> $payload   Operation payload.
	 * @param array<string,mixed> $context   Non-domain execution context.
	 * @return mixed|WP_Error
	 */
	public function call( string $operation, array $payload = array(), array $context = array() );

	/**
	 * Return a lightweight provider health payload.
	 *
	 * @return array<string,mixed>
	 */
	public function health(): array;
}

final class ARE_Platform_Service_Registry {
	/** @var array<string,ARE_Platform_Service_Interface> */
	private static array $services = array();

	public static function init(): void {
		self::$services = array();
		do_action( 'algq_platform_service_registry_ready' );
	}

	/**
	 * Register an authoritative provider.
	 *
	 * Re-registering the exact same provider class for the same ID is idempotent.
	 * A different provider cannot silently replace an existing authority.
	 *
	 * @return true|WP_Error
	 */
	public static function register( ARE_Platform_Service_Interface $service ) {
		$id = self::normalize_id( $service->id() );
		if ( '' === $id ) {
			return new WP_Error(
				'algq_service_invalid_id',
				__( 'A platform service must declare a valid service ID.', 'algonquian-real-estate-platform' )
			);
		}

		if ( isset( self::$services[ $id ] ) ) {
			$existing = self::$services[ $id ];
			if ( get_class( $existing ) === get_class( $service ) ) {
				return true;
			}

			return new WP_Error(
				'algq_service_authority_conflict',
				sprintf(
					/* translators: %s: service ID */
					__( 'A different provider is already registered for service %s.', 'algonquian-real-estate-platform' ),
					$id
				)
			);
		}

		self::$services[ $id ] = $service;
		do_action( 'algq_platform_service_registered', $id, $service );
		return true;
	}

	public static function has( string $id ): bool {
		return isset( self::$services[ self::normalize_id( $id ) ] );
	}

	public static function get( string $id ): ?ARE_Platform_Service_Interface {
		$id = self::normalize_id( $id );
		return self::$services[ $id ] ?? null;
	}

	/**
	 * Execute one registered service operation.
	 *
	 * @param array<string,mixed> $payload Operation payload.
	 * @param array<string,mixed> $context Non-domain execution context.
	 * @return mixed|WP_Error
	 */
	public static function call( string $id, string $operation, array $payload = array(), array $context = array() ) {
		$id        = self::normalize_id( $id );
		$operation = self::normalize_operation( $operation );
		$service   = self::get( $id );

		if ( ! $service ) {
			return new WP_Error(
				'algq_service_not_found',
				sprintf(
					/* translators: %s: service ID */
					__( 'No authoritative provider is registered for service %s.', 'algonquian-real-estate-platform' ),
					$id
				),
				array( 'service_id' => $id )
			);
		}

		$supported = array_map( array( self::class, 'normalize_operation' ), $service->operations() );
		if ( '' === $operation || ! in_array( $operation, $supported, true ) ) {
			return new WP_Error(
				'algq_service_operation_not_supported',
				sprintf(
					/* translators: 1: operation, 2: service ID */
					__( 'Operation %1$s is not supported by service %2$s.', 'algonquian-real-estate-platform' ),
					$operation,
					$id
				),
				array( 'service_id' => $id, 'operation' => $operation )
			);
		}

		$request_id = sanitize_text_field(
			(string) ( $context['request_id'] ?? ( function_exists( 'wp_generate_uuid4' ) ? wp_generate_uuid4() : uniqid( 'algq_', true ) ) )
		);
		$caller_plugin = sanitize_key( (string) ( $context['caller_plugin'] ?? '' ) );
		$context = array_merge(
			$context,
			array(
				'service_id'    => $id,
				'operation'     => $operation,
				'request_id'    => $request_id,
				'caller_plugin' => $caller_plugin,
				'actor_user_id' => function_exists( 'get_current_user_id' ) ? get_current_user_id() : 0,
			)
		);

		$result = $service->call( $operation, $payload, $context );

		do_action(
			'algq_platform_service_called',
			$id,
			$operation,
			array(
				'request_id'    => $request_id,
				'caller_plugin' => $caller_plugin,
				'success'       => ! is_wp_error( $result ),
				'error_code'    => is_wp_error( $result ) ? $result->get_error_code() : '',
			)
		);

		return $result;
	}

	/**
	 * Return service discovery metadata without exposing provider internals.
	 *
	 * @param bool $include_health Whether to invoke provider health callbacks.
	 * @return array<string,array<string,mixed>>
	 */
	public static function catalog( bool $include_health = false ): array {
		$catalog = array();
		foreach ( self::$services as $id => $service ) {
			$catalog[ $id ] = array(
				'id'         => $id,
				'version'    => sanitize_text_field( $service->version() ),
				'operations' => array_values( array_unique( array_map( array( self::class, 'normalize_operation' ), $service->operations() ) ) ),
				'provider'   => get_class( $service ),
			);
			if ( $include_health ) {
				$catalog[ $id ]['health'] = $service->health();
			}
		}
		return $catalog;
	}

	private static function normalize_id( string $id ): string {
		$id = strtolower( trim( $id ) );
		return (string) preg_replace( '/[^a-z0-9._-]/', '', $id );
	}

	private static function normalize_operation( string $operation ): string {
		$operation = strtolower( trim( $operation ) );
		return (string) preg_replace( '/[^a-z0-9._-]/', '', $operation );
	}
}

if ( ! function_exists( 'algq_platform_register_service' ) ) {
	/** @return true|WP_Error */
	function algq_platform_register_service( ARE_Platform_Service_Interface $service ) {
		return ARE_Platform_Service_Registry::register( $service );
	}
}

if ( ! function_exists( 'algq_platform_service' ) ) {
	function algq_platform_service( string $id ): ?ARE_Platform_Service_Interface {
		return ARE_Platform_Service_Registry::get( $id );
	}
}

if ( ! function_exists( 'algq_platform_service_call' ) ) {
	/**
	 * @param array<string,mixed> $payload Operation payload.
	 * @param array<string,mixed> $context Non-domain execution context.
	 * @return mixed|WP_Error
	 */
	function algq_platform_service_call( string $id, string $operation, array $payload = array(), array $context = array() ) {
		return ARE_Platform_Service_Registry::call( $id, $operation, $payload, $context );
	}
}

if ( ! function_exists( 'algq_platform_services' ) ) {
	/** @return array<string,array<string,mixed>> */
	function algq_platform_services( bool $include_health = false ): array {
		return ARE_Platform_Service_Registry::catalog( $include_health );
	}
}
