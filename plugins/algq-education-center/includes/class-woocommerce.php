<?php
if (!defined('ABSPATH')) { exit; }

class ALGQ_Education_WooCommerce {
    public static function init() {
        add_filter('algq_education_product_button', array(__CLASS__, 'product_button'), 10, 3);
        add_filter('algq_education_has_product_access', array(__CLASS__, 'has_product_access'), 10, 3);
    }

    public static function enabled() {
        $options = get_option('algq_education_options', array());
        return !empty($options['enable_woocommerce']) && class_exists('WooCommerce');
    }

    public static function product_id_for_post($post_id) {
        $post_id = absint($post_id);
        if (!$post_id) { return 0; }
        $product_id = absint(get_post_meta($post_id, 'algq_course_product_id', true));
        if (!$product_id) { $product_id = absint(get_post_meta($post_id, 'algq_guide_product_id', true)); }
        return $product_id;
    }

    public static function has_product_access($allowed, $user_id, $product_id) {
        $user_id = absint($user_id);
        $product_id = absint($product_id);
        if (!$product_id) { return true; }
        if (!$user_id) { return false; }
        if (current_user_can('manage_options')) { return true; }
        if (!function_exists('wc_customer_bought_product')) { return false; }
        $user = get_userdata($user_id);
        if (!$user || empty($user->user_email)) { return false; }
        return wc_customer_bought_product($user->user_email, $user_id, $product_id);
    }

    public static function user_has_access_to_post($post_id, $user_id = 0) {
        $product_id = self::product_id_for_post($post_id);
        if (!$product_id) { return true; }
        return self::has_product_access(false, $user_id ? $user_id : get_current_user_id(), $product_id);
    }

    public static function product_button($html, $product_id, $label = '') {
        $product_id = absint($product_id);
        if (!$product_id || !function_exists('wc_get_product')) { return ''; }
        $product = wc_get_product($product_id);
        if (!$product) { return ''; }
        $label = $label ? sanitize_text_field($label) : __('Get Access', 'algq-education-center');
        $url = $product->is_purchasable() ? $product->add_to_cart_url() : get_permalink($product_id);
        ob_start();
        echo '<a class="algq-btn algq-btn-gold" href="' . esc_url($url) . '">' . esc_html($label) . '</a>';
        return ob_get_clean();
    }

    public static function render_access_notice($post_id) {
        $product_id = self::product_id_for_post($post_id);
        if (!$product_id) { return ''; }
        ob_start();
        echo '<div class="algq-edu-access-notice">';
        echo '<strong>' . esc_html__('Premium education item', 'algq-education-center') . '</strong>';
        echo '<p>' . esc_html__('Purchase or approved access is required to view this content.', 'algq-education-center') . '</p>';
        echo apply_filters('algq_education_product_button', '', $product_id, __('Purchase Access', 'algq-education-center'));
        echo '</div>';
        return ob_get_clean();
    }

    public static function linked_products($limit = 12) {
        $limit = absint($limit) ?: 12;
        $items = get_posts(array(
            'post_type' => array('algq_course', 'algq_guide'),
            'post_status' => 'publish',
            'posts_per_page' => $limit,
            'meta_query' => array(
                'relation' => 'OR',
                array('key' => 'algq_course_product_id', 'value' => 0, 'compare' => '>', 'type' => 'NUMERIC'),
                array('key' => 'algq_guide_product_id', 'value' => 0, 'compare' => '>', 'type' => 'NUMERIC'),
            ),
        ));
        return $items;
    }
}
