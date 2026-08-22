<?php
/**
 * Conversation-synchronized public shortcode UI for Algonquian Deal Intake.
 *
 * Keeps the canonical intake handlers/data model intact while ensuring every
 * page-facing shortcode renders useful, branded operational content.
 *
 * @package Algonquian_Deal_Intake
 */

defined( 'ABSPATH' ) || exit;

final class ALGQ_Deal_Intake_Conversation_UI {
	public static function register_hooks(): void {
		add_action( 'init', array( __CLASS__, 'register_shortcodes' ), 30 );
	}

	public static function register_shortcodes(): void {
		add_shortcode( 'algq_deal_intake_form', array( __CLASS__, 'public_intake' ) );
		add_shortcode( 'algq_property_submission', array( __CLASS__, 'public_intake' ) );
		add_shortcode( 'deal_intake_form_public', array( __CLASS__, 'public_intake' ) );
		add_shortcode( 'deal_intake_form_internal', array( __CLASS__, 'internal_intake' ) );
		add_shortcode( 'deal_quick_capture', array( __CLASS__, 'quick_capture' ) );
		add_shortcode( 'algq_homeowner_options', array( __CLASS__, 'homeowner_options' ) );
		add_shortcode( 'algq_seller_portal', array( __CLASS__, 'seller_portal' ) );
		add_shortcode( 'algq_deal_intake_about', array( __CLASS__, 'about' ) );
	}

	private static function shell_start( string $eyebrow, string $title, string $lead = '' ): string {
		$html  = '<section class="algq-di-experience">';
		$html .= '<header class="algq-di-experience__hero">';
		$html .= '<span class="algq-di-experience__eyebrow">' . esc_html( $eyebrow ) . '</span>';
		$html .= '<h2>' . esc_html( $title ) . '</h2>';
		if ( '' !== $lead ) {
			$html .= '<p>' . esc_html( $lead ) . '</p>';
		}
		$html .= '</header>';
		return $html;
	}

	private static function shell_end(): string {
		return '</section>';
	}

	public static function public_intake(): string {
		$html  = self::shell_start(
			__( 'Property Owners • Acquisition Intake', 'algq-deal-intake' ),
			__( 'Request a Property Review', 'algq-deal-intake' ),
			__( 'Tell Algonquian Real Estate about the property, your timing, and the situation. The intake creates a review record; it is not an offer, contract, appraisal, brokerage agreement, or commitment to purchase.', 'algq-deal-intake' )
		);
		$html .= '<div class="algq-di-kpi-grid">';
		$html .= self::card( '01', __( 'Property', 'algq-deal-intake' ), __( 'Address, type, condition and supporting information.', 'algq-deal-intake' ) );
		$html .= self::card( '02', __( 'Owner Goals', 'algq-deal-intake' ), __( 'Timing, asking price and reason for exploring options.', 'algq-deal-intake' ) );
		$html .= self::card( '03', __( 'Review', 'algq-deal-intake' ), __( 'Duplicate screening, lead scoring and acquisition review.', 'algq-deal-intake' ) );
		$html .= self::card( '04', __( 'Next Step', 'algq-deal-intake' ), __( 'Qualified opportunities can move into the canonical Pipeline CRM.', 'algq-deal-intake' ) );
		$html .= '</div>';
		$html .= '<div class="algq-di-workspace">' . ALGQ_Deal_Intake_Pages::public_form() . '</div>';
		$html .= '<div class="algq-di-disclosure"><strong>' . esc_html__( 'What happens after submission', 'algq-deal-intake' ) . '</strong><p>' . esc_html__( 'Algonquian Real Estate reviews the submitted information, supporting records, duplicate indicators and acquisition fit. A submission may be accepted for further review, placed into follow-up, or declined. Material transaction decisions remain subject to human review and appropriate professional review.', 'algq-deal-intake' ) . '</p></div>';
		$html .= self::shell_end();
		return $html;
	}

