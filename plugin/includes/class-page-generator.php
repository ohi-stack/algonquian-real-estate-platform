<?php
/**
 * Idempotent platform page generation.
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
				'content' => self::page_content( 'Platform Infrastructure', 'Algonquian Real Estate Platform', 'Shared security, registry, mail, audit, private files, health monitoring, page generation, and integration contracts for the Algonquian Real Estate plugin ecosystem.', '[algq_platform_overview]' ),
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
				'content' => self::page_content( 'Shared Platform Services', 'Algonquian Real Estate Platform Plugin', 'The Platform Plugin provides registry, security, capabilities, mail, audit, private file storage, health monitoring, page generation, and integration contracts for companion plugins.', '[algq_platform_overview]' ),
			),
			'platform_start' => array(
				'title'   => 'Getting Started With the Algonquian Real Estate Platform',
				'slug'    => 'plugin/platform/start',
				'parent'  => 'platform_plugin',
				'content' => self::page_content( 'Platform Administration', 'Getting Started', 'Confirm the Platform Plugin is active before enabling companion plugins. Review capabilities, registry status, mail configuration, private storage, generated pages, and the platform health report.', '[algq_platform_overview]' ),
			),
			'platform_docs' => array(
				'title'   => 'Algonquian Real Estate Platform Documentation',
				'slug'    => 'plugin/platform/docs',
				'parent'  => 'platform_plugin',
				'content' => self::page_content( 'User and Administrator Guide Library', 'Platform Documentation', 'Use the platform documentation for installation, capabilities, plugin registration, audit events, email delivery, private file handling, page generation, health checks, security, and troubleshooting.', '[algq_platform_overview]' ),
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
