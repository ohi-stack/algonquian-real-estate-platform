<?php
if (!defined('ABSPATH')) { exit; }

class ALGQ_Education_Gradebook {
    public static function init() {
        add_shortcode('algq_instructor_gradebook', array(__CLASS__, 'render_gradebook'));
        add_action('wp_ajax_algq_gradebook_note', array(__CLASS__, 'ajax_save_note'));
    }

    public static function render_gradebook($atts = array()) {
        if (!current_user_can('edit_posts')) {
            return '<div class="algq-edu-notice">' . esc_html__('Instructor access required.', 'algq-education-center') . '</div>';
        }

        $users = get_users(array('number' => 50, 'fields' => array('ID', 'display_name', 'user_email')));
        $courses = get_posts(array('post_type' => 'algq_course', 'post_status' => 'publish', 'posts_per_page' => -1));

        ob_start();
        echo '<section class="algq-edu algq-gradebook"><header class="algq-section-header"><p class="algq-kicker">' . esc_html__('Instructor LMS', 'algq-education-center') . '</p><h1>' . esc_html__('Instructor Gradebook', 'algq-education-center') . '</h1><p>' . esc_html__('Review student course progress, certificate status, quiz records, and instructor notes.', 'algq-education-center') . '</p></header>';
        echo '<div class="algq-card-grid">';
        foreach ($users as $user) {
            $enrolled = class_exists('ALGQ_Education_Enrollment') ? ALGQ_Education_Enrollment::enrolled_courses($user->ID) : array();
            if (!$enrolled) { continue; }
            echo '<article class="algq-card"><h2>' . esc_html($user->display_name) . '</h2><p>' . esc_html($user->user_email) . '</p>';
            foreach ($courses as $course) {
                if (!in_array(absint($course->ID), array_map('absint', $enrolled), true)) { continue; }
                $percent = class_exists('ALGQ_Education_Progress') ? ALGQ_Education_Progress::course_percentage($user->ID, $course->ID) : 0;
                $cert = get_user_meta($user->ID, 'algq_certificate_course_' . absint($course->ID), true);
                echo '<div class="algq-gradebook-row"><strong>' . esc_html(get_the_title($course)) . '</strong><div class="algq-progress"><span style="width:' . esc_attr((string) $percent) . '%"></span></div><div class="algq-meta"><span>' . esc_html($percent . '%') . '</span><span>' . esc_html($cert ? __('Certificate Issued', 'algq-education-center') : __('Certificate Pending', 'algq-education-center')) . '</span></div></div>';
            }
            $note = get_user_meta($user->ID, 'algq_gradebook_note', true);
            echo '<p><strong>' . esc_html__('Instructor Note:', 'algq-education-center') . '</strong> ' . esc_html($note ? $note : __('No note recorded.', 'algq-education-center')) . '</p></article>';
        }
        echo '</div></section>';
        return ob_get_clean();
    }

    public static function ajax_save_note() {
        if (!current_user_can('edit_posts')) { wp_send_json_error(array('message'=>__('Permission denied.', 'algq-education-center')), 403); }
        check_ajax_referer('algq_gradebook_note', 'nonce');
        $user_id = isset($_POST['user_id']) ? absint($_POST['user_id']) : 0;
        $note = isset($_POST['note']) ? sanitize_textarea_field(wp_unslash($_POST['note'])) : '';
        if (!$user_id) { wp_send_json_error(array('message'=>__('Invalid user.', 'algq-education-center')), 400); }
        update_user_meta($user_id, 'algq_gradebook_note', $note);
        wp_send_json_success(array('message'=>__('Gradebook note saved.', 'algq-education-center')));
    }
}
