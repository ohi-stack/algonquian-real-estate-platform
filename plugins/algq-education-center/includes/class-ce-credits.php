<?php
if (!defined('ABSPATH')) { exit; }

class ALGQ_Education_CE_Credits {
    public static function init() {
        add_shortcode('algq_ce_credits', array(__CLASS__, 'render_user_credits'));
        add_shortcode('algq_ce_admin_report', array(__CLASS__, 'render_admin_report'));
        add_action('algq_education_course_completed', array(__CLASS__, 'award_course_credits'), 10, 2);
    }

    public static function award_course_credits($user_id, $course_id) {
        $user_id = absint($user_id);
        $course_id = absint($course_id);
        $credits = (float) get_post_meta($course_id, 'algq_ce_credit_hours', true);
        if (!$user_id || !$course_id || $credits <= 0) { return false; }
        update_user_meta($user_id, 'algq_ce_course_' . $course_id, $credits);
        update_user_meta($user_id, 'algq_ce_course_awarded_at_' . $course_id, current_time('mysql'));
        return true;
    }

    public static function user_total($user_id) {
        $user_id = absint($user_id);
        $courses = get_posts(array('post_type'=>'algq_course','post_status'=>'publish','posts_per_page'=>-1,'fields'=>'ids'));
        $total = 0.0;
        foreach ($courses as $course_id) { $total += (float) get_user_meta($user_id, 'algq_ce_course_' . absint($course_id), true); }
        return $total;
    }

    public static function render_user_credits($atts = array()) {
        if (!is_user_logged_in()) { return '<div class="algq-edu-notice">' . esc_html__('Please log in to view continuing education credits.', 'algq-education-center') . '</div>'; }
        $user_id = get_current_user_id();
        $total = self::user_total($user_id);
        ob_start();
        echo '<section class="algq-edu algq-ce-credits"><header class="algq-section-header"><p class="algq-kicker">' . esc_html__('Continuing Education', 'algq-education-center') . '</p><h1>' . esc_html__('CE Credits', 'algq-education-center') . '</h1><p>' . esc_html__('Track completed education credit hours across eligible courses.', 'algq-education-center') . '</p></header><div class="algq-stat-grid"><div class="algq-stat"><strong>' . esc_html(number_format_i18n($total, 1)) . '</strong><span>' . esc_html__('Credit Hours', 'algq-education-center') . '</span></div></div></section>';
        return ob_get_clean();
    }

    public static function render_admin_report($atts = array()) {
        if (!current_user_can('manage_options')) { return '<div class="algq-edu-notice">' . esc_html__('Administrator access required.', 'algq-education-center') . '</div>'; }
        $users = get_users(array('number'=>100));
        ob_start();
        echo '<section class="algq-edu algq-ce-report"><header class="algq-section-header"><p class="algq-kicker">' . esc_html__('CE Reporting', 'algq-education-center') . '</p><h1>' . esc_html__('Continuing Education Report', 'algq-education-center') . '</h1></header><div class="algq-card-grid">';
        foreach ($users as $user) {
            $total = self::user_total($user->ID);
            if ($total <= 0) { continue; }
            echo '<article class="algq-card"><h2>' . esc_html($user->display_name) . '</h2><p>' . esc_html($user->user_email) . '</p><div class="algq-meta"><span>' . esc_html(number_format_i18n($total, 1) . ' credit hours') . '</span></div></article>';
        }
        echo '</div></section>';
        return ob_get_clean();
    }
}
