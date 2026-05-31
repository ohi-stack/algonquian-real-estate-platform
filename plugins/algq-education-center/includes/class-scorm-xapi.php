<?php
if (!defined('ABSPATH')) { exit; }

class ALGQ_Education_SCORM_XAPI {
    public static function init() {
        add_action('init', array(__CLASS__, 'register_post_type'));
        add_shortcode('algq_scorm_package', array(__CLASS__, 'render_package'));
        add_action('wp_ajax_algq_xapi_statement', array(__CLASS__, 'ajax_statement'));
        add_action('wp_ajax_nopriv_algq_xapi_statement', array(__CLASS__, 'ajax_statement'));
    }

    public static function register_post_type() {
        register_post_type('algq_scorm_package', array(
            'labels' => array('name'=>__('SCORM / xAPI Packages','algq-education-center'),'singular_name'=>__('SCORM / xAPI Package','algq-education-center')),
            'public'=>false,'show_ui'=>true,'show_in_menu'=>'algq-education','supports'=>array('title','editor','revisions'),'rewrite'=>false
        ));
    }

    public static function render_package($atts = array()) {
        $atts = shortcode_atts(array('id'=>0), $atts, 'algq_scorm_package');
        $package_id = absint($atts['id']);
        if (!$package_id || 'algq_scorm_package' !== get_post_type($package_id)) {
            return '<div class="algq-edu-notice">' . esc_html__('Training package not found.', 'algq-education-center') . '</div>';
        }
        $launch_url = esc_url(get_post_meta($package_id, 'algq_scorm_launch_url', true));
        if (!$launch_url) {
            return '<div class="algq-edu-notice">' . esc_html__('Launch URL is not configured.', 'algq-education-center') . '</div>';
        }
        ob_start();
        echo '<section class="algq-edu algq-scorm-package"><header class="algq-section-header"><p class="algq-kicker">' . esc_html__('SCORM / xAPI', 'algq-education-center') . '</p><h1>' . esc_html(get_the_title($package_id)) . '</h1></header><div class="algq-content"><iframe title="' . esc_attr(get_the_title($package_id)) . '" src="' . esc_url($launch_url) . '" style="width:100%;min-height:720px;border:0"></iframe></div></section>';
        return ob_get_clean();
    }

    public static function record_statement($user_id, $verb, $object, $result = array()) {
        $user_id = absint($user_id);
        $statement = array(
            'user_id' => $user_id,
            'verb' => sanitize_key($verb),
            'object' => sanitize_text_field($object),
            'result' => is_array($result) ? array_map('sanitize_text_field', $result) : array(),
            'timestamp' => current_time('mysql'),
        );
        $log = get_user_meta($user_id, 'algq_xapi_statements', true);
        $log = is_array($log) ? $log : array();
        $log[] = $statement;
        update_user_meta($user_id, 'algq_xapi_statements', array_slice($log, -250));
        return $statement;
    }

    public static function ajax_statement() {
        if (!is_user_logged_in()) { wp_send_json_error(array('message'=>__('Login required.', 'algq-education-center')), 401); }
        check_ajax_referer('algq_xapi_statement', 'nonce');
        $verb = isset($_POST['verb']) ? sanitize_key($_POST['verb']) : '';
        $object = isset($_POST['object']) ? sanitize_text_field(wp_unslash($_POST['object'])) : '';
        if (!$verb || !$object) { wp_send_json_error(array('message'=>__('Invalid statement.', 'algq-education-center')), 400); }
        wp_send_json_success(self::record_statement(get_current_user_id(), $verb, $object));
    }
}
