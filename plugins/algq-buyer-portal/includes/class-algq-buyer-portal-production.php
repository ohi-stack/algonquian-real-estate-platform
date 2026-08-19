<?php
/**
 * Production safeguards and evidence capture for Buyer Portal forms.
 *
 * @package Algonquian_Buyer_Portal
 */

defined( 'ABSPATH' ) || exit;

final class ALGQ_Buyer_Portal_Production {
    private const CONSENT_VERSION = '2026-08-19';
    private const PRIVACY_VERSION = '2026-08-19';
    private const TERMS_VERSION = '2026-08-19';

    public static function init(): void {
        add_filter( 'do_shortcode_tag', array( __CLASS__, 'decorate_registration_form' ), 10, 4 );
        add_filter( 'wp_redirect', array( __CLASS__, 'normalize_registration_redirect' ), 20, 2 );
        add_action( 'admin_post_nopriv_algq_buyer_register', array( __CLASS__, 'guard_registration' ), 1 );
        add_action( 'user_register', array( __CLASS__, 'capture_registration_evidence' ), 20, 2 );
        add_action( 'algq_buyer_interest_submitted', array( __CLASS__, 'notify_interest' ), 20, 2 );
    }

    /**
     * Add anti-automation fields and qualification inputs without duplicating the
     * authoritative Buyer Portal shortcode implementation.
     *
     * @param string               $output Shortcode HTML.
     * @param string               $tag Shortcode name.
     * @param array<string,mixed>  $attr Attributes.
     * @param array<int,mixed>     $m Regex match data.
     */
    public static function decorate_registration_form( string $output, string $tag, array $attr, array $m ): string {
        unset( $attr, $m );

        if ( 'algq_buyer_registration' !== $tag || is_user_logged_in() || ! str_contains( $output, '<form' ) ) {
            return $output;
        }

        $security = sprintf(
            '<input type="hidden" name="algq_buyer_started_at" value="%1$d"><div style="position:absolute;left:-10000px;width:1px;height:1px;overflow:hidden" aria-hidden="true"><label>Website<input type="text" name="algq_buyer_website" value="" tabindex="-1" autocomplete="off"></label></div>',
            time()
        );

        $qualification = '<label>Primary acquisition strategy<select name="investment_strategy"><option value="">Select</option><option value="rental">Rental / Hold</option><option value="flip">Fix and Flip</option><option value="wholesale">Wholesale / Assignment</option><option value="development">Development / Redevelopment</option><option value="owner_occupant">Owner Occupant</option><option value="other">Other</option></select></label>'
            . '<label>Typical purchase range<input name="purchase_range" maxlength="100" placeholder="Example: $150,000–$500,000"></label>'
            . '<label>Proof of funds / financing status<select name="proof_of_funds_status"><option value="">Select</option><option value="available">Available</option><option value="preapproved">Financing pre-approved</option><option value="in_progress">In progress</option><option value="not_yet">Not yet available</option></select></label>';

        $output = preg_replace( '/(<form\b[^>]*>)/i', '$1' . $security, $output, 1 ) ?: $output;

        $needle = '<label><input required type="checkbox" name="terms_consent" value="1">';
        if ( str_contains( $output, $needle ) ) {
            $output = str_replace( $needle, $qualification . $needle, $output );
        } else {
            $output = str_replace( '</form>', $qualification . '</form>', $output );
        }

        return $output;
    }

    /**
     * Keep the legacy registration handler compatible while normalizing its
     * obsolete /buyers-login/ redirect to the canonical /buyer-login/ route.
     */
    public static function normalize_registration_redirect( string $location, int $status ): string {
        unset( $status );

        if ( 'algq_buyer_register' !== sanitize_key( wp_unslash( $_POST['action'] ?? '' ) ) ) {
            return $location;
        }

        $legacy = trailingslashit( home_url( '/buyers-login/' ) );
        if ( str_starts_with( trailingslashit( strtok( $location, '?' ) ?: '' ), $legacy ) ) {
            $query = wp_parse_url( $location, PHP_URL_QUERY );
            $canonical = home_url( '/buyer-login/' );
            return $query ? $canonical . '?' . $query : $canonical;
        }

        return $location;
    }

    /**
     * Reject automated or malformed registration submissions before the legacy
     * handler creates a WordPress account.
     */
    public static function guard_registration(): void {
        $nonce = isset( $_POST['_wpnonce'] ) ? sanitize_text_field( wp_unslash( $_POST['_wpnonce'] ) ) : '';
        if ( ! wp_verify_nonce( $nonce, 'algq_buyer_register' ) ) {
            self::abort( __( 'The buyer registration request could not be verified.', 'algq-buyer-portal' ), 403 );
        }

        if ( ! empty( $_POST['algq_buyer_website'] ) ) {
            self::quiet_success();
        }

        $started = isset( $_POST['algq_buyer_started_at'] ) ? absint( $_POST['algq_buyer_started_at'] ) : 0;
        if ( 0 === $started || time() - $started < 3 || time() - $started > DAY_IN_SECONDS ) {
            self::abort( __( 'The buyer registration request expired or was submitted too quickly.', 'algq-buyer-portal' ), 400 );
        }

        $name  = sanitize_text_field( wp_unslash( $_POST['name'] ?? '' ) );
        $email = sanitize_email( wp_unslash( $_POST['email'] ?? '' ) );

        if ( '' === trim( $name ) || strlen( $name ) > 190 || ! is_email( $email ) || empty( $_POST['terms_consent'] ) ) {
            self::abort( __( 'Complete the required buyer registration fields and accept the terms and privacy notice.', 'algq-buyer-portal' ), 400 );
        }

        $key   = 'algq_buyer_register_' . hash_hmac( 'sha256', strtolower( $email ) . '|' . self::client_ip(), wp_salt( 'auth' ) );
        $count = absint( get_transient( $key ) );
        $limit = max( 1, absint( apply_filters( 'algq_buyer_registration_rate_limit', 3 ) ) );

        if ( $count >= $limit ) {
            self::abort( __( 'Too many buyer registration attempts were received. Please try again later.', 'algq-buyer-portal' ), 429 );
        }

        set_transient( $key, $count + 1, HOUR_IN_SECONDS );
    }

