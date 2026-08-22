<?php
/**
 * Plugin Name: Algonquian Navigation
 * Plugin URI: https://algonquianrealestate.com/algonquian-navigation/
 * Description: Responsive 6 × 6 enterprise navigation and four-column footer for Algonquian Real Estate.
 * Version: 0.2.0
 * Author: Algonquian Real Estate, LLC
 * Author URI: https://algonquianrealestate.com/technology/
 * Text Domain: algonquian-navigation
 * Requires at least: 6.8
 * Requires PHP: 8.2
 * License: Proprietary
 *
 * @package AlgonquianNavigation
 */

defined( 'ABSPATH' ) || exit;

if ( ! defined( 'ALGQ_NAVIGATION_VERSION' ) ) {
	define( 'ALGQ_NAVIGATION_VERSION', '0.2.0' );
}

if ( ! defined( 'ALGQ_NAVIGATION_FILE' ) ) {
	define( 'ALGQ_NAVIGATION_FILE', __FILE__ );
}

if ( ! defined( 'ALGQ_NAVIGATION_DIR' ) ) {
	define( 'ALGQ_NAVIGATION_DIR', plugin_dir_path( __FILE__ ) );
}

if ( ! defined( 'ALGQ_NAVIGATION_URL' ) ) {
	define( 'ALGQ_NAVIGATION_URL', plugin_dir_url( __FILE__ ) );
}

final class ALGQ_Navigation {
	private const STYLE_HANDLE = 'algq-navigation';
	private const SCRIPT_HANDLE = 'algq-navigation';

	public static function init(): void {
		add_action( 'init', array( __CLASS__, 'register_shortcodes' ), 20 );
		add_action( 'after_setup_theme', array( __CLASS__, 'register_menu_locations' ) );
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue_assets' ) );
	}

	public static function activate(): void {
		update_option( 'algq_navigation_version', ALGQ_NAVIGATION_VERSION );
	}

	public static function deactivate(): void {
		// Navigation contains no scheduled jobs or durable operational records.
	}

	public static function register_menu_locations(): void {
		register_nav_menus(
			array(
				'algq_primary_menu'     => __( 'Algonquian Primary Mega Menu', 'algonquian-navigation' ),
				'algq_utility_menu'     => __( 'Algonquian Utility Navigation', 'algonquian-navigation' ),
				'algq_mobile_menu'      => __( 'Algonquian Mobile Navigation', 'algonquian-navigation' ),
				'algq_footer_company'   => __( 'Footer Company Links', 'algonquian-navigation' ),
				'algq_footer_property'  => __( 'Footer Property & Services Links', 'algonquian-navigation' ),
				'algq_footer_investors' => __( 'Footer Investor & Platform Links', 'algonquian-navigation' ),
				'algq_footer_legal'     => __( 'Footer Resources & Legal Links', 'algonquian-navigation' ),
			)
		);
	}

	public static function register_shortcodes(): void {
		if ( ! shortcode_exists( 'algq_mega_menu' ) ) {
			add_shortcode( 'algq_mega_menu', array( __CLASS__, 'mega_menu_shortcode' ) );
		}

		if ( ! shortcode_exists( 'algq_footer_links' ) ) {
			add_shortcode( 'algq_footer_links', array( __CLASS__, 'footer_shortcode' ) );
		}
	}

	public static function enqueue_assets(): void {
		$css = ALGQ_NAVIGATION_DIR . 'assets/algq-navigation.css';
		$js  = ALGQ_NAVIGATION_DIR . 'assets/algq-navigation.js';

		if ( is_file( $css ) ) {
			wp_enqueue_style(
				self::STYLE_HANDLE,
				ALGQ_NAVIGATION_URL . 'assets/algq-navigation.css',
				array(),
				(string) filemtime( $css )
			);
		}

		if ( is_file( $js ) ) {
			wp_enqueue_script(
				self::SCRIPT_HANDLE,
				ALGQ_NAVIGATION_URL . 'assets/algq-navigation.js',
				array(),
				(string) filemtime( $js ),
				true
			);
		}
	}

