<?php
/** Generated administration method group for Algonquian Deal Intake. */
defined( 'ABSPATH' ) || exit;

trait ALGQ_Deal_Intake_Admin_Actions {
	public static function resolve_duplicate(): void {
		if ( ! current_user_can( ALGQ_Deal_Intake_Security::CAP_REVIEW ) ) {
			wp_die( esc_html__( 'You do not have permission to resolve duplicates.', 'algq-deal-intake' ) );
		}
		$duplicate_id = isset( $_GET['duplicate_id'] ) ? absint( $_GET['duplicate_id'] ) : 0;
		$resolution = isset( $_GET['resolution'] ) ? sanitize_key( wp_unslash( $_GET['resolution'] ) ) : '';
		if ( ! $duplicate_id || ! in_array( $resolution, array( 'separate', 'duplicate' ), true ) || ! ALGQ_Deal_Intake_Security::verify_nonce( 'algq_di_resolve_duplicate_' . $duplicate_id ) ) {
			wp_die( esc_html__( 'The duplicate resolution request could not be verified.', 'algq-deal-intake' ) );
		}
		global $wpdb;
		$table = ALGQ_Deal_Intake_Database::table( 'duplicates' );
		$row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d", $duplicate_id ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		if ( ! $row ) {
			wp_die( esc_html__( 'Duplicate record not found.', 'algq-deal-intake' ) );
		}
		$wpdb->update( $table, array( 'status' => 'resolved', 'reviewer_id' => get_current_user_id(), 'resolution' => $resolution, 'resolved_at' => current_time( 'mysql', true ) ), array( 'id' => $duplicate_id ), array( '%s', '%d', '%s', '%s' ), array( '%d' ) );
		if ( 'separate' === $resolution ) {
			$remaining = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$table} WHERE submission_id = %d AND status = 'pending'", (int) $row['submission_id'] ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			if ( 0 === $remaining ) {
				$wpdb->update( ALGQ_Deal_Intake_Database::table( 'submissions' ), array( 'duplicate_status' => 'clear', 'updated_at' => current_time( 'mysql', true ) ), array( 'id' => (int) $row['submission_id'] ), array( '%s', '%s' ), array( '%d' ) );
			}
		} else {
			$wpdb->update( ALGQ_Deal_Intake_Database::table( 'submissions' ), array( 'duplicate_status' => 'confirmed_duplicate', 'status' => 'archived', 'updated_at' => current_time( 'mysql', true ) ), array( 'id' => (int) $row['submission_id'] ), array( '%s', '%s', '%s' ), array( '%d' ) );
		}
		wp_safe_redirect( admin_url( 'admin.php?page=algq-deal-intake-duplicates' ) );
		exit;
	}

	public static function export_csv(): void {
		if ( ! current_user_can( ALGQ_Deal_Intake_Security::CAP_EXPORT ) || ! ALGQ_Deal_Intake_Security::verify_nonce( 'algq_di_export_csv' ) ) {
			wp_die( esc_html__( 'You do not have permission to export Deal Intake data.', 'algq-deal-intake' ) );
		}
		global $wpdb;
		$table = ALGQ_Deal_Intake_Database::table( 'submissions' );
		$ids = $wpdb->get_col( "SELECT id FROM {$table} WHERE deleted_at IS NULL ORDER BY created_at DESC" ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		nocache_headers();
		header( 'Content-Type: text/csv; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename=algq-deal-intake-' . gmdate( 'Y-m-d' ) . '.csv' );
		$output = fopen( 'php://output', 'w' );
		if ( false === $output ) {
			wp_die( esc_html__( 'The export stream could not be opened.', 'algq-deal-intake' ) );
		}
		fputcsv( $output, array( 'Reference', 'Status', 'Created', 'Seller', 'Email', 'Phone', 'Address', 'City', 'State', 'Asking Price', 'Lead Score', 'Duplicate Status', 'Pipeline Deal ID' ) );
		foreach ( $ids as $id ) {
			$record = ALGQ_Deal_Intake_Submissions::get( (int) $id );
			fputcsv( $output, array( self::csv_value( $record['uuid'] ?? '' ), self::csv_value( $record['status'] ?? '' ), self::csv_value( $record['created_at'] ?? '' ), self::csv_value( $record['seller_name'] ?? '' ), self::csv_value( $record['seller_email'] ?? '' ), self::csv_value( $record['seller_phone'] ?? '' ), self::csv_value( $record['address'] ?? '' ), self::csv_value( $record['city'] ?? '' ), self::csv_value( $record['state'] ?? '' ), self::csv_value( $record['asking_price'] ?? '' ), self::csv_value( $record['lead_score'] ?? '' ), self::csv_value( $record['duplicate_status'] ?? '' ), self::csv_value( $record['pipeline_deal_id'] ?? '' ) ) );
		}
		fclose( $output );
		exit;
	}

	private static function csv_value( mixed $value ): string {
		$value = (string) $value;
		if ( preg_match( '/^[=+\-@]/', $value ) ) {
			return "'" . $value;
		}
		return $value;
	}
}
