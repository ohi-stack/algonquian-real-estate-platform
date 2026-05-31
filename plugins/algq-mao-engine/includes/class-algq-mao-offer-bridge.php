<?php
/**
 * Offer Generator bridge for Algonquian MAO Engine.
 *
 * @package Algonquian_MAO_Engine
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Connects saved underwriting records to Offer Generator workflows.
 */
final class ALGQ_MAO_Offer_Bridge {
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

		add_filter( 'algq_offer_generator_deal_payload', array( $this, 'add_mao_payload_to_offer' ), 10, 2 );
		add_action( 'algq_mao_underwriting_saved', array( $this, 'maybe_mark_offer_ready' ), 10, 2 );
	}

	/**
	 * Add MAO underwriting values to an Offer Generator payload.
	 *
	 * @param array $payload Existing offer payload.
	 * @param int   $deal_id Deal ID.
	 * @return array
	 */
	public function add_mao_payload_to_offer( $payload, $deal_id ) {
		$payload = is_array( $payload ) ? $payload : array();
		$deal_id = absint( $deal_id );

		if ( ! $deal_id ) {
			return $payload;
		}

		$underwriting = $this->get_latest_underwriting( $deal_id );

		if ( ! $underwriting ) {
			return $payload;
		}

		$payload['mao_engine'] = array(
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

		$payload['offer_range'] = array(
			'low'      => max( 0, (float) $underwriting->mao * 0.95 ),
			'target'   => max( 0, (float) $underwriting->mao ),
			'high'     => max( 0, (float) $underwriting->mao * 1.05 ),
			'currency' => 'USD',
		);

		return $payload;
	}

	/**
	 * Mark deal as offer-ready after underwriting is saved.
	 *
	 * @param int   $underwriting_id Underwriting ID.
	 * @param array $result Calculation result.
	 * @return void
	 */
	public function maybe_mark_offer_ready( $underwriting_id, $result ) {
		global $wpdb;

		$underwriting_id = absint( $underwriting_id );

		if ( ! $underwriting_id ) {
			return;
		}

		$underwriting = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$this->underwriting_table} WHERE id = %d",
				$underwriting_id
			)
		);

		if ( ! $underwriting || empty( $underwriting->deal_id ) ) {
			return;
		}

		$deal_id = absint( $underwriting->deal_id );

		/**
		 * Allow the Offer Generator to receive immediate notice that a deal is ready.
		 *
		 * Implementing plugin may hook:
		 * add_action( 'algq_mao_offer_ready', 'handler', 10, 3 );
		 */
		do_action(
			'algq_mao_offer_ready',
			$deal_id,
			$underwriting_id,
			array(
				'mao'              => (float) $underwriting->mao,
				'estimated_spread' => (float) $underwriting->estimated_spread,
				'risk_flag'        => sanitize_text_field( $underwriting->risk_flag ),
				'strategy'         => sanitize_key( $underwriting->strategy ),
			)
		);
	}

	/**
	 * Get latest underwriting record for a deal.
	 *
	 * @param int $deal_id Deal ID.
	 * @return object|null
	 */
	private function get_latest_underwriting( $deal_id ) {
		global $wpdb;

		return $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$this->underwriting_table} WHERE deal_id = %d ORDER BY created_at DESC, id DESC LIMIT 1",
				absint( $deal_id )
			)
		);
	}
}
