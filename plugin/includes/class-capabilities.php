<?php
/**
 * Shared roles and capabilities.
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

	public static function init(): void {
		add_action( 'admin_init', array( __CLASS__, 'reconcile' ) );
	}

	public static function install(): void {
		$administrator = get_role( 'administrator' );
		if ( $administrator ) {
			foreach ( self::PLATFORM_CAPABILITIES as $capability ) {
				$administrator->add_cap( $capability );
			}
		}

		add_role(
			'algq_platform_manager',
			__( 'Algonquian Platform Manager', 'algonquian-real-estate-platform' ),
			array_fill_keys( array_merge( array( 'read' ), self::PLATFORM_CAPABILITIES ), true )
		);

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
}
