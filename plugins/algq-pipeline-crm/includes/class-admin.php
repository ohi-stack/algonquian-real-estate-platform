<?php

defined( 'ABSPATH' ) || exit;

final class ALGQ_Pipeline_Admin {
    public static function init(): void {
        add_action( 'admin_menu', array( __CLASS__, 'menu' ) );
        add_action( 'admin_enqueue_scripts', array( __CLASS__, 'assets' ) );
        add_action( 'admin_post_algq_pipeline_create_deal', array( __CLASS__, 'create_deal' ) );
        add_action( 'admin_post_algq_pipeline_save_settings', array( __CLASS__, 'save_settings' ) );
        add_action( 'wp_dashboard_setup', array( __CLASS__, 'dashboard_widget' ) );
    }

    public static function menu(): void {
        add_menu_page( 'Pipeline CRM', 'Pipeline CRM', 'view_algq_deals', 'algq-pipeline', array( __CLASS__, 'dashboard' ), 'dashicons-networking', 26 );
        add_submenu_page( 'algq-pipeline', 'Pipeline Dashboard', 'Dashboard', 'view_algq_deals', 'algq-pipeline', array( __CLASS__, 'dashboard' ) );
        add_submenu_page( 'algq-pipeline', 'Pipeline Board', 'Pipeline Board', 'view_algq_deals', 'algq-pipeline-board', array( __CLASS__, 'board' ) );
        add_submenu_page( 'algq-pipeline', 'Deals', 'Deals', 'view_algq_deals', 'algq-pipeline-deals', array( __CLASS__, 'deals' ) );
        add_submenu_page( 'algq-pipeline', 'Create Deal', 'Create Deal', 'create_algq_deals', 'algq-pipeline-create', array( __CLASS__, 'create' ) );
        add_submenu_page( 'algq-pipeline', 'Settings', 'Settings', 'manage_algq_pipeline', 'algq-pipeline-settings', array( __CLASS__, 'settings' ) );
    }

    public static function assets( string $hook ): void {
        if ( false === strpos( $hook, 'algq-pipeline' ) ) {
            return;
        }
        ALGQ_Pipeline_Shortcodes::register_assets();
        wp_enqueue_style( 'algq-pipeline' );
        wp_enqueue_script( 'algq-pipeline' );
    }

