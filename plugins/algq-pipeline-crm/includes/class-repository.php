<?php

defined( 'ABSPATH' ) || exit;

final class ALGQ_Pipeline_Repository {
    private array $tables;

    public function __construct() {
        $this->tables = ALGQ_Pipeline_Database::tables();
    }

    public function find( $identifier ): ?array {
        global $wpdb;
        if ( is_numeric( $identifier ) ) {
            $sql = $wpdb->prepare( "SELECT * FROM {$this->tables['deals']} WHERE id = %d", absint( $identifier ) );
        } else {
            $identifier = sanitize_text_field( (string) $identifier );
            $sql = $wpdb->prepare( "SELECT * FROM {$this->tables['deals']} WHERE uuid = %s OR deal_number = %s LIMIT 1", $identifier, $identifier );
        }
        $row = $wpdb->get_row( $sql, ARRAY_A );
        return $row ?: null;
    }

    public function find_by_source( string $system, string $record_id ): ?array {
        global $wpdb;
        if ( '' === $system || '' === $record_id ) {
            return null;
        }
        $row = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$this->tables['deals']} WHERE source_system = %s AND source_record_id = %s LIMIT 1",
                $system,
                $record_id
            ),
            ARRAY_A
        );
        return $row ?: null;
    }

    public function insert( array $data ) {
        global $wpdb;
        $ok = $wpdb->insert( $this->tables['deals'], $data, $this->formats( $data ) );
        return $ok ? (int) $wpdb->insert_id : false;
    }

    public function update( int $id, array $data, ?int $expected_version = null ): bool {
        global $wpdb;
        $where = array( 'id' => $id );
        $where_format = array( '%d' );
        if ( null !== $expected_version ) {
            $where['record_version'] = $expected_version;
            $where_format[] = '%d';
        }
        $result = $wpdb->update( $this->tables['deals'], $data, $where, $this->formats( $data ), $where_format );
        return false !== $result && $result > 0;
    }

    public function list( array $args = array() ): array {
        global $wpdb;
        $args = wp_parse_args(
            $args,
            array( 'stage' => '', 'search' => '', 'assigned_user_id' => 0, 'include_archived' => false, 'page' => 1, 'per_page' => 25 )
        );
        $where = array( '1=1' );
        $values = array();
        if ( ! $args['include_archived'] ) {
            $where[] = 'archived_at IS NULL';
        }
        if ( $args['stage'] && ALGQ_Pipeline_Stages::is_valid( $args['stage'] ) ) {
            $where[] = 'stage = %s';
            $values[] = ALGQ_Pipeline_Stages::normalize( $args['stage'] );
        }
        if ( $args['assigned_user_id'] ) {
            $where[] = 'assigned_user_id = %d';
            $values[] = absint( $args['assigned_user_id'] );
        }
        if ( $args['search'] ) {
            $like = '%' . $wpdb->esc_like( sanitize_text_field( $args['search'] ) ) . '%';
            $where[] = '(deal_number LIKE %s OR title LIKE %s OR property_address LIKE %s OR primary_contact LIKE %s)';
            array_push( $values, $like, $like, $like, $like );
        }
        $page = max( 1, absint( $args['page'] ) );
        $per_page = min( 100, max( 1, absint( $args['per_page'] ) ) );
        $offset = ( $page - 1 ) * $per_page;
        $sql = "SELECT * FROM {$this->tables['deals']} WHERE " . implode( ' AND ', $where ) . ' ORDER BY updated_at DESC, id DESC LIMIT %d OFFSET %d';
        array_push( $values, $per_page, $offset );
        return $wpdb->get_results( $wpdb->prepare( $sql, $values ), ARRAY_A ) ?: array();
    }

    public function count( array $args = array() ): int {
        global $wpdb;
        $args = wp_parse_args( $args, array( 'stage' => '', 'include_archived' => false ) );
        $where = array( '1=1' );
        $values = array();
        if ( ! $args['include_archived'] ) {
            $where[] = 'archived_at IS NULL';
        }
        if ( $args['stage'] && ALGQ_Pipeline_Stages::is_valid( $args['stage'] ) ) {
            $where[] = 'stage = %s';
            $values[] = ALGQ_Pipeline_Stages::normalize( $args['stage'] );
        }
        $sql = "SELECT COUNT(*) FROM {$this->tables['deals']} WHERE " . implode( ' AND ', $where );
        return (int) ( $values ? $wpdb->get_var( $wpdb->prepare( $sql, $values ) ) : $wpdb->get_var( $sql ) );
    }

    public function count_by_stage(): array {
        global $wpdb;
        $rows = $wpdb->get_results( "SELECT stage, COUNT(*) AS total FROM {$this->tables['deals']} WHERE archived_at IS NULL GROUP BY stage", ARRAY_A );
        $counts = array_fill_keys( array_keys( ALGQ_Pipeline_Stages::all() ), 0 );
        foreach ( $rows ?: array() as $row ) {
            $counts[ $row['stage'] ] = (int) $row['total'];
        }
        return $counts;
    }

    public function add_stage_history( int $deal_id, string $from, string $to, string $reason, array $context, int $user_id ): void {
        global $wpdb;
        $wpdb->insert(
            $this->tables['stage_history'],
            array(
                'deal_id' => $deal_id,
                'from_stage' => $from,
                'to_stage' => $to,
                'reason' => $reason,
                'context_json' => wp_json_encode( $context ),
                'changed_by' => $user_id,
                'changed_at' => current_time( 'mysql', true ),
            ),
            array( '%d', '%s', '%s', '%s', '%s', '%d', '%s' )
        );
    }

    public function add_activity( int $deal_id, string $event, string $message, array $metadata = array(), int $user_id = 0 ): void {
        global $wpdb;
        $wpdb->insert(
            $this->tables['activity'],
            array(
                'deal_id' => $deal_id,
                'event' => sanitize_key( $event ),
                'message' => sanitize_textarea_field( $message ),
                'metadata_json' => wp_json_encode( $metadata ),
                'actor_user_id' => $user_id,
                'created_at' => current_time( 'mysql', true ),
            ),
            array( '%d', '%s', '%s', '%s', '%d', '%s' )
        );
    }

    public function activity( int $deal_id = 0, int $limit = 50 ): array {
        global $wpdb;
        $limit = min( 200, max( 1, $limit ) );
        if ( $deal_id ) {
            return $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$this->tables['activity']} WHERE deal_id = %d ORDER BY created_at DESC, id DESC LIMIT %d", $deal_id, $limit ), ARRAY_A ) ?: array();
        }
        return $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$this->tables['activity']} ORDER BY created_at DESC, id DESC LIMIT %d", $limit ), ARRAY_A ) ?: array();
    }

    private function formats( array $data ): array {
        $ints = array( 'assigned_user_id', 'record_version', 'created_by', 'updated_by' );
        $floats = array( 'asking_price', 'offer_amount' );
        $formats = array();
        foreach ( array_keys( $data ) as $key ) {
            $formats[] = in_array( $key, $ints, true ) ? '%d' : ( in_array( $key, $floats, true ) ? '%f' : '%s' );
        }
        return $formats;
    }
}
