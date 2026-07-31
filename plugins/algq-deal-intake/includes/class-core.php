<?php
/**
 * Core runtime, security, database, capabilities, and bootstrap services.
 *
 * @package Algonquian_Deal_Intake
 */

defined( 'ABSPATH' ) || exit;

final class ALGQ_Deal_Intake_Security {
	public const CAP_MANAGE = 'manage_algq_intake';
	public const CAP_REVIEW = 'review_algq_intake';
	public const CAP_EXPORT = 'export_algq_intake';
	public const CAP_VIEW_PRIVATE = 'view_algq_intake_private';

	public static function verify_nonce( string $action, string $field = 'algq_di_nonce' ): bool {
		$nonce = isset( $_REQUEST[ $field ] ) ? sanitize_text_field( wp_unslash( $_REQUEST[ $field ] ) ) : '';
		return '' !== $nonce && (bool) wp_verify_nonce( $nonce, $action );
	}

	public static function text( mixed $value ): string {
		return sanitize_text_field( wp_unslash( (string) $value ) );
	}

	public static function textarea( mixed $value ): string {
		return sanitize_textarea_field( wp_unslash( (string) $value ) );
	}

	public static function email( mixed $value ): string {
		return sanitize_email( wp_unslash( (string) $value ) );
	}

	public static function phone( mixed $value ): string {
		return preg_replace( '/[^0-9\+\-\(\)\.\sx]/i', '', wp_unslash( (string) $value ) ) ?? '';
	}

	public static function money( mixed $value ): string {
		$normalized = preg_replace( '/[^0-9\.\-]/', '', wp_unslash( (string) $value ) );
		return is_numeric( $normalized ) ? number_format( (float) $normalized, 2, '.', '' ) : '0.00';
	}

	public static function key( mixed $value ): string {
		return sanitize_key( wp_unslash( (string) $value ) );
	}

	public static function normalize_address( array $data ): string {
		$parts = array_filter(
			array(
				self::text( $data['address'] ?? '' ),
				self::text( $data['city'] ?? '' ),
				strtoupper( self::text( $data['state'] ?? '' ) ),
				self::text( $data['postal_code'] ?? '' ),
			)
		);

		$value = strtolower( implode( '|', $parts ) );
		return preg_replace( '/[^a-z0-9\|]/', '', $value ) ?? '';
	}

	public static function request_ip(): string {
		$ip = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';
		return (string) apply_filters( 'algq_di_consent_ip_address', $ip );
	}

	public static function request_user_agent(): string {
		$agent = isset( $_SERVER['HTTP_USER_AGENT'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ) : '';
		return substr( $agent, 0, 500 );
	}

	public static function rate_limit_key(): string {
		$material = self::request_ip() . '|' . self::request_user_agent();
		return 'algq_di_rate_' . hash_hmac( 'sha256', $material, wp_salt( 'nonce' ) );
	}

	public static function current_user_id(): int {
		return is_user_logged_in() ? get_current_user_id() : 0;
	}
}

final class ALGQ_Deal_Intake_Database {
	public static function table( string $name ): string {
		global $wpdb;
		$map = array(
			'submissions' => $wpdb->prefix . 'algq_intake_submissions',
			'sellers' => $wpdb->prefix . 'algq_intake_sellers',
			'properties' => $wpdb->prefix . 'algq_intake_properties',
			'consents' => $wpdb->prefix . 'algq_intake_consents',
			'attachments' => $wpdb->prefix . 'algq_intake_attachments',
			'duplicates' => $wpdb->prefix . 'algq_intake_duplicate_queue',
		);

		if ( ! isset( $map[ $name ] ) ) {
			throw new InvalidArgumentException( 'Unknown Deal Intake table.' );
		}

		return $map[ $name ];
	}

