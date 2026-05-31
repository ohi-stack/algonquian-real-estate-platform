<?php
if (!defined('ABSPATH')) { exit; }

class ALGQ_Education_Data_Export {
    public static function init() {
        add_shortcode('algq_education_export_tools', array(__CLASS__, 'render_tools'));
        add_action('admin_post_algq_export_learning_progress', array(__CLASS__, 'export_learning_progress'));
        add_action('admin_post_algq_export_students', array(__CLASS__, 'export_students'));
    }

    public static function render_tools($atts = array()) {
        if (!current_user_can('manage_options')) {
            return '<div class="algq-edu-notice">' . esc_html__('Administrator access required.', 'algq-education-center') . '</div>';
        }
        $progress_url = wp_nonce_url(admin_url('admin-post.php?action=algq_export_learning_progress'), 'algq_export_learning_progress');
        $students_url = wp_nonce_url(admin_url('admin-post.php?action=algq_export_students'), 'algq_export_students');
        ob_start();
        echo '<section class="algq-edu algq-export-tools"><header class="algq-section-header"><p class="algq-kicker">' . esc_html__('Data Governance', 'algq-education-center') . '</p><h1>' . esc_html__('Education Export Tools', 'algq-education-center') . '</h1><p>' . esc_html__('Export LMS records for compliance, reporting, backup, and administrative review.', 'algq-education-center') . '</p></header><div class="algq-card-grid">';
        echo '<article class="algq-card"><h2>' . esc_html__('Learning Progress Export', 'algq-education-center') . '</h2><p>' . esc_html__('Download course and lesson completion records as CSV.', 'algq-education-center') . '</p><a class="algq-btn algq-btn-gold" href="' . esc_url($progress_url) . '">' . esc_html__('Export Progress CSV', 'algq-education-center') . '</a></article>';
        echo '<article class="algq-card"><h2>' . esc_html__('Student Export', 'algq-education-center') . '</h2><p>' . esc_html__('Download student enrollment, points, streaks, and CE credit totals as CSV.', 'algq-education-center') . '</p><a class="algq-btn algq-btn-gold" href="' . esc_url($students_url) . '">' . esc_html__('Export Students CSV', 'algq-education-center') . '</a></article>';
        echo '</div></section>';
        return ob_get_clean();
    }

    public static function export_learning_progress() {
        if (!current_user_can('manage_options')) { wp_die(esc_html__('Permission denied.', 'algq-education-center')); }
        check_admin_referer('algq_export_learning_progress');
        global $wpdb;
        $table = $wpdb->prefix . 'algq_learning_progress';
        $rows = $wpdb->get_results('SELECT * FROM ' . $table . ' ORDER BY updated_at DESC', ARRAY_A);
        self::csv('algq-learning-progress.csv', array('id','user_id','course_id','lesson_id','completed','completed_at','created_at','updated_at'), $rows);
    }

    public static function export_students() {
        if (!current_user_can('manage_options')) { wp_die(esc_html__('Permission denied.', 'algq-education-center')); }
        check_admin_referer('algq_export_students');
        $users = get_users(array('number'=>-1));
        $rows = array();
        foreach ($users as $user) {
            $rows[] = array(
                'user_id' => $user->ID,
                'display_name' => $user->display_name,
                'email' => $user->user_email,
                'enrolled_courses' => implode('|', class_exists('ALGQ_Education_Enrollment') ? ALGQ_Education_Enrollment::enrolled_courses($user->ID) : array()),
                'learning_points' => absint(get_user_meta($user->ID, 'algq_learning_points', true)),
                'learning_streak' => absint(get_user_meta($user->ID, 'algq_learning_streak', true)),
                'ce_credits' => class_exists('ALGQ_Education_CE_Credits') ? ALGQ_Education_CE_Credits::user_total($user->ID) : 0,
            );
        }
        self::csv('algq-students.csv', array('user_id','display_name','email','enrolled_courses','learning_points','learning_streak','ce_credits'), $rows);
    }

    private static function csv($filename, $headers, $rows) {
        nocache_headers();
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=' . sanitize_file_name($filename));
        $out = fopen('php://output', 'w');
        fputcsv($out, $headers);
        foreach ((array) $rows as $row) {
            $line = array();
            foreach ($headers as $header) { $line[] = isset($row[$header]) ? $row[$header] : ''; }
            fputcsv($out, $line);
        }
        fclose($out);
        exit;
    }
}
