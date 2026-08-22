<?php
/**
 * Conservative uninstall for Algonquian Navigation.
 *
 * @package AlgonquianNavigation
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

delete_option( 'algq_navigation_version' );
