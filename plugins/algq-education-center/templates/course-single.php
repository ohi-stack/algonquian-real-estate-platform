<?php
if (!defined('ABSPATH')) { exit; }
$course_id = isset($course) ? absint($course->ID) : 0;
$can_access = class_exists('ALGQ_Education_Access_Control') ? ALGQ_Education_Access_Control::can_access_post($course_id) : true;
$lessons = class_exists('ALGQ_Education_Progress') ? ALGQ_Education_Progress::course_lessons($course_id) : array();
$percent = (is_user_logged_in() && class_exists('ALGQ_Education_Progress')) ? ALGQ_Education_Progress::course_percentage(get_current_user_id(), $course_id) : 0;
$duration = get_post_meta($course_id, 'algq_course_duration', true);
$difficulty = get_post_meta($course_id, 'algq_course_difficulty', true);
$access = get_post_meta($course_id, 'algq_course_access_level', true);
?>
<section class="algq-edu algq-course-single">
    <header class="algq-section-header">
        <p class="algq-kicker"><?php echo esc_html__('Course', 'algq-education-center'); ?></p>
        <h1><?php echo esc_html(get_the_title($course_id)); ?></h1>
        <p><?php echo esc_html(get_the_excerpt($course_id) ? get_the_excerpt($course_id) : wp_trim_words(wp_strip_all_tags($course->post_content), 34)); ?></p>
    </header>
    <?php if (!$can_access) : ?>
        <?php echo class_exists('ALGQ_Education_WooCommerce') ? ALGQ_Education_WooCommerce::render_access_notice($course_id) : '<div class="algq-edu-notice">' . esc_html__('Access restricted.', 'algq-education-center') . '</div>'; ?>
    <?php else : ?>
        <div class="algq-meta algq-course-meta">
            <?php if ($duration) : ?><span><?php echo esc_html($duration); ?></span><?php endif; ?>
            <?php if ($difficulty) : ?><span><?php echo esc_html($difficulty); ?></span><?php endif; ?>
            <?php if ($access) : ?><span><?php echo esc_html($access); ?></span><?php endif; ?>
        </div>
        <div class="algq-progress"><span style="width:<?php echo esc_attr((string) $percent); ?>%"></span></div>
        <p><?php echo esc_html(sprintf(__('%d%% complete', 'algq-education-center'), $percent)); ?></p>
        <div class="algq-content"><?php echo wp_kses_post(wpautop($course->post_content)); ?></div>
        <h2><?php echo esc_html__('Lessons', 'algq-education-center'); ?></h2>
        <div class="algq-card-grid">
            <?php if ($lessons) : foreach ($lessons as $lesson) : ?>
                <article class="algq-card">
                    <h3><?php echo esc_html(get_the_title($lesson)); ?></h3>
                    <p><?php echo esc_html(get_the_excerpt($lesson) ? get_the_excerpt($lesson) : wp_trim_words(wp_strip_all_tags($lesson->post_content), 20)); ?></p>
                    <a class="algq-btn algq-btn-outline" href="<?php echo esc_url(add_query_arg('lesson_id', $lesson->ID, home_url('/education/courses'))); ?>"><?php echo esc_html__('Open Lesson', 'algq-education-center'); ?></a>
                </article>
            <?php endforeach; else : ?>
                <article class="algq-card"><p><?php echo esc_html__('No lessons are assigned to this course yet.', 'algq-education-center'); ?></p></article>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</section>
