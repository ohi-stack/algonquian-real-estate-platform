<?php
/**
 * Buyer offers.
 *
 * @package Algonquian_Deal_Marketplace
 */

defined( 'ABSPATH' ) || exit;

final class ALGQ_DM_Offers {
	private const STATUSES = array( 'submitted', 'under_review', 'accepted', 'rejected', 'withdrawn', 'expired' );

	public static function init(): void {
		add_action( 'admin_post_algq_dm_submit_offer', array( __CLASS__, 'handle_submit' ) );
		add_action( 'admin_post_algq_dm_update_offer_status', array( __CLASS__, 'handle_status_update' ) );
	}

	/** @return array<int,object> */
	public static function for_user( int $user_id, int $limit = 20 ): array {
		global $wpdb;
		return $wpdb->get_results(
			$wpdb->prepare(
				'SELECT * FROM ' . ALGQ_DM_Support::table( 'offers' ) . ' WHERE user_id = %d ORDER BY created_at DESC LIMIT %d',
				$user_id,
				$limit
			)
		);
	}

	public static function create( int $user_id, int $deal_id, float $amount, float $earnest_money, string $financing_type, string $terms ): int|WP_Error {
		if ( ! ALGQ_DM_Access::can_view_deal( $deal_id, $user_id ) ) {
			return new WP_Error( 'algq_dm_access_denied', __( 'You are not authorized to submit an offer on this deal.', 'algq-deal-marketplace' ) );
		}
		if ( 'yes' === get_option( 'algq_dm_nda_required', 'yes' ) && ! ALGQ_DM_NDA::accepted( $user_id, $deal_id ) ) {
			return new WP_Error( 'algq_dm_nda_required', __( 'The required NDA must be accepted before submitting an offer.', 'algq-deal-marketplace' ) );
		}
		if ( $amount <= 0 ) {
			return new WP_Error( 'algq_dm_invalid_amount', __( 'Enter a valid offer amount.', 'algq-deal-marketplace' ) );
		}
		$allowed_financing = array( 'cash', 'conventional', 'private', 'seller_financing', 'joint_venture', 'other' );
		if ( ! in_array( $financing_type, $allowed_financing, true ) ) {
			$financing_type = 'other';
		}

		global $wpdb;
		$now = current_time( 'mysql', true );
		$inserted = $wpdb->insert(
			ALGQ_DM_Support::table( 'offers' ),
			array(
				'offer_uuid'    => wp_generate_uuid4(),
				'user_id'       => $user_id,
				'deal_id'       => $deal_id,
				'offer_amount'  => $amount,
				'earnest_money' => max( 0, $earnest_money ),
				'financing_type'=> $financing_type,
				'terms'         => wp_kses_post( $terms ),
				'status'        => 'submitted',
				'created_at'    => $now,
				'updated_at'    => $now,
			),
			array( '%s', '%d', '%d', '%f', '%f', '%s', '%s', '%s', '%s', '%s' )
		);
		if ( false === $inserted ) {
			return new WP_Error( 'algq_dm_offer_storage_failed', __( 'The offer could not be stored.', 'algq-deal-marketplace' ) );
		}

		$offer_id = (int) $wpdb->insert_id;
		ALGQ_DM_Support::audit( 'offer_submitted', $deal_id, array( 'financing_type' => $financing_type ), $offer_id );
		do_action( 'algq_dm_offer_submitted', $offer_id, $deal_id, $user_id );

		$admin_email = get_option( 'admin_email' );
		ALGQ_DM_Support::send_mail(
			$admin_email,
			sprintf( __( 'Marketplace offer submitted: %s', 'algq-deal-marketplace' ), get_the_title( $deal_id ) ),
			sprintf( __( 'Offer #%1$d was submitted for deal #%2$d. Review it in the Deal Marketplace administration area.', 'algq-deal-marketplace' ), $offer_id, $deal_id ),
			array( 'event' => 'buyer_offer_submitted', 'deal_id' => $deal_id )
		);

		return $offer_id;
	}

