<?php
/**
 * Automatic branded WPBakery page generation for the Algonquian platform.
 *
 * @package AlgonquianRealEstatePlatform
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Creates required pages and inserts the correct platform shortcodes.
 */
final class ALGQ_Platform_Page_Generator {

	/**
	 * Required platform pages.
	 *
	 * @return array<string,array<string,mixed>>
	 */
	public static function pages() {
		return array(
			'platform' => self::page(
				'Algonquian Real Estate Platform', 'algonquian-real-estate-platform', '[algq_plugin_suite]',
				'ARE Technology Division • Platform Infrastructure', 'Algonquian Real Estate Platform',
				'A unified acquisition, underwriting, transaction, document, buyer, funding, automation, and executive reporting platform developed for Algonquian Real Estate LLC.',
				'/plugins/', 'Explore Platform', '/contact/', 'Contact ARE'
			),
			'seller_intake' => self::page(
				'Sell Your Property', 'sell-your-property', '[algq_seller_intake]',
				'Seller-Direct Acquisition • Connecticut', 'Submit a Property',
				'Provide seller and property information for confidential preliminary review by Algonquian Real Estate.',
				'#algq-module', 'Start Submission', '/contact/', 'Contact Acquisitions'
			),
			'mao_calculator' => self::page(
				'MAO Calculator', 'mao-calculator', '[algq_mao_calculator]',
				'Disciplined Underwriting • Scenario Analysis', 'Maximum Allowable Offer Engine',
				'Evaluate acquisition scenarios using ARV, repairs, operating assumptions, financing costs, and target returns.',
				'#algq-module', 'Open Calculator', '/underwriting/', 'View Underwriting'
			),
			'buyer_registration' => self::page(
				'Buyer Registration', 'buyers-register', '[algq_buyer_registration]',
				'Investor Access • Controlled Deal Distribution', 'Register as a Buyer',
				'Create a buyer profile, provide acquisition criteria, and request access to qualified investment opportunities.',
				'#algq-module', 'Register Now', '/buyer-dashboard/', 'Buyer Dashboard'
			),
			'pipeline' => array_merge(
				self::page(
					'Pipeline CRM', 'pipeline', '[algq_pipeline_crm]',
					'Acquisition Lifecycle • Internal Operations', 'Deal Pipeline CRM',
					'Manage opportunities from initial intake through underwriting, offer, contract, funding, buyer assignment, and closing.',
					'#algq-module', 'Open Pipeline', '/plugin/pipeline-crm/start/', 'Getting Started'
				),
				array(
					'cards' => self::pipeline_cards(),
					'body'  => self::pipeline_overview_body(),
				)
			),
			'pipeline_overview' => array_merge(
				self::page(
					'Algonquian Pipeline CRM', 'plugin/pipeline-crm', '[algq_pipeline_crm]',
					'Algonquian Real Estate Platform • Enterprise Operations', 'Algonquian Pipeline CRM',
					'The authoritative acquisition workspace for canonical deal records, assignments, stage controls, notes, tasks, activity history, and closing status.',
					'/pipeline/', 'Open Pipeline', '/plugin/pipeline-crm/start/', 'Getting Started'
				),
				array(
					'cards' => self::pipeline_cards(),
					'body'  => self::pipeline_overview_body(),
				)
			),
			'pipeline_start' => array_merge(
				self::page(
					'Getting Started With the Algonquian Pipeline CRM', 'plugin/pipeline-crm/start', '[algq_pipeline_crm]',
					'Algonquian Real Estate Platform • Enterprise Operations', 'Getting Started With the Algonquian Pipeline CRM',
					'Configure the acquisition pipeline, assign deal responsibility, manage activity, and move opportunities through a controlled lifecycle from intake through closing.',
					'/pipeline/', 'Open Pipeline Board', '/plugin/pipeline-crm/docs/', 'View Documentation'
				),
				array(
					'cards'        => self::pipeline_cards(),
					'body'         => self::pipeline_getting_started_body(),
					'module_title' => 'Live Pipeline Workspace',
				)
			),
			'pipeline_docs' => array_merge(
				self::page(
					'Algonquian Pipeline CRM Documentation', 'plugin/pipeline-crm/docs', '[algq_pipeline_crm]',
					'Pipeline CRM • User and Administrator Reference', 'Pipeline CRM Documentation',
					'Review stage definitions, deal-record requirements, permissions, automation events, integration boundaries, and operational controls.',
					'/plugin/pipeline-crm/start/', 'Getting Started', '/pipeline/', 'Open Pipeline'
				),
				array(
					'cards' => self::pipeline_cards(),
					'body'  => self::pipeline_docs_body(),
				)
			),
			'buyer_portal' => self::page(
				'Buyer Portal', 'buyer-dashboard', '[algq_buyer_portal]',
				'Secure Buyer Access • Deal Packages', 'Buyer Portal',
				'Review permitted deal opportunities, manage NDA status, express interest, and access controlled transaction materials.',
				'#algq-module', 'Open Portal', '/buyers-register/', 'Register'
			),
			'funding' => self::page(
				'Funding Tracker', 'funding-dashboard', '[algq_funding_tracker]',
				'Capital Sources • Funding Coordination', 'Funding Tracker',
				'Track lenders, private capital, joint-venture relationships, commitments, terms, and funding status by deal.',
				'#algq-module', 'View Funding', '/contact/', 'Capital Inquiries'
			),
			'documents' => self::page(
				'Document Library', 'documents', '[algq_document_library]',
				'Institutional Documentation • Controlled Access', 'Document Library',
				'Centralized access to entity, financing, acquisition, financial-control, risk-management, and property-management documentation.',
				'#algq-module', 'Open Library', '/contact/', 'Request Documents'
			),
			'automation' => self::page(
				'Automation Engine', 'automation-rules', '[algq_automation_engine]',
				'Workflow Automation • Event Processing', 'Automation Engine',
				'Coordinate status-based actions, document generation, notifications, tasks, signature workflows, and closing processes.',
				'#algq-module', 'View Automations', '/dashboard/', 'System Health'
			),
			'digital_store' => self::page(
				'Digital Store', 'digital-store', '[algq_digital_store]',
				'ARE Digital Products • Real Estate Systems', 'Algonquian Digital Store',
				'Explore real estate templates, forms, calculators, workflow systems, documentation packages, and operational tools.',
				'#algq-module', 'Browse Products', '/product-vault/', 'Product Vault'
			),
			'product_vault' => self::page(
				'Product Vault', 'product-vault', '[algq_product_vault]',
				'Protected Downloads • Customer Access', 'Product Vault',
				'Access authorized digital products, purchased resources, templates, calculators, and related documentation.',
				'#algq-module', 'Open Vault', '/digital-store/', 'Return to Store'
			),
			'checkout' => self::page(
				'Store Checkout', 'store-checkout', '[algq_store_checkout]',
				'Secure Checkout • Digital Entitlements', 'Complete Your Order',
				'Complete checkout for approved digital products and receive controlled access through the Product Vault.',
				'#algq-module', 'Continue Checkout', '/digital-store/', 'Back to Store'
			),
			'admin_dashboard' => self::page(
				'Algonquian Admin Command Center', 'dashboard', '[algq_admin_dashboard]',
				'Executive Oversight • Internal Access', 'Admin Command Center',
				'Monitor acquisition activity, pipeline value, underwriting, offers, buyers, funding, documents, automation, and system health.',
				'#algq-module', 'Open Dashboard', '/algonquian-real-estate-platform/', 'Platform Overview'
			),
		);
	}

