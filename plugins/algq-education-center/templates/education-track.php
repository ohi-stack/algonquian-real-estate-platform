<?php
if (!defined('ABSPATH')) { exit; }
$type = isset($atts['type']) ? sanitize_key($atts['type']) : 'public';
$tracks = array(
    'seller' => array('Seller Education','Flexible sale options, property submission readiness, seller financing, subject-to basics, and closing expectations.'),
    'buyer' => array('Buyer Education','Buyer registration, NDA-gated access, deal review, funding readiness, due diligence, and offer submission.'),
    'lender' => array('Lender Education','Funding package review, underwriting standards, capital stack structure, documentation, and transaction controls.'),
    'acquisition' => array('Acquisition Training','Deal sourcing, underwriting, MAO discipline, seller outreach, offer strategy, and transaction workflow.'),
    'start' => array('Getting Started','Install, activate, review automatic pages, configure courses and guides, connect WooCommerce, and publish training tracks.'),
    'public' => array('Education Track','Structured education and operating guidance for Algonquian Real Estate users.'),
);
$track = isset($tracks[$type]) ? $tracks[$type] : $tracks['public'];
?>
<section class="algq-edu algq-track algq-track-<?php echo esc_attr($type); ?>">
    <header class="algq-section-header">
        <p class="algq-kicker"><?php echo esc_html__('Education Track', 'algq-education-center'); ?></p>
        <h1><?php echo esc_html($track[0]); ?></h1>
        <p><?php echo esc_html($track[1]); ?></p>
    </header>
    <div class="algq-card-grid">
        <?php
        $steps = array('Orientation','Core Concepts','Required Documents','Workflow Review','Next Action');
        foreach ($steps as $index => $step) : ?>
            <article class="algq-card">
                <span class="algq-badge"><?php echo esc_html(sprintf(__('Step %d', 'algq-education-center'), $index + 1)); ?></span>
                <h2><?php echo esc_html($step); ?></h2>
                <p><?php echo esc_html__('Review this section inside the Education Center and connect the relevant course, guide, or platform module.', 'algq-education-center'); ?></p>
            </article>
        <?php endforeach; ?>
    </div>
</section>
