<?php
/**
 * Platform health monitoring.
 *
 * @package AlgonquianRealEstatePlatform
 */

defined( 'ABSPATH' ) || exit;

final class ALGQ_Platform_Health_Monitor {
	private const EVENT = 'algq_platform_hourly_health';
	private const OPTION = 'algq_platform_health_snapshot';

	public static function init(): void {
		add_action( self::EVENT, array( __CLASS__, 'run' ) );
		add_action( 'rest_api_init', array( __CLASS__, 'register_rest_route' ) );
		self::schedule();
	}

	public static function schedule(): void {
		if ( ! wp_next_scheduled( self::EVENT ) ) {
			wp_schedule_event( time() + HOUR_IN_SECONDS, 'hourly', self::EVENT );
		}
	}

	public static function unschedule(): void {
		$timestamp = wp_next_scheduled( self::EVENT );
		if ( $timestamp ) {
			wp_unschedule_event( $timestamp, self::EVENT );
		}
	}

	public static function register_rest_route(): void {
		register_rest_route(
			'algq/v1',
			'/health',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => static fn(): WP_REST_Response => rest_ensure_response( self::latest() ),
				'permission_callback' => static fn(): bool => current_user_can( 'view_algq_system_health' ),
			)
		);
	}

	/** @return array<string,mixed> */
	public static function run(): array {
		global $wpdb;
		$checks = array();

		$checks['database'] = self::check(
			$wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', ALGQ_Platform_Audit_Log::table() ) ) === ALGQ_Platform_Audit_Log::table(),
			'Audit table available'
		);
		$checks['mail_log'] = self::check(
			$wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', ALGQ_Mail_Gateway::table() ) ) === ALGQ_Mail_Gateway::table(),
			'Mail log table available'
		);
		$checks['private_storage'] = self::check( ALGQ_Private_Files::ensure_storage(), 'Private storage writable' );
		$checks['cron'] = self::check( (bool) wp_next_scheduled( self::EVENT ), 'Health cron scheduled' );
		$checks['generated_pages'] = self::check( ALGQ_Platform_Page_Generator::missing_count() === 0, 'Required platform pages present' );

		$registry = ALGQ_Platform_Registry::status();
		$degraded = count(
			array_filter(
				$registry,
				static fn( array $plugin ): bool => ! empty( $plugin['active'] ) && empty( $plugin['compatible'] )
			)
		);
		$checks['registry'] = self::check( 0 === $degraded, 'Active companion plugins compatible' );

		$failed   = count( array_filter( $checks, static fn( array $check ): bool => 'pass' !== $check['status'] ) );
		$snapshot = array(
			'overall'    => 0 === $failed ? 'healthy' : 'degraded',
			'checks'     => $checks,
			'checked_at' => current_time( 'mysql', true ),
			'version'    => ALGQ_PLATFORM_VERSION,
		);
		update_option( self::OPTION, $snapshot, false );
		ALGQ_Platform_Audit_Log::log( 'platform.health_checked', array( 'overall' => $snapshot['overall'], 'failed_checks' => $failed ) );
		return $snapshot;
	}

	/** @return array<string,mixed> */
	public static function latest(): array {
		$snapshot = get_option( self::OPTION, array() );
		return is_array( $snapshot ) ? $snapshot : array();
	}

	/** @return array{status:string,message:string} */
	private static function check( bool $passed, string $message ): array {
		return array( 'status' => $passed ? 'pass' : 'fail', 'message' => $message );
	}
}
