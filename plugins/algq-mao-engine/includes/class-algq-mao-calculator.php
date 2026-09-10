<?php
/** Strategy-specific MAO and financing calculation service. */
defined( 'ABSPATH' ) || exit;

final class ALGQ_MAO_Calculator {
	const OPTION_KEY      = 'algq_mao_assumptions';
	const FORMULA_VERSION = '2.1.0';

	public static function defaults() {
		return array(
			'assumption_version'                 => '2026.08',
			'wholesale_arv_multiplier'           => '0.70',
			'wholesale_closing_rate'             => '0.03',
			'flip_selling_cost_rate'             => '0.08',
			'rental_vacancy_rate'                => '0.05',
			'rental_reserve_rate'                => '0.05',
			'rental_target_cap_rate'             => '0.08',
			'repair_risk_threshold'              => '0.35',
			'minimum_profit_margin'              => '0.10',
			'default_holding_costs'              => '0',
			'default_financing_costs'            => '0',
			'default_desired_profit'             => '25000',
			'default_assignment_fee'             => '10000',
			'seller_default_interest_rate'       => '0.06',
			'seller_default_amortization_years'  => '30',
			'seller_default_balloon_years'       => '5',
			'seller_minimum_dscr'                => '1.15',
			'refinance_default_interest_rate'    => '0.07',
			'refinance_default_amortization'     => '30',
			'refinance_default_ltv'              => '0.75',
			'conventional_default_interest_rate' => '0.07',
			'conventional_default_amortization'  => '30',
			'conventional_default_down_rate'     => '0.25',
			'auto_request_stage_change'          => '1',
		);
	}

	public function assumptions() {
		return wp_parse_args( get_option( self::OPTION_KEY, array() ), self::defaults() );
	}

