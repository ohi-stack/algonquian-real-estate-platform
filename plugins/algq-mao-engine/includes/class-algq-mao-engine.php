<?php
/** Main plugin orchestrator and public rendering. */
defined( 'ABSPATH' ) || exit;

final class ALGQ_MAO_Engine {
	private static $instance; private $calculator;
	public static function instance() { if ( ! self::$instance ) { self::$instance = new self(); } return self::$instance; }
	public static function activate() {
		global $wp_version;
		if ( version_compare( PHP_VERSION, '8.1', '<' ) || version_compare( (string) $wp_version, '6.5', '<' ) ) { deactivate_plugins( plugin_basename( ALGQ_MAO_ENGINE_FILE ) ); wp_die( 'Algonquian MAO Engine requires WordPress 6.5+ and PHP 8.1+.' ); }
		ALGQ_MAO_Database::install();
		update_option( ALGQ_MAO_Calculator::OPTION_KEY, wp_parse_args( get_option( ALGQ_MAO_Calculator::OPTION_KEY, array() ), ALGQ_MAO_Calculator::defaults() ), false );
		self::grant_caps(); ALGQ_MAO_Pages::install(); flush_rewrite_rules();
	}
	public static function deactivate() { flush_rewrite_rules(); }
	private static function grant_caps() {
		$map = array( 'administrator' => array( 'view_algq_underwriting','manage_algq_underwriting','approve_algq_underwriting','manage_algq_mao_settings' ), 'algq_acquisition_manager' => array( 'view_algq_underwriting','manage_algq_underwriting','approve_algq_underwriting' ), 'algq_analyst' => array( 'view_algq_underwriting','manage_algq_underwriting' ), 'algq_auditor' => array( 'view_algq_underwriting' ) );
		foreach ( $map as $name => $caps ) { if ( $role = get_role( $name ) ) { foreach ( $caps as $cap ) { $role->add_cap( $cap ); } } }
	}
	private function __construct() {
		$this->calculator = new ALGQ_MAO_Calculator();
		add_action( 'admin_init', array( $this, 'upgrade' ), 5 );
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_assets' ) ); add_action( 'wp_enqueue_scripts', array( $this, 'public_assets' ) );
		add_shortcode( 'algq_mao_calculator', array( $this, 'calculator_markup' ) ); add_shortcode( 'algq_mao_plugin_page', array( $this, 'plugin_page' ) );
		new ALGQ_MAO_REST( $this->calculator ); new ALGQ_MAO_Admin( $this, $this->calculator );
	}
	public function upgrade() { if ( ALGQ_MAO_ENGINE_SCHEMA_VERSION !== get_option( ALGQ_MAO_Database::SCHEMA_OPTION ) ) { ALGQ_MAO_Database::install(); self::grant_caps(); ALGQ_MAO_Pages::install(); } }
	public function calculate( $data ) { return $this->calculator->calculate( $data ); }
	public function assumptions() { return $this->calculator->assumptions(); }
	public function persist( $deal_id, $name, $result, $user_id = 0 ) {
		$id = ALGQ_MAO_Database::insert( $deal_id, $name, $result, $user_id );
		if ( ! $id ) { $this->audit( 'underwriting_save_failed', array( 'deal_id' => $deal_id ), false ); return false; }
		$result['deal_id'] = absint( $deal_id ); $this->audit( 'underwriting_saved', array( 'underwriting_id' => $id, 'deal_id' => $deal_id, 'strategy' => $result['strategy'], 'risk_flag' => $result['risk_flag'] ), true );
		do_action( 'algq_mao_underwriting_saved', $id, $result ); do_action( 'algq_mao_underwriting_saved_v2', $id, $deal_id, $result ); return $id;
	}
	public function save_system( $deal_id, $result, $name = 'Automated underwriting' ) { return $this->persist( absint( $deal_id ), sanitize_text_field( $name ), $result, 0 ); }
	public function latest( $deal_id, $approved = false ) { return ALGQ_MAO_Database::latest( $deal_id, $approved ); }
	public function health() { return array( 'status' => ALGQ_MAO_Database::exists() ? 'healthy' : 'failed', 'version' => ALGQ_MAO_ENGINE_VERSION, 'schema_version' => get_option( ALGQ_MAO_Database::SCHEMA_OPTION, '' ), 'table_exists' => ALGQ_MAO_Database::exists(), 'calculator_page' => (bool) get_page_by_path( 'plugin/mao-engine/calculator' ), 'seller_financing' => true ); }
	public function registry_payload() { return array( 'slug' => 'algq-mao-engine', 'name' => 'Algonquian MAO Engine', 'version' => ALGQ_MAO_ENGINE_VERSION, 'schema_version' => ALGQ_MAO_ENGINE_SCHEMA_VERSION, 'admin_page' => 'algq-mao-engine', 'rest_namespace' => 'algq/v1', 'health_callback' => array( $this, 'health' ), 'capabilities' => array( 'view_algq_underwriting','manage_algq_underwriting','approve_algq_underwriting','manage_algq_mao_settings' ) ); }
	public function audit( $event, $context, $success ) { $p = array( 'event' => sanitize_key( $event ), 'plugin' => 'algq-mao-engine', 'user_id' => get_current_user_id(), 'success' => (bool) $success, 'context' => is_array( $context ) ? $context : array() ); function_exists( 'algq_log_event' ) ? algq_log_event( $p ) : do_action( 'algq_audit_event', $p ); }
	public static function record_array( $r ) {
		return array(
			'id' => absint( $r->id ), 'uuid' => sanitize_text_field( $r->uuid ), 'deal_id' => absint( $r->deal_id ), 'scenario_name' => sanitize_text_field( $r->scenario_name ),
			'strategy' => sanitize_key( $r->strategy ), 'status' => sanitize_key( $r->status ), 'formula_version' => sanitize_text_field( $r->formula_version ), 'assumption_version' => sanitize_text_field( $r->assumption_version ),
			'mao' => (float) $r->mao, 'purchase_price' => (float) $r->purchase_price, 'down_payment' => (float) $r->down_payment, 'seller_financed_principal' => (float) $r->seller_financed_principal,
			'monthly_payment' => (float) $r->monthly_payment, 'annual_debt_service' => (float) $r->annual_debt_service, 'balloon_balance' => (float) $r->balloon_balance, 'total_debt_service' => (float) $r->total_debt_service,
			'estimated_spread' => (float) $r->estimated_spread, 'projected_profit' => (float) $r->projected_profit, 'noi' => (float) $r->noi, 'cap_rate' => (float) $r->cap_rate, 'dscr' => (float) $r->dscr, 'cash_flow' => (float) $r->cash_flow,
			'refinance_capacity' => (float) $r->refinance_capacity, 'refinance_gap' => (float) $r->refinance_gap, 'conventional_monthly_payment' => (float) $r->conventional_monthly_payment,
			'risk_flag' => sanitize_text_field( $r->risk_flag ), 'risk_reasons' => json_decode( (string) $r->risk_reasons, true ) ?: array(), 'created_at' => sanitize_text_field( $r->created_at ), 'approved_at' => sanitize_text_field( (string) $r->approved_at )
		);
	}
	public function admin_assets( $hook ) { if ( false !== strpos( (string) $hook, 'algq-mao' ) ) { $this->assets(); } }
	public function public_assets() { global $post; if ( $post instanceof WP_Post && ( has_shortcode( $post->post_content, 'algq_mao_calculator' ) || has_shortcode( $post->post_content, 'algq_mao_plugin_page' ) ) ) { $this->assets(); } }
	private function assets() { wp_enqueue_style( 'algq-mao-engine', ALGQ_MAO_ENGINE_URL . 'assets/css/algq-mao-engine.css', array(), ALGQ_MAO_ENGINE_VERSION ); wp_enqueue_script( 'algq-mao-engine', ALGQ_MAO_ENGINE_URL . 'assets/js/algq-mao-engine.js', array(), ALGQ_MAO_ENGINE_VERSION, true ); wp_localize_script( 'algq-mao-engine', 'algqMao', array( 'endpoint' => esc_url_raw( rest_url( 'algq/v1/mao/calculate' ) ), 'nonce' => is_user_logged_in() ? wp_create_nonce( 'wp_rest' ) : '', 'currency' => 'USD', 'labels' => array( 'calculating' => 'Calculating…', 'error' => 'The calculation could not be completed.' ) ) ); }
	public function calculator_markup() {
		$a = $this->assumptions(); $save = current_user_can( 'manage_algq_underwriting' ); ob_start(); ?>
		<div class="algq-mao-shell"><form class="algq-mao-card algq-mao-calculator" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>"><input type="hidden" name="action" value="algq_mao_save_underwriting" /><?php wp_nonce_field( 'algq_mao_save_underwriting', 'algq_mao_nonce' ); ?>
		<header><span>Acquisition Underwriting</span><h2>MAO & Financing Analysis</h2><p>Compare acquisition strategies using versioned assumptions. Seller-financing scenarios include debt service, balloon exposure, refinance assumptions, and conventional financing comparisons.</p></header>
		<div class="algq-mao-grid">
		<label>Strategy<select name="strategy"><option value="wholesale">Wholesale</option><option value="flip">Fix and Flip</option><option value="rental">Rental</option><option value="multifamily">Multifamily</option><option value="seller_financing">Seller Financing</option></select></label>
		<label>Scenario Name<input type="text" name="scenario_name" /></label><label>Deal ID<input type="number" name="deal_id" min="0" step="1" /></label>
		<?php
		$fields = array(
			'arv' => array( 'After Repair / Stabilized Value', 'wholesale,flip,rental,multifamily,seller_financing', '' ),
			'repairs' => array( 'Repairs', 'wholesale,flip,rental,multifamily,seller_financing', '' ),
			'purchase_costs' => array( 'Purchase / Closing Costs', 'wholesale,flip,rental,multifamily,seller_financing', '' ),
			'holding_costs' => array( 'Holding Costs', 'wholesale,flip,rental,multifamily,seller_financing', $a['default_holding_costs'] ),
			'financing_costs' => array( 'Financing Costs', 'wholesale,flip,rental,multifamily,seller_financing', $a['default_financing_costs'] ),
			'selling_costs' => array( 'Selling Costs', 'flip', '' ), 'desired_profit' => array( 'Desired Profit', 'wholesale,flip', $a['default_desired_profit'] ), 'assignment_fee' => array( 'Assignment Fee', 'wholesale', $a['default_assignment_fee'] ),
			'annual_gross_income' => array( 'Annual Gross Income', 'rental,multifamily,seller_financing', '' ), 'other_annual_income' => array( 'Other Annual Income', 'rental,multifamily,seller_financing', '' ), 'annual_operating_expenses' => array( 'Annual Operating Expenses', 'rental,multifamily,seller_financing', '' ), 'annual_debt_service' => array( 'Annual Debt Service', 'rental,multifamily', '' ), 'target_cap_rate' => array( 'Target Cap Rate', 'rental,multifamily', $a['rental_target_cap_rate'] ),
			'purchase_price' => array( 'Purchase Price', 'seller_financing', '' ), 'down_payment' => array( 'Down Payment', 'seller_financing', '' ), 'seller_financed_principal' => array( 'Seller-Financed Principal (blank = price - down)', 'seller_financing', '' ),
			'seller_interest_rate' => array( 'Seller Interest Rate (e.g. 6 or 0.06)', 'seller_financing', $a['seller_default_interest_rate'] ), 'seller_amortization_years' => array( 'Seller Amortization (Years)', 'seller_financing', $a['seller_default_amortization_years'] ), 'seller_balloon_years' => array( 'Balloon / Maturity (Years)', 'seller_financing', $a['seller_default_balloon_years'] ), 'seller_monthly_payment' => array( 'Monthly Payment Override', 'seller_financing', '' ),
			'refinance_value' => array( 'Refinance Value Assumption', 'seller_financing', '' ), 'refinance_interest_rate' => array( 'Refinance Interest Rate', 'seller_financing', $a['refinance_default_interest_rate'] ), 'refinance_amortization_years' => array( 'Refinance Amortization (Years)', 'seller_financing', $a['refinance_default_amortization'] ), 'refinance_ltv' => array( 'Refinance LTV (e.g. 75 or 0.75)', 'seller_financing', $a['refinance_default_ltv'] ),
			'conventional_down_payment' => array( 'Conventional Down Payment', 'seller_financing', '' ), 'conventional_interest_rate' => array( 'Conventional Interest Rate', 'seller_financing', $a['conventional_default_interest_rate'] ), 'conventional_amortization_years' => array( 'Conventional Amortization (Years)', 'seller_financing', $a['conventional_default_amortization'] ),
		);
		foreach ( $fields as $key => $config ) : ?>
		<label data-strategy-field="<?php echo esc_attr( $config[1] ); ?>"><?php echo esc_html( $config[0] ); ?><input type="number" name="<?php echo esc_attr( $key ); ?>" min="0" step="0.01" value="<?php echo esc_attr( $config[2] ); ?>" /></label>
		<?php endforeach; ?>
		</div><div class="algq-mao-actions"><button type="button" class="button button-primary algq-mao-calculate">Calculate Scenario</button><?php if ( $save ) : ?><button type="submit" class="button">Save Draft Underwriting</button><?php endif; ?></div><div class="algq-mao-result" aria-live="polite"><p>Analytical estimate only; not an appraisal, loan commitment, tax advice, legal advice, guarantee, or binding offer.</p></div></form></div>
		<?php return (string) ob_get_clean();
	}
	public function plugin_page( $atts ) {
		$v = sanitize_key( shortcode_atts( array( 'view' => 'overview' ), $atts )['view'] );
		if ( 'start' === $v ) { return '<section class="algq-mao-page"><h1>Getting Started With the Algonquian MAO Engine</h1><ol><li>Review assumptions and permissions.</li><li>Select wholesale, flip, rental, multifamily, or seller financing.</li><li>For seller financing, enter purchase price, down payment, note terms, balloon, operating income, refinance assumptions, and conventional comparison assumptions.</li><li>Review payment, DSCR, cash flow, balloon balance, refinance gap, sensitivity, and risk reasons.</li><li>Save a draft and obtain human approval before generating an offer.</li></ol></section>'; }
		if ( 'docs' === $v ) { return '<section class="algq-mao-page"><h1>MAO Engine Documentation</h1><p>Shortcode: <code>[algq_mao_calculator]</code></p><p>Pipeline CRM owns canonical deal records. The MAO Engine owns versioned underwriting scenarios and financing analyses. Seller-financing results may be consumed by the Offer Generator and Funding Tracker after approval.</p></section>'; }
		return '<section class="algq-mao-page"><h1>Algonquian MAO Engine</h1><p>Version ' . esc_html( ALGQ_MAO_ENGINE_VERSION ) . ' provides strategy formulas, seller-financing debt analysis, conventional financing comparison, sensitivity analysis, approval controls, and platform integrations.</p><a href="' . esc_url( home_url( '/plugin/mao-engine/calculator/' ) ) . '">Open Calculator</a></section>';
	}
}
