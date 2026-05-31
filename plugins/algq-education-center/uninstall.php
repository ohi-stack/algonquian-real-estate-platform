<?php
/**
 * Uninstall cleanup for Algonquian Education Center.
 */

if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

global $wpdb;

$delete_data = get_option('algq_education_delete_data_on_uninstall', false);

delete_option('algq_education_version');
delete_option('algq_education_options');

delete_transient('algq_education_status');

if ($delete_data) {
    $table = $wpdb->prefix . 'algq_learning_progress';
    $wpdb->query("DROP TABLE IF EXISTS {$table}");

    $post_types = array('algq_course', 'algq_lesson', 'algq_guide');
    foreach ($post_types as $post_type) {
        $posts = get_posts(array(
            'post_type' => $post_type,
            'post_status' => 'any',
            'numberposts' => -1,
            'fields' => 'ids',
        ));

        foreach ($posts as $post_id) {
            wp_delete_post(absint($post_id), true);
        }
    }
}
