<?php

defined( 'ABSPATH' ) || exit;

final class ALGQ_Offer_Shortcodes {
    public static function init(): void {
        add_shortcode( 'algq_offer_generator', array( __CLASS__, 'dashboard' ) );
        add_shortcode( 'algq_offer_builder', array( __CLASS__, 'builder' ) );
        add_shortcode( 'algq_offer_history', array( __CLASS__, 'history' ) );
    }

    private static function authorize( string $capability ): string {
        if ( ! is_user_logged_in() ) {
            return '<div class="algq-alert algq-alert-warning">' . esc_html__( 'Sign in to access the Offer Generator.', 'algq-offer-generator' ) . '</div>';
        }
        if ( ! current_user_can( $capability ) && ! current_user_can( 'manage_algq_offers' ) ) {
            return '<div class="algq-alert algq-alert-error">' . esc_html__( 'You are not authorized to access this offer workspace.', 'algq-offer-generator' ) . '</div>';
        }
        return '';
    }

    public static function dashboard(): string {
        $denied = self::authorize( 'view_algq_offer_history' );
        if ( $denied ) {
            return $denied;
        }

        $counts       = wp_count_posts( 'algq_offer' );
        $create_page  = get_page_by_path( 'generate-offer' );
        $history_page = get_page_by_path( 'offer-history' );
        $create_url   = $create_page ? get_permalink( $create_page ) : admin_url( 'post-new.php?post_type=algq_offer' );
        $history_url  = $history_page ? get_permalink( $history_page ) : admin_url( 'edit.php?post_type=algq_offer' );

        ob_start();
        ?>
        <section class="algq-ui algq-offer-dashboard">
            <div class="algq-hero"><p class="algq-kicker">Algonquian Real Estate</p><h1><?php esc_html_e( 'Offer Generator', 'algq-offer-generator' ); ?></h1><p><?php esc_html_e( 'Create, review, approve, version, and route acquisition offers from controlled deal data.', 'algq-offer-generator' ); ?></p></div>
            <div class="algq-grid algq-grid-4">
                <article class="algq-stat"><strong><?php echo esc_html( (string) ( $counts->draft ?? 0 ) ); ?></strong><span><?php esc_html_e( 'Draft Offers', 'algq-offer-generator' ); ?></span></article>
                <article class="algq-stat"><strong><?php echo esc_html( (string) ( $counts->publish ?? 0 ) ); ?></strong><span><?php esc_html_e( 'Published Records', 'algq-offer-generator' ); ?></span></article>
                <article class="algq-stat"><strong>5</strong><span><?php esc_html_e( 'Offer Strategies', 'algq-offer-generator' ); ?></span></article>
                <article class="algq-stat"><strong><?php echo esc_html( ALGQ_OFFER_VERSION ); ?></strong><span><?php esc_html_e( 'Version', 'algq-offer-generator' ); ?></span></article>
            </div>
            <div class="algq-grid">
                <article class="algq-card"><span class="algq-badge">Create</span><h2><?php esc_html_e( 'New Offer', 'algq-offer-generator' ); ?></h2><p><?php esc_html_e( 'Build a cash, seller-financing, subject-to, letter-of-intent, or purchase proposal.', 'algq-offer-generator' ); ?></p><a class="algq-btn algq-btn-gold" href="<?php echo esc_url( $create_url ); ?>"><?php esc_html_e( 'Start Offer', 'algq-offer-generator' ); ?></a></article>
                <article class="algq-card"><span class="algq-badge">Control</span><h2><?php esc_html_e( 'Offer History', 'algq-offer-generator' ); ?></h2><p><?php esc_html_e( 'Review status, versions, linked deals, approval evidence, and document state.', 'algq-offer-generator' ); ?></p><a class="algq-btn algq-btn-navy" href="<?php echo esc_url( $history_url ); ?>"><?php esc_html_e( 'View History', 'algq-offer-generator' ); ?></a></article>
            </div>
        </section>
        <?php
        return (string) ob_get_clean();
    }

