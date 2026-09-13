<?php
/** Administrative interface composed from focused method groups. */
defined( 'ABSPATH' ) || exit;
require_once __DIR__ . '/trait-admin-pages.php';
require_once __DIR__ . '/trait-admin-actions.php';
require_once __DIR__ . '/trait-admin-dashboard.php';
final class ALGQ_Deal_Intake_Admin {
    use ALGQ_Deal_Intake_Admin_Pages, ALGQ_Deal_Intake_Admin_Actions, ALGQ_Deal_Intake_Admin_Dashboard {
        ALGQ_Deal_Intake_Admin_Dashboard::dashboard insteadof ALGQ_Deal_Intake_Admin_Pages;
    }
}
