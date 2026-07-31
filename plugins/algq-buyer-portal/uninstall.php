<?php

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

// Preserve buyer, NDA, interest, download, deal, and page records by default.
// Destructive removal requires an explicit administrator-approved retention decision.
delete_option( 'algq_buyer_portal_version' );
