<?php
if (!defined('ABSPATH')) { exit; }
$lesson_id = isset($lesson) ? absint($lesson->ID) : 0;
$course_id = absint(get_post_meta($lesson_id, 'algq_lesson_course_id', true));
$can_access = class_exists('ALGQ_Education_Access_Control') ? ALGQ_Education_Access_Control::can_access_post($lesson_id) : true;
$video_url = get_post_meta($lesson_id, 'algq_lesson_video_url', true);
$download_url = get_post_meta($lesson_id, 'algq_lesson_download_url', true);
$is_complete = (is_user_logged_in() && class_exists('ALGQ_Education_Progress')) ? ALGQ_Education_Progress::is_complete(get_current_user_id(), $course_id, $lesson_id) : false;
?>
<section class="algq-edu algq-lesson-single" data-course-id="<?php echo esc_attr((string) $course_id); ?>" data-lesson-id="<?php echo esc_attr((string) $lesson_id); ?>">
    <header class="algq-section-header">
        <p class="algq-kicker"><?php echo esc_html__('Lesson', 'algq-education-center'); ?></p>
        <h1><?php echo esc_html(get_the_title($lesson_id)); ?></h1>
        <?php if ($course_id) : ?><p><?php echo esc_html(sprintf(__('Course ID: %d', 'algq-education-center'), $course_id)); ?></p><?php endif; ?>
    </header>
    <?php if (!$can_access) : ?>
        <div class="algq-edu-notice"><?php echo esc_html__('Access restricted. Complete registration, approval, or purchase requirements to view this lesson.', 'algq-education-center'); ?></div>
    <?php else : ?>
        <?php if ($video_url) : ?>
            <div class="algq-video"><a class="algq-btn algq-btn-outline" href="<?php echo esc_url($video_url); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html__('Open Lesson Video', 'algq-education-center'); ?></a></div>
        <?php endif; ?>
        <div class="algq-content"><?php echo wp_kses_post(wpautop($lesson->post_content)); ?></div>
        <div class="algq-actions">
            <?php if ($download_url) : ?><a class="algq-btn algq-btn-outline" href="<?php echo esc_url($download_url); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html__('Download Resource', 'algq-education-center'); ?></a><?php endif; ?>
            <?php if (is_user_logged_in() && $course_id) : ?>
                <button type="button" class="algq-btn algq-btn-gold algq-complete-lesson" data-status="<?php echo esc_attr($is_complete ? 'complete' : 'incomplete'); ?>"><?php echo esc_html($is_complete ? __('Mark Incomplete', 'algq-education-center') : __('Mark Complete', 'algq-education-center')); ?></button>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</section>
