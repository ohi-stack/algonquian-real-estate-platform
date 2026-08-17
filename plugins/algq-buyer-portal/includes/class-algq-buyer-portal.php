<?php

defined( 'ABSPATH' ) || exit;

final class ALGQ_Buyer_Portal {
    private static ?self $instance = null;

    public static function instance(): self {
        return self::$instance ??= new self();
    }

    public function init(): void {
        add_action( 'init', array( $this, 'register_deal_post_type' ) );
        add_action( 'admin_menu', array( $this, 'admin_menu' ) );
        add_action( 'wp_enqueue_scripts', array( $this, 'assets' ) );

        add_shortcode( 'algq_buyer_registration', array( $this, 'registration_shortcode' ) );
        add_shortcode( 'algq_buyer_login', array( $this, 'login_shortcode' ) );
        add_shortcode( 'algq_buyer_dashboard', array( $this, 'dashboard_shortcode' ) );
        add_shortcode( 'algq_buyer_deals', array( $this, 'deals_shortcode' ) );

        add_action( 'admin_post_nopriv_algq_buyer_register', array( $this, 'handle_register' ) );
        add_action( 'admin_post_algq_accept_nda', array( $this, 'handle_accept_nda' ) );
        add_action( 'admin_post_algq_buyer_interest', array( $this, 'handle_interest' ) );
        add_action( 'admin_post_algq_buyer_download', array( $this, 'handle_download' ) );
    }

    public function assets(): void {
        wp_enqueue_style( 'algq-buyer-portal', ALGQ_BUYER_PORTAL_URL . 'assets/css/buyer-portal.css', array(), ALGQ_BUYER_PORTAL_VERSION );
    }

    public function register_deal_post_type(): void {
        register_post_type(
            'algq_buyer_deal',
            array(
                'labels'       => array( 'name' => 'Buyer Deals', 'singular_name' => 'Buyer Deal' ),
                'public'       => false,
                'show_ui'      => true,
                'show_in_menu' => true,
                'supports'     => array( 'title', 'editor', 'thumbnail', 'custom-fields' ),
                'map_meta_cap' => true,
                'capabilities' => array(
                    'edit_posts'   => 'algq_manage_buyer_deals',
                    'publish_posts'=> 'algq_manage_buyer_deals',
                    'delete_posts' => 'algq_manage_buyer_deals',
                    'read_private_posts' => 'algq_manage_buyer_deals',
                ),
                'menu_icon'    => 'dashicons-building',
            )
        );
    }

    public function admin_menu(): void {
        add_menu_page( 'Buyer Portal', 'Buyer Portal', 'algq_manage_buyer_portal', 'algq-buyer-portal', array( $this, 'admin_page' ), 'dashicons-shield-alt', 58 );
    }

    public function admin_page(): void {
        if ( ! current_user_can( 'algq_manage_buyer_portal' ) ) {
            wp_die( esc_html__( 'Unauthorized.', 'algq-buyer-portal' ) );
        }
        echo '<div class="wrap"><h1>Algonquian Buyer Portal</h1><p>Version ' . esc_html( ALGQ_BUYER_PORTAL_VERSION ) . '</p><p>Deal visibility is buyer-specific. Package downloads require an authorized buyer, a current NDA acceptance, and a protected WordPress attachment.</p></div>';
    }

    public function registration_shortcode(): string {
        if ( is_user_logged_in() ) {
            return '<div class="algq-portal"><p>You are already signed in.</p></div>';
        }
        $action = esc_url( admin_url( 'admin-post.php' ) );
        ob_start();
        ?>
        <div class="algq-portal"><h2>Buyer Registration</h2>
        <form method="post" action="<?php echo $action; ?>">
            <?php wp_nonce_field( 'algq_buyer_register' ); ?>
            <input type="hidden" name="action" value="algq_buyer_register">
            <label>Full name<input required name="name" autocomplete="name"></label>
            <label>Email<input required type="email" name="email" autocomplete="email"></label>
            <label>Company<input name="company" autocomplete="organization"></label>
            <label>Phone<input name="phone" autocomplete="tel"></label>
            <label>Target markets<textarea name="target_markets"></textarea></label>
            <label>Property types<textarea name="property_types"></textarea></label>
            <label><input required type="checkbox" name="terms_consent" value="1"> I agree to the site terms and privacy policy.</label>
            <button type="submit">Request Buyer Access</button>
        </form></div>
        <?php
        return (string) ob_get_clean();
    }

    public function login_shortcode(): string {
        ob_start();
        echo '<div class="algq-portal"><h2>Buyer Login</h2>';
        wp_login_form( array( 'redirect' => home_url( '/buyer-dashboard/' ) ) );
        echo '</div>';
        return (string) ob_get_clean();
    }

