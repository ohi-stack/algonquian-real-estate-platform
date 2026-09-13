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

    public static function render_funding_track(): void {
        $summary = ALGQ_Command_Center_Data_Provider::funding_summary();
        $fallback = array(
            'connected'          => false,
            'requested'          => (float) ( $summary['needed'] ?? 0 ),
            'committed'          => (float) ( $summary['committed'] ?? 0 ),
            'funded'             => 0.0,
            'gap'                => (float) ( $summary['gap'] ?? 0 ),
            'commitment_percent' => absint( $summary['percent'] ?? 0 ),
            'funded_percent'     => 0,
            'source_count'       => 0,
            'record_count'       => 0,
            'recent_records'     => array(),
        );
        $track = apply_filters( 'algq_command_center_funding_track', $fallback );
        $track = is_array( $track ) ? wp_parse_args( $track, $fallback ) : $fallback;

        echo '<section class="algq-panel algq-funding-track">';
        echo '<div class="algq-panel-heading algq-funding-heading"><div><span class="algq-eyebrow">' . esc_html__( 'Capital', 'algq-command-center' ) . '</span><h3>' . esc_html__( 'Funding Track', 'algq-command-center' ) . '</h3><p>' . esc_html__( 'Executive view of requested, committed, and funded capital. Funding Tracker remains the authoritative record.', 'algq-command-center' ) . '</p></div>';
        echo '<a class="algq-button" href="' . esc_url( admin_url( 'admin.php?page=algq-funding-tracker' ) ) . '">' . esc_html__( 'Open Funding Tracker', 'algq-command-center' ) . '</a></div>';

        if ( empty( $track['connected'] ) ) {
            echo '<div class="algq-funding-empty"><strong>' . esc_html__( 'Live Funding Tracker data is not connected.', 'algq-command-center' ) . '</strong><span>' . esc_html__( 'Activate or integrate Algonquian Funding Tracker to populate authoritative capital totals here.', 'algq-command-center' ) . '</span></div></section>';
            return;
        }

        $requested = max( 0, (float) $track['requested'] );
        $committed = max( 0, (float) $track['committed'] );
        $funded = max( 0, (float) $track['funded'] );
        $gap = max( 0, (float) $track['gap'] );
        $commitment_percent = min( 100, max( 0, absint( $track['commitment_percent'] ) ) );
        $funded_percent = min( 100, max( 0, absint( $track['funded_percent'] ) ) );

        echo '<div class="algq-funding-metrics">';
        self::render_funding_metric( __( 'Requested', 'algq-command-center' ), $requested, 'requested' );
        self::render_funding_metric( __( 'Committed', 'algq-command-center' ), $committed, 'committed' );
        self::render_funding_metric( __( 'Funded', 'algq-command-center' ), $funded, 'funded' );
        self::render_funding_metric( __( 'Funding Gap', 'algq-command-center' ), $gap, 'gap' );
        echo '</div>';

        echo '<div class="algq-funding-progress-grid">';
        self::render_progress( __( 'Commitment Coverage', 'algq-command-center' ), $commitment_percent );
        self::render_progress( __( 'Funded Coverage', 'algq-command-center' ), $funded_percent );
        echo '</div>';

        echo '<div class="algq-funding-meta"><span><strong>' . esc_html( number_format_i18n( absint( $track['source_count'] ) ) ) . '</strong> ' . esc_html__( 'Capital Sources', 'algq-command-center' ) . '</span><span><strong>' . esc_html( number_format_i18n( absint( $track['record_count'] ) ) ) . '</strong> ' . esc_html__( 'Funding Records', 'algq-command-center' ) . '</span></div>';

        $recent = is_array( $track['recent_records'] ) ? array_slice( $track['recent_records'], 0, 5 ) : array();
        if ( ! empty( $recent ) ) {
            echo '<div class="algq-funding-recent"><h4>' . esc_html__( 'Recent Funding Activity', 'algq-command-center' ) . '</h4><div class="algq-funding-table-wrap"><table class="algq-funding-table"><thead><tr><th>' . esc_html__( 'Deal', 'algq-command-center' ) . '</th><th>' . esc_html__( 'Source', 'algq-command-center' ) . '</th><th>' . esc_html__( 'Status', 'algq-command-center' ) . '</th><th>' . esc_html__( 'Requested', 'algq-command-center' ) . '</th><th>' . esc_html__( 'Committed', 'algq-command-center' ) . '</th><th>' . esc_html__( 'Funded', 'algq-command-center' ) . '</th></tr></thead><tbody>';
            foreach ( $recent as $record ) {
                $status = sanitize_key( (string) ( $record['status'] ?? '' ) );
                echo '<tr><td>' . esc_html( absint( $record['deal_id'] ?? 0 ) ? '#' . absint( $record['deal_id'] ) : '—' ) . '</td><td>' . esc_html( (string) ( $record['source_name'] ?? '—' ) ) . '</td><td><span class="algq-status algq-status-capital">' . esc_html( ucwords( str_replace( '_', ' ', $status ?: 'unknown' ) ) ) . '</span></td><td>' . esc_html( self::currency( (float) ( $record['requested_amount'] ?? 0 ) ) ) . '</td><td>' . esc_html( self::currency( (float) ( $record['committed_amount'] ?? 0 ) ) ) . '</td><td>' . esc_html( self::currency( (float) ( $record['funded_amount'] ?? 0 ) ) ) . '</td></tr>';
            }
            echo '</tbody></table></div></div>';
        }

        echo '</section>';
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

    private static function render_funding_metric( string $label, float $value, string $variant ): void {
        echo '<div class="algq-funding-metric algq-funding-metric-' . esc_attr( sanitize_key( $variant ) ) . '"><span>' . esc_html( $label ) . '</span><strong>' . esc_html( self::currency( $value ) ) . '</strong></div>';
    }

    private static function render_progress( string $label, int $percent ): void {
        echo '<div class="algq-funding-progress"><div><span>' . esc_html( $label ) . '</span><strong>' . esc_html( $percent . '%' ) . '</strong></div><div class="algq-progress-track" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="' . esc_attr( (string) $percent ) . '"><span style="width:' . esc_attr( (string) $percent ) . '%"></span></div></div>';
    }

    private static function currency( float $value ): string {
        return '$' . number_format_i18n( max( 0, $value ), 0 );
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
