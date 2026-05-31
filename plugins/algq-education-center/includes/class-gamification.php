<?php
if (!defined('ABSPATH')) { exit; }

class ALGQ_Education_Gamification {
    public static function init() {
        add_action('wp_ajax_algq_record_learning_activity', array(__CLASS__, 'ajax_record_activity'));
        add_shortcode('algq_learning_streaks', array(__CLASS__, 'learning_streaks'));
        add_shortcode('algq_lms_leaderboard', array(__CLASS__, 'leaderboard'));
    }

    public static function record_activity($user_id, $points = 1) {
        $user_id = absint($user_id);
        $points = absint($points);
        if (!$user_id) { return false; }
        $today = current_time('Y-m-d');
        $last = get_user_meta($user_id, 'algq_last_learning_activity', true);
        $streak = absint(get_user_meta($user_id, 'algq_learning_streak', true));
        if ($last !== $today) {
            $yesterday = date('Y-m-d', strtotime(current_time('Y-m-d') . ' -1 day'));
            $streak = ($last === $yesterday) ? $streak + 1 : 1;
            update_user_meta($user_id, 'algq_learning_streak', $streak);
            update_user_meta($user_id, 'algq_last_learning_activity', $today);
        }
        $total_points = absint(get_user_meta($user_id, 'algq_learning_points', true)) + $points;
        update_user_meta($user_id, 'algq_learning_points', $total_points);
        return array('streak'=>$streak,'points'=>$total_points);
    }

    public static function learning_streaks($atts = array()) {
        if (!is_user_logged_in()) { return '<div class="algq-edu-notice">' . esc_html__('Please log in to view learning streaks.', 'algq-education-center') . '</div>'; }
        $user_id = get_current_user_id();
        $streak = absint(get_user_meta($user_id, 'algq_learning_streak', true));
        $points = absint(get_user_meta($user_id, 'algq_learning_points', true));
        ob_start();
        echo '<section class="algq-edu algq-learning-streaks"><header class="algq-section-header"><p class="algq-kicker">' . esc_html__('Gamification', 'algq-education-center') . '</p><h1>' . esc_html__('Learning Streaks', 'algq-education-center') . '</h1></header><div class="algq-stat-grid"><div class="algq-stat"><strong>' . esc_html((string) $streak) . '</strong><span>' . esc_html__('Day Streak', 'algq-education-center') . '</span></div><div class="algq-stat"><strong>' . esc_html((string) $points) . '</strong><span>' . esc_html__('Learning Points', 'algq-education-center') . '</span></div></div></section>';
        return ob_get_clean();
    }

    public static function leaderboard($atts = array()) {
        if (!current_user_can('edit_posts')) { return '<div class="algq-edu-notice">' . esc_html__('Instructor access required.', 'algq-education-center') . '</div>'; }
        $users = get_users(array('meta_key'=>'algq_learning_points','orderby'=>'meta_value_num','order'=>'DESC','number'=>10));
        ob_start();
        echo '<section class="algq-edu algq-leaderboard"><header class="algq-section-header"><p class="algq-kicker">' . esc_html__('Leaderboard', 'algq-education-center') . '</p><h1>' . esc_html__('Top Learners', 'algq-education-center') . '</h1></header><div class="algq-card-grid">';
        foreach ($users as $user) {
            $points = absint(get_user_meta($user->ID, 'algq_learning_points', true));
            $streak = absint(get_user_meta($user->ID, 'algq_learning_streak', true));
            echo '<article class="algq-card"><h2>' . esc_html($user->display_name) . '</h2><div class="algq-meta"><span>' . esc_html($points . ' points') . '</span><span>' . esc_html($streak . ' day streak') . '</span></div></article>';
        }
        if (!$users) { echo '<article class="algq-card"><h2>' . esc_html__('No learner activity yet.', 'algq-education-center') . '</h2></article>'; }
        echo '</div></section>';
        return ob_get_clean();
    }

    public static function ajax_record_activity() {
        if (!is_user_logged_in()) { wp_send_json_error(array('message'=>__('Login required.', 'algq-education-center')), 401); }
        check_ajax_referer('algq_education_activity', 'nonce');
        $points = isset($_POST['points']) ? absint($_POST['points']) : 1;
        wp_send_json_success(self::record_activity(get_current_user_id(), $points));
    }
}
