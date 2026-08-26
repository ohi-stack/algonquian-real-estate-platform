<?php
/**
 * Platform service adapter for the canonical Pipeline CRM deal authority.
 */

defined( 'ABSPATH' ) || exit;

final class ALGQ_Pipeline_Platform_Service implements ARE_Platform_Service_Interface {
	public function id(): string {
		return 'pipeline.deals';
	}

	public function version(): string {
		return defined( 'ALGQ_PIPELINE_VERSION' ) ? (string) ALGQ_PIPELINE_VERSION : 'unknown';
	}

	public function operations(): array {
		return array( 'get', 'create', 'update', 'transition', 'query', 'activity' );
	}

	public function call( string $operation, array $payload = array(), array $context = array() ) {
		switch ( $operation ) {
			case 'get':
				return $this->get_deal( $payload['identifier'] ?? $payload['deal_id'] ?? 0 );
			case 'create':
				return $this->create_deal( $payload, $context );
			case 'update':
				return $this->update_deal( $payload );
			case 'transition':
				return $this->transition_deal( $payload, $context );
			case 'query':
				return $this->query_deals( $payload );
			case 'activity':
				return $this->activity( $payload );
		}

		return new WP_Error( 'algq_pipeline_service_operation', 'Unsupported Pipeline CRM service operation.' );
	}

	public function health(): array {
		$available = class_exists( 'ALGQ_Pipeline_Service' ) || class_exists( 'ALGQ_Pipeline_Deal_Repository' );
		return array(
			'status'  => $available ? 'ok' : 'error',
			'label'   => 'Pipeline CRM Deal Service',
			'message' => $available ? 'Canonical deal authority is available through the Platform Service Interface.' : 'Pipeline CRM deal authority is unavailable.',
			'version' => $this->version(),
		);
	}

	private function get_deal( $identifier ) {
		if ( class_exists( 'ALGQ_Pipeline_Service' ) ) {
			return ALGQ_Pipeline_Service::instance()->get_deal( $identifier );
		}

		if ( class_exists( 'ALGQ_Pipeline_Deal_Repository' ) ) {
			if ( is_numeric( $identifier ) ) {
				return ALGQ_Pipeline_Deal_Repository::get( absint( $identifier ) );
			}

			$identifier = sanitize_text_field( (string) $identifier );
			foreach ( ALGQ_Pipeline_Deal_Repository::query( array( 'search' => $identifier, 'limit' => 25 ) ) as $deal ) {
				if ( $identifier === (string) ( $deal['uuid'] ?? '' ) || $identifier === (string) ( $deal['deal_number'] ?? '' ) ) {
					return $deal;
				}
			}
		}

		return null;
	}

	private function create_deal( array $payload, array $context ) {
		$input = $this->normalize_create_payload( $payload, $context );

		if ( class_exists( 'ALGQ_Pipeline_Service' ) ) {
			$result = ALGQ_Pipeline_Service::instance()->create_deal( $input );
			if ( is_wp_error( $result ) || is_array( $result ) ) {
				return $result;
			}
			return $this->get_deal( $result );
		}

		if ( class_exists( 'ALGQ_Pipeline_Deal_Repository' ) ) {
			$existing = $this->find_v21_source_match( $input );
			if ( $existing ) {
				return $existing;
			}

			$v21 = array(
				'title'                  => $input['title'],
				'property_address'       => $input['property_address'],
				'municipality'           => $input['municipality'],
				'state'                  => $input['state'],
				'postal_code'            => $input['postal_code'],
				'primary_contact_name'   => $input['primary_contact_name'],
				'primary_contact_email'  => $input['primary_contact_email'],
				'primary_contact_phone'  => $input['primary_contact_phone'],
				'assigned_user_id'       => $input['assigned_user_id'],
				'stage_key'              => $input['stage'],
				'priority'               => $input['priority'],
				'acquisition_strategy'   => $input['strategy'],
				'source'                 => $input['source'],
				'source_plugin'          => $input['source_system'],
				'external_source_id'     => $input['source_record_id'],
				'intake_submission_id'   => $input['intake_submission_id'],
				'asking_price'           => $input['asking_price'],
				'next_action'            => sanitize_text_field( (string) ( $payload['next_action'] ?? '' ) ),
				'next_action_due_at'     => sanitize_text_field( (string) ( $payload['next_action_due_at'] ?? '' ) ),
			);
			$deal_id = ALGQ_Pipeline_Deal_Repository::create( $v21 );
			if ( is_wp_error( $deal_id ) ) {
				return $deal_id;
			}
			return $this->get_deal( $deal_id );
		}

		return new WP_Error( 'algq_pipeline_unavailable', 'Pipeline CRM is not available.' );
	}

