<?php
/**
 * Platform integration bridge for Algonquian MAO Engine.
 *
 * Connects Deal Intake, Pipeline CRM, Offer Generator, and Command Center.
 *
 * @package Algonquian_MAO_Engine
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * MAO platform bridge.
 */
final class ALGQ_MAO_Platform_Bridge {
	/**
	 * Underwriting table.
	 *
	 * @var string
	 */
	private $underwriting_table;

	/**
	 * Deals table.
	 *
	 * @var string
	 */
	private $deals_table;

	/**
	 * Constructor.
	 */
	public function __construct() {
		global $wpdb;

		$this->underwriting_table = $wpdb->prefix . 'algq_underwriting';
		$this->deals_table        = $wpdb->prefix . 'algq_deals';

		add_action( 'algq_deal_intake_created', array( $this, 'handle_deal_intake_created' ), 10, 2 );
		add_action( 'algq_mao_underwriting_saved', array( $this, 'handle_underwriting_saved' ), 10, 2 );

		add_filter( 'algq_pipeline_deal_stage_payload', array( $this, 'add_underwriting_to_pipeline_payload' ), 10, 2 );
		add_filter( 'algq_offer_generator_deal_payload', array( $this, 'add_underwriting_to_offer_payload' ), 10, 2 );
		add_filter( 'algq_command_center_metrics', array( $this, 'add_command_center_metrics' ), 10, 1 );
	}

	/**
	 * Handle deal created by Deal Intake.
	 *
	 * Expected action signature:
	 * do_action( 'algq_deal_intake_created', $deal_id, $deal_payload );
	 *
	 * @param int   $deal_id Deal ID.
	 * @param array $deal_payload Deal payload.
	 * @return void
	 */
	public function handle_deal_intake_created( $deal_id, $deal_payload ) {
		$deal_id      = absint( $deal_id );
		$deal_payload = is_array( $deal_payload ) ? $deal_payload : array();

		if ( ! $deal_id ) {
			return;
		}

		if ( empty( $deal_payload['arv'] ) || empty( $deal_payload['repairs'] ) ) {
			$this->move_pipeline_stage( $deal_id, 'Underwriting Needed', 'missing_underwriting_inputs' );
			return;
		}

		$calculator = $this->get_calculator();

		if ( ! $calculator || ! method_exists( $calculator, 'calculate' ) ) {
			$this->move_pipeline_stage( $deal_id, 'Underwriting Needed', 'calculator_unavailable' );
			return;
		}

		$result = $calculator->calculate(
			array(
				'arv'            => $deal_payload['arv'],
				'repairs'        => $deal_payload['repairs'],
				'holding_costs'  => $deal_payload['holding_costs'] ?? null,
				'desired_profit' => $deal_payload['desired_profit'] ?? null,
				'assignment_fee' => $deal_payload['assignment_fee'] ?? null,
				'strategy'       => $deal_payload['strategy'] ?? 'wholesale',
			)
		);

		$underwriting_id = $this->save_underwriting_snapshot( $deal_id, $result );

		if ( $underwriting_id ) {
			$this->move_pipeline_stage( $deal_id, 'Underwriting', 'auto_underwritten' );

			do_action( 'algq_mao_underwriting_saved', $underwriting_id, $result );
		}
	}

	/**
	 * Handle underwriting saved.
	 *
	 * @param int   $underwriting_id Underwriting ID.
	 * @param array $result Calculation result.
	 * @return void
	 */
	public function handle_underwriting_saved( $underwriting_id, $result ) {
		$underwriting_id = absint( $underwriting_id );
		$result          = is_array( $result ) ? $result : array();

		if ( ! $underwriting_id ) {
			return;
		}

		$underwriting = $this->get_underwriting_by_id( $underwriting_id );

		if ( ! $underwriting || empty( $underwriting->deal_id ) ) {
			return;
		}

		$deal_id = absint( $underwriting->deal_id );

		if ( 'High Risk' === (string) $underwriting->risk_flag ) {
			$this->move_pipeline_stage( $deal_id, 'Underwriting Review', 'high_risk_underwriting' );
		} else {
			$this->move_pipeline_stage( $deal_id, 'Offer Ready', 'mao_complete' );
		}

		do_action(
			'algq_mao_offer_ready',
			$deal_id,
			$underwriting_id,
			$this->format_offer_ready_payload( $underwriting )
		);

		do_action(
			'algq_command_center_event',
			'mao_engine',
			'underwriting_saved',
			array(
				'deal_id'          => $deal_id,
				'underwriting_id'  => $underwriting_id,
				'mao'              => (float) $underwriting->mao,
				'estimated_spread' => (float) $underwriting->estimated_spread,
				'risk_flag'        => sanitize_text_field( $underwriting->risk_flag ),
			)
		);
	}

