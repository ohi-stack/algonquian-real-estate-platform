<?php
/**
 * Plugin Name: Algonquian Real Estate Platform
 * Plugin URI: https://algonquianrealestate.com/technology/platform/
 * Description: Shared infrastructure, security, registry, mail delivery, audit logging, private file storage, health monitoring, capabilities, and common integration contracts for the Algonquian Real Estate plugin ecosystem.
 * Version: 2.0.0
 * Author: Onegodian
 * Author URI: https://algonquianrealestate.com/
 * Text Domain: algonquian-real-estate-platform
 * Domain Path: /languages
 * Requires at least: 6.8
 * Requires PHP: 8.2
 * License: Proprietary
 *
 * @package AlgonquianRealEstatePlatform
 */

defined( 'ABSPATH' ) || exit;

if ( ! defined( 'ALGQ_PLATFORM_VERSION' ) ) {
	define( 'ALGQ_PLATFORM_VERSION', '2.0.0' );
}

if ( ! defined( 'ALGQ_PLATFORM_FILE' ) ) {
	define( 'ALGQ_PLATFORM_FILE', __FILE__ );
}

if ( ! defined( 'ALGQ_PLATFORM_DIR' ) ) {
	define( 'ALGQ_PLATFORM_DIR', plugin_dir_path( __FILE__ ) );
}

if ( ! defined( 'ALGQ_PLATFORM_URL' ) ) {
	define( 'ALGQ_PLATFORM_URL', plugin_dir_url( __FILE__ ) );
}

require_once ALGQ_PLATFORM_DIR . 'includes/class-capabilities.php';
require_once ALGQ_PLATFORM_DIR . 'includes/class-plugin-registry.php';
require_once ALGQ_PLATFORM_DIR . 'includes/class-audit-log.php';
require_once ALGQ_PLATFORM_DIR . 'includes/class-mail-gateway.php';
require_once ALGQ_PLATFORM_DIR . 'includes/class-private-files.php';
require_once ALGQ_PLATFORM_DIR . 'includes/class-health-monitor.php';
require_once ALGQ_PLATFORM_DIR . 'includes/class-page-generator.php';

final class ALGQ_Platform {
	private static ?self $instance = null;

	/** @var array<string,string> */
	private array $admin_pages = array();

