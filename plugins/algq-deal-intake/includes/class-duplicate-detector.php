<?php
/**
 * Duplicate-detection service.
 *
 * @package Algonquian_Deal_Intake
 */

defined( 'ABSPATH' ) || exit;

final class ALGQ_Deal_Intake_Duplicate_Detector {
	public static function find_matches( array $seller, array $property, int $exclude_submission_id = 0 ): array {
		global $wpdb;

		$submissions = ALGQ_Deal_Intake_Database::table( 'submissions' );
		$sellers = ALGQ_Deal_Intake_Database::table( 'sellers' );
		$properties = ALGQ_Deal_Intake_Database::table( 'properties' );

		$email = ALGQ_Deal_Intake_Security::email( $seller['email'] ?? '' );
		$phone = preg_replace( '/\D+/', '', ALGQ_Deal_Intake_Security::phone( $seller['phone'] ?? '' ) );
		$address = ALGQ_Deal_Intake_Security::normalize_address( $property );
		$parcel = ALGQ_Deal_Intake_Security::text( $property['parcel'] ?? '' );

		$clauses = array();
		$params = array();

		if ( '' !== $email ) {
			$clauses[] = 's.email = %s';
			$params[] = $email;
		}

		if ( '' !== $phone ) {
			$clauses[] = "REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(s.phone, ' ', ''), '-', ''), '(', ''), ')', ''), '.', '') = %s";
			$params[] = $phone;
		}

		if ( '' !== $address ) {
			$clauses[] = 'p.address_normalized = %s';
			$params[] = $address;
		}

		if ( '' !== $parcel ) {
			$clauses[] = 'p.parcel = %s';
			$params[] = $parcel;
		}

		if ( empty( $clauses ) ) {
			return array();
		}

		$where = '(' . implode( ' OR ', $clauses ) . ')';
		if ( $exclude_submission_id > 0 ) {
			$where .= ' AND sub.id <> %d';
			$params[] = $exclude_submission_id;
		}

		$sql = "SELECT sub.id, sub.uuid, s.email, s.phone, p.address_normalized, p.parcel
			FROM {$submissions} sub
			INNER JOIN {$sellers} s ON s.id = sub.seller_id
			INNER JOIN {$properties} p ON p.id = sub.property_id
			WHERE sub.deleted_at IS NULL AND {$where}
			ORDER BY sub.created_at DESC
			LIMIT 20";

		$prepared = $wpdb->prepare( $sql, $params ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$rows = $wpdb->get_results( $prepared, ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$matches = array();

		foreach ( $rows as $row ) {
			$fields = array();
			$score = 0;

			if ( '' !== $email && hash_equals( strtolower( $email ), strtolower( (string) $row['email'] ) ) ) {
				$fields[] = 'email';
				$score += 35;
			}

			$row_phone = preg_replace( '/\D+/', '', (string) $row['phone'] );
			if ( '' !== $phone && hash_equals( $phone, $row_phone ) ) {
				$fields[] = 'phone';
				$score += 30;
			}

			if ( '' !== $address && hash_equals( $address, (string) $row['address_normalized'] ) ) {
				$fields[] = 'address';
				$score += 45;
			}

			if ( '' !== $parcel && hash_equals( strtolower( $parcel ), strtolower( (string) $row['parcel'] ) ) ) {
				$fields[] = 'parcel';
				$score += 50;
			}

			$matches[] = array(
				'submission_id' => (int) $row['id'],
				'uuid' => (string) $row['uuid'],
				'score' => min( 100, $score ),
				'fields' => $fields,
			);
		}

		usort(
			$matches,
			static fn( array $a, array $b ): int => $b['score'] <=> $a['score']
		);

		return $matches;
	}

	public static function queue_matches( int $submission_id, array $matches ): void {
		global $wpdb;
		$table = ALGQ_Deal_Intake_Database::table( 'duplicates' );

		foreach ( $matches as $match ) {
			if ( (int) $match['score'] < 30 ) {
				continue;
			}

			$wpdb->insert(
				$table,
				array(
					'submission_id' => $submission_id,
					'matched_submission_id' => (int) $match['submission_id'],
					'match_score' => (int) $match['score'],
					'matched_fields' => wp_json_encode( $match['fields'] ),
					'status' => 'pending',
					'created_at' => current_time( 'mysql', true ),
				),
				array( '%d', '%d', '%d', '%s', '%s', '%s' )
			);
		}
	}
}
