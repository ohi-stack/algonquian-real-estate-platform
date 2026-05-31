<?php
/**
 * Main plugin loader.
 *
 * @package Algonquian_Command_Center
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class ALGQ_Command_Center {
	public function run() {
		( new ALGQ_Command_Center_Assets() )->register();
		( new ALGQ_Command_Center_Shortcodes() )->register();
		( new ALGQ_Command_Center_Admin() )->register();
	}
}
