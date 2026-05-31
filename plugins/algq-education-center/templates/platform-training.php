<?php
if (!defined('ABSPATH')) { exit; }
$modules = array(
    array('Deal Intake','Seller lead capture, property submission, intake records, and deal creation.'),
    array('Pipeline CRM','Kanban stages, deal movement, internal notes, assignments, and activity tracking.'),
    array('MAO Engine','ARV, repairs, holding costs, strategy mode, risk flags, and offer discipline.'),
    array('Offer Generator','LOIs, purchase agreements, seller financing offers, PDF export, and deal-file storage.'),
    array('Funding Tracker','Capital sources, lender terms, funding gaps, commitments, and deal-level capital stack.'),
    array('Buyer Portal','Buyer registration, NDA gating, deal access, downloads, and buyer interest submissions.'),
    array('Document Library','Institutional forms, lender packages, due diligence files, and version-controlled documents.'),
    array('Command Center','Executive KPIs, system health, pipeline value, revenue widgets, and operational reporting.'),
);
?>
<section class="algq-edu algq-platform-training">
    <header class="algq-section-header">
        <p class="algq-kicker"><?php echo esc_html__('ARE Technology Division', 'algq-education-center'); ?></p>
        <h1><?php echo esc_html__('Platform Training', 'algq-education-center'); ?></h1>
        <p><?php echo esc_html__('Operational training for the Algonquian Real Estate software platform, plugin suite, and transaction workflow.', 'algq-education-center'); ?></p>
    </header>
    <div class="algq-card-grid">
        <?php foreach ($modules as $module) : ?>
            <article class="algq-card algq-platform-card">
                <span class="algq-badge"><?php echo esc_html__('Platform Module', 'algq-education-center'); ?></span>
                <h2><?php echo esc_html($module[0]); ?></h2>
                <p><?php echo esc_html($module[1]); ?></p>
            </article>
        <?php endforeach; ?>
    </div>
</section>
