<?php

defined( 'ABSPATH' ) || exit;

final class ALGQ_Automation_Admin {
    public static function register(): void {
        add_action( 'admin_menu', array( __CLASS__, 'menu' ) );
        add_action( 'admin_enqueue_scripts', array( __CLASS__, 'assets' ) );
        add_action( 'admin_post_algq_automation_save_rule', array( __CLASS__, 'save_rule' ) );
        add_action( 'admin_post_algq_automation_toggle_rule', array( __CLASS__, 'toggle_rule' ) );
        add_action( 'admin_post_algq_automation_archive_rule', array( __CLASS__, 'archive_rule' ) );
        add_action( 'admin_post_algq_automation_retry_job', array( __CLASS__, 'retry_job' ) );
        add_action( 'admin_post_algq_automation_run_queue', array( __CLASS__, 'run_queue' ) );
        add_action( 'admin_post_algq_automation_save_settings', array( __CLASS__, 'save_settings' ) );
    }

    public static function menu(): void {
        add_menu_page(
            __( 'Algonquian Automation', 'algq-automation-engine' ),
            __( 'ARE Automation', 'algq-automation-engine' ),
            'view_algq_automation',
            'algq-automation',
            array( __CLASS__, 'dashboard' ),
            'dashicons-controls-repeat',
            57
        );

        add_submenu_page( 'algq-automation', __( 'Automation Rules', 'algq-automation-engine' ), __( 'Rules', 'algq-automation-engine' ), 'view_algq_automation', 'algq-automation-rules', array( __CLASS__, 'rules' ) );
        add_submenu_page( 'algq-automation', __( 'Automation Queue', 'algq-automation-engine' ), __( 'Queue', 'algq-automation-engine' ), 'view_algq_automation_logs', 'algq-automation-queue', array( __CLASS__, 'queue' ) );
        add_submenu_page( 'algq-automation', __( 'Automation Logs', 'algq-automation-engine' ), __( 'Logs', 'algq-automation-engine' ), 'view_algq_automation_logs', 'algq-automation-logs', array( __CLASS__, 'logs' ) );
        add_submenu_page( 'algq-automation', __( 'Automation Settings', 'algq-automation-engine' ), __( 'Settings', 'algq-automation-engine' ), 'manage_algq_automation', 'algq-automation-settings', array( __CLASS__, 'settings' ) );
    }

    public static function assets( string $hook ): void {
        if ( ! str_contains( $hook, 'algq-automation' ) ) {
            return;
        }

        wp_enqueue_style( 'algq-automation-admin', ALGQ_AUTOMATION_URL . 'assets/css/admin.css', array(), ALGQ_AUTOMATION_VERSION );
    }

