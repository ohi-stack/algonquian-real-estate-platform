<?php

defined( 'ABSPATH' ) || exit;

final class ALGQ_Offer_Admin {
    public static function init(): void {
        add_action( 'admin_menu', array( __CLASS__, 'menu' ) );
        add_action( 'admin_post_algq_offer_create_seller_financing', array( __CLASS__, 'create_seller_financing' ) );
    }

    public static function menu(): void {
        add_menu_page( __( 'ARE Offer Generator', 'algq-offer-generator' ), __( 'ARE Offers', 'algq-offer-generator' ), 'manage_algq_offers', 'algq-offer-generator', array( __CLASS__, 'render_dashboard' ), 'dashicons-media-document', 32 );
        add_submenu_page( 'algq-offer-generator', __( 'All Offers', 'algq-offer-generator' ), __( 'All Offers', 'algq-offer-generator' ), 'view_algq_offer_history', 'edit.php?post_type=algq_offer' );
        add_submenu_page( 'algq-offer-generator', __( 'Seller Financing Proposals', 'algq-offer-generator' ), __( 'Seller Financing', 'algq-offer-generator' ), 'create_algq_offers', 'algq-offer-seller-financing', array( __CLASS__, 'render_seller_financing' ) );
        add_submenu_page( 'algq-offer-generator', __( 'Offer Templates', 'algq-offer-generator' ), __( 'Templates', 'algq-offer-generator' ), 'manage_algq_offer_templates', 'edit.php?post_type=algq_offer_template' );
    }

    public static function render_dashboard(): void {
        if ( ! current_user_can( 'manage_algq_offers' ) ) { wp_die( esc_html__( 'You are not authorized to manage offers.', 'algq-offer-generator' ) ); }
        $offers = wp_count_posts( 'algq_offer' );
        $templates = wp_count_posts( 'algq_offer_template' );
        $recent = get_posts( array( 'post_type' => 'algq_offer', 'post_status' => array( 'publish', 'draft', 'pending', 'private' ), 'posts_per_page' => 5, 'orderby' => 'modified', 'order' => 'DESC' ) );
        $seller_financing_count = (int) ( new WP_Query( array( 'post_type' => 'algq_offer', 'post_status' => 'any', 'posts_per_page' => 1, 'fields' => 'ids', 'meta_key' => '_algq_offer_strategy', 'meta_value' => 'seller_financing' ) ) )->found_posts;
        ?>
        <div class="wrap algq-ui algq-offer-admin">
            <section class="algq-admin-hero"><p class="algq-kicker"><?php esc_html_e( 'Algonquian Real Estate', 'algq-offer-generator' ); ?></p><h1><?php esc_html_e( 'Offer Generator Command Panel', 'algq-offer-generator' ); ?></h1><p><?php esc_html_e( 'Control proposal creation, review, approval, versioning, document generation, and downstream execution.', 'algq-offer-generator' ); ?></p></section>
            <div class="algq-grid algq-grid-4">
                <div class="algq-stat"><strong><?php echo esc_html( (string) ( $offers->draft ?? 0 ) ); ?></strong><span><?php esc_html_e( 'Draft Proposals', 'algq-offer-generator' ); ?></span></div>
                <div class="algq-stat"><strong><?php echo esc_html( (string) $seller_financing_count ); ?></strong><span><?php esc_html_e( 'Seller Financing', 'algq-offer-generator' ); ?></span></div>
                <div class="algq-stat"><strong><?php echo esc_html( (string) ( $templates->publish ?? 0 ) ); ?></strong><span><?php esc_html_e( 'Templates', 'algq-offer-generator' ); ?></span></div>
                <div class="algq-stat"><strong><?php echo esc_html( ALGQ_OFFER_VERSION ); ?></strong><span><?php esc_html_e( 'Version', 'algq-offer-generator' ); ?></span></div>
            </div>
            <p><a class="button button-primary" href="<?php echo esc_url( admin_url( 'admin.php?page=algq-offer-seller-financing' ) ); ?>"><?php esc_html_e( 'Create Seller Financing Proposal', 'algq-offer-generator' ); ?></a> <a class="button" href="<?php echo esc_url( admin_url( 'post-new.php?post_type=algq_offer' ) ); ?>"><?php esc_html_e( 'Create Other Offer', 'algq-offer-generator' ); ?></a> <a class="button" href="<?php echo esc_url( admin_url( 'edit.php?post_type=algq_offer' ) ); ?>"><?php esc_html_e( 'Review All Offers', 'algq-offer-generator' ); ?></a></p>
            <h2><?php esc_html_e( 'Recently Modified', 'algq-offer-generator' ); ?></h2>
            <ul><?php foreach ( $recent as $offer ) : ?><li><a href="<?php echo esc_url( get_edit_post_link( $offer->ID, 'raw' ) ); ?>"><?php echo esc_html( get_the_title( $offer ) ); ?></a> — <?php echo esc_html( (string) get_post_meta( $offer->ID, '_algq_offer_offer_status', true ) ); ?></li><?php endforeach; ?></ul>
        </div>
        <?php
    }

