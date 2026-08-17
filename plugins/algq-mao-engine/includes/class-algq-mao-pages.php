<?php
/** Idempotent WPBakery page generation. */
defined( 'ABSPATH' ) || exit;

final class ALGQ_MAO_Pages {
	public static function install() {
		foreach ( array(
			'plugin/mao-engine' => array( 'Algonquian MAO Engine', '[algq_mao_plugin_page]' ),
			'plugin/mao-engine/start' => array( 'Getting Started With the Algonquian MAO Engine', '[algq_mao_plugin_page view="start"]' ),
			'plugin/mao-engine/docs' => array( 'Algonquian MAO Engine Documentation', '[algq_mao_plugin_page view="docs"]' ),
			'plugin/mao-engine/calculator' => array( 'Algonquian MAO Calculator', '[algq_mao_calculator]' ),
		) as $path => $page ) { self::ensure( $path, $page[0], self::wrap( $page[1] ) ); }
	}

	private static function ensure( $path, $title, $content ) {
		if ( $page = get_page_by_path( $path, OBJECT, 'page' ) ) { return (int) $page->ID; }
		$segments = array_values( array_filter( explode( '/', trim( $path, '/' ) ) ) ); $parent = 0; $current = '';
		foreach ( $segments as $index => $slug ) {
			$current = ltrim( $current . '/' . $slug, '/' ); $found = get_page_by_path( $current, OBJECT, 'page' );
			if ( $found ) { $parent = (int) $found->ID; continue; }
			$final = $index === count( $segments ) - 1;
			$id = wp_insert_post( array( 'post_title' => $final ? sanitize_text_field( $title ) : ucwords( str_replace( '-', ' ', $slug ) ), 'post_name' => sanitize_title( $slug ), 'post_content' => $final ? $content : '', 'post_status' => 'publish', 'post_type' => 'page', 'post_parent' => $parent ), true );
			if ( is_wp_error( $id ) ) { return 0; } $parent = (int) $id;
		}
		return $parent;
	}
	private static function wrap( $shortcode ) { return '[vc_row full_width="stretch_row_content"][vc_column][vc_column_text]' . $shortcode . '[/vc_column_text][/vc_column][/vc_row]'; }
}
