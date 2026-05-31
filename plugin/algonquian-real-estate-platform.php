<?php
/**
 * Plugin Name: Algonquian Real Estate Platform
 * Plugin URI: https://algonquianrealestate.com
 * Description: Core acquisition, underwriting, buyer registration, and admin dashboard platform for Algonquian Real Estate LLC.
 * Version: 1.0.0-rc.1
 * Author: Onegodian
 * Text Domain: algonquian-real-estate-platform
 * Domain Path: /languages
 * Requires at least: 6.0
 * Requires PHP: 7.4
 *
 * @package AlgonquianRealEstatePlatform
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'ALGQ_PLATFORM_VERSION', '1.0.0-rc.1' );
define( 'ALGQ_PLATFORM_FILE', __FILE__ );
define( 'ALGQ_PLATFORM_DIR', plugin_dir_path( __FILE__ ) );
define( 'ALGQ_PLATFORM_URL', plugin_dir_url( __FILE__ ) );

/**
 * Main platform plugin class.
 */
final class ALGQ_Platform {

	/**
	 * Singleton instance.
	 *
	 * @var ALGQ_Platform|null
	 */
	private static $instance = null;

	/**
	 * Return singleton instance.
	 *
	 * @return ALGQ_Platform
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Constructor.
	 */
	private function __construct() {
		add_action( 'init', array( $this, 'register_shortcodes' ) );
		add_action( 'admin_menu', array( $this, 'register_admin_menu' ) );
		add_action( 'admin_post_algq_platform_save_settings', array( $this, 'handle_save_settings' ) );
		add_action( 'admin_post_algq_platform_clear_activity', array( $this, 'handle_clear_activity' ) );
	}

	/**
	 * Plugin activation tasks.
	 *
	 * @return void
	 */
	public static function activate() {
		global $wpdb;

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$charset_collate = $wpdb->get_charset_collate();
		$deals_table     = $wpdb->prefix . 'algq_deals';
		$buyers_table    = $wpdb->prefix . 'algq_buyers';
		$activity_table  = $wpdb->prefix . 'algq_activity_log';

		$sql = array();

		$sql[] = "CREATE TABLE {$deals_table} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			deal_uid varchar(64) NOT NULL,
			seller_name varchar(190) NOT NULL DEFAULT '',
			seller_email varchar(190) NOT NULL DEFAULT '',
			seller_phone varchar(80) NOT NULL DEFAULT '',
			property_address text NOT NULL,
			asking_price decimal(14,2) NOT NULL DEFAULT 0.00,
			repair_estimate decimal(14,2) NOT NULL DEFAULT 0.00,
			arv decimal(14,2) NOT NULL DEFAULT 0.00,
			status varchar(80) NOT NULL DEFAULT 'lead_captured',
			created_at datetime NOT NULL,
			updated_at datetime NOT NULL,
			PRIMARY KEY  (id),
			UNIQUE KEY deal_uid (deal_uid)
		) {$charset_collate};";