	private function update_deal( array $payload ) {
		$deal_id = absint( $payload['deal_id'] ?? 0 );
		if ( ! $deal_id ) {
			return new WP_Error( 'algq_pipeline_deal_id_required', 'A canonical deal_id is required.' );
		}

		$changes = is_array( $payload['changes'] ?? null ) ? $payload['changes'] : $payload;
		unset( $changes['deal_id'], $changes['identifier'], $changes['expected_version'], $changes['record_version'] );

		if ( class_exists( 'ALGQ_Pipeline_Service' ) ) {
			$current = ALGQ_Pipeline_Service::instance()->get_deal( $deal_id );
			if ( ! $current ) {
				return new WP_Error( 'algq_pipeline_not_found', 'Deal not found.' );
			}
			$expected = absint( $payload['expected_version'] ?? $payload['record_version'] ?? $current['record_version'] ?? 0 );
			return ALGQ_Pipeline_Service::instance()->update_deal( $deal_id, $changes, $expected );
		}

		if ( class_exists( 'ALGQ_Pipeline_Deal_Repository' ) ) {
			$current = ALGQ_Pipeline_Deal_Repository::get( $deal_id );
			if ( ! $current ) {
				return new WP_Error( 'algq_pipeline_not_found', 'Deal not found.' );
			}
			$expected = absint( $payload['expected_version'] ?? $payload['record_version'] ?? $current['record_version'] ?? 0 );
			if ( $expected && (int) $current['record_version'] !== $expected ) {
				return new WP_Error( 'algq_pipeline_conflict', 'This deal was changed by another request.', array( 'status' => 409 ) );
			}
			$result = ALGQ_Pipeline_Deal_Repository::update( $deal_id, $changes );
			return is_wp_error( $result ) ? $result : $this->get_deal( $deal_id );
		}

		return new WP_Error( 'algq_pipeline_unavailable', 'Pipeline CRM is not available.' );
	}

	private function transition_deal( array $payload, array $context ) {
		$deal_id = absint( $payload['deal_id'] ?? 0 );
		$target  = sanitize_key( (string) ( $payload['target'] ?? $payload['stage'] ?? '' ) );
		if ( ! $deal_id || '' === $target ) {
			return new WP_Error( 'algq_pipeline_transition_required', 'A canonical deal_id and target stage are required.' );
		}

		if ( class_exists( 'ALGQ_Pipeline_Service' ) ) {
			$transition_context = array_merge(
				$context,
				is_array( $payload['context'] ?? null ) ? $payload['context'] : array(),
				array(
					'reason'         => sanitize_text_field( (string) ( $payload['reason'] ?? '' ) ),
					'record_version' => absint( $payload['expected_version'] ?? $payload['record_version'] ?? 0 ),
				)
			);
			return ALGQ_Pipeline_Service::instance()->transition( $deal_id, $target, $transition_context );
		}

		if ( class_exists( 'ALGQ_Pipeline_Deal_Repository' ) ) {
			$changes = array(
				'stage_key'   => $target,
				'stage_reason' => sanitize_text_field( (string) ( $payload['reason'] ?? '' ) ),
			);
			$result = ALGQ_Pipeline_Deal_Repository::update( $deal_id, $changes );
			return is_wp_error( $result ) ? $result : $this->get_deal( $deal_id );
		}

		return new WP_Error( 'algq_pipeline_unavailable', 'Pipeline CRM is not available.' );
	}

	private function query_deals( array $payload ): array {
		if ( class_exists( 'ALGQ_Pipeline_Service' ) ) {
			return ALGQ_Pipeline_Service::instance()->repository()->list( $payload );
		}
		if ( class_exists( 'ALGQ_Pipeline_Deal_Repository' ) ) {
			return ALGQ_Pipeline_Deal_Repository::query( $payload );
		}
		return array();
	}

