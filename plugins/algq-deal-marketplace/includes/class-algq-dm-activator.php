<?php
/**
 * Activation, migrations, capabilities, and generated pages.
 *
 * @package Algonquian_Deal_Marketplace
 */

defined( 'ABSPATH' ) || exit;

final class ALGQ_DM_Activator {
	public static function activate(): void {
		self::migrate();
		flush_rewrite_rules();
	}

	public static function deactivate(): void {
		flush_rewrite_rules();
	}

	public static function migrate(): void {
		self::install_capabilities();
		self::install_tables();
		self::migrate_legacy_nda();
		self::ensure_pages();

		update_option( 'algq_dm_schema_version', ALGQ_DM_SCHEMA_VERSION, false );
		update_option( 'algq_dm_release_status', '2.0.0 Production', false );
		add_option( 'algq_dm_nda_required', 'yes', '', false );
		add_option( 'algq_dm_default_nda_version', '2026.1', '', false );
		add_option( 'algq_dm_delete_data_on_uninstall', 'no', '', false );
	}

	private static function install_capabilities(): void {
		$buyer_caps = array(
			'read',
			'view_algq_buyer_portal',
			'view_algq_deals',
			'view_algq_buyer_dashboard',
			'view_algq_marketplace',
			'view_algq_marketplace_deals',
			'accept_algq_marketplace_nda',
			'submit_algq_marketplace_offer',
			'download_algq_marketplace_packages',
		);

		$buyer = get_role( 'algq_buyer' );
		if ( ! $buyer ) {
			add_role( 'algq_buyer', __( 'Algonquian Buyer', 'algq-deal-marketplace' ), array( 'read' => true ) );
			$buyer = get_role( 'algq_buyer' );
		}
		if ( $buyer ) {
			foreach ( $buyer_caps as $capability ) {
				$buyer->add_cap( $capability );
			}
		}

		$admin_caps = array_merge(
			$buyer_caps,
			array(
				'manage_algq_marketplace',
				'grant_algq_marketplace_access',
				'review_algq_marketplace_offers',
				'export_algq_marketplace_reports',
				'edit_algq_market_deal',
				'read_algq_market_deal',
				'delete_algq_market_deal',
				'edit_algq_market_deals',
				'edit_others_algq_market_deals',
				'publish_algq_market_deals',
				'read_private_algq_market_deals',
				'delete_algq_market_deals',
				'delete_private_algq_market_deals',
				'delete_published_algq_market_deals',
				'delete_others_algq_market_deals',
				'edit_private_algq_market_deals',
				'edit_published_algq_market_deals',
			)
		);

		$administrator = get_role( 'administrator' );
		if ( $administrator ) {
			foreach ( $admin_caps as $capability ) {
				$administrator->add_cap( $capability );
			}
		}
	}

	private static function install_tables(): void {
		global $wpdb;
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$charset = $wpdb->get_charset_collate();
		$activity = ALGQ_DM_Support::table( 'activity' );
		$nda      = ALGQ_DM_Support::table( 'nda_acceptances' );
		$offers   = ALGQ_DM_Support::table( 'offers' );
		$grants   = ALGQ_DM_Support::table( 'access_grants' );

		dbDelta(
			"CREATE TABLE {$activity} (
				id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
				event_uuid CHAR(36) NOT NULL,
				user_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
				deal_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
				offer_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
				event_name VARCHAR(100) NOT NULL,
				event_context LONGTEXT NULL,
				ip_hash CHAR(64) NOT NULL DEFAULT '',
				user_agent_hash CHAR(64) NOT NULL DEFAULT '',
				created_at DATETIME NOT NULL,
				PRIMARY KEY (id),
				UNIQUE KEY event_uuid (event_uuid),
				KEY user_id (user_id),
				KEY deal_id (deal_id),
				KEY event_name (event_name),
				KEY created_at (created_at)
			) {$charset};"
		);

