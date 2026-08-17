<?php
/**
 * Dashboard widgets.
 *
 * @package Algonquian_Command_Center
 */

defined( 'ABSPATH' ) || exit;

final class ALGQ_Command_Center_Widgets {
    public static function registry(): array {
        return apply_filters(
            'algq_command_center_widget_registry',
            array(
                'new_leads' => array( 'label' => __( 'New Leads', 'algq-command-center' ), 'group' => 'acquisition', 'format' => 'number' ),
                'active_deals' => array( 'label' => __( 'Active Deals', 'algq-command-center' ), 'group' => 'acquisition', 'format' => 'number' ),
                'underwriting_queue' => array( 'label' => __( 'Underwriting Queue', 'algq-command-center' ), 'group' => 'acquisition', 'format' => 'number' ),
                'offers_pending' => array( 'label' => __( 'Offers Pending', 'algq-command-center' ), 'group' => 'transactions', 'format' => 'number' ),
                'contracts_pending' => array( 'label' => __( 'Under Contract', 'algq-command-center' ), 'group' => 'transactions', 'format' => 'number' ),
                'closings' => array( 'label' => __( 'Closings', 'algq-command-center' ), 'group' => 'transactions', 'format' => 'number' ),
                'buyers_registered' => array( 'label' => __( 'Registered Buyers', 'algq-command-center' ), 'group' => 'buyers', 'format' => 'number' ),
                'buyer_interest' => array( 'label' => __( 'Buyer Interest', 'algq-command-center' ), 'group' => 'buyers', 'format' => 'number' ),
                'pipeline_value' => array( 'label' => __( 'Pipeline Value', 'algq-command-center' ), 'group' => 'executive', 'format' => 'currency' ),
                'funding_status' => array( 'label' => __( 'Funding Progress', 'algq-command-center' ), 'group' => 'capital', 'format' => 'percent' ),
                'documents_generated' => array( 'label' => __( 'Documents', 'algq-command-center' ), 'group' => 'documents', 'format' => 'number' ),
                'signatures_pending' => array( 'label' => __( 'Signatures Pending', 'algq-command-center' ), 'group' => 'documents', 'format' => 'number' ),
                'automation_failed' => array( 'label' => __( 'Automation Failures', 'algq-command-center' ), 'group' => 'automation', 'format' => 'number' ),
                'system_health_score' => array( 'label' => __( 'Platform Health', 'algq-command-center' ), 'group' => 'platform', 'format' => 'health' ),
            )
        );
    }

    public static function enabled_widgets(): array {
        $allowed = array_keys( self::registry() );
        $enabled = (array) get_option( 'algq_command_center_enabled_widgets', $allowed );
        return array_values( array_intersect( array_map( 'sanitize_key', $enabled ), $allowed ) );
    }

    public static function render_kpi_cards(): void {
        $metrics = ALGQ_Command_Center_Data_Provider::metrics();
        $registry = self::registry();
        echo '<div class="algq-kpi-grid" data-algq-sortable="kpis">';
        foreach ( self::enabled_widgets() as $key ) {
            if ( ! isset( $registry[ $key ] ) ) {
                continue;
            }
            echo '<section class="algq-kpi-card" draggable="true">';
            echo '<span class="algq-kpi-label">' . esc_html( $registry[ $key ]['label'] ) . '</span>';
            echo '<strong class="algq-kpi-value">' . esc_html( self::format_value( $key, $registry[ $key ], $metrics ) ) . '</strong>';
            echo '<small class="algq-kpi-group">' . esc_html( ucfirst( (string) $registry[ $key ]['group'] ) ) . '</small>';
            echo '</section>';
        }
        echo '</div>';
    }

    public static function render_activity_feed(): void {
        $items = ALGQ_Command_Center_Data_Provider::activity();
        echo '<section class="algq-panel"><div class="algq-panel-heading"><h3>' . esc_html__( 'Recent Operational Activity', 'algq-command-center' ) . '</h3></div><ul class="algq-feed">';
        foreach ( $items as $item ) {
            echo '<li><strong>' . esc_html( (string) ( $item['type'] ?? 'Activity' ) ) . '</strong><span>' . esc_html( (string) ( $item['message'] ?? '' ) ) . '</span><em>' . esc_html( (string) ( $item['time'] ?? '' ) ) . '</em></li>';
        }
        echo '</ul></section>';
    }

    public static function render_pipeline(): void {
        $stages = ALGQ_Command_Center_Data_Provider::pipeline_stages();
        echo '<section class="algq-panel"><div class="algq-panel-heading"><h3>' . esc_html__( 'Pipeline by Stage', 'algq-command-center' ) . '</h3></div><div class="algq-stage-list">';
        foreach ( $stages as $stage ) {
            echo '<div class="algq-stage-row"><span>' . esc_html( (string) $stage['label'] ) . '</span><strong>' . esc_html( (string) $stage['count'] ) . '</strong></div>';
        }
        echo '</div></section>';
    }

    public static function render_health(): void {
        $checks = ALGQ_Command_Center_Health_Monitor::checks();
        echo '<section class="algq-panel"><div class="algq-panel-heading"><h3>' . esc_html__( 'Platform Health', 'algq-command-center' ) . '</h3></div><div class="algq-health-list">';
        foreach ( $checks as $check ) {
            $status = sanitize_key( (string) ( $check['status'] ?? 'warning' ) );
            echo '<div class="algq-health-row"><span class="algq-status algq-status-' . esc_attr( $status ) . '">' . esc_html( ucfirst( $status ) ) . '</span><strong>' . esc_html( (string) ( $check['label'] ?? '' ) ) . '</strong><span>' . esc_html( (string) ( $check['message'] ?? '' ) ) . '</span></div>';
        }
        echo '</div></section>';
    }

    private static function format_value( string $key, array $config, array $metrics ): string {
        if ( 'system_health_score' === $key ) {
            $health = $metrics['system_health'] ?? array();
            return isset( $health['score'] ) ? (string) absint( $health['score'] ) . '%' : '0%';
        }
        $value = $metrics[ $key ] ?? 0;
        return match ( $config['format'] ?? 'number' ) {
            'currency' => '$' . number_format_i18n( (float) $value, 0 ),
            'percent' => is_array( $value ) ? (string) absint( $value['percent'] ?? 0 ) . '%' : (string) absint( $value ) . '%',
            default => number_format_i18n( (int) $value ),
        };
    }
}
