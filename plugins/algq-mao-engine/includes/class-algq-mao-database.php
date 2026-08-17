<?php
/** Versioned persistence for underwriting scenarios. */
defined( 'ABSPATH' ) || exit;

final class ALGQ_MAO_Database {
	const SCHEMA_OPTION = 'algq_mao_schema_version';

	public static function table() { global $wpdb; return $wpdb->prefix . 'algq_underwriting'; }

	public static function install() {
		global $wpdb;
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		$table = self::table(); $charset = $wpdb->get_charset_collate();
		$sql = "CREATE TABLE {$table} (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			uuid CHAR(36) NULL DEFAULT NULL,
			deal_id BIGINT UNSIGNED DEFAULT 0,
			scenario_name VARCHAR(190) DEFAULT '',
			strategy VARCHAR(40) DEFAULT 'wholesale',
			status VARCHAR(30) DEFAULT 'draft',
			formula_version VARCHAR(30) NOT NULL DEFAULT '1.0.0',
			assumption_version VARCHAR(64) NOT NULL DEFAULT 'legacy',
			input_snapshot LONGTEXT NULL,
			result_snapshot LONGTEXT NULL,
			arv DECIMAL(16,2) DEFAULT 0, repairs DECIMAL(16,2) DEFAULT 0, purchase_costs DECIMAL(16,2) DEFAULT 0,
			holding_costs DECIMAL(16,2) DEFAULT 0, financing_costs DECIMAL(16,2) DEFAULT 0, selling_costs DECIMAL(16,2) DEFAULT 0,
			desired_profit DECIMAL(16,2) DEFAULT 0, assignment_fee DECIMAL(16,2) DEFAULT 0,
			annual_gross_income DECIMAL(16,2) DEFAULT 0, annual_operating_expenses DECIMAL(16,2) DEFAULT 0, target_cap_rate DECIMAL(8,5) DEFAULT 0,
			mao DECIMAL(16,2) DEFAULT 0, estimated_spread DECIMAL(16,2) DEFAULT 0, projected_profit DECIMAL(16,2) DEFAULT 0,
			noi DECIMAL(16,2) DEFAULT 0, cap_rate DECIMAL(8,5) DEFAULT 0,
			risk_flag VARCHAR(40) DEFAULT 'Review', risk_reasons LONGTEXT,
			created_by BIGINT UNSIGNED DEFAULT 0, approved_by BIGINT UNSIGNED DEFAULT 0,
			created_at DATETIME NOT NULL, updated_at DATETIME NULL, approved_at DATETIME NULL,
			PRIMARY KEY (id), UNIQUE KEY uuid (uuid), KEY deal_id (deal_id), KEY status (status), KEY risk_flag (risk_flag), KEY created_at (created_at)
		) {$charset};";
		dbDelta( $sql );
		$ids = $wpdb->get_col( "SELECT id FROM {$table} WHERE uuid IS NULL OR uuid = ''" ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		foreach ( $ids as $id ) {
			$wpdb->update( $table, array( 'uuid' => wp_generate_uuid4(), 'formula_version' => '1.0.0', 'assumption_version' => 'legacy', 'updated_at' => current_time( 'mysql' ) ), array( 'id' => absint( $id ) ), array( '%s', '%s', '%s', '%s' ), array( '%d' ) );
		}
		update_option( self::SCHEMA_OPTION, ALGQ_MAO_ENGINE_SCHEMA_VERSION, false );
	}

	public static function insert( $deal_id, $name, $result, $user_id ) {
		global $wpdb; $i = $result['inputs']; $a = $result['assumptions']; $now = current_time( 'mysql' );
		$data = array(
			'uuid' => wp_generate_uuid4(), 'deal_id' => absint( $deal_id ), 'scenario_name' => sanitize_text_field( $name ),
			'strategy' => sanitize_key( $result['strategy'] ), 'status' => 'draft', 'formula_version' => ALGQ_MAO_Calculator::FORMULA_VERSION,
			'assumption_version' => sanitize_text_field( $a['assumption_version'] ?? 'unknown' ),
			'input_snapshot' => wp_json_encode( $i ), 'result_snapshot' => wp_json_encode( $result ),
			'arv' => $result['arv'], 'repairs' => $result['repairs'], 'purchase_costs' => $result['purchase_costs'],
			'holding_costs' => $result['holding_costs'], 'financing_costs' => $result['financing_costs'], 'selling_costs' => $result['selling_costs'],
			'desired_profit' => $result['desired_profit'], 'assignment_fee' => $result['assignment_fee'],
			'annual_gross_income' => $i['annual_gross_income'], 'annual_operating_expenses' => $i['annual_operating_expenses'],
			'target_cap_rate' => $i['target_cap_rate'] > 0 ? $i['target_cap_rate'] : $a['rental_target_cap_rate'],
			'mao' => $result['mao'], 'estimated_spread' => $result['estimated_spread'], 'projected_profit' => $result['projected_profit'],
			'noi' => $result['noi'], 'cap_rate' => $result['cap_rate'], 'risk_flag' => sanitize_text_field( $result['risk_flag'] ),
			'risk_reasons' => wp_json_encode( $result['risk_reasons'] ), 'created_by' => absint( $user_id ), 'approved_by' => 0,
			'created_at' => $now, 'updated_at' => $now,
		);
		$formats = array( '%s','%d','%s','%s','%s','%s','%s','%s','%s','%f','%f','%f','%f','%f','%f','%f','%f','%f','%f','%f','%f','%f','%f','%f','%f','%s','%s','%d','%d','%s','%s' );
		return $wpdb->insert( self::table(), $data, $formats ) ? (int) $wpdb->insert_id : false;
	}

	public static function approve( $id, $user_id ) {
		global $wpdb; return $wpdb->update( self::table(), array( 'status' => 'approved', 'approved_by' => absint( $user_id ), 'approved_at' => current_time( 'mysql' ), 'updated_at' => current_time( 'mysql' ) ), array( 'id' => absint( $id ), 'status' => 'draft' ), array( '%s','%d','%s','%s' ), array( '%d','%s' ) );
	}

	public static function get( $id ) { global $wpdb; return $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . self::table() . ' WHERE id = %d', absint( $id ) ) ); }
	public static function latest( $deal_id, $approved = false ) {
		global $wpdb;
		return $approved
			? $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . self::table() . ' WHERE deal_id = %d AND status = %s ORDER BY approved_at DESC, id DESC LIMIT 1', absint( $deal_id ), 'approved' ) )
			: $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . self::table() . ' WHERE deal_id = %d ORDER BY created_at DESC, id DESC LIMIT 1', absint( $deal_id ) ) );
	}
	public static function recent( $limit = 200 ) { global $wpdb; return $wpdb->get_results( $wpdb->prepare( 'SELECT * FROM ' . self::table() . ' ORDER BY created_at DESC, id DESC LIMIT %d', absint( $limit ) ) ); }
	public static function metrics() {
		global $wpdb; $t = self::table();
		return array(
			'total' => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$t}" ), // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			'approved' => (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$t} WHERE status=%s", 'approved' ) ),
			'high_risk' => (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$t} WHERE risk_flag=%s", 'High Risk' ) ),
			'average_mao' => (float) $wpdb->get_var( "SELECT AVG(mao) FROM {$t}" ), // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		);
	}
	public static function exists() { global $wpdb; return self::table() === $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', self::table() ) ); }
}
