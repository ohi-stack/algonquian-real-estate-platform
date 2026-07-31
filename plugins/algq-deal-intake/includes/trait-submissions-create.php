<?php
/** Generated method group for Algonquian Deal Intake. */
defined( 'ABSPATH' ) || exit;

trait ALGQ_Deal_Intake_Submissions_Create {
	public static function create_from_array( array $input, bool $internal = false ): int|WP_Error {
		global $wpdb;

		$validated = self::validate( $input, $internal );
		if ( is_wp_error( $validated ) ) {
			return $validated;
		}

		$seller = $validated['seller'];
		$property = $validated['property'];
		$submission = $validated['submission'];
		$consent = $validated['consent'];
		$matches = ALGQ_Deal_Intake_Duplicate_Detector::find_matches( $seller, $property );
		$now = current_time( 'mysql', true );

		$wpdb->query( 'START TRANSACTION' );

		try {
			$seller_inserted = $wpdb->insert(
				ALGQ_Deal_Intake_Database::table( 'sellers' ),
				array(
					'uuid' => wp_generate_uuid4(),
					'name' => $seller['name'],
					'email' => $seller['email'],
					'phone' => $seller['phone'],
					'mailing_address' => $seller['mailing_address'],
					'preferred_contact' => $seller['preferred_contact'],
					'created_at' => $now,
					'updated_at' => $now,
				),
				array( '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s' )
			);

			if ( false === $seller_inserted ) {
				throw new RuntimeException( 'seller_insert_failed' );
			}
			$seller_id = (int) $wpdb->insert_id;

			$property_inserted = $wpdb->insert(
				ALGQ_Deal_Intake_Database::table( 'properties' ),
				array(
					'uuid' => wp_generate_uuid4(),
					'address' => $property['address'],
					'address_normalized' => $property['address_normalized'],
					'city' => $property['city'],
					'state' => $property['state'],
					'postal_code' => $property['postal_code'],
					'county' => $property['county'],
					'parcel' => $property['parcel'],
					'property_type' => $property['property_type'],
					'occupancy' => $property['occupancy'],
					'condition_summary' => $property['condition_summary'],
					'mortgage_status' => $property['mortgage_status'],
					'estimated_value' => $property['estimated_value'],
					'created_at' => $now,
					'updated_at' => $now,
				),
				array( '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%f', '%s', '%s' )
			);

			if ( false === $property_inserted ) {
				throw new RuntimeException( 'property_insert_failed' );
			}
			$property_id = (int) $wpdb->insert_id;

			$duplicate_status = empty( $matches ) ? 'clear' : 'review_required';
			$submission_inserted = $wpdb->insert(
				ALGQ_Deal_Intake_Database::table( 'submissions' ),
				array(
					'uuid' => wp_generate_uuid4(),
					'seller_id' => $seller_id,
					'property_id' => $property_id,
					'status' => 'pending_review',
					'lead_source' => $submission['lead_source'],
					'campaign' => $submission['campaign'],
					'motivation' => $submission['motivation'],
					'timeline' => $submission['timeline'],
					'asking_price' => $submission['asking_price'],
					'lead_score' => self::lead_score( $validated ),
					'duplicate_status' => $duplicate_status,
					'created_by' => ALGQ_Deal_Intake_Security::current_user_id(),
					'created_at' => $now,
					'updated_at' => $now,
				),
				array( '%s', '%d', '%d', '%s', '%s', '%s', '%s', '%s', '%f', '%d', '%s', '%d', '%s', '%s' )
			);

			if ( false === $submission_inserted ) {
				throw new RuntimeException( 'submission_insert_failed' );
			}
			$submission_id = (int) $wpdb->insert_id;

			$consent_inserted = $wpdb->insert(
				ALGQ_Deal_Intake_Database::table( 'consents' ),
				array(
					'submission_id' => $submission_id,
					'consent_version' => $consent['consent_version'],
					'privacy_version' => $consent['privacy_version'],
					'terms_version' => $consent['terms_version'],
					'accepted' => 1,
					'ip_address' => $consent['ip_address'],
					'user_agent' => $consent['user_agent'],
					'accepted_at' => $now,
				),
				array( '%d', '%s', '%s', '%s', '%d', '%s', '%s', '%s' )
			);

			if ( false === $consent_inserted ) {
				throw new RuntimeException( 'consent_insert_failed' );
			}

			ALGQ_Deal_Intake_Duplicate_Detector::queue_matches( $submission_id, $matches );
			$wpdb->query( 'COMMIT' );
		} catch ( Throwable $error ) {
			$wpdb->query( 'ROLLBACK' );
			do_action( 'algq_audit_event', 'deal_intake.persistence_failed', array( 'error' => $error->getMessage() ) );
			return new WP_Error( 'persistence_failed', __( 'The submission could not be saved. Please contact Algonquian Real Estate.', 'algq-deal-intake' ) );
		}

		$record = self::get( $submission_id );
		do_action( 'algq_deal_intake_submission_created', $submission_id, $record );
		do_action( 'algq_audit_event', 'deal_intake.submission_created', array( 'submission_id' => $submission_id, 'duplicate_status' => $record['duplicate_status'] ?? '' ) );
		self::notify( $record );

		return $submission_id;
	}