	/**
	 * Add underwriting values to Pipeline CRM payload.
	 *
	 * @param array $payload Existing payload.
	 * @param int   $deal_id Deal ID.
	 * @return array
	 */
	public function add_underwriting_to_pipeline_payload( $payload, $deal_id ) {
		$payload      = is_array( $payload ) ? $payload : array();
		$underwriting = $this->get_latest_underwriting( absint( $deal_id ) );

		if ( ! $underwriting ) {
			return $payload;
		}

		$payload['underwriting'] = array(
			'id'               => absint( $underwriting->id ),
			'mao'              => (float) $underwriting->mao,
			'estimated_spread' => (float) $underwriting->estimated_spread,
			'risk_flag'        => sanitize_text_field( $underwriting->risk_flag ),
			'strategy'         => sanitize_key( $underwriting->strategy ),
			'created_at'       => sanitize_text_field( $underwriting->created_at ),
		);

		return $payload;
	}

	/**
	 * Add underwriting values to Offer Generator payload.
	 *
	 * @param array $payload Existing payload.
	 * @param int   $deal_id Deal ID.
	 * @return array
	 */
	public function add_underwriting_to_offer_payload( $payload, $deal_id ) {
		$payload      = is_array( $payload ) ? $payload : array();
		$underwriting = $this->get_latest_underwriting( absint( $deal_id ) );

		if ( ! $underwriting ) {
			return $payload;
		}

		$payload['mao_engine']  = $this->format_offer_ready_payload( $underwriting );
		$payload['offer_range'] = array(
			'low'      => round( max( 0, (float) $underwriting->mao * 0.95 ), 2 ),
			'target'   => round( max( 0, (float) $underwriting->mao ), 2 ),
			'high'     => round( max( 0, (float) $underwriting->mao * 1.05 ), 2 ),
			'currency' => 'USD',
		);

		return $payload;
	}

