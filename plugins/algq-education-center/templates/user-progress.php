<?php
if (!defined('ABSPATH')) { exit; }
$user_id = isset($user_id) ? absint($user_id) : get_current_user_id();
$summary = class_exists('ALGQ_Education_Progress') ? ALGQ_Education_Progress::user_summary($user_id) : array('completed_lessons'=>0,'active_courses'=>0);
$courses = get_posts(array('post_type'=>'algq_course','post_status'=>'publish','posts_per_page'=>-1));
?>
<section class="algq-edu algq-user-progress">
    <header class="algq-section-header">
        <p class="algq-kicker"><?php echo esc_html__('Learning Dashboard', 'algq-education-center'); ?></p>
        <h1><?php echo esc_html__('My Learning Progress', 'algq-education-center'); ?></h1>
        <p><?php echo esc_html__('Track completed lessons, active courses, and course completion percentages.', 'algq-education-center'); ?></p>
    </header>
    <div class="algq-stat-grid">
        <div class="algq-stat"><strong><?php echo esc_html((string) $summary['completed_lessons']); ?></strong><span><?php echo esc_html__('Completed Lessons', 'algq-education-center'); ?></span></div>
        <div class="algq-stat"><strong><?php echo esc_html((string) $summary['active_courses']); ?></strong><span><?php echo esc_html__('Active Courses', 'algq-education-center'); ?></span></div>
    </div>
    <div class="algq-card-grid">
        <?php if ($courses) : foreach ($courses as $course) : ?>
            <?php $percent = class_exists('ALGQ_Education_Progress') ? ALGQ_Education_Progress::course_percentage($user_id, $course->ID) : 0; ?>
            <article class="algq-card">
                <h2><?php echo esc_html(get_the_title($course)); ?></h2>
                <div class="algq-progress"><span style="width:<?php echo esc_attr((string) $percent); ?>%"></span></div>
                <p><?php echo esc_html(sprintf(__('%d%% complete', 'algq-education-center'), $percent)); ?></p>
            </article>
        <?php endforeach; else : ?>
            <article class="algq-card"><h2><?php echo esc_html__('No courses available.', 'algq-education-center'); ?></h2></article>
        <?php endif; ?>
    </div>
</section>
