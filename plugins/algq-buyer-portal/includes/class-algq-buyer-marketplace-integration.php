<?php
/**
 * Investors page and Buyer Portal -> Marketplace integration.
 *
 * @package Algonquian_Buyer_Portal
 */

defined( 'ABSPATH' ) || exit;

final class ALGQ_Buyer_Marketplace_Integration {
    private const PAGE_VERSION_OPTION = 'algq_investors_page_version';

    public static function init(): void {
        add_action( 'init', array( __CLASS__, 'reconcile_buyer_role' ), 20 );
        add_action( 'init', array( __CLASS__, 'ensure_investors_page' ), 30 );
        add_filter( 'login_redirect', array( __CLASS__, 'buyer_login_redirect' ), 20, 3 );
        add_shortcode( 'algq_investors_page', array( __CLASS__, 'investors_shortcode' ) );
    }

    /**
     * Keep the shared buyer role activation-order safe.
     *
     * A registered buyer receives base Buyer Portal and Marketplace capabilities.
     * Deal-level private/premium authorization remains controlled by the Marketplace plugin.
     */
    public static function reconcile_buyer_role(): void {
        $buyer = get_role( 'algq_buyer' );
        if ( ! $buyer ) {
            add_role( 'algq_buyer', __( 'Algonquian Buyer', 'algq-buyer-portal' ), array( 'read' => true ) );
            $buyer = get_role( 'algq_buyer' );
        }

        if ( ! $buyer ) {
            return;
        }

        $caps = array(
            'read',
            'algq_view_buyer_portal',
            'view_algq_buyer_portal',
            'view_algq_buyer_dashboard',
            'view_algq_deals',
            'accept_algq_nda',
            'submit_algq_buyer_interest',
            'download_algq_deal_documents',
            'view_algq_marketplace',
            'view_algq_marketplace_deals',
            'accept_algq_marketplace_nda',
            'submit_algq_marketplace_offer',
            'download_algq_marketplace_packages',
        );

        foreach ( $caps as $cap ) {
            if ( ! $buyer->has_cap( $cap ) ) {
                $buyer->add_cap( $cap );
            }
        }
    }

    /**
     * Explicitly replace /investors/ with the production investor/buyer access page.
     */
    public static function ensure_investors_page(): void {
        if ( ALGQ_BUYER_PORTAL_VERSION === get_option( self::PAGE_VERSION_OPTION ) ) {
            return;
        }

        $content = '[vc_row full_width="stretch_row_content" css=".vc_custom_algq_investors_hero{background:#0b1f33;padding-top:82px !important;padding-bottom:82px !important;}"][vc_column][vc_column_text]'
            . '<div style="max-width:1080px;margin:0 auto;padding:0 28px;text-align:center;color:#fff;">'
            . '<div style="display:inline-block;padding:9px 16px;border:1px solid rgba(199,164,74,.55);border-radius:999px;color:#e8c86d;font-size:12px;font-weight:800;letter-spacing:.10em;text-transform:uppercase;">Algonquian Real Estate • Investors &amp; Buyers</div>'
            . '<h1 style="margin:22px 0 18px;color:#fff;font-size:52px;line-height:1.1;font-weight:800;">Connect With Connecticut Real Estate Opportunities</h1>'
            . '<p style="max-width:820px;margin:0 auto;color:#d7e0e7;font-size:19px;line-height:1.75;">Register as a buyer, sign in to your account, and access eligible marketplace opportunities through a controlled authorization workflow.</p>'
            . '</div>[/vc_column_text][/vc_column][/vc_row]'
            . '[vc_row full_width="stretch_row" css=".vc_custom_algq_investors_body{background:#fff;padding-top:72px !important;padding-bottom:72px !important;}"][vc_column][vc_column_text][algq_investors_page][/vc_column_text][/vc_column][/vc_row]';

        $page = get_page_by_path( 'investors', OBJECT, 'page' );
        $args = array(
            'post_title'   => 'Investors & Capital',
            'post_name'    => 'investors',
            'post_content' => $content,
            'post_status'  => 'publish',
            'post_type'    => 'page',
        );

        if ( $page instanceof WP_Post ) {
            $args['ID'] = (int) $page->ID;
            $page_id = wp_update_post( $args, true );
        } else {
            $page_id = wp_insert_post( $args, true );
        }

        if ( ! is_wp_error( $page_id ) && $page_id ) {
            update_post_meta( (int) $page_id, '_algq_managed_investors_page', '1' );
            update_post_meta( (int) $page_id, '_algq_managed_investors_page_version', ALGQ_BUYER_PORTAL_VERSION );
            update_option( self::PAGE_VERSION_OPTION, ALGQ_BUYER_PORTAL_VERSION, false );
        }
    }

