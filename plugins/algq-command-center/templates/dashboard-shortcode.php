<?php defined( 'ABSPATH' ) || exit; ?>
<div class="algq-command-center algq-public-dashboard">
<header class="algq-page-header"><div><span class="algq-eyebrow">Executive Transaction Control</span><h2><?php echo esc_html__( 'Algonquian Admin Command Center', 'algq-command-center' ); ?></h2><p><?php echo esc_html__( 'Decisions, exceptions, revenue, deadlines, agent operations and platform health across the Algonquian Real Estate transaction system.', 'algq-command-center' ); ?></p></div><span class="algq-version">v<?php echo esc_html( ALGQ_COMMAND_CENTER_VERSION ); ?></span></header>
<?php ALGQ_Command_Center_Widgets::render_executive_brief(); ?>
<?php ALGQ_Command_Center_Widgets::render_decisions(); ?>
<?php ALGQ_Command_Center_Widgets::render_kpi_cards(); ?>
<?php ALGQ_Command_Center_Widgets::render_revenue(); ?>
<div class="algq-two-col"><?php ALGQ_Command_Center_Widgets::render_risk(); ?><?php ALGQ_Command_Center_Widgets::render_deadlines(); ?></div>
<?php ALGQ_Command_Center_Widgets::render_pipeline(); ?>
<div class="algq-two-col"><?php ALGQ_Command_Center_Widgets::render_capital(); ?><?php ALGQ_Command_Center_Widgets::render_approvals(); ?></div>
<?php ALGQ_Command_Center_Widgets::render_agents(); ?>
<?php ALGQ_Command_Center_Widgets::render_activity_feed(); ?>
<?php ALGQ_Command_Center_Widgets::render_health(); ?>
</div>
