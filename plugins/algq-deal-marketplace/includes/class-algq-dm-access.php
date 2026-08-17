<?php
/**
 * Buyer authorization, grants, entitlements, and controlled downloads.
 *
 * @package Algonquian_Deal_Marketplace
 */

defined( 'ABSPATH' ) || exit;

final class ALGQ_DM_Access {
	public static function init(): void {
		add_action( 'admin_post_algq_dm_download_package', array( __CLASS__, 'download_package' ) );
	}

	public static function buyer_has_base_access( int $user_id = 0 ): bool {
		$user_id = $user_id ?: get_current_user_id();
		if ( $user_id <= 0 ) {
			return false;
		}
		if ( user_can( $user_id, 'manage_algq_marketplace' ) || user_can( $user_id, 'manage_options' ) ) {
			return true;
		}
		return user_can( $user_id, 'view_algq_marketplace' ) && user_can( $user_id, 'view_algq_marketplace_deals' );
	}

	public static function can_view_deal( int $deal_id, int $user_id = 0 ): bool {
		$user_id = $user_id ?: get_current_user_id();
		$post = get_post( $deal_id );
		if ( ! $post || 'algq_market_deal' !== $post->post_type || 'publish' !== $post->post_status ) {
			return user_can( $user_id, 'manage_algq_marketplace' );
		}
		if ( ! self::buyer_has_base_access( $user_id ) ) {
			return false;
		}

		$expires_at = (string) ALGQ_DM_Marketplace::meta( $deal_id, 'expires_at', '' );
		if ( '' !== $expires_at && strtotime( $expires_at . ' 23:59:59 UTC' ) < time() && ! user_can( $user_id, 'manage_algq_marketplace' ) ) {
			return false;
		}

		$allowed = array_filter( array_map( 'absint', explode( ',', (string) ALGQ_DM_Marketplace::meta( $deal_id, 'allowed_buyers', '' ) ) ) );
		if ( ! empty( $allowed ) && ! in_array( $user_id, $allowed, true ) && ! user_can( $user_id, 'manage_algq_marketplace' ) ) {
			return false;
		}

		$tier = (string) ALGQ_DM_Marketplace::meta( $deal_id, 'access_tier', 'registered' );
		$allowed_by_tier = 'registered' === $tier;
		if ( in_array( $tier, array( 'premium', 'private' ), true ) ) {
			$allowed_by_tier = self::has_active_grant( $user_id, $deal_id );
		}

		$allowed_by_tier = (bool) apply_filters( 'algq_dm_user_can_access_deal', $allowed_by_tier, $user_id, $deal_id, $tier );
		return $allowed_by_tier || user_can( $user_id, 'manage_algq_marketplace' );
	}

	public static function can_download( int $deal_id, int $user_id = 0 ): bool {
		$user_id = $user_id ?: get_current_user_id();
		if ( ! self::can_view_deal( $deal_id, $user_id ) ) {
			return false;
		}
		if ( ! user_can( $user_id, 'download_algq_marketplace_packages' ) && ! user_can( $user_id, 'manage_algq_marketplace' ) ) {
			return false;
		}
		$nda_required = 'yes' === get_option( 'algq_dm_nda_required', 'yes' );
		if ( $nda_required && ! ALGQ_DM_NDA::accepted( $user_id, $deal_id ) ) {
			return false;
		}
		return absint( ALGQ_DM_Marketplace::meta( $deal_id, 'package_attachment_id', 0 ) ) > 0;
	}