    /**
     * Send buyers directly into the authorized Marketplace after successful login.
     *
     * @param string           $redirect_to           Redirect URL.
     * @param string           $requested_redirect_to Requested redirect URL.
     * @param WP_User|WP_Error $user                  Authenticated user or error.
     */
    public static function buyer_login_redirect( string $redirect_to, string $requested_redirect_to, $user ): string {
        unset( $requested_redirect_to );

        if ( ! $user instanceof WP_User ) {
            return $redirect_to;
        }

        if ( in_array( 'algq_buyer', (array) $user->roles, true ) || user_can( $user, 'view_algq_marketplace' ) ) {
            if ( shortcode_exists( 'algq_deal_marketplace' ) ) {
                return self::marketplace_url();
            }
            return home_url( '/buyer-dashboard/' );
        }

        return $redirect_to;
    }

    /**
     * Resolve the Marketplace permalink from the Marketplace plugin first, then
     * support the legacy live route during migration, then use the v2 fallback.
     */
    private static function marketplace_url(): string {
        $page_id = absint( get_option( 'algq_dm_marketplace_page_id', 0 ) );
        if ( $page_id > 0 ) {
            $permalink = get_permalink( $page_id );
            if ( is_string( $permalink ) && '' !== $permalink ) {
                return $permalink;
            }
        }

        $legacy_page = get_page_by_path( 'deal-marketplace', OBJECT, 'page' );
        if ( $legacy_page instanceof WP_Post ) {
            $permalink = get_permalink( $legacy_page );
            if ( is_string( $permalink ) && '' !== $permalink ) {
                return $permalink;
            }
        }

        return home_url( '/marketplace/' );
    }

