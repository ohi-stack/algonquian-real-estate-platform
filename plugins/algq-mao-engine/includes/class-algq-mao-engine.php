<?php
/**
 * Core class for Algonquian MAO Engine.
 *
 * @package Algonquian_MAO_Engine
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Main MAO Engine class.
 */
final class ALGQ_MAO_Engine {
	/**
	 * Singleton instance.
	 *
	 * @var ALGQ_MAO_Engine|null
	 */
	private static $instance = null;

	/**
	 * Deals table name.
	 *
	 * @var string
	 */
	private $deals_table;

	/**
	 * Underwriting table name.
	 *
	 * @var string
	 */
	private $underwriting_table;

	/**
	 * Return singleton instance.
	 *
	 * @return ALGQ_MAO_Engine
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Activation callback.
	 *
	 * @return void
	 */
	public static function activate() {
		self::create_tables();
		self::seed_options();
		self::create_pages();
		flush_rewrite_rules();
	}

	/**
	 * Deactivation callback.
	 *
	 * @return void
	 */
	public static function deactivate() {
		flush_rewrite_rules();
	}

	/**
	 * Constructor.
	 */
	private function __construct() {
		global $wpdb;

		$this->deals_table        = $wpdb->prefix . 'algq_deals';
		$this->underwriting_table = $wpdb->prefix . 'algq_underwriting';

		add_action( 'admin_menu', array( $this, 'register_admin_menu' ) );
		add_action( 'admin_init', array( $this, 'register_settings' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_assets' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
		add_action( 'admin_post_algq_mao_save_settings', array( $this, 'handle_settings_save' ) );
		add_action( 'admin_post_algq_mao_save_underwriting', array( $this, 'handle_underwriting_save' ) );
		add_action( 'admin_post_nopriv_algq_mao_save_underwriting', array( $this, 'handle_underwriting_save' ) );
		add_action( 'rest_api_init', array( $this, 'register_rest_routes' ) );

		add_shortcode( 'algq_mao_calculator', array( $this, 'render_calculator_shortcode' ) );
		add_shortcode( 'algq_mao_plugin_page', array( $this, 'render_plugin_page_shortcode' ) );
	}

	/**
	 * Create database tables.
	 *
	 * @return void
	 */
	private static function create_tables() {
		global $wpdb;

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$charset             = $wpdb->get_charset_collate();
		$deals_table         = $wpdb->prefix . 'algq_deals';
		$underwriting_table  = $wpdb->prefix . 'algq_underwriting';

		dbDelta(
			"CREATE TABLE {$deals_table} (
				id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
				deal_uid VARCHAR(64) NOT NULL,
				property_address TEXT NOT NULL,
				seller_name VARCHAR(190) DEFAULT '',
				seller_email VARCHAR(190) DEFAULT '',
				seller_phone VARCHAR(80) DEFAULT '',
				asking_price DECIMAL(14,2) DEFAULT 0,
				arv DECIMAL(14,2) DEFAULT 0,
				repairs DECIMAL(14,2) DEFAULT 0,
				strategy VARCHAR(40) DEFAULT 'wholesale',
				status VARCHAR(80) DEFAULT 'Lead Captured',
				notes LONGTEXT,
				created_at DATETIME NOT NULL,
				updated_at DATETIME NOT NULL,
				PRIMARY KEY (id),
				UNIQUE KEY deal_uid (deal_uid)
			) {$charset};"
		);

		dbDelta(
			"CREATE TABLE {$underwriting_table} (
				id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
				deal_id BIGINT UNSIGNED DEFAULT 0,
				strategy VARCHAR(40) DEFAULT 'wholesale',
				arv DECIMAL(14,2) DEFAULT 0,
				repairs DECIMAL(14,2) DEFAULT 0,
				holding_costs DECIMAL(14,2) DEFAULT 0,
				closing_costs DECIMAL(14,2) DEFAULT 0,
				desired_profit DECIMAL(14,2) DEFAULT 0,
				assignment_fee DECIMAL(14,2) DEFAULT 0,
				mao DECIMAL(14,2) DEFAULT 0,
				estimated_spread DECIMAL(14,2) DEFAULT 0,
				risk_flag VARCHAR(40) DEFAULT 'Review',
				formula_snapshot LONGTEXT,
				created_by BIGINT UNSIGNED DEFAULT 0,
				created_at DATETIME NOT NULL,
				PRIMARY KEY (id),
				KEY deal_id (deal_id),
				KEY risk_flag (risk_flag)
			) {$charset};"
		);
	}