    public static function builder(): string {
        $denied = self::authorize( 'create_algq_offers' );
        if ( $denied ) {
            return $denied;
        }

        $notice = '';
        if ( 'POST' === strtoupper( $_SERVER['REQUEST_METHOD'] ?? '' ) && isset( $_POST['algq_offer_builder_submit'] ) ) {
            $nonce = sanitize_text_field( wp_unslash( $_POST['algq_offer_nonce'] ?? '' ) );
            if ( ! wp_verify_nonce( $nonce, 'algq_generate_offer' ) ) {
                $notice = '<div class="algq-alert algq-alert-error">' . esc_html__( 'The security token expired. Refresh the page and try again.', 'algq-offer-generator' ) . '</div>';
            } else {
                $result = ALGQ_Offer_Service::create( wp_unslash( $_POST ), get_current_user_id() );
                if ( is_wp_error( $result ) ) {
                    $notice = '<div class="algq-alert algq-alert-error">' . esc_html( $result->get_error_message() ) . '</div>';
                } else {
                    $edit_url = get_edit_post_link( $result, 'raw' );
                    $link     = $edit_url ? '<a href="' . esc_url( $edit_url ) . '">' . esc_html__( 'Open record', 'algq-offer-generator' ) . '</a>' : '';
                    $notice   = '<div class="algq-alert algq-alert-success">' . sprintf( esc_html__( 'Offer %1$s was created as a draft. %2$s', 'algq-offer-generator' ), esc_html( get_post_meta( $result, '_algq_offer_offer_number', true ) ), $link ) . '</div>';
                }
            }
        }

        ob_start();
        ?>
        <section class="algq-ui algq-offer-builder">
            <div class="algq-hero"><p class="algq-kicker">Controlled Offer Workflow</p><h1><?php esc_html_e( 'Generate Offer', 'algq-offer-generator' ); ?></h1><p><?php esc_html_e( 'Create a versioned draft, then route it for review, approval, document generation, and signature.', 'algq-offer-generator' ); ?></p></div>
            <?php echo wp_kses_post( $notice ); ?>
            <form class="algq-card algq-offer-form" method="post">
                <?php wp_nonce_field( 'algq_generate_offer', 'algq_offer_nonce' ); ?>
                <p><label><?php esc_html_e( 'Linked Deal ID', 'algq-offer-generator' ); ?><br><input type="number" min="0" name="deal_id" value="<?php echo esc_attr( absint( $_GET['deal_id'] ?? 0 ) ); ?>"></label></p>
                <p><label><?php esc_html_e( 'Offer Strategy', 'algq-offer-generator' ); ?><br><select name="offer_strategy" required><option value="cash">Cash Offer</option><option value="seller_financing">Seller Financing</option><option value="subject_to">Subject-To</option><option value="loi">Letter of Intent</option><option value="purchase">Purchase and Sale Proposal</option></select></label></p>
                <p><label><?php esc_html_e( 'Property Address', 'algq-offer-generator' ); ?><br><input type="text" name="property_address" maxlength="255"></label></p>
                <p><label><?php esc_html_e( 'Purchase Price', 'algq-offer-generator' ); ?><br><input type="number" min="0.01" step="0.01" name="purchase_price" required></label></p>
                <p><label><?php esc_html_e( 'Down Payment', 'algq-offer-generator' ); ?><br><input type="number" min="0" step="0.01" name="down_payment"></label></p>
                <p><label><?php esc_html_e( 'Monthly Payment', 'algq-offer-generator' ); ?><br><input type="number" min="0" step="0.01" name="monthly_payment"></label></p>
                <p><label><?php esc_html_e( 'Interest Rate (%)', 'algq-offer-generator' ); ?><br><input type="number" min="0" step="0.001" name="interest_rate"></label></p>
                <p><label><?php esc_html_e( 'Amortization (months)', 'algq-offer-generator' ); ?><br><input type="number" min="0" name="amortization_months"></label></p>
                <p><label><?php esc_html_e( 'Balloon (months)', 'algq-offer-generator' ); ?><br><input type="number" min="0" name="balloon_months"></label></p>
                <p><label><?php esc_html_e( 'Proposed Closing Date', 'algq-offer-generator' ); ?><br><input type="date" name="closing_date"></label></p>
                <p><label><?php esc_html_e( 'Contingencies', 'algq-offer-generator' ); ?><br><textarea name="contingencies" rows="4"></textarea></label></p>
                <p><label><?php esc_html_e( 'Notes and Terms', 'algq-offer-generator' ); ?><br><textarea name="offer_terms" rows="7"></textarea></label></p>
                <button class="algq-btn algq-btn-navy" type="submit" name="algq_offer_builder_submit" value="1"><?php esc_html_e( 'Create Versioned Draft', 'algq-offer-generator' ); ?></button>
            </form>
        </section>
        <?php
        return (string) ob_get_clean();
    }