	private static function page( $title, $slug, $shortcode, $eyebrow, $heading, $intro, $primary_url, $primary_cta, $second_url, $second_cta ) {
		return compact( 'title', 'slug', 'shortcode', 'eyebrow', 'heading', 'intro', 'primary_url', 'primary_cta', 'second_url', 'second_cta' );
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

			$page_id = wp_insert_post(
				array(
					'post_title'   => sanitize_text_field( $page['title'] ),
					'post_name'    => sanitize_title( basename( $page['slug'] ) ),
					'post_content' => $content,
					'post_status'  => 'publish',
					'post_type'    => 'page',
				)
			);

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
		$hero_image   = (int) apply_filters( 'algq_platform_page_hero_image_id', 6422, $page );
		$eyebrow      = esc_html( $page['eyebrow'] );
		$heading      = esc_html( $page['heading'] );
		$intro        = esc_html( $page['intro'] );
		$primary      = rawurlencode( $page['primary_url'] );
		$secondary    = rawurlencode( $page['second_url'] );
		$primary_cta  = esc_attr( $page['primary_cta'] );
		$second_cta   = esc_attr( $page['second_cta'] );
		$cards        = isset( $page['cards'] ) ? $page['cards'] : self::default_cards();
		$body         = isset( $page['body'] ) ? $page['body'] : self::default_body();
		$module_title = isset( $page['module_title'] ) ? esc_html( $page['module_title'] ) : '';

		return '[vc_row full_width="stretch_row_content" parallax="content-moving" parallax_image="' . $hero_image . '"]'
			. '[vc_column css=".vc_custom_algonquian_platform_hero{background:linear-gradient(180deg, rgba(15,25,35,0.90) 0%, rgba(15,25,35,0.72) 55%, rgba(15,25,35,0.92) 100%) !important;}"]'
			. '[vc_empty_space height="20px"]'
			. '[vc_column_text]<div style="text-align:center;max-width:1100px;margin:0 auto;"><div style="display:inline-block;padding:10px 18px;border:1px solid rgba(230,238,245,0.20);border-radius:999px;background:rgba(11,58,99,0.35);color:#e6eef5;font-size:13px;letter-spacing:.08em;text-transform:uppercase;">' . $eyebrow . '</div></div>[/vc_column_text]'
			. '[vc_empty_space height="22px"]'
			. '[vc_column_text]<h1 style="color:#e6eef5;font-size:58px;line-height:1.05;font-weight:800;text-align:center;margin:0;">' . $heading . '</h1><p style="color:#e6eef5;opacity:.92;font-size:20px;line-height:1.6;text-align:center;max-width:980px;margin:14px auto 0;">' . $intro . '</p>[/vc_column_text]'
			. '[vc_empty_space height="28px"]'
			. self::metric_row( $cards )
			. '[vc_empty_space height="40px"]'
			. '[vc_row_inner][vc_column_inner width="1/2"][la_btn title="' . $primary_cta . '" shape="rounded" color="primary" size="lg" align="center" link="url:' . $primary . '|title:' . rawurlencode( $page['primary_cta'] ) . '"][vc_empty_space height="15px"][/vc_column_inner]'
			. '[vc_column_inner width="1/2"][la_btn title="' . $second_cta . '" style="outline" shape="rounded" size="lg" align="center" link="url:' . $secondary . '|title:' . rawurlencode( $page['second_cta'] ) . '"][vc_empty_space height="15px"][/vc_column_inner][/vc_row_inner]'
			. '[vc_empty_space height="50px"][/vc_column][/vc_row]'
			. $body
			. '[vc_row el_id="algq-module" css=".vc_custom_algq_module_section{padding-top:45px !important;padding-bottom:45px !important;background:#f4f6f8 !important;}"][vc_column]'
			. ( $module_title ? '[vc_column_text]<h2 style="text-align:center;">' . $module_title . '</h2>[/vc_column_text][vc_empty_space height="15px"]' : '' )
			. '[vc_column_text]' . $page['shortcode'] . '[/vc_column_text][/vc_column][/vc_row]'
			. '[vc_row full_width="stretch_row_content" parallax="content-moving" parallax_image="' . $hero_image . '"][vc_column css=".vc_custom_algonquian_footer_hero{background:linear-gradient(180deg, rgba(15,25,35,0.92) 0%, rgba(15,25,35,0.78) 55%, rgba(15,25,35,0.94) 100%) !important;}"][vc_empty_space height="60px"][vc_column_text]<div style="max-width:900px;margin:0 auto;text-align:center;"><h2 style="color:#fff;">Disciplined Systems. Clear Workflows. Long-Term Value.</h2><p style="color:#fff;opacity:.88;font-size:18px;line-height:1.7;">Algonquian Real Estate combines real estate operations, institutional documentation, and proprietary technology infrastructure to support consistent decision-making and responsible growth.</p></div>[/vc_column_text][vc_empty_space height="60px"][/vc_column][/vc_row]';
	}

