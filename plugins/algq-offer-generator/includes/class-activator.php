<?php

defined( 'ABSPATH' ) || exit;

final class ALGQ_Offer_Activator {
    public static function activate(): void {
        self::upgrade();
        self::create_pages();
        self::create_default_templates();
    }

    public static function upgrade(): void {
        if ( class_exists( 'ALGQ_Offer_Post_Types' ) ) {
            ALGQ_Offer_Post_Types::register();
        }
        if ( class_exists( 'ALGQ_Offer_Role_Capabilities' ) ) {
            ALGQ_Offer_Role_Capabilities::install_roles();
        }
        if ( class_exists( 'ALGQ_Offer_Audit_Log' ) ) {
            ALGQ_Offer_Audit_Log::create_table();
        }
        update_option( 'algq_offer_db_version', ALGQ_OFFER_DB_VERSION, false );
    }

    private static function create_pages(): void {
        $pages = array(
            'offer-generator' => array( 'title' => 'Offer Generator', 'shortcode' => '[algq_offer_generator]' ),
            'generate-offer'  => array( 'title' => 'Generate Offer', 'shortcode' => '[algq_offer_builder]' ),
            'offer-history'   => array( 'title' => 'Offer History', 'shortcode' => '[algq_offer_history]' ),
        );

        foreach ( $pages as $slug => $data ) {
            $existing = get_page_by_path( $slug, OBJECT, 'page' );
            if ( $existing instanceof WP_Post ) {
                continue;
            }

            $page_id = wp_insert_post(
                array(
                    'post_title'   => $data['title'],
                    'post_name'    => $slug,
                    'post_status'  => 'publish',
                    'post_type'    => 'page',
                    'post_content' => '[vc_row][vc_column][vc_column_text]' . $data['shortcode'] . '[/vc_column_text][/vc_column][/vc_row]',
                    'meta_input'   => array(
                        '_algq_generated_by'      => 'algq-offer-generator',
                        '_algq_generated_version' => ALGQ_OFFER_VERSION,
                    ),
                ),
                true
            );

            if ( ! is_wp_error( $page_id ) ) {
                update_option( 'algq_offer_page_' . str_replace( '-', '_', $slug ), absint( $page_id ), false );
            }
        }
    }

    private static function create_default_templates(): void {
        if ( ! post_type_exists( 'algq_offer_template' ) ) {
            return;
        }

        $templates = array(
            'cash'             => 'Cash Offer Summary',
            'seller_financing' => 'Seller Financing Proposal',
            'subject_to'       => 'Subject-To Proposal',
            'loi'              => 'Letter of Intent',
            'purchase'         => 'Purchase and Sale Proposal',
        );

        foreach ( $templates as $type => $title ) {
            $existing = get_posts(
                array(
                    'post_type'      => 'algq_offer_template',
                    'post_status'    => 'any',
                    'posts_per_page' => 1,
                    'meta_key'       => '_algq_template_type',
                    'meta_value'     => $type,
                    'fields'         => 'ids',
                )
            );
            if ( $existing ) {
                continue;
            }

            $template_id = wp_insert_post(
                array(
                    'post_type'    => 'algq_offer_template',
                    'post_status'  => 'publish',
                    'post_title'   => $title,
                    'post_content' => "{{company_name}}\n\n{{offer_type}}\nProperty: {{property_address}}\nPurchase Price: {{purchase_price}}\nClosing Date: {{closing_date}}\n\n{{terms}}",
                ),
                true
            );

            if ( ! is_wp_error( $template_id ) ) {
                update_post_meta( $template_id, '_algq_template_type', $type );
                update_post_meta( $template_id, '_algq_template_version', 1 );
            }
        }
    }
}
