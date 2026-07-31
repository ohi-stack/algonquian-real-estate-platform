<?php
/** Strategy-specific MAO calculation service. */
defined( 'ABSPATH' ) || exit;

final class ALGQ_MAO_Calculator {
	const OPTION_KEY      = 'algq_mao_assumptions';
	const FORMULA_VERSION = '2.0.0';

	public static function defaults() {
		return array(
			'assumption_version'        => '2026.07',
			'wholesale_arv_multiplier'  => '0.70',
			'wholesale_closing_rate'    => '0.03',
			'flip_selling_cost_rate'    => '0.08',
			'rental_vacancy_rate'       => '0.05',
			'rental_reserve_rate'       => '0.05',
			'rental_target_cap_rate'    => '0.08',
			'repair_risk_threshold'     => '0.35',
			'minimum_profit_margin'     => '0.10',
			'default_holding_costs'     => '0',
			'default_financing_costs'   => '0',
			'default_desired_profit'    => '25000',
			'default_assignment_fee'    => '10000',
			'auto_request_stage_change' => '1',
		);
	}

	public function assumptions() {
		return wp_parse_args( get_option( self::OPTION_KEY, array() ), self::defaults() );
	}

	public function sanitize_assumptions( $input ) {
		$input = is_array( $input ) ? $input : array();
		return array(
			'assumption_version'        => sanitize_text_field( $input['assumption_version'] ?? '2026.07' ),
			'wholesale_arv_multiplier'  => (string) $this->rate( $input['wholesale_arv_multiplier'] ?? .70, .10, 1 ),
			'wholesale_closing_rate'    => (string) $this->rate( $input['wholesale_closing_rate'] ?? .03, 0, .25 ),
			'flip_selling_cost_rate'    => (string) $this->rate( $input['flip_selling_cost_rate'] ?? .08, 0, .30 ),
			'rental_vacancy_rate'       => (string) $this->rate( $input['rental_vacancy_rate'] ?? .05, 0, .50 ),
			'rental_reserve_rate'       => (string) $this->rate( $input['rental_reserve_rate'] ?? .05, 0, .50 ),
			'rental_target_cap_rate'    => (string) $this->rate( $input['rental_target_cap_rate'] ?? .08, .01, .50 ),
			'repair_risk_threshold'     => (string) $this->rate( $input['repair_risk_threshold'] ?? .35, .05, 1 ),
			'minimum_profit_margin'     => (string) $this->rate( $input['minimum_profit_margin'] ?? .10, 0, 1 ),
			'default_holding_costs'     => (string) $this->amount( $input['default_holding_costs'] ?? 0 ),
			'default_financing_costs'   => (string) $this->amount( $input['default_financing_costs'] ?? 0 ),
			'default_desired_profit'    => (string) $this->amount( $input['default_desired_profit'] ?? 25000 ),
			'default_assignment_fee'    => (string) $this->amount( $input['default_assignment_fee'] ?? 10000 ),
			'auto_request_stage_change' => empty( $input['auto_request_stage_change'] ) ? '0' : '1',
		);
	}

	public function calculate( $raw ) {
		$i = $this->normalize( $raw );
		$a = $this->assumptions();
		$r = $this->strategy( $i, $a );
		$risk = $this->risk( $i, $r, $a );
		$r['risk_flag']       = $risk['flag'];
		$r['risk_reasons']    = $risk['reasons'];
		$r['formula_version'] = self::FORMULA_VERSION;
		$r['assumptions']     = $a;
		$r['inputs']          = $i;
		$r['sensitivity']     = $this->sensitivity( $i, $a );
		return $r;
	}

	private function normalize( $raw ) {
		$raw = is_array( $raw ) ? $raw : array();
		$strategy = sanitize_key( $raw['strategy'] ?? 'wholesale' );
		if ( ! in_array( $strategy, array( 'wholesale', 'flip', 'rental', 'multifamily' ), true ) ) {
			$strategy = 'wholesale';
		}
		$out = array( 'strategy' => $strategy );
		foreach ( array( 'arv', 'repairs', 'purchase_costs', 'holding_costs', 'financing_costs', 'selling_costs', 'desired_profit', 'assignment_fee', 'annual_gross_income', 'other_annual_income', 'annual_operating_expenses', 'annual_debt_service' ) as $key ) {
			$out[ $key ] = $this->amount( $raw[ $key ] ?? 0 );
		}
		$out['target_cap_rate'] = $this->rate( $raw['target_cap_rate'] ?? 0, 0, 1 );
		return $out;
	}

