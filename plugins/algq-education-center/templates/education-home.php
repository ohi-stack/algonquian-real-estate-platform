<?php
if (!defined('ABSPATH')) { exit; }
?>
<section class="algq-edu algq-edu-home">
    <div class="algq-edu-hero">
        <p class="algq-kicker"><?php echo esc_html__('Algonquian Real Estate', 'algq-education-center'); ?></p>
        <h1><?php echo esc_html__('Education Center', 'algq-education-center'); ?></h1>
        <p><?php echo esc_html__('Structured training, guides, platform documentation, and digital products for sellers, buyers, lenders, and internal ARE users.', 'algq-education-center'); ?></p>
        <div class="algq-actions">
            <a class="algq-btn algq-btn-gold" href="<?php echo esc_url(home_url('/education/courses')); ?>"><?php echo esc_html__('View Courses', 'algq-education-center'); ?></a>
            <a class="algq-btn algq-btn-outline" href="<?php echo esc_url(home_url('/education/products')); ?>"><?php echo esc_html__('Browse Products', 'algq-education-center'); ?></a>
        </div>
    </div>
    <div class="algq-card-grid">
        <?php
        $tracks = array(
            array('Seller Education', 'Flexible sale options, seller financing, subject-to basics, and property submission readiness.', '/education/sellers'),
            array('Buyer Education', 'Buyer registration, NDA-gated deal access, due diligence, and deal package review.', '/education/buyers'),
            array('Lender Education', 'Funding package readiness, capital stack basics, underwriting review, and documentation expectations.', '/education/lenders'),
            array('Platform Training', 'Training for Deal Intake, Pipeline CRM, MAO Engine, Offer Generator, Document Library, and Command Center.', '/education/platform-training'),
        );
        foreach ($tracks as $track) : ?>
            <article class="algq-card">
                <h2><?php echo esc_html($track[0]); ?></h2>
                <p><?php echo esc_html($track[1]); ?></p>
                <a href="<?php echo esc_url(home_url($track[2])); ?>"><?php echo esc_html__('Open Track', 'algq-education-center'); ?></a>
            </article>
        <?php endforeach; ?>
    </div>
</section>