	public static function mega_menu_shortcode(): string {
		return self::render_navigation();
	}

	public static function footer_shortcode(): string {
		return self::render_footer();
	}

	/**
	 * @return array<string,array<string,mixed>>
	 */
	public static function schema(): array {
		$schema = array(
			'property-owners' => array(
				'label'   => 'Property Owners',
				'url'     => '/property-owners/',
				'columns' => array(
					'Explore Your Options' => array(
						'Property Owner Overview' => '/property-owners/',
						'What Are My Options?' => '/property-options/',
						'Request a Property Review' => '/request-property-review/',
						'Property Options Consultation' => '/property-consultation/',
						'Property Owner Resources' => '/property-owner-resources/',
						'Property Owner FAQ' => '/faq/',
					),
					'Sell a Property' => array(
						'Sell Your Property' => '/sell-your-property/',
						'Submit a Property' => '/submit-a-property/',
						'Sell As-Is' => '/sell-as-is/',
						'Direct Sale' => '/direct-sale/',
						'Traditional Sale' => '/traditional-sale/',
						'Property Sale Readiness' => '/property-sale-readiness/',
					),
					'Planning and Transitions' => array(
						'Property Transition Planning' => '/property-transition-planning/',
						'Legacy Conversation' => '/legacy-conversation/',
						'Property Legacy Planning' => '/property-legacy-planning/',
						'Empty Nest Transition' => '/empty-nest-transition/',
						'Downsizing Support' => '/senior-property-assistance/downsizing/',
						'Preparing for a Future Move' => '/property-transition-planning/',
					),
					'Inherited and Estate Property' => array(
						'Inherited Property Guidance' => '/inherited-property-guidance/',
						'Estate Transition Assistance' => '/estate-transition-assistance/',
						'Property Valuation Coordination' => '/estate-transition-assistance/property-valuation/',
						'Clean-Out Coordination' => '/estate-transition-assistance/cleanout/',
						'Contractor Coordination' => '/estate-transition-assistance/contractors/',
						'Property Sale Options' => '/estate-transition-assistance/sale-options/',
					),
					'Senior Property Assistance' => array(
						'Senior Property Assistance' => '/senior-property-assistance/',
						'Aging in Place' => '/senior-property-assistance/aging-in-place/',
						'Maintenance Coordination' => '/senior-property-assistance/home-maintenance-coordination/',
						'Future Property Planning' => '/senior-property-assistance/next-generation/',
						'Selling When the Time Is Right' => '/sell-your-property/',
						'Request Assistance' => '/senior-property-assistance/consultation/',
					),
					'Stewardship and Property Care' => array(
						'Property Stewardship Services' => '/property-stewardship-services/',
						'Trusted Property Contact' => '/trusted-property-contact/',
						'Property Check-Ins' => '/property-check-ins/',
						'Vacant Property Monitoring' => '/vacant-property-monitoring/',
						'Community Property Preservation' => '/community-property-preservation/',
						'Stewardship Portal' => '/client-portal/',
					),
				),
			),
			'acquisitions' => array(
				'label'   => 'Acquisitions',
				'url'     => '/acquisitions/',
				'columns' => array(
					'Acquisition Overview' => array(
						'Acquisitions' => '/acquisitions/',
						'Acquisition Criteria' => '/acquisition-criteria/',
						'Acquisition Process' => '/acquisitions/acquisition-process/',
						'How We Review Properties' => '/acquisitions/how-we-review-properties/',
						'Target Connecticut Markets' => '/connecticut/',
						'Acquisition FAQ' => '/acquisitions/faq/',
					),
					'Submit Opportunities' => array(
						'Submit a Property' => '/submit-a-property/',
						'Submit a Deal' => '/submit-a-deal/',
						'Broker Submissions' => '/acquisitions/broker-submissions/',
						'Wholesaler Submissions' => '/acquisitions/wholesaler-submissions/',
						'Attorney and Estate Referrals' => '/acquisitions/attorney-estate-referrals/',
						'Referral Partners' => '/acquisitions/referral-partners/',
					),
					'Property Types' => array(
						'Three-Family Properties' => '/acquisitions/three-family/',
						'Small Multifamily' => '/acquisitions/multifamily/',
						'Residential Properties' => '/acquisitions/residential/',
						'Mixed-Use Properties' => '/acquisitions/mixed-use/',
						'Commercial Properties' => '/acquisitions/commercial/',
						'Development Sites' => '/development-concepts/',
					),
					'Opportunity Types' => array(
						'Off-Market Properties' => '/acquisitions/off-market-properties/',
						'Value-Add Properties' => '/acquisitions/value-add-properties/',
						'Distressed Properties' => '/acquisitions/distressed-properties/',
						'Vacant Properties' => '/acquisitions/vacant-properties/',
						'Underutilized Properties' => '/acquisitions/underutilized-properties/',
						'Tired Landlord Opportunities' => '/sell-your-property/tired-landlord/',
					),
					'Transaction Structures' => array(
						'Conventional Acquisition' => '/acquisitions/conventional-acquisition/',
						'Seller Financing' => '/acquisitions/seller-financed-opportunities/',
						'Joint Venture Acquisitions' => '/acquisitions/joint-venture-acquisitions/',
						'Private Capital Acquisitions' => '/private-lenders/',
						'Subject-To Opportunities' => '/acquisitions/subject-to-acquisitions/',
						'Flexible Transaction Structures' => '/acquisitions/',
					),
					'Underwriting and Due Diligence' => array(
						'Underwriting Overview' => '/underwriting/',
						'Maximum Allowable Offer' => '/technology/plugins/mao-engine/',
						'Property Due Diligence' => '/acquisitions/due-diligence/',
						'Financial Review' => '/underwriting/',
						'Risk Review' => '/underwriting/',
						'Required Documents' => '/forms-documents/',
					),
				),
			),
			'investors' => array(
				'label'   => 'Investors & Capital',
				'url'     => '/investors/',
				'columns' => array(
					'Investor Network' => array(
						'Investor Overview' => '/investors/',
						'Join the Investor Network' => '/investors/#buyer-access',
						'Investor Criteria' => '/investors/investor-criteria/',
						'Connecticut Opportunities' => '/marketplace/',
						'Investor FAQ' => '/investors/faq/',
						'Investor Disclosures' => '/investors/disclosures/',
					),
					'Buyers' => array(
						'Buyer Registration' => '/buyers-register/',
						'Buyer Login' => '/buyer-login/',
						'Buyer Dashboard' => '/buyer-dashboard/',
						'Available Deals' => '/marketplace/',
						'Buyer Marketplace' => '/marketplace/',
						'Buyer Profile' => '/buyer-dashboard/',
					),
					'Private Capital' => array(
						'Private Lenders' => '/private-lenders/',
						'Equity Partners' => '/equity-partners/',
						'Joint Venture Partners' => '/joint-venture-partners/',
						'Capital Partner Network' => '/investors/',
						'Funding Relationships' => '/funding/',
						'Submit an Investor Profile' => '/contact/',
					),
					'Deal Access' => array(
						'Deal Marketplace' => '/marketplace/',
						'Available Opportunities' => '/marketplace/',
						'Deal Packages' => '/marketplace/',
						'NDA Access' => '/marketplace/',
						'Submit Interest' => '/marketplace/',
						'Submit an Offer' => '/marketplace/',
					),
					'Investor Resources' => array(
						'How We Review Deals' => '/acquisitions/how-we-review-properties/',
						'Investor Readiness' => '/investors/investor-readiness/',
						'Underwriting Standards' => '/underwriting/',
						'Deal Package Guide' => '/forms-documents/',
						'Private Lending Guide' => '/private-lenders/',
						'Joint Venture Guide' => '/joint-venture-partners/',
					),
					'Lenders' => array(
						'Lender Overview' => '/private-lenders/',
						'Lender Resources' => '/forms-documents/',
						'Company Overview' => '/about/',
						'Business Plan' => '/business-plan/',
						'Acquisition Criteria' => '/acquisition-criteria/',
						'Request Documentation Access' => '/contact/',
					),
				),
			),
			'services' => array(
				'label'   => 'Services',
				'url'     => '/services/',
				'columns' => array(
					'Property Services' => array(
						'Services Overview' => '/services/',
						'Request a Service Consultation' => '/contact/',
						'Submit a Service Request' => '/client-portal/',
						'Property Condition Review' => '/request-property-review/',
						'Property Document Organization' => '/forms-documents/',
						'Service FAQ' => '/faq/',
					),
					'Property Stewardship' => array(
						'Property Stewardship Services' => '/property-stewardship-services/',
						'Essential Property Watch' => '/property-stewardship-services/',
						'Active Stewardship' => '/property-stewardship-services/',
						'Transition Stewardship' => '/property-stewardship-services/',
						'Stewardship Enrollment' => '/contact/',
						'Stewardship Portal' => '/client-portal/',
					),
					'Property Monitoring' => array(
						'Property Check-Ins' => '/property-check-ins/',
						'Exterior Check-Ins' => '/property-check-ins/',
						'Authorized Interior Check-Ins' => '/property-check-ins/',
						'Photo and Condition Reports' => '/client-portal/',
						'Vacant Property Monitoring' => '/vacant-property-monitoring/',
						'Storm Property Reviews' => '/property-check-ins/',
					),
					'Maintenance Coordination' => array(
						'Maintenance Coordination' => '/services/',
						'Vendor Coordination' => '/vendor-coordination/',
						'Contractor Coordination' => '/services/',
						'Lawn and Exterior Oversight' => '/services/',
						'Seasonal Property Reviews' => '/property-check-ins/',
						'Repair Scheduling' => '/services/',
					),
					'Property Transition Support' => array(
						'Prepare a Property for Sale' => '/prepare-property-for-sale/',
						'Clean-Out Coordination' => '/estate-transition-assistance/cleanout/',
						'Repair Coordination' => '/services/',
						'Market Readiness Review' => '/property-sale-readiness/',
						'Transition Planning' => '/property-transition-planning/',
						'Sale Options Review' => '/property-options/',
					),
					'Specialized Assistance' => array(
						'Trusted Property Contact' => '/trusted-property-contact/',
						'Senior Property Assistance' => '/senior-property-assistance/',
						'Property Rescue Services' => '/property-rescue-services/',
						'Community Property Preservation' => '/community-property-preservation/',
						'Open Space & Community Land Initiative' => '/open-space-awareness/',
						'Property Legacy Planning' => '/property-legacy-planning/',
					),
				),
			),
			'technology' => array(
				'label'   => 'Technology',
				'url'     => '/technology/',
				'columns' => array(
					'Technology Division' => array(
						'Technology Overview' => '/technology/',
						'ARE Technology Division' => '/technology/',
						'Technology Strategy' => '/technology/',
						'Platform Architecture' => '/technology/platform/',
						'Development Roadmap' => '/technology/',
						'Technology Contact' => '/contact/',
					),
					'Platform' => array(
						'Algonquian Real Estate Platform' => '/algonquian-real-estate-platform/',
						'Platform Overview' => '/technology/platform/',
						'Shared Services' => '/technology/platform/',
						'Security and Permissions' => '/technology/platform/',
						'System Health' => '/technology/platform/',
						'Platform Documentation' => '/plugin/platform/docs/',
					),
					'Acquisition Systems' => array(
						'Algonquian Deal Intake' => '/algonquian-deal-intake/',
						'Algonquian Pipeline CRM' => '/algonquian-pipeline-crm/',
						'Algonquian MAO Engine' => '/algonquian-mao-engine/',
						'Algonquian Offer Generator' => '/algonquian-offer-generator/',
						'Deal Marketplace' => '/algonquian-deal-marketplace/',
						'Buyer Portal' => '/algonquian-buyer-portal/',
					),
					'Operations Systems' => array(
						'Funding Tracker' => '/algonquian-funding-tracker/',
						'Automation Engine' => '/algonquian-automation-engine/',
						'Document Library' => '/algonquian-document-library/',
						'PDF and Signature Engine' => '/algonquian-pdf-signature-engine/',
						'Admin Command Center' => '/algonquian-admin-command-center/',
						'Reporting and Analytics' => '/algonquian-admin-command-center/',
					),
					'Commerce and Products' => array(
						'Digital Store' => '/store/',
						'Product Vault' => '/product-vault/',
						'WooCommerce Bridge' => '/algq-woocommerce-bridge/',
						'Software Licensing' => '/technology/',
						'Digital Products' => '/algonquian-digital-products/',
						'Customer Account' => '/my-account/',
					),
					'Guides and Documentation' => array(
						'Plugin Library' => '/plugin/',
						'Getting Started' => '/plugin/platform/start/',
						'User Guides' => '/documentation/',
						'Administrator Guides' => '/documentation/',
						'Technical Documentation' => '/documentation/',
						'Troubleshooting' => '/documentation/',
					),
				),
			),
			'company' => array(
				'label'   => 'Company',
				'url'     => '/about/',
				'columns' => array(
					'About Algonquian' => array(
						'About Algonquian Real Estate' => '/about/',
						'Company Overview' => '/about/company-overview/',
						'Mission and Vision' => '/mission/',
						'Company Development History' => '/company-development-history/',
						'Connecticut Focus' => '/connecticut/',
						'Land, Names & Living History' => '/land-names-living-history/',
					),
					'Leadership' => array(
						'Leadership' => '/about/leadership/',
						'Founder and Managing Member' => '/about/leadership/',
						'Management Structure' => '/about/leadership/',
						'Governance' => '/about/company-overview/',
						'Company Responsibilities' => '/about/company-overview/',
						'Contact Leadership' => '/contact/',
					),
					'Company Information' => array(
						'Legal Entity Overview' => '/about/company-overview/',
						'Company Status' => '/about/company-overview/',
						'Principal Office' => '/contact/',
						'Registered Agent' => '/about/company-overview/',
						'Company Documentation' => '/forms-documents/',
						'Forms and Documents' => '/forms-documents/',
					),
					'Resources' => array(
						'Articles and Insights' => '/insights/',
						'Property Owner Resources' => '/property-owner-resources/',
						'Investor Resources' => '/investors/',
						'Lender Resources' => '/private-lenders/',
						'Frequently Asked Questions' => '/faq/',
						'News and Updates' => '/insights/',
					),
					'Work With Us' => array(
						'Contact Algonquian Real Estate' => '/contact/',
						'Property Inquiry' => '/submit-a-property/',
						'Investor Inquiry' => '/investors/',
						'Lender Inquiry' => '/private-lenders/',
						'Vendor Inquiry' => '/contact/',
						'Partnership Inquiry' => '/contact/',
					),
					'Legal and Compliance' => array(
						'Privacy Policy' => '/privacy-policy/',
						'Terms of Use' => '/terms-of-use/',
						'Website Disclaimer' => '/disclaimer/',
						'Professional Services Disclaimer' => '/professional-boundaries/',
						'Investment Disclosures' => '/investors/disclosures/',
						'Accessibility Statement' => '/accessibility/',
					),
				),
			),
		);

		return (array) apply_filters( 'algq_navigation_schema', $schema );
	}

