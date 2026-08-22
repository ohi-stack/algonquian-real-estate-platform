<?php
/**
 * Plugin Name: Algonquian Enterprise Mega Menu
 * Plugin URI: https://algonquianrealestate.com
 * Description: Accessible enterprise mega menu for Algonquian Real Estate public services, acquisitions, investors, technology, documents, and account access.
 * Version: 1.0.0-rc.1
 * Author: Onegodian | Algonquian Real Estate
 * Text Domain: algq-enterprise-mega-menu
 * Requires at least: 6.0
 * Requires PHP: 7.4
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class ALGQ_Enterprise_Mega_Menu {
	const VERSION = '1.0.0-rc.1';

	public static function init() {
		add_action( 'init', array( __CLASS__, 'register_shortcode' ) );
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue_assets' ) );
	}

	public static function register_shortcode() {
		add_shortcode( 'algq_mega_menu', array( __CLASS__, 'render' ) );
	}

	public static function enqueue_assets() {
		$base = plugin_dir_url( __FILE__ );
		$dir  = plugin_dir_path( __FILE__ );
		$css  = $dir . 'assets/algq-mega-menu.css';
		$js   = $dir . 'assets/algq-mega-menu.js';

		if ( file_exists( $css ) ) {
			wp_enqueue_style( 'algq-enterprise-mega-menu', $base . 'assets/algq-mega-menu.css', array(), filemtime( $css ) );
		}

		if ( file_exists( $js ) ) {
			wp_enqueue_script( 'algq-enterprise-mega-menu', $base . 'assets/algq-mega-menu.js', array(), filemtime( $js ), true );
		}
	}

	private static function groups() {
		return array(
			'property-owners' => array(
				'label' => __( 'Property Owners', 'algq-enterprise-mega-menu' ),
				'intro' => __( 'Explore selling, stewardship, inherited-property, and transition options.', 'algq-enterprise-mega-menu' ),
				'links' => array(
					array( 'label' => 'Sell Your Property', 'url' => '/sell-your-property/', 'copy' => 'Submit a property for confidential review.' ),
					array( 'label' => 'What Are My Options?', 'url' => '/property-options/', 'copy' => 'Compare sale, hold, financing, and transition paths.' ),
					array( 'label' => 'Property Stewardship', 'url' => '/property-stewardship-services/', 'copy' => 'Owner-authorized property observation and coordination.' ),
					array( 'label' => 'Inherited Property Guidance', 'url' => '/inherited-property-guidance/', 'copy' => 'Review practical options for inherited real estate.' ),
					array( 'label' => 'Senior Property Assistance', 'url' => '/senior-property-assistance/', 'copy' => 'Support for aging owners, downsizing, and property care.' ),
					array( 'label' => 'Trusted Property Contact', 'url' => '/trusted-property-contact/', 'copy' => 'A local point of contact for property-related coordination.' ),
				),
			),
			'acquisitions' => array(
				'label' => __( 'Acquisitions', 'algq-enterprise-mega-menu' ),
				'intro' => __( 'Learn how Algonquian Real Estate sources, evaluates, and structures opportunities.', 'algq-enterprise-mega-menu' ),
				'links' => array(
					array( 'label' => 'Acquisition Criteria', 'url' => '/acquisition-criteria/', 'copy' => 'Target property types, markets, and transaction characteristics.' ),
					array( 'label' => 'Development Concepts', 'url' => '/development-concepts/', 'copy' => 'Conceptual redevelopment and adaptive-use presentations.' ),
					array( 'label' => 'Multifamily', 'url' => '/multifamily/', 'copy' => 'Connecticut multifamily acquisition and development focus.' ),
					array( 'label' => 'Commercial Properties', 'url' => '/commercial-real-estate/', 'copy' => 'Mixed-use, office, industrial, and small commercial opportunities.' ),
					array( 'label' => 'Seller Financing', 'url' => '/seller-financing/', 'copy' => 'Flexible owner-financed acquisition structures.' ),
					array( 'label' => 'Submit a Deal', 'url' => '/sell-your-property/', 'copy' => 'Send property information to the acquisition team.' ),
				),
			),
			'investors' => array(
				'label' => __( 'Investors & Capital', 'algq-enterprise-mega-menu' ),
				'intro' => __( 'Access investor education, buyer registration, deal distribution, and capital relationships.', 'algq-enterprise-mega-menu' ),
				'links' => array(
					array( 'label' => 'Investor Network', 'url' => '/investors/', 'copy' => 'Join the Connecticut investor and capital-partner network.' ),
					array( 'label' => 'Buyer Registration', 'url' => '/buyers-register/', 'copy' => 'Create a buyer profile and acquisition criteria.' ),
					array( 'label' => 'Buyer Portal', 'url' => '/buyer-dashboard/', 'copy' => 'Review authorized deal packages and buyer materials.' ),
					array( 'label' => 'Private Capital', 'url' => '/private-capital/', 'copy' => 'Information for lenders, JV partners, and strategic capital.' ),
					array( 'label' => 'Funding Relationships', 'url' => '/funding-dashboard/', 'copy' => 'Track or coordinate approved funding relationships.' ),
					array( 'label' => 'Investor Resources', 'url' => '/investor-resources/', 'copy' => 'Guides on underwriting, deal packages, and partnership readiness.' ),
				),
			),
			'platform' => array(
				'label' => __( 'Technology Platform', 'algq-enterprise-mega-menu' ),
				'intro' => __( 'Explore the proprietary software and operating infrastructure developed by ARE Tech.', 'algq-enterprise-mega-menu' ),
				'links' => array(
					array( 'label' => 'Platform Overview', 'url' => '/algonquian-real-estate-platform/', 'copy' => 'Unified acquisition, underwriting, document, and reporting platform.' ),
					array( 'label' => 'Plugin Suite', 'url' => '/plugins/', 'copy' => 'View the protected foundation and supporting plugin catalog.' ),
					array( 'label' => 'Pipeline CRM', 'url' => '/plugin/pipeline-crm/', 'copy' => 'Canonical deal records and acquisition lifecycle management.' ),
					array( 'label' => 'MAO Engine', 'url' => '/plugin/mao-engine/', 'copy' => 'Versioned underwriting and maximum allowable offer analysis.' ),
					array( 'label' => 'Document Library', 'url' => '/plugin/document-library/', 'copy' => 'Controlled institutional records, templates, and packages.' ),
					array( 'label' => 'Automation Engine', 'url' => '/plugin/automation-engine/', 'copy' => 'Trigger-based workflow coordination and system actions.' ),
				),
			),
			'documents' => array(
				'label' => __( 'Documents & Resources', 'algq-enterprise-mega-menu' ),
				'intro' => __( 'Review public information, documentation categories, guides, and company resources.', 'algq-enterprise-mega-menu' ),
				'links' => array(
					array( 'label' => 'Document Library', 'url' => '/documents/', 'copy' => 'Institutional entity, financing, acquisition, and compliance records.' ),
					array( 'label' => 'Forms & Documents', 'url' => '/forms-documents/', 'copy' => 'Overview of standard forms available during active review.' ),
					array( 'label' => 'Plugin Guides', 'url' => '/plugin-guides/', 'copy' => 'User, administrator, configuration, and technical guides.' ),
					array( 'label' => 'Digital Store', 'url' => '/digital-store/', 'copy' => 'Templates, calculators, forms, workflows, and digital products.' ),
					array( 'label' => 'Property Resources', 'url' => '/resources/', 'copy' => 'Educational material for owners, buyers, and investors.' ),
					array( 'label' => 'Contact', 'url' => '/contact/', 'copy' => 'Request information or speak with Algonquian Real Estate.' ),
				),
			),
		);
	}

	public static function render() {
		$groups = self::groups();
		ob_start();
		?>
		<nav class="algq-mega-menu" aria-label="<?php esc_attr_e( 'Algonquian Real Estate primary navigation', 'algq-enterprise-mega-menu' ); ?>">
			<div class="algq-mega-menu__bar">
				<a class="algq-mega-menu__brand" href="<?php echo esc_url( home_url( '/' ) ); ?>">
					<span class="algq-mega-menu__brand-mark" aria-hidden="true">ARE</span>
					<span><strong><?php esc_html_e( 'Algonquian Real Estate', 'algq-enterprise-mega-menu' ); ?></strong><small><?php esc_html_e( 'Connecticut Real Estate & Technology', 'algq-enterprise-mega-menu' ); ?></small></span>
				</a>
				<button class="algq-mega-menu__mobile-toggle" type="button" aria-expanded="false" aria-controls="algq-mega-menu-primary">
					<span><?php esc_html_e( 'Menu', 'algq-enterprise-mega-menu' ); ?></span>
					<span aria-hidden="true">☰</span>
				</button>
				<div id="algq-mega-menu-primary" class="algq-mega-menu__primary">
					<a class="algq-mega-menu__home" href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php esc_html_e( 'Home', 'algq-enterprise-mega-menu' ); ?></a>
					<?php foreach ( $groups as $key => $group ) : ?>
						<div class="algq-mega-menu__item">
							<button type="button" class="algq-mega-menu__trigger" aria-expanded="false" aria-controls="algq-mega-panel-<?php echo esc_attr( $key ); ?>">
								<?php echo esc_html( $group['label'] ); ?><span aria-hidden="true">⌄</span>
							</button>
							<section id="algq-mega-panel-<?php echo esc_attr( $key ); ?>" class="algq-mega-menu__panel" hidden>
								<div class="algq-mega-menu__panel-intro">
									<p class="algq-mega-menu__eyebrow"><?php esc_html_e( 'Algonquian Real Estate', 'algq-enterprise-mega-menu' ); ?></p>
									<h2><?php echo esc_html( $group['label'] ); ?></h2>
									<p><?php echo esc_html( $group['intro'] ); ?></p>
								</div>
								<div class="algq-mega-menu__links">
									<?php foreach ( $group['links'] as $link ) : ?>
										<a href="<?php echo esc_url( home_url( $link['url'] ) ); ?>"><strong><?php echo esc_html( $link['label'] ); ?></strong><span><?php echo esc_html( $link['copy'] ); ?></span></a>
									<?php endforeach; ?>
								</div>
							</section>
						</div>
					<?php endforeach; ?>
					<div class="algq-mega-menu__actions">
						<a href="<?php echo esc_url( home_url( '/contact/' ) ); ?>"><?php esc_html_e( 'Contact', 'algq-enterprise-mega-menu' ); ?></a>
						<a class="algq-mega-menu__cta" href="<?php echo esc_url( home_url( '/sell-your-property/' ) ); ?>"><?php esc_html_e( 'Submit a Property', 'algq-enterprise-mega-menu' ); ?></a>
					</div>
				</div>
			</div>
		</nav>
		<?php
		return ob_get_clean();
	}
}

ALGQ_Enterprise_Mega_Menu::init();
