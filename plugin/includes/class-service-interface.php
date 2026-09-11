<?php
/**
 * Shared ARE service contract and registry.
 *
 * The Platform resolves approved cross-plugin operations without allowing
 * companion plugins to reach into another plugin's tables directly.
 *
 * @package AlgonquianRealEstatePlatform
 */

defined( 'ABSPATH' ) || exit;

interface ARE_Platform_Service_Interface {
	public function id(): string;
	public function version(): string;
	/** @return string[] */
	public function operations(): array;
	/** @return mixed */
	public function call( string $operation, array $payload = array(), array $context = array() );
	/** @return array<string,mixed> */
	public function health(): array;
}

final class ARE_Platform_Callable_Service_Adapter implements ARE_Platform_Service_Interface {
	private string $service_id;
	/** @var callable */
	private $provider;
	/** @var string[] */
	private array $supported_operations;
	private string $service_version;

	/** @param callable $provider @param string[] $operations */
	public function __construct( string $service_id, callable $provider, array $operations = array( '*' ), string $version = '' ) {
		$this->service_id            = sanitize_key( str_replace( '.', '-', $service_id ) );
		$this->provider              = $provider;
		$this->supported_operations  = $operations ?: array( '*' );
		$this->service_version       = $version ?: ( defined( 'ALGQ_PLATFORM_VERSION' ) ? ALGQ_PLATFORM_VERSION : '1.0.0' );
	}

	public function id(): string { return str_replace( '-', '.', $this->service_id ); }
	public function version(): string { return $this->service_version; }
	public function operations(): array { return $this->supported_operations; }
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
}

final class ARE_Platform_Service_Registry {
	/** @var array<string,ARE_Platform_Service_Interface> */
	private static array $services = array();
	private static bool $initialized = false;

	public static function init(): void {
		if ( self::$initialized ) {
			return;
		}
		self::$initialized = true;
		do_action( 'algq_platform_service_registry_ready' );
	}

	/** @return true|WP_Error */
	public static function register( ARE_Platform_Service_Interface $service ) {
		$id = self::normalize_id( $service->id() );
		if ( '' === $id ) {
			return new WP_Error( 'algq_service_invalid_id', __( 'A platform service must provide a valid ID.', 'algonquian-real-estate-platform' ) );
		}
		if ( isset( self::$services[ $id ] ) && self::$services[ $id ] !== $service ) {
			return new WP_Error( 'algq_service_duplicate_authority', sprintf( __( 'Service %s already has an authoritative provider.', 'algonquian-real-estate-platform' ), $id ) );
		}
		self::$services[ $id ] = $service;
		do_action( 'algq_platform_service_registered', $id, $service );
		return true;
	}

	/** @param callable $provider @param string[] $operations @return true|WP_Error */
	public static function register_callable( string $service_id, callable $provider, array $operations = array( '*' ), string $version = '' ) {
		return self::register( new ARE_Platform_Callable_Service_Adapter( $service_id, $provider, $operations, $version ) );
	}

	public static function has( string $service_id ): bool {
		return isset( self::$services[ self::normalize_id( $service_id ) ] );
	}

	public static function get( string $service_id ): ?ARE_Platform_Service_Interface {
		return self::$services[ self::normalize_id( $service_id ) ] ?? null;
	}

	/** @return mixed */
	public static function call( string $service_id, string $operation, array $payload = array(), array $context = array() ) {
		$service = self::get( $service_id );
		if ( ! $service ) {
			return new WP_Error( 'algq_service_unavailable', sprintf( __( 'Service %s is not registered.', 'algonquian-real-estate-platform' ), sanitize_text_field( $service_id ) ) );
		}
		$operations = $service->operations();
		if ( ! in_array( '*', $operations, true ) && ! in_array( $operation, $operations, true ) ) {
			return new WP_Error( 'algq_service_operation_unavailable', sprintf( __( 'Operation %1$s is not available on %2$s.', 'algonquian-real-estate-platform' ), sanitize_text_field( $operation ), sanitize_text_field( $service_id ) ) );
		}

		$context = wp_parse_args(
			$context,
			array(
				'correlation_id' => wp_generate_uuid4(),
				'caller_plugin'  => '',
				'user_id'        => get_current_user_id(),
			)
		);
		do_action( 'algq_platform_service_calling', $service_id, $operation, $payload, $context );
		$result = $service->call( $operation, $payload, $context );
		do_action( 'algq_platform_service_called', $service_id, $operation, $result, $context );
		return $result;
	}

	/** @return array<string,array<string,mixed>> */
	public static function catalog(): array {
		$catalog = array();
		foreach ( self::$services as $id => $service ) {
			$catalog[ $id ] = array(
				'id'         => $id,
				'version'    => $service->version(),
				'operations' => $service->operations(),
				'health'     => $service->health(),
			);
		}
		return $catalog;
	}

	private static function normalize_id( string $service_id ): string {
		$service_id = strtolower( trim( $service_id ) );
		$service_id = preg_replace( '/[^a-z0-9._-]/', '', $service_id );
		return is_string( $service_id ) ? $service_id : '';
	}
}

/**
 * Register either an object-backed service or a callable provider.
 *
 * @param ARE_Platform_Service_Interface|string $service
 * @param callable|null                          $provider
 * @param string[]                               $operations
 * @return true|WP_Error
 */
function algq_platform_register_service( $service, $provider = null, array $operations = array( '*' ), string $version = '' ) {
	ARE_Platform_Service_Registry::init();
	if ( $service instanceof ARE_Platform_Service_Interface ) {
		return ARE_Platform_Service_Registry::register( $service );
	}
	if ( is_string( $service ) && is_callable( $provider ) ) {
		return ARE_Platform_Service_Registry::register_callable( $service, $provider, $operations, $version );
	}
	return new WP_Error( 'algq_service_invalid_provider', __( 'Invalid platform service provider.', 'algonquian-real-estate-platform' ) );
}

/** @return mixed */
function algq_platform_service_call( string $service_id, string $operation, array $payload = array(), array $context = array() ) {
	return ARE_Platform_Service_Registry::call( $service_id, $operation, $payload, $context );
}

function algq_platform_service_available( string $service_id ): bool {
	return ARE_Platform_Service_Registry::has( $service_id );
}
