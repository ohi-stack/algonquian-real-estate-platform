<?php
if (!defined('ABSPATH')) { exit; }

class ALGQ_Education_Corporate_Accounts {
    public static function init() {
        add_action('init', array(__CLASS__, 'register_post_type'));
        add_shortcode('algq_corporate_training_dashboard', array(__CLASS__, 'render_dashboard'));
    }

    public static function register_post_type() {
        register_post_type('algq_corporate_account', array(
            'labels' => array('name'=>__('Corporate Training Accounts','algq-education-center'),'singular_name'=>__('Corporate Training Account','algq-education-center')),
            'public'=>false,'show_ui'=>true,'show_in_menu'=>'algq-education','supports'=>array('title','editor','revisions'),'rewrite'=>false
        ));
    }

    public static function learner_ids($account_id) {
        $raw = get_post_meta(absint($account_id), 'algq_corporate_learner_ids', true);
        return array_filter(array_map('absint', explode(',', (string) $raw)));
    }

    public static function course_ids($account_id) {
        $raw = get_post_meta(absint($account_id), 'algq_corporate_course_ids', true);
        return array_filter(array_map('absint', explode(',', (string) $raw)));
    }

    public static function account_summary($account_id) {
        $learners = self::learner_ids($account_id);
        $courses = self::course_ids($account_id);
        $complete = 0; $total = 0;
        if (class_exists('ALGQ_Education_Progress')) {
            foreach ($learners as $user_id) {
                foreach ($courses as $course_id) {
                    $total++;
                    if (ALGQ_Education_Progress::course_percentage($user_id, $course_id) >= 100) { $complete++; }
                }
            }
        }
        return array('learners'=>count($learners),'courses'=>count($courses),'complete'=>$complete,'total'=>$total,'completion_rate'=>$total ? (int) round(($complete/$total)*100) : 0);
    }

    public static function render_dashboard($atts = array()) {
        if (!current_user_can('manage_options')) { return '<div class="algq-edu-notice">' . esc_html__('Administrator access required.', 'algq-education-center') . '</div>'; }
        $accounts = get_posts(array('post_type'=>'algq_corporate_account','post_status'=>'publish','posts_per_page'=>50));
        ob_start();
        echo '<section class="algq-edu algq-corporate-dashboard"><header class="algq-section-header"><p class="algq-kicker">' . esc_html__('Corporate Training','algq-education-center') . '</p><h1>' . esc_html__('Corporate Training Accounts','algq-education-center') . '</h1><p>' . esc_html__('Manage organization-level learning programs, team enrollments, and progress reporting.', 'algq-education-center') . '</p></header><div class="algq-card-grid">';
        foreach ($accounts as $account) {
            $s = self::account_summary($account->ID);
            echo '<article class="algq-card"><span class="algq-badge">' . esc_html__('Organization','algq-education-center') . '</span><h2>' . esc_html(get_the_title($account)) . '</h2><div class="algq-meta"><span>' . esc_html($s['learners'] . ' learners') . '</span><span>' . esc_html($s['courses'] . ' courses') . '</span><span>' . esc_html($s['completion_rate'] . '% complete') . '</span></div><div class="algq-progress"><span style="width:' . esc_attr((string) $s['completion_rate']) . '%"></span></div></article>';
        }
        if (!$accounts) { echo '<article class="algq-card"><h2>' . esc_html__('No corporate accounts yet.', 'algq-education-center') . '</h2><p>' . esc_html__('Create a Corporate Training Account to begin team-level LMS reporting.', 'algq-education-center') . '</p></article>'; }
        echo '</div></section>';
        return ob_get_clean();
    }
}
