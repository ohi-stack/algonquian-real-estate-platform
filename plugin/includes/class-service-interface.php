<?php
/**
 * Shared service contract and registry for authoritative ARE plugin operations.
 *
 * The Platform owns the contract and discovery layer only. Domain plugins retain
 * ownership of their records, validation rules, state transitions, and audits.
 * Platform 3.1 extends the established object-backed contract with an optional
 * callable adapter without changing the stable public contract.
 *
 * @package AlgonquianRealEstatePlatform
 */

defined( 'ABSPATH' ) || exit;

interface ARE_Platform_Service_Interface {
	/** Stable namespaced service identifier, e.g. pipeline.deals. */
	public function id(): string;

	/** Provider/service version exposed for diagnostics. */
	public function version(): string;

	/** @return array<int,string> Supported operation names. */
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

	/** @return array<string,mixed> */
	public function health(): array;
}

/**
 * Backward-compatible adapter for services that are naturally implemented as a callable.
 */
final class ARE_Platform_Callable_Service_Adapter implements ARE_Platform_Service_Interface {
	private string $service_id;
	/** @var callable */
	private $provider;
	/** @var array<int,string> */
	private array $supported_operations;
	private string $service_version;

	/**
	 * @param callable          $provider
	 * @param array<int,string> $operations
	 */
	public function __construct( string $service_id, callable $provider, array $operations = array( '*' ), string $version = '' ) {
		$this->service_id           = self::normalize_id( $service_id );
		$this->provider             = $provider;
		$this->supported_operations = $operations ?: array( '*' );
		$this->service_version      = $version ?: ( defined( 'ALGQ_PLATFORM_VERSION' ) ? ALGQ_PLATFORM_VERSION : '1.0.0' );
	}

	public function id(): string {
		return $this->service_id;
	}

	public function version(): string {
		return $this->service_version;
	}

	public function operations(): array {
		return $this->supported_operations;
	}

	public function call( string $operation, array $payload = array(), array $context = array() ) {
		return call_user_func( $this->provider, $operation, $payload, $context );
	}

	public function health(): array {
		return array(
			'status'  => 'ok',
			'message' => 'Callable service provider registered.',
			'version' => $this->service_version,
		);
	}

	private static function normalize_id( string $id ): string {
		$id = strtolower( trim( $id ) );
		return (string) preg_replace( '/[^a-z0-9._-]/', '', $id );
	}
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

	/**
	 * Register a callable provider through the same authority controls.
	 *
	 * @param callable          $provider
	 * @param array<int,string> $operations
	 * @return true|WP_Error
	 */
	public static function register_callable( string $service_id, callable $provider, array $operations = array( '*' ), string $version = '' ) {
		return self::register( new ARE_Platform_Callable_Service_Adapter( $service_id, $provider, $operations, $version ) );
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

		$declared  = $service->operations();
		$wildcard  = in_array( '*', $declared, true );
		$supported = array_map( array( self::class, 'normalize_operation' ), array_filter( $declared, static fn( string $value ): bool => '*' !== $value ) );
		if ( '' === $operation || ( ! $wildcard && ! in_array( $operation, $supported, true ) ) ) {
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

		$request_id    = sanitize_text_field(
			(string) ( $context['request_id'] ?? $context['correlation_id'] ?? ( function_exists( 'wp_generate_uuid4' ) ? wp_generate_uuid4() : uniqid( 'algq_', true ) ) )
		);
		$caller_plugin = sanitize_key( (string) ( $context['caller_plugin'] ?? '' ) );
		$actor_user_id = function_exists( 'get_current_user_id' ) ? get_current_user_id() : 0;

		// System-controlled fields intentionally override caller-supplied values.
		$context = array_merge(
			$context,
			array(
				'service_id'     => $id,
				'operation'      => $operation,
				'request_id'     => $request_id,
				'correlation_id' => $request_id,
				'caller_plugin'  => $caller_plugin,
				'actor_user_id'  => $actor_user_id,
			)
		);

		do_action( 'algq_platform_service_calling', $id, $operation, $payload, $context );
		$result = $service->call( $operation, $payload, $context );

		do_action(
			'algq_platform_service_called',
			$id,
			$operation,
			array(
				'request_id'     => $request_id,
				'correlation_id' => $request_id,
				'caller_plugin'  => $caller_plugin,
				'actor_user_id'  => $actor_user_id,
				'success'        => ! is_wp_error( $result ),
				'error_code'     => is_wp_error( $result ) ? $result->get_error_code() : '',
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
			$operations = array();
			foreach ( $service->operations() as $operation ) {
				$operations[] = '*' === $operation ? '*' : self::normalize_operation( $operation );
			}
			$catalog[ $id ] = array(
				'id'         => $id,
				'version'    => sanitize_text_field( $service->version() ),
				'operations' => array_values( array_unique( $operations ) ),
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
	/**
	 * Register an object-backed service or a callable provider.
	 *
	 * @param ARE_Platform_Service_Interface|string $service
	 * @param callable|null                          $provider
	 * @param array<int,string>                      $operations
	 * @return true|WP_Error
	 */
	function algq_platform_register_service( $service, $provider = null, array $operations = array( '*' ), string $version = '' ) {
		if ( $service instanceof ARE_Platform_Service_Interface ) {
			return ARE_Platform_Service_Registry::register( $service );
		}
		if ( is_string( $service ) && is_callable( $provider ) ) {
			return ARE_Platform_Service_Registry::register_callable( $service, $provider, $operations, $version );
		}
		return new WP_Error( 'algq_service_invalid_provider', __( 'Invalid platform service provider.', 'algonquian-real-estate-platform' ) );
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

if ( ! function_exists( 'algq_platform_service_available' ) ) {
	function algq_platform_service_available( string $id ): bool {
		return ARE_Platform_Service_Registry::has( $id );
	}
}

if ( ! function_exists( 'algq_platform_services' ) ) {
	/** @return array<string,array<string,mixed>> */
	function algq_platform_services( bool $include_health = false ): array {
		return ARE_Platform_Service_Registry::catalog( $include_health );
	}
}