		$sql[] = "CREATE TABLE {$buyers_table} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			buyer_uid varchar(64) NOT NULL,
			buyer_name varchar(190) NOT NULL DEFAULT '',
			buyer_email varchar(190) NOT NULL DEFAULT '',
			buyer_phone varchar(80) NOT NULL DEFAULT '',
			criteria text NOT NULL,
			status varchar(80) NOT NULL DEFAULT 'registered',
			created_at datetime NOT NULL,
			updated_at datetime NOT NULL,
			PRIMARY KEY  (id),
			UNIQUE KEY buyer_uid (buyer_uid)
		) {$charset_collate};";

		$sql[] = "CREATE TABLE {$activity_table} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			object_type varchar(80) NOT NULL DEFAULT '',
			object_id bigint(20) unsigned NOT NULL DEFAULT 0,
			action varchar(120) NOT NULL DEFAULT '',
			message text NOT NULL,
			created_by bigint(20) unsigned NOT NULL DEFAULT 0,
			created_at datetime NOT NULL,
			PRIMARY KEY  (id),
			KEY object_lookup (object_type, object_id)
		) {$charset_collate};";

		foreach ( $sql as $statement ) {
			dbDelta( $statement );
		}

		add_option( 'algq_platform_version', ALGQ_PLATFORM_VERSION );
		add_option( 'algq_platform_release_status', '1.0.0 Release Candidate' );
		add_option( 'algq_platform_brand_colors', array(
			'blue' => '#2F4A6D',
			'gold' => '#C8A96A',
			'ink'  => '#111827',
			'bg'   => '#F9FAFB',
		) );
	}

	/**
	 * Register public shortcodes.
	 *
	 * @return void
	 */
	public function register_shortcodes() {
		add_shortcode( 'algq_seller_intake', array( $this, 'seller_intake_shortcode' ) );
		add_shortcode( 'algq_mao_calculator', array( $this, 'mao_calculator_shortcode' ) );
		add_shortcode( 'algq_buyer_registration', array( $this, 'buyer_registration_shortcode' ) );
		add_shortcode( 'algq_admin_dashboard', array( $this, 'admin_dashboard_shortcode' ) );
	}

	/**
	 * Register admin menu.
	 *
	 * @return void
	 */
	public function register_admin_menu() {
		add_menu_page(
			esc_html__( 'Algonquian Platform', 'algonquian-real-estate-platform' ),
			esc_html__( 'Algonquian', 'algonquian-real-estate-platform' ),
			'manage_options',
			'algq-platform',
			array( $this, 'render_admin_dashboard' ),
			'dashicons-building',
			26
		);

		add_submenu_page(
			'algq-platform',
			esc_html__( 'Platform Settings', 'algonquian-real-estate-platform' ),
			esc_html__( 'Settings', 'algonquian-real-estate-platform' ),
			'manage_options',
			'algq-platform-settings',
			array( $this, 'render_settings_page' )
		);
	}

	/**
	 * Seller intake shortcode.
	 *
	 * @return string
	 */
	public function seller_intake_shortcode() {
		ob_start();
		?>
		<div class="algq-platform-card">
			<h2><?php echo esc_html__( 'Seller Intake', 'algonquian-real-estate-platform' ); ?></h2>
			<form method="post">
				<?php wp_nonce_field( 'algq_seller_intake', 'algq_seller_intake_nonce' ); ?>
				<p><label><?php echo esc_html__( 'Seller Name', 'algonquian-real-estate-platform' ); ?><input type="text" name="seller_name" /></label></p>
				<p><label><?php echo esc_html__( 'Property Address', 'algonquian-real-estate-platform' ); ?><input type="text" name="property_address" /></label></p>
				<p><label><?php echo esc_html__( 'Asking Price', 'algonquian-real-estate-platform' ); ?><input type="number" step="0.01" name="asking_price" /></label></p>
				<p><button type="submit"><?php echo esc_html__( 'Submit Property', 'algonquian-real-estate-platform' ); ?></button></p>
			</form>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * MAO calculator shortcode.
	 *
	 * @return string
	 */
	public function mao_calculator_shortcode() {
		ob_start();
		?>
		<div class="algq-platform-card">
			<h2><?php echo esc_html__( 'MAO Calculator', 'algonquian-real-estate-platform' ); ?></h2>
			<p><?php echo esc_html__( 'Formula: ARV × 70% − repairs.', 'algonquian-real-estate-platform' ); ?></p>
			<form method="post">
				<?php wp_nonce_field( 'algq_mao_calculator', 'algq_mao_calculator_nonce' ); ?>
				<p><label><?php echo esc_html__( 'ARV', 'algonquian-real-estate-platform' ); ?><input type="number" step="0.01" name="arv" /></label></p>
				<p><label><?php echo esc_html__( 'Repairs', 'algonquian-real-estate-platform' ); ?><input type="number" step="0.01" name="repairs" /></label></p>
				<p><button type="submit"><?php echo esc_html__( 'Calculate', 'algonquian-real-estate-platform' ); ?></button></p>
			</form>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Buyer registration shortcode.
	 *
	 * @return string
	 */
	public function buyer_registration_shortcode() {
		ob_start();
		?>
		<div class="algq-platform-card">
			<h2><?php echo esc_html__( 'Buyer Registration', 'algonquian-real-estate-platform' ); ?></h2>
			<form method="post">
				<?php wp_nonce_field( 'algq_buyer_registration', 'algq_buyer_registration_nonce' ); ?>
				<p><label><?php echo esc_html__( 'Buyer Name', 'algonquian-real-estate-platform' ); ?><input type="text" name="buyer_name" /></label></p>
				<p><label><?php echo esc_html__( 'Email', 'algonquian-real-estate-platform' ); ?><input type="email" name="buyer_email" /></label></p>
				<p><label><?php echo esc_html__( 'Buying Criteria', 'algonquian-real-estate-platform' ); ?><textarea name="criteria"></textarea></label></p>
				<p><button type="submit"><?php echo esc_html__( 'Register Buyer', 'algonquian-real-estate-platform' ); ?></button></p>
			</form>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Admin dashboard shortcode.
	 *
	 * @return string
	 */
	public function admin_dashboard_shortcode() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return esc_html__( 'You do not have permission to view this dashboard.', 'algonquian-real-estate-platform' );
		}

		ob_start();
		?>
		<div class="algq-platform-card">
			<h2><?php echo esc_html__( 'Algonquian Admin Dashboard', 'algonquian-real-estate-platform' ); ?></h2>
			<p><?php echo esc_html__( 'Release Status: 1.0.0 Release Candidate', 'algonquian-real-estate-platform' ); ?></p>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Render admin dashboard.
	 *
	 * @return void
	 */
	public function render_admin_dashboard() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Insufficient permissions.', 'algonquian-real-estate-platform' ) );
		}

		?>
		<div class="wrap">
			<h1><?php echo esc_html__( 'Algonquian Real Estate Platform', 'algonquian-real-estate-platform' ); ?></h1>
			<p><strong><?php echo esc_html__( 'Release Status:', 'algonquian-real-estate-platform' ); ?></strong> <?php echo esc_html( get_option( 'algq_platform_release_status', '1.0.0 Release Candidate' ) ); ?></p>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="algq_platform_clear_activity" />
				<?php wp_nonce_field( 'algq_platform_clear_activity', 'algq_platform_nonce' ); ?>
				<?php submit_button( esc_html__( 'Clear Activity Log', 'algonquian-real-estate-platform' ), 'secondary' ); ?>
			</form>
		</div>
		<?php
	}

	/**
	 * Render settings page.
	 *
	 * @return void
	 */
	public function render_settings_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Insufficient permissions.', 'algonquian-real-estate-platform' ) );
		}

		$status = get_option( 'algq_platform_release_status', '1.0.0 Release Candidate' );
		?>
		<div class="wrap">
			<h1><?php echo esc_html__( 'Platform Settings', 'algonquian-real-estate-platform' ); ?></h1>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="algq_platform_save_settings" />
				<?php wp_nonce_field( 'algq_platform_save_settings', 'algq_platform_nonce' ); ?>
				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><label for="algq_platform_release_status"><?php echo esc_html__( 'Release Status', 'algonquian-real-estate-platform' ); ?></label></th>
						<td><input class="regular-text" id="algq_platform_release_status" name="release_status" type="text" value="<?php echo esc_attr( $status ); ?>" /></td>
					</tr>
				</table>
				<?php submit_button( esc_html__( 'Save Settings', 'algonquian-real-estate-platform' ) ); ?>
			</form>
		</div>
		<?php
	}

	/**
	 * Save settings handler.
	 *
	 * @return void
	 */
	public function handle_save_settings() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Insufficient permissions.', 'algonquian-real-estate-platform' ) );
		}

		check_admin_referer( 'algq_platform_save_settings', 'algq_platform_nonce' );

		$release_status = isset( $_POST['release_status'] ) ? sanitize_text_field( wp_unslash( $_POST['release_status'] ) ) : '1.0.0 Release Candidate';
		update_option( 'algq_platform_release_status', $release_status );

		wp_safe_redirect( admin_url( 'admin.php?page=algq-platform-settings&updated=1' ) );
		exit;
	}

	/**
	 * Clear activity handler.
	 *
	 * @return void
	 */
	public function handle_clear_activity() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Insufficient permissions.', 'algonquian-real-estate-platform' ) );
		}

		check_admin_referer( 'algq_platform_clear_activity', 'algq_platform_nonce' );

		global $wpdb;
		$table = $wpdb->prefix . 'algq_activity_log';
		$wpdb->query( "TRUNCATE TABLE {$table}" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		wp_safe_redirect( admin_url( 'admin.php?page=algq-platform&cleared=1' ) );
		exit;
	}
}

register_activation_hook( __FILE__, array( 'ALGQ_Platform', 'activate' ) );
ALGQ_Platform::instance();
