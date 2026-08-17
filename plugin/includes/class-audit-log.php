<?php
/**
 * Append-only structured audit service.
 *
 * @package AlgonquianRealEstatePlatform
 */

defined( 'ABSPATH' ) || exit;

final class ALGQ_Platform_Audit_Log {
	public static function init(): void {
		add_action( 'algq_audit_event', array( __CLASS__, 'handle_event' ), 10, 3 );
	}

	public static function install(): void {
		global $wpdb;
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$table           = self::table();
		$charset_collate = $wpdb->get_charset_collate();
		$sql             = "CREATE TABLE {$table} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			event_uuid char(36) NOT NULL,
			event_name varchar(190) NOT NULL,
			plugin_slug varchar(190) NOT NULL DEFAULT 'algonquian-real-estate-platform',
			severity varchar(20) NOT NULL DEFAULT 'info',
			user_id bigint(20) unsigned NOT NULL DEFAULT 0,
			object_type varchar(100) NOT NULL DEFAULT '',
			object_id varchar(190) NOT NULL DEFAULT '',
			request_source varchar(50) NOT NULL DEFAULT '',
			payload longtext NULL,
			created_at datetime NOT NULL,
			PRIMARY KEY (id),
			UNIQUE KEY event_uuid (event_uuid),
			KEY event_name (event_name),
			KEY plugin_slug (plugin_slug),
			KEY object_lookup (object_type, object_id),
			KEY created_at (created_at)
		) {$charset_collate};";

		dbDelta( $sql );
	}

	/**
	 * @param array<string,mixed> $payload Event payload.
	 * @param array<string,mixed> $context Event context.
	 */
	public static function log( string $event_name, array $payload = array(), array $context = array() ): bool {
		global $wpdb;
		$event_name = sanitize_key( str_replace( '-', '_', $event_name ) );
		if ( '' === $event_name ) {
			return false;
		}

		$inserted = $wpdb->insert(
			self::table(),
			array(
				'event_uuid'     => wp_generate_uuid4(),
				'event_name'     => $event_name,
				'plugin_slug'    => sanitize_key( (string) ( $context['plugin'] ?? 'algonquian-real-estate-platform' ) ),
				'severity'       => sanitize_key( (string) ( $context['severity'] ?? 'info' ) ),
				'user_id'        => get_current_user_id(),
				'object_type'    => sanitize_key( (string) ( $context['object_type'] ?? '' ) ),
				'object_id'      => sanitize_text_field( (string) ( $context['object_id'] ?? '' ) ),
				'request_source' => self::request_source(),
				'payload'        => wp_json_encode( self::redact( $payload ), JSON_UNESCAPED_SLASHES ),
				'created_at'     => current_time( 'mysql', true ),
			),
			array( '%s', '%s', '%s', '%s', '%d', '%s', '%s', '%s', '%s', '%s' )
		);

		return false !== $inserted;
	}

	/**
	 * @param array<string,mixed> $payload Event payload.
	 * @param array<string,mixed> $context Event context.
	 */
	public static function handle_event( string $event_name, array $payload = array(), array $context = array() ): void {
		self::log( $event_name, $payload, $context );
	}

	public static function table(): string {
		global $wpdb;
		return $wpdb->prefix . 'algq_audit_log';
	}

	/** @param mixed $value */
	private static function redact( $value ) {
		$sensitive = array( 'password', 'pass', 'secret', 'token', 'authorization', 'api_key', 'signature', 'account_number' );
		if ( ! is_array( $value ) ) {
			return is_scalar( $value ) || null === $value ? $value : '[unsupported]';
		}

		$clean = array();
		foreach ( $value as $key => $item ) {
			$normalized    = strtolower( (string) $key );
			$clean[ $key ] = in_array( $normalized, $sensitive, true ) ? '[redacted]' : self::redact( $item );
		}
		return $clean;
	}

	private static function request_source(): string {
		if ( wp_doing_cron() ) {
			return 'cron';
		}
		if ( wp_doing_ajax() ) {
			return 'ajax';
		}
		if ( defined( 'REST_REQUEST' ) && REST_REQUEST ) {
			return 'rest';
		}
		return is_admin() ? 'admin' : 'frontend';
	}
}

if ( ! function_exists( 'algq_log_event' ) ) {
	/**
	 * @param array<string,mixed> $payload Event payload.
	 * @param array<string,mixed> $context Event context.
	 */
	function algq_log_event( string $event_name, array $payload = array(), array $context = array() ): bool {
		return ALGQ_Platform_Audit_Log::log( $event_name, $payload, $context );
	}
}
