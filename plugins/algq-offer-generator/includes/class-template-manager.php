<?php
if (!defined('ABSPATH')) { exit; }

class ALGQ_Offer_Template_Manager {
    public static function init() {
        add_action('add_meta_boxes', array(__CLASS__, 'add_template_metabox'));
        add_action('save_post_algq_offer_template', array(__CLASS__, 'save_template_meta'));
    }

    public static function add_template_metabox() {
        add_meta_box('algq_offer_template_fields', __('Template Settings', 'algq-offer-generator'), array(__CLASS__, 'render_template_metabox'), 'algq_offer_template', 'normal', 'high');
    }

    public static function render_template_metabox($post) {
        wp_nonce_field('algq_offer_template_meta', 'algq_offer_template_nonce');
        $type = get_post_meta($post->ID, '_algq_template_type', true);
        $merge = get_post_meta($post->ID, '_algq_merge_fields', true);
        echo '<p><label><strong>' . esc_html__('Template Type', 'algq-offer-generator') . '</strong></label></p>';
        echo '<input type="text" class="widefat" name="algq_template_type" value="' . esc_attr($type) . '" placeholder="purchase_agreement, loi, seller_financing, subject_to" />';
        echo '<p><label><strong>' . esc_html__('Merge Fields', 'algq-offer-generator') . '</strong></label></p>';
        echo '<textarea class="widefat" rows="6" name="algq_merge_fields" placeholder="{{seller_name}}, {{property_address}}, {{purchase_price}}">' . esc_textarea($merge) . '</textarea>';
    }

    public static function save_template_meta($post_id) {
        if (!isset($_POST['algq_offer_template_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['algq_offer_template_nonce'])), 'algq_offer_template_meta')) { return; }
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) { return; }
        if (!current_user_can('edit_post', $post_id)) { return; }
        update_post_meta($post_id, '_algq_template_type', isset($_POST['algq_template_type']) ? sanitize_key(wp_unslash($_POST['algq_template_type'])) : '');
        update_post_meta($post_id, '_algq_merge_fields', isset($_POST['algq_merge_fields']) ? sanitize_textarea_field(wp_unslash($_POST['algq_merge_fields'])) : '');
    }
}
