<?php
/**
 * Lightweight contract test for ARE_Platform_Service_Interface.
 *
 * This runs without WordPress by providing only the minimal stubs required by
 * the service registry. Runtime integration still requires WordPress testing.
 */

declare( strict_types=1 );

define( 'ABSPATH', __DIR__ . '/' );

final class WP_Error {
	private string $code;
	private string $message;
	private array $data;

	public function __construct( string $code = '', string $message = '', array $data = array() ) {
		$this->code = $code;
		$this->message = $message;
		$this->data = $data;
	}

	public function get_error_code(): string {
		return $this->code;
	}

	public function get_error_message(): string {
		return $this->message;
	}

	public function get_error_data(): array {
		return $this->data;
	}
}

function is_wp_error( $value ): bool {
	return $value instanceof WP_Error;
}

function __( string $message, string $domain = '' ): string { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
	return $message;
}

function do_action( string $hook, ...$args ): void { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
}

function wp_generate_uuid4(): string {
	return '00000000-0000-4000-8000-000000000001';
}

function get_current_user_id(): int {
	return 77;
}

function sanitize_key( string $value ): string {
	$value = strtolower( $value );
	return (string) preg_replace( '/[^a-z0-9_-]/', '', $value );
}

function sanitize_text_field( $value ): string {
	return trim( strip_tags( (string) $value ) );
}

require_once dirname( __DIR__ ) . '/plugin/includes/class-service-interface.php';

function assert_contract( bool $condition, string $message ): void {
	if ( ! $condition ) {
		fwrite( STDERR, "FAIL: {$message}\n" );
		exit( 1 );
	}
}

final class ARE_Test_Service implements ARE_Platform_Service_Interface {
	public function id(): string {
		return 'test.service';
	}

	public function version(): string {
		return '1.2.3';
	}

	public function operations(): array {
		return array( 'ping' );
	}

	public function call( string $operation, array $payload = array(), array $context = array() ) {
		return array(
			'operation' => $operation,
			'payload'   => $payload,
			'context'   => $context,
		);
	}

	public function health(): array {
		return array( 'status' => 'ok' );
	}
}

final class ARE_Conflicting_Test_Service implements ARE_Platform_Service_Interface {
	public function id(): string {
		return 'test.service';
	}

	public function version(): string {
		return '9.9.9';
	}

	public function operations(): array {
		return array( 'ping' );
	}

	public function call( string $operation, array $payload = array(), array $context = array() ) {
		return true;
	}

	public function health(): array {
		return array( 'status' => 'ok' );
	}
}

ARE_Platform_Service_Registry::init();

$provider = new ARE_Test_Service();
assert_contract( true === algq_platform_register_service( $provider ), 'first provider registration should succeed' );
assert_contract( ARE_Platform_Service_Registry::has( 'test.service' ), 'registered service should be discoverable' );
assert_contract( true === algq_platform_register_service( new ARE_Test_Service() ), 'same provider class should re-register idempotently' );

$conflict = algq_platform_register_service( new ARE_Conflicting_Test_Service() );
assert_contract( is_wp_error( $conflict ), 'different provider must not replace existing authority' );
assert_contract( 'algq_service_authority_conflict' === $conflict->get_error_code(), 'authority conflict should expose the canonical error code' );

$result = algq_platform_service_call(
	'test.service',
	'PING',
	array( 'value' => 'ok' ),
	array(
		'caller_plugin' => 'ALGQ-Agent-Engine',
		'service_id'    => 'spoofed.service',
		'operation'     => 'spoofed',
		'actor_user_id' => 999,
	)
);
assert_contract( is_array( $result ), 'supported operation should execute' );
assert_contract( 'ping' === $result['operation'], 'operation should be normalized' );
assert_contract( 'test.service' === $result['context']['service_id'], 'service_id cannot be spoofed by caller context' );
assert_contract( 'ping' === $result['context']['operation'], 'operation metadata cannot be spoofed by caller context' );
assert_contract( 'algq-agent-engine' === $result['context']['caller_plugin'], 'caller plugin should be normalized' );
assert_contract( 77 === $result['context']['actor_user_id'], 'actor user ID should come from the active WordPress user context' );

$unsupported = algq_platform_service_call( 'test.service', 'delete_everything' );
assert_contract( is_wp_error( $unsupported ), 'unsupported operation should return WP_Error' );
assert_contract( 'algq_service_operation_not_supported' === $unsupported->get_error_code(), 'unsupported operation error code should be stable' );

$missing = algq_platform_service_call( 'missing.service', 'ping' );
assert_contract( is_wp_error( $missing ), 'missing service should return WP_Error' );
assert_contract( 'algq_service_not_found' === $missing->get_error_code(), 'missing service error code should be stable' );

$catalog = algq_platform_services( true );
assert_contract( isset( $catalog['test.service'] ), 'catalog should expose registered service' );
assert_contract( '1.2.3' === $catalog['test.service']['version'], 'catalog should expose provider version' );
assert_contract( array( 'ping' ) === $catalog['test.service']['operations'], 'catalog should expose supported operations' );
assert_contract( 'ok' === $catalog['test.service']['health']['status'], 'catalog should expose health when requested' );

fwrite( STDOUT, "PASS: Platform Service Interface contract\n" );
