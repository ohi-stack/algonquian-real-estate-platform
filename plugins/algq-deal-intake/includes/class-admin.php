<?php
/** Administrative interface composed from focused method groups. */
defined( 'ABSPATH' ) || exit;
require_once __DIR__ . '/trait-admin-pages.php';
require_once __DIR__ . '/trait-admin-actions.php';
final class ALGQ_Deal_Intake_Admin {
    use ALGQ_Deal_Intake_Admin_Pages;
    use ALGQ_Deal_Intake_Admin_Actions;
}
