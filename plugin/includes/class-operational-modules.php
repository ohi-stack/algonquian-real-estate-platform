<?php
/**
 * Operational workspaces for lifecycle modules 13-20.
 *
 * These modules coordinate cross-functional work while preserving the
 * authoritative record boundaries of Pipeline CRM, Deal Intake, MAO,
 * Offer Generator, Document Library, Funding Tracker and the other ARE
 * companion systems.
 *
 * @package AlgonquianRealEstatePlatform
 */

defined( 'ABSPATH' ) || exit;

final class ALGQ_Platform_Operational_Modules {
	private const TABLE_SUFFIX = 'algq_module_work_items';
	private const SCHEMA_VERSION = '3.1.0';

	public static function init(): void {
		add_action( 'init', array( __CLASS__, 'register_shortcodes' ), 110 );
		add_action( 'admin_menu', array( __CLASS__, 'register_admin_menu' ), 30 );
		add_action( 'admin_post_algq_module_save_item', array( __CLASS__, 'handle_save_item' ) );
		add_action( 'rest_api_init', array( __CLASS__, 'register_rest_routes' ) );
	}

	public static function activate(): void {
		self::install_schema();
		self::create_missing_pages();
		update_option( 'algq_platform_modules_schema_version', self::SCHEMA_VERSION, false );
	}

