<?php
/**
 * About Plugin administration and public-information page.
 *
 * @package Algonquian_Deal_Intake
 */

defined( 'ABSPATH' ) || exit;

final class ALGQ_Deal_Intake_About {
	public static function register_hooks(): void {
		add_action( 'admin_menu', array( __CLASS__, 'menu' ), 99 );
		add_action( 'init', array( __CLASS__, 'register_shortcode' ) );
		add_action( 'admin_init', array( __CLASS__, 'ensure_public_page' ) );
		add_filter( 'plugin_action_links_' . plugin_basename( ALGQ_DI_FILE ), array( __CLASS__, 'plugin_action_links' ) );
	}

	public static function menu(): void {
		add_submenu_page(
			self::menu_parent(),
			__( 'About Algonquian Deal Intake', 'algq-deal-intake' ),
			__( 'About Plugin', 'algq-deal-intake' ),
			ALGQ_Deal_Intake_Security::CAP_REVIEW,
			'algq-deal-intake-about',
			array( __CLASS__, 'admin_page' )
		);
	}

	private static function menu_parent(): string {
		global $menu;

		if ( is_array( $menu ) ) {
			foreach ( $menu as $item ) {
				if ( isset( $item[2] ) && 'algq-platform' === $item[2] ) {
					return 'algq-platform';
				}
			}
		}

		return 'algq-deal-intake';
	}

	public static function plugin_action_links( array $links ): array {
		$about = '<a href="' . esc_url( admin_url( 'admin.php?page=algq-deal-intake-about' ) ) . '">' . esc_html__( 'About', 'algq-deal-intake' ) . '</a>';
		array_unshift( $links, $about );
		return $links;
	}

	public static function register_shortcode(): void {
		add_shortcode( 'algq_deal_intake_about', array( __CLASS__, 'public_shortcode' ) );
	}

	public static function ensure_public_page(): void {
		if ( current_user_can( 'activate_plugins' ) ) {
			self::create_public_page();
		}
	}

	public static function create_public_page(): int {
		$stored_id = absint( get_option( 'algq_di_about_page_id' ) );
		if ( $stored_id && 'trash' !== get_post_status( $stored_id ) ) {
			return $stored_id;
		}

		$existing = get_page_by_path( 'plugin/deal-intake/about', OBJECT, 'page' );
		if ( $existing instanceof WP_Post ) {
			update_option( 'algq_di_about_page_id', $existing->ID );
			return (int) $existing->ID;
		}

		$parent_id = self::ensure_parent_page( 'plugin', 'Plugin' );
		$parent_id = self::ensure_parent_page( 'deal-intake', 'Algonquian Deal Intake', $parent_id );

		$page_id = wp_insert_post(
			array(
				'post_title' => __( 'About Algonquian Deal Intake', 'algq-deal-intake' ),
				'post_name' => 'about',
				'post_type' => 'page',
				'post_status' => 'publish',
				'post_parent' => $parent_id,
				'post_content' => '[vc_column_text][algq_deal_intake_about][/vc_column_text]',
			),
			true
		);

		if ( is_wp_error( $page_id ) ) {
			return 0;
		}

		update_option( 'algq_di_about_page_id', (int) $page_id );
		update_post_meta( (int) $page_id, '_algq_generated_by', 'algq-deal-intake' );
		update_post_meta( (int) $page_id, '_algq_generated_version', ALGQ_DI_VERSION );
		return (int) $page_id;
	}

	private static function ensure_parent_page( string $slug, string $title, int $parent_id = 0 ): int {
		$path = 0 === $parent_id ? $slug : 'plugin/' . $slug;
		$existing = get_page_by_path( $path, OBJECT, 'page' );
		if ( $existing instanceof WP_Post ) {
			return (int) $existing->ID;
		}

		$page_id = wp_insert_post(
			array(
				'post_title' => $title,
				'post_name' => $slug,
				'post_type' => 'page',
				'post_status' => 'publish',
				'post_parent' => $parent_id,
				'post_content' => '',
			),
			true
		);

		return is_wp_error( $page_id ) ? $parent_id : (int) $page_id;
	}

