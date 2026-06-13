<?php
/**
 * Plugin overview template.
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
			<p class="algq-eyebrow"><?php echo esc_html__( 'Algonquian Real Estate', 'algq-command-center' ); ?></p>
			<h1><?php echo esc_html__( 'Admin Command Center Overview', 'algq-command-center' ); ?></h1>
			<p><?php echo esc_html__( 'The Command Center is the executive dashboard for acquisitions, funding, buyers, documents, automation, reporting, and plugin health.', 'algq-command-center' ); ?></p>
		</div>
		<div class="algq-hero-actions">
			<a class="algq-btn algq-btn--gold" href="<?php echo esc_url( home_url( '/dashboard' ) ); ?>"><?php echo esc_html__( 'Open Dashboard', 'algq-command-center' ); ?></a>
		</div>
	</header>

	<section class="algq-dashboard-grid">
		<div class="algq-panel"><h3><?php echo esc_html__( 'Core Functions', 'algq-command-center' ); ?></h3><p><?php echo esc_html__( 'Executive KPI cards, pipeline reporting, funding status, buyer activity, document activity, plugin health, CSV/PDF reporting, and operational governance.', 'algq-command-center' ); ?></p></div>
		<div class="algq-panel"><h3><?php echo esc_html__( 'Production Role', 'algq-command-center' ); ?></h3><p><?php echo esc_html__( 'This plugin serves as the command layer above the ARE acquisition, capital, automation, document, marketplace, and commerce modules.', 'algq-command-center' ); ?></p></div>
	</section>
</div>
