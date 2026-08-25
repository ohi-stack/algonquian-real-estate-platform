<?php
/**
 * Shared Algonquian Real Estate admin branding and motion layer.
 *
 * @package AlgonquianRealEstatePlatform
 */

defined( 'ABSPATH' ) || exit;

final class ALGQ_Admin_Branding {
	public static function init(): void {
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue' ), 100 );
		add_filter( 'admin_body_class', array( __CLASS__, 'body_class' ) );
	}

	public static function enqueue( string $hook_suffix ): void {
		if ( ! self::is_algq_screen( $hook_suffix ) ) {
			return;
		}

		$css = ALGQ_PLATFORM_DIR . 'assets/css/admin-branding.css';
		$js  = ALGQ_PLATFORM_DIR . 'assets/js/admin-branding.js';

		if ( file_exists( $css ) ) {
			wp_enqueue_style( 'algq-admin-branding', ALGQ_PLATFORM_URL . 'assets/css/admin-branding.css', array(), (string) filemtime( $css ) );
		}

		if ( file_exists( $js ) ) {
			wp_enqueue_script( 'algq-admin-branding', ALGQ_PLATFORM_URL . 'assets/js/admin-branding.js', array(), (string) filemtime( $js ), true );
		}
	}

	public static function body_class( string $classes ): string {
		return self::is_algq_screen( '' ) ? trim( $classes . ' algq-admin-branded' ) : $classes;
	}

	private static function is_algq_screen( string $hook_suffix ): bool {
		$page = isset( $_GET['page'] ) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : '';
		$post_type = isset( $_GET['post_type'] ) ? sanitize_key( wp_unslash( $_GET['post_type'] ) ) : '';
		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
		$screen_id = $screen && isset( $screen->id ) ? (string) $screen->id : '';

		$haystack = strtolower( implode( ' ', array( $page, $post_type, $screen_id, $hook_suffix ) ) );
		return str_contains( $haystack, 'algq' ) || str_contains( $haystack, 'algonquian' );
	}
}