	private static function default_cards() {
		return array(
			array( 'code' => 'CT', 'label' => 'Market', 'value' => 'Connecticut-Based Operations', 'accent' => '#3ed4c9' ),
			array( 'code' => 'DI', 'label' => 'Acquisition', 'value' => 'Seller Intake & Deal Flow', 'accent' => '#00a8ff' ),
			array( 'code' => 'UW', 'label' => 'Underwriting', 'value' => 'Cash-Flow-Driven Analysis', 'accent' => '#d1a54a' ),
			array( 'code' => 'LT', 'label' => 'Strategy', 'value' => 'Long-Term Value Creation', 'accent' => '#e6eef5' ),
		);
	}

	private static function pipeline_cards() {
		return array(
			array( 'code' => 'CRM', 'label' => 'System', 'value' => 'Acquisition Pipeline', 'accent' => '#00a8ff' ),
			array( 'code' => 'ID', 'label' => 'Primary Record', 'value' => 'Canonical Deal Record', 'accent' => '#3ed4c9' ),
			array( 'code' => 'KB', 'label' => 'Interface', 'value' => 'Kanban & Deal Workspace', 'accent' => '#d1a54a' ),
			array( 'code' => 'CL', 'label' => 'Lifecycle', 'value' => 'Intake Through Closing', 'accent' => '#e6eef5' ),
		);
	}

