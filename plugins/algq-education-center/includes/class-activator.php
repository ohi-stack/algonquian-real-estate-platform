<?php
if (!defined('ABSPATH')) { exit; }

class ALGQ_Education_Activator {
    public static function activate() {
        self::create_tables();
        self::create_pages();
        update_option('algq_education_version', ALGQ_EDU_VERSION);
        flush_rewrite_rules();
    }

    private static function create_tables() {
        global $wpdb;
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        $charset_collate = $wpdb->get_charset_collate();
        $table = $wpdb->prefix . 'algq_learning_progress';
        $sql = "CREATE TABLE {$table} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id BIGINT UNSIGNED NOT NULL,
            course_id BIGINT UNSIGNED NOT NULL,
            lesson_id BIGINT UNSIGNED NOT NULL,
            completed TINYINT(1) NOT NULL DEFAULT 0,
            completed_at DATETIME NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY user_course (user_id, course_id),
            KEY lesson_id (lesson_id)
        ) {$charset_collate};";
        dbDelta($sql);
    }

    private static function create_pages() {
        $pages = array(
            'education' => array('title' => 'Education Center', 'content' => '[algq_education_home]'),
            'education/courses' => array('title' => 'Course Library', 'content' => '[algq_course_list]'),
            'education/sellers' => array('title' => 'Seller Education', 'content' => '[algq_education_track type="seller"]'),
            'education/buyers' => array('title' => 'Buyer Education', 'content' => '[algq_education_track type="buyer"]'),
            'education/lenders' => array('title' => 'Lender Education', 'content' => '[algq_education_track type="lender"]'),
            'education/acquisition' => array('title' => 'Acquisition Training', 'content' => '[algq_education_track type="acquisition"]'),
            'education/platform-training' => array('title' => 'Platform Training', 'content' => '[algq_platform_training]'),
            'education/products' => array('title' => 'Digital Product Library', 'content' => '[algq_product_library]'),
            'education/progress' => array('title' => 'My Learning Progress', 'content' => '[algq_user_progress]'),
            'plugin/education-center' => array('title' => 'Algonquian Education Center', 'content' => '[algq_education_home]'),
            'plugin/education-center/start' => array('title' => 'Education Center Getting Started', 'content' => '[algq_education_track type="start"]'),
            'plugin/education-center/docs' => array('title' => 'Education Center Documentation', 'content' => '[algq_platform_training]'),
        );
        foreach ($pages as $path => $data) {
            $slug = basename($path);
            if (get_page_by_path($path)) { continue; }
            wp_insert_post(array(
                'post_title' => sanitize_text_field($data['title']),
                'post_name' => sanitize_title($slug),
                'post_content' => wp_kses_post($data['content']),
                'post_status' => 'publish',
                'post_type' => 'page',
            ));
        }
    }
}
