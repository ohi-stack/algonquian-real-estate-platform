<?php
if (!defined('ABSPATH')) { exit; }

class ALGQ_Tenant_Activator {
    public static function activate() {
        self::create_pages();
    }

    private static function create_pages() {
        $pages = array(
            'tenant-portal' => array('title' => 'Tenant Portal', 'shortcode' => '[algq_tenant_dashboard]'),
            'pay-rent' => array('title' => 'Pay Rent', 'shortcode' => '[algq_pay_rent]'),
            'maintenance' => array('title' => 'Maintenance Request', 'shortcode' => '[algq_maintenance_request]'),
            'rental-application' => array('title' => 'Rental Application', 'shortcode' => '[algq_rental_application]'),
            'document-vault' => array('title' => 'Tenant Document Vault', 'shortcode' => '[algq_tenant_document_vault]'),
            'my-lease' => array('title' => 'My Lease', 'shortcode' => '[algq_lease_details]'),
        );

        foreach ($pages as $slug => $data) {
            if (get_page_by_path($slug)) { continue; }
            wp_insert_post(array(
                'post_title' => $data['title'],
                'post_name' => $slug,
                'post_status' => 'publish',
                'post_type' => 'page',
                'post_content' => '[vc_row][vc_column][vc_column_text]' . $data['shortcode'] . '[/vc_column_text][/vc_column][/vc_row]',
            ));
        }
    }
}