	/**
	 * Seed default options.
	 *
	 * @return void
	 */
	private static function seed_options() {
		if ( false === get_option( 'algq_mao_assumptions' ) ) {
			add_option(
				'algq_mao_assumptions',
				array(
					'arv_multiplier'              => '0.70',
					'closing_cost_rate'           => '0.03',
					'default_holding_costs'       => '0',
					'default_desired_profit'      => '20000',
					'default_assignment_fee'      => '10000',
					'auto_move_to_underwriting'   => '1',
				)
			);
		}
	}

	/**
	 * Create system pages.
	 *
	 * @return void
	 */
	private static function create_pages() {
		$pages = array(
			'plugin/mao-engine'            => array( 'title' => 'Algonquian MAO Engine', 'content' => '[algq_mao_plugin_page]' ),
			'plugin/mao-engine/start'      => array( 'title' => 'MAO Engine Getting Started', 'content' => '[algq_mao_plugin_page view="start"]' ),
			'plugin/mao-engine/docs'       => array( 'title' => 'MAO Engine Documentation', 'content' => '[algq_mao_plugin_page view="docs"]' ),
			'plugin/mao-engine/calculator' => array( 'title' => 'MAO Calculator', 'content' => '[algq_mao_calculator]' ),
		);

		foreach ( $pages as $path => $page ) {
			if ( get_page_by_path( $path ) ) {
				continue;
			}

			wp_insert_post(
				array(
					'post_title'   => sanitize_text_field( $page['title'] ),
					'post_name'    => sanitize_title( basename( $path ) ),
					'post_content' => wp_kses_post( $page['content'] ),
					'post_status'  => 'publish',
					'post_type'    => 'page',
				)
			);
		}
	}

	/**
	 * Register admin menu.
	 *
	 * @return void
	 */
	public function register_admin_menu() {
		add_menu_page( 'MAO Engine', 'MAO Engine', 'manage_options', 'algq-mao-engine', array( $this, 'render_admin_dashboard' ), 'dashicons-calculator', 28 );
		add_submenu_page( 'algq-mao-engine', 'Underwriting', 'Underwriting', 'manage_options', 'algq-mao-underwriting', array( $this, 'render_admin_underwriting' ) );
		add_submenu_page( 'algq-mao-engine', 'Settings', 'Settings', 'manage_options', 'algq-mao-settings', array( $this, 'render_admin_settings' ) );
	}

	/**
	 * Register settings.
	 *
	 * @return void
	 */
	public function register_settings() {
		register_setting( 'algq_mao_settings', 'algq_mao_assumptions', array( $this, 'sanitize_assumptions' ) );
	}

	/**
	 * Enqueue assets.
	 *
	 * @return void
	 */
	public function enqueue_assets() {
		wp_enqueue_style( 'algq-mao-engine', ALGQ_MAO_ENGINE_URL . 'assets/css/algq-mao-engine.css', array(), ALGQ_MAO_ENGINE_VERSION );
		wp_enqueue_script( 'algq-mao-engine', ALGQ_MAO_ENGINE_URL . 'assets/js/algq-mao-engine.js', array(), ALGQ_MAO_ENGINE_VERSION, true );
	}

