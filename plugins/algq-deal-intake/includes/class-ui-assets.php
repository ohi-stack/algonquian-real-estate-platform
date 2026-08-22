<?php
/** Ensure the shared Deal Intake UI stylesheet loads for every page-facing shortcode. */
defined( 'ABSPATH' ) || exit;

final class ALGQ_Deal_Intake_UI_Assets {
	public static function register_hooks(): void {
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue' ), 20 );
	}

	public static function enqueue(): void {
		if ( ! is_singular() ) {
			return;
		}
		$post = get_post();
		if ( ! $post instanceof WP_Post ) {
			return;
		}
		$content = (string) $post->post_content;
		$tags = array(
			'algq_deal_intake_form',
			'algq_property_submission',
			'deal_intake_form_public',
			'deal_intake_form_internal',
			'deal_quick_capture',
			'algq_homeowner_options',
			'algq_seller_portal',
			'algq_deal_intake_about',
			'algq_seller_intake_entry',
		);
		foreach ( $tags as $tag ) {
			if ( has_shortcode( $content, $tag ) ) {
				wp_enqueue_style( 'algq-deal-intake', ALGQ_DI_URL . 'assets/css/front.css', array(), ALGQ_DI_VERSION );
				return;
			}
		}
	}
}
