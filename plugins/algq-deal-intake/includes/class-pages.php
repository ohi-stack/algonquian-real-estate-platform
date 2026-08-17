<?php
/**
 * Idempotent page generation and shortcode registration.
 *
 * @package Algonquian_Deal_Intake
 */

defined( 'ABSPATH' ) || exit;

final class ALGQ_Deal_Intake_Pages {
	public static function register_hooks(): void {
		add_action( 'init', array( __CLASS__, 'register_shortcodes' ) );
	}

	public static function register_shortcodes(): void {
		add_shortcode( 'algq_deal_intake_form', array( __CLASS__, 'public_form' ) );
		add_shortcode( 'algq_property_submission', array( __CLASS__, 'public_form' ) );
		add_shortcode( 'deal_intake_form_public', array( __CLASS__, 'public_form' ) );
		add_shortcode( 'deal_intake_form_internal', array( __CLASS__, 'internal_form' ) );
		add_shortcode( 'deal_quick_capture', array( __CLASS__, 'quick_form' ) );
		add_shortcode( 'algq_homeowner_options', array( __CLASS__, 'homeowner_options' ) );
		add_shortcode( 'algq_seller_portal', array( __CLASS__, 'seller_portal' ) );
	}

	public static function create_pages(): void {
		$pages = array(
			'algq_di_submit_property_page_id' => array( 'Submit Property', 'submit-property', '[algq_deal_intake_form]' ),
			'algq_di_sell_property_page_id' => array( 'Sell Your Property', 'sell-your-property', '[algq_deal_intake_form]' ),
			'algq_di_homeowner_options_page_id' => array( 'Homeowner Options', 'homeowner-options', '[algq_homeowner_options]' ),
			'algq_di_seller_portal_page_id' => array( 'Seller Portal', 'seller-portal', '[algq_seller_portal]' ),
			'algq_di_thank_you_page_id' => array( 'Property Submission Received', 'property-submission-received', self::thank_you_content() ),
			'algq_di_plugin_page_id' => array( 'Algonquian Deal Intake', 'plugin/deal-intake', self::plugin_overview_content() ),
			'algq_di_start_page_id' => array( 'Getting Started With Algonquian Deal Intake', 'plugin/deal-intake/start', self::getting_started_content() ),
			'algq_di_docs_page_id' => array( 'Algonquian Deal Intake Documentation', 'plugin/deal-intake/docs', self::documentation_content() ),
		);

		foreach ( $pages as $option => $definition ) {
			self::create_page( $option, $definition[0], $definition[1], $definition[2] );
		}
	}

	private static function create_page( string $option, string $title, string $path, string $content ): void {
		$stored_id = absint( get_option( $option ) );
		if ( $stored_id && 'trash' !== get_post_status( $stored_id ) ) {
			return;
		}

		$existing = get_page_by_path( $path, OBJECT, 'page' );
		if ( $existing instanceof WP_Post ) {
			update_option( $option, $existing->ID );
			return;
		}

		$segments = array_values( array_filter( explode( '/', trim( $path, '/' ) ) ) );
		$slug = array_pop( $segments );
		$parent_id = 0;
		$parent_path = '';

		foreach ( $segments as $segment ) {
			$parent_path = ltrim( $parent_path . '/' . $segment, '/' );
			$parent = get_page_by_path( $parent_path, OBJECT, 'page' );
			if ( ! $parent ) {
				$parent_id = wp_insert_post(
					array(
						'post_title' => ucwords( str_replace( '-', ' ', $segment ) ),
						'post_name' => $segment,
						'post_type' => 'page',
						'post_status' => 'publish',
						'post_parent' => $parent_id,
						'post_content' => '',
					)
				);
			} else {
				$parent_id = $parent->ID;
			}
		}

		$page_id = wp_insert_post(
			array(
				'post_title' => $title,
				'post_name' => $slug,
				'post_type' => 'page',
				'post_status' => 'publish',
				'post_parent' => $parent_id,
				'post_content' => $content,
			),
			true
		);

		if ( ! is_wp_error( $page_id ) ) {
			update_option( $option, (int) $page_id );
			update_post_meta( (int) $page_id, '_algq_generated_by', 'algq-deal-intake' );
			update_post_meta( (int) $page_id, '_algq_generated_version', ALGQ_DI_VERSION );
		}
	}

	public static function public_form(): string {
		return self::render_form( false, false );
	}

	public static function internal_form(): string {
		if ( ! current_user_can( ALGQ_Deal_Intake_Security::CAP_REVIEW ) ) {
			return '<div class="algq-di-notice algq-di-error">' . esc_html__( 'You do not have permission to use the internal intake form.', 'algq-deal-intake' ) . '</div>';
		}

		return self::render_form( true, false );
	}

	public static function quick_form(): string {
		if ( ! current_user_can( ALGQ_Deal_Intake_Security::CAP_REVIEW ) ) {
			return '<div class="algq-di-notice algq-di-error">' . esc_html__( 'You do not have permission to use quick capture.', 'algq-deal-intake' ) . '</div>';
		}

		return self::render_form( true, true );
	}

