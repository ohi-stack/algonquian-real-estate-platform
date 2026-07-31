<?php
/**
 * Public and buyer shortcodes.
 *
 * @package Algonquian_Deal_Marketplace
 */

defined( 'ABSPATH' ) || exit;

final class ALGQ_DM_Shortcodes {
	public static function init(): void {
		add_shortcode( 'algq_deal_marketplace', array( __CLASS__, 'marketplace' ) );
		add_shortcode( 'algq_buyer_marketplace_dashboard', array( __CLASS__, 'dashboard' ) );
		add_shortcode( 'algq_buyer_nda_gate', array( __CLASS__, 'nda_gate' ) );
		add_shortcode( 'algq_buyer_offer_form', array( __CLASS__, 'offer_form' ) );
		add_shortcode( 'algq_deal_marketplace_plugin_card', array( __CLASS__, 'plugin_card' ) );
	}

	private static function assets(): void {
		wp_enqueue_style( 'algq-dm-public', ALGQ_DM_URL . 'assets/css/public.css', array(), ALGQ_DM_VERSION );
		wp_enqueue_script( 'algq-dm-public', ALGQ_DM_URL . 'assets/js/public.js', array(), ALGQ_DM_VERSION, true );
	}

	private static function status_notice(): string {
		$status = isset( $_GET['algq_dm_status'] ) ? sanitize_key( wp_unslash( $_GET['algq_dm_status'] ) ) : '';
		$messages = array(
			'nda_accepted'                  => __( 'NDA acceptance recorded.', 'algq-deal-marketplace' ),
			'nda_acknowledgment_required'   => __( 'You must affirm the NDA acknowledgment.', 'algq-deal-marketplace' ),
			'nda_error'                     => __( 'The NDA acceptance could not be recorded.', 'algq-deal-marketplace' ),
			'access_denied'                 => __( 'You are not authorized for that deal.', 'algq-deal-marketplace' ),
			'offer_submitted'               => __( 'Your offer was submitted for review.', 'algq-deal-marketplace' ),
			'offer_rate_limited'            => __( 'Please wait before submitting another offer.', 'algq-deal-marketplace' ),
			'algq_dm_nda_required'          => __( 'Accept the required NDA before submitting an offer.', 'algq-deal-marketplace' ),
			'algq_dm_invalid_amount'        => __( 'Enter a valid offer amount.', 'algq-deal-marketplace' ),
			'algq_dm_access_denied'         => __( 'You are not authorized to submit an offer for that deal.', 'algq-deal-marketplace' ),
			'algq_dm_offer_storage_failed'  => __( 'The offer could not be stored. Contact support.', 'algq-deal-marketplace' ),
			'offer_terms_too_long'          => __( 'Offer terms exceed the permitted length.', 'algq-deal-marketplace' ),
		);
		if ( '' === $status || ! isset( $messages[ $status ] ) ) {
			return '';
		}
		$is_error = ! in_array( $status, array( 'nda_accepted', 'offer_submitted' ), true );
		return '<div class="algq-dm__notice ' . ( $is_error ? 'is-error' : 'is-success' ) . '" role="status">' . esc_html( $messages[ $status ] ) . '</div>';
	}

