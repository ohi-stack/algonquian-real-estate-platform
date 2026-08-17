<?php
/**
 * Marketplace deal content type and metadata.
 *
 * @package Algonquian_Deal_Marketplace
 */

defined( 'ABSPATH' ) || exit;

final class ALGQ_DM_Marketplace {
	private const META_PREFIX = '_algq_dm_';

	public static function init(): void {
		add_action( 'init', array( __CLASS__, 'register_post_type' ) );
		add_action( 'add_meta_boxes_algq_market_deal', array( __CLASS__, 'add_meta_boxes' ) );
		add_action( 'save_post_algq_market_deal', array( __CLASS__, 'save' ), 10, 2 );
	}

	public static function register_post_type(): void {
		register_post_type(
			'algq_market_deal',
			array(
				'labels' => array(
					'name'          => __( 'Marketplace Deals', 'algq-deal-marketplace' ),
					'singular_name' => __( 'Marketplace Deal', 'algq-deal-marketplace' ),
					'add_new_item'  => __( 'Add Marketplace Deal', 'algq-deal-marketplace' ),
					'edit_item'     => __( 'Edit Marketplace Deal', 'algq-deal-marketplace' ),
				),
				'public'              => false,
				'show_ui'             => true,
				'show_in_menu'        => 'algq-deal-marketplace',
				'show_in_rest'        => false,
				'supports'            => array( 'title', 'editor', 'thumbnail', 'revisions' ),
				'map_meta_cap'        => true,
				'capability_type'     => array( 'algq_market_deal', 'algq_market_deals' ),
				'has_archive'         => false,
				'exclude_from_search' => true,
				'menu_icon'           => 'dashicons-building',
			)
		);
	}

	public static function add_meta_boxes(): void {
		add_meta_box( 'algq_dm_deal_economics', __( 'Deal Economics', 'algq-deal-marketplace' ), array( __CLASS__, 'render_economics' ), 'algq_market_deal', 'normal', 'high' );
		add_meta_box( 'algq_dm_deal_access', __( 'Access and Confidentiality', 'algq-deal-marketplace' ), array( __CLASS__, 'render_access' ), 'algq_market_deal', 'side', 'high' );
		add_meta_box( 'algq_dm_deal_package', __( 'Controlled Deal Package', 'algq-deal-marketplace' ), array( __CLASS__, 'render_package' ), 'algq_market_deal', 'side', 'default' );
	}

	private static function input( WP_Post $post, string $key, string $label, string $type = 'text' ): void {
		$value = get_post_meta( $post->ID, self::META_PREFIX . $key, true );
		echo '<p><label for="algq_dm_' . esc_attr( $key ) . '"><strong>' . esc_html( $label ) . '</strong></label><br>';
		echo '<input class="widefat" type="' . esc_attr( $type ) . '" id="algq_dm_' . esc_attr( $key ) . '" name="algq_dm_' . esc_attr( $key ) . '" value="' . esc_attr( (string) $value ) . '"></p>';
	}

	public static function render_economics( WP_Post $post ): void {
		wp_nonce_field( 'algq_dm_save_deal', 'algq_dm_nonce' );
		self::input( $post, 'address', __( 'Property address', 'algq-deal-marketplace' ) );
		self::input( $post, 'city', __( 'City', 'algq-deal-marketplace' ) );
		self::input( $post, 'state', __( 'State', 'algq-deal-marketplace' ) );
		self::input( $post, 'zip', __( 'ZIP code', 'algq-deal-marketplace' ) );
		self::input( $post, 'price', __( 'Asking price', 'algq-deal-marketplace' ), 'number' );
		self::input( $post, 'arv', __( 'After-repair value', 'algq-deal-marketplace' ), 'number' );
		self::input( $post, 'strategy', __( 'Primary strategy', 'algq-deal-marketplace' ) );
		self::input( $post, 'expires_at', __( 'Marketplace expiration (YYYY-MM-DD)', 'algq-deal-marketplace' ) );
	}