		dbDelta(
			"CREATE TABLE {$nda} (
				id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
				acceptance_uuid CHAR(36) NOT NULL,
				user_id BIGINT UNSIGNED NOT NULL,
				deal_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
				nda_version VARCHAR(80) NOT NULL,
				document_hash CHAR(64) NOT NULL DEFAULT '',
				ip_hash CHAR(64) NOT NULL DEFAULT '',
				user_agent_hash CHAR(64) NOT NULL DEFAULT '',
				accepted_at DATETIME NOT NULL,
				revoked_at DATETIME NULL,
				PRIMARY KEY (id),
				UNIQUE KEY acceptance_uuid (acceptance_uuid),
				KEY user_deal_version (user_id, deal_id, nda_version),
				KEY accepted_at (accepted_at)
			) {$charset};"
		);

		dbDelta(
			"CREATE TABLE {$offers} (
				id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
				offer_uuid CHAR(36) NOT NULL,
				user_id BIGINT UNSIGNED NOT NULL,
				deal_id BIGINT UNSIGNED NOT NULL,
				offer_amount DECIMAL(16,2) NOT NULL DEFAULT 0,
				earnest_money DECIMAL(16,2) NOT NULL DEFAULT 0,
				financing_type VARCHAR(40) NOT NULL DEFAULT 'cash',
				terms LONGTEXT NULL,
				status VARCHAR(40) NOT NULL DEFAULT 'submitted',
				created_at DATETIME NOT NULL,
				updated_at DATETIME NOT NULL,
				PRIMARY KEY (id),
				UNIQUE KEY offer_uuid (offer_uuid),
				KEY user_id (user_id),
				KEY deal_id (deal_id),
				KEY status (status),
				KEY created_at (created_at)
			) {$charset};"
		);

		dbDelta(
			"CREATE TABLE {$grants} (
				id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
				grant_uuid CHAR(36) NOT NULL,
				user_id BIGINT UNSIGNED NOT NULL,
				deal_id BIGINT UNSIGNED NOT NULL,
				source VARCHAR(60) NOT NULL DEFAULT 'manual',
				entitlement_key VARCHAR(191) NOT NULL DEFAULT '',
				created_at DATETIME NOT NULL,
				expires_at DATETIME NULL,
				revoked_at DATETIME NULL,
				PRIMARY KEY (id),
				UNIQUE KEY grant_uuid (grant_uuid),
				KEY user_deal (user_id, deal_id),
				KEY entitlement_key (entitlement_key),
				KEY expires_at (expires_at)
			) {$charset};"
		);
	}

	private static function migrate_legacy_nda(): void {
		if ( get_option( 'algq_dm_legacy_nda_migrated' ) ) {
			return;
		}

		global $wpdb;
		$user_ids = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT user_id FROM {$wpdb->usermeta} WHERE meta_key = %s AND meta_value = %s",
				'algq_dm_nda_accepted',
				'yes'
			)
		);

		foreach ( $user_ids as $user_id ) {
			ALGQ_DM_NDA::record_acceptance( absint( $user_id ), 0, 'legacy-1.0', '', false );
		}

		update_option( 'algq_dm_legacy_nda_migrated', 'yes', false );
	}

	private static function ensure_pages(): void {
		$marketplace_id = self::ensure_page(
			'marketplace',
			__( 'Deal Marketplace', 'algq-deal-marketplace' ),
			'[vc_row full_width="stretch_row_content"][vc_column][vc_column_text]<div class="algq-marketplace-page-intro"><strong>Algonquian Real Estate • Controlled Deal Access</strong><h1>Deal Marketplace</h1><p>Review curated Connecticut real estate opportunities through a controlled buyer-access workflow.</p></div>[/vc_column_text][vc_column_text][algq_deal_marketplace][/vc_column_text][/vc_column][/vc_row]'
		);
		update_option( 'algq_dm_marketplace_page_id', $marketplace_id, false );

		$buyer_dashboard = get_page_by_path( 'buyer-dashboard' );
		if ( $buyer_dashboard instanceof WP_Post ) {
			$dashboard_id = self::ensure_page( 'marketplace', __( 'Marketplace', 'algq-deal-marketplace' ), '[vc_row][vc_column][vc_column_text][algq_buyer_marketplace_dashboard][/vc_column_text][/vc_column][/vc_row]', (int) $buyer_dashboard->ID );
			$nda_id       = self::ensure_page( 'nda', __( 'Marketplace NDA', 'algq-deal-marketplace' ), '[vc_row][vc_column][vc_column_text][algq_buyer_nda_gate][/vc_column_text][/vc_column][/vc_row]', (int) $buyer_dashboard->ID );
			$offer_id     = self::ensure_page( 'submit-offer', __( 'Submit Marketplace Offer', 'algq-deal-marketplace' ), '[vc_row][vc_column][vc_column_text][algq_buyer_offer_form][/vc_column_text][/vc_column][/vc_row]', (int) $buyer_dashboard->ID );
		} else {
			$dashboard_id = self::ensure_page( 'buyer-marketplace', __( 'Buyer Marketplace', 'algq-deal-marketplace' ), '[vc_row][vc_column][vc_column_text][algq_buyer_marketplace_dashboard][/vc_column_text][/vc_column][/vc_row]' );
			$nda_id       = self::ensure_page( 'buyer-marketplace-nda', __( 'Buyer Marketplace NDA', 'algq-deal-marketplace' ), '[vc_row][vc_column][vc_column_text][algq_buyer_nda_gate][/vc_column_text][/vc_column][/vc_row]' );
			$offer_id     = self::ensure_page( 'buyer-marketplace-submit-offer', __( 'Submit Marketplace Offer', 'algq-deal-marketplace' ), '[vc_row][vc_column][vc_column_text][algq_buyer_offer_form][/vc_column_text][/vc_column][/vc_row]' );
		}
		update_option( 'algq_dm_dashboard_page_id', $dashboard_id, false );
		update_option( 'algq_dm_nda_page_id', $nda_id, false );
		update_option( 'algq_dm_offer_page_id', $offer_id, false );

		$plugin_parent = get_page_by_path( 'plugin' );
		$plugin_parent_id = $plugin_parent instanceof WP_Post ? (int) $plugin_parent->ID : self::ensure_page( 'plugin', __( 'Plugin Library', 'algq-deal-marketplace' ), '' );
		$plugin_id = self::ensure_page( 'deal-marketplace', __( 'Algonquian Deal Marketplace', 'algq-deal-marketplace' ), '[vc_row][vc_column][vc_column_text][algq_deal_marketplace_plugin_card][/vc_column_text][/vc_column][/vc_row]', $plugin_parent_id );
		self::ensure_page( 'start', __( 'Getting Started With Deal Marketplace', 'algq-deal-marketplace' ), '[vc_row][vc_column][vc_column_text][algq_deal_marketplace_plugin_card][/vc_column_text][/vc_column][/vc_row]', $plugin_id );
		self::ensure_page( 'docs', __( 'Deal Marketplace Documentation', 'algq-deal-marketplace' ), '[vc_row][vc_column][vc_column_text][algq_deal_marketplace_plugin_card][/vc_column_text][/vc_column][/vc_row]', $plugin_id );
	}

	private static function ensure_page( string $slug, string $title, string $content, int $parent_id = 0 ): int {
		$existing = get_page_by_path( $slug, OBJECT, 'page' );
		if ( $parent_id > 0 ) {
			$children = get_pages(
				array(
					'parent'      => $parent_id,
					'post_status' => array( 'publish', 'draft', 'private' ),
				)
			);
			foreach ( $children as $child ) {
				if ( $slug === $child->post_name ) {
					return (int) $child->ID;
				}
			}
		} elseif ( $existing instanceof WP_Post && 0 === (int) $existing->post_parent ) {
			return (int) $existing->ID;
		}

		$page_id = wp_insert_post(
			array(
				'post_title'   => $title,
				'post_name'    => $slug,
				'post_content' => $content,
				'post_status'  => 'publish',
				'post_type'    => 'page',
				'post_parent'  => $parent_id,
			),
			true
		);

		return is_wp_error( $page_id ) ? 0 : (int) $page_id;
	}
}
