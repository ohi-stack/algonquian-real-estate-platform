<?php
/** Generated administration method group for Algonquian Deal Intake. */
defined( 'ABSPATH' ) || exit;

trait ALGQ_Deal_Intake_Admin_Pages {
	public static function register_hooks(): void {
		add_action( 'admin_menu', array( __CLASS__, 'menu' ) );
		add_action( 'admin_init', array( __CLASS__, 'settings' ) );
		add_action( 'admin_post_algq_di_export_csv', array( __CLASS__, 'export_csv' ) );
		add_action( 'admin_post_algq_di_resolve_duplicate', array( __CLASS__, 'resolve_duplicate' ) );
	}

	public static function menu(): void {
		$parent = self::platform_parent_exists() ? 'algq-platform' : '';
		if ( '' === $parent ) {
			add_menu_page(
				__( 'Algonquian Deal Intake', 'algq-deal-intake' ),
				__( 'Deal Intake', 'algq-deal-intake' ),
				ALGQ_Deal_Intake_Security::CAP_REVIEW,
				'algq-deal-intake',
				array( __CLASS__, 'dashboard' ),
				'dashicons-forms',
				27
			);
		} else {
			add_submenu_page(
				$parent,
				__( 'Algonquian Deal Intake', 'algq-deal-intake' ),
				__( 'Deal Intake', 'algq-deal-intake' ),
				ALGQ_Deal_Intake_Security::CAP_REVIEW,
				'algq-deal-intake',
				array( __CLASS__, 'dashboard' )
			);
		}

		$submenu_parent = '' === $parent ? 'algq-deal-intake' : $parent;

		add_submenu_page(
			$submenu_parent,
			__( 'Duplicate Review', 'algq-deal-intake' ),
			__( 'Duplicate Review', 'algq-deal-intake' ),
			ALGQ_Deal_Intake_Security::CAP_REVIEW,
			'algq-deal-intake-duplicates',
			array( __CLASS__, 'duplicates_page' )
		);

		add_submenu_page(
			$submenu_parent,
			__( 'Deal Intake Settings', 'algq-deal-intake' ),
			__( 'Settings', 'algq-deal-intake' ),
			ALGQ_Deal_Intake_Security::CAP_MANAGE,
			'algq-deal-intake-settings',
			array( __CLASS__, 'settings_page' )
		);
	}

	private static function platform_parent_exists(): bool {
		global $menu;
		if ( ! is_array( $menu ) ) {
			return false;
		}
		foreach ( $menu as $item ) {
			if ( isset( $item[2] ) && 'algq-platform' === $item[2] ) {
				return true;
			}
		}
		return false;
	}

	public static function settings(): void {
		register_setting( 'algq_di_settings', 'algq_di_notification_email', array( 'sanitize_callback' => 'sanitize_email' ) );
		register_setting( 'algq_di_settings', 'algq_di_privacy_version', array( 'sanitize_callback' => 'sanitize_text_field' ) );
		register_setting( 'algq_di_settings', 'algq_di_terms_version', array( 'sanitize_callback' => 'sanitize_text_field' ) );
		register_setting( 'algq_di_settings', 'algq_di_consent_version', array( 'sanitize_callback' => 'sanitize_text_field' ) );
		register_setting( 'algq_di_settings', 'algq_di_rate_limit_per_hour', array( 'sanitize_callback' => 'absint' ) );
		register_setting( 'algq_di_settings', 'algq_di_delete_data_on_uninstall', array( 'sanitize_callback' => 'rest_sanitize_boolean' ) );
	}

