<?php
/**
 * Idempotent platform page generation and shared enterprise navigation.
 *
 * @package AlgonquianRealEstatePlatform
 */

defined( 'ABSPATH' ) || exit;

final class ALGQ_Platform_Page_Generator {
	/** @return array<string,array<string,string>> */
	public static function pages(): array {
		return array(
			'platform' => array(
				'title'   => 'Algonquian Real Estate Platform',
				'slug'    => 'algonquian-real-estate-platform',
				'parent'  => '',
				'content' => self::page_content( 'Platform Infrastructure', 'Algonquian Real Estate Platform', 'Shared security, registry, mail, audit, private files, health monitoring, page generation, navigation, and integration contracts for the Algonquian Real Estate plugin ecosystem.', '[algq_platform_overview]' ),
			),
			'plugin_root' => array(
				'title'   => 'Plugin Library',
				'slug'    => 'plugin',
				'parent'  => '',
				'content' => self::page_content( 'Algonquian Real Estate Technology Division', 'Plugin Library', 'Access plugin overviews, Getting Started guides, documentation, health information, and operational routes for the Algonquian Real Estate platform.', '[algq_platform_overview]' ),
			),
			'platform_plugin' => array(
				'title'   => 'Algonquian Real Estate Platform Plugin',
				'slug'    => 'plugin/platform',
				'parent'  => 'plugin_root',
				'content' => self::page_content( 'Shared Platform Services', 'Algonquian Real Estate Platform Plugin', 'The Platform Plugin provides registry, security, capabilities, mail, audit, private file storage, health monitoring, page generation, enterprise navigation, and integration contracts for companion plugins.', '[algq_platform_overview]' ),
			),
			'platform_start' => array(
				'title'   => 'Getting Started With the Algonquian Real Estate Platform',
				'slug'    => 'plugin/platform/start',
				'parent'  => 'platform_plugin',
				'content' => self::page_content( 'Platform Administration', 'Getting Started', 'Confirm the Platform Plugin is active before enabling companion plugins. Review capabilities, registry status, mail configuration, private storage, generated pages, navigation, and the platform health report.', '[algq_platform_overview]' ),
			),
			'platform_docs' => array(
				'title'   => 'Algonquian Real Estate Platform Documentation',
				'slug'    => 'plugin/platform/docs',
				'parent'  => 'platform_plugin',
				'content' => self::page_content( 'User and Administrator Guide Library', 'Platform Documentation', 'Use the platform documentation for installation, capabilities, plugin registration, audit events, email delivery, private file handling, page generation, navigation, health checks, security, and troubleshooting.', '[algq_platform_overview]' ),
			),
		);
	}

	/** @return array<string,int> */
	public static function create_missing_pages(): array {
		$page_ids = (array) get_option( 'algq_platform_generated_pages', array() );
		foreach ( self::pages() as $key => $page ) {
			$existing = get_page_by_path( $page['slug'], OBJECT, 'page' );
			if ( $existing instanceof WP_Post ) {
				$page_ids[ $key ] = (int) $existing->ID;
				continue;
			}

			$parent_key = (string) ( $page['parent'] ?? '' );
			$parent_id  = $parent_key && isset( $page_ids[ $parent_key ] ) ? absint( $page_ids[ $parent_key ] ) : 0;
			$page_id    = wp_insert_post(
				array(
					'post_title'   => sanitize_text_field( $page['title'] ),
					'post_name'    => sanitize_title( basename( $page['slug'] ) ),
					'post_parent'  => $parent_id,
					'post_content' => $page['content'],
					'post_status'  => 'publish',
					'post_type'    => 'page',
				)
			);

			if ( ! is_wp_error( $page_id ) ) {
				$page_ids[ $key ] = (int) $page_id;
				update_post_meta( $page_id, '_algq_generated_page', '1' );
				update_post_meta( $page_id, '_algq_generated_page_key', $key );
				update_post_meta( $page_id, '_algq_generated_by_version', ALGQ_PLATFORM_VERSION );
			}
		}

		update_option( 'algq_platform_generated_pages', $page_ids, false );
		return array_map( 'absint', $page_ids );
	}

	/** Compatibility alias retained for older activation hooks. */
	public static function create_pages(): array {
		return self::create_missing_pages();
	}