	public static function instance(): self {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	private function __construct() {
		add_action( 'plugins_loaded', array( $this, 'boot' ), 5 );
	}

	public function boot(): void {
		load_plugin_textdomain( 'algonquian-real-estate-platform', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );

		if ( ! self::meets_requirements() ) {
			add_action( 'admin_notices', array( $this, 'render_requirement_notice' ) );
			return;
		}

		ALGQ_Platform_Capabilities::init();
		ALGQ_Platform_Registry::init();
		ALGQ_Platform_Audit_Log::init();
		ALGQ_Mail_Gateway::init();
		ALGQ_Private_Files::init();
		ALGQ_Platform_Health_Monitor::init();

		add_action( 'init', array( $this, 'register_shortcodes' ), 100 );
		add_action( 'admin_menu', array( $this, 'register_admin_menu' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_public_assets' ) );
		add_action( 'admin_post_algq_platform_save_settings', array( $this, 'handle_save_settings' ) );
		add_action( 'admin_post_algq_platform_rebuild_pages', array( $this, 'handle_rebuild_pages' ) );
		add_action( 'admin_post_algq_platform_run_health', array( $this, 'handle_run_health' ) );
		add_action( 'admin_post_algq_platform_test_email', array( $this, 'handle_test_email' ) );
	}

	public static function activate(): void {
		if ( ! self::meets_requirements() ) {
			deactivate_plugins( plugin_basename( __FILE__ ) );
			wp_die(
				esc_html__( 'Algonquian Real Estate Platform 2.0.0 requires WordPress 6.8 or later and PHP 8.2 or later.', 'algonquian-real-estate-platform' ),
				esc_html__( 'Platform requirements not met', 'algonquian-real-estate-platform' ),
				array( 'back_link' => true )
			);
		}

		ALGQ_Platform_Capabilities::install();
		ALGQ_Platform_Audit_Log::install();
		ALGQ_Mail_Gateway::install();
		ALGQ_Private_Files::ensure_storage();
		ALGQ_Platform_Page_Generator::create_missing_pages();
		ALGQ_Platform_Health_Monitor::schedule();

		update_option( 'algq_platform_version', ALGQ_PLATFORM_VERSION );
		update_option( 'algq_platform_schema_version', '2.0.0' );
		update_option( 'algq_platform_release_status', 'Production infrastructure core' );

		ALGQ_Platform_Audit_Log::log(
			'platform.activated',
			array( 'version' => ALGQ_PLATFORM_VERSION ),
			array( 'severity' => 'info', 'plugin' => 'algonquian-real-estate-platform' )
		);
	}

	public static function deactivate(): void {
		ALGQ_Platform_Health_Monitor::unschedule();
	}

	private static function meets_requirements(): bool {
		global $wp_version;

		return version_compare( PHP_VERSION, '8.2', '>=' )
			&& isset( $wp_version )
			&& version_compare( (string) $wp_version, '6.8', '>=' );
	}

	public function render_requirement_notice(): void {
		if ( ! current_user_can( 'activate_plugins' ) ) {
			return;
		}

		echo '<div class="notice notice-error"><p>'
			. esc_html__( 'Algonquian Real Estate Platform 2.0.0 is inactive because WordPress 6.8+ and PHP 8.2+ are required.', 'algonquian-real-estate-platform' )
			. '</p></div>';
	}

	public function register_shortcodes(): void {
		add_shortcode( 'algq_platform_overview', array( $this, 'render_platform_overview_shortcode' ) );

		if ( ! shortcode_exists( 'algq_plugin_suite' ) ) {
			add_shortcode( 'algq_plugin_suite', array( $this, 'render_platform_overview_shortcode' ) );
		}

		$legacy_bridges = array(
			'algq_seller_intake'      => 'Algonquian Deal Intake',
			'algq_mao_calculator'      => 'Algonquian MAO Engine',
			'algq_buyer_registration'  => 'Algonquian Buyer Portal',
			'algq_pipeline_crm'         => 'Algonquian Pipeline CRM',
			'algq_buyer_portal'         => 'Algonquian Buyer Portal',
			'algq_funding_tracker'      => 'Algonquian Funding Tracker',
			'algq_document_library'     => 'Algonquian Document Library',
			'algq_automation_engine'    => 'Algonquian Automation Engine',
			'algq_admin_dashboard'      => 'Algonquian Admin Command Center',
			'algq_digital_store'        => 'Algonquian Digital Store',
			'algq_product_vault'        => 'Algonquian Digital Store',
			'algq_store_checkout'       => 'Algonquian Digital Store',
		);

		foreach ( $legacy_bridges as $shortcode => $plugin_name ) {
			if ( shortcode_exists( $shortcode ) ) {
				continue;
			}

			add_shortcode(
				$shortcode,
				static function () use ( $plugin_name ): string {
					if ( current_user_can( 'manage_algq_platform' ) ) {
						return sprintf(
							'<div class="algq-platform-notice"><strong>%1$s</strong><p>%2$s</p></div>',
							esc_html( $plugin_name ),
							esc_html__( 'The companion plugin responsible for this interface is not active or has not registered its shortcode.', 'algonquian-real-estate-platform' )
						);
					}

					return '';
				}
			);
		}
	}

	public function render_platform_overview_shortcode(): string {
		$registry = ALGQ_Platform_Registry::status();
		$healthy  = count(
			array_filter(
				$registry,
				static fn( array $plugin ): bool => ! empty( $plugin['active'] ) && ! empty( $plugin['compatible'] )
			)
		);

		ob_start();
		?>
		<section class="algq-platform-overview">
			<p class="algq-eyebrow"><?php echo esc_html__( 'Algonquian Real Estate Technology Division', 'algonquian-real-estate-platform' ); ?></p>
			<h2><?php echo esc_html__( 'Shared Platform Infrastructure', 'algonquian-real-estate-platform' ); ?></h2>
			<p><?php echo esc_html__( 'Centralized security, capabilities, registry, audit logging, mail delivery, private file handling, health monitoring, page generation, and integration contracts for the Algonquian Real Estate plugin ecosystem.', 'algonquian-real-estate-platform' ); ?></p>
			<div class="algq-platform-kpis">
				<div><strong><?php echo esc_html( (string) count( $registry ) ); ?></strong><span><?php echo esc_html__( 'Registered modules', 'algonquian-real-estate-platform' ); ?></span></div>
				<div><strong><?php echo esc_html( (string) $healthy ); ?></strong><span><?php echo esc_html__( 'Active and compatible', 'algonquian-real-estate-platform' ); ?></span></div>
				<div><strong><?php echo esc_html( ALGQ_PLATFORM_VERSION ); ?></strong><span><?php echo esc_html__( 'Platform version', 'algonquian-real-estate-platform' ); ?></span></div>
			</div>
		</section>
		<?php
		return (string) ob_get_clean();
	}

	public function register_admin_menu(): void {
		$this->admin_pages[] = add_menu_page(
			esc_html__( 'Algonquian Platform', 'algonquian-real-estate-platform' ),
			esc_html__( 'Algonquian', 'algonquian-real-estate-platform' ),
			'manage_algq_platform',
			'algq-platform',
			array( $this, 'render_admin_dashboard' ),
			'dashicons-building',
			26
		);

		$this->admin_pages[] = add_submenu_page(
			'algq-platform',
			esc_html__( 'Platform Settings', 'algonquian-real-estate-platform' ),
			esc_html__( 'Settings', 'algonquian-real-estate-platform' ),
			'manage_algq_platform',
			'algq-platform-settings',
			array( $this, 'render_settings_page' )
		);
	}

	public function enqueue_admin_assets( string $hook_suffix ): void {
		if ( ! in_array( $hook_suffix, array_filter( $this->admin_pages ), true ) ) {
			return;
		}

		$this->enqueue_shared_assets( true );
	}

	public function enqueue_public_assets(): void {
		if ( ! is_singular() ) {
			return;
		}

		global $post;
		if ( $post instanceof WP_Post && has_shortcode( (string) $post->post_content, 'algq_platform_overview' ) ) {
			$this->enqueue_shared_assets( false );
		}
	}

	private function enqueue_shared_assets( bool $admin ): void {
		$path = ALGQ_PLATFORM_DIR . 'assets/css/algq-platform.css';
		if ( ! file_exists( $path ) ) {
			return;
		}

		wp_enqueue_style(
			$admin ? 'algq-platform-admin' : 'algq-platform',
			ALGQ_PLATFORM_URL . 'assets/css/algq-platform.css',
			array(),
			(string) filemtime( $path )
		);
	}

	public function render_admin_dashboard(): void {
		$this->assert_admin_access();
		$health   = ALGQ_Platform_Health_Monitor::latest();
		$registry = ALGQ_Platform_Registry::status();
		?>
		<div class="wrap algq-admin-wrap">
			<h1><?php echo esc_html__( 'Algonquian Real Estate Platform', 'algonquian-real-estate-platform' ); ?></h1>
			<p><?php echo esc_html__( 'Infrastructure authority for shared platform services. Operational records remain owned by their designated companion plugins.', 'algonquian-real-estate-platform' ); ?></p>
			<div class="algq-platform-kpis">
				<div><strong><?php echo esc_html( ALGQ_PLATFORM_VERSION ); ?></strong><span><?php echo esc_html__( 'Version', 'algonquian-real-estate-platform' ); ?></span></div>
				<div><strong><?php echo esc_html( (string) count( $registry ) ); ?></strong><span><?php echo esc_html__( 'Registered plugins', 'algonquian-real-estate-platform' ); ?></span></div>
				<div><strong><?php echo esc_html( strtoupper( (string) ( $health['overall'] ?? 'not run' ) ) ); ?></strong><span><?php echo esc_html__( 'Health status', 'algonquian-real-estate-platform' ); ?></span></div>
			</div>
			<p>
				<a class="button button-primary" href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=algq_platform_run_health' ), 'algq_platform_run_health' ) ); ?>"><?php echo esc_html__( 'Run Health Check', 'algonquian-real-estate-platform' ); ?></a>
				<a class="button" href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=algq_platform_rebuild_pages' ), 'algq_platform_rebuild_pages' ) ); ?>"><?php echo esc_html__( 'Create Missing Pages', 'algonquian-real-estate-platform' ); ?></a>
			</p>
			<h2><?php echo esc_html__( 'Plugin Registry', 'algonquian-real-estate-platform' ); ?></h2>
			<table class="widefat striped"><thead><tr><th><?php echo esc_html__( 'Plugin', 'algonquian-real-estate-platform' ); ?></th><th><?php echo esc_html__( 'Installed', 'algonquian-real-estate-platform' ); ?></th><th><?php echo esc_html__( 'Active', 'algonquian-real-estate-platform' ); ?></th><th><?php echo esc_html__( 'Compatible', 'algonquian-real-estate-platform' ); ?></th></tr></thead><tbody>
			<?php foreach ( $registry as $plugin ) : ?>
				<tr><td><?php echo esc_html( $plugin['name'] ); ?></td><td><?php echo esc_html( $plugin['installed'] ? 'Yes' : 'No' ); ?></td><td><?php echo esc_html( $plugin['active'] ? 'Yes' : 'No' ); ?></td><td><?php echo esc_html( $plugin['compatible'] ? 'Yes' : 'No' ); ?></td></tr>
			<?php endforeach; ?>
			</tbody></table>
		</div>
		<?php
	}

	public function render_settings_page(): void {
		$this->assert_admin_access();
		$settings = ALGQ_Mail_Gateway::settings();
		?>
		<div class="wrap algq-admin-wrap">
			<h1><?php echo esc_html__( 'Platform Settings', 'algonquian-real-estate-platform' ); ?></h1>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="algq_platform_save_settings">
				<?php wp_nonce_field( 'algq_platform_save_settings' ); ?>
				<h2><?php echo esc_html__( 'Algonquian Mail Gateway', 'algonquian-real-estate-platform' ); ?></h2>
				<p><?php echo esc_html__( 'SMTP passwords must be provided through ALGQ_SMTP_PASSWORD or an environment secret. Passwords are not stored in WordPress options.', 'algonquian-real-estate-platform' ); ?></p>
				<table class="form-table" role="presentation">
					<tr><th scope="row"><?php echo esc_html__( 'Enable SMTP', 'algonquian-real-estate-platform' ); ?></th><td><label><input type="checkbox" name="mail[enabled]" value="1" <?php checked( ! empty( $settings['enabled'] ) ); ?>> <?php echo esc_html__( 'Route wp_mail() through the platform gateway', 'algonquian-real-estate-platform' ); ?></label></td></tr>
					<tr><th scope="row"><label for="algq-mail-host"><?php echo esc_html__( 'SMTP Host', 'algonquian-real-estate-platform' ); ?></label></th><td><input class="regular-text" id="algq-mail-host" name="mail[host]" value="<?php echo esc_attr( $settings['host'] ?? '' ); ?>"></td></tr>
					<tr><th scope="row"><label for="algq-mail-port"><?php echo esc_html__( 'SMTP Port', 'algonquian-real-estate-platform' ); ?></label></th><td><input class="small-text" type="number" min="1" max="65535" id="algq-mail-port" name="mail[port]" value="<?php echo esc_attr( (string) ( $settings['port'] ?? 587 ) ); ?>"></td></tr>
					<tr><th scope="row"><label for="algq-mail-encryption"><?php echo esc_html__( 'Encryption', 'algonquian-real-estate-platform' ); ?></label></th><td><select id="algq-mail-encryption" name="mail[encryption]"><option value="tls" <?php selected( $settings['encryption'] ?? 'tls', 'tls' ); ?>>TLS</option><option value="ssl" <?php selected( $settings['encryption'] ?? '', 'ssl' ); ?>>SSL</option><option value="none" <?php selected( $settings['encryption'] ?? '', 'none' ); ?>><?php echo esc_html__( 'None', 'algonquian-real-estate-platform' ); ?></option></select></td></tr>
					<tr><th scope="row"><label for="algq-mail-username"><?php echo esc_html__( 'Username', 'algonquian-real-estate-platform' ); ?></label></th><td><input class="regular-text" id="algq-mail-username" name="mail[username]" value="<?php echo esc_attr( $settings['username'] ?? '' ); ?>"></td></tr>
					<tr><th scope="row"><label for="algq-mail-from-email"><?php echo esc_html__( 'From Email', 'algonquian-real-estate-platform' ); ?></label></th><td><input class="regular-text" type="email" id="algq-mail-from-email" name="mail[from_email]" value="<?php echo esc_attr( $settings['from_email'] ?? '' ); ?>"></td></tr>
					<tr><th scope="row"><label for="algq-mail-from-name"><?php echo esc_html__( 'From Name', 'algonquian-real-estate-platform' ); ?></label></th><td><input class="regular-text" id="algq-mail-from-name" name="mail[from_name]" value="<?php echo esc_attr( $settings['from_name'] ?? 'Algonquian Real Estate' ); ?>"></td></tr>
				</table>
				<?php submit_button(); ?>
			</form>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="algq_platform_test_email">
				<?php wp_nonce_field( 'algq_platform_test_email' ); ?>
				<label for="algq-test-email"><?php echo esc_html__( 'Send test email to', 'algonquian-real-estate-platform' ); ?></label>
				<input type="email" id="algq-test-email" name="test_email" value="<?php echo esc_attr( wp_get_current_user()->user_email ); ?>" required>
				<?php submit_button( esc_html__( 'Send Test Email', 'algonquian-real-estate-platform' ), 'secondary', 'submit', false ); ?>
			</form>
		</div>
		<?php
	}

	public function handle_save_settings(): void {
		$this->assert_admin_access();
		check_admin_referer( 'algq_platform_save_settings' );
		$input = isset( $_POST['mail'] ) && is_array( $_POST['mail'] ) ? wp_unslash( $_POST['mail'] ) : array();
		ALGQ_Mail_Gateway::save_settings( $input );
		ALGQ_Platform_Audit_Log::log( 'platform.settings.updated', array( 'section' => 'mail' ) );
		wp_safe_redirect( admin_url( 'admin.php?page=algq-platform-settings&updated=1' ) );
		exit;
	}

	public function handle_rebuild_pages(): void {
		$this->assert_admin_access();
		check_admin_referer( 'algq_platform_rebuild_pages' );
		ALGQ_Platform_Page_Generator::create_missing_pages();
		ALGQ_Platform_Audit_Log::log( 'platform.pages.reconciled' );
		wp_safe_redirect( admin_url( 'admin.php?page=algq-platform&pages=1' ) );
		exit;
	}

	public function handle_run_health(): void {
		$this->assert_admin_access();
		check_admin_referer( 'algq_platform_run_health' );
		ALGQ_Platform_Health_Monitor::run();
		wp_safe_redirect( admin_url( 'admin.php?page=algq-platform&health=1' ) );
		exit;
	}

	public function handle_test_email(): void {
		$this->assert_admin_access( 'manage_algq_email' );
		check_admin_referer( 'algq_platform_test_email' );
		$email = isset( $_POST['test_email'] ) ? sanitize_email( wp_unslash( $_POST['test_email'] ) ) : '';
		$sent  = $email && wp_mail( $email, 'Algonquian Mail Gateway Test', 'This is a transactional email test from the Algonquian Real Estate Platform.' );
		ALGQ_Platform_Audit_Log::log( 'mail.test', array( 'success' => (bool) $sent, 'recipient_domain' => self::email_domain( $email ) ) );
		wp_safe_redirect( admin_url( 'admin.php?page=algq-platform-settings&mail_test=' . ( $sent ? 'success' : 'failed' ) ) );
		exit;
	}

	private function assert_admin_access( string $capability = 'manage_algq_platform' ): void {
		if ( ! current_user_can( $capability ) ) {
			wp_die( esc_html__( 'Insufficient permissions.', 'algonquian-real-estate-platform' ) );
		}
	}

	private static function email_domain( string $email ): string {
		$parts = explode( '@', $email );
		return 2 === count( $parts ) ? sanitize_text_field( $parts[1] ) : '';
	}
}

register_activation_hook( __FILE__, array( 'ALGQ_Platform', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'ALGQ_Platform', 'deactivate' ) );
ALGQ_Platform::instance();