	/**
	 * Add MAO metrics to Command Center.
	 *
	 * @param array $metrics Existing metrics.
	 * @return array
	 */
	public function add_command_center_metrics( $metrics ) {
		global $wpdb;

		$metrics = is_array( $metrics ) ? $metrics : array();

		$total = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$this->underwriting_table}" );
		$high  = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$this->underwriting_table} WHERE risk_flag = %s", 'High Risk' ) );
		$avg   = (float) $wpdb->get_var( "SELECT AVG(mao) FROM {$this->underwriting_table}" );
		$queue = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$this->deals_table} WHERE status IN (%s, %s)", 'Underwriting Needed', 'Underwriting Review' ) );

		$metrics['mao_engine'] = array(
			'underwritten_deals'  => $total,
			'average_mao'         => round( $avg, 2 ),
			'high_risk_deals'     => $high,
			'underwriting_queue'  => $queue,
			'last_updated'        => current_time( 'mysql' ),
		);

		return $metrics;
	}

	/**
	 * Save underwriting snapshot.
	 *
	 * @param int   $deal_id Deal ID.
	 * @param array $result Calculation result.
	 * @return int|false
	 */
	private function save_underwriting_snapshot( $deal_id, $result ) {
		global $wpdb;

		$result = is_array( $result ) ? $result : array();

		$inserted = $wpdb->insert(
			$this->underwriting_table,
			array(
				'deal_id'          => absint( $deal_id ),
				'strategy'         => sanitize_key( $result['strategy'] ?? 'wholesale' ),
				'arv'              => (float) ( $result['arv'] ?? 0 ),
				'repairs'          => (float) ( $result['repairs'] ?? 0 ),
				'holding_costs'    => (float) ( $result['holding_costs'] ?? 0 ),
				'closing_costs'    => (float) ( $result['closing_costs'] ?? 0 ),
				'desired_profit'   => (float) ( $result['desired_profit'] ?? 0 ),
				'assignment_fee'   => (float) ( $result['assignment_fee'] ?? 0 ),
				'mao'              => (float) ( $result['mao'] ?? 0 ),
				'estimated_spread' => (float) ( $result['estimated_spread'] ?? 0 ),
				'risk_flag'        => sanitize_text_field( $result['risk_flag'] ?? 'Review' ),
				'formula_snapshot' => wp_json_encode( $result['assumptions'] ?? array() ),
				'created_by'       => get_current_user_id(),
				'created_at'       => current_time( 'mysql' ),
			),
			array( '%d', '%s', '%f', '%f', '%f', '%f', '%f', '%f', '%f', '%f', '%s', '%s', '%d', '%s' )
		);

		return $inserted ? (int) $wpdb->insert_id : false;
	}

	/**
	 * Move Pipeline CRM stage.
	 *
	 * Uses action contract so Pipeline CRM can implement its own persistence.
	 * Also updates local deals table as fallback.
	 *
	 * @param int    $deal_id Deal ID.
	 * @param string $stage New stage.
	 * @param string $reason Reason code.
	 * @return void
	 */
	private function move_pipeline_stage( $deal_id, $stage, $reason ) {
		global $wpdb;

		$deal_id = absint( $deal_id );
		$stage   = sanitize_text_field( $stage );
		$reason  = sanitize_key( $reason );

		if ( ! $deal_id || '' === $stage ) {
			return;
		}

		$wpdb->update(
			$this->deals_table,
			array(
				'status'     => $stage,
				'updated_at' => current_time( 'mysql' ),
			),
			array( 'id' => $deal_id ),
			array( '%s', '%s' ),
			array( '%d' )
		);

		do_action(
			'algq_pipeline_stage_change_requested',
			$deal_id,
			$stage,
			array(
				'source' => 'mao_engine',
				'reason' => $reason,
			)
		);
	}

	/**
	 * Get calculator instance.
	 *
	 * @return ALGQ_MAO_Engine|null
	 */
	private function get_calculator() {
		return class_exists( 'ALGQ_MAO_Engine' ) ? ALGQ_MAO_Engine::instance() : null;
	}

	/**
	 * Get underwriting by ID.
	 *
	 * @param int $underwriting_id Underwriting ID.
	 * @return object|null
	 */
	private function get_underwriting_by_id( $underwriting_id ) {
		global $wpdb;

		return $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$this->underwriting_table} WHERE id = %d",
				absint( $underwriting_id )
			)
		);
	}

	/**
	 * Get latest underwriting record.
	 *
	 * @param int $deal_id Deal ID.
	 * @return object|null
	 */
	private function get_latest_underwriting( $deal_id ) {
		global $wpdb;

		if ( ! $deal_id ) {
			return null;
		}

		return $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$this->underwriting_table} WHERE deal_id = %d ORDER BY created_at DESC, id DESC LIMIT 1",
				absint( $deal_id )
			)
		);
	}

	/**
	 * Format offer-ready payload.
	 *
	 * @param object $underwriting Underwriting row.
	 * @return array
	 */
	private function format_offer_ready_payload( $underwriting ) {
		return array(
			'underwriting_id'  => absint( $underwriting->id ),
			'deal_id'          => absint( $underwriting->deal_id ),
			'strategy'         => sanitize_key( $underwriting->strategy ),
			'arv'              => (float) $underwriting->arv,
			'repairs'          => (float) $underwriting->repairs,
			'holding_costs'    => (float) $underwriting->holding_costs,
			'closing_costs'    => (float) $underwriting->closing_costs,
			'desired_profit'   => (float) $underwriting->desired_profit,
			'assignment_fee'   => (float) $underwriting->assignment_fee,
			'mao'              => (float) $underwriting->mao,
			'estimated_spread' => (float) $underwriting->estimated_spread,
			'risk_flag'        => sanitize_text_field( $underwriting->risk_flag ),
			'created_at'       => sanitize_text_field( $underwriting->created_at ),
		);
	}
}
