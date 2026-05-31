<?php
if (!defined('ABSPATH')) { exit; }

class ALGQ_Education_Quiz_Builder {
    public static function init() {
        add_action('init', array(__CLASS__, 'register_question_bank'));
        add_action('add_meta_boxes', array(__CLASS__, 'add_meta_boxes'));
        add_action('save_post_algq_question', array(__CLASS__, 'save_question_meta'), 10, 2);
        add_action('save_post_algq_quiz', array(__CLASS__, 'save_quiz_meta'), 10, 2);
        add_shortcode('algq_quiz', array(__CLASS__, 'render_quiz'));
    }

    public static function register_question_bank() {
        register_post_type('algq_question', array(
            'labels' => array('name'=>__('Question Bank','algq-education-center'),'singular_name'=>__('Question','algq-education-center')),
            'public'=>false,
            'show_ui'=>true,
            'show_in_menu'=>'algq-education',
            'supports'=>array('title','editor','revisions'),
            'rewrite'=>false
        ));
    }

    public static function add_meta_boxes() {
        add_meta_box('algq_question_details', __('Question Details','algq-education-center'), array(__CLASS__, 'render_question_meta'), 'algq_question', 'normal', 'high');
        add_meta_box('algq_quiz_builder', __('Quiz Builder','algq-education-center'), array(__CLASS__, 'render_quiz_meta'), 'algq_quiz', 'normal', 'high');
    }

    public static function render_question_meta($post) {
        wp_nonce_field('algq_save_question_meta', 'algq_question_nonce');
        $type = get_post_meta($post->ID, 'algq_question_type', true);
        $answers = get_post_meta($post->ID, 'algq_question_answers', true);
        $correct = get_post_meta($post->ID, 'algq_question_correct_answer', true);
        echo '<p><label><strong>' . esc_html__('Question Type','algq-education-center') . '</strong></label><br><select name="algq_question_type"><option value="multiple_choice" ' . selected($type, 'multiple_choice', false) . '>Multiple Choice</option><option value="true_false" ' . selected($type, 'true_false', false) . '>True / False</option></select></p>';
        echo '<p><label><strong>' . esc_html__('Answers','algq-education-center') . '</strong></label><br><textarea class="widefat" rows="6" name="algq_question_answers">' . esc_textarea($answers) . '</textarea><span class="description">One answer per line.</span></p>';
        echo '<p><label><strong>' . esc_html__('Correct Answer','algq-education-center') . '</strong></label><br><input class="widefat" type="text" name="algq_question_correct_answer" value="' . esc_attr($correct) . '"></p>';
    }

    public static function render_quiz_meta($post) {
        wp_nonce_field('algq_save_quiz_meta', 'algq_quiz_nonce');
        $question_ids = get_post_meta($post->ID, 'algq_quiz_question_ids', true);
        $pass_score = get_post_meta($post->ID, 'algq_quiz_pass_score', true);
        echo '<p><label><strong>' . esc_html__('Question IDs','algq-education-center') . '</strong></label><br><input class="widefat" type="text" name="algq_quiz_question_ids" value="' . esc_attr($question_ids) . '"><span class="description">Comma-separated algq_question post IDs.</span></p>';
        echo '<p><label><strong>' . esc_html__('Passing Score','algq-education-center') . '</strong></label><br><input type="number" min="0" max="100" name="algq_quiz_pass_score" value="' . esc_attr($pass_score ? $pass_score : 70) . '">%</p>';
    }

    public static function save_question_meta($post_id, $post) {
        if (!isset($_POST['algq_question_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['algq_question_nonce'])), 'algq_save_question_meta') || !current_user_can('edit_post', $post_id)) { return; }
        update_post_meta($post_id, 'algq_question_type', isset($_POST['algq_question_type']) ? sanitize_key($_POST['algq_question_type']) : 'multiple_choice');
        update_post_meta($post_id, 'algq_question_answers', isset($_POST['algq_question_answers']) ? sanitize_textarea_field(wp_unslash($_POST['algq_question_answers'])) : '');
        update_post_meta($post_id, 'algq_question_correct_answer', isset($_POST['algq_question_correct_answer']) ? sanitize_text_field(wp_unslash($_POST['algq_question_correct_answer'])) : '');
    }

    public static function save_quiz_meta($post_id, $post) {
        if (!isset($_POST['algq_quiz_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['algq_quiz_nonce'])), 'algq_save_quiz_meta') || !current_user_can('edit_post', $post_id)) { return; }
        update_post_meta($post_id, 'algq_quiz_question_ids', isset($_POST['algq_quiz_question_ids']) ? sanitize_text_field(wp_unslash($_POST['algq_quiz_question_ids'])) : '');
        update_post_meta($post_id, 'algq_quiz_pass_score', isset($_POST['algq_quiz_pass_score']) ? absint($_POST['algq_quiz_pass_score']) : 70);
    }

    public static function render_quiz($atts = array()) {
        $atts = shortcode_atts(array('id'=>0), $atts, 'algq_quiz');
        $quiz_id = absint($atts['id']);
        if (!$quiz_id || 'algq_quiz' !== get_post_type($quiz_id)) { return '<div class="algq-edu-notice">' . esc_html__('Quiz not found.','algq-education-center') . '</div>'; }
        $ids = get_post_meta($quiz_id, 'algq_quiz_question_ids', true);
        $ids = array_filter(array_map('absint', explode(',', (string) $ids)));
        ob_start();
        echo '<section class="algq-edu algq-quiz" data-quiz-id="' . esc_attr((string) $quiz_id) . '"><header class="algq-section-header"><p class="algq-kicker">' . esc_html__('Quiz','algq-education-center') . '</p><h1>' . esc_html(get_the_title($quiz_id)) . '</h1></header><div class="algq-card-grid">';
        foreach ($ids as $question_id) {
            if ('algq_question' !== get_post_type($question_id)) { continue; }
            echo '<article class="algq-card"><h2>' . esc_html(get_the_title($question_id)) . '</h2><p>' . wp_kses_post(wpautop(get_post_field('post_content', $question_id))) . '</p></article>';
        }
        echo '</div></section>';
        return ob_get_clean();
    }
}
