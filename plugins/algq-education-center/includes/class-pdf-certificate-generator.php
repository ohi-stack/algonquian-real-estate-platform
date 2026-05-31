<?php
if (!defined('ABSPATH')) { exit; }

class ALGQ_Education_PDF_Certificate_Generator {
    public static function init() {
        add_shortcode('algq_pdf_certificate_download', array(__CLASS__, 'download_shortcode'));
        add_action('admin_post_algq_download_pdf_certificate', array(__CLASS__, 'download_certificate'));
    }

    public static function download_shortcode($atts = array()) {
        $atts = shortcode_atts(array('course_id'=>0), $atts, 'algq_pdf_certificate_download');
        $course_id = absint($atts['course_id']);
        if (!is_user_logged_in() || !$course_id) { return ''; }
        if (!class_exists('ALGQ_Education_Progress') || ALGQ_Education_Progress::course_percentage(get_current_user_id(), $course_id) < 100) {
            return '<div class="algq-edu-notice">' . esc_html__('Complete the course to unlock the PDF certificate.', 'algq-education-center') . '</div>';
        }
        $url = wp_nonce_url(admin_url('admin-post.php?action=algq_download_pdf_certificate&course_id=' . $course_id), 'algq_download_pdf_certificate_' . $course_id);
        return '<a class="algq-btn algq-btn-gold" href="' . esc_url($url) . '">' . esc_html__('Download PDF Certificate', 'algq-education-center') . '</a>';
    }

    public static function download_certificate() {
        if (!is_user_logged_in()) { wp_die(esc_html__('Login required.', 'algq-education-center')); }
        $course_id = isset($_GET['course_id']) ? absint($_GET['course_id']) : 0;
        $nonce = isset($_GET['_wpnonce']) ? sanitize_text_field(wp_unslash($_GET['_wpnonce'])) : '';
        if (!$course_id || !wp_verify_nonce($nonce, 'algq_download_pdf_certificate_' . $course_id)) { wp_die(esc_html__('Invalid certificate request.', 'algq-education-center')); }
        if (!class_exists('ALGQ_Education_Progress') || ALGQ_Education_Progress::course_percentage(get_current_user_id(), $course_id) < 100) { wp_die(esc_html__('Course is not complete.', 'algq-education-center')); }

        $certificate_id = 0;
        if (class_exists('ALGQ_Education_LMS_Advanced')) { $certificate_id = ALGQ_Education_LMS_Advanced::issue_certificate(get_current_user_id(), $course_id); }
        if (!$certificate_id) { wp_die(esc_html__('Certificate could not be issued.', 'algq-education-center')); }

        $code = class_exists('ALGQ_Education_Certificate_Verification') ? ALGQ_Education_Certificate_Verification::code_for_certificate($certificate_id) : ('ALGQ-' . $certificate_id);
        $verify_url = class_exists('ALGQ_Education_Certificate_Verification') ? ALGQ_Education_Certificate_Verification::verification_url($certificate_id) : home_url('/education/certificate/verify/' . rawurlencode($code));
        if (class_exists('ALGQ_Education_Audit_Log')) { ALGQ_Education_Audit_Log::record('certificate_pdf_downloaded', 'certificate', $certificate_id, 'PDF certificate downloaded.'); }
        self::render_pdf($certificate_id, get_current_user_id(), $course_id, $code, $verify_url);
    }

    private static function render_pdf($certificate_id, $user_id, $course_id, $code, $verify_url) {
        $user = get_userdata(absint($user_id));
        $student = $user ? $user->display_name : __('Student', 'algq-education-center');
        $course = get_the_title(absint($course_id));
        $issued = date_i18n(get_option('date_format'), current_time('timestamp'));
        $issuer = class_exists('ALGQ_Education_Admin_Settings_Framework') ? ALGQ_Education_Admin_Settings_Framework::get('certificate_issuer_name') : 'Algonquian Real Estate Education Center';
        $qr = 'https://api.qrserver.com/v1/create-qr-code/?size=140x140&data=' . rawurlencode($verify_url);
        $html = '<!doctype html><html><head><meta charset="utf-8"><style>body{font-family:DejaVu Sans,Arial,sans-serif;background:#071a33;margin:0;padding:42px}.cert{background:#fff;border:10px solid #c9a34a;padding:60px;text-align:center;color:#071a33}.kicker{color:#9b7b28;letter-spacing:3px;text-transform:uppercase;font-size:12px}.title{font-size:42px;margin:18px 0}.student{font-size:34px;font-weight:700;margin:20px 0;color:#111827}.course{font-size:24px;margin:18px 0}.meta{margin-top:34px;color:#374151;font-size:13px}.qr{margin-top:26px}.small{font-size:11px;color:#6b7280}</style></head><body><div class="cert"><div class="kicker">' . esc_html($issuer) . '</div><div class="title">Certificate of Completion</div><p>This certifies that</p><div class="student">' . esc_html($student) . '</div><p>has successfully completed</p><div class="course">' . esc_html($course) . '</div><div class="meta">Issued: ' . esc_html($issued) . '<br>Certificate ID: ' . esc_html((string) $certificate_id) . '<br>Verification Code: ' . esc_html($code) . '</div><div class="qr"><img src="' . esc_url($qr) . '" alt="QR Verification Code"></div><p class="small">Verify this credential at: ' . esc_html($verify_url) . '</p></div></body></html>';

        if (class_exists('Dompdf\\Dompdf')) {
            $dompdf = new Dompdf\\Dompdf();
            $dompdf->loadHtml($html);
            $dompdf->setPaper('letter', 'landscape');
            $dompdf->render();
            $dompdf->stream('algq-certificate-' . absint($certificate_id) . '.pdf', array('Attachment'=>true));
            exit;
        }

        header('Content-Type: text/html; charset=utf-8');
        header('Content-Disposition: attachment; filename="algq-certificate-' . absint($certificate_id) . '.html"');
        echo $html;
        exit;
    }
}
