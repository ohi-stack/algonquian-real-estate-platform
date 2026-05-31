<?php
if (!defined('ABSPATH')) { exit; }

class ALGQ_Education_Privacy_Tools {
    public static function init() {
        add_shortcode('algq_education_privacy_tools', array(__CLASS__, 'render_tools'));
        add_action('admin_post_algq_export_user_learning_data', array(__CLASS__, 'export_user_data'));
        add_action('admin_post_algq_delete_user_learning_data', array(__CLASS__, 'delete_user_data'));
    }

    public static function render_tools($atts = array()) {
        if (!current_user_can('manage_options')) {
            return '<div class="algq-edu-notice">' . esc_html__('Administrator access required.', 'algq-education-center') . '</div>';
        }
        ob_start();
        echo '<section class="algq-edu algq-privacy-tools"><header class="algq-section-header"><p class="algq-kicker">' . esc_html__('Privacy', 'algq-education-center') . '</p><h1>' . esc_html__('Education Privacy Tools', 'algq-education-center') . '</h1><p>' . esc_html__('Export or delete learner-specific LMS records for compliance and data-governance review.', 'algq-education-center') . '</p></header>';
        echo '<div class="algq-card"><form method="get" action="' . esc_url(admin_url('admin-post.php')) . '"><input type="hidden" name="action" value="algq_export_user_learning_data" />' . wp_nonce_field('algq_export_user_learning_data', '_wpnonce', true, false) . '<p><label><strong>' . esc_html__('User ID', 'algq-education-center') . '</strong></label><br><input type="number" name="user_id" min="1" required /></p><button class="algq-btn algq-btn-gold" type="submit">' . esc_html__('Export User Learning Data', 'algq-education-center') . '</button></form></div>';
        echo '<div class="algq-card"><form method="post" action="' . esc_url(admin_url('admin-post.php')) . '"><input type="hidden" name="action" value="algq_delete_user_learning_data" />' . wp_nonce_field('algq_delete_user_learning_data', '_wpnonce', true, false) . '<p><label><strong>' . esc_html__('User ID', 'algq-education-center') . '</strong></label><br><input type="number" name="user_id" min="1" required /></p><button class="algq-btn algq-btn-outline" type="submit">' . esc_html__('Delete User Learning Data', 'algq-education-center') . '</button></form></div>';
        echo '</section>';
        return ob_get_clean();
    }

    public static function user_payload($user_id) {
        $user_id = absint($user_id);
        $user = get_userdata($user_id);
        if (!$user) { return array(); }
        return array(
            'user_id' => $user_id,
            'display_name' => $user->display_name,
            'email' => $user->user_email,
            'enrolled_courses' => class_exists('ALGQ_Education_Enrollment') ? ALGQ_Education_Enrollment::enrolled_courses($user_id) : array(),
            'learning_points' => absint(get_user_meta($user_id, 'algq_learning_points', true)),
            'learning_streak' => absint(get_user_meta($user_id, 'algq_learning_streak', true)),
            'ce_credits' => class_exists('ALGQ_Education_CE_Credits') ? ALGQ_Education_CE_Credits::user_total($user_id) : 0,
            'badges' => get_user_meta($user_id, 'algq_awarded_badges', true),
        );
    }

    public static function export_user_data() {
        if (!current_user_can('manage_options')) { wp_die(esc_html__('Permission denied.', 'algq-education-center')); }
        check_admin_referer('algq_export_user_learning_data');
        $user_id = isset($_GET['user_id']) ? absint($_GET['user_id']) : 0;
        $payload = self::user_payload($user_id);
        if (!$payload) { wp_die(esc_html__('User not found.', 'algq-education-center')); }
        nocache_headers();
        header('Content-Type: application/json; charset=utf-8');
        header('Content-Disposition: attachment; filename=algq-user-learning-data-' . $user_id . '.json');
        echo wp_json_encode($payload, JSON_PRETTY_PRINT);
        exit;
    }

    public static function delete_user_data() {
        if (!current_user_can('manage_options')) { wp_die(esc_html__('Permission denied.', 'algq-education-center')); }
        check_admin_referer('algq_delete_user_learning_data');
        $user_id = isset($_POST['user_id']) ? absint($_POST['user_id']) : 0;
        if (!$user_id || !get_userdata($user_id)) { wp_die(esc_html__('User not found.', 'algq-education-center')); }
        global $wpdb;
        $wpdb->delete($wpdb->prefix . 'algq_learning_progress', array('user_id'=>$user_id), array('%d'));
        foreach (array('algq_enrolled_courses','algq_learning_points','algq_learning_streak','algq_last_learning_activity','algq_awarded_badges','algq_xapi_statements','algq_gradebook_note') as $key) { delete_user_meta($user_id, $key); }
        if (class_exists('ALGQ_Education_Audit_Log')) { ALGQ_Education_Audit_Log::record('privacy_delete', 'user', $user_id, 'User learning data deleted.'); }
        wp_safe_redirect(admin_url('admin.php?page=algq-education'));
        exit;
    }
}
