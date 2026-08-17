<?php
/**
 * Safe administrative command controller.
 *
 * @package Algonquian_Command_Center
 */

defined( 'ABSPATH' ) || exit;

final class ALGQ_Command_Center_Command_Controller {
    private const COMMANDS = array( 'run_health_check', 'refresh_metrics', 'rebuild_pages', 'reconcile_capabilities', 'clear_command_center_cache' );

    public function register(): void {
        add_action( 'admin_post_algq_command_center_command', array( $this, 'handle' ) );
    }

    public function handle(): void {
        if ( ! ALGQ_Command_Center_Security::can_run_commands() ) {
            wp_die( esc_html__( 'You are not authorized to run Command Center commands.', 'algq-command-center' ), '', array( 'response' => 403 ) );
        }
        check_admin_referer( 'algq_command_center_command' );
        $command = isset( $_POST['command'] ) ? sanitize_key( wp_unslash( $_POST['command'] ) ) : '';
        if ( ! in_array( $command, self::COMMANDS, true ) ) {
            wp_die( esc_html__( 'Unknown Command Center command.', 'algq-command-center' ), '', array( 'response' => 400 ) );
        }

        $result = $this->execute( $command );
        ALGQ_Command_Center_Audit_Provider::record_command( $command, array( 'result' => $result ) );
        do_action( 'algq_command_center_command_executed', $command, $result );

        $redirect = add_query_arg( array( 'page' => 'algq-command-center-system-health', 'algq_command' => $command, 'algq_result' => $result ? 'success' : 'warning' ), admin_url( 'admin.php' ) );
        wp_safe_redirect( $redirect );
        exit;
    }

    private function execute( string $command ): bool {
        return match ( $command ) {
            'run_health_check' => $this->run_health_check(),
            'refresh_metrics' => $this->refresh_metrics(),
            'rebuild_pages' => $this->rebuild_pages(),
            'reconcile_capabilities' => $this->reconcile_capabilities(),
            'clear_command_center_cache' => $this->clear_cache(),
            default => false,
        };
    }

    private function run_health_check(): bool {
        set_transient( 'algq_cc_last_health_summary', ALGQ_Command_Center_Health_Monitor::summary(), HOUR_IN_SECONDS );
        update_option( 'algq_command_center_last_health_check', gmdate( 'c' ) );
        return true;
    }

    private function refresh_metrics(): bool {
        set_transient( 'algq_cc_last_metrics', ALGQ_Command_Center_Data_Provider::metrics(), 5 * MINUTE_IN_SECONDS );
        update_option( 'algq_command_center_last_metrics_refresh', gmdate( 'c' ) );
        return true;
    }

    private function rebuild_pages(): bool {
        ALGQ_Command_Center_Page_Generator::create_required_pages();
        return true;
    }

    private function reconcile_capabilities(): bool {
        ALGQ_Command_Center_Activator::grant_capabilities();
        return true;
    }

    private function clear_cache(): bool {
        delete_transient( 'algq_cc_last_health_summary' );
        delete_transient( 'algq_cc_last_metrics' );
        return true;
    }
}
