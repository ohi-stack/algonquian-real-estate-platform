<?php
/** REST API for authorized calculations and scenario reads. */
defined( 'ABSPATH' ) || exit;

final class ALGQ_MAO_REST {
	private $calculator;
	public function __construct( $calculator ) { $this->calculator = $calculator; add_action( 'rest_api_init', array( $this, 'register' ) ); }
	public function register() {
		register_rest_route( 'algq/v1', '/mao/calculate', array( 'methods' => WP_REST_Server::CREATABLE, 'callback' => array( $this, 'calculate' ), 'permission_callback' => array( $this, 'can_view' ), 'args' => $this->args() ) );
		register_rest_route( 'algq/v1', '/mao/scenarios/(?P<id>\d+)', array( 'methods' => WP_REST_Server::READABLE, 'callback' => array( $this, 'scenario' ), 'permission_callback' => array( $this, 'can_view' ) ) );
	}
	public function can_view() {
		if ( class_exists( 'ALGQ_Platform_Capabilities' ) ) {
			return ALGQ_Platform_Capabilities::can_access_system( 'underwriting' );
		}
		return is_user_logged_in() && current_user_can( 'view_algq_underwriting' );
	}
	public function calculate( $request ) {
		return rest_ensure_response( $this->calculator->calculate( $request->get_params() ) );
	}
	public function scenario( $request ) {
		$record = ALGQ_MAO_Database::get( absint( $request['id'] ) );
		return $record ? rest_ensure_response( ALGQ_MAO_Engine::record_array( $record ) ) : new WP_Error( 'algq_mao_not_found', __( 'Underwriting scenario not found.', 'algq-mao-engine' ), array( 'status' => 404 ) );
	}
	private function args() {
		$amount = array( 'type' => 'number', 'required' => false, 'sanitize_callback' => array( $this->calculator, 'amount' ), 'validate_callback' => function ( $v ) { return is_numeric( $v ) && (float) $v >= 0; } );
		$args = array( 'strategy' => array( 'type' => 'string', 'enum' => array( 'wholesale','flip','rental','multifamily' ), 'default' => 'wholesale' ) );
		foreach ( array( 'arv','repairs','purchase_costs','holding_costs','financing_costs','selling_costs','desired_profit','assignment_fee','annual_gross_income','other_annual_income','annual_operating_expenses','annual_debt_service' ) as $key ) { $args[ $key ] = $amount; }
		$args['target_cap_rate'] = array( 'type' => 'number', 'required' => false, 'sanitize_callback' => array( $this->calculator, 'public_rate' ), 'validate_callback' => function ( $v ) { return is_numeric( $v ) && (float) $v >= 0 && (float) $v <= 1; } );
		return $args;
	}
}
