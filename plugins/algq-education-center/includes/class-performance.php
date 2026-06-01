<?php
if (!defined('ABSPATH')) { exit; }

class ALGQ_Education_Performance {
    const KPI_TRANSIENT = 'algq_education_cached_kpis';
    const REVENUE_TRANSIENT = 'algq_education_cached_revenue';
    const TENANT_TRANSIENT_PREFIX = 'algq_education_tenant_metrics_';

    public static function init() {
        add_action('save_post', array(__CLASS__, 'flush_core_cache'));
        add_action('deleted_post', array(__CLASS__, 'flush_core_cache'));
        add_action('updated_user_meta', array(__CLASS__, 'flush_core_cache'));
        add_action('added_user_meta', array(__CLASS__, 'flush_core_cache'));
        add_action('deleted_user_meta', array(__CLASS__, 'flush_core_cache'));
        add_shortcode('algq_education_performance_status', array(__CLASS__, 'render_status'));
    }

    public static function cached_kpis($callback, $ttl = 300) {
        $cached = get_transient(self::KPI_TRANSIENT);
        if (false !== $cached) { return $cached; }
        $value = is_callable($callback) ? call_user_func($callback) : array();
        set_transient(self::KPI_TRANSIENT, $value, absint($ttl));
        return $value;
    }

    public static function cached_revenue($callback, $ttl = 600) {
        $cached = get_transient(self::REVENUE_TRANSIENT);
        if (false !== $cached) { return $cached; }
        $value = is_callable($callback) ? call_user_func($callback) : array('linked_products'=>0,'orders'=>0,'revenue'=>0);
        set_transient(self::REVENUE_TRANSIENT, $value, absint($ttl));
        return $value;
    }

    public static function cached_tenant_metrics($tenant_id, $callback, $ttl = 600) {
        $key = self::TENANT_TRANSIENT_PREFIX . absint($tenant_id);
        $cached = get_transient($key);
        if (false !== $cached) { return $cached; }
        $value = is_callable($callback) ? call_user_func($callback, absint($tenant_id)) : array();
        set_transient($key, $value, absint($ttl));
        return $value;
    }

    public static function paged_users($page = 1, $per_page = 50, $args = array()) {
        $page = max(1, absint($page));
        $per_page = max(1, min(200, absint($per_page)));
        return get_users(wp_parse_args($args, array(
            'number' => $per_page,
            'offset' => ($page - 1) * $per_page,
            'fields' => array('ID','display_name','user_email'),
        )));
    }

    public static function flush_core_cache() {
        delete_transient(self::KPI_TRANSIENT);
        delete_transient(self::REVENUE_TRANSIENT);
    }

    public static function render_status($atts = array()) {
        if (!current_user_can('manage_options')) {
            return '<div class="algq-edu-notice">' . esc_html__('Administrator access required.', 'algq-education-center') . '</div>';
        }
        ob_start();
        echo '<section class="algq-edu algq-performance-status"><header class="algq-section-header"><p class="algq-kicker">' . esc_html__('Performance', 'algq-education-center') . '</p><h1>' . esc_html__('Education Performance Cache', 'algq-education-center') . '</h1><p>' . esc_html__('Transient caching is enabled for KPI, revenue, and tenant reporting layers.', 'algq-education-center') . '</p></header><div class="algq-card-grid">';
        echo '<article class="algq-card"><h2>' . esc_html__('KPI Cache', 'algq-education-center') . '</h2><p>' . esc_html(false !== get_transient(self::KPI_TRANSIENT) ? __('Warm', 'algq-education-center') : __('Empty', 'algq-education-center')) . '</p></article>';
        echo '<article class="algq-card"><h2>' . esc_html__('Revenue Cache', 'algq-education-center') . '</h2><p>' . esc_html(false !== get_transient(self::REVENUE_TRANSIENT) ? __('Warm', 'algq-education-center') : __('Empty', 'algq-education-center')) . '</p></article>';
        echo '</div></section>';
        return ob_get_clean();
    }
}
