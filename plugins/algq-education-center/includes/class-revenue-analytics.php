<?php
if (!defined('ABSPATH')) { exit; }

class ALGQ_Education_Revenue_Analytics {
    public static function init() {
        add_shortcode('algq_lms_revenue_analytics', array(__CLASS__, 'render'));
    }

    public static function summary() {
        $items = class_exists('ALGQ_Education_WooCommerce') ? ALGQ_Education_WooCommerce::linked_products(100) : array();
        $linked_products = array();
        foreach ($items as $item) {
            $product_id = ALGQ_Education_WooCommerce::product_id_for_post($item->ID);
            if ($product_id) { $linked_products[$product_id] = $product_id; }
        }
        $revenue = 0.0;
        $orders = 0;
        if (function_exists('wc_get_orders') && $linked_products) {
            $wc_orders = wc_get_orders(array('limit'=>100,'status'=>array('wc-completed','wc-processing')));
            foreach ($wc_orders as $order) {
                foreach ($order->get_items() as $order_item) {
                    if (in_array(absint($order_item->get_product_id()), $linked_products, true)) {
                        $orders++;
                        $revenue += (float) $order_item->get_total();
                    }
                }
            }
        }
        return array('linked_products'=>count($linked_products),'orders'=>$orders,'revenue'=>$revenue);
    }

    public static function render($atts = array()) {
        if (!current_user_can('manage_options')) {
            return '<div class="algq-edu-notice">' . esc_html__('Administrator access required.', 'algq-education-center') . '</div>';
        }
        $summary = self::summary();
        ob_start();
        echo '<section class="algq-edu algq-revenue-analytics"><header class="algq-section-header"><p class="algq-kicker">' . esc_html__('Revenue Analytics', 'algq-education-center') . '</p><h1>' . esc_html__('LMS Revenue Analytics', 'algq-education-center') . '</h1><p>' . esc_html__('WooCommerce-linked education product performance.', 'algq-education-center') . '</p></header><div class="algq-stat-grid">';
        echo '<div class="algq-stat"><strong>' . esc_html((string) $summary['linked_products']) . '</strong><span>' . esc_html__('Linked Products', 'algq-education-center') . '</span></div>';
        echo '<div class="algq-stat"><strong>' . esc_html((string) $summary['orders']) . '</strong><span>' . esc_html__('Orders', 'algq-education-center') . '</span></div>';
        echo '<div class="algq-stat"><strong>' . esc_html(wp_strip_all_tags(wc_price($summary['revenue']))) . '</strong><span>' . esc_html__('Revenue', 'algq-education-center') . '</span></div>';
        echo '</div></section>';
        return ob_get_clean();
    }
}
