<?php
if (!defined('ABSPATH')) { exit; }

class ALGQ_Education_Certificate_Verification {
    public static function init() {
        add_shortcode('algq_certificate_verify', array(__CLASS__, 'verify_shortcode'));
        add_action('init', array(__CLASS__, 'add_rewrite'));
        add_filter('query_vars', array(__CLASS__, 'query_vars'));
        add_action('template_redirect', array(__CLASS__, 'maybe_render_verification'));
    }

    public static function add_rewrite() {
        add_rewrite_rule('^education/certificate/verify/([^/]+)/?', 'index.php?algq_certificate_code=$matches[1]', 'top');
    }

    public static function query_vars($vars) {
        $vars[] = 'algq_certificate_code';
        return $vars;
    }

    public static function code_for_certificate($certificate_id) {
        $certificate_id = absint($certificate_id);
        $code = get_post_meta($certificate_id, 'algq_certificate_code', true);
        if (!$code) {
            $code = 'ALGQ-' . $certificate_id . '-' . strtoupper(wp_generate_password(8, false, false));
            update_post_meta($certificate_id, 'algq_certificate_code', sanitize_text_field($code));
        }
        return $code;
    }

    public static function verification_url($certificate_id) {
        return home_url('/education/certificate/verify/' . rawurlencode(self::code_for_certificate($certificate_id)) . '/');
    }

    public static function find_certificate($code) {
        $code = sanitize_text_field($code);
        $posts = get_posts(array('post_type'=>'algq_certificate','post_status'=>'publish','posts_per_page'=>1,'meta_key'=>'algq_certificate_code','meta_value'=>$code));
        return $posts ? $posts[0] : null;
    }

    public static function verify_shortcode($atts = array()) {
        $atts = shortcode_atts(array('code'=>''), $atts, 'algq_certificate_verify');
        return self::render_result(sanitize_text_field($atts['code']));
    }

    public static function maybe_render_verification() {
        $code = get_query_var('algq_certificate_code');
        if (!$code) { return; }
        status_header(200);
        nocache_headers();
        echo '<!doctype html><html><head><meta charset="utf-8"><title>Certificate Verification</title></head><body>';
        echo self::render_result($code);
        echo '</body></html>';
        exit;
    }

    private static function render_result($code) {
        $cert = self::find_certificate($code);
        ob_start();
        echo '<section class="algq-edu algq-certificate-verify"><header class="algq-section-header"><p class="algq-kicker">' . esc_html__('Credential Registry', 'algq-education-center') . '</p><h1>' . esc_html__('Certificate Verification', 'algq-education-center') . '</h1></header>';
        if (!$cert) {
            echo '<div class="algq-edu-notice">' . esc_html__('Certificate not found or not yet issued.', 'algq-education-center') . '</div></section>';
            return ob_get_clean();
        }
        $user_id = absint(get_post_meta($cert->ID, 'algq_certificate_user_id', true));
        $course_id = absint(get_post_meta($cert->ID, 'algq_certificate_course_id', true));
        $issued = get_post_meta($cert->ID, 'algq_certificate_issued_at', true);
        $user = get_userdata($user_id);
        echo '<article class="algq-card"><span class="algq-badge">' . esc_html__('Verified', 'algq-education-center') . '</span><h2>' . esc_html(get_the_title($cert)) . '</h2><p><strong>' . esc_html__('Student:', 'algq-education-center') . '</strong> ' . esc_html($user ? $user->display_name : __('Student', 'algq-education-center')) . '</p><p><strong>' . esc_html__('Course:', 'algq-education-center') . '</strong> ' . esc_html(get_the_title($course_id)) . '</p><p><strong>' . esc_html__('Issued:', 'algq-education-center') . '</strong> ' . esc_html($issued) . '</p><p><strong>' . esc_html__('Verification Code:', 'algq-education-center') . '</strong> ' . esc_html($code) . '</p></article></section>';
        return ob_get_clean();
    }
}
