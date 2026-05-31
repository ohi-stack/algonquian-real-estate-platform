<?php
if (!defined('ABSPATH')) {
    exit;
}

class ALGQ_Education_Admin {
    public static function init() {
        add_action('admin_menu', array(__CLASS__, 'register_menu'));
        add_action('admin_init', array(__CLASS__, 'register_settings'));
    }

    public static function register_menu() {
        add_menu_page(
            __('Algonquian Education', 'algq-education-center'),
            __('Algonquian Education', 'algq-education-center'),
            'manage_options',
            'algq-education',
            array(__CLASS__, 'render_dashboard'),
            'dashicons-welcome-learn-more',
            31
        );

        add_submenu_page('algq-education', __('Dashboard', 'algq-education-center'), __('Dashboard', 'algq-education-center'), 'manage_options', 'algq-education', array(__CLASS__, 'render_dashboard'));
        add_submenu_page('algq-education', __('Courses', 'algq-education-center'), __('Courses', 'algq-education-center'), 'edit_posts', 'edit.php?post_type=algq_course');
        add_submenu_page('algq-education', __('Lessons', 'algq-education-center'), __('Lessons', 'algq-education-center'), 'edit_posts', 'edit.php?post_type=algq_lesson');
        add_submenu_page('algq-education', __('Guides', 'algq-education-center'), __('Guides', 'algq-education-center'), 'edit_posts', 'edit.php?post_type=algq_guide');
        add_submenu_page('algq-education', __('Settings', 'algq-education-center'), __('Settings', 'algq-education-center'), 'manage_options', 'algq-education-settings', array(__CLASS__, 'render_settings'));
    }

    public static function register_settings() {
        register_setting('algq_education_settings', 'algq_education_options', array(
            'type' => 'array',
            'sanitize_callback' => array(__CLASS__, 'sanitize_options'),
            'default' => array(),
        ));

        add_settings_section('algq_education_main', __('Education Center Settings', 'algq-education-center'), '__return_false', 'algq-education-settings');

        add_settings_field('brand_theme', __('Brand Theme', 'algq-education-center'), array(__CLASS__, 'field_brand_theme'), 'algq-education-settings', 'algq_education_main');
        add_settings_field('require_login_progress', __('Require Login for Progress', 'algq-education-center'), array(__CLASS__, 'field_require_login_progress'), 'algq-education-settings', 'algq_education_main');
        add_settings_field('enable_woocommerce', __('Enable WooCommerce Links', 'algq-education-center'), array(__CLASS__, 'field_enable_woocommerce'), 'algq-education-settings', 'algq_education_main');
    }

    public static function sanitize_options($options) {
        $clean = array();
        $clean['brand_theme'] = isset($options['brand_theme']) ? sanitize_key($options['brand_theme']) : 'blue_gold';
        $clean['require_login_progress'] = !empty($options['require_login_progress']) ? 1 : 0;
        $clean['enable_woocommerce'] = !empty($options['enable_woocommerce']) ? 1 : 0;
        return $clean;
    }

