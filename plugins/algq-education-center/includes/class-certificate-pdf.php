<?php
if (!defined('ABSPATH')) { exit; }

class ALGQ_Education_Certificate_PDF {
    public static function init() {
        add_shortcode('algq_certificate_download', array(__CLASS__, 'download_shortcode'));
        add_action('admin_post_algq_download_certificate', array(__CLASS__, 'download_certificate'));
        add_action('admin_post_nopriv_algq_download_certificate', array(__CLASS__, 'download_certificate'));
    }

    public static function download_shortcode($atts = array()) {
        $atts = shortcode_atts(array('course_id'=>0), $atts, 'algq_certificate_download');
        $course_id = absint($atts['course_id']);
        if (!is_user_logged_in() || !$course_id) { return ''; }
        if (!class_exists('ALGQ_Education_Progress') || ALGQ_Education_Progress::course_percentage(get_current_user_id(), $course_id) < 100) {
            return '<div class="algq-edu-notice">' . esc_html__('Complete the course to unlock the certificate.', 'algq-education-center') . '</div>';
        }
        $url = wp_nonce_url(admin_url('admin-post.php?action=algq_download_certificate&course_id=' . $course_id), 'algq_download_certificate_' . $course_id);
        return '<a class="algq-btn algq-btn-gold" href="' . esc_url($url) . '">' . esc_html__('Download Certificate', 'algq-education-center') . '</a>';
    }

    public static function download_certificate() {
        if (!is_user_logged_in()) { wp_die(esc_html__('Login required.', 'algq-education-center')); }
        $course_id = isset($_GET['course_id']) ? absint($_GET['course_id']) : 0;
        if (!$course_id || !wp_verify_nonce(isset($_GET['_wpnonce']) ? sanitize_text_field(wp_unslash($_GET['_wpnonce'])) : '', 'algq_download_certificate_' . $course_id)) {
            wp_die(esc_html__('Invalid certificate request.', 'algq-education-center'));
        }
        if (!class_exists('ALGQ_Education_Progress') || ALGQ_Education_Progress::course_percentage(get_current_user_id(), $course_id) < 100) {
            wp_die(esc_html__('Course is not complete.', 'algq-education-center'));
        }
        if (class_exists('ALGQ_Education_LMS_Advanced')) { ALGQ_Education_LMS_Advanced::issue_certificate(get_current_user_id(), $course_id); }
        self::render_html_certificate(get_current_user_id(), $course_id);
    }

    public static function render_html_certificate($user_id, $course_id) {
        $user = get_userdata(absint($user_id));
        $course_title = get_the_title(absint($course_id));
        header('Content-Type: text/html; charset=utf-8');
        header('Content-Disposition: attachment; filename="algq-certificate-course-' . absint($course_id) . '.html"');
        echo '<!doctype html><html><head><meta charset="utf-8"><title>Certificate</title><style>body{font-family:Georgia,serif;background:#071a33;padding:60px}.cert{background:#fff;border:12px solid #c9a34a;padding:70px;text-align:center}.k{color:#c9a34a;letter-spacing:.18em;text-transform:uppercase}.n{font-size:42px;color:#071a33}.c{font-size:28px}.d{margin-top:40px;color:#555}</style></head><body><div class="cert"><div class="k">Algonquian Real Estate Education Center</div><h1>Certificate of Completion</h1><p>This certifies that</p><div class="n">' . esc_html($user ? $user->display_name : __('Student', 'algq-education-center')) . '</div><p>has completed</p><div class="c">' . esc_html($course_title) . '</div><div class="d">Issued ' . esc_html(date_i18n(get_option('date_format'), current_time('timestamp'))) . '</div></div></body></html>';
        exit;
    }
}
