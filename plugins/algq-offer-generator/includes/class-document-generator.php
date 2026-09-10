<?php

defined( 'ABSPATH' ) || exit;

final class ALGQ_Offer_Document_Generator {
    public static function init(): void {
        add_action( 'wp_ajax_algq_generate_offer_document', array( __CLASS__, 'ajax_generate_document' ) );
    }

    public static function ajax_generate_document(): void {
        check_ajax_referer( 'algq_offer_generator', 'nonce' );
        if ( ! current_user_can( 'generate_algq_offer_documents' ) ) {
            wp_send_json_error( array( 'message' => __( 'Permission denied.', 'algq-offer-generator' ) ), 403 );
        }

        $offer_id = isset( $_POST['offer_id'] ) ? absint( $_POST['offer_id'] ) : 0;
        if ( ! $offer_id || 'algq_offer' !== get_post_type( $offer_id ) ) {
            wp_send_json_error( array( 'message' => __( 'Invalid offer.', 'algq-offer-generator' ) ), 400 );
        }

        $html = self::render_offer_html( $offer_id );
        $hash = hash( 'sha256', $html );
        $offer = ALGQ_Offer_Service::get( $offer_id );
        $metadata = array(
            'offer_id' => $offer_id,
            'offer_number' => $offer['offer_number'],
            'proposal_type' => $offer['proposal_type'],
            'strategy' => $offer['strategy'],
            'underwriting_id' => absint( $offer['underwriting_id'] ),
            'version' => absint( $offer['version_number'] ?: 1 ),
            'hash' => $hash,
            'generated_by' => get_current_user_id(),
            'generated_at' => current_time( 'mysql' ),
        );

        update_post_meta( $offer_id, '_algq_offer_document_html', wp_kses_post( $html ) );
        update_post_meta( $offer_id, '_algq_offer_document_hash', $hash );
        update_post_meta( $offer_id, '_algq_offer_document_metadata', $metadata );

        $document_id = apply_filters( 'algq_document_library_create_from_offer', 0, $offer_id, $html, $metadata );
        if ( $document_id ) { update_post_meta( $offer_id, '_algq_offer_document_id', absint( $document_id ) ); }

        do_action( 'algq_offer_document_generated', $offer_id, get_current_user_id(), $metadata );
        wp_send_json_success( array( 'html' => $html, 'hash' => $hash, 'documentId' => absint( $document_id ) ) );
    }

