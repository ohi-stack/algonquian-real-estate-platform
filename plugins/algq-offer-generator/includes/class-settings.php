<?php
if (!defined('ABSPATH')) { exit; }

class ALGQ_Offer_Settings {
    public static function init() {
        add_action('admin_init', array(__CLASS__, 'register'));
    }

    public static function register() {
        register_setting('algq_offer_settings', 'algq_offer_default_strategy', array('sanitize_callback' => 'sanitize_text_field'));
        register_setting('algq_offer_settings', 'algq_offer_company_name', array('sanitize_callback' => 'sanitize_text_field'));
    }
}
