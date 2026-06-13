<?php
if (!defined('ABSPATH')) { exit; }

class ALGQ_Tenant_Post_Types {
    public static function init() {
        add_action('init', array(__CLASS__, 'register'));
    }

    public static function register() {
        $types = array(
            'algq_property' => array('singular' => 'Property', 'plural' => 'Properties', 'icon' => 'dashicons-building'),
            'algq_unit' => array('singular' => 'Rental Unit', 'plural' => 'Rental Units', 'icon' => 'dashicons-admin-home'),
            'algq_lease' => array('singular' => 'Lease', 'plural' => 'Leases', 'icon' => 'dashicons-media-document'),
            'algq_maintenance' => array('singular' => 'Maintenance Request', 'plural' => 'Maintenance Requests', 'icon' => 'dashicons-hammer'),
            'algq_application' => array('singular' => 'Rental Application', 'plural' => 'Rental Applications', 'icon' => 'dashicons-clipboard'),
            'algq_tenant_notice' => array('singular' => 'Tenant Notice', 'plural' => 'Tenant Notices', 'icon' => 'dashicons-megaphone'),
        );

        foreach ($types as $post_type => $data) {
            register_post_type($post_type, array(
                'labels' => array(
                    'name' => $data['plural'],
                    'singular_name' => $data['singular'],
                    'add_new_item' => 'Add New ' . $data['singular'],
                    'edit_item' => 'Edit ' . $data['singular'],
                    'view_item' => 'View ' . $data['singular'],
                    'search_items' => 'Search ' . $data['plural'],
                ),
                'public' => false,
                'show_ui' => true,
                'show_in_menu' => 'algq-tenant-portal',
                'show_in_rest' => true,
                'supports' => array('title', 'editor', 'author', 'custom-fields'),
                'menu_icon' => $data['icon'],
                'capability_type' => 'post',
            ));
        }
    }
}
