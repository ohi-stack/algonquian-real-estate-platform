<?php
/**
 * Activation routines.
 *
 * @package Algonquian_Command_Center
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class ALGQ_Command_Center_Activator {
	public static function activate() {
		update_option( 'algq_command_center_version', ALGQ_COMMAND_CENTER_VERSION );
		update_option( 'algq_command_center_release_status', '1.0.0 Release Candidate' );
		update_option(
			'algq_command_center_enabled_widgets',
			array(
				'total_deals',
				'offers_sent',
				'contracts_pending',
				'funding_status',
				'buyer_activity',
				'pipeline_value',
				'recent_documents',
			)
		);

		if ( class_exists( 'ALGQ_Command_Center_Page_Generator' ) ) {
			ALGQ_Command_Center_Page_Generator::create_required_pages();
		}
	}
}
