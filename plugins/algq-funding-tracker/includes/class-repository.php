<?php
/**
 * Database access layer.
 *
 * @package Algonquian_Funding_Tracker
 */

defined( 'ABSPATH' ) || exit;

final class ALGQ_Funding_Tracker_Repository {
	/** @var wpdb */
	private $wpdb;
	private $sources_table;
	private $funding_table;
	private $activity_table;

	public function __construct() {
		global $wpdb;
		$this->wpdb           = $wpdb;
		$this->sources_table  = $wpdb->prefix . 'algq_capital_sources';
		$this->funding_table  = $wpdb->prefix . 'algq_funding_commitments';
		$this->activity_table = $wpdb->prefix . 'algq_funding_activity';
	}

	/**
	 * Create a capital source.
	 *
	 * @param array $data Sanitized source data.
	 * @return int|WP_Error
	 */
	public function create_source( array $data ) {
		$now = current_time( 'mysql', true );

		$record = array(
			'uuid'                     => wp_generate_uuid4(),
			'name'                     => sanitize_text_field( $data['name'] ?? '' ),
			'organization'             => sanitize_text_field( $data['organization'] ?? '' ),
			'source_type'              => $this->allowed_value( $data['source_type'] ?? '', self::source_types(), 'private_lender' ),
			'status'                   => $this->allowed_value( $data['status'] ?? '', self::source_statuses(), 'prospect' ),
			'email'                    => sanitize_email( $data['email'] ?? '' ),
			'phone'                    => sanitize_text_field( $data['phone'] ?? '' ),
			'preferred_markets'        => sanitize_textarea_field( $data['preferred_markets'] ?? '' ),
			'preferred_property_types' => sanitize_textarea_field( $data['preferred_property_types'] ?? '' ),
			'minimum_amount'           => $this->decimal( $data['minimum_amount'] ?? 0 ),
			'maximum_amount'           => $this->decimal( $data['maximum_amount'] ?? 0 ),
			'interest_rate'            => $this->nullable_decimal( $data['interest_rate'] ?? null ),
			'term_months'              => $this->nullable_int( $data['term_months'] ?? null ),
			'notes'                    => sanitize_textarea_field( $data['notes'] ?? '' ),
			'created_by'               => get_current_user_id(),
			'updated_by'               => get_current_user_id(),
			'created_at'               => $now,
			'updated_at'               => $now,
		);

		if ( '' === $record['name'] ) {
			return new WP_Error( 'algq_funding_missing_name', __( 'Capital source name is required.', 'algq-funding-tracker' ) );
		}

		$result = $this->wpdb->insert( $this->sources_table, $record );

		if ( false === $result ) {
			return new WP_Error( 'algq_funding_source_insert_failed', __( 'The capital source could not be saved.', 'algq-funding-tracker' ) );
		}

		$source_id = (int) $this->wpdb->insert_id;
		$this->log_activity( 'capital_source.created', 0, $source_id, 0, $record );
		return $source_id;
	}

