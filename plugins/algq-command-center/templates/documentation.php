<?php
/**
 * Documentation template.
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
			<p class="algq-eyebrow"><?php echo esc_html__( 'Documentation', 'algq-command-center' ); ?></p>
			<h1><?php echo esc_html__( 'Command Center Documentation', 'algq-command-center' ); ?></h1>
			<p><?php echo esc_html__( 'Reference guide for widgets, data sources, KPI formulas, role visibility, exports, health checks, and production-hardening requirements.', 'algq-command-center' ); ?></p>
		</div>
	</header>

	<section class="algq-dashboard-grid">
		<div class="algq-panel"><h3><?php echo esc_html__( 'Widget Definitions', 'algq-command-center' ); ?></h3><p><?php echo esc_html__( 'KPI widgets summarize leads, deals, offers, contracts, funding, buyers, pipeline value, documents, revenue, and operational activity.', 'algq-command-center' ); ?></p></div>
		<div class="algq-panel"><h3><?php echo esc_html__( 'Security Requirements', 'algq-command-center' ); ?></h3><p><?php echo esc_html__( 'All admin actions must use capability checks, nonces, sanitized inputs, escaped outputs, and graceful handling of inactive companion plugins.', 'algq-command-center' ); ?></p></div>
		<div class="algq-panel"><h3><?php echo esc_html__( 'Generated Pages', 'algq-command-center' ); ?></h3><p><code>/dashboard</code></p><p><code>/plugin/command-center</code></p><p><code>/plugin/command-center/start</code></p><p><code>/plugin/command-center/docs</code></p></div>
		<div class="algq-panel"><h3><?php echo esc_html__( 'Release Status', 'algq-command-center' ); ?></h3><p><?php echo esc_html__( 'Release Candidate / Production Hardening. Verify all integrations and exports before relying on final production reporting.', 'algq-command-center' ); ?></p></div>
	</section>
</div>
