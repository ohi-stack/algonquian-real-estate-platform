<?php

defined( 'ABSPATH' ) || exit;

final class ALGQ_Pipeline_Shortcodes {
    public static function init(): void {
        add_shortcode( 'algq_pipeline_dashboard', array( __CLASS__, 'dashboard' ) );
        add_shortcode( 'algq_pipeline_board', array( __CLASS__, 'board' ) );
        add_shortcode( 'algq_pipeline_activity', array( __CLASS__, 'activity' ) );
        add_action( 'wp_enqueue_scripts', array( __CLASS__, 'register_assets' ) );
    }

    public static function register_assets(): void {
        wp_register_style( 'algq-pipeline', ALGQ_PIPELINE_URL . 'assets/css/pipeline.css', array(), ALGQ_PIPELINE_VERSION );
        wp_register_script( 'algq-pipeline', ALGQ_PIPELINE_URL . 'assets/js/pipeline.js', array(), ALGQ_PIPELINE_VERSION, true );
        wp_localize_script( 'algq-pipeline', 'ALGQPipeline', array( 'root' => esc_url_raw( rest_url( 'algq/v1' ) ), 'nonce' => wp_create_nonce( 'wp_rest' ), 'conflictMessage' => __( 'This deal changed elsewhere. Refresh the board before trying again.', 'algq-pipeline-crm' ) ) );
    }

    public static function dashboard(): string {
        if ( ! current_user_can( 'view_algq_deals' ) ) {
            return self::restricted();
        }
        self::enqueue();
        $repo = ALGQ_Pipeline_Service::instance()->repository();
        $counts = $repo->count_by_stage();
        $active = array_sum( array_diff_key( $counts, array_flip( array( 'closed', 'lost', 'withdrawn', 'archived' ) ) ) );
        ob_start();
        ?>
        <div class="algq-pipeline-shell">
            <header class="algq-pipeline-header"><div><span class="algq-eyebrow">Algonquian Real Estate Platform</span><h2>Pipeline CRM</h2><p>Canonical deal records and controlled acquisition lifecycle.</p></div><a class="algq-button algq-button-primary" href="#algq-pipeline-board">Open Pipeline</a></header>
            <div class="algq-kpi-grid">
                <div class="algq-kpi"><span>Active Deals</span><strong><?php echo esc_html( $active ); ?></strong></div>
                <div class="algq-kpi"><span>Underwriting</span><strong><?php echo esc_html( $counts['underwriting'] ?? 0 ); ?></strong></div>
                <div class="algq-kpi"><span>Under Contract</span><strong><?php echo esc_html( $counts['under_contract'] ?? 0 ); ?></strong></div>
                <div class="algq-kpi"><span>Closing Scheduled</span><strong><?php echo esc_html( $counts['closing_scheduled'] ?? 0 ); ?></strong></div>
            </div>
            <?php echo self::board_markup(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
        </div>
        <?php
        return (string) ob_get_clean();
    }

    public static function board(): string {
        if ( ! current_user_can( 'view_algq_deals' ) ) {
            return self::restricted();
        }
        self::enqueue();
        return '<div class="algq-pipeline-shell">' . self::board_markup() . '</div>';
    }

    public static function activity(): string {
        if ( ! current_user_can( 'view_algq_deals' ) ) {
            return self::restricted();
        }
        self::enqueue();
        $items = ALGQ_Pipeline_Service::instance()->repository()->activity( 0, 50 );
        ob_start();
        ?><div class="algq-pipeline-shell"><section class="algq-panel"><h2>Recent Pipeline Activity</h2><div class="algq-activity-list">
        <?php if ( ! $items ) : ?><p class="algq-empty">No pipeline activity has been recorded.</p><?php endif; ?>
        <?php foreach ( $items as $item ) : ?><article><strong><?php echo esc_html( ucwords( str_replace( '_', ' ', $item['event'] ) ) ); ?></strong><p><?php echo esc_html( $item['message'] ); ?></p><time><?php echo esc_html( get_date_from_gmt( $item['created_at'], get_option( 'date_format' ) . ' ' . get_option( 'time_format' ) ) ); ?></time></article><?php endforeach; ?>
        </div></section></div><?php
        return (string) ob_get_clean();
    }

    private static function board_markup(): string {
        $repo = ALGQ_Pipeline_Service::instance()->repository();
        $settings = wp_parse_args( get_option( 'algq_pipeline_settings', array() ), array( 'cards_per_stage' => 50 ) );
        ob_start();
        ?><div id="algq-pipeline-board" class="algq-board" data-can-transition="<?php echo current_user_can( 'transition_algq_deals' ) ? '1' : '0'; ?>">
        <?php foreach ( ALGQ_Pipeline_Stages::all() as $key => $label ) :
            if ( 'archived' === $key ) { continue; }
            $deals = $repo->list( array( 'stage' => $key, 'per_page' => absint( $settings['cards_per_stage'] ) ) );
        ?><section class="algq-stage" data-stage="<?php echo esc_attr( $key ); ?>"><header><span><?php echo esc_html( $label ); ?></span><b><?php echo esc_html( count( $deals ) ); ?></b></header><div class="algq-dropzone">
            <?php if ( ! $deals ) : ?><p class="algq-empty">No deals</p><?php endif; ?>
            <?php foreach ( $deals as $deal ) : ?><article class="algq-deal-card" draggable="<?php echo current_user_can( 'transition_algq_deals' ) ? 'true' : 'false'; ?>" data-id="<?php echo esc_attr( $deal['id'] ); ?>" data-version="<?php echo esc_attr( $deal['record_version'] ); ?>"><span class="algq-deal-number"><?php echo esc_html( $deal['deal_number'] ); ?></span><h3><?php echo esc_html( $deal['title'] ); ?></h3><p><?php echo esc_html( $deal['property_address'] ); ?></p><div><span class="algq-priority algq-priority-<?php echo esc_attr( $deal['priority'] ); ?>"><?php echo esc_html( ucfirst( $deal['priority'] ) ); ?></span><?php if ( (float) $deal['asking_price'] > 0 ) : ?><span><?php echo esc_html( wp_strip_all_tags( wp_sprintf( '$%s', number_format_i18n( (float) $deal['asking_price'], 0 ) ) ) ); ?></span><?php endif; ?></div></article><?php endforeach; ?>
        </div></section><?php endforeach; ?></div><?php
        return (string) ob_get_clean();
    }

    private static function enqueue(): void {
        wp_enqueue_style( 'algq-pipeline' );
        wp_enqueue_script( 'algq-pipeline' );
    }

    private static function restricted(): string {
        return '<div class="algq-pipeline-restricted">' . esc_html__( 'Access to Pipeline CRM is restricted.', 'algq-pipeline-crm' ) . '</div>';
    }
}