	private static function render_form( bool $internal, bool $quick ): string {
		$action = $internal ? 'algq_di_submit_internal' : 'algq_di_submit_public';
		$nonce_action = $internal ? 'algq_di_submit_internal' : 'algq_di_submit_public';
		$state = $internal ? 'CT' : '';

		ob_start();
		?>
		<section class="algq-di-shell">
			<header class="algq-di-header">
				<span class="algq-di-badge"><?php echo esc_html( $internal ? __( 'Internal Acquisition Intake', 'algq-deal-intake' ) : __( 'Connecticut Property Submission', 'algq-deal-intake' ) ); ?></span>
				<h2><?php echo esc_html( $quick ? __( 'Quick Deal Capture', 'algq-deal-intake' ) : __( 'Tell Us About the Property', 'algq-deal-intake' ) ); ?></h2>
				<p><?php esc_html_e( 'Submit the information available today. A submission is an intake record and is not an offer, contract, valuation, or commitment to purchase.', 'algq-deal-intake' ); ?></p>
			</header>
			<form class="algq-di-form" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" novalidate>
				<input type="hidden" name="action" value="<?php echo esc_attr( $action ); ?>">
				<input type="hidden" name="algq_di_started_at" value="<?php echo esc_attr( time() ); ?>">
				<input type="text" name="algq_di_website" value="" tabindex="-1" autocomplete="off" class="algq-di-honeypot" aria-hidden="true">
				<?php wp_nonce_field( $nonce_action, 'algq_di_nonce' ); ?>
				<div class="algq-di-grid">
					<label><?php esc_html_e( 'Seller or Contact Name', 'algq-deal-intake' ); ?><span aria-hidden="true"> *</span><input type="text" name="seller_name" required></label>
					<label><?php esc_html_e( 'Email', 'algq-deal-intake' ); ?><input type="email" name="seller_email"></label>
					<label><?php esc_html_e( 'Phone', 'algq-deal-intake' ); ?><input type="tel" name="seller_phone"></label>
					<label><?php esc_html_e( 'Preferred Contact', 'algq-deal-intake' ); ?><select name="preferred_contact"><option value=""><?php esc_html_e( 'Select', 'algq-deal-intake' ); ?></option><option value="phone"><?php esc_html_e( 'Phone', 'algq-deal-intake' ); ?></option><option value="email"><?php esc_html_e( 'Email', 'algq-deal-intake' ); ?></option><option value="text"><?php esc_html_e( 'Text', 'algq-deal-intake' ); ?></option></select></label>
					<label class="algq-di-span-2"><?php esc_html_e( 'Property Address', 'algq-deal-intake' ); ?><span aria-hidden="true"> *</span><input type="text" name="address" required></label>
					<label><?php esc_html_e( 'City', 'algq-deal-intake' ); ?><span aria-hidden="true"> *</span><input type="text" name="city" required></label>
					<label><?php esc_html_e( 'State', 'algq-deal-intake' ); ?><span aria-hidden="true"> *</span><input type="text" name="state" maxlength="2" value="<?php echo esc_attr( $state ); ?>" required></label>
					<label><?php esc_html_e( 'ZIP Code', 'algq-deal-intake' ); ?><input type="text" name="postal_code" maxlength="20"></label>
					<label><?php esc_html_e( 'Property Type', 'algq-deal-intake' ); ?><select name="property_type"><option value=""><?php esc_html_e( 'Select', 'algq-deal-intake' ); ?></option><option value="single-family"><?php esc_html_e( 'Single-Family', 'algq-deal-intake' ); ?></option><option value="multifamily"><?php esc_html_e( 'Multifamily', 'algq-deal-intake' ); ?></option><option value="mixed-use"><?php esc_html_e( 'Mixed-Use', 'algq-deal-intake' ); ?></option><option value="commercial"><?php esc_html_e( 'Commercial', 'algq-deal-intake' ); ?></option><option value="land"><?php esc_html_e( 'Land', 'algq-deal-intake' ); ?></option><option value="other"><?php esc_html_e( 'Other', 'algq-deal-intake' ); ?></option></select></label>
					<label><?php esc_html_e( 'Asking Price', 'algq-deal-intake' ); ?><input type="text" inputmode="decimal" name="asking_price"></label>
					<label><?php esc_html_e( 'Desired Timeline', 'algq-deal-intake' ); ?><select name="timeline"><option value=""><?php esc_html_e( 'Select', 'algq-deal-intake' ); ?></option><option value="0-30-days"><?php esc_html_e( '0–30 days', 'algq-deal-intake' ); ?></option><option value="31-90-days"><?php esc_html_e( '31–90 days', 'algq-deal-intake' ); ?></option><option value="3-6-months"><?php esc_html_e( '3–6 months', 'algq-deal-intake' ); ?></option><option value="exploring"><?php esc_html_e( 'Exploring options', 'algq-deal-intake' ); ?></option></select></label>
					<label><?php esc_html_e( 'Lead Source', 'algq-deal-intake' ); ?><select name="lead_source"><option value="website"><?php esc_html_e( 'Website', 'algq-deal-intake' ); ?></option><option value="referral"><?php esc_html_e( 'Referral', 'algq-deal-intake' ); ?></option><option value="broker"><?php esc_html_e( 'Broker', 'algq-deal-intake' ); ?></option><option value="attorney"><?php esc_html_e( 'Attorney', 'algq-deal-intake' ); ?></option><option value="driving-for-dollars"><?php esc_html_e( 'Driving for Dollars', 'algq-deal-intake' ); ?></option><option value="property-stewardship"><?php esc_html_e( 'Property Stewardship', 'algq-deal-intake' ); ?></option><option value="other"><?php esc_html_e( 'Other', 'algq-deal-intake' ); ?></option></select></label>
					<?php if ( ! $quick ) : ?>
					<label class="algq-di-span-2"><?php esc_html_e( 'Property Condition and Situation', 'algq-deal-intake' ); ?><textarea name="condition_summary" rows="5"></textarea></label>
					<label class="algq-di-span-2"><?php esc_html_e( 'Reason for Exploring Options', 'algq-deal-intake' ); ?><textarea name="motivation" rows="3"></textarea></label>
					<?php endif; ?>
				</div>
				<?php if ( ! $internal ) : ?>
				<label class="algq-di-consent"><input type="checkbox" name="consent_accepted" value="1" required> <span><?php esc_html_e( 'I authorize Algonquian Real Estate LLC to contact me about this property submission. I understand this submission is informational and does not create an agency, fiduciary, legal, lending, appraisal, brokerage, or purchase obligation.', 'algq-deal-intake' ); ?></span></label>
				<?php else : ?>
				<label class="algq-di-consent"><input type="checkbox" name="consent_accepted" value="1" required> <span><?php esc_html_e( 'I confirm that the contact authorization or other lawful intake basis has been documented for this internal submission.', 'algq-deal-intake' ); ?></span></label>
				<?php endif; ?>
				<button type="submit" class="algq-di-submit"><?php echo esc_html( $quick ? __( 'Capture Lead', 'algq-deal-intake' ) : __( 'Submit Property', 'algq-deal-intake' ) ); ?></button>
			</form>
		</section>
		<?php
		return (string) ob_get_clean();
	}

