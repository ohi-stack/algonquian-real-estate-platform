<?php
if (!defined('ABSPATH')) { exit; }

class ALGQ_Education_REST_API {
    public static function init() {
        add_action('rest_api_init', array(__CLASS__, 'register_routes'));
    }

    public static function register_routes() {
        register_rest_route('algq-education/v1', '/courses', array('methods'=>'GET','callback'=>array(__CLASS__, 'courses'),'permission_callback'=>'__return_true'));
        register_rest_route('algq-education/v1', '/me', array('methods'=>'GET','callback'=>array(__CLASS__, 'me'),'permission_callback'=>array(__CLASS__, 'logged_in')));
        register_rest_route('algq-education/v1', '/analytics', array('methods'=>'GET','callback'=>array(__CLASS__, 'analytics'),'permission_callback'=>array(__CLASS__, 'admin_only')));
    }

    public static function courses() {
        $posts = get_posts(array('post_type'=>'algq_course','post_status'=>'publish','posts_per_page'=>50));
        $data = array();
        foreach ($posts as $post) {
            $data[] = array('id'=>absint($post->ID),'title'=>get_the_title($post),'excerpt'=>wp_strip_all_tags(get_the_excerpt($post)),'access'=>sanitize_key(get_post_meta($post->ID, 'algq_course_access_level', true)));
        }
        return rest_ensure_response($data);
    }

    public static function me() {
        $user_id = get_current_user_id();
        $courses = class_exists('ALGQ_Education_Enrollment') ? ALGQ_Education_Enrollment::enrolled_courses($user_id) : array();
        $items = array();
        foreach ($courses as $course_id) {
            $items[] = array('id'=>absint($course_id),'title'=>get_the_title($course_id),'progress'=>class_exists('ALGQ_Education_Progress') ? ALGQ_Education_Progress::course_percentage($user_id, $course_id) : 0);
        }
        return rest_ensure_response(array('user_id'=>$user_id,'courses'=>$items));
    }

    public static function analytics() {
        $lms = class_exists('ALGQ_Education_LMS_Advanced') ? ALGQ_Education_LMS_Advanced::analytics_summary() : array();
        $revenue = class_exists('ALGQ_Education_Revenue_Analytics') ? ALGQ_Education_Revenue_Analytics::summary() : array();
        return rest_ensure_response(array('lms'=>$lms,'revenue'=>$revenue));
    }

    public static function logged_in() { return is_user_logged_in(); }
    public static function admin_only() { return current_user_can('manage_options'); }
}
