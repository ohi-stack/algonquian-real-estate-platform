<?php
/**
 * Main plugin loader.
 *
 * @package Algonquian_Command_Center
 */

defined( 'ABSPATH' ) || exit;

final class ALGQ_Command_Center {
    public function run(): void {
        ALGQ_Command_Center_Activator::maybe_upgrade();
        ( new ALGQ_Command_Center_Assets() )->register();
        ( new ALGQ_Command_Center_Shortcodes() )->register();
        ( new ALGQ_Command_Center_Admin() )->register();
        ( new ALGQ_Command_Center_Report_Controller() )->register();
        ( new ALGQ_Command_Center_Command_Controller() )->register();

        add_action(
            'admin_notices',
            static function (): void {
                if ( version_compare( get_bloginfo( 'version' ), ALGQ_COMMAND_CENTER_MIN_WP, '<' ) && current_user_can( 'manage_options' ) ) {
                    echo '<div class="notice notice-warning"><p>' . esc_html__( 'Algonquian Admin Command Center is designed for WordPress 6.8 or newer.', 'algq-command-center' ) . '</p></div>';
                }
            }
        );
    }
}
