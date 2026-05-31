<?php
/**
 * Automatic page generation service for Algonquian Real Estate plugins.
 *
 * @package AlgonquianRealEstate
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Creates or updates WordPress pages that host plugin shortcodes.
 */
class ALGQ_Page_Generator {

	/**
	 * Option key storing generated page IDs.
	 *
	 * @var string
	 */
	const OPTION_KEY = 'algq_generated_pages';

	/**
	 * Create missing pages and ensure each page contains its required shortcode.
	 *
	 * @param array $pages Page definitions.
	 * @return array<string,int> Map of page key to page ID.
	 */
	public static function ensure_pages( array $pages ) {
		$stored = get_option( self::OPTION_KEY, array() );

		if ( ! is_array( $stored ) ) {
			$stored = array();
		}

		foreach ( $pages as $key => $page ) {
			$key       = sanitize_key( $key );
			$title     = isset( $page['title'] ) ? sanitize_text_field( $page['title'] ) : '';
			$slug      = isset( $page['slug'] ) ? sanitize_title( $page['slug'] ) : sanitize_title( $title );
			$shortcode = isset( $page['shortcode'] ) ? trim( wp_kses_post( $page['shortcode'] ) ) : '';
			$status    = isset( $page['status'] ) ? sanitize_key( $page['status'] ) : 'publish';

			if ( '' === $title || '' === $slug || '' === $shortcode ) {
				continue;
			}

			$page_id = self::find_existing_page_id( $stored, $key, $slug );

			$content = self::build_page_content( $shortcode );

			if ( $page_id ) {
				$current = get_post( $page_id );

				if ( $current && false === strpos( (string) $current->post_content, $shortcode ) ) {
					wp_update_post(
						array(
							'ID'           => $page_id,
							'post_content' => $content,
						)
					);
				}
			} else {
				$page_id = wp_insert_post(
					array(
						'post_title'   => $title,
						'post_name'    => $slug,
						'post_content' => $content,
						'post_status'  => $status,
						'post_type'    => 'page',
					)
				);
			}

			if ( $page_id && ! is_wp_error( $page_id ) ) {
				$stored[ $key ] = absint( $page_id );
			}
		}

		update_option( self::OPTION_KEY, $stored, false );

		return $stored;
	}

	/**
	 * Locate existing page by stored ID or slug.
	 *
	 * @param array  $stored Stored page IDs.
	 * @param string $key Page key.
	 * @param string $slug Page slug.
	 * @return int
	 */
	private static function find_existing_page_id( array $stored, $key, $slug ) {
		if ( isset( $stored[ $key ] ) ) {
			$page_id = absint( $stored[ $key ] );
			if ( $page_id && 'page' === get_post_type( $page_id ) ) {
				return $page_id;
			}
		}

		$existing = get_page_by_path( $slug, OBJECT, 'page' );

		return $existing ? absint( $existing->ID ) : 0;
	}

	/**
	 * Build WPBakery-safe page content.
	 *
	 * @param string $shortcode Shortcode string.
	 * @return string
	 */
	private static function build_page_content( $shortcode ) {
		return "[vc_row][vc_column][vc_column_text]\n" . $shortcode . "\n[/vc_column_text][/vc_column][/vc_row]";
	}
}