    public static function history(): string {
        $denied = self::authorize( 'view_algq_offer_history' );
        if ( $denied ) {
            return $denied;
        }

        $paged = max( 1, absint( get_query_var( 'paged' ) ?: ( $_GET['offer_page'] ?? 1 ) ) );
        $query = new WP_Query(
            array(
                'post_type'      => 'algq_offer',
                'posts_per_page' => 20,
                'post_status'    => array( 'publish', 'draft', 'pending', 'private' ),
                'paged'          => $paged,
                'orderby'        => 'modified',
                'order'          => 'DESC',
            )
        );

        ob_start();
        ?>
        <section class="algq-ui algq-offer-history">
            <div class="algq-hero"><p class="algq-kicker">Version and Approval Control</p><h1><?php esc_html_e( 'Offer History', 'algq-offer-generator' ); ?></h1><p><?php esc_html_e( 'Review protected offer records without exposing seller or transaction data publicly.', 'algq-offer-generator' ); ?></p></div>
            <div class="algq-table"><table><thead><tr><th><?php esc_html_e( 'Offer', 'algq-offer-generator' ); ?></th><th><?php esc_html_e( 'Strategy', 'algq-offer-generator' ); ?></th><th><?php esc_html_e( 'Amount', 'algq-offer-generator' ); ?></th><th><?php esc_html_e( 'Workflow Status', 'algq-offer-generator' ); ?></th><th><?php esc_html_e( 'Version', 'algq-offer-generator' ); ?></th><th><?php esc_html_e( 'Modified', 'algq-offer-generator' ); ?></th></tr></thead><tbody>
            <?php if ( $query->have_posts() ) : while ( $query->have_posts() ) : $query->the_post(); $offer = ALGQ_Offer_Service::get( get_the_ID() ); ?>
                <tr><td><a href="<?php echo esc_url( get_edit_post_link( get_the_ID(), 'raw' ) ); ?>"><?php the_title(); ?></a></td><td><?php echo esc_html( str_replace( '_', ' ', (string) $offer['strategy'] ) ); ?></td><td><?php echo esc_html( '$' . number_format_i18n( (float) $offer['purchase_price'], 2 ) ); ?></td><td><?php echo esc_html( (string) ( $offer['offer_status'] ?: get_post_status() ) ); ?></td><td><?php echo esc_html( (string) ( $offer['version_number'] ?: 1 ) ); ?></td><td><?php echo esc_html( get_the_modified_date() ); ?></td></tr>
            <?php endwhile; wp_reset_postdata(); else : ?><tr><td colspan="6"><?php esc_html_e( 'No offers found.', 'algq-offer-generator' ); ?></td></tr><?php endif; ?>
            </tbody></table></div>
        </section>
        <?php
        return (string) ob_get_clean();
    }
}
