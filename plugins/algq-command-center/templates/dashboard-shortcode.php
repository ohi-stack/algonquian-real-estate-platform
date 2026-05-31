<?php
/**
 * Public/internal dashboard shortcode template.
 *
 * @package Algonquian_Command_Center
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="algq-command-center algq-command-center--public" data-algq-dashboard>
	<header class="algq-hero">
		<div>
			<p class="algq-eyebrow"><?php echo esc_html__( 'Algonquian Real Estate', 'algq-command-center' ); ?></p>
			<h1><?php echo esc_html__( 'Executive Command Center', 'algq-command-center' ); ?></h1>
			<p><?php echo esc_html__( 'Central operating dashboard for acquisitions, funding, buyers, documents, automation, revenue, and platform health.', 'algq-command-center' ); ?></p>
		</div>
		<div class="algq-hero-actions">
			<button type="button" class="algq-btn algq-btn--ghost" data-algq-theme-toggle><?php echo esc_html__( 'Toggle Dark Mode', 'algq-command-center' ); ?></button>
			<button type="button" class="algq-btn" data-algq-export="csv"><?php echo esc_html__( 'Export CSV', 'algq-command-center' ); ?></button>
			<button type="button" class="algq-btn algq-btn--gold" data-algq-export="pdf"><?php echo esc_html__( 'PDF Report', 'algq-command-center' ); ?></button>
		</div>
	</header>

	<?php ALGQ_Command_Center_Widgets::render_kpi_cards(); ?>

	<section class="algq-dashboard-grid">
		<?php ALGQ_Command_Center_Widgets::render_pipeline(); ?>
		<?php ALGQ_Command_Center_Widgets::render_activity_feed(); ?>
		<div class="algq-panel">
			<h3><?php echo esc_html__( 'System Health', 'algq-command-center' ); ?></h3>
			<div class="algq-health-list">
				<div><span><?php echo esc_html__( 'WordPress', 'algq-command-center' ); ?></span><strong><?php echo esc_html( get_bloginfo( 'version' ) ); ?></strong></div>
				<div><span><?php echo esc_html__( 'PHP', 'algq-command-center' ); ?></span><strong><?php echo esc_html( PHP_VERSION ); ?></strong></div>
				<div><span><?php echo esc_html__( 'Command Center', 'algq-command-center' ); ?></span><strong><?php echo esc_html( ALGQ_COMMAND_CENTER_VERSION ); ?></strong></div>
				<div><span><?php echo esc_html__( 'WooCommerce', 'algq-command-center' ); ?></span><strong><?php echo esc_html( class_exists( 'WooCommerce' ) ? 'Active' : 'Not Connected' ); ?></strong></div>
			</div>
		</div>
		<div class="algq-panel">
			<h3><?php echo esc_html__( 'Revenue Snapshot', 'algq-command-center' ); ?></h3>
			<p><?php echo esc_html__( 'WooCommerce and Stripe revenue widgets will populate when those integrations are active. Missing integrations degrade gracefully without fatal errors.', 'algq-command-center' ); ?></p>
			<div class="algq-mini-grid">
				<div><span><?php echo esc_html__( 'Orders', 'algq-command-center' ); ?></span><strong>0</strong></div>
				<div><span><?php echo esc_html__( 'MRR', 'algq-command-center' ); ?></span><strong>$0</strong></div>
			</div>
		</div>
	</section>
</div>
