<?php
/**
 * Admin dashboard and operations navigation.
 *
 * @package Algonquian_Command_Center
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class ALGQ_Command_Center_Admin {
	public function register() {
		add_action( 'admin_menu', array( $this, 'menu' ) );
		add_action( 'admin_init', array( $this, 'register_settings' ) );
		add_action( 'admin_post_algq_command_center_save_settings', array( $this, 'save_settings' ) );
	}

	public function menu() {
		add_menu_page(
			esc_html__( 'Algonquian Command Center', 'algq-command-center' ),
			esc_html__( 'Command Center', 'algq-command-center' ),
			ALGQ_Command_Center_Security::CAPABILITY,
			'algq-command-center',
			array( $this, 'render_dashboard' ),
			'dashicons-chart-area',
			3
		);

		$items = array(
			'Dashboard'      => 'algq-command-center',
			'Deals'          => 'algq-command-center-deals',
			'Pipeline'       => 'algq-command-center-pipeline',
			'Funding'        => 'algq-command-center-funding',
			'Buyers'         => 'algq-command-center-buyers',
			'Documents'      => 'algq-command-center-documents',
			'Automation'     => 'algq-command-center-automation',
			'Reports'        => 'algq-command-center-reports',
			'Plugins'        => 'algq-command-center-plugins',
			'Settings'       => 'algq-command-center-settings',
			'System Health'  => 'algq-command-center-system-health',
		);

		foreach ( $items as $label => $slug ) {
			add_submenu_page(
				'algq-command-center',
				esc_html( $label ),
				esc_html( $label ),
				ALGQ_Command_Center_Security::CAPABILITY,
				$slug,
				array( $this, 'render_router' )
			);
		}
	}

	public function register_settings() {
		register_setting(
			'algq_command_center_settings',
			'algq_command_center_enabled_widgets',
			array(
				'type'              => 'array',
				'sanitize_callback' => array( $this, 'sanitize_widgets' ),
				'default'           => array_keys( ALGQ_Command_Center_Widgets::registry() ),
			)
		);
		register_setting( 'algq_command_center_settings', 'algq_command_center_pipeline_value', array( 'sanitize_callback' => 'floatval' ) );
		register_setting( 'algq_command_center_settings', 'algq_command_center_funding_committed', array( 'sanitize_callback' => 'floatval' ) );
		register_setting( 'algq_command_center_settings', 'algq_command_center_funding_needed', array( 'sanitize_callback' => 'floatval' ) );
	}

	public function sanitize_widgets( $widgets ) {
		$allowed = array_keys( ALGQ_Command_Center_Widgets::registry() );
		return array_values( array_intersect( array_map( 'sanitize_key', (array) $widgets ), $allowed ) );
	}

	public function save_settings() {
		ALGQ_Command_Center_Security::require_manage_capability();
		check_admin_referer( ALGQ_Command_Center_Security::NONCE_ACTION, ALGQ_Command_Center_Security::NONCE_NAME );

		$widgets = isset( $_POST['algq_command_center_enabled_widgets'] ) ? (array) wp_unslash( $_POST['algq_command_center_enabled_widgets'] ) : array();
		update_option( 'algq_command_center_enabled_widgets', $this->sanitize_widgets( $widgets ) );
		update_option( 'algq_command_center_pipeline_value', isset( $_POST['algq_command_center_pipeline_value'] ) ? floatval( wp_unslash( $_POST['algq_command_center_pipeline_value'] ) ) : 0 );
		update_option( 'algq_command_center_funding_committed', isset( $_POST['algq_command_center_funding_committed'] ) ? floatval( wp_unslash( $_POST['algq_command_center_funding_committed'] ) ) : 0 );
		update_option( 'algq_command_center_funding_needed', isset( $_POST['algq_command_center_funding_needed'] ) ? floatval( wp_unslash( $_POST['algq_command_center_funding_needed'] ) ) : 0 );

		wp_safe_redirect( admin_url( 'admin.php?page=algq-command-center-settings&updated=1' ) );
		exit;
	}

	public function render_dashboard() {
		ALGQ_Command_Center_Security::require_manage_capability();
		include ALGQ_COMMAND_CENTER_DIR . 'templates/admin-dashboard.php';
	}

	public function render_router() {
		ALGQ_Command_Center_Security::require_manage_capability();
		$page = isset( $_GET['page'] ) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : 'algq-command-center';

		switch ( $page ) {
			case 'algq-command-center-settings':
				$this->render_settings();
				break;
			case 'algq-command-center-system-health':
				$this->render_system_health();
				break;
			case 'algq-command-center-plugins':
				$this->render_plugins();
				break;
			default:
				$this->render_section( $page );
		}
	}

	private function render_settings() {
		$registry = ALGQ_Command_Center_Widgets::registry();
		$enabled  = ALGQ_Command_Center_Widgets::enabled_widgets();
		include ALGQ_COMMAND_CENTER_DIR . 'templates/settings.php';
	}

	private function render_system_health() {
		include ALGQ_COMMAND_CENTER_DIR . 'templates/system-health.php';
	}

	private function render_plugins() {
		include ALGQ_COMMAND_CENTER_DIR . 'templates/plugin-library.php';
	}

	private function render_section( $page ) {
		$section = str_replace( 'algq-command-center-', '', $page );
		$section = 'algq-command-center' === $page ? 'dashboard' : $section;
		include ALGQ_COMMAND_CENTER_DIR . 'templates/section.php';
	}
}
