<?php
/**
 * Public-form feedback, property-review hardening, and campaign attribution.
 *
 * @package Algonquian_Deal_Intake
 */

defined( 'ABSPATH' ) || exit;

final class ALGQ_Deal_Intake_Production_Feedback {
	public static function register_hooks(): void {
		add_filter( 'do_shortcode_tag', array( __CLASS__, 'enhance_output' ), 30, 4 );
	}

	public static function enhance_output( string $output, string $tag, array|string $attr, array $match ): string { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
		$form_tags = array(
			'algq_deal_intake_form',
			'algq_property_submission',
			'deal_intake_form_public',
			'deal_intake_form_internal',
			'deal_quick_capture',
			'algq_property_review',
			'algq_seller_intake_entry',
		);

		if ( ! in_array( $tag, $form_tags, true ) || false === strpos( $output, 'algq-di-form' ) ) {
			return $output;
		}

		// Property Review embeds the canonical form directly, so apply the same artifact layer.
		if ( 'algq_property_review' === $tag && class_exists( 'ALGQ_Deal_Intake_Artifacts' ) ) {
			$output = ALGQ_Deal_Intake_Artifacts::enhance_form_output( $output, 'algq_deal_intake_form', array(), array() );
		}

		$output = self::inject_campaign_attribution( $output );
		$output = self::inject_error_notice( $output );

		return $output;
	}

	private static function inject_campaign_attribution( string $output ): string {
		if ( false !== strpos( $output, 'name="campaign"' ) ) {
			return $output;
		}

		$parts = array();
		foreach ( array( 'utm_source', 'utm_medium', 'utm_campaign', 'utm_content', 'utm_term', 'gclid', 'fbclid' ) as $key ) {
			if ( isset( $_GET[ $key ] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
				$value = sanitize_text_field( wp_unslash( $_GET[ $key ] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
				if ( '' !== $value ) {
					$parts[] = $key . '=' . substr( $value, 0, 80 );
				}
			}
		}

		if ( ! $parts ) {
			return $output;
		}

		$campaign = substr( implode( '|', $parts ), 0, 120 );
		$hidden = '<input type="hidden" name="campaign" value="' . esc_attr( $campaign ) . '">';

		return preg_replace( '/(<input\s+type="hidden"\s+name="action"[^>]*>)/i', '$1' . $hidden, $output, 1 ) ?? $output;
	}

	private static function inject_error_notice( string $output ): string {
		$code = isset( $_GET['algq_di_error'] ) ? sanitize_key( wp_unslash( $_GET['algq_di_error'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( '' === $code ) {
			return $output;
		}

		$messages = array(
			'invalid_request' => __( 'The submission could not be verified. Please review the form and try again.', 'algq-deal-intake' ),
			'rate_limited' => __( 'Too many submissions were received from this connection. Please try again later or contact Algonquian Real Estate.', 'algq-deal-intake' ),
			'missing_required' => __( 'Please complete the required seller and property fields.', 'algq-deal-intake' ),
			'missing_contact' => __( 'Please provide at least one contact method: email or phone.', 'algq-deal-intake' ),
			'invalid_email' => __( 'Please enter a valid email address.', 'algq-deal-intake' ),
			'consent_required' => __( 'Contact authorization and submission consent are required.', 'algq-deal-intake' ),
			'persistence_failed' => __( 'The submission could not be saved. Please contact Algonquian Real Estate if the problem continues.', 'algq-deal-intake' ),
			'security_challenge_failed' => __( 'The anti-spam verification was not completed. Please try again.', 'algq-deal-intake' ),
		);

		$message = $messages[ $code ] ?? __( 'The submission could not be completed. Please review the form and try again.', 'algq-deal-intake' );
		$notice = '<div class="algq-di-notice algq-di-error" role="alert" aria-live="assertive"><strong>' . esc_html__( 'Submission not completed', 'algq-deal-intake' ) . '</strong><p>' . esc_html( $message ) . '</p></div>';

		if ( preg_match( '/(<form\s+class="algq-di-form"[^>]*>)/i', $output ) ) {
			return preg_replace( '/(<form\s+class="algq-di-form"[^>]*>)/i', $notice . '$1', $output, 1 ) ?? $output;
		}

		return $notice . $output;
	}
}