	public static function dashboard(): void {
		if ( ! current_user_can( ALGQ_Deal_Intake_Security::CAP_REVIEW ) ) {
			wp_die( esc_html__( 'You do not have permission to review intake submissions.', 'algq-deal-intake' ) );
		}

		global $wpdb;
		$table = ALGQ_Deal_Intake_Database::table( 'submissions' );
		$counts = $wpdb->get_results( "SELECT status, COUNT(*) AS total FROM {$table} WHERE deleted_at IS NULL GROUP BY status", OBJECT_K ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$recent_ids = $wpdb->get_col( "SELECT id FROM {$table} WHERE deleted_at IS NULL ORDER BY created_at DESC LIMIT 25" ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		?>
		<div class="wrap algq-di-admin">
			<div class="algq-di-admin-header">
				<div><h1><?php esc_html_e( 'Algonquian Deal Intake', 'algq-deal-intake' ); ?></h1><p><?php esc_html_e( 'Review seller leads and property submissions before creating canonical Pipeline CRM deals.', 'algq-deal-intake' ); ?></p></div>
				<div><span class="algq-di-version">Version <?php echo esc_html( ALGQ_DI_VERSION ); ?></span></div>
			</div>
			<div class="algq-di-kpis">
				<?php foreach ( array( 'pending_review' => 'Pending Review', 'accepted' => 'Accepted', 'awaiting_pipeline' => 'Awaiting Pipeline', 'archived' => 'Archived' ) as $status => $label ) : ?>
					<div class="algq-di-kpi"><strong><?php echo esc_html( $label ); ?></strong><span><?php echo esc_html( isset( $counts[ $status ] ) ? (string) $counts[ $status ]->total : '0' ); ?></span></div>
				<?php endforeach; ?>
			</div>
			<p class="algq-di-actions"><a class="button button-primary" href="<?php echo esc_url( get_permalink( absint( get_option( 'algq_di_submit_property_page_id' ) ) ) ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Open Public Intake', 'algq-deal-intake' ); ?></a> <a class="button" href="<?php echo esc_url( admin_url( 'admin.php?page=algq-deal-intake-duplicates' ) ); ?>"><?php esc_html_e( 'Review Duplicates', 'algq-deal-intake' ); ?></a><?php if ( current_user_can( ALGQ_Deal_Intake_Security::CAP_EXPORT ) ) : ?> <a class="button" href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=algq_di_export_csv' ), 'algq_di_export_csv', 'algq_di_nonce' ) ); ?>"><?php esc_html_e( 'Export CSV', 'algq-deal-intake' ); ?></a><?php endif; ?></p>
			<table class="widefat striped algq-di-table">
				<thead><tr><th><?php esc_html_e( 'Reference', 'algq-deal-intake' ); ?></th><th><?php esc_html_e( 'Property', 'algq-deal-intake' ); ?></th><th><?php esc_html_e( 'Seller', 'algq-deal-intake' ); ?></th><th><?php esc_html_e( 'Score', 'algq-deal-intake' ); ?></th><th><?php esc_html_e( 'Status', 'algq-deal-intake' ); ?></th><th><?php esc_html_e( 'Actions', 'algq-deal-intake' ); ?></th></tr></thead>
				<tbody>
				<?php if ( empty( $recent_ids ) ) : ?>
					<tr><td colspan="6"><?php esc_html_e( 'No submissions have been recorded.', 'algq-deal-intake' ); ?></td></tr>
				<?php else : ?>
					<?php foreach ( $recent_ids as $submission_id ) : $record = ALGQ_Deal_Intake_Submissions::get( (int) $submission_id ); ?>
					<tr>
						<td><code><?php echo esc_html( substr( (string) $record['uuid'], 0, 8 ) ); ?></code><br><small><?php echo esc_html( (string) $record['created_at'] ); ?></small></td>
						<td><?php echo esc_html( trim( $record['address'] . ', ' . $record['city'] . ', ' . $record['state'] ) ); ?></td>
						<td><?php echo esc_html( (string) $record['seller_name'] ); ?><br><small><?php echo esc_html( (string) $record['seller_email'] ); ?></small></td>
						<td><?php echo esc_html( (string) $record['lead_score'] ); ?></td>
						<td><span class="algq-di-status algq-di-status-<?php echo esc_attr( sanitize_html_class( (string) $record['status'] ) ); ?>"><?php echo esc_html( ucwords( str_replace( '_', ' ', (string) $record['status'] ) ) ); ?></span><?php if ( 'review_required' === $record['duplicate_status'] ) : ?><br><small class="algq-di-warning"><?php esc_html_e( 'Duplicate review required', 'algq-deal-intake' ); ?></small><?php endif; ?></td>
						<td><?php if ( in_array( $record['status'], array( 'pending_review', 'awaiting_pipeline' ), true ) ) : ?><a class="button button-small" href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=algq_di_accept_submission&submission_id=' . absint( $record['id'] ) ), 'algq_di_accept_' . absint( $record['id'] ), 'algq_di_nonce' ) ); ?>"><?php esc_html_e( 'Accept / Send to CRM', 'algq-deal-intake' ); ?></a><?php endif; ?> <a class="button button-small" href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=algq_di_archive_submission&submission_id=' . absint( $record['id'] ) ), 'algq_di_archive_' . absint( $record['id'] ), 'algq_di_nonce' ) ); ?>"><?php esc_html_e( 'Archive', 'algq-deal-intake' ); ?></a></td>
					</tr>
					<?php endforeach; ?>
				<?php endif; ?>
				</tbody>
			</table>
		</div>
		<?php
	}

	public static function duplicates_page(): void {
		if ( ! current_user_can( ALGQ_Deal_Intake_Security::CAP_REVIEW ) ) {
			wp_die( esc_html__( 'You do not have permission to review duplicates.', 'algq-deal-intake' ) );
		}
		global $wpdb;
		$table = ALGQ_Deal_Intake_Database::table( 'duplicates' );
		$rows = $wpdb->get_results( "SELECT * FROM {$table} WHERE status = 'pending' ORDER BY match_score DESC, created_at DESC LIMIT 100", ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		?>
		<div class="wrap algq-di-admin"><h1><?php esc_html_e( 'Deal Intake Duplicate Review', 'algq-deal-intake' ); ?></h1><table class="widefat striped"><thead><tr><th><?php esc_html_e( 'Submission', 'algq-deal-intake' ); ?></th><th><?php esc_html_e( 'Potential Match', 'algq-deal-intake' ); ?></th><th><?php esc_html_e( 'Score', 'algq-deal-intake' ); ?></th><th><?php esc_html_e( 'Matched Fields', 'algq-deal-intake' ); ?></th><th><?php esc_html_e( 'Resolution', 'algq-deal-intake' ); ?></th></tr></thead><tbody>
		<?php if ( empty( $rows ) ) : ?><tr><td colspan="5"><?php esc_html_e( 'No pending duplicate reviews.', 'algq-deal-intake' ); ?></td></tr><?php else : foreach ( $rows as $row ) : ?>
		<tr><td><?php echo esc_html( (string) $row['submission_id'] ); ?></td><td><?php echo esc_html( (string) $row['matched_submission_id'] ); ?></td><td><?php echo esc_html( (string) $row['match_score'] ); ?></td><td><?php echo esc_html( implode( ', ', (array) json_decode( (string) $row['matched_fields'], true ) ) ); ?></td><td><a class="button button-small" href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=algq_di_resolve_duplicate&duplicate_id=' . absint( $row['id'] ) . '&resolution=separate' ), 'algq_di_resolve_duplicate_' . absint( $row['id'] ), 'algq_di_nonce' ) ); ?>"><?php esc_html_e( 'Keep Separate', 'algq-deal-intake' ); ?></a> <a class="button button-small" href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=algq_di_resolve_duplicate&duplicate_id=' . absint( $row['id'] ) . '&resolution=duplicate' ), 'algq_di_resolve_duplicate_' . absint( $row['id'] ), 'algq_di_nonce' ) ); ?>"><?php esc_html_e( 'Mark Duplicate', 'algq-deal-intake' ); ?></a></td></tr>
		<?php endforeach; endif; ?>
		</tbody></table></div>
		<?php
	}

	public static function settings_page(): void {
		if ( ! current_user_can( ALGQ_Deal_Intake_Security::CAP_MANAGE ) ) {
			wp_die( esc_html__( 'You do not have permission to manage Deal Intake settings.', 'algq-deal-intake' ) );
		}
		?>
		<div class="wrap algq-di-admin"><h1><?php esc_html_e( 'Deal Intake Settings', 'algq-deal-intake' ); ?></h1><form method="post" action="options.php"><?php settings_fields( 'algq_di_settings' ); ?><table class="form-table" role="presentation"><tr><th scope="row"><label for="algq_di_notification_email"><?php esc_html_e( 'Notification Email', 'algq-deal-intake' ); ?></label></th><td><input class="regular-text" type="email" id="algq_di_notification_email" name="algq_di_notification_email" value="<?php echo esc_attr( (string) get_option( 'algq_di_notification_email' ) ); ?>"></td></tr><tr><th scope="row"><label for="algq_di_consent_version"><?php esc_html_e( 'Consent Version', 'algq-deal-intake' ); ?></label></th><td><input type="text" id="algq_di_consent_version" name="algq_di_consent_version" value="<?php echo esc_attr( (string) get_option( 'algq_di_consent_version', '1.0' ) ); ?>"></td></tr><tr><th scope="row"><label for="algq_di_privacy_version"><?php esc_html_e( 'Privacy Version', 'algq-deal-intake' ); ?></label></th><td><input type="text" id="algq_di_privacy_version" name="algq_di_privacy_version" value="<?php echo esc_attr( (string) get_option( 'algq_di_privacy_version', '1.0' ) ); ?>"></td></tr><tr><th scope="row"><label for="algq_di_terms_version"><?php esc_html_e( 'Terms Version', 'algq-deal-intake' ); ?></label></th><td><input type="text" id="algq_di_terms_version" name="algq_di_terms_version" value="<?php echo esc_attr( (string) get_option( 'algq_di_terms_version', '1.0' ) ); ?>"></td></tr><tr><th scope="row"><label for="algq_di_rate_limit_per_hour"><?php esc_html_e( 'Public Submissions Per Hour', 'algq-deal-intake' ); ?></label></th><td><input type="number" min="1" max="100" id="algq_di_rate_limit_per_hour" name="algq_di_rate_limit_per_hour" value="<?php echo esc_attr( (string) get_option( 'algq_di_rate_limit_per_hour', 10 ) ); ?>"></td></tr><tr><th scope="row"><?php esc_html_e( 'Uninstall Data Deletion', 'algq-deal-intake' ); ?></th><td><label><input type="checkbox" name="algq_di_delete_data_on_uninstall" value="1" <?php checked( (bool) get_option( 'algq_di_delete_data_on_uninstall', false ) ); ?>> <?php esc_html_e( 'Delete Deal Intake tables and settings only when the plugin is uninstalled. Operational data is preserved by default.', 'algq-deal-intake' ); ?></label></td></tr></table><?php submit_button(); ?></form><h2><?php esc_html_e( 'Shortcodes', 'algq-deal-intake' ); ?></h2><p><code>[algq_deal_intake_form]</code> <code>[deal_intake_form_internal]</code> <code>[deal_quick_capture]</code> <code>[algq_homeowner_options]</code> <code>[algq_seller_portal]</code></p></div>
		<?php
	}
}
