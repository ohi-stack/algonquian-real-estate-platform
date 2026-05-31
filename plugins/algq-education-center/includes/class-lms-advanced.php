<?php
if (!defined('ABSPATH')) { exit; }

class ALGQ_Education_LMS_Advanced {
    public static function init() {
        add_action('init', array(__CLASS__, 'register_taxonomies'));
        add_action('init', array(__CLASS__, 'register_post_types'));
        add_action('wp_ajax_algq_submit_quiz', array(__CLASS__, 'ajax_submit_quiz'));
        add_action('wp_ajax_algq_award_badge', array(__CLASS__, 'ajax_award_badge'));
    }

    public static function register_taxonomies() {
        register_taxonomy('algq_course_category', array('algq_course'), array(
            'labels' => array('name'=>__('Course Categories','algq-education-center'),'singular_name'=>__('Course Category','algq-education-center')),
            'public'=>false,'show_ui'=>true,'show_admin_column'=>true,'hierarchical'=>true,'rewrite'=>false
        ));
        register_taxonomy('algq_course_track', array('algq_course'), array(
            'labels' => array('name'=>__('Course Tracks','algq-education-center'),'singular_name'=>__('Course Track','algq-education-center')),
            'public'=>false,'show_ui'=>true,'show_admin_column'=>true,'hierarchical'=>true,'rewrite'=>false
        ));
    }

    public static function register_post_types() {
        register_post_type('algq_quiz', array('labels'=>array('name'=>__('Quizzes','algq-education-center'),'singular_name'=>__('Quiz','algq-education-center')),'public'=>false,'show_ui'=>true,'show_in_menu'=>'algq-education','supports'=>array('title','editor','revisions'),'rewrite'=>false));
        register_post_type('algq_certificate', array('labels'=>array('name'=>__('Certificates','algq-education-center'),'singular_name'=>__('Certificate','algq-education-center')),'public'=>false,'show_ui'=>true,'show_in_menu'=>'algq-education','supports'=>array('title','editor','revisions'),'rewrite'=>false));
        register_post_type('algq_badge', array('labels'=>array('name'=>__('Badges','algq-education-center'),'singular_name'=>__('Badge','algq-education-center')),'public'=>false,'show_ui'=>true,'show_in_menu'=>'algq-education','supports'=>array('title','editor','thumbnail','revisions'),'rewrite'=>false));
    }

    public static function prerequisites_met($user_id, $course_id) {
        $required = get_post_meta(absint($course_id), 'algq_prerequisite_course_ids', true);
        $required = is_array($required) ? array_map('absint', $required) : array_filter(array_map('absint', explode(',', (string) $required)));
        if (!$required) { return true; }
        foreach ($required as $required_course_id) {
            if (class_exists('ALGQ_Education_Progress') && ALGQ_Education_Progress::course_percentage(absint($user_id), $required_course_id) < 100) { return false; }
        }
        return true;
    }

    public static function drip_available($user_id, $course_id) {
        $days = absint(get_post_meta(absint($course_id), 'algq_drip_days_after_enrollment', true));
        if (!$days || !class_exists('ALGQ_Education_Enrollment')) { return true; }
        $enrolled_at = get_user_meta(absint($user_id), 'algq_course_enrolled_at_' . absint($course_id), true);
        if (!$enrolled_at) { return false; }
        return strtotime($enrolled_at . ' +' . $days . ' days') <= current_time('timestamp');
    }

    public static function issue_certificate($user_id, $course_id) {
        $user_id = absint($user_id); $course_id = absint($course_id);
        if (!$user_id || !$course_id) { return 0; }
        $existing = get_user_meta($user_id, 'algq_certificate_course_' . $course_id, true);
        if ($existing) { return absint($existing); }
        $certificate_id = wp_insert_post(array('post_type'=>'algq_certificate','post_status'=>'publish','post_title'=>sprintf(__('Certificate: Course %d / User %d','algq-education-center'), $course_id, $user_id),'post_content'=>''));
        if ($certificate_id && !is_wp_error($certificate_id)) {
            update_post_meta($certificate_id, 'algq_certificate_user_id', $user_id);
            update_post_meta($certificate_id, 'algq_certificate_course_id', $course_id);
            update_post_meta($certificate_id, 'algq_certificate_issued_at', current_time('mysql'));
            update_user_meta($user_id, 'algq_certificate_course_' . $course_id, absint($certificate_id));
            return absint($certificate_id);
        }
        return 0;
    }

    public static function award_badge($user_id, $badge_id) {
        $user_id = absint($user_id); $badge_id = absint($badge_id);
        if (!$user_id || !$badge_id || 'algq_badge' !== get_post_type($badge_id)) { return false; }
        $badges = get_user_meta($user_id, 'algq_awarded_badges', true);
        $badges = is_array($badges) ? array_map('absint', $badges) : array();
        if (!in_array($badge_id, $badges, true)) { $badges[] = $badge_id; }
        update_user_meta($user_id, 'algq_awarded_badges', array_values(array_unique($badges)));
        update_user_meta($user_id, 'algq_badge_awarded_at_' . $badge_id, current_time('mysql'));
        return true;
    }

    public static function analytics_summary() {
        $courses = wp_count_posts('algq_course'); $lessons = wp_count_posts('algq_lesson'); $quizzes = wp_count_posts('algq_quiz'); $certs = wp_count_posts('algq_certificate');
        return array('courses'=>absint($courses->publish ?? 0),'lessons'=>absint($lessons->publish ?? 0),'quizzes'=>absint($quizzes->publish ?? 0),'certificates'=>absint($certs->publish ?? 0));
    }

    public static function ajax_submit_quiz() {
        if (!is_user_logged_in()) { wp_send_json_error(array('message'=>__('Login required.','algq-education-center')), 401); }
        check_ajax_referer('algq_education_quiz', 'nonce');
        $quiz_id = isset($_POST['quiz_id']) ? absint($_POST['quiz_id']) : 0;
        $score = isset($_POST['score']) ? absint($_POST['score']) : 0;
        if (!$quiz_id || 'algq_quiz' !== get_post_type($quiz_id)) { wp_send_json_error(array('message'=>__('Invalid quiz.','algq-education-center')), 400); }
        update_user_meta(get_current_user_id(), 'algq_quiz_score_' . $quiz_id, min(100, $score));
        update_user_meta(get_current_user_id(), 'algq_quiz_completed_at_' . $quiz_id, current_time('mysql'));
        wp_send_json_success(array('message'=>__('Quiz submitted.','algq-education-center'),'score'=>min(100, $score)));
    }

    public static function ajax_award_badge() {
        if (!current_user_can('manage_options')) { wp_send_json_error(array('message'=>__('Permission denied.','algq-education-center')), 403); }
        check_ajax_referer('algq_education_admin', 'nonce');
        $user_id = isset($_POST['user_id']) ? absint($_POST['user_id']) : 0;
        $badge_id = isset($_POST['badge_id']) ? absint($_POST['badge_id']) : 0;
        $ok = self::award_badge($user_id, $badge_id);
        $ok ? wp_send_json_success(array('message'=>__('Badge awarded.','algq-education-center'))) : wp_send_json_error(array('message'=>__('Badge could not be awarded.','algq-education-center')), 400);
    }
}
