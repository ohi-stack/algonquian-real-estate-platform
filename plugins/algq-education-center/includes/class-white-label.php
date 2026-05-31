<?php
if (!defined('ABSPATH')) { exit; }

class ALGQ_Education_White_Label {
    public static function init() {
        add_shortcode('algq_white_label_portal', array(__CLASS__, 'render_portal'));
        add_filter('body_class', array(__CLASS__, 'body_class'));
    }

    public static function settings($portal = 'default') {
        $portal = sanitize_key($portal);
        $all = get_option('algq_white_label_portals', array());
        $defaults = array(
            'name' => 'Algonquian Education Center',
            'logo_url' => '',
            'primary_color' => '#071a33',
            'accent_color' => '#c9a34a',
            'course_ids' => '',
        );
        return isset($all[$portal]) && is_array($all[$portal]) ? wp_parse_args($all[$portal], $defaults) : $defaults;
    }

    public static function body_class($classes) {
        if (is_page()) { $classes[] = 'algq-white-label-ready'; }
        return $classes;
    }

    public static function course_ids($portal = 'default') {
        $settings = self::settings($portal);
        return array_filter(array_map('absint', explode(',', (string) $settings['course_ids'])));
    }

    public static function render_portal($atts = array()) {
        $atts = shortcode_atts(array('portal'=>'default'), $atts, 'algq_white_label_portal');
        $portal = sanitize_key($atts['portal']);
        $settings = self::settings($portal);
        $course_ids = self::course_ids($portal);
        ob_start();
        echo '<section class="algq-edu algq-white-label-portal" style="--algq-blue:' . esc_attr($settings['primary_color']) . ';--algq-gold:' . esc_attr($settings['accent_color']) . '">';
        echo '<header class="algq-section-header">';
        if (!empty($settings['logo_url'])) { echo '<img src="' . esc_url($settings['logo_url']) . '" alt="" style="max-width:180px;height:auto;margin-bottom:18px">'; }
        echo '<p class="algq-kicker">' . esc_html__('Training Portal', 'algq-education-center') . '</p><h1>' . esc_html($settings['name']) . '</h1><p>' . esc_html__('A branded learning portal powered by Algonquian Education Center.', 'algq-education-center') . '</p></header><div class="algq-card-grid">';
        if ($course_ids) {
            foreach ($course_ids as $course_id) {
                echo '<article class="algq-card"><span class="algq-badge">' . esc_html__('Course', 'algq-education-center') . '</span><h2>' . esc_html(get_the_title($course_id)) . '</h2><p>' . esc_html(wp_trim_words(wp_strip_all_tags(get_post_field('post_content', $course_id)), 24)) . '</p></article>';
            }
        } else {
            echo '<article class="algq-card"><h2>' . esc_html__('No portal courses assigned.', 'algq-education-center') . '</h2><p>' . esc_html__('Configure course IDs for this white-label portal.', 'algq-education-center') . '</p></article>';
        }
        echo '</div></section>';
        return ob_get_clean();
    }
}