	public static function handle_submit(): never {
		if ( ! is_user_logged_in() || ! current_user_can( 'submit_algq_marketplace_offer' ) ) {
			ALGQ_DM_Support::abort( __( 'Buyer access is required.', 'algq-deal-marketplace' ), 403 );
		}
		$deal_id = isset( $_POST['deal_id'] ) ? absint( $_POST['deal_id'] ) : 0;
		check_admin_referer( 'algq_dm_submit_offer_' . $deal_id );
		$return_url = isset( $_POST['return_url'] ) ? esc_url_raw( wp_unslash( $_POST['return_url'] ) ) : home_url( '/marketplace/' );
		$rate_key = 'algq_dm_offer_rate_' . get_current_user_id() . '_' . $deal_id;
		if ( get_transient( $rate_key ) ) {
			ALGQ_DM_Support::redirect_with_status( $return_url, 'offer_rate_limited', $deal_id );
		}

		$amount = isset( $_POST['offer_amount'] ) ? (float) wp_unslash( $_POST['offer_amount'] ) : 0;
		$earnest = isset( $_POST['earnest_money'] ) ? (float) wp_unslash( $_POST['earnest_money'] ) : 0;
		$financing = isset( $_POST['financing_type'] ) ? sanitize_key( wp_unslash( $_POST['financing_type'] ) ) : 'cash';
		$terms = isset( $_POST['terms'] ) ? wp_kses_post( wp_unslash( $_POST['terms'] ) ) : '';
		if ( strlen( $terms ) > 10000 ) {
			ALGQ_DM_Support::redirect_with_status( $return_url, 'offer_terms_too_long', $deal_id );
		}

		$result = self::create( get_current_user_id(), $deal_id, $amount, $earnest, $financing, $terms );
		if ( is_wp_error( $result ) ) {
			ALGQ_DM_Support::redirect_with_status( $return_url, $result->get_error_code(), $deal_id );
		}
		set_transient( $rate_key, '1', MINUTE_IN_SECONDS );
		ALGQ_DM_Support::redirect_with_status( $return_url, 'offer_submitted', $deal_id );
	}

	public static function handle_status_update(): never {
		if ( ! current_user_can( 'review_algq_marketplace_offers' ) ) {
			ALGQ_DM_Support::abort( __( 'You are not authorized to review marketplace offers.', 'algq-deal-marketplace' ), 403 );
		}
		$offer_id = isset( $_POST['offer_id'] ) ? absint( $_POST['offer_id'] ) : 0;
		check_admin_referer( 'algq_dm_update_offer_' . $offer_id );
		$status = isset( $_POST['status'] ) ? sanitize_key( wp_unslash( $_POST['status'] ) ) : '';
		if ( ! in_array( $status, self::STATUSES, true ) ) {
			ALGQ_DM_Support::abort( __( 'Invalid offer status.', 'algq-deal-marketplace' ), 400 );
		}
		global $wpdb;
		$offer = $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . ALGQ_DM_Support::table( 'offers' ) . ' WHERE id = %d', $offer_id ) );
		if ( ! $offer ) {
			ALGQ_DM_Support::abort( __( 'Offer not found.', 'algq-deal-marketplace' ), 404 );
		}
		$wpdb->update(
			ALGQ_DM_Support::table( 'offers' ),
			array( 'status' => $status, 'updated_at' => current_time( 'mysql', true ) ),
			array( 'id' => $offer_id ),
			array( '%s', '%s' ),
			array( '%d' )
		);
		ALGQ_DM_Support::audit( 'offer_status_changed', (int) $offer->deal_id, array( 'status' => $status ), $offer_id );
		do_action( 'algq_dm_offer_status_changed', $offer_id, $status, (int) $offer->deal_id );
		ALGQ_DM_Support::redirect_with_status( admin_url( 'admin.php?page=algq-dm-offers' ), 'offer_status_updated' );
	}
}