	private static function install_schema(): void {
		global $wpdb;
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		$table   = self::table();
		$charset = $wpdb->get_charset_collate();
		$sql = "CREATE TABLE {$table} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			module_key varchar(64) NOT NULL,
			deal_id bigint(20) unsigned NOT NULL DEFAULT 0,
			external_ref varchar(191) NOT NULL DEFAULT '',
			status varchar(64) NOT NULL DEFAULT 'new',
			assigned_user_id bigint(20) unsigned NOT NULL DEFAULT 0,
			next_action varchar(255) NOT NULL DEFAULT '',
			due_at datetime NULL,
			summary text NOT NULL,
			payload_json longtext NULL,
			created_at datetime NOT NULL,
			updated_at datetime NOT NULL,
			PRIMARY KEY  (id),
			KEY module_key (module_key),
			KEY deal_id (deal_id),
			KEY status (status),
			KEY due_at (due_at)
		) {$charset};";
		dbDelta( $sql );
	}

	/** @return array<string,array<string,mixed>> */
	public static function modules(): array {
		return array(
			'seller-portal' => array(
				'number' => 13,
				'name' => 'Seller Portal',
				'shortcode' => 'algq_seller_portal_v3',
				'service' => 'seller.portal',
				'route' => '/seller-portal/',
				'purpose' => 'Seller-facing status, requested information, appointments, offer visibility and next-step coordination.',
				'authority' => 'Presentation/workflow layer. Deal Intake and Pipeline CRM retain seller-intake and canonical Deal authority.',
				'access' => 'logged_in',
			),
			'title-closing-engine' => array(
				'number' => 14,
				'name' => 'Title & Closing Engine',
				'shortcode' => 'algq_title_closing_engine',
				'service' => 'closing.workflow',
				'route' => '/platform/title-closing/',
				'purpose' => 'Coordinate title review, attorney/title-company handoffs, closing conditions, deadlines and closing readiness.',
				'authority' => 'Owns closing coordination work items only. Legal and title determinations remain with qualified professionals.',
				'access' => 'staff',
			),
			'disposition-engine' => array(
				'number' => 15,
				'name' => 'Disposition Engine',
				'shortcode' => 'algq_disposition_engine',
				'service' => 'disposition.workflow',
				'route' => '/platform/disposition/',
				'purpose' => 'Coordinate approved opportunity packaging, buyer matching, outreach, buyer responses and disposition handoff.',
				'authority' => 'Owns disposition work items. Buyer Portal and Deal Marketplace retain their authoritative records.',
				'access' => 'staff',
			),
			'investor-portal' => array(
				'number' => 16,
				'name' => 'Investor Portal',
				'shortcode' => 'algq_investor_portal',
				'service' => 'investor.portal',
				'route' => '/investor-portal/',
				'purpose' => 'Provide authorized investors, private lenders and capital partners a controlled view of opportunities, documents and next actions.',
				'authority' => 'Presentation layer. Funding Tracker, Pipeline CRM and Document Library retain authoritative records.',
				'access' => 'logged_in',
			),
			'reporting-analytics' => array(
				'number' => 17,
				'name' => 'Reporting & Analytics',
				'shortcode' => 'algq_reporting_analytics',
				'service' => 'reporting.analytics',
				'route' => '/platform/reporting/',
				'purpose' => 'Aggregate lifecycle KPIs, pipeline velocity, workload, closing readiness, funding status and operational exceptions.',
				'authority' => 'Read and aggregation layer only; source systems remain authoritative.',
				'access' => 'staff',
			),
			'document-vault' => array(
				'number' => 18,
				'name' => 'Document Vault',
				'shortcode' => 'algq_document_vault',
				'service' => 'documents.vault',
				'route' => '/platform/document-vault/',
				'purpose' => 'Expose controlled access to protected files, delivery state, retention state and access auditing.',
				'authority' => 'Storage/access layer. Document Library remains the canonical document-record owner.',
				'access' => 'staff',
			),
			'task-project-management' => array(
				'number' => 19,
				'name' => 'Task / Project Management',
				'shortcode' => 'algq_task_project_management',
				'service' => 'projects.tasks',
				'route' => '/platform/tasks/',
				'purpose' => 'Track next actions, owners, deadlines, projects, dependencies and completion history across the lifecycle.',
				'authority' => 'Owns platform work-item records; linked Deal identity remains owned by Pipeline CRM.',
				'access' => 'staff',
			),
			'communications-hub' => array(
				'number' => 20,
				'name' => 'Communications Hub',
				'shortcode' => 'algq_communications_hub',
				'service' => 'communications.hub',
				'route' => '/platform/communications/',
				'purpose' => 'Coordinate email, SMS and VoIP events, consent state, templates, follow-up and provider integrations.',
				'authority' => 'Owns communication-coordination records only; delivery requires configured providers and applicable consent controls.',
				'access' => 'staff',
			),
		);
	}

	public static function register_shortcodes(): void {
		foreach ( self::modules() as $key => $module ) {
			$shortcode = (string) $module['shortcode'];
			if ( ! shortcode_exists( $shortcode ) ) {
				add_shortcode( $shortcode, static fn() => self::render_workspace( $key ) );
			}
		}
		if ( ! shortcode_exists( 'algq_operational_modules' ) ) {
			add_shortcode( 'algq_operational_modules', array( __CLASS__, 'render_all_modules' ) );
		}
		if ( ! shortcode_exists( 'algq_platform_modules' ) ) {
			add_shortcode( 'algq_platform_modules', array( __CLASS__, 'render_all_modules' ) );
		}
	}

	public static function register_admin_menu(): void {
		add_submenu_page(
			'algq-platform',
			__( 'Operational Modules', 'algonquian-real-estate-platform' ),
			__( 'Operational Modules', 'algonquian-real-estate-platform' ),
			'manage_algq_platform',
			'algq-platform-modules',
			array( __CLASS__, 'render_admin_page' )
		);
	}

	public static function render_all_modules(): string {
		$html = '<section class="algq-module-grid">';
		foreach ( self::modules() as $key => $module ) {
			$html .= self::module_card( $key, $module );
		}
		return $html . '</section>';
	}

	public static function render_workspace( string $key ): string {
		$modules = self::modules();
		if ( ! isset( $modules[ $key ] ) ) {
			return '';
		}
		$module = $modules[ $key ];
		if ( 'staff' === $module['access'] && ! current_user_can( 'manage_algq_platform' ) ) {
			return '<div class="algq-platform-notice">' . esc_html__( 'This workspace is restricted to authorized Algonquian Real Estate users.', 'algonquian-real-estate-platform' ) . '</div>';
		}
		if ( 'logged_in' === $module['access'] && ! is_user_logged_in() ) {
			return '<div class="algq-platform-notice">' . esc_html__( 'Please sign in to access this portal.', 'algonquian-real-estate-platform' ) . '</div>';
		}

		$items   = self::get_items( $key, 25 );
		$service = self::service_snapshot( $module );
		ob_start();
		?>
		<section class="algq-module-workspace">
			<p class="algq-eyebrow"><?php echo esc_html( 'Module ' . (int) $module['number'] ); ?></p>
			<h2><?php echo esc_html( (string) $module['name'] ); ?></h2>
			<p><?php echo esc_html( (string) $module['purpose'] ); ?></p>
			<p class="algq-authority"><strong><?php esc_html_e( 'Authority boundary:', 'algonquian-real-estate-platform' ); ?></strong> <?php echo esc_html( (string) $module['authority'] ); ?></p>
			<?php if ( current_user_can( 'manage_algq_platform' ) ) : ?>
				<div class="algq-platform-kpis">
					<div><strong><?php echo esc_html( (string) count( $items ) ); ?></strong><span><?php esc_html_e( 'Recent items', 'algonquian-real-estate-platform' ); ?></span></div>
					<div><strong><?php echo esc_html( (string) self::count_open( $key ) ); ?></strong><span><?php esc_html_e( 'Open', 'algonquian-real-estate-platform' ); ?></span></div>
					<div><strong><?php echo esc_html( (string) self::count_due( $key ) ); ?></strong><span><?php esc_html_e( 'Due / overdue', 'algonquian-real-estate-platform' ); ?></span></div>
					<div><strong><?php echo esc_html( (string) $service['health'] ); ?></strong><span><?php esc_html_e( 'Service', 'algonquian-real-estate-platform' ); ?></span></div>
				</div>
				<?php echo wp_kses_post( self::items_table( $items ) ); ?>
			<?php else : ?>
				<?php echo wp_kses_post( self::portal_context( $key ) ); ?>
			<?php endif; ?>
		</section>
		<?php
		return (string) ob_get_clean();
	}

	public static function render_admin_page(): void {
		if ( ! current_user_can( 'manage_algq_platform' ) ) {
			wp_die( esc_html__( 'Insufficient permissions.', 'algonquian-real-estate-platform' ) );
		}
		$key = isset( $_GET['module'] ) ? sanitize_key( wp_unslash( $_GET['module'] ) ) : '';
		$modules = self::modules();
		?>
		<div class="wrap algq-admin-wrap">
			<h1><?php esc_html_e( 'Operational Modules', 'algonquian-real-estate-platform' ); ?></h1>
			<p><?php esc_html_e( 'Modules 13–20 coordinate cross-functional transaction work without replacing the authoritative companion plugins.', 'algonquian-real-estate-platform' ); ?></p>
			<?php if ( $key && isset( $modules[ $key ] ) ) : ?>
				<?php echo wp_kses_post( self::render_workspace( $key ) ); ?>
				<h2><?php esc_html_e( 'Add Work Item', 'algonquian-real-estate-platform' ); ?></h2>
				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
					<input type="hidden" name="action" value="algq_module_save_item">
					<input type="hidden" name="module_key" value="<?php echo esc_attr( $key ); ?>">
					<?php wp_nonce_field( 'algq_module_save_item_' . $key ); ?>
					<table class="form-table"><tr><th>Deal ID</th><td><input type="number" min="0" name="deal_id"></td></tr><tr><th>External Reference</th><td><input class="regular-text" name="external_ref"></td></tr><tr><th>Status</th><td><select name="status"><option>new</option><option>active</option><option>waiting</option><option>blocked</option><option>ready</option><option>complete</option><option>cancelled</option></select></td></tr><tr><th>Summary</th><td><textarea class="large-text" rows="4" name="summary" required></textarea></td></tr><tr><th>Next Action</th><td><input class="large-text" name="next_action"></td></tr><tr><th>Due</th><td><input type="datetime-local" name="due_at"></td></tr></table>
					<?php submit_button( __( 'Save Work Item', 'algonquian-real-estate-platform' ) ); ?>
				</form>
			<?php else : ?>
				<?php echo wp_kses_post( self::render_all_modules() ); ?>
			<?php endif; ?>
		</div>
		<?php
	}

	public static function handle_save_item(): void {
		if ( ! current_user_can( 'manage_algq_platform' ) ) {
			wp_die( esc_html__( 'Insufficient permissions.', 'algonquian-real-estate-platform' ) );
		}
		$key = isset( $_POST['module_key'] ) ? sanitize_key( wp_unslash( $_POST['module_key'] ) ) : '';
		if ( ! isset( self::modules()[ $key ] ) ) {
			wp_die( esc_html__( 'Invalid module.', 'algonquian-real-estate-platform' ) );
		}
		check_admin_referer( 'algq_module_save_item_' . $key );
		self::insert_item(
			$key,
			array(
				'deal_id' => isset( $_POST['deal_id'] ) ? absint( $_POST['deal_id'] ) : 0,
				'external_ref' => isset( $_POST['external_ref'] ) ? sanitize_text_field( wp_unslash( $_POST['external_ref'] ) ) : '',
				'status' => isset( $_POST['status'] ) ? sanitize_key( wp_unslash( $_POST['status'] ) ) : 'new',
				'assigned_user_id' => get_current_user_id(),
				'next_action' => isset( $_POST['next_action'] ) ? sanitize_text_field( wp_unslash( $_POST['next_action'] ) ) : '',
				'due_at' => self::sanitize_datetime( isset( $_POST['due_at'] ) ? (string) wp_unslash( $_POST['due_at'] ) : '' ),
				'summary' => isset( $_POST['summary'] ) ? sanitize_textarea_field( wp_unslash( $_POST['summary'] ) ) : '',
			)
		);
		wp_safe_redirect( admin_url( 'admin.php?page=algq-platform-modules&module=' . rawurlencode( $key ) . '&saved=1' ) );
		exit;
	}

	public static function register_rest_routes(): void {
		register_rest_route( 'algq/v3', '/modules', array(
			'methods' => WP_REST_Server::READABLE,
			'callback' => static fn() => rest_ensure_response( self::modules() ),
			'permission_callback' => static fn() => current_user_can( 'manage_algq_platform' ),
		) );
		register_rest_route( 'algq/v3', '/modules/(?P<module>[a-z0-9-]+)/status', array(
			'methods' => WP_REST_Server::READABLE,
			'callback' => static function ( WP_REST_Request $request ) {
				$key = sanitize_key( (string) $request['module'] );
				$modules = self::modules();
				return isset( $modules[ $key ] ) ? rest_ensure_response( self::service_snapshot( $modules[ $key ] ) ) : new WP_Error( 'algq_invalid_module', __( 'Invalid module.', 'algonquian-real-estate-platform' ), array( 'status' => 404 ) );
			},
			'permission_callback' => static fn() => current_user_can( 'manage_algq_platform' ),
		) );
		register_rest_route( 'algq/v3', '/modules/(?P<module>[a-z0-9-]+)/items', array(
			array(
				'methods' => WP_REST_Server::READABLE,
				'callback' => static function ( WP_REST_Request $request ) {
					$key = sanitize_key( (string) $request['module'] );
					return isset( self::modules()[ $key ] ) ? rest_ensure_response( self::get_items( $key, 100 ) ) : new WP_Error( 'algq_invalid_module', __( 'Invalid module.', 'algonquian-real-estate-platform' ), array( 'status' => 404 ) );
				},
				'permission_callback' => static fn() => current_user_can( 'manage_algq_platform' ),
			),
			array(
				'methods' => WP_REST_Server::CREATABLE,
				'callback' => static function ( WP_REST_Request $request ) {
					$key = sanitize_key( (string) $request['module'] );
					if ( ! isset( self::modules()[ $key ] ) ) {
						return new WP_Error( 'algq_invalid_module', __( 'Invalid module.', 'algonquian-real-estate-platform' ), array( 'status' => 404 ) );
					}
					$id = self::insert_item( $key, array(
						'deal_id' => absint( $request->get_param( 'deal_id' ) ),
						'external_ref' => sanitize_text_field( (string) $request->get_param( 'external_ref' ) ),
						'status' => sanitize_key( (string) ( $request->get_param( 'status' ) ?: 'new' ) ),
						'assigned_user_id' => absint( $request->get_param( 'assigned_user_id' ) ?: get_current_user_id() ),
						'next_action' => sanitize_text_field( (string) $request->get_param( 'next_action' ) ),
						'due_at' => self::sanitize_datetime( (string) $request->get_param( 'due_at' ) ),
						'summary' => sanitize_textarea_field( (string) $request->get_param( 'summary' ) ),
						'payload' => is_array( $request->get_param( 'payload' ) ) ? $request->get_param( 'payload' ) : array(),
					) );
					return rest_ensure_response( array( 'id' => $id, 'module' => $key ) );
				},
				'permission_callback' => static fn() => current_user_can( 'manage_algq_platform' ),
			),
		) );
	}

	/** @param array<string,mixed> $data */
	private static function insert_item( string $key, array $data ): int {
		global $wpdb;
		$allowed_status = array( 'new', 'active', 'waiting', 'blocked', 'ready', 'complete', 'cancelled' );
		$status = in_array( $data['status'] ?? 'new', $allowed_status, true ) ? (string) $data['status'] : 'new';
		$now = current_time( 'mysql', true );
		$wpdb->insert(
			self::table(),
			array(
				'module_key' => $key,
				'deal_id' => absint( $data['deal_id'] ?? 0 ),
				'external_ref' => sanitize_text_field( (string) ( $data['external_ref'] ?? '' ) ),
				'status' => $status,
				'assigned_user_id' => absint( $data['assigned_user_id'] ?? 0 ),
				'next_action' => sanitize_text_field( (string) ( $data['next_action'] ?? '' ) ),
				'due_at' => $data['due_at'] ?: null,
				'summary' => sanitize_textarea_field( (string) ( $data['summary'] ?? '' ) ),
				'payload_json' => wp_json_encode( $data['payload'] ?? array() ),
				'created_at' => $now,
				'updated_at' => $now,
			),
			array( '%s', '%d', '%s', '%s', '%d', '%s', '%s', '%s', '%s', '%s', '%s' )
		);
		$id = (int) $wpdb->insert_id;
		if ( $id && class_exists( 'ALGQ_Platform_Audit_Log' ) ) {
			ALGQ_Platform_Audit_Log::log( 'module.work_item.created', array( 'module' => $key, 'item_id' => $id, 'deal_id' => absint( $data['deal_id'] ?? 0 ), 'status' => $status ) );
		}
		if ( $id ) {
			do_action( 'algq_platform_module_item_saved', $key, $id, $data );
		}
		return $id;
	}

	/** @return array<int,array<string,mixed>> */
	private static function get_items( string $key, int $limit ): array {
		global $wpdb;
		$limit = max( 1, min( 500, $limit ) );
		$sql = $wpdb->prepare( 'SELECT * FROM ' . self::table() . ' WHERE module_key = %s ORDER BY updated_at DESC, id DESC LIMIT %d', $key, $limit );
		return (array) $wpdb->get_results( $sql, ARRAY_A );
	}

	private static function count_open( string $key ): int {
		global $wpdb;
		$sql = $wpdb->prepare( "SELECT COUNT(*) FROM " . self::table() . " WHERE module_key = %s AND status NOT IN ('complete','cancelled')", $key );
		return (int) $wpdb->get_var( $sql );
	}

	private static function count_due( string $key ): int {
		global $wpdb;
		$now = current_time( 'mysql', true );
		$sql = $wpdb->prepare( "SELECT COUNT(*) FROM " . self::table() . " WHERE module_key = %s AND due_at IS NOT NULL AND due_at <= %s AND status NOT IN ('complete','cancelled')", $key, $now );
		return (int) $wpdb->get_var( $sql );
	}

	/** @param array<int,array<string,mixed>> $items */
	private static function items_table( array $items ): string {
		if ( empty( $items ) ) {
			return '<div class="algq-platform-notice">' . esc_html__( 'No work items recorded yet.', 'algonquian-real-estate-platform' ) . '</div>';
		}
		$html = '<table class="widefat striped"><thead><tr><th>ID</th><th>Deal</th><th>Status</th><th>Summary</th><th>Next Action</th><th>Due</th></tr></thead><tbody>';
		foreach ( $items as $item ) {
			$html .= '<tr><td>' . esc_html( (string) $item['id'] ) . '</td><td>' . esc_html( (string) $item['deal_id'] ) . '</td><td>' . esc_html( ucfirst( (string) $item['status'] ) ) . '</td><td>' . esc_html( (string) $item['summary'] ) . '</td><td>' . esc_html( (string) $item['next_action'] ) . '</td><td>' . esc_html( (string) ( $item['due_at'] ?: '—' ) ) . '</td></tr>';
		}
		return $html . '</tbody></table>';
	}

	/** @param array<string,mixed> $module */
	private static function module_card( string $key, array $module ): string {
		$url = current_user_can( 'manage_algq_platform' ) ? admin_url( 'admin.php?page=algq-platform-modules&module=' . rawurlencode( $key ) ) : home_url( (string) $module['route'] );
		return '<article class="algq-module-card"><span class="algq-badge">Module ' . esc_html( (string) $module['number'] ) . '</span><h3>' . esc_html( (string) $module['name'] ) . '</h3><p>' . esc_html( (string) $module['purpose'] ) . '</p><a class="button" href="' . esc_url( $url ) . '">' . esc_html__( 'Open Workspace', 'algonquian-real-estate-platform' ) . '</a></article>';
	}

	private static function portal_context( string $key ): string {
		$context = apply_filters( 'algq_platform_' . str_replace( '-', '_', $key ) . '_context', array(), get_current_user_id() );
		if ( empty( $context ) ) {
			return '<div class="algq-platform-notice">' . esc_html__( 'No linked records are currently available for this account.', 'algonquian-real-estate-platform' ) . '</div>';
		}
		return '<pre class="algq-module-context">' . esc_html( wp_json_encode( $context, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES ) ) . '</pre>';
	}

	/** @param array<string,mixed> $module @return array<string,mixed> */
	private static function service_snapshot( array $module ): array {
		$defaults = array( 'health' => 'Local workspace ready', 'records' => 0, 'open' => 0 );
		$service_id = sanitize_text_field( (string) ( $module['service'] ?? '' ) );
		if ( '' === $service_id || ! function_exists( 'algq_platform_service_call' ) || ! algq_platform_service_available( $service_id ) ) {
			return $defaults;
		}
		$result = algq_platform_service_call( $service_id, 'snapshot', array(), array( 'caller_plugin' => 'algonquian-real-estate-platform' ) );
		if ( is_wp_error( $result ) ) {
			$defaults['health'] = 'Awaiting companion service';
			return $defaults;
		}
		return is_array( $result ) ? wp_parse_args( $result, $defaults ) : $defaults;
	}

	private static function create_missing_pages(): void {
		foreach ( self::modules() as $module ) {
			$path = trim( (string) $module['route'], '/' );
			$slug = basename( $path );
			if ( get_page_by_path( $path ) || get_page_by_path( $slug ) ) {
				continue;
			}
			wp_insert_post(
				array(
					'post_type' => 'page',
					'post_status' => 'publish',
					'post_title' => (string) $module['name'],
					'post_name' => $slug,
					'post_content' => '[vc_row full_width="stretch_row_content"][vc_column][vc_column_text][' . (string) $module['shortcode'] . '][/vc_column_text][/vc_column][/vc_row]',
				)
			);
		}
	}

	private static function table(): string {
		global $wpdb;
		return $wpdb->prefix . self::TABLE_SUFFIX;
	}

	private static function sanitize_datetime( string $value ): ?string {
		$value = sanitize_text_field( $value );
		if ( '' === $value ) {
			return null;
		}
		$timestamp = strtotime( $value );
		return false === $timestamp ? null : gmdate( 'Y-m-d H:i:s', $timestamp );
	}
}