	public function sanitize_assumptions( $input ) {
		$input = is_array( $input ) ? $input : array();
		return array(
			'assumption_version'                 => sanitize_text_field( $input['assumption_version'] ?? '2026.08' ),
			'wholesale_arv_multiplier'           => (string) $this->rate( $input['wholesale_arv_multiplier'] ?? .70, .10, 1 ),
			'wholesale_closing_rate'             => (string) $this->rate( $input['wholesale_closing_rate'] ?? .03, 0, .25 ),
			'flip_selling_cost_rate'             => (string) $this->rate( $input['flip_selling_cost_rate'] ?? .08, 0, .30 ),
			'rental_vacancy_rate'                => (string) $this->rate( $input['rental_vacancy_rate'] ?? .05, 0, .50 ),
			'rental_reserve_rate'                => (string) $this->rate( $input['rental_reserve_rate'] ?? .05, 0, .50 ),
			'rental_target_cap_rate'             => (string) $this->rate( $input['rental_target_cap_rate'] ?? .08, .01, .50 ),
			'repair_risk_threshold'              => (string) $this->rate( $input['repair_risk_threshold'] ?? .35, .05, 1 ),
			'minimum_profit_margin'              => (string) $this->rate( $input['minimum_profit_margin'] ?? .10, 0, 1 ),
			'default_holding_costs'              => (string) $this->amount( $input['default_holding_costs'] ?? 0 ),
			'default_financing_costs'            => (string) $this->amount( $input['default_financing_costs'] ?? 0 ),
			'default_desired_profit'             => (string) $this->amount( $input['default_desired_profit'] ?? 25000 ),
			'default_assignment_fee'             => (string) $this->amount( $input['default_assignment_fee'] ?? 10000 ),
			'seller_default_interest_rate'       => (string) $this->percent_rate( $input['seller_default_interest_rate'] ?? .06 ),
			'seller_default_amortization_years'  => (string) $this->years( $input['seller_default_amortization_years'] ?? 30, 1, 50 ),
			'seller_default_balloon_years'       => (string) $this->years( $input['seller_default_balloon_years'] ?? 5, 0, 50 ),
			'seller_minimum_dscr'                => (string) round( max( 0, min( 10, (float) ( $input['seller_minimum_dscr'] ?? 1.15 ) ) ), 3 ),
			'refinance_default_interest_rate'    => (string) $this->percent_rate( $input['refinance_default_interest_rate'] ?? .07 ),
			'refinance_default_amortization'     => (string) $this->years( $input['refinance_default_amortization'] ?? 30, 1, 50 ),
			'refinance_default_ltv'              => (string) $this->rate( $input['refinance_default_ltv'] ?? .75, 0, 1 ),
			'conventional_default_interest_rate' => (string) $this->percent_rate( $input['conventional_default_interest_rate'] ?? .07 ),
			'conventional_default_amortization'  => (string) $this->years( $input['conventional_default_amortization'] ?? 30, 1, 50 ),
			'conventional_default_down_rate'     => (string) $this->rate( $input['conventional_default_down_rate'] ?? .25, 0, 1 ),
			'auto_request_stage_change'          => empty( $input['auto_request_stage_change'] ) ? '0' : '1',
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
		$allowed = array( 'wholesale', 'flip', 'rental', 'multifamily', 'seller_financing' );
		if ( ! in_array( $strategy, $allowed, true ) ) { $strategy = 'wholesale'; }
		$out = array( 'strategy' => $strategy );
		foreach ( array(
			'arv','repairs','purchase_costs','holding_costs','financing_costs','selling_costs','desired_profit','assignment_fee',
			'annual_gross_income','other_annual_income','annual_operating_expenses','annual_debt_service','purchase_price','down_payment',
			'seller_financed_principal','seller_monthly_payment','refinance_value','conventional_down_payment'
		) as $key ) {
			$out[ $key ] = $this->amount( $raw[ $key ] ?? 0 );
		}
		$out['target_cap_rate']                 = $this->rate( $raw['target_cap_rate'] ?? 0, 0, 1 );
		$out['seller_interest_rate']            = $this->percent_rate( $raw['seller_interest_rate'] ?? 0 );
		$out['seller_amortization_years']       = $this->years( $raw['seller_amortization_years'] ?? 0, 0, 50 );
		$out['seller_balloon_years']            = $this->years( $raw['seller_balloon_years'] ?? 0, 0, 50 );
		$out['refinance_interest_rate']         = $this->percent_rate( $raw['refinance_interest_rate'] ?? 0 );
		$out['refinance_amortization_years']    = $this->years( $raw['refinance_amortization_years'] ?? 0, 0, 50 );
		$out['refinance_ltv']                   = $this->rate( $raw['refinance_ltv'] ?? 0, 0, 1 );
		$out['conventional_interest_rate']      = $this->percent_rate( $raw['conventional_interest_rate'] ?? 0 );
		$out['conventional_amortization_years'] = $this->years( $raw['conventional_amortization_years'] ?? 0, 0, 50 );
		return $out;
	}

	private function strategy( $i, $a ) {
		if ( 'seller_financing' === $i['strategy'] ) {
			return $this->seller_financing( $i, $a );
		}

		$arv       = (float) $i['arv'];
		$repairs   = (float) $i['repairs'];
		$purchase  = (float) $i['purchase_costs'];
		$holding   = $i['holding_costs'] > 0 ? (float) $i['holding_costs'] : (float) $a['default_holding_costs'];
		$financing = $i['financing_costs'] > 0 ? (float) $i['financing_costs'] : (float) $a['default_financing_costs'];
		$profit    = $i['desired_profit'] > 0 ? (float) $i['desired_profit'] : (float) $a['default_desired_profit'];
		$assignment= $i['assignment_fee'] > 0 ? (float) $i['assignment_fee'] : (float) $a['default_assignment_fee'];
		$selling   = (float) $i['selling_costs'];
		$closing = $noi = $cap = $income_value = 0.0;

		if ( 'flip' === $i['strategy'] ) {
			$selling = $selling > 0 ? $selling : $arv * (float) $a['flip_selling_cost_rate'];
			$mao = $arv - $repairs - $purchase - $holding - $financing - $selling - $profit;
			$formula = 'ARV - repairs - purchase - holding - financing - selling - desired profit';
		} elseif ( in_array( $i['strategy'], array( 'rental', 'multifamily' ), true ) ) {
			$noi_data = $this->noi( $i, $a );
			$noi = $noi_data['noi'];
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

	private function seller_financing( $i, $a ) {
		$price = (float) $i['purchase_price'];
		$down  = min( $price, (float) $i['down_payment'] );
		$principal = (float) $i['seller_financed_principal'];
		if ( $principal <= 0 && $price > 0 ) { $principal = max( 0, $price - $down ); }

		$rate = $i['seller_interest_rate'] > 0 ? (float) $i['seller_interest_rate'] : (float) $a['seller_default_interest_rate'];
		$amort_years = $i['seller_amortization_years'] > 0 ? (int) $i['seller_amortization_years'] : (int) $a['seller_default_amortization_years'];
		$balloon_years = $i['seller_balloon_years'] > 0 ? (int) $i['seller_balloon_years'] : (int) $a['seller_default_balloon_years'];
		$term_months = max( 1, $amort_years * 12 );
		$payment = (float) $i['seller_monthly_payment'];
		if ( $payment <= 0 ) { $payment = $this->payment( $principal, $rate, $term_months ); }
		$balloon_months = $balloon_years > 0 ? min( $term_months, $balloon_years * 12 ) : $term_months;
		$balloon_balance = $this->balance_after( $principal, $rate, $term_months, $balloon_months, $payment );
		$annual_debt = $payment * 12;
		$total_debt_service = ( $payment * $balloon_months ) + $balloon_balance;

		$noi_data = $this->noi( $i, $a );
		$noi = $noi_data['noi'];
		$cash_flow = $noi - $annual_debt;
		$dscr = $annual_debt > 0 ? max( 0, $noi / $annual_debt ) : 0;

		$refi_value = $i['refinance_value'] > 0 ? (float) $i['refinance_value'] : max( (float) $i['arv'], $price );
		$refi_rate = $i['refinance_interest_rate'] > 0 ? (float) $i['refinance_interest_rate'] : (float) $a['refinance_default_interest_rate'];
		$refi_amort = $i['refinance_amortization_years'] > 0 ? (int) $i['refinance_amortization_years'] : (int) $a['refinance_default_amortization'];
		$refi_ltv = $i['refinance_ltv'] > 0 ? (float) $i['refinance_ltv'] : (float) $a['refinance_default_ltv'];
		$refi_capacity = max( 0, $refi_value * $refi_ltv );
		$refi_loan = min( $balloon_balance, $refi_capacity );
		$refi_gap = max( 0, $balloon_balance - $refi_capacity );
		$refi_payment = $this->payment( $refi_loan, $refi_rate, max( 1, $refi_amort * 12 ) );

		$conventional_down = (float) $i['conventional_down_payment'];
		if ( $conventional_down <= 0 && $price > 0 ) { $conventional_down = $price * (float) $a['conventional_default_down_rate']; }
		$conventional_principal = max( 0, $price - min( $price, $conventional_down ) );
		$conventional_rate = $i['conventional_interest_rate'] > 0 ? (float) $i['conventional_interest_rate'] : (float) $a['conventional_default_interest_rate'];
		$conventional_amort = $i['conventional_amortization_years'] > 0 ? (int) $i['conventional_amortization_years'] : (int) $a['conventional_default_amortization'];
		$conventional_payment = $this->payment( $conventional_principal, $conventional_rate, max( 1, $conventional_amort * 12 ) );
		$conventional_annual_debt = $conventional_payment * 12;
		$conventional_cash_flow = $noi - $conventional_annual_debt;
		$conventional_dscr = $conventional_annual_debt > 0 ? max( 0, $noi / $conventional_annual_debt ) : 0;

		$equity = max( 0, max( (float) $i['arv'], $price ) - $principal );
		return array(
			'strategy' => 'seller_financing',
			'arv' => round( (float) $i['arv'], 2 ),
			'repairs' => round( (float) $i['repairs'], 2 ),
			'purchase_costs' => round( (float) $i['purchase_costs'], 2 ),
			'holding_costs' => round( (float) $i['holding_costs'], 2 ),
			'financing_costs' => round( (float) $i['financing_costs'], 2 ),
			'selling_costs' => 0.0, 'closing_costs' => 0.0, 'desired_profit' => 0.0, 'assignment_fee' => 0.0,
			'purchase_price' => round( $price, 2 ), 'down_payment' => round( $down, 2 ),
			'seller_financed_principal' => round( $principal, 2 ), 'seller_interest_rate' => round( $rate, 5 ),
			'seller_amortization_years' => $amort_years, 'seller_balloon_years' => $balloon_years,
			'monthly_payment' => round( $payment, 2 ), 'annual_debt_service' => round( $annual_debt, 2 ),
			'balloon_balance' => round( $balloon_balance, 2 ), 'total_debt_service' => round( $total_debt_service, 2 ),
			'noi' => round( $noi, 2 ), 'cash_flow' => round( $cash_flow, 2 ), 'dscr' => round( $dscr, 3 ),
			'refinance_value' => round( $refi_value, 2 ), 'refinance_ltv' => round( $refi_ltv, 5 ), 'refinance_capacity' => round( $refi_capacity, 2 ),
			'refinance_loan_amount' => round( $refi_loan, 2 ), 'refinance_gap' => round( $refi_gap, 2 ), 'refinance_interest_rate' => round( $refi_rate, 5 ),
			'refinance_monthly_payment' => round( $refi_payment, 2 ),
			'conventional_down_payment' => round( $conventional_down, 2 ), 'conventional_principal' => round( $conventional_principal, 2 ),
			'conventional_interest_rate' => round( $conventional_rate, 5 ), 'conventional_monthly_payment' => round( $conventional_payment, 2 ),
			'conventional_annual_debt_service' => round( $conventional_annual_debt, 2 ), 'conventional_cash_flow' => round( $conventional_cash_flow, 2 ),
			'conventional_dscr' => round( $conventional_dscr, 3 ),
			'monthly_payment_savings' => round( $conventional_payment - $payment, 2 ),
			'upfront_cash_savings' => round( $conventional_down - $down, 2 ),
			'income_value' => 0.0, 'cap_rate' => 0.0, 'mao' => round( $price, 2 ),
			'estimated_spread' => round( $equity, 2 ), 'projected_profit' => round( max( 0, $cash_flow ), 2 ),
			'profit_margin' => $price > 0 ? round( max( 0, $cash_flow ) / $price, 5 ) : 0,
			'formula' => 'Seller-financing debt service from principal, rate and amortization; balloon balance at stated maturity; NOI/annual debt service for DSCR; refinance capacity from value × LTV; conventional financing comparison on identical purchase price.',
		);
	}

	private function risk( $i, $r, $a ) {
		$high = $review = array();
		$arv = (float) $i['arv'];
		if ( 'seller_financing' === $i['strategy'] ) {
			if ( (float) $r['purchase_price'] <= 0 ) { $high[] = 'missing_purchase_price'; }
			if ( (float) $r['seller_financed_principal'] <= 0 ) { $high[] = 'non_positive_seller_principal'; }
			if ( (float) $r['cash_flow'] < 0 ) { $review[] = 'negative_annual_cash_flow'; }
			if ( (float) $r['noi'] > 0 && (float) $r['dscr'] < (float) $a['seller_minimum_dscr'] ) { $review[] = 'seller_dscr_below_minimum'; }
			if ( (float) $r['refinance_gap'] > 0 ) { $review[] = 'balloon_refinance_shortfall'; }
			if ( (int) $r['seller_balloon_years'] > 0 && (int) $r['seller_balloon_years'] < 3 ) { $review[] = 'short_balloon_term'; }
			if ( (float) $r['monthly_payment_savings'] < 0 ) { $review[] = 'seller_payment_exceeds_conventional'; }
			if ( $high ) { return array( 'flag' => 'High Risk', 'reasons' => array_values( array_unique( array_merge( $high, $review ) ) ) ); }
			return $review ? array( 'flag' => 'Review', 'reasons' => array_values( array_unique( $review ) ) ) : array( 'flag' => 'Acceptable', 'reasons' => array() );
		}
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
		if ( 'seller_financing' === $i['strategy'] ) {
			foreach ( array( 'conservative' => array( .90, 1.01 ), 'base' => array( 1, 1 ), 'optimistic' => array( 1.10, .99 ) ) as $name => $f ) {
				$s = $i;
				$s['refinance_value'] = round( max( $i['refinance_value'], $i['arv'], $i['purchase_price'] ) * $f[0], 2 );
				$s['seller_interest_rate'] = max( 0, ( $i['seller_interest_rate'] > 0 ? $i['seller_interest_rate'] : (float) $a['seller_default_interest_rate'] ) * $f[1] );
				$r = $this->seller_financing( $s, $a );
				$out[ $name ] = array( 'refinance_value' => $s['refinance_value'], 'monthly_payment' => $r['monthly_payment'], 'balloon_balance' => $r['balloon_balance'], 'refinance_gap' => $r['refinance_gap'], 'dscr' => $r['dscr'], 'cash_flow' => $r['cash_flow'] );
			}
			return $out;
		}
		foreach ( array( 'conservative' => array( .90, 1.10 ), 'base' => array( 1, 1 ), 'optimistic' => array( 1.10, .90 ) ) as $name => $f ) {
			$s = $i; $s['arv'] = round( $i['arv'] * $f[0], 2 ); $s['repairs'] = round( $i['repairs'] * $f[1], 2 );
			$r = $this->strategy( $s, $a );
			$out[ $name ] = array( 'arv' => $s['arv'], 'repairs' => $s['repairs'], 'mao' => $r['mao'], 'projected_profit' => $r['projected_profit'] );
		}
		return $out;
	}

	private function noi( $i, $a ) {
		$effective = ( (float) $i['annual_gross_income'] * ( 1 - (float) $a['rental_vacancy_rate'] ) ) + (float) $i['other_annual_income'];
		$noi = $effective - (float) $i['annual_operating_expenses'] - ( $effective * (float) $a['rental_reserve_rate'] );
		return array( 'effective_income' => max( 0, $effective ), 'noi' => $noi );
	}

	private function payment( $principal, $annual_rate, $months ) {
		$principal = max( 0, (float) $principal ); $months = max( 1, (int) $months ); $monthly_rate = max( 0, (float) $annual_rate ) / 12;
		if ( $principal <= 0 ) { return 0.0; }
		if ( $monthly_rate <= 0 ) { return $principal / $months; }
		$factor = pow( 1 + $monthly_rate, $months );
		return $factor > 1 ? $principal * ( $monthly_rate * $factor ) / ( $factor - 1 ) : $principal / $months;
	}

	private function balance_after( $principal, $annual_rate, $term_months, $paid_months, $payment ) {
		$principal = max( 0, (float) $principal ); $paid_months = max( 0, min( (int) $term_months, (int) $paid_months ) );
		$monthly_rate = max( 0, (float) $annual_rate ) / 12;
		if ( $paid_months >= $term_months ) { return 0.0; }
		if ( $monthly_rate <= 0 ) { return max( 0, $principal - ( (float) $payment * $paid_months ) ); }
		$factor = pow( 1 + $monthly_rate, $paid_months );
		return max( 0, ( $principal * $factor ) - ( (float) $payment * ( ( $factor - 1 ) / $monthly_rate ) ) );
	}

	public function amount( $value ) { return round( max( 0, (float) $value ), 2 ); }
	public function public_rate( $value ) { return $this->rate( $value, 0, 1 ); }
	private function percent_rate( $value ) { $v = (float) $value; if ( $v > 1 ) { $v /= 100; } return $this->rate( $v, 0, 1 ); }
	private function years( $value, $min, $max ) { return max( $min, min( $max, (int) round( (float) $value ) ) ); }
	private function rate( $value, $min, $max ) { return round( max( $min, min( $max, (float) $value ) ), 5 ); }
}
