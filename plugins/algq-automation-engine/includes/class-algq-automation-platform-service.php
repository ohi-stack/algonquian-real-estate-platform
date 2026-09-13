<?php
/**
 * Platform Service Interface provider for Automation Engine 2.1.
 *
 * @package AlgonquianAutomationEngine
 */

defined( 'ABSPATH' ) || exit;

if ( ! interface_exists( 'ARE_Platform_Service_Interface' ) ) {
    return;
}

final class ALGQ_Automation_Platform_Service implements ARE_Platform_Service_Interface {
    public function id(): string {
        return 'automation.workflows';
    }

    public function version(): string {
        return ALGQ_AUTOMATION_VERSION;
    }

    public function operations(): array {
        return array(
            'dispatch_event',
            'event_catalog',
            'workflow_templates',
        );
    }

    public function call( string $operation, array $payload = array(), array $context = array() ) {
        return match ( $operation ) {
            'dispatch_event'     => $this->dispatch_event( $payload, $context ),
            'event_catalog'      => $this->event_catalog(),
            'workflow_templates' => $this->workflow_templates(),
            default              => new WP_Error(
                'algq_automation_operation_not_supported',
                __( 'The requested Automation Engine service operation is not supported.', 'algq-automation-engine' )
            ),
        };
    }

    public function health(): array {
        $health = ALGQ_Automation_Engine::health();
        $health['service_id'] = $this->id();
        $health['canonical_event_count'] = count( ALGQ_Automation_Event_Bridge::canonical_events() );
        return $health;
    }

    private function dispatch_event( array $payload, array $context ) {
        $event_key   = ALGQ_Automation_Event_Bridge::normalize_event_key( (string) ( $payload['event_key'] ?? '' ) );
        $object_type = sanitize_key( (string) ( $payload['object_type'] ?? '' ) );
        $object_id   = absint( $payload['object_id'] ?? 0 );
        $event_data  = $payload['payload'] ?? array();
        $event_data  = is_array( $event_data ) ? $event_data : array();

        if ( ! ALGQ_Automation_Event_Bridge::is_supported( $event_key ) ) {
            return new WP_Error(
                'algq_automation_event_not_supported',
                __( 'The requested canonical automation event is not registered.', 'algq-automation-engine' ),
                array( 'event_key' => $event_key )
            );
        }

        $event_data['service_context'] = array_filter(
            array(
                'request_id'    => sanitize_text_field( (string) ( $context['request_id'] ?? '' ) ),
                'caller_plugin' => sanitize_key( (string) ( $context['caller_plugin'] ?? '' ) ),
            )
        );

        $jobs = ALGQ_Automation_Event_Bridge::dispatch(
            $event_key,
            $object_type,
            $object_id,
            $event_data
        );

        return array(
            'event_key'   => $event_key,
            'storage_key' => ALGQ_Automation_Event_Bridge::storage_key( $event_key ),
            'queued_jobs' => array_values( array_map( 'absint', $jobs ) ),
        );
    }

    private function event_catalog(): array {
        $events = array();
        foreach ( ALGQ_Automation_Event_Bridge::canonical_events() as $event_key => $label ) {
            $events[ $event_key ] = array(
                'event_key'   => $event_key,
                'storage_key' => ALGQ_Automation_Event_Bridge::storage_key( $event_key ),
                'label'       => wp_strip_all_tags( $label ),
            );
        }
        return $events;
    }

    private function workflow_templates(): array {
        $templates = array();
        foreach ( ALGQ_Automation_Event_Bridge::workflow_templates() as $event_key => $template ) {
            $templates[ $event_key ] = array(
                'event_key'   => $event_key,
                'storage_key' => ALGQ_Automation_Event_Bridge::storage_key( $event_key ),
                'name'        => sanitize_text_field( (string) $template['name'] ),
                'description' => sanitize_textarea_field( (string) $template['description'] ),
                'action_key'  => sanitize_key( (string) $template['action_key'] ),
                'status'      => 'draft',
            );
        }
        return $templates;
    }
}