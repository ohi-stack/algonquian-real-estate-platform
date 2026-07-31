<?php
/**
 * Shared Stripe integration service for the Algonquian Real Estate Platform.
 *
 * @package AlgonquianRealEstatePlatform
 */

defined( 'ABSPATH' ) || exit;

final class ALGQ_Stripe_Integration {

    private const OPTION_KEY = 'algq_stripe_settings';
    private const REST_NAMESPACE = 'algq/v1';
    private const WEBHOOK_ROUTE = '/stripe/webhook';

    /** @var self|null */
    private static $instance = null;

    /** @var array<string,mixed> */
    private $settings = array();

    public static function instance(): self {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    private function __construct() {
        $this->settings = $this->load_settings();

        add_action( 'rest_api_init', array( $this, 'register_routes' ) );
        add_action( 'admin_init', array( $this, 'register_settings' ) );
    }

    /**
     * Register the centralized Stripe webhook endpoint.
     */
    public function register_routes(): void {
        register_rest_route(
            self::REST_NAMESPACE,
            self::WEBHOOK_ROUTE,
            array(
                'methods'             => 'POST',
                'callback'            => array( $this, 'handle_webhook' ),
                'permission_callback' => '__return_true',
            )
        );
    }

    /**
     * Register non-secret administrative settings.
     */
    public function register_settings(): void {
        register_setting(
            'algq_platform_integrations',
            self::OPTION_KEY,
            array(
                'type'              => 'array',
                'sanitize_callback' => array( $this, 'sanitize_settings' ),
                'default'           => array(),
            )
        );
    }

    /**
     * Create a Stripe Checkout Session through the shared platform transport.
     *
     * @param array<string,mixed> $args Checkout arguments.
     * @return array<string,mixed>|WP_Error
     */
    public function create_checkout_session( array $args ) {
        if ( ! $this->is_configured() ) {
            return new WP_Error( 'algq_stripe_not_configured', 'Stripe is not configured.' );
        }

        $payload = array(
            'mode'        => sanitize_key( $args['mode'] ?? 'payment' ),
            'success_url' => esc_url_raw( $args['success_url'] ?? home_url( '/' ) ),
            'cancel_url'  => esc_url_raw( $args['cancel_url'] ?? home_url( '/' ) ),
            'metadata'    => $this->sanitize_metadata( $args['metadata'] ?? array() ),
            'line_items'  => $args['line_items'] ?? array(),
        );

        return $this->request( 'POST', '/v1/checkout/sessions', $payload );
    }

    /**
     * Create a Stripe Billing Portal Session.
     *
     * @return array<string,mixed>|WP_Error
     */
    public function create_billing_portal_session( string $customer_id, string $return_url ) {
        return $this->request(
            'POST',
            '/v1/billing_portal/sessions',
            array(
                'customer'   => sanitize_text_field( $customer_id ),
                'return_url' => esc_url_raw( $return_url ),
            )
        );
    }

    /**
     * Process and dispatch a verified Stripe webhook.
     */
    public function handle_webhook( WP_REST_Request $request ) {
        $payload   = $request->get_body();
        $signature = (string) $request->get_header( 'stripe-signature' );

        if ( ! $this->verify_signature( $payload, $signature ) ) {
            return new WP_REST_Response( array( 'error' => 'invalid_signature' ), 400 );
        }

        $event = json_decode( $payload, true );

        if ( ! is_array( $event ) || empty( $event['id'] ) || empty( $event['type'] ) ) {
            return new WP_REST_Response( array( 'error' => 'invalid_payload' ), 400 );
        }

        $event_id   = sanitize_text_field( $event['id'] );
        $event_type = sanitize_text_field( $event['type'] );

        if ( get_transient( 'algq_stripe_event_' . md5( $event_id ) ) ) {
            return new WP_REST_Response( array( 'received' => true, 'duplicate' => true ), 200 );
        }

        set_transient( 'algq_stripe_event_' . md5( $event_id ), 1, WEEK_IN_SECONDS );

        do_action( 'algq_stripe_event_received', $event_type, $event );
        do_action( 'algq_stripe_event_' . str_replace( '.', '_', $event_type ), $event );

        if ( function_exists( 'algq_log_event' ) ) {
            algq_log_event(
                'stripe.webhook.received',
                array(
                    'event_id'   => $event_id,
                    'event_type' => $event_type,
                )
            );
        }

        return new WP_REST_Response( array( 'received' => true ), 200 );
    }

    public function is_configured(): bool {
        return '' !== $this->get_secret_key() && '' !== $this->get_webhook_secret();
    }

    /** @return array<string,mixed> */
    public function sanitize_settings( $settings ): array {
        $settings = is_array( $settings ) ? $settings : array();

        return array(
            'enabled'      => ! empty( $settings['enabled'] ) ? 1 : 0,
            'test_mode'    => ! empty( $settings['test_mode'] ) ? 1 : 0,
            'publishable_key' => sanitize_text_field( $settings['publishable_key'] ?? '' ),
        );
    }

    /**
     * Execute a request against Stripe's API without exposing credentials to plugins.
     *
     * @return array<string,mixed>|WP_Error
     */
    private function request( string $method, string $path, array $payload = array() ) {
        $response = wp_remote_request(
            'https://api.stripe.com' . $path,
            array(
                'method'  => strtoupper( $method ),
                'timeout' => 20,
                'headers' => array(
                    'Authorization' => 'Bearer ' . $this->get_secret_key(),
                    'Content-Type'  => 'application/x-www-form-urlencoded',
                ),
                'body'    => $payload,
            )
        );

        if ( is_wp_error( $response ) ) {
            return $response;
        }

        $status = wp_remote_retrieve_response_code( $response );
        $body   = json_decode( wp_remote_retrieve_body( $response ), true );

        if ( $status < 200 || $status >= 300 ) {
            return new WP_Error(
                'algq_stripe_api_error',
                sanitize_text_field( $body['error']['message'] ?? 'Stripe request failed.' ),
                array( 'status' => $status )
            );
        }

        return is_array( $body ) ? $body : array();
    }

    private function verify_signature( string $payload, string $signature_header ): bool {
        $secret = $this->get_webhook_secret();

        if ( '' === $secret || '' === $signature_header ) {
            return false;
        }

        $parts = array();
        foreach ( explode( ',', $signature_header ) as $part ) {
            $pair = array_map( 'trim', explode( '=', $part, 2 ) );
            if ( 2 === count( $pair ) ) {
                $parts[ $pair[0] ][] = $pair[1];
            }
        }

        $timestamp  = isset( $parts['t'][0] ) ? (int) $parts['t'][0] : 0;
        $signatures = $parts['v1'] ?? array();

        if ( 0 === $timestamp || abs( time() - $timestamp ) > 300 ) {
            return false;
        }

        $expected = hash_hmac( 'sha256', $timestamp . '.' . $payload, $secret );

        foreach ( $signatures as $signature ) {
            if ( hash_equals( $expected, $signature ) ) {
                return true;
            }
        }

        return false;
    }

    private function get_secret_key(): string {
        if ( defined( 'ALGQ_STRIPE_SECRET_KEY' ) ) {
            return (string) ALGQ_STRIPE_SECRET_KEY;
        }

        return (string) getenv( 'ALGQ_STRIPE_SECRET_KEY' );
    }

    private function get_webhook_secret(): string {
        if ( defined( 'ALGQ_STRIPE_WEBHOOK_SECRET' ) ) {
            return (string) ALGQ_STRIPE_WEBHOOK_SECRET;
        }

        return (string) getenv( 'ALGQ_STRIPE_WEBHOOK_SECRET' );
    }

    /** @return array<string,mixed> */
    private function load_settings(): array {
        $settings = get_option( self::OPTION_KEY, array() );
        return is_array( $settings ) ? $settings : array();
    }

    /** @return array<string,string> */
    private function sanitize_metadata( array $metadata ): array {
        $clean = array();
        foreach ( $metadata as $key => $value ) {
            $clean[ sanitize_key( (string) $key ) ] = sanitize_text_field( (string) $value );
        }
        return $clean;
    }
}
