<?php
if (!defined('ABSPATH')) { exit; }

class ALGQ_Education_Email_Notifications {
    public static function init() {
        add_action('algq_education_course_enrolled', array(__CLASS__, 'course_enrolled'), 10, 2);
        add_action('algq_education_course_completed', array(__CLASS__, 'course_completed'), 10, 2);
        add_action('algq_education_certificate_issued', array(__CLASS__, 'certificate_issued'), 10, 3);
        add_action('algq_education_assignment_submitted', array(__CLASS__, 'assignment_submitted'), 10, 3);
    }

    public static function enabled() {
        $options = get_option('algq_education_options', array());
        return !isset($options['enable_email_notifications']) || !empty($options['enable_email_notifications']);
    }

    public static function course_enrolled($user_id, $course_id) {
        if (!self::enabled()) { return; }
        $user = get_userdata(absint($user_id));
        if (!$user) { return; }
        self::send($user->user_email, __('Course Enrollment Confirmed', 'algq-education-center'), sprintf(__('You are now enrolled in %s.', 'algq-education-center'), get_the_title(absint($course_id))));
    }

    public static function course_completed($user_id, $course_id) {
        if (!self::enabled()) { return; }
        $user = get_userdata(absint($user_id));
        if (!$user) { return; }
        self::send($user->user_email, __('Course Completed', 'algq-education-center'), sprintf(__('Congratulations. You completed %s.', 'algq-education-center'), get_the_title(absint($course_id))));
    }

    public static function certificate_issued($user_id, $course_id, $certificate_id) {
        if (!self::enabled()) { return; }
        $user = get_userdata(absint($user_id));
        if (!$user) { return; }
        self::send($user->user_email, __('Certificate Issued', 'algq-education-center'), sprintf(__('Your certificate for %s has been issued. Certificate ID: %d', 'algq-education-center'), get_the_title(absint($course_id)), absint($certificate_id)));
    }

    public static function assignment_submitted($user_id, $assignment_id, $course_id = 0) {
        if (!self::enabled()) { return; }
        $admin_email = get_option('admin_email');
        if (!$admin_email) { return; }
        $user = get_userdata(absint($user_id));
        $name = $user ? $user->display_name : __('Student', 'algq-education-center');
        self::send($admin_email, __('Assignment Submitted', 'algq-education-center'), sprintf(__('%s submitted assignment: %s', 'algq-education-center'), $name, get_the_title(absint($assignment_id))));
    }

    private static function send($to, $subject, $body) {
        $headers = array('Content-Type: text/plain; charset=UTF-8');
        $prefix = '[Algonquian Education Center] ';
        return wp_mail(sanitize_email($to), $prefix . sanitize_text_field($subject), wp_strip_all_tags($body), $headers);
    }
}
