<?php
if (!defined('ABSPATH')) { exit; }
?>
<section class="algq-edu algq-course-list">
    <header class="algq-section-header">
        <p class="algq-kicker"><?php echo esc_html__('LMS Catalog', 'algq-education-center'); ?></p>
        <h1><?php echo esc_html__('Course Library', 'algq-education-center'); ?></h1>
        <p><?php echo esc_html__('Browse structured training for acquisition, underwriting, buyer readiness, lender preparation, and ARE platform operations.', 'algq-education-center'); ?></p>
    </header>
    <div class="algq-card-grid">
        <?php if (isset($courses) && $courses->have_posts()) : ?>
            <?php while ($courses->have_posts()) : $courses->the_post(); ?>
                <?php
                $course_id = get_the_ID();
                $access = get_post_meta($course_id, 'algq_course_access_level', true);
                $duration = get_post_meta($course_id, 'algq_course_duration', true);
                $difficulty = get_post_meta($course_id, 'algq_course_difficulty', true);
                $product_id = absint(get_post_meta($course_id, 'algq_course_product_id', true));
                ?>
                <article class="algq-card algq-course-card">
                    <span class="algq-badge"><?php echo esc_html($access ? $access : __('Public', 'algq-education-center')); ?></span>
                    <h2><?php the_title(); ?></h2>
                    <p><?php echo esc_html(get_the_excerpt() ? get_the_excerpt() : wp_trim_words(wp_strip_all_tags(get_the_content()), 24)); ?></p>
                    <div class="algq-meta">
                        <?php if ($duration) : ?><span><?php echo esc_html($duration); ?></span><?php endif; ?>
                        <?php if ($difficulty) : ?><span><?php echo esc_html($difficulty); ?></span><?php endif; ?>
                    </div>
                    <div class="algq-actions">
                        <?php if ($product_id && class_exists('ALGQ_Education_WooCommerce')) : ?>
                            <?php echo apply_filters('algq_education_product_button', '', $product_id, __('Get Access', 'algq-education-center')); ?>
                        <?php else : ?>
                            <a class="algq-btn algq-btn-gold" href="<?php echo esc_url(add_query_arg('course_id', $course_id, home_url('/education/courses'))); ?>"><?php echo esc_html__('View Course', 'algq-education-center'); ?></a>
                        <?php endif; ?>
                    </div>
                </article>
            <?php endwhile; wp_reset_postdata(); ?>
        <?php else : ?>
            <article class="algq-card"><h2><?php echo esc_html__('No courses published yet.', 'algq-education-center'); ?></h2><p><?php echo esc_html__('Create a course in the Algonquian Education admin menu to populate this catalog.', 'algq-education-center'); ?></p></article>
        <?php endif; ?>
    </div>
</section>
