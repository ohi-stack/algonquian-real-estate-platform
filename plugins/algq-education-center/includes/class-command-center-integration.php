<?php
if (!defined('ABSPATH')) { exit; }

class ALGQ_Education_Command_Center_Integration {
    public static function init() {
        add_filter('algq_command_center_widgets', array(__CLASS__, 'register_widget'));
        add_shortcode('algq_education_command_center_widget', array(__CLASS__, 'render_widget'));
    }

    public static function register_widget($widgets) {
        $widgets['education_center'] = array(
            'title' => __('Education Center', 'algq-education-center'),
            'callback' => array(__CLASS__, 'render_widget'),
            'capability' => 'manage_options',
        );
        return $widgets;
    }

    public static function metrics() {
        $courses = wp_count_posts('algq_course');
        $lessons = wp_count_posts('algq_lesson');
        $guides = wp_count_posts('algq_guide');
        $certs = wp_count_posts('algq_certificate');
        $revenue = class_exists('ALGQ_Education_Revenue_Analytics') ? ALGQ_Education_Revenue_Analytics::summary() : array('linked_products'=>0,'orders'=>0,'revenue'=>0);
        return array(
            'courses' => absint($courses->publish ?? 0),
            'lessons' => absint($lessons->publish ?? 0),
            'guides' => absint($guides->publish ?? 0),
            'certificates' => absint($certs->publish ?? 0),
            'linked_products' => absint($revenue['linked_products'] ?? 0),
            'orders' => absint($revenue['orders'] ?? 0),
            'revenue' => (float) ($revenue['revenue'] ?? 0),
        );
    }

    public static function render_widget($atts = array()) {
        if (!current_user_can('manage_options')) {
            return '<div class="algq-edu-notice">' . esc_html__('Administrator access required.', 'algq-education-center') . '</div>';
        }
        $m = self::metrics();
        ob_start();
        echo '<section class="algq-edu algq-command-education"><header class="algq-section-header"><p class="algq-kicker">' . esc_html__('Command Center', 'algq-education-center') . '</p><h1>' . esc_html__('Education Center Metrics', 'algq-education-center') . '</h1><p>' . esc_html__('Executive LMS, certification, and revenue reporting for Algonquian Real Estate.', 'algq-education-center') . '</p></header><div class="algq-stat-grid">';
        foreach (array('courses'=>'Courses','lessons'=>'Lessons','guides'=>'Guides','certificates'=>'Certificates','orders'=>'Orders') as $key => $label) {
            echo '<div class="algq-stat"><strong>' . esc_html((string) $m[$key]) . '</strong><span>' . esc_html__($label, 'algq-education-center') . '</span></div>';
        }
        echo '<div class="algq-stat"><strong>' . esc_html(function_exists('wc_price') ? wp_strip_all_tags(wc_price($m['revenue'])) : number_format_i18n($m['revenue'], 2)) . '</strong><span>' . esc_html__('Education Revenue', 'algq-education-center') . '</span></div>';
        echo '</div></section>';
        return ob_get_clean();
    }
}
