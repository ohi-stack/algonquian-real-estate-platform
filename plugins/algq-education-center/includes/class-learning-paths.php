<?php
if (!defined('ABSPATH')) { exit; }

class ALGQ_Education_Learning_Paths {
    public static function init() {
        add_action('init', array(__CLASS__, 'register_post_types'));
        add_shortcode('algq_learning_path', array(__CLASS__, 'render_learning_path'));
        add_shortcode('algq_certification_program', array(__CLASS__, 'render_certification_program'));
    }

    public static function register_post_types() {
        register_post_type('algq_learning_path', array('labels'=>array('name'=>__('Learning Paths','algq-education-center'),'singular_name'=>__('Learning Path','algq-education-center')),'public'=>false,'show_ui'=>true,'show_in_menu'=>'algq-education','supports'=>array('title','editor','thumbnail','revisions'),'rewrite'=>false));
        register_post_type('algq_cert_program', array('labels'=>array('name'=>__('Certification Programs','algq-education-center'),'singular_name'=>__('Certification Program','algq-education-center')),'public'=>false,'show_ui'=>true,'show_in_menu'=>'algq-education','supports'=>array('title','editor','thumbnail','revisions'),'rewrite'=>false));
    }

    public static function course_ids($post_id) {
        $raw = get_post_meta(absint($post_id), 'algq_path_course_ids', true);
        return array_filter(array_map('absint', explode(',', (string) $raw)));
    }

    public static function path_percentage($user_id, $path_id) {
        $courses = self::course_ids($path_id);
        if (!$courses || !class_exists('ALGQ_Education_Progress')) { return 0; }
        $total = count($courses); $complete = 0;
        foreach ($courses as $course_id) { if (ALGQ_Education_Progress::course_percentage($user_id, $course_id) >= 100) { $complete++; } }
        return (int) round(($complete / $total) * 100);
    }

    public static function render_learning_path($atts = array()) {
        $atts = shortcode_atts(array('id'=>0), $atts, 'algq_learning_path');
        return self::render_path(absint($atts['id']), false);
    }

    public static function render_certification_program($atts = array()) {
        $atts = shortcode_atts(array('id'=>0), $atts, 'algq_certification_program');
        return self::render_path(absint($atts['id']), true);
    }

    private static function render_path($path_id, $certification) {
        $expected_type = $certification ? 'algq_cert_program' : 'algq_learning_path';
        if (!$path_id || $expected_type !== get_post_type($path_id)) { return '<div class="algq-edu-notice">' . esc_html__('Learning path not found.', 'algq-education-center') . '</div>'; }
        $courses = self::course_ids($path_id);
        $percent = is_user_logged_in() ? self::path_percentage(get_current_user_id(), $path_id) : 0;
        ob_start();
        echo '<section class="algq-edu algq-learning-path"><header class="algq-section-header"><p class="algq-kicker">' . esc_html($certification ? __('Certification Program','algq-education-center') : __('Learning Path','algq-education-center')) . '</p><h1>' . esc_html(get_the_title($path_id)) . '</h1><p>' . esc_html(wp_trim_words(wp_strip_all_tags(get_post_field('post_content', $path_id)), 32)) . '</p></header><div class="algq-progress"><span style="width:' . esc_attr((string) $percent) . '%"></span></div><p>' . esc_html(sprintf(__('%d%% complete','algq-education-center'), $percent)) . '</p><div class="algq-card-grid">';
        foreach ($courses as $course_id) { echo '<article class="algq-card"><span class="algq-badge">' . esc_html__('Course','algq-education-center') . '</span><h2>' . esc_html(get_the_title($course_id)) . '</h2></article>'; }
        if (!$courses) { echo '<article class="algq-card"><h2>' . esc_html__('No courses assigned.', 'algq-education-center') . '</h2></article>'; }
        echo '</div></section>';
        return ob_get_clean();
    }
}
