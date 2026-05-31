<?php
/**
 * Security helpers for Algonquian Command Center.
 *
 * @package Algonquian_Command_Center
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class ALGQ_Command_Center_Security {
	const CAPABILITY = 'manage_options';
	const NONCE_ACTION = 'algq_command_center_action';
	const NONCE_NAME = 'algq_command_center_nonce';

	public static function can_manage() {
		return current_user_can( self::CAPABILITY );
	}

	public static function require_manage_capability() {
		if ( ! self::can_manage() ) {
			wp_die( esc_html__( 'You do not have permission to access the Algonquian Command Center.', 'algq-command-center' ) );
		}
	}

	public static function verify_nonce_from_request() {
		$nonce = isset( $_REQUEST[ self::NONCE_NAME ] ) ? sanitize_text_field( wp_unslash( $_REQUEST[ self::NONCE_NAME ] ) ) : '';
		return wp_verify_nonce( $nonce, self::NONCE_ACTION );
	}

	public static function nonce_field() {
		wp_nonce_field( self::NONCE_ACTION, self::NONCE_NAME );
	}

	public static function clean_text( $value ) {
		return sanitize_text_field( wp_unslash( $value ) );
	}

	public static function esc_metric( $value ) {
		return esc_html( is_scalar( $value ) ? (string) $value : '' );
	}
}
