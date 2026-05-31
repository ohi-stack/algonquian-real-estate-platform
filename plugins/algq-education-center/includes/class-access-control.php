<?php
if (!defined('ABSPATH')) { exit; }

class ALGQ_Education_Access_Control {
    const DEFAULT_ACCESS = 'public';

    public static function init() {
        add_filter('algq_education_can_access', array(__CLASS__, 'filter_can_access'), 10, 4);
    }

    public static function can_access_post($post_id, $user_id = 0) {
        $post_id = absint($post_id);
        if (!$post_id) { return false; }

        $post_type = get_post_type($post_id);
        $key = 'algq_course_access_level';
        if ('algq_guide' === $post_type) { $key = 'algq_guide_access_level'; }
        if ('algq_lesson' === $post_type) {
            $course_id = absint(get_post_meta($post_id, 'algq_lesson_course_id', true));
            return $course_id ? self::can_access_post($course_id, $user_id) : is_user_logged_in();
        }

        $level = get_post_meta($post_id, $key, true);
        $level = $level ? sanitize_key($level) : self::DEFAULT_ACCESS;
        return self::can_access_level($level, $user_id, $post_id);
    }

    public static function can_access_level($level, $user_id = 0, $object_id = 0) {
        $level = sanitize_key($level ? $level : self::DEFAULT_ACCESS);
        $user_id = $user_id ? absint($user_id) : get_current_user_id();

        if ('public' === $level) { return true; }
        if ('registered' === $level) { return $user_id > 0; }
        if ('admin' === $level) { return current_user_can('manage_options'); }
        if ('internal' === $level) { return current_user_can('edit_posts') || current_user_can('manage_options'); }
        if ('buyer' === $level) { return self::user_has_role($user_id, array('algq_buyer', 'buyer', 'customer')) || current_user_can('manage_options'); }
        if ('lender' === $level) { return self::user_has_role($user_id, array('algq_lender', 'lender')) || current_user_can('manage_options'); }
        if ('paid' === $level) { return self::has_paid_access($user_id, $object_id) || current_user_can('manage_options'); }

        return apply_filters('algq_education_unknown_access_level', false, $level, $user_id, $object_id);
    }

    public static function user_has_role($user_id, $roles) {
        $user = get_userdata(absint($user_id));
        if (!$user) { return false; }
        return (bool) array_intersect((array) $roles, (array) $user->roles);
    }

    public static function has_paid_access($user_id, $object_id) {
        $user_id = absint($user_id);
        $object_id = absint($object_id);
        if (!$user_id || !$object_id) { return false; }

        $product_id = absint(get_post_meta($object_id, 'algq_course_product_id', true));
        if (!$product_id) { $product_id = absint(get_post_meta($object_id, 'algq_guide_product_id', true)); }
        if (!$product_id || !function_exists('wc_customer_bought_product')) { return false; }

        $user = get_userdata($user_id);
        if (!$user || empty($user->user_email)) { return false; }
        return wc_customer_bought_product($user->user_email, $user_id, $product_id);
    }

    public static function filter_can_access($allowed, $level, $user_id, $object_id) {
        return self::can_access_level($level, $user_id, $object_id);
    }

    public static function denial_message($level = '') {
        $level = sanitize_key($level);
        if ('paid' === $level) { return __('Purchase or membership access is required to view this education item.', 'algq-education-center'); }
        if ('internal' === $level) { return __('Internal access is required to view this education item.', 'algq-education-center'); }
        if ('lender' === $level) { return __('Approved lender access is required to view this education item.', 'algq-education-center'); }
        if ('buyer' === $level) { return __('Approved buyer access is required to view this education item.', 'algq-education-center'); }
        return __('You do not have access to view this education item.', 'algq-education-center');
    }
}
