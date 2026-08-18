<?php
/**
 * Shared roles, capabilities, and protected-system access controls.
 *
 * @package AlgonquianRealEstatePlatform
 */

defined( 'ABSPATH' ) || exit;

final class ALGQ_Platform_Capabilities {
	/** @var string[] */
	private const PLATFORM_CAPABILITIES = array(
		'manage_algq_platform',
		'manage_algq_platform_plugins',
		'manage_algq_platform_pages',
		'manage_algq_platform_files',
		'manage_algq_email',
		'view_algq_audit_logs',
		'export_algq_reports',
		'view_algq_system_health',
		'view_algq_internal_operations',
		'view_algq_funding',
		'view_algq_deals',
		'view_algq_underwriting',
		'view_algq_offer_history',
		'view_algq_documents',
		'view_algq_command_center',
	);

	/** @var string[] */
	private const BUYER_CAPABILITIES = array(
		'read',
		'algq_view_buyer_portal',
		'view_algq_deals',
		'view_algq_buyer_dashboard',
		'view_algq_marketplace',
		'accept_algq_nda',
		'submit_algq_buyer_interest',
		'download_algq_deal_documents',
		'submit_algq_buyer_offer',
	);

	/**
	 * Operational shortcodes that must never render for an unauthorized visitor.
	 * Public marketing, registration, login, documentation, and intake shortcodes
	 * are intentionally excluded from this map.
	 *
	 * @var array<string,string>
	 */
	private const PROTECTED_SHORTCODES = array(
		'algq_funding_tracker'              => 'funding',
		'algq_funding_dashboard'            => 'funding',
		'algq_capital_sources'              => 'funding',
		'algq_pipeline_crm'                 => 'crm',
		'algq_pipeline_dashboard'           => 'crm',
		'algq_pipeline_board'               => 'crm',
		'algq_pipeline_activity'            => 'crm',
		'algq_mao_calculator'               => 'underwriting',
		'algq_offer_generator'              => 'offers',
		'algq_offer_builder'                => 'offers',
		'algq_offer_history'                => 'offers',
		'algq_document_library'             => 'documents',
		'algq_pdf_engine'                   => 'documents',
		'algq_signature_archive'            => 'documents',
		'algq_command_center'               => 'command_center',
		'algq_admin_dashboard'              => 'command_center',
		'algq_command_center_kpis'          => 'analytics',
		'algq_command_center_pipeline'      => 'analytics',
		'algq_command_center_activity'      => 'analytics',
		'algq_command_center_health'        => 'analytics',
		'algq_buyer_dashboard'              => 'buyer_portal',
		'algq_buyer_deals'                  => 'buyer_portal',
		'algq_buyer_marketplace_dashboard'  => 'buyer_portal',
		'algq_buyer_nda_gate'               => 'buyer_portal',
		'algq_buyer_offer_form'             => 'buyer_portal',
		'algq_stewardship_portal'           => 'stewardship_portal',
		'algq_tenant_portal'                => 'tenant_portal',
		'algq_tenant_dashboard'             => 'tenant_portal',
		'algq_tenant_documents'             => 'tenant_portal',
		'algq_tenant_lease'                 => 'tenant_portal',
		'algq_tenant_maintenance'           => 'tenant_portal',
	);

	/**
	 * Canonical operational routes requiring an authorization gate even if a
	 * page builder or legacy page no longer contains the expected shortcode.
	 *
	 * @var array<string,string>
	 */
	private const PROTECTED_PATHS = array(
		'funding-tracker'                 => 'funding',
		'funding-dashboard'               => 'funding',
		'pipeline-crm'                    => 'crm',
		'pipeline-board'                  => 'crm',
		'underwriting'                    => 'underwriting',
		'mao-calculator'                  => 'underwriting',
		'plugin/mao-engine/calculator'    => 'underwriting',
		'offer-generator'                 => 'offers',
		'generate-offer'                  => 'offers',
		'offer-history'                   => 'offers',
		'document-library'                => 'documents',
		'documents/signatures'            => 'documents',
		'command-center'                  => 'command_center',
		'internal-analytics'              => 'analytics',
		'buyer-dashboard'                 => 'buyer_portal',
		'buyer-deals'                     => 'buyer_portal',
		'buyer-dashboard/marketplace'     => 'buyer_portal',
		'buyer-dashboard/nda'             => 'buyer_portal',
		'buyer-dashboard/submit-offer'    => 'buyer_portal',
		'property-stewardship-portal'     => 'stewardship_portal',
		'tenant-portal'                   => 'tenant_portal',
		'client-portal'                   => 'client_portal',
	);