    public static function dashboard(): void {
        global $wpdb;

        ALGQ_Automation_Security::require_capability( 'view_algq_automation' );
        $tables = ALGQ_Automation_DB::tables();
        $counts = array(
            'active_rules' => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$tables['rules']} WHERE status = 'active'" ),
            'queued_jobs'  => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$tables['jobs']} WHERE status IN ('pending','retry','running')" ),
            'dead_jobs'    => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$tables['jobs']} WHERE status = 'dead'" ),
            'tasks'        => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$tables['tasks']} WHERE task_status = 'pending'" ),
        );
        $health = ALGQ_Automation_Engine::health();

        self::header( __( 'Algonquian Automation Engine', 'algq-automation-engine' ), __( 'Controlled workflow execution across the Algonquian Real Estate platform.', 'algq-automation-engine' ) );
        echo '<div class="algq-kpi-grid">';
        self::kpi( __( 'Active Rules', 'algq-automation-engine' ), $counts['active_rules'] );
        self::kpi( __( 'Queued Jobs', 'algq-automation-engine' ), $counts['queued_jobs'] );
        self::kpi( __( 'Dead-Letter Jobs', 'algq-automation-engine' ), $counts['dead_jobs'] );
        self::kpi( __( 'Open Tasks', 'algq-automation-engine' ), $counts['tasks'] );
        echo '</div>';
        echo '<div class="algq-panel"><h2>' . esc_html__( 'System Health', 'algq-automation-engine' ) . '</h2>';
        echo '<p><span class="algq-status algq-status-' . esc_attr( $health['status'] ) . '">' . esc_html( ucfirst( $health['status'] ) ) . '</span></p>';
        if ( $health['issues'] ) {
            echo '<ul>';
            foreach ( $health['issues'] as $issue ) {
                echo '<li>' . esc_html( $issue ) . '</li>';
            }
            echo '</ul>';
        }
        echo '</div>';
        echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '"><input type="hidden" name="action" value="algq_automation_run_queue">';
        wp_nonce_field( 'algq_automation_run_queue' );
        submit_button( __( 'Run Queue Now', 'algq-automation-engine' ), 'secondary', 'submit', false );
        echo '</form></div>';
    }

    public static function rules(): void {
        global $wpdb;

        ALGQ_Automation_Security::require_capability( 'view_algq_automation' );
        $tables = ALGQ_Automation_DB::tables();
        $rules  = $wpdb->get_results( "SELECT * FROM {$tables['rules']} WHERE status <> 'archived' ORDER BY priority ASC, id DESC", ARRAY_A );

        self::header( __( 'Automation Rules', 'algq-automation-engine' ), __( 'Create, validate, activate, pause, and archive workflow rules.', 'algq-automation-engine' ) );
        self::notice();

        if ( ALGQ_Automation_Security::can( 'edit_algq_automation_rules' ) ) {
            self::rule_form();
        }

        echo '<div class="algq-panel"><table class="widefat striped"><thead><tr><th>' . esc_html__( 'Rule', 'algq-automation-engine' ) . '</th><th>' . esc_html__( 'Trigger', 'algq-automation-engine' ) . '</th><th>' . esc_html__( 'Action', 'algq-automation-engine' ) . '</th><th>' . esc_html__( 'Status', 'algq-automation-engine' ) . '</th><th>' . esc_html__( 'Last Run', 'algq-automation-engine' ) . '</th><th>' . esc_html__( 'Actions', 'algq-automation-engine' ) . '</th></tr></thead><tbody>';
        foreach ( $rules as $rule ) {
            echo '<tr><td><strong>' . esc_html( $rule['rule_name'] ) . '</strong><br><small>' . esc_html( $rule['description'] ) . '</small></td>';
            echo '<td><code>' . esc_html( $rule['trigger_key'] ) . '</code></td><td><code>' . esc_html( $rule['action_key'] ) . '</code></td>';
            echo '<td><span class="algq-status algq-status-' . esc_attr( $rule['status'] ) . '">' . esc_html( ucfirst( $rule['status'] ) ) . '</span></td>';
            echo '<td>' . esc_html( $rule['last_run_at'] ?: '—' ) . '</td><td>';
            if ( ALGQ_Automation_Security::can( 'edit_algq_automation_rules' ) ) {
                self::action_link( 'algq_automation_toggle_rule', 'algq_automation_toggle_rule_' . $rule['id'], array( 'rule_id' => $rule['id'] ), 'active' === $rule['status'] ? __( 'Pause', 'algq-automation-engine' ) : __( 'Activate', 'algq-automation-engine' ) );
            }
            if ( ALGQ_Automation_Security::can( 'delete_algq_automation_rules' ) ) {
                echo ' | ';
                self::action_link( 'algq_automation_archive_rule', 'algq_automation_archive_rule_' . $rule['id'], array( 'rule_id' => $rule['id'] ), __( 'Archive', 'algq-automation-engine' ) );
            }
            echo '</td></tr>';
        }
        if ( ! $rules ) {
            echo '<tr><td colspan="6">' . esc_html__( 'No automation rules have been created.', 'algq-automation-engine' ) . '</td></tr>';
        }
        echo '</tbody></table></div></div>';
    }

    private static function rule_form(): void {
        $triggers = ALGQ_Automation_Engine::triggers();
        $actions  = ALGQ_Automation_Actions::registry();

        echo '<div class="algq-panel"><h2>' . esc_html__( 'Create Rule', 'algq-automation-engine' ) . '</h2><form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
        echo '<input type="hidden" name="action" value="algq_automation_save_rule">';
        wp_nonce_field( 'algq_automation_save_rule' );
        echo '<div class="algq-form-grid"><p><label>' . esc_html__( 'Rule name', 'algq-automation-engine' ) . '<input class="regular-text" required name="rule_name"></label></p>';
        echo '<p><label>' . esc_html__( 'Description', 'algq-automation-engine' ) . '<input class="regular-text" name="description"></label></p>';
        echo '<p><label>' . esc_html__( 'Trigger', 'algq-automation-engine' ) . '<select name="trigger_key" required><option value="">—</option>';
        foreach ( $triggers as $key => $label ) {
            echo '<option value="' . esc_attr( $key ) . '">' . esc_html( $label ) . '</option>';
        }
        echo '</select></label></p><p><label>' . esc_html__( 'Action', 'algq-automation-engine' ) . '<select name="action_key" required><option value="">—</option>';
        foreach ( $actions as $key => $action ) {
            echo '<option value="' . esc_attr( $key ) . '">' . esc_html( $action['label'] ) . '</option>';
        }
        echo '</select></label></p><p><label>' . esc_html__( 'Priority', 'algq-automation-engine' ) . '<input type="number" name="priority" value="100" min="1" max="1000"></label></p>';
        echo '<p><label>' . esc_html__( 'Max attempts', 'algq-automation-engine' ) . '<input type="number" name="max_attempts" value="3" min="1" max="10"></label></p></div>';
        echo '<p><label>' . esc_html__( 'Conditions JSON', 'algq-automation-engine' ) . '<textarea class="large-text code" rows="5" name="conditions">[]</textarea></label><br><small>' . esc_html__( 'Example: [{"field":"payload.new_status","operator":"equals","value":"closed"}]', 'algq-automation-engine' ) . '</small></p>';
        echo '<p><label>' . esc_html__( 'Action payload JSON', 'algq-automation-engine' ) . '<textarea class="large-text code" rows="6" name="action_payload">{}</textarea></label></p>';
        echo '<p><label><input type="checkbox" name="activate" value="1"> ' . esc_html__( 'Activate immediately', 'algq-automation-engine' ) . '</label></p>';
        submit_button( __( 'Create Rule', 'algq-automation-engine' ) );
        echo '</form></div>';
    }

    public static function queue(): void {
        global $wpdb;

        ALGQ_Automation_Security::require_capability( 'view_algq_automation_logs' );
        $tables = ALGQ_Automation_DB::tables();
        $jobs   = $wpdb->get_results( "SELECT * FROM {$tables['jobs']} ORDER BY id DESC LIMIT 100", ARRAY_A );

        self::header( __( 'Automation Queue', 'algq-automation-engine' ), __( 'Review pending, retrying, completed, and dead-letter jobs.', 'algq-automation-engine' ) );
        self::notice();
        echo '<div class="algq-panel"><table class="widefat striped"><thead><tr><th>ID</th><th>' . esc_html__( 'Event', 'algq-automation-engine' ) . '</th><th>' . esc_html__( 'Status', 'algq-automation-engine' ) . '</th><th>' . esc_html__( 'Attempts', 'algq-automation-engine' ) . '</th><th>' . esc_html__( 'Available', 'algq-automation-engine' ) . '</th><th>' . esc_html__( 'Error', 'algq-automation-engine' ) . '</th><th>' . esc_html__( 'Action', 'algq-automation-engine' ) . '</th></tr></thead><tbody>';
        foreach ( $jobs as $job ) {
            echo '<tr><td>' . absint( $job['id'] ) . '</td><td><code>' . esc_html( $job['event_key'] ) . '</code></td><td><span class="algq-status algq-status-' . esc_attr( $job['status'] ) . '">' . esc_html( ucfirst( $job['status'] ) ) . '</span></td><td>' . absint( $job['attempts'] ) . '/' . absint( $job['max_attempts'] ) . '</td><td>' . esc_html( $job['available_at'] ) . '</td><td>' . esc_html( $job['last_error'] ?: '—' ) . '</td><td>';
            if ( in_array( $job['status'], array( 'dead', 'failed', 'completed' ), true ) && ALGQ_Automation_Security::can( 'run_algq_automation' ) ) {
                self::action_link( 'algq_automation_retry_job', 'algq_automation_retry_job_' . $job['id'], array( 'job_id' => $job['id'] ), __( 'Retry', 'algq-automation-engine' ) );
            }
            echo '</td></tr>';
        }
        if ( ! $jobs ) {
            echo '<tr><td colspan="7">' . esc_html__( 'The automation queue is empty.', 'algq-automation-engine' ) . '</td></tr>';
        }
        echo '</tbody></table></div></div>';
    }

    public static function logs(): void {
        global $wpdb;

        ALGQ_Automation_Security::require_capability( 'view_algq_automation_logs' );
        $tables = ALGQ_Automation_DB::tables();
        $logs   = $wpdb->get_results( "SELECT * FROM {$tables['logs']} ORDER BY id DESC LIMIT 100", ARRAY_A );
        self::header( __( 'Automation Audit Log', 'algq-automation-engine' ), __( 'Append-only operational history with sensitive values redacted.', 'algq-automation-engine' ) );
        echo '<div class="algq-panel"><table class="widefat striped"><thead><tr><th>' . esc_html__( 'Time', 'algq-automation-engine' ) . '</th><th>' . esc_html__( 'Event', 'algq-automation-engine' ) . '</th><th>' . esc_html__( 'Status', 'algq-automation-engine' ) . '</th><th>' . esc_html__( 'Message', 'algq-automation-engine' ) . '</th></tr></thead><tbody>';
        foreach ( $logs as $log ) {
            echo '<tr><td>' . esc_html( $log['created_at'] ) . '</td><td><code>' . esc_html( $log['event_key'] ) . '</code></td><td>' . esc_html( $log['status'] ) . '</td><td>' . esc_html( $log['message'] ) . '</td></tr>';
        }
        if ( ! $logs ) {
            echo '<tr><td colspan="4">' . esc_html__( 'No audit events have been recorded.', 'algq-automation-engine' ) . '</td></tr>';
        }
        echo '</tbody></table></div></div>';
    }

    public static function settings(): void {
        ALGQ_Automation_Security::require_capability( 'manage_algq_automation' );
        $settings = ALGQ_Automation_Engine::settings();
        self::header( __( 'Automation Settings', 'algq-automation-engine' ), __( 'Control execution, queue processing, idempotency, retention, and uninstall behavior.', 'algq-automation-engine' ) );
        self::notice();
        echo '<div class="algq-panel"><form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '"><input type="hidden" name="action" value="algq_automation_save_settings">';
        wp_nonce_field( 'algq_automation_save_settings' );
        self::checkbox( 'enabled', __( 'Enable automation execution', 'algq-automation-engine' ), $settings );
        self::checkbox( 'logging_enabled', __( 'Enable local audit logging', 'algq-automation-engine' ), $settings );
        self::checkbox( 'queue_enabled', __( 'Enable background queue processing', 'algq-automation-engine' ), $settings );
        echo '<p><label>' . esc_html__( 'Queue batch size', 'algq-automation-engine' ) . ' <input type="number" min="1" max="100" name="batch_size" value="' . absint( $settings['batch_size'] ) . '"></label></p>';
        echo '<p><label>' . esc_html__( 'Default maximum attempts', 'algq-automation-engine' ) . ' <input type="number" min="1" max="10" name="default_attempts" value="' . absint( $settings['default_attempts'] ) . '"></label></p>';
        echo '<p><label>' . esc_html__( 'Duplicate suppression window (seconds)', 'algq-automation-engine' ) . ' <input type="number" min="1" max="86400" name="dedupe_window" value="' . absint( $settings['dedupe_window'] ) . '"></label></p>';
        self::checkbox( 'delete_data_on_uninstall', __( 'Delete Automation Engine data on uninstall', 'algq-automation-engine' ), $settings );
        echo '<p class="description">' . esc_html__( 'Operational data is preserved by default. Enable deletion only after records have been exported and retention requirements have been reviewed.', 'algq-automation-engine' ) . '</p>';
        submit_button( __( 'Save Settings', 'algq-automation-engine' ) );
        echo '</form></div></div>';
    }

    public static function save_rule(): void {
        global $wpdb;

        ALGQ_Automation_Security::verify_admin_request( 'algq_automation_save_rule', 'edit_algq_automation_rules' );
        $conditions = ALGQ_Automation_Security::decode_json_object( $_POST['conditions'] ?? '[]' );
        $payload    = ALGQ_Automation_Security::decode_json_object( $_POST['action_payload'] ?? '{}' );

        if ( is_wp_error( $conditions ) || is_wp_error( $payload ) ) {
            self::redirect( 'algq-automation-rules', 'invalid_json' );
        }

        $trigger = sanitize_key( wp_unslash( $_POST['trigger_key'] ?? '' ) );
        $action  = sanitize_key( wp_unslash( $_POST['action_key'] ?? '' ) );

        if ( ! isset( ALGQ_Automation_Engine::triggers()[ $trigger ] ) || ! isset( ALGQ_Automation_Actions::registry()[ $action ] ) ) {
            self::redirect( 'algq-automation-rules', 'invalid_registry_key' );
        }

        $rule_name = sanitize_text_field( wp_unslash( $_POST['rule_name'] ?? '' ) );
        if ( '' === $rule_name ) {
            self::redirect( 'algq-automation-rules', 'rule_name_required' );
        }

        $now   = current_time( 'mysql', true );
        $saved = $wpdb->insert(
            ALGQ_Automation_DB::tables()['rules'],
            array(
                'uuid'           => wp_generate_uuid4(),
                'rule_name'      => $rule_name,
                'description'    => sanitize_textarea_field( wp_unslash( $_POST['description'] ?? '' ) ),
                'trigger_key'    => $trigger,
                'conditions'     => wp_json_encode( $conditions ),
                'action_key'     => $action,
                'action_payload' => wp_json_encode( $payload ),
                'status'         => empty( $_POST['activate'] ) ? 'draft' : 'active',
                'priority'       => min( 1000, max( 1, absint( $_POST['priority'] ?? 100 ) ) ),
                'max_attempts'   => min( 10, max( 1, absint( $_POST['max_attempts'] ?? 3 ) ) ),
                'created_by'     => get_current_user_id(),
                'updated_by'     => get_current_user_id(),
                'created_at'     => $now,
                'updated_at'     => $now,
            ),
            array( '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%d', '%d', '%d', '%d', '%s', '%s' )
        );

        self::redirect( 'algq-automation-rules', false === $saved ? 'save_failed' : 'saved' );
    }

    public static function toggle_rule(): void {
        global $wpdb;
        $rule_id = absint( $_GET['rule_id'] ?? 0 );
        ALGQ_Automation_Security::verify_admin_request( 'algq_automation_toggle_rule_' . $rule_id, 'edit_algq_automation_rules' );
        $table  = ALGQ_Automation_DB::tables()['rules'];
        $status = $wpdb->get_var( $wpdb->prepare( "SELECT status FROM {$table} WHERE id = %d", $rule_id ) );
        $wpdb->update( $table, array( 'status' => 'active' === $status ? 'paused' : 'active', 'updated_by' => get_current_user_id(), 'updated_at' => current_time( 'mysql', true ) ), array( 'id' => $rule_id ), array( '%s', '%d', '%s' ), array( '%d' ) );
        self::redirect( 'algq-automation-rules', 'updated' );
    }

    public static function archive_rule(): void {
        global $wpdb;
        $rule_id = absint( $_GET['rule_id'] ?? 0 );
        ALGQ_Automation_Security::verify_admin_request( 'algq_automation_archive_rule_' . $rule_id, 'delete_algq_automation_rules' );
        $wpdb->update( ALGQ_Automation_DB::tables()['rules'], array( 'status' => 'archived', 'updated_by' => get_current_user_id(), 'updated_at' => current_time( 'mysql', true ) ), array( 'id' => $rule_id ), array( '%s', '%d', '%s' ), array( '%d' ) );
        self::redirect( 'algq-automation-rules', 'archived' );
    }

    public static function retry_job(): void {
        $job_id = absint( $_GET['job_id'] ?? 0 );
        ALGQ_Automation_Security::verify_admin_request( 'algq_automation_retry_job_' . $job_id, 'run_algq_automation' );
        ALGQ_Automation_Engine::retry_job( $job_id );
        self::redirect( 'algq-automation-queue', 'retry_queued' );
    }

    public static function run_queue(): void {
        ALGQ_Automation_Security::verify_admin_request( 'algq_automation_run_queue', 'run_algq_automation' );
        ALGQ_Automation_Engine::process_queue();
        self::redirect( 'algq-automation', 'queue_processed' );
    }

    public static function save_settings(): void {
        ALGQ_Automation_Security::verify_admin_request( 'algq_automation_save_settings', 'manage_algq_automation' );
        $settings = array(
            'enabled'          => empty( $_POST['enabled'] ) ? 0 : 1,
            'logging_enabled'  => empty( $_POST['logging_enabled'] ) ? 0 : 1,
            'queue_enabled'    => empty( $_POST['queue_enabled'] ) ? 0 : 1,
            'batch_size'       => min( 100, max( 1, absint( $_POST['batch_size'] ?? 10 ) ) ),
            'default_attempts' => min( 10, max( 1, absint( $_POST['default_attempts'] ?? 3 ) ) ),
            'dedupe_window'    => min( DAY_IN_SECONDS, max( 1, absint( $_POST['dedupe_window'] ?? 300 ) ) ),
            'delete_data_on_uninstall' => empty( $_POST['delete_data_on_uninstall'] ) ? 0 : 1,
        );
        update_option( 'algq_automation_settings', $settings, false );
        self::redirect( 'algq-automation-settings', 'settings_saved' );
    }

    private static function header( string $title, string $description ): void {
        echo '<div class="wrap algq-automation-admin"><div class="algq-page-header"><div><h1>' . esc_html( $title ) . '</h1><p>' . esc_html( $description ) . '</p></div><div><span class="algq-version">v' . esc_html( ALGQ_AUTOMATION_VERSION ) . '</span></div></div>';
    }

    private static function kpi( string $label, int $value ): void {
        echo '<div class="algq-kpi"><strong>' . number_format_i18n( $value ) . '</strong><span>' . esc_html( $label ) . '</span></div>';
    }

    private static function checkbox( string $name, string $label, array $settings ): void {
        echo '<p><label><input type="checkbox" name="' . esc_attr( $name ) . '" value="1" ' . checked( ! empty( $settings[ $name ] ), true, false ) . '> ' . esc_html( $label ) . '</label></p>';
    }

    private static function action_link( string $action, string $nonce_action, array $args, string $label ): void {
        $url = add_query_arg( array_merge( array( 'action' => $action ), $args ), admin_url( 'admin-post.php' ) );
        echo '<a href="' . esc_url( wp_nonce_url( $url, $nonce_action ) ) . '">' . esc_html( $label ) . '</a>';
    }

    private static function redirect( string $page, string $notice ): never {
        wp_safe_redirect( add_query_arg( array( 'page' => $page, 'algq_notice' => $notice ), admin_url( 'admin.php' ) ) );
        exit;
    }

    private static function notice(): void {
        if ( empty( $_GET['algq_notice'] ) ) {
            return;
        }
        echo '<div class="notice notice-info is-dismissible"><p>' . esc_html( sanitize_text_field( wp_unslash( $_GET['algq_notice'] ) ) ) . '</p></div>';
    }
}
