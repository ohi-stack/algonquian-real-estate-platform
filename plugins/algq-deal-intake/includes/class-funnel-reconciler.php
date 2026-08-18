<?php
/**
 * Production seller-funnel reconciliation.
 *
 * Repairs the two public money-making routes without overwriting administrator-authored
 * page content. Known placeholders and the retired seller-intake shortcode are replaced
 * in place; otherwise the production Deal Intake form is appended once.
 *
 * @package Algonquian_Deal_Intake
 */

defined( 'ABSPATH' ) || exit;

final class ALGQ_Deal_Intake_Funnel_Reconciler {
	private const RECONCILIATION_VERSION = '2026-08-18.1';
	private const OPTION_VERSION = 'algq_di_funnel_reconciliation_version';
	private const FORM_SHORTCODE = '[algq_deal_intake_form]';

	public static function register_hooks(): void {
		add_action( 'init', array( __CLASS__, 'register_compatibility_shortcode' ), 15 );
		add_action( 'init', array( __CLASS__, 'maybe_reconcile' ), 50 );
		add_action( 'template_redirect', array( __CLASS__, 'redirect_legacy_submit_property' ) );
	}

	/**
	 * Keep the retired shortcode rendering while deployed page content is reconciled.
	 */
	public static function register_compatibility_shortcode(): void {
		if ( class_exists( 'ALGQ_Deal_Intake_Pages' ) ) {
			add_shortcode( 'algq_seller_intake_entry', array( 'ALGQ_Deal_Intake_Pages', 'public_form' ) );
		}
	}

	/**
	 * One-time, retry-safe production reconciliation.
	 */
	public static function maybe_reconcile(): void {
		if ( self::RECONCILIATION_VERSION === (string) get_option( self::OPTION_VERSION, '' ) ) {
			return;
		}

		$submit_ok = self::reconcile_page(
			'submit-a-property',
			__( 'Submit a Property', 'algq-deal-intake' ),
			'algq_di_submit_property_page_id'
		);
		$sell_ok = self::reconcile_page(
			'sell-your-property',
			__( 'Sell Your Property', 'algq-deal-intake' ),
			'algq_di_sell_property_page_id'
		);

		if ( $submit_ok && $sell_ok ) {
			update_option( self::OPTION_VERSION, self::RECONCILIATION_VERSION );
		}
	}

	private static function reconcile_page( string $slug, string $title, string $option_name ): bool {
		$page = get_page_by_path( $slug, OBJECT, 'page' );

		if ( ! $page instanceof WP_Post ) {
			$page_id = wp_insert_post(
				array(
					'post_title' => $title,
					'post_name' => $slug,
					'post_type' => 'page',
					'post_status' => 'publish',
					'post_content' => self::form_block(),
				),
				true
			);

			if ( is_wp_error( $page_id ) ) {
				return false;
			}

			$page = get_post( (int) $page_id );
		}

		if ( ! $page instanceof WP_Post ) {
			return false;
		}

		$content = (string) $page->post_content;
		$has_production_form = has_shortcode( $content, 'algq_deal_intake_form' )
			|| has_shortcode( $content, 'algq_property_submission' )
			|| has_shortcode( $content, 'deal_intake_form_public' );

		if ( ! $has_production_form ) {
			$placeholders = array(
				'[YOUR_FORM_PLUGIN_SHORTCODE_HERE]',
				'[FORM_PLUGIN_SHORTCODE]',
				'FORM_PLUGIN_SHORTCODE',
				'[algq_seller_intake_entry]',
			);
			$repaired = str_replace( $placeholders, self::FORM_SHORTCODE, $content );

			if ( $repaired === $content ) {
				$repaired = rtrim( $content ) . "\n\n" . self::form_block();
			}

			$result = wp_update_post(
				array(
					'ID' => $page->ID,
					'post_content' => $repaired,
				),
				true
			);

			if ( is_wp_error( $result ) ) {
				return false;
			}
		}

		update_option( $option_name, (int) $page->ID );
		update_post_meta( (int) $page->ID, '_algq_funnel_reconciled', self::RECONCILIATION_VERSION );

		return true;
	}

	private static function form_block(): string {
		return '[vc_row][vc_column][vc_column_text]' . self::FORM_SHORTCODE . '[/vc_column_text][/vc_column][/vc_row]';
	}

	/**
	 * Consolidate the older generated route into the public conversion route used by the site.
	 */
	public static function redirect_legacy_submit_property(): void {
		if ( is_admin() || ! is_page( 'submit-property' ) ) {
			return;
		}

		$target_id = absint( get_option( 'algq_di_submit_property_page_id' ) );
		$target_url = $target_id ? get_permalink( $target_id ) : '';

		if ( $target_url && get_queried_object_id() !== $target_id ) {
			wp_safe_redirect( $target_url, 301, 'Algonquian Deal Intake' );
			exit;
		}
	}
}
