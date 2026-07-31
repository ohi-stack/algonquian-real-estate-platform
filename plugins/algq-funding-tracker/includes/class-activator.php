<?php
/**
 * Activation and schema management.
 *
 * @package Algonquian_Funding_Tracker
 */

defined( 'ABSPATH' ) || exit;

final class ALGQ_Funding_Tracker_Activator {
	const SCHEMA_VERSION = '1.0.0';

	/**
	 * Run controlled upgrades when the stored schema version is stale.
	 */
	public static function maybe_upgrade() {
		if ( version_compare( (string) get_option( 'algq_funding_tracker_schema_version', '0.0.0' ), self::SCHEMA_VERSION, '<' ) ) {
			self::activate();
		}
	}

	/**
	 * Install tables, capabilities, and generated pages.
	 */
	public static function activate() {
		self::create_tables();
		self::install_capabilities();
		self::create_pages();
		update_option( 'algq_funding_tracker_schema_version', self::SCHEMA_VERSION, false );
	}

	/**
	 * Create plugin-owned database tables.
	 */
	private static function create_tables() {
		global $wpdb;

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$charset_collate = $wpdb->get_charset_collate();
		$sources_table   = $wpdb->prefix . 'algq_capital_sources';
		$funding_table   = $wpdb->prefix . 'algq_funding_commitments';
		$activity_table  = $wpdb->prefix . 'algq_funding_activity';

		$sql_sources = "CREATE TABLE {$sources_table} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			uuid char(36) NOT NULL,
			name varchar(190) NOT NULL,
			organization varchar(190) NOT NULL DEFAULT '',
			source_type varchar(50) NOT NULL DEFAULT 'private_lender',
			status varchar(40) NOT NULL DEFAULT 'prospect',
			email varchar(190) NOT NULL DEFAULT '',
			phone varchar(50) NOT NULL DEFAULT '',
			preferred_markets text NULL,
			preferred_property_types text NULL,
			minimum_amount decimal(18,2) NOT NULL DEFAULT 0,
			maximum_amount decimal(18,2) NOT NULL DEFAULT 0,
			interest_rate decimal(8,4) NULL,
			term_months int(10) unsigned NULL,
			notes longtext NULL,
			created_by bigint(20) unsigned NOT NULL DEFAULT 0,
			updated_by bigint(20) unsigned NOT NULL DEFAULT 0,
			created_at datetime NOT NULL,
			updated_at datetime NOT NULL,
			deleted_at datetime NULL,
			PRIMARY KEY  (id),
			UNIQUE KEY uuid (uuid),
			KEY source_type (source_type),
			KEY status (status),
			KEY organization (organization)
		) {$charset_collate};";