	private static function metric_row( $cards ) {
		$output = '[vc_row_inner]';
		foreach ( $cards as $card ) {
			$output .= '[vc_column_inner width="1/4"][vc_column_text]'
				. '<div style="display:flex;gap:12px;align-items:flex-start;padding:18px;border:1px solid rgba(230,238,245,.14);border-radius:16px;background:rgba(11,58,99,.28);">'
				. '<div style="width:44px;height:44px;border-radius:12px;background:rgba(230,238,245,.10);display:flex;align-items:center;justify-content:center;border:1px solid rgba(230,238,245,.22);"><span style="color:' . esc_attr( $card['accent'] ) . ';font-weight:800;">' . esc_html( $card['code'] ) . '</span></div>'
				. '<div><div style="color:#e6eef5;font-weight:bold;font-size:14px;letter-spacing:.06em;text-transform:uppercase;">' . esc_html( $card['label'] ) . '</div><div style="color:#e6eef5;opacity:.90;font-size:15px;margin-top:6px;">' . esc_html( $card['value'] ) . '</div></div></div>'
				. '[/vc_column_text][/vc_column_inner]';
		}
		return $output . '[/vc_row_inner]';
	}

	private static function default_body() {
		return '[vc_row][vc_column][vc_empty_space height="35px"][vc_column_text]<div style="max-width:900px;margin:0 auto;text-align:center;"><h2>Enterprise Platform Infrastructure</h2><p>Use the workspace below to access the active module. Generated pages retain the approved Algonquian Real Estate visual system while preserving administrator-edited content.</p></div>[/vc_column_text][vc_empty_space height="35px"][/vc_column][/vc_row]';
	}

	private static function pipeline_overview_body() {
		return '[vc_row][vc_column width="1/2"][vc_column_text]<h2>Authoritative Deal Management</h2><p>The Pipeline CRM maintains the master deal record, current lifecycle stage, assignment, priority, internal notes, tasks, activity history, related records, and final closing or archive status.</p><p>Connected modules reference the same canonical deal identifier rather than creating competing deal records.</p>[/vc_column_text][/vc_column][vc_column width="1/2"][vc_column_text]<h2>Core Interfaces</h2><ul><li>Kanban pipeline board</li><li>Deal list and filters</li><li>Deal detail workspace</li><li>Activity timeline</li><li>Task and follow-up controls</li><li>Stage-history view</li><li>Related underwriting, offers, documents, funding, and automation</li></ul>[/vc_column_text][/vc_column][/vc_row]';
	}