	/**
	 * Sanitize assumptions.
	 *
	 * @param array $input Raw input.
	 * @return array
	 */
	public function sanitize_assumptions( $input ) {
		$input = is_array( $input ) ? $input : array();

		return array(
			'arv_multiplier'            => (string) max( 0, min( 1, (float) ( $input['arv_multiplier'] ?? 0.70 ) ) ),
			'closing_cost_rate'         => (string) max( 0, min( 1, (float) ( $input['closing_cost_rate'] ?? 0.03 ) ) ),
			'default_holding_costs'     => (string) max( 0, (float) ( $input['default_holding_costs'] ?? 0 ) ),
			'default_desired_profit'    => (string) max( 0, (float) ( $input['default_desired_profit'] ?? 20000 ) ),
			'default_assignment_fee'    => (string) max( 0, (float) ( $input['default_assignment_fee'] ?? 10000 ) ),
			'auto_move_to_underwriting' => ! empty( $input['auto_move_to_underwriting'] ) ? '1' : '0',
		);
	}

	/**
	 * Get assumptions.
	 *
	 * @return array
	 */
	private function get_assumptions() {
		$defaults = array(
			'arv_multiplier'            => '0.70',
			'closing_cost_rate'         => '0.03',
			'default_holding_costs'     => '0',
			'default_desired_profit'    => '20000',
			'default_assignment_fee'    => '10000',
			'auto_move_to_underwriting' => '1',
		);

		return wp_parse_args( get_option( 'algq_mao_assumptions', array() ), $defaults );
	}

	/**
	 * Calculate MAO.
	 *
	 * @param array $data Calculation input.
	 * @return array
	 */
	public function calculate( $data ) {
		$assumptions    = $this->get_assumptions();
		$arv            = max( 0, (float) ( $data['arv'] ?? 0 ) );
		$repairs        = max( 0, (float) ( $data['repairs'] ?? 0 ) );
		$holding_costs  = max( 0, (float) ( $data['holding_costs'] ?? $assumptions['default_holding_costs'] ) );
		$desired_profit = max( 0, (float) ( $data['desired_profit'] ?? $assumptions['default_desired_profit'] ) );
		$assignment_fee = max( 0, (float) ( $data['assignment_fee'] ?? $assumptions['default_assignment_fee'] ) );
		$strategy       = sanitize_key( $data['strategy'] ?? 'wholesale' );

		$closing_costs = $arv * (float) $assumptions['closing_cost_rate'];
		$mao           = ( $arv * (float) $assumptions['arv_multiplier'] ) - $repairs - $holding_costs - $closing_costs - $desired_profit;

		if ( 'wholesale' === $strategy ) {
			$mao -= $assignment_fee;
		}

		$spread = max( 0, $arv - $repairs - $mao );
		$risk   = 'Acceptable';

		if ( $mao <= 0 || $repairs > ( $arv * 0.35 ) ) {
			$risk = 'High Risk';
		} elseif ( $spread < $desired_profit ) {
			$risk = 'Review';
		}

		return array(
			'arv'              => round( $arv, 2 ),
			'repairs'          => round( $repairs, 2 ),
			'holding_costs'    => round( $holding_costs, 2 ),
			'closing_costs'    => round( $closing_costs, 2 ),
			'desired_profit'   => round( $desired_profit, 2 ),
			'assignment_fee'   => round( $assignment_fee, 2 ),
			'strategy'         => $strategy,
			'mao'              => round( $mao, 2 ),
			'estimated_spread' => round( $spread, 2 ),
			'risk_flag'        => $risk,
			'assumptions'      => $assumptions,
		);
	}

