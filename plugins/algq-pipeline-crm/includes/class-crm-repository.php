<?php

defined( 'ABSPATH' ) || exit;

/**
 * Persistence for the shared ARE relationship CRM layer.
 *
 * Specialized plugin data remains authoritative in the owning plugin. This
 * repository stores shared identity, relationship, activity, task and link
 * records only.
 */
final class ALGQ_Pipeline_CRM_Repository {
    private array $tables;

    public function __construct() {
        $this->tables = ALGQ_Pipeline_Database::tables();
    }

    public function find_contact( $identifier ): ?array {
        global $wpdb;

        if ( is_numeric( $identifier ) ) {
            $sql = $wpdb->prepare( "SELECT * FROM {$this->tables['crm_contacts']} WHERE id = %d LIMIT 1", absint( $identifier ) );
        } else {
            $identifier = sanitize_text_field( (string) $identifier );
            $sql = $wpdb->prepare( "SELECT * FROM {$this->tables['crm_contacts']} WHERE uuid = %s LIMIT 1", $identifier );
        }

        $row = $wpdb->get_row( $sql, ARRAY_A );
        return $row ?: null;
    }

    public function find_contact_by_source( string $system, string $record_id ): ?array {
        global $wpdb;
        if ( '' === $system || '' === $record_id ) {
            return null;
        }

        $row = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$this->tables['crm_contacts']} WHERE source_system = %s AND source_record_id = %s LIMIT 1",
                $system,
                $record_id
            ),
            ARRAY_A
        );

        return $row ?: null;
    }

    public function insert_contact( array $data ) {
        global $wpdb;
        $ok = $wpdb->insert( $this->tables['crm_contacts'], $data, $this->formats( $data ) );
        return $ok ? (int) $wpdb->insert_id : false;
    }

    public function update_contact( int $id, array $data ): bool {
        global $wpdb;
        return false !== $wpdb->update( $this->tables['crm_contacts'], $data, array( 'id' => $id ), $this->formats( $data ), array( '%d' ) );
    }

    public function list_contacts( array $args = array() ): array {
        global $wpdb;

        $args = wp_parse_args(
            $args,
            array(
                'relationship_type' => '',
                'status'            => '',
                'priority'          => '',
                'owner_user_id'     => 0,
                'search'            => '',
                'next_action_due'   => false,
                'page'              => 1,
                'per_page'          => 25,
            )
        );

        $joins  = array();
        $where  = array( '1=1' );
        $values = array();

        if ( $args['relationship_type'] ) {
            $joins[]  = "INNER JOIN {$this->tables['crm_relationships']} rel ON rel.contact_id = c.id";
            $where[]  = 'rel.relationship_type = %s';
            $values[] = sanitize_key( $args['relationship_type'] );
        }
        if ( $args['status'] ) {
            $where[]  = 'c.status = %s';
            $values[] = sanitize_key( $args['status'] );
        }
        if ( $args['priority'] ) {
            $where[]  = 'c.priority = %s';
            $values[] = sanitize_key( $args['priority'] );
        }
        if ( $args['owner_user_id'] ) {
            $where[]  = 'c.owner_user_id = %d';
            $values[] = absint( $args['owner_user_id'] );
        }
        if ( $args['search'] ) {
            $like     = '%' . $wpdb->esc_like( sanitize_text_field( $args['search'] ) ) . '%';
            $where[]  = '(c.display_name LIKE %s OR c.email LIKE %s OR c.phone LIKE %s)';
            array_push( $values, $like, $like, $like );
        }
        if ( $args['next_action_due'] ) {
            $where[]  = 'c.next_action_at IS NOT NULL AND c.next_action_at <= %s';
            $values[] = current_time( 'mysql', true );
        }

        $page     = max( 1, absint( $args['page'] ) );
        $per_page = min( 100, max( 1, absint( $args['per_page'] ) ) );
        $offset   = ( $page - 1 ) * $per_page;

        $sql = 'SELECT DISTINCT c.* FROM ' . $this->tables['crm_contacts'] . ' c ' . implode( ' ', $joins ) .
            ' WHERE ' . implode( ' AND ', $where ) . ' ORDER BY c.next_action_at IS NULL, c.next_action_at ASC, c.updated_at DESC LIMIT %d OFFSET %d';
        array_push( $values, $per_page, $offset );

        return $wpdb->get_results( $wpdb->prepare( $sql, $values ), ARRAY_A ) ?: array();
    }

    public function insert_organization( array $data ) {
        global $wpdb;
        $ok = $wpdb->insert( $this->tables['crm_organizations'], $data, $this->formats( $data ) );
        return $ok ? (int) $wpdb->insert_id : false;
    }

    public function find_organization( $identifier ): ?array {
        global $wpdb;
        if ( is_numeric( $identifier ) ) {
            $sql = $wpdb->prepare( "SELECT * FROM {$this->tables['crm_organizations']} WHERE id = %d LIMIT 1", absint( $identifier ) );
        } else {
            $identifier = sanitize_text_field( (string) $identifier );
            $sql = $wpdb->prepare( "SELECT * FROM {$this->tables['crm_organizations']} WHERE uuid = %s LIMIT 1", $identifier );
        }
        $row = $wpdb->get_row( $sql, ARRAY_A );
        return $row ?: null;
    }

    public function insert_relationship( array $data ) {
        global $wpdb;
        $ok = $wpdb->insert( $this->tables['crm_relationships'], $data, $this->formats( $data ) );
        return $ok ? (int) $wpdb->insert_id : false;
    }

    public function find_relationship_by_source( string $system, string $record_id ): ?array {
        global $wpdb;
        if ( '' === $system || '' === $record_id ) {
            return null;
        }
        $row = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$this->tables['crm_relationships']} WHERE source_system = %s AND source_record_id = %s LIMIT 1",
                $system,
                $record_id
            ),
            ARRAY_A
        );
        return $row ?: null;
    }

    public function relationships_for_contact( int $contact_id ): array {
        global $wpdb;
        return $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$this->tables['crm_relationships']} WHERE contact_id = %d ORDER BY updated_at DESC, id DESC",
                $contact_id
            ),
            ARRAY_A
        ) ?: array();
    }

    public function add_activity( array $data ) {
        global $wpdb;
        $ok = $wpdb->insert( $this->tables['crm_activity'], $data, $this->formats( $data ) );
        return $ok ? (int) $wpdb->insert_id : false;
    }

    public function activity_for_contact( int $contact_id, int $limit = 50 ): array {
        global $wpdb;
        $limit = min( 200, max( 1, $limit ) );
        return $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$this->tables['crm_activity']} WHERE contact_id = %d ORDER BY created_at DESC, id DESC LIMIT %d",
                $contact_id,
                $limit
            ),
            ARRAY_A
        ) ?: array();
    }

    public function insert_task( array $data ) {
        global $wpdb;
        $ok = $wpdb->insert( $this->tables['crm_tasks'], $data, $this->formats( $data ) );
        return $ok ? (int) $wpdb->insert_id : false;
    }

    public function tasks( array $args = array() ): array {
        global $wpdb;
        $args = wp_parse_args( $args, array( 'contact_id' => 0, 'assigned_user_id' => 0, 'status' => 'open', 'due_before' => '', 'limit' => 50 ) );
        $where = array( '1=1' );
        $values = array();
        if ( $args['contact_id'] ) {
            $where[] = 'contact_id = %d';
            $values[] = absint( $args['contact_id'] );
        }
        if ( $args['assigned_user_id'] ) {
            $where[] = 'assigned_user_id = %d';
            $values[] = absint( $args['assigned_user_id'] );
        }
        if ( $args['status'] ) {
            $where[] = 'status = %s';
            $values[] = sanitize_key( $args['status'] );
        }
        if ( $args['due_before'] ) {
            $where[] = 'due_at IS NOT NULL AND due_at <= %s';
            $values[] = sanitize_text_field( $args['due_before'] );
        }
        $limit = min( 200, max( 1, absint( $args['limit'] ) ) );
        $sql = "SELECT * FROM {$this->tables['crm_tasks']} WHERE " . implode( ' AND ', $where ) . ' ORDER BY due_at IS NULL, due_at ASC, id DESC LIMIT %d';
        $values[] = $limit;
        return $wpdb->get_results( $wpdb->prepare( $sql, $values ), ARRAY_A ) ?: array();
    }

    private function formats( array $data ): array {
        $ints = array(
            'owner_user_id', 'contact_id', 'organization_id', 'deal_id', 'related_record_id',
            'actor_user_id', 'assigned_user_id', 'created_by', 'updated_by',
        );
        $formats = array();
        foreach ( array_keys( $data ) as $key ) {
            $formats[] = in_array( $key, $ints, true ) ? '%d' : '%s';
        }
        return $formats;
    }
}
