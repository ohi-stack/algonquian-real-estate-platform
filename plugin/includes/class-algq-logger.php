<?php
defined( 'ABSPATH' ) || exit;

class ALGQ_Logger {
	public static function log( $object_type, $object_id, $action, $note = '' ) {
		global $wpdb;
		$table = ALGQ_Database::table( 'activity_log' );
		return $wpdb->insert( $table, array(
			'object_type' => sanitize_key( $object_type ),
			'object_id'   => sanitize_text_field( (string) $object_id ),
			'action'      => sanitize_text_field( $action ),
			'note'        => sanitize_textarea_field( $note ),
			'user_id'     => get_current_user_id(),
			'created_at'  => current_time( 'mysql' ),
		), array( '%s', '%s', '%s', '%s', '%d', '%s' ) );
	}
}