	/**
	 * Create a deal-level funding commitment/request.
	 *
	 * @param array $data Sanitized funding data.
	 * @return int|WP_Error
	 */
	public function create_commitment( array $data ) {
		$capital_source_id = absint( $data['capital_source_id'] ?? 0 );
		$deal_id           = absint( $data['deal_id'] ?? 0 );

		if ( $deal_id && function_exists( 'algq_get_deal' ) && ! algq_get_deal( $deal_id ) ) {
			return new WP_Error( 'algq_funding_invalid_deal', __( 'Select a valid Pipeline CRM deal.', 'algq-funding-tracker' ) );
		}

		if ( ! $capital_source_id || ! $this->get_source( $capital_source_id ) ) {
			return new WP_Error( 'algq_funding_invalid_source', __( 'Select a valid capital source.', 'algq-funding-tracker' ) );
		}

		$now = current_time( 'mysql', true );
		$record = array(
			'uuid'              => wp_generate_uuid4(),
			'deal_id'           => $deal_id,
			'capital_source_id' => $capital_source_id,
			'funding_type'      => $this->allowed_value( $data['funding_type'] ?? '', self::funding_types(), 'debt' ),
			'status'            => $this->allowed_value( $data['status'] ?? '', self::commitment_statuses(), 'requested' ),
			'requested_amount'  => $this->decimal( $data['requested_amount'] ?? 0 ),
			'committed_amount'  => $this->decimal( $data['committed_amount'] ?? 0 ),
			'funded_amount'     => $this->decimal( $data['funded_amount'] ?? 0 ),
			'interest_rate'     => $this->nullable_decimal( $data['interest_rate'] ?? null ),
			'points'            => $this->nullable_decimal( $data['points'] ?? null ),
			'term_months'       => $this->nullable_int( $data['term_months'] ?? null ),
			'maturity_date'     => $this->nullable_date( $data['maturity_date'] ?? null ),
			'commitment_date'   => $this->nullable_date( $data['commitment_date'] ?? null ),
			'funded_date'       => $this->nullable_date( $data['funded_date'] ?? null ),
			'conditions'        => sanitize_textarea_field( $data['conditions'] ?? '' ),
			'notes'             => sanitize_textarea_field( $data['notes'] ?? '' ),
			'created_by'        => get_current_user_id(),
			'updated_by'        => get_current_user_id(),
			'created_at'        => $now,
			'updated_at'        => $now,
		);

		$result = $this->wpdb->insert( $this->funding_table, $record );
		if ( false === $result ) {
			return new WP_Error( 'algq_funding_commitment_insert_failed', __( 'The funding record could not be saved.', 'algq-funding-tracker' ) );
		}

		$commitment_id = (int) $this->wpdb->insert_id;
		$this->log_activity( 'funding_commitment.created', $commitment_id, $capital_source_id, $record['deal_id'], $record );
		return $commitment_id;
	}

	/**
	 * Update the operational status and amounts for a funding record.
	 *
	 * @param int   $commitment_id Funding record ID.
	 * @param array $data Submitted update values.
	 * @return true|WP_Error
	 */
	public function update_commitment( $commitment_id, array $data ) {
		$commitment_id = absint( $commitment_id );
		$existing = $this->wpdb->get_row(
			$this->wpdb->prepare( "SELECT * FROM {$this->funding_table} WHERE id = %d AND deleted_at IS NULL", $commitment_id ),
			ARRAY_A
		);

		if ( ! $existing ) {
			return new WP_Error( 'algq_funding_record_not_found', __( 'Funding record not found.', 'algq-funding-tracker' ) );
		}

		$record = array(
			'status'           => $this->allowed_value( $data['status'] ?? '', self::commitment_statuses(), $existing['status'] ),
			'committed_amount' => $this->decimal( $data['committed_amount'] ?? $existing['committed_amount'] ),
			'funded_amount'    => $this->decimal( $data['funded_amount'] ?? $existing['funded_amount'] ),
			'commitment_date'  => $this->nullable_date( $data['commitment_date'] ?? $existing['commitment_date'] ),
			'funded_date'      => $this->nullable_date( $data['funded_date'] ?? $existing['funded_date'] ),
			'updated_by'       => get_current_user_id(),
			'updated_at'       => current_time( 'mysql', true ),
		);

		$result = $this->wpdb->update( $this->funding_table, $record, array( 'id' => $commitment_id ) );
		if ( false === $result ) {
			return new WP_Error( 'algq_funding_update_failed', __( 'The funding record could not be updated.', 'algq-funding-tracker' ) );
		}

		$this->log_activity(
			'funding_commitment.updated',
			$commitment_id,
			(int) $existing['capital_source_id'],
			(int) $existing['deal_id'],
			array(
				'previous_status'  => $existing['status'],
				'new_status'       => $record['status'],
				'committed_amount' => $record['committed_amount'],
				'funded_amount'    => $record['funded_amount'],
			)
		);

		return true;
	}

	/**
	 * Retrieve capital sources.
	 */
	public function get_sources( $limit = 100 ) {
		$limit = min( 500, max( 1, absint( $limit ) ) );
		$sql = $this->wpdb->prepare(
			"SELECT * FROM {$this->sources_table} WHERE deleted_at IS NULL ORDER BY updated_at DESC LIMIT %d",
			$limit
		);
		return $this->wpdb->get_results( $sql, ARRAY_A );
	}

	/**
	 * Retrieve one capital source.
	 */
	public function get_source( $source_id ) {
		$sql = $this->wpdb->prepare(
			"SELECT * FROM {$this->sources_table} WHERE id = %d AND deleted_at IS NULL",
			absint( $source_id )
		);
		return $this->wpdb->get_row( $sql, ARRAY_A );
	}

