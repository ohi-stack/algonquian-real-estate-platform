<?php
/**
 * Plugin Name: Algonquian Property Stewardship Services
 * Plugin URI: https://algonquianrealestate.com/property-stewardship-services/
 * Description: Property observation, visit reporting, vendor coordination, maintenance scheduling, emergency-contact authorization, and stewardship records for Algonquian Real Estate LLC.
 * Version: 1.0.0-rc.1
 * Author: Onegodian | Algonquian Real Estate
 * Text Domain: algq-property-stewardship
 * Requires at least: 6.0
 * Requires PHP: 7.4
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class ALGQ_Property_Stewardship {
	const VERSION = '1.0.0-rc.1';
	const CAPABILITY = 'manage_algq_stewardship';

	public static function init() {
		add_action( 'init', array( __CLASS__, 'register_post_types' ) );
		add_action( 'init', array( __CLASS__, 'register_shortcodes' ) );
		add_action( 'admin_menu', array( __CLASS__, 'register_admin_menu' ) );
	}

	public static function activate() {
		self::register_post_types();
		$admin = get_role( 'administrator' );
		if ( $admin ) {
			$admin->add_cap( self::CAPABILITY );
		}
		self::create_pages();
		flush_rewrite_rules();
	}

	public static function deactivate() {
		flush_rewrite_rules();
	}

	public static function register_post_types() {
		register_post_type(
			'algq_stewardship',
			array(
				'labels' => array(
					'name'          => __( 'Stewardship Clients', 'algq-property-stewardship' ),
					'singular_name' => __( 'Stewardship Client', 'algq-property-stewardship' ),
				),
				'public'              => false,
				'show_ui'             => true,
				'show_in_menu'        => false,
				'supports'            => array( 'title', 'editor', 'custom-fields' ),
				'capability_type'     => 'post',
				'map_meta_cap'        => true,
				'exclude_from_search' => true,
			)
		);

		register_post_type(
			'algq_steward_visit',
			array(
				'labels' => array(
					'name'          => __( 'Property Visits', 'algq-property-stewardship' ),
					'singular_name' => __( 'Property Visit', 'algq-property-stewardship' ),
				),
				'public'              => false,
				'show_ui'             => true,
				'show_in_menu'        => false,
				'supports'            => array( 'title', 'editor', 'thumbnail', 'custom-fields' ),
				'map_meta_cap'        => true,
				'exclude_from_search' => true,
			)
		);

		register_post_type(
			'algq_steward_vendor',
			array(
				'labels' => array(
					'name'          => __( 'Stewardship Vendors', 'algq-property-stewardship' ),
					'singular_name' => __( 'Stewardship Vendor', 'algq-property-stewardship' ),
				),
				'public'              => false,
				'show_ui'             => true,
				'show_in_menu'        => false,
				'supports'            => array( 'title', 'editor', 'custom-fields' ),
				'map_meta_cap'        => true,
				'exclude_from_search' => true,
			)
		);
	}

	public static function register_shortcodes() {
		add_shortcode( 'algq_property_stewardship', array( __CLASS__, 'render_service_page' ) );
		add_shortcode( 'algq_stewardship_portal', array( __CLASS__, 'render_portal' ) );
	}

	public static function register_admin_menu() {
		add_menu_page(
			__( 'Property Stewardship', 'algq-property-stewardship' ),
			__( 'Stewardship', 'algq-property-stewardship' ),
			self::CAPABILITY,
			'algq-property-stewardship',
			array( __CLASS__, 'render_admin_dashboard' ),
			'dashicons-shield-alt',
			27
		);
		add_submenu_page( 'algq-property-stewardship', __( 'Clients', 'algq-property-stewardship' ), __( 'Clients', 'algq-property-stewardship' ), self::CAPABILITY, 'edit.php?post_type=algq_stewardship' );
		add_submenu_page( 'algq-property-stewardship', __( 'Property Visits', 'algq-property-stewardship' ), __( 'Property Visits', 'algq-property-stewardship' ), self::CAPABILITY, 'edit.php?post_type=algq_steward_visit' );
		add_submenu_page( 'algq-property-stewardship', __( 'Vendors', 'algq-property-stewardship' ), __( 'Vendors', 'algq-property-stewardship' ), self::CAPABILITY, 'edit.php?post_type=algq_steward_vendor' );
	}

	private static function service_cards() {
		return array(
			'Property Watch' => 'Scheduled exterior observations, time-stamped photographs, condition summaries, and notification of visible concerns.',
			'Active Stewardship' => 'Maintenance scheduling, vendor coordination, seasonal oversight, storm observations, and documented follow-up.',
			'Transition Stewardship' => 'Property-readiness planning, clean-out and repair coordination, document organization, and owner-directed professional referrals.',
		);
	}

	public static function render_service_page() {
		ob_start();
		?>
		<section class="algq-stewardship-service">
			<p class="algq-stewardship-eyebrow"><?php esc_html_e( 'Property Coordination • Owner-Directed Services', 'algq-property-stewardship' ); ?></p>
			<h2><?php esc_html_e( 'Property Stewardship Services™', 'algq-property-stewardship' ); ?></h2>
			<p><?php esc_html_e( 'Even if you are not ready to sell, Algonquian Real Estate can help you protect one of your most valuable assets through structured property observation, communication, and service coordination.', 'algq-property-stewardship' ); ?></p>
			<div class="algq-stewardship-grid">
				<?php foreach ( self::service_cards() as $title => $copy ) : ?>
					<article><h3><?php echo esc_html( $title ); ?></h3><p><?php echo esc_html( $copy ); ?></p></article>
				<?php endforeach; ?>
			</div>
			<div class="algq-stewardship-boundary">
				<h3><?php esc_html_e( 'Defined Service Boundaries', 'algq-property-stewardship' ); ?></h3>
				<p><?php esc_html_e( 'These services do not constitute legal advice, estate planning, fiduciary decision-making, guardianship, insurance adjustment, licensed inspection, security services, or guaranteed prevention of loss. All activity remains subject to written owner authorization.', 'algq-property-stewardship' ); ?></p>
			</div>
		</section>
		<?php
		return ob_get_clean();
	}

	public static function render_portal() {
		if ( ! is_user_logged_in() ) {
			return '<p>' . esc_html__( 'Please sign in to access stewardship records.', 'algq-property-stewardship' ) . '</p>';
		}
		return '<section class="algq-stewardship-portal"><h2>' . esc_html__( 'Property Stewardship Portal', 'algq-property-stewardship' ) . '</h2><p>' . esc_html__( 'Authorized clients can review visit reports, photographs, maintenance history, vendor activity, documents, and approved follow-up items.', 'algq-property-stewardship' ) . '</p></section>';
	}

	public static function render_admin_dashboard() {
		if ( ! current_user_can( self::CAPABILITY ) ) {
			wp_die( esc_html__( 'Insufficient permissions.', 'algq-property-stewardship' ) );
		}
		$counts = array(
			'Clients' => wp_count_posts( 'algq_stewardship' )->publish,
			'Visits'  => wp_count_posts( 'algq_steward_visit' )->publish,
			'Vendors' => wp_count_posts( 'algq_steward_vendor' )->publish,
		);
		echo '<div class="wrap"><h1>' . esc_html__( 'Property Stewardship Services', 'algq-property-stewardship' ) . '</h1><p>' . esc_html__( 'Operational dashboard for owner-authorized property observations, visit reports, vendor coordination, maintenance scheduling, emergency contacts, and transition support.', 'algq-property-stewardship' ) . '</p><ul>';
		foreach ( $counts as $label => $count ) {
			echo '<li><strong>' . esc_html( $label ) . ':</strong> ' . esc_html( (string) $count ) . '</li>';
		}
		echo '</ul></div>';
	}

	private static function create_pages() {
		$pages = array(
			'property-stewardship-services' => array( 'Property Stewardship Services', '[vc_column_text][algq_property_stewardship][/vc_column_text]' ),
			'property-stewardship-portal'   => array( 'Property Stewardship Portal', '[vc_column_text][algq_stewardship_portal][/vc_column_text]' ),
		);
		foreach ( $pages as $slug => $definition ) {
			if ( get_page_by_path( $slug, OBJECT, 'page' ) ) {
				continue;
			}
			wp_insert_post( array( 'post_title' => $definition[0], 'post_name' => $slug, 'post_content' => $definition[1], 'post_status' => 'publish', 'post_type' => 'page' ) );
		}
	}
}

register_activation_hook( __FILE__, array( 'ALGQ_Property_Stewardship', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'ALGQ_Property_Stewardship', 'deactivate' ) );
ALGQ_Property_Stewardship::init();
