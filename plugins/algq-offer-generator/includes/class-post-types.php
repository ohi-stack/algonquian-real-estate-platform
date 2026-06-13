<?php
if (!defined('ABSPATH')) { exit; }

class ALGQ_Offer_Post_Types {
    public static function init() {
        add_action('init', array(__CLASS__, 'register'));
    }

    public static function register() {
        register_post_type('algq_offer', array(
            'labels' => array(
                'name' => __('Offers', 'algq-offer-generator'),
                'singular_name' => __('Offer', 'algq-offer-generator'),
                'add_new_item' => __('Add New Offer', 'algq-offer-generator'),
                'edit_item' => __('Edit Offer', 'algq-offer-generator'),
            ),
            'public' => false,
            'show_ui' => true,
            'show_in_menu' => false,
            'supports' => array('title', 'editor', 'author'),
            'capability_type' => 'post',
        ));

        register_post_type('algq_offer_template', array(
            'labels' => array(
                'name' => __('Offer Templates', 'algq-offer-generator'),
                'singular_name' => __('Offer Template', 'algq-offer-generator'),
            ),
            'public' => false,
            'show_ui' => true,
            'show_in_menu' => false,
            'supports' => array('title', 'editor'),
            'capability_type' => 'post',
        ));
    }
}
