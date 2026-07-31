<?php

defined( 'ABSPATH' ) || exit;

final class ALGQ_Digital_Products {
    private const POST_TYPE = 'algq_digital_product';
    private const TAXONOMY = 'algq_product_category';
    private const CAPABILITY = 'manage_algq_digital_products';
    private const OPTION_VERSION = 'algq_digital_products_version';
    private const OPTION_SCHEMA = 'algq_digital_products_schema_version';
    private const OPTION_PAGES = 'algq_digital_products_page_ids';
    private const OPTION_DELETE_DATA = 'algq_digital_products_delete_data_on_uninstall';

    private static ?self $instance = null;

    public static function instance(): self {
        return self::$instance ??= new self();
    }

    public function boot(): void {
        add_action( 'plugins_loaded', array( $this, 'load_textdomain' ) );
        add_action( 'init', array( $this, 'register_content_types' ) );
        add_action( 'init', array( $this, 'register_shortcodes' ) );
        add_action( 'rest_api_init', array( $this, 'register_rest_routes' ) );
        add_action( 'add_meta_boxes', array( $this, 'register_meta_box' ) );
        add_action( 'save_post_' . self::POST_TYPE, array( $this, 'save_meta' ), 10, 2 );
        add_action( 'admin_menu', array( $this, 'admin_menu' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'admin_assets' ) );
        add_action( 'wp_enqueue_scripts', array( $this, 'register_public_assets' ) );
        add_filter( 'manage_' . self::POST_TYPE . '_posts_columns', array( $this, 'admin_columns' ) );
        add_action( 'manage_' . self::POST_TYPE . '_posts_custom_column', array( $this, 'admin_column' ), 10, 2 );
        add_filter( 'plugin_action_links_' . plugin_basename( ALGQ_DIGITAL_PRODUCTS_FILE ), array( $this, 'action_links' ) );

        do_action(
            'algq_plugin_registered',
            array(
                'slug' => 'algq-digital-products',
                'version' => ALGQ_DIGITAL_PRODUCTS_VERSION,
                'required_platform_version' => '1.0.0',
                'capabilities' => array( self::CAPABILITY ),
                'rest_namespace' => 'algq/v1',
                'health_callback' => array( $this, 'health_status' ),
            )
        );
    }

    public static function activate(): void {
        $plugin = self::instance();
        $plugin->register_content_types();
        $plugin->grant_capabilities();
        $plugin->create_pages();
        update_option( self::OPTION_VERSION, ALGQ_DIGITAL_PRODUCTS_VERSION, false );
        update_option( self::OPTION_SCHEMA, ALGQ_DIGITAL_PRODUCTS_SCHEMA_VERSION, false );
        add_option( self::OPTION_DELETE_DATA, false, '', false );
        flush_rewrite_rules();
        $plugin->audit( 'digital_products.activated', array( 'version' => ALGQ_DIGITAL_PRODUCTS_VERSION ) );
    }

    public static function deactivate(): void {
        flush_rewrite_rules();
    }

    public function load_textdomain(): void {
        load_plugin_textdomain( 'algq-digital-products', false, dirname( plugin_basename( ALGQ_DIGITAL_PRODUCTS_FILE ) ) . '/languages' );
    }