	/** @param array<string,mixed> $atts */
	public static function marketplace( array $atts = array() ): string {
		self::assets();
		$atts = shortcode_atts( array( 'limit' => 24 ), $atts, 'algq_deal_marketplace' );
		$query = new WP_Query(
			array(
				'post_type'      => 'algq_market_deal',
				'post_status'    => 'publish',
				'posts_per_page' => min( 100, max( 1, absint( $atts['limit'] ) ) ),
				'orderby'        => array( 'menu_order' => 'ASC', 'date' => 'DESC' ),
			)
		);

		ob_start();
		echo '<section class="algq-dm" aria-labelledby="algq-dm-title">' . wp_kses_post( self::status_notice() );
		echo '<header class="algq-dm__header"><span class="algq-dm__eyebrow">' . esc_html__( 'Algonquian Real Estate', 'algq-deal-marketplace' ) . '</span><h2 id="algq-dm-title">' . esc_html__( 'Deal Marketplace', 'algq-deal-marketplace' ) . '</h2><p>' . esc_html__( 'Curated opportunities are distributed through controlled buyer access, confidentiality acknowledgment, and record-level authorization.', 'algq-deal-marketplace' ) . '</p></header>';
		if ( ! $query->have_posts() ) {
			echo '<div class="algq-dm__empty"><h3>' . esc_html__( 'No active marketplace opportunities', 'algq-deal-marketplace' ) . '</h3><p>' . esc_html__( 'Qualified buyers may register or update their acquisition criteria for future opportunities.', 'algq-deal-marketplace' ) . '</p></div></section>';
			return (string) ob_get_clean();
		}

		echo '<div class="algq-dm__grid">';
		while ( $query->have_posts() ) {
			$query->the_post();
			$deal_id = get_the_ID();
			$can_view = ALGQ_DM_Access::can_view_deal( $deal_id );
			$city = (string) ALGQ_DM_Marketplace::meta( $deal_id, 'city', '' );
			$state = (string) ALGQ_DM_Marketplace::meta( $deal_id, 'state', 'CT' );
			echo '<article class="algq-dm__card">';
			if ( has_post_thumbnail() ) {
				echo get_the_post_thumbnail( $deal_id, 'large', array( 'class' => 'algq-dm__image', 'loading' => 'lazy' ) );
			}
			echo '<div class="algq-dm__card-body"><span class="algq-dm__location">' . esc_html( trim( $city . ', ' . $state, ', ' ) ) . '</span><h3>' . esc_html( get_the_title() ) . '</h3>';
			if ( $can_view ) {
				echo '<p class="algq-dm__summary">' . wp_kses_post( wp_trim_words( get_the_excerpt() ?: get_the_content(), 28 ) ) . '</p>';
				echo '<dl class="algq-dm__metrics"><div><dt>' . esc_html__( 'Ask', 'algq-deal-marketplace' ) . '</dt><dd>' . esc_html( self::money( ALGQ_DM_Marketplace::meta( $deal_id, 'price', 0 ) ) ) . '</dd></div><div><dt>' . esc_html__( 'ARV', 'algq-deal-marketplace' ) . '</dt><dd>' . esc_html( self::money( ALGQ_DM_Marketplace::meta( $deal_id, 'arv', 0 ) ) ) . '</dd></div></dl>';
				if ( 'yes' === get_option( 'algq_dm_nda_required', 'yes' ) && ! ALGQ_DM_NDA::accepted( get_current_user_id(), $deal_id ) ) {
					echo '<a class="algq-dm__button" href="' . esc_url( self::page_url( 'algq_dm_nda_page_id', array( 'deal_id' => $deal_id ) ) ) . '">' . esc_html__( 'Accept NDA', 'algq-deal-marketplace' ) . '</a>';
				} else {
					if ( ALGQ_DM_Access::can_download( $deal_id ) ) {
						echo '<a class="algq-dm__button" href="' . esc_url( ALGQ_DM_Access::download_url( $deal_id ) ) . '">' . esc_html__( 'Download Deal Package', 'algq-deal-marketplace' ) . '</a>';
					}
					echo '<a class="algq-dm__button is-secondary" href="' . esc_url( self::page_url( 'algq_dm_offer_page_id', array( 'deal_id' => $deal_id ) ) ) . '">' . esc_html__( 'Submit Offer', 'algq-deal-marketplace' ) . '</a>';
				}
			} elseif ( ! is_user_logged_in() ) {
				echo '<p>' . esc_html__( 'Sign in through the Buyer Portal to request controlled access.', 'algq-deal-marketplace' ) . '</p>';
			} else {
				echo '<p>' . esc_html__( 'This opportunity requires an approved entitlement or explicit access grant.', 'algq-deal-marketplace' ) . '</p>';
			}
			echo '</div></article>';
		}
		wp_reset_postdata();
		echo '</div></section>';
		return (string) ob_get_clean();
	}

