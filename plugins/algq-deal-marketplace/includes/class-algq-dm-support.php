<?php
/**
 * Shared support services.
 *
 * @package Algonquian_Deal_Marketplace
 */

defined( 'ABSPATH' ) || exit;

final class ALGQ_DM_Support {
	public static function table( string $suffix ): string {
		global $wpdb;
		return $wpdb->prefix . 'algq_dm_' . sanitize_key( $suffix );
	}

	public static function hash_private_value( string $value ): string {
		return hash_hmac( 'sha256', $value, wp_salt( 'auth' ) );
	}

	public static function client_ip_hash(): string {
		$ip = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';
		return '' === $ip ? '' : self::hash_private_value( $ip );
	}

	public static function user_agent_hash(): string {
		$agent = isset( $_SERVER['HTTP_USER_AGENT'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ) : '';
		return '' === $agent ? '' : self::hash_private_value( $agent );
	}

	/**
	 * Append an event to the local audit table and emit the platform audit hook.
	 *
	 * @param string               $event Event key.
	 * @param int                  $deal_id Related deal.
	 * @param array<string,mixed>  $context Structured context without secrets.
	 * @param int                  $offer_id Related offer.
	 */
	public static function audit( string $event, int $deal_id = 0, array $context = array(), int $offer_id = 0 ): void {
		global $wpdb;

		$event = sanitize_key( $event );
		$data  = array(
			'event_uuid'      => wp_generate_uuid4(),
			'user_id'         => get_current_user_id(),
			'deal_id'         => absint( $deal_id ),
			'offer_id'        => absint( $offer_id ),
			'event_name'      => $event,
			'event_context'   => wp_json_encode( $context, JSON_UNESCAPED_SLASHES ),
			'ip_hash'         => self::client_ip_hash(),
			'user_agent_hash' => self::user_agent_hash(),
			'created_at'      => current_time( 'mysql', true ),
		);

		$wpdb->insert(
			self::table( 'activity' ),
			$data,
			array( '%s', '%d', '%d', '%d', '%s', '%s', '%s', '%s', '%s' )
		);

		do_action(
			'algq_audit_log',
			array(
				'event'      => 'deal_marketplace.' . $event,
				'plugin'     => 'algq-deal-marketplace',
				'user_id'    => get_current_user_id(),
				'deal_id'    => absint( $deal_id ),
				'offer_id'   => absint( $offer_id ),
				'context'    => $context,
				'occurred_at'=> gmdate( 'c' ),
			)
		);
	}

	/**
	 * Send through the shared platform mail service when available.
	 *
	 * @param string $to Recipient.
	 * @param string $subject Subject.
	 * @param string $message Message.
	 * @param array<string,mixed> $metadata Classification metadata.
	 */
	public static function send_mail( string $to, string $subject, string $message, array $metadata = array() ): bool {
		if ( function_exists( 'algq_send_mail' ) ) {
			return (bool) algq_send_mail(
				array(
					'to'       => $to,
					'subject'  => $subject,
					'message'  => $message,
					'module'   => 'deal-marketplace',
					'event'    => $metadata['event'] ?? 'marketplace_notification',
					'related_id' => absint( $metadata['deal_id'] ?? 0 ),
				)
			);
		}

		return wp_mail( $to, $subject, $message );
	}

	public static function abort( string $message, int $status = 403 ): never {
		wp_die(
			esc_html( $message ),
			esc_html__( 'Marketplace request denied', 'algq-deal-marketplace' ),
			array( 'response' => $status )
		);
	}

	public static function redirect_with_status( string $url, string $status, int $deal_id = 0 ): never {
		$url = add_query_arg(
			array_filter(
				array(
					'algq_dm_status' => sanitize_key( $status ),
					'deal_id'        => $deal_id > 0 ? $deal_id : null,
				)
			),
			$url
		);
		wp_safe_redirect( $url );
		exit;
	}
}