    public function register_content_types(): void {
        register_post_type(
            self::POST_TYPE,
            array(
                'labels' => array(
                    'name' => __( 'Digital Products', 'algq-digital-products' ),
                    'singular_name' => __( 'Digital Product', 'algq-digital-products' ),
                    'add_new_item' => __( 'Add Digital Product', 'algq-digital-products' ),
                    'edit_item' => __( 'Edit Digital Product', 'algq-digital-products' ),
                    'menu_name' => __( 'Digital Products', 'algq-digital-products' ),
                ),
                'public' => true,
                'show_in_rest' => true,
                'has_archive' => false,
                'rewrite' => array( 'slug' => 'digital-product', 'with_front' => false ),
                'menu_icon' => 'dashicons-products',
                'supports' => array( 'title', 'editor', 'excerpt', 'thumbnail', 'revisions', 'page-attributes' ),
                'capability_type' => array( 'algq_digital_product', 'algq_digital_products' ),
                'map_meta_cap' => true,
                'capabilities' => array(
                    'edit_post' => self::CAPABILITY,
                    'read_post' => 'read',
                    'delete_post' => self::CAPABILITY,
                    'edit_posts' => self::CAPABILITY,
                    'edit_others_posts' => self::CAPABILITY,
                    'publish_posts' => self::CAPABILITY,
                    'read_private_posts' => self::CAPABILITY,
                    'delete_posts' => self::CAPABILITY,
                    'delete_private_posts' => self::CAPABILITY,
                    'delete_published_posts' => self::CAPABILITY,
                    'delete_others_posts' => self::CAPABILITY,
                    'edit_private_posts' => self::CAPABILITY,
                    'edit_published_posts' => self::CAPABILITY,
                    'create_posts' => self::CAPABILITY,
                ),
                'show_in_menu' => false,
            )
        );

        register_taxonomy(
            self::TAXONOMY,
            self::POST_TYPE,
            array(
                'labels' => array(
                    'name' => __( 'Product Categories', 'algq-digital-products' ),
                    'singular_name' => __( 'Product Category', 'algq-digital-products' ),
                ),
                'public' => true,
                'hierarchical' => true,
                'show_in_rest' => true,
                'show_admin_column' => true,
                'rewrite' => array( 'slug' => 'digital-product-category', 'with_front' => false ),
            )
        );

        foreach ( $this->meta_schema() as $key => $schema ) {
            register_post_meta(
                self::POST_TYPE,
                $key,
                array(
                    'type' => $schema['type'],
                    'single' => true,
                    'show_in_rest' => true,
                    'sanitize_callback' => $schema['sanitize'],
                    'auth_callback' => static fn(): bool => current_user_can( self::CAPABILITY ),
                )
            );
        }
    }

    private function meta_schema(): array {
        return array(
            '_algq_product_type' => array( 'type' => 'string', 'sanitize' => 'sanitize_key' ),
            '_algq_product_version' => array( 'type' => 'string', 'sanitize' => 'sanitize_text_field' ),
            '_algq_product_sku' => array( 'type' => 'string', 'sanitize' => 'sanitize_text_field' ),
            '_algq_product_visibility' => array( 'type' => 'string', 'sanitize' => 'sanitize_key' ),
            '_algq_delivery_mode' => array( 'type' => 'string', 'sanitize' => 'sanitize_key' ),
            '_algq_price_label' => array( 'type' => 'string', 'sanitize' => 'sanitize_text_field' ),
            '_algq_woocommerce_product_id' => array( 'type' => 'integer', 'sanitize' => 'absint' ),
            '_algq_documentation_url' => array( 'type' => 'string', 'sanitize' => 'esc_url_raw' ),
            '_algq_download_attachment_id' => array( 'type' => 'integer', 'sanitize' => 'absint' ),
            '_algq_cta_label' => array( 'type' => 'string', 'sanitize' => 'sanitize_text_field' ),
        );
    }

    public function register_shortcodes(): void {
        add_shortcode( 'algq_digital_products', array( $this, 'catalog_shortcode' ) );
        add_shortcode( 'algq_digital_product', array( $this, 'product_shortcode' ) );
    }

    public function register_public_assets(): void {
        wp_register_style( 'algq-digital-products', ALGQ_DIGITAL_PRODUCTS_URL . 'assets/css/algq-digital-products.css', array(), ALGQ_DIGITAL_PRODUCTS_VERSION );
    }

    public function catalog_shortcode( $atts ): string {
        $atts = shortcode_atts( array( 'category' => '', 'limit' => 12, 'columns' => 3 ), $atts, 'algq_digital_products' );
        wp_enqueue_style( 'algq-digital-products' );

        $args = array(
            'post_type' => self::POST_TYPE,
            'post_status' => 'publish',
            'posts_per_page' => min( 50, max( 1, absint( $atts['limit'] ) ) ),
            'orderby' => array( 'menu_order' => 'ASC', 'date' => 'DESC' ),
            'meta_query' => $this->visibility_query(),
        );
        if ( $atts['category'] ) {
            $args['tax_query'] = array( array( 'taxonomy' => self::TAXONOMY, 'field' => 'slug', 'terms' => sanitize_title( $atts['category'] ) ) );
        }
        $args = apply_filters( 'algq_digital_products_query_args', $args, $atts );
        $query = new WP_Query( $args );
        $columns = min( 4, max( 1, absint( $atts['columns'] ) ) );

        ob_start();
        ?>
        <section class="algq-dp-shell" aria-labelledby="algq-dp-title">
            <header class="algq-dp-header">
                <span class="algq-dp-eyebrow"><?php esc_html_e( 'Algonquian Real Estate Technology Division', 'algq-digital-products' ); ?></span>
                <h2 id="algq-dp-title"><?php esc_html_e( 'Digital Products', 'algq-digital-products' ); ?></h2>
                <p><?php esc_html_e( 'Templates, calculators, guides, checklists, plugins, and operating resources for disciplined real estate workflows.', 'algq-digital-products' ); ?></p>
            </header>
            <?php if ( $query->have_posts() ) : ?>
                <div class="algq-dp-grid algq-dp-grid--<?php echo esc_attr( (string) $columns ); ?>">
                    <?php while ( $query->have_posts() ) : $query->the_post(); echo wp_kses_post( $this->card( get_the_ID() ) ); endwhile; ?>
                </div>
            <?php else : ?>
                <div class="algq-dp-empty"><h3><?php esc_html_e( 'Catalog preparation is in progress.', 'algq-digital-products' ); ?></h3><p><?php esc_html_e( 'No public digital products are available in this category yet.', 'algq-digital-products' ); ?></p></div>
            <?php endif; ?>
        </section>
        <?php
        wp_reset_postdata();
        return (string) ob_get_clean();
    }

