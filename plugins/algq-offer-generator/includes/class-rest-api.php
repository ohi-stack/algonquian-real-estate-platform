<?php

defined( 'ABSPATH' ) || exit;

final class ALGQ_Offer_REST_API {
    public static function init(): void { add_action( 'rest_api_init', array( __CLASS__, 'routes' ) ); }

    public static function routes(): void {
        register_rest_route( 'algq/v1', '/offers', array(
            array( 'methods' => WP_REST_Server::READABLE, 'callback' => array( __CLASS__, 'index' ), 'permission_callback' => array( __CLASS__, 'can_view' ) ),
            array( 'methods' => WP_REST_Server::CREATABLE, 'callback' => array( __CLASS__, 'create' ), 'permission_callback' => array( __CLASS__, 'can_create' ) ),
        ) );
        register_rest_route( 'algq/v1', '/offers/seller-financing/from-underwriting', array(
            'methods' => WP_REST_Server::CREATABLE,
            'callback' => array( __CLASS__, 'create_seller_financing_from_underwriting' ),
            'permission_callback' => array( __CLASS__, 'can_create' ),
            'args' => array(
                'deal_id' => array( 'type' => 'integer', 'required' => true, 'sanitize_callback' => 'absint', 'validate_callback' => static fn( $v ) => absint( $v ) > 0 ),
                'proposal_type' => array( 'type' => 'string', 'required' => false, 'default' => 'proposal', 'enum' => array( 'proposal', 'term_sheet', 'loi', 'offer' ) ),
                'closing_date' => array( 'type' => 'string', 'required' => false ),
                'contingencies' => array( 'type' => 'string', 'required' => false ),
                'terms' => array( 'type' => 'string', 'required' => false ),
            ),
        ) );
        register_rest_route( 'algq/v1', '/offers/(?P<id>\d+)', array(
            array( 'methods' => WP_REST_Server::READABLE, 'callback' => array( __CLASS__, 'show' ), 'permission_callback' => array( __CLASS__, 'can_view_one' ) ),
            array( 'methods' => 'PATCH', 'callback' => array( __CLASS__, 'update' ), 'permission_callback' => array( __CLASS__, 'can_edit_one' ) ),
        ) );
        register_rest_route( 'algq/v1', '/offers/(?P<id>\d+)/approve', array( 'methods' => WP_REST_Server::CREATABLE, 'callback' => array( __CLASS__, 'approve' ), 'permission_callback' => static fn() => current_user_can( 'approve_algq_offers' ) ) );
        register_rest_route( 'algq/v1', '/offers/(?P<id>\d+)/document', array( 'methods' => WP_REST_Server::CREATABLE, 'callback' => array( __CLASS__, 'document' ), 'permission_callback' => static fn() => current_user_can( 'generate_algq_offer_documents' ) ) );
    }

    public static function can_view(): bool { return current_user_can( 'view_algq_offer_history' ) || current_user_can( 'manage_algq_offers' ); }
    public static function can_create(): bool { return current_user_can( 'create_algq_offers' ) || current_user_can( 'manage_algq_offers' ); }
    public static function can_view_one( WP_REST_Request $request ): bool { return current_user_can( 'read_post', absint( $request['id'] ) ) || self::can_view(); }
    public static function can_edit_one( WP_REST_Request $request ): bool { return current_user_can( 'edit_post', absint( $request['id'] ) ) || current_user_can( 'manage_algq_offers' ); }

    public static function index( WP_REST_Request $request ): WP_REST_Response {
        $page = max( 1, absint( $request->get_param( 'page' ) ?: 1 ) );
        $per_page = min( 100, max( 1, absint( $request->get_param( 'per_page' ) ?: 20 ) ) );
        $query = new WP_Query( array( 'post_type' => 'algq_offer', 'post_status' => array( 'publish', 'draft', 'pending', 'private' ), 'paged' => $page, 'posts_per_page' => $per_page, 'orderby' => 'modified', 'order' => 'DESC' ) );
        return new WP_REST_Response( array( 'items' => array_map( array( __CLASS__, 'prepare' ), $query->posts ), 'page' => $page, 'pages' => (int) $query->max_num_pages, 'total' => (int) $query->found_posts ) );
    }

