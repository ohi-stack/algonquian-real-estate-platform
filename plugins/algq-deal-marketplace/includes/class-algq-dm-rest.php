<?php
/**
 * Marketplace REST interface.
 *
 * @package Algonquian_Deal_Marketplace
 */

defined( 'ABSPATH' ) || exit;

final class ALGQ_DM_REST {
	public static function init(): void {
		add_action( 'rest_api_init', array( __CLASS__, 'routes' ) );
	}

	public static function routes(): void {
		register_rest_route(
			'algq/v1',
			'/marketplace/deals',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( __CLASS__, 'deals' ),
				'permission_callback' => array( __CLASS__, 'can_view_marketplace' ),
				'args'                => array(
					'page'     => array( 'sanitize_callback' => 'absint', 'default' => 1 ),
					'per_page' => array( 'sanitize_callback' => 'absint', 'default' => 20 ),
				),
			)
		);
		register_rest_route(
			'algq/v1',
			'/marketplace/deals/(?P<id>\d+)',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( __CLASS__, 'deal' ),
				'permission_callback' => array( __CLASS__, 'can_view_deal_request' ),
				'args'                => array( 'id' => array( 'sanitize_callback' => 'absint' ) ),
			)
		);
		register_rest_route(
			'algq/v1',
			'/marketplace/offers',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( __CLASS__, 'create_offer' ),
				'permission_callback' => static fn(): bool => current_user_can( 'submit_algq_marketplace_offer' ),
				'args'                => array(
					'deal_id'        => array( 'required' => true, 'sanitize_callback' => 'absint' ),
					'offer_amount'   => array( 'required' => true, 'sanitize_callback' => static fn( $value ): float => (float) $value ),
					'earnest_money'  => array( 'sanitize_callback' => static fn( $value ): float => (float) $value, 'default' => 0 ),
					'financing_type' => array( 'sanitize_callback' => 'sanitize_key', 'default' => 'cash' ),
					'terms'          => array( 'sanitize_callback' => 'wp_kses_post', 'default' => '' ),
				),
			)
		);
	}

	public static function can_view_marketplace(): bool {
		return ALGQ_DM_Access::buyer_has_base_access();
	}

	public static function can_view_deal_request( WP_REST_Request $request ): bool {
		return ALGQ_DM_Access::can_view_deal( absint( $request['id'] ) );
	}

	public static function deals( WP_REST_Request $request ): WP_REST_Response {
		$per_page = min( 100, max( 1, absint( $request['per_page'] ) ) );
		$page = max( 1, absint( $request['page'] ) );
		$query = new WP_Query( array( 'post_type' => 'algq_market_deal', 'post_status' => 'publish', 'posts_per_page' => $per_page, 'paged' => $page ) );
		$data = array();
		foreach ( $query->posts as $post ) {
			if ( ALGQ_DM_Access::can_view_deal( (int) $post->ID ) ) {
				$data[] = self::serialize_deal( (int) $post->ID );
			}
		}
		$response = rest_ensure_response( $data );
		$response->header( 'X-WP-Total', (string) $query->found_posts );
		$response->header( 'X-WP-TotalPages', (string) $query->max_num_pages );
		return $response;
	}

	public static function deal( WP_REST_Request $request ): WP_REST_Response {
		return rest_ensure_response( self::serialize_deal( absint( $request['id'] ) ) );
	}

	public static function create_offer( WP_REST_Request $request ): WP_REST_Response|WP_Error {
		$result = ALGQ_DM_Offers::create(
			get_current_user_id(),
			absint( $request['deal_id'] ),
			(float) $request['offer_amount'],
			(float) $request['earnest_money'],
			sanitize_key( (string) $request['financing_type'] ),
			wp_kses_post( (string) $request['terms'] )
		);
		if ( is_wp_error( $result ) ) {
			return $result;
		}
		return new WP_REST_Response( array( 'offer_id' => $result, 'status' => 'submitted' ), 201 );
	}

	/** @return array<string,mixed> */
	private static function serialize_deal( int $deal_id ): array {
		return array(
			'id'              => $deal_id,
			'title'           => get_the_title( $deal_id ),
			'summary'         => wp_strip_all_tags( get_post_field( 'post_excerpt', $deal_id ) ?: wp_trim_words( get_post_field( 'post_content', $deal_id ), 40 ) ),
			'city'            => ALGQ_DM_Marketplace::meta( $deal_id, 'city', '' ),
			'state'           => ALGQ_DM_Marketplace::meta( $deal_id, 'state', 'CT' ),
			'price'           => (float) ALGQ_DM_Marketplace::meta( $deal_id, 'price', 0 ),
			'arv'             => (float) ALGQ_DM_Marketplace::meta( $deal_id, 'arv', 0 ),
			'strategy'        => ALGQ_DM_Marketplace::meta( $deal_id, 'strategy', '' ),
			'nda_accepted'    => ALGQ_DM_NDA::accepted( get_current_user_id(), $deal_id ),
			'can_download'    => ALGQ_DM_Access::can_download( $deal_id ),
			'download_url'    => ALGQ_DM_Access::can_download( $deal_id ) ? ALGQ_DM_Access::download_url( $deal_id ) : null,
		);
	}
}
