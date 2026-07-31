<?php

defined( 'ABSPATH' ) || exit;

final class ALGQ_Offer_Admin {
    public static function init(): void {
        add_action( 'admin_menu', array( __CLASS__, 'menu' ) );
    }

    public static function menu(): void {
        add_menu_page( __( 'ARE Offer Generator', 'algq-offer-generator' ), __( 'ARE Offers', 'algq-offer-generator' ), 'manage_algq_offers', 'algq-offer-generator', array( __CLASS__, 'render_dashboard' ), 'dashicons-media-document', 32 );
        add_submenu_page( 'algq-offer-generator', __( 'All Offers', 'algq-offer-generator' ), __( 'All Offers', 'algq-offer-generator' ), 'view_algq_offer_history', 'edit.php?post_type=algq_offer' );
        add_submenu_page( 'algq-offer-generator', __( 'Offer Templates', 'algq-offer-generator' ), __( 'Templates', 'algq-offer-generator' ), 'manage_algq_offer_templates', 'edit.php?post_type=algq_offer_template' );
    }

    public static function render_dashboard(): void {
        if ( ! current_user_can( 'manage_algq_offers' ) ) {
            wp_die( esc_html__( 'You are not authorized to manage offers.', 'algq-offer-generator' ) );
        }
        $offers    = wp_count_posts( 'algq_offer' );
        $templates = wp_count_posts( 'algq_offer_template' );
        $recent    = get_posts( array( 'post_type' => 'algq_offer', 'post_status' => array( 'publish', 'draft', 'pending', 'private' ), 'posts_per_page' => 5, 'orderby' => 'modified', 'order' => 'DESC' ) );
        ?>
        <div class="wrap algq-ui algq-offer-admin">
            <section class="algq-admin-hero"><p class="algq-kicker"><?php esc_html_e( 'Algonquian Real Estate', 'algq-offer-generator' ); ?></p><h1><?php esc_html_e( 'Offer Generator Command Panel', 'algq-offer-generator' ); ?></h1><p><?php esc_html_e( 'Control offer creation, review, approval, versioning, document generation, and downstream execution.', 'algq-offer-generator' ); ?></p></section>
            <div class="algq-grid algq-grid-4">
                <div class="algq-stat"><strong><?php echo esc_html( (string) ( $offers->draft ?? 0 ) ); ?></strong><span><?php esc_html_e( 'Draft Offers', 'algq-offer-generator' ); ?></span></div>
                <div class="algq-stat"><strong><?php echo esc_html( (string) ( $offers->publish ?? 0 ) ); ?></strong><span><?php esc_html_e( 'Published Records', 'algq-offer-generator' ); ?></span></div>
                <div class="algq-stat"><strong><?php echo esc_html( (string) ( $templates->publish ?? 0 ) ); ?></strong><span><?php esc_html_e( 'Templates', 'algq-offer-generator' ); ?></span></div>
                <div class="algq-stat"><strong><?php echo esc_html( ALGQ_OFFER_VERSION ); ?></strong><span><?php esc_html_e( 'Version', 'algq-offer-generator' ); ?></span></div>
            </div>
            <p><a class="button button-primary" href="<?php echo esc_url( admin_url( 'post-new.php?post_type=algq_offer' ) ); ?>"><?php esc_html_e( 'Create Offer Record', 'algq-offer-generator' ); ?></a> <a class="button" href="<?php echo esc_url( admin_url( 'edit.php?post_type=algq_offer' ) ); ?>"><?php esc_html_e( 'Review All Offers', 'algq-offer-generator' ); ?></a></p>
            <h2><?php esc_html_e( 'Recently Modified', 'algq-offer-generator' ); ?></h2>
            <ul><?php foreach ( $recent as $offer ) : ?><li><a href="<?php echo esc_url( get_edit_post_link( $offer->ID, 'raw' ) ); ?>"><?php echo esc_html( get_the_title( $offer ) ); ?></a> — <?php echo esc_html( (string) get_post_meta( $offer->ID, '_algq_offer_status', true ) ); ?></li><?php endforeach; ?></ul>
        </div>
        <?php
    }
}