    public function product_shortcode( $atts ): string {
        $atts = shortcode_atts( array( 'id' => 0, 'slug' => '' ), $atts, 'algq_digital_product' );
        $post = absint( $atts['id'] ) ? get_post( absint( $atts['id'] ) ) : get_page_by_path( sanitize_title( $atts['slug'] ), OBJECT, self::POST_TYPE );
        if ( ! $post || self::POST_TYPE !== $post->post_type || 'publish' !== $post->post_status || ! $this->visible( $post->ID ) ) {
            return '<div class="algq-dp-empty">' . esc_html__( 'Digital product not found or access is unavailable.', 'algq-digital-products' ) . '</div>';
        }
        wp_enqueue_style( 'algq-digital-products' );
        return '<section class="algq-dp-shell algq-dp-single">' . $this->card( $post->ID, true ) . '</section>';
    }

    private function card( int $id, bool $expanded = false ): string {
        $type = (string) get_post_meta( $id, '_algq_product_type', true );
        $version = (string) get_post_meta( $id, '_algq_product_version', true );
        $delivery = (string) get_post_meta( $id, '_algq_delivery_mode', true );
        $terms = get_the_terms( $id, self::TAXONOMY );
        $category = is_array( $terms ) && isset( $terms[0] ) ? $terms[0]->name : '';
        $summary = has_excerpt( $id ) ? get_the_excerpt( $id ) : wp_trim_words( wp_strip_all_tags( get_post_field( 'post_content', $id ) ), 30 );
        $cta = $this->cta( $id );

        ob_start();
        ?>
        <article class="algq-dp-card<?php echo $expanded ? ' algq-dp-card--expanded' : ''; ?>">
            <?php if ( has_post_thumbnail( $id ) ) : ?><div class="algq-dp-media"><?php echo get_the_post_thumbnail( $id, 'large', array( 'loading' => 'lazy' ) ); ?></div><?php endif; ?>
            <div class="algq-dp-card-body">
                <div class="algq-dp-meta">
                    <?php if ( $type ) : ?><span><?php echo esc_html( $this->type_label( $type ) ); ?></span><?php endif; ?>
                    <?php if ( $category ) : ?><span><?php echo esc_html( $category ); ?></span><?php endif; ?>
                    <?php if ( $version ) : ?><span><?php echo esc_html( sprintf( __( 'Version %s', 'algq-digital-products' ), $version ) ); ?></span><?php endif; ?>
                </div>
                <h3><?php echo esc_html( get_the_title( $id ) ); ?></h3>
                <p><?php echo esc_html( $summary ); ?></p>
                <?php if ( $expanded ) : ?><div class="algq-dp-content"><?php echo wp_kses_post( apply_filters( 'the_content', get_post_field( 'post_content', $id ) ) ); ?></div><?php endif; ?>
                <div class="algq-dp-footer">
                    <div><?php if ( $this->price( $id ) ) : ?><strong class="algq-dp-price"><?php echo esc_html( $this->price( $id ) ); ?></strong><?php endif; ?><?php if ( $delivery ) : ?><small><?php echo esc_html( $this->delivery_label( $delivery ) ); ?></small><?php endif; ?></div>
                    <a class="algq-dp-button" href="<?php echo esc_url( $cta['url'] ); ?>"><?php echo esc_html( $cta['label'] ); ?></a>
                </div>
            </div>
        </article>
        <?php
        return (string) apply_filters( 'algq_digital_products_item', ob_get_clean(), $id, $expanded );
    }