	/**
	 * Retrieve funding commitments with source names.
	 */
	public function get_commitments( $limit = 100 ) {
		$limit = min( 500, max( 1, absint( $limit ) ) );
		$sql = $this->wpdb->prepare(
			"SELECT c.*, s.name AS source_name, s.organization AS source_organization
			 FROM {$this->funding_table} c
			 INNER JOIN {$this->sources_table} s ON s.id = c.capital_source_id
			 WHERE c.deleted_at IS NULL
			 ORDER BY c.updated_at DESC LIMIT %d",
			$limit
		);
		return $this->wpdb->get_results( $sql, ARRAY_A );
	}

	/**
	 * Funding KPI summary.
	 */
	public function get_summary() {
		$row = $this->wpdb->get_row(
			"SELECT
				COUNT(*) AS record_count,
				COALESCE(SUM(requested_amount), 0) AS requested_total,
				COALESCE(SUM(committed_amount), 0) AS committed_total,
				COALESCE(SUM(funded_amount), 0) AS funded_total
			 FROM {$this->funding_table}
			 WHERE deleted_at IS NULL",
			ARRAY_A
		);

		$source_count = (int) $this->wpdb->get_var(
			"SELECT COUNT(*) FROM {$this->sources_table} WHERE deleted_at IS NULL"
		);

		$row['source_count'] = $source_count;
		$row['record_count'] = (int) $row['record_count'];
		return $row;
	}

	/**
	 * Write an append-only activity record and forward an event to the platform.
	 */
	public function log_activity( $event_name, $commitment_id, $source_id, $deal_id, array $event_data = array() ) {
		$this->wpdb->insert(
			$this->activity_table,
			array(
				'commitment_id'     => absint( $commitment_id ),
				'capital_source_id' => absint( $source_id ),
				'deal_id'           => absint( $deal_id ),
				'event_name'        => preg_replace( '/[^a-z0-9._-]/', '', strtolower( (string) $event_name ) ),
				'event_data'        => wp_json_encode( $this->redact_event_data( $event_data ) ),
				'user_id'           => get_current_user_id(),
				'created_at'        => current_time( 'mysql', true ),
			)
		);

		do_action(
			'algq_audit_event',
			array(
				'plugin'     => 'algq-funding-tracker',
				'event'      => preg_replace( '/[^a-z0-9._-]/', '', strtolower( (string) $event_name ) ),
				'related_id' => absint( $commitment_id ?: $source_id ),
				'deal_id'    => absint( $deal_id ),
				'user_id'    => get_current_user_id(),
			)
		);
	}

	public static function source_types() {
		return array( 'bank', 'credit_union', 'cdfi', 'private_lender', 'equity_partner', 'joint_venture', 'seller_financing', 'grant', 'internal', 'other' );
	}

	public static function source_statuses() {
		return array( 'prospect', 'contacted', 'application', 'due_diligence', 'active', 'inactive', 'declined' );
	}

	public static function funding_types() {
		return array( 'debt', 'equity', 'joint_venture', 'seller_financing', 'grant', 'earnest_money', 'due_diligence', 'other' );
	}

	public static function commitment_statuses() {
		return array( 'proposed', 'requested', 'under_review', 'conditionally_approved', 'committed', 'partially_funded', 'funded', 'declined', 'cancelled', 'repaid', 'closed' );
	}

	private function allowed_value( $value, array $allowed, $default ) {
		$value = sanitize_key( $value );
		return in_array( $value, $allowed, true ) ? $value : $default;
	}

	private function decimal( $value ) {
		return number_format( max( 0, (float) $value ), 2, '.', '' );
	}

	private function nullable_decimal( $value ) {
		return ( '' === $value || null === $value ) ? null : number_format( max( 0, (float) $value ), 4, '.', '' );
	}

	private function nullable_int( $value ) {
		return ( '' === $value || null === $value ) ? null : absint( $value );
	}

	private function nullable_date( $value ) {
		$value = sanitize_text_field( (string) $value );
		if ( '' === $value ) {
			return null;
		}
		$date = DateTime::createFromFormat( 'Y-m-d', $value );
		return ( $date && $date->format( 'Y-m-d' ) === $value ) ? $value : null;
	}

	private function redact_event_data( array $data ) {
		unset( $data['notes'], $data['conditions'], $data['email'], $data['phone'] );
		return $data;
	}
}
