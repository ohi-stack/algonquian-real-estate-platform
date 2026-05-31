<?php
if (!defined('ABSPATH')) { exit; }

class ALGQ_Education_Admin_Settings_Framework {
    public static function init() {
        add_action('admin_init', array(__CLASS__, 'register_settings'));
        add_shortcode('algq_education_settings_overview', array(__CLASS__, 'settings_overview'));
    }

    public static function defaults() {
        return array(
            'enable_email_notifications' => 1,
            'enable_gamification' => 1,
            'enable_certificates' => 1,
            'enable_ce_credits' => 1,
            'enable_mobile_api' => 1,
            'enable_rest_api' => 1,
            'enable_white_label' => 0,
            'enable_corporate_accounts' => 0,
            'certificate_issuer_name' => 'Algonquian Real Estate Education Center',
            'data_retention_days' => 2555,
        );
    }

    public static function get($key = null) {
        $options = wp_parse_args(get_option('algq_education_enterprise_options', array()), self::defaults());
        return $key ? ($options[$key] ?? null) : $options;
    }

    public static function register_settings() {
        register_setting('algq_education_enterprise_settings', 'algq_education_enterprise_options', array('type'=>'array','sanitize_callback'=>array(__CLASS__, 'sanitize'),'default'=>self::defaults()));
        add_settings_section('algq_education_enterprise_main', __('Enterprise LMS Settings', 'algq-education-center'), '__return_false', 'algq-education-enterprise-settings');
        foreach (self::defaults() as $key => $default) {
            add_settings_field($key, ucwords(str_replace('_',' ', $key)), array(__CLASS__, 'render_field'), 'algq-education-enterprise-settings', 'algq_education_enterprise_main', array('key'=>$key, 'default'=>$default));
        }
    }

    public static function sanitize($options) {
        $clean = self::defaults();
        foreach ($clean as $key => $default) {
            if (is_int($default)) { $clean[$key] = !empty($options[$key]) ? absint($options[$key]) : 0; }
            else { $clean[$key] = isset($options[$key]) ? sanitize_text_field(wp_unslash($options[$key])) : $default; }
        }
        return $clean;
    }

    public static function render_field($args) {
        $key = sanitize_key($args['key']);
        $value = self::get($key);
        if (is_int($args['default'])) {
            echo '<input type="checkbox" name="algq_education_enterprise_options[' . esc_attr($key) . ']" value="1" ' . checked(1, absint($value), false) . ' />';
        } else {
            echo '<input type="text" class="regular-text" name="algq_education_enterprise_options[' . esc_attr($key) . ']" value="' . esc_attr($value) . '" />';
        }
    }

    public static function settings_overview($atts = array()) {
        if (!current_user_can('manage_options')) { return '<div class="algq-edu-notice">' . esc_html__('Administrator access required.', 'algq-education-center') . '</div>'; }
        $options = self::get();
        ob_start();
        echo '<section class="algq-edu algq-settings-overview"><header class="algq-section-header"><p class="algq-kicker">' . esc_html__('Administration', 'algq-education-center') . '</p><h1>' . esc_html__('Enterprise LMS Settings', 'algq-education-center') . '</h1></header><div class="algq-card-grid">';
        foreach ($options as $key => $value) { echo '<article class="algq-card"><span class="algq-badge">' . esc_html($key) . '</span><h2>' . esc_html(is_scalar($value) ? (string) $value : '') . '</h2></article>'; }
        echo '</div></section>';
        return ob_get_clean();
    }
}
