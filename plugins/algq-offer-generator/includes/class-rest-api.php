<?php
if (!defined('ABSPATH')) { exit; }

class ALGQ_Offer_REST_API {
    public static function init() {
        add_action('rest_api_init', array(__CLASS__, 'routes'));
    }

    public static function routes() {
        register_rest_route('algq-offers/v1', '/offers', array(
            'methods' => 'GET',
            'callback' => array(__CLASS__, 'offers'),
            'permission_callback' => function () { return current_user_can('edit_posts'); },
        ));
    }

    public static function offers() {
        $posts = get_posts(array('post_type' => 'algq_offer', 'posts_per_page' => 50, 'post_status' => array('publish', 'draft', 'pending')));
        return rest_ensure_response(array_map(function ($post) {
            return array(
                'id' => $post->ID,
                'title' => get_the_title($post),
                'status' => get_post_status($post),
                'strategy' => get_post_meta($post->ID, '_algq_offer_strategy', true),
                'price' => get_post_meta($post->ID, '_algq_offer_price', true),
            );
        }, $posts));
    }
}
