<?php
/**
 * Secured CSV and print/PDF-ready report controller.
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
        $this->authorize( 'algq_command_center_export_csv' );
        $metrics = ALGQ_Command_Center_Data_Provider::metrics();
        $health = ALGQ_Command_Center_Health_Monitor::checks();

        nocache_headers();
        header( 'Content-Type: text/csv; charset=utf-8' );
        header( 'Content-Disposition: attachment; filename="algq-command-center-' . gmdate( 'Y-m-d-His' ) . '.csv"' );
        $stream = fopen( 'php://output', 'w' );
        if ( false === $stream ) {
            wp_die( esc_html__( 'Unable to create report stream.', 'algq-command-center' ) );
        }

        fputcsv( $stream, array( 'Section', 'Metric', 'Value', 'Status' ) );
        foreach ( $metrics as $key => $value ) {
            fputcsv( $stream, array( 'KPI', self::csv_cell( $key ), self::csv_cell( is_array( $value ) ? wp_json_encode( $value ) : (string) $value ), '' ) );
        }
        foreach ( $health as $key => $check ) {
            fputcsv( $stream, array( 'Health', self::csv_cell( $key ), self::csv_cell( (string) ( $check['message'] ?? '' ) ), self::csv_cell( (string) ( $check['status'] ?? 'warning' ) ) ) );
        }
        fclose( $stream );
        ALGQ_Command_Center_Audit_Provider::record_command( 'export_csv' );
        exit;
    }

    public function print_report(): void {
        $this->authorize( 'algq_command_center_print_report' );
        $metrics = ALGQ_Command_Center_Data_Provider::metrics();
        $health = ALGQ_Command_Center_Health_Monitor::checks();
        ALGQ_Command_Center_Audit_Provider::record_command( 'print_report' );

        nocache_headers();
        header( 'Content-Type: text/html; charset=utf-8' );
        ?>
        <!doctype html><html <?php language_attributes(); ?>><head>
        <meta charset="<?php bloginfo( 'charset' ); ?>"><meta name="viewport" content="width=device-width,initial-scale=1">
        <title><?php echo esc_html__( 'Algonquian Admin Command Center Report', 'algq-command-center' ); ?></title>
        <style>body{font-family:Arial,sans-serif;margin:32px;color:#17202a}h1,h2{color:#0b1f33}table{width:100%;border-collapse:collapse;margin:18px 0}th,td{border:1px solid #d9e0e6;padding:9px;text-align:left}th{background:#f5f7f9}.operational{color:#146c43}.warning{color:#8a6500}.failed{color:#b42318}.optional{color:#536270}@media print{button{display:none}}</style>
        </head><body>
        <button type="button" onclick="window.print()"><?php echo esc_html__( 'Print or Save as PDF', 'algq-command-center' ); ?></button>
        <h1><?php echo esc_html__( 'Algonquian Admin Command Center', 'algq-command-center' ); ?></h1>
        <p><?php echo esc_html( sprintf( __( 'Version %1$s • Generated %2$s UTC', 'algq-command-center' ), ALGQ_COMMAND_CENTER_VERSION, gmdate( 'Y-m-d H:i:s' ) ) ); ?></p>
        <h2><?php echo esc_html__( 'Executive KPIs', 'algq-command-center' ); ?></h2><table><thead><tr><th>Metric</th><th>Value</th></tr></thead><tbody>
        <?php foreach ( $metrics as $key => $value ) : ?><tr><td><?php echo esc_html( ucwords( str_replace( '_', ' ', (string) $key ) ) ); ?></td><td><?php echo esc_html( is_array( $value ) ? (string) wp_json_encode( $value ) : (string) $value ); ?></td></tr><?php endforeach; ?>
        </tbody></table><h2><?php echo esc_html__( 'Platform Health', 'algq-command-center' ); ?></h2><table><thead><tr><th>Component</th><th>Status</th><th>Details</th></tr></thead><tbody>
        <?php foreach ( $health as $check ) : $status = sanitize_key( (string) ( $check['status'] ?? 'warning' ) ); ?><tr><td><?php echo esc_html( (string) ( $check['label'] ?? '' ) ); ?></td><td class="<?php echo esc_attr( $status ); ?>"><?php echo esc_html( ucfirst( $status ) ); ?></td><td><?php echo esc_html( (string) ( $check['message'] ?? '' ) ); ?></td></tr><?php endforeach; ?>
        </tbody></table></body></html><?php
        exit;
    }

    private function authorize( string $nonce_action ): void {
        if ( ! ALGQ_Command_Center_Security::can_export() ) {
            wp_die( esc_html__( 'You are not authorized to export Command Center reports.', 'algq-command-center' ), '', array( 'response' => 403 ) );
        }
        check_admin_referer( $nonce_action );
    }

    private static function csv_cell( mixed $value ): string {
        $value = wp_strip_all_tags( (string) $value );
        if ( preg_match( '/^[=+\-@\t\r]/', $value ) ) {
            $value = "'" . $value;
        }
        return $value;
    }
}
