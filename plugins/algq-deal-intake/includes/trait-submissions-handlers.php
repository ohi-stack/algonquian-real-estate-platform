<?php
/** Generated method group for Algonquian Deal Intake. */
defined( 'ABSPATH' ) || exit;

trait ALGQ_Deal_Intake_Submissions_Handlers {
	public static function register_hooks(): void {
		add_action( 'admin_post_nopriv_algq_di_submit_public', array( __CLASS__, 'handle_public' ) );
		add_action( 'admin_post_algq_di_submit_public', array( __CLASS__, 'handle_public' ) );
		add_action( 'admin_post_algq_di_submit_internal', array( __CLASS__, 'handle_internal' ) );
		add_action( 'admin_post_algq_di_accept_submission', array( __CLASS__, 'handle_accept' ) );
		add_action( 'admin_post_algq_di_archive_submission', array( __CLASS__, 'handle_archive' ) );
	}

	public static function handle_public(): void {
		if ( ! ALGQ_Deal_Intake_Security::verify_nonce( 'algq_di_submit_public' ) ) {
			self::redirect_error( 'invalid_request' );
		}

		if ( ! empty( $_POST['algq_di_website'] ) ) {
			self::redirect_success( 0 );
		}

		$started = isset( $_POST['algq_di_started_at'] ) ? absint( $_POST['algq_di_started_at'] ) : 0;
		if ( 0 === $started || time() - $started < 3 ) {
			self::redirect_error( 'invalid_request' );
		}

		if ( ! self::check_rate_limit() ) {
			self::redirect_error( 'rate_limited' );
		}

		$result = self::create_from_array( wp_unslash( $_POST ), false );
		if ( is_wp_error( $result ) ) {
			self::redirect_error( $result->get_error_code() );
		}

		self::redirect_success( (int) $result );
	}

	public static function handle_internal(): void {
		if ( ! current_user_can( ALGQ_Deal_Intake_Security::CAP_REVIEW ) ) {
			wp_die( esc_html__( 'You do not have permission to create internal intake records.', 'algq-deal-intake' ) );
		}

		if ( ! ALGQ_Deal_Intake_Security::verify_nonce( 'algq_di_submit_internal' ) ) {
			wp_die( esc_html__( 'The request could not be verified.', 'algq-deal-intake' ) );
		}

		$result = self::create_from_array( wp_unslash( $_POST ), true );
		if ( is_wp_error( $result ) ) {
			wp_die( esc_html( $result->get_error_message() ) );
		}

		wp_safe_redirect( admin_url( 'admin.php?page=algq-deal-intake&created=' . absint( $result ) ) );
		exit;
	}

	private static function redirect_success( int $submission_id ): void {
		$url = get_permalink( absint( get_option( 'algq_di_thank_you_page_id' ) ) );
		if ( ! $url ) {
			$url = home_url( '/' );
		}
		wp_safe_redirect( add_query_arg( 'reference', $submission_id, $url ) );
		exit;
	}

	private static function redirect_error( string $code ): void {
		$referer = wp_get_referer() ?: home_url( '/' );
		wp_safe_redirect( add_query_arg( 'algq_di_error', sanitize_key( $code ), $referer ) );
		exit;
	}
}