    public static function investors_shortcode(): string {
        $logged_in = is_user_logged_in();
        $is_buyer = $logged_in && ( current_user_can( 'algq_view_buyer_portal' ) || current_user_can( 'view_algq_buyer_portal' ) );
        $marketplace_ready = shortcode_exists( 'algq_deal_marketplace' );
        $marketplace_url = self::marketplace_url();

        ob_start();
        ?>
        <section class="algq-investors" style="max-width:1180px;margin:0 auto;padding:0 28px;">
            <div style="max-width:850px;margin:0 auto 46px;text-align:center;">
                <div style="margin-bottom:13px;color:#167c80;font-size:12px;font-weight:800;letter-spacing:.10em;text-transform:uppercase;">Investor &amp; Buyer Access</div>
                <h2 style="margin:0 0 16px;color:#0b1f33;font-size:42px;line-height:1.18;font-weight:800;">A controlled path from registration to deal access</h2>
                <p style="margin:0;color:#65737f;font-size:18px;line-height:1.75;">Buyer accounts are used to manage marketplace access, confidentiality requirements, deal-specific permissions, package delivery, and offer activity.</p>
            </div>

            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:18px;margin-bottom:48px;">
                <div style="padding:26px;border:1px solid #dce3e8;border-radius:14px;background:#f7f9fa;"><strong style="display:block;color:#0b1f33;font-size:18px;">1. Register</strong><span style="color:#65737f;line-height:1.6;">Create a buyer account and provide your acquisition profile.</span></div>
                <div style="padding:26px;border:1px solid #dce3e8;border-radius:14px;background:#f7f9fa;"><strong style="display:block;color:#0b1f33;font-size:18px;">2. Account Created</strong><span style="color:#65737f;line-height:1.6;">WordPress creates the account and sends the buyer credential setup notice.</span></div>
                <div style="padding:26px;border:1px solid #dce3e8;border-radius:14px;background:#f7f9fa;"><strong style="display:block;color:#0b1f33;font-size:18px;">3. Sign In</strong><span style="color:#65737f;line-height:1.6;">Authenticate through the Buyer Login page.</span></div>
                <div style="padding:26px;border:1px solid #dce3e8;border-radius:14px;background:#f7f9fa;"><strong style="display:block;color:#0b1f33;font-size:18px;">4. Marketplace</strong><span style="color:#65737f;line-height:1.6;">Registered-tier deals become viewable subject to deal status, NDA requirements, and any record-level restrictions.</span></div>
            </div>

            <?php if ( $is_buyer ) : ?>
                <div style="padding:34px;border-radius:16px;background:#0b1f33;color:#fff;">
                    <div style="color:#c7a44a;font-size:12px;font-weight:800;letter-spacing:.10em;text-transform:uppercase;">Buyer Account Active</div>
                    <h3 style="margin:10px 0 12px;color:#fff;font-size:29px;">Your buyer access is available.</h3>
                    <p style="margin:0 0 24px;color:#cbd5dc;font-size:17px;line-height:1.7;">Open the Marketplace to review opportunities available to your account. Private or premium opportunities may still require a specific access grant and current NDA acceptance.</p>
                    <?php if ( $marketplace_ready ) : ?>
                        <a href="<?php echo esc_url( $marketplace_url ); ?>" style="display:inline-block;margin:4px;padding:14px 24px;border-radius:7px;background:#c7a44a;color:#071422;font-weight:800;text-decoration:none;">Open Marketplace</a>
                    <?php endif; ?>
                    <a href="<?php echo esc_url( home_url( '/buyer-dashboard/' ) ); ?>" style="display:inline-block;margin:4px;padding:13px 24px;border:1px solid rgba(255,255,255,.40);border-radius:7px;color:#fff;font-weight:700;text-decoration:none;">Buyer Dashboard</a>
                </div>
            <?php else : ?>
                <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(320px,1fr));gap:28px;align-items:start;">
                    <div style="padding:34px;border:1px solid #dce3e8;border-radius:16px;background:#f5f7f9;">
                        <div style="color:#167c80;font-size:12px;font-weight:800;letter-spacing:.10em;text-transform:uppercase;">New Buyers</div>
                        <h3 style="margin:10px 0 10px;color:#0b1f33;font-size:28px;">Buyer Registration</h3>
                        <p style="color:#65737f;line-height:1.7;">Create your buyer account to begin the controlled marketplace-access process.</p>
                        <?php echo do_shortcode( '[algq_buyer_registration]' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                    </div>
                    <div style="padding:34px;border-radius:16px;background:#0b1f33;color:#fff;">
                        <div style="color:#c7a44a;font-size:12px;font-weight:800;letter-spacing:.10em;text-transform:uppercase;">Existing Buyers</div>
                        <h3 style="margin:10px 0 10px;color:#fff;font-size:28px;">Buyer Login</h3>
                        <p style="color:#cbd5dc;line-height:1.7;">Already registered? Sign in and you will be routed to the Marketplace when the Marketplace plugin is active.</p>
                        <a href="<?php echo esc_url( home_url( '/buyers-login/' ) ); ?>" style="display:inline-block;margin-top:8px;padding:14px 24px;border-radius:7px;background:#c7a44a;color:#071422;font-weight:800;text-decoration:none;">Buyer Login</a>
                        <?php if ( $marketplace_ready ) : ?>
                            <a href="<?php echo esc_url( $marketplace_url ); ?>" style="display:inline-block;margin:8px 0 0 6px;padding:13px 24px;border:1px solid rgba(255,255,255,.40);border-radius:7px;color:#fff;font-weight:700;text-decoration:none;">View Marketplace</a>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>

            <p style="margin:34px auto 0;max-width:920px;color:#7a8791;font-size:14px;line-height:1.7;text-align:center;">Marketplace access is not an offer to sell securities or a promise of investment availability or returns. Access to individual real estate opportunities may be limited, revoked, conditioned on confidentiality requirements, or subject to additional review.</p>
        </section>
        <?php
        return (string) ob_get_clean();
    }
}