	public static function install(): void {
		global $wpdb;
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$charset = $wpdb->get_charset_collate();

		$sql = array();
		$sql[] = 'CREATE TABLE ' . self::table( 'sellers' ) . " (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			uuid char(36) NOT NULL,
			name varchar(190) NOT NULL,
			email varchar(190) NOT NULL DEFAULT '',
			phone varchar(80) NOT NULL DEFAULT '',
			mailing_address text NULL,
			preferred_contact varchar(40) NOT NULL DEFAULT '',
			created_at datetime NOT NULL,
			updated_at datetime NOT NULL,
			PRIMARY KEY (id),
			UNIQUE KEY uuid (uuid),
			KEY email (email),
			KEY phone (phone)
		) {$charset};";

		$sql[] = 'CREATE TABLE ' . self::table( 'properties' ) . " (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			uuid char(36) NOT NULL,
			address varchar(255) NOT NULL,
			address_normalized varchar(255) NOT NULL,
			city varchar(120) NOT NULL DEFAULT '',
			state char(2) NOT NULL DEFAULT 'CT',
			postal_code varchar(20) NOT NULL DEFAULT '',
			county varchar(120) NOT NULL DEFAULT '',
			parcel varchar(120) NOT NULL DEFAULT '',
			property_type varchar(80) NOT NULL DEFAULT '',
			occupancy varchar(80) NOT NULL DEFAULT '',
			condition_summary text NULL,
			mortgage_status varchar(80) NOT NULL DEFAULT '',
			estimated_value decimal(14,2) NOT NULL DEFAULT 0.00,
			created_at datetime NOT NULL,
			updated_at datetime NOT NULL,
			PRIMARY KEY (id),
			UNIQUE KEY uuid (uuid),
			KEY address_normalized (address_normalized),
			KEY parcel (parcel)
		) {$charset};";

		$sql[] = 'CREATE TABLE ' . self::table( 'submissions' ) . " (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			uuid char(36) NOT NULL,
			seller_id bigint(20) unsigned NOT NULL,
			property_id bigint(20) unsigned NOT NULL,
			status varchar(40) NOT NULL DEFAULT 'pending_review',
			lead_source varchar(80) NOT NULL DEFAULT 'website',
			campaign varchar(120) NOT NULL DEFAULT '',
			motivation varchar(80) NOT NULL DEFAULT '',
			timeline varchar(80) NOT NULL DEFAULT '',
			asking_price decimal(14,2) NOT NULL DEFAULT 0.00,
			lead_score smallint unsigned NOT NULL DEFAULT 0,
			duplicate_status varchar(40) NOT NULL DEFAULT 'clear',
			pipeline_deal_id bigint(20) unsigned NOT NULL DEFAULT 0,
			created_by bigint(20) unsigned NOT NULL DEFAULT 0,
			created_at datetime NOT NULL,
			updated_at datetime NOT NULL,
			deleted_at datetime NULL,
			PRIMARY KEY (id),
			UNIQUE KEY uuid (uuid),
			KEY seller_id (seller_id),
			KEY property_id (property_id),
			KEY status (status),
			KEY created_at (created_at),
			KEY pipeline_deal_id (pipeline_deal_id)
		) {$charset};";

		$sql[] = 'CREATE TABLE ' . self::table( 'consents' ) . " (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			submission_id bigint(20) unsigned NOT NULL,
			consent_version varchar(40) NOT NULL,
			privacy_version varchar(40) NOT NULL,
			terms_version varchar(40) NOT NULL,
			accepted tinyint(1) unsigned NOT NULL DEFAULT 0,
			ip_address varchar(100) NOT NULL DEFAULT '',
			user_agent varchar(500) NOT NULL DEFAULT '',
			accepted_at datetime NOT NULL,
			PRIMARY KEY (id),
			KEY submission_id (submission_id)
		) {$charset};";

		$sql[] = 'CREATE TABLE ' . self::table( 'attachments' ) . " (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			submission_id bigint(20) unsigned NOT NULL,
			storage_key varchar(255) NOT NULL,
			original_name varchar(255) NOT NULL,
			mime_type varchar(120) NOT NULL,
			file_size bigint(20) unsigned NOT NULL DEFAULT 0,
			checksum char(64) NOT NULL DEFAULT '',
			created_by bigint(20) unsigned NOT NULL DEFAULT 0,
			created_at datetime NOT NULL,
			PRIMARY KEY (id),
			KEY submission_id (submission_id),
			UNIQUE KEY storage_key (storage_key)
		) {$charset};";

		$sql[] = 'CREATE TABLE ' . self::table( 'duplicates' ) . " (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			submission_id bigint(20) unsigned NOT NULL,
			matched_submission_id bigint(20) unsigned NOT NULL,
			match_score smallint unsigned NOT NULL DEFAULT 0,
			matched_fields longtext NULL,
			status varchar(40) NOT NULL DEFAULT 'pending',
			reviewer_id bigint(20) unsigned NOT NULL DEFAULT 0,
			resolution text NULL,
			created_at datetime NOT NULL,
			resolved_at datetime NULL,
			PRIMARY KEY (id),
			KEY submission_id (submission_id),
			KEY matched_submission_id (matched_submission_id),
			KEY status (status)
		) {$charset};";

		foreach ( $sql as $statement ) {
			dbDelta( $statement );
		}
	}
}

