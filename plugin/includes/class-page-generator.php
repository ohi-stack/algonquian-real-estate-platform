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
			'platform' => array(
				'title'       => 'Algonquian Real Estate Platform',
				'slug'        => 'algonquian-real-estate-platform',
				'shortcode'   => '[algq_plugin_suite]',
				'eyebrow'     => 'ARE Technology Division • Platform Infrastructure',
				'heading'     => 'Algonquian Real Estate Platform',
				'intro'       => 'A unified acquisition, underwriting, transaction, document, buyer, funding, automation, and executive reporting platform developed for Algonquian Real Estate LLC.',
				'primary_url' => '/plugins/',
				'primary_cta' => 'Explore Platform',
				'second_url'  => '/contact/',
				'second_cta'  => 'Contact ARE',
			),
			'seller_intake' => array(
				'title'       => 'Sell Your Property',
				'slug'        => 'sell-your-property',
				'shortcode'   => '[algq_seller_intake]',
				'eyebrow'     => 'Seller-Direct Acquisition • Connecticut',
				'heading'     => 'Submit a Property',
				'intro'       => 'Provide seller and property information for confidential preliminary review by Algonquian Real Estate.',
				'primary_url' => '#algq-module',
				'primary_cta' => 'Start Submission',
				'second_url'  => '/contact/',
				'second_cta'  => 'Contact Acquisitions',
			),
			'mao_calculator' => array(
				'title'       => 'MAO Calculator',
				'slug'        => 'mao-calculator',
				'shortcode'   => '[algq_mao_calculator]',
				'eyebrow'     => 'Disciplined Underwriting • Scenario Analysis',
				'heading'     => 'Maximum Allowable Offer Engine',
				'intro'       => 'Evaluate acquisition scenarios using ARV, repairs, operating assumptions, financing costs, and target returns.',
				'primary_url' => '#algq-module',
				'primary_cta' => 'Open Calculator',
				'second_url'  => '/underwriting/',
				'second_cta'  => 'View Underwriting',
			),
			'buyer_registration' => array(
				'title'       => 'Buyer Registration',
				'slug'        => 'buyers-register',
				'shortcode'   => '[algq_buyer_registration]',
				'eyebrow'     => 'Investor Access • Controlled Deal Distribution',
				'heading'     => 'Register as a Buyer',
				'intro'       => 'Create a buyer profile, provide acquisition criteria, and request access to qualified investment opportunities.',
				'primary_url' => '#algq-module',
				'primary_cta' => 'Register Now',
				'second_url'  => '/buyer-dashboard/',
				'second_cta'  => 'Buyer Dashboard',
			),
			'pipeline' => array(
				'title'       => 'Pipeline CRM',
				'slug'        => 'pipeline',
				'shortcode'   => '[algq_pipeline_crm]',
				'eyebrow'     => 'Acquisition Lifecycle • Internal Operations',
				'heading'     => 'Deal Pipeline CRM',
				'intro'       => 'Manage opportunities from initial intake through underwriting, offer, contract, funding, buyer assignment, and closing.',
				'primary_url' => '#algq-module',
				'primary_cta' => 'Open Pipeline',
				'second_url'  => '/dashboard/',
				'second_cta'  => 'Command Center',
			),
			'buyer_portal' => array(
				'title'       => 'Buyer Portal',
				'slug'        => 'buyer-dashboard',
				'shortcode'   => '[algq_buyer_portal]',
				'eyebrow'     => 'Secure Buyer Access • Deal Packages',
				'heading'     => 'Buyer Portal',
				'intro'       => 'Review permitted deal opportunities, manage NDA status, express interest, and access controlled transaction materials.',
				'primary_url' => '#algq-module',
				'primary_cta' => 'Open Portal',
				'second_url'  => '/buyers-register/',
				'second_cta'  => 'Register',
			),
			'funding' => array(
				'title'       => 'Funding Tracker',
				'slug'        => 'funding-dashboard',
				'shortcode'   => '[algq_funding_tracker]',
				'eyebrow'     => 'Capital Sources • Funding Coordination',
				'heading'     => 'Funding Tracker',
				'intro'       => 'Track lenders, private capital, joint-venture relationships, commitments, terms, and funding status by deal.',
				'primary_url' => '#algq-module',
				'primary_cta' => 'View Funding',
				'second_url'  => '/contact/',
				'second_cta'  => 'Capital Inquiries',
			),
			'documents' => array(
				'title'       => 'Document Library',
				'slug'        => 'documents',
				'shortcode'   => '[algq_document_library]',
				'eyebrow'     => 'Institutional Documentation • Controlled Access',
				'heading'     => 'Document Library',
				'intro'       => 'Centralized access to entity, financing, acquisition, financial-control, risk-management, and property-management documentation.',
				'primary_url' => '#algq-module',
				'primary_cta' => 'Open Library',
				'second_url'  => '/contact/',
				'second_cta'  => 'Request Documents',
			),
			'automation' => array(
				'title'       => 'Automation Engine',
				'slug'        => 'automation-rules',
				'shortcode'   => '[algq_automation_engine]',
				'eyebrow'     => 'Workflow Automation • Event Processing',
				'heading'     => 'Automation Engine',
				'intro'       => 'Coordinate status-based actions, document generation, notifications, tasks, signature workflows, and closing processes.',
				'primary_url' => '#algq-module',
				'primary_cta' => 'View Automations',
				'second_url'  => '/dashboard/',
				'second_cta'  => 'System Health',
			),
			'digital_store' => array(
				'title'       => 'Digital Store',
				'slug'        => 'digital-store',
				'shortcode'   => '[algq_digital_store]',
				'eyebrow'     => 'ARE Digital Products • Real Estate Systems',
				'heading'     => 'Algonquian Digital Store',
				'intro'       => 'Explore real estate templates, forms, calculators, workflow systems, documentation packages, and operational tools.',
				'primary_url' => '#algq-module',
				'primary_cta' => 'Browse Products',
				'second_url'  => '/product-vault/',
				'second_cta'  => 'Product Vault',
			),
			'product_vault' => array(
				'title'       => 'Product Vault',
				'slug'        => 'product-vault',
				'shortcode'   => '[algq_product_vault]',
				'eyebrow'     => 'Protected Downloads • Customer Access',
				'heading'     => 'Product Vault',
				'intro'       => 'Access authorized digital products, purchased resources, templates, calculators, and related documentation.',
				'primary_url' => '#algq-module',
				'primary_cta' => 'Open Vault',
				'second_url'  => '/digital-store/',
				'second_cta'  => 'Return to Store',
			),
			'checkout' => array(
				'title'       => 'Store Checkout',
				'slug'        => 'store-checkout',
				'shortcode'   => '[algq_store_checkout]',
				'eyebrow'     => 'Secure Checkout • Digital Entitlements',
				'heading'     => 'Complete Your Order',
				'intro'       => 'Complete checkout for approved digital products and receive controlled access through the Product Vault.',
				'primary_url' => '#algq-module',
				'primary_cta' => 'Continue Checkout',
				'second_url'  => '/digital-store/',
				'second_cta'  => 'Back to Store',
			),
			'admin_dashboard' => array(
				'title'       => 'Algonquian Admin Command Center',
				'slug'        => 'dashboard',
				'shortcode'   => '[algq_admin_dashboard]',
				'eyebrow'     => 'Executive Oversight • Internal Access',
				'heading'     => 'Admin Command Center',
				'intro'       => 'Monitor acquisition activity, pipeline value, underwriting, offers, buyers, funding, documents, automation, and system health.',
				'primary_url' => '#algq-module',
				'primary_cta' => 'Open Dashboard',
				'second_url'  => '/algonquian-real-estate-platform/',
				'second_cta'  => 'Platform Overview',
			),
		);
	}

	/**
	 * Create or confirm pages without overwriting administrator-edited pages.
	 *
	 * @return array<string,int>
	 */
	public static function create_pages() {
		$page_ids = array();

		foreach ( self::pages() as $key => $page ) {
			$existing = get_page_by_path( $page['slug'], OBJECT, 'page' );
			$content  = self::build_page_content( $page );

			if ( $existing instanceof WP_Post ) {
				$page_ids[ $key ] = (int) $existing->ID;

				// Preserve edited page content. Only repair pages where the required shortcode is missing.
				if ( false === strpos( (string) $existing->post_content, $page['shortcode'] ) ) {
					wp_update_post(
						array(
							'ID'           => (int) $existing->ID,
							'post_content' => $content,
						)
					);
				}
				continue;
			}

			$page_id = wp_insert_post(
				array(
					'post_title'   => sanitize_text_field( $page['title'] ),
					'post_name'    => sanitize_title( $page['slug'] ),
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

	/**
	 * Build a branded WPBakery page using the approved ARE layout system.
	 *
	 * @param array<string,mixed> $page Page definition.
	 * @return string
	 */
	private static function build_page_content( $page ) {
		$hero_image = (int) apply_filters( 'algq_platform_page_hero_image_id', 6422, $page );
		$eyebrow    = esc_html( $page['eyebrow'] );
		$heading    = esc_html( $page['heading'] );
		$intro      = esc_html( $page['intro'] );
		$primary    = rawurlencode( $page['primary_url'] );
		$secondary  = rawurlencode( $page['second_url'] );
		$primary_cta = esc_attr( $page['primary_cta'] );
		$second_cta  = esc_attr( $page['second_cta'] );

		return '[vc_row full_width="stretch_row_content" parallax="content-moving" parallax_image="' . $hero_image . '"]'
			. '[vc_column css=".vc_custom_algonquian_platform_hero{background:linear-gradient(180deg, rgba(15,25,35,0.90) 0%, rgba(15,25,35,0.72) 55%, rgba(15,25,35,0.92) 100%) !important;}"]'
			. '[vc_empty_space height="40px"]'
			. '[vc_column_text]<div style="text-align:center;max-width:1100px;margin:0 auto;"><div style="display:inline-block;padding:10px 18px;border:1px solid rgba(230,238,245,0.20);border-radius:999px;background:rgba(11,58,99,0.35);color:#e6eef5;font-size:13px;letter-spacing:.08em;text-transform:uppercase;">' . $eyebrow . '</div></div>[/vc_column_text]'
			. '[vc_empty_space height="22px"]'
			. '[vc_column_text]<h1 style="color:#e6eef5;font-size:58px;line-height:1.05;font-weight:800;text-align:center;margin:0;">' . $heading . '</h1><p style="color:#e6eef5;opacity:.92;font-size:20px;line-height:1.6;text-align:center;max-width:980px;margin:14px auto 0;">' . $intro . '</p>[/vc_column_text]'
			. '[vc_empty_space height="28px"]'
			. self::metric_row()
			. '[vc_empty_space height="36px"]'
			. '[vc_row_inner][vc_column_inner width="1/2"][la_btn title="' . $primary_cta . '" shape="rounded" color="primary" size="lg" align="center" link="url:' . $primary . '|title:' . rawurlencode( $page['primary_cta'] ) . '"][/vc_column_inner]'
			. '[vc_column_inner width="1/2"][la_btn title="' . $second_cta . '" style="outline" shape="rounded" size="lg" align="center" link="url:' . $secondary . '|title:' . rawurlencode( $page['second_cta'] ) . '"][/vc_column_inner][/vc_row_inner]'
			. '[vc_empty_space height="50px"][/vc_column][/vc_row]'
			. '[vc_row el_id="algq-module" css=".vc_custom_algq_module_section{padding-top:45px !important;padding-bottom:45px !important;background:#f4f6f8 !important;}"][vc_column][vc_column_text]'
			. $page['shortcode']
			. '[/vc_column_text][/vc_column][/vc_row]'
			. '[vc_row][vc_column][vc_empty_space height="35px"][vc_column_text]<div style="max-width:900px;margin:0 auto;text-align:center;"><h2>Disciplined Systems. Clear Workflows. Long-Term Value.</h2><p>Algonquian Real Estate combines real estate operations, institutional documentation, and proprietary technology infrastructure to support consistent decision-making and responsible growth.</p></div>[/vc_column_text][vc_empty_space height="35px"][/vc_column][/vc_row]';
	}

	/**
	 * Shared four-card platform metric row.
	 *
	 * @return string
	 */
	private static function metric_row() {
		$cards = array(
			array( 'code' => 'CT', 'label' => 'Market', 'value' => 'Connecticut-Based Operations', 'accent' => '#3ed4c9' ),
			array( 'code' => 'DI', 'label' => 'Acquisition', 'value' => 'Seller Intake & Deal Flow', 'accent' => '#00a8ff' ),
			array( 'code' => 'UW', 'label' => 'Underwriting', 'value' => 'Cash-Flow-Driven Analysis', 'accent' => '#d1a54a' ),
			array( 'code' => 'LT', 'label' => 'Strategy', 'value' => 'Long-Term Value Creation', 'accent' => '#e6eef5' ),
		);

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
}
