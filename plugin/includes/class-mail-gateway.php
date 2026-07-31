<?php
/**
 * Centralized WordPress mail transport and delivery logging.
 *
 * @package AlgonquianRealEstatePlatform
 */

defined( 'ABSPATH' ) || exit;

final class ALGQ_Mail_Gateway {
	private const OPTION_KEY = 'algq_mail_settings';

	public static function init(): void {
		add_action( 'phpmailer_init', array( __CLASS__, 'configure_phpmailer' ) );
		add_filter( 'wp_mail_from', array( __CLASS__, 'filter_from_email' ) );
		add_filter( 'wp_mail_from_name', array( __CLASS__, 'filter_from_name' ) );
		add_action( 'wp_mail_succeeded', array( __CLASS__, 'log_success' ) );
		add_action( 'wp_mail_failed', array( __CLASS__, 'log_failure' ) );
	}

	public static function install(): void {
		global $wpdb;
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		$table           = self::table();
		$charset_collate = $wpdb->get_charset_collate();
		$sql             = "CREATE TABLE {$table} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			message_uuid char(36) NOT NULL,
			status varchar(20) NOT NULL,
			recipient_domain varchar(190) NOT NULL DEFAULT '',
			recipient_hash char(64) NOT NULL DEFAULT '',
			subject varchar(255) NOT NULL DEFAULT '',
			transport varchar(50) NOT NULL DEFAULT 'wp_mail',
			error_code varchar(100) NOT NULL DEFAULT '',
			error_message text NULL,
			created_by bigint(20) unsigned NOT NULL DEFAULT 0,
			created_at datetime NOT NULL,
			PRIMARY KEY (id),
			UNIQUE KEY message_uuid (message_uuid),
			KEY status (status),
			KEY created_at (created_at)
		) {$charset_collate};";
		dbDelta( $sql );
	}

	/** @return array<string,mixed> */
	public static function settings(): array {
		$defaults = array(
			'enabled'    => false,
			'host'       => '',
			'port'       => 587,
			'encryption' => 'tls',
			'username'   => '',
			'from_email' => '',
			'from_name'  => 'Algonquian Real Estate',
		);
		return array_merge( $defaults, (array) get_option( self::OPTION_KEY, array() ) );
	}

	/** @param array<string,mixed> $input */
	public static function save_settings( array $input ): void {
		$encryption = sanitize_key( (string) ( $input['encryption'] ?? 'tls' ) );
		if ( ! in_array( $encryption, array( 'tls', 'ssl', 'none' ), true ) ) {
			$encryption = 'tls';
		}

		$settings = array(
			'enabled'    => ! empty( $input['enabled'] ),
			'host'       => sanitize_text_field( (string) ( $input['host'] ?? '' ) ),
			'port'       => min( 65535, max( 1, absint( $input['port'] ?? 587 ) ) ),
			'encryption' => $encryption,
			'username'   => sanitize_text_field( (string) ( $input['username'] ?? '' ) ),
			'from_email' => sanitize_email( (string) ( $input['from_email'] ?? '' ) ),
			'from_name'  => sanitize_text_field( (string) ( $input['from_name'] ?? 'Algonquian Real Estate' ) ),
		);
		update_option( self::OPTION_KEY, $settings, false );
	}

	/** @param PHPMailer\PHPMailer\PHPMailer $phpmailer */
	public static function configure_phpmailer( $phpmailer ): void {
		$settings = self::settings();
		if ( empty( $settings['enabled'] ) && ! defined( 'ALGQ_SMTP_HOST' ) ) {
			return;
		}

		$host = defined( 'ALGQ_SMTP_HOST' ) ? ALGQ_SMTP_HOST : $settings['host'];
		if ( ! is_string( $host ) || '' === trim( $host ) ) {
			return;
		}

		$phpmailer->isSMTP();
		$phpmailer->Host     = sanitize_text_field( $host );
		$phpmailer->Port     = defined( 'ALGQ_SMTP_PORT' ) ? absint( ALGQ_SMTP_PORT ) : absint( $settings['port'] );
		$phpmailer->SMTPAuth = defined( 'ALGQ_SMTP_USERNAME' ) || '' !== (string) $settings['username'];
		$phpmailer->Timeout  = 20;

		if ( $phpmailer->SMTPAuth ) {
			$phpmailer->Username = defined( 'ALGQ_SMTP_USERNAME' ) ? (string) ALGQ_SMTP_USERNAME : (string) $settings['username'];
			$phpmailer->Password = defined( 'ALGQ_SMTP_PASSWORD' ) ? (string) ALGQ_SMTP_PASSWORD : (string) getenv( 'ALGQ_SMTP_PASSWORD' );
		}

		$encryption = defined( 'ALGQ_SMTP_ENCRYPTION' ) ? sanitize_key( (string) ALGQ_SMTP_ENCRYPTION ) : (string) $settings['encryption'];
		if ( in_array( $encryption, array( 'tls', 'ssl' ), true ) ) {
			$phpmailer->SMTPSecure = $encryption;
		} else {
			$phpmailer->SMTPSecure  = '';
			$phpmailer->SMTPAutoTLS = false;
		}
	}

	public static function filter_from_email( string $email ): string {
		$settings = self::settings();
		$from     = defined( 'ALGQ_SMTP_FROM_EMAIL' ) ? sanitize_email( (string) ALGQ_SMTP_FROM_EMAIL ) : sanitize_email( (string) $settings['from_email'] );
		return $from ?: $email;
	}

	public static function filter_from_name( string $name ): string {
		$settings = self::settings();
		$from     = defined( 'ALGQ_SMTP_FROM_NAME' ) ? sanitize_text_field( (string) ALGQ_SMTP_FROM_NAME ) : sanitize_text_field( (string) $settings['from_name'] );
		return $from ?: $name;
	}

	/** @param array<string,mixed> $mail_data */
	public static function log_success( array $mail_data ): void {
		self::write_log( 'success', $mail_data );
	}

	public static function log_failure( WP_Error $error ): void {
		$data = $error->get_error_data();
		self::write_log(
			'failed',
			is_array( $data ) ? $data : array(),
			(string) $error->get_error_code(),
			(string) $error->get_error_message()
		);
	}

	public static function table(): string {
		global $wpdb;
		return $wpdb->prefix . 'algq_mail_log';
	}

	/**
	 * @param array<string,mixed> $mail_data Mail data.
	 */
	private static function write_log( string $status, array $mail_data, string $error_code = '', string $error_message = '' ): void {
		global $wpdb;
		$recipients = $mail_data['to'] ?? array();
		$recipients = is_array( $recipients ) ? $recipients : array( $recipients );
		$recipient  = sanitize_email( (string) reset( $recipients ) );
		$domain     = '';
		if ( str_contains( $recipient, '@' ) ) {
			$domain = sanitize_text_field( substr( strrchr( $recipient, '@' ), 1 ) );
		}

		$wpdb->insert(
			self::table(),
			array(
				'message_uuid'     => wp_generate_uuid4(),
				'status'           => sanitize_key( $status ),
				'recipient_domain' => $domain,
				'recipient_hash'   => $recipient ? hash_hmac( 'sha256', strtolower( $recipient ), wp_salt( 'auth' ) ) : '',
				'subject'          => sanitize_text_field( (string) ( $mail_data['subject'] ?? '' ) ),
				'transport'        => ( self::settings()['enabled'] || defined( 'ALGQ_SMTP_HOST' ) ) ? 'smtp' : 'wp_mail',
				'error_code'       => sanitize_key( $error_code ),
				'error_message'    => sanitize_textarea_field( $error_message ),
				'created_by'       => get_current_user_id(),
				'created_at'       => current_time( 'mysql', true ),
			),
			array( '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%d', '%s' )
		);
	}
}
