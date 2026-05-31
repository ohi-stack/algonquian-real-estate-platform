<?php
if (!defined('ABSPATH')) { exit; }

class ALGQ_Education_SaaS_Licensing {
    public static function init() {
        add_shortcode('algq_lms_license_status', array(__CLASS__, 'render_status'));
        add_action('wp_ajax_algq_validate_lms_license', array(__CLASS__, 'ajax_validate'));
    }

    public static function plan() {
        $options = get_option('algq_education_saas_license', array());
        return wp_parse_args(is_array($options) ? $options : array(), array(
            'license_key' => '',
            'plan' => 'internal',
            'status' => 'active',
            'expires_at' => '',
            'site_limit' => 1,
            'student_limit' => 0,
        ));
    }

    public static function is_active() {
        $plan = self::plan();
        if ('active' !== sanitize_key($plan['status'])) { return false; }
        if (!empty($plan['expires_at']) && strtotime($plan['expires_at']) < current_time('timestamp')) { return false; }
        return true;
    }

    public static function feature_allowed($feature) {
        $plan = self::plan();
        $tier = sanitize_key($plan['plan']);
        $feature = sanitize_key($feature);
        $matrix = array(
            'internal' => array('courses','certificates','woocommerce','command_center'),
            'professional' => array('courses','certificates','woocommerce','command_center','corporate_accounts','white_label'),
            'enterprise' => array('courses','certificates','woocommerce','command_center','corporate_accounts','white_label','mobile_api','scorm','multi_instructor','tenant_management'),
        );
        return in_array($feature, isset($matrix[$tier]) ? $matrix[$tier] : $matrix['internal'], true);
    }

    public static function student_limit_reached() {
        $plan = self::plan();
        $limit = absint($plan['student_limit']);
        if (!$limit) { return false; }
        $users = get_users(array('role__in'=>array('algq_student','subscriber','customer'),'fields'=>'ID','number'=>-1));
        return count($users) >= $limit;
    }

    public static function render_status($atts = array()) {
        if (!current_user_can('manage_options')) { return '<div class="algq-edu-notice">' . esc_html__('Administrator access required.', 'algq-education-center') . '</div>'; }
        $plan = self::plan();
        ob_start();
        echo '<section class="algq-edu algq-license-status"><header class="algq-section-header"><p class="algq-kicker">' . esc_html__('SaaS Licensing', 'algq-education-center') . '</p><h1>' . esc_html__('LMS License Status', 'algq-education-center') . '</h1></header><div class="algq-card-grid">';
        foreach ($plan as $key => $value) { echo '<article class="algq-card"><span class="algq-badge">' . esc_html($key) . '</span><h2>' . esc_html((string) $value) . '</h2></article>'; }
        echo '</div></section>';
        return ob_get_clean();
    }

    public static function ajax_validate() {
        if (!current_user_can('manage_options')) { wp_send_json_error(array('message'=>__('Permission denied.', 'algq-education-center')), 403); }
        check_ajax_referer('algq_lms_license', 'nonce');
        $license_key = isset($_POST['license_key']) ? sanitize_text_field(wp_unslash($_POST['license_key'])) : '';
        if (!$license_key) { wp_send_json_error(array('message'=>__('License key required.', 'algq-education-center')), 400); }
        $license = array('license_key'=>$license_key,'plan'=>'enterprise','status'=>'active','expires_at'=>'','site_limit'=>1,'student_limit'=>0);
        update_option('algq_education_saas_license', $license);
        wp_send_json_success(array('message'=>__('License validated locally.', 'algq-education-center'), 'license'=>$license));
    }
}
