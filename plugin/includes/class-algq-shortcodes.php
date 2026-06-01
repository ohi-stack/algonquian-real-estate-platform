<?php
defined( 'ABSPATH' ) || exit;

class ALGQ_Shortcodes {
	public static function init() {
		add_shortcode( 'algq_seller_intake', array( __CLASS__, 'seller_intake' ) );
		add_shortcode( 'algq_mao_calculator', array( __CLASS__, 'mao_calculator' ) );
		add_shortcode( 'algq_buyer_registration', array( __CLASS__, 'buyer_registration' ) );
		add_shortcode( 'algq_admin_dashboard', array( __CLASS__, 'admin_dashboard' ) );
		add_shortcode( 'algq_offer_generator', array( __CLASS__, 'offer_generator' ) );
	}

	private static function notice( $message, $type = 'success' ) {
		return '<div class="algq-notice algq-' . esc_attr( $type ) . '">' . esc_html( $message ) . '</div>';
	}

	private static function deal_id() {
		return 'ALGQ-' . gmdate( 'Ymd' ) . '-' . wp_rand( 1000, 9999 );
	}

	public static function seller_intake() {
		wp_enqueue_style( 'algq-re-frontend' );
		global $wpdb;
		$out = '';
		if ( 'POST' === $_SERVER['REQUEST_METHOD'] && isset( $_POST['algq_seller_intake_nonce'] ) ) {
			if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['algq_seller_intake_nonce'] ) ), 'algq_seller_intake' ) ) {
				$out .= self::notice( 'Security check failed. Please try again.', 'error' );
			} else {
				$table = ALGQ_Database::table( 'deals' );
				$now = current_time( 'mysql' );
				$wpdb->insert( $table, array(
					'deal_id' => self::deal_id(),
					'seller_name' => sanitize_text_field( wp_unslash( $_POST['seller_name'] ?? '' ) ),
					'seller_email' => sanitize_email( wp_unslash( $_POST['seller_email'] ?? '' ) ),
					'seller_phone' => sanitize_text_field( wp_unslash( $_POST['seller_phone'] ?? '' ) ),
					'property_address' => sanitize_textarea_field( wp_unslash( $_POST['property_address'] ?? '' ) ),
					'asking_price' => (float) ( $_POST['asking_price'] ?? 0 ),
					'mortgage_balance' => (float) ( $_POST['mortgage_balance'] ?? 0 ),
					'monthly_payment' => (float) ( $_POST['monthly_payment'] ?? 0 ),
					'property_condition' => sanitize_text_field( wp_unslash( $_POST['property_condition'] ?? '' ) ),
					'condition_notes' => sanitize_textarea_field( wp_unslash( $_POST['condition_notes'] ?? '' ) ),
					'status' => 'Lead Captured',
					'created_at' => $now,
					'updated_at' => $now,
				) );
				$out .= self::notice( 'Property submitted. Algonquian Real Estate will review the opportunity.' );
			}
		}
		ob_start(); ?>
		<div class="algq-wrap"><div class="algq-hero"><div class="algq-eyebrow">Seller Intake</div><h2 class="algq-title">Submit a Property</h2><p class="algq-subtitle">Send property information directly into the Algonquian acquisition system.</p></div><?php echo $out; ?>
		<form method="post" class="algq-grid"><?php wp_nonce_field( 'algq_seller_intake', 'algq_seller_intake_nonce' ); ?>
			<div class="algq-card"><label class="algq-label">Seller Name</label><input class="algq-input" name="seller_name" required></div>
			<div class="algq-card"><label class="algq-label">Email</label><input class="algq-input" type="email" name="seller_email"></div>
			<div class="algq-card"><label class="algq-label">Phone</label><input class="algq-input" name="seller_phone"></div>
			<div class="algq-card algq-card-wide"><label class="algq-label">Property Address</label><textarea class="algq-textarea" name="property_address" required></textarea></div>
			<div class="algq-card"><label class="algq-label">Asking Price</label><input class="algq-input" type="number" step="0.01" name="asking_price"></div>
			<div class="algq-card"><label class="algq-label">Mortgage Balance</label><input class="algq-input" type="number" step="0.01" name="mortgage_balance"></div>
			<div class="algq-card"><label class="algq-label">Monthly Payment</label><input class="algq-input" type="number" step="0.01" name="monthly_payment"></div>
			<div class="algq-card"><label class="algq-label">Condition</label><select class="algq-select" name="property_condition"><option>Unknown</option><option>Excellent</option><option>Good</option><option>Needs Work</option><option>Distressed</option></select></div>
			<div class="algq-card algq-card-wide"><label class="algq-label">Notes</label><textarea class="algq-textarea" name="condition_notes"></textarea></div>
			<div class="algq-card algq-card-full"><button class="algq-btn algq-btn-primary" type="submit">Submit Property</button></div>
		</form></div><?php return ob_get_clean();
	}

	public static function mao_calculator() {
		wp_enqueue_style( 'algq-re-frontend' );
		wp_enqueue_script( 'algq-re-frontend' );
		return '<div class="algq-wrap"><div class="algq-hero"><div class="algq-eyebrow">Underwriting</div><h2 class="algq-title">MAO Calculator</h2><p class="algq-subtitle">Formula: MAO = (ARV × 70%) - repairs - costs - assignment fee.</p></div><div class="algq-grid"><div class="algq-card"><label class="algq-label">ARV</label><input class="algq-input algq-mao" id="algq_arv" type="number" value="300000"></div><div class="algq-card"><label class="algq-label">Repairs</label><input class="algq-input algq-mao" id="algq_repairs" type="number" value="40000"></div><div class="algq-card"><label class="algq-label">Costs + Fee</label><input class="algq-input algq-mao" id="algq_costs" type="number" value="15000"></div><div class="algq-card algq-card-full"><div class="algq-kpi"><div><span>Maximum Allowable Offer</span><strong id="algq_mao_result">$155,000</strong></div><span class="algq-badge">Live</span></div></div></div></div>';
	}

	public static function buyer_registration() {
		wp_enqueue_style( 'algq-re-frontend' );
		global $wpdb; $out = '';
		if ( 'POST' === $_SERVER['REQUEST_METHOD'] && isset( $_POST['algq_buyer_nonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['algq_buyer_nonce'] ) ), 'algq_buyer_registration' ) ) {
			$wpdb->insert( ALGQ_Database::table( 'buyers' ), array( 'buyer_name' => sanitize_text_field( wp_unslash( $_POST['buyer_name'] ?? '' ) ), 'buyer_email' => sanitize_email( wp_unslash( $_POST['buyer_email'] ?? '' ) ), 'buyer_phone' => sanitize_text_field( wp_unslash( $_POST['buyer_phone'] ?? '' ) ), 'markets' => sanitize_textarea_field( wp_unslash( $_POST['markets'] ?? '' ) ), 'property_types' => sanitize_textarea_field( wp_unslash( $_POST['property_types'] ?? '' ) ), 'cash_available' => (float) ( $_POST['cash_available'] ?? 0 ), 'nda_accepted' => ! empty( $_POST['nda_accepted'] ) ? 1 : 0, 'status' => 'Pending Review', 'created_at' => current_time( 'mysql' ), 'updated_at' => current_time( 'mysql' ) ) );
			$out = self::notice( 'Buyer registration submitted for review.' );
		}
		ob_start(); ?><div class="algq-wrap"><div class="algq-hero"><div class="algq-eyebrow">Buyer Portal</div><h2 class="algq-title">Register as a Buyer</h2><p class="algq-subtitle">Request access to future deal packages and buyer opportunities.</p></div><?php echo $out; ?><form method="post" class="algq-grid"><?php wp_nonce_field( 'algq_buyer_registration', 'algq_buyer_nonce' ); ?><div class="algq-card"><label class="algq-label">Name</label><input class="algq-input" name="buyer_name" required></div><div class="algq-card"><label class="algq-label">Email</label><input class="algq-input" type="email" name="buyer_email" required></div><div class="algq-card"><label class="algq-label">Phone</label><input class="algq-input" name="buyer_phone"></div><div class="algq-card"><label class="algq-label">Cash Available</label><input class="algq-input" type="number" name="cash_available"></div><div class="algq-card algq-card-wide"><label class="algq-label">Markets</label><textarea class="algq-textarea" name="markets"></textarea></div><div class="algq-card algq-card-wide"><label class="algq-label">Property Types</label><textarea class="algq-textarea" name="property_types"></textarea></div><div class="algq-card algq-card-full"><label><input type="checkbox" name="nda_accepted" value="1" required> I agree to NDA-gated deal access terms.</label><br><br><button class="algq-btn algq-btn-primary" type="submit">Submit Buyer Registration</button></div></form></div><?php return ob_get_clean();
	}

	public static function offer_generator() {
		wp_enqueue_style( 'algq-re-frontend' ); wp_enqueue_script( 'algq-re-frontend' );
		$s = ALGQ_Offer_Engine::scenario( 285000, 5000, 3, 50 );
		return '<div class="algq-wrap"><div class="algq-hero"><div class="algq-eyebrow">Creative Finance</div><h2 class="algq-title">Subject-To & Creative Offer Generator</h2><p class="algq-subtitle">Model higher-price, better-terms offers for motivated sellers.</p></div><div class="algq-grid"><div class="algq-card"><label class="algq-label">Purchase Price</label><input class="algq-input algq-offer" id="algq_price" value="285000" type="number"></div><div class="algq-card"><label class="algq-label">Down Payment</label><input class="algq-input algq-offer" id="algq_down" value="5000" type="number"></div><div class="algq-card"><label class="algq-label">Rate %</label><input class="algq-input algq-offer" id="algq_rate" value="3" type="number" step="0.1"></div><div class="algq-card"><label class="algq-label">Term Years</label><input class="algq-input algq-offer" id="algq_term" value="50" type="number"></div><div class="algq-card algq-card-wide"><div class="algq-kpi"><div><span>Monthly Payment</span><strong id="algq_payment">$' . esc_html( number_format_i18n( $s['monthly_payment'], 0 ) ) . '</strong></div><span class="algq-badge">Offer</span></div></div><div class="algq-card"><div class="algq-kpi"><div><span>Seller Total</span><strong id="algq_total">$' . esc_html( number_format_i18n( $s['seller_total'], 0 ) ) . '</strong></div></div></div></div></div>';
	}

	public static function admin_dashboard() {
		if ( ! current_user_can( 'manage_options' ) ) { return self::notice( 'This dashboard is restricted.', 'error' ); }
		ob_start(); ALGQ_Admin::dashboard(); return ob_get_clean();
	}
}