	public static function homeowner_options(): string {
		return '<section class="algq-di-shell"><h2>' . esc_html__( 'Explore Your Property Options', 'algq-deal-intake' ) . '</h2><p>' . esc_html__( 'Algonquian Real Estate can review a direct sale, as-is sale, seller-financing discussion, continued ownership, property stewardship, or a future transition plan. Appropriate legal, tax, brokerage, lending, appraisal, and estate matters should be reviewed with licensed professionals.', 'algq-deal-intake' ) . '</p><p><a class="algq-di-submit" href="' . esc_url( get_permalink( absint( get_option( 'algq_di_submit_property_page_id' ) ) ) ) . '">' . esc_html__( 'Submit a Property', 'algq-deal-intake' ) . '</a></p></section>';
	}

	public static function seller_portal(): string {
		if ( ! is_user_logged_in() ) {
			return '<section class="algq-di-shell"><h2>' . esc_html__( 'Seller Portal', 'algq-deal-intake' ) . '</h2><p>' . esc_html__( 'Authentication is required. Seller record-level access must be granted before a submission can be displayed.', 'algq-deal-intake' ) . '</p></section>';
		}

		return '<section class="algq-di-shell"><h2>' . esc_html__( 'Seller Portal', 'algq-deal-intake' ) . '</h2><p>' . esc_html__( 'No seller submissions are displayed unless an authorized platform service confirms record-level access.', 'algq-deal-intake' ) . '</p></section>';
	}

	private static function thank_you_content(): string {
		return '[vc_column_text]<h1>Property Submission Received</h1><p>Thank you. Algonquian Real Estate LLC will review the information provided. This confirmation is not an offer, contract, valuation, or commitment to purchase.</p>[/vc_column_text]';
	}

	private static function plugin_overview_content(): string {
		return '[vc_column_text]<h1>Algonquian Deal Intake</h1><p>Authoritative seller-lead and property-submission intake for the Algonquian Real Estate Platform.</p><p><strong>Version:</strong> 2.0.0</p>[/vc_column_text]';
	}

	private static function getting_started_content(): string {
		return '[vc_column_text]<h1>Getting Started With Algonquian Deal Intake</h1><ol><li>Confirm the Platform Plugin is active.</li><li>Review privacy, consent, notification, and rate-limit settings.</li><li>Publish the generated property-submission page.</li><li>Submit a controlled test lead.</li><li>Review duplicate status and hand the accepted opportunity to Pipeline CRM.</li></ol>[/vc_column_text]';
	}

	private static function documentation_content(): string {
		return '[vc_column_text]<h1>Algonquian Deal Intake Documentation</h1><p>Shortcodes: <code>[algq_deal_intake_form]</code>, <code>[deal_intake_form_internal]</code>, <code>[deal_quick_capture]</code>, <code>[algq_homeowner_options]</code>, and <code>[algq_seller_portal]</code>.</p><p>Pipeline CRM becomes the canonical deal authority after an intake submission is accepted.</p>[/vc_column_text]';
	}
}