final class ALGQ_Deal_Intake_Capabilities {
	public static function register_hooks(): void {
		add_action( 'admin_init', array( __CLASS__, 'maybe_sync' ) );
	}

	public static function install(): void {
		$all = array(
			ALGQ_Deal_Intake_Security::CAP_MANAGE,
			ALGQ_Deal_Intake_Security::CAP_REVIEW,
			ALGQ_Deal_Intake_Security::CAP_EXPORT,
			ALGQ_Deal_Intake_Security::CAP_VIEW_PRIVATE,
		);

		$administrator = get_role( 'administrator' );
		if ( $administrator ) {
			foreach ( $all as $capability ) {
				$administrator->add_cap( $capability );
			}
		}

		$manager = get_role( 'algq_acquisition_manager' );
		if ( ! $manager ) {
			$manager = add_role(
				'algq_acquisition_manager',
				__( 'Acquisition Manager', 'algq-deal-intake' ),
				array(
					'read' => true,
				)
			);
		}

		if ( $manager ) {
			foreach ( $all as $capability ) {
				$manager->add_cap( $capability );
			}
		}

		$coordinator = get_role( 'algq_lead_coordinator' );
		if ( ! $coordinator ) {
			$coordinator = add_role(
				'algq_lead_coordinator',
				__( 'Lead Coordinator', 'algq-deal-intake' ),
				array(
					'read' => true,
				)
			);
		}

		if ( $coordinator ) {
			$coordinator->add_cap( ALGQ_Deal_Intake_Security::CAP_REVIEW );
			$coordinator->add_cap( ALGQ_Deal_Intake_Security::CAP_VIEW_PRIVATE );
		}
	}

	public static function maybe_sync(): void {
		if ( get_option( 'algq_di_caps_version' ) !== ALGQ_DI_VERSION ) {
			self::install();
			update_option( 'algq_di_caps_version', ALGQ_DI_VERSION );
		}
	}
}

final class ALGQ_Deal_Intake_Plugin {
	private static ?self $instance = null;
	private bool $booted = false;

