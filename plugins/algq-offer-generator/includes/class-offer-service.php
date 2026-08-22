<?php

defined( 'ABSPATH' ) || exit;

final class ALGQ_Offer_Service {
    private const STRATEGIES = array( 'cash', 'seller_financing', 'subject_to', 'loi', 'purchase' );
    private const PROPOSAL_TYPES = array( 'proposal', 'term_sheet', 'loi', 'offer' );
    private const LOCKED_UNDERWRITING_FIELDS = array(
        'purchase_price', 'down_payment', 'seller_financed_principal', 'monthly_payment', 'interest_rate',
        'amortization_months', 'balloon_months', 'balloon_balance', 'annual_debt_service', 'total_debt_service',
        'dscr', 'cash_flow', 'refinance_capacity', 'refinance_gap', 'conventional_monthly_payment',
        'underwriting_id', 'underwriting_uuid', 'underwriting_formula_version', 'underwriting_approved_at', 'underwriting_locked',
    );

    public static function strategies(): array { return self::STRATEGIES; }
    public static function proposal_types(): array { return self::PROPOSAL_TYPES; }

    public static function create_from_approved_underwriting( int $deal_id, string $proposal_type = 'proposal', array $overrides = array(), int $user_id = 0 ) {
        if ( ! class_exists( 'ALGQ_Offer_Seller_Financing_Proposals' ) ) {
            return new WP_Error( 'algq_offer_proposal_service_missing', __( 'Seller-financing proposal service is unavailable.', 'algq-offer-generator' ), array( 'status' => 500 ) );
        }
        $input = ALGQ_Offer_Seller_Financing_Proposals::proposal_input_from_underwriting( $deal_id, $proposal_type );
        if ( is_wp_error( $input ) ) { return $input; }

        foreach ( $overrides as $key => $value ) {
            if ( ! in_array( $key, self::LOCKED_UNDERWRITING_FIELDS, true ) ) {
                $input[ $key ] = $value;
            }
        }
        return self::create( $input, $user_id );
    }

    public static function create( array $input, int $user_id = 0 ) {
        $user_id = $user_id ?: get_current_user_id();
        if ( ! user_can( $user_id, 'create_algq_offers' ) && ! user_can( $user_id, 'manage_algq_offers' ) ) {
            return new WP_Error( 'algq_offer_forbidden', __( 'You are not authorized to create offers.', 'algq-offer-generator' ), array( 'status' => 403 ) );
        }

        $data = self::sanitize_input( $input );
        $error = self::validate( $data );
        if ( is_wp_error( $error ) ) { return $error; }

        if ( $data['deal_id'] && class_exists( 'ALGQ_Offer_Deal_Integration' ) ) {
            $deal = ALGQ_Offer_Deal_Integration::get_deal_summary( $data['deal_id'] );
            if ( empty( $data['property_address'] ) && ! empty( $deal['address'] ) ) { $data['property_address'] = sanitize_text_field( $deal['address'] ); }
            if ( empty( $data['property_address'] ) && ! empty( $deal['title'] ) ) { $data['property_address'] = sanitize_text_field( $deal['title'] ); }
        }

        $offer_id = wp_insert_post( array(
            'post_type' => 'algq_offer', 'post_status' => 'draft', 'post_title' => __( 'New Acquisition Proposal', 'algq-offer-generator' ),
            'post_author' => $user_id, 'post_content' => '',
        ), true );
        if ( is_wp_error( $offer_id ) ) { return $offer_id; }

        $offer_number = sprintf( 'ARE-OFR-%s-%06d', gmdate( 'Y' ), absint( $offer_id ) );
        wp_update_post( array( 'ID' => $offer_id, 'post_title' => sprintf( '%1$s — %2$s', $offer_number, $data['property_address'] ?: __( 'Linked Deal', 'algq-offer-generator' ) ) ) );

        $data['offer_number'] = $offer_number;
        $data['offer_status'] = 'draft';
        $data['version_number'] = 1;
        $data['created_by'] = $user_id;
        $data['created_at'] = current_time( 'mysql' );
        self::write_meta( $offer_id, $data );
        self::snapshot( $offer_id, 'created', $user_id );

        do_action( 'algq_offer_created', $offer_id, $data, $user_id );
        do_action( 'algq_offer_saved', $offer_id, $user_id );
        if ( 'seller_financing' === $data['strategy'] ) {
            do_action( 'algq_seller_financing_proposal_created', $offer_id, $data['deal_id'], $data['underwriting_id'], $data['proposal_type'] );
        }
        return $offer_id;
    }

