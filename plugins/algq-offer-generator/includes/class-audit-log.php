<?php

defined( 'ABSPATH' ) || exit;

final class ALGQ_Offer_Audit_Log {
    public static function init(): void {
        add_action( 'algq_offer_created', array( __CLASS__, 'log_offer_created' ), 10, 3 );
        add_action( 'algq_offer_saved', array( __CLASS__, 'log_offer_saved' ), 10, 2 );
        add_action( 'algq_offer_status_changed', array( __CLASS__, 'log_status_changed' ), 10, 3 );
        add_action( 'algq_offer_document_generated', array( __CLASS__, 'log_document_generated' ), 10, 3 );
        add_action( 'algq_offer_pdf_requested', array( __CLASS__, 'log_pdf_requested' ), 10, 2 );
    }

    public static function create_table(): void {
        global $wpdb;
        $table   = $wpdb->prefix . 'algq_offer_audit_log';
        $charset = $wpdb->get_charset_collate();
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta( "CREATE TABLE $table (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            event varchar(120) NOT NULL,
            object_id bigint(20) unsigned NOT NULL DEFAULT 0,
            user_id bigint(20) unsigned NOT NULL DEFAULT 0,
            details longtext NULL,
            created_at datetime NOT NULL,
            PRIMARY KEY  (id),
            KEY event (event),
            KEY object_id (object_id),
            KEY created_at (created_at)
        ) $charset;" );
    }

    public static function record( string $event, int $object_id = 0, array $details = array() ): void {
        global $wpdb;
        $safe_details = array_intersect_key(
            $details,
            array_flip( array( 'user_id', 'version', 'status', 'deal_id', 'document_id', 'hash', 'offer_number' ) )
        );

        $wpdb->insert(
            $wpdb->prefix . 'algq_offer_audit_log',
            array(
                'event'      => sanitize_key( $event ),
                'object_id'  => absint( $object_id ),
                'user_id'    => get_current_user_id(),
                'details'    => wp_json_encode( $safe_details ),
                'created_at' => current_time( 'mysql' ),
            ),
            array( '%s', '%d', '%d', '%s', '%s' )
        );

        if ( function_exists( 'algq_log_event' ) ) {
            algq_log_event(
                'offer.' . sanitize_key( $event ),
                array(
                    'plugin'     => 'algq-offer-generator',
                    'related_id' => absint( $object_id ),
                    'details'    => $safe_details,
                )
            );
        }
    }

    public static function log_offer_created( int $offer_id, array $data, int $user_id ): void {
        self::record( 'created', $offer_id, array( 'user_id' => $user_id, 'deal_id' => absint( $data['deal_id'] ?? 0 ), 'offer_number' => $data['offer_number'] ?? '' ) );
    }

    public static function log_offer_saved( int $offer_id, int $user_id ): void {
        self::record( 'saved', $offer_id, array( 'user_id' => $user_id, 'version' => absint( get_post_meta( $offer_id, '_algq_offer_version_number', true ) ) ) );
    }

    public static function log_status_changed( int $offer_id, string $status, int $user_id ): void {
        self::record( 'status_changed', $offer_id, array( 'user_id' => $user_id, 'status' => sanitize_key( $status ) ) );
    }

    public static function log_document_generated( int $offer_id, int $user_id, array $metadata = array() ): void {
        self::record( 'document_generated', $offer_id, array( 'user_id' => $user_id, 'document_id' => absint( $metadata['document_id'] ?? 0 ), 'hash' => $metadata['hash'] ?? '' ) );
    }

    public static function log_pdf_requested( int $offer_id, int $user_id ): void {
        self::record( 'pdf_requested', $offer_id, array( 'user_id' => $user_id ) );
    }
}
