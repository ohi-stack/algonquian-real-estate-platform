<?php
if (!defined('ABSPATH')) { exit; }

class ALGQ_Offer_Automation_Hooks {
    public static function init() {
        add_action('algq_offer_document_generated', array(__CLASS__, 'on_document_generated'), 10, 2);
        add_action('algq_offer_saved', array(__CLASS__, 'on_offer_saved'), 10, 2);
    }

    public static function on_document_generated($offer_id, $user_id) {
        update_post_meta(absint($offer_id), '_algq_offer_last_generated_by', absint($user_id));
        update_post_meta(absint($offer_id), '_algq_offer_last_generated_at', current_time('mysql'));
        do_action('algq_automation_event', 'offer_document_generated', array('offer_id' => absint($offer_id), 'user_id' => absint($user_id)));
    }

    public static function on_offer_saved($offer_id, $user_id) {
        do_action('algq_automation_event', 'offer_saved', array('offer_id' => absint($offer_id), 'user_id' => absint($user_id)));
    }
}
