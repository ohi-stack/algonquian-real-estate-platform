<?php
/**
 * Audit visibility and event bridge.
 *
 * The Command Center does not own the platform audit log. It reads events through
 * extension filters and emits command events to the platform audit service.
 *
 * @package Algonquian_Command_Center
 */

defined( 'ABSPATH' ) || exit;

final class ALGQ_Command_Center_Audit_Provider {
    public static function recent_events( int $limit = 25 ): array {
        if ( ! ALGQ_Command_Center_Security::can_view_audit() ) {
            return array();
        }

        $events = apply_filters( 'algq_command_center_audit_events', array(), max( 1, min( 100, $limit ) ) );
        return is_array( $events ) ? array_slice( array_values( $events ), 0, $limit ) : array();
    }

    public static function record_command( string $command, array $context = array() ): void {
        $event = array(
            'event' => 'command_center.command_executed',
            'command' => sanitize_key( $command ),
            'user_id' => get_current_user_id(),
            'context' => $context,
            'occurred_at' => gmdate( 'c' ),
            'source_plugin' => 'algq-command-center',
        );

        do_action( 'algq_audit_event', $event );
        do_action( 'algq_command_center_audit_event', $event );
    }
}
