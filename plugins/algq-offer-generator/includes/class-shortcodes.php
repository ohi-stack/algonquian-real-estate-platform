<?php
if (!defined('ABSPATH')) { exit; }

class ALGQ_Offer_Shortcodes {
    public static function init() {
        add_shortcode('algq_offer_generator', array(__CLASS__, 'dashboard'));
        add_shortcode('algq_offer_builder', array(__CLASS__, 'builder'));
        add_shortcode('algq_offer_history', array(__CLASS__, 'history'));
    }

    public static function dashboard() {
        ob_start(); ?>
        <section class="algq-ui algq-offer-dashboard">
            <div class="algq-hero"><p class="algq-kicker">Algonquian Real Estate</p><h1>Offer Generator</h1><p>Generate acquisition offers, seller-financing terms, letters of intent, and transaction-ready documents from deal data.</p></div>
            <div class="algq-grid algq-grid-4">
                <article class="algq-stat"><strong>4</strong><span>Offer Types</span></article>
                <article class="algq-stat"><strong>PDF</strong><span>Document Output</span></article>
                <article class="algq-stat"><strong>CRM</strong><span>Deal Integration</span></article>
                <article class="algq-stat"><strong>ARE</strong><span>Institutional Workflow</span></article>
            </div>
            <div class="algq-grid">
                <article class="algq-card"><span class="algq-badge">Cash</span><h2>Cash Offer</h2><p>Produce a clean cash offer summary for fast seller review.</p><a class="algq-btn algq-btn-gold" href="/generate-offer">Start Offer</a></article>
                <article class="algq-card"><span class="algq-badge">Terms</span><h2>Seller Financing</h2><p>Build payment terms, down payment, amortization, and balloon structures.</p><a class="algq-btn algq-btn-gold" href="/generate-offer">Build Terms</a></article>
                <article class="algq-card"><span class="algq-badge">Creative</span><h2>Subject-To</h2><p>Generate a structured subject-to proposal using existing debt and monthly payment details.</p><a class="algq-btn algq-btn-gold" href="/generate-offer">Create Proposal</a></article>
            </div>
        </section>
        <?php return ob_get_clean();
    }

    public static function builder() {
        ob_start(); ?>
        <section class="algq-ui algq-offer-builder">
            <div class="algq-hero"><p class="algq-kicker">Document Execution</p><h1>Generate Offer</h1><p>Select a strategy, enter terms, preview the document, and save the generated offer to the deal file.</p></div>
            <form class="algq-card algq-offer-form" method="post">
                <?php wp_nonce_field('algq_generate_offer', 'algq_offer_nonce'); ?>
                <p><label>Offer Strategy<br><select name="offer_strategy"><option value="cash">Cash Offer</option><option value="seller_financing">Seller Financing</option><option value="subject_to">Subject-To</option><option value="loi">Letter of Intent</option></select></label></p>
                <p><label>Property Address<br><input type="text" name="property_address" required></label></p>
                <p><label>Purchase Price<br><input type="number" step="0.01" name="purchase_price" required></label></p>
                <p><label>Down Payment<br><input type="number" step="0.01" name="down_payment"></label></p>
                <p><label>Notes / Terms<br><textarea name="offer_terms" rows="6"></textarea></label></p>
                <button class="algq-btn algq-btn-navy" type="submit">Generate Offer Draft</button>
            </form>
        </section>
        <?php return ob_get_clean();
    }

    public static function history() {
        $offers = get_posts(array('post_type' => 'algq_offer', 'posts_per_page' => 20, 'post_status' => 'any'));
        ob_start(); ?>
        <section class="algq-ui algq-offer-history">
            <div class="algq-hero"><p class="algq-kicker">Version History</p><h1>Offer History</h1><p>Review generated offers and transaction document versions.</p></div>
            <div class="algq-table"><table><thead><tr><th>Offer</th><th>Status</th><th>Date</th></tr></thead><tbody>
            <?php foreach ($offers as $offer) : ?><tr><td><?php echo esc_html($offer->post_title); ?></td><td><?php echo esc_html($offer->post_status); ?></td><td><?php echo esc_html(get_the_date('', $offer)); ?></td></tr><?php endforeach; ?>
            </tbody></table></div>
        </section>
        <?php return ob_get_clean();
    }
}
