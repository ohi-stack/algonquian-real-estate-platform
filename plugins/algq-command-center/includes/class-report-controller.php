<?php
/**
 * Secured CSV and print-ready report controller.
 *
 * @package Algonquian_Command_Center
 */

defined( 'ABSPATH' ) || exit;

final class ALGQ_Command_Center_Report_Controller {
    public function register(): void {
        add_action( 'admin_post_algq_command_center_export_csv', array( $this, 'export_csv' ) );
        add_action( 'admin_post_algq_command_center_print_report', array( $this, 'print_report' ) );
    }

    public function export_csv(): void {
        $this->authorize( 'export_algq_reports', 'algq_command_center_export_csv' );

        $metrics = ALGQ_Command_Center_Data_Provider::metrics();
        $health  = ALGQ_Command_Center_Health_Monitor::checks();

        nocache_headers();
        header( 'Content-Type: text/csv; charset=utf-8' );
        header( 'Content-Disposition: attachment; filename="algq-command-center-' . gmdate( 'Y-m-d-His' ) . '.csv"' );

        $stream = fopen( 'php://output', 'w' );
        if ( false === $stream ) {
            wp_die( esc_html__( 'Unable to create the report stream.', 'algq-command-center' ) );
        }

        fputcsv( $stream, array( 'Section', 'Metric', 'Value', 'Status' ) );
        foreach ( $metrics as $key => $value ) {
            if ( is_array( $value ) ) {
                $value = wp_json_encode( $value );
            }
            fputcsv( $stream, array( 'KPI', sanitize_key( $key ), (string) $value, '' ) );
        }

        foreach ( $health as $key => $check ) {
            fputcsv(
                $stream,
                array(
                    'Health',
                    sanitize_key( $key ),
                    isset( $check['message'] ) ? wp_strip_all_tags( (string) $check['message'] ) : '',
                    isset( $check['status'] ) ? sanitize_key( (string) $check['status'] ) : 'warning',
                )
            );
        }

        fclose( $stream );
        exit;
    }

    public function print_report(): void {
        $this->authorize( 'export_algq_reports', 'algq_command_center_print_report' );

        $metrics = ALGQ_Command_Center_Data_Provider::metrics();
        $health  = ALGQ_Command_Center_Health_Monitor::checks();

        nocache_headers();
        header( 'Content-Type: text/html; charset=utf-8' );
        ?>
        <!doctype html>
        <html <?php language_attributes(); ?>>
        <head>
            <meta charset="<?php bloginfo( 'charset' ); ?>">
            <meta name="viewport" content="width=device-width,initial-scale=1">
            <title><?php echo esc_html__( 'Algonquian Admin Command Center Report', 'algq-command-center' ); ?></title>
            <style>body{font-family:Arial,sans-serif;margin:32px;color:#17202a}h1{color:#0b1f33}table{width:100%;border-collapse:collapse;margin:18px 0}th,td{border:1px solid #d9e0e6;padding:9px;text-align:left}th{background:#f5f7f9}.status-operational{color:#146c43}.status-warning{color:#8a6500}.status-failed{color:#b42318}@media print{button{display:none}}</style>
        </head>
        <body>
            <button type="button" onclick="window.print()"><?php echo esc_html__( 'Print or Save as PDF', 'algq-command-center' ); ?></button>
            <h1><?php echo esc_html__( 'Algonquian Admin Command Center', 'algq-command-center' ); ?></h1>
            <p><?php echo esc_html( sprintf( __( 'Generated %s UTC', 'algq-command-center' ), gmdate( 'Y-m-d H:i:s' ) ) ); ?></p>
            <h2><?php echo esc_html__( 'Executive KPIs', 'algq-command-center' ); ?></h2>
            <table><thead><tr><th><?php echo esc_html__( 'Metric', 'algq-command-center' ); ?></th><th><?php echo esc_html__( 'Value', 'algq-command-center' ); ?></th></tr></thead><tbody>
            <?php foreach ( $metrics as $key => $value ) : ?>
                <tr><td><?php echo esc_html( ucwords( str_replace( '_', ' ', $key ) ) ); ?></td><td><?php echo esc_html( is_array( $value ) ? wp_json_encode( $value ) : (string) $value ); ?></td></tr>
            <?php endforeach; ?>
            </tbody></table>
            <h2><?php echo esc_html__( 'System Health', 'algq-command-center' ); ?></h2>
            <table><thead><tr><th><?php echo esc_html__( 'Component', 'algq-command-center' ); ?></th><th><?php echo esc_html__( 'Status', 'algq-command-center' ); ?></th><th><?php echo esc_html__( 'Details', 'algq-command-center' ); ?></th></tr></thead><tbody>
            <?php foreach ( $health as $check ) : $status = isset( $check['status'] ) ? sanitize_key( $check['status'] ) : 'warning'; ?>
                <tr><td><?php echo esc_html( (string) $check['label'] ); ?></td><td class="status-<?php echo esc_attr( $status ); ?>"><?php echo esc_html( ucfirst( $status ) ); ?></td><td><?php echo esc_html( (string) $check['message'] ); ?></td></tr>
            <?php endforeach; ?>
            </tbody></table>
        </body>
        </html>
        <?php
        exit;
    }

    private function authorize( string $capability, string $nonce_action ): void {
        if ( ! current_user_can( $capability ) && ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'You are not authorized to export Command Center reports.', 'algq-command-center' ), 403 );
        }

        check_admin_referer( $nonce_action );
    }
}