	public static function dashboard(): string {
		self::assets();
		if ( ! is_user_logged_in() ) {
			return '<div class="algq-dm__notice is-error">' . esc_html__( 'Please sign in through the Buyer Portal.', 'algq-deal-marketplace' ) . '</div>';
		}
		if ( ! ALGQ_DM_Access::buyer_has_base_access() ) {
			return '<div class="algq-dm__notice is-error">' . esc_html__( 'Your account does not have Marketplace access.', 'algq-deal-marketplace' ) . '</div>';
		}
		$offers = ALGQ_DM_Offers::for_user( get_current_user_id() );
		ob_start();
		echo '<section class="algq-dm algq-dm--dashboard"><header class="algq-dm__header"><h2>' . esc_html__( 'Buyer Marketplace Dashboard', 'algq-deal-marketplace' ) . '</h2><p>' . esc_html__( 'Review authorized opportunities, confidentiality status, packages, and submitted offers.', 'algq-deal-marketplace' ) . '</p></header>';
		echo '<div class="algq-dm__panel"><h3>' . esc_html__( 'Your submitted offers', 'algq-deal-marketplace' ) . '</h3>';
		if ( empty( $offers ) ) {
			echo '<p>' . esc_html__( 'No offers have been submitted from this account.', 'algq-deal-marketplace' ) . '</p>';
		} else {
			echo '<div class="algq-dm__table-wrap"><table class="algq-dm__table"><thead><tr><th>' . esc_html__( 'Deal', 'algq-deal-marketplace' ) . '</th><th>' . esc_html__( 'Amount', 'algq-deal-marketplace' ) . '</th><th>' . esc_html__( 'Status', 'algq-deal-marketplace' ) . '</th><th>' . esc_html__( 'Submitted', 'algq-deal-marketplace' ) . '</th></tr></thead><tbody>';
			foreach ( $offers as $offer ) {
				echo '<tr><td>' . esc_html( get_the_title( (int) $offer->deal_id ) ) . '</td><td>' . esc_html( self::money( $offer->offer_amount ) ) . '</td><td><span class="algq-dm__status">' . esc_html( ucwords( str_replace( '_', ' ', (string) $offer->status ) ) ) . '</span></td><td>' . esc_html( get_date_from_gmt( (string) $offer->created_at, get_option( 'date_format' ) ) ) . '</td></tr>';
			}
			echo '</tbody></table></div>';
		}
		echo '</div>' . do_shortcode( '[algq_deal_marketplace]' ) . '</section>';
		return (string) ob_get_clean();
	}

	public static function nda_gate(): string {
		self::assets();
		if ( ! is_user_logged_in() ) {
			return '<div class="algq-dm__notice is-error">' . esc_html__( 'Please sign in before accepting the Marketplace NDA.', 'algq-deal-marketplace' ) . '</div>';
		}
		$deal_id = isset( $_GET['deal_id'] ) ? absint( $_GET['deal_id'] ) : 0;
		if ( $deal_id > 0 && ! ALGQ_DM_Access::can_view_deal( $deal_id ) ) {
			return '<div class="algq-dm__notice is-error">' . esc_html__( 'You are not authorized for this deal.', 'algq-deal-marketplace' ) . '</div>';
		}
		$version = ALGQ_DM_NDA::required_version( $deal_id );
		if ( ALGQ_DM_NDA::accepted( get_current_user_id(), $deal_id ) ) {
			return '<div class="algq-dm__notice is-success">' . esc_html( sprintf( __( 'NDA version %s is already accepted for this access scope.', 'algq-deal-marketplace' ), $version ) ) . '</div>';
		}
		$nda_text = (string) apply_filters( 'algq_dm_nda_text', __( 'Confidential deal materials are provided solely for evaluation by the authorized buyer. Materials may not be distributed, copied, published, or used to contact owners, occupants, brokers, lenders, vendors, or counterparties outside the process authorized by Algonquian Real Estate. This acknowledgment does not replace a separately executed agreement where one is required.', 'algq-deal-marketplace' ), $deal_id, $version );
		ob_start();
		echo '<section class="algq-dm"><div class="algq-dm__panel"><h2>' . esc_html__( 'Marketplace Confidentiality Acknowledgment', 'algq-deal-marketplace' ) . '</h2><p><strong>' . esc_html( sprintf( __( 'Version: %s', 'algq-deal-marketplace' ), $version ) ) . '</strong></p><div class="algq-dm__nda-text">' . wpautop( wp_kses_post( $nda_text ) ) . '</div>';
		echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '"><input type="hidden" name="action" value="algq_dm_accept_nda"><input type="hidden" name="deal_id" value="' . esc_attr( (string) $deal_id ) . '"><input type="hidden" name="return_url" value="' . esc_url( self::current_url() ) . '">';
		wp_nonce_field( 'algq_dm_accept_nda_' . $deal_id );
		echo '<label class="algq-dm__checkbox"><input type="checkbox" name="nda_acknowledgment" value="1" required> <span>' . esc_html__( 'I have read and agree to the confidentiality acknowledgment identified above.', 'algq-deal-marketplace' ) . '</span></label><button class="algq-dm__button" type="submit">' . esc_html__( 'Record NDA Acceptance', 'algq-deal-marketplace' ) . '</button></form></div></section>';
		return (string) ob_get_clean();
	}

