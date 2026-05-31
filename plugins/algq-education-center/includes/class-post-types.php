<?php
if (!defined('ABSPATH')) {
    exit;
}

class ALGQ_Education_Post_Types {
    public static function init() {
        add_action('init', array(__CLASS__, 'register'));
        add_action('add_meta_boxes', array(__CLASS__, 'add_meta_boxes'));
        add_action('save_post_algq_course', array(__CLASS__, 'save_course_meta'), 10, 2);
        add_action('save_post_algq_lesson', array(__CLASS__, 'save_lesson_meta'), 10, 2);
        add_action('save_post_algq_guide', array(__CLASS__, 'save_guide_meta'), 10, 2);
    }

    public static function register() {
        self::register_course();
        self::register_lesson();
        self::register_guide();
    }

    private static function register_course() {
        register_post_type('algq_course', array(
            'labels' => array(
                'name' => __('Courses', 'algq-education-center'),
                'singular_name' => __('Course', 'algq-education-center'),
                'add_new_item' => __('Add New Course', 'algq-education-center'),
                'edit_item' => __('Edit Course', 'algq-education-center'),
            ),
            'public' => false,
            'show_ui' => true,
            'show_in_menu' => false,
            'supports' => array('title', 'editor', 'excerpt', 'thumbnail', 'revisions'),
            'capability_type' => 'post',
            'map_meta_cap' => true,
            'has_archive' => false,
            'rewrite' => false,
        ));
    }

    private static function register_lesson() {
        register_post_type('algq_lesson', array(
            'labels' => array(
                'name' => __('Lessons', 'algq-education-center'),
                'singular_name' => __('Lesson', 'algq-education-center'),
                'add_new_item' => __('Add New Lesson', 'algq-education-center'),
                'edit_item' => __('Edit Lesson', 'algq-education-center'),
            ),
            'public' => false,
            'show_ui' => true,
            'show_in_menu' => false,
            'supports' => array('title', 'editor', 'excerpt', 'thumbnail', 'revisions'),
            'capability_type' => 'post',
            'map_meta_cap' => true,
            'has_archive' => false,
            'rewrite' => false,
        ));
    }

    private static function register_guide() {
        register_post_type('algq_guide', array(
            'labels' => array(
                'name' => __('Guides', 'algq-education-center'),
                'singular_name' => __('Guide', 'algq-education-center'),
                'add_new_item' => __('Add New Guide', 'algq-education-center'),
                'edit_item' => __('Edit Guide', 'algq-education-center'),
            ),
            'public' => false,
            'show_ui' => true,
            'show_in_menu' => false,
            'supports' => array('title', 'editor', 'excerpt', 'thumbnail', 'revisions'),
            'capability_type' => 'post',
            'map_meta_cap' => true,
            'has_archive' => false,
            'rewrite' => false,
        ));
    }

    public static function add_meta_boxes() {
        add_meta_box('algq_course_details', __('Course Details', 'algq-education-center'), array(__CLASS__, 'render_course_meta'), 'algq_course', 'normal', 'high');
        add_meta_box('algq_lesson_details', __('Lesson Details', 'algq-education-center'), array(__CLASS__, 'render_lesson_meta'), 'algq_lesson', 'normal', 'high');
        add_meta_box('algq_guide_details', __('Guide Details', 'algq-education-center'), array(__CLASS__, 'render_guide_meta'), 'algq_guide', 'normal', 'high');
    }

    public static function render_course_meta($post) {
        wp_nonce_field('algq_save_course_meta', 'algq_course_nonce');
        self::field($post->ID, 'algq_course_audience', 'Audience', 'seller, buyer, lender, internal, public');
        self::field($post->ID, 'algq_course_access_level', 'Access Level', 'public, registered, paid, internal, admin');
        self::field($post->ID, 'algq_course_duration', 'Estimated Duration', 'Example: 45 minutes');
        self::field($post->ID, 'algq_course_difficulty', 'Difficulty', 'beginner, intermediate, advanced');
        self::field($post->ID, 'algq_course_product_id', 'WooCommerce Product ID', 'Optional numeric product ID');
    }

