<?php
if (!defined('ABSPATH')) { exit; }

class ALGQ_Offer_Admin {
    public static function init() {
        add_action('admin_menu', array(__CLASS__, 'menu'));
    }

    public static function menu() {
        add_menu_page(
            __('ARE Offer Generator', 'algq-offer-generator'),
            __('ARE Offers', 'algq-offer-generator'),
            'edit_posts',
            'algq-offer-generator',
            array(__CLASS__, 'render_dashboard'),
            'dashicons-media-document',
            32
        );
    }

    public static function render_dashboard() {
        if (!current_user_can('edit_posts')) { return; }
        $offers = wp_count_posts('algq_offer');
        $templates = wp_count_posts('algq_offer_template');
        ?>
        <div class="wrap algq-ui algq-offer-admin">
            <section class="algq-admin-hero">
                <p class="algq-kicker"><?php esc_html_e('Algonquian Real Estate', 'algq-offer-generator'); ?></p>
                <h1><?php esc_html_e('Offer Generator Command Panel', 'algq-offer-generator'); ?></h1>
                <p><?php esc_html_e('Generate acquisition offers, seller-financing proposals, cash summaries, and transaction-ready documents.', 'algq-offer-generator'); ?></p>
            </section>
            <div class="algq-grid algq-grid-4">
                <div class="algq-stat"><strong><?php echo esc_html(isset($offers->publish) ? $offers->publish : 0); ?></strong><span><?php esc_html_e('Published Offers', 'algq-offer-generator'); ?></span></div>
                <div class="algq-stat"><strong><?php echo esc_html(isset($offers->draft) ? $offers->draft : 0); ?></strong><span><?php esc_html_e('Draft Offers', 'algq-offer-generator'); ?></span></div>
                <div class="algq-stat"><strong><?php echo esc_html(isset($templates->publish) ? $templates->publish : 0); ?></strong><span><?php esc_html_e('Templates', 'algq-offer-generator'); ?></span></div>
                <div class="algq-stat"><strong>1.0.0</strong><span><?php esc_html_e('Version', 'algq-offer-generator'); ?></span></div>
            </div>
        </div>
        <?php
    }
}
