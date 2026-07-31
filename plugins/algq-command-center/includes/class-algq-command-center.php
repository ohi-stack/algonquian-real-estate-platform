<?php
/**
 * Main plugin loader.
 *
 * @package Algonquian_Command_Center
 */

defined( 'ABSPATH' ) || exit;

final class ALGQ_Command_Center {
    public function run(): void {
        ( new ALGQ_Command_Center_Assets() )->register();
        ( new ALGQ_Command_Center_Shortcodes() )->register();
        ( new ALGQ_Command_Center_Admin() )->register();
        ( new ALGQ_Command_Center_Report_Controller() )->register();

        add_filter(
            'algq_command_center_metrics',
            static function ( array $metrics ): array {
                $metrics['system_health'] = ALGQ_Command_Center_Health_Monitor::summary();
                return $metrics;
            }
        );
    }
}
