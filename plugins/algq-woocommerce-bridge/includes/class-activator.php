<?php
defined( 'ABSPATH' ) || exit;

final class ALGQ_WCB_Activator {
    public static function activate(): void {
        self::create_tables();
        self::migrate_legacy();
        self::create_pages();
        ALGQ_WCB_Capabilities::register();
        update_option( 'algq_wcb_version', ALGQ_WCB_VERSION );
        update_option( 'algq_wcb_schema_version', ALGQ_WCB_SCHEMA_VERSION );
        add_option( 'algq_wcb_brand_name', 'Algonquian Real Estate' );
        do_action( 'algq_plugin_registered', 'algq-woocommerce-bridge', ALGQ_WCB_VERSION );
    }

    public static function maybe_upgrade(): void {
        if ( version_compare( (string) get_option( 'algq_wcb_schema_version', '0.0.0' ), ALGQ_WCB_SCHEMA_VERSION, '<' ) ) {
            self::activate();
        } else {
            ALGQ_WCB_Capabilities::register();
        }
    }

    public static function deactivate(): void {
        // Operational data and capabilities are preserved during ordinary deactivation.
    }

    private static function create_tables(): void {
        global $wpdb;
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        $charset = $wpdb->get_charset_collate();
        $table = ALGQ_WCB_Entitlements::table();
        dbDelta( "CREATE TABLE {$table} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            uuid char(36) NOT NULL,
            user_id bigint(20) unsigned NOT NULL,
            order_id bigint(20) unsigned NOT NULL,
            order_item_id bigint(20) unsigned NOT NULL,
            product_id bigint(20) unsigned NOT NULL,
            variation_id bigint(20) unsigned NOT NULL DEFAULT 0,
            access_key varchar(191) NOT NULL,
            status varchar(30) NOT NULL DEFAULT 'active',
            quantity int unsigned NOT NULL DEFAULT 1,
            granted_at datetime NOT NULL,
            expires_at datetime NULL,
            revoked_at datetime NULL,
            source varchar(50) NOT NULL DEFAULT 'order',
            updated_at datetime NOT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY entitlement_identity (user_id,order_id,order_item_id,access_key),
            UNIQUE KEY uuid (uuid),
            KEY user_access (user_id,access_key,status),
            KEY order_id (order_id),
            KEY product_id (product_id),
            KEY expires_at (expires_at)
        ) {$charset};" );
    }

    private static function migrate_legacy(): void {
        global $wpdb;
        $legacy = $wpdb->prefix . 'algq_wcb_access_log';
        if ( $legacy !== $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $legacy ) ) ) {
            return;
        }
        $rows = (array) $wpdb->get_results( "SELECT user_id,order_id,product_id,access_key,status,created_at FROM {$legacy} ORDER BY id ASC" );
        foreach ( $rows as $row ) {
            $order = function_exists( 'wc_get_order' ) ? wc_get_order( absint( $row->order_id ) ) : false;
            $item_id = 0;
            if ( $order instanceof WC_Order ) {
                foreach ( $order->get_items( 'line_item' ) as $candidate_id => $item ) {
                    if ( absint( $item->get_product_id() ) === absint( $row->product_id ) ) {
                        $item_id = absint( $candidate_id );
                        break;
                    }
                }
            }
            if ( ! $item_id ) {
                $item_id = absint( $row->product_id );
            }
            ALGQ_WCB_Entitlements::grant( array(
                'user_id' => absint( $row->user_id ),
                'order_id' => absint( $row->order_id ),
                'order_item_id' => $item_id,
                'product_id' => absint( $row->product_id ),
                'access_key' => sanitize_key( $row->access_key ),
                'source' => 'legacy_migration',
            ) );
            if ( 'active' !== $row->status ) {
                ALGQ_WCB_Entitlements::revoke_order_item( absint( $row->order_id ), $item_id, 'legacy_migration' );
            }
        }
        update_option( 'algq_wcb_legacy_migrated', current_time( 'mysql', true ) );
    }

    private static function create_pages(): void {
        $pages = array(
            'algq-commerce' => array( 'title' => 'ARE Commerce Access', 'shortcode' => '[algq_commerce_access]' ),
            'algq-purchased-products' => array( 'title' => 'ARE Purchased Products', 'shortcode' => '[algq_purchased_products]' ),
            'algq-buyer-entitlements' => array( 'title' => 'ARE Buyer Entitlements', 'shortcode' => '[algq_buyer_entitlements]' ),
        );
        foreach ( $pages as $slug => $page ) {
            if ( get_page_by_path( $slug ) ) {
                continue;
            }
            wp_insert_post( array(
                'post_title' => $page['title'],
                'post_name' => $slug,
                'post_content' => "[vc_row][vc_column][vc_column_text]\n{$page['shortcode']}\n[/vc_column_text][/vc_column][/vc_row]",
                'post_status' => 'publish',
                'post_type' => 'page',
            ) );
        }
    }
}