	private static function validate( array $input, bool $internal ): array|WP_Error {
		$name = ALGQ_Deal_Intake_Security::text( $input['seller_name'] ?? '' );
		$email = ALGQ_Deal_Intake_Security::email( $input['seller_email'] ?? '' );
		$phone = ALGQ_Deal_Intake_Security::phone( $input['seller_phone'] ?? '' );
		$address = ALGQ_Deal_Intake_Security::text( $input['address'] ?? '' );
		$city = ALGQ_Deal_Intake_Security::text( $input['city'] ?? '' );
		$state = strtoupper( substr( ALGQ_Deal_Intake_Security::text( $input['state'] ?? '' ), 0, 2 ) );
		$consent = ! empty( $input['consent_accepted'] );

		if ( '' === $name || '' === $address || '' === $city || 2 !== strlen( $state ) ) {
			return new WP_Error( 'missing_required', __( 'Name, property address, city, and two-letter state are required.', 'algq-deal-intake' ) );
		}

		if ( '' === $email && '' === $phone ) {
			return new WP_Error( 'missing_contact', __( 'Provide at least one contact method: email or phone.', 'algq-deal-intake' ) );
		}

		if ( '' !== $email && ! is_email( $email ) ) {
			return new WP_Error( 'invalid_email', __( 'The email address is not valid.', 'algq-deal-intake' ) );
		}

		if ( ! $consent ) {
			return new WP_Error( 'consent_required', __( 'Contact authorization and submission consent are required.', 'algq-deal-intake' ) );
		}

		$property_input = array(
			'address' => $address,
			'city' => $city,
			'state' => $state,
			'postal_code' => ALGQ_Deal_Intake_Security::text( $input['postal_code'] ?? '' ),
			'parcel' => ALGQ_Deal_Intake_Security::text( $input['parcel'] ?? '' ),
		);

		return array(
			'seller' => array(
				'name' => $name,
				'email' => $email,
				'phone' => $phone,
				'mailing_address' => ALGQ_Deal_Intake_Security::text( $input['mailing_address'] ?? '' ),
				'preferred_contact' => ALGQ_Deal_Intake_Security::key( $input['preferred_contact'] ?? '' ),
			),
			'property' => array(
				'address' => $address,
				'address_normalized' => ALGQ_Deal_Intake_Security::normalize_address( $property_input ),
				'city' => $city,
				'state' => $state,
				'postal_code' => $property_input['postal_code'],
				'county' => ALGQ_Deal_Intake_Security::text( $input['county'] ?? '' ),
				'parcel' => $property_input['parcel'],
				'property_type' => ALGQ_Deal_Intake_Security::key( $input['property_type'] ?? '' ),
				'occupancy' => ALGQ_Deal_Intake_Security::key( $input['occupancy'] ?? '' ),
				'condition_summary' => ALGQ_Deal_Intake_Security::textarea( $input['condition_summary'] ?? '' ),
				'mortgage_status' => ALGQ_Deal_Intake_Security::key( $input['mortgage_status'] ?? '' ),
				'estimated_value' => ALGQ_Deal_Intake_Security::money( $input['estimated_value'] ?? 0 ),
			),
			'submission' => array(
				'lead_source' => ALGQ_Deal_Intake_Security::key( $input['lead_source'] ?? ( $internal ? 'internal' : 'website' ) ),
				'campaign' => ALGQ_Deal_Intake_Security::text( $input['campaign'] ?? '' ),
				'motivation' => ALGQ_Deal_Intake_Security::textarea( $input['motivation'] ?? '' ),
				'timeline' => ALGQ_Deal_Intake_Security::key( $input['timeline'] ?? '' ),
				'asking_price' => ALGQ_Deal_Intake_Security::money( $input['asking_price'] ?? 0 ),
			),
			'consent' => array(
				'consent_version' => (string) get_option( 'algq_di_consent_version', '1.0' ),
				'privacy_version' => (string) get_option( 'algq_di_privacy_version', '1.0' ),
				'terms_version' => (string) get_option( 'algq_di_terms_version', '1.0' ),
				'ip_address' => ALGQ_Deal_Intake_Security::request_ip(),
				'user_agent' => ALGQ_Deal_Intake_Security::request_user_agent(),
			),
		);
	}

	private static function lead_score( array $validated ): int {
		$score = 20;
		$timeline = $validated['submission']['timeline'];
		if ( '0-30-days' === $timeline ) {
			$score += 30;
		} elseif ( '31-90-days' === $timeline ) {
			$score += 20;
		} elseif ( '3-6-months' === $timeline ) {
			$score += 10;
		}

		$motivation = strtolower( $validated['submission']['motivation'] . ' ' . $validated['property']['condition_summary'] );
		foreach ( array( 'vacant', 'inherited', 'foreclosure', 'repairs', 'landlord', 'estate', 'code' ) as $signal ) {
			if ( false !== strpos( $motivation, $signal ) ) {
				$score += 7;
			}
		}

		if ( (float) $validated['submission']['asking_price'] > 0 ) {
			$score += 5;
		}

		return min( 100, $score );
	}

	private static function check_rate_limit(): bool {
		$key = ALGQ_Deal_Intake_Security::rate_limit_key();
		$count = absint( get_transient( $key ) );
		$limit = max( 1, absint( get_option( 'algq_di_rate_limit_per_hour', 10 ) ) );

		if ( $count >= $limit ) {
			return false;
		}

		set_transient( $key, $count + 1, HOUR_IN_SECONDS );
		return true;
	}
}