    private function visibility_query(): array {
        return array(
            'relation' => 'OR',
            array( 'key' => '_algq_product_visibility', 'compare' => 'NOT EXISTS' ),
            array( 'key' => '_algq_product_visibility', 'value' => is_user_logged_in() ? array( 'public', 'customer' ) : array( 'public' ), 'compare' => 'IN' ),
        );
    }

    private function visible( int $id ): bool {
        $visibility = (string) get_post_meta( $id, '_algq_product_visibility', true );
        return '' === $visibility || 'public' === $visibility || ( 'customer' === $visibility && is_user_logged_in() ) || current_user_can( self::CAPABILITY );
    }

    private function price( int $id ): string {
        $woo_id = absint( get_post_meta( $id, '_algq_woocommerce_product_id', true ) );
        if ( $woo_id && function_exists( 'wc_get_product' ) ) {
            $product = wc_get_product( $woo_id );
            if ( $product && $product->is_visible() ) {
                return wp_strip_all_tags( $product->get_price_html() );
            }
        }
        return (string) get_post_meta( $id, '_algq_price_label', true );
    }

    private function cta( int $id ): array {
        $label = (string) get_post_meta( $id, '_algq_cta_label', true ) ?: __( 'View Product', 'algq-digital-products' );
        $woo_id = absint( get_post_meta( $id, '_algq_woocommerce_product_id', true ) );
        if ( $woo_id && function_exists( 'wc_get_product' ) ) {
            $product = wc_get_product( $woo_id );
            if ( $product && $product->is_purchasable() ) {
                return array( 'url' => get_permalink( $woo_id ), 'label' => $label );
            }
        }
        $docs = (string) get_post_meta( $id, '_algq_documentation_url', true );
        return array( 'url' => $docs ?: get_permalink( $id ), 'label' => $label );
    }

    public function register_meta_box(): void {
        add_meta_box( 'algq-digital-product-details', __( 'Digital Product Details', 'algq-digital-products' ), array( $this, 'meta_box' ), self::POST_TYPE, 'normal', 'high' );
    }

    public function meta_box( WP_Post $post ): void {
        wp_nonce_field( 'algq_digital_product_save', 'algq_digital_product_nonce' );
        $get = static fn( string $key ) => get_post_meta( $post->ID, $key, true );
        ?>
        <div class="algq-dp-admin-grid">
            <p><label for="algq-product-type"><strong><?php esc_html_e( 'Product Type', 'algq-digital-products' ); ?></strong></label><select id="algq-product-type" name="algq_product_type"><?php foreach ( $this->types() as $value => $label ) : ?><option value="<?php echo esc_attr( $value ); ?>" <?php selected( $get( '_algq_product_type' ), $value ); ?>><?php echo esc_html( $label ); ?></option><?php endforeach; ?></select></p>
            <p><label for="algq-product-version"><strong><?php esc_html_e( 'Version', 'algq-digital-products' ); ?></strong></label><input id="algq-product-version" name="algq_product_version" type="text" value="<?php echo esc_attr( $get( '_algq_product_version' ) ); ?>" placeholder="1.0.0"></p>
            <p><label for="algq-product-sku"><strong><?php esc_html_e( 'SKU', 'algq-digital-products' ); ?></strong></label><input id="algq-product-sku" name="algq_product_sku" type="text" value="<?php echo esc_attr( $get( '_algq_product_sku' ) ); ?>"></p>
            <p><label for="algq-product-visibility"><strong><?php esc_html_e( 'Visibility', 'algq-digital-products' ); ?></strong></label><select id="algq-product-visibility" name="algq_product_visibility"><option value="public" <?php selected( $get( '_algq_product_visibility' ) ?: 'public', 'public' ); ?>><?php esc_html_e( 'Public', 'algq-digital-products' ); ?></option><option value="customer" <?php selected( $get( '_algq_product_visibility' ), 'customer' ); ?>><?php esc_html_e( 'Authenticated Customer', 'algq-digital-products' ); ?></option><option value="internal" <?php selected( $get( '_algq_product_visibility' ), 'internal' ); ?>><?php esc_html_e( 'Internal', 'algq-digital-products' ); ?></option></select></p>
            <p><label for="algq-delivery-mode"><strong><?php esc_html_e( 'Delivery Mode', 'algq-digital-products' ); ?></strong></label><select id="algq-delivery-mode" name="algq_delivery_mode"><option value="download" <?php selected( $get( '_algq_delivery_mode' ), 'download' ); ?>><?php esc_html_e( 'Protected Download', 'algq-digital-products' ); ?></option><option value="license" <?php selected( $get( '_algq_delivery_mode' ), 'license' ); ?>><?php esc_html_e( 'Software License', 'algq-digital-products' ); ?></option><option value="external" <?php selected( $get( '_algq_delivery_mode' ), 'external' ); ?>><?php esc_html_e( 'External Delivery', 'algq-digital-products' ); ?></option><option value="service" <?php selected( $get( '_algq_delivery_mode' ), 'service' ); ?>><?php esc_html_e( 'Service / Implementation', 'algq-digital-products' ); ?></option></select></p>
            <p><label for="algq-price-label"><strong><?php esc_html_e( 'Fallback Price Label', 'algq-digital-products' ); ?></strong></label><input id="algq-price-label" name="algq_price_label" type="text" value="<?php echo esc_attr( $get( '_algq_price_label' ) ); ?>" placeholder="$49 or Contact for pricing"></p>
            <p><label for="algq-woo-id"><strong><?php esc_html_e( 'WooCommerce Product ID', 'algq-digital-products' ); ?></strong></label><input id="algq-woo-id" name="algq_woocommerce_product_id" type="number" min="0" value="<?php echo esc_attr( (string) absint( $get( '_algq_woocommerce_product_id' ) ) ); ?>"></p>
            <p><label for="algq-docs-url"><strong><?php esc_html_e( 'Documentation URL', 'algq-digital-products' ); ?></strong></label><input id="algq-docs-url" name="algq_documentation_url" type="url" value="<?php echo esc_attr( $get( '_algq_documentation_url' ) ); ?>"></p>
            <p><label for="algq-attachment-id"><strong><?php esc_html_e( 'Protected Attachment ID', 'algq-digital-products' ); ?></strong></label><input id="algq-attachment-id" name="algq_download_attachment_id" type="number" min="0" value="<?php echo esc_attr( (string) absint( $get( '_algq_download_attachment_id' ) ) ); ?>"><small><?php esc_html_e( 'Stored for the entitlement controller; the attachment URL is never exposed by this plugin.', 'algq-digital-products' ); ?></small></p>
            <p><label for="algq-cta-label"><strong><?php esc_html_e( 'CTA Label', 'algq-digital-products' ); ?></strong></label><input id="algq-cta-label" name="algq_cta_label" type="text" value="<?php echo esc_attr( $get( '_algq_cta_label' ) ); ?>" placeholder="View Product"></p>
        </div>
        <?php
    }

