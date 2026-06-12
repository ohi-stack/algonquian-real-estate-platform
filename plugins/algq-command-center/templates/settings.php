<?php
/**
 * Command Center settings screen.
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
				<p class="algq-eyebrow"><?php echo esc_html__( 'Settings', 'algq-command-center' ); ?></p>
				<h1><?php echo esc_html__( 'Command Center Configuration', 'algq-command-center' ); ?></h1>
				<p><?php echo esc_html__( 'Configure executive widgets, pipeline value, funding visibility, dashboard behavior, and reporting defaults.', 'algq-command-center' ); ?></p>
			</div>
			<div class="algq-hero-actions">
				<button type="button" class="algq-btn algq-btn--ghost" data-algq-theme-toggle><?php echo esc_html__( 'Toggle Dark Mode', 'algq-command-center' ); ?></button>
				<a class="algq-btn algq-btn--gold" href="<?php echo esc_url( admin_url( 'admin.php?page=algq-command-center' ) ); ?>"><?php echo esc_html__( 'Back to Dashboard', 'algq-command-center' ); ?></a>
			</div>
		</header>

		<?php if ( isset( $_GET['updated'] ) ) : ?>
			<div class="notice notice-success is-dismissible"><p><?php echo esc_html__( 'Command Center settings saved.', 'algq-command-center' ); ?></p></div>
		<?php endif; ?>

		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="algq-panel">
			<input type="hidden" name="action" value="algq_command_center_save_settings" />
			<?php ALGQ_Command_Center_Security::nonce_field(); ?>

			<div class="algq-settings-grid">
				<section>
					<h3><?php echo esc_html__( 'Executive Metrics', 'algq-command-center' ); ?></h3>
					<div class="algq-field">
						<label for="algq_command_center_pipeline_value"><?php echo esc_html__( 'Pipeline Value', 'algq-command-center' ); ?></label>
						<input id="algq_command_center_pipeline_value" type="number" step="0.01" name="algq_command_center_pipeline_value" value="<?php echo esc_attr( get_option( 'algq_command_center_pipeline_value', 0 ) ); ?>" />
					</div>
					<div class="algq-field">
						<label for="algq_command_center_funding_committed"><?php echo esc_html__( 'Funding Committed', 'algq-command-center' ); ?></label>
						<input id="algq_command_center_funding_committed" type="number" step="0.01" name="algq_command_center_funding_committed" value="<?php echo esc_attr( get_option( 'algq_command_center_funding_committed', 0 ) ); ?>" />
					</div>
					<div class="algq-field">
						<label for="algq_command_center_funding_needed"><?php echo esc_html__( 'Funding Needed', 'algq-command-center' ); ?></label>
						<input id="algq_command_center_funding_needed" type="number" step="0.01" name="algq_command_center_funding_needed" value="<?php echo esc_attr( get_option( 'algq_command_center_funding_needed', 0 ) ); ?>" />
					</div>
				</section>

				<section>
					<h3><?php echo esc_html__( 'Widget Visibility', 'algq-command-center' ); ?></h3>
					<?php foreach ( $registry as $key => $widget ) : ?>
						<label class="algq-check-row">
							<input type="checkbox" name="algq_command_center_enabled_widgets[]" value="<?php echo esc_attr( $key ); ?>" <?php checked( in_array( $key, $enabled, true ) ); ?> />
							<span><?php echo esc_html( $widget['label'] ); ?></span>
							<em><?php echo esc_html( ucfirst( $widget['group'] ) ); ?></em>
						</label>
					<?php endforeach; ?>
				</section>
			</div>

			<p class="submit"><button type="submit" class="algq-btn algq-btn--gold"><?php echo esc_html__( 'Save Settings', 'algq-command-center' ); ?></button></p>
		</form>
	</div>
</div>
