<?php
/** Controlled cross-plugin contracts. */
defined( 'ABSPATH' ) || exit;

final class ALGQ_MAO_Platform_Bridge {
	private $engine;
	public function __construct( $engine ) {
		$this->engine = $engine;
		add_action( 'algq_deal_intake_created', array( $this, 'auto' ), 10, 2 ); add_action( 'algq_pipeline_deal_created', array( $this, 'auto' ), 10, 2 );
		add_action( 'algq_mao_underwriting_saved_v2', array( $this, 'saved' ), 10, 3 ); add_action( 'algq_mao_underwriting_approved', array( $this, 'approved' ), 10, 2 );
		add_filter( 'algq_pipeline_deal_payload', array( $this, 'pipeline_payload' ), 10, 2 ); add_filter( 'algq_offer_generator_deal_payload', array( $this, 'offer_payload' ), 10, 2 ); add_filter( 'algq_command_center_metrics', array( $this, 'metrics' ) );
	}
	public function auto( $deal_id, $p ) {
		$p = is_array( $p ) ? $p : array(); $deal_id = absint( $deal_id ); if ( ! $deal_id || empty( $p['arv'] ) || $this->engine->latest( $deal_id ) ) { return; }
		$r = $this->engine->calculate( array( 'strategy' => $p['strategy'] ?? 'wholesale', 'arv' => $p['arv'], 'repairs' => $p['repairs'] ?? 0, 'purchase_costs' => $p['purchase_costs'] ?? 0, 'holding_costs' => $p['holding_costs'] ?? 0, 'financing_costs' => $p['financing_costs'] ?? 0, 'desired_profit' => $p['desired_profit'] ?? 0, 'assignment_fee' => $p['assignment_fee'] ?? 0, 'annual_gross_income' => $p['annual_gross_income'] ?? 0, 'annual_operating_expenses' => $p['annual_operating_expenses'] ?? 0, 'annual_debt_service' => $p['annual_debt_service'] ?? 0 ) );
		$this->engine->save_system( $deal_id, $r, 'Automated intake scenario' );
	}
	public function saved( $id, $deal_id, $result ) { if ( ! $deal_id || '1' !== $this->engine->assumptions()['auto_request_stage_change'] ) { return; } do_action( 'algq_pipeline_stage_change_requested', absint( $deal_id ), 'High Risk' === ( $result['risk_flag'] ?? '' ) ? 'Underwriting Review' : 'Underwriting', array( 'source' => 'algq-mao-engine', 'underwriting_id' => absint( $id ), 'reason' => 'underwriting_saved' ) ); }
	public function approved( $id, $r ) { if ( ! $r || empty( $r->deal_id ) ) { return; } $p = ALGQ_MAO_Engine::record_array( $r ); do_action( 'algq_mao_offer_ready', absint( $r->deal_id ), absint( $id ), $p ); do_action( 'algq_automation_event', 'mao.underwriting_approved', $p ); do_action( 'algq_pipeline_stage_change_requested', absint( $r->deal_id ), 'Offer Ready', array( 'source' => 'algq-mao-engine', 'underwriting_id' => absint( $id ), 'reason' => 'underwriting_approved' ) ); }
	public function pipeline_payload( $p, $deal_id ) { $p = is_array( $p ) ? $p : array(); if ( $r = $this->engine->latest( $deal_id ) ) { $p['underwriting'] = ALGQ_MAO_Engine::record_array( $r ); } return $p; }
	public function offer_payload( $p, $deal_id ) { $p = is_array( $p ) ? $p : array(); if ( $r = $this->engine->latest( $deal_id, true ) ) { $p['mao_engine'] = ALGQ_MAO_Engine::record_array( $r ); $p['offer_range'] = array( 'low' => round( max( 0, $r->mao * .95 ), 2 ), 'target' => round( max( 0, $r->mao ), 2 ), 'high' => round( max( 0, $r->mao * 1.05 ), 2 ), 'currency' => 'USD' ); } return $p; }
	public function metrics( $m ) { $m = is_array( $m ) ? $m : array(); $m['mao_engine'] = $this->engine->health(); return $m; }
}
