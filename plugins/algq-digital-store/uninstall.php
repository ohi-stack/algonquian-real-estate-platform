<?php
/**
 * Conservative uninstall routine for Algonquian Digital Store.
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

delete_option( 'algq_digital_store_version' );
delete_option( 'algq_digital_store_pages' );

// WooCommerce products, orders, downloads, customers, and generated pages are retained.
// They may be shared with other platform modules or required for accounting and audit records.
