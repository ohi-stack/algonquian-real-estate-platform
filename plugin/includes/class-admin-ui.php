<?php
/**
 * Shared Algonquian Real Estate WordPress admin UI controller.
 *
 * Companion plugins remain responsible for their business records and workflows.
 * This class only supplies the common ARE presentation layer for their wp-admin
 * screens.
 *
 * @package AlgonquianRealEstatePlatform
 */

defined( 'ABSPATH' ) || exit;

final class ALGQ_Admin_UI {
	private const BODY_CLASS = 'are-admin-ui';

	public static function init(): void {
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_assets' ), 99 );
		add_filter( 'admin_body_class', array( __CLASS__, 'add_body_class' ) );
	}

	public static function enqueue_assets( string $hook_suffix ): void {
		if ( ! self::is_are_screen( $hook_suffix ) ) {
			return;
		}

		$css_path = ALGQ_PLATFORM_DIR . 'assets/css/admin-ui.css';
		$js_path  = ALGQ_PLATFORM_DIR . 'assets/js/admin-ui.js';

		if ( file_exists( $css_path ) ) {
			wp_enqueue_style(
				'algq-are-admin-ui',
				ALGQ_PLATFORM_URL . 'assets/css/admin-ui.css',
				array(),
				(string) filemtime( $css_path )
			);
		}

		if ( file_exists( $js_path ) ) {
			wp_enqueue_script(
				'algq-are-admin-ui',
				ALGQ_PLATFORM_URL . 'assets/js/admin-ui.js',
				array(),
				(string) filemtime( $js_path ),
				true
			);
		}
	}

	public static function add_body_class( string $classes ): string {
		if ( ! self::is_are_screen( '' ) ) {
			return $classes;
		}

		return trim( $classes . ' ' . self::BODY_CLASS );
	}

	private static function is_are_screen( string $hook_suffix ): bool {
		$page      = isset( $_GET['page'] ) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : '';
		$post_type = isset( $_GET['post_type'] ) ? sanitize_key( wp_unslash( $_GET['post_type'] ) ) : '';
		$taxonomy  = isset( $_GET['taxonomy'] ) ? sanitize_key( wp_unslash( $_GET['taxonomy'] ) ) : '';
		$screen    = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
		$screen_id = $screen && isset( $screen->id ) ? (string) $screen->id : '';
		$base      = $screen && isset( $screen->base ) ? (string) $screen->base : '';
		$parent    = $screen && isset( $screen->parent_base ) ? (string) $screen->parent_base : '';

		$haystack = strtolower(
			implode(
				' ',
				array_filter( array( $page, $post_type, $taxonomy, $screen_id, $base, $parent, $hook_suffix ) )
			)
		);

		$is_are_screen = str_contains( $haystack, 'algq' )
			|| str_contains( $haystack, 'algonquian' )
			|| str_contains( $haystack, 'are-' );

		/**
		 * Allows companion plugins to opt an additional wp-admin screen into or
		 * out of the shared ARE UI without coupling the Platform to plugin code.
		 *
		 * @param bool                 $is_are_screen Current decision.
		 * @param array<string,string> $context       Sanitized screen context.
		 */
		return (bool) apply_filters(
			'algq_admin_ui_is_are_screen',
			$is_are_screen,
			array(
				'page'        => $page,
				'post_type'   => $post_type,
				'taxonomy'    => $taxonomy,
				'screen_id'   => $screen_id,
				'base'        => $base,
				'parent_base' => $parent,
				'hook_suffix' => $hook_suffix,
			)
		);
	}
}
