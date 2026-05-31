<?php
defined( 'ABSPATH' ) || exit;

class ALGQ_Offer_Engine {
	public static function payment( $principal, $annual_rate, $years ) {
		$principal = max( 0, (float) $principal );
		$annual_rate = max( 0, (float) $annual_rate );
		$years = max( 1, absint( $years ) );
		$months = $years * 12;
		if ( 0.0 === $annual_rate ) { return $principal / $months; }
		$monthly_rate = ( $annual_rate / 100 ) / 12;
		return $principal * ( $monthly_rate * pow( 1 + $monthly_rate, $months ) ) / ( pow( 1 + $monthly_rate, $months ) - 1 );
	}

	public static function scenario( $price, $down, $rate, $years ) {
		$price = max( 0, (float) $price );
		$down = max( 0, (float) $down );
		$financed = max( 0, $price - $down );
		$payment = self::payment( $financed, $rate, $years );
		return array(
			'purchase_price' => $price,
			'down_payment'   => $down,
			'financed'       => $financed,
			'interest_rate'  => (float) $rate,
			'term_years'     => absint( $years ),
			'monthly_payment'=> round( $payment, 2 ),
			'seller_total'   => round( $payment * absint( $years ) * 12, 2 ),
			'total_interest' => round( ( $payment * absint( $years ) * 12 ) - $financed, 2 ),
		);
	}
}
