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

        $html     = self::render_offer_html( $offer_id );
        $hash     = hash( 'sha256', $html );
        $metadata = array(
            'offer_id'     => $offer_id,
            'offer_number' => get_post_meta( $offer_id, '_algq_offer_offer_number', true ),
            'version'      => absint( get_post_meta( $offer_id, '_algq_offer_version_number', true ) ?: 1 ),
            'hash'         => $hash,
            'generated_by' => get_current_user_id(),
            'generated_at' => current_time( 'mysql' ),
        );

        update_post_meta( $offer_id, '_algq_offer_document_html', wp_kses_post( $html ) );
        update_post_meta( $offer_id, '_algq_offer_document_hash', $hash );
        update_post_meta( $offer_id, '_algq_offer_document_metadata', $metadata );

        $document_id = apply_filters( 'algq_document_library_create_from_offer', 0, $offer_id, $html, $metadata );
        if ( $document_id ) {
            update_post_meta( $offer_id, '_algq_offer_document_id', absint( $document_id ) );
        }

        do_action( 'algq_offer_document_generated', $offer_id, get_current_user_id(), $metadata );
        wp_send_json_success( array( 'html' => $html, 'hash' => $hash, 'documentId' => absint( $document_id ) ) );
    }

    public static function render_offer_html( int $offer_id ): string {
        $offer        = ALGQ_Offer_Service::get( $offer_id );
        $company_name = get_option( 'algq_offer_company_name', 'Algonquian Real Estate LLC' );
        $strategy     = ucwords( str_replace( '_', ' ', (string) $offer['strategy'] ) );

        ob_start();
        ?>
        <article class="algq-offer-document" data-offer-id="<?php echo esc_attr( (string) $offer_id ); ?>" data-offer-version="<?php echo esc_attr( (string) ( $offer['version_number'] ?: 1 ) ); ?>">
            <header><p><?php echo esc_html( $company_name ); ?></p><h1><?php echo esc_html( $strategy ?: __( 'Acquisition Offer', 'algq-offer-generator' ) ); ?></h1><p><?php echo esc_html( (string) $offer['offer_number'] ); ?></p></header>
            <dl>
                <dt><?php esc_html_e( 'Property', 'algq-offer-generator' ); ?></dt><dd><?php echo esc_html( (string) $offer['property_address'] ); ?></dd>
                <dt><?php esc_html_e( 'Purchase Price', 'algq-offer-generator' ); ?></dt><dd><?php echo esc_html( '$' . number_format_i18n( (float) $offer['purchase_price'], 2 ) ); ?></dd>
                <dt><?php esc_html_e( 'Down Payment', 'algq-offer-generator' ); ?></dt><dd><?php echo esc_html( '$' . number_format_i18n( (float) $offer['down_payment'], 2 ) ); ?></dd>
                <dt><?php esc_html_e( 'Monthly Payment', 'algq-offer-generator' ); ?></dt><dd><?php echo esc_html( '$' . number_format_i18n( (float) $offer['monthly_payment'], 2 ) ); ?></dd>
                <dt><?php esc_html_e( 'Interest Rate', 'algq-offer-generator' ); ?></dt><dd><?php echo esc_html( (string) $offer['interest_rate'] . '%' ); ?></dd>
                <dt><?php esc_html_e( 'Closing Date', 'algq-offer-generator' ); ?></dt><dd><?php echo esc_html( (string) $offer['closing_date'] ); ?></dd>
            </dl>
            <section><h2><?php esc_html_e( 'Contingencies', 'algq-offer-generator' ); ?></h2><?php echo wpautop( esc_html( (string) $offer['contingencies'] ) ); ?></section>
            <section><h2><?php esc_html_e( 'Additional Terms', 'algq-offer-generator' ); ?></h2><?php echo wpautop( esc_html( (string) ( $offer['terms'] ?: $offer['notes'] ) ) ); ?></section>
            <footer><p><?php esc_html_e( 'This is a business proposal and must be reviewed for transaction-specific legal sufficiency before execution. It is not legal advice.', 'algq-offer-generator' ); ?></p></footer>
        </article>
        <?php
        return (string) ob_get_clean();
    }
}
