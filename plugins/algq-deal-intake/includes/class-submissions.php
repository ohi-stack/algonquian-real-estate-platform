<?php
/** Submission orchestration composed from focused method groups. */
defined( 'ABSPATH' ) || exit;
require_once __DIR__ . '/trait-submissions-handlers.php';
require_once __DIR__ . '/trait-submissions-create.php';
require_once __DIR__ . '/trait-submissions-workflow.php';
final class ALGQ_Deal_Intake_Submissions {
    use ALGQ_Deal_Intake_Submissions_Handlers;
    use ALGQ_Deal_Intake_Submissions_Create;
    use ALGQ_Deal_Intake_Submissions_Workflow;
}
