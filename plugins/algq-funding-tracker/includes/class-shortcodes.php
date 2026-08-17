<?php
/**
 * Public and authenticated dashboard shortcodes.
 *
 * @package Algonquian_Funding_Tracker
 */

defined( 'ABSPATH' ) || exit;

final class ALGQ_Funding_Tracker_Shortcodes {
	private $repository;

	public function __construct( ALGQ_Funding_Tracker_Repository $repository ) {
		$this->repository = $repository;
	}

	public function register() {
		add_shortcode( 'algq_funding_tracker', array( $this, 'render_overview' ) );
		add_shortcode( 'algq_funding_dashboard', array( $this, 'render_dashboard' ) );
		add_shortcode( 'algq_capital_sources', array( $this, 'render_sources' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'register_assets' ) );
	}

	public function register_assets() {
		wp_register_style( 'algq-funding-tracker-public', ALGQ_FUNDING_TRACKER_URL . 'assets/css/admin.css', array(), ALGQ_FUNDING_TRACKER_VERSION );
	}

	public function render_overview() {
		wp_enqueue_style( 'algq-funding-tracker-public' );
		return '<section class="algq-public-card"><p class="algq-eyebrow">Algonquian Real Estate Platform</p><h2>' . esc_html__( 'Algonquian Funding Tracker', 'algq-funding-tracker' ) . '</h2><p>' . esc_html__( 'A controlled system for documenting capital relationships, deal-level funding requests, commitments, financing terms, and funding progress.', 'algq-funding-tracker' ) . '</p><p><strong>' . esc_html__( 'Administrative tracking only:', 'algq-funding-tracker' ) . '</strong> ' . esc_html__( 'The module does not transfer funds or replace executed loan, investment, accounting, or legal records.', 'algq-funding-tracker' ) . '</p></section>';
	}

	public function render_dashboard() {
		if ( ! is_user_logged_in() || ! current_user_can( 'view_algq_funding' ) ) {
			return '<p>' . esc_html__( 'Authorized access is required to view the funding dashboard.', 'algq-funding-tracker' ) . '</p>';
		}

		wp_enqueue_style( 'algq-funding-tracker-public' );
		$summary = $this->repository->get_summary();
		$rows    = $this->repository->get_commitments( 25 );

		ob_start();
		?>
		<section class="algq-public-card">
			<h2><?php esc_html_e( 'Funding Dashboard', 'algq-funding-tracker' ); ?></h2>
			<div class="algq-kpi-grid">
				<div class="algq-kpi"><span><?php esc_html_e( 'Sources', 'algq-funding-tracker' ); ?></span><strong><?php echo esc_html( $summary['source_count'] ); ?></strong></div>
				<div class="algq-kpi"><span><?php esc_html_e( 'Requested', 'algq-funding-tracker' ); ?></span><strong><?php echo esc_html( $this->money( $summary['requested_total'] ) ); ?></strong></div>
				<div class="algq-kpi"><span><?php esc_html_e( 'Committed', 'algq-funding-tracker' ); ?></span><strong><?php echo esc_html( $this->money( $summary['committed_total'] ) ); ?></strong></div>
				<div class="algq-kpi"><span><?php esc_html_e( 'Funded', 'algq-funding-tracker' ); ?></span><strong><?php echo esc_html( $this->money( $summary['funded_total'] ) ); ?></strong></div>
			</div>
			<?php if ( empty( $rows ) ) : ?>
				<p><?php esc_html_e( 'No funding records are available.', 'algq-funding-tracker' ); ?></p>
			<?php else : ?>
				<div class="algq-table-wrap"><table><thead><tr><th><?php esc_html_e( 'Deal', 'algq-funding-tracker' ); ?></th><th><?php esc_html_e( 'Source', 'algq-funding-tracker' ); ?></th><th><?php esc_html_e( 'Status', 'algq-funding-tracker' ); ?></th><th><?php esc_html_e( 'Requested', 'algq-funding-tracker' ); ?></th><th><?php esc_html_e( 'Funded', 'algq-funding-tracker' ); ?></th></tr></thead><tbody><?php foreach ( $rows as $row ) : ?><tr><td><?php echo esc_html( $row['deal_id'] ?: '—' ); ?></td><td><?php echo esc_html( $row['source_name'] ); ?></td><td><?php echo esc_html( ucwords( str_replace( '_', ' ', $row['status'] ) ) ); ?></td><td><?php echo esc_html( $this->money( $row['requested_amount'] ) ); ?></td><td><?php echo esc_html( $this->money( $row['funded_amount'] ) ); ?></td></tr><?php endforeach; ?></tbody></table></div>
			<?php endif; ?>
		</section>
		<?php
		return ob_get_clean();
	}

	public function render_sources() {
		if ( ! is_user_logged_in() || ! current_user_can( 'view_algq_funding' ) ) {
			return '<p>' . esc_html__( 'Authorized access is required.', 'algq-funding-tracker' ) . '</p>';
		}
		wp_enqueue_style( 'algq-funding-tracker-public' );
		$sources = $this->repository->get_sources( 100 );
		ob_start();
		echo '<section class="algq-public-card"><h2>' . esc_html__( 'Capital Sources', 'algq-funding-tracker' ) . '</h2>';
		if ( empty( $sources ) ) {
			echo '<p>' . esc_html__( 'No capital sources are available.', 'algq-funding-tracker' ) . '</p>';
		} else {
			echo '<ul class="algq-source-list">';
			foreach ( $sources as $source ) {
				echo '<li><strong>' . esc_html( $source['name'] ) . '</strong><span>' . esc_html( ucwords( str_replace( '_', ' ', $source['source_type'] ) ) ) . '</span></li>';
			}
			echo '</ul>';
		}
		echo '</section>';
		return ob_get_clean();
	}

	private function money( $amount ) {
		return '$' . number_format_i18n( (float) $amount, 2 );
	}
}
