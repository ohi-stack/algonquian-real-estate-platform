<?php
if (!defined('ABSPATH')) { exit; }

class ALGQ_Education_Role_Capabilities {
    public static function init() {
        add_shortcode('algq_education_roles_report', array(__CLASS__, 'render_roles_report'));
    }

    public static function roles() {
        return array(
            'algq_student' => array(
                'label' => __('Algonquian Student', 'algq-education-center'),
                'caps' => array('read' => true, 'algq_view_courses' => true, 'algq_submit_assignments' => true),
            ),
            'algq_instructor' => array(
                'label' => __('Algonquian Instructor', 'algq-education-center'),
                'caps' => array('read' => true, 'edit_posts' => true, 'algq_manage_courses' => true, 'algq_grade_assignments' => true, 'algq_view_gradebook' => true),
            ),
            'algq_training_manager' => array(
                'label' => __('Algonquian Training Manager', 'algq-education-center'),
                'caps' => array('read' => true, 'edit_posts' => true, 'algq_manage_courses' => true, 'algq_manage_students' => true, 'algq_view_reports' => true, 'algq_manage_corporate_accounts' => true),
            ),
        );
    }

    public static function install_roles() {
        foreach (self::roles() as $role => $data) {
            if (!get_role($role)) { add_role($role, $data['label'], $data['caps']); }
            else {
                $wp_role = get_role($role);
                foreach ($data['caps'] as $cap => $grant) { $grant ? $wp_role->add_cap($cap) : $wp_role->remove_cap($cap); }
            }
        }
        $admin = get_role('administrator');
        if ($admin) {
            foreach (array('algq_view_courses','algq_submit_assignments','algq_manage_courses','algq_grade_assignments','algq_view_gradebook','algq_manage_students','algq_view_reports','algq_manage_corporate_accounts') as $cap) { $admin->add_cap($cap); }
        }
    }

    public static function remove_roles() {
        foreach (array_keys(self::roles()) as $role) { remove_role($role); }
    }

    public static function render_roles_report($atts = array()) {
        if (!current_user_can('manage_options')) { return '<div class="algq-edu-notice">' . esc_html__('Administrator access required.', 'algq-education-center') . '</div>'; }
        ob_start();
        echo '<section class="algq-edu algq-roles-report"><header class="algq-section-header"><p class="algq-kicker">' . esc_html__('Security', 'algq-education-center') . '</p><h1>' . esc_html__('Role & Capability Matrix', 'algq-education-center') . '</h1></header><div class="algq-card-grid">';
        foreach (self::roles() as $role => $data) {
            echo '<article class="algq-card"><span class="algq-badge">' . esc_html($role) . '</span><h2>' . esc_html($data['label']) . '</h2><p>' . esc_html(implode(', ', array_keys(array_filter($data['caps'])))) . '</p></article>';
        }
        echo '</div></section>';
        return ob_get_clean();
    }
}