	private function activity( array $payload ): array {
		$deal_id = absint( $payload['deal_id'] ?? 0 );
		$limit   = min( 200, max( 1, absint( $payload['limit'] ?? 50 ) ) );
		if ( class_exists( 'ALGQ_Pipeline_Service' ) ) {
			return ALGQ_Pipeline_Service::instance()->repository()->activity( $deal_id, $limit );
		}
		if ( class_exists( 'ALGQ_Pipeline_Deal_Repository' ) && $deal_id ) {
			$related = ALGQ_Pipeline_Deal_Repository::related( $deal_id );
			return array_slice( (array) ( $related['activity'] ?? array() ), 0, $limit );
		}
		return array();
	}

	/** @return array<string,mixed> */
	private function normalize_create_payload( array $payload, array $context ): array {
		$property = is_array( $payload['property'] ?? null ) ? $payload['property'] : array();
		$seller   = is_array( $payload['seller'] ?? null ) ? $payload['seller'] : array();

		$street = sanitize_text_field( (string) ( $payload['property_address'] ?? $property['address'] ?? '' ) );
		$city   = sanitize_text_field( (string) ( $payload['municipality'] ?? $property['city'] ?? '' ) );
		$state  = sanitize_text_field( (string) ( $payload['state'] ?? $property['state'] ?? 'CT' ) );
		$zip    = sanitize_text_field( (string) ( $payload['postal_code'] ?? $property['postal_code'] ?? '' ) );
		$full_address = trim( implode( ', ', array_filter( array( $street, $city, $state . ( $zip ? ' ' . $zip : '' ) ) ) ) );

		$source_system = sanitize_key( (string) ( $payload['source_system'] ?? $payload['source_plugin'] ?? $context['caller_plugin'] ?? '' ) );
		$source_record = sanitize_text_field( (string) ( $payload['source_record_id'] ?? $payload['external_source_id'] ?? $payload['intake_submission_id'] ?? '' ) );
		$stage         = sanitize_key( (string) ( $payload['stage'] ?? $payload['stage_key'] ?? $payload['initial_stage'] ?? 'new_intake' ) );
		if ( 'lead_captured' === $stage ) {
			$stage = 'new_intake';
		}

		return array(
			'title'                 => sanitize_text_field( (string) ( $payload['title'] ?? $street ?: $full_address ) ),
			'property_address'      => $full_address ?: $street,
			'municipality'          => $city,
			'state'                 => $state,
			'postal_code'           => $zip,
			'primary_contact'       => sanitize_text_field( (string) ( $payload['primary_contact'] ?? $payload['primary_contact_name'] ?? $seller['name'] ?? '' ) ),
			'primary_contact_name'  => sanitize_text_field( (string) ( $payload['primary_contact_name'] ?? $payload['primary_contact'] ?? $seller['name'] ?? '' ) ),
			'primary_contact_email' => sanitize_email( (string) ( $payload['primary_contact_email'] ?? $seller['email'] ?? '' ) ),
			'primary_contact_phone' => sanitize_text_field( (string) ( $payload['primary_contact_phone'] ?? $seller['phone'] ?? '' ) ),
			'assigned_user_id'      => absint( $payload['assigned_user_id'] ?? 0 ),
			'stage'                 => $stage,
			'priority'              => sanitize_key( (string) ( $payload['priority'] ?? 'normal' ) ),
			'strategy'              => sanitize_key( (string) ( $payload['strategy'] ?? $payload['acquisition_strategy'] ?? '' ) ),
			'source'                => sanitize_text_field( (string) ( $payload['source'] ?? $payload['lead_source'] ?? '' ) ),
			'source_system'         => $source_system,
			'source_record_id'      => $source_record,
			'intake_submission_id'  => absint( $payload['intake_submission_id'] ?? 0 ),
			'asking_price'          => round( (float) ( $payload['asking_price'] ?? 0 ), 2 ),
			'offer_amount'          => round( (float) ( $payload['offer_amount'] ?? 0 ), 2 ),
		);
	}

	private function find_v21_source_match( array $input ): ?array {
		if ( ! class_exists( 'ALGQ_Pipeline_Schema' ) || '' === $input['source_system'] || '' === $input['source_record_id'] ) {
			return null;
		}

		global $wpdb;
		$tables = ALGQ_Pipeline_Schema::tables();
		$table  = $tables['deals'] ?? '';
		if ( ! $table ) {
			return null;
		}

		$id = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT id FROM {$table} WHERE source_plugin = %s AND external_source_id = %s AND deleted_at IS NULL LIMIT 1",
				$input['source_system'],
				$input['source_record_id']
			)
		);

		return $id ? ALGQ_Pipeline_Deal_Repository::get( (int) $id ) : null;
	}
}
