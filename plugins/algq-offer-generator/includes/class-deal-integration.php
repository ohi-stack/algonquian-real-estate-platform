<?php
if (!defined('ABSPATH')) { exit; }

class ALGQ_Offer_Deal_Integration {
    public static function init() {
        add_action('save_post_algq_offer', array(__CLASS__, 'sync_offer_to_deal'), 20, 3);
    }

    public static function sync_offer_to_deal($post_id, $post, $update) {
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) { return; }
        $deal_id = absint(get_post_meta($post_id, '_algq_deal_id', true));
        if (!$deal_id) { return; }
        update_post_meta($deal_id, '_algq_latest_offer_id', $post_id);
        update_post_meta($deal_id, '_algq_latest_offer_status', get_post_status($post_id));
        do_action('algq_offer_synced_to_deal', $post_id, $deal_id);
    }

    public static function get_deal_summary($deal_id) {
        $deal_id = absint($deal_id);
        if (!$deal_id) { return array(); }
        return array(
            'id' => $deal_id,
            'title' => get_the_title($deal_id),
            'address' => get_post_meta($deal_id, '_algq_property_address', true),
            'seller' => get_post_meta($deal_id, '_algq_seller_name', true),
            'asking_price' => get_post_meta($deal_id, '_algq_asking_price', true),
        );
    }
}
