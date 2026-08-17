<?php
/**
 * Activation and page reconciliation for Algonquian Digital Store.
 */

defined( 'ABSPATH' ) || exit;

final class ALGQ_Digital_Store_Activator {
    private const PAGE_OPTION = 'algq_digital_store_pages';

    public static function activate(): void {
        self::add_capabilities();
        self::create_pages();
        update_option( 'algq_digital_store_version', ALGQ_DIGITAL_STORE_VERSION, false );
        flush_rewrite_rules();
    }

    public static function deactivate(): void {
        flush_rewrite_rules();
    }

    public static function add_capabilities(): void {
        $administrator = get_role( 'administrator' );
        if ( $administrator ) {
            $administrator->add_cap( 'manage_algq_digital_store' );
            $administrator->add_cap( 'view_algq_product_vault' );
        }

        $customer = get_role( 'customer' );
        if ( $customer ) {
            $customer->add_cap( 'view_algq_product_vault' );
        }
    }

    public static function create_pages(): void {
        $page_ids = get_option( self::PAGE_OPTION, array() );
        $page_ids = is_array( $page_ids ) ? $page_ids : array();

        $store_id = self::ensure_page(
            'store',
            'Algonquian Digital Store',
            'store',
            '[algq_digital_store]'
        );

        $page_ids['store'] = $store_id;
        $page_ids['product_vault'] = self::ensure_page(
            'product_vault',
            'Product Vault',
            'product-vault',
            '[algq_product_vault]'
        );
        $page_ids['checkout'] = self::ensure_page(
            'checkout',
            'Store Checkout',
            'checkout',
            '[algq_store_checkout]',
            $store_id
        );

        update_option( self::PAGE_OPTION, array_map( 'absint', $page_ids ), false );
    }

    private static function ensure_page(
        string $key,
        string $title,
        string $slug,
        string $shortcode,
        int $parent_id = 0
    ): int {
        $saved = get_option( self::PAGE_OPTION, array() );
        $saved = is_array( $saved ) ? $saved : array();
        $saved_id = isset( $saved[ $key ] ) ? absint( $saved[ $key ] ) : 0;

        if ( $saved_id && 'page' === get_post_type( $saved_id ) && 'trash' !== get_post_status( $saved_id ) ) {
            return $saved_id;
        }

        $path = $parent_id ? trim( get_page_uri( $parent_id ) . '/' . $slug, '/' ) : $slug;
        $existing = get_page_by_path( $path, OBJECT, 'page' );
        if ( $existing instanceof WP_Post ) {
            return (int) $existing->ID;
        }

        $page_id = wp_insert_post(
            array(
                'post_title'   => sanitize_text_field( $title ),
                'post_name'    => sanitize_title( $slug ),
                'post_parent'  => absint( $parent_id ),
                'post_status'  => 'publish',
                'post_type'    => 'page',
                'post_content' => '[vc_column_text]' . $shortcode . '[/vc_column_text]',
            ),
            true
        );

        return is_wp_error( $page_id ) ? 0 : (int) $page_id;
    }
}
