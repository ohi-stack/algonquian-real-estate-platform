<?php

defined( 'ABSPATH' ) || exit;

final class ALGQ_Offer_Post_Types {
    public static function init(): void {
        add_action( 'init', array( __CLASS__, 'register' ) );
    }

    public static function register(): void {
        register_post_type(
            'algq_offer',
            array(
                'labels' => array(
                    'name'          => __( 'Offers', 'algq-offer-generator' ),
                    'singular_name' => __( 'Offer', 'algq-offer-generator' ),
                    'add_new_item'  => __( 'Add New Offer', 'algq-offer-generator' ),
                    'edit_item'     => __( 'Edit Offer', 'algq-offer-generator' ),
                ),
                'public'              => false,
                'show_ui'             => true,
                'show_in_menu'        => false,
                'supports'            => array( 'title', 'editor', 'author', 'revisions' ),
                'capability_type'     => array( 'algq_offer', 'algq_offers' ),
                'map_meta_cap'        => true,
                'show_in_rest'        => false,
                'exclude_from_search' => true,
            )
        );

        register_post_type(
            'algq_offer_template',
            array(
                'labels' => array(
                    'name'          => __( 'Offer Templates', 'algq-offer-generator' ),
                    'singular_name' => __( 'Offer Template', 'algq-offer-generator' ),
                ),
                'public'              => false,
                'show_ui'             => true,
                'show_in_menu'        => false,
                'supports'            => array( 'title', 'editor', 'revisions' ),
                'capability_type'     => 'post',
                'capabilities'        => array(
                    'edit_posts'          => 'manage_algq_offer_templates',
                    'edit_others_posts'   => 'manage_algq_offer_templates',
                    'publish_posts'       => 'manage_algq_offer_templates',
                    'read_private_posts'  => 'manage_algq_offer_templates',
                    'delete_posts'        => 'manage_algq_offer_templates',
                    'delete_others_posts' => 'manage_algq_offer_templates',
                    'edit_post'           => 'manage_algq_offer_templates',
                    'read_post'           => 'manage_algq_offer_templates',
                    'delete_post'         => 'manage_algq_offer_templates',
                ),
                'map_meta_cap'        => false,
                'show_in_rest'        => false,
                'exclude_from_search' => true,
            )
        );
    }
}
