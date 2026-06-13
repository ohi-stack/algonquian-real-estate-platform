<?php
/**
 * Plugin library template.
 *
 * @package Algonquian_Command_Center
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$plugins = array(
	array( 'name' => 'Algonquian Deal Intake', 'slug' => 'deal-intake', 'version' => '1.0.2-rc.2', 'description' => 'Lead capture, seller intake, property submissions, and acquisition opportunity routing.' ),
	array( 'name' => 'Algonquian Pipeline CRM', 'slug' => 'pipeline-crm', 'version' => '1.0.0', 'description' => 'Acquisition lifecycle CRM with Kanban stages, activity tracking, and deal movement.' ),
	array( 'name' => 'Algonquian MAO Engine', 'slug' => 'mao-engine', 'version' => '1.0.0', 'description' => 'Maximum allowable offer underwriting calculator and scenario engine.' ),
	array( 'name' => 'Algonquian Offer Generator', 'slug' => 'offer-generator', 'version' => '1.0.0', 'description' => 'Offer and transaction document generator for acquisition workflows.' ),
	array( 'name' => 'Algonquian Buyer Portal', 'slug' => 'buyer-portal', 'version' => '1.0.0', 'description' => 'Secure buyer-facing portal with NDA gating and controlled downloads.' ),
	array( 'name' => 'Algonquian Funding Tracker', 'slug' => 'funding-tracker', 'version' => '1.0.0', 'description' => 'Capital source, lender, JV, and deal-level funding management.' ),
	array( 'name' => 'Algonquian Automation Engine', 'slug' => 'automation-engine', 'version' => '1.0.0', 'description' => 'Trigger-based workflow automation across the ARE platform.' ),
	array( 'name' => 'Algonquian PDF & Signature Engine', 'slug' => 'pdf-engine', 'version' => '1.0.0', 'description' => 'PDF generation, document versioning, and signature status tracking.' ),
	array( 'name' => 'Algonquian Document Library', 'slug' => 'document-library', 'version' => '1.0.0', 'description' => 'Institutional forms, templates, lender packages, and document controls.' ),
	array( 'name' => 'Algonquian Admin Command Center', 'slug' => 'command-center', 'version' => ALGQ_COMMAND_CENTER_VERSION, 'description' => 'Executive KPI dashboard, plugin health, reporting, and operational command layer.' ),
);
?>
<div class="wrap algq-admin-shell">
	<div class="algq-command-center" data-algq-dashboard>
		<header class="algq-hero">
			<div>
				<p class="algq-eyebrow"><?php echo esc_html__( 'Plugin Suite', 'algq-command-center' ); ?></p>
				<h1><?php echo esc_html__( 'ARE Plugin Library', 'algq-command-center' ); ?></h1>
				<p><?php echo esc_html__( 'Production plugin catalog for Algonquian Real Estate modules, documentation, and getting-started workflows.', 'algq-command-center' ); ?></p>
			</div>
			<div class="algq-admin-actions">
				<button type="button" class="algq-btn algq-btn--ghost" data-algq-theme-toggle><?php echo esc_html__( 'Dark Mode', 'algq-command-center' ); ?></button>
				<a class="algq-btn algq-btn--gold" href="<?php echo esc_url( home_url( '/plugins' ) ); ?>"><?php echo esc_html__( 'Open /plugins', 'algq-command-center' ); ?></a>
			</div>
		</header>

		<section class="algq-kpi-grid">
			<?php foreach ( $plugins as $plugin ) : ?>
				<article class="algq-panel algq-plugin-card">
					<p class="algq-eyebrow"><?php echo esc_html__( 'By Onegodian | Algonquian Real Estate', 'algq-command-center' ); ?></p>
					<h3><?php echo esc_html( $plugin['name'] ); ?></h3>
					<p><?php echo esc_html( $plugin['description'] ); ?></p>
					<div class="algq-mini-grid">
						<div><span><?php echo esc_html__( 'Version', 'algq-command-center' ); ?></span><strong><?php echo esc_html( $plugin['version'] ); ?></strong></div>
						<div><span><?php echo esc_html__( 'Status', 'algq-command-center' ); ?></span><strong><?php echo esc_html__( 'Installed / Review', 'algq-command-center' ); ?></strong></div>
					</div>
					<p class="algq-card-actions">
						<a class="algq-btn" href="<?php echo esc_url( home_url( '/plugin/' . $plugin['slug'] ) ); ?>"><?php echo esc_html__( 'View Details', 'algq-command-center' ); ?></a>
						<a class="algq-btn algq-btn--ghost" href="<?php echo esc_url( home_url( '/plugin/' . $plugin['slug'] . '/start' ) ); ?>"><?php echo esc_html__( 'Getting Started', 'algq-command-center' ); ?></a>
						<a class="algq-btn algq-btn--gold" href="<?php echo esc_url( home_url( '/plugin/' . $plugin['slug'] . '/docs' ) ); ?>"><?php echo esc_html__( 'Documentation', 'algq-command-center' ); ?></a>
					</p>
				</article>
			<?php endforeach; ?>
		</section>
	</div>
</div>
