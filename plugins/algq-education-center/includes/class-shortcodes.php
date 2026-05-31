<?php
if (!defined('ABSPATH')) {
    exit;
}

class ALGQ_Education_Shortcodes {
    public static function init() {
        add_shortcode('algq_education_home', array(__CLASS__, 'education_home'));
        add_shortcode('algq_course_list', array(__CLASS__, 'course_list'));
        add_shortcode('algq_course', array(__CLASS__, 'course'));
        add_shortcode('algq_lesson', array(__CLASS__, 'lesson'));
        add_shortcode('algq_education_track', array(__CLASS__, 'education_track'));
        add_shortcode('algq_platform_training', array(__CLASS__, 'platform_training'));
        add_shortcode('algq_product_library', array(__CLASS__, 'product_library'));
        add_shortcode('algq_user_progress', array(__CLASS__, 'user_progress'));
    }

    public static function education_home($atts = array()) {
        return self::render('education-home.php', array('atts' => shortcode_atts(array(), $atts, 'algq_education_home')));
    }

    public static function course_list($atts = array()) {
        $atts = shortcode_atts(array('limit' => 12, 'audience' => ''), $atts, 'algq_course_list');
        $args = array(
            'post_type' => 'algq_course',
            'post_status' => 'publish',
            'posts_per_page' => absint($atts['limit']),
            'orderby' => 'menu_order date',
            'order' => 'ASC',
        );
        if (!empty($atts['audience'])) {
            $args['meta_query'] = array(array(
                'key' => 'algq_course_audience',
                'value' => sanitize_text_field($atts['audience']),
                'compare' => 'LIKE',
            ));
        }
        return self::render('course-list.php', array('atts' => $atts, 'courses' => new WP_Query($args)));
    }

    public static function course($atts = array()) {
        $atts = shortcode_atts(array('id' => 0), $atts, 'algq_course');
        $course = get_post(absint($atts['id']));
        if (!$course || 'algq_course' !== $course->post_type) {
            return self::notice(__('Course not found.', 'algq-education-center'));
        }
        return self::render('course-single.php', array('course' => $course));
    }

    public static function lesson($atts = array()) {
        $atts = shortcode_atts(array('id' => 0), $atts, 'algq_lesson');
        $lesson = get_post(absint($atts['id']));
        if (!$lesson || 'algq_lesson' !== $lesson->post_type) {
            return self::notice(__('Lesson not found.', 'algq-education-center'));
        }
        return self::render('lesson-single.php', array('lesson' => $lesson));
    }

    public static function education_track($atts = array()) {
        $atts = shortcode_atts(array('type' => 'public'), $atts, 'algq_education_track');
        $atts['type'] = sanitize_key($atts['type']);
        return self::render('education-track.php', array('atts' => $atts));
    }

    public static function platform_training($atts = array()) {
        return self::render('platform-training.php', array('atts' => shortcode_atts(array(), $atts, 'algq_platform_training')));
    }

    public static function product_library($atts = array()) {
        $atts = shortcode_atts(array('limit' => 12), $atts, 'algq_product_library');
        return self::render('product-library.php', array('atts' => $atts));
    }

    public static function user_progress($atts = array()) {
        if (!is_user_logged_in()) {
            return self::notice(__('Please log in to view learning progress.', 'algq-education-center'));
        }
        return self::render('user-progress.php', array('atts' => shortcode_atts(array(), $atts, 'algq_user_progress'), 'user_id' => get_current_user_id()));
    }

    private static function render($template, $data = array()) {
        $template_path = ALGQ_EDU_DIR . 'templates/' . sanitize_file_name($template);
        if (!file_exists($template_path)) {
            return self::notice(sprintf(__('Template missing: %s', 'algq-education-center'), esc_html($template)));
        }
        ob_start();
        $data = is_array($data) ? $data : array();
        extract($data, EXTR_SKIP);
        include $template_path;
        return ob_get_clean();
    }

    private static function notice($message) {
        ob_start();
        echo '<div class="algq-edu-notice">' . esc_html($message) . '</div>';
        return ob_get_clean();
    }
}