    public static function dashboard(): void {
        self::header( 'Pipeline CRM', 'Manage canonical deal records and the controlled acquisition lifecycle.' );
        echo do_shortcode( '[algq_pipeline_dashboard]' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        self::footer();
    }

    public static function board(): void {
        self::header( 'Pipeline Board', 'Move authorized deals between valid acquisition stages.' );
        echo do_shortcode( '[algq_pipeline_board]' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        self::footer();
    }

    public static function deals(): void {
        $repo = ALGQ_Pipeline_Service::instance()->repository();
        $search = sanitize_text_field( wp_unslash( $_GET['s'] ?? '' ) );
        $stage = sanitize_key( wp_unslash( $_GET['stage'] ?? '' ) );
        $items = $repo->list( array( 'search' => $search, 'stage' => $stage, 'per_page' => 100, 'include_archived' => true ) );
        self::header( 'Deals', 'Search and review canonical deal records.' );
        ?>
        <form method="get" class="algq-admin-filter"><input type="hidden" name="page" value="algq-pipeline-deals"><input type="search" name="s" value="<?php echo esc_attr( $search ); ?>" placeholder="Deal number, title, address, or contact"><select name="stage"><option value="">All stages</option><?php foreach ( ALGQ_Pipeline_Stages::all() as $key => $label ) : ?><option value="<?php echo esc_attr( $key ); ?>" <?php selected( $stage, $key ); ?>><?php echo esc_html( $label ); ?></option><?php endforeach; ?></select><button class="button">Filter</button></form>
        <div class="algq-panel"><table class="widefat striped"><thead><tr><th>Deal</th><th>Property</th><th>Stage</th><th>Priority</th><th>Assigned</th><th>Updated</th></tr></thead><tbody>
        <?php if ( ! $items ) : ?><tr><td colspan="6">No deals found.</td></tr><?php endif; ?>
        <?php foreach ( $items as $deal ) : $user = $deal['assigned_user_id'] ? get_user_by( 'id', $deal['assigned_user_id'] ) : null; ?><tr><td><strong><?php echo esc_html( $deal['deal_number'] ); ?></strong><br><?php echo esc_html( $deal['title'] ); ?></td><td><?php echo esc_html( $deal['property_address'] ); ?></td><td><?php echo esc_html( ALGQ_Pipeline_Stages::label( $deal['stage'] ) ); ?></td><td><?php echo esc_html( ucfirst( $deal['priority'] ) ); ?></td><td><?php echo esc_html( $user ? $user->display_name : 'Unassigned' ); ?></td><td><?php echo esc_html( get_date_from_gmt( $deal['updated_at'], get_option( 'date_format' ) ) ); ?></td></tr><?php endforeach; ?>
        </tbody></table></div><?php
        self::footer();
    }

    public static function create(): void {
        self::header( 'Create Deal', 'Create a canonical deal record directly in Pipeline CRM.' );
        ?>
        <div class="algq-panel algq-form-panel"><form action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="post"><input type="hidden" name="action" value="algq_pipeline_create_deal"><?php wp_nonce_field( 'algq_pipeline_create_deal' ); ?><div class="algq-form-grid"><label>Deal title<input name="title" required></label><label>Property address<input name="property_address"></label><label>Primary contact<input name="primary_contact"></label><label>Asking price<input type="number" min="0" step="0.01" name="asking_price"></label><label>Priority<select name="priority"><option value="normal">Normal</option><option value="low">Low</option><option value="high">High</option><option value="critical">Critical</option></select></label><label>Source<input name="source"></label></div><p><button class="button button-primary">Create Deal</button></p></form></div>
        <?php self::footer();
    }

    public static function create_deal(): void {
        if ( ! current_user_can( 'create_algq_deals' ) ) {
            wp_die( esc_html__( 'You are not permitted to create deals.', 'algq-pipeline-crm' ) );
        }
        check_admin_referer( 'algq_pipeline_create_deal' );
        $result = ALGQ_Pipeline_Service::instance()->create_deal( array_map( 'wp_unslash', $_POST ) );
        $url = admin_url( 'admin.php?page=algq-pipeline-deals' );
        $url = add_query_arg( is_wp_error( $result ) ? array( 'algq_error' => $result->get_error_code() ) : array( 'algq_created' => 1 ), $url );
        wp_safe_redirect( $url );
        exit;
    }

    public static function settings(): void {
        $settings = wp_parse_args( get_option( 'algq_pipeline_settings', array() ), array( 'cards_per_stage' => 50, 'delete_data_on_uninstall' => 'no' ) );
        self::header( 'Pipeline Settings', 'Control board limits and conservative uninstall behavior.' );
        ?><div class="algq-panel algq-form-panel"><form action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="post"><input type="hidden" name="action" value="algq_pipeline_save_settings"><?php wp_nonce_field( 'algq_pipeline_save_settings' ); ?><label>Cards per stage<input type="number" min="10" max="100" name="cards_per_stage" value="<?php echo esc_attr( $settings['cards_per_stage'] ); ?>"></label><label><input type="checkbox" name="delete_data_on_uninstall" value="yes" <?php checked( $settings['delete_data_on_uninstall'], 'yes' ); ?>> Delete Pipeline CRM-owned data on uninstall</label><p class="description">Deactivation never removes records. Uninstall cleanup occurs only when this option is explicitly enabled.</p><button class="button button-primary">Save Settings</button></form></div><?php
        self::footer();
    }

    public static function save_settings(): void {
        if ( ! current_user_can( 'manage_algq_pipeline' ) ) { wp_die( 'Unauthorized.' ); }
        check_admin_referer( 'algq_pipeline_save_settings' );
        update_option( 'algq_pipeline_settings', array( 'cards_per_stage' => min( 100, max( 10, absint( $_POST['cards_per_stage'] ?? 50 ) ) ), 'delete_data_on_uninstall' => isset( $_POST['delete_data_on_uninstall'] ) ? 'yes' : 'no' ), false );
        wp_safe_redirect( add_query_arg( 'updated', 1, admin_url( 'admin.php?page=algq-pipeline-settings' ) ) );
        exit;
    }

    public static function dashboard_widget(): void {
        if ( current_user_can( 'view_algq_deals' ) ) {
            wp_add_dashboard_widget( 'algq_pipeline_widget', 'Algonquian Pipeline CRM', array( __CLASS__, 'widget' ) );
        }
    }

    public static function widget(): void {
        $repo = ALGQ_Pipeline_Service::instance()->repository();
        echo '<p><strong>' . esc_html( $repo->count() ) . '</strong> active deal records.</p><p><a class="button button-primary" href="' . esc_url( admin_url( 'admin.php?page=algq-pipeline' ) ) . '">Open Pipeline CRM</a></p>';
    }

    private static function header( string $title, string $description ): void {
        echo '<div class="wrap algq-admin-wrap"><header class="algq-admin-header"><div><span class="algq-eyebrow">Algonquian Real Estate Platform</span><h1>' . esc_html( $title ) . '</h1><p>' . esc_html( $description ) . '</p></div><div><span class="algq-health">Operational</span><small>Version ' . esc_html( ALGQ_PIPELINE_VERSION ) . '</small></div></header>';
    }

    private static function footer(): void { echo '</div>'; }
}