	/**
	 * @return array<string,array<string,string>>
	 */
	public static function utilities(): array {
		$utilities = array(
			'search' => array( 'label' => 'Search', 'url' => '/?s=', 'icon' => 'search' ),
			'buyer-login' => array( 'label' => 'Buyer Login', 'url' => '/buyer-login/', 'icon' => 'user' ),
			'client-portal' => array( 'label' => 'Client Portal', 'url' => '/client-portal/', 'icon' => 'lock' ),
			'contact' => array( 'label' => 'Contact', 'url' => '/contact/', 'icon' => 'mail' ),
			'submit' => array( 'label' => 'Submit a Property', 'url' => '/submit-a-property/', 'icon' => 'home', 'class' => 'algq-navigation__utility--primary' ),
		);

		return (array) apply_filters( 'algq_navigation_utilities', $utilities );
	}

	/**
	 * @return array<string,array<string,string>>
	 */
	public static function footer_schema(): array {
		$footer = array(
			'Company' => array(
				'Home' => '/',
				'About Algonquian Real Estate' => '/about/',
				'Leadership' => '/about/leadership/',
				'Company Development History' => '/company-development-history/',
				'Connecticut Focus' => '/connecticut/',
				'Technology Division' => '/technology/',
			),
			'Property Owners & Services' => array(
				'Property Owners' => '/property-owners/',
				'What Are My Options?' => '/property-options/',
				'Sell Your Property' => '/sell-your-property/',
				'Submit a Property' => '/submit-a-property/',
				'Property Stewardship Services' => '/property-stewardship-services/',
				'Trusted Property Contact' => '/trusted-property-contact/',
			),
			'Investors & Platform' => array(
				'Acquisitions' => '/acquisitions/',
				'Acquisition Criteria' => '/acquisition-criteria/',
				'Investor Network' => '/investors/',
				'Buyer Registration' => '/buyers-register/',
				'Deal Marketplace' => '/marketplace/',
				'Technology Platform' => '/technology/',
			),
			'Resources & Legal' => array(
				'Articles and Insights' => '/insights/',
				'Forms and Documents' => '/forms-documents/',
				'Frequently Asked Questions' => '/faq/',
				'Privacy Policy' => '/privacy-policy/',
				'Terms of Use' => '/terms-of-use/',
				'Accessibility Statement' => '/accessibility/',
			),
		);

		return (array) apply_filters( 'algq_footer_navigation_schema', $footer );
	}