	public static function render_access( WP_Post $post ): void {
		$access = (string) get_post_meta( $post->ID, self::META_PREFIX . 'access_tier', true );
		$access = $access ?: 'registered';
		$allowed = (string) get_post_meta( $post->ID, self::META_PREFIX . 'allowed_buyers', true );
		$nda_version = (string) get_post_meta( $post->ID, self::META_PREFIX . 'nda_version', true );
		$nda_version = $nda_version ?: (string) get_option( 'algq_dm_default_nda_version', '2026.1' );

		echo '<p><label for="algq_dm_access_tier"><strong>' . esc_html__( 'Access tier', 'algq-deal-marketplace' ) . '</strong></label><br><select class="widefat" id="algq_dm_access_tier" name="algq_dm_access_tier">';
		foreach ( array( 'registered' => __( 'Registered buyers', 'algq-deal-marketplace' ), 'premium' => __( 'Premium entitlement', 'algq-deal-marketplace' ), 'private' => __( 'Explicit grant only', 'algq-deal-marketplace' ) ) as $value => $label ) {
			echo '<option value="' . esc_attr( $value ) . '" ' . selected( $access, $value, false ) . '>' . esc_html( $label ) . '</option>';
		}
		echo '</select></p>';
		echo '<p><label for="algq_dm_nda_version"><strong>' . esc_html__( 'Required NDA version', 'algq-deal-marketplace' ) . '</strong></label><br><input class="widefat" id="algq_dm_nda_version" name="algq_dm_nda_version" value="' . esc_attr( $nda_version ) . '"></p>';
		echo '<p><label for="algq_dm_allowed_buyers"><strong>' . esc_html__( 'Allowed buyer user IDs', 'algq-deal-marketplace' ) . '</strong></label><br><input class="widefat" id="algq_dm_allowed_buyers" name="algq_dm_allowed_buyers" value="' . esc_attr( $allowed ) . '"></p>';
		echo '<p class="description">' . esc_html__( 'Comma-separated. When supplied, the list is enforced in addition to the selected tier.', 'algq-deal-marketplace' ) . '</p>';
	}

	public static function render_package( WP_Post $post ): void {
		self::input( $post, 'package_attachment_id', __( 'Private package attachment ID', 'algq-deal-marketplace' ), 'number' );
		echo '<p class="description">' . esc_html__( 'Use a private-storage attachment or Document Library record. The plugin never renders a raw package URL.', 'algq-deal-marketplace' ) . '</p>';
	}

	public static function save( int $post_id, WP_Post $post ): void {
		if ( wp_is_post_revision( $post_id ) || wp_is_post_autosave( $post_id ) ) {
			return;
		}
		if ( ! isset( $_POST['algq_dm_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['algq_dm_nonce'] ) ), 'algq_dm_save_deal' ) ) {
			return;
		}
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		$text_fields = array( 'address', 'city', 'state', 'zip', 'strategy', 'expires_at', 'nda_version' );
		foreach ( $text_fields as $field ) {
			if ( isset( $_POST[ 'algq_dm_' . $field ] ) ) {
				update_post_meta( $post_id, self::META_PREFIX . $field, sanitize_text_field( wp_unslash( $_POST[ 'algq_dm_' . $field ] ) ) );
			}
		}

		foreach ( array( 'price', 'arv' ) as $field ) {
			if ( isset( $_POST[ 'algq_dm_' . $field ] ) ) {
				update_post_meta( $post_id, self::META_PREFIX . $field, max( 0, (float) wp_unslash( $_POST[ 'algq_dm_' . $field ] ) ) );
			}
		}

		if ( isset( $_POST['algq_dm_package_attachment_id'] ) ) {
			update_post_meta( $post_id, self::META_PREFIX . 'package_attachment_id', absint( $_POST['algq_dm_package_attachment_id'] ) );
		}

		if ( isset( $_POST['algq_dm_access_tier'] ) ) {
			$tier = sanitize_key( wp_unslash( $_POST['algq_dm_access_tier'] ) );
			update_post_meta( $post_id, self::META_PREFIX . 'access_tier', in_array( $tier, array( 'registered', 'premium', 'private' ), true ) ? $tier : 'private' );
		}

		if ( isset( $_POST['algq_dm_allowed_buyers'] ) ) {
			$ids = array_filter( array_map( 'absint', explode( ',', sanitize_text_field( wp_unslash( $_POST['algq_dm_allowed_buyers'] ) ) ) ) );
			update_post_meta( $post_id, self::META_PREFIX . 'allowed_buyers', implode( ',', array_unique( $ids ) ) );
		}

		ALGQ_DM_Support::audit( 'deal_saved', $post_id, array( 'post_status' => $post->post_status ) );
		do_action( 'algq_dm_deal_saved', $post_id, get_current_user_id() );
	}

	public static function meta( int $deal_id, string $key, mixed $default = '' ): mixed {
		$value = get_post_meta( $deal_id, self::META_PREFIX . $key, true );
		return '' === $value ? $default : $value;
	}
}
