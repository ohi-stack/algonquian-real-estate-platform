<?php
if (!defined('ABSPATH')) { exit; }

class ALGQ_Education_Mobile_API {
    public static function init() {
        add_action('rest_api_init', array(__CLASS__, 'register_routes'));
    }

    public static function register_routes() {
        register_rest_route('algq-education/mobile/v1', '/dashboard', array('methods'=>'GET','callback'=>array(__CLASS__, 'dashboard'),'permission_callback'=>array(__CLASS__, 'logged_in')));
        register_rest_route('algq-education/mobile/v1', '/courses/(?P<id>\d+)', array('methods'=>'GET','callback'=>array(__CLASS__, 'course'),'permission_callback'=>array(__CLASS__, 'logged_in')));
        register_rest_route('algq-education/mobile/v1', '/progress', array('methods'=>'POST','callback'=>array(__CLASS__, 'progress'),'permission_callback'=>array(__CLASS__, 'logged_in')));
    }

    public static function dashboard() {
        $user_id = get_current_user_id();
        $courses = class_exists('ALGQ_Education_Enrollment') ? ALGQ_Education_Enrollment::enrolled_courses($user_id) : array();
        $items = array();
        foreach ($courses as $course_id) {
            $items[] = array(
                'id' => absint($course_id),
                'title' => get_the_title($course_id),
                'progress' => class_exists('ALGQ_Education_Progress') ? ALGQ_Education_Progress::course_percentage($user_id, $course_id) : 0,
                'certificate_id' => absint(get_user_meta($user_id, 'algq_certificate_course_' . absint($course_id), true)),
            );
        }
        return rest_ensure_response(array('user_id'=>$user_id,'courses'=>$items));
    }

    public static function course($request) {
        $course_id = absint($request['id']);
        if (!$course_id || 'algq_course' !== get_post_type($course_id)) { return new WP_Error('algq_course_not_found', __('Course not found.', 'algq-education-center'), array('status'=>404)); }
        $lessons = class_exists('ALGQ_Education_Progress') ? ALGQ_Education_Progress::course_lessons($course_id) : array();
        $lesson_data = array();
        foreach ($lessons as $lesson) { $lesson_data[] = array('id'=>absint($lesson->ID),'title'=>get_the_title($lesson),'excerpt'=>wp_strip_all_tags(get_the_excerpt($lesson))); }
        return rest_ensure_response(array('id'=>$course_id,'title'=>get_the_title($course_id),'content'=>wp_strip_all_tags(get_post_field('post_content', $course_id)),'lessons'=>$lesson_data));
    }

    public static function progress($request) {
        $course_id = absint($request->get_param('course_id'));
        $lesson_id = absint($request->get_param('lesson_id'));
        $complete = (bool) $request->get_param('complete');
        if (!$course_id || !$lesson_id || !class_exists('ALGQ_Education_Progress')) { return new WP_Error('algq_invalid_progress', __('Invalid progress request.', 'algq-education-center'), array('status'=>400)); }
        $ok = $complete ? ALGQ_Education_Progress::mark_complete(get_current_user_id(), $course_id, $lesson_id) : ALGQ_Education_Progress::mark_incomplete(get_current_user_id(), $course_id, $lesson_id);
        if (!$ok) { return new WP_Error('algq_progress_failed', __('Progress could not be updated.', 'algq-education-center'), array('status'=>500)); }
        return rest_ensure_response(array('success'=>true,'percentage'=>ALGQ_Education_Progress::course_percentage(get_current_user_id(), $course_id)));
    }

    public static function logged_in() { return is_user_logged_in(); }
}
