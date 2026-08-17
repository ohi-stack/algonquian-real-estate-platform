<?php defined( 'ABSPATH' ) || exit; ?>
<div class="wrap algq-command-center algq-admin-shell">
    <header class="algq-page-header">
        <div><span class="algq-eyebrow">Algonquian Real Estate Platform</span><h1><?php echo esc_html__( 'Admin Command Center', 'algq-command-center' ); ?></h1><p><?php echo esc_html__( 'Executive operational intelligence across acquisitions, capital, buyers, documents, automation, and platform health.', 'algq-command-center' ); ?></p></div>
        <div class="algq-header-meta"><span class="algq-version">v<?php echo esc_html( ALGQ_COMMAND_CENTER_VERSION ); ?></span><a class="button button-secondary" href="<?php echo esc_url( admin_url( 'admin.php?page=algq-command-center-system-health' ) ); ?>"><?php echo esc_html__( 'System Health', 'algq-command-center' ); ?></a></div>
    </header>
    <?php ALGQ_Command_Center_Widgets::render_kpi_cards(); ?>
    <div class="algq-two-col"><?php ALGQ_Command_Center_Widgets::render_pipeline(); ?><?php ALGQ_Command_Center_Widgets::render_activity_feed(); ?></div>
    <?php ALGQ_Command_Center_Widgets::render_health(); ?>
</div>