    public function dashboard_shortcode(): string {
        if ( ! $this->is_buyer() ) {
            return $this->access_message();
        }
        $deals = $this->get_visible_deals();
        $nda = $this->has_current_nda( get_current_user_id(), 0 );
        return '<div class="algq-portal"><h2>Buyer Dashboard</h2><div class="algq-grid"><div><strong>' . esc_html( (string) count( $deals ) ) . '</strong><span>Authorized Deals</span></div><div><strong>' . esc_html( $nda ? 'Accepted' : 'Required' ) . '</strong><span>General NDA</span></div></div><p><a class="algq-button" href="' . esc_url( home_url( '/buyer-deals/' ) ) . '">View Deals</a></p></div>';
    }

    public function deals_shortcode(): string {
        if ( ! $this->is_buyer() ) {
            return $this->access_message();
        }
        $deals = $this->get_visible_deals();
        ob_start();
        echo '<div class="algq-portal"><h2>Authorized Buyer Deals</h2>';
        if ( ! $deals ) {
            echo '<p>No deals are currently assigned to your buyer account.</p></div>';
            return (string) ob_get_clean();
        }
        echo '<div class="algq-deals">';
        foreach ( $deals as $deal ) {
            $nda = $this->has_current_nda( get_current_user_id(), $deal->ID );
            echo '<article class="algq-card"><h3>' . esc_html( get_the_title( $deal ) ) . '</h3><p>' . esc_html( wp_trim_words( $deal->post_content, 35 ) ) . '</p>';
            if ( ! $nda ) {
                echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
                wp_nonce_field( 'algq_accept_nda_' . $deal->ID );
                echo '<input type="hidden" name="action" value="algq_accept_nda"><input type="hidden" name="deal_id" value="' . esc_attr( (string) $deal->ID ) . '"><button type="submit">Accept NDA</button></form>';
            } else {
                echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
                wp_nonce_field( 'algq_buyer_interest_' . $deal->ID );
                echo '<input type="hidden" name="action" value="algq_buyer_interest"><input type="hidden" name="deal_id" value="' . esc_attr( (string) $deal->ID ) . '"><textarea name="message" placeholder="Optional message"></textarea><button type="submit">Submit Interest</button></form>';
                echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
                wp_nonce_field( 'algq_buyer_download_' . $deal->ID );
                echo '<input type="hidden" name="action" value="algq_buyer_download"><input type="hidden" name="deal_id" value="' . esc_attr( (string) $deal->ID ) . '"><button type="submit">Download Package</button></form>';
            }
            echo '</article>';
        }
        echo '</div></div>';
        return (string) ob_get_clean();
    }

    public function handle_register(): void {
        check_admin_referer( 'algq_buyer_register' );
        $email = sanitize_email( wp_unslash( $_POST['email'] ?? '' ) );
        $name = sanitize_text_field( wp_unslash( $_POST['name'] ?? '' ) );
        if ( ! is_email( $email ) || email_exists( $email ) || empty( $_POST['terms_consent'] ) ) {
            $this->redirect_back( 'registration-error' );
        }
        $user_id = wp_insert_user( array( 'user_login' => $email, 'user_email' => $email, 'display_name' => $name ?: $email, 'user_pass' => wp_generate_password( 20, true ) ) );
        if ( is_wp_error( $user_id ) ) {
            $this->redirect_back( 'registration-error' );
        }
        $user = new WP_User( $user_id );
        $user->set_role( 'algq_buyer' );
        foreach ( array( 'company', 'phone', 'target_markets', 'property_types' ) as $field ) {
            update_user_meta( $user_id, 'algq_buyer_' . $field, sanitize_textarea_field( wp_unslash( $_POST[ $field ] ?? '' ) ) );
        }
        update_user_meta( $user_id, 'algq_buyer_terms_accepted_at', current_time( 'mysql', true ) );
        wp_new_user_notification( $user_id, null, 'both' );
        wp_safe_redirect( home_url( '/buyers-login/?registered=1' ) );
        exit;
    }

    public function handle_accept_nda(): void {
        $this->require_buyer();
        $deal_id = absint( $_POST['deal_id'] ?? 0 );
        check_admin_referer( 'algq_accept_nda_' . $deal_id );
        $this->require_deal_access( $deal_id );
        global $wpdb;
        $wpdb->insert( $wpdb->prefix . 'algq_buyer_nda', array(
            'user_id' => get_current_user_id(),
            'deal_id' => $deal_id,
            'nda_version' => sanitize_text_field( get_option( 'algq_buyer_nda_version', '1.0' ) ),
            'accepted_at' => current_time( 'mysql', true ),
            'ip_hash' => hash( 'sha256', (string) ( $_SERVER['REMOTE_ADDR'] ?? '' ) ),
            'user_agent_hash' => hash( 'sha256', (string) ( $_SERVER['HTTP_USER_AGENT'] ?? '' ) ),
            'acceptance_uuid' => wp_generate_uuid4(),
        ) );
        $this->redirect_back( 'nda-accepted' );
    }