    public static function update( int $offer_id, array $input, int $user_id = 0 ) {
        $user_id = $user_id ?: get_current_user_id();
        if ( 'algq_offer' !== get_post_type( $offer_id ) ) { return new WP_Error( 'algq_offer_invalid', __( 'Invalid offer.', 'algq-offer-generator' ), array( 'status' => 404 ) ); }
        if ( ! user_can( $user_id, 'edit_post', $offer_id ) && ! user_can( $user_id, 'manage_algq_offers' ) ) { return new WP_Error( 'algq_offer_forbidden', __( 'You are not authorized to edit this offer.', 'algq-offer-generator' ), array( 'status' => 403 ) ); }

        $current = self::get( $offer_id );
        $merged = array_merge( $current, $input );
        if ( array_key_exists( 'offer_strategy', $input ) ) { $merged['strategy'] = $input['offer_strategy']; }
        if ( array_key_exists( 'price', $input ) && ! array_key_exists( 'purchase_price', $input ) ) { $merged['purchase_price'] = $input['price']; }
        if ( array_key_exists( 'offer_terms', $input ) ) { $merged['terms'] = $input['offer_terms']; $merged['notes'] = $input['offer_terms']; }

        if ( ! empty( $current['underwriting_locked'] ) ) {
            foreach ( self::LOCKED_UNDERWRITING_FIELDS as $key ) {
                if ( array_key_exists( $key, $current ) ) { $merged[ $key ] = $current[ $key ]; }
            }
            $merged['strategy'] = 'seller_financing';
        }

        $data = self::sanitize_input( $merged );
        $error = self::validate( $data );
        if ( is_wp_error( $error ) ) { return $error; }

        $data['version_number'] = max( 1, absint( $current['version_number'] ?? 1 ) + 1 );
        $data['updated_by'] = $user_id;
        $data['updated_at'] = current_time( 'mysql' );
        self::write_meta( $offer_id, $data );
        self::snapshot( $offer_id, 'updated', $user_id );
        do_action( 'algq_offer_saved', $offer_id, $user_id );
        return $offer_id;
    }

    public static function approve( int $offer_id, int $user_id = 0 ) {
        $user_id = $user_id ?: get_current_user_id();
        if ( ! user_can( $user_id, 'approve_algq_offers' ) ) { return new WP_Error( 'algq_offer_forbidden', __( 'You are not authorized to approve offers.', 'algq-offer-generator' ), array( 'status' => 403 ) ); }
        if ( 'algq_offer' !== get_post_type( $offer_id ) ) { return new WP_Error( 'algq_offer_invalid', __( 'Invalid offer.', 'algq-offer-generator' ), array( 'status' => 404 ) ); }

        $offer = self::get( $offer_id );
        if ( 'seller_financing' === $offer['strategy'] && ( empty( $offer['underwriting_id'] ) || empty( $offer['underwriting_locked'] ) ) ) {
            return new WP_Error( 'algq_offer_underwriting_required', __( 'Seller-financing offers must be tied to an approved MAO underwriting scenario before approval.', 'algq-offer-generator' ), array( 'status' => 409 ) );
        }

        update_post_meta( $offer_id, '_algq_offer_status', 'approved' );
        update_post_meta( $offer_id, '_algq_offer_approved_by', $user_id );
        update_post_meta( $offer_id, '_algq_offer_approved_at', current_time( 'mysql' ) );
        self::snapshot( $offer_id, 'approved', $user_id );
        do_action( 'algq_offer_status_changed', $offer_id, 'approved', $user_id );
        do_action( 'algq_offer_approved', $offer_id, self::get( $offer_id ), $user_id );
        return true;
    }

