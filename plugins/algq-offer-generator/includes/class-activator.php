<?php
if (!defined('ABSPATH')) { exit; }

class ALGQ_Offer_Activator {
    public static function activate() {
        self::create_pages();
    }

    private static function create_pages() {
        $pages = array(
            'offer-generator' => array('title' => 'Offer Generator', 'shortcode' => '[algq_offer_generator]'),
            'generate-offer' => array('title' => 'Generate Offer', 'shortcode' => '[algq_offer_builder]'),
            'offer-history' => array('title' => 'Offer History', 'shortcode' => '[algq_offer_history]'),
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