	public static function has_active_grant( int $user_id, int $deal_id ): bool {
		global $wpdb;
		$table = ALGQ_DM_Support::table( 'access_grants' );
		$count = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$table} WHERE user_id = %d AND deal_id = %d AND revoked_at IS NULL AND (expires_at IS NULL OR expires_at > %s)",
				$user_id,
				$deal_id,
				current_time( 'mysql', true )
			)
		);
		return (int) $count > 0;
	}

	public static function grant( int $user_id, int $deal_id, string $source = 'manual', string $entitlement_key = '', ?string $expires_at = null ): bool {
		global $wpdb;
		if ( $user_id <= 0 || $deal_id <= 0 ) {
			return false;
		}
		$inserted = $wpdb->insert(
			ALGQ_DM_Support::table( 'access_grants' ),
			array(
				'grant_uuid'      => wp_generate_uuid4(),
				'user_id'         => $user_id,
				'deal_id'         => $deal_id,
				'source'          => sanitize_key( $source ),
				'entitlement_key' => sanitize_text_field( $entitlement_key ),
				'created_at'      => current_time( 'mysql', true ),
				'expires_at'      => $expires_at,
				'revoked_at'      => null,
			),
			array( '%s', '%d', '%d', '%s', '%s', '%s', '%s', '%s' )
		);
		if ( false !== $inserted ) {
			ALGQ_DM_Support::audit( 'access_granted', $deal_id, array( 'target_user_id' => $user_id, 'source' => $source ) );
			return true;
		}
		return false;
	}

	public static function revoke_by_entitlement( string $entitlement_key ): void {
		global $wpdb;
		$wpdb->update(
			ALGQ_DM_Support::table( 'access_grants' ),
			array( 'revoked_at' => current_time( 'mysql', true ) ),
			array( 'entitlement_key' => sanitize_text_field( $entitlement_key ) ),
			array( '%s' ),
			array( '%s' )
		);
	}

	public static function handle_entitlement_created( int $user_id, string $entitlement_key, array $metadata = array() ): void {
		$deal_id = absint( $metadata['deal_id'] ?? 0 );
		if ( $deal_id > 0 ) {
			self::grant( $user_id, $deal_id, 'stripe', $entitlement_key, $metadata['expires_at'] ?? null );
		}
	}

	public static function handle_entitlement_revoked( int $user_id, string $entitlement_key, array $metadata = array() ): void {
		unset( $user_id, $metadata );
		self::revoke_by_entitlement( $entitlement_key );
	}

	public static function download_url( int $deal_id ): string {
		return wp_nonce_url(
			add_query_arg(
				array(
					'action'  => 'algq_dm_download_package',
					'deal_id' => $deal_id,
				),
				admin_url( 'admin-post.php' )
			),
			'algq_dm_download_' . $deal_id
		);
	}

	public static function download_package(): never {
		$deal_id = isset( $_GET['deal_id'] ) ? absint( $_GET['deal_id'] ) : 0;
		check_admin_referer( 'algq_dm_download_' . $deal_id );
		if ( ! self::can_download( $deal_id ) ) {
			ALGQ_DM_Support::abort( __( 'You are not authorized to download this deal package.', 'algq-deal-marketplace' ), 403 );
		}

		$attachment_id = absint( ALGQ_DM_Marketplace::meta( $deal_id, 'package_attachment_id', 0 ) );
		$file_path = (string) apply_filters( 'algq_dm_package_file_path', get_attached_file( $attachment_id ), $attachment_id, $deal_id );
		if ( '' === $file_path || ! is_readable( $file_path ) ) {
			ALGQ_DM_Support::abort( __( 'The deal package is unavailable. Contact Algonquian Real Estate.', 'algq-deal-marketplace' ), 404 );
		}

		ALGQ_DM_Support::audit( 'package_downloaded', $deal_id, array( 'attachment_id' => $attachment_id ) );
		do_action( 'algq_dm_package_downloaded', $deal_id, get_current_user_id(), $attachment_id );

		while ( ob_get_level() ) {
			ob_end_clean();
		}
		nocache_headers();
		header( 'Content-Type: application/octet-stream' );
		header( 'Content-Disposition: attachment; filename="' . rawurlencode( basename( $file_path ) ) . '"' );
		header( 'Content-Length: ' . (string) filesize( $file_path ) );
		header( 'X-Content-Type-Options: nosniff' );
		readfile( $file_path ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_read_readfile
		exit;
	}
}
