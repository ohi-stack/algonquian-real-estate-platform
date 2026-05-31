<?php
/**
 * Asset loader.
 *
 * @package Algonquian_Command_Center
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class ALGQ_Command_Center_Assets {
	public function register() {
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_assets' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'public_assets' ) );
	}

	public function admin_assets( $hook ) {
		if ( false === strpos( (string) $hook, 'algq-command-center' ) ) {
			return;
		}
		$this->enqueue();
	}

	public function public_assets() {
		if ( is_singular() ) {
			global $post;
			if ( $post && has_shortcode( $post->post_content, 'algq_command_center' ) ) {
				$this->enqueue();
			}
		}
	}

	private function enqueue() {
		wp_enqueue_style( 'algq-command-center', ALGQ_COMMAND_CENTER_URL . 'assets/css/command-center.css', array(), ALGQ_COMMAND_CENTER_VERSION );
		wp_enqueue_script( 'algq-command-center', ALGQ_COMMAND_CENTER_URL . 'assets/js/command-center.js', array(), ALGQ_COMMAND_CENTER_VERSION, true );
	}
}