    public function handle_interest(): void {
        $this->require_buyer();
        $deal_id = absint( $_POST['deal_id'] ?? 0 );
        check_admin_referer( 'algq_buyer_interest_' . $deal_id );
        $this->require_deal_access( $deal_id );
        if ( ! $this->has_current_nda( get_current_user_id(), $deal_id ) ) {
            wp_die( esc_html__( 'NDA acceptance is required.', 'algq-buyer-portal' ), '', array( 'response' => 403 ) );
        }
        global $wpdb;
        $wpdb->insert( $wpdb->prefix . 'algq_buyer_interest', array( 'user_id' => get_current_user_id(), 'deal_id' => $deal_id, 'message' => sanitize_textarea_field( wp_unslash( $_POST['message'] ?? '' ) ), 'status' => 'new', 'created_at' => current_time( 'mysql', true ) ) );
        do_action( 'algq_buyer_interest_submitted', get_current_user_id(), $deal_id );
        $this->redirect_back( 'interest-submitted' );
    }

    public function handle_download(): void {
        $this->require_buyer();
        $deal_id = absint( $_POST['deal_id'] ?? 0 );
        check_admin_referer( 'algq_buyer_download_' . $deal_id );
        $this->require_deal_access( $deal_id );
        if ( ! $this->has_current_nda( get_current_user_id(), $deal_id ) ) {
            wp_die( esc_html__( 'NDA acceptance is required.', 'algq-buyer-portal' ), '', array( 'response' => 403 ) );
        }
        $attachment_id = absint( get_post_meta( $deal_id, '_algq_package_attachment_id', true ) );
        $path = $attachment_id ? get_attached_file( $attachment_id ) : '';
        if ( ! $path || ! is_readable( $path ) ) {
            wp_die( esc_html__( 'The package is unavailable.', 'algq-buyer-portal' ), '', array( 'response' => 404 ) );
        }
        global $wpdb;
        $wpdb->insert( $wpdb->prefix . 'algq_buyer_downloads', array( 'user_id' => get_current_user_id(), 'deal_id' => $deal_id, 'attachment_id' => $attachment_id, 'file_hash' => hash_file( 'sha256', $path ) ?: '', 'created_at' => current_time( 'mysql', true ) ) );
        nocache_headers();
        header( 'Content-Type: application/octet-stream' );
        header( 'Content-Disposition: attachment; filename="' . rawurlencode( basename( $path ) ) . '"' );
        header( 'Content-Length: ' . (string) filesize( $path ) );
        readfile( $path );
        exit;
    }

    private function get_visible_deals(): array {
        $user_id = get_current_user_id();
        return get_posts( array(
            'post_type' => 'algq_buyer_deal',
            'post_status' => 'publish',
            'numberposts' => 100,
            'meta_query' => array( array( 'key' => '_algq_authorized_buyer_ids', 'value' => '"' . $user_id . '"', 'compare' => 'LIKE' ) ),
        ) );
    }

    private function has_current_nda( int $user_id, int $deal_id ): bool {
        global $wpdb;
        $version = sanitize_text_field( get_option( 'algq_buyer_nda_version', '1.0' ) );
        return (bool) $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$wpdb->prefix}algq_buyer_nda WHERE user_id = %d AND deal_id = %d AND nda_version = %s ORDER BY id DESC LIMIT 1", $user_id, $deal_id, $version ) );
    }

    private function is_buyer(): bool {
        return is_user_logged_in() && current_user_can( 'algq_view_buyer_portal' );
    }

    private function require_buyer(): void {
        if ( ! $this->is_buyer() ) {
            wp_die( esc_html__( 'Buyer access is required.', 'algq-buyer-portal' ), '', array( 'response' => 403 ) );
        }
    }

    private function require_deal_access( int $deal_id ): void {
        $ids = get_post_meta( $deal_id, '_algq_authorized_buyer_ids', true );
        $ids = is_array( $ids ) ? array_map( 'absint', $ids ) : array();
        if ( 'algq_buyer_deal' !== get_post_type( $deal_id ) || ! in_array( get_current_user_id(), $ids, true ) ) {
            wp_die( esc_html__( 'You are not authorized for this deal.', 'algq-buyer-portal' ), '', array( 'response' => 403 ) );
        }
    }

    private function access_message(): string {
        return '<div class="algq-portal"><p>Authorized buyer access is required. <a href="' . esc_url( home_url( '/buyers-login/' ) ) . '">Sign in</a></p></div>';
    }

    private function redirect_back( string $status ): void {
        $url = wp_get_referer() ?: home_url( '/buyer-dashboard/' );
        wp_safe_redirect( add_query_arg( 'algq_status', rawurlencode( $status ), $url ) );
        exit;
    }
}