    public static function create( WP_REST_Request $request ) {
        $result = ALGQ_Offer_Service::create( $request->get_json_params() ?: $request->get_params(), get_current_user_id() );
        return is_wp_error( $result ) ? $result : new WP_REST_Response( self::prepare( get_post( $result ), true ), 201 );
    }

    public static function create_seller_financing_from_underwriting( WP_REST_Request $request ) {
        $overrides = array(
            'closing_date' => sanitize_text_field( (string) $request->get_param( 'closing_date' ) ),
            'contingencies' => sanitize_textarea_field( (string) $request->get_param( 'contingencies' ) ),
            'terms' => sanitize_textarea_field( (string) $request->get_param( 'terms' ) ),
        );
        $result = ALGQ_Offer_Service::create_from_approved_underwriting( absint( $request->get_param( 'deal_id' ) ), sanitize_key( $request->get_param( 'proposal_type' ) ?: 'proposal' ), $overrides, get_current_user_id() );
        return is_wp_error( $result ) ? $result : new WP_REST_Response( self::prepare( get_post( $result ), true ), 201 );
    }

    public static function show( WP_REST_Request $request ) {
        $post = get_post( absint( $request['id'] ) );
        return $post && 'algq_offer' === $post->post_type ? rest_ensure_response( self::prepare( $post, true ) ) : new WP_Error( 'algq_offer_not_found', __( 'Offer not found.', 'algq-offer-generator' ), array( 'status' => 404 ) );
    }

    public static function update( WP_REST_Request $request ) {
        $result = ALGQ_Offer_Service::update( absint( $request['id'] ), $request->get_json_params() ?: $request->get_params(), get_current_user_id() );
        return is_wp_error( $result ) ? $result : rest_ensure_response( self::prepare( get_post( $result ), true ) );
    }

    public static function approve( WP_REST_Request $request ) {
        $result = ALGQ_Offer_Service::approve( absint( $request['id'] ), get_current_user_id() );
        return is_wp_error( $result ) ? $result : rest_ensure_response( self::prepare( get_post( absint( $request['id'] ) ), true ) );
    }

    public static function document( WP_REST_Request $request ) {
        $offer_id = absint( $request['id'] );
        if ( 'algq_offer' !== get_post_type( $offer_id ) ) { return new WP_Error( 'algq_offer_not_found', __( 'Offer not found.', 'algq-offer-generator' ), array( 'status' => 404 ) ); }
        $html = ALGQ_Offer_Document_Generator::render_offer_html( $offer_id );
        $hash = hash( 'sha256', $html );
        update_post_meta( $offer_id, '_algq_offer_document_html', wp_kses_post( $html ) );
        update_post_meta( $offer_id, '_algq_offer_document_hash', $hash );
        do_action( 'algq_offer_document_generated', $offer_id, get_current_user_id() );
        return rest_ensure_response( array( 'offer_id' => $offer_id, 'hash' => $hash ) );
    }

    private static function prepare( WP_Post $post, bool $detailed = false ): array {
        $data = ALGQ_Offer_Service::get( $post->ID );
        $out = array(
            'id' => $post->ID, 'offer_number' => $data['offer_number'], 'title' => get_the_title( $post ),
            'workflow_status' => $data['offer_status'] ?: get_post_status( $post ), 'strategy' => $data['strategy'], 'proposal_type' => $data['proposal_type'],
            'purchase_price' => $data['purchase_price'], 'deal_id' => absint( $data['deal_id'] ), 'underwriting_id' => absint( $data['underwriting_id'] ),
            'version' => absint( $data['version_number'] ?: 1 ), 'modified_gmt' => get_post_modified_time( 'c', true, $post ),
        );
        if ( $detailed ) { $out['details'] = $data; $out['document_hash'] = get_post_meta( $post->ID, '_algq_offer_document_hash', true ); }
        return $out;
    }
}