	public static function admin_page(): void {
		if ( ! current_user_can( ALGQ_Deal_Intake_Security::CAP_REVIEW ) ) {
			wp_die( esc_html__( 'You do not have permission to view this plugin information.', 'algq-deal-intake' ) );
		}

		$platform_ready = defined( 'ALGQ_PLATFORM_VERSION' ) || defined( 'ALGQ_REAL_ESTATE_PLATFORM_VERSION' );
		$pipeline_ready = function_exists( 'algq_pipeline_create_deal' ) || has_filter( 'algq_pipeline_create_deal' );
		$schema_ready = ALGQ_DI_SCHEMA_VERSION === (string) get_option( 'algq_di_schema_version', '' );
		$about_url = get_permalink( absint( get_option( 'algq_di_about_page_id' ) ) );
		$docs_url = get_permalink( absint( get_option( 'algq_di_docs_page_id' ) ) );
		?>
		<div class="wrap algq-di-admin">
			<div class="algq-di-admin-header">
				<div>
					<p class="algq-di-version"><?php echo esc_html__( 'Algonquian Real Estate Technology Division', 'algq-deal-intake' ); ?></p>
					<h1><?php esc_html_e( 'About Algonquian Deal Intake', 'algq-deal-intake' ); ?></h1>
					<p><?php esc_html_e( 'The authoritative seller-lead and property-submission entry point for the Algonquian Real Estate Platform.', 'algq-deal-intake' ); ?></p>
				</div>
				<div><span class="algq-di-version"><?php echo esc_html( 'Version ' . ALGQ_DI_VERSION ); ?></span></div>
			</div>

			<div class="algq-di-kpis">
				<div class="algq-di-kpi"><strong><?php esc_html_e( 'Operational Authority', 'algq-deal-intake' ); ?></strong><span><?php esc_html_e( 'Intake', 'algq-deal-intake' ); ?></span></div>
				<div class="algq-di-kpi"><strong><?php esc_html_e( 'Canonical Handoff', 'algq-deal-intake' ); ?></strong><span><?php esc_html_e( 'Pipeline CRM', 'algq-deal-intake' ); ?></span></div>
				<div class="algq-di-kpi"><strong><?php esc_html_e( 'Minimum WordPress', 'algq-deal-intake' ); ?></strong><span>6.8</span></div>
				<div class="algq-di-kpi"><strong><?php esc_html_e( 'Minimum PHP', 'algq-deal-intake' ); ?></strong><span>8.2</span></div>
			</div>

			<h2><?php esc_html_e( 'Purpose and Authority', 'algq-deal-intake' ); ?></h2>
			<p><?php esc_html_e( 'Deal Intake records seller leads, property submissions, consent evidence, lead-source attribution, lead scoring, duplicate review, and the initial request to create a canonical Pipeline CRM deal. Once Pipeline CRM returns a canonical deal ID, Pipeline CRM owns the acquisition lifecycle.', 'algq-deal-intake' ); ?></p>

			<div class="algq-di-kpis">
				<div class="algq-di-kpi"><strong><?php esc_html_e( 'Deal Intake Owns', 'algq-deal-intake' ); ?></strong><p><?php esc_html_e( 'Seller records, property records, intake submissions, consent evidence, submission metadata, duplicate review, and initial CRM handoff requests.', 'algq-deal-intake' ); ?></p></div>
				<div class="algq-di-kpi"><strong><?php esc_html_e( 'Other Plugins Own', 'algq-deal-intake' ); ?></strong><p><?php esc_html_e( 'Pipeline stages, tasks, underwriting, offers, documents, signatures, funding, automation rules, and closing workflow.', 'algq-deal-intake' ); ?></p></div>
				<div class="algq-di-kpi"><strong><?php esc_html_e( 'Public Protection', 'algq-deal-intake' ); ?></strong><p><?php esc_html_e( 'Nonce enforcement, honeypot controls, minimum submission time, configurable rate limiting, validation, sanitization, and versioned consent evidence.', 'algq-deal-intake' ); ?></p></div>
				<div class="algq-di-kpi"><strong><?php esc_html_e( 'Data Protection', 'algq-deal-intake' ); ?></strong><p><?php esc_html_e( 'Granular capabilities, protected REST routes, prepared SQL, conservative uninstall behavior, and shared audit and mail integration hooks.', 'algq-deal-intake' ); ?></p></div>
			</div>

			<h2><?php esc_html_e( 'Integration Health', 'algq-deal-intake' ); ?></h2>
			<table class="widefat striped algq-di-table">
				<tbody>
					<tr><th scope="row"><?php esc_html_e( 'Platform Plugin', 'algq-deal-intake' ); ?></th><td><?php echo wp_kses_post( self::health_label( $platform_ready, __( 'Detected', 'algq-deal-intake' ), __( 'Not detected', 'algq-deal-intake' ) ) ); ?></td></tr>
					<tr><th scope="row"><?php esc_html_e( 'Pipeline CRM Contract', 'algq-deal-intake' ); ?></th><td><?php echo wp_kses_post( self::health_label( $pipeline_ready, __( 'Available', 'algq-deal-intake' ), __( 'Awaiting integration', 'algq-deal-intake' ) ) ); ?></td></tr>
					<tr><th scope="row"><?php esc_html_e( 'Database Schema', 'algq-deal-intake' ); ?></th><td><?php echo wp_kses_post( self::health_label( $schema_ready, __( 'Current', 'algq-deal-intake' ), __( 'Upgrade required', 'algq-deal-intake' ) ) ); ?></td></tr>
					<tr><th scope="row"><?php esc_html_e( 'Release Classification', 'algq-deal-intake' ); ?></th><td><strong><?php esc_html_e( 'Production candidate pending deployment-level acceptance testing', 'algq-deal-intake' ); ?></strong></td></tr>
				</tbody>
			</table>

			<h2><?php esc_html_e( 'Registered Shortcodes', 'algq-deal-intake' ); ?></h2>
			<p><code>[algq_deal_intake_form]</code> <code>[algq_property_submission]</code> <code>[deal_intake_form_public]</code> <code>[deal_intake_form_internal]</code> <code>[deal_quick_capture]</code> <code>[algq_homeowner_options]</code> <code>[algq_seller_portal]</code> <code>[algq_deal_intake_about]</code></p>

			<h2><?php esc_html_e( 'Plugin Information', 'algq-deal-intake' ); ?></h2>
			<table class="widefat striped algq-di-table">
				<tbody>
					<tr><th scope="row"><?php esc_html_e( 'Plugin Slug', 'algq-deal-intake' ); ?></th><td><code>algq-deal-intake</code></td></tr>
					<tr><th scope="row"><?php esc_html_e( 'Text Domain', 'algq-deal-intake' ); ?></th><td><code>algq-deal-intake</code></td></tr>
					<tr><th scope="row"><?php esc_html_e( 'Author', 'algq-deal-intake' ); ?></th><td><?php esc_html_e( 'Onegodian | Algonquian Real Estate Technology Division', 'algq-deal-intake' ); ?></td></tr>
					<tr><th scope="row"><?php esc_html_e( 'Parent Organization', 'algq-deal-intake' ); ?></th><td><?php esc_html_e( 'Algonquian Real Estate LLC', 'algq-deal-intake' ); ?></td></tr>
					<tr><th scope="row"><?php esc_html_e( 'License', 'algq-deal-intake' ); ?></th><td><?php esc_html_e( 'Proprietary / Internal Use', 'algq-deal-intake' ); ?></td></tr>
				</tbody>
			</table>

			<p class="algq-di-actions">
				<a class="button button-primary" href="<?php echo esc_url( admin_url( 'admin.php?page=algq-deal-intake' ) ); ?>"><?php esc_html_e( 'Open Deal Intake', 'algq-deal-intake' ); ?></a>
				<a class="button" href="<?php echo esc_url( admin_url( 'admin.php?page=algq-deal-intake-settings' ) ); ?>"><?php esc_html_e( 'Settings', 'algq-deal-intake' ); ?></a>
				<?php if ( $about_url ) : ?><a class="button" href="<?php echo esc_url( $about_url ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'View Public About Page', 'algq-deal-intake' ); ?></a><?php endif; ?>
				<?php if ( $docs_url ) : ?><a class="button" href="<?php echo esc_url( $docs_url ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Documentation', 'algq-deal-intake' ); ?></a><?php endif; ?>
			</p>
		</div>
		<?php
	}

