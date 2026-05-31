<?php
defined( 'ABSPATH' ) || exit;

class ALGQ_Database {
	public static function activate() {
		self::create_tables();
		self::create_default_pages();
		update_option( 'algq_re_version', ALGQ_RE_VERSION );
		flush_rewrite_rules();
	}

	public static function table( $name ) {
		global $wpdb;
		return $wpdb->prefix . 'algq_' . sanitize_key( $name );
	}

	public static function create_tables() {
		global $wpdb;
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		$charset = $wpdb->get_charset_collate();
		$deals = self::table( 'deals' );
		$buyers = self::table( 'buyers' );
		$offers = self::table( 'offers' );
		$activity = self::table( 'activity_log' );

		dbDelta( "CREATE TABLE {$deals} (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			deal_id VARCHAR(40) NOT NULL,
			seller_name VARCHAR(191) NOT NULL DEFAULT '',
			seller_email VARCHAR(191) NOT NULL DEFAULT '',
			seller_phone VARCHAR(60) NOT NULL DEFAULT '',
			property_address TEXT NOT NULL,
			asking_price DECIMAL(14,2) NOT NULL DEFAULT 0,
			mortgage_balance DECIMAL(14,2) NOT NULL DEFAULT 0,
			monthly_payment DECIMAL(14,2) NOT NULL DEFAULT 0,
			property_condition VARCHAR(80) NOT NULL DEFAULT '',
			condition_notes LONGTEXT NULL,
			status VARCHAR(80) NOT NULL DEFAULT 'Lead Captured',
			created_at DATETIME NOT NULL,
			updated_at DATETIME NOT NULL,
			PRIMARY KEY (id),
			UNIQUE KEY deal_id (deal_id),
			KEY status (status)
		) {$charset};" );

		dbDelta( "CREATE TABLE {$buyers} (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			buyer_name VARCHAR(191) NOT NULL DEFAULT '',
			buyer_email VARCHAR(191) NOT NULL DEFAULT '',
			buyer_phone VARCHAR(60) NOT NULL DEFAULT '',
			markets TEXT NULL,
			property_types TEXT NULL,
			cash_available DECIMAL(14,2) NOT NULL DEFAULT 0,
			nda_accepted TINYINT(1) NOT NULL DEFAULT 0,
			status VARCHAR(80) NOT NULL DEFAULT 'Pending Review',
			created_at DATETIME NOT NULL,
			updated_at DATETIME NOT NULL,
			PRIMARY KEY (id),
			KEY buyer_email (buyer_email),
			KEY status (status)
		) {$charset};" );

		dbDelta( "CREATE TABLE {$offers} (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			deal_id VARCHAR(40) NOT NULL DEFAULT '',
			strategy VARCHAR(80) NOT NULL DEFAULT 'Seller Financing',
			purchase_price DECIMAL(14,2) NOT NULL DEFAULT 0,
			down_payment DECIMAL(14,2) NOT NULL DEFAULT 0,
			interest_rate DECIMAL(6,3) NOT NULL DEFAULT 0,
			term_years INT UNSIGNED NOT NULL DEFAULT 0,
			monthly_payment DECIMAL(14,2) NOT NULL DEFAULT 0,
			seller_total DECIMAL(14,2) NOT NULL DEFAULT 0,
			created_at DATETIME NOT NULL,
			PRIMARY KEY (id),
			KEY deal_id (deal_id)
		) {$charset};" );

		dbDelta( "CREATE TABLE {$activity} (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			object_type VARCHAR(80) NOT NULL DEFAULT '',
			object_id VARCHAR(80) NOT NULL DEFAULT '',
			action VARCHAR(120) NOT NULL DEFAULT '',
			note LONGTEXT NULL,
			user_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
			created_at DATETIME NOT NULL,
			PRIMARY KEY (id),
			KEY object_lookup (object_type, object_id)
		) {$charset};" );
	}

	public static function create_default_pages() {
		$pages = array(
			'sell-your-property' => array( 'Sell Your Property', '[vc_column_text][algq_seller_intake][/vc_column_text]' ),
			'mao-calculator' => array( 'MAO Calculator', '[vc_column_text][algq_mao_calculator][/vc_column_text]' ),
			'buyers-register' => array( 'Buyer Registration', '[vc_column_text][algq_buyer_registration][/vc_column_text]' ),
			'plugin/offer-generator' => array( 'Offer Generator', '[vc_column_text][algq_offer_generator][/vc_column_text]' ),
			'dashboard' => array( 'Algonquian Dashboard', '[vc_column_text][algq_admin_dashboard][/vc_column_text]' ),
		);
		foreach ( $pages as $slug => $data ) {
			if ( get_page_by_path( $slug ) ) { continue; }
			wp_insert_post( array( 'post_title' => $data[0], 'post_name' => basename( $slug ), 'post_content' => $data[1], 'post_status' => 'publish', 'post_type' => 'page' ) );
		}
	}
}