	/**
	 * Save underwriting form submission.
	 *
	 * @return void
	 */
	public function handle_underwriting_save() {
		if ( ! isset( $_POST['algq_mao_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['algq_mao_nonce'] ) ), 'algq_mao_save_underwriting' ) ) {
			wp_die( esc_html__( 'Security check failed.', 'algq-mao-engine' ) );
		}

		$deal_id = isset( $_POST['deal_id'] ) ? absint( $_POST['deal_id'] ) : 0;
		$result  = $this->calculate(
			array(
				'arv'            => isset( $_POST['arv'] ) ? sanitize_text_field( wp_unslash( $_POST['arv'] ) ) : 0,
				'repairs'        => isset( $_POST['repairs'] ) ? sanitize_text_field( wp_unslash( $_POST['repairs'] ) ) : 0,
				'holding_costs'  => isset( $_POST['holding_costs'] ) ? sanitize_text_field( wp_unslash( $_POST['holding_costs'] ) ) : 0,
				'desired_profit' => isset( $_POST['desired_profit'] ) ? sanitize_text_field( wp_unslash( $_POST['desired_profit'] ) ) : 0,
				'assignment_fee' => isset( $_POST['assignment_fee'] ) ? sanitize_text_field( wp_unslash( $_POST['assignment_fee'] ) ) : 0,
				'strategy'       => isset( $_POST['strategy'] ) ? sanitize_key( wp_unslash( $_POST['strategy'] ) ) : 'wholesale',
			)
		);

		$this->save_underwriting( $deal_id, $result );

		wp_safe_redirect( add_query_arg( 'algq_mao_saved', '1', wp_get_referer() ? wp_get_referer() : home_url( '/' ) ) );
		exit;
	}

	/**
	 * Save underwriting record.
	 *
	 * @param int   $deal_id Deal ID.
	 * @param array $result Calculation result.
	 * @return int|false
	 */
	private function save_underwriting( $deal_id, $result ) {
		global $wpdb;

		$inserted = $wpdb->insert(
			$this->underwriting_table,
			array(
				'deal_id'          => absint( $deal_id ),
				'strategy'         => sanitize_key( $result['strategy'] ),
				'arv'              => (float) $result['arv'],
				'repairs'          => (float) $result['repairs'],
				'holding_costs'    => (float) $result['holding_costs'],
				'closing_costs'    => (float) $result['closing_costs'],
				'desired_profit'   => (float) $result['desired_profit'],
				'assignment_fee'   => (float) $result['assignment_fee'],
				'mao'              => (float) $result['mao'],
				'estimated_spread' => (float) $result['estimated_spread'],
				'risk_flag'        => sanitize_text_field( $result['risk_flag'] ),
				'formula_snapshot' => wp_json_encode( $result['assumptions'] ),
				'created_by'       => get_current_user_id(),
				'created_at'       => current_time( 'mysql' ),
			),
			array( '%d', '%s', '%f', '%f', '%f', '%f', '%f', '%f', '%f', '%f', '%s', '%s', '%d', '%s' )
		);

		if ( $inserted && $deal_id && '1' === $this->get_assumptions()['auto_move_to_underwriting'] ) {
			$wpdb->update(
				$this->deals_table,
				array( 'status' => 'Underwriting', 'updated_at' => current_time( 'mysql' ) ),
				array( 'id' => absint( $deal_id ) ),
				array( '%s', '%s' ),
				array( '%d' )
			);
		}

		return $inserted ? (int) $wpdb->insert_id : false;
	}

	/**
	 * Register REST routes.
	 *
	 * @return void
	 */
	public function register_rest_routes() {
		register_rest_route(
			'algq/v1',
			'/mao/calculate',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'rest_calculate' ),
				'permission_callback' => '__return_true',
			)
		);
	}

	/**
	 * REST calculation callback.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response
	 */
	public function rest_calculate( $request ) {
		$params = $request->get_json_params();
		$params = is_array( $params ) ? $params : array();

		return rest_ensure_response( $this->calculate( $params ) );
	}

