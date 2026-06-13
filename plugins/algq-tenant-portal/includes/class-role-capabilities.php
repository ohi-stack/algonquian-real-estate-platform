<?php
if (!defined('ABSPATH')) { exit; }

class ALGQ_Tenant_Role_Capabilities {
    public static function init() {
        add_shortcode('algq_tenant_roles_report', array(__CLASS__, 'render_roles_report'));
    }

    public static function install_roles() {
        add_role('algq_tenant', __('ARE Tenant', 'algq-tenant-portal'), array(
            'read' => true,
            'algq_view_tenant_portal' => true,
            'algq_pay_rent' => true,
            'algq_view_own_lease' => true,
            'algq_submit_maintenance' => true,
            'algq_view_own_documents' => true,
            'algq_message_management' => true,
        ));

        add_role('algq_applicant', __('ARE Rental Applicant', 'algq-tenant-portal'), array(
            'read' => true,
            'algq_submit_application' => true,
            'algq_view_application_status' => true,
        ));

        add_role('algq_property_manager', __('ARE Property Manager', 'algq-tenant-portal'), array(
            'read' => true,
            'upload_files' => true,
            'algq_manage_properties' => true,
            'algq_manage_units' => true,
            'algq_manage_leases' => true,
            'algq_manage_tenants' => true,
            'algq_manage_rent_payments' => true,
            'algq_manage_maintenance' => true,
            'algq_manage_applications' => true,
            'algq_manage_tenant_documents' => true,
            'algq_view_tenant_reports' => true,
        ));

        $admin = get_role('administrator');
        if ($admin) {
            foreach (self::all_caps() as $cap) {
                $admin->add_cap($cap);
            }
        }
    }

    public static function all_caps() {
        return array(
            'algq_view_tenant_portal',
            'algq_pay_rent',
            'algq_view_own_lease',
            'algq_submit_maintenance',
            'algq_view_own_documents',
            'algq_message_management',
            'algq_submit_application',
            'algq_view_application_status',
            'algq_manage_properties',
            'algq_manage_units',
            'algq_manage_leases',
            'algq_manage_tenants',
            'algq_manage_rent_payments',
            'algq_manage_maintenance',
            'algq_manage_applications',
            'algq_manage_tenant_documents',
            'algq_view_tenant_reports',
            'algq_manage_tenant_settings',
        );
    }

    public static function can_manage() {
        return current_user_can('algq_manage_tenants') || current_user_can('manage_options');
    }

    public static function render_roles_report() {
        if (!current_user_can('manage_options')) {
            return '<div class="algq-notice">' . esc_html__('Administrator access required.', 'algq-tenant-portal') . '</div>';
        }
        ob_start();
        echo '<section class="algq-ui algq-tenant-roles"><div class="algq-hero"><p class="algq-kicker">' . esc_html__('Access Control', 'algq-tenant-portal') . '</p><h1>' . esc_html__('Tenant Portal Roles', 'algq-tenant-portal') . '</h1><p>' . esc_html__('Role and capability framework for tenants, applicants, property managers, and administrators.', 'algq-tenant-portal') . '</p></div><div class="algq-grid">';
        $roles = array('algq_tenant' => 'Tenant', 'algq_applicant' => 'Rental Applicant', 'algq_property_manager' => 'Property Manager');
        foreach ($roles as $role_key => $label) {
            $role = get_role($role_key);
            echo '<article class="algq-card"><span class="algq-badge">' . esc_html($role_key) . '</span><h2>' . esc_html($label) . '</h2><p>' . esc_html($role ? __('Installed', 'algq-tenant-portal') : __('Not installed', 'algq-tenant-portal')) . '</p></article>';
        }
        echo '</div></section>';
        return ob_get_clean();
    }
}
