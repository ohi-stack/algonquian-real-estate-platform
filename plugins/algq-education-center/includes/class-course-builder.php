<?php
if (!defined('ABSPATH')) { exit; }

class ALGQ_Education_Course_Builder {
    public static function init() {
        add_action('add_meta_boxes', array(__CLASS__, 'add_meta_boxes'));
        add_action('save_post_algq_course', array(__CLASS__, 'save_builder_meta'), 20, 2);
        add_action('wp_ajax_algq_course_builder_assign', array(__CLASS__, 'ajax_assign_item'));
    }

    public static function add_meta_boxes() {
        add_meta_box('algq_course_builder', __('Course Builder', 'algq-education-center'), array(__CLASS__, 'render_builder'), 'algq_course', 'normal', 'high');
    }

    public static function render_builder($post) {
        wp_nonce_field('algq_save_course_builder', 'algq_course_builder_nonce');
        $lesson_ids = get_post_meta($post->ID, 'algq_course_builder_lesson_ids', true);
        $quiz_ids = get_post_meta($post->ID, 'algq_course_builder_quiz_ids', true);
        $badge_ids = get_post_meta($post->ID, 'algq_course_builder_badge_ids', true);
        echo '<div class="algq-course-builder">';
        echo '<p><strong>' . esc_html__('Build the course sequence using comma-separated post IDs.', 'algq-education-center') . '</strong></p>';
        echo '<p><label>' . esc_html__('Lesson IDs', 'algq-education-center') . '</label><br><input class="widefat" type="text" name="algq_course_builder_lesson_ids" value="' . esc_attr($lesson_ids) . '" placeholder="101,102,103"></p>';
        echo '<p><label>' . esc_html__('Quiz IDs', 'algq-education-center') . '</label><br><input class="widefat" type="text" name="algq_course_builder_quiz_ids" value="' . esc_attr($quiz_ids) . '" placeholder="201,202"></p>';
        echo '<p><label>' . esc_html__('Completion Badge IDs', 'algq-education-center') . '</label><br><input class="widefat" type="text" name="algq_course_builder_badge_ids" value="' . esc_attr($badge_ids) . '" placeholder="301"></p>';
        echo '<p class="description">' . esc_html__('This foundation supports future drag-and-drop ordering while remaining safe for production use today.', 'algq-education-center') . '</p>';
        echo '</div>';
    }

    public static function save_builder_meta($post_id, $post) {
        if (!isset($_POST['algq_course_builder_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['algq_course_builder_nonce'])), 'algq_save_course_builder')) { return; }
        if (!current_user_can('edit_post', $post_id)) { return; }
        $fields = array('algq_course_builder_lesson_ids','algq_course_builder_quiz_ids','algq_course_builder_badge_ids');
        foreach ($fields as $field) {
            $value = isset($_POST[$field]) ? sanitize_text_field(wp_unslash($_POST[$field])) : '';
            $ids = array_filter(array_map('absint', explode(',', $value)));
            update_post_meta($post_id, $field, implode(',', $ids));
        }
    }

    public static function course_items($course_id) {
        $course_id = absint($course_id);
        return array(
            'lessons' => self::ids_from_meta($course_id, 'algq_course_builder_lesson_ids'),
            'quizzes' => self::ids_from_meta($course_id, 'algq_course_builder_quiz_ids'),
            'badges' => self::ids_from_meta($course_id, 'algq_course_builder_badge_ids'),
        );
    }

    private static function ids_from_meta($post_id, $key) {
        $raw = get_post_meta(absint($post_id), $key, true);
        return array_filter(array_map('absint', explode(',', (string) $raw)));
    }

    public static function ajax_assign_item() {
        if (!current_user_can('edit_posts')) { wp_send_json_error(array('message'=>__('Permission denied.', 'algq-education-center')), 403); }
        check_ajax_referer('algq_course_builder', 'nonce');
        $course_id = isset($_POST['course_id']) ? absint($_POST['course_id']) : 0;
        $item_id = isset($_POST['item_id']) ? absint($_POST['item_id']) : 0;
        $type = isset($_POST['type']) ? sanitize_key($_POST['type']) : '';
        if (!$course_id || !$item_id) { wp_send_json_error(array('message'=>__('Missing course or item.', 'algq-education-center')), 400); }
        $map = array('lesson'=>'algq_course_builder_lesson_ids','quiz'=>'algq_course_builder_quiz_ids','badge'=>'algq_course_builder_badge_ids');
        if (!isset($map[$type])) { wp_send_json_error(array('message'=>__('Invalid item type.', 'algq-education-center')), 400); }
        $ids = self::ids_from_meta($course_id, $map[$type]);
        if (!in_array($item_id, $ids, true)) { $ids[] = $item_id; }
        update_post_meta($course_id, $map[$type], implode(',', array_values(array_unique($ids))));
        wp_send_json_success(array('message'=>__('Course builder updated.', 'algq-education-center'), 'items'=>self::course_items($course_id)));
    }
}