	/** @param array<string,mixed> $atts */
	public static function offer_form( array $atts = array() ): string {
		self::assets();
		$atts = shortcode_atts( array( 'deal_id' => 0 ), $atts, 'algq_buyer_offer_form' );
		$deal_id = absint( $atts['deal_id'] ) ?: ( isset( $_GET['deal_id'] ) ? absint( $_GET['deal_id'] ) : 0 );
		if ( $deal_id <= 0 || ! ALGQ_DM_Access::can_view_deal( $deal_id ) ) {
			return '<div class="algq-dm__notice is-error">' . esc_html__( 'Choose an authorized marketplace deal before submitting an offer.', 'algq-deal-marketplace' ) . '</div>';
		}
		if ( 'yes' === get_option( 'algq_dm_nda_required', 'yes' ) && ! ALGQ_DM_NDA::accepted( get_current_user_id(), $deal_id ) ) {
			return '<div class="algq-dm__notice is-error">' . esc_html__( 'Accept the required NDA before submitting an offer.', 'algq-deal-marketplace' ) . '</div>';
		}
		ob_start();
		echo '<section class="algq-dm"><form class="algq-dm__form" method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '"><input type="hidden" name="action" value="algq_dm_submit_offer"><input type="hidden" name="deal_id" value="' . esc_attr( (string) $deal_id ) . '"><input type="hidden" name="return_url" value="' . esc_url( self::current_url() ) . '">';
		wp_nonce_field( 'algq_dm_submit_offer_' . $deal_id );
		echo '<h2>' . esc_html( sprintf( __( 'Submit an Offer: %s', 'algq-deal-marketplace' ), get_the_title( $deal_id ) ) ) . '</h2>';
		echo '<label>' . esc_html__( 'Offer amount', 'algq-deal-marketplace' ) . '<input type="number" name="offer_amount" min="1" step="0.01" required></label>';
		echo '<label>' . esc_html__( 'Earnest money', 'algq-deal-marketplace' ) . '<input type="number" name="earnest_money" min="0" step="0.01"></label>';
		echo '<label>' . esc_html__( 'Financing type', 'algq-deal-marketplace' ) . '<select name="financing_type"><option value="cash">' . esc_html__( 'Cash', 'algq-deal-marketplace' ) . '</option><option value="conventional">' . esc_html__( 'Conventional financing', 'algq-deal-marketplace' ) . '</option><option value="private">' . esc_html__( 'Private financing', 'algq-deal-marketplace' ) . '</option><option value="seller_financing">' . esc_html__( 'Seller financing proposal', 'algq-deal-marketplace' ) . '</option><option value="joint_venture">' . esc_html__( 'Joint venture proposal', 'algq-deal-marketplace' ) . '</option><option value="other">' . esc_html__( 'Other', 'algq-deal-marketplace' ) . '</option></select></label>';
		echo '<label>' . esc_html__( 'Material terms and contingencies', 'algq-deal-marketplace' ) . '<textarea name="terms" rows="8" maxlength="10000"></textarea></label>';
		echo '<p class="algq-dm__disclaimer">' . esc_html__( 'Submitting this form records an expression of proposed terms for review. It does not create a binding purchase agreement or acceptance.', 'algq-deal-marketplace' ) . '</p><button class="algq-dm__button" type="submit" data-algq-confirm-offer="1">' . esc_html__( 'Submit Offer for Review', 'algq-deal-marketplace' ) . '</button></form></section>';
		return (string) ob_get_clean();
	}

	public static function plugin_card(): string {
		self::assets();
		return '<div class="algq-dm algq-dm__plugin-card"><span class="algq-dm__eyebrow">' . esc_html__( 'Algonquian Real Estate Technology Division', 'algq-deal-marketplace' ) . '</span><h2>' . esc_html__( 'Algonquian Deal Marketplace', 'algq-deal-marketplace' ) . '</h2><p><strong>' . esc_html__( 'Version 2.0.0 Production', 'algq-deal-marketplace' ) . '</strong></p><p>' . esc_html__( 'Controlled buyer distribution with additive buyer capabilities, versioned NDA evidence, deal-level authorization, secure package delivery, offer workflows, audit events, and shared Stripe-entitlement hooks.', 'algq-deal-marketplace' ) . '</p></div>';
	}

	private static function page_url( string $option, array $args = array() ): string {
		$page_id = absint( get_option( $option ) );
		$url = $page_id > 0 ? get_permalink( $page_id ) : home_url( '/marketplace/' );
		return add_query_arg( $args, $url );
	}

	private static function current_url(): string {
		$scheme = is_ssl() ? 'https' : 'http';
		$host = isset( $_SERVER['HTTP_HOST'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_HOST'] ) ) : wp_parse_url( home_url(), PHP_URL_HOST );
		$uri = isset( $_SERVER['REQUEST_URI'] ) ? esc_url_raw( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '/';
		return esc_url_raw( $scheme . '://' . $host . $uri );
	}

	private static function money( mixed $amount ): string {
		return '$' . number_format_i18n( (float) $amount, 0 );
	}
}
