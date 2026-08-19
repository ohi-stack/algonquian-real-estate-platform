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

	/**
	 * Send a transactional platform email through wp_mail().
	 *
	 * @param array<string,mixed> $args Message arguments.
	 * @return true|WP_Error
	 */
	public static function send( array $args ) {
		$to      = sanitize_email( (string) ( $args['to'] ?? '' ) );
		$subject = sanitize_text_field( (string) ( $args['subject'] ?? '' ) );
		$message = (string) ( $args['message'] ?? '' );

		if ( ! is_email( $to ) || '' === $subject || '' === trim( wp_strip_all_tags( $message ) ) ) {
			return new WP_Error( 'algq_mail_invalid_message', __( 'A valid recipient, subject, and message are required.', 'algonquian-real-estate-platform' ) );
		}

		$headers = array();
		foreach ( (array) ( $args['headers'] ?? array() ) as $header ) {
			$header = trim( str_replace( array( "\r", "\n" ), '', (string) $header ) );
			if ( '' !== $header ) {
				$headers[] = $header;
			}
		}

		$reply_to = sanitize_email( (string) ( $args['reply_to'] ?? '' ) );
		if ( is_email( $reply_to ) ) {
			$headers[] = 'Reply-To: ' . $reply_to;
		}

		$attachments = array();
		foreach ( (array) ( $args['attachments'] ?? array() ) as $attachment ) {
			$path = wp_normalize_path( (string) $attachment );
			if ( is_file( $path ) && is_readable( $path ) ) {
				$attachments[] = $path;
			}
		}

		$sent = wp_mail( $to, $subject, $message, $headers, $attachments );
		$context = array(
			'module'           => sanitize_key( (string) ( $args['module'] ?? 'platform' ) ),
			'event'            => sanitize_key( (string) ( $args['event'] ?? 'transactional_email' ) ),
			'related_id'       => absint( $args['related_id'] ?? 0 ),
			'success'          => (bool) $sent,
			'recipient_domain' => self::email_domain( $to ),
			'attachment_count' => count( $attachments ),
		);

		if ( class_exists( 'ALGQ_Platform_Audit_Log' ) ) {
			ALGQ_Platform_Audit_Log::log( 'mail.transactional', $context );
		} else {
			do_action( 'algq_audit_event', 'mail.transactional', $context );
		}

		return $sent ? true : new WP_Error( 'algq_mail_send_failed', __( 'The message could not be sent.', 'algonquian-real-estate-platform' ) );
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

	private static function email_domain( string $email ): string {
		$parts = explode( '@', $email );
		return 2 === count( $parts ) ? sanitize_text_field( strtolower( $parts[1] ) ) : '';
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

if ( ! function_exists( 'algq_send_mail' ) ) {
	/**
	 * Shared companion-plugin mail contract.
	 *
	 * @param array<string,mixed> $args Mail arguments.
	 * @return true|WP_Error
	 */
	function algq_send_mail( array $args ) {
		return ALGQ_Mail_Gateway::send( $args );
	}
}

/**
 * Platform-level baseline throttle for unauthenticated Algonquian POST actions.
 * Companion plugins may apply stricter action-specific controls.
 */
final class ALGQ_Public_Form_Throttle {
	private const DEFAULT_RATE_LIMIT = 20;

	public static function init(): void {
		add_action( 'admin_init', array( __CLASS__, 'enforce_rate_limit' ), 1 );
	}

	public static function enforce_rate_limit(): void {
		if ( is_user_logged_in() || 'POST' !== strtoupper( (string) ( $_SERVER['REQUEST_METHOD'] ?? '' ) ) ) {
			return;
		}

		$action = isset( $_REQUEST['action'] ) ? sanitize_key( wp_unslash( $_REQUEST['action'] ) ) : '';
		if ( '' === $action || ! str_starts_with( $action, 'algq_' ) ) {
			return;
		}

		$limit = max( 1, absint( apply_filters( 'algq_public_form_rate_limit_per_hour', self::DEFAULT_RATE_LIMIT, $action ) ) );
		$key   = self::rate_limit_key( $action );
		$count = absint( get_transient( $key ) );

		if ( $count >= $limit ) {
			$context = array(
				'action' => $action,
				'limit'  => $limit,
			);
			if ( class_exists( 'ALGQ_Platform_Audit_Log' ) ) {
				ALGQ_Platform_Audit_Log::log( 'security.public_form_rate_limited', $context );
			} else {
				do_action( 'algq_audit_event', 'security.public_form_rate_limited', $context );
			}
			status_header( 429 );
			wp_die(
				esc_html__( 'Too many requests. Please wait before submitting this form again.', 'algonquian-real-estate-platform' ),
				esc_html__( 'Request limit reached', 'algonquian-real-estate-platform' ),
				array( 'response' => 429 )
			);
		}

		set_transient( $key, $count + 1, HOUR_IN_SECONDS );
	}

	private static function rate_limit_key( string $action ): string {
		$ip = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';
		$ua = isset( $_SERVER['HTTP_USER_AGENT'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ) : '';
		return 'algq_public_form_rate_' . hash_hmac( 'sha256', $action . '|' . $ip . '|' . substr( $ua, 0, 300 ), wp_salt( 'nonce' ) );
	}
}

ALGQ_Public_Form_Throttle::init();