    public static function render_offer_html( int $offer_id ): string {
        $offer = ALGQ_Offer_Service::get( $offer_id );
        $company_name = get_option( 'algq_offer_company_name', 'Algonquian Real Estate LLC' );
        $type_labels = array(
            'proposal' => __( 'Seller Financing Proposal', 'algq-offer-generator' ),
            'term_sheet' => __( 'Seller Financing Term Sheet', 'algq-offer-generator' ),
            'loi' => __( 'Letter of Intent — Seller Financing', 'algq-offer-generator' ),
            'offer' => __( 'Seller Financing Offer', 'algq-offer-generator' ),
        );
        $strategy = ucwords( str_replace( '_', ' ', (string) $offer['strategy'] ) );
        $heading = 'seller_financing' === $offer['strategy']
            ? ( $type_labels[ $offer['proposal_type'] ] ?? $type_labels['proposal'] )
            : ( $strategy ?: __( 'Acquisition Offer', 'algq-offer-generator' ) );
        $sections = 'seller_financing' === $offer['strategy'] && class_exists( 'ALGQ_Offer_Seller_Financing_Proposals' )
            ? ALGQ_Offer_Seller_Financing_Proposals::compose_sections( $offer )
            : array();

        ob_start();
        ?>
        <article class="algq-offer-document" data-offer-id="<?php echo esc_attr( (string) $offer_id ); ?>" data-offer-version="<?php echo esc_attr( (string) ( $offer['version_number'] ?: 1 ) ); ?>">
            <header>
                <p><?php echo esc_html( $company_name ); ?></p>
                <h1><?php echo esc_html( $heading ); ?></h1>
                <p><?php echo esc_html( (string) $offer['offer_number'] ); ?></p>
            </header>

            <dl>
                <dt><?php esc_html_e( 'Property', 'algq-offer-generator' ); ?></dt><dd><?php echo esc_html( (string) $offer['property_address'] ); ?></dd>
                <dt><?php esc_html_e( 'Purchase Price', 'algq-offer-generator' ); ?></dt><dd><?php echo esc_html( '$' . number_format_i18n( (float) $offer['purchase_price'], 2 ) ); ?></dd>
                <dt><?php esc_html_e( 'Down Payment', 'algq-offer-generator' ); ?></dt><dd><?php echo esc_html( '$' . number_format_i18n( (float) $offer['down_payment'], 2 ) ); ?></dd>
                <?php if ( 'seller_financing' === $offer['strategy'] ) : ?>
                    <dt><?php esc_html_e( 'Seller-Financed Principal', 'algq-offer-generator' ); ?></dt><dd><?php echo esc_html( '$' . number_format_i18n( (float) $offer['seller_financed_principal'], 2 ) ); ?></dd>
                    <dt><?php esc_html_e( 'Interest Rate', 'algq-offer-generator' ); ?></dt><dd><?php echo esc_html( number_format_i18n( (float) $offer['interest_rate'], 3 ) . '%' ); ?></dd>
                    <dt><?php esc_html_e( 'Amortization', 'algq-offer-generator' ); ?></dt><dd><?php echo esc_html( absint( $offer['amortization_months'] ) . ' months' ); ?></dd>
                    <dt><?php esc_html_e( 'Monthly Payment', 'algq-offer-generator' ); ?></dt><dd><?php echo esc_html( '$' . number_format_i18n( (float) $offer['monthly_payment'], 2 ) ); ?></dd>
                    <dt><?php esc_html_e( 'Balloon Term', 'algq-offer-generator' ); ?></dt><dd><?php echo esc_html( absint( $offer['balloon_months'] ) ? absint( $offer['balloon_months'] ) . ' months' : 'None stated' ); ?></dd>
                    <?php if ( (float) $offer['balloon_balance'] > 0 ) : ?><dt><?php esc_html_e( 'Modeled Balloon Balance', 'algq-offer-generator' ); ?></dt><dd><?php echo esc_html( '$' . number_format_i18n( (float) $offer['balloon_balance'], 2 ) ); ?></dd><?php endif; ?>
                <?php endif; ?>
                <dt><?php esc_html_e( 'Closing Date', 'algq-offer-generator' ); ?></dt><dd><?php echo esc_html( (string) $offer['closing_date'] ); ?></dd>
            </dl>

            <?php foreach ( $sections as $key => $text ) : ?>
                <section><h2><?php echo esc_html( ucwords( str_replace( '_', ' ', $key ) ) ); ?></h2><p><?php echo esc_html( $text ); ?></p></section>
            <?php endforeach; ?>

            <section><h2><?php esc_html_e( 'Contingencies', 'algq-offer-generator' ); ?></h2><?php echo wpautop( esc_html( (string) $offer['contingencies'] ) ); ?></section>
            <section><h2><?php esc_html_e( 'Additional Terms', 'algq-offer-generator' ); ?></h2><?php echo wpautop( esc_html( (string) ( $offer['terms'] ?: $offer['notes'] ) ) ); ?></section>

            <?php if ( ! empty( $offer['underwriting_id'] ) ) : ?>
                <footer>
                    <p><?php echo esc_html( sprintf( __( 'Proposal economics were imported from approved MAO underwriting scenario #%d and are locked to that approved analytical record.', 'algq-offer-generator' ), absint( $offer['underwriting_id'] ) ) ); ?></p>
                    <p><?php esc_html_e( 'This document presents proposed business terms and must receive transaction-specific legal, title, tax, insurance, and closing review as applicable before execution.', 'algq-offer-generator' ); ?></p>
                </footer>
            <?php else : ?>
                <footer><p><?php esc_html_e( 'This is a business proposal and must be reviewed for transaction-specific legal sufficiency before execution. It is not legal advice.', 'algq-offer-generator' ); ?></p></footer>
            <?php endif; ?>
        </article>
        <?php
        return (string) ob_get_clean();
    }
}