    public static function get( int $offer_id ): array {
        $keys = array(
            'offer_number','strategy','proposal_type','purchase_price','down_payment','seller_financed_principal','monthly_payment','interest_rate',
            'amortization_months','balloon_months','balloon_balance','annual_debt_service','total_debt_service','dscr','cash_flow','refinance_capacity',
            'refinance_gap','conventional_monthly_payment','closing_date','contingencies','notes','terms','property_address','deal_id','template_id',
            'underwriting_id','underwriting_uuid','underwriting_formula_version','underwriting_approved_at','underwriting_locked',
            'offer_status','version_number','created_by','created_at','updated_by','updated_at','approved_by','approved_at',
        );
        $data = array( 'id' => $offer_id );
        foreach ( $keys as $key ) { $data[ $key ] = get_post_meta( $offer_id, '_algq_offer_' . $key, true ); }
        if ( empty( $data['purchase_price'] ) ) { $data['purchase_price'] = get_post_meta( $offer_id, '_algq_offer_price', true ); }
        return $data;
    }

    private static function sanitize_input( array $input ): array {
        return array(
            'strategy' => sanitize_key( $input['strategy'] ?? $input['offer_strategy'] ?? 'cash' ),
            'proposal_type' => sanitize_key( $input['proposal_type'] ?? 'offer' ),
            'purchase_price' => self::decimal( $input['purchase_price'] ?? $input['price'] ?? '' ),
            'down_payment' => self::decimal( $input['down_payment'] ?? '' ),
            'seller_financed_principal' => self::decimal( $input['seller_financed_principal'] ?? '' ),
            'monthly_payment' => self::decimal( $input['monthly_payment'] ?? '' ),
            'interest_rate' => self::decimal( $input['interest_rate'] ?? '' ),
            'amortization_months' => absint( $input['amortization_months'] ?? 0 ),
            'balloon_months' => absint( $input['balloon_months'] ?? 0 ),
            'balloon_balance' => self::decimal( $input['balloon_balance'] ?? '' ),
            'annual_debt_service' => self::decimal( $input['annual_debt_service'] ?? '' ),
            'total_debt_service' => self::decimal( $input['total_debt_service'] ?? '' ),
            'dscr' => self::decimal( $input['dscr'] ?? '' ),
            'cash_flow' => self::signed_decimal( $input['cash_flow'] ?? '' ),
            'refinance_capacity' => self::decimal( $input['refinance_capacity'] ?? '' ),
            'refinance_gap' => self::decimal( $input['refinance_gap'] ?? '' ),
            'conventional_monthly_payment' => self::decimal( $input['conventional_monthly_payment'] ?? '' ),
            'closing_date' => self::date( $input['closing_date'] ?? '' ),
            'contingencies' => sanitize_textarea_field( wp_unslash( $input['contingencies'] ?? '' ) ),
            'notes' => sanitize_textarea_field( wp_unslash( $input['notes'] ?? $input['offer_terms'] ?? '' ) ),
            'terms' => sanitize_textarea_field( wp_unslash( $input['terms'] ?? $input['offer_terms'] ?? '' ) ),
            'property_address' => sanitize_text_field( wp_unslash( $input['property_address'] ?? '' ) ),
            'deal_id' => absint( $input['deal_id'] ?? 0 ),
            'template_id' => absint( $input['template_id'] ?? 0 ),
            'underwriting_id' => absint( $input['underwriting_id'] ?? 0 ),
            'underwriting_uuid' => sanitize_text_field( $input['underwriting_uuid'] ?? '' ),
            'underwriting_formula_version' => sanitize_text_field( $input['underwriting_formula_version'] ?? '' ),
            'underwriting_approved_at' => sanitize_text_field( $input['underwriting_approved_at'] ?? '' ),
            'underwriting_locked' => empty( $input['underwriting_locked'] ) ? 0 : 1,
        );
    }