    public static function render_lesson_meta($post) {
        wp_nonce_field('algq_save_lesson_meta', 'algq_lesson_nonce');
        self::field($post->ID, 'algq_lesson_course_id', 'Parent Course ID', 'Numeric algq_course post ID');
        self::field($post->ID, 'algq_lesson_order', 'Lesson Order', 'Numeric order');
        self::field($post->ID, 'algq_lesson_video_url', 'Video URL', 'Optional video URL');
        self::field($post->ID, 'algq_lesson_download_url', 'Download URL', 'Optional file URL');
        self::checkbox($post->ID, 'algq_lesson_completion_required', 'Completion Required');
    }

    public static function render_guide_meta($post) {
        wp_nonce_field('algq_save_guide_meta', 'algq_guide_nonce');
        self::field($post->ID, 'algq_guide_category', 'Guide Category', 'seller, buyer, lender, acquisition, platform');
        self::field($post->ID, 'algq_guide_access_level', 'Access Level', 'public, registered, paid, internal, admin');
        self::field($post->ID, 'algq_guide_download_url', 'Download URL', 'Optional PDF or file URL');
        self::field($post->ID, 'algq_guide_product_id', 'WooCommerce Product ID', 'Optional numeric product ID');
        self::field($post->ID, 'algq_guide_related_plugin', 'Related Plugin', 'Optional plugin slug');
    }

    private static function field($post_id, $key, $label, $description = '') {
        $value = get_post_meta($post_id, $key, true);
        echo '<p><label for="' . esc_attr($key) . '"><strong>' . esc_html($label) . '</strong></label><br />';
        echo '<input type="text" id="' . esc_attr($key) . '" name="' . esc_attr($key) . '" value="' . esc_attr($value) . '" class="widefat" />';
        if ($description) {
            echo '<span class="description">' . esc_html($description) . '</span>';
        }
        echo '</p>';
    }

    private static function checkbox($post_id, $key, $label) {
        $value = get_post_meta($post_id, $key, true);
        echo '<p><label><input type="checkbox" name="' . esc_attr($key) . '" value="1" ' . checked('1', $value, false) . ' /> ' . esc_html($label) . '</label></p>';
    }

    public static function save_course_meta($post_id, $post) {
        if (!self::can_save($post_id, 'algq_course_nonce', 'algq_save_course_meta')) { return; }
        self::save_text_fields($post_id, array('algq_course_audience', 'algq_course_access_level', 'algq_course_duration', 'algq_course_difficulty'));
        self::save_absint_fields($post_id, array('algq_course_product_id'));
    }

    public static function save_lesson_meta($post_id, $post) {
        if (!self::can_save($post_id, 'algq_lesson_nonce', 'algq_save_lesson_meta')) { return; }
        self::save_absint_fields($post_id, array('algq_lesson_course_id', 'algq_lesson_order'));
        self::save_url_fields($post_id, array('algq_lesson_video_url', 'algq_lesson_download_url'));
        update_post_meta($post_id, 'algq_lesson_completion_required', isset($_POST['algq_lesson_completion_required']) ? '1' : '0');
    }

    public static function save_guide_meta($post_id, $post) {
        if (!self::can_save($post_id, 'algq_guide_nonce', 'algq_save_guide_meta')) { return; }
        self::save_text_fields($post_id, array('algq_guide_category', 'algq_guide_access_level', 'algq_guide_related_plugin'));
        self::save_url_fields($post_id, array('algq_guide_download_url'));
        self::save_absint_fields($post_id, array('algq_guide_product_id'));
    }

    private static function can_save($post_id, $nonce_field, $nonce_action) {
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) { return false; }
        if (!isset($_POST[$nonce_field]) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST[$nonce_field])), $nonce_action)) { return false; }
        if (!current_user_can('edit_post', $post_id)) { return false; }
        return true;
    }

    private static function save_text_fields($post_id, $fields) {
        foreach ($fields as $field) {
            $value = isset($_POST[$field]) ? sanitize_text_field(wp_unslash($_POST[$field])) : '';
            update_post_meta($post_id, $field, $value);
        }
    }

    private static function save_absint_fields($post_id, $fields) {
        foreach ($fields as $field) {
            $value = isset($_POST[$field]) ? absint($_POST[$field]) : 0;
            update_post_meta($post_id, $field, $value);
        }
    }

    private static function save_url_fields($post_id, $fields) {
        foreach ($fields as $field) {
            $value = isset($_POST[$field]) ? esc_url_raw(wp_unslash($_POST[$field])) : '';
            update_post_meta($post_id, $field, $value);
        }
    }
}
