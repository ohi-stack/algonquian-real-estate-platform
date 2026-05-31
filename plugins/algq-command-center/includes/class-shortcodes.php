<?php
/**
 * Shortcodes for Algonquian Command Center.
 *
 * @package Algonquian_Command_Center
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class ALGQ_Command_Center_Shortcodes {
	public function register() {
		add_shortcode( 'algq_command_center', array( $this, 'dashboard' ) );
		add_shortcode( 'algq_command_center_overview', array( $this, 'overview' ) );
		add_shortcode( 'algq_command_center_start', array( $this, 'getting_started' ) );
		add_shortcode( 'algq_command_center_docs', array( $this, 'documentation' ) );
		add_shortcode( 'algq_command_center_kpis', array( $this, 'kpis' ) );
		add_shortcode( 'algq_command_center_pipeline', array( $this, 'pipeline' ) );
		add_shortcode( 'algq_command_center_activity', array( $this, 'activity' ) );
	}

	public function dashboard() {
		if ( ! is_user_logged_in() || ! current_user_can( 'read' ) ) {
			return '<div class="algq-command-center algq-notice">' . esc_html__( 'Please log in to view the Command Center.', 'algq-command-center' ) . '</div>';
		}
		return $this->render_template( 'dashboard-shortcode.php' );
	}

	public function overview() {
		return $this->render_template( 'plugin-overview.php' );
	}

	public function getting_started() {
		return $this->render_template( 'getting-started.php' );
	}

	public function documentation() {
		return $this->render_template( 'documentation.php' );
	}

	public function kpis() {
		ob_start();
		ALGQ_Command_Center_Widgets::render_kpi_cards();
		return ob_get_clean();
	}

	public function pipeline() {
		ob_start();
		ALGQ_Command_Center_Widgets::render_pipeline();
		return ob_get_clean();
	}

	public function activity() {
		ob_start();
		ALGQ_Command_Center_Widgets::render_activity_feed();
		return ob_get_clean();
	}

	private function render_template( $template ) {
		$file = ALGQ_COMMAND_CENTER_DIR . 'templates/' . sanitize_file_name( $template );
		if ( ! file_exists( $file ) ) {
			return '';
		}
		ob_start();
		include $file;
		return ob_get_clean();
	}
}
