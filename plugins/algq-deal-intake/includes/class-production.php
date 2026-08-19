<?php
/**
 * Production notification defaults for Algonquian Deal Intake.
 *
 * @package Algonquian_Deal_Intake
 */

defined( 'ABSPATH' ) || exit;

final class ALGQ_Deal_Intake_Production {
	private const COMPANY_EMAIL = 'algonquianre@gmail.com';

	public static function init(): void {
		self::ensure_company_notification_email();
	}

	private static function ensure_company_notification_email(): void {
		$company = function_exists( 'algq_company_notification_email' )
			? algq_company_notification_email()
			: sanitize_email( (string) get_option( 'algq_company_notification_email', self::COMPANY_EMAIL ) );

		if ( ! is_email( $company ) ) {
			$company = self::COMPANY_EMAIL;
		}

		if ( false === get_option( 'algq_company_notification_email', false ) ) {
			add_option( 'algq_company_notification_email', $company, '', false );
		}

		$current = sanitize_email( (string) get_option( 'algq_di_notification_email', '' ) );
		$admin   = sanitize_email( (string) get_option( 'admin_email', '' ) );

		// Preserve an intentional custom intake mailbox. Migrate only the old
		// empty/default-admin configuration to the verified ARE operations inbox.
		if ( '' === $current || ( is_email( $admin ) && strtolower( $current ) === strtolower( $admin ) ) ) {
			update_option( 'algq_di_notification_email', $company, false );
			self::audit( 'deal_intake.notification_email_migrated', array( 'recipient_domain' => self::email_domain( $company ) ) );
		}
	}

	/** @param array<string,mixed> $context */
	private static function audit( string $event, array $context ): void {
		if ( function_exists( 'algq_log_event' ) ) {
			algq_log_event( $event, array_merge( array( 'plugin' => 'algq-deal-intake' ), $context ) );
			return;
		}
		do_action( 'algq_audit_event', $event, array_merge( array( 'plugin' => 'algq-deal-intake' ), $context ) );
	}

	private static function email_domain( string $email ): string {
		$parts = explode( '@', $email );
		return 2 === count( $parts ) ? sanitize_text_field( $parts[1] ) : '';
	}
}