	private function strategy( $i, $a ) {
		$arv = (float) $i['arv'];
		$repairs = (float) $i['repairs'];
		$purchase = (float) $i['purchase_costs'];
		$holding = $i['holding_costs'] > 0 ? (float) $i['holding_costs'] : (float) $a['default_holding_costs'];
		$financing = $i['financing_costs'] > 0 ? (float) $i['financing_costs'] : (float) $a['default_financing_costs'];
		$profit = $i['desired_profit'] > 0 ? (float) $i['desired_profit'] : (float) $a['default_desired_profit'];
		$assignment = $i['assignment_fee'] > 0 ? (float) $i['assignment_fee'] : (float) $a['default_assignment_fee'];
		$selling = (float) $i['selling_costs'];
		$closing = $noi = $cap = $income_value = 0.0;

		if ( 'flip' === $i['strategy'] ) {
			$selling = $selling > 0 ? $selling : $arv * (float) $a['flip_selling_cost_rate'];
			$mao = $arv - $repairs - $purchase - $holding - $financing - $selling - $profit;
			$formula = 'ARV - repairs - purchase - holding - financing - selling - desired profit';
		} elseif ( in_array( $i['strategy'], array( 'rental', 'multifamily' ), true ) ) {
			$effective = ( (float) $i['annual_gross_income'] * ( 1 - (float) $a['rental_vacancy_rate'] ) ) + (float) $i['other_annual_income'];
			$noi = $effective - (float) $i['annual_operating_expenses'] - ( $effective * (float) $a['rental_reserve_rate'] );
			$target = $i['target_cap_rate'] > 0 ? (float) $i['target_cap_rate'] : (float) $a['rental_target_cap_rate'];
			$income_value = $target > 0 ? max( 0, $noi / $target ) : 0;
			$ceiling = $arv > 0 && $income_value > 0 ? min( $arv, $income_value ) : max( $arv, $income_value );
			$mao = $ceiling - $repairs - $purchase - $holding - $financing;
			$cap = $mao > 0 ? max( 0, $noi / $mao ) : 0;
			$profit = $assignment = 0.0;
			$formula = 'Available value ceiling - repairs - purchase - holding - financing; income value = NOI / target cap rate';
		} else {
			$closing = $arv * (float) $a['wholesale_closing_rate'];
			$mao = ( $arv * (float) $a['wholesale_arv_multiplier'] ) - $repairs - $purchase - $holding - $financing - $closing - $profit - $assignment;
			$formula = '(ARV × multiplier) - repairs - purchase - holding - financing - closing - desired profit - assignment fee';
		}

		$mao = round( $mao, 2 );
		$total_costs = $repairs + $purchase + $holding + $financing + $selling + $closing;
		$projected = max( 0, $arv - max( 0, $mao ) - $total_costs );
		$dscr = $i['annual_debt_service'] > 0 ? max( 0, $noi / (float) $i['annual_debt_service'] ) : 0;
		return array(
			'strategy' => $i['strategy'], 'arv' => round( $arv, 2 ), 'repairs' => round( $repairs, 2 ),
			'purchase_costs' => round( $purchase, 2 ), 'holding_costs' => round( $holding, 2 ), 'financing_costs' => round( $financing, 2 ),
			'selling_costs' => round( $selling, 2 ), 'closing_costs' => round( $closing, 2 ), 'desired_profit' => round( $profit, 2 ),
			'assignment_fee' => round( $assignment, 2 ), 'noi' => round( $noi, 2 ), 'income_value' => round( $income_value, 2 ),
			'cap_rate' => round( $cap, 5 ), 'dscr' => round( $dscr, 3 ), 'mao' => $mao,
			'estimated_spread' => round( max( 0, $arv - max( 0, $mao ) - $repairs ), 2 ),
			'projected_profit' => round( $projected, 2 ), 'profit_margin' => $arv > 0 ? round( $projected / $arv, 5 ) : 0,
			'formula' => $formula,
		);
	}

	private function risk( $i, $r, $a ) {
		$high = $review = array();
		$arv = (float) $i['arv'];
		if ( (float) $r['mao'] <= 0 ) { $high[] = 'non_positive_mao'; }
		if ( $arv > 0 && (float) $i['repairs'] / $arv > (float) $a['repair_risk_threshold'] ) { $high[] = 'repairs_exceed_threshold'; }
		if ( $arv <= 0 && ! in_array( $i['strategy'], array( 'rental', 'multifamily' ), true ) ) { $high[] = 'missing_arv'; }
		if ( in_array( $i['strategy'], array( 'rental', 'multifamily' ), true ) ) {
			if ( (float) $i['annual_gross_income'] <= 0 ) { $review[] = 'missing_rental_income'; }
			if ( (float) $r['noi'] <= 0 ) { $high[] = 'non_positive_noi'; }
			if ( (float) $i['annual_debt_service'] > 0 && (float) $r['dscr'] < 1.15 ) { $review[] = 'dscr_below_1_15'; }
		} elseif ( (float) $r['profit_margin'] < (float) $a['minimum_profit_margin'] ) { $review[] = 'profit_margin_below_threshold'; }
		if ( $high ) { return array( 'flag' => 'High Risk', 'reasons' => array_values( array_unique( array_merge( $high, $review ) ) ) ); }
		return $review ? array( 'flag' => 'Review', 'reasons' => array_values( array_unique( $review ) ) ) : array( 'flag' => 'Acceptable', 'reasons' => array() );
	}

	private function sensitivity( $i, $a ) {
		$out = array();
		foreach ( array( 'conservative' => array( .90, 1.10 ), 'base' => array( 1, 1 ), 'optimistic' => array( 1.10, .90 ) ) as $name => $f ) {
			$s = $i; $s['arv'] = round( $i['arv'] * $f[0], 2 ); $s['repairs'] = round( $i['repairs'] * $f[1], 2 );
			$r = $this->strategy( $s, $a );
			$out[ $name ] = array( 'arv' => $s['arv'], 'repairs' => $s['repairs'], 'mao' => $r['mao'], 'projected_profit' => $r['projected_profit'] );
		}
		return $out;
	}

	public function amount( $value ) { return round( max( 0, (float) $value ), 2 ); }
	public function public_rate( $value ) { return $this->rate( $value, 0, 1 ); }
	private function rate( $value, $min, $max ) { return round( max( $min, min( $max, (float) $value ) ), 5 ); }
}
