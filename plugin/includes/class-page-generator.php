<?php
/**
 * Automatic page generation for Algonquian platform shortcodes.
 *
 * @package AlgonquianRealEstatePlatform
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Creates required pages and inserts the correct shortcodes.
 */
final class ALGQ_Platform_Page_Generator {

	/**
	 * Required platform pages.
	 *
	 * @return array<string,array<string,string>>
	 */
	public static function pages() {
		return array(
			'seller_intake' => array(
				'title'     => 'Sell Your Property',
				'slug'      => 'sell-your-property',
				'shortcode' => '[algq_seller_intake]',
			),
			'mao_calculator' => array(
				'title'     => 'MAO Calculator',
				'slug'      => 'mao-calculator',
				'shortcode' => '[algq_mao_calculator]',
			),
			'buyer_registration' => array(
				'title'     => 'Buyer Registration',
				'slug'      => 'buyers-register',
				'shortcode' => '[algq_buyer_registration]',
			),
			'admin_dashboard' => array(
				'title'     => 'Algonquian Admin Dashboard',
				'slug'      => 'dashboard',
				'shortcode' => '[algq_admin_dashboard]',
			),
		);
	}

	/**
	 * Create or confirm pages.
	 *
	 * @return array<string,int>
	 */
	public static function create_pages() {
		$page_ids = array();

		foreach ( self::pages() as $key => $page ) {
			$existing = get_page_by_path( $page['slug'], OBJECT, 'page' );
			$content  = self::wrap_shortcode_for_wpbakery( $page['shortcode'] );

			if ( $existing instanceof WP_Post ) {
				$page_ids[ $key ] = (int) $existing->ID;

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
			}
		}

		update_option( 'algq_platform_generated_pages', $page_ids );

		return $page_ids;
	}

	/**
	 * Wrap shortcode using valid WPBakery shortcode syntax.
	 *
	 * @param string $shortcode Shortcode string.
	 * @return string
	 */
	private static function wrap_shortcode_for_wpbakery( $shortcode ) {
		return "[vc_column_text]\n" . $shortcode . "\n[/vc_column_text]";
	}
}
