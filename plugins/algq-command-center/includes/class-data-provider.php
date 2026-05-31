<?php
/**
 * Data provider for dashboard metrics.
 *
 * @package Algonquian_Command_Center
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class ALGQ_Command_Center_Data_Provider {
	public static function metrics() {
		return array(
			'total_deals'       => self::count_posts( array( 'algq_deal', 'algq_pipeline_deal' ) ),
			'leads_captured'    => self::count_posts( array( 'algq_lead', 'algq_deal' ) ),
			'offers_sent'       => self::count_posts( array( 'algq_offer', 'algq_document' ) ),
			'contracts_pending' => self::count_posts_by_meta( 'algq_deal', 'algq_status', 'under_contract' ),
			'buyers_registered' => self::count_users_by_role( 'algq_buyer' ),
			'funding_status'    => self::funding_summary(),
			'pipeline_value'    => self::pipeline_value(),
			'recent_documents'  => self::count_posts( array( 'algq_document' ) ),
		);
	}

	public static function activity() {
		return array(
			array( 'type' => 'Deal', 'message' => 'Recent acquisitions and pipeline updates appear here when connected plugins are active.', 'time' => 'Live' ),
			array( 'type' => 'Funding', 'message' => 'Funding commitments and lender updates are summarized from the Funding Tracker.', 'time' => 'Live' ),
			array( 'type' => 'Documents', 'message' => 'Generated offers, LOIs, agreements, and PDF activity are surfaced in this feed.', 'time' => 'Live' ),
		);
	}

	public static function pipeline_stages() {
		$stages = array(
			'Lead Captured'  => 'lead_captured',
			'Underwriting'   => 'underwriting',
			'Offer Sent'     => 'offer_sent',
			'Under Contract' => 'under_contract',
			'Buyer Assigned' => 'buyer_assigned',
			'Closed'         => 'closed',
		);

		$output = array();
		foreach ( $stages as $label => $key ) {
			$output[] = array(
				'label' => $label,
				'key'   => $key,
				'count' => self::count_posts_by_meta( 'algq_deal', 'algq_stage', $key ),
			);
		}
		return $output;
	}

	private static function count_posts( $post_types ) {
		$count = 0;
		foreach ( (array) $post_types as $post_type ) {
			$obj = wp_count_posts( $post_type );
			if ( $obj && isset( $obj->publish ) ) {
				$count += (int) $obj->publish;
			}
		}
		return $count;
	}

	private static function count_posts_by_meta( $post_type, $meta_key, $meta_value ) {
		$query = new WP_Query(
			array(
				'post_type'      => sanitize_key( $post_type ),
				'post_status'    => 'publish',
				'posts_per_page' => 1,
				'fields'         => 'ids',
				'no_found_rows'  => false,
				'meta_query'     => array(
					array(
						'key'   => sanitize_key( $meta_key ),
						'value' => sanitize_key( $meta_value ),
					),
				),
			)
		);
		return (int) $query->found_posts;
	}

	private static function count_users_by_role( $role ) {
		$users = count_users();
		return isset( $users['avail_roles'][ $role ] ) ? (int) $users['avail_roles'][ $role ] : 0;
	}

	private static function funding_summary() {
		$committed = (float) get_option( 'algq_command_center_funding_committed', 0 );
		$needed    = (float) get_option( 'algq_command_center_funding_needed', 0 );
		return array(
			'committed' => $committed,
			'needed'    => $needed,
			'percent'   => $needed > 0 ? round( ( $committed / $needed ) * 100 ) : 0,
		);
	}

	private static function pipeline_value() {
		return (float) get_option( 'algq_command_center_pipeline_value', 0 );
	}
}
