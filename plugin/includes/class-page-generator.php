<?php
/**
 * Automatic branded WPBakery page generation for the Algonquian platform.
 *
 * @package AlgonquianRealEstatePlatform
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class ALGQ_Platform_Page_Generator {

	public static function pages() {
		return array(
			'platform' => self::page( 'Algonquian Real Estate Platform', 'algonquian-real-estate-platform', '[algq_plugin_suite]', 'ARE Technology Division • Platform Infrastructure', 'Algonquian Real Estate Platform', 'A unified acquisition, underwriting, transaction, document, buyer, funding, automation, and executive reporting platform developed for Algonquian Real Estate LLC.', '/plugins/', 'Explore Platform', '/contact/', 'Contact ARE' ),
			'seller_intake' => self::page( 'Sell Your Property', 'sell-your-property', '[algq_seller_intake]', 'Seller-Direct Acquisition • Connecticut', 'Submit a Property', 'Provide seller and property information for confidential preliminary review by Algonquian Real Estate.', '#algq-module', 'Start Submission', '/contact/', 'Contact Acquisitions' ),
			'mao_calculator' => self::page( 'MAO Calculator', 'mao-calculator', '[algq_mao_calculator]', 'Disciplined Underwriting • Scenario Analysis', 'Maximum Allowable Offer Engine', 'Evaluate acquisition scenarios using ARV, repairs, operating assumptions, financing costs, and target returns.', '#algq-module', 'Open Calculator', '/underwriting/', 'View Underwriting' ),
			'buyer_registration' => self::page( 'Buyer Registration', 'buyers-register', '[algq_buyer_registration]', 'Investor Access • Controlled Deal Distribution', 'Register as a Buyer', 'Create a buyer profile, provide acquisition criteria, and request access to qualified investment opportunities.', '#algq-module', 'Register Now', '/buyer-dashboard/', 'Buyer Dashboard' ),
			'pipeline' => self::page( 'Pipeline CRM', 'pipeline', '[algq_pipeline_crm]', 'Acquisition Lifecycle • Internal Operations', 'Deal Pipeline CRM', 'Manage opportunities from initial intake through underwriting, offer, contract, funding, buyer assignment, and closing.', '#algq-module', 'Open Pipeline', '/dashboard/', 'Command Center' ),
			'buyer_portal' => self::page( 'Buyer Portal', 'buyer-dashboard', '[algq_buyer_portal]', 'Secure Buyer Access • Deal Packages', 'Buyer Portal', 'Review permitted deal opportunities, manage NDA status, express interest, and access controlled transaction materials.', '#algq-module', 'Open Portal', '/buyers-register/', 'Register' ),
			'funding' => self::page( 'Funding Tracker', 'funding-dashboard', '[algq_funding_tracker]', 'Capital Sources • Funding Coordination', 'Funding Tracker', 'Track lenders, private capital, joint-venture relationships, commitments, terms, and funding status by deal.', '#algq-module', 'View Funding', '/contact/', 'Capital Inquiries' ),
			'documents' => self::page( 'Document Library', 'documents', '[algq_document_library]', 'Institutional Documentation • Controlled Access', 'Document Library', 'Centralized access to entity, financing, acquisition, financial-control, risk-management, and property-management documentation.', '#algq-module', 'Open Library', '/contact/', 'Request Documents' ),
			'automation' => self::page( 'Automation Engine', 'automation-rules', '[algq_automation_engine]', 'Workflow Automation • Event Processing', 'Automation Engine', 'Coordinate status-based actions, document generation, notifications, tasks, signature workflows, and closing processes.', '#algq-module', 'View Automations', '/dashboard/', 'System Health' ),
			'digital_store' => self::page( 'Digital Store', 'digital-store', '[algq_digital_store]', 'ARE Digital Products • Real Estate Systems', 'Algonquian Digital Store', 'Explore real estate templates, forms, calculators, workflow systems, documentation packages, and operational tools.', '#algq-module', 'Browse Products', '/product-vault/', 'Product Vault' ),
			'product_vault' => self::page( 'Product Vault', 'product-vault', '[algq_product_vault]', 'Protected Downloads • Customer Access', 'Product Vault', 'Access authorized digital products, purchased resources, templates, calculators, and related documentation.', '#algq-module', 'Open Vault', '/digital-store/', 'Return to Store' ),
			'checkout' => self::page( 'Store Checkout', 'store-checkout', '[algq_store_checkout]', 'Secure Checkout • Digital Entitlements', 'Complete Your Order', 'Complete checkout for approved digital products and receive controlled access through the Product Vault.', '#algq-module', 'Continue Checkout', '/digital-store/', 'Back to Store' ),
			'admin_dashboard' => self::page( 'Algonquian Admin Command Center', 'dashboard', '[algq_admin_dashboard]', 'Executive Oversight • Internal Access', 'Admin Command Center', 'Monitor acquisition activity, pipeline value, underwriting, offers, buyers, funding, documents, automation, and system health.', '#algq-module', 'Open Dashboard', '/algonquian-real-estate-platform/', 'Platform Overview' ),
			'property_stewardship' => self::page( 'Property Stewardship Services', 'property-stewardship-services', '<!-- algq-property-stewardship-services -->', 'Property Monitoring • Coordination • Owner Support', 'Property Stewardship Services', 'Reliable local oversight and property-service coordination for homeowners who cannot always be there.', '/property-stewardship-consultation/', 'Request a Consultation', '#service-levels', 'View Service Levels', 'stewardship' ),
			'trusted_property_contact' => self::page( 'Trusted Property Contact™', 'trusted-property-contact', '<!-- algq-trusted-property-contact -->', 'A Reliable Local Contact for Your Property', 'Trusted Property Contact™', 'Professional property-related communication and coordination when you need someone local who knows your property.', '/property-stewardship-consultation/', 'Request More Information', '/property-stewardship-services/', 'View Stewardship Services', 'trusted' ),
			'property_stewardship_consultation' => self::page( 'Property Stewardship Consultation', 'property-stewardship-consultation', '[algq_seller_intake]', 'Confidential Initial Property Review', 'Request a Property Stewardship Consultation', 'Tell us about the property, your location, and the assistance you are seeking. An inquiry does not obligate you to purchase services or sell your property.', '#algq-module', 'Start Inquiry', '/property-stewardship-services/', 'Learn About Stewardship', 'consultation' ),
		);
	}

	private static function page( $title, $slug, $shortcode, $eyebrow, $heading, $intro, $primary_url, $primary_cta, $second_url, $second_cta, $template = 'standard' ) {
		return compact( 'title', 'slug', 'shortcode', 'eyebrow', 'heading', 'intro', 'primary_url', 'primary_cta', 'second_url', 'second_cta', 'template' );
	}

	public static function create_pages() {
		$page_ids = array();
		foreach ( self::pages() as $key => $page ) {
			$existing = get_page_by_path( $page['slug'], OBJECT, 'page' );
			$content  = self::build_page_content( $page );
			if ( $existing instanceof WP_Post ) {
				$page_ids[ $key ] = (int) $existing->ID;
				if ( false === strpos( (string) $existing->post_content, $page['shortcode'] ) ) {
					wp_update_post( array( 'ID' => (int) $existing->ID, 'post_content' => $content ) );
				}
				continue;
			}
			$page_id = wp_insert_post( array( 'post_title' => sanitize_text_field( $page['title'] ), 'post_name' => sanitize_title( $page['slug'] ), 'post_content' => $content, 'post_status' => 'publish', 'post_type' => 'page' ) );
			if ( ! is_wp_error( $page_id ) ) {
				$page_ids[ $key ] = (int) $page_id;
				update_post_meta( $page_id, '_algq_generated_page', '1' );
				update_post_meta( $page_id, '_algq_required_shortcode', $page['shortcode'] );
			}
		}
		update_option( 'algq_platform_generated_pages', $page_ids );
		return $page_ids;
	}

	private static function build_page_content( $page ) {
		if ( 'stewardship' === $page['template'] ) {
			return self::stewardship_page( $page );
		}
		if ( 'trusted' === $page['template'] ) {
			return self::trusted_page( $page );
		}
		if ( 'consultation' === $page['template'] ) {
			return self::consultation_page( $page );
		}
		return self::hero( $page, self::default_metrics() ) . '[vc_row el_id="algq-module" css=".vc_custom_algq_module_section{padding-top:45px !important;padding-bottom:45px !important;background:#f4f6f8 !important;}"][vc_column][vc_column_text]' . $page['shortcode'] . '[/vc_column_text][/vc_column][/vc_row][vc_row][vc_column][vc_empty_space height="35px"][vc_column_text]<div style="max-width:900px;margin:0 auto;text-align:center;"><h2>Disciplined Systems. Clear Workflows. Long-Term Value.</h2><p>Algonquian Real Estate combines real estate operations, institutional documentation, and proprietary technology infrastructure to support consistent decision-making and responsible growth.</p></div>[/vc_column_text][vc_empty_space height="35px"][/vc_column][/vc_row]';
	}

	private static function stewardship_page( $page ) {
		return '<!-- algq-property-stewardship-services -->' . self::hero( $page, self::stewardship_metrics() )
			. '[vc_row][vc_column width="1/2"][vc_single_image image="1535" img_size="large" alignment="center"][/vc_column][vc_column width="1/2"][vc_column_text]<h2>Support for Owners Who Cannot Always Be There</h2><p>A property can require ongoing attention even when the owner is not preparing to sell.</p><p>These services are designed for homeowners who are aging, traveling, living outside the area, managing an inherited or vacant property, or having difficulty coordinating routine property needs.</p><p>We provide a reliable local point of contact to observe, document, communicate, and coordinate authorized property-related services.</p>[/vc_column_text][/vc_column][/vc_row]'
			. '[vc_row css=".vc_custom_section_gray{background:#f4f6f8;padding-top:55px;padding-bottom:55px;}"][vc_column][vc_column_text]<h2 style="text-align:center;">Available Stewardship Services</h2><ul><li>Scheduled property check-ins with photographs and written updates</li><li>Vendor, lawn, landscaping, snow-removal, repair, and maintenance coordination</li><li>Storm observations after conditions are reasonably safe</li><li>Visible vacancy, damage, and deterioration monitoring</li><li>Emergency contact coordination for property-related incidents</li><li>Maintenance-history and service-record organization</li></ul>[/vc_column_text][/vc_column][/vc_row]'
			. '[vc_row el_id="service-levels" css=".vc_custom_service_levels{background:#0f1923;padding-top:65px;padding-bottom:65px;}"][vc_column][vc_column_text]<h2 style="color:#e6eef5;text-align:center;">Service Levels</h2><p style="color:#e6eef5;opacity:.82;text-align:center;max-width:800px;margin:12px auto 38px;">Each engagement is defined through a written agreement identifying visit frequency, access, authorized activities, and communication procedures.</p>[/vc_column_text][vc_row_inner]'
			. self::dark_card( 'Essential Watch', 'Scheduled exterior visit, photographic update, visible-condition summary, and owner notification of observed concerns.', '#00a8ff' )
			. self::dark_card( 'Active Steward', 'Essential Watch plus maintenance scheduling, vendor coordination, seasonal observations, and vacancy monitoring.', '#d1a54a' )
			. self::dark_card( 'Transition Support', 'Active Steward plus transition consultation, maintenance planning, and sale, lease, or renovation preparation support.', '#3ed4c9' )
			. '[/vc_row_inner][/vc_column][/vc_row]'
			. '[vc_row][vc_column][vc_column_text]<h2>Our Role and Service Boundaries</h2><p>Algonquian Real Estate acts only within the written scope authorized by the property owner. These services do not create an attorney-client, fiduciary, guardianship, conservatorship, trustee, executor, power-of-attorney, caregiving, or emergency-response relationship.</p><p>We do not control owner finances, enter contracts in the owner’s name, approve unauthorized expenditures, provide legal or financial advice, perform regulated trade services, or guarantee prevention of property loss.</p>[/vc_column_text][/vc_column][/vc_row]'
			. self::cta( 'Protect Your Property With Reliable Local Support', 'Tell us about the property and the assistance that would provide the greatest peace of mind.', '/property-stewardship-consultation/', 'Request a Stewardship Consultation' );
	}

	private static function trusted_page( $page ) {
		return '<!-- algq-trusted-property-contact -->' . self::hero( $page, self::trusted_metrics() )
			. '[vc_row][vc_column width="1/2"][vc_column_text]<h2>You May Not Need to Sell</h2><p>Many homeowners do not need someone to purchase their property today. They need someone dependable who can answer a call, visit when authorized, coordinate a service appointment, document visible concerns, and help them understand what is happening locally.</p><p>Trusted Property Contact™ provides that practical point of contact without taking control away from the owner.</p>[/vc_column_text][/vc_column][vc_column width="1/2"][vc_single_image image="1535" img_size="large" alignment="center"][/vc_column][/vc_row]'
			. '[vc_row css=".vc_custom_trusted_for{background:#f4f6f8;padding-top:55px;padding-bottom:55px;}"][vc_column][vc_column_text]<h2 style="text-align:center;">Who This Service May Help</h2><ul><li>Homeowners who live alone or have limited local support</li><li>Owners whose family lives outside Connecticut</li><li>People who travel for extended periods</li><li>Second-home, inherited-property, and temporarily vacant-property owners</li></ul>[/vc_column_text][/vc_column][/vc_row]'
			. '[vc_row css=".vc_custom_trusted_boundaries{background:#0f1923;padding-top:60px;padding-bottom:60px;}"][vc_column][vc_column_text]<h2 style="color:#e6eef5;text-align:center;">The Owner Remains in Control</h2><p style="color:#e6eef5;opacity:.88;text-align:center;">Our authority is limited to the services expressly approved by the property owner in writing.</p></div>[/vc_column_text][vc_row_inner]'
			. self::dark_card( 'What We May Do', 'Observe and report, communicate with the owner, coordinate approved services, maintain records, and connect licensed professionals.', '#3ed4c9', '1/2' )
			. self::dark_card( 'What We Do Not Replace', 'An attorney, trustee, executor, conservator, power of attorney, financial adviser, contractor, inspector, or emergency service.', '#d1a54a', '1/2' )
			. '[/vc_row_inner][/vc_column][/vc_row]'
			. self::cta( 'A Relationship Built on Communication and Respect', 'Reliable communication, local knowledge, professional coordination, and respect for the owner’s instructions.', '/property-stewardship-consultation/', 'Request More Information' );
	}

	private static function consultation_page( $page ) {
		return self::hero( $page, self::default_metrics() )
			. '[vc_row el_id="algq-module"][vc_column width="1/3"][vc_column_text]<div style="background:#0f1923;border-radius:18px;padding:30px;"><h2 style="color:#e6eef5;">What Happens Next</h2><p style="color:#e6eef5;"><strong>1. Initial Inquiry</strong><br>Provide basic property and contact information.</p><p style="color:#e6eef5;"><strong>2. Consultation</strong><br>Discuss scope, frequency, access, and communication preferences.</p><p style="color:#e6eef5;"><strong>3. Written Service Plan</strong><br>Any engagement is documented before services begin.</p></div>[/vc_column_text][/vc_column][vc_column width="2/3"][vc_column_text]<h2>Property Stewardship Inquiry</h2><p>Do not submit banking information, passwords, access codes, medical records, or other highly sensitive information through this form.</p>[/vc_column_text][vc_column_text]' . $page['shortcode'] . '[/vc_column_text][/vc_column][/vc_row]'
			. '[vc_row css=".vc_custom_consultation_information{background:#f4f6f8;padding-top:55px;padding-bottom:55px;}"][vc_column][vc_column_text]<h2 style="text-align:center;">Before Services Begin</h2><p style="max-width:900px;margin:14px auto;">A written agreement should identify the property, authorized client, requested services, visit frequency, access procedures, vendor and expenditure authorization, emergency communication procedures, fees, cancellation rights, insurance provisions, and limitations of service.</p>[/vc_column_text][/vc_column][/vc_row]'
			. self::cta( 'Not Ready to Sell? You Still Have Options.', 'Property stewardship can support visibility, organization, and local coordination while you decide what is right for the property’s future.', '/property-stewardship-services/', 'Learn About Property Stewardship' );
	}

	private static function hero( $page, $metrics ) {
		$hero = (int) apply_filters( 'algq_platform_page_hero_image_id', 6422, $page );
		return '[vc_row full_width="stretch_row_content" parallax="content-moving" parallax_image="' . $hero . '"][vc_column css=".vc_custom_algonquian_platform_hero{background:linear-gradient(180deg,rgba(15,25,35,.92) 0%,rgba(15,25,35,.74) 55%,rgba(15,25,35,.94) 100%) !important;}"][vc_empty_space height="40px"][vc_column_text]<div style="text-align:center;max-width:1100px;margin:0 auto;"><div style="display:inline-block;padding:10px 18px;border:1px solid rgba(230,238,245,.20);border-radius:999px;background:rgba(11,58,99,.35);color:#e6eef5;font-size:13px;letter-spacing:.08em;text-transform:uppercase;">' . esc_html( $page['eyebrow'] ) . '</div></div>[/vc_column_text][vc_empty_space height="22px"][vc_column_text]<h1 style="color:#e6eef5;font-size:58px;line-height:1.05;font-weight:800;text-align:center;margin:0;">' . esc_html( $page['heading'] ) . '</h1><p style="color:#e6eef5;opacity:.92;font-size:20px;line-height:1.6;text-align:center;max-width:980px;margin:14px auto 0;">' . esc_html( $page['intro'] ) . '</p>[/vc_column_text][vc_empty_space height="28px"]' . $metrics . '[vc_empty_space height="36px"][vc_row_inner][vc_column_inner width="1/2"][la_btn title="' . esc_attr( $page['primary_cta'] ) . '" shape="rounded" color="primary" size="lg" align="center" link="url:' . rawurlencode( $page['primary_url'] ) . '|title:' . rawurlencode( $page['primary_cta'] ) . '"][/vc_column_inner][vc_column_inner width="1/2"][la_btn title="' . esc_attr( $page['second_cta'] ) . '" style="outline" shape="rounded" size="lg" align="center" link="url:' . rawurlencode( $page['second_url'] ) . '|title:' . rawurlencode( $page['second_cta'] ) . '"][/vc_column_inner][/vc_row_inner][vc_empty_space height="50px"][/vc_column][/vc_row]';
	}

	private static function default_metrics() {
		return self::metrics( array( array( 'CT', 'Market', 'Connecticut-Based Operations', '#3ed4c9' ), array( 'DI', 'Acquisition', 'Seller Intake & Deal Flow', '#00a8ff' ), array( 'UW', 'Underwriting', 'Cash-Flow-Driven Analysis', '#d1a54a' ), array( 'LT', 'Strategy', 'Long-Term Value Creation', '#e6eef5' ) ) );
	}

	private static function stewardship_metrics() {
		return self::metrics( array( array( 'PC', 'Property Check-Ins', 'Scheduled Visits & Reports', '#00a8ff' ), array( 'VC', 'Vendor Coordination', 'Authorized Service Scheduling', '#3ed4c9' ), array( 'SI', 'Storm Inspections', 'Post-Weather Observations', '#d1a54a' ), array( 'EC', 'Emergency Contact', 'Property-Related Coordination', '#e6eef5' ) ) );
	}

	private static function trusted_metrics() {
		return self::metrics( array( array( '01', 'Observe', 'Property Check-Ins', '#00a8ff' ), array( '02', 'Coordinate', 'Approved Services', '#3ed4c9' ), array( '03', 'Report', 'Photos & Records', '#d1a54a' ), array( '04', 'Respect', 'Owner-Controlled Scope', '#e6eef5' ) ) );
	}

	private static function metrics( $cards ) {
		$out = '[vc_row_inner]';
		foreach ( $cards as $card ) {
			$out .= '[vc_column_inner width="1/4"][vc_column_text]<div style="display:flex;gap:12px;align-items:flex-start;padding:18px;border:1px solid rgba(230,238,245,.14);border-radius:16px;background:rgba(11,58,99,.28);min-height:120px;"><div style="width:44px;height:44px;border-radius:12px;background:rgba(230,238,245,.10);display:flex;align-items:center;justify-content:center;border:1px solid rgba(230,238,245,.22);flex-shrink:0;"><span style="color:' . esc_attr( $card[3] ) . ';font-weight:800;">' . esc_html( $card[0] ) . '</span></div><div><div style="color:#e6eef5;font-weight:bold;font-size:14px;letter-spacing:.06em;text-transform:uppercase;">' . esc_html( $card[1] ) . '</div><div style="color:#e6eef5;opacity:.90;font-size:15px;margin-top:6px;">' . esc_html( $card[2] ) . '</div></div></div>[/vc_column_text][/vc_column_inner]';
		}
		return $out . '[/vc_row_inner]';
	}

	private static function dark_card( $title, $copy, $accent, $width = '1/3' ) {
		return '[vc_column_inner width="' . esc_attr( $width ) . '"][vc_column_text]<div style="padding:30px;border:1px solid ' . esc_attr( $accent ) . ';border-radius:18px;background:rgba(11,58,99,.28);min-height:260px;"><h3 style="color:' . esc_attr( $accent ) . ';">' . esc_html( $title ) . '</h3><p style="color:#e6eef5;opacity:.90;line-height:1.8;">' . esc_html( $copy ) . '</p></div>[/vc_column_text][/vc_column_inner]';
	}

	private static function cta( $title, $copy, $url, $label ) {
		return '[vc_row full_width="stretch_row_content" css=".vc_custom_algq_cta{background:#0b3a63;padding-top:65px;padding-bottom:65px;}"][vc_column][vc_column_text]<h2 style="color:#fff;text-align:center;">' . esc_html( $title ) . '</h2><p style="color:#fff;opacity:.88;font-size:18px;line-height:1.7;text-align:center;max-width:850px;margin:14px auto 30px;">' . esc_html( $copy ) . '</p>[/vc_column_text][la_btn title="' . esc_attr( $label ) . '" shape="rounded" color="primary" size="lg" align="center" link="url:' . rawurlencode( $url ) . '|title:' . rawurlencode( $label ) . '"][vc_empty_space height="10px"][/vc_column][/vc_row]';
	}
}