	/**
	 * Render calculator shortcode.
	 *
	 * @return string
	 */
	public function render_calculator_shortcode() {
		$assumptions = $this->get_assumptions();
		ob_start();
		?>
		<div class="algq-mao-wrap">
			<form class="algq-mao-card" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="algq_mao_save_underwriting" />
				<?php wp_nonce_field( 'algq_mao_save_underwriting', 'algq_mao_nonce' ); ?>
				<h2><?php echo esc_html__( 'MAO Calculator', 'algq-mao-engine' ); ?></h2>
				<label><?php echo esc_html__( 'Deal ID', 'algq-mao-engine' ); ?><input type="number" name="deal_id" min="0" /></label>
				<label><?php echo esc_html__( 'After Repair Value', 'algq-mao-engine' ); ?><input type="number" step="0.01" name="arv" class="algq-mao-input" required /></label>
				<label><?php echo esc_html__( 'Repair Estimate', 'algq-mao-engine' ); ?><input type="number" step="0.01" name="repairs" class="algq-mao-input" required /></label>
				<label><?php echo esc_html__( 'Holding Costs', 'algq-mao-engine' ); ?><input type="number" step="0.01" name="holding_costs" class="algq-mao-input" value="<?php echo esc_attr( $assumptions['default_holding_costs'] ); ?>" /></label>
				<label><?php echo esc_html__( 'Desired Profit', 'algq-mao-engine' ); ?><input type="number" step="0.01" name="desired_profit" class="algq-mao-input" value="<?php echo esc_attr( $assumptions['default_desired_profit'] ); ?>" /></label>
				<label><?php echo esc_html__( 'Assignment Fee', 'algq-mao-engine' ); ?><input type="number" step="0.01" name="assignment_fee" class="algq-mao-input" value="<?php echo esc_attr( $assumptions['default_assignment_fee'] ); ?>" /></label>
				<label><?php echo esc_html__( 'Strategy', 'algq-mao-engine' ); ?><select name="strategy" class="algq-mao-input"><option value="wholesale">Wholesale</option><option value="flip">Flip</option><option value="rental">Rental</option></select></label>
				<button type="button" class="button algq-mao-calc-button"><?php echo esc_html__( 'Calculate', 'algq-mao-engine' ); ?></button>
				<button type="submit" class="button button-primary"><?php echo esc_html__( 'Save Underwriting', 'algq-mao-engine' ); ?></button>
				<div class="algq-mao-result" aria-live="polite"></div>
			</form>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Render plugin page shortcode.
	 *
	 * @param array $atts Attributes.
	 * @return string
	 */
	public function render_plugin_page_shortcode( $atts ) {
		$atts = shortcode_atts( array( 'view' => 'overview' ), $atts, 'algq_mao_plugin_page' );
		$view = sanitize_key( $atts['view'] );

		if ( 'start' === $view ) {
			return '<div class="algq-mao-page"><h1>Getting Started: MAO Engine</h1><ol><li>Activate the plugin.</li><li>Review formula settings.</li><li>Open the calculator.</li><li>Enter ARV, repairs, costs, and strategy.</li><li>Save underwriting to the deal record.</li></ol></div>';
		}

		if ( 'docs' === $view ) {
			return '<div class="algq-mao-page"><h1>MAO Engine Documentation</h1><p><strong>Formula:</strong> MAO = (ARV × multiplier) - repairs - holding costs - closing costs - desired profit. Wholesale mode also subtracts assignment fee.</p><p><strong>Shortcode:</strong> [algq_mao_calculator]</p></div>';
		}

		return '<div class="algq-mao-page"><h1>Algonquian MAO Engine</h1><p>Version 1.0.0 | By Onegodian | Algonquian Real Estate</p><p>Calculates maximum allowable offer, estimated spread, profit assumptions, and risk flags for acquisition underwriting.</p><p><a href="/plugin/mao-engine/calculator">Open Calculator</a></p></div>';
	}

	/**
	 * Render admin dashboard.
	 *
	 * @return void
	 */
	public function render_admin_dashboard() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Insufficient permissions.', 'algq-mao-engine' ) );
		}

