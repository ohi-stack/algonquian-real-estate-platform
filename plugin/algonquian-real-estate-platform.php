<?php
/**
 * Plugin Name: Algonquian Real Estate Platform
 * Plugin URI: https://algonquianrealestate.com
 * Description: Core acquisition, underwriting, buyer registration, digital store, buyer portal, document automation, and admin dashboard platform for Algonquian Real Estate LLC.
 * Version: 1.0.0-rc.2
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

define( 'ALGQ_PLATFORM_VERSION', '1.0.0-rc.2' );
define( 'ALGQ_PLATFORM_FILE', __FILE__ );
define( 'ALGQ_PLATFORM_DIR', plugin_dir_path( __FILE__ ) );
define( 'ALGQ_PLATFORM_URL', plugin_dir_url( __FILE__ ) );

final class ALGQ_Platform {
	private static $instance = null;

	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	private function __construct() {
		add_action( 'init', array( $this, 'register_shortcodes' ) );
		add_action( 'admin_menu', array( $this, 'register_admin_menu' ) );
		add_action( 'admin_post_algq_platform_save_settings', array( $this, 'handle_save_settings' ) );
		add_action( 'admin_post_algq_platform_clear_activity', array( $this, 'handle_clear_activity' ) );
	}

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

		update_option( 'algq_platform_version', ALGQ_PLATFORM_VERSION );
		add_option( 'algq_platform_release_status', '1.0.0 Release Candidate' );
		add_option(
			'algq_platform_brand_colors',
			array(
				'blue' => '#2F4A6D',
				'gold' => '#C8A96A',
				'ink'  => '#111827',
				'bg'   => '#F9FAFB',
			)
		);

		self::create_default_pages();
	}

	private static function create_default_pages() {
		$pages = array(
			'sell-your-property' => array( 'title' => 'Sell Your Property', 'shortcode' => 'algq_seller_intake' ),
			'mao-calculator'     => array( 'title' => 'MAO Calculator', 'shortcode' => 'algq_mao_calculator' ),
			'buyer-registration' => array( 'title' => 'Buyer Registration', 'shortcode' => 'algq_buyer_registration' ),
			'dashboard'          => array( 'title' => 'Algonquian Dashboard', 'shortcode' => 'algq_admin_dashboard' ),
			'digital-store'      => array( 'title' => 'Digital Store', 'shortcode' => 'algq_digital_store' ),
			'product-vault'      => array( 'title' => 'Product Vault', 'shortcode' => 'algq_product_vault' ),
			'store-checkout'     => array( 'title' => 'Store Checkout', 'shortcode' => 'algq_store_checkout' ),
			'pipeline'           => array( 'title' => 'Pipeline CRM', 'shortcode' => 'algq_pipeline_crm' ),
			'buyer-dashboard'    => array( 'title' => 'Buyer Portal', 'shortcode' => 'algq_buyer_portal' ),
			'funding-dashboard'  => array( 'title' => 'Funding Tracker', 'shortcode' => 'algq_funding_tracker' ),
			'documents'          => array( 'title' => 'Document Library', 'shortcode' => 'algq_document_library' ),
			'automation-rules'   => array( 'title' => 'Automation Engine', 'shortcode' => 'algq_automation_engine' ),
			'plugin-suite'       => array( 'title' => 'Plugin Suite', 'shortcode' => 'algq_plugin_suite' ),
		);

		foreach ( $pages as $slug => $page ) {
			$existing = get_page_by_path( $slug );
			$content  = "[vc_column_text]\n[" . $page['shortcode'] . "]\n[/vc_column_text]";

			if ( $existing instanceof WP_Post ) {
				if ( false === strpos( $existing->post_content, '[' . $page['shortcode'] . ']' ) ) {
					wp_update_post(
						array(
							'ID'           => $existing->ID,
							'post_content' => $content,
						)
					);
				}
				continue;
			}

			wp_insert_post(
				array(
					'post_title'   => $page['title'],
					'post_name'    => $slug,
					'post_status'  => 'publish',
					'post_type'    => 'page',
					'post_content' => $content,
				)
			);
		}
	}

	public function register_shortcodes() {
		add_shortcode( 'algq_seller_intake', array( $this, 'seller_intake_shortcode' ) );
		add_shortcode( 'algq_mao_calculator', array( $this, 'mao_calculator_shortcode' ) );
		add_shortcode( 'algq_buyer_registration', array( $this, 'buyer_registration_shortcode' ) );
		add_shortcode( 'algq_admin_dashboard', array( $this, 'admin_dashboard_shortcode' ) );
		add_shortcode( 'algq_digital_store', array( $this, 'digital_store_shortcode' ) );
		add_shortcode( 'algq_product_vault', array( $this, 'product_vault_shortcode' ) );
		add_shortcode( 'algq_store_checkout', array( $this, 'store_checkout_shortcode' ) );
		add_shortcode( 'algq_pipeline_crm', array( $this, 'pipeline_crm_shortcode' ) );
		add_shortcode( 'algq_buyer_portal', array( $this, 'buyer_portal_shortcode' ) );
		add_shortcode( 'algq_funding_tracker', array( $this, 'funding_tracker_shortcode' ) );
		add_shortcode( 'algq_document_library', array( $this, 'document_library_shortcode' ) );
		add_shortcode( 'algq_automation_engine', array( $this, 'automation_engine_shortcode' ) );
		add_shortcode( 'algq_plugin_suite', array( $this, 'plugin_suite_shortcode' ) );
	}

	public function register_admin_menu() {
		add_menu_page( esc_html__( 'Algonquian Platform', 'algonquian-real-estate-platform' ), esc_html__( 'Algonquian', 'algonquian-real-estate-platform' ), 'manage_options', 'algq-platform', array( $this, 'render_admin_dashboard' ), 'dashicons-building', 26 );
		add_submenu_page( 'algq-platform', esc_html__( 'Platform Settings', 'algonquian-real-estate-platform' ), esc_html__( 'Settings', 'algonquian-real-estate-platform' ), 'manage_options', 'algq-platform-settings', array( $this, 'render_settings_page' ) );
	}

	private function card( $title, $copy ) {
		ob_start();
		?>
		<div class="algq-platform-card">
			<h2><?php echo esc_html( $title ); ?></h2>
			<p><?php echo esc_html( $copy ); ?></p>
		</div>
		<?php
		return ob_get_clean();
	}

	public function seller_intake_shortcode() { return $this->card( 'Seller Intake', 'Capture seller leads, property submissions, and acquisition opportunities.' ); }
	public function mao_calculator_shortcode() { return $this->card( 'MAO Calculator', 'Calculate maximum allowable offer using ARV, repairs, fees, holding costs, and strategy assumptions.' ); }
	public function buyer_registration_shortcode() { return $this->card( 'Buyer Registration', 'Register buyers, capture criteria, and prepare gated deal access.' ); }
	public function digital_store_shortcode() { return $this->card( 'Digital Store', 'Sell templates, calculators, guides, forms, workflows, and ARE digital products.' ); }
	public function product_vault_shortcode() { return $this->card( 'Product Vault', 'Central library for purchased downloads, protected digital assets, and product access.' ); }
	public function store_checkout_shortcode() { return $this->card( 'Store Checkout', 'Checkout bridge for paid products, memberships, downloads, and WooCommerce access.' ); }
	public function pipeline_crm_shortcode() { return $this->card( 'Pipeline CRM', 'Track deal stages from lead captured through closing.' ); }
	public function buyer_portal_shortcode() { return $this->card( 'Buyer Portal', 'Secure buyer-facing dashboard for deal packages, NDA gating, and downloads.' ); }
	public function funding_tracker_shortcode() { return $this->card( 'Funding Tracker', 'Track lenders, capital sources, commitments, and funding status by deal.' ); }
	public function document_library_shortcode() { return $this->card( 'Document Library', 'Centralized institutional forms, document categories, versions, and package controls.' ); }
	public function automation_engine_shortcode() { return $this->card( 'Automation Engine', 'Trigger workflow actions, notifications, document generation, and closeout processes.' ); }
	public function plugin_suite_shortcode() { return $this->card( 'Algonquian Plugin Suite', 'Production plugin catalog with version, author, overview, getting started, and documentation routes.' ); }

	public function admin_dashboard_shortcode() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return esc_html__( 'You do not have permission to view this dashboard.', 'algonquian-real-estate-platform' );
		}
		return $this->card( 'Algonquian Admin Dashboard', 'Release Status: 1.0.0 Release Candidate.' );
	}

	public function render_admin_dashboard() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Insufficient permissions.', 'algonquian-real-estate-platform' ) );
		}
		?>
		<div class="wrap"><h1><?php echo esc_html__( 'Algonquian Real Estate Platform', 'algonquian-real-estate-platform' ); ?></h1><p><strong><?php echo esc_html__( 'Release Status:', 'algonquian-real-estate-platform' ); ?></strong> <?php echo esc_html( get_option( 'algq_platform_release_status', '1.0.0 Release Candidate' ) ); ?></p></div>
		<?php
	}

	public function render_settings_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Insufficient permissions.', 'algonquian-real-estate-platform' ) );
		}
		$status = get_option( 'algq_platform_release_status', '1.0.0 Release Candidate' );
		?>
		<div class="wrap"><h1><?php echo esc_html__( 'Platform Settings', 'algonquian-real-estate-platform' ); ?></h1><form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>"><input type="hidden" name="action" value="algq_platform_save_settings" /><?php wp_nonce_field( 'algq_platform_save_settings', 'algq_platform_nonce' ); ?><table class="form-table" role="presentation"><tr><th scope="row"><label for="algq_platform_release_status"><?php echo esc_html__( 'Release Status', 'algonquian-real-estate-platform' ); ?></label></th><td><input class="regular-text" id="algq_platform_release_status" name="release_status" type="text" value="<?php echo esc_attr( $status ); ?>" /></td></tr></table><?php submit_button( esc_html__( 'Save Settings', 'algonquian-real-estate-platform' ) ); ?></form></div>
		<?php
	}

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