    public static function render_dashboard() {
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('You do not have permission to access this page.', 'algq-education-center'));
        }
        $counts = self::counts();
        echo '<div class="wrap algq-admin-wrap">';
        echo '<h1>' . esc_html__('Algonquian Education Center', 'algq-education-center') . '</h1>';
        echo '<p>' . esc_html__('LMS, education tracks, digital product library, and platform training for Algonquian Real Estate.', 'algq-education-center') . '</p>';
        echo '<div class="algq-admin-grid">';
        self::card(__('Courses', 'algq-education-center'), $counts['courses'], __('Published course records', 'algq-education-center'));
        self::card(__('Lessons', 'algq-education-center'), $counts['lessons'], __('Published lesson records', 'algq-education-center'));
        self::card(__('Guides', 'algq-education-center'), $counts['guides'], __('Published guide records', 'algq-education-center'));
        self::card(__('Progress Records', 'algq-education-center'), $counts['progress'], __('Stored learning progress rows', 'algq-education-center'));
        echo '</div>';
        echo '<div class="algq-admin-panel"><h2>' . esc_html__('Production Status', 'algq-education-center') . '</h2>';
        echo '<ul class="algq-checklist">';
        foreach (self::status_items() as $label => $status) {
            echo '<li><span class="algq-status algq-status-' . esc_attr($status ? 'ok' : 'warn') . '"></span>' . esc_html($label) . '</li>';
        }
        echo '</ul></div>';
        echo '</div>';
    }

    public static function render_settings() {
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('You do not have permission to access this page.', 'algq-education-center'));
        }
        echo '<div class="wrap algq-admin-wrap">';
        echo '<h1>' . esc_html__('Education Center Settings', 'algq-education-center') . '</h1>';
        echo '<form method="post" action="options.php">';
        settings_fields('algq_education_settings');
        do_settings_sections('algq-education-settings');
        submit_button(__('Save Settings', 'algq-education-center'));
        echo '</form></div>';
    }

    public static function field_brand_theme() {
        $options = get_option('algq_education_options', array());
        $value = isset($options['brand_theme']) ? sanitize_key($options['brand_theme']) : 'blue_gold';
        echo '<select name="algq_education_options[brand_theme]">';
        echo '<option value="blue_gold" ' . selected($value, 'blue_gold', false) . '>' . esc_html__('Blue / Gold', 'algq-education-center') . '</option>';
        echo '<option value="black_gold" ' . selected($value, 'black_gold', false) . '>' . esc_html__('Black / Gold', 'algq-education-center') . '</option>';
        echo '<option value="white_gold" ' . selected($value, 'white_gold', false) . '>' . esc_html__('White / Gold', 'algq-education-center') . '</option>';
        echo '</select>';
    }

    public static function field_require_login_progress() {
        $options = get_option('algq_education_options', array());
        $checked = !empty($options['require_login_progress']);
        echo '<label><input type="checkbox" name="algq_education_options[require_login_progress]" value="1" ' . checked($checked, true, false) . ' /> ' . esc_html__('Require users to be logged in before progress is displayed.', 'algq-education-center') . '</label>';
    }

    public static function field_enable_woocommerce() {
        $options = get_option('algq_education_options', array());
        $checked = !empty($options['enable_woocommerce']);
        echo '<label><input type="checkbox" name="algq_education_options[enable_woocommerce]" value="1" ' . checked($checked, true, false) . ' /> ' . esc_html__('Show WooCommerce-linked product access buttons where configured.', 'algq-education-center') . '</label>';
    }

    private static function counts() {
        global $wpdb;
        $progress_table = $wpdb->prefix . 'algq_learning_progress';
        $progress = $wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $progress_table)) === $progress_table ? absint($wpdb->get_var("SELECT COUNT(*) FROM {$progress_table}")) : 0;
        return array(
            'courses' => wp_count_posts('algq_course')->publish ?? 0,
            'lessons' => wp_count_posts('algq_lesson')->publish ?? 0,
            'guides' => wp_count_posts('algq_guide')->publish ?? 0,
            'progress' => $progress,
        );
    }

    private static function card($title, $value, $description) {
        echo '<div class="algq-admin-card">';
        echo '<strong>' . esc_html($title) . '</strong>';
        echo '<span>' . esc_html((string) $value) . '</span>';
        echo '<p>' . esc_html($description) . '</p>';
        echo '</div>';
    }

    private static function status_items() {
        return array(
            __('Plugin bootstrap loaded', 'algq-education-center') => defined('ALGQ_EDU_VERSION'),
            __('Course post type registered', 'algq-education-center') => post_type_exists('algq_course'),
            __('Lesson post type registered', 'algq-education-center') => post_type_exists('algq_lesson'),
            __('Guide post type registered', 'algq-education-center') => post_type_exists('algq_guide'),
            __('WooCommerce detected', 'algq-education-center') => class_exists('WooCommerce'),
        );
    }
}
