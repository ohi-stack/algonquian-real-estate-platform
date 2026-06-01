<?php
/**
 * Admin dashboard template.
 *
 * @package Algonquian_Command_Center
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="wrap algq-admin-shell">
	<div class="algq-command-center" data-algq-dashboard>
		<header class="algq-hero">
			<div>
				<p class="algq-eyebrow"><?php echo esc_html__( 'Algonquian Real Estate', 'algq-command-center' ); ?></p>
				<h1><?php echo esc_html__( 'Admin Command Center', 'algq-command-center' ); ?></h1>
				<p><?php echo esc_html__( 'Executive operations dashboard for acquisitions, funding, buyers, documents, automation, revenue, and platform health.', 'algq-command-center' ); ?></p>
			</div>
			<div class="algq-admin-actions">
				<button type="button" class="algq-btn algq-btn--ghost" data-algq-theme-toggle><?php echo esc_html__( 'Dark Mode', 'algq-command-center' ); ?></button>
				<a class="algq-btn" href="<?php echo esc_url( admin_url( 'admin.php?page=algq-command-center-reports' ) ); ?>"><?php echo esc_html__( 'Reports', 'algq-command-center' ); ?></a>
				<a class="algq-btn algq-btn--gold" href="<?php echo esc_url( admin_url( 'admin.php?page=algq-command-center-settings' ) ); ?>"><?php echo esc_html__( 'Settings', 'algq-command-center' ); ?></a>
			</div>
		</header>

		<nav class="algq-tabs" aria-label="<?php echo esc_attr__( 'Command Center Sections', 'algq-command-center' ); ?>">
			<button class="algq-tab is-active" data-algq-tab="overview" type="button"><?php echo esc_html__( 'Overview', 'algq-command-center' ); ?></button>
			<button class="algq-tab" data-algq-tab="pipeline" type="button"><?php echo esc_html__( 'Pipeline', 'algq-command-center' ); ?></button>
			<button class="algq-tab" data-algq-tab="health" type="button"><?php echo esc_html__( 'Health', 'algq-command-center' ); ?></button>
		</nav>

		<section class="algq-tab-panel is-active" data-algq-panel="overview">
			<?php ALGQ_Command_Center_Widgets::render_kpi_cards(); ?>
			<div class="algq-dashboard-grid">
				<?php ALGQ_Command_Center_Widgets::render_pipeline(); ?>
				<?php ALGQ_Command_Center_Widgets::render_activity_feed(); ?>
			</div>
		</section>

		<section class="algq-tab-panel" data-algq-panel="pipeline">
			<div class="algq-dashboard-grid">
				<?php ALGQ_Command_Center_Widgets::render_pipeline(); ?>
				<div class="algq-panel"><h3><?php echo esc_html__( 'Executive Reporting', 'algq-command-center' ); ?></h3><p><?php echo esc_html__( 'CSV and PDF reporting controls are staged here for hardened export workflows.', 'algq-command-center' ); ?></p><button type="button" class="algq-btn" data-algq-export="csv"><?php echo esc_html__( 'Export CSV', 'algq-command-center' ); ?></button></div>
			</div>
		</section>

		<section class="algq-tab-panel" data-algq-panel="health">
			<div class="algq-dashboard-grid">
				<div class="algq-panel"><h3><?php echo esc_html__( 'System Health', 'algq-command-center' ); ?></h3><div class="algq-health-list"><div><span>WordPress</span><strong><?php echo esc_html( get_bloginfo( 'version' ) ); ?></strong></div><div><span>PHP</span><strong><?php echo esc_html( PHP_VERSION ); ?></strong></div><div><span>WooCommerce</span><strong><?php echo esc_html( class_exists( 'WooCommerce' ) ? 'Active' : 'Not Connected' ); ?></strong></div><div><span>Command Center</span><strong><?php echo esc_html( ALGQ_COMMAND_CENTER_VERSION ); ?></strong></div></div></div>
				<div class="algq-panel"><h3><?php echo esc_html__( 'Operational Notes', 'algq-command-center' ); ?></h3><p><?php echo esc_html__( 'Missing integrations are reported as inactive and do not create fatal dependency chains.', 'algq-command-center' ); ?></p></div>
			</div>
		</section>
	</div>
</div>
