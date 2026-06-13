<?php
/**
 * Plugin Name: Algonquian Real Estate Platform
 * Plugin URI: https://algonquianrealestate.com
 * Description: Core acquisition, underwriting, buyer registration, digital store, buyer portal, tenant services, document automation, and admin dashboard platform for Algonquian Real Estate LLC.
 * Version: 1.0.0-rc.4
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

define( 'ALGQ_PLATFORM_VERSION', '1.0.0-rc.4' );
define( 'ALGQ_PLATFORM_FILE', __FILE__ );
define( 'ALGQ_PLATFORM_DIR', plugin_dir_path( __FILE__ ) );
define( 'ALGQ_PLATFORM_URL', plugin_dir_url( __FILE__ ) );

require_once ALGQ_PLATFORM_DIR . 'includes/class-page-generator.php';

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
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_public_assets' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );
		add_action( 'admin_menu', array( $this, 'register_admin_menu' ) );
		add_action( 'admin_post_algq_platform_save_settings', array( $this, 'handle_save_settings' ) );
		add_action( 'admin_post_algq_platform_clear_activity', array( $this, 'handle_clear_activity' ) );
	}

	public function enqueue_public_assets() {
		$css_path = ALGQ_PLATFORM_DIR . 'assets/css/algq-platform.css';
		$js_path  = ALGQ_PLATFORM_DIR . 'assets/js/algq-platform.js';

		if ( file_exists( $css_path ) ) {
			wp_enqueue_style( 'algq-platform', ALGQ_PLATFORM_URL . 'assets/css/algq-platform.css', array(), filemtime( $css_path ) );
		}

		if ( file_exists( $js_path ) ) {
			wp_enqueue_script( 'algq-platform', ALGQ_PLATFORM_URL . 'assets/js/algq-platform.js', array(), filemtime( $js_path ), true );
			wp_localize_script(
				'algq-platform',
				'ALGQPlatform',
				array(
					'version' => ALGQ_PLATFORM_VERSION,
					'homeUrl' => esc_url_raw( home_url( '/' ) ),
					'ajaxUrl' => esc_url_raw( admin_url( 'admin-ajax.php' ) ),
					'nonce'   => wp_create_nonce( 'algq_platform_public' ),
				)
			);
		}
	}

	public function enqueue_admin_assets( $hook_suffix ) {
		$css_path = ALGQ_PLATFORM_DIR . 'assets/css/algq-platform.css';
		$js_path  = ALGQ_PLATFORM_DIR . 'assets/js/algq-platform.js';

		$allowed_hooks = array(
			'toplevel_page_algq-platform',
			'algonquian_page_algq-platform-settings',
		);

		if ( ! in_array( $hook_suffix, $allowed_hooks, true ) ) {
			return;
		}

		if ( file_exists( $css_path ) ) {
			wp_enqueue_style( 'algq-platform-admin', ALGQ_PLATFORM_URL . 'assets/css/algq-platform.css', array(), filemtime( $css_path ) );
		}

		if ( file_exists( $js_path ) ) {
			wp_enqueue_script( 'algq-platform-admin', ALGQ_PLATFORM_URL . 'assets/js/algq-platform.js', array(), filemtime( $js_path ), true );
		}
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
		update_option( 'algq_platform_release_status', '1.0.0 Release Candidate' );
		add_option(
			'algq_platform_brand_colors',
			array(
				'blue' => '#2F4A6D',
				'gold' => '#C8A96A',
				'ink'  => '#111827',
				'bg'   => '#F9FAFB',
			)
		);

		if ( class_exists( 'ALGQ_Platform_Page_Generator' ) ) {
			ALGQ_Platform_Page_Generator::create_pages();
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
		add_shortcode( 'algq_tenant_center', array( $this, 'tenant_center_shortcode' ) );
		add_shortcode( 'algq_tenant_application', array( $this, 'tenant_application_shortcode' ) );
		add_shortcode( 'algq_rent_payment', array( $this, 'rent_payment_shortcode' ) );
		add_shortcode( 'algq_maintenance_request', array( $this, 'maintenance_request_shortcode' ) );
		add_shortcode( 'algq_tenant_forms', array( $this, 'tenant_forms_shortcode' ) );
		add_shortcode( 'algq_tenant_portal', array( $this, 'tenant_portal_shortcode' ) );
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

	private function link_card( $title, $copy, $url, $label ) {
		ob_start();
		?>
		<div class="algq-kpi">
			<span class="algq-badge"><?php echo esc_html__( 'Tenant Services', 'algonquian-real-estate-platform' ); ?></span>
			<h3><?php echo esc_html( $title ); ?></h3>
			<p><?php echo esc_html( $copy ); ?></p>
			<a class="algq-button" href="<?php echo esc_url( $url ); ?>"><?php echo esc_html( $label ); ?></a>
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

	public function tenant_center_shortcode() {
		ob_start();
		?>
		<section class="algq-platform-card algq-tenant-center">
			<span class="algq-badge"><?php echo esc_html__( 'Tenant / Renter Center', 'algonquian-real-estate-platform' ); ?></span>
			<h2><?php echo esc_html__( 'Tenant Services', 'algonquian-real-estate-platform' ); ?></h2>
			<p><?php echo esc_html__( 'Apply for housing, submit renter documents, pay rent online, request maintenance, access tenant forms, and use the tenant portal for property-management communications.', 'algonquian-real-estate-platform' ); ?></p>
			<div class="algq-grid algq-tenant-grid">
				<?php
				echo $this->link_card( 'Apply Online', 'Submit renter application information and supporting documentation.', home_url( '/tenants/apply/' ), 'Start Application' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				echo $this->link_card( 'Pay Rent Online', 'Access rent-payment instructions, payment links, receipts, and policy information.', home_url( '/tenants/pay-rent/' ), 'Pay Rent' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				echo $this->link_card( 'Maintenance Request', 'Submit repair requests, non-emergency maintenance issues, and move-in notes.', home_url( '/tenants/maintenance/' ), 'Request Service' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				echo $this->link_card( 'Tenant Forms', 'Access applications, income verification, inspection forms, security deposit forms, and notices.', home_url( '/tenants/forms/' ), 'View Forms' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				echo $this->link_card( 'Tenant Portal', 'Log in to view account information, documents, maintenance status, and property notices.', home_url( '/tenants/portal/' ), 'Open Portal' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				?>
			</div>
		</section>
		<?php
		return ob_get_clean();
	}

	public function tenant_application_shortcode() { return $this->card( 'Tenant Application', 'Renter application intake for applicant identity, household details, rental history, employment, income verification, references, pets, vehicles, and required uploads.' ); }
	public function rent_payment_shortcode() { return $this->card( 'Pay Rent Online', 'Rent payment hub for secure payment links, payment policy, due dates, late-fee disclosures, receipts, and account support.' ); }
	public function maintenance_request_shortcode() { return $this->card( 'Maintenance Request', 'Tenant maintenance intake for property address, unit, issue category, urgency, access permission, photo uploads, and status tracking.' ); }
	public function tenant_forms_shortcode() { return $this->card( 'Tenant Forms', 'Tenant application, income verification, move-in / move-out inspection, security deposit handling, maintenance request, late notice, and lease renewal / non-renewal forms.' ); }

	public function tenant_portal_shortcode() {
		$tenant_name      = is_user_logged_in() ? wp_get_current_user()->display_name : __( 'Tenant', 'algonquian-real-estate-platform' );
		$dashboard_cards  = array(
			array( 'label' => 'Rent Status', 'value' => 'Current', 'detail' => 'Next payment window visible in payment center.' ),
			array( 'label' => 'Lease File', 'value' => 'Active', 'detail' => 'Lease, addenda, and renewal notices.' ),
			array( 'label' => 'Maintenance', 'value' => '0 Open', 'detail' => 'Track requests and service updates.' ),
			array( 'label' => 'Documents', 'value' => 'Ready', 'detail' => 'Forms, notices, receipts, and inspections.' ),
		);
		$quick_actions = array(
			array( 'title' => 'Pay Rent', 'copy' => 'Open rent-payment instructions and secure payment access.', 'url' => home_url( '/tenants/pay-rent/' ) ),
			array( 'title' => 'Request Maintenance', 'copy' => 'Submit a repair issue with unit, priority, access notes, and photos.', 'url' => home_url( '/tenants/maintenance/' ) ),
			array( 'title' => 'View Tenant Forms', 'copy' => 'Access application, inspection, security deposit, renewal, and notice forms.', 'url' => home_url( '/tenants/forms/' ) ),
			array( 'title' => 'Contact Management', 'copy' => 'Send a property-management support request or update your contact information.', 'url' => home_url( '/contact/' ) ),
		);
		ob_start();
		?>
		<section class="algq-platform-card algq-tenant-dashboard">
			<div class="algq-tenant-hero">
				<div>
					<span class="algq-badge"><?php echo esc_html__( 'Tenant Portal Dashboard', 'algonquian-real-estate-platform' ); ?></span>
					<h2><?php echo esc_html( sprintf( __( 'Welcome, %s', 'algonquian-real-estate-platform' ), $tenant_name ) ); ?></h2>
					<p><?php echo esc_html__( 'Central dashboard for rent access, lease documents, maintenance requests, tenant notices, inspections, and property-management support.', 'algonquian-real-estate-platform' ); ?></p>
				</div>
				<a class="algq-button" href="<?php echo esc_url( home_url( '/tenants/pay-rent/' ) ); ?>"><?php echo esc_html__( 'Pay Rent', 'algonquian-real-estate-platform' ); ?></a>
			</div>

			<div class="algq-grid algq-tenant-kpis">
				<?php foreach ( $dashboard_cards as $card ) : ?>
					<div class="algq-kpi algq-tenant-kpi">
						<span><?php echo esc_html( $card['label'] ); ?></span>
						<strong><?php echo esc_html( $card['value'] ); ?></strong>
						<p><?php echo esc_html( $card['detail'] ); ?></p>
					</div>
				<?php endforeach; ?>
			</div>

			<div class="algq-tenant-dashboard-layout">
				<div class="algq-tenant-panel">
					<h3><?php echo esc_html__( 'Lease & Unit Snapshot', 'algonquian-real-estate-platform' ); ?></h3>
					<ul class="algq-tenant-list">
						<li><strong><?php echo esc_html__( 'Property:', 'algonquian-real-estate-platform' ); ?></strong> <?php echo esc_html__( 'Assigned rental property / unit record', 'algonquian-real-estate-platform' ); ?></li>
						<li><strong><?php echo esc_html__( 'Lease Status:', 'algonquian-real-estate-platform' ); ?></strong> <?php echo esc_html__( 'Active / pending renewal review', 'algonquian-real-estate-platform' ); ?></li>
						<li><strong><?php echo esc_html__( 'Security Deposit:', 'algonquian-real-estate-platform' ); ?></strong> <?php echo esc_html__( 'Tracked through CT-compliant handling record', 'algonquian-real-estate-platform' ); ?></li>
						<li><strong><?php echo esc_html__( 'Inspection File:', 'algonquian-real-estate-platform' ); ?></strong> <?php echo esc_html__( 'Move-in / move-out condition documentation', 'algonquian-real-estate-platform' ); ?></li>
					</ul>
				</div>

				<div class="algq-tenant-panel">
					<h3><?php echo esc_html__( 'Notices & Documents', 'algonquian-real-estate-platform' ); ?></h3>
					<div class="algq-document-row"><span><?php echo esc_html__( 'Residential Lease Agreement', 'algonquian-real-estate-platform' ); ?></span><em><?php echo esc_html__( 'Available', 'algonquian-real-estate-platform' ); ?></em></div>
					<div class="algq-document-row"><span><?php echo esc_html__( 'Security Deposit Handling Form', 'algonquian-real-estate-platform' ); ?></span><em><?php echo esc_html__( 'Available', 'algonquian-real-estate-platform' ); ?></em></div>
					<div class="algq-document-row"><span><?php echo esc_html__( 'Move-In / Move-Out Inspection Form', 'algonquian-real-estate-platform' ); ?></span><em><?php echo esc_html__( 'Available', 'algonquian-real-estate-platform' ); ?></em></div>
					<div class="algq-document-row"><span><?php echo esc_html__( 'Lease Renewal / Non-Renewal Notice', 'algonquian-real-estate-platform' ); ?></span><em><?php echo esc_html__( 'Template', 'algonquian-real-estate-platform' ); ?></em></div>
				</div>
			</div>

			<div class="algq-tenant-panel algq-tenant-actions">
				<h3><?php echo esc_html__( 'Quick Actions', 'algonquian-real-estate-platform' ); ?></h3>
				<div class="algq-grid">
					<?php foreach ( $quick_actions as $action ) : ?>
						<a class="algq-action-card" href="<?php echo esc_url( $action['url'] ); ?>">
							<strong><?php echo esc_html( $action['title'] ); ?></strong>
							<span><?php echo esc_html( $action['copy'] ); ?></span>
						</a>
					<?php endforeach; ?>
				</div>
			</div>
		</section>
		<?php
		return ob_get_clean();
	}

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
		<div class="wrap"><h1><?php echo esc_html__( 'Algonquian Real Estate Platform', 'algonquian-real-estate-platform' ); ?></h1><p><strong><?php echo esc_html__( 'Release Status:', 'algonquian-real-estate-platform' ); ?></strong> <?php echo esc_html( get_option( 'algq_platform_release_status', '1.0.0 Release Candidate' ) ); ?></p><h2><?php echo esc_html__( 'Tenant / Renter Center', 'algonquian-real-estate-platform' ); ?></h2><p><?php echo esc_html__( 'Tenant services module added: tenant center, applications, online rent payment hub, maintenance requests, tenant forms, resources, and portal pages.', 'algonquian-real-estate-platform' ); ?></p></div>
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
