<?php defined( 'ABSPATH' ) || exit; ?>
<div class="algq-command-center algq-public-dashboard">
    <header class="algq-page-header"><div><span class="algq-eyebrow">Executive Operations</span><h2><?php echo esc_html__( 'Algonquian Admin Command Center', 'algq-command-center' ); ?></h2><p><?php echo esc_html__( 'Protected operating visibility across the Algonquian Real Estate Platform.', 'algq-command-center' ); ?></p></div><span class="algq-version">v<?php echo esc_html( ALGQ_COMMAND_CENTER_VERSION ); ?></span></header>
    <?php ALGQ_Command_Center_Widgets::render_kpi_cards(); ?>
    <div class="algq-two-col"><?php ALGQ_Command_Center_Widgets::render_pipeline(); ?><?php ALGQ_Command_Center_Widgets::render_activity_feed(); ?></div>
    <?php ALGQ_Command_Center_Widgets::render_health(); ?>
</div>