		global $wpdb;
		$total = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$this->underwriting_table}" );
		$high  = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$this->underwriting_table} WHERE risk_flag = %s", 'High Risk' ) );
		$avg   = (float) $wpdb->get_var( "SELECT AVG(mao) FROM {$this->underwriting_table}" );
		?>
		<div class="wrap algq-mao-admin">
			<h1><?php echo esc_html__( 'Algonquian MAO Engine', 'algq-mao-engine' ); ?></h1>
			<div class="algq-mao-kpis">
				<div><strong><?php echo esc_html__( 'Underwritten Deals', 'algq-mao-engine' ); ?></strong><span><?php echo esc_html( number_format_i18n( $total ) ); ?></span></div>
				<div><strong><?php echo esc_html__( 'Average MAO', 'algq-mao-engine' ); ?></strong><span><?php echo esc_html( '$' . number_format_i18n( $avg, 0 ) ); ?></span></div>
				<div><strong><?php echo esc_html__( 'High Risk', 'algq-mao-engine' ); ?></strong><span><?php echo esc_html( number_format_i18n( $high ) ); ?></span></div>
			</div>
		</div>
		<?php
	}

	/**
	 * Render underwriting list.
	 *
	 * @return void
	 */
	public function render_admin_underwriting() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Insufficient permissions.', 'algq-mao-engine' ) );
		}

		global $wpdb;
		$rows = $wpdb->get_results( "SELECT * FROM {$this->underwriting_table} ORDER BY created_at DESC LIMIT 100" );
		?>
		<div class="wrap">
			<h1><?php echo esc_html__( 'Underwriting Records', 'algq-mao-engine' ); ?></h1>
			<table class="widefat striped">
				<thead><tr><th>ID</th><th>Deal</th><th>Strategy</th><th>ARV</th><th>Repairs</th><th>MAO</th><th>Spread</th><th>Risk</th><th>Date</th></tr></thead>
				<tbody>
				<?php foreach ( $rows as $row ) : ?>
					<tr>
						<td><?php echo esc_html( $row->id ); ?></td>
						<td><?php echo esc_html( $row->deal_id ); ?></td>
						<td><?php echo esc_html( $row->strategy ); ?></td>
						<td><?php echo esc_html( '$' . number_format_i18n( (float) $row->arv, 0 ) ); ?></td>
						<td><?php echo esc_html( '$' . number_format_i18n( (float) $row->repairs, 0 ) ); ?></td>
						<td><?php echo esc_html( '$' . number_format_i18n( (float) $row->mao, 0 ) ); ?></td>
						<td><?php echo esc_html( '$' . number_format_i18n( (float) $row->estimated_spread, 0 ) ); ?></td>
						<td><?php echo esc_html( $row->risk_flag ); ?></td>
						<td><?php echo esc_html( $row->created_at ); ?></td>
					</tr>
				<?php endforeach; ?>
				</tbody>
			</table>
		</div>
		<?php
	}

	/**
	 * Render settings page.
	 *
	 * @return void
	 */
	public function render_admin_settings() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Insufficient permissions.', 'algq-mao-engine' ) );
		}

		$options = $this->get_assumptions();
		?>
		<div class="wrap">
			<h1><?php echo esc_html__( 'MAO Settings', 'algq-mao-engine' ); ?></h1>
			<form method="post" action="options.php">
				<?php settings_fields( 'algq_mao_settings' ); ?>
				<table class="form-table" role="presentation">
					<?php foreach ( $options as $key => $value ) : ?>
						<tr>
							<th scope="row"><label for="algq_mao_<?php echo esc_attr( $key ); ?>"><?php echo esc_html( ucwords( str_replace( '_', ' ', $key ) ) ); ?></label></th>
							<td><input id="algq_mao_<?php echo esc_attr( $key ); ?>" name="algq_mao_assumptions[<?php echo esc_attr( $key ); ?>]" value="<?php echo esc_attr( $value ); ?>" class="regular-text" /></td>
						</tr>
					<?php endforeach; ?>
				</table>
				<?php submit_button(); ?>
			</form>
		</div>
		<?php
	}
}