	private static function health_label( bool $healthy, string $yes, string $no ): string {
		$class = $healthy ? 'algq-di-status-accepted' : 'algq-di-status-awaiting_pipeline';
		$label = $healthy ? $yes : $no;
		return '<span class="algq-di-status ' . esc_attr( $class ) . '">' . esc_html( $label ) . '</span>';
	}

	public static function public_shortcode(): string {
		ob_start();
		?>
		<section class="algq-di-shell">
			<header class="algq-di-header">
				<span class="algq-di-badge"><?php esc_html_e( 'Algonquian Real Estate Platform • Acquisition Operations', 'algq-deal-intake' ); ?></span>
				<h1><?php esc_html_e( 'Algonquian Deal Intake', 'algq-deal-intake' ); ?></h1>
				<p><?php esc_html_e( 'A controlled seller-lead and property-submission system that validates intake data, preserves consent evidence, reviews potential duplicates, scores opportunities, and requests creation of a canonical deal in Pipeline CRM.', 'algq-deal-intake' ); ?></p>
			</header>
			<h2><?php esc_html_e( 'What the Plugin Does', 'algq-deal-intake' ); ?></h2>
			<ul>
				<li><?php esc_html_e( 'Captures public and internal property submissions.', 'algq-deal-intake' ); ?></li>
				<li><?php esc_html_e( 'Records seller, property, source, campaign, timeline, and consent information.', 'algq-deal-intake' ); ?></li>
				<li><?php esc_html_e( 'Checks for likely duplicate submissions and routes them for review.', 'algq-deal-intake' ); ?></li>
				<li><?php esc_html_e( 'Calculates an initial lead-quality score from the submitted situation and timing.', 'algq-deal-intake' ); ?></li>
				<li><?php esc_html_e( 'Hands accepted opportunities to Pipeline CRM without duplicating ownership of the deal lifecycle.', 'algq-deal-intake' ); ?></li>
			</ul>
			<h2><?php esc_html_e( 'Authority Boundary', 'algq-deal-intake' ); ?></h2>
			<p><?php esc_html_e( 'Deal Intake owns intake-time records. Pipeline CRM owns the canonical deal after acceptance. Underwriting, offers, documents, signatures, funding, automation rules, and closing activity remain under their respective Algonquian platform modules.', 'algq-deal-intake' ); ?></p>
			<h2><?php esc_html_e( 'Version and Requirements', 'algq-deal-intake' ); ?></h2>
			<p><strong><?php esc_html_e( 'Version:', 'algq-deal-intake' ); ?></strong> <?php echo esc_html( ALGQ_DI_VERSION ); ?><br><strong><?php esc_html_e( 'WordPress:', 'algq-deal-intake' ); ?></strong> 6.8+<br><strong><?php esc_html_e( 'PHP:', 'algq-deal-intake' ); ?></strong> 8.2+<br><strong><?php esc_html_e( 'Author:', 'algq-deal-intake' ); ?></strong> <?php esc_html_e( 'Onegodian | Algonquian Real Estate Technology Division', 'algq-deal-intake' ); ?></p>
			<p><a class="algq-di-submit" href="<?php echo esc_url( get_permalink( absint( get_option( 'algq_di_submit_property_page_id' ) ) ) ); ?>"><?php esc_html_e( 'Open Property Submission', 'algq-deal-intake' ); ?></a></p>
		</section>
		<?php
		return (string) ob_get_clean();
	}
}

ALGQ_Deal_Intake_About::register_hooks();
register_activation_hook( ALGQ_DI_FILE, array( 'ALGQ_Deal_Intake_About', 'create_public_page' ) );
