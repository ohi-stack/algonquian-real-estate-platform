<?php
if (!defined('ABSPATH')) { exit; }
$limit = isset($atts['limit']) ? absint($atts['limit']) : 12;
$items = class_exists('ALGQ_Education_WooCommerce') ? ALGQ_Education_WooCommerce::linked_products($limit) : array();
?>
<section class="algq-edu algq-product-library">
    <header class="algq-section-header">
        <p class="algq-kicker"><?php echo esc_html__('Digital Products', 'algq-education-center'); ?></p>
        <h1><?php echo esc_html__('Product Library', 'algq-education-center'); ?></h1>
        <p><?php echo esc_html__('Premium training, guides, templates, checklists, and toolkits connected to WooCommerce products.', 'algq-education-center'); ?></p>
    </header>
    <div class="algq-card-grid">
        <?php if (!empty($items)) : ?>
            <?php foreach ($items as $item) : ?>
                <?php $product_id = class_exists('ALGQ_Education_WooCommerce') ? ALGQ_Education_WooCommerce::product_id_for_post($item->ID) : 0; ?>
                <article class="algq-card algq-product-card">
                    <span class="algq-badge"><?php echo esc_html(get_post_type($item->ID)); ?></span>
                    <h2><?php echo esc_html(get_the_title($item)); ?></h2>
                    <p><?php echo esc_html(get_the_excerpt($item) ? get_the_excerpt($item) : wp_trim_words(wp_strip_all_tags($item->post_content), 24)); ?></p>
                    <div class="algq-actions">
                        <?php echo apply_filters('algq_education_product_button', '', $product_id, __('Get Access', 'algq-education-center')); ?>
                    </div>
                </article>
            <?php endforeach; ?>
        <?php else : ?>
            <article class="algq-card">
                <h2><?php echo esc_html__('No linked products yet.', 'algq-education-center'); ?></h2>
                <p><?php echo esc_html__('Add WooCommerce product IDs to courses or guides to populate this monetized product library.', 'algq-education-center'); ?></p>
            </article>
        <?php endif; ?>
    </div>
</section>
