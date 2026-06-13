<?php
if (!defined('ABSPATH')) { exit; }

class ALGQ_Offer_PDF_Integration {
    public static function init() {
        add_action('wp_ajax_algq_offer_download_pdf', array(__CLASS__, 'download_pdf'));
    }

    public static function download_pdf() {
        check_ajax_referer('algq_offer_generator', 'nonce');
        if (!current_user_can('edit_posts')) { wp_die(esc_html__('Permission denied.', 'algq-offer-generator')); }
        $offer_id = isset($_GET['offer_id']) ? absint($_GET['offer_id']) : 0;
        if (!$offer_id || get_post_type($offer_id) !== 'algq_offer') { wp_die(esc_html__('Invalid offer.', 'algq-offer-generator')); }
        $html = get_post_meta($offer_id, '_algq_offer_document_html', true);
        if (!$html && class_exists('ALGQ_Offer_Document_Generator')) { $html = ALGQ_Offer_Document_Generator::render_offer_html($offer_id); }
        header('Content-Type: text/html; charset=' . get_option('blog_charset'));
        header('Content-Disposition: attachment; filename="algq-offer-' . $offer_id . '.html"');
        echo '<!doctype html><html><head><meta charset="utf-8"><title>' . esc_html(get_the_title($offer_id)) . '</title></head><body>' . wp_kses_post($html) . '</body></html>';
        exit;
    }
}