	public static function instance(): self {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	private function __construct() {}

	public function run(): void {
		if ( $this->booted ) {
			return;
		}

		$this->booted = true;

		load_plugin_textdomain( 'algq-deal-intake', false, dirname( plugin_basename( ALGQ_DI_FILE ) ) . '/languages' );

		ALGQ_Deal_Intake_Capabilities::register_hooks();
		ALGQ_Deal_Intake_Pages::register_hooks();
		ALGQ_Deal_Intake_Submissions::register_hooks();
		ALGQ_Deal_Intake_REST::register_hooks();
		ALGQ_Deal_Intake_Admin::register_hooks();

		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_public_assets' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );
		add_action( 'admin_notices', array( $this, 'platform_notice' ) );

		$this->maybe_upgrade();
	}

	public static function activate(): void {
		if ( version_compare( PHP_VERSION, '8.2', '<' ) ) {
			deactivate_plugins( plugin_basename( ALGQ_DI_FILE ) );
			wp_die( esc_html__( 'Algonquian Deal Intake requires PHP 8.2 or newer.', 'algq-deal-intake' ) );
		}

		global $wp_version;
		if ( version_compare( (string) $wp_version, '6.8', '<' ) ) {
			deactivate_plugins( plugin_basename( ALGQ_DI_FILE ) );
			wp_die( esc_html__( 'Algonquian Deal Intake requires WordPress 6.8 or newer.', 'algq-deal-intake' ) );
		}

		ALGQ_Deal_Intake_Database::install();
		ALGQ_Deal_Intake_Capabilities::install();
		ALGQ_Deal_Intake_Pages::create_pages();

		add_option( 'algq_di_notification_email', get_option( 'admin_email' ) );
		add_option( 'algq_di_privacy_version', '1.0' );
		add_option( 'algq_di_terms_version', '1.0' );
		add_option( 'algq_di_consent_version', '1.0' );
		add_option( 'algq_di_rate_limit_per_hour', 10 );
		add_option( 'algq_di_delete_data_on_uninstall', false );
		update_option( 'algq_di_version', ALGQ_DI_VERSION );
		update_option( 'algq_di_schema_version', ALGQ_DI_SCHEMA_VERSION );

		flush_rewrite_rules( false );
	}

	public static function deactivate(): void {
		flush_rewrite_rules( false );
	}

	private function maybe_upgrade(): void {
		$installed = (string) get_option( 'algq_di_schema_version', '0.0.0' );
		if ( version_compare( $installed, ALGQ_DI_SCHEMA_VERSION, '<' ) ) {
			ALGQ_Deal_Intake_Database::install();
			ALGQ_Deal_Intake_Capabilities::install();
			update_option( 'algq_di_schema_version', ALGQ_DI_SCHEMA_VERSION );
		}

		if ( get_option( 'algq_di_version' ) !== ALGQ_DI_VERSION ) {
			update_option( 'algq_di_version', ALGQ_DI_VERSION );
		}
	}

	public function enqueue_public_assets(): void {
		if ( ! is_singular() ) {
			return;
		}

		$post = get_post();
		if ( ! $post || ! has_shortcode( (string) $post->post_content, 'algq_deal_intake_form' ) && ! has_shortcode( (string) $post->post_content, 'deal_intake_form_public' ) && ! has_shortcode( (string) $post->post_content, 'deal_intake_form_internal' ) && ! has_shortcode( (string) $post->post_content, 'deal_quick_capture' ) ) {
			return;
		}

		wp_enqueue_style( 'algq-deal-intake', ALGQ_DI_URL . 'assets/css/front.css', array(), ALGQ_DI_VERSION );
	}

	public function enqueue_admin_assets( string $hook_suffix ): void {
		if ( false === strpos( $hook_suffix, 'algq-deal-intake' ) ) {
			return;
		}

		wp_enqueue_style( 'algq-deal-intake-admin', ALGQ_DI_URL . 'assets/css/admin.css', array(), ALGQ_DI_VERSION );
	}

	public function platform_notice(): void {
		if ( ! current_user_can( 'activate_plugins' ) ) {
			return;
		}

		if ( defined( 'ALGQ_PLATFORM_VERSION' ) || defined( 'ALGQ_REAL_ESTATE_PLATFORM_VERSION' ) ) {
			return;
		}

		echo '<div class="notice notice-warning"><p>';
		echo esc_html__( 'Algonquian Deal Intake is active, but the Algonquian Real Estate Platform Plugin was not detected. Shared audit, mail, file-storage, and Pipeline CRM services will operate only through available compatibility hooks.', 'algq-deal-intake' );
		echo '</p></div>';
	}
}

