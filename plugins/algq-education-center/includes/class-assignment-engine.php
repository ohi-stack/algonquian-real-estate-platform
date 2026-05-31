<?php
if (!defined('ABSPATH')) { exit; }

class ALGQ_Education_Assignment_Engine {
    public static function init() {
        add_action('init', array(__CLASS__, 'register_post_type'));
        add_action('add_meta_boxes', array(__CLASS__, 'add_meta_boxes'));
        add_action('save_post_algq_assignment', array(__CLASS__, 'save_assignment_meta'), 10, 2);
        add_shortcode('algq_assignment', array(__CLASS__, 'render_assignment'));
        add_action('wp_ajax_algq_submit_assignment', array(__CLASS__, 'ajax_submit_assignment'));
        add_action('wp_ajax_algq_grade_assignment', array(__CLASS__, 'ajax_grade_assignment'));
    }

    public static function register_post_type() {
        register_post_type('algq_assignment', array(
            'labels' => array('name'=>__('Assignments','algq-education-center'),'singular_name'=>__('Assignment','algq-education-center')),
            'public'=>false,'show_ui'=>true,'show_in_menu'=>'algq-education','supports'=>array('title','editor','revisions'),'rewrite'=>false
        ));
    }

    public static function add_meta_boxes() {
        add_meta_box('algq_assignment_details', __('Assignment Details','algq-education-center'), array(__CLASS__, 'render_assignment_meta'), 'algq_assignment', 'normal', 'high');
    }

    public static function render_assignment_meta($post) {
        wp_nonce_field('algq_save_assignment_meta', 'algq_assignment_nonce');
        $course_id = get_post_meta($post->ID, 'algq_assignment_course_id', true);
        $lesson_id = get_post_meta($post->ID, 'algq_assignment_lesson_id', true);
        $points = get_post_meta($post->ID, 'algq_assignment_points', true);
        echo '<p><label><strong>' . esc_html__('Course ID','algq-education-center') . '</strong></label><br><input class="widefat" type="number" name="algq_assignment_course_id" value="' . esc_attr($course_id) . '"></p>';
        echo '<p><label><strong>' . esc_html__('Lesson ID','algq-education-center') . '</strong></label><br><input class="widefat" type="number" name="algq_assignment_lesson_id" value="' . esc_attr($lesson_id) . '"></p>';
        echo '<p><label><strong>' . esc_html__('Points','algq-education-center') . '</strong></label><br><input class="widefat" type="number" name="algq_assignment_points" value="' . esc_attr($points ? $points : 100) . '"></p>';
    }

    public static function save_assignment_meta($post_id, $post) {
        if (!isset($_POST['algq_assignment_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['algq_assignment_nonce'])), 'algq_save_assignment_meta')) { return; }
        if (!current_user_can('edit_post', $post_id)) { return; }
        update_post_meta($post_id, 'algq_assignment_course_id', isset($_POST['algq_assignment_course_id']) ? absint($_POST['algq_assignment_course_id']) : 0);
        update_post_meta($post_id, 'algq_assignment_lesson_id', isset($_POST['algq_assignment_lesson_id']) ? absint($_POST['algq_assignment_lesson_id']) : 0);
        update_post_meta($post_id, 'algq_assignment_points', isset($_POST['algq_assignment_points']) ? absint($_POST['algq_assignment_points']) : 100);
    }

    public static function render_assignment($atts = array()) {
        $atts = shortcode_atts(array('id'=>0), $atts, 'algq_assignment');
        $assignment_id = absint($atts['id']);
        if (!$assignment_id || 'algq_assignment' !== get_post_type($assignment_id)) { return '<div class="algq-edu-notice">' . esc_html__('Assignment not found.','algq-education-center') . '</div>'; }
        if (!is_user_logged_in()) { return '<div class="algq-edu-notice">' . esc_html__('Please log in to submit this assignment.','algq-education-center') . '</div>'; }
        $submission = self::get_submission(get_current_user_id(), $assignment_id);
        ob_start();
        echo '<section class="algq-edu algq-assignment" data-assignment-id="' . esc_attr((string) $assignment_id) . '"><header class="algq-section-header"><p class="algq-kicker">' . esc_html__('Assignment','algq-education-center') . '</p><h1>' . esc_html(get_the_title($assignment_id)) . '</h1></header><div class="algq-content">' . wp_kses_post(wpautop(get_post_field('post_content', $assignment_id))) . '</div>';
        echo '<div class="algq-card"><h2>' . esc_html__('Submission','algq-education-center') . '</h2><p>' . esc_html($submission ? __('Submitted. Await instructor review or grade.','algq-education-center') : __('No submission recorded yet.','algq-education-center')) . '</p><textarea class="algq-assignment-response" rows="6" style="width:100%"></textarea><button type="button" class="algq-btn algq-btn-gold algq-submit-assignment" data-assignment-id="' . esc_attr((string) $assignment_id) . '">' . esc_html__('Submit Assignment','algq-education-center') . '</button></div></section>';
        return ob_get_clean();
    }

    public static function get_submission($user_id, $assignment_id) {
        return get_user_meta(absint($user_id), 'algq_assignment_submission_' . absint($assignment_id), true);
    }

    public static function ajax_submit_assignment() {
        if (!is_user_logged_in()) { wp_send_json_error(array('message'=>__('Login required.','algq-education-center')), 401); }
        check_ajax_referer('algq_assignment_submit', 'nonce');
        $assignment_id = isset($_POST['assignment_id']) ? absint($_POST['assignment_id']) : 0;
        $response = isset($_POST['response']) ? sanitize_textarea_field(wp_unslash($_POST['response'])) : '';
        if (!$assignment_id || 'algq_assignment' !== get_post_type($assignment_id)) { wp_send_json_error(array('message'=>__('Invalid assignment.','algq-education-center')), 400); }
        update_user_meta(get_current_user_id(), 'algq_assignment_submission_' . $assignment_id, $response);
        update_user_meta(get_current_user_id(), 'algq_assignment_submitted_at_' . $assignment_id, current_time('mysql'));
        wp_send_json_success(array('message'=>__('Assignment submitted.','algq-education-center')));
    }

    public static function ajax_grade_assignment() {
        if (!current_user_can('edit_posts')) { wp_send_json_error(array('message'=>__('Permission denied.','algq-education-center')), 403); }
        check_ajax_referer('algq_assignment_grade', 'nonce');
        $user_id = isset($_POST['user_id']) ? absint($_POST['user_id']) : 0;
        $assignment_id = isset($_POST['assignment_id']) ? absint($_POST['assignment_id']) : 0;
        $grade = isset($_POST['grade']) ? absint($_POST['grade']) : 0;
        $note = isset($_POST['note']) ? sanitize_textarea_field(wp_unslash($_POST['note'])) : '';
        if (!$user_id || !$assignment_id) { wp_send_json_error(array('message'=>__('Missing user or assignment.','algq-education-center')), 400); }
        update_user_meta($user_id, 'algq_assignment_grade_' . $assignment_id, min(100, $grade));
        update_user_meta($user_id, 'algq_assignment_grade_note_' . $assignment_id, $note);
        update_user_meta($user_id, 'algq_assignment_graded_at_' . $assignment_id, current_time('mysql'));
        wp_send_json_success(array('message'=>__('Assignment graded.','algq-education-center')));
    }
}
