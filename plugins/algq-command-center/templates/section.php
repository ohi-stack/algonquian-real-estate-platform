<?php
/**
 * Generic admin section template.
 *
 * @package Algonquian_Command_Center
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$section_label = isset( $section ) ? ucwords( str_replace( '-', ' ', sanitize_key( $section ) ) ) : 'Dashboard';
?>
<div class="wrap algq-admin-shell">
	<div class="algq-command-center" data-algq-dashboard>
		<header class="algq-hero">
			<div>
				<p class="algq-eyebrow"><?php echo esc_html__( 'Command Center Module', 'algq-command-center' ); ?></p>
				<h1><?php echo esc_html( $section_label ); ?></h1>
				<p><?php echo esc_html__( 'This operational section is connected to the Algonquian Real Estate executive dashboard and will surface data from companion ARE plugins as integrations are hardened.', 'algq-command-center' ); ?></p>
			</div>
			<div class="algq-admin-actions">
				<button type="button" class="algq-btn algq-btn--ghost" data-algq-theme-toggle><?php echo esc_html__( 'Dark Mode', 'algq-command-center' ); ?></button>
				<a class="algq-btn algq-btn--gold" href="<?php echo esc_url( admin_url( 'admin.php?page=algq-command-center' ) ); ?>"><?php echo esc_html__( 'Dashboard', 'algq-command-center' ); ?></a>
			</div>
		</header>

		<section class="algq-dashboard-grid">
			<div class="algq-panel">
				<h3><?php echo esc_html__( 'Operational Status', 'algq-command-center' ); ?></h3>
				<p><?php echo esc_html__( 'This section is available and ready for production data binding.', 'algq-command-center' ); ?></p>
				<div class="algq-health-list">
					<div><span><?php echo esc_html__( 'Section', 'algq-command-center' ); ?></span><strong><?php echo esc_html( $section_label ); ?></strong></div>
					<div><span><?php echo esc_html__( 'Status', 'algq-command-center' ); ?></span><strong><?php echo esc_html__( 'Ready for Integration', 'algq-command-center' ); ?></strong></div>
				</div>
			</div>
			<div class="algq-panel">
				<h3><?php echo esc_html__( 'Next Hardening Step', 'algq-command-center' ); ?></h3>
				<p><?php echo esc_html__( 'Connect this section to algq-core shared services for audit logs, health checks, exports, dependency checks, and role-based visibility.', 'algq-command-center' ); ?></p>
			</div>
		</section>
	</div>
</div>