		$sql_funding = "CREATE TABLE {$funding_table} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			uuid char(36) NOT NULL,
			deal_id bigint(20) unsigned NOT NULL DEFAULT 0,
			capital_source_id bigint(20) unsigned NOT NULL,
			funding_type varchar(50) NOT NULL DEFAULT 'debt',
			status varchar(40) NOT NULL DEFAULT 'requested',
			requested_amount decimal(18,2) NOT NULL DEFAULT 0,
			committed_amount decimal(18,2) NOT NULL DEFAULT 0,
			funded_amount decimal(18,2) NOT NULL DEFAULT 0,
			interest_rate decimal(8,4) NULL,
			points decimal(8,4) NULL,
			term_months int(10) unsigned NULL,
			maturity_date date NULL,
			commitment_date date NULL,
			funded_date date NULL,
			conditions longtext NULL,
			notes longtext NULL,
			created_by bigint(20) unsigned NOT NULL DEFAULT 0,
			updated_by bigint(20) unsigned NOT NULL DEFAULT 0,
			created_at datetime NOT NULL,
			updated_at datetime NOT NULL,
			deleted_at datetime NULL,
			PRIMARY KEY  (id),
			UNIQUE KEY uuid (uuid),
			KEY deal_id (deal_id),
			KEY capital_source_id (capital_source_id),
			KEY status (status),
			KEY funding_type (funding_type)
		) {$charset_collate};";

		$sql_activity = "CREATE TABLE {$activity_table} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			commitment_id bigint(20) unsigned NOT NULL DEFAULT 0,
			capital_source_id bigint(20) unsigned NOT NULL DEFAULT 0,
			deal_id bigint(20) unsigned NOT NULL DEFAULT 0,
			event_name varchar(100) NOT NULL,
			event_data longtext NULL,
			user_id bigint(20) unsigned NOT NULL DEFAULT 0,
			created_at datetime NOT NULL,
			PRIMARY KEY  (id),
			KEY commitment_id (commitment_id),
			KEY capital_source_id (capital_source_id),
			KEY deal_id (deal_id),
			KEY event_name (event_name),
			KEY created_at (created_at)
		) {$charset_collate};";

		dbDelta( $sql_sources );
		dbDelta( $sql_funding );
		dbDelta( $sql_activity );
	}

	/**
	 * Grant granular capabilities to administrators.
	 */
	private static function install_capabilities() {
		$role = get_role( 'administrator' );

		if ( ! $role ) {
			return;
		}

		foreach ( self::capabilities() as $capability ) {
			$role->add_cap( $capability );
		}
	}

	/**
	 * Capabilities owned by this plugin.
	 *
	 * @return string[]
	 */
	public static function capabilities() {
		return array(
			'manage_algq_funding',
			'view_algq_funding',
			'edit_algq_funding',
			'export_algq_funding',
		);
	}

	/**
	 * Create operational and documentation pages without overwriting content.
	 */
	private static function create_pages() {
		$pages = array(
			'funding-dashboard' => array(
				'title'   => 'Funding Dashboard',
				'content' => "[vc_column_text]\n[algq_funding_dashboard]\n[/vc_column_text]",
			),
			'plugin/funding-tracker' => array(
				'title'   => 'Algonquian Funding Tracker',
				'content' => "[vc_column_text]\n[algq_funding_tracker]\n[/vc_column_text]",
			),
			'plugin/funding-tracker/start' => array(
				'title'   => 'Getting Started With Algonquian Funding Tracker',
				'content' => "[vc_column_text]\n<h2>Getting Started</h2>\n<p>Add capital sources, record deal-level requests, update commitments, and track funded amounts through closing.</p>\n[/vc_column_text]",
			),
			'plugin/funding-tracker/docs' => array(
				'title'   => 'Algonquian Funding Tracker Documentation',
				'content' => "[vc_column_text]\n<h2>Documentation</h2>\n<p>The Funding Tracker records lender and investor relationships, terms, commitments, funding progress, and related activity. It does not transfer money or replace accounting records.</p>\n[/vc_column_text]",
			),
		);

		foreach ( $pages as $path => $page ) {
			self::create_page_path( $path, $page['title'], $page['content'] );
		}
	}

	/**
	 * Create a nested page path one segment at a time.
	 */
	private static function create_page_path( $path, $title, $content ) {
		$segments  = array_values( array_filter( explode( '/', trim( $path, '/' ) ) ) );
		$parent_id = 0;
		$full_path = '';

		foreach ( $segments as $index => $segment ) {
			$full_path = ltrim( $full_path . '/' . $segment, '/' );
			$existing  = get_page_by_path( $full_path, OBJECT, 'page' );

			if ( $existing ) {
				$parent_id = (int) $existing->ID;
				continue;
			}

			$is_final = ( $index === count( $segments ) - 1 );
			$page_id  = wp_insert_post(
				array(
					'post_title'   => $is_final ? $title : ucwords( str_replace( '-', ' ', $segment ) ),
					'post_name'    => sanitize_title( $segment ),
					'post_content' => $is_final ? $content : '',
					'post_status'  => 'publish',
					'post_type'    => 'page',
					'post_parent'  => $parent_id,
				),
				true
			);

			if ( ! is_wp_error( $page_id ) ) {
				$parent_id = (int) $page_id;
			}
		}
	}
}
