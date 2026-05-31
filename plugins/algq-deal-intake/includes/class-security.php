<?php
/**
 * Security utilities for Algonquian Deal Intake.
 *
 * @package Algonquian_Deal_Intake
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class ALGQ_Deal_Intake_Security {
	const CAPABILITY = 'manage_options';
	const NONCE_ACTION = 'algq_deal_intake_action';
	const NONCE_NAME = 'algq_deal_intake_nonce';

	public static function can_manage() {
		return current_user_can( self::CAPABILITY );
	}

	public static function require_capability() {
		if ( ! self::can_manage() ) {
			wp_die( esc_html__( 'You do not have permission to manage Deal Intake.', 'algq-deal-intake' ) );
		}
	}

	public static function nonce_field() {
		wp_nonce_field( self::NONCE_ACTION, self::NONCE_NAME );
	}

	public static function verify_request_nonce() {
		$nonce = isset( $_REQUEST[ self::NONCE_NAME ] ) ? sanitize_text_field( wp_unslash( $_REQUEST[ self::NONCE_NAME ] ) ) : '';
		return (bool) wp_verify_nonce( $nonce, self::NONCE_ACTION );
	}

	public static function clean_text( $value ) {
		return sanitize_text_field( wp_unslash( $value ) );
	}

	public static function clean_email( $value ) {
		return sanitize_email( wp_unslash( $value ) );
	}

	public static function clean_money( $value ) {
		return preg_replace( '/[^0-9\.]/', '', (string) wp_unslash( $value ) );
	}
}
