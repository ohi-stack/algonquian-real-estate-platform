<?php
/**
 * Dashboard workspace for Algonquian Deal Intake.
 *
 * @package Algonquian_Deal_Intake
 */
defined( 'ABSPATH' ) || exit;

trait ALGQ_Deal_Intake_Admin_Dashboard {
	public static function dashboard(): void {
		if ( ! current_user_can( ALGQ_Deal_Intake_Security::CAP_REVIEW ) ) {
			wp_die( esc_html__( 'You do not have permission to review intake submissions.', 'algq-deal-intake' ) );
		}

		global $wpdb;
		$table = ALGQ_Deal_Intake_Database::table( 'submissions' );

		$allowed_periods = array( 7, 30, 90 );
		$period = isset( $_GET['period'] ) ? absint( $_GET['period'] ) : 7; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( ! in_array( $period, $allowed_periods, true ) ) {
			$period = 7;
		}

		$now = current_time( 'timestamp', true );
		$current_start = gmdate( 'Y-m-d H:i:s', $now - ( DAY_IN_SECONDS * $period ) );
		$previous_start = gmdate( 'Y-m-d H:i:s', $now - ( DAY_IN_SECONDS * $period * 2 ) );
		$previous_end = $current_start;

		$total_submissions = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table} WHERE deleted_at IS NULL" ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$current_submissions = self::dashboard_count_between( $table, $current_start, null );
		$previous_submissions = self::dashboard_count_between( $table, $previous_start, $previous_end );
		$current_review = self::dashboard_count_between( $table, $current_start, null, "status = 'pending_review'" );
		$previous_review = self::dashboard_count_between( $table, $previous_start, $previous_end, "status = 'pending_review'" );
		$current_qualified = self::dashboard_count_between( $table, $current_start, null, 'lead_score >= 70' );
		$previous_qualified = self::dashboard_count_between( $table, $previous_start, $previous_end, 'lead_score >= 70' );
		$current_created = self::dashboard_count_between( $table, $current_start, null, "status = 'accepted'" );
		$previous_created = self::dashboard_count_between( $table, $previous_start, $previous_end, "status = 'accepted'" );