    public static function render_seller_financing(): void {
        if ( ! current_user_can( 'create_algq_offers' ) && ! current_user_can( 'manage_algq_offers' ) ) { wp_die( esc_html__( 'You are not authorized to create proposals.', 'algq-offer-generator' ) ); }
        ?>
        <div class="wrap algq-ui algq-offer-admin">
            <section class="algq-admin-hero"><p class="algq-kicker"><?php esc_html_e( 'Seller Financing', 'algq-offer-generator' ); ?></p><h1><?php esc_html_e( 'Create Proposal From Approved Underwriting', 'algq-offer-generator' ); ?></h1><p><?php esc_html_e( 'Enter the canonical Pipeline CRM deal ID. The Offer Generator will import and lock the approved MAO seller-financing economics, then create a versioned proposal record for review.', 'algq-offer-generator' ); ?></p></section>
            <?php if ( isset( $_GET['algq_offer_error'] ) ) : ?><div class="notice notice-error"><p><?php echo esc_html( sanitize_text_field( wp_unslash( $_GET['algq_offer_error'] ) ) ); ?></p></div><?php endif; ?>
            <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
                <input type="hidden" name="action" value="algq_offer_create_seller_financing" />
                <?php wp_nonce_field( 'algq_offer_create_seller_financing', 'algq_offer_nonce' ); ?>
                <table class="form-table" role="presentation">
                    <tr><th><label for="deal_id"><?php esc_html_e( 'Deal ID', 'algq-offer-generator' ); ?></label></th><td><input id="deal_id" name="deal_id" type="number" min="1" required class="regular-text" /></td></tr>
                    <tr><th><label for="proposal_type"><?php esc_html_e( 'Document Type', 'algq-offer-generator' ); ?></label></th><td><select id="proposal_type" name="proposal_type"><option value="proposal">Seller Financing Proposal</option><option value="term_sheet">Term Sheet</option><option value="loi">Letter of Intent</option><option value="offer">Approved Offer Draft</option></select></td></tr>
                    <tr><th><label for="closing_date"><?php esc_html_e( 'Proposed Closing Date', 'algq-offer-generator' ); ?></label></th><td><input id="closing_date" name="closing_date" type="date" /></td></tr>
                    <tr><th><label for="contingencies"><?php esc_html_e( 'Contingencies', 'algq-offer-generator' ); ?></label></th><td><textarea id="contingencies" name="contingencies" rows="5" class="large-text"></textarea></td></tr>
                    <tr><th><label for="terms"><?php esc_html_e( 'Additional Business Terms', 'algq-offer-generator' ); ?></label></th><td><textarea id="terms" name="terms" rows="7" class="large-text"></textarea><p class="description"><?php esc_html_e( 'Do not restate or alter MAO-owned purchase price, principal, rate, amortization, balloon, or payment economics here. Those fields are imported from the approved underwriting record.', 'algq-offer-generator' ); ?></p></td></tr>
                </table>
                <?php submit_button( __( 'Create Draft Proposal', 'algq-offer-generator' ) ); ?>
            </form>
        </div>
        <?php
    }

    public static function create_seller_financing(): void {
        if ( ! current_user_can( 'create_algq_offers' ) && ! current_user_can( 'manage_algq_offers' ) ) { wp_die( esc_html__( 'You are not authorized to create proposals.', 'algq-offer-generator' ), '', array( 'response' => 403 ) ); }
        check_admin_referer( 'algq_offer_create_seller_financing', 'algq_offer_nonce' );
        $deal_id = isset( $_POST['deal_id'] ) ? absint( $_POST['deal_id'] ) : 0;
        $proposal_type = isset( $_POST['proposal_type'] ) ? sanitize_key( wp_unslash( $_POST['proposal_type'] ) ) : 'proposal';
        $overrides = array(
            'closing_date' => isset( $_POST['closing_date'] ) ? sanitize_text_field( wp_unslash( $_POST['closing_date'] ) ) : '',
            'contingencies' => isset( $_POST['contingencies'] ) ? sanitize_textarea_field( wp_unslash( $_POST['contingencies'] ) ) : '',
            'terms' => isset( $_POST['terms'] ) ? sanitize_textarea_field( wp_unslash( $_POST['terms'] ) ) : '',
        );
        $result = ALGQ_Offer_Service::create_from_approved_underwriting( $deal_id, $proposal_type, $overrides, get_current_user_id() );
        if ( is_wp_error( $result ) ) {
            wp_safe_redirect( add_query_arg( 'algq_offer_error', rawurlencode( $result->get_error_message() ), admin_url( 'admin.php?page=algq-offer-seller-financing' ) ) );
            exit;
        }
        wp_safe_redirect( get_edit_post_link( absint( $result ), 'raw' ) ?: admin_url( 'post.php?post=' . absint( $result ) . '&action=edit' ) );
        exit;
    }
}