	private static function pipeline_getting_started_body() {
		return '[vc_row][vc_column width="1/2"][vc_column_text]<h2>1. Verify Platform Readiness</h2><p>Confirm that the Platform Plugin and Pipeline CRM are active, compatible, using the current schema, and registered with the platform health system.</p><h2>2. Configure Stages</h2><p>Use stable lifecycle keys for New Intake, Contact Attempted, Contact Established, Preliminary Review, Underwriting, Offer Preparation, Offer Sent, Negotiation, Under Contract, Due Diligence, Funding, Buyer Distribution, Closing Scheduled, Closed, Lost, Withdrawn, and Archived.</p>[/vc_column_text][/vc_column][vc_column width="1/2"][vc_column_text]<h2>3. Assign Permissions</h2><p>Grant only the capabilities required to view, create, edit, assign, move, close, archive, export, or audit deal records.</p><h2>4. Create the First Deal</h2><p>Create a deal through Deal Intake or the authorized internal entry process. Confirm that the record appears once in the deal list and once on the Kanban board.</p>[/vc_column_text][/vc_column][/vc_row]'
			. '[vc_row css=".vc_custom_section_gray{background:#f4f6f8;padding-top:45px !important;padding-bottom:45px !important;}"][vc_column][vc_column_text]<h2>Controlled Operating Workflow</h2><p><strong>Intake → Contact → Preliminary Review → Underwriting → Offer → Negotiation → Contract → Due Diligence → Funding or Buyer Distribution → Closing → Archive</strong></p><p>Every stage movement must pass server-side validation and create a stage-history and audit event.</p>[/vc_column_text][/vc_column][/vc_row]'
			. '[vc_row][vc_column width="1/2"][vc_column_text]<h2>Required Transition Controls</h2><ul><li>Offer Sent requires an offer record.</li><li>Under Contract requires an acknowledged or executed contract.</li><li>Closed requires a closing date and disposition record.</li><li>Lost requires a standardized loss reason.</li><li>Reopening an archived deal creates an audit event.</li></ul>[/vc_column_text][/vc_column][vc_column width="1/2"][vc_column_text]<h2>Daily Operating Practice</h2><ul><li>Review all new intake records.</li><li>Assign every active deal.</li><li>Set the next action and due date.</li><li>Update seller contact activity.</li><li>Resolve overdue tasks and stalled deals.</li><li>Confirm offer, contract, funding, and closing status.</li></ul>[/vc_column_text][/vc_column][/vc_row]';
	}

	private static function pipeline_docs_body() {
		return '[vc_row][vc_column width="1/2"][vc_column_text]<h2>Canonical Deal Record</h2><ul><li>Deal UUID and human-readable deal number</li><li>Property and primary seller references</li><li>Assigned user and current stage</li><li>Priority and acquisition strategy</li><li>Asking price and current offer amount</li><li>Underwriting, contract, buyer, funding, and closing status</li><li>Created date, last activity, and archive status</li></ul>[/vc_column_text][/vc_column][vc_column width="1/2"][vc_column_text]<h2>Integration Boundary</h2><p>The CRM owns the deal lifecycle. Deal Intake creates normalized submissions; the MAO Engine owns underwriting; Offer Generator owns offers; Document Library owns document metadata; PDF & Signature owns rendering and signing; Automation Engine orchestrates approved actions.</p>[/vc_column_text][/vc_column][/vc_row]'
			. '[vc_row css=".vc_custom_section_gray{background:#f4f6f8;padding-top:45px !important;padding-bottom:45px !important;}"][vc_column][vc_column_text]<h2>Security and Audit Requirements</h2><p>All administrative screens, REST requests, exports, file links, and stage-changing actions require capability checks, nonce or request validation, input validation, output escaping, and append-only audit events for material changes.</p>[/vc_column_text][/vc_column][/vc_row]';
	}
}