		$duplicate_review = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table} WHERE deleted_at IS NULL AND duplicate_status = 'review_required'" ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$awaiting_pipeline = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table} WHERE deleted_at IS NULL AND status = 'awaiting_pipeline'" ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$accepted_total = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table} WHERE deleted_at IS NULL AND status = 'accepted'" ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$pending_total = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table} WHERE deleted_at IS NULL AND status = 'pending_review'" ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$qualified_total = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table} WHERE deleted_at IS NULL AND lead_score >= 70" ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

		$recent_ids = $wpdb->get_col( "SELECT id FROM {$table} WHERE deleted_at IS NULL ORDER BY created_at DESC LIMIT 8" ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$lead_sources = self::dashboard_lead_sources( $table, $current_start );
		$activity = self::dashboard_daily_activity( $table );
		$public_page_id = absint( get_option( 'algq_di_submit_property_page_id' ) );
		$public_url = $public_page_id ? get_permalink( $public_page_id ) : '';
		$notification_email = sanitize_email( (string) get_option( 'algq_di_notification_email', '' ) );
		$crm_connected = function_exists( 'algq_pipeline_create_deal' );
		$docs_url = home_url( '/plugin/deal-intake/docs/' );
		?>
		<div class="wrap algq-di-admin algq-di-dashboard-v2">
			<header class="algq-di-dashboard-header">
				<div class="algq-di-dashboard-heading">
					<div class="algq-di-eyebrow"><?php esc_html_e( 'Algonquian Real Estate Platform', 'algq-deal-intake' ); ?></div>
					<div class="algq-di-title-row"><h1><?php esc_html_e( 'Deal Intake Dashboard', 'algq-deal-intake' ); ?></h1><span class="algq-di-version">v<?php echo esc_html( ALGQ_DI_VERSION ); ?></span></div>
					<p><?php esc_html_e( 'Overview of incoming seller and property submissions before controlled Pipeline CRM handoff.', 'algq-deal-intake' ); ?></p>
				</div>
				<div class="algq-di-header-actions">
					<a class="algq-di-btn algq-di-btn-outline" href="<?php echo esc_url( $docs_url ); ?>" target="_blank" rel="noopener noreferrer"><span class="dashicons dashicons-media-document"></span><?php esc_html_e( 'Documentation', 'algq-deal-intake' ); ?></a>
					<?php if ( $public_url ) : ?><a class="algq-di-btn algq-di-btn-gold" href="<?php echo esc_url( $public_url ); ?>" target="_blank" rel="noopener noreferrer"><span class="dashicons dashicons-external"></span><?php esc_html_e( 'View Public Intake', 'algq-deal-intake' ); ?></a><?php endif; ?>
				</div>
			</header>

			<div class="algq-di-toolbar">
				<div><strong><?php esc_html_e( 'Reporting window', 'algq-deal-intake' ); ?></strong><span><?php echo esc_html( sprintf( _n( 'Last %d day', 'Last %d days', $period, 'algq-deal-intake' ), $period ) ); ?></span></div>
				<form method="get" class="algq-di-period-form">
					<input type="hidden" name="page" value="algq-deal-intake">
					<label class="screen-reader-text" for="algq-di-period"><?php esc_html_e( 'Reporting period', 'algq-deal-intake' ); ?></label>
					<select id="algq-di-period" name="period" onchange="this.form.submit()">
						<?php foreach ( $allowed_periods as $days ) : ?><option value="<?php echo esc_attr( (string) $days ); ?>" <?php selected( $period, $days ); ?>><?php echo esc_html( sprintf( _n( '%d day', '%d days', $days, 'algq-deal-intake' ), $days ) ); ?></option><?php endforeach; ?>
					</select>
					<a class="algq-di-refresh" href="<?php echo esc_url( admin_url( 'admin.php?page=algq-deal-intake&period=' . $period ) ); ?>"><span class="dashicons dashicons-update"></span><?php esc_html_e( 'Refresh', 'algq-deal-intake' ); ?></a>
				</form>
			</div>

			<section class="algq-di-kpis algq-di-kpis-five" aria-label="<?php esc_attr_e( 'Deal intake key performance indicators', 'algq-deal-intake' ); ?>">
				<?php self::dashboard_kpi( 'groups', __( 'Total Submissions', 'algq-deal-intake' ), $total_submissions, self::dashboard_trend( $current_submissions, $previous_submissions ), 'blue' ); ?>
				<?php self::dashboard_kpi( 'clipboard', __( 'New Submissions', 'algq-deal-intake' ), $current_submissions, self::dashboard_trend( $current_submissions, $previous_submissions ), 'teal' ); ?>
				<?php self::dashboard_kpi( 'visibility', __( 'In Review', 'algq-deal-intake' ), $current_review, self::dashboard_trend( $current_review, $previous_review ), 'gold' ); ?>
				<?php self::dashboard_kpi( 'yes-alt', __( 'Qualified Leads', 'algq-deal-intake' ), $current_qualified, self::dashboard_trend( $current_qualified, $previous_qualified ), 'green' ); ?>
				<?php self::dashboard_kpi( 'admin-links', __( 'Deals Created', 'algq-deal-intake' ), $current_created, self::dashboard_trend( $current_created, $previous_created ), 'purple' ); ?>
			</section>

			<div class="algq-di-dashboard-grid">
				<div class="algq-di-main-column">
					<section class="algq-di-panel">
						<div class="algq-di-panel-head"><div><h2><?php esc_html_e( 'Submission Pipeline', 'algq-deal-intake' ); ?></h2><p><?php esc_html_e( 'Current intake-state snapshot. Qualification is based on a lead score of 70 or higher.', 'algq-deal-intake' ); ?></p></div></div>
						<div class="algq-di-pipeline">
							<?php self::dashboard_stage( __( 'Pending Review', 'algq-deal-intake' ), $pending_total, 'blue' ); ?>
							<?php self::dashboard_stage( __( 'Duplicate Review', 'algq-deal-intake' ), $duplicate_review, 'teal' ); ?>
							<?php self::dashboard_stage( __( 'Qualified', 'algq-deal-intake' ), $qualified_total, 'gold' ); ?>
							<?php self::dashboard_stage( __( 'Awaiting CRM', 'algq-deal-intake' ), $awaiting_pipeline, 'green' ); ?>
							<?php self::dashboard_stage( __( 'Deals Created', 'algq-deal-intake' ), $accepted_total, 'purple' ); ?>
						</div>
						<div class="algq-di-activity-chart" aria-label="<?php esc_attr_e( 'Submission activity over the last seven days', 'algq-deal-intake' ); ?>">
							<div class="algq-di-chart-legend"><span><i class="algq-di-dot algq-di-dot-blue"></i><?php esc_html_e( 'Submissions', 'algq-deal-intake' ); ?></span></div>
							<div class="algq-di-bars">
								<?php foreach ( $activity as $point ) : ?>
									<div class="algq-di-bar-group"><div class="algq-di-bar-value"><?php echo esc_html( (string) $point['count'] ); ?></div><div class="algq-di-bar-track"><div class="algq-di-bar" style="height:<?php echo esc_attr( (string) $point['height'] ); ?>%"></div></div><span><?php echo esc_html( $point['label'] ); ?></span></div>
								<?php endforeach; ?>
							</div>
						</div>
					</section>

					<section class="algq-di-panel algq-di-recent-panel">
						<div class="algq-di-panel-head"><div><h2><?php esc_html_e( 'Recent Submissions', 'algq-deal-intake' ); ?></h2><p><?php esc_html_e( 'Most recent intake records, with controlled actions retained in Deal Intake.', 'algq-deal-intake' ); ?></p></div><?php if ( current_user_can( ALGQ_Deal_Intake_Security::CAP_EXPORT ) ) : ?><a class="algq-di-text-link" href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=algq_di_export_csv' ), 'algq_di_export_csv', 'algq_di_nonce' ) ); ?>"><?php esc_html_e( 'Export CSV', 'algq-deal-intake' ); ?><span class="dashicons dashicons-download"></span></a><?php endif; ?></div>
						<div class="algq-di-table-wrap"><table class="widefat algq-di-table"><thead><tr><th><?php esc_html_e( 'Reference', 'algq-deal-intake' ); ?></th><th><?php esc_html_e( 'Submitted', 'algq-deal-intake' ); ?></th><th><?php esc_html_e( 'Seller', 'algq-deal-intake' ); ?></th><th><?php esc_html_e( 'Property', 'algq-deal-intake' ); ?></th><th><?php esc_html_e( 'Source', 'algq-deal-intake' ); ?></th><th><?php esc_html_e( 'Score', 'algq-deal-intake' ); ?></th><th><?php esc_html_e( 'Status', 'algq-deal-intake' ); ?></th><th><?php esc_html_e( 'Action', 'algq-deal-intake' ); ?></th></tr></thead><tbody>
						<?php if ( empty( $recent_ids ) ) : ?><tr><td colspan="8" class="algq-di-empty"><?php esc_html_e( 'No submissions have been recorded.', 'algq-deal-intake' ); ?></td></tr><?php else : foreach ( $recent_ids as $submission_id ) : $record = ALGQ_Deal_Intake_Submissions::get( (int) $submission_id ); if ( empty( $record ) ) { continue; } ?>
						<tr><td><code>#ADI-<?php echo esc_html( str_pad( (string) absint( $record['id'] ), 6, '0', STR_PAD_LEFT ) ); ?></code></td><td><?php echo esc_html( mysql2date( 'M j, Y g:i A', (string) $record['created_at'], true ) ); ?></td><td><strong><?php echo esc_html( (string) $record['seller_name'] ); ?></strong><br><small><?php echo esc_html( (string) $record['seller_email'] ); ?></small></td><td><?php echo esc_html( trim( $record['address'] . ', ' . $record['city'] . ', ' . $record['state'] ) ); ?></td><td><?php echo esc_html( self::dashboard_source_label( (string) $record['lead_source'] ) ); ?></td><td><strong><?php echo esc_html( (string) absint( $record['lead_score'] ) ); ?></strong></td><td><span class="algq-di-status algq-di-status-<?php echo esc_attr( sanitize_html_class( (string) $record['status'] ) ); ?>"><?php echo esc_html( ucwords( str_replace( '_', ' ', (string) $record['status'] ) ) ); ?></span><?php if ( 'review_required' === $record['duplicate_status'] ) : ?><br><small class="algq-di-warning"><?php esc_html_e( 'Duplicate review required', 'algq-deal-intake' ); ?></small><?php endif; ?></td><td><?php if ( in_array( $record['status'], array( 'pending_review', 'awaiting_pipeline' ), true ) ) : ?><a class="algq-di-row-action" href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=algq_di_accept_submission&submission_id=' . absint( $record['id'] ) ), 'algq_di_accept_' . absint( $record['id'] ), 'algq_di_nonce' ) ); ?>"><?php esc_html_e( 'Accept', 'algq-deal-intake' ); ?></a><?php else : ?><span class="algq-di-muted">—</span><?php endif; ?></td></tr>
						<?php endforeach; endif; ?>
						</tbody></table></div>
					</section>
				</div>

				<aside class="algq-di-side-column">
					<section class="algq-di-panel">
						<div class="algq-di-panel-head"><div><h2><?php esc_html_e( 'Lead Sources', 'algq-deal-intake' ); ?></h2><p><?php echo esc_html( sprintf( _n( 'Current %d-day window', 'Current %d-day window', $period, 'algq-deal-intake' ), $period ) ); ?></p></div></div>
						<?php if ( empty( $lead_sources ) ) : ?><p class="algq-di-empty"><?php esc_html_e( 'No source data is available for this period.', 'algq-deal-intake' ); ?></p><?php else : ?><div class="algq-di-source-list"><?php foreach ( $lead_sources as $source ) : ?><div class="algq-di-source-row"><div><span class="algq-di-source-dot" style="background:<?php echo esc_attr( $source['color'] ); ?>"></span><span><?php echo esc_html( $source['label'] ); ?></span></div><strong><?php echo esc_html( (string) $source['count'] ); ?> <small>(<?php echo esc_html( (string) $source['percent'] ); ?>%)</small></strong></div><div class="algq-di-source-track"><span style="width:<?php echo esc_attr( (string) $source['percent'] ); ?>%;background:<?php echo esc_attr( $source['color'] ); ?>"></span></div><?php endforeach; ?></div><?php endif; ?>
					</section>

					<section class="algq-di-panel">
						<div class="algq-di-panel-head"><div><h2><?php esc_html_e( 'Quick Actions', 'algq-deal-intake' ); ?></h2></div></div>
						<div class="algq-di-quick-grid">
							<?php if ( $public_url ) : ?><a href="<?php echo esc_url( $public_url ); ?>" target="_blank" rel="noopener noreferrer"><span class="dashicons dashicons-welcome-write-blog"></span><?php esc_html_e( 'Public Intake', 'algq-deal-intake' ); ?></a><?php endif; ?>
							<a href="<?php echo esc_url( admin_url( 'admin.php?page=algq-deal-intake-duplicates' ) ); ?>"><span class="dashicons dashicons-admin-page"></span><?php esc_html_e( 'Duplicates', 'algq-deal-intake' ); ?></a>
							<?php if ( current_user_can( ALGQ_Deal_Intake_Security::CAP_EXPORT ) ) : ?><a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=algq_di_export_csv' ), 'algq_di_export_csv', 'algq_di_nonce' ) ); ?>"><span class="dashicons dashicons-upload"></span><?php esc_html_e( 'Export Leads', 'algq-deal-intake' ); ?></a><?php endif; ?>
							<?php if ( current_user_can( ALGQ_Deal_Intake_Security::CAP_MANAGE ) ) : ?><a href="<?php echo esc_url( admin_url( 'admin.php?page=algq-deal-intake-settings' ) ); ?>"><span class="dashicons dashicons-admin-generic"></span><?php esc_html_e( 'Settings', 'algq-deal-intake' ); ?></a><?php endif; ?>
						</div>
					</section>

					<section class="algq-di-panel">
						<div class="algq-di-panel-head"><div><h2><?php esc_html_e( 'System Status', 'algq-deal-intake' ); ?></h2><p><?php esc_html_e( 'Operational dependencies and intake controls.', 'algq-deal-intake' ); ?></p></div></div>
						<div class="algq-di-system-list">
							<?php self::dashboard_system_status( __( 'Public Intake Page', 'algq-deal-intake' ), (bool) $public_url, $public_url ? __( 'Active', 'algq-deal-intake' ) : __( 'Not configured', 'algq-deal-intake' ) ); ?>
							<?php self::dashboard_system_status( __( 'Email Notifications', 'algq-deal-intake' ), (bool) is_email( $notification_email ), is_email( $notification_email ) ? __( 'Configured', 'algq-deal-intake' ) : __( 'Needs attention', 'algq-deal-intake' ) ); ?>
							<?php self::dashboard_system_status( __( 'Pipeline CRM Integration', 'algq-deal-intake' ), $crm_connected, $crm_connected ? __( 'Connected', 'algq-deal-intake' ) : __( 'Hook only', 'algq-deal-intake' ) ); ?>
							<?php self::dashboard_system_status( __( 'Duplicate Detection', 'algq-deal-intake' ), class_exists( 'ALGQ_Deal_Intake_Duplicate_Detector' ), __( 'Active', 'algq-deal-intake' ) ); ?>
						</div>
					</section>
				</aside>
			</div>
		</div>
		<?php
	}

	private static function dashboard_count_between( string $table, string $start, ?string $end = null, string $extra = '' ): int {
		global $wpdb;
		$sql = "SELECT COUNT(*) FROM {$table} WHERE deleted_at IS NULL AND created_at >= %s"; // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$args = array( $start );
		if ( null !== $end ) {
			$sql .= ' AND created_at < %s';
			$args[] = $end;
		}
		if ( '' !== $extra ) {
			$sql .= ' AND ' . $extra;
		}
		return (int) $wpdb->get_var( $wpdb->prepare( $sql, $args ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
	}

	private static function dashboard_trend( int $current, int $previous ): array {
		if ( 0 === $previous ) {
			return array( 'direction' => $current > 0 ? 'up' : 'flat', 'value' => $current > 0 ? 100 : 0 );
		}
		$value = (int) round( ( ( $current - $previous ) / $previous ) * 100 );
		return array( 'direction' => $value > 0 ? 'up' : ( $value < 0 ? 'down' : 'flat' ), 'value' => abs( $value ) );
	}

	private static function dashboard_kpi( string $icon, string $label, int $value, array $trend, string $accent ): void {
		$direction = (string) ( $trend['direction'] ?? 'flat' );
		$trend_icon = 'up' === $direction ? '↑' : ( 'down' === $direction ? '↓' : '→' );
		?>
		<div class="algq-di-kpi algq-di-kpi-<?php echo esc_attr( $accent ); ?>"><div class="algq-di-kpi-icon"><span class="dashicons dashicons-<?php echo esc_attr( $icon ); ?>"></span></div><div class="algq-di-kpi-body"><strong><?php echo esc_html( $label ); ?></strong><span class="algq-di-kpi-value"><?php echo esc_html( number_format_i18n( $value ) ); ?></span><small class="algq-di-trend algq-di-trend-<?php echo esc_attr( $direction ); ?>"><?php echo esc_html( $trend_icon . ' ' . absint( $trend['value'] ?? 0 ) . '%' ); ?> <?php esc_html_e( 'vs prior period', 'algq-deal-intake' ); ?></small></div></div>
		<?php
	}

	private static function dashboard_stage( string $label, int $value, string $accent ): void {
		?><div class="algq-di-stage algq-di-stage-<?php echo esc_attr( $accent ); ?>"><strong><?php echo esc_html( $label ); ?></strong><span><?php echo esc_html( number_format_i18n( $value ) ); ?></span></div><?php
	}

	private static function dashboard_daily_activity( string $table ): array {
		global $wpdb;
		$today = current_time( 'timestamp', true );
		$points = array();
		$max = 1;
		for ( $i = 6; $i >= 0; $i-- ) {
			$start = gmdate( 'Y-m-d 00:00:00', $today - ( DAY_IN_SECONDS * $i ) );
			$end = gmdate( 'Y-m-d 00:00:00', $today - ( DAY_IN_SECONDS * ( $i - 1 ) ) );
			$count = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$table} WHERE deleted_at IS NULL AND created_at >= %s AND created_at < %s", $start, $end ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$max = max( $max, $count );
			$points[] = array( 'label' => gmdate( 'M j', strtotime( $start . ' UTC' ) ), 'count' => $count );
		}
		foreach ( $points as &$point ) {
			$point['height'] = max( 4, (int) round( ( $point['count'] / $max ) * 100 ) );
		}
		unset( $point );
		return $points;
	}

	private static function dashboard_lead_sources( string $table, string $start ): array {
		global $wpdb;
		$rows = $wpdb->get_results( $wpdb->prepare( "SELECT lead_source, COUNT(*) AS total FROM {$table} WHERE deleted_at IS NULL AND created_at >= %s GROUP BY lead_source ORDER BY total DESC", $start ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$total = 0;
		foreach ( $rows as $row ) {
			$total += absint( $row['total'] );
		}
		if ( 0 === $total ) {
			return array();
		}
		$colors = array( '#2563b8', '#15969a', '#dda51f', '#2e934f', '#7651aa', '#7b8794' );
		$out = array();
		foreach ( array_slice( $rows, 0, 6 ) as $index => $row ) {
			$count = absint( $row['total'] );
			$out[] = array( 'label' => self::dashboard_source_label( (string) $row['lead_source'] ), 'count' => $count, 'percent' => max( 1, (int) round( ( $count / $total ) * 100 ) ), 'color' => $colors[ $index ] ?? '#7b8794' );
		}
		return $out;
	}

	private static function dashboard_source_label( string $source ): string {
		$source = trim( str_replace( array( '-', '_' ), ' ', $source ) );
		return '' === $source ? __( 'Unattributed', 'algq-deal-intake' ) : ucwords( $source );
	}

	private static function dashboard_system_status( string $label, bool $healthy, string $status ): void {
		?><div class="algq-di-system-row"><span><span class="dashicons <?php echo esc_attr( $healthy ? 'dashicons-yes-alt' : 'dashicons-warning' ); ?>"></span><?php echo esc_html( $label ); ?></span><strong class="<?php echo esc_attr( $healthy ? 'algq-di-system-ok' : 'algq-di-system-warn' ); ?>"><?php echo esc_html( $status ); ?></strong></div><?php
	}
}
