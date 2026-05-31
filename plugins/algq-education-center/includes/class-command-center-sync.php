<?php
if (!defined('ABSPATH')) { exit; }

class ALGQ_Education_Command_Center_Sync {
    public static function init() {
        add_filter('algq_command_center_kpis', array(__CLASS__, 'register_kpis'));
        add_filter('algq_command_center_health_checks', array(__CLASS__, 'register_health_checks'));
        add_shortcode('algq_education_kpi_dashboard', array(__CLASS__, 'render_dashboard'));
    }

    public static function kpis() {
        $courses = wp_count_posts('algq_course');
        $lessons = wp_count_posts('algq_lesson');
        $certs = wp_count_posts('algq_certificate');
        $assignments = wp_count_posts('algq_assignment');
        $corporate = wp_count_posts('algq_corporate_account');
        $revenue = class_exists('ALGQ_Education_Revenue_Analytics') ? ALGQ_Education_Revenue_Analytics::summary() : array('orders'=>0,'revenue'=>0);
        $users = get_users(array('fields'=>'ID','number'=>-1));
        $active_students = 0;
        $ce_total = 0.0;
        foreach ($users as $user_id) {
            if (class_exists('ALGQ_Education_Enrollment') && ALGQ_Education_Enrollment::enrolled_courses($user_id)) { $active_students++; }
            if (class_exists('ALGQ_Education_CE_Credits')) { $ce_total += (float) ALGQ_Education_CE_Credits::user_total($user_id); }
        }
        return array(
            'education_courses' => absint($courses->publish ?? 0),
            'education_lessons' => absint($lessons->publish ?? 0),
            'active_students' => absint($active_students),
            'certificates_issued' => absint($certs->publish ?? 0),
            'assignments_published' => absint($assignments->publish ?? 0),
            'ce_credits_awarded' => round($ce_total, 1),
            'education_orders' => absint($revenue['orders'] ?? 0),
            'education_revenue' => (float) ($revenue['revenue'] ?? 0),
            'corporate_accounts' => absint($corporate->publish ?? 0),
        );
    }

    public static function register_kpis($kpis) {
        foreach (self::kpis() as $key => $value) { $kpis[$key] = $value; }
        return $kpis;
    }

    public static function register_health_checks($checks) {
        $checks['education_center'] = array(
            'label' => __('Education Center', 'algq-education-center'),
            'status' => defined('ALGQ_EDU_VERSION') ? 'ok' : 'warning',
            'version' => defined('ALGQ_EDU_VERSION') ? ALGQ_EDU_VERSION : 'unknown',
            'message' => __('Enterprise LMS module loaded.', 'algq-education-center'),
        );
        return $checks;
    }

    public static function render_dashboard($atts = array()) {
        if (!current_user_can('manage_options')) { return '<div class="algq-edu-notice">' . esc_html__('Administrator access required.', 'algq-education-center') . '</div>'; }
        $kpis = self::kpis();
        ob_start();
        echo '<section class="algq-edu algq-education-kpi-dashboard"><header class="algq-section-header"><p class="algq-kicker">' . esc_html__('Executive KPI Sync', 'algq-education-center') . '</p><h1>' . esc_html__('Education Command Center KPIs', 'algq-education-center') . '</h1><p>' . esc_html__('Deep LMS metrics prepared for the Algonquian Command Center.', 'algq-education-center') . '</p></header><div class="algq-stat-grid">';
        foreach ($kpis as $key => $value) {
            $display = is_float($value) ? number_format_i18n($value, 1) : (string) $value;
            if ('education_revenue' === $key && function_exists('wc_price')) { $display = wp_strip_all_tags(wc_price($value)); }
            echo '<div class="algq-stat"><strong>' . esc_html($display) . '</strong><span>' . esc_html(ucwords(str_replace('_',' ', $key))) . '</span></div>';
        }
        echo '</div></section>';
        return ob_get_clean();
    }
}
