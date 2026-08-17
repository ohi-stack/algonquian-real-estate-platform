<?php
defined( 'ABSPATH' ) || exit;

final class ALGQ_WCB_Entitlements {
    public static function table(): string {
        global $wpdb;
        return $wpdb->prefix . 'algq_wcb_entitlements';
    }

    public static function grant_from_order( WC_Order $order, string $source = 'order' ): int {
        $user_id = absint( $order->get_user_id() );
        if ( ! $user_id ) {
            return 0;
        }
        $count = 0;
        foreach ( $order->get_items( 'line_item' ) as $item_id => $item ) {
            if ( ! $item instanceof WC_Order_Item_Product ) {
                continue;
            }
            $product_id   = absint( $item->get_product_id() );
            $variation_id = absint( $item->get_variation_id() );
            $access_key   = self::product_access_key( $variation_id ?: $product_id );
            if ( '' === $access_key ) {
                continue;
            }
            $duration_days = self::product_duration_days( $variation_id ?: $product_id );
            $expires_at    = $duration_days > 0 ? gmdate( 'Y-m-d H:i:s', time() + ( DAY_IN_SECONDS * $duration_days ) ) : null;
            if ( self::grant( array(
                'user_id' => $user_id,
                'order_id' => $order->get_id(),
                'order_item_id' => $item_id,
                'product_id' => $product_id,
                'variation_id' => $variation_id,
                'access_key' => $access_key,
                'quantity' => max( 1, absint( $item->get_quantity() ) ),
                'expires_at' => $expires_at,
                'source' => $source,
            ) ) ) {
                ++$count;
            }
        }
        return $count;
    }

    public static function grant( array $data ): bool {
        global $wpdb;
        $table         = self::table();
        $user_id       = absint( $data['user_id'] ?? 0 );
        $order_id      = absint( $data['order_id'] ?? 0 );
        $order_item_id = absint( $data['order_item_id'] ?? 0 );
        $product_id    = absint( $data['product_id'] ?? 0 );
        $variation_id  = absint( $data['variation_id'] ?? 0 );
        $access_key    = sanitize_key( (string) ( $data['access_key'] ?? '' ) );
        if ( ! $user_id || ! $order_id || ! $order_item_id || ! $product_id || '' === $access_key ) {
            return false;
        }
        $existing_id = $wpdb->get_var( $wpdb->prepare(
            "SELECT id FROM {$table} WHERE user_id=%d AND order_id=%d AND order_item_id=%d AND access_key=%s LIMIT 1",
            $user_id, $order_id, $order_item_id, $access_key
        ) );
        $row = array(
            'uuid' => wp_generate_uuid4(),
            'user_id' => $user_id,
            'order_id' => $order_id,
            'order_item_id' => $order_item_id,
            'product_id' => $product_id,
            'variation_id' => $variation_id,
            'access_key' => $access_key,
            'status' => 'active',
            'quantity' => max( 1, absint( $data['quantity'] ?? 1 ) ),
            'granted_at' => current_time( 'mysql', true ),
            'expires_at' => ! empty( $data['expires_at'] ) ? sanitize_text_field( (string) $data['expires_at'] ) : null,
            'revoked_at' => null,
            'source' => sanitize_key( (string) ( $data['source'] ?? 'order' ) ),
            'updated_at' => current_time( 'mysql', true ),
        );
        if ( $existing_id ) {
            unset( $row['uuid'] );
            $result = $wpdb->update( $table, $row, array( 'id' => absint( $existing_id ) ) );
        } else {
            $result = $wpdb->insert( $table, $row );
        }
        if ( false === $result ) {
            return false;
        }
        do_action( 'algq_wcb_access_granted', $user_id, $product_id, $access_key, $order_id );
        do_action( 'algq_entitlement_changed', $user_id, $access_key, 'active', $order_id );
        return true;
    }

    public static function revoke_order( int $order_id, string $reason = 'order_revoked' ): int {
        global $wpdb;
        $table = self::table();
        $rows = $wpdb->get_results( $wpdb->prepare( "SELECT id,user_id,access_key FROM {$table} WHERE order_id=%d AND status='active'", $order_id ) );
        $count = 0;
        foreach ( (array) $rows as $row ) {
            $updated = $wpdb->update( $table,
                array( 'status' => 'revoked', 'revoked_at' => current_time( 'mysql', true ), 'source' => sanitize_key( $reason ), 'updated_at' => current_time( 'mysql', true ) ),
                array( 'id' => absint( $row->id ) )
            );
            if ( false !== $updated ) {
                ++$count;
                do_action( 'algq_entitlement_changed', absint( $row->user_id ), sanitize_key( $row->access_key ), 'revoked', $order_id );
            }
        }
        return $count;
    }

    public static function revoke_order_item( int $order_id, int $order_item_id, string $reason = 'refund' ): int {
        global $wpdb;
        return (int) $wpdb->update(
            self::table(),
            array( 'status' => 'revoked', 'revoked_at' => current_time( 'mysql', true ), 'source' => sanitize_key( $reason ), 'updated_at' => current_time( 'mysql', true ) ),
            array( 'order_id' => $order_id, 'order_item_id' => $order_item_id, 'status' => 'active' )
        );
    }

    public static function user_has_access( int $user_id, string $access_key ): bool {
        global $wpdb;
        $now = current_time( 'mysql', true );
        $id = $wpdb->get_var( $wpdb->prepare(
            "SELECT id FROM " . self::table() . " WHERE user_id=%d AND access_key=%s AND status='active' AND (expires_at IS NULL OR expires_at='' OR expires_at>%s) LIMIT 1",
            $user_id, sanitize_key( $access_key ), $now
        ) );
        return (bool) $id;
    }

    public static function for_user( int $user_id, int $limit = 100 ): array {
        global $wpdb;
        $limit = max( 1, min( 250, $limit ) );
        $rows = (array) $wpdb->get_results( $wpdb->prepare(
            "SELECT * FROM " . self::table() . " WHERE user_id=%d ORDER BY granted_at DESC LIMIT %d",
            $user_id, $limit
        ), ARRAY_A );
        $now = current_time( 'mysql', true );
        foreach ( $rows as &$row ) {
            if ( 'active' === ( $row['status'] ?? '' ) && ! empty( $row['expires_at'] ) && $row['expires_at'] <= $now ) {
                $row['status'] = 'expired';
            }
        }
        unset( $row );
        return $rows;
    }

    public static function product_access_key( int $product_id ): string {
        if ( 'yes' !== get_post_meta( $product_id, '_algq_entitlement_enabled', true ) ) {
            return '';
        }
        $key = sanitize_key( (string) get_post_meta( $product_id, '_algq_access_key', true ) );
        return '' !== $key ? $key : 'product_' . $product_id;
    }

    public static function product_duration_days( int $product_id ): int {
        return max( 0, absint( get_post_meta( $product_id, '_algq_access_duration_days', true ) ) );
    }
}
