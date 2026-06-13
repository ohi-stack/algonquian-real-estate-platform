<?php
if (!defined('ABSPATH')) { exit; }

class ALGQ_Offer_Audit_Log {
    public static function init() {
        add_action('algq_offer_saved', array(__CLASS__, 'log_offer_saved'), 10, 2);
        add_action('algq_offer_document_generated', array(__CLASS__, 'log_document_generated'), 10, 2);
    }

    public static function create_table() {
        global $wpdb;
        $table = $wpdb->prefix . 'algq_offer_audit_log';
        $charset = $wpdb->get_charset_collate();
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta("CREATE TABLE $table (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            event varchar(120) NOT NULL,
            object_id bigint(20) unsigned NOT NULL DEFAULT 0,
            user_id bigint(20) unsigned NOT NULL DEFAULT 0,
            details longtext NULL,
            created_at datetime NOT NULL,
            PRIMARY KEY  (id),
            KEY event (event),
            KEY object_id (object_id)
        ) $charset;");
    }

    public static function record($event, $object_id = 0, $details = array()) {
        global $wpdb;
        $wpdb->insert($wpdb->prefix . 'algq_offer_audit_log', array(
            'event' => sanitize_key($event),
            'object_id' => absint($object_id),
            'user_id' => get_current_user_id(),
            'details' => wp_json_encode($details),
            'created_at' => current_time('mysql'),
        ));
    }

    public static function log_offer_saved($offer_id, $user_id) { self::record('offer_saved', $offer_id, array('user_id' => absint($user_id))); }
    public static function log_document_generated($offer_id, $user_id) { self::record('document_generated', $offer_id, array('user_id' => absint($user_id))); }
}
