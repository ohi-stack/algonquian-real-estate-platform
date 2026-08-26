<?php
/** Generated method group for Algonquian Deal Intake. */
defined( 'ABSPATH' ) || exit;

trait ALGQ_Deal_Intake_Submissions_Workflow {
	public static function get( int $submission_id ): array {
		global $wpdb;
		$sql = $wpdb->prepare(
			'SELECT sub.*, s.name AS seller_name, s.email AS seller_email, s.phone AS seller_phone, p.address, p.city, p.state, p.postal_code, p.property_type, p.condition_summary
			FROM ' . ALGQ_Deal_Intake_Database::table( 'submissions' ) . ' sub
			INNER JOIN ' . ALGQ_Deal_Intake_Database::table( 'sellers' ) . ' s ON s.id = sub.seller_id
			INNER JOIN ' . ALGQ_Deal_Intake_Database::table( 'properties' ) . ' p ON p.id = sub.property_id
			WHERE sub.id = %d LIMIT 1',
			$submission_id
		);
		$row = $wpdb->get_row( $sql, ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		return is_array( $row ) ? $row : array();
	}

	public static function handle_accept(): void {
		if ( ! current_user_can( ALGQ_Deal_Intake_Security::CAP_REVIEW ) ) {
			wp_die( esc_html__( 'You do not have permission to accept submissions.', 'algq-deal-intake' ) );
		}
		$submission_id = isset( $_GET['submission_id'] ) ? absint( $_GET['submission_id'] ) : 0;
		if ( ! $submission_id || ! ALGQ_Deal_Intake_Security::verify_nonce( 'algq_di_accept_' . $submission_id ) ) {
			wp_die( esc_html__( 'The acceptance request could not be verified.', 'algq-deal-intake' ) );
		}

		$result = self::accept( $submission_id );
		$status = is_wp_error( $result ) ? 'handoff_pending' : 'accepted';
		wp_safe_redirect( admin_url( 'admin.php?page=algq-deal-intake&status=' . rawurlencode( $status ) ) );
		exit;
	}

	public static function accept( int $submission_id ): int|WP_Error {
		global $wpdb;
		$record = self::get( $submission_id );
		if ( empty( $record ) ) {
			return new WP_Error( 'not_found', __( 'Submission not found.', 'algq-deal-intake' ) );
		}
		if ( ! empty( $record['pipeline_deal_id'] ) ) {
			return (int) $record['pipeline_deal_id'];
		}
		if ( 'review_required' === $record['duplicate_status'] ) {
			return new WP_Error( 'duplicate_review_required', __( 'Resolve the duplicate review before creating a Pipeline CRM deal.', 'algq-deal-intake' ) );
		}

		$property_address = trim(
			implode(
				', ',
				array_filter(
					array(
						sanitize_text_field( (string) $record['address'] ),
						sanitize_text_field( (string) $record['city'] ),
						trim( sanitize_text_field( (string) $record['state'] ) . ' ' . sanitize_text_field( (string) $record['postal_code'] ) ),
					)
				)
			)
		);

		$payload = array(
			'title' => sanitize_text_field( (string) ( $record['address'] ?: $record['uuid'] ) ),
			'property_address' => $property_address,
			'municipality' => sanitize_text_field( (string) $record['city'] ),
			'state' => sanitize_text_field( (string) $record['state'] ),
			'postal_code' => sanitize_text_field( (string) $record['postal_code'] ),
			'primary_contact' => sanitize_text_field( (string) $record['seller_name'] ),
			'primary_contact_name' => sanitize_text_field( (string) $record['seller_name'] ),
			'primary_contact_email' => sanitize_email( (string) $record['seller_email'] ),
			'primary_contact_phone' => sanitize_text_field( (string) $record['seller_phone'] ),
			'intake_submission_id' => $submission_id,
			'intake_uuid' => sanitize_text_field( (string) $record['uuid'] ),
			'source_system' => 'algq-deal-intake',
			'source_record_id' => (string) $submission_id,
			'source' => sanitize_text_field( (string) $record['lead_source'] ),
			'asking_price' => (float) $record['asking_price'],
			'lead_score' => (int) $record['lead_score'],
			'stage' => 'new_intake',
			'property' => array(
				'address' => sanitize_text_field( (string) $record['address'] ),
				'city' => sanitize_text_field( (string) $record['city'] ),
				'state' => sanitize_text_field( (string) $record['state'] ),
				'postal_code' => sanitize_text_field( (string) $record['postal_code'] ),
				'property_type' => sanitize_text_field( (string) $record['property_type'] ),
			),
			'seller' => array(
				'name' => sanitize_text_field( (string) $record['seller_name'] ),
				'email' => sanitize_email( (string) $record['seller_email'] ),
				'phone' => sanitize_text_field( (string) $record['seller_phone'] ),
			),
		);

		$pipeline_result = null;
		$service_missing = false;

		if ( function_exists( 'algq_platform_service_call' ) ) {
			$pipeline_result = algq_platform_service_call(
				'pipeline.deals',
				'create',
				$payload,
				array(
					'caller_plugin' => 'algq-deal-intake',
					'intake_submission_id' => $submission_id,
				)
			);
			$service_missing = is_wp_error( $pipeline_result ) && 'algq_service_not_found' === $pipeline_result->get_error_code();
		}

		if ( null === $pipeline_result || $service_missing ) {
			if ( function_exists( 'algq_pipeline_create_deal' ) ) {
				$pipeline_result = algq_pipeline_create_deal( $payload );
			} else {
				$pipeline_result = new WP_Error( 'pipeline_unavailable', __( 'Pipeline CRM is not available.', 'algq-deal-intake' ) );
			}
		}

		if ( is_wp_error( $pipeline_result ) ) {
			self::mark_pipeline_handoff_pending( $submission_id );
			do_action( 'algq_deal_intake_pipeline_handoff_requested', $submission_id, $payload, $pipeline_result );
			return $pipeline_result;
		}

		$deal_id = self::pipeline_deal_id_from_result( $pipeline_result );
		$deal_id = absint( apply_filters( 'algq_pipeline_create_deal', $deal_id, $payload, $submission_id ) );

		if ( ! $deal_id ) {
			self::mark_pipeline_handoff_pending( $submission_id );
			do_action( 'algq_deal_intake_pipeline_handoff_requested', $submission_id, $payload );
			return new WP_Error( 'pipeline_unavailable', __( 'The submission was approved, but Pipeline CRM did not return a canonical deal ID.', 'algq-deal-intake' ) );
		}

		$wpdb->update(
			ALGQ_Deal_Intake_Database::table( 'submissions' ),
			array(
				'status' => 'accepted',
				'pipeline_deal_id' => $deal_id,
				'updated_at' => current_time( 'mysql', true ),
			),
			array( 'id' => $submission_id ),
			array( '%s', '%d', '%s' ),
			array( '%d' )
		);

		do_action( 'algq_deal_intake_deal_created', $submission_id, $deal_id, $payload );
		do_action( 'algq_audit_event', 'deal_intake.deal_created', array( 'submission_id' => $submission_id, 'deal_id' => $deal_id ) );
		return $deal_id;
	}

	private static function pipeline_deal_id_from_result( $result ): int {
		if ( is_numeric( $result ) ) {
			return absint( $result );
		}
		if ( is_array( $result ) ) {
			return absint( $result['id'] ?? $result['deal_id'] ?? 0 );
		}
		if ( is_object( $result ) ) {
			return absint( $result->id ?? $result->deal_id ?? 0 );
		}
		return 0;
	}

	private static function mark_pipeline_handoff_pending( int $submission_id ): void {
		global $wpdb;
		$wpdb->update(
			ALGQ_Deal_Intake_Database::table( 'submissions' ),
			array( 'status' => 'awaiting_pipeline', 'updated_at' => current_time( 'mysql', true ) ),
			array( 'id' => $submission_id ),
			array( '%s', '%s' ),
			array( '%d' )
		);
	}

	public static function handle_archive(): void {
		if ( ! current_user_can( ALGQ_Deal_Intake_Security::CAP_REVIEW ) ) {
			wp_die( esc_html__( 'You do not have permission to archive submissions.', 'algq-deal-intake' ) );
		}
		$submission_id = isset( $_GET['submission_id'] ) ? absint( $_GET['submission_id'] ) : 0;
		if ( ! $submission_id || ! ALGQ_Deal_Intake_Security::verify_nonce( 'algq_di_archive_' . $submission_id ) ) {
			wp_die( esc_html__( 'The archive request could not be verified.', 'algq-deal-intake' ) );
		}
		global $wpdb;
		$wpdb->update(
			ALGQ_Deal_Intake_Database::table( 'submissions' ),
			array( 'status' => 'archived', 'updated_at' => current_time( 'mysql', true ) ),
			array( 'id' => $submission_id ),
			array( '%s', '%s' ),
			array( '%d' )
		);
		wp_safe_redirect( admin_url( 'admin.php?page=algq-deal-intake&status=archived' ) );
		exit;
	}

	private static function notify( array $record ): void {
		$email = sanitize_email( (string) get_option( 'algq_di_notification_email', get_option( 'admin_email' ) ) );
		if ( ! is_email( $email ) ) {
			return;
		}
		$subject = sprintf( 'New property submission: %s', $record['uuid'] ?? '' );
		$message = sprintf(
			"A new property submission was received.\n\nReference: %s\nProperty: %s, %s, %s\nSeller: %s\nLead score: %s\nDuplicate status: %s",
			$record['uuid'] ?? '',
			$record['address'] ?? '',
			$record['city'] ?? '',
			$record['state'] ?? '',
			$record['seller_name'] ?? '',
			$record['lead_score'] ?? 0,
			$record['duplicate_status'] ?? ''
		);

		if ( function_exists( 'algq_send_mail' ) ) {
			algq_send_mail(
				array(
					'to' => $email,
					'subject' => $subject,
					'message' => $message,
					'module' => 'deal-intake',
					'event' => 'submission_received',
					'related_id' => (int) ( $record['id'] ?? 0 ),
				)
			);
			return;
		}

		wp_mail( $email, $subject, $message );
	}
}
