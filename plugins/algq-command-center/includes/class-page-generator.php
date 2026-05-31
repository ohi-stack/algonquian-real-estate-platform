<?php
/**
 * Automatic page generation for Command Center.
 *
 * @package Algonquian_Command_Center
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class ALGQ_Command_Center_Page_Generator {
	public static function pages() {
		return array(
			'algq_command_center_dashboard_page_id' => array(
				'title'     => 'Dashboard',
				'slug'      => 'dashboard',
				'shortcode' => '[algq_command_center]',
			),
			'algq_command_center_overview_page_id' => array(
				'title'     => 'Command Center',
				'slug'      => 'plugin/command-center',
				'shortcode' => '[algq_command_center_overview]',
			),
			'algq_command_center_start_page_id' => array(
				'title'     => 'Command Center Getting Started',
				'slug'      => 'plugin/command-center/start',
				'shortcode' => '[algq_command_center_start]',
			),
			'algq_command_center_docs_page_id' => array(
				'title'     => 'Command Center Documentation',
				'slug'      => 'plugin/command-center/docs',
				'shortcode' => '[algq_command_center_docs]',
			),
		);
	}

	public static function create_required_pages() {
		foreach ( self::pages() as $option_key => $page ) {
			self::create_or_update_page( $option_key, $page );
		}
		flush_rewrite_rules();
	}

	private static function create_or_update_page( $option_key, $page ) {
		$existing_id = absint( get_option( $option_key ) );
		if ( $existing_id && 'trash' !== get_post_status( $existing_id ) ) {
			return $existing_id;
		}

		$existing = get_page_by_path( sanitize_title( $page['slug'] ) );
		if ( $existing ) {
			update_option( $option_key, absint( $existing->ID ) );
			return absint( $existing->ID );
		}

		$page_id = wp_insert_post(
			array(
				'post_title'   => sanitize_text_field( $page['title'] ),
				'post_name'    => sanitize_title( basename( $page['slug'] ) ),
				'post_content' => sanitize_text_field( $page['shortcode'] ),
				'post_status'  => 'publish',
				'post_type'    => 'page',
			)
		);

		if ( ! is_wp_error( $page_id ) ) {
			update_option( $option_key, absint( $page_id ) );
		}

		return $page_id;
	}
}
