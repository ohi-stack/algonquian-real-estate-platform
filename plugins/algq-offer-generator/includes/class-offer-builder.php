<?php
if (!defined('ABSPATH')) { exit; }

class ALGQ_Offer_Builder {
    public static function init() {
        add_action('wp_ajax_algq_save_offer_terms', array(__CLASS__, 'save_offer_terms'));
    }

    public static function save_offer_terms() {
        check_ajax_referer('algq_offer_generator', 'nonce');
        if (!current_user_can('edit_posts')) {
            wp_send_json_error(array('message' => __('Permission denied.', 'algq-offer-generator')), 403);
        }

        $offer_id = isset($_POST['offer_id']) ? absint($_POST['offer_id']) : 0;
        if (!$offer_id || get_post_type($offer_id) !== 'algq_offer') {
            wp_send_json_error(array('message' => __('Invalid offer.', 'algq-offer-generator')), 400);
        }

        $fields = array('strategy', 'purchase_price', 'down_payment', 'monthly_payment', 'closing_date', 'contingencies', 'notes');
        foreach ($fields as $field) {
            if (isset($_POST[$field])) {
                update_post_meta($offer_id, '_algq_offer_' . $field, sanitize_textarea_field(wp_unslash($_POST[$field])));
            }
        }

        do_action('algq_offer_terms_saved', $offer_id, get_current_user_id());
        wp_send_json_success(array('message' => __('Offer terms saved.', 'algq-offer-generator')));
    }
}