	public static function missing_count(): int {
		$missing = 0;
		foreach ( self::pages() as $page ) {
			if ( ! get_page_by_path( $page['slug'], OBJECT, 'page' ) ) {
				++$missing;
			}
		}
		return $missing;
	}

	private static function page_content( string $eyebrow, string $heading, string $intro, string $shortcode ): string {
		return '[vc_row full_width="stretch_row_content" css=".vc_custom_algq_platform_hero{background:#0f1923;padding-top:70px !important;padding-bottom:70px !important;}"][vc_column][vc_column_text]'
			. '<div style="text-align:center;max-width:1000px;margin:0 auto;color:#e6eef5;">'
			. '<div style="display:inline-block;padding:10px 18px;border:1px solid rgba(230,238,245,.2);border-radius:999px;color:#e6eef5;font-size:13px;letter-spacing:.08em;text-transform:uppercase;">' . esc_html( $eyebrow ) . '</div>'
			. '<h1 style="color:#fff;margin:22px 0 14px;">' . esc_html( $heading ) . '</h1>'
			. '<p style="font-size:18px;line-height:1.7;opacity:.9;">' . esc_html( $intro ) . '</p>'
			. '</div>[/vc_column_text][/vc_column][/vc_row]'
			. '[vc_row css=".vc_custom_algq_platform_module{padding-top:50px !important;padding-bottom:50px !important;background:#f4f6f8 !important;}"][vc_column][vc_column_text]'
			. $shortcode
			. '[/vc_column_text][/vc_column][/vc_row]';
	}
}

/**
 * Shared 6x6 public navigation contract.
 *
 * The Platform Plugin owns navigation structure and presentation only. Companion plugins
 * remain authoritative for their operational records and protected application screens.
 */
final class ALGQ_Platform_Navigation {
	private const STYLE_HANDLE = 'algq-enterprise-navigation';
	private const SCRIPT_HANDLE = 'algq-enterprise-navigation';