	public static function internal_intake(): string {
		if ( ! current_user_can( ALGQ_Deal_Intake_Security::CAP_REVIEW ) ) {
			return self::restricted( __( 'Internal Deal Intake', 'algq-deal-intake' ) );
		}
		$html  = self::shell_start( __( 'Acquisition Operations • Internal', 'algq-deal-intake' ), __( 'Internal Deal Intake', 'algq-deal-intake' ), __( 'Create a controlled intake record from an authorized owner conversation, referral, broker lead, attorney referral, driving-for-dollars lead, or other documented source.', 'algq-deal-intake' ) );
		$html .= '<div class="algq-di-workspace">' . ALGQ_Deal_Intake_Pages::internal_form() . '</div>';
		$html .= self::shell_end();
		return $html;
	}

	public static function quick_capture(): string {
		if ( ! current_user_can( ALGQ_Deal_Intake_Security::CAP_REVIEW ) ) {
			return self::restricted( __( 'Quick Deal Capture', 'algq-deal-intake' ) );
		}
		$html  = self::shell_start( __( 'Acquisition Operations • Fast Entry', 'algq-deal-intake' ), __( 'Quick Deal Capture', 'algq-deal-intake' ), __( 'Capture the minimum actionable lead information now, then complete qualification and underwriting through the transaction workflow.', 'algq-deal-intake' ) );
		$html .= '<div class="algq-di-workspace">' . ALGQ_Deal_Intake_Pages::quick_form() . '</div>';
		$html .= self::shell_end();
		return $html;
	}

	public static function homeowner_options(): string {
		$submit = home_url( '/submit-a-property/' );
		$html  = self::shell_start( __( 'Property Owners • Explore Your Options', 'algq-deal-intake' ), __( 'What Are My Options?', 'algq-deal-intake' ), __( 'The best property decision depends on what you are trying to accomplish. Algonquian Real Estate organizes the major paths so an owner can decide what deserves further review without being pushed immediately toward a sale.', 'algq-deal-intake' ) );
		$html .= '<div class="algq-di-option-grid">';
		$options = array(
			array( 'Sell Traditionally', 'Compare a conventional market sale where an appropriate licensed real estate professional is involved.' ),
			array( 'Direct / As-Is Review', 'Explore whether a direct acquisition or as-is disposition fits the property, timing and owner objectives.' ),
			array( 'Repair Before Sale', 'Evaluate whether selected repairs or improvements may improve marketability or economics before disposition.' ),
			array( 'Retain the Property', 'Consider continued ownership, rental economics, maintenance needs and the responsibilities of holding the asset.' ),
			array( 'Seller Financing Discussion', 'Where appropriate, evaluate owner-carried financing concepts subject to legal, tax, underwriting and documentation review.' ),
			array( 'Property Stewardship', 'For owners not ready to sell, coordinate authorized observation, documentation and property-service activity.' ),
			array( 'Inherited / Transition Property', 'Organize the property questions involved in inheritance, vacancy, downsizing, family transition or long-distance ownership.' ),
			array( 'Development / Redevelopment', 'For land, infill, underutilized buildings or assemblage opportunities, route the site into development-concept review.' ),
		);
		foreach ( $options as $option ) {
			$html .= '<article class="algq-di-option"><h3>' . esc_html( $option[0] ) . '</h3><p>' . esc_html( $option[1] ) . '</p></article>';
		}
		$html .= '</div>';
		$html .= '<div class="algq-di-cta"><div><strong>' . esc_html__( 'Start with the property, not a predetermined strategy.', 'algq-deal-intake' ) . '</strong><p>' . esc_html__( 'Submit the facts available today and the intake workflow can route the opportunity to the appropriate next review.', 'algq-deal-intake' ) . '</p></div><a class="algq-di-primary" href="' . esc_url( $submit ) . '">' . esc_html__( 'Request a Property Review', 'algq-deal-intake' ) . '</a></div>';
		$html .= self::shell_end();
		return $html;
	}

