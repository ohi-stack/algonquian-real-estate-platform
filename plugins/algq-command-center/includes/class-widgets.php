<?php
/**
 * Dashboard widgets for Algonquian Command Center.
 *
 * @package Algonquian_Command_Center
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class ALGQ_Command_Center_Widgets {
	public static function registry() {
		return array(
			'total_deals' => array(
				'label' => __( 'Total Deals', 'algq-command-center' ),
				'group' => 'acquisition',
			),
			'leads_captured' => array(
				'label' => __( 'Leads Captured', 'algq-command-center' ),
				'group' => 'acquisition',
			),
			'offers_sent' => array(
				'label' => __( 'Offers Sent', 'algq-command-center' ),
				'group' => 'documents',
			),
			'contracts_pending' => array(
				'label' => __( 'Contracts Pending', 'algq-command-center' ),
				'group' => 'transactions',
			),
			'buyers_registered' => array(
				'label' => __( 'Buyers Registered', 'algq-command-center' ),
				'group' => 'buyers',
			),
			'pipeline_value' => array(
				'label' => __( 'Pipeline Value', 'algq-command-center' ),
				'group' => 'executive',
			),
			'funding_status' => array(
				'label' => __( 'Funding Status', 'algq-command-center' ),
				'group' => 'capital',
			),
			'recent_documents' => array(
				'label' => __( 'Documents Generated', 'algq-command-center' ),
				'group' => 'documents',
			),
		);
	}

	public static function enabled_widgets() {
		$enabled = get_option( 'algq_command_center_enabled_widgets', array_keys( self::registry() ) );
		return array_values( array_intersect( array_map( 'sanitize_key', (array) $enabled ), array_keys( self::registry() ) ) );
	}

	public static function render_kpi_cards() {
		$metrics  = ALGQ_Command_Center_Data_Provider::metrics();
		$registry = self::registry();
		$enabled  = self::enabled_widgets();

		echo '<div class="algq-kpi-grid" data-algq-sortable="kpis">';
		foreach ( $enabled as $key ) {
			if ( ! isset( $registry[ $key ] ) ) {
				continue;
			}
			$value = isset( $metrics[ $key ] ) ? $metrics[ $key ] : 0;
			if ( is_array( $value ) ) {
				$value = isset( $value['percent'] ) ? $value['percent'] . '%' : '0%';
			} elseif ( 'pipeline_value' === $key ) {
				$value = '$' . number_format_i18n( (float) $value, 0 );
			}
			echo '<section class="algq-kpi-card" draggable="true">';
			echo '<span class="algq-kpi-label">' . esc_html( $registry[ $key ]['label'] ) . '</span>';
			echo '<strong class="algq-kpi-value">' . esc_html( (string) $value ) . '</strong>';
			echo '<small class="algq-kpi-group">' . esc_html( ucfirst( $registry[ $key ]['group'] ) ) . '</small>';
			echo '</section>';
		}
		echo '</div>';
	}

	public static function render_activity_feed() {
		$items = ALGQ_Command_Center_Data_Provider::activity();
		echo '<div class="algq-panel"><h3>' . esc_html__( 'Operational Activity', 'algq-command-center' ) . '</h3><ul class="algq-feed">';
		foreach ( $items as $item ) {
			echo '<li><strong>' . esc_html( $item['type'] ) . '</strong><span>' . esc_html( $item['message'] ) . '</span><em>' . esc_html( $item['time'] ) . '</em></li>';
		}
		echo '</ul></div>';
	}

	public static function render_pipeline() {
		$stages = ALGQ_Command_Center_Data_Provider::pipeline_stages();
		echo '<div class="algq-panel"><h3>' . esc_html__( 'Pipeline by Stage', 'algq-command-center' ) . '</h3><div class="algq-stage-list">';
		foreach ( $stages as $stage ) {
			echo '<div class="algq-stage-row"><span>' . esc_html( $stage['label'] ) . '</span><strong>' . esc_html( (string) $stage['count'] ) . '</strong></div>';
		}
		echo '</div></div>';
	}
}