    /**
     * Persist versioned consent and privacy-preserving request evidence.
     *
     * @param int                 $user_id New user ID.
     * @param array<string,mixed> $userdata User data supplied to wp_insert_user().
     */
    public static function capture_registration_evidence( int $user_id, array $userdata = array() ): void {
        unset( $userdata );

        if ( 'algq_buyer_register' !== sanitize_key( wp_unslash( $_POST['action'] ?? '' ) ) ) {
            return;
        }

        update_user_meta( $user_id, 'algq_buyer_consent_version', self::CONSENT_VERSION );
        update_user_meta( $user_id, 'algq_buyer_privacy_version', self::PRIVACY_VERSION );
        update_user_meta( $user_id, 'algq_buyer_terms_version', self::TERMS_VERSION );
        update_user_meta( $user_id, 'algq_buyer_consent_accepted_at', current_time( 'mysql', true ) );
        update_user_meta( $user_id, 'algq_buyer_registration_ip_hash', self::privacy_hash( self::client_ip() ) );
        update_user_meta( $user_id, 'algq_buyer_registration_user_agent_hash', self::privacy_hash( sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ?? '' ) ) ) );
        update_user_meta( $user_id, 'algq_buyer_investment_strategy', sanitize_key( wp_unslash( $_POST['investment_strategy'] ?? '' ) ) );
        update_user_meta( $user_id, 'algq_buyer_purchase_range', sanitize_text_field( wp_unslash( $_POST['purchase_range'] ?? '' ) ) );
        update_user_meta( $user_id, 'algq_buyer_proof_of_funds_status', sanitize_key( wp_unslash( $_POST['proof_of_funds_status'] ?? '' ) ) );

        self::audit(
            'buyer.registration_created',
            array(
                'user_id' => $user_id,
                'consent_version' => self::CONSENT_VERSION,
            )
        );

        $user = get_userdata( $user_id );
        $email = $user instanceof WP_User ? $user->user_email : sanitize_email( wp_unslash( $_POST['email'] ?? '' ) );
        $name = $user instanceof WP_User ? $user->display_name : sanitize_text_field( wp_unslash( $_POST['name'] ?? '' ) );
        $company = sanitize_text_field( wp_unslash( $_POST['company'] ?? '' ) );
        $markets = sanitize_textarea_field( wp_unslash( $_POST['target_markets'] ?? '' ) );
        $types = sanitize_textarea_field( wp_unslash( $_POST['property_types'] ?? '' ) );

        self::send_company_mail(
            'New Algonquian buyer registration',
            sprintf(
                "A new buyer registration was created.\n\nName: %s\nEmail: %s\nCompany: %s\nTarget markets: %s\nProperty types: %s\nUser ID: %d",
                $name,
                $email,
                $company,
                $markets,
                $types,
                $user_id
            )
        );
    }

    public static function notify_interest( int $user_id, int $deal_id ): void {
        $user = get_userdata( $user_id );
        self::send_company_mail(
            sprintf( 'Buyer interest submitted for deal #%d', $deal_id ),
            sprintf(
                "Buyer interest was submitted.\n\nDeal ID: %d\nBuyer: %s\nBuyer email: %s\nUser ID: %d",
                $deal_id,
                $user instanceof WP_User ? $user->display_name : '',
                $user instanceof WP_User ? $user->user_email : '',
                $user_id
            )
        );
    }

    private static function company_email(): string {
        $email = sanitize_email( (string) apply_filters( 'algq_company_notification_email', get_option( 'algq_company_notification_email', 'algonquianre@gmail.com' ) ) );
        return is_email( $email ) ? $email : 'algonquianre@gmail.com';
    }

    private static function send_company_mail( string $subject, string $message ): bool {
        return (bool) wp_mail(
            self::company_email(),
            sanitize_text_field( $subject ),
            $message,
            array( 'Content-Type: text/plain; charset=UTF-8' )
        );
    }

    private static function client_ip(): string {
        return sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ?? '' ) );
    }

    private static function privacy_hash( string $value ): string {
        return '' === $value ? '' : hash_hmac( 'sha256', $value, wp_salt( 'auth' ) );
    }

    /** @param array<string,mixed> $context */
    private static function audit( string $event, array $context ): void {
        if ( function_exists( 'algq_log_event' ) ) {
            algq_log_event( $event, array_merge( array( 'plugin' => 'algq-buyer-portal' ), $context ) );
            return;
        }
        do_action( 'algq_audit_event', $event, array_merge( array( 'plugin' => 'algq-buyer-portal' ), $context ) );
    }

    private static function abort( string $message, int $status ): never {
        wp_die( esc_html( $message ), esc_html__( 'Buyer Registration', 'algq-buyer-portal' ), array( 'response' => $status ) );
    }

    private static function quiet_success(): never {
        $target = wp_validate_redirect( wp_get_referer() ?: home_url( '/buyers-register/' ), home_url( '/buyers-register/' ) );
        wp_safe_redirect( add_query_arg( 'registered', '1', $target ) );
        exit;
    }
}