    public function save_meta( int $id, WP_Post $post ): void {
        if ( ! isset( $_POST['algq_digital_product_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['algq_digital_product_nonce'] ) ), 'algq_digital_product_save' ) ) return;
        if ( ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || wp_is_post_revision( $id ) || self::POST_TYPE !== $post->post_type || ! current_user_can( self::CAPABILITY ) ) return;

        $fields = array(
            'algq_product_type' => array( '_algq_product_type', 'sanitize_key' ),
            'algq_product_version' => array( '_algq_product_version', 'sanitize_text_field' ),
            'algq_product_sku' => array( '_algq_product_sku', 'sanitize_text_field' ),
            'algq_product_visibility' => array( '_algq_product_visibility', 'sanitize_key' ),
            'algq_delivery_mode' => array( '_algq_delivery_mode', 'sanitize_key' ),
            'algq_price_label' => array( '_algq_price_label', 'sanitize_text_field' ),
            'algq_woocommerce_product_id' => array( '_algq_woocommerce_product_id', 'absint' ),
            'algq_documentation_url' => array( '_algq_documentation_url', 'esc_url_raw' ),
            'algq_download_attachment_id' => array( '_algq_download_attachment_id', 'absint' ),
            'algq_cta_label' => array( '_algq_cta_label', 'sanitize_text_field' ),
        );
        foreach ( $fields as $input => $config ) {
            if ( isset( $_POST[ $input ] ) ) update_post_meta( $id, $config[0], call_user_func( $config[1], wp_unslash( $_POST[ $input ] ) ) );
        }
        $this->audit( 'digital_products.updated', array( 'product_id' => $id, 'status' => $post->post_status ) );
        do_action( 'algq_digital_product_saved', $id, $post );
    }

    public function admin_menu(): void {
        add_menu_page( __( 'Algonquian Digital Products', 'algq-digital-products' ), __( 'Digital Products', 'algq-digital-products' ), self::CAPABILITY, 'algq-digital-products', array( $this, 'admin_page' ), 'dashicons-products', 58 );
        add_submenu_page( 'algq-digital-products', __( 'All Digital Products', 'algq-digital-products' ), __( 'All Products', 'algq-digital-products' ), self::CAPABILITY, 'edit.php?post_type=' . self::POST_TYPE );
        add_submenu_page( 'algq-digital-products', __( 'Product Categories', 'algq-digital-products' ), __( 'Categories', 'algq-digital-products' ), self::CAPABILITY, 'edit-tags.php?taxonomy=' . self::TAXONOMY . '&post_type=' . self::POST_TYPE );
    }

    public function admin_page(): void {
        if ( ! current_user_can( self::CAPABILITY ) ) wp_die( esc_html__( 'You do not have permission to manage digital products.', 'algq-digital-products' ) );
        $counts = wp_count_posts( self::POST_TYPE );
        $health = $this->health_status();
        ?>
        <div class="wrap algq-dp-admin-wrap">
            <div class="algq-dp-admin-header"><div><span class="algq-dp-admin-eyebrow"><?php esc_html_e( 'Algonquian Real Estate Technology Division', 'algq-digital-products' ); ?></span><h1><?php esc_html_e( 'Algonquian Digital Products', 'algq-digital-products' ); ?></h1><p><?php esc_html_e( 'Manage the authoritative catalog, product metadata, visibility, delivery classification, and WooCommerce links.', 'algq-digital-products' ); ?></p></div><div class="algq-dp-admin-actions"><a class="button button-primary" href="<?php echo esc_url( admin_url( 'post-new.php?post_type=' . self::POST_TYPE ) ); ?>"><?php esc_html_e( 'Add Product', 'algq-digital-products' ); ?></a><a class="button" href="<?php echo esc_url( admin_url( 'edit.php?post_type=' . self::POST_TYPE ) ); ?>"><?php esc_html_e( 'Open Catalog', 'algq-digital-products' ); ?></a></div></div>
            <div class="algq-dp-admin-cards"><article><strong><?php echo esc_html( (string) ( $counts->publish ?? 0 ) ); ?></strong><span><?php esc_html_e( 'Published', 'algq-digital-products' ); ?></span></article><article><strong><?php echo esc_html( (string) ( $counts->draft ?? 0 ) ); ?></strong><span><?php esc_html_e( 'Drafts', 'algq-digital-products' ); ?></span></article><article><strong><?php echo esc_html( ALGQ_DIGITAL_PRODUCTS_VERSION ); ?></strong><span><?php esc_html_e( 'Plugin Version', 'algq-digital-products' ); ?></span></article><article><strong><?php echo esc_html( $health['status'] ); ?></strong><span><?php esc_html_e( 'System Health', 'algq-digital-products' ); ?></span></article></div>
            <div class="algq-dp-admin-panel"><h2><?php esc_html_e( 'Authority Boundary', 'algq-digital-products' ); ?></h2><p><?php esc_html_e( 'Digital Products owns catalog records and metadata. Digital Store and WooCommerce own checkout and orders. The WooCommerce Bridge or entitlement service owns post-purchase access and protected delivery.', 'algq-digital-products' ); ?></p><h2><?php esc_html_e( 'Shortcodes', 'algq-digital-products' ); ?></h2><code>[algq_digital_products]</code> <code>[algq_digital_product id="123"]</code></div>
        </div>
        <?php
    }

    public function admin_assets( string $hook ): void {
        $screen = get_current_screen();
        if ( ! $screen || ( self::POST_TYPE !== $screen->post_type && false === strpos( $hook, 'algq-digital-products' ) ) ) return;
        wp_enqueue_style( 'algq-digital-products-admin', ALGQ_DIGITAL_PRODUCTS_URL . 'assets/css/algq-digital-products-admin.css', array(), ALGQ_DIGITAL_PRODUCTS_VERSION );
        wp_enqueue_script( 'algq-digital-products-admin', ALGQ_DIGITAL_PRODUCTS_URL . 'assets/js/algq-digital-products-admin.js', array(), ALGQ_DIGITAL_PRODUCTS_VERSION, true );
    }

    public function register_rest_routes(): void {
        register_rest_route( 'algq/v1', '/digital-products', array( 'methods' => WP_REST_Server::READABLE, 'callback' => array( $this, 'rest_catalog' ), 'permission_callback' => '__return_true', 'args' => array( 'page' => array( 'default' => 1, 'sanitize_callback' => 'absint' ), 'per_page' => array( 'default' => 12, 'sanitize_callback' => 'absint' ), 'category' => array( 'sanitize_callback' => 'sanitize_title' ) ) ) );
        register_rest_route( 'algq/v1', '/digital-products/(?P<id>\d+)', array( 'methods' => WP_REST_Server::READABLE, 'callback' => array( $this, 'rest_product' ), 'permission_callback' => '__return_true', 'args' => array( 'id' => array( 'validate_callback' => static fn( $value ): bool => absint( $value ) > 0 ) ) ) );
    }

    public function rest_catalog( WP_REST_Request $request ): WP_REST_Response {
        $args = array( 'post_type' => self::POST_TYPE, 'post_status' => 'publish', 'posts_per_page' => min( 50, max( 1, absint( $request['per_page'] ) ) ), 'paged' => max( 1, absint( $request['page'] ) ), 'meta_query' => $this->visibility_query() );
        if ( $request['category'] ) $args['tax_query'] = array( array( 'taxonomy' => self::TAXONOMY, 'field' => 'slug', 'terms' => sanitize_title( $request['category'] ) ) );
        $query = new WP_Query( $args );
        $response = new WP_REST_Response( array_map( array( $this, 'rest_data' ), $query->posts ), 200 );
        $response->header( 'X-WP-Total', (string) $query->found_posts );
        $response->header( 'X-WP-TotalPages', (string) $query->max_num_pages );
        return $response;
    }

    public function rest_product( WP_REST_Request $request ) {
        $post = get_post( absint( $request['id'] ) );
        if ( ! $post || self::POST_TYPE !== $post->post_type || 'publish' !== $post->post_status || ! $this->visible( $post->ID ) ) return new WP_Error( 'algq_product_not_found', __( 'Digital product not found.', 'algq-digital-products' ), array( 'status' => 404 ) );
        return rest_ensure_response( $this->rest_data( $post ) );
    }

    public function rest_data( WP_Post $post ): array {
        $terms = wp_get_post_terms( $post->ID, self::TAXONOMY, array( 'fields' => 'names' ) );
        return array( 'id' => $post->ID, 'slug' => $post->post_name, 'title' => get_the_title( $post ), 'summary' => has_excerpt( $post ) ? get_the_excerpt( $post ) : wp_trim_words( wp_strip_all_tags( $post->post_content ), 30 ), 'url' => get_permalink( $post ), 'type' => (string) get_post_meta( $post->ID, '_algq_product_type', true ), 'version' => (string) get_post_meta( $post->ID, '_algq_product_version', true ), 'delivery' => (string) get_post_meta( $post->ID, '_algq_delivery_mode', true ), 'price_label' => $this->price( $post->ID ), 'categories' => is_wp_error( $terms ) ? array() : $terms, 'cta' => $this->cta( $post->ID ) );
    }

    public function admin_columns( array $columns ): array {
        return array_merge( $columns, array( 'algq_type' => __( 'Type', 'algq-digital-products' ), 'algq_version' => __( 'Version', 'algq-digital-products' ), 'algq_visibility' => __( 'Visibility', 'algq-digital-products' ), 'algq_woo' => __( 'WooCommerce', 'algq-digital-products' ) ) );
    }

    public function admin_column( string $column, int $id ): void {
        if ( 'algq_type' === $column ) echo esc_html( $this->type_label( (string) get_post_meta( $id, '_algq_product_type', true ) ) );
        elseif ( 'algq_version' === $column ) echo esc_html( (string) get_post_meta( $id, '_algq_product_version', true ) );
        elseif ( 'algq_visibility' === $column ) echo esc_html( ucfirst( (string) get_post_meta( $id, '_algq_product_visibility', true ) ?: 'public' ) );
        elseif ( 'algq_woo' === $column ) { $woo_id = absint( get_post_meta( $id, '_algq_woocommerce_product_id', true ) ); echo $woo_id ? esc_html( '#' . $woo_id ) : '&mdash;'; }
    }

    public function action_links( array $links ): array {
        array_unshift( $links, '<a href="' . esc_url( admin_url( 'admin.php?page=algq-digital-products' ) ) . '">' . esc_html__( 'Overview', 'algq-digital-products' ) . '</a>' );
        return $links;
    }

    public function health_status(): array {
        $checks = array( 'post_type_registered' => post_type_exists( self::POST_TYPE ), 'taxonomy_registered' => taxonomy_exists( self::TAXONOMY ), 'version_current' => ALGQ_DIGITAL_PRODUCTS_VERSION === get_option( self::OPTION_VERSION ), 'schema_current' => ALGQ_DIGITAL_PRODUCTS_SCHEMA_VERSION === get_option( self::OPTION_SCHEMA ), 'pages_recorded' => is_array( get_option( self::OPTION_PAGES, array() ) ) );
        return array( 'status' => in_array( false, $checks, true ) ? 'Degraded' : 'Operational', 'checks' => $checks );
    }

    private function grant_capabilities(): void {
        foreach ( array( 'administrator', 'shop_manager' ) as $role_name ) { $role = get_role( $role_name ); if ( $role ) $role->add_cap( self::CAPABILITY ); }
    }

    private function create_pages(): void {
        $pages = array(
            'digital-products' => array( 'title' => 'Digital Products', 'content' => '[vc_row full_width="stretch_row_content"][vc_column][vc_column_text][algq_digital_products][/vc_column_text][/vc_column][/vc_row]' ),
            'plugin/digital-products' => array( 'title' => 'Algonquian Digital Products', 'content' => '[vc_row full_width="stretch_row_content"][vc_column][vc_column_text]<h1>Algonquian Digital Products</h1><p>Authoritative catalog and metadata management for Algonquian Real Estate digital products.</p>[/vc_column_text][/vc_column][/vc_row]' ),
            'plugin/digital-products/start' => array( 'title' => 'Getting Started With Algonquian Digital Products', 'content' => '[vc_row][vc_column][vc_column_text]<h1>Getting Started With Algonquian Digital Products</h1><ol><li>Create a product record.</li><li>Assign type, version, visibility, and delivery mode.</li><li>Link its WooCommerce product where applicable.</li><li>Publish <code>[algq_digital_products]</code>.</li></ol>[/vc_column_text][/vc_column][/vc_row]' ),
            'plugin/digital-products/docs' => array( 'title' => 'Algonquian Digital Products Documentation', 'content' => '[vc_row][vc_column][vc_column_text]<h1>Algonquian Digital Products Documentation</h1><p>Shortcodes: <code>[algq_digital_products]</code> and <code>[algq_digital_product id="123"]</code>.</p><p>Checkout and payment processing remain the responsibility of Digital Store and WooCommerce. Entitlements and protected downloads must be enforced by the WooCommerce Bridge or designated platform service.</p>[/vc_column_text][/vc_column][/vc_row]' ),
        );
        $stored = get_option( self::OPTION_PAGES, array() );
        $stored = is_array( $stored ) ? $stored : array();
        foreach ( $pages as $path => $definition ) {
            $existing = get_page_by_path( $path );
            if ( $existing ) { $stored[ $path ] = $existing->ID; continue; }
            $parent = 0; $current = ''; $segments = explode( '/', $path );
            foreach ( $segments as $index => $segment ) {
                $current = $current ? $current . '/' . $segment : $segment;
                $found = get_page_by_path( $current );
                if ( $found ) { $parent = $found->ID; continue; }
                $leaf = $index === count( $segments ) - 1;
                $page_id = wp_insert_post( array( 'post_type' => 'page', 'post_status' => 'publish', 'post_parent' => $parent, 'post_name' => sanitize_title( $segment ), 'post_title' => sanitize_text_field( $leaf ? $definition['title'] : ucwords( str_replace( '-', ' ', $segment ) ) ), 'post_content' => $leaf ? $definition['content'] : '' ), true );
                if ( is_wp_error( $page_id ) ) break;
                $parent = (int) $page_id;
            }
            if ( $parent ) $stored[ $path ] = $parent;
        }
        update_option( self::OPTION_PAGES, $stored, false );
    }

    private function types(): array {
        return array( 'template' => __( 'Template', 'algq-digital-products' ), 'calculator' => __( 'Calculator', 'algq-digital-products' ), 'checklist' => __( 'Checklist', 'algq-digital-products' ), 'guide' => __( 'Guide', 'algq-digital-products' ), 'plugin' => __( 'WordPress Plugin', 'algq-digital-products' ), 'workflow' => __( 'Workflow', 'algq-digital-products' ), 'document_kit' => __( 'Document Kit', 'algq-digital-products' ), 'service' => __( 'Implementation Service', 'algq-digital-products' ) );
    }

    private function type_label( string $type ): string {
        return $this->types()[ $type ] ?? __( 'Digital Product', 'algq-digital-products' );
    }

    private function delivery_label( string $mode ): string {
        return array( 'download' => __( 'Protected download', 'algq-digital-products' ), 'license' => __( 'Software license', 'algq-digital-products' ), 'external' => __( 'External delivery', 'algq-digital-products' ), 'service' => __( 'Service delivery', 'algq-digital-products' ) )[ $mode ] ?? '';
    }

    private function audit( string $event, array $context = array() ): void {
        if ( function_exists( 'algq_log_event' ) ) { algq_log_event( $event, 'algq-digital-products', $context ); return; }
        do_action( 'algq_audit_event', $event, 'algq-digital-products', $context );
    }
}
