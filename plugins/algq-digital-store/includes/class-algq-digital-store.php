<?php
/**
 * Runtime services for Algonquian Digital Store.
 */

defined( 'ABSPATH' ) || exit;

final class ALGQ_Digital_Store {
    private static ?self $instance = null;

    public static function instance(): self {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    private function __construct() {}

    public function init(): void {
        add_action( 'init', array( $this, 'register_shortcodes' ) );
        add_action( 'init', array( 'ALGQ_Digital_Store_Activator', 'add_capabilities' ) );
        add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_assets' ) );
        add_action( 'admin_menu', array( $this, 'register_admin_menu' ) );
        add_action( 'admin_notices', array( $this, 'dependency_notice' ) );
        add_action( 'woocommerce_payment_complete', array( $this, 'emit_entitlement_events' ) );
        add_action( 'woocommerce_order_status_processing', array( $this, 'emit_entitlement_events' ) );
        add_action( 'woocommerce_order_status_completed', array( $this, 'emit_entitlement_events' ) );
    }

    public function register_shortcodes(): void {
        add_shortcode( 'algq_digital_store', array( $this, 'render_store' ) );
        add_shortcode( 'algq_product_vault', array( $this, 'render_vault' ) );
        add_shortcode( 'algq_store_checkout', array( $this, 'render_checkout' ) );
    }

    public function enqueue_assets(): void {
        if ( ! is_singular() ) {
            return;
        }

        $post = get_post();
        if ( ! $post instanceof WP_Post ) {
            return;
        }

        $shortcodes = array( 'algq_digital_store', 'algq_product_vault', 'algq_store_checkout' );
        foreach ( $shortcodes as $shortcode ) {
            if ( has_shortcode( $post->post_content, $shortcode ) ) {
                wp_enqueue_style(
                    'algq-digital-store',
                    ALGQ_DIGITAL_STORE_URL . 'assets/css/algq-digital-store.css',
                    array(),
                    ALGQ_DIGITAL_STORE_VERSION
                );
                return;
            }
        }
    }

    public function dependency_notice(): void {
        if ( ! current_user_can( 'manage_algq_digital_store' ) || class_exists( 'WooCommerce' ) ) {
            return;
        }

        echo '<div class="notice notice-warning"><p>' . esc_html__(
            'Algonquian Digital Store is active, but WooCommerce is not available. Catalog, checkout, orders, and customer entitlements remain disabled until WooCommerce is activated.',
            'algq-digital-store'
        ) . '</p></div>';
    }

    public function render_store( array $atts = array() ): string {
        $atts = shortcode_atts(
            array(
                'limit'    => 12,
                'category' => '',
            ),
            $atts,
            'algq_digital_store'
        );

        if ( ! class_exists( 'WooCommerce' ) || ! function_exists( 'wc_get_products' ) ) {
            return $this->notice(
                __( 'The digital catalog is temporarily unavailable because the commerce service is not active.', 'algq-digital-store' ),
                'warning'
            );
        }

        $args = array(
            'status'     => 'publish',
            'limit'      => max( 1, min( 48, absint( $atts['limit'] ) ) ),
            'orderby'    => 'menu_order',
            'order'      => 'ASC',
            'return'     => 'objects',
            'visibility' => 'catalog',
        );

        if ( '' !== trim( (string) $atts['category'] ) ) {
            $args['category'] = array( sanitize_title( (string) $atts['category'] ) );
        }

        /**
         * Filters the authoritative WooCommerce product query for the Digital Store.
         */
        $args = apply_filters( 'algq_digital_store_product_query', $args, $atts );
        $products = wc_get_products( $args );

        ob_start();
        ?>
        <section class="algq-store" aria-labelledby="algq-store-title">
            <header class="algq-store__hero">
                <div class="algq-store__mark" aria-hidden="true">ARE</div>
                <div>
                    <p class="algq-store__eyebrow"><?php esc_html_e( 'Algonquian Real Estate Technology Division', 'algq-digital-store' ); ?></p>
                    <h2 id="algq-store-title"><?php esc_html_e( 'Algonquian Digital Store', 'algq-digital-store' ); ?></h2>
                    <p><?php esc_html_e( 'Secure access to approved plugins, templates, calculators, guides, and operational resources.', 'algq-digital-store' ); ?></p>
                </div>
            </header>

            <?php if ( empty( $products ) ) : ?>
                <?php echo wp_kses_post( $this->notice( __( 'No published digital products are currently available.', 'algq-digital-store' ), 'info' ) ); ?>
            <?php else : ?>
                <div class="algq-store__grid">
                    <?php foreach ( $products as $product ) : ?>
                        <?php if ( ! $product instanceof WC_Product || ! $product->is_visible() ) { continue; } ?>
                        <article class="algq-product-card">
                            <a class="algq-product-card__image" href="<?php echo esc_url( $product->get_permalink() ); ?>">
                                <?php echo wp_kses_post( $product->get_image( 'woocommerce_thumbnail', array( 'loading' => 'lazy' ) ) ); ?>
                            </a>
                            <div class="algq-product-card__body">
                                <p class="algq-product-card__type"><?php echo esc_html( $this->product_type_label( $product ) ); ?></p>
                                <h3><a href="<?php echo esc_url( $product->get_permalink() ); ?>"><?php echo esc_html( $product->get_name() ); ?></a></h3>
                                <div class="algq-product-card__description"><?php echo wp_kses_post( wpautop( $product->get_short_description() ) ); ?></div>
                                <div class="algq-product-card__price"><?php echo wp_kses_post( $product->get_price_html() ); ?></div>
                                <a class="algq-button" href="<?php echo esc_url( $this->product_action_url( $product ) ); ?>">
                                    <?php echo esc_html( $this->product_action_label( $product ) ); ?>
                                </a>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>
        <?php
        return (string) ob_get_clean();
    }

    public function render_vault(): string {
        if ( ! is_user_logged_in() ) {
            $login_url = wp_login_url( $this->current_url() );
            return $this->notice(
                sprintf(
                    /* translators: %s: login URL. */
                    __( 'Sign in to review your purchased products and authorized downloads. <a href="%s">Sign in</a>.', 'algq-digital-store' ),
                    esc_url( $login_url )
                ),
                'info',
                true
            );
        }

        if ( ! class_exists( 'WooCommerce' ) || ! function_exists( 'wc_get_customer_available_downloads' ) ) {
            return $this->notice(
                __( 'The Product Vault is temporarily unavailable because the commerce service is not active.', 'algq-digital-store' ),
                'warning'
            );
        }

        $user_id = get_current_user_id();
        if ( ! current_user_can( 'view_algq_product_vault' ) && ! current_user_can( 'manage_algq_digital_store' ) ) {
            return $this->notice( __( 'Your account is not authorized to access the Product Vault.', 'algq-digital-store' ), 'error' );
        }

        $downloads = wc_get_customer_available_downloads( $user_id );
        $orders = wc_get_orders(
            array(
                'customer_id' => $user_id,
                'status'      => array( 'wc-processing', 'wc-completed' ),
                'limit'       => 50,
                'orderby'     => 'date',
                'order'       => 'DESC',
                'return'      => 'objects',
            )
        );

        ob_start();
        ?>
        <section class="algq-vault" aria-labelledby="algq-vault-title">
            <header class="algq-vault__header">
                <p class="algq-store__eyebrow"><?php esc_html_e( 'Authenticated Customer Access', 'algq-digital-store' ); ?></p>
                <h2 id="algq-vault-title"><?php esc_html_e( 'Product Vault', 'algq-digital-store' ); ?></h2>
                <p><?php esc_html_e( 'Downloads and purchase records are derived from paid WooCommerce orders and server-side entitlements.', 'algq-digital-store' ); ?></p>
            </header>

            <h3><?php esc_html_e( 'Available Downloads', 'algq-digital-store' ); ?></h3>
            <?php if ( empty( $downloads ) ) : ?>
                <?php echo wp_kses_post( $this->notice( __( 'No active downloads are currently assigned to your account.', 'algq-digital-store' ), 'info' ) ); ?>
            <?php else : ?>
                <div class="algq-vault__grid">
                    <?php foreach ( $downloads as $download ) : ?>
                        <article class="algq-vault-item">
                            <h4><?php echo esc_html( $download['product_name'] ?? __( 'Digital Product', 'algq-digital-store' ) ); ?></h4>
                            <?php if ( ! empty( $download['download_name'] ) ) : ?>
                                <p><?php echo esc_html( $download['download_name'] ); ?></p>
                            <?php endif; ?>
                            <dl>
                                <div><dt><?php esc_html_e( 'Downloads remaining', 'algq-digital-store' ); ?></dt><dd><?php echo esc_html( $download['downloads_remaining'] ?? __( 'Unlimited', 'algq-digital-store' ) ); ?></dd></div>
                                <div><dt><?php esc_html_e( 'Expires', 'algq-digital-store' ); ?></dt><dd><?php echo esc_html( $download['access_expires'] ?? __( 'No expiration', 'algq-digital-store' ) ); ?></dd></div>
                            </dl>
                            <?php if ( ! empty( $download['download_url'] ) ) : ?>
                                <a class="algq-button" href="<?php echo esc_url( $download['download_url'] ); ?>"><?php esc_html_e( 'Download', 'algq-digital-store' ); ?></a>
                            <?php endif; ?>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <h3><?php esc_html_e( 'Purchase History', 'algq-digital-store' ); ?></h3>
            <?php if ( empty( $orders ) ) : ?>
                <?php echo wp_kses_post( $this->notice( __( 'No paid digital-store orders were found for this account.', 'algq-digital-store' ), 'info' ) ); ?>
            <?php else : ?>
                <div class="algq-vault__orders">
                    <?php foreach ( $orders as $order ) : ?>
                        <?php if ( ! $order instanceof WC_Order ) { continue; } ?>
                        <article class="algq-order-card">
                            <div>
                                <h4><?php echo esc_html( sprintf( __( 'Order #%s', 'algq-digital-store' ), $order->get_order_number() ) ); ?></h4>
                                <p><?php echo esc_html( wc_format_datetime( $order->get_date_created() ) ); ?> · <?php echo esc_html( wc_get_order_status_name( $order->get_status() ) ); ?></p>
                            </div>
                            <strong><?php echo wp_kses_post( $order->get_formatted_order_total() ); ?></strong>
                            <a href="<?php echo esc_url( $order->get_view_order_url() ); ?>"><?php esc_html_e( 'View order', 'algq-digital-store' ); ?></a>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>
        <?php
        return (string) ob_get_clean();
    }

    public function render_checkout(): string {
        if ( ! class_exists( 'WooCommerce' ) ) {
            return $this->notice(
                __( 'Checkout is unavailable because WooCommerce is not active.', 'algq-digital-store' ),
                'warning'
            );
        }

        return do_shortcode( '[woocommerce_checkout]' );
    }

    public function emit_entitlement_events( int $order_id ): void {
        if ( ! function_exists( 'wc_get_order' ) ) {
            return;
        }

        $order = wc_get_order( $order_id );
        if ( ! $order instanceof WC_Order || ! $order->is_paid() ) {
            return;
        }

        if ( 'yes' === $order->get_meta( '_algq_digital_store_events_emitted', true ) ) {
            return;
        }

        $user_id = (int) $order->get_user_id();
        foreach ( $order->get_items( 'line_item' ) as $item ) {
            $product = $item->get_product();
            if ( ! $product instanceof WC_Product ) {
                continue;
            }

            do_action(
                'algq_digital_store_entitlement_granted',
                array(
                    'order_id'     => $order->get_id(),
                    'user_id'      => $user_id,
                    'product_id'   => $product->get_id(),
                    'variation_id' => $item->get_variation_id(),
                    'quantity'     => $item->get_quantity(),
                    'downloadable' => $product->is_downloadable(),
                    'virtual'      => $product->is_virtual(),
                )
            );

            do_action(
                'algq_audit_event',
                array(
                    'event'      => 'digital_store.entitlement_granted',
                    'plugin'     => 'algq-digital-store',
                    'related_id' => $order->get_id(),
                    'user_id'    => $user_id,
                    'metadata'   => array(
                        'product_id' => $product->get_id(),
                        'quantity'   => $item->get_quantity(),
                    ),
                )
            );
        }

        $order->update_meta_data( '_algq_digital_store_events_emitted', 'yes' );
        $order->save();
    }

    public function register_admin_menu(): void {
        add_menu_page(
            __( 'Algonquian Digital Store', 'algq-digital-store' ),
            __( 'ARE Digital Store', 'algq-digital-store' ),
            'manage_algq_digital_store',
            'algq-digital-store',
            array( $this, 'render_admin_page' ),
            'dashicons-store',
            58
        );
    }

    public function render_admin_page(): void {
        if ( ! current_user_can( 'manage_algq_digital_store' ) ) {
            wp_die( esc_html__( 'Insufficient permissions.', 'algq-digital-store' ) );
        }

        $pages = get_option( 'algq_digital_store_pages', array() );
        $counts = class_exists( 'WooCommerce' ) ? wp_count_posts( 'product' ) : null;
        $product_count = is_object( $counts ) && isset( $counts->publish ) ? absint( $counts->publish ) : 0;
        ?>
        <div class="wrap">
            <h1><?php esc_html_e( 'Algonquian Digital Store', 'algq-digital-store' ); ?></h1>
            <p><?php esc_html_e( 'WooCommerce is the catalog, price, order, and download authority. The Platform Plugin remains the authority for shared Stripe credentials and webhook processing.', 'algq-digital-store' ); ?></p>
            <table class="widefat striped" style="max-width:900px">
                <tbody>
                    <tr><th><?php esc_html_e( 'Plugin version', 'algq-digital-store' ); ?></th><td><?php echo esc_html( ALGQ_DIGITAL_STORE_VERSION ); ?></td></tr>
                    <tr><th><?php esc_html_e( 'WooCommerce', 'algq-digital-store' ); ?></th><td><?php echo esc_html( class_exists( 'WooCommerce' ) ? __( 'Active', 'algq-digital-store' ) : __( 'Not active', 'algq-digital-store' ) ); ?></td></tr>
                    <tr><th><?php esc_html_e( 'Published products', 'algq-digital-store' ); ?></th><td><?php echo esc_html( (string) $product_count ); ?></td></tr>
                    <tr><th><?php esc_html_e( 'Shared Stripe service', 'algq-digital-store' ); ?></th><td><?php echo esc_html( class_exists( 'ALGQ_Stripe_Integration' ) ? __( 'Detected', 'algq-digital-store' ) : __( 'Not detected; WooCommerce gateways remain authoritative', 'algq-digital-store' ) ); ?></td></tr>
                    <tr><th><?php esc_html_e( 'Generated pages', 'algq-digital-store' ); ?></th><td><code><?php echo esc_html( wp_json_encode( array_map( 'absint', is_array( $pages ) ? $pages : array() ) ) ); ?></code></td></tr>
                </tbody>
            </table>
            <h2><?php esc_html_e( 'Shortcodes', 'algq-digital-store' ); ?></h2>
            <p><code>[algq_digital_store]</code> <code>[algq_product_vault]</code> <code>[algq_store_checkout]</code></p>
        </div>
        <?php
    }

    private function product_type_label( WC_Product $product ): string {
        if ( $product->is_downloadable() ) {
            return __( 'Digital Download', 'algq-digital-store' );
        }
        if ( $product->is_virtual() ) {
            return __( 'Digital Service', 'algq-digital-store' );
        }
        return __( 'Product', 'algq-digital-store' );
    }

    private function product_action_url( WC_Product $product ): string {
        if ( $product->is_purchasable() && $product->is_in_stock() && $product->is_type( 'simple' ) ) {
            return $product->add_to_cart_url();
        }
        return $product->get_permalink();
    }

    private function product_action_label( WC_Product $product ): string {
        if ( $product->is_purchasable() && $product->is_in_stock() && $product->is_type( 'simple' ) ) {
            return $product->add_to_cart_text();
        }
        return __( 'View product', 'algq-digital-store' );
    }

    private function current_url(): string {
        $page_id = get_queried_object_id();
        if ( $page_id ) {
            $permalink = get_permalink( $page_id );
            if ( is_string( $permalink ) && '' !== $permalink ) {
                return $permalink;
            }
        }

        return home_url( '/product-vault/' );
    }

    private function notice( string $message, string $type = 'info', bool $allow_link = false ): string {
        $allowed = $allow_link ? array( 'a' => array( 'href' => array() ) ) : array();
        return sprintf(
            '<div class="algq-store-notice algq-store-notice--%1$s" role="status">%2$s</div>',
            esc_attr( $type ),
            $allow_link ? wp_kses( $message, $allowed ) : esc_html( $message )
        );
    }
}
