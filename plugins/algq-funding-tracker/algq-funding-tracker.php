<?php
/**
 * Plugin Name: Algonquian Funding Tracker
 * Plugin URI: https://algonquianrealestate.com/technology/plugins/funding-tracker/
 * Description: Tracks capital sources, lender and investor relationships, deal-level funding requests, commitments, funding progress, and activity for Algonquian Real Estate.
 * Version: 1.0.0
 * Author: Onegodian | Algonquian Real Estate
 * Author URI: https://algonquianrealestate.com
 * Text Domain: algq-funding-tracker
 * Domain Path: /languages
 * Requires at least: 6.0
 * Requires PHP: 7.4
 * License: Proprietary
 *
 * @package Algonquian_Funding_Tracker
 */

defined( 'ABSPATH' ) || exit;

define( 'ALGQ_FUNDING_TRACKER_VERSION', '1.0.0' );
define( 'ALGQ_FUNDING_TRACKER_FILE', __FILE__ );
define( 'ALGQ_FUNDING_TRACKER_DIR', plugin_dir_path( __FILE__ ) );
define( 'ALGQ_FUNDING_TRACKER_URL', plugin_dir_url( __FILE__ ) );

require_once ALGQ_FUNDING_TRACKER_DIR . 'includes/class-activator.php';
require_once ALGQ_FUNDING_TRACKER_DIR . 'includes/class-repository.php';
require_once ALGQ_FUNDING_TRACKER_DIR . 'includes/class-admin.php';
require_once ALGQ_FUNDING_TRACKER_DIR . 'includes/class-shortcodes.php';
require_once ALGQ_FUNDING_TRACKER_DIR . 'includes/class-rest.php';

register_activation_hook( ALGQ_FUNDING_TRACKER_FILE, array( 'ALGQ_Funding_Tracker_Activator', 'activate' ) );

/**
 * Boot the plugin after WordPress has loaded active plugins.
 */
function algq_funding_tracker_boot() {
	ALGQ_Funding_Tracker_Activator::maybe_upgrade();

	load_plugin_textdomain(
		'algq-funding-tracker',
		false,
		dirname( plugin_basename( ALGQ_FUNDING_TRACKER_FILE ) ) . '/languages'
	);

	$repository = new ALGQ_Funding_Tracker_Repository();

	( new ALGQ_Funding_Tracker_Admin( $repository ) )->register();
	( new ALGQ_Funding_Tracker_Shortcodes( $repository ) )->register();
	( new ALGQ_Funding_Tracker_REST( $repository ) )->register();

	/*
	 * Publish read-only capital intelligence to the Admin Command Center.
	 * Funding Tracker remains authoritative for capital-source and funding records;
	 * Command Center only consumes the normalized summary through filters.
	 */
	add_filter(
		'algq_command_center_funding_summary',
		static function ( $fallback ) use ( $repository ) {
			$summary = $repository->get_summary();
			if ( ! is_array( $summary ) ) {
				return $fallback;
			}

			return array(
				'committed' => max( 0, (float) ( $summary['committed_total'] ?? 0 ) ),
				'needed'    => max( 0, (float) ( $summary['requested_total'] ?? 0 ) ),
			);
		}
	);

	add_filter(
		'algq_command_center_funding_track',
		static function ( $fallback ) use ( $repository ) {
			$summary = $repository->get_summary();
			if ( ! is_array( $summary ) ) {
				return $fallback;
			}

			$requested = max( 0, (float) ( $summary['requested_total'] ?? 0 ) );
			$committed = max( 0, (float) ( $summary['committed_total'] ?? 0 ) );
			$funded    = max( 0, (float) ( $summary['funded_total'] ?? 0 ) );
			$records   = array();

			foreach ( (array) $repository->get_commitments( 5 ) as $record ) {
				$records[] = array(
					'id'               => absint( $record['id'] ?? 0 ),
					'deal_id'          => absint( $record['deal_id'] ?? 0 ),
					'source_name'      => sanitize_text_field( (string) ( $record['source_name'] ?? '' ) ),
					'status'           => sanitize_key( (string) ( $record['status'] ?? '' ) ),
					'requested_amount' => max( 0, (float) ( $record['requested_amount'] ?? 0 ) ),
					'committed_amount' => max( 0, (float) ( $record['committed_amount'] ?? 0 ) ),
					'funded_amount'    => max( 0, (float) ( $record['funded_amount'] ?? 0 ) ),
					'updated_at'       => sanitize_text_field( (string) ( $record['updated_at'] ?? '' ) ),
				);
			}

			return array(
				'connected'         => true,
				'requested'         => $requested,
				'committed'         => $committed,
				'funded'            => $funded,
				'gap'               => max( 0, $requested - $committed ),
				'commitment_percent'=> $requested > 0 ? min( 100, (int) round( ( $committed / $requested ) * 100 ) ) : 0,
				'funded_percent'    => $requested > 0 ? min( 100, (int) round( ( $funded / $requested ) * 100 ) ) : 0,
				'source_count'      => absint( $summary['source_count'] ?? 0 ),
				'record_count'      => absint( $summary['record_count'] ?? 0 ),
				'recent_records'    => $records,
			);
		}
	);

	add_action( 'admin_notices', 'algq_funding_tracker_dependency_notice' );
}
add_action( 'plugins_loaded', 'algq_funding_tracker_boot' );

/**
 * Display a non-blocking notice when the shared platform plugin is unavailable.
 */
function algq_funding_tracker_dependency_notice() {
	if ( defined( 'ALGQ_PLATFORM_VERSION' ) || function_exists( 'algq_platform' ) ) {
		return;
	}

	if ( ! current_user_can( 'activate_plugins' ) ) {
		return;
	}

	printf(
		'<div class="notice notice-warning"><p>%s</p></div>',
		esc_html__( 'Algonquian Funding Tracker is operating in standalone compatibility mode. Activate the Algonquian Real Estate Platform Plugin to enable centralized navigation, audit, mail, and health services.', 'algq-funding-tracker' )
	);
}