	public static function render_navigation(): string {
		$navigation_id = wp_unique_id( 'algq-navigation-' );
		$menu_id       = $navigation_id . '-menu';

		ob_start();
		?>
		<nav id="<?php echo esc_attr( $navigation_id ); ?>" class="algq-navigation" data-algq-navigation aria-label="<?php echo esc_attr__( 'Algonquian Real Estate primary navigation', 'algonquian-navigation' ); ?>">
			<div class="algq-navigation__inner">
				<button class="algq-navigation__mobile-toggle" type="button" aria-expanded="false" aria-controls="<?php echo esc_attr( $menu_id ); ?>" aria-label="<?php echo esc_attr__( 'Open navigation', 'algonquian-navigation' ); ?>">
					<span class="algq-navigation__hamburger" aria-hidden="true"><span></span><span></span><span></span></span>
					<span class="algq-navigation__mobile-label"><?php echo esc_html__( 'Menu', 'algonquian-navigation' ); ?></span>
				</button>

				<div id="<?php echo esc_attr( $menu_id ); ?>" class="algq-navigation__menu">
					<ul class="algq-navigation__primary">
						<?php foreach ( self::schema() as $key => $section ) : ?>
							<?php $panel_id = $navigation_id . '-' . sanitize_html_class( (string) $key ); ?>
							<li class="algq-navigation__section" data-algq-nav-section>
								<div class="algq-navigation__section-head">
									<a class="algq-navigation__section-link" href="<?php echo esc_url( home_url( (string) $section['url'] ) ); ?>"><?php echo esc_html( (string) $section['label'] ); ?></a>
									<button class="algq-navigation__section-toggle" type="button" aria-expanded="false" aria-controls="<?php echo esc_attr( $panel_id ); ?>" aria-label="<?php echo esc_attr( sprintf( __( 'Open %s menu', 'algonquian-navigation' ), (string) $section['label'] ) ); ?>">
										<span aria-hidden="true"></span>
									</button>
								</div>
								<div id="<?php echo esc_attr( $panel_id ); ?>" class="algq-navigation__panel">
									<div class="algq-navigation__grid">
										<?php foreach ( (array) $section['columns'] as $column_title => $links ) : ?>
											<section class="algq-navigation__column">
												<h2><?php echo esc_html( (string) $column_title ); ?></h2>
												<ul>
													<?php foreach ( (array) $links as $label => $url ) : ?>
														<li><a href="<?php echo esc_url( home_url( (string) $url ) ); ?>"><?php echo esc_html( (string) $label ); ?></a></li>
													<?php endforeach; ?>
												</ul>
											</section>
										<?php endforeach; ?>
									</div>
								</div>
							</li>
						<?php endforeach; ?>
					</ul>

					<div class="algq-navigation__utilities" aria-label="<?php echo esc_attr__( 'Utility navigation', 'algonquian-navigation' ); ?>">
						<?php foreach ( self::utilities() as $utility ) : ?>
							<?php
							$classes = 'algq-navigation__utility';
							if ( ! empty( $utility['class'] ) ) {
								$classes .= ' ' . sanitize_html_class( (string) $utility['class'] );
							}
							?>
							<a class="<?php echo esc_attr( $classes ); ?>" href="<?php echo esc_url( home_url( (string) $utility['url'] ) ); ?>" aria-label="<?php echo esc_attr( (string) $utility['label'] ); ?>" title="<?php echo esc_attr( (string) $utility['label'] ); ?>">
								<?php echo self::icon( (string) $utility['icon'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
								<span class="algq-navigation__utility-label"><?php echo esc_html( (string) $utility['label'] ); ?></span>
							</a>
						<?php endforeach; ?>
					</div>
				</div>
			</div>
			<button class="algq-navigation__backdrop" type="button" aria-label="<?php echo esc_attr__( 'Close navigation', 'algonquian-navigation' ); ?>" tabindex="-1"></button>
		</nav>
		<?php
		return (string) ob_get_clean();
	}

	public static function render_footer(): string {
		ob_start();
		?>
		<nav class="algq-navigation-footer" aria-label="<?php echo esc_attr__( 'Algonquian Real Estate footer navigation', 'algonquian-navigation' ); ?>">
			<div class="algq-navigation-footer__grid">
				<?php foreach ( self::footer_schema() as $heading => $links ) : ?>
					<section class="algq-navigation-footer__column">
						<h2><?php echo esc_html( (string) $heading ); ?></h2>
						<ul>
							<?php foreach ( (array) $links as $label => $url ) : ?>
								<li><a href="<?php echo esc_url( home_url( (string) $url ) ); ?>"><?php echo esc_html( (string) $label ); ?></a></li>
							<?php endforeach; ?>
						</ul>
					</section>
				<?php endforeach; ?>
			</div>
		</nav>
		<?php
		return (string) ob_get_clean();
	}

	private static function icon( string $name ): string {
		$icons = array(
			'search' => '<svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="11" cy="11" r="6"></circle><path d="m16 16 4 4"></path></svg>',
			'user' => '<svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="8" r="4"></circle><path d="M4 21c1.5-4 4.2-6 8-6s6.5 2 8 6"></path></svg>',
			'lock' => '<svg viewBox="0 0 24 24" aria-hidden="true"><rect x="5" y="10" width="14" height="10" rx="2"></rect><path d="M8 10V7a4 4 0 0 1 8 0v3"></path></svg>',
			'mail' => '<svg viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="5" width="18" height="14" rx="2"></rect><path d="m4 7 8 6 8-6"></path></svg>',
			'home' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 11 12 3l9 8"></path><path d="M5 10v11h14V10"></path><path d="M9 21v-7h6v7"></path></svg>',
		);

		return $icons[ $name ] ?? '';
	}
}

register_activation_hook( ALGQ_NAVIGATION_FILE, array( 'ALGQ_Navigation', 'activate' ) );
register_deactivation_hook( ALGQ_NAVIGATION_FILE, array( 'ALGQ_Navigation', 'deactivate' ) );
ALGQ_Navigation::init();

if ( ! function_exists( 'algq_render_mega_menu' ) ) {
	function algq_render_mega_menu(): void {
		echo ALGQ_Navigation::render_navigation(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}
}
