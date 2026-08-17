<?php

defined( 'ABSPATH' ) || exit;

final class ALGQ_Buyer_Portal_Activator {
    public static function activate(): void {
        self::create_roles();
        self::create_tables();
        self::create_pages();
        update_option( 'algq_buyer_portal_version', ALGQ_BUYER_PORTAL_VERSION, false );
        flush_rewrite_rules();
    }

    public static function deactivate(): void {
        flush_rewrite_rules();
    }

    private static function create_roles(): void {
        $buyer = get_role( 'algq_buyer' );
        if ( ! $buyer ) {
            $buyer = add_role( 'algq_buyer', 'Algonquian Buyer', array( 'read' => true ) );
        }

        $buyer_caps = array(
            'read',
            'algq_view_buyer_portal',
            'view_algq_buyer_dashboard',
            'view_algq_deals',
            'accept_algq_nda',
            'submit_algq_buyer_interest',
            'download_algq_deal_documents',
        );
        if ( $buyer ) {
            foreach ( $buyer_caps as $cap ) {
                $buyer->add_cap( $cap );
            }
        }

        $admin = get_role( 'administrator' );
        if ( $admin ) {
            foreach ( array_merge( $buyer_caps, array( 'algq_manage_buyer_portal', 'algq_manage_buyer_deals', 'algq_export_buyer_activity' ) ) as $cap ) {
                $admin->add_cap( $cap );
            }
        }
    }

    private static function create_tables(): void {
        global $wpdb;
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        $charset = $wpdb->get_charset_collate();

        dbDelta( "CREATE TABLE {$wpdb->prefix}algq_buyer_nda (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id BIGINT UNSIGNED NOT NULL,
            deal_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
            nda_version VARCHAR(64) NOT NULL,
            accepted_at DATETIME NOT NULL,
            ip_hash CHAR(64) NOT NULL DEFAULT '',
            user_agent_hash CHAR(64) NOT NULL DEFAULT '',
            acceptance_uuid CHAR(36) NOT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY acceptance_uuid (acceptance_uuid),
            KEY user_deal (user_id, deal_id)
        ) $charset;" );

        dbDelta( "CREATE TABLE {$wpdb->prefix}algq_buyer_interest (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id BIGINT UNSIGNED NOT NULL,
            deal_id BIGINT UNSIGNED NOT NULL,
            message TEXT NULL,
            status VARCHAR(40) NOT NULL DEFAULT 'new',
            created_at DATETIME NOT NULL,
            PRIMARY KEY (id),
            KEY user_id (user_id),
            KEY deal_id (deal_id)
        ) $charset;" );

        dbDelta( "CREATE TABLE {$wpdb->prefix}algq_buyer_downloads (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id BIGINT UNSIGNED NOT NULL,
            deal_id BIGINT UNSIGNED NOT NULL,
            attachment_id BIGINT UNSIGNED NOT NULL,
            file_hash CHAR(64) NOT NULL DEFAULT '',
            created_at DATETIME NOT NULL,
            PRIMARY KEY (id),
            KEY user_id (user_id),
            KEY deal_id (deal_id)
        ) $charset;" );
    }

    private static function create_pages(): void {
        $pages = array(
            'buyers-register' => array( 'Buyer Registration', '[algq_buyer_registration]' ),
            'buyers-login'    => array( 'Buyer Login', '[algq_buyer_login]' ),
            'buyer-dashboard' => array( 'Buyer Dashboard', '[algq_buyer_dashboard]' ),
            'buyer-deals'     => array( 'Buyer Deals', '[algq_buyer_deals]' ),
        );

        foreach ( $pages as $slug => $page ) {
            if ( get_page_by_path( $slug ) ) {
                continue;
            }
            wp_insert_post(
                array(
                    'post_title'   => $page[0],
                    'post_name'    => $slug,
                    'post_status'  => 'publish',
                    'post_type'    => 'page',
                    'post_content' => "[vc_column_text]\n{$page[1]}\n[/vc_column_text]",
                )
            );
        }
    }
}