	public static function init(): void {
		add_action( 'init', array( __CLASS__, 'register_locations_and_shortcodes' ), 20 );
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'register_assets' ), 20 );
	}

	public static function register_locations_and_shortcodes(): void {
		register_nav_menus(
			array(
				'algq_primary' => __( 'Algonquian Enterprise Primary Navigation', 'algonquian-real-estate-platform' ),
				'algq_footer'  => __( 'Algonquian Enterprise Footer Navigation', 'algonquian-real-estate-platform' ),
			)
		);

		if ( ! shortcode_exists( 'algq_mega_menu' ) ) {
			add_shortcode( 'algq_mega_menu', array( __CLASS__, 'render_mega_menu' ) );
		}

		if ( ! shortcode_exists( 'algq_footer_links' ) ) {
			add_shortcode( 'algq_footer_links', array( __CLASS__, 'render_footer' ) );
		}
	}

	public static function register_assets(): void {
		wp_register_style( self::STYLE_HANDLE, false, array(), defined( 'ALGQ_PLATFORM_VERSION' ) ? ALGQ_PLATFORM_VERSION : '2.0.0' );
		wp_enqueue_style( self::STYLE_HANDLE );
		wp_add_inline_style( self::STYLE_HANDLE, self::css() );

		wp_register_script( self::SCRIPT_HANDLE, '', array(), defined( 'ALGQ_PLATFORM_VERSION' ) ? ALGQ_PLATFORM_VERSION : '2.0.0', true );
		wp_enqueue_script( self::SCRIPT_HANDLE );
		wp_add_inline_script( self::SCRIPT_HANDLE, self::javascript() );
	}

	/** @return array<string,array<string,mixed>> */
	public static function schema(): array {
		$schema = array(
			'property-owners' => array(
				'label'   => 'Property Owners',
				'url'     => '/property-owners/',
				'columns' => array(
					'Explore Your Options' => array( 'Property Owner Overview' => '/property-owners/', 'What Are My Property Options?' => '/property-options/', 'Request a Property Review' => '/request-property-review/', 'Property Options Consultation' => '/property-consultation/', 'Property Owner Resources' => '/property-owner-resources/', 'Frequently Asked Questions' => '/faq/' ),
					'Sell a Property' => array( 'Sell Your Property' => '/sell-your-property/', 'Submit a Property' => '/submit-a-property/', 'Sell As-Is' => '/sell-as-is/', 'Traditional Sale' => '/traditional-sale/', 'Direct Sale' => '/direct-sale/', 'Property Sale Readiness' => '/property-sale-readiness/' ),
					'Property Transitions' => array( 'Property Transition Planning' => '/property-transition-planning/', 'Empty Nest Transition' => '/empty-nest-transition/', 'Downsizing' => '/senior-property-assistance/downsizing/', 'Preparing for a Future Move' => '/property-transition-planning/', 'Preparing a Property for Sale' => '/prepare-property-for-sale/', 'Transition Checklist' => '/property-transition-planning/checklist/' ),
					'Inherited & Estate Property' => array( 'Inherited Property Guidance' => '/inherited-property-guidance/', 'Estate Transition Assistance' => '/estate-transition-assistance/', 'Property Valuation Coordination' => '/estate-transition-assistance/property-valuation/', 'Clean-Out Coordination' => '/estate-transition-assistance/cleanout/', 'Contractor Coordination' => '/estate-transition-assistance/contractors/', 'Sale Options' => '/estate-transition-assistance/sale-options/' ),
					'Senior Property Assistance' => array( 'Senior Property Assistance' => '/senior-property-assistance/', 'Aging in Place' => '/senior-property-assistance/aging-in-place/', 'Downsizing Support' => '/senior-property-assistance/downsizing/', 'Maintenance Coordination' => '/senior-property-assistance/home-maintenance-coordination/', 'Future Property Planning' => '/senior-property-assistance/next-generation/', 'Request Assistance' => '/senior-property-assistance/consultation/' ),
					'Stewardship & Property Care' => array( 'Property Stewardship Services' => '/property-stewardship-services/', 'Trusted Property Contact' => '/trusted-property-contact/', 'Property Check-Ins' => '/property-check-ins/', 'Vacant Property Monitoring' => '/vacant-property-monitoring/', 'Vendor Coordination' => '/vendor-coordination/', 'Property Stewardship Portal' => '/property-stewardship-portal/' ),
				),
			),
			'acquisitions' => array(
				'label'   => 'Acquisitions',
				'url'     => '/acquisitions/',
				'columns' => array(
					'Acquisition Overview' => array( 'Acquisitions' => '/acquisitions/', 'Acquisition Criteria' => '/acquisition-criteria/', 'How We Review Properties' => '/acquisitions/how-we-review-properties/', 'Acquisition Process' => '/acquisitions/acquisition-process/', 'Frequently Asked Questions' => '/acquisitions/faq/', 'Contact Acquisitions' => '/contact/' ),
					'Submit Opportunities' => array( 'Submit a Property' => '/submit-a-property/', 'Submit a Deal' => '/submit-a-deal/', 'Broker Submissions' => '/acquisitions/broker-submissions/', 'Wholesaler Submissions' => '/acquisitions/wholesaler-submissions/', 'Attorney and Estate Referrals' => '/acquisitions/attorney-estate-referrals/', 'Referral Partners' => '/acquisitions/referral-partners/' ),
					'Property Types' => array( 'Multifamily Properties' => '/acquisitions/multifamily/', 'Three-Family Properties' => '/acquisitions/three-family/', 'Mixed-Use Properties' => '/acquisitions/mixed-use/', 'Commercial Properties' => '/acquisitions/commercial/', 'Residential Properties' => '/acquisitions/residential/', 'Development Sites' => '/development-concepts/' ),
					'Opportunity Types' => array( 'Off-Market Properties' => '/acquisitions/off-market-properties/', 'Value-Add Properties' => '/acquisitions/value-add-properties/', 'Distressed Properties' => '/acquisitions/distressed-properties/', 'Vacant Properties' => '/acquisitions/vacant-properties/', 'Underutilized Properties' => '/acquisitions/underutilized-properties/', 'Tired Landlord Opportunities' => '/sell-your-property/tired-landlord/' ),
					'Transaction Structures' => array( 'Conventional Acquisition' => '/acquisitions/', 'Seller Financing' => '/acquisitions/seller-financed-opportunities/', 'Joint Venture Acquisitions' => '/acquisitions/joint-venture-acquisitions/', 'Private Capital Acquisitions' => '/investors/private-capital/', 'Subject-To Opportunities' => '/acquisitions/subject-to-acquisitions/', 'Flexible Transaction Structures' => '/acquisitions/' ),
					'Underwriting & Due Diligence' => array( 'Underwriting Overview' => '/underwriting/', 'Maximum Allowable Offer' => '/technology/plugins/mao-engine/', 'Property Due Diligence' => '/acquisitions/due-diligence/', 'Financial Review' => '/underwriting/', 'Risk Review' => '/underwriting/', 'Required Documents' => '/forms-documents/' ),
				),
			),
			'investors' => array(
				'label'   => 'Investors & Capital',
				'url'     => '/investors/',
				'columns' => array(
					'Investor Network' => array( 'Investor Overview' => '/investors/', 'Join the Investor Network' => '/investors/join/', 'Investor Criteria' => '/investors/investment-criteria/', 'Connecticut Opportunities' => '/investment-opportunities/', 'Investor Frequently Asked Questions' => '/investors/frequently-asked-questions/', 'Investor Disclosures' => '/investors/disclosures/' ),
					'Buyers' => array( 'Buyer Registration' => '/buyer-registration/', 'Buyer Login' => '/buyer-login/', 'Buyer Dashboard' => '/buyer-dashboard/', 'Available Deals' => '/buyer-dashboard/deals/', 'Buyer Marketplace' => '/marketplace/', 'Buyer Profile' => '/buyer-dashboard/profile/' ),
					'Private Capital' => array( 'Private Lenders' => '/capital-partners/private-lenders/', 'Equity Partners' => '/investors/equity-partners/', 'Joint Venture Partners' => '/investors/joint-ventures/', 'Capital Partner Network' => '/capital-partners/', 'Funding Relationships' => '/investors/funding-relationships/', 'Submit Investor Profile' => '/investors/join/' ),
					'Deal Access' => array( 'Deal Marketplace' => '/marketplace/', 'Available Opportunities' => '/investment-opportunities/', 'Deal Packages' => '/investors/deal-packages/', 'NDA Access' => '/buyer-dashboard/nda/', 'Submit Interest' => '/buyer-dashboard/deals/', 'Submit an Offer' => '/buyer-dashboard/submit-offer/' ),
					'Investor Resources' => array( 'How We Review Deals' => '/investors/how-we-review-deals/', 'Investor Readiness' => '/investors/investor-readiness/', 'Underwriting Standards' => '/underwriting/', 'Deal Package Guide' => '/investors/deal-packages/', 'Private Lending Guide' => '/investors/private-lenders/', 'Joint Venture Guide' => '/investors/joint-ventures/' ),
					'Lenders' => array( 'Lender Overview' => '/capital-partners/', 'Lender Resources' => '/lender-resources/', 'Company Overview' => '/about/company-overview/', 'Business Plan' => '/business-plan/', 'Acquisition Criteria' => '/acquisition-criteria/', 'Request Documentation Access' => '/forms-documents/' ),
				),
			),
			'services' => array(
				'label'   => 'Services',
				'url'     => '/services/',
				'columns' => array(
					'Property Services' => array( 'Property Services Overview' => '/services/', 'Request a Service Consultation' => '/property-service-consultation/', 'Submit a Service Request' => '/property-service-request/', 'Property Condition Review' => '/property-services/property-condition-review/', 'Property Document Organization' => '/property-document-organization/', 'Service Frequently Asked Questions' => '/services/faq/' ),
					'Property Stewardship' => array( 'Property Stewardship Services' => '/property-stewardship-services/', 'Essential Property Watch' => '/property-stewardship-services/essential-watch/', 'Active Stewardship' => '/property-stewardship-services/active-steward/', 'Transition Stewardship' => '/property-stewardship-services/transition-support/', 'Stewardship Enrollment' => '/property-stewardship-services/enrollment/', 'Stewardship Portal' => '/property-stewardship-portal/' ),
					'Property Monitoring' => array( 'Property Check-Ins' => '/property-check-ins/', 'Exterior Check-Ins' => '/property-check-ins/exterior/', 'Authorized Interior Check-Ins' => '/property-check-ins/interior/', 'Photo and Condition Reports' => '/property-check-ins/photo-reports/', 'Vacant Property Monitoring' => '/vacant-property-monitoring/', 'Storm Property Reviews' => '/storm-property-inspections/' ),
					'Maintenance Coordination' => array( 'Maintenance Coordination' => '/maintenance-coordination/', 'Vendor Coordination' => '/vendor-coordination/', 'Contractor Coordination' => '/contractor-coordination/', 'Lawn and Exterior Oversight' => '/lawn-exterior-oversight/', 'Seasonal Property Reviews' => '/seasonal-property-reviews/', 'Repair Scheduling' => '/maintenance-coordination/' ),
					'Property Transition Support' => array( 'Prepare a Property for Sale' => '/prepare-property-for-sale/', 'Clean-Out Coordination' => '/prepare-property-for-sale/cleanout/', 'Repair Coordination' => '/prepare-property-for-sale/repairs/', 'Market Readiness Review' => '/prepare-property-for-sale/market-readiness/', 'Transition Planning' => '/property-transition-planning/', 'Sale Options Review' => '/property-options/' ),
					'Specialized Assistance' => array( 'Trusted Property Contact' => '/trusted-property-contact/', 'Senior Property Assistance' => '/senior-property-assistance/', 'Property Rescue Services' => '/property-rescue-services/', 'Community Property Preservation' => '/community-property-preservation/', 'Property Legacy Planning' => '/property-legacy-planning/', 'Legacy Conversation' => '/legacy-conversation/' ),
				),
			),
			'technology' => array(
				'label'   => 'Technology',
				'url'     => '/technology/',
				'columns' => array(
					'Technology Division' => array( 'Technology Overview' => '/technology/', 'ARE Technology Division' => '/technology-division/', 'Technology Strategy' => '/technology/strategy/', 'Platform Architecture' => '/technology/platform-architecture/', 'Development Roadmap' => '/technology/platform-roadmap/', 'Technology Contact' => '/contact/' ),
					'Platform' => array( 'Algonquian Real Estate Platform' => '/algonquian-real-estate-platform/', 'Platform Overview' => '/plugin/platform/', 'Shared Services' => '/plugin/platform/', 'Security and Permissions' => '/plugin/platform/docs/', 'System Health' => '/plugin/platform/docs/', 'Platform Documentation' => '/plugin/platform/docs/' ),
					'Acquisition Systems' => array( 'Algonquian Deal Intake' => '/plugin/deal-intake/', 'Algonquian Pipeline CRM' => '/plugin/pipeline-crm/', 'Algonquian MAO Engine' => '/plugin/mao-engine/', 'Algonquian Offer Generator' => '/plugin/offer-generator/', 'Deal Marketplace' => '/marketplace/', 'Buyer Portal' => '/buyer-dashboard/' ),
					'Operations Systems' => array( 'Funding Tracker' => '/plugin/funding-tracker/', 'Automation Engine' => '/plugin/automation-engine/', 'Document Library' => '/plugin/document-library/', 'PDF and Signature Engine' => '/plugin/pdf-signature/', 'Admin Command Center' => '/plugin/command-center/', 'Reporting and Analytics' => '/plugin/command-center/docs/' ),
					'Commerce & Products' => array( 'Digital Store' => '/store/', 'Product Vault' => '/product-vault/', 'WooCommerce Bridge' => '/plugin/woocommerce-bridge/', 'Software Licensing' => '/technology/software-licensing/', 'Digital Products' => '/digital-products/', 'Customer Account' => '/my-account/' ),
					'Guides & Documentation' => array( 'Plugin Library' => '/plugin/', 'Getting Started' => '/plugin/platform/start/', 'User Guides' => '/plugin-guides/', 'Administrator Guides' => '/plugin-guides/', 'Technical Documentation' => '/plugin/platform/docs/', 'Troubleshooting' => '/plugin/platform/docs/' ),
				),
			),
			'company' => array(
				'label'   => 'Company',
				'url'     => '/about/',
				'columns' => array(
					'About Algonquian' => array( 'About Algonquian Real Estate' => '/about/', 'Company Overview' => '/about/company-overview/', 'Mission and Vision' => '/mission/', 'Company Development History' => '/company-development-history/', 'Connecticut Focus' => '/connecticut/', 'Operating Principles' => '/about/operating-principles/' ),
					'Leadership' => array( 'Leadership' => '/about/leadership/', 'Founder and Managing Member' => '/about/leadership/', 'Management Structure' => '/about/governance/', 'Governance' => '/governance/', 'Company Responsibilities' => '/about/operating-principles/', 'Contact Leadership' => '/contact/' ),
					'Company Information' => array( 'Legal Entity Overview' => '/about/company-overview/', 'Company Status' => '/about/company-overview/', 'Principal Office' => '/about/company-overview/', 'Registered Agent' => '/about/company-overview/', 'Company Documentation' => '/forms-documents/', 'Forms and Documents' => '/forms-documents/' ),
					'Resources' => array( 'Articles and Insights' => '/insights/', 'Property Owner Resources' => '/property-owner-resources/', 'Investor Resources' => '/investor-resources/', 'Lender Resources' => '/lender-resources/', 'Frequently Asked Questions' => '/faq/', 'News and Updates' => '/news/' ),
					'Work With Us' => array( 'Contact Algonquian Real Estate' => '/contact/', 'Property Inquiry' => '/contact/property-inquiry/', 'Investor Inquiry' => '/contact/investor-inquiry/', 'Lender Inquiry' => '/contact/lender-inquiry/', 'Vendor Inquiry' => '/contact/vendor-inquiry/', 'Partnership Inquiry' => '/contact/partnership-inquiry/' ),
					'Legal & Compliance' => array( 'Privacy Policy' => '/privacy-policy/', 'Terms of Use' => '/terms-of-use/', 'Website Disclaimer' => '/website-disclaimer/', 'Professional Services Disclaimer' => '/professional-boundaries/', 'Investment Disclosures' => '/investment-disclosures/', 'Accessibility Statement' => '/accessibility/' ),
				),
			),
		);

		return (array) apply_filters( 'algq_navigation_schema', $schema );
	}

	/** @return array<string,array<string,string>> */
	public static function footer_schema(): array {
		$footer = array(
			'Company' => array( 'About Algonquian Real Estate' => '/about/', 'Mission and Vision' => '/mission/', 'Company Development History' => '/company-development-history/', 'Leadership' => '/about/leadership/', 'Connecticut Focus' => '/connecticut/', 'Contact' => '/contact/' ),
			'Property & Acquisition' => array( 'Property Owners' => '/property-owners/', 'Sell Your Property' => '/sell-your-property/', 'Property Stewardship' => '/property-stewardship-services/', 'Senior Property Assistance' => '/senior-property-assistance/', 'Acquisition Criteria' => '/acquisition-criteria/', 'Submit a Property' => '/submit-a-property/' ),
			'Investors & Technology' => array( 'Investor Network' => '/investors/', 'Buyer Registration' => '/buyer-registration/', 'Deal Marketplace' => '/marketplace/', 'Technology Division' => '/technology/', 'Plugin Library' => '/plugin/', 'Digital Store' => '/store/' ),
			'Resources & Legal' => array( 'Forms and Documents' => '/forms-documents/', 'Articles and Insights' => '/insights/', 'Frequently Asked Questions' => '/faq/', 'Privacy Policy' => '/privacy-policy/', 'Terms of Use' => '/terms-of-use/', 'Accessibility Statement' => '/accessibility/' ),
		);

		return (array) apply_filters( 'algq_footer_navigation_schema', $footer );
	}

	public static function render_mega_menu(): string {
		self::register_assets();
		$schema = self::schema();
		ob_start();
		?>
		<nav class="algq-mega" aria-label="<?php echo esc_attr__( 'Primary', 'algonquian-real-estate-platform' ); ?>">
			<div class="algq-mega__bar">
				<button class="algq-mega__mobile-toggle" type="button" aria-expanded="false" aria-controls="algq-mega-panel">
					<span aria-hidden="true">☰</span><span><?php echo esc_html__( 'Menu', 'algonquian-real-estate-platform' ); ?></span>
				</button>
				<div id="algq-mega-panel" class="algq-mega__panel">
					<?php foreach ( $schema as $key => $item ) : ?>
						<div class="algq-mega__item" data-algq-menu="<?php echo esc_attr( $key ); ?>">
							<button class="algq-mega__trigger" type="button" aria-expanded="false"><?php echo esc_html( (string) $item['label'] ); ?></button>
							<div class="algq-mega__dropdown">
								<div class="algq-mega__grid">
									<?php foreach ( (array) $item['columns'] as $heading => $links ) : ?>
										<section class="algq-mega__column">
											<h3><?php echo esc_html( (string) $heading ); ?></h3>
											<ul>
												<?php foreach ( (array) $links as $label => $url ) : ?>
													<li><a href="<?php echo esc_url( home_url( (string) $url ) ); ?>"><?php echo esc_html( (string) $label ); ?></a></li>
												<?php endforeach; ?>
											</ul>
										</section>
									<?php endforeach; ?>
								</div>
							</div>
						</div>
					<?php endforeach; ?>
				</div>
				<div class="algq-mega__utilities">
					<a href="<?php echo esc_url( home_url( '/?s=' ) ); ?>" aria-label="<?php echo esc_attr__( 'Search', 'algonquian-real-estate-platform' ); ?>">⌕<span>Search</span></a>
					<a href="<?php echo esc_url( home_url( '/buyer-login/' ) ); ?>">Buyer Login</a>
					<a href="<?php echo esc_url( home_url( '/client-portal/' ) ); ?>">Client Portal</a>
					<a class="algq-mega__cta" href="<?php echo esc_url( home_url( '/submit-a-property/' ) ); ?>">Submit a Property</a>
				</div>
			</div>
		</nav>
		<?php
		return (string) ob_get_clean();
	}

	public static function render_footer(): string {
		self::register_assets();
		ob_start();
		?>
		<footer class="algq-footer-links">
			<div class="algq-footer-links__grid">
				<?php foreach ( self::footer_schema() as $heading => $links ) : ?>
					<section>
						<h3><?php echo esc_html( (string) $heading ); ?></h3>
						<ul>
							<?php foreach ( $links as $label => $url ) : ?>
								<li><a href="<?php echo esc_url( home_url( (string) $url ) ); ?>"><?php echo esc_html( (string) $label ); ?></a></li>
							<?php endforeach; ?>
						</ul>
					</section>
				<?php endforeach; ?>
			</div>
			<div class="algq-footer-links__bottom">
				<span>© <?php echo esc_html( wp_date( 'Y' ) ); ?> Algonquian Real Estate LLC. All rights reserved.</span>
				<nav aria-label="<?php echo esc_attr__( 'Footer legal', 'algonquian-real-estate-platform' ); ?>">
					<a href="<?php echo esc_url( home_url( '/website-disclaimer/' ) ); ?>">Website Disclaimer</a>
					<a href="<?php echo esc_url( home_url( '/professional-boundaries/' ) ); ?>">Professional Services Disclaimer</a>
					<a href="<?php echo esc_url( home_url( '/investment-disclosures/' ) ); ?>">Investment Disclosures</a>
					<a href="<?php echo esc_url( home_url( '/sitemap/' ) ); ?>">Sitemap</a>
				</nav>
			</div>
		</footer>
		<?php
		return (string) ob_get_clean();
	}

	private static function css(): string {
		return '.algq-mega{position:relative;z-index:9999;background:#fff;border-bottom:1px solid #d9e0e6;font-family:inherit}.algq-mega__bar{max-width:1440px;margin:0 auto;padding:0 22px;display:flex;align-items:center;gap:14px;min-height:68px}.algq-mega__panel{display:flex;align-items:stretch;gap:2px;min-width:0;flex:1}.algq-mega__item{position:static}.algq-mega__trigger{border:0;background:transparent;color:#0b1f33;font-weight:700;padding:24px 12px;cursor:pointer;white-space:nowrap}.algq-mega__trigger:focus-visible,.algq-mega a:focus-visible,.algq-mega__mobile-toggle:focus-visible{outline:3px solid #167c80;outline-offset:2px}.algq-mega__dropdown{display:none;position:absolute;left:0;right:0;top:100%;background:#fff;border-top:1px solid #e7ebef;border-bottom:1px solid #d9e0e6;box-shadow:0 16px 40px rgba(11,31,51,.14);padding:30px 22px}.algq-mega__item.is-open .algq-mega__dropdown{display:block}.algq-mega__grid{max-width:1396px;margin:0 auto;display:grid;grid-template-columns:repeat(6,minmax(0,1fr));gap:22px}.algq-mega__column h3,.algq-footer-links h3{margin:0 0 12px;color:#0b1f33;font-size:14px;letter-spacing:.04em}.algq-mega__column ul,.algq-footer-links ul{list-style:none;margin:0;padding:0}.algq-mega__column li+li,.algq-footer-links li+li{margin-top:8px}.algq-mega__column a,.algq-footer-links a{color:#52606d;text-decoration:none}.algq-mega__column a:hover,.algq-footer-links a:hover{color:#167c80}.algq-mega__utilities{display:flex;align-items:center;gap:10px;flex-shrink:0}.algq-mega__utilities a{color:#0b1f33;text-decoration:none;font-weight:650;white-space:nowrap}.algq-mega__utilities a:first-child{display:inline-flex;align-items:center;gap:5px}.algq-mega__cta{padding:11px 16px!important;background:#c7a44a;color:#071422!important;border-radius:7px}.algq-mega__mobile-toggle{display:none;border:0;background:transparent;color:#0b1f33;font-weight:800;font-size:16px;padding:16px 0}.algq-footer-links{background:#071422;color:#dce5ed;padding:52px 28px 24px}.algq-footer-links__grid{max-width:1180px;margin:0 auto;display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:36px}.algq-footer-links h3{color:#fff}.algq-footer-links a{color:#c5d0d9}.algq-footer-links__bottom{max-width:1180px;margin:38px auto 0;padding-top:22px;border-top:1px solid rgba(255,255,255,.14);display:flex;justify-content:space-between;gap:24px;font-size:13px}.algq-footer-links__bottom nav{display:flex;gap:14px;flex-wrap:wrap}@media(max-width:1399px){.algq-mega__utilities a:not(.algq-mega__cta) span{display:none}.algq-mega__utilities{gap:7px}.algq-mega__trigger{padding-left:8px;padding-right:8px;font-size:13px}}@media(max-width:1099px){.algq-mega__bar{display:block;min-height:0}.algq-mega__mobile-toggle{display:flex;width:100%;align-items:center;gap:8px;justify-content:flex-start}.algq-mega__panel{display:none;flex-direction:column}.algq-mega.is-open .algq-mega__panel{display:flex}.algq-mega__item{border-top:1px solid #edf0f2}.algq-mega__trigger{width:100%;text-align:left;padding:15px 2px;font-size:15px}.algq-mega__dropdown{position:static;box-shadow:none;border:0;padding:4px 0 20px}.algq-mega__grid{grid-template-columns:repeat(2,minmax(0,1fr));gap:22px}.algq-mega__utilities{display:none;padding:10px 0 18px;flex-wrap:wrap}.algq-mega.is-open .algq-mega__utilities{display:flex}.algq-mega__cta{margin-left:0}.algq-footer-links__grid{grid-template-columns:repeat(2,minmax(0,1fr))}}@media(max-width:767px){.algq-mega__grid{grid-template-columns:1fr}.algq-footer-links__grid{grid-template-columns:1fr}.algq-footer-links__bottom{display:block}.algq-footer-links__bottom nav{margin-top:12px}}';
	}

	private static function javascript(): string {
		return '(function(){function init(root){if(root.dataset.algqReady){return;}root.dataset.algqReady="1";var mobile=root.querySelector(".algq-mega__mobile-toggle");var items=[].slice.call(root.querySelectorAll(".algq-mega__item"));function closeItems(except){items.forEach(function(item){if(item===except){return;}item.classList.remove("is-open");var button=item.querySelector(".algq-mega__trigger");if(button){button.setAttribute("aria-expanded","false");}});}if(mobile){mobile.addEventListener("click",function(){var open=root.classList.toggle("is-open");mobile.setAttribute("aria-expanded",open?"true":"false");if(!open){closeItems();}});}items.forEach(function(item){var button=item.querySelector(".algq-mega__trigger");if(!button){return;}button.addEventListener("click",function(){var willOpen=!item.classList.contains("is-open");closeItems(item);item.classList.toggle("is-open",willOpen);button.setAttribute("aria-expanded",willOpen?"true":"false");});});document.addEventListener("click",function(event){if(!root.contains(event.target)){closeItems();root.classList.remove("is-open");if(mobile){mobile.setAttribute("aria-expanded","false");}}});root.addEventListener("keydown",function(event){if(event.key==="Escape"){closeItems();root.classList.remove("is-open");if(mobile){mobile.setAttribute("aria-expanded","false");mobile.focus();}}});}document.addEventListener("DOMContentLoaded",function(){document.querySelectorAll(".algq-mega").forEach(init);});})();';
	}
}

ALGQ_Platform_Navigation::init();
