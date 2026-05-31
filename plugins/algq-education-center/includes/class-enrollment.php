<?php
if (!defined('ABSPATH')) { exit; }

class ALGQ_Education_Enrollment {
    public static function init() {
        add_action('wp_ajax_algq_enroll_course', array(__CLASS__, 'ajax_enroll_course'));
        add_action('wp_ajax_algq_unenroll_course', array(__CLASS__, 'ajax_unenroll_course'));
    }

    public static function enroll($user_id, $course_id) {
        $user_id = absint($user_id);
        $course_id = absint($course_id);
        if (!$user_id || !$course_id || 'algq_course' !== get_post_type($course_id)) { return false; }
        $enrolled = get_user_meta($user_id, 'algq_enrolled_courses', true);
        $enrolled = is_array($enrolled) ? array_map('absint', $enrolled) : array();
        if (!in_array($course_id, $enrolled, true)) { $enrolled[] = $course_id; }
        update_user_meta($user_id, 'algq_enrolled_courses', array_values(array_unique($enrolled)));
        update_user_meta($user_id, 'algq_course_enrolled_at_' . $course_id, current_time('mysql'));
        return true;
    }

    public static function unenroll($user_id, $course_id) {
        $user_id = absint($user_id);
        $course_id = absint($course_id);
        if (!$user_id || !$course_id) { return false; }
        $enrolled = get_user_meta($user_id, 'algq_enrolled_courses', true);
        $enrolled = is_array($enrolled) ? array_map('absint', $enrolled) : array();
        $enrolled = array_values(array_diff($enrolled, array($course_id)));
        update_user_meta($user_id, 'algq_enrolled_courses', $enrolled);
        delete_user_meta($user_id, 'algq_course_enrolled_at_' . $course_id);
        return true;
    }

    public static function is_enrolled($user_id, $course_id) {
        $enrolled = get_user_meta(absint($user_id), 'algq_enrolled_courses', true);
        $enrolled = is_array($enrolled) ? array_map('absint', $enrolled) : array();
        return in_array(absint($course_id), $enrolled, true);
    }

    public static function enrolled_courses($user_id) {
        $enrolled = get_user_meta(absint($user_id), 'algq_enrolled_courses', true);
        return is_array($enrolled) ? array_values(array_unique(array_map('absint', $enrolled))) : array();
    }

    public static function render_button($course_id, $user_id = 0) {
        $course_id = absint($course_id);
        $user_id = $user_id ? absint($user_id) : get_current_user_id();
        if (!$course_id) { return ''; }
        if (!$user_id) {
            return '<a class="algq-btn algq-btn-gold" href="' . esc_url(wp_login_url(get_permalink($course_id))) . '">' . esc_html__('Log In to Enroll', 'algq-education-center') . '</a>';
        }
        $enrolled = self::is_enrolled($user_id, $course_id);
        ob_start();
        echo '<button type="button" class="algq-btn ' . esc_attr($enrolled ? 'algq-btn-outline algq-unenroll-course' : 'algq-btn-gold algq-enroll-course') . '" data-course-id="' . esc_attr((string) $course_id) . '" data-status="' . esc_attr($enrolled ? 'enrolled' : 'available') . '">';
        echo esc_html($enrolled ? __('Unenroll', 'algq-education-center') : __('Enroll in Course', 'algq-education-center'));
        echo '</button>';
        return ob_get_clean();
    }

    public static function ajax_enroll_course() { self::ajax_change(true); }
    public static function ajax_unenroll_course() { self::ajax_change(false); }

    private static function ajax_change($enroll) {
        if (!is_user_logged_in()) { wp_send_json_error(array('message'=>__('Login required.', 'algq-education-center')), 401); }
        check_ajax_referer('algq_education_enrollment', 'nonce');
        $course_id = isset($_POST['course_id']) ? absint($_POST['course_id']) : 0;
        if (!$course_id || 'algq_course' !== get_post_type($course_id)) { wp_send_json_error(array('message'=>__('Invalid course.', 'algq-education-center')), 400); }
        if (class_exists('ALGQ_Education_Access_Control') && !ALGQ_Education_Access_Control::can_access_post($course_id)) { wp_send_json_error(array('message'=>__('Access denied.', 'algq-education-center')), 403); }
        $ok = $enroll ? self::enroll(get_current_user_id(), $course_id) : self::unenroll(get_current_user_id(), $course_id);
        if (!$ok) { wp_send_json_error(array('message'=>__('Enrollment could not be updated.', 'algq-education-center')), 500); }
        wp_send_json_success(array('message'=>__('Enrollment updated.', 'algq-education-center'), 'enrolled'=>self::is_enrolled(get_current_user_id(), $course_id)));
    }
}
