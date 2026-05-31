<?php
if (!defined('ABSPATH')) { exit; }

class ALGQ_Education_Tenant_Manager {
    public static function init() {
        add_action('init', array(__CLASS__, 'register_post_type'));
        add_shortcode('algq_tenant_dashboard', array(__CLASS__, 'render_dashboard'));
    }

    public static function register_post_type() {
        register_post_type('algq_lms_tenant', array(
            'labels' => array('name'=>__('LMS Tenants','algq-education-center'),'singular_name'=>__('LMS Tenant','algq-education-center')),
            'public'=>false,
            'show_ui'=>true,
            'show_in_menu'=>'algq-education',
            'supports'=>array('title','editor','thumbnail','revisions'),
            'rewrite'=>false,
        ));
    }

    public static function tenant_for_user($user_id) {
        $user_id = absint($user_id);
        if (!$user_id) { return 0; }
        return absint(get_user_meta($user_id, 'algq_lms_tenant_id', true));
    }

    public static function assign_user($user_id, $tenant_id) {
        $user_id = absint($user_id);
        $tenant_id = absint($tenant_id);
        if (!$user_id || !$tenant_id || 'algq_lms_tenant' !== get_post_type($tenant_id)) { return false; }
        update_user_meta($user_id, 'algq_lms_tenant_id', $tenant_id);
        if (class_exists('ALGQ_Education_Audit_Log')) {
            ALGQ_Education_Audit_Log::record('tenant_user_assigned', 'tenant', $tenant_id, 'User assigned to LMS tenant.', $user_id);
        }
        return true;
    }

    public static function tenant_courses($tenant_id) {
        $raw = get_post_meta(absint($tenant_id), 'algq_tenant_course_ids', true);
        return array_filter(array_map('absint', explode(',', (string) $raw)));
    }

    public static function tenant_users($tenant_id) {
        return get_users(array(
            'meta_key' => 'algq_lms_tenant_id',
            'meta_value' => absint($tenant_id),
            'fields' => array('ID','display_name','user_email'),
            'number' => 500,
        ));
    }

    public static function tenant_metrics($tenant_id) {
        $users = self::tenant_users($tenant_id);
        $courses = self::tenant_courses($tenant_id);
        $complete = 0;
        $total = 0;
        foreach ($users as $user) {
            foreach ($courses as $course_id) {
                $total++;
                if (class_exists('ALGQ_Education_Progress') && ALGQ_Education_Progress::course_percentage($user->ID, $course_id) >= 100) { $complete++; }
            }
        }
        return array(
            'users' => count($users),
            'courses' => count($courses),
            'assigned_records' => $total,
            'completed_records' => $complete,
            'completion_rate' => $total ? (int) round(($complete / $total) * 100) : 0,
        );
    }

    public static function render_dashboard($atts = array()) {
        if (!current_user_can('manage_options')) {
            return '<div class="algq-edu-notice">' . esc_html__('Administrator access required.', 'algq-education-center') . '</div>';
        }
        $tenants = get_posts(array('post_type'=>'algq_lms_tenant','post_status'=>'publish','posts_per_page'=>50));
        ob_start();
        echo '<section class="algq-edu algq-tenant-dashboard"><header class="algq-section-header"><p class="algq-kicker">' . esc_html__('Tenant Management', 'algq-education-center') . '</p><h1>' . esc_html__('LMS Tenant Dashboard', 'algq-education-center') . '</h1><p>' . esc_html__('Manage multi-organization LMS deployment, tenant users, tenant courses, and completion reporting.', 'algq-education-center') . '</p></header><div class="algq-card-grid">';
        if ($tenants) {
            foreach ($tenants as $tenant) {
                $m = self::tenant_metrics($tenant->ID);
                echo '<article class="algq-card"><span class="algq-badge">' . esc_html__('Tenant', 'algq-education-center') . '</span><h2>' . esc_html(get_the_title($tenant)) . '</h2><div class="algq-meta"><span>' . esc_html($m['users'] . ' users') . '</span><span>' . esc_html($m['courses'] . ' courses') . '</span><span>' . esc_html($m['completion_rate'] . '% complete') . '</span></div><div class="algq-progress"><span style="width:' . esc_attr((string) $m['completion_rate']) . '%"></span></div></article>';
            }
        } else {
            echo '<article class="algq-card"><h2>' . esc_html__('No tenants configured.', 'algq-education-center') . '</h2><p>' . esc_html__('Create an LMS Tenant to begin multi-organization training deployment.', 'algq-education-center') . '</p></article>';
        }
        echo '</div></section>';
        return ob_get_clean();
    }
}