	public static function init(): void {
		add_action( 'admin_init', array( __CLASS__, 'reconcile' ) );
		add_action( 'template_redirect', array( __CLASS__, 'protect_current_request' ), 0 );
		add_filter( 'pre_do_shortcode_tag', array( __CLASS__, 'protect_shortcode' ), 1, 4 );
	}

	public static function install(): void {
		$administrator = get_role( 'administrator' );
		if ( $administrator ) {
			foreach ( self::PLATFORM_CAPABILITIES as $capability ) {
				$administrator->add_cap( $capability );
			}
		}

		$manager = get_role( 'algq_platform_manager' );
		if ( ! $manager ) {
			add_role(
				'algq_platform_manager',
				__( 'Algonquian Platform Manager', 'algonquian-real-estate-platform' ),
				array_fill_keys( array_merge( array( 'read' ), self::PLATFORM_CAPABILITIES ), true )
			);
			$manager = get_role( 'algq_platform_manager' );
		}

		if ( $manager ) {
			foreach ( array_merge( array( 'read' ), self::PLATFORM_CAPABILITIES ) as $capability ) {
				$manager->add_cap( $capability );
			}
		}

		$buyer = get_role( 'algq_buyer' );
		if ( ! $buyer ) {
			add_role(
				'algq_buyer',
				__( 'Algonquian Buyer', 'algonquian-real-estate-platform' ),
				array_fill_keys( self::BUYER_CAPABILITIES, true )
			);
			$buyer = get_role( 'algq_buyer' );
		}

		if ( $buyer ) {
			foreach ( self::BUYER_CAPABILITIES as $capability ) {
				$buyer->add_cap( $capability );
			}
		}
	}

	public static function reconcile(): void {
		if ( get_option( 'algq_platform_capability_version' ) === ALGQ_PLATFORM_VERSION ) {
			return;
		}

		self::install();
		update_option( 'algq_platform_capability_version', ALGQ_PLATFORM_VERSION );
	}

	/** @return string[] */
	public static function platform_capabilities(): array {
		return self::PLATFORM_CAPABILITIES;
	}

	/** @return string[] */
	public static function buyer_capabilities(): array {
		return self::BUYER_CAPABILITIES;
	}

	/**
	 * Block direct front-end access to a protected operational route before its
	 * template, page builder, shortcode, or cached component can render.
	 */
	public static function protect_current_request(): void {
		if ( is_admin() || wp_doing_ajax() || wp_doing_cron() || is_feed() ) {
			return;
		}

		$system = self::system_for_current_request();
		if ( '' === $system ) {
			return;
		}

		self::send_private_response_headers();

		if ( self::can_access_system( $system ) ) {
			return;
		}

		self::record_denial( $system, 'route' );

		if ( ! is_user_logged_in() ) {
			wp_safe_redirect( wp_login_url( self::current_url() ) );
			exit;
		}

		wp_die(
			esc_html__( 'Your account is not authorized to access this protected Algonquian Real Estate system.', 'algonquian-real-estate-platform' ),
			esc_html__( 'Access denied', 'algonquian-real-estate-platform' ),
			array( 'response' => 403 )
		);
	}

	/**
	 * Prevent a protected operational shortcode from becoming an authorization
	 * bypass when embedded in an otherwise public page.
	 *
	 * @param string|false|null $return Short-circuit return value.
	 * @param string            $tag    Shortcode tag.
	 * @param array|string      $attr   Shortcode attributes.
	 * @param array             $m      Regex match data.
	 * @return string|false|null
	 */
	public static function protect_shortcode( $return, string $tag, $attr, array $m ) {
		unset( $attr, $m );

		if ( null !== $return || ! isset( self::PROTECTED_SHORTCODES[ $tag ] ) ) {
			return $return;
		}

		$system = self::PROTECTED_SHORTCODES[ $tag ];
		if ( self::can_access_system( $system ) ) {
			return $return;
		}

		self::record_denial( $system, 'shortcode:' . $tag );

		if ( ! is_user_logged_in() ) {
			return sprintf(
				'<div class="algq-protected-system-notice"><p>%1$s</p><p><a href="%2$s">%3$s</a></p></div>',
				esc_html__( 'Authentication is required to access this protected system.', 'algonquian-real-estate-platform' ),
				esc_url( wp_login_url( self::current_url() ) ),
				esc_html__( 'Sign in', 'algonquian-real-estate-platform' )
			);
		}

		return '<div class="algq-protected-system-notice"><p>'
			. esc_html__( 'Your account is not authorized to access this protected system.', 'algonquian-real-estate-platform' )
			. '</p></div>';
	}

