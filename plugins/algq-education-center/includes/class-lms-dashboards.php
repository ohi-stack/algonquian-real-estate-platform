<?php
if (!defined('ABSPATH')) { exit; }

class ALGQ_Education_LMS_Dashboards {
    public static function init() {
        add_shortcode('algq_student_dashboard', array(__CLASS__, 'student_dashboard'));
        add_shortcode('algq_instructor_dashboard', array(__CLASS__, 'instructor_dashboard'));
        add_shortcode('algq_lms_analytics', array(__CLASS__, 'analytics_dashboard'));
    }

    public static function student_dashboard($atts = array()) {
        if (!is_user_logged_in()) { return self::notice(__('Please log in to view your student dashboard.', 'algq-education-center')); }
        $user_id = get_current_user_id();
        $courses = class_exists('ALGQ_Education_Enrollment') ? ALGQ_Education_Enrollment::enrolled_courses($user_id) : array();
        ob_start();
        echo '<section class="algq-edu algq-student-dashboard"><header class="algq-section-header"><p class="algq-kicker">' . esc_html__('Student LMS', 'algq-education-center') . '</p><h1>' . esc_html__('Student Dashboard', 'algq-education-center') . '</h1><p>' . esc_html__('View enrolled courses, progress, certificates, badges, and quiz history.', 'algq-education-center') . '</p></header>';
        echo '<div class="algq-card-grid">';
        if ($courses) {
            foreach ($courses as $course_id) {
                $percent = class_exists('ALGQ_Education_Progress') ? ALGQ_Education_Progress::course_percentage($user_id, $course_id) : 0;
                echo '<article class="algq-card"><h2>' . esc_html(get_the_title($course_id)) . '</h2><div class="algq-progress"><span style="width:' . esc_attr((string) $percent) . '%"></span></div><p>' . esc_html(sprintf(__('%d%% complete', 'algq-education-center'), $percent)) . '</p></article>';
            }
        } else {
            echo '<article class="algq-card"><h2>' . esc_html__('No enrollments yet.', 'algq-education-center') . '</h2><p>' . esc_html__('Enroll in a course to begin tracking progress.', 'algq-education-center') . '</p></article>';
        }
        echo '</div></section>';
        return ob_get_clean();
    }

    public static function instructor_dashboard($atts = array()) {
        if (!current_user_can('edit_posts')) { return self::notice(__('Instructor access required.', 'algq-education-center')); }
        $courses = get_posts(array('post_type'=>'algq_course','post_status'=>'publish','posts_per_page'=>-1));
        ob_start();
        echo '<section class="algq-edu algq-instructor-dashboard"><header class="algq-section-header"><p class="algq-kicker">' . esc_html__('Instructor LMS', 'algq-education-center') . '</p><h1>' . esc_html__('Instructor Dashboard', 'algq-education-center') . '</h1><p>' . esc_html__('Review courses, lessons, quizzes, certificates, badges, and student activity.', 'algq-education-center') . '</p></header>';
        echo '<div class="algq-card-grid">';
        foreach ($courses as $course) {
            $lessons = class_exists('ALGQ_Education_Progress') ? count(ALGQ_Education_Progress::course_lessons($course->ID)) : 0;
            echo '<article class="algq-card"><span class="algq-badge">' . esc_html__('Course', 'algq-education-center') . '</span><h2>' . esc_html(get_the_title($course)) . '</h2><p>' . esc_html(sprintf(__('%d assigned lessons', 'algq-education-center'), $lessons)) . '</p></article>';
        }
        echo '</div></section>';
        return ob_get_clean();
    }

    public static function analytics_dashboard($atts = array()) {
        if (!current_user_can('manage_options')) { return self::notice(__('Administrator access required.', 'algq-education-center')); }
        $summary = class_exists('ALGQ_Education_LMS_Advanced') ? ALGQ_Education_LMS_Advanced::analytics_summary() : array('courses'=>0,'lessons'=>0,'quizzes'=>0,'certificates'=>0);
        ob_start();
        echo '<section class="algq-edu algq-lms-analytics"><header class="algq-section-header"><p class="algq-kicker">' . esc_html__('LMS Analytics', 'algq-education-center') . '</p><h1>' . esc_html__('Analytics & Reporting', 'algq-education-center') . '</h1><p>' . esc_html__('Executive education metrics for courses, lessons, quizzes, and certificates.', 'algq-education-center') . '</p></header><div class="algq-stat-grid">';
        foreach ($summary as $label => $value) { echo '<div class="algq-stat"><strong>' . esc_html((string) $value) . '</strong><span>' . esc_html(ucwords(str_replace('_',' ', $label))) . '</span></div>'; }
        echo '</div></section>';
        return ob_get_clean();
    }

    private static function notice($message) {
        ob_start(); echo '<div class="algq-edu-notice">' . esc_html($message) . '</div>'; return ob_get_clean();
    }
}
