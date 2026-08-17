<?php
/**
 * Marketplace administration.
 *
 * @package Algonquian_Deal_Marketplace
 */

defined( 'ABSPATH' ) || exit;

final class ALGQ_DM_Admin {
	public static function init(): void {
		add_action( 'admin_menu', array( __CLASS__, 'menu' ) );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'assets' ) );
	}

	public static function menu(): void {
		add_menu_page( __( 'Deal Marketplace', 'algq-deal-marketplace' ), __( 'Deal Marketplace', 'algq-deal-marketplace' ), 'manage_algq_marketplace', 'algq-deal-marketplace', array( __CLASS__, 'overview' ), 'dashicons-store', 31 );
		add_submenu_page( 'algq-deal-marketplace', __( 'Marketplace Overview', 'algq-deal-marketplace' ), __( 'Overview', 'algq-deal-marketplace' ), 'manage_algq_marketplace', 'algq-deal-marketplace', array( __CLASS__, 'overview' ) );
		add_submenu_page( 'algq-deal-marketplace', __( 'Marketplace Offers', 'algq-deal-marketplace' ), __( 'Offers', 'algq-deal-marketplace' ), 'review_algq_marketplace_offers', 'algq-dm-offers', array( __CLASS__, 'offers' ) );
		add_submenu_page( 'algq-deal-marketplace', __( 'Marketplace Settings', 'algq-deal-marketplace' ), __( 'Settings', 'algq-deal-marketplace' ), 'manage_algq_marketplace', 'algq-dm-settings', array( __CLASS__, 'settings' ) );
	}

	public static function assets( string $hook ): void {
		if ( ! str_contains( $hook, 'algq' ) && 'post.php' !== $hook && 'post-new.php' !== $hook ) {
			return;
		}
		wp_enqueue_style( 'algq-dm-admin', ALGQ_DM_URL . 'assets/css/admin.css', array(), ALGQ_DM_VERSION );
	}

	public static function overview(): void {
		if ( ! current_user_can( 'manage_algq_marketplace' ) ) {
			ALGQ_DM_Support::abort( __( 'Unauthorized.', 'algq-deal-marketplace' ), 403 );
		}
		global $wpdb;
		$deal_counts = wp_count_posts( 'algq_market_deal' );
		$offers_total = (int) $wpdb->get_var( 'SELECT COUNT(*) FROM ' . ALGQ_DM_Support::table( 'offers' ) );
		$nda_total = (int) $wpdb->get_var( 'SELECT COUNT(*) FROM ' . ALGQ_DM_Support::table( 'nda_acceptances' ) . ' WHERE revoked_at IS NULL' );
		$grants_total = (int) $wpdb->get_var( 'SELECT COUNT(*) FROM ' . ALGQ_DM_Support::table( 'access_grants' ) . ' WHERE revoked_at IS NULL' );
		$health = self::health();

		echo '<div class="wrap algq-dm-admin"><div class="algq-dm-admin__header"><div><span>Algonquian Real Estate Platform</span><h1>' . esc_html__( 'Deal Marketplace', 'algq-deal-marketplace' ) . '</h1><p>' . esc_html__( 'Controlled distribution, access evidence, buyer offers, and secure package activity.', 'algq-deal-marketplace' ) . '</p></div><div><strong>2.0.0 Production</strong></div></div>';
		echo '<div class="algq-dm-admin__cards">';
		self::card( __( 'Published Deals', 'algq-deal-marketplace' ), (string) ( $deal_counts->publish ?? 0 ) );
		self::card( __( 'Offers', 'algq-deal-marketplace' ), (string) $offers_total );
		self::card( __( 'Active NDA Records', 'algq-deal-marketplace' ), (string) $nda_total );
		self::card( __( 'Active Access Grants', 'algq-deal-marketplace' ), (string) $grants_total );
		echo '</div><div class="algq-dm-admin__panel"><h2>' . esc_html__( 'Production Health', 'algq-deal-marketplace' ) . '</h2><table class="widefat striped"><tbody>';
		foreach ( $health as $label => $item ) {
			echo '<tr><th>' . esc_html( $label ) . '</th><td><span class="algq-dm-admin__status is-' . esc_attr( $item['status'] ) . '">' . esc_html( ucfirst( $item['status'] ) ) . '</span></td><td>' . esc_html( $item['message'] ) . '</td></tr>';
		}
		echo '</tbody></table></div></div>';
	}

	private static function card( string $label, string $value ): void {
		echo '<div class="algq-dm-admin__card"><span>' . esc_html( $label ) . '</span><strong>' . esc_html( $value ) . '</strong></div>';
	}

	/** @return array<string,array{status:string,message:string}> */
	private static function health(): array {
		$buyer = get_role( 'algq_buyer' );
		$buyer_caps_ok = $buyer && $buyer->has_cap( 'view_algq_marketplace' ) && $buyer->has_cap( 'submit_algq_marketplace_offer' ) && $buyer->has_cap( 'download_algq_marketplace_packages' );
		$private_filter = has_filter( 'algq_dm_package_file_path' ) || defined( 'ALGQ_PRIVATE_STORAGE_DIR' );
		return array(
			__( 'Database schema', 'algq-deal-marketplace' ) => array( 'status' => ALGQ_DM_SCHEMA_VERSION === get_option( 'algq_dm_schema_version' ) ? 'healthy' : 'warning', 'message' => __( 'Marketplace schema version and migrations.', 'algq-deal-marketplace' ) ),
			__( 'Buyer capabilities', 'algq-deal-marketplace' ) => array( 'status' => $buyer_caps_ok ? 'healthy' : 'warning', 'message' => __( 'Shared buyer role must include Marketplace and Buyer Portal capabilities.', 'algq-deal-marketplace' ) ),
			__( 'Private package storage', 'algq-deal-marketplace' ) => array( 'status' => $private_filter ? 'healthy' : 'warning', 'message' => $private_filter ? __( 'A private package path integration is registered.', 'algq-deal-marketplace' ) : __( 'Configure the Document Library or private-storage path filter before publishing confidential packages.', 'algq-deal-marketplace' ) ),
			__( 'Central audit integration', 'algq-deal-marketplace' ) => array( 'status' => has_action( 'algq_audit_log' ) ? 'healthy' : 'degraded', 'message' => __( 'Local audit remains active; the Platform audit listener is optional but recommended.', 'algq-deal-marketplace' ) ),
		);
	}

	public static function offers(): void {
		if ( ! current_user_can( 'review_algq_marketplace_offers' ) ) {
			ALGQ_DM_Support::abort( __( 'Unauthorized.', 'algq-deal-marketplace' ), 403 );
		}
		global $wpdb;
		$offers = $wpdb->get_results( 'SELECT * FROM ' . ALGQ_DM_Support::table( 'offers' ) . ' ORDER BY created_at DESC LIMIT 200' );
		echo '<div class="wrap algq-dm-admin"><h1>' . esc_html__( 'Marketplace Offers', 'algq-deal-marketplace' ) . '</h1><table class="widefat striped"><thead><tr><th>ID</th><th>' . esc_html__( 'Buyer', 'algq-deal-marketplace' ) . '</th><th>' . esc_html__( 'Deal', 'algq-deal-marketplace' ) . '</th><th>' . esc_html__( 'Amount', 'algq-deal-marketplace' ) . '</th><th>' . esc_html__( 'Financing', 'algq-deal-marketplace' ) . '</th><th>' . esc_html__( 'Status', 'algq-deal-marketplace' ) . '</th><th>' . esc_html__( 'Action', 'algq-deal-marketplace' ) . '</th></tr></thead><tbody>';
		foreach ( $offers as $offer ) {
			$user = get_userdata( (int) $offer->user_id );
			echo '<tr><td>' . esc_html( (string) $offer->id ) . '</td><td>' . esc_html( $user ? $user->display_name : '#' . $offer->user_id ) . '</td><td>' . esc_html( get_the_title( (int) $offer->deal_id ) ) . '</td><td>$' . esc_html( number_format_i18n( (float) $offer->offer_amount, 2 ) ) . '</td><td>' . esc_html( ucwords( str_replace( '_', ' ', (string) $offer->financing_type ) ) ) . '</td><td>' . esc_html( ucwords( str_replace( '_', ' ', (string) $offer->status ) ) ) . '</td><td><form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '"><input type="hidden" name="action" value="algq_dm_update_offer_status"><input type="hidden" name="offer_id" value="' . esc_attr( (string) $offer->id ) . '">';
			wp_nonce_field( 'algq_dm_update_offer_' . $offer->id );
			echo '<select name="status"><option value="under_review">' . esc_html__( 'Under review', 'algq-deal-marketplace' ) . '</option><option value="accepted">' . esc_html__( 'Accepted', 'algq-deal-marketplace' ) . '</option><option value="rejected">' . esc_html__( 'Rejected', 'algq-deal-marketplace' ) . '</option><option value="expired">' . esc_html__( 'Expired', 'algq-deal-marketplace' ) . '</option></select> <button class="button">' . esc_html__( 'Update', 'algq-deal-marketplace' ) . '</button></form></td></tr>';
		}
		if ( empty( $offers ) ) {
			echo '<tr><td colspan="7">' . esc_html__( 'No offers found.', 'algq-deal-marketplace' ) . '</td></tr>';
		}
		echo '</tbody></table></div>';
	}

	public static function settings(): void {
		if ( ! current_user_can( 'manage_algq_marketplace' ) ) {
			ALGQ_DM_Support::abort( __( 'Unauthorized.', 'algq-deal-marketplace' ), 403 );
		}
		if ( isset( $_POST['algq_dm_save_settings'] ) ) {
			check_admin_referer( 'algq_dm_settings' );
			update_option( 'algq_dm_nda_required', isset( $_POST['nda_required'] ) ? 'yes' : 'no', false );
			update_option( 'algq_dm_default_nda_version', isset( $_POST['default_nda_version'] ) ? sanitize_text_field( wp_unslash( $_POST['default_nda_version'] ) ) : '2026.1', false );
			update_option( 'algq_dm_delete_data_on_uninstall', isset( $_POST['delete_data'] ) ? 'yes' : 'no', false );
			echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Marketplace settings saved.', 'algq-deal-marketplace' ) . '</p></div>';
		}
		$required = 'yes' === get_option( 'algq_dm_nda_required', 'yes' );
		$delete = 'yes' === get_option( 'algq_dm_delete_data_on_uninstall', 'no' );
		echo '<div class="wrap algq-dm-admin"><h1>' . esc_html__( 'Marketplace Settings', 'algq-deal-marketplace' ) . '</h1><form method="post">';
		wp_nonce_field( 'algq_dm_settings' );
		echo '<table class="form-table"><tr><th>' . esc_html__( 'Require NDA', 'algq-deal-marketplace' ) . '</th><td><label><input type="checkbox" name="nda_required" value="1" ' . checked( $required, true, false ) . '> ' . esc_html__( 'Require the current versioned acceptance before package downloads and offers.', 'algq-deal-marketplace' ) . '</label></td></tr><tr><th><label for="default_nda_version">' . esc_html__( 'Default NDA version', 'algq-deal-marketplace' ) . '</label></th><td><input id="default_nda_version" name="default_nda_version" value="' . esc_attr( (string) get_option( 'algq_dm_default_nda_version', '2026.1' ) ) . '"></td></tr><tr><th>' . esc_html__( 'Uninstall cleanup', 'algq-deal-marketplace' ) . '</th><td><label><input type="checkbox" name="delete_data" value="1" ' . checked( $delete, true, false ) . '> ' . esc_html__( 'Delete Marketplace-owned data only when the plugin is uninstalled.', 'algq-deal-marketplace' ) . '</label><p class="description">' . esc_html__( 'Disabled by default. Shared buyer accounts, Buyer Portal records, and Platform records are never deleted.', 'algq-deal-marketplace' ) . '</p></td></tr></table><p><button class="button button-primary" name="algq_dm_save_settings" value="1">' . esc_html__( 'Save Settings', 'algq-deal-marketplace' ) . '</button></p></form></div>';
	}
}