	/**
	 * Determine whether the current user may enter a protected system.
	 * Domain-specific capabilities remain authoritative; manage_options is the
	 * emergency administrator override.
	 */
	public static function can_access_system( string $system ): bool {
		if ( ! is_user_logged_in() ) {
			return false;
		}

		if ( current_user_can( 'manage_options' ) ) {
			return true;
		}

		switch ( $system ) {
			case 'funding':
				return current_user_can( 'view_algq_funding' ) || current_user_can( 'manage_algq_funding' );

			case 'crm':
				return current_user_can( 'manage_algq_pipeline' )
					|| current_user_can( 'edit_algq_deals' )
					|| current_user_can( 'create_algq_deals' )
					|| ( current_user_can( 'view_algq_internal_operations' ) && current_user_can( 'view_algq_deals' ) );

			case 'underwriting':
				return current_user_can( 'view_algq_underwriting' )
					|| current_user_can( 'manage_algq_underwriting' )
					|| current_user_can( 'approve_algq_underwriting' );

			case 'offers':
				return current_user_can( 'manage_algq_offers' )
					|| current_user_can( 'view_algq_offer_history' )
					|| current_user_can( 'create_algq_offers' )
					|| current_user_can( 'edit_algq_offers' );

			case 'documents':
				return current_user_can( 'view_algq_documents' )
					|| current_user_can( 'manage_algq_documents' )
					|| current_user_can( 'download_algq_documents' )
					|| current_user_can( 'generate_algq_pdfs' )
					|| current_user_can( 'manage_algq_signatures' );

			case 'command_center':
				return current_user_can( 'view_algq_command_center' ) || current_user_can( 'manage_algq_command_center' );

			case 'analytics':
				return current_user_can( 'view_algq_command_center' )
					|| current_user_can( 'manage_algq_command_center' )
					|| current_user_can( 'export_algq_reports' )
					|| current_user_can( 'view_algq_audit_logs' )
					|| current_user_can( 'view_algq_system_health' );

			case 'buyer_portal':
				return current_user_can( 'view_algq_buyer_dashboard' )
					|| current_user_can( 'algq_view_buyer_portal' )
					|| current_user_can( 'algq_manage_buyer_portal' );

			case 'stewardship_portal':
				return current_user_can( 'view_algq_stewardship_portal' ) || current_user_can( 'manage_algq_stewardship' );

			case 'tenant_portal':
				return current_user_can( 'algq_view_tenant_portal' )
					|| current_user_can( 'algq_manage_tenants' )
					|| current_user_can( 'algq_manage_properties' );

			case 'client_portal':
				return self::can_access_system( 'buyer_portal' )
					|| self::can_access_system( 'stewardship_portal' )
					|| self::can_access_system( 'tenant_portal' );
		}

		return false;
	}

	private static function system_for_current_request(): string {
		$path = trim( (string) wp_parse_url( self::current_url(), PHP_URL_PATH ), '/' );
		$home_path = trim( (string) wp_parse_url( home_url( '/' ), PHP_URL_PATH ), '/' );
		if ( $home_path && 0 === strpos( $path, $home_path . '/' ) ) {
			$path = substr( $path, strlen( $home_path ) + 1 );
		}

		if ( isset( self::PROTECTED_PATHS[ $path ] ) ) {
			return self::PROTECTED_PATHS[ $path ];
		}

		if ( ! is_singular( 'page' ) ) {
			return '';
		}

		$post = get_queried_object();
		if ( ! $post instanceof WP_Post ) {
			return '';
		}

		$content = (string) $post->post_content;
		foreach ( self::PROTECTED_SHORTCODES as $shortcode => $system ) {
			if ( has_shortcode( $content, $shortcode ) ) {
				return $system;
			}
		}

		return '';
	}

	private static function send_private_response_headers(): void {
		if ( ! defined( 'DONOTCACHEPAGE' ) ) {
			define( 'DONOTCACHEPAGE', true );
		}
		nocache_headers();
		if ( ! headers_sent() ) {
			header( 'X-Robots-Tag: noindex, nofollow, noarchive', true );
			header( 'Referrer-Policy: same-origin', true );
		}
	}

	private static function current_url(): string {
		$request_uri = isset( $_SERVER['REQUEST_URI'] ) ? wp_unslash( (string) $_SERVER['REQUEST_URI'] ) : '/';
		$request_uri = '/' . ltrim( $request_uri, '/' );
		return esc_url_raw( home_url( $request_uri ) );
	}

	private static function record_denial( string $system, string $surface ): void {
		if ( ! class_exists( 'ALGQ_Platform_Audit_Log' ) ) {
			return;
		}

		ALGQ_Platform_Audit_Log::log(
			'security.protected_system_denied',
			array(
				'system'  => sanitize_key( $system ),
				'surface' => sanitize_text_field( $surface ),
				'user_id' => get_current_user_id(),
			),
			array( 'severity' => 'warning', 'plugin' => 'algonquian-real-estate-platform' )
		);
	}
}
