<?php
if (!defined('ABSPATH')) {
    exit;
}

class ALGQ_Education_Progress {
    public static function init() {
        add_action('wp_ajax_algq_mark_lesson_complete', array(__CLASS__, 'ajax_mark_lesson_complete'));
        add_action('wp_ajax_algq_mark_lesson_incomplete', array(__CLASS__, 'ajax_mark_lesson_incomplete'));
    }

    public static function table() {
        global $wpdb;
        return $wpdb->prefix . 'algq_learning_progress';
    }

    public static function mark_complete($user_id, $course_id, $lesson_id) {
        global $wpdb;

        $user_id   = absint($user_id);
        $course_id = absint($course_id);
        $lesson_id = absint($lesson_id);

        if (!$user_id || !$course_id || !$lesson_id) {
            return false;
        }

        $record_id = self::get_record_id($user_id, $course_id, $lesson_id);

        $data = array(
            'user_id'      => $user_id,
            'course_id'    => $course_id,
            'lesson_id'    => $lesson_id,
            'completed'    => 1,
            'completed_at' => current_time('mysql'),
            'updated_at'   => current_time('mysql'),
        );

        if ($record_id) {
            return false !== $wpdb->update(
                self::table(),
                $data,
                array('id' => $record_id),
                array('%d', '%d', '%d', '%d', '%s', '%s'),
                array('%d')
            );
        }

        $data['created_at'] = current_time('mysql');

        return false !== $wpdb->insert(
            self::table(),
            $data,
            array('%d', '%d', '%d', '%d', '%s', '%s', '%s')
        );
    }

    public static function mark_incomplete($user_id, $course_id, $lesson_id) {
        global $wpdb;

        $user_id   = absint($user_id);
        $course_id = absint($course_id);
        $lesson_id = absint($lesson_id);

        if (!$user_id || !$course_id || !$lesson_id) {
            return false;
        }

        return false !== $wpdb->update(
            self::table(),
            array(
                'completed'    => 0,
                'completed_at' => null,
                'updated_at'   => current_time('mysql'),
            ),
            array(
                'user_id'   => $user_id,
                'course_id' => $course_id,
                'lesson_id' => $lesson_id,
            ),
            array('%d', '%s', '%s'),
            array('%d', '%d', '%d')
        );
    }

    public static function is_complete($user_id, $course_id, $lesson_id) {
        global $wpdb;

        return (bool) $wpdb->get_var(
            $wpdb->prepare(
                'SELECT completed FROM ' . self::table() . ' WHERE user_id = %d AND course_id = %d AND lesson_id = %d LIMIT 1',
                absint($user_id),
                absint($course_id),
                absint($lesson_id)
            )
        );
    }

    public static function course_percentage($user_id, $course_id) {
        $lessons = self::course_lessons($course_id);
        $total   = count($lessons);

        if (!$total) {
            return 0;
        }

        $complete = 0;

        foreach ($lessons as $lesson) {
            if (self::is_complete($user_id, $course_id, $lesson->ID)) {
                $complete++;
            }
        }

        return (int) round(($complete / $total) * 100);
    }

    public static function course_lessons($course_id) {
        return get_posts(
            array(
                'post_type'      => 'algq_lesson',
                'post_status'    => 'publish',
                'posts_per_page' => -1,
                'meta_key'       => 'algq_lesson_course_id',
                'meta_value'     => absint($course_id),
                'orderby'        => 'meta_value_num date',
                'order'          => 'ASC',
            )
        );
    }

    public static function user_summary($user_id) {
        global $wpdb;

        $user_id = absint($user_id);

        if (!$user_id) {
            return array(
                'completed_lessons' => 0,
                'active_courses'    => 0,
            );
        }

        $completed = absint(
            $wpdb->get_var(
                $wpdb->prepare(
                    'SELECT COUNT(*) FROM ' . self::table() . ' WHERE user_id = %d AND completed = 1',
                    $user_id
                )
            )
        );

        $courses = absint(
            $wpdb->get_var(
                $wpdb->prepare(
                    'SELECT COUNT(DISTINCT course_id) FROM ' . self::table() . ' WHERE user_id = %d',
                    $user_id
                )
            )
        );

        return array(
            'completed_lessons' => $completed,
            'active_courses'    => $courses,
        );
    }

    private static function get_record_id($user_id, $course_id, $lesson_id) {
        global $wpdb;

        return absint(
            $wpdb->get_var(
                $wpdb->prepare(
                    'SELECT id FROM ' . self::table() . ' WHERE user_id = %d AND course_id = %d AND lesson_id = %d LIMIT 1',
                    absint($user_id),
                    absint($course_id),
                    absint($lesson_id)
                )
            )
        );
    }

    public static function ajax_mark_lesson_complete() {
        self::ajax_change_status(true);
    }

    public static function ajax_mark_lesson_incomplete() {
        self::ajax_change_status(false);
    }

    private static function ajax_change_status($complete) {
        if (!is_user_logged_in()) {
            wp_send_json_error(array('message' => __('Login required.', 'algq-education-center')), 401);
        }

        check_ajax_referer('algq_education_progress', 'nonce');

        $course_id = isset($_POST['course_id']) ? absint($_POST['course_id']) : 0;
        $lesson_id = isset($_POST['lesson_id']) ? absint($_POST['lesson_id']) : 0;

        if (!$course_id || !$lesson_id) {
            wp_send_json_error(array('message' => __('Missing course or lesson.', 'algq-education-center')), 400);
        }

        $ok = $complete
            ? self::mark_complete(get_current_user_id(), $course_id, $lesson_id)
            : self::mark_incomplete(get_current_user_id(), $course_id, $lesson_id);

        if (!$ok) {
            wp_send_json_error(array('message' => __('Progress could not be updated.', 'algq-education-center')), 500);
        }

        wp_send_json_success(
            array(
                'message'    => __('Progress updated.', 'algq-education-center'),
                'percentage' => self::course_percentage(get_current_user_id(), $course_id),
            )
        );
    }
}
