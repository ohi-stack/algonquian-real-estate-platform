<?php
/**
 * System health template.
 *
 * @package Algonquian_Command_Center
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$checks = array(
	array( 'label' => 'WordPress Version', 'value' => get_bloginfo( 'version' ), 'status' => 'pass' ),
	array( 'label' => 'PHP Version', 'value' => PHP_VERSION, 'status' => version_compare( PHP_VERSION, '7.4', '>=' ) ? 'pass' : 'fail' ),
	array( 'label' => 'Command Center', 'value' => ALGQ_COMMAND_CENTER_VERSION, 'status' => 'pass' ),
	array( 'label' => 'WooCommerce', 'value' => class_exists( 'WooCommerce' ) ? 'Active' : 'Not Connected', 'status' => class_exists( 'WooCommerce' ) ? 'pass' : 'warning' ),
	array( 'label' => 'Stripe Integration', 'value' => class_exists( 'WC_Stripe' ) || defined( 'ALGQ_STRIPE_VERSION' ) ? 'Detected' : 'Not Connected', 'status' => class_exists( 'WC_Stripe' ) || defined( 'ALGQ_STRIPE_VERSION' ) ? 'pass' : 'warning' ),
	array( 'label' => 'REST API', 'value' => 'Available through WordPress core', 'status' => 'pass' ),
);
?>
<div class="wrap algq-admin-shell">
	<div class="algq-command-center" data-algq-dashboard>
		<header class="algq-hero">
			<div>
				<p class="algq-eyebrow"><?php echo esc_html__( 'Operational Hardening', 'algq-command-center' ); ?></p>
				<h1><?php echo esc_html__( 'System Health', 'algq-command-center' ); ?></h1>
				<p><?php echo esc_html__( 'Monitor WordPress, PHP, integrations, dependencies, and ARE platform readiness from one executive control panel.', 'algq-command-center' ); ?></p>
			</div>
			<div class="algq-admin-actions">
				<button type="button" class="algq-btn algq-btn--ghost" data-algq-theme-toggle><?php echo esc_html__( 'Dark Mode', 'algq-command-center' ); ?></button>
				<a class="algq-btn algq-btn--gold" href="<?php echo esc_url( admin_url( 'admin.php?page=algq-command-center' ) ); ?>"><?php echo esc_html__( 'Dashboard', 'algq-command-center' ); ?></a>
			</div>
		</header>

		<section class="algq-dashboard-grid">
			<div class="algq-panel">
				<h3><?php echo esc_html__( 'Core Environment', 'algq-command-center' ); ?></h3>
				<div class="algq-health-list">
					<?php foreach ( $checks as $check ) : ?>
						<div>
							<span><?php echo esc_html( $check['label'] ); ?></span>
							<strong><?php echo esc_html( $check['value'] ); ?></strong>
							<em class="algq-status-pill algq-status-<?php echo esc_attr( $check['status'] ); ?>"><?php echo esc_html( strtoupper( $check['status'] ) ); ?></em>
						</div>
					<?php endforeach; ?>
				</div>
			</div>

			<div class="algq-panel">
				<h3><?php echo esc_html__( 'Production Notes', 'algq-command-center' ); ?></h3>
				<p><?php echo esc_html__( 'Inactive companion plugins are shown as warnings, not fatal errors. This preserves production stability while allowing phased integration.', 'algq-command-center' ); ?></p>
				<p><?php echo esc_html__( 'Next hardening target: route all plugin health checks through algq-core shared services.', 'algq-command-center' ); ?></p>
			</div>
		</section>
	</div>
</div>
