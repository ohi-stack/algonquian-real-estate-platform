<?php
if (!defined('ABSPATH')) { exit; }

class ALGQ_Offer_Role_Capabilities {
    public static function init() {}

    public static function install_roles() {
        add_role('algq_offer_manager', __('ARE Offer Manager', 'algq-offer-generator'), array(
            'read' => true,
            'edit_posts' => true,
            'upload_files' => true,
            'manage_algq_offers' => true,
        ));
        foreach (array('administrator', 'editor') as $role_name) {
            $role = get_role($role_name);
            if ($role) {
                $role->add_cap('manage_algq_offers');
            }
        }
    }
}
