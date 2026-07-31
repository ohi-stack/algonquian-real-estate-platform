<?php
/**
 * Versioned NDA acceptance evidence.
 *
 * @package Algonquian_Deal_Marketplace
 */

defined( 'ABSPATH' ) || exit;

final class ALGQ_DM_NDA {
	public static function init(): void {
		add_action( 'admin_post_algq_dm_accept_nda', array( __CLASS__, 'handle_acceptance' ) );
	}

	public static function required_version( int $deal_id ): string {
		return sanitize_text_field(
			(string) ALGQ_DM_Marketplace::meta(
				$deal_id,
				'nda_version',
				(string) get_option( 'algq_dm_default_nda_version', '2026.1' )
			)
		);
	}

	public static function accepted( int $user_id, int $deal_id ): bool {
		if ( user_can( $user_id, 'manage_algq_marketplace' ) ) {
			return true;
		}
		global $wpdb;
		$version = self::required_version( $deal_id );
		$table = ALGQ_DM_Support::table( 'nda_acceptances' );
		$count = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$table} WHERE user_id = %d AND nda_version = %s AND revoked_at IS NULL AND (deal_id = %d OR deal_id = 0)",
				$user_id,
				$version,
				$deal_id
			)
		);
		return (int) $count > 0;
	}

	public static function record_acceptance( int $user_id, int $deal_id, string $version, string $document_hash = '', bool $audit = true ): bool {
		global $wpdb;
		if ( $user_id <= 0 || '' === $version ) {
			return false;
		}

		$existing = $wpdb->get_var(
			$wpdb->prepare(
				'SELECT id FROM ' . ALGQ_DM_Support::table( 'nda_acceptances' ) . ' WHERE user_id = %d AND deal_id = %d AND nda_version = %s AND revoked_at IS NULL LIMIT 1',
				$user_id,
				$deal_id,
				$version
			)
		);
		if ( $existing ) {
			return true;
		}

		$inserted = $wpdb->insert(
			ALGQ_DM_Support::table( 'nda_acceptances' ),
			array(
				'acceptance_uuid' => wp_generate_uuid4(),
				'user_id'         => $user_id,
				'deal_id'         => $deal_id,
				'nda_version'     => sanitize_text_field( $version ),
				'document_hash'   => sanitize_text_field( $document_hash ),
				'ip_hash'         => ALGQ_DM_Support::client_ip_hash(),
				'user_agent_hash' => ALGQ_DM_Support::user_agent_hash(),
				'accepted_at'     => current_time( 'mysql', true ),
				'revoked_at'      => null,
			),
			array( '%s', '%d', '%d', '%s', '%s', '%s', '%s', '%s', '%s' )
		);

		if ( false !== $inserted && $audit ) {
			ALGQ_DM_Support::audit( 'nda_accepted', $deal_id, array( 'nda_version' => $version ) );
			do_action( 'algq_dm_nda_accepted', $user_id, $deal_id, $version );
		}
		return false !== $inserted;
	}

	public static function handle_acceptance(): never {
		if ( ! is_user_logged_in() || ! current_user_can( 'accept_algq_marketplace_nda' ) ) {
			ALGQ_DM_Support::abort( __( 'Buyer access is required.', 'algq-deal-marketplace' ), 403 );
		}
		$deal_id = isset( $_POST['deal_id'] ) ? absint( $_POST['deal_id'] ) : 0;
		check_admin_referer( 'algq_dm_accept_nda_' . $deal_id );
		$return_url = isset( $_POST['return_url'] ) ? esc_url_raw( wp_unslash( $_POST['return_url'] ) ) : home_url( '/marketplace/' );

		if ( ! isset( $_POST['nda_acknowledgment'] ) || '1' !== sanitize_text_field( wp_unslash( $_POST['nda_acknowledgment'] ) ) ) {
			ALGQ_DM_Support::redirect_with_status( $return_url, 'nda_acknowledgment_required', $deal_id );
		}
		if ( $deal_id > 0 && ! ALGQ_DM_Access::can_view_deal( $deal_id ) ) {
			ALGQ_DM_Support::redirect_with_status( $return_url, 'access_denied', $deal_id );
		}

		$version = self::required_version( $deal_id );
		$document_hash = (string) apply_filters( 'algq_dm_nda_document_hash', '', $deal_id, $version );
		if ( self::record_acceptance( get_current_user_id(), $deal_id, $version, $document_hash ) ) {
			ALGQ_DM_Support::redirect_with_status( $return_url, 'nda_accepted', $deal_id );
		}
		ALGQ_DM_Support::redirect_with_status( $return_url, 'nda_error', $deal_id );
	}
}
