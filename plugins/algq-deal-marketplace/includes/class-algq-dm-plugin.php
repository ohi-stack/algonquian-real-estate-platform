<?php
/**
 * Plugin coordinator.
 *
 * @package Algonquian_Deal_Marketplace
 */

defined( 'ABSPATH' ) || exit;

final class ALGQ_DM_Plugin {
	private static ?self $instance = null;

	public static function instance(): self {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	private function __construct() {}

	public function run(): void {
		load_plugin_textdomain( 'algq-deal-marketplace', false, dirname( plugin_basename( ALGQ_DM_FILE ) ) . '/languages' );

		ALGQ_DM_Marketplace::init();
		ALGQ_DM_Access::init();
		ALGQ_DM_NDA::init();
		ALGQ_DM_Offers::init();
		ALGQ_DM_Shortcodes::init();
		ALGQ_DM_Admin::init();
		ALGQ_DM_REST::init();

		add_action( 'admin_init', array( $this, 'maybe_upgrade' ) );
		add_filter( 'plugin_action_links_' . plugin_basename( ALGQ_DM_FILE ), array( $this, 'action_links' ) );
		add_action( 'algq_stripe_entitlement_created', array( 'ALGQ_DM_Access', 'handle_entitlement_created' ), 10, 3 );
		add_action( 'algq_stripe_entitlement_revoked', array( 'ALGQ_DM_Access', 'handle_entitlement_revoked' ), 10, 3 );
	}

	public function maybe_upgrade(): void {
		if ( ALGQ_DM_SCHEMA_VERSION !== get_option( 'algq_dm_schema_version' ) ) {
			ALGQ_DM_Activator::migrate();
		}
	}

	/**
	 * @param array<int,string> $links Existing action links.
	 * @return array<int,string>
	 */
	public function action_links( array $links ): array {
		$links[] = '<a href="' . esc_url( admin_url( 'admin.php?page=algq-deal-marketplace' ) ) . '">' . esc_html__( 'Overview', 'algq-deal-marketplace' ) . '</a>';
		$links[] = '<a href="' . esc_url( admin_url( 'admin.php?page=algq-dm-settings' ) ) . '">' . esc_html__( 'Settings', 'algq-deal-marketplace' ) . '</a>';
		return $links;
	}
}
