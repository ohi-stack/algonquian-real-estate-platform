<?php
/**
 * Getting started template.
 *
 * @package Algonquian_Command_Center
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="algq-command-center" data-algq-dashboard>
	<header class="algq-hero">
		<div>
			<p class="algq-eyebrow"><?php echo esc_html__( 'Getting Started', 'algq-command-center' ); ?></p>
			<h1><?php echo esc_html__( 'Set Up the Command Center', 'algq-command-center' ); ?></h1>
			<p><?php echo esc_html__( 'Connect active plugins, choose default widgets, verify generated pages, and confirm system health before relying on executive reports.', 'algq-command-center' ); ?></p>
		</div>
	</header>

	<section class="algq-dashboard-grid">
		<div class="algq-panel"><h3><?php echo esc_html__( 'Setup Steps', 'algq-command-center' ); ?></h3><ol><li><?php echo esc_html__( 'Activate required ARE plugins.', 'algq-command-center' ); ?></li><li><?php echo esc_html__( 'Confirm /dashboard and plugin pages were generated.', 'algq-command-center' ); ?></li><li><?php echo esc_html__( 'Open Command Center settings and choose widgets.', 'algq-command-center' ); ?></li><li><?php echo esc_html__( 'Review System Health warnings.', 'algq-command-center' ); ?></li><li><?php echo esc_html__( 'Begin production-hardening QA.', 'algq-command-center' ); ?></li></ol></div>
		<div class="algq-panel"><h3><?php echo esc_html__( 'Required Shortcodes', 'algq-command-center' ); ?></h3><p><code>[algq_command_center]</code></p><p><code>[algq_command_center_overview]</code></p><p><code>[algq_command_center_start]</code></p><p><code>[algq_command_center_docs]</code></p></div>
	</section>
</div>
