<?php
/**
 * WordPress administration interface.
 *
 * @package Algonquian_Funding_Tracker
 */

defined( 'ABSPATH' ) || exit;

final class ALGQ_Funding_Tracker_Admin {
	private $repository;

	public function __construct( ALGQ_Funding_Tracker_Repository $repository ) {
		$this->repository = $repository;
	}

	public function register() {
		add_action( 'admin_menu', array( $this, 'register_menu' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
		add_action( 'admin_post_algq_funding_add_source', array( $this, 'handle_add_source' ) );
		add_action( 'admin_post_algq_funding_add_commitment', array( $this, 'handle_add_commitment' ) );
		add_action( 'admin_post_algq_funding_update_commitment', array( $this, 'handle_update_commitment' ) );
		add_action( 'admin_post_algq_funding_export', array( $this, 'handle_export' ) );
	}

	public function register_menu() {
		$parent_slug   = 'algq-real-estate';
		$parent_exists = isset( $GLOBALS['admin_page_hooks'][ $parent_slug ] );

		if ( $parent_exists ) {
			add_submenu_page(
				$parent_slug,
				__( 'Funding Tracker', 'algq-funding-tracker' ),
				__( 'Funding Tracker', 'algq-funding-tracker' ),
				'view_algq_funding',
				'algq-funding-tracker',
				array( $this, 'render_page' )
			);
			return;
		}

		add_menu_page(
			__( 'Algonquian Funding Tracker', 'algq-funding-tracker' ),
			__( 'Funding Tracker', 'algq-funding-tracker' ),
			'view_algq_funding',
			'algq-funding-tracker',
			array( $this, 'render_page' ),
			'dashicons-chart-area',
			27
		);
	}

	public function enqueue_assets( $hook_suffix ) {
		if ( false === strpos( $hook_suffix, 'algq-funding-tracker' ) ) {
			return;
		}

		wp_enqueue_style(
			'algq-funding-tracker-admin',
			ALGQ_FUNDING_TRACKER_URL . 'assets/css/admin.css',
			array(),
			ALGQ_FUNDING_TRACKER_VERSION
		);
		wp_enqueue_script(
			'algq-funding-tracker-admin',
			ALGQ_FUNDING_TRACKER_URL . 'assets/js/admin.js',
			array(),
			ALGQ_FUNDING_TRACKER_VERSION,
			true
		);
	}

	public function handle_add_source() {
		$this->authorize_write( 'algq_funding_source_nonce', 'algq_funding_add_source' );
		$result = $this->repository->create_source( wp_unslash( $_POST ) );
		$this->redirect_with_result( $result, 'capital-source' );
	}

	public function handle_add_commitment() {
		$this->authorize_write( 'algq_funding_commitment_nonce', 'algq_funding_add_commitment' );
		$result = $this->repository->create_commitment( wp_unslash( $_POST ) );
		$this->redirect_with_result( $result, 'funding-record' );
	}

	public function handle_update_commitment() {
		$this->authorize_write( 'algq_funding_update_nonce', 'algq_funding_update_commitment' );
		$result = $this->repository->update_commitment( absint( $_POST['commitment_id'] ?? 0 ), wp_unslash( $_POST ) );
		$this->redirect_with_result( $result, 'funding-update' );
	}

	public function handle_export() {
		if ( ! current_user_can( 'export_algq_funding' ) ) {
			wp_die( esc_html__( 'You are not authorized to export funding records.', 'algq-funding-tracker' ), '', array( 'response' => 403 ) );
		}
		check_admin_referer( 'algq_funding_export' );
		$rows = $this->repository->get_commitments( 500 );
		nocache_headers();
		header( 'Content-Type: text/csv; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename=algq-funding-export-' . gmdate( 'Y-m-d' ) . '.csv' );
		$output = fopen( 'php://output', 'w' );
		fputcsv( $output, array( 'Record ID', 'Deal ID', 'Capital Source', 'Organization', 'Type', 'Status', 'Requested', 'Committed', 'Funded', 'Interest Rate', 'Term Months', 'Commitment Date', 'Funded Date', 'Updated At' ) );
		foreach ( $rows as $row ) {
			fputcsv( $output, array( $row['id'], $row['deal_id'], $row['source_name'], $row['source_organization'], $row['funding_type'], $row['status'], $row['requested_amount'], $row['committed_amount'], $row['funded_amount'], $row['interest_rate'], $row['term_months'], $row['commitment_date'], $row['funded_date'], $row['updated_at'] ) );
		}
		fclose( $output );
		exit;
	}

	private function authorize_write( $nonce_name, $nonce_action ) {
		if ( ! current_user_can( 'edit_algq_funding' ) ) {
			wp_die( esc_html__( 'You are not authorized to modify funding records.', 'algq-funding-tracker' ), '', array( 'response' => 403 ) );
		}
		check_admin_referer( $nonce_action, $nonce_name );
	}

	private function redirect_with_result( $result, $record_type ) {
		$args = array( 'page' => 'algq-funding-tracker' );
		if ( is_wp_error( $result ) ) {
			$args['algq_error'] = $result->get_error_code();
		} else {
			$args['algq_saved'] = sanitize_key( $record_type );
		}
		wp_safe_redirect( add_query_arg( $args, admin_url( 'admin.php' ) ) );
		exit;
	}

	public function render_page() {
		if ( ! current_user_can( 'view_algq_funding' ) ) {
			wp_die( esc_html__( 'You are not authorized to view funding records.', 'algq-funding-tracker' ), '', array( 'response' => 403 ) );
		}

		$summary     = $this->repository->get_summary();
		$sources     = $this->repository->get_sources();
		$commitments = $this->repository->get_commitments();
		?>
		<div class="wrap algq-funding-wrap">
			<header class="algq-funding-header">
				<div>
					<p class="algq-eyebrow"><?php esc_html_e( 'Algonquian Real Estate Platform', 'algq-funding-tracker' ); ?></p>
					<h1><?php esc_html_e( 'Algonquian Funding Tracker', 'algq-funding-tracker' ); ?></h1>
					<p><?php esc_html_e( 'Track capital relationships, funding requests, commitments, funded amounts, and deal-level financing progress.', 'algq-funding-tracker' ); ?></p>
				</div>
				<div class="algq-header-actions"><span class="algq-version"><?php echo esc_html( ALGQ_FUNDING_TRACKER_VERSION ); ?></span><?php if ( current_user_can( 'export_algq_funding' ) ) : ?><a class="button" href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=algq_funding_export' ), 'algq_funding_export' ) ); ?>"><?php esc_html_e( 'Export CSV', 'algq-funding-tracker' ); ?></a><?php endif; ?></div>
			</header>

			<?php $this->render_notices(); ?>

			<div class="algq-kpi-grid">
				<?php $this->render_kpi( __( 'Capital Sources', 'algq-funding-tracker' ), $summary['source_count'] ); ?>
				<?php $this->render_kpi( __( 'Requested', 'algq-funding-tracker' ), $this->money( $summary['requested_total'] ) ); ?>
				<?php $this->render_kpi( __( 'Committed', 'algq-funding-tracker' ), $this->money( $summary['committed_total'] ) ); ?>
				<?php $this->render_kpi( __( 'Funded', 'algq-funding-tracker' ), $this->money( $summary['funded_total'] ) ); ?>
			</div>

			<div class="algq-funding-grid">
				<section class="algq-panel">
					<h2><?php esc_html_e( 'Add Capital Source', 'algq-funding-tracker' ); ?></h2>
					<?php $this->render_source_form(); ?>
				</section>
				<section class="algq-panel">
					<h2><?php esc_html_e( 'Add Funding Record', 'algq-funding-tracker' ); ?></h2>
					<?php $this->render_commitment_form( $sources ); ?>
				</section>
			</div>

			<section class="algq-panel">
				<h2><?php esc_html_e( 'Funding Pipeline', 'algq-funding-tracker' ); ?></h2>
				<?php $this->render_commitments_table( $commitments ); ?>
			</section>

			<section class="algq-panel">
				<h2><?php esc_html_e( 'Capital Sources', 'algq-funding-tracker' ); ?></h2>
				<?php $this->render_sources_table( $sources ); ?>
			</section>
			<p class="description"><?php esc_html_e( 'This module is an administrative relationship and commitment tracker. It does not transfer funds, originate loans, provide accounting, or replace executed financing documents.', 'algq-funding-tracker' ); ?></p>
		</div>
		<?php
	}

	private function render_notices() {
		if ( isset( $_GET['algq_saved'] ) ) {
			echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Funding Tracker record saved.', 'algq-funding-tracker' ) . '</p></div>';
		}
		if ( isset( $_GET['algq_error'] ) ) {
			echo '<div class="notice notice-error"><p>' . esc_html__( 'The record could not be saved. Review required fields and try again.', 'algq-funding-tracker' ) . '</p></div>';
		}
	}

	private function render_kpi( $label, $value ) {
		printf( '<div class="algq-kpi"><span>%s</span><strong>%s</strong></div>', esc_html( $label ), esc_html( $value ) );
	}

	private function render_source_form() {
		?>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="algq-form-grid">
			<input type="hidden" name="action" value="algq_funding_add_source">
			<?php wp_nonce_field( 'algq_funding_add_source', 'algq_funding_source_nonce' ); ?>
			<label><span><?php esc_html_e( 'Contact Name', 'algq-funding-tracker' ); ?> *</span><input required name="name" type="text"></label>
			<label><span><?php esc_html_e( 'Organization', 'algq-funding-tracker' ); ?></span><input name="organization" type="text"></label>
			<label><span><?php esc_html_e( 'Source Type', 'algq-funding-tracker' ); ?></span><?php $this->select( 'source_type', ALGQ_Funding_Tracker_Repository::source_types() ); ?></label>
			<label><span><?php esc_html_e( 'Status', 'algq-funding-tracker' ); ?></span><?php $this->select( 'status', ALGQ_Funding_Tracker_Repository::source_statuses() ); ?></label>
			<label><span><?php esc_html_e( 'Email', 'algq-funding-tracker' ); ?></span><input name="email" type="email"></label>
			<label><span><?php esc_html_e( 'Phone', 'algq-funding-tracker' ); ?></span><input name="phone" type="text"></label>
			<label><span><?php esc_html_e( 'Minimum Amount', 'algq-funding-tracker' ); ?></span><input min="0" step="0.01" name="minimum_amount" type="number"></label>
			<label><span><?php esc_html_e( 'Maximum Amount', 'algq-funding-tracker' ); ?></span><input min="0" step="0.01" name="maximum_amount" type="number"></label>
			<label class="algq-span-2"><span><?php esc_html_e( 'Notes', 'algq-funding-tracker' ); ?></span><textarea name="notes" rows="3"></textarea></label>
			<div class="algq-span-2"><button class="button button-primary" type="submit"><?php esc_html_e( 'Add Capital Source', 'algq-funding-tracker' ); ?></button></div>
		</form>
		<?php
	}

	private function render_commitment_form( array $sources ) {
		?>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="algq-form-grid">
			<input type="hidden" name="action" value="algq_funding_add_commitment">
			<?php wp_nonce_field( 'algq_funding_add_commitment', 'algq_funding_commitment_nonce' ); ?>
			<label><span><?php esc_html_e( 'Capital Source', 'algq-funding-tracker' ); ?> *</span><select required name="capital_source_id"><option value=""><?php esc_html_e( 'Select source', 'algq-funding-tracker' ); ?></option><?php foreach ( $sources as $source ) : ?><option value="<?php echo esc_attr( $source['id'] ); ?>"><?php echo esc_html( $source['name'] . ( $source['organization'] ? ' — ' . $source['organization'] : '' ) ); ?></option><?php endforeach; ?></select></label>
			<label><span><?php esc_html_e( 'Deal ID', 'algq-funding-tracker' ); ?></span><input min="0" name="deal_id" type="number"></label>
			<label><span><?php esc_html_e( 'Funding Type', 'algq-funding-tracker' ); ?></span><?php $this->select( 'funding_type', ALGQ_Funding_Tracker_Repository::funding_types() ); ?></label>
			<label><span><?php esc_html_e( 'Status', 'algq-funding-tracker' ); ?></span><?php $this->select( 'status', ALGQ_Funding_Tracker_Repository::commitment_statuses(), 'requested' ); ?></label>
			<label><span><?php esc_html_e( 'Requested Amount', 'algq-funding-tracker' ); ?></span><input min="0" step="0.01" name="requested_amount" type="number"></label>
			<label><span><?php esc_html_e( 'Committed Amount', 'algq-funding-tracker' ); ?></span><input min="0" step="0.01" name="committed_amount" type="number"></label>
			<label><span><?php esc_html_e( 'Funded Amount', 'algq-funding-tracker' ); ?></span><input min="0" step="0.01" name="funded_amount" type="number"></label>
			<label><span><?php esc_html_e( 'Interest Rate (%)', 'algq-funding-tracker' ); ?></span><input min="0" step="0.0001" name="interest_rate" type="number"></label>
			<label class="algq-span-2"><span><?php esc_html_e( 'Conditions', 'algq-funding-tracker' ); ?></span><textarea name="conditions" rows="3"></textarea></label>
			<div class="algq-span-2"><button class="button button-primary" type="submit" <?php disabled( empty( $sources ) ); ?>><?php esc_html_e( 'Add Funding Record', 'algq-funding-tracker' ); ?></button></div>
		</form>
		<?php
	}

	private function render_commitments_table( array $rows ) {
		if ( empty( $rows ) ) {
			echo '<p>' . esc_html__( 'No funding records have been created.', 'algq-funding-tracker' ) . '</p>';
			return;
		}
		?>
		<div class="algq-table-wrap"><table class="widefat striped"><thead><tr><th><?php esc_html_e( 'Deal', 'algq-funding-tracker' ); ?></th><th><?php esc_html_e( 'Capital Source', 'algq-funding-tracker' ); ?></th><th><?php esc_html_e( 'Type', 'algq-funding-tracker' ); ?></th><th><?php esc_html_e( 'Status', 'algq-funding-tracker' ); ?></th><th><?php esc_html_e( 'Requested', 'algq-funding-tracker' ); ?></th><th><?php esc_html_e( 'Committed', 'algq-funding-tracker' ); ?></th><th><?php esc_html_e( 'Funded', 'algq-funding-tracker' ); ?></th><th><?php esc_html_e( 'Progress', 'algq-funding-tracker' ); ?></th><th><?php esc_html_e( 'Update', 'algq-funding-tracker' ); ?></th></tr></thead><tbody>
		<?php foreach ( $rows as $row ) : $progress = $this->progress( $row['requested_amount'], $row['funded_amount'] ); ?>
		<tr><td><?php echo esc_html( $row['deal_id'] ?: '—' ); ?></td><td><?php echo esc_html( $row['source_name'] ); ?></td><td><?php echo esc_html( $this->label( $row['funding_type'] ) ); ?></td><td><span class="algq-status"><?php echo esc_html( $this->label( $row['status'] ) ); ?></span></td><td><?php echo esc_html( $this->money( $row['requested_amount'] ) ); ?></td><td><?php echo esc_html( $this->money( $row['committed_amount'] ) ); ?></td><td><?php echo esc_html( $this->money( $row['funded_amount'] ) ); ?></td><td><div class="algq-progress" aria-label="<?php echo esc_attr( $progress . '% funded' ); ?>"><span style="width:<?php echo esc_attr( $progress ); ?>%"></span></div><small><?php echo esc_html( $progress . '%' ); ?></small></td><td><form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="algq-inline-update"><input type="hidden" name="action" value="algq_funding_update_commitment"><input type="hidden" name="commitment_id" value="<?php echo esc_attr( $row['id'] ); ?>"><?php wp_nonce_field( 'algq_funding_update_commitment', 'algq_funding_update_nonce' ); ?><?php $this->select( 'status', ALGQ_Funding_Tracker_Repository::commitment_statuses(), $row['status'] ); ?><input aria-label="<?php esc_attr_e( 'Committed amount', 'algq-funding-tracker' ); ?>" name="committed_amount" type="number" min="0" step="0.01" value="<?php echo esc_attr( $row['committed_amount'] ); ?>"><input aria-label="<?php esc_attr_e( 'Funded amount', 'algq-funding-tracker' ); ?>" name="funded_amount" type="number" min="0" step="0.01" value="<?php echo esc_attr( $row['funded_amount'] ); ?>"><button class="button button-small" type="submit"><?php esc_html_e( 'Save', 'algq-funding-tracker' ); ?></button></form></td></tr>
		<?php endforeach; ?>
		</tbody></table></div>
		<?php
	}

	private function render_sources_table( array $rows ) {
		if ( empty( $rows ) ) {
			echo '<p>' . esc_html__( 'No capital sources have been added.', 'algq-funding-tracker' ) . '</p>';
			return;
		}
		?>
		<div class="algq-table-wrap"><table class="widefat striped"><thead><tr><th><?php esc_html_e( 'Name', 'algq-funding-tracker' ); ?></th><th><?php esc_html_e( 'Organization', 'algq-funding-tracker' ); ?></th><th><?php esc_html_e( 'Type', 'algq-funding-tracker' ); ?></th><th><?php esc_html_e( 'Status', 'algq-funding-tracker' ); ?></th><th><?php esc_html_e( 'Range', 'algq-funding-tracker' ); ?></th></tr></thead><tbody>
		<?php foreach ( $rows as $row ) : ?>
		<tr><td><?php echo esc_html( $row['name'] ); ?></td><td><?php echo esc_html( $row['organization'] ?: '—' ); ?></td><td><?php echo esc_html( $this->label( $row['source_type'] ) ); ?></td><td><span class="algq-status"><?php echo esc_html( $this->label( $row['status'] ) ); ?></span></td><td><?php echo esc_html( $this->money( $row['minimum_amount'] ) . ' – ' . $this->money( $row['maximum_amount'] ) ); ?></td></tr>
		<?php endforeach; ?>
		</tbody></table></div>
		<?php
	}

	private function select( $name, array $options, $selected = '' ) {
		echo '<select name="' . esc_attr( $name ) . '">';
		foreach ( $options as $option ) {
			printf( '<option value="%1$s" %2$s>%3$s</option>', esc_attr( $option ), selected( $selected, $option, false ), esc_html( $this->label( $option ) ) );
		}
		echo '</select>';
	}

	private function money( $amount ) {
		return '$' . number_format_i18n( (float) $amount, 2 );
	}

	private function label( $value ) {
		return ucwords( str_replace( '_', ' ', $value ) );
	}

	private function progress( $requested, $funded ) {
		$requested = (float) $requested;
		if ( $requested <= 0 ) {
			return ( (float) $funded > 0 ) ? 100 : 0;
		}
		return min( 100, max( 0, (int) round( ( (float) $funded / $requested ) * 100 ) ) );
	}
}
