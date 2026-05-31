<?php
if (!defined('ABSPATH')) { exit; }

class ALGQ_Education_Audit_Log {
    public static function init() {
        add_shortcode('algq_education_audit_log', array(__CLASS__, 'render_log'));
    }

    public static function table() {
        global $wpdb;
        return $wpdb->prefix . 'algq_education_audit_log';
    }

    public static function create_table() {
        global $wpdb;
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        $charset_collate = $wpdb->get_charset_collate();
        $table = self::table();
        $sql = "CREATE TABLE {$table} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
            event_type VARCHAR(120) NOT NULL,
            object_type VARCHAR(120) NOT NULL DEFAULT '',
            object_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
            message TEXT NULL,
            ip_address VARCHAR(100) NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY event_type (event_type),
            KEY object_ref (object_type, object_id),
            KEY user_id (user_id)
        ) {$charset_collate};";
        dbDelta($sql);
    }

    public static function record($event_type, $object_type = '', $object_id = 0, $message = '', $user_id = 0) {
        global $wpdb;
        $user_id = $user_id ? absint($user_id) : get_current_user_id();
        return false !== $wpdb->insert(self::table(), array(
            'user_id' => $user_id,
            'event_type' => sanitize_key($event_type),
            'object_type' => sanitize_key($object_type),
            'object_id' => absint($object_id),
            'message' => sanitize_textarea_field($message),
            'ip_address' => isset($_SERVER['REMOTE_ADDR']) ? sanitize_text_field(wp_unslash($_SERVER['REMOTE_ADDR'])) : '',
            'created_at' => current_time('mysql'),
        ), array('%d','%s','%s','%d','%s','%s','%s'));
    }

    public static function recent($limit = 50) {
        global $wpdb;
        $limit = absint($limit) ?: 50;
        return $wpdb->get_results($wpdb->prepare('SELECT * FROM ' . self::table() . ' ORDER BY created_at DESC LIMIT %d', $limit));
    }

    public static function render_log($atts = array()) {
        if (!current_user_can('manage_options')) {
            return '<div class="algq-edu-notice">' . esc_html__('Administrator access required.', 'algq-education-center') . '</div>';
        }
        $atts = shortcode_atts(array('limit'=>50), $atts, 'algq_education_audit_log');
        $rows = self::recent(absint($atts['limit']));
        ob_start();
        echo '<section class="algq-edu algq-audit-log"><header class="algq-section-header"><p class="algq-kicker">' . esc_html__('Governance', 'algq-education-center') . '</p><h1>' . esc_html__('Education Audit Log', 'algq-education-center') . '</h1><p>' . esc_html__('Administrative and learning activity records for compliance review.', 'algq-education-center') . '</p></header><div class="algq-card-grid">';
        if ($rows) {
            foreach ($rows as $row) {
                echo '<article class="algq-card"><span class="algq-badge">' . esc_html($row->event_type) . '</span><h2>' . esc_html($row->object_type . ' #' . $row->object_id) . '</h2><p>' . esc_html($row->message) . '</p><div class="algq-meta"><span>' . esc_html($row->created_at) . '</span><span>' . esc_html('User ' . absint($row->user_id)) . '</span></div></article>';
            }
        } else {
            echo '<article class="algq-card"><h2>' . esc_html__('No audit records yet.', 'algq-education-center') . '</h2></article>';
        }
        echo '</div></section>';
        return ob_get_clean();
    }
}
