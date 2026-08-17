<?php
/** Conservative uninstall handler. */
defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

if ( ! defined( 'ALGQ_STEWARDSHIP_PURGE_ON_UNINSTALL' ) || true !== ALGQ_STEWARDSHIP_PURGE_ON_UNINSTALL ) {
    return;
}

delete_option( 'algq_property_stewardship_version' );
// Operational stewardship posts and protected documents are intentionally preserved unless a separate audited purge routine is executed.
