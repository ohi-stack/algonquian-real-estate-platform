<?php
/** Admin pages, authorized persistence, and calculator rendering. */
defined( 'ABSPATH' ) || exit;

final class ALGQ_MAO_Admin {
	private $engine; private $calculator;
	public function __construct( $engine, $calculator ) {
		$this->engine = $engine; $this->calculator = $calculator;
		add_action( 'admin_menu', array( $this, 'menu' ) );
		add_action( 'admin_init', array( $this, 'settings' ) );
		add_action( 'admin_post_algq_mao_save_underwriting', array( $this, 'save' ) );
		add_action( 'admin_post_algq_mao_approve_underwriting', array( $this, 'approve' ) );
	}
	public function menu() {
		add_menu_page( 'Algonquian MAO Engine', 'MAO Engine', 'view_algq_underwriting', 'algq-mao-engine', array( $this, 'dashboard' ), 'dashicons-calculator', 28 );
		add_submenu_page( 'algq-mao-engine', 'Underwriting Scenarios', 'Scenarios', 'view_algq_underwriting', 'algq-mao-underwriting', array( $this, 'scenarios' ) );
		add_submenu_page( 'algq-mao-engine', 'MAO Calculator', 'Calculator', 'manage_algq_underwriting', 'algq-mao-calculator', array( $this, 'calculator_page' ) );
		add_submenu_page( 'algq-mao-engine', 'MAO Settings', 'Settings', 'manage_algq_mao_settings', 'algq-mao-settings', array( $this, 'settings_page' ) );
	}
	public function settings() { register_setting( 'algq_mao_settings', ALGQ_MAO_Calculator::OPTION_KEY, array( 'type' => 'array', 'sanitize_callback' => array( $this->calculator, 'sanitize_assumptions' ), 'default' => ALGQ_MAO_Calculator::defaults() ) ); }
	public function save() {
		if ( ! current_user_can( 'manage_algq_underwriting' ) ) { wp_die( esc_html__( 'You are not authorized to save underwriting.', 'algq-mao-engine' ), '', array( 'response' => 403 ) ); }
		check_admin_referer( 'algq_mao_save_underwriting', 'algq_mao_nonce' );
		$result = $this->calculator->calculate( $this->request( $_POST ) );
		$id = $this->engine->persist( isset( $_POST['deal_id'] ) ? absint( $_POST['deal_id'] ) : 0, isset( $_POST['scenario_name'] ) ? sanitize_text_field( wp_unslash( $_POST['scenario_name'] ) ) : '', $result, get_current_user_id() );
		wp_safe_redirect( add_query_arg( $id ? 'algq_mao_saved' : 'algq_mao_error', '1', wp_get_referer() ?: admin_url( 'admin.php?page=algq-mao-underwriting' ) ) ); exit;
	}
	public function approve() {
		if ( ! current_user_can( 'approve_algq_underwriting' ) ) { wp_die( esc_html__( 'You are not authorized to approve underwriting.', 'algq-mao-engine' ), '', array( 'response' => 403 ) ); }
		$id = isset( $_GET['underwriting_id'] ) ? absint( $_GET['underwriting_id'] ) : 0; check_admin_referer( 'algq_mao_approve_' . $id );
		if ( ALGQ_MAO_Database::approve( $id, get_current_user_id() ) ) { $record = ALGQ_MAO_Database::get( $id ); $this->engine->audit( 'underwriting_approved', array( 'underwriting_id' => $id, 'deal_id' => $record ? (int) $record->deal_id : 0 ), true ); do_action( 'algq_mao_underwriting_approved', $id, $record ); }
		wp_safe_redirect( admin_url( 'admin.php?page=algq-mao-underwriting' ) ); exit;
	}
	public function dashboard() {
		$this->cap( 'view_algq_underwriting' ); $m = ALGQ_MAO_Database::metrics();
		echo '<div class="wrap algq-mao-admin"><h1>Algonquian MAO Engine</h1><p>Authoritative acquisition underwriting, including seller-financing debt analysis and comparison scenarios.</p><div class="algq-mao-kpis">';
		foreach ( array( 'Scenarios' => $m['total'], 'Approved' => $m['approved'], 'Seller Financing' => $m['seller_financing'], 'High Risk' => $m['high_risk'] ) as $label => $value ) { echo '<div><strong>' . esc_html( $label ) . '</strong><span>' . esc_html( $value ) . '</span></div>'; }
		echo '</div></div>';
	}
	public function scenarios() {
		$this->cap( 'view_algq_underwriting' ); $rows = ALGQ_MAO_Database::recent();
		?><div class="wrap algq-mao-admin"><h1>Underwriting Scenarios</h1><?php if ( isset( $_GET['algq_mao_saved'] ) ) : ?><div class="notice notice-success"><p>Underwriting scenario saved as a draft.</p></div><?php endif; ?><table class="widefat striped algq-mao-table"><thead><tr><th>ID</th><th>Deal</th><th>Scenario</th><th>Strategy</th><th>MAO / Price</th><th>Profit / NOI / Cash Flow</th><th>Risk</th><th>Status</th><th>Created</th><th>Action</th></tr></thead><tbody><?php if ( ! $rows ) : ?><tr><td colspan="10">No scenarios saved.</td></tr><?php else : foreach ( $rows as $r ) : ?><tr><td><?php echo esc_html( $r->id ); ?></td><td><?php echo esc_html( $r->deal_id ?: '—' ); ?></td><td><?php echo esc_html( $r->scenario_name ?: 'Untitled' ); ?></td><td><?php echo esc_html( ucwords( str_replace( '_', ' ', $r->strategy ) ) ); ?></td><td><?php echo esc_html( '$' . number_format_i18n( 'seller_financing' === $r->strategy ? $r->purchase_price : $r->mao, 0 ) ); ?></td><td><?php if ( 'seller_financing' === $r->strategy ) { echo esc_html( '$' . number_format_i18n( $r->cash_flow, 0 ) . ' CF / ' . number_format_i18n( $r->dscr, 2 ) . 'x DSCR' ); } else { echo esc_html( in_array( $r->strategy, array( 'rental','multifamily' ), true ) ? '$' . number_format_i18n( $r->noi, 0 ) . ' NOI' : '$' . number_format_i18n( $r->projected_profit, 0 ) ); } ?></td><td><?php echo esc_html( $r->risk_flag ); ?></td><td><?php echo esc_html( ucfirst( $r->status ) ); ?></td><td><?php echo esc_html( $r->created_at ); ?></td><td><?php if ( 'draft' === $r->status && current_user_can( 'approve_algq_underwriting' ) ) : ?><a class="button" href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=algq_mao_approve_underwriting&underwriting_id=' . absint( $r->id ) ), 'algq_mao_approve_' . absint( $r->id ) ) ); ?>">Approve</a><?php else : ?>—<?php endif; ?></td></tr><?php endforeach; endif; ?></tbody></table></div><?php
	}
	public function calculator_page() { $this->cap( 'manage_algq_underwriting' ); echo '<div class="wrap"><h1>MAO & Financing Calculator</h1>' . $this->engine->calculator_markup() . '</div>'; }
	public function settings_page() {
		$this->cap( 'manage_algq_mao_settings' ); $o = $this->calculator->assumptions();
		$labels = array(
			'assumption_version','wholesale_arv_multiplier','wholesale_closing_rate','flip_selling_cost_rate','rental_vacancy_rate','rental_reserve_rate','rental_target_cap_rate','repair_risk_threshold','minimum_profit_margin','default_holding_costs','default_financing_costs','default_desired_profit','default_assignment_fee',
			'seller_default_interest_rate','seller_default_amortization_years','seller_default_balloon_years','seller_minimum_dscr','refinance_default_interest_rate','refinance_default_amortization','refinance_default_ltv','conventional_default_interest_rate','conventional_default_amortization','conventional_default_down_rate'
		);
		?><div class="wrap"><h1>MAO Formula Settings</h1><p>Changes apply only to new calculations. Saved scenarios retain their input, result, formula, and assumption snapshots.</p><form method="post" action="options.php"><?php settings_fields( 'algq_mao_settings' ); ?><table class="form-table"><?php foreach ( $labels as $key ) : ?><tr><th><label for="<?php echo esc_attr( $key ); ?>"><?php echo esc_html( ucwords( str_replace( '_', ' ', $key ) ) ); ?></label></th><td><input id="<?php echo esc_attr( $key ); ?>" class="regular-text" name="algq_mao_assumptions[<?php echo esc_attr( $key ); ?>]" value="<?php echo esc_attr( $o[ $key ] ); ?>" /></td></tr><?php endforeach; ?><tr><th>Pipeline Integration</th><td><label><input type="checkbox" name="algq_mao_assumptions[auto_request_stage_change]" value="1" <?php checked( '1', $o['auto_request_stage_change'] ); ?> /> Request stage changes after save or approval.</label></td></tr></table><?php submit_button(); ?></form></div><?php
	}
	private function request( $source ) {
		$out = array();
		foreach ( array(
			'strategy','arv','repairs','purchase_costs','holding_costs','financing_costs','selling_costs','desired_profit','assignment_fee','annual_gross_income','other_annual_income','annual_operating_expenses','annual_debt_service','target_cap_rate',
			'purchase_price','down_payment','seller_financed_principal','seller_interest_rate','seller_amortization_years','seller_balloon_years','seller_monthly_payment','refinance_value','refinance_interest_rate','refinance_amortization_years','refinance_ltv','conventional_down_payment','conventional_interest_rate','conventional_amortization_years'
		) as $key ) { $out[ $key ] = isset( $source[ $key ] ) ? sanitize_text_field( wp_unslash( $source[ $key ] ) ) : ''; }
		return $out;
	}
	private function cap( $cap ) { if ( ! current_user_can( $cap ) ) { wp_die( esc_html__( 'Insufficient permissions.', 'algq-mao-engine' ), '', array( 'response' => 403 ) ); } }
}