	public static function seller_portal(): string {
		$html = self::shell_start( __( 'Property Owners • Secure Access', 'algq-deal-intake' ), __( 'Seller Portal', 'algq-deal-intake' ), __( 'A controlled workspace for property-submission status, authorized records, requested information and next steps.', 'algq-deal-intake' ) );
		if ( ! is_user_logged_in() ) {
			$html .= '<div class="algq-di-portal-state"><h3>' . esc_html__( 'Authentication Required', 'algq-deal-intake' ) . '</h3><p>' . esc_html__( 'Seller information is not exposed publicly. Sign in through the authorized Algonquian Real Estate account workflow. Record-level access must also be granted before any submission is displayed.', 'algq-deal-intake' ) . '</p></div>';
		} else {
			$html .= '<div class="algq-di-portal-state"><h3>' . esc_html__( 'Secure Seller Workspace', 'algq-deal-intake' ) . '</h3><p>' . esc_html__( 'You are signed in. Submission details remain hidden unless the platform confirms that this account has record-level authorization for the seller or property record.', 'algq-deal-intake' ) . '</p></div>';
		}
		$html .= '<div class="algq-di-kpi-grid">' . self::card( 'SUB', 'Submission', 'Property intake and reference information.' ) . self::card( 'DOC', 'Documents', 'Authorized supporting records and requests.' ) . self::card( 'ACT', 'Activity', 'Approved status and follow-up information.' ) . self::card( 'SEC', 'Access', 'Authentication plus record-level authorization.' ) . '</div>';
		$html .= self::shell_end();
		return $html;
	}

	public static function about(): string {
		$html  = self::shell_start( __( 'Algonquian Real Estate Technology Division', 'algq-deal-intake' ), __( 'Algonquian Deal Intake', 'algq-deal-intake' ), __( 'The authoritative entry point for seller leads and property submissions entering the Algonquian Real Estate operating platform.', 'algq-deal-intake' ) );
		$html .= '<div class="algq-di-kpi-grid">' . self::card( '2.1', 'Release', 'Current production-candidate source.' ) . self::card( 'INT', 'Authority', 'Seller and property intake evidence.' ) . self::card( 'CRM', 'Handoff', 'Pipeline CRM owns the canonical deal after acceptance.' ) . self::card( 'PDF', 'Artifacts', 'Protected submission files and intake PDF archive.' ) . '</div>';
		$html .= '<div class="algq-di-option-grid">';
		$html .= '<article class="algq-di-option"><h3>Core Functions</h3><p>Public and internal intake, validation, consent evidence, duplicate review, lead scoring, supporting files, protected PDF records, audit events and controlled CRM handoff.</p></article>';
		$html .= '<article class="algq-di-option"><h3>Human Control</h3><p>Intake automation organizes information and workflow. It does not independently approve acquisition strategy, binding offers, contracts, financing commitments or closing decisions.</p></article>';
		$html .= '<article class="algq-di-option"><h3>Current Interfaces</h3><p><code>[algq_deal_intake_form]</code> <code>[algq_property_submission]</code> <code>[deal_intake_form_public]</code> <code>[deal_intake_form_internal]</code> <code>[deal_quick_capture]</code> <code>[algq_homeowner_options]</code> <code>[algq_seller_portal]</code> <code>[algq_deal_intake_about]</code></p></article>';
		$html .= '<article class="algq-di-option"><h3>Transaction Boundary</h3><p>Deal Intake owns submission-time evidence. Pipeline CRM owns deal lifecycle; MAO owns underwriting; Offer Generator owns offers; controlled document systems retain their respective authorities.</p></article>';
		$html .= '</div>';
		$html .= self::shell_end();
		return $html;
	}

	private static function card( string $code, string $title, string $text ): string {
		return '<article class="algq-di-kpi"><span>' . esc_html( $code ) . '</span><div><strong>' . esc_html( $title ) . '</strong><p>' . esc_html( $text ) . '</p></div></article>';
	}

	private static function restricted( string $title ): string {
		return '<section class="algq-di-experience"><div class="algq-di-portal-state"><h3>' . esc_html( $title ) . '</h3><p>' . esc_html__( 'This interface is restricted to authorized Algonquian Real Estate acquisition personnel.', 'algq-deal-intake' ) . '</p></div></section>';
	}
}
