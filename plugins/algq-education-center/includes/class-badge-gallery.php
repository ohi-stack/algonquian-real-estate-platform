<?php
if (!defined('ABSPATH')) { exit; }

class ALGQ_Education_Badge_Gallery {
    public static function init() {
        add_shortcode('algq_badge_gallery', array(__CLASS__, 'badge_gallery'));
        add_shortcode('algq_student_transcript', array(__CLASS__, 'student_transcript'));
    }

    public static function badge_gallery($atts = array()) {
        if (!is_user_logged_in()) { return self::notice(__('Please log in to view badges.', 'algq-education-center')); }
        $user_id = get_current_user_id();
        $badges = get_user_meta($user_id, 'algq_awarded_badges', true);
        $badges = is_array($badges) ? array_map('absint', $badges) : array();
        ob_start();
        echo '<section class="algq-edu algq-badge-gallery"><header class="algq-section-header"><p class="algq-kicker">' . esc_html__('Achievements', 'algq-education-center') . '</p><h1>' . esc_html__('Badge Gallery', 'algq-education-center') . '</h1><p>' . esc_html__('View earned badges and achievement history.', 'algq-education-center') . '</p></header><div class="algq-card-grid">';
        if ($badges) {
            foreach ($badges as $badge_id) {
                if ('algq_badge' !== get_post_type($badge_id)) { continue; }
                $awarded = get_user_meta($user_id, 'algq_badge_awarded_at_' . $badge_id, true);
                echo '<article class="algq-card algq-badge-card"><span class="algq-badge">' . esc_html__('Badge', 'algq-education-center') . '</span><h2>' . esc_html(get_the_title($badge_id)) . '</h2><p>' . esc_html(get_the_excerpt($badge_id) ? get_the_excerpt($badge_id) : wp_trim_words(wp_strip_all_tags(get_post_field('post_content', $badge_id)), 22)) . '</p>';
                if ($awarded) { echo '<div class="algq-meta"><span>' . esc_html(sprintf(__('Awarded %s', 'algq-education-center'), $awarded)) . '</span></div>'; }
                echo '</article>';
            }
        } else {
            echo '<article class="algq-card"><h2>' . esc_html__('No badges awarded yet.', 'algq-education-center') . '</h2><p>' . esc_html__('Complete courses, quizzes, and achievements to earn badges.', 'algq-education-center') . '</p></article>';
        }
        echo '</div></section>';
        return ob_get_clean();
    }

    public static function student_transcript($atts = array()) {
        if (!is_user_logged_in()) { return self::notice(__('Please log in to view your transcript.', 'algq-education-center')); }
        $user_id = get_current_user_id();
        $courses = class_exists('ALGQ_Education_Enrollment') ? ALGQ_Education_Enrollment::enrolled_courses($user_id) : array();
        ob_start();
        echo '<section class="algq-edu algq-student-transcript"><header class="algq-section-header"><p class="algq-kicker">' . esc_html__('Transcript', 'algq-education-center') . '</p><h1>' . esc_html__('Student Transcript', 'algq-education-center') . '</h1><p>' . esc_html__('Formal record of enrolled courses, completion percentages, certificates, and quiz results.', 'algq-education-center') . '</p></header><div class="algq-card-grid">';
        if ($courses) {
            foreach ($courses as $course_id) {
                $percent = class_exists('ALGQ_Education_Progress') ? ALGQ_Education_Progress::course_percentage($user_id, $course_id) : 0;
                $certificate_id = get_user_meta($user_id, 'algq_certificate_course_' . absint($course_id), true);
                echo '<article class="algq-card"><h2>' . esc_html(get_the_title($course_id)) . '</h2><div class="algq-progress"><span style="width:' . esc_attr((string) $percent) . '%"></span></div><p>' . esc_html(sprintf(__('%d%% complete', 'algq-education-center'), $percent)) . '</p><div class="algq-meta"><span>' . esc_html($certificate_id ? __('Certificate Issued', 'algq-education-center') : __('Certificate Pending', 'algq-education-center')) . '</span></div></article>';
            }
        } else {
            echo '<article class="algq-card"><h2>' . esc_html__('No transcript records yet.', 'algq-education-center') . '</h2><p>' . esc_html__('Enroll in courses to begin building a transcript.', 'algq-education-center') . '</p></article>';
        }
        echo '</div></section>';
        return ob_get_clean();
    }

    private static function notice($message) {
        ob_start(); echo '<div class="algq-edu-notice">' . esc_html($message) . '</div>'; return ob_get_clean();
    }
}
