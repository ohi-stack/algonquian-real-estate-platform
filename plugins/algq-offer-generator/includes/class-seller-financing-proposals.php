<?php

defined( 'ABSPATH' ) || exit;

/**
 * Seller-financing proposal composition and approved-underwriting import.
 *
 * The MAO Engine remains authoritative for underwriting mathematics. This
 * class consumes approved underwriting snapshots and turns those approved
 * economics into controlled proposal language.
 */
final class ALGQ_Offer_Seller_Financing_Proposals {
    private const DOCUMENT_TYPES = array( 'proposal', 'term_sheet', 'loi', 'offer' );

    public static function document_types(): array {
        return self::DOCUMENT_TYPES;
    }

    public static function approved_underwriting_payload( int $deal_id ): array {
        if ( ! $deal_id ) {
            return array();
        }

        $payload = apply_filters( 'algq_offer_generator_deal_payload', array(), $deal_id );
        $underwriting = isset( $payload['mao_engine'] ) && is_array( $payload['mao_engine'] ) ? $payload['mao_engine'] : array();

        if ( empty( $underwriting ) || 'approved' !== sanitize_key( $underwriting['status'] ?? '' ) ) {
            return array();
        }

        return $underwriting;
    }

    public static function proposal_input_from_underwriting( int $deal_id, string $document_type = 'proposal' ) {
        $document_type = sanitize_key( $document_type );
        if ( ! in_array( $document_type, self::DOCUMENT_TYPES, true ) ) {
            return new WP_Error( 'algq_offer_document_type', __( 'Select a valid proposal document type.', 'algq-offer-generator' ), array( 'status' => 400 ) );
        }

        $u = self::approved_underwriting_payload( $deal_id );
        if ( ! $u ) {
            return new WP_Error( 'algq_offer_underwriting_required', __( 'An approved MAO underwriting scenario is required before generating seller-financing proposal terms.', 'algq-offer-generator' ), array( 'status' => 409 ) );
        }
        if ( 'seller_financing' !== sanitize_key( $u['strategy'] ?? '' ) ) {
            return new WP_Error( 'algq_offer_wrong_underwriting_strategy', __( 'The approved underwriting scenario is not a seller-financing scenario.', 'algq-offer-generator' ), array( 'status' => 409 ) );
        }

        $inputs = isset( $u['inputs'] ) && is_array( $u['inputs'] ) ? $u['inputs'] : array();
        $principal = (float) ( $u['seller_financed_principal'] ?? $inputs['seller_financed_principal'] ?? 0 );
        $price = (float) ( $u['purchase_price'] ?? $inputs['purchase_price'] ?? 0 );
        $down = (float) ( $u['down_payment'] ?? $inputs['down_payment'] ?? max( 0, $price - $principal ) );

        return array(
            'strategy'                     => 'seller_financing',
            'proposal_type'                => $document_type,
            'deal_id'                      => $deal_id,
            'purchase_price'               => $price,
            'down_payment'                 => $down,
            'seller_financed_principal'    => $principal,
            'interest_rate'                => (float) ( $u['interest_rate'] ?? $inputs['interest_rate'] ?? 0 ),
            'amortization_months'          => absint( $u['amortization_months'] ?? $inputs['amortization_months'] ?? 0 ),
            'balloon_months'               => absint( $u['balloon_months'] ?? $inputs['balloon_months'] ?? 0 ),
            'monthly_payment'              => (float) ( $u['monthly_payment'] ?? 0 ),
            'balloon_balance'              => (float) ( $u['balloon_balance'] ?? 0 ),
            'annual_debt_service'          => (float) ( $u['annual_debt_service'] ?? 0 ),
            'total_debt_service'           => (float) ( $u['total_debt_service'] ?? 0 ),
            'dscr'                         => (float) ( $u['dscr'] ?? 0 ),
            'cash_flow'                    => (float) ( $u['cash_flow'] ?? 0 ),
            'refinance_capacity'           => (float) ( $u['refinance_capacity'] ?? 0 ),
            'refinance_gap'                => (float) ( $u['refinance_gap'] ?? 0 ),
            'conventional_monthly_payment' => (float) ( $u['conventional_monthly_payment'] ?? 0 ),
            'underwriting_id'              => absint( $u['id'] ?? 0 ),
            'underwriting_uuid'            => sanitize_text_field( $u['uuid'] ?? '' ),
            'underwriting_formula_version' => sanitize_text_field( $u['formula_version'] ?? '' ),
            'underwriting_approved_at'     => sanitize_text_field( $u['approved_at'] ?? '' ),
            'underwriting_locked'          => 1,
        );
    }

    public static function compose_sections( array $offer ): array {
        $principal = (float) ( $offer['seller_financed_principal'] ?? 0 );
        $amortization = absint( $offer['amortization_months'] ?? 0 );
        $balloon = absint( $offer['balloon_months'] ?? 0 );

        $summary = sprintf(
            'Proposed purchase price of %1$s with %2$s due at closing and %3$s financed by the seller.',
            self::money( $offer['purchase_price'] ?? 0 ),
            self::money( $offer['down_payment'] ?? 0 ),
            self::money( $principal )
        );

        $payment = sprintf(
            'Seller-financed principal: %1$s. Interest rate: %2$s%%. Amortization: %3$s. Estimated monthly principal-and-interest payment: %4$s.',
            self::money( $principal ),
            number_format_i18n( (float) ( $offer['interest_rate'] ?? 0 ), 3 ),
            $amortization ? sprintf( _n( '%d month', '%d months', $amortization, 'algq-offer-generator' ), $amortization ) : __( 'to be agreed', 'algq-offer-generator' ),
            self::money( $offer['monthly_payment'] ?? 0 )
        );

        $maturity = $balloon
            ? sprintf( 'A balloon payment is contemplated after %1$d months. The modeled remaining principal balance at that point is approximately %2$s.', $balloon, self::money( $offer['balloon_balance'] ?? 0 ) )
            : __( 'No balloon term is stated in the approved underwriting scenario.', 'algq-offer-generator' );

        return array(
            'transaction_summary' => $summary,
            'payment_terms'       => $payment,
            'maturity'            => $maturity,
            'servicing'           => __( 'The parties may require independent third-party loan servicing, payment records, and tax/insurance escrow arrangements as agreed in final transaction documents.', 'algq-offer-generator' ),
            'security'            => __( 'Final seller-financing documents are expected to include transaction-specific promissory-note and mortgage/security-instrument terms prepared or reviewed by qualified counsel and completed through the closing professional.', 'algq-offer-generator' ),
            'legal_status'        => __( 'This proposal summarizes business terms only. Unless expressly replaced by a fully executed binding agreement, it is not intended to constitute the promissory note, mortgage, deed, legal opinion, tax advice, or final closing instrument.', 'algq-offer-generator' ),
        );
    }

    private static function money( $value ): string {
        return '$' . number_format_i18n( (float) $value, 2 );
    }
}