    private static function validate( array $data ) {
        if ( ! in_array( $data['strategy'], self::STRATEGIES, true ) ) { return new WP_Error( 'algq_offer_strategy', __( 'Select a valid offer strategy.', 'algq-offer-generator' ), array( 'status' => 400 ) ); }
        if ( ! in_array( $data['proposal_type'], self::PROPOSAL_TYPES, true ) ) { return new WP_Error( 'algq_offer_proposal_type', __( 'Select a valid proposal type.', 'algq-offer-generator' ), array( 'status' => 400 ) ); }
        if ( empty( $data['property_address'] ) && empty( $data['deal_id'] ) ) { return new WP_Error( 'algq_offer_address', __( 'A property address or linked deal is required.', 'algq-offer-generator' ), array( 'status' => 400 ) ); }
        if ( (float) $data['purchase_price'] <= 0 ) { return new WP_Error( 'algq_offer_price', __( 'Purchase price must be greater than zero.', 'algq-offer-generator' ), array( 'status' => 400 ) ); }
        if ( (float) $data['down_payment'] > (float) $data['purchase_price'] ) { return new WP_Error( 'algq_offer_down_payment', __( 'Down payment cannot exceed purchase price.', 'algq-offer-generator' ), array( 'status' => 400 ) ); }
        if ( 'seller_financing' === $data['strategy'] ) {
            if ( (float) $data['seller_financed_principal'] <= 0 ) { return new WP_Error( 'algq_offer_seller_principal', __( 'Seller-financed principal must be greater than zero.', 'algq-offer-generator' ), array( 'status' => 400 ) ); }
            if ( $data['balloon_months'] && $data['amortization_months'] && $data['balloon_months'] > $data['amortization_months'] ) { return new WP_Error( 'algq_offer_balloon', __( 'Balloon term cannot exceed the amortization term.', 'algq-offer-generator' ), array( 'status' => 400 ) ); }
        }
        return true;
    }

    private static function write_meta( int $offer_id, array $data ): void {
        foreach ( $data as $key => $value ) { if ( 'id' !== $key ) { update_post_meta( $offer_id, '_algq_offer_' . sanitize_key( $key ), $value ); } }
        if ( array_key_exists( 'purchase_price', $data ) ) { update_post_meta( $offer_id, '_algq_offer_price', $data['purchase_price'] ); }
    }

    private static function snapshot( int $offer_id, string $event, int $user_id ): void {
        $versions = get_post_meta( $offer_id, '_algq_offer_versions', true );
        $versions = is_array( $versions ) ? $versions : array();
        $versions[] = array( 'event' => sanitize_key( $event ), 'version' => absint( get_post_meta( $offer_id, '_algq_offer_version_number', true ) ?: 1 ), 'user_id' => $user_id, 'created_at' => current_time( 'mysql' ), 'data' => self::get( $offer_id ) );
        update_post_meta( $offer_id, '_algq_offer_versions', array_slice( $versions, -50 ) );
    }

    private static function decimal( $value ): string { $value = preg_replace( '/[^0-9.]/', '', (string) $value ); return '' === $value ? '0.00' : number_format( max( 0, (float) $value ), 2, '.', '' ); }
    private static function signed_decimal( $value ): string { $value = preg_replace( '/[^0-9.\-]/', '', (string) $value ); return '' === $value ? '0.00' : number_format( (float) $value, 2, '.', '' ); }
    private static function date( $value ): string { $value = sanitize_text_field( (string) $value ); $date = DateTime::createFromFormat( 'Y-m-d', $value ); return $date && $date->format( 'Y-m-d' ) === $value ? $value : ''; }
}
