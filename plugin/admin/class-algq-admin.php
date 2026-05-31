<?php
defined( 'ABSPATH' ) || exit;

class ALGQ_Admin {
	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'menu' ) );
		add_action( 'admin_init', array( __CLASS__, 'register_settings' ) );
	}

	public static function menu() {
		add_menu_page( 'Algonquian Real Estate', 'Algonquian RE', 'manage_options', 'algq-re', array( __CLASS__, 'dashboard' ), 'dashicons-building', 26 );
		add_submenu_page( 'algq-re', 'Dashboard', 'Dashboard', 'manage_options', 'algq-re', array( __CLASS__, 'dashboard' ) );
		add_submenu_page( 'algq-re', 'Deals', 'Deals', 'manage_options', 'algq-re-deals', array( __CLASS__, 'deals' ) );
		add_submenu_page( 'algq-re', 'Buyers', 'Buyers', 'manage_options', 'algq-re-buyers', array( __CLASS__, 'buyers' ) );
		add_submenu_page( 'algq-re', 'Offers', 'Offers', 'manage_options', 'algq-re-offers', array( __CLASS__, 'offers' ) );
		add_submenu_page( 'algq-re', 'Settings', 'Settings', 'manage_options', 'algq-re-settings', array( __CLASS__, 'settings' ) );
	}

	public static function register_settings() {
		register_setting( 'algq_re_settings', 'algq_re_settings', array( 'sanitize_callback' => array( __CLASS__, 'sanitize_settings' ) ) );
	}

	public static function sanitize_settings( $input ) {
		return array(
			'brand_name' => sanitize_text_field( $input['brand_name'] ?? 'Algonquian Real Estate, LLC' ),
			'primary_color' => sanitize_hex_color( $input['primary_color'] ?? '#002f5f' ),
			'accent_color' => sanitize_hex_color( $input['accent_color'] ?? '#c8a64b' ),
			'admin_email' => sanitize_email( $input['admin_email'] ?? get_option( 'admin_email' ) ),
			'keep_data_on_uninstall' => ! empty( $input['keep_data_on_uninstall'] ) ? 1 : 0,
		);
	}

	private static function count_rows( $table, $where = '' ) {
		global $wpdb;
		$table = esc_sql( $table );
		$sql = "SELECT COUNT(*) FROM {$table} {$where}";
		return (int) $wpdb->get_var( $sql );
	}

	private static function money( $value ) {
		return '$' . number_format_i18n( (float) $value, 0 );
	}

	public static function dashboard() {
		if ( ! current_user_can( 'manage_options' ) ) { wp_die( esc_html__( 'Unauthorized', 'algonquian-real-estate' ) ); }
		global $wpdb;
		$deals = ALGQ_Database::table( 'deals' );
		$buyers = ALGQ_Database::table( 'buyers' );
		$offers = ALGQ_Database::table( 'offers' );
		$activity = ALGQ_Database::table( 'activity_log' );
		$total_deals = self::count_rows( $deals );
		$total_buyers = self::count_rows( $buyers );
		$total_offers = self::count_rows( $offers );
		$pipeline_value = (float) $wpdb->get_var( "SELECT COALESCE(SUM(asking_price),0) FROM {$deals}" );
		$recent_deals = $wpdb->get_results( "SELECT deal_id, seller_name, property_address, asking_price, status, created_at FROM {$deals} ORDER BY id DESC LIMIT 8" );
		$recent_activity = $wpdb->get_results( "SELECT object_type, object_id, action, note, created_at FROM {$activity} ORDER BY id DESC LIMIT 8" );
		?>
		<div class="wrap algq-admin-wrap">
			<h1>Algonquian Real Estate Command Center</h1>
			<p class="algq-admin-subtitle">Acquisition, underwriting, offer generation, buyer activity, and transaction operations dashboard.</p>
			<div class="algq-admin-grid algq-admin-kpis">
				<?php self::kpi( 'Total Deals', $total_deals, 'Lead and acquisition records' ); ?>
				<?php self::kpi( 'Pipeline Value', self::money( $pipeline_value ), 'Aggregate asking price' ); ?>
				<?php self::kpi( 'Offers Generated', $total_offers, 'Creative and standard offers' ); ?>
				<?php self::kpi( 'Buyers Registered', $total_buyers, 'Buyer portal records' ); ?>
			</div>
			<div class="algq-admin-grid">
				<div class="algq-admin-panel algq-admin-span-8"><h2>Recent Deals</h2><?php self::deals_table( $recent_deals ); ?></div>
				<div class="algq-admin-panel algq-admin-span-4"><h2>Quick Actions</h2><?php self::quick_actions(); ?></div>
				<div class="algq-admin-panel algq-admin-span-12"><h2>Recent Activity</h2><?php self::activity_table( $recent_activity ); ?></div>
			</div>
		</div>
		<?php
	}

	private static function kpi( $label, $value, $note ) {
		echo '<div class="algq-admin-card"><span>' . esc_html( $label ) . '</span><strong>' . esc_html( (string) $value ) . '</strong><p>' . esc_html( $note ) . '</p></div>';
	}

	private static function deals_table( $rows ) {
		echo '<table class="widefat striped algq-admin-table"><thead><tr><th>Deal ID</th><th>Seller</th><th>Property</th><th>Price</th><th>Status</th></tr></thead><tbody>';
		if ( empty( $rows ) ) { echo '<tr><td colspan="5">No deals yet.</td></tr>'; }
		foreach ( (array) $rows as $row ) {
			echo '<tr><td>' . esc_html( $row->deal_id ) . '</td><td>' . esc_html( $row->seller_name ) . '</td><td>' . esc_html( wp_trim_words( $row->property_address, 8 ) ) . '</td><td>' . esc_html( self::money( $row->asking_price ) ) . '</td><td><span class="algq-status">' . esc_html( $row->status ) . '</span></td></tr>';
		}
		echo '</tbody></table>';
	}

	private static function activity_table( $rows ) {
		echo '<table class="widefat striped algq-admin-table"><thead><tr><th>Object</th><th>Action</th><th>Note</th><th>Date</th></tr></thead><tbody>';
		if ( empty( $rows ) ) { echo '<tr><td colspan="4">No activity yet.</td></tr>'; }
		foreach ( (array) $rows as $row ) {
			echo '<tr><td>' . esc_html( $row->object_type . ' #' . $row->object_id ) . '</td><td>' . esc_html( $row->action ) . '</td><td>' . esc_html( wp_trim_words( $row->note, 12 ) ) . '</td><td>' . esc_html( $row->created_at ) . '</td></tr>';
		}
		echo '</tbody></table>';
	}

	private static function quick_actions() {
		$actions = array(
			'Seller Intake Page' => home_url( '/sell-your-property/' ),
			'MAO Calculator' => home_url( '/mao-calculator/' ),
			'Offer Generator' => home_url( '/plugin/offer-generator/' ),
			'Buyer Registration' => home_url( '/buyers-register/' ),
		);
		echo '<div class="algq-actions">';
		foreach ( $actions as $label => $url ) { echo '<a class="button button-primary" href="' . esc_url( $url ) . '">' . esc_html( $label ) . '</a>'; }
		echo '</div>';
	}

	public static function deals() { self::dashboard(); }
	public static function buyers() { self::dashboard(); }
	public static function offers() { self::dashboard(); }

	public static function settings() {
		if ( ! current_user_can( 'manage_options' ) ) { wp_die( esc_html__( 'Unauthorized', 'algonquian-real-estate' ) ); }
		$options = wp_parse_args( get_option( 'algq_re_settings', array() ), array( 'brand_name' => 'Algonquian Real Estate, LLC', 'primary_color' => '#002f5f', 'accent_color' => '#c8a64b', 'admin_email' => get_option( 'admin_email' ), 'keep_data_on_uninstall' => 1 ) );
		?>
		<div class="wrap algq-admin-wrap"><h1>Algonquian RE Settings</h1><form method="post" action="options.php"><?php settings_fields( 'algq_re_settings' ); ?>
			<table class="form-table" role="presentation"><tbody>
			<tr><th>Brand Name</th><td><input class="regular-text" name="algq_re_settings[brand_name]" value="<?php echo esc_attr( $options['brand_name'] ); ?>"></td></tr>
			<tr><th>Primary Color</th><td><input type="text" name="algq_re_settings[primary_color]" value="<?php echo esc_attr( $options['primary_color'] ); ?>"></td></tr>
			<tr><th>Accent Color</th><td><input type="text" name="algq_re_settings[accent_color]" value="<?php echo esc_attr( $options['accent_color'] ); ?>"></td></tr>
			<tr><th>Admin Email</th><td><input class="regular-text" type="email" name="algq_re_settings[admin_email]" value="<?php echo esc_attr( $options['admin_email'] ); ?>"></td></tr>
			<tr><th>Uninstall</th><td><label><input type="checkbox" name="algq_re_settings[keep_data_on_uninstall]" value="1" <?php checked( $options['keep_data_on_uninstall'], 1 ); ?>> Keep platform data if plugin is uninstalled</label></td></tr>
			</tbody></table><?php submit_button(); ?></form></div>
		<?php
	}
}
