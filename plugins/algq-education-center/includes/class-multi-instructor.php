<?php
if (!defined('ABSPATH')) { exit; }

class ALGQ_Education_Multi_Instructor {
    public static function init() {
        add_action('add_meta_boxes', array(__CLASS__, 'add_meta_boxes'));
        add_action('save_post_algq_course', array(__CLASS__, 'save_meta'), 30, 2);
        add_shortcode('algq_course_instructors', array(__CLASS__, 'render_instructors'));
    }

    public static function add_meta_boxes() {
        add_meta_box('algq_course_instructors', __('Course Instructors', 'algq-education-center'), array(__CLASS__, 'render_meta_box'), 'algq_course', 'side', 'default');
    }

    public static function render_meta_box($post) {
        wp_nonce_field('algq_save_course_instructors', 'algq_course_instructors_nonce');
        $raw = get_post_meta($post->ID, 'algq_course_instructor_ids', true);
        echo '<p><label><strong>' . esc_html__('Instructor User IDs', 'algq-education-center') . '</strong></label></p>';
        echo '<input type="text" class="widefat" name="algq_course_instructor_ids" value="' . esc_attr($raw) . '" placeholder="2,5,9" />';
        echo '<p class="description">' . esc_html__('Comma-separated WordPress user IDs with instructor or editor access.', 'algq-education-center') . '</p>';
    }

    public static function save_meta($post_id, $post) {
        if (!isset($_POST['algq_course_instructors_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['algq_course_instructors_nonce'])), 'algq_save_course_instructors')) { return; }
        if (!current_user_can('edit_post', $post_id)) { return; }
        $raw = isset($_POST['algq_course_instructor_ids']) ? sanitize_text_field(wp_unslash($_POST['algq_course_instructor_ids'])) : '';
        $ids = array_filter(array_map('absint', explode(',', $raw)));
        update_post_meta($post_id, 'algq_course_instructor_ids', implode(',', array_unique($ids)));
    }

    public static function instructor_ids($course_id) {
        $raw = get_post_meta(absint($course_id), 'algq_course_instructor_ids', true);
        return array_filter(array_map('absint', explode(',', (string) $raw)));
    }

    public static function user_can_instruct($user_id, $course_id) {
        $user_id = absint($user_id);
        if (!$user_id || !$course_id) { return false; }
        if (current_user_can('manage_options')) { return true; }
        return in_array($user_id, self::instructor_ids($course_id), true) || user_can($user_id, 'edit_posts');
    }

    public static function render_instructors($atts = array()) {
        $atts = shortcode_atts(array('course_id'=>0), $atts, 'algq_course_instructors');
        $course_id = absint($atts['course_id']);
        if (!$course_id) { return ''; }
        $ids = self::instructor_ids($course_id);
        ob_start();
        echo '<section class="algq-edu algq-course-instructors"><div class="algq-card-grid">';
        if ($ids) {
            foreach ($ids as $id) {
                $user = get_userdata($id);
                if (!$user) { continue; }
                echo '<article class="algq-card"><span class="algq-badge">' . esc_html__('Instructor', 'algq-education-center') . '</span><h2>' . esc_html($user->display_name) . '</h2><p>' . esc_html($user->user_email) . '</p></article>';
            }
        } else {
            echo '<article class="algq-card"><p>' . esc_html__('No instructors assigned.', 'algq-education-center') . '</p></article>';
        }
        echo '</div></section>';
        return ob_get_clean();
    }
}
