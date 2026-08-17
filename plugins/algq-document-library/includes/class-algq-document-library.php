<?php
/**
 * Main plugin coordinator.
 */

defined( 'ABSPATH' ) || exit;

final class ALGQ_Document_Library {

    private static bool $booted = false;

    /**
     * Initialize the plugin once WordPress and companion plugins are available.
     */
    public static function boot(): void {
        if ( self::$booted ) {
            return;
        }
        self::$booted = true;

        load_plugin_textdomain( 'algq-document-library', false, dirname( ALGQ_DOC_LIB_BASENAME ) . '/languages' );

        add_action( 'init', array( __CLASS__, 'register_content_types' ) );
        add_action( 'init', array( __CLASS__, 'register_shortcodes' ) );
        add_action( 'rest_api_init', array( __CLASS__, 'register_rest_routes' ) );
        add_action( 'admin_menu', array( __CLASS__, 'admin_menu' ) );
        add_action( 'add_meta_boxes', array( __CLASS__, 'meta_boxes' ) );
        add_action( 'save_post_algq_document', array( __CLASS__, 'save_document' ), 10, 2 );
        add_action( 'save_post_algq_doc_package', array( __CLASS__, 'save_package' ), 10, 2 );
        add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_assets' ) );
        add_action( 'admin_notices', array( __CLASS__, 'admin_notices' ) );
        add_action( 'post_edit_form_tag', array( __CLASS__, 'post_edit_form_tag' ) );
        add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue_assets' ) );
        add_action( 'admin_post_algq_document_download', array( __CLASS__, 'handle_download' ) );
        add_action( 'admin_post_nopriv_algq_document_download', array( __CLASS__, 'handle_download' ) );
        add_action( 'admin_init', array( __CLASS__, 'maybe_upgrade' ) );
        add_filter( 'plugin_action_links_' . ALGQ_DOC_LIB_BASENAME, array( __CLASS__, 'plugin_action_links' ) );
        add_filter( 'algq_platform_plugin_registry', array( __CLASS__, 'register_with_platform' ) );

        ALGQ_Document_Library_Requests::init();
    }

    /**
     * Run idempotent schema upgrades.
     */
    public static function maybe_upgrade(): void {
        if ( ALGQ_DOC_LIB_SCHEMA_VERSION === get_option( 'algq_document_library_schema_version' ) ) {
            return;
        }

        ALGQ_Document_Library_Activator::create_tables();
        ALGQ_Document_Library_Activator::grant_capabilities();
        ALGQ_Document_Library_Storage::ensure_private_directory();
        self::create_generated_pages();
        update_option( 'algq_document_library_schema_version', ALGQ_DOC_LIB_SCHEMA_VERSION, false );
    }

    /**
     * Register authoritative document and package records.
     */
    public static function register_content_types(): void {
        register_post_type(
            'algq_document',
            array(
                'labels' => array(
                    'name'               => __( 'ARE Documents', 'algq-document-library' ),
                    'singular_name'      => __( 'ARE Document', 'algq-document-library' ),
                    'add_new_item'       => __( 'Add Document', 'algq-document-library' ),
                    'edit_item'          => __( 'Edit Document', 'algq-document-library' ),
                    'view_item'          => __( 'View Document Record', 'algq-document-library' ),
                    'search_items'       => __( 'Search Documents', 'algq-document-library' ),
                    'not_found'          => __( 'No documents found.', 'algq-document-library' ),
                    'not_found_in_trash' => __( 'No documents found in Trash.', 'algq-document-library' ),
                ),
                'public'              => false,
                'show_ui'             => true,
                'show_in_menu'        => false,
                'show_in_rest'        => false,
                'supports'            => array( 'title', 'editor', 'excerpt', 'author', 'revisions' ),
                'map_meta_cap'        => true,
                'capability_type'     => array( 'algq_document', 'algq_documents' ),
                'capabilities'        => self::document_capability_map(),
                'delete_with_user'    => false,
                'exclude_from_search' => true,
            )
        );

        register_post_type(
            'algq_doc_package',
            array(
                'labels' => array(
                    'name'          => __( 'Document Packages', 'algq-document-library' ),
                    'singular_name' => __( 'Document Package', 'algq-document-library' ),
                    'add_new_item'  => __( 'Assemble Document Package', 'algq-document-library' ),
                    'edit_item'     => __( 'Edit Document Package', 'algq-document-library' ),
                ),
                'public'          => false,
                'show_ui'         => true,
                'show_in_menu'    => false,
                'supports'        => array( 'title', 'editor', 'revisions' ),
                'map_meta_cap'    => true,
                'capability_type' => array( 'algq_doc_package', 'algq_doc_packages' ),
                'capabilities'    => array(
                    'edit_post'              => 'assemble_algq_document_packages',
                    'read_post'              => 'view_algq_documents',
                    'delete_post'            => 'assemble_algq_document_packages',
                    'edit_posts'             => 'assemble_algq_document_packages',
                    'edit_others_posts'      => 'assemble_algq_document_packages',
                    'publish_posts'          => 'assemble_algq_document_packages',
                    'read_private_posts'     => 'view_algq_documents',
                    'delete_posts'           => 'assemble_algq_document_packages',
                    'delete_private_posts'   => 'assemble_algq_document_packages',
                    'delete_published_posts' => 'assemble_algq_document_packages',
                    'delete_others_posts'    => 'assemble_algq_document_packages',
                    'edit_private_posts'     => 'assemble_algq_document_packages',
                    'edit_published_posts'   => 'assemble_algq_document_packages',
                    'create_posts'           => 'assemble_algq_document_packages',
                ),
            )
        );

        register_taxonomy(
            'algq_doc_category',
            array( 'algq_document' ),
            array(
                'labels'            => array(
                    'name'          => __( 'Document Categories', 'algq-document-library' ),
                    'singular_name' => __( 'Document Category', 'algq-document-library' ),
                ),
                'public'            => false,
                'show_ui'           => true,
                'show_admin_column' => true,
                'show_in_rest'      => false,
                'hierarchical'      => true,
                'capabilities'      => array(
                    'manage_terms' => 'manage_algq_documents',
                    'edit_terms'   => 'manage_algq_documents',
                    'delete_terms' => 'manage_algq_documents',
                    'assign_terms' => 'manage_algq_documents',
                ),
            )
        );

        self::ensure_category_terms();
    }

    /**
     * Register public interfaces.
     */
    public static function register_shortcodes(): void {
        add_shortcode( 'algq_document_library', array( __CLASS__, 'shortcode_library' ) );
        add_shortcode( 'algq_document_request', array( 'ALGQ_Document_Library_Requests', 'render_form' ) );
        add_shortcode( 'algq_document_packages', array( __CLASS__, 'shortcode_packages' ) );
    }

    /**
     * Register a least-privilege REST read endpoint.
     */
    public static function register_rest_routes(): void {
        register_rest_route(
            'algq/v1',
            '/documents',
            array(
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => array( __CLASS__, 'rest_documents' ),
                'permission_callback' => static fn(): bool => current_user_can( 'view_algq_documents' ),
                'args'                => array(
                    'category' => array( 'sanitize_callback' => 'sanitize_key' ),
                    'page'     => array( 'sanitize_callback' => 'absint', 'default' => 1 ),
                    'per_page' => array( 'sanitize_callback' => 'absint', 'default' => 20 ),
                ),
            )
        );
    }

    /**
     * Return document metadata without exposing storage paths.
     */
    public static function rest_documents( WP_REST_Request $request ): WP_REST_Response {
        $page     = max( 1, (int) $request->get_param( 'page' ) );
        $per_page = min( 100, max( 1, (int) $request->get_param( 'per_page' ) ) );
        $category = sanitize_key( (string) $request->get_param( 'category' ) );
        $args     = array(
            'post_type'      => 'algq_document',
            'post_status'    => 'publish',
            'paged'          => $page,
            'posts_per_page' => $per_page,
            'orderby'        => 'title',
            'order'          => 'ASC',
        );

        if ( $category ) {
            $args['tax_query'] = array(
                array(
                    'taxonomy' => 'algq_doc_category',
                    'field'    => 'slug',
                    'terms'    => $category,
                ),
            );
        }

        $query = new WP_Query( $args );
        $items = array_map(
            static function ( WP_Post $document ): array {
                return array(
                    'id'              => $document->ID,
                    'title'           => get_the_title( $document ),
                    'summary'         => get_the_excerpt( $document ),
                    'version'         => (string) get_post_meta( $document->ID, '_algq_doc_version', true ),
                    'access'          => (string) get_post_meta( $document->ID, '_algq_doc_access', true ),
                    'confidentiality' => (string) get_post_meta( $document->ID, '_algq_doc_confidentiality', true ),
                    'expires_at'      => (string) get_post_meta( $document->ID, '_algq_doc_expires_at', true ),
                    'has_file'        => (bool) get_post_meta( $document->ID, '_algq_doc_private_path', true ),
                );
            },
            $query->posts
        );

        $response = new WP_REST_Response( $items, 200 );
        $response->header( 'X-WP-Total', (string) $query->found_posts );
        $response->header( 'X-WP-TotalPages', (string) $query->max_num_pages );
        return $response;
    }

    /**
     * Add the plugin's administrative navigation.
     */
    public static function admin_menu(): void {
        add_menu_page(
            __( 'Document Library', 'algq-document-library' ),
            __( 'Document Library', 'algq-document-library' ),
            'view_algq_documents',
            'algq-document-library',
            array( __CLASS__, 'admin_overview' ),
            'dashicons-media-document',
            27
        );
        add_submenu_page( 'algq-document-library', __( 'Overview', 'algq-document-library' ), __( 'Overview', 'algq-document-library' ), 'view_algq_documents', 'algq-document-library', array( __CLASS__, 'admin_overview' ) );
        add_submenu_page( 'algq-document-library', __( 'All Documents', 'algq-document-library' ), __( 'All Documents', 'algq-document-library' ), 'view_algq_documents', 'edit.php?post_type=algq_document' );
        add_submenu_page( 'algq-document-library', __( 'Add Document', 'algq-document-library' ), __( 'Add Document', 'algq-document-library' ), 'upload_algq_documents', 'post-new.php?post_type=algq_document' );
        add_submenu_page( 'algq-document-library', __( 'Document Packages', 'algq-document-library' ), __( 'Packages', 'algq-document-library' ), 'assemble_algq_document_packages', 'edit.php?post_type=algq_doc_package' );
        add_submenu_page( 'algq-document-library', __( 'Access Requests', 'algq-document-library' ), __( 'Access Requests', 'algq-document-library' ), 'manage_algq_document_requests', 'algq-document-requests', array( 'ALGQ_Document_Library_Requests', 'admin_page' ) );
        add_submenu_page( 'algq-document-library', __( 'Documentation', 'algq-document-library' ), __( 'Documentation', 'algq-document-library' ), 'view_algq_documents', 'algq-document-library-docs', array( __CLASS__, 'docs_page' ) );
    }

    /**
     * Enqueue scoped styles and scripts only where used.
     */
    public static function enqueue_assets( string $hook = '' ): void {
        $is_admin_screen = is_admin() && ( str_contains( $hook, 'algq-document' ) || 'post.php' === $hook || 'post-new.php' === $hook || 'edit.php' === $hook );
        $is_public_page  = ! is_admin() && is_singular();

        if ( ! $is_admin_screen && ! $is_public_page ) {
            return;
        }

        wp_enqueue_style( 'algq-document-library', ALGQ_DOC_LIB_URL . 'assets/css/algq-document-library.css', array(), ALGQ_DOC_LIB_VERSION );
        wp_enqueue_script( 'algq-document-library', ALGQ_DOC_LIB_URL . 'assets/js/algq-document-library.js', array(), ALGQ_DOC_LIB_VERSION, true );
    }

    /**
     * Ensure the post editor can receive protected file uploads.
     */
    public static function post_edit_form_tag( WP_Post $post ): void {
        if ( 'algq_document' === $post->post_type ) {
            echo ' enctype="multipart/form-data"';
        }
    }

    /**
     * Surface upload validation errors to the current administrator.
     */
    public static function admin_notices(): void {
        $key     = 'algq_doc_upload_error_' . get_current_user_id();
        $message = get_transient( $key );
        if ( ! $message ) {
            return;
        }
        delete_transient( $key );
        echo '<div class="notice notice-error is-dismissible"><p>' . esc_html( (string) $message ) . '</p></div>';
    }

    /**
     * Register document and package controls.
     */
    public static function meta_boxes(): void {
        add_meta_box( 'algq_doc_controls', __( 'Document Controls', 'algq-document-library' ), array( __CLASS__, 'document_meta_box' ), 'algq_document', 'normal', 'high' );
        add_meta_box( 'algq_doc_file', __( 'Protected File', 'algq-document-library' ), array( __CLASS__, 'document_file_meta_box' ), 'algq_document', 'side', 'high' );
        add_meta_box( 'algq_package_documents', __( 'Package Documents', 'algq-document-library' ), array( __CLASS__, 'package_meta_box' ), 'algq_doc_package', 'normal', 'high' );
    }

    /**
     * Render document governance fields.
     */
    public static function document_meta_box( WP_Post $post ): void {
        wp_nonce_field( 'algq_doc_save', 'algq_doc_nonce' );
        $version         = (string) ( get_post_meta( $post->ID, '_algq_doc_version', true ) ?: '1.0.0' );
        $access          = (string) ( get_post_meta( $post->ID, '_algq_doc_access', true ) ?: 'approved_request' );
        $confidentiality = (string) ( get_post_meta( $post->ID, '_algq_doc_confidentiality', true ) ?: 'confidential' );
        $expires_at      = (string) get_post_meta( $post->ID, '_algq_doc_expires_at', true );
        $retention       = (string) ( get_post_meta( $post->ID, '_algq_doc_retention', true ) ?: 'business_record' );
        $related_deal    = absint( get_post_meta( $post->ID, '_algq_doc_related_deal_id', true ) );
        $legal_hold      = (bool) get_post_meta( $post->ID, '_algq_doc_legal_hold', true );
        $is_template     = (bool) get_post_meta( $post->ID, '_algq_doc_is_template', true );
        ?>
        <div class="algq-form-grid algq-admin-form">
            <label><span><?php esc_html_e( 'Version', 'algq-document-library' ); ?></span><input name="algq_doc_version" value="<?php echo esc_attr( $version ); ?>" pattern="[0-9]+\.[0-9]+\.[0-9]+(?:[-+][A-Za-z0-9.-]+)?" /></label>
            <label><span><?php esc_html_e( 'Access classification', 'algq-document-library' ); ?></span><select name="algq_doc_access">
                <?php foreach ( self::access_levels() as $key => $label ) : ?><option value="<?php echo esc_attr( $key ); ?>" <?php selected( $access, $key ); ?>><?php echo esc_html( $label ); ?></option><?php endforeach; ?>
            </select></label>
            <label><span><?php esc_html_e( 'Confidentiality', 'algq-document-library' ); ?></span><select name="algq_doc_confidentiality">
                <?php foreach ( self::confidentiality_levels() as $key => $label ) : ?><option value="<?php echo esc_attr( $key ); ?>" <?php selected( $confidentiality, $key ); ?>><?php echo esc_html( $label ); ?></option><?php endforeach; ?>
            </select></label>
            <label><span><?php esc_html_e( 'Expiration date', 'algq-document-library' ); ?></span><input type="date" name="algq_doc_expires_at" value="<?php echo esc_attr( $expires_at ); ?>" /></label>
            <label><span><?php esc_html_e( 'Retention classification', 'algq-document-library' ); ?></span><select name="algq_doc_retention">
                <?php foreach ( self::retention_classes() as $key => $label ) : ?><option value="<?php echo esc_attr( $key ); ?>" <?php selected( $retention, $key ); ?>><?php echo esc_html( $label ); ?></option><?php endforeach; ?>
            </select></label>
            <label><span><?php esc_html_e( 'Related deal ID', 'algq-document-library' ); ?></span><input type="number" min="0" name="algq_doc_related_deal_id" value="<?php echo esc_attr( (string) $related_deal ); ?>" /></label>
        </div>
        <p><label><input type="checkbox" name="algq_doc_is_template" value="1" <?php checked( $is_template ); ?> /> <?php esc_html_e( 'This record is a reusable document template.', 'algq-document-library' ); ?></label></p>
        <p><label><input type="checkbox" name="algq_doc_legal_hold" value="1" <?php checked( $legal_hold ); ?> /> <?php esc_html_e( 'Legal hold: prevent routine deletion or replacement without explicit administrative action.', 'algq-document-library' ); ?></label></p>
        <?php
    }

    /**
     * Render private upload controls and integrity information.
     */
    public static function document_file_meta_box( WP_Post $post ): void {
        $original_name = (string) get_post_meta( $post->ID, '_algq_doc_original_name', true );
        $file_hash     = (string) get_post_meta( $post->ID, '_algq_doc_file_hash', true );
        $file_size     = absint( get_post_meta( $post->ID, '_algq_doc_file_size', true ) );
        $has_file      = (bool) get_post_meta( $post->ID, '_algq_doc_private_path', true );
        ?>
        <?php if ( $has_file ) : ?>
            <p><strong><?php echo esc_html( $original_name ); ?></strong></p>
            <p><?php echo esc_html( size_format( $file_size ) ); ?></p>
            <p><code class="algq-hash"><?php echo esc_html( $file_hash ); ?></code></p>
            <?php if ( self::user_can_download( $post->ID ) ) : ?>
                <p><a class="button" href="<?php echo esc_url( self::download_url( $post->ID ) ); ?>"><?php esc_html_e( 'Download Current File', 'algq-document-library' ); ?></a></p>
            <?php endif; ?>
        <?php else : ?>
            <p><?php esc_html_e( 'No protected file is attached.', 'algq-document-library' ); ?></p>
        <?php endif; ?>
        <p><label for="algq_doc_upload"><strong><?php esc_html_e( 'Upload or replace file', 'algq-document-library' ); ?></strong></label></p>
        <input id="algq_doc_upload" type="file" name="algq_doc_upload" />
        <p class="description"><?php esc_html_e( 'Files are moved to protected storage and delivered only through the authorized download controller.', 'algq-document-library' ); ?></p>
        <?php
    }

    /**
     * Render package membership controls.
     */
    public static function package_meta_box( WP_Post $post ): void {
        wp_nonce_field( 'algq_package_save', 'algq_package_nonce' );
        $selected = array_map( 'absint', (array) get_post_meta( $post->ID, '_algq_package_documents', true ) );
        $documents = get_posts(
            array(
                'post_type'      => 'algq_document',
                'post_status'    => 'publish',
                'posts_per_page' => 500,
                'orderby'        => 'title',
                'order'          => 'ASC',
            )
        );
        ?>
        <p><?php esc_html_e( 'Select the authoritative document records included in this package. Downloads still enforce each document’s access classification.', 'algq-document-library' ); ?></p>
        <div class="algq-package-selector">
            <?php foreach ( $documents as $document ) : ?>
                <label><input type="checkbox" name="algq_package_documents[]" value="<?php echo esc_attr( (string) $document->ID ); ?>" <?php checked( in_array( $document->ID, $selected, true ) ); ?> /> <?php echo esc_html( get_the_title( $document ) ); ?></label>
            <?php endforeach; ?>
        </div>
        <?php
    }

    /**
     * Persist document controls and private file revisions.
     */
    public static function save_document( int $post_id, WP_Post $post ): void {
        if ( wp_is_post_revision( $post_id ) || wp_is_post_autosave( $post_id ) ) {
            return;
        }
        if ( ! isset( $_POST['algq_doc_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['algq_doc_nonce'] ) ), 'algq_doc_save' ) ) {
            return;
        }
        if ( ! current_user_can( 'edit_post', $post_id ) ) {
            return;
        }

        $version = sanitize_text_field( wp_unslash( $_POST['algq_doc_version'] ?? '1.0.0' ) );
        if ( ! preg_match( '/^\d+\.\d+\.\d+(?:[-+][A-Za-z0-9.-]+)?$/', $version ) ) {
            $version = '1.0.0';
        }

        $access          = sanitize_key( wp_unslash( $_POST['algq_doc_access'] ?? 'approved_request' ) );
        $confidentiality = sanitize_key( wp_unslash( $_POST['algq_doc_confidentiality'] ?? 'confidential' ) );
        $retention       = sanitize_key( wp_unslash( $_POST['algq_doc_retention'] ?? 'business_record' ) );
        $expires_at      = sanitize_text_field( wp_unslash( $_POST['algq_doc_expires_at'] ?? '' ) );

        update_post_meta( $post_id, '_algq_doc_version', $version );
        update_post_meta( $post_id, '_algq_doc_access', array_key_exists( $access, self::access_levels() ) ? $access : 'approved_request' );
        update_post_meta( $post_id, '_algq_doc_confidentiality', array_key_exists( $confidentiality, self::confidentiality_levels() ) ? $confidentiality : 'confidential' );
        update_post_meta( $post_id, '_algq_doc_retention', array_key_exists( $retention, self::retention_classes() ) ? $retention : 'business_record' );
        update_post_meta( $post_id, '_algq_doc_expires_at', preg_match( '/^\d{4}-\d{2}-\d{2}$/', $expires_at ) ? $expires_at : '' );
        update_post_meta( $post_id, '_algq_doc_related_deal_id', absint( $_POST['algq_doc_related_deal_id'] ?? 0 ) );
        update_post_meta( $post_id, '_algq_doc_is_template', ! empty( $_POST['algq_doc_is_template'] ) ? 1 : 0 );
        update_post_meta( $post_id, '_algq_doc_legal_hold', ! empty( $_POST['algq_doc_legal_hold'] ) ? 1 : 0 );

        if ( ! empty( $_FILES['algq_doc_upload']['name'] ) && current_user_can( 'upload_algq_documents' ) ) {
            $result = ALGQ_Document_Library_Storage::store_upload( $_FILES['algq_doc_upload'] ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
            if ( is_wp_error( $result ) ) {
                set_transient( 'algq_doc_upload_error_' . get_current_user_id(), $result->get_error_message(), MINUTE_IN_SECONDS );
            } else {
                self::archive_current_file_metadata( $post_id );
                update_post_meta( $post_id, '_algq_doc_private_path', $result['relative_path'] );
                update_post_meta( $post_id, '_algq_doc_original_name', $result['original_name'] );
                update_post_meta( $post_id, '_algq_doc_mime_type', $result['mime_type'] );
                update_post_meta( $post_id, '_algq_doc_file_hash', $result['file_hash'] );
                update_post_meta( $post_id, '_algq_doc_file_size', absint( $result['file_size'] ) );
                update_post_meta( $post_id, '_algq_doc_file_uploaded_at', current_time( 'mysql', true ) );
                update_post_meta( $post_id, '_algq_doc_file_uploaded_by', get_current_user_id() );
                self::audit( 'document.file_uploaded', array( 'document_id' => $post_id, 'version' => $version, 'file_hash' => $result['file_hash'] ) );
            }
        }

        self::audit( 'document.record_updated', array( 'document_id' => $post_id, 'version' => $version, 'status' => $post->post_status ) );
    }

    /**
     * Persist package document relationships.
     */
    public static function save_package( int $post_id, WP_Post $post ): void {
        if ( wp_is_post_revision( $post_id ) || wp_is_post_autosave( $post_id ) ) {
            return;
        }
        if ( ! isset( $_POST['algq_package_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['algq_package_nonce'] ) ), 'algq_package_save' ) ) {
            return;
        }
        if ( ! current_user_can( 'edit_post', $post_id ) ) {
            return;
        }

        $document_ids = array_values( array_unique( array_filter( array_map( 'absint', (array) ( $_POST['algq_package_documents'] ?? array() ) ) ) ) );
        $document_ids = array_values( array_filter( $document_ids, static fn( int $id ): bool => 'algq_document' === get_post_type( $id ) ) );
        update_post_meta( $post_id, '_algq_package_documents', $document_ids );
        self::audit( 'document_package.updated', array( 'package_id' => $post_id, 'document_ids' => $document_ids, 'status' => $post->post_status ) );
    }

    /**
     * Render the categorized front-end library.
     */
    public static function shortcode_library( array $atts = array() ): string {
        $atts = shortcode_atts(
            array(
                'show_request_form' => 'yes',
                'category'          => '',
            ),
            $atts,
            'algq_document_library'
        );

        $term_args = array(
            'taxonomy'   => 'algq_doc_category',
            'hide_empty' => false,
        );
        $requested_category = sanitize_key( (string) $atts['category'] );
        if ( $requested_category ) {
            $term_args['slug'] = $requested_category;
        }
        $terms = get_terms( $term_args );

        if ( is_wp_error( $terms ) ) {
            return '';
        }

        ob_start();
        ?>
        <section class="algq-doclib" aria-labelledby="algq-doclib-title">
            <header class="algq-doclib-hero">
                <span class="algq-eyebrow"><?php esc_html_e( 'Algonquian Real Estate Platform', 'algq-document-library' ); ?></span>
                <h2 id="algq-doclib-title"><?php esc_html_e( 'Institutional Document Library', 'algq-document-library' ); ?></h2>
                <p><?php esc_html_e( 'Controlled access to entity, lender, acquisition, financial, compliance, and property-management records.', 'algq-document-library' ); ?></p>
            </header>
            <div class="algq-doclib-toolbar">
                <label><span class="screen-reader-text"><?php esc_html_e( 'Search documents', 'algq-document-library' ); ?></span><input type="search" data-algq-doc-search placeholder="<?php esc_attr_e( 'Search the document library', 'algq-document-library' ); ?>" /></label>
            </div>
            <?php foreach ( $terms as $term ) : ?>
                <?php
                $documents = get_posts(
                    array(
                        'post_type'      => 'algq_document',
                        'post_status'    => 'publish',
                        'posts_per_page' => -1,
                        'orderby'        => 'title',
                        'order'          => 'ASC',
                        'tax_query'      => array(
                            array(
                                'taxonomy' => 'algq_doc_category',
                                'field'    => 'term_id',
                                'terms'    => $term->term_id,
                            ),
                        ),
                    )
                );
                ?>
                <section class="algq-doc-category" data-algq-doc-category>
                    <h3><?php echo esc_html( $term->name ); ?></h3>
                    <?php if ( empty( $documents ) ) : ?>
                        <div class="algq-empty-state"><?php esc_html_e( 'No published records are currently listed in this category.', 'algq-document-library' ); ?></div>
                    <?php else : ?>
                        <div class="algq-doc-grid">
                            <?php foreach ( $documents as $document ) : ?>
                                <?php echo self::document_card( $document ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </section>
            <?php endforeach; ?>
            <?php if ( 'yes' === strtolower( (string) $atts['show_request_form'] ) ) : ?>
                <?php echo ALGQ_Document_Library_Requests::render_form(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
            <?php endif; ?>
        </section>
        <?php
        return (string) ob_get_clean();
    }

    /**
     * Render published document packages.
     */
    public static function shortcode_packages(): string {
        $packages = get_posts(
            array(
                'post_type'      => 'algq_doc_package',
                'post_status'    => 'publish',
                'posts_per_page' => -1,
                'orderby'        => 'title',
                'order'          => 'ASC',
            )
        );

        ob_start();
        ?><div class="algq-doc-packages">
            <?php foreach ( $packages as $package ) : ?>
                <?php $document_ids = array_map( 'absint', (array) get_post_meta( $package->ID, '_algq_package_documents', true ) ); ?>
                <article class="algq-package-card">
                    <h3><?php echo esc_html( get_the_title( $package ) ); ?></h3>
                    <div><?php echo wp_kses_post( wpautop( $package->post_content ) ); ?></div>
                    <p><?php echo esc_html( sprintf( _n( '%d document', '%d documents', count( $document_ids ), 'algq-document-library' ), count( $document_ids ) ) ); ?></p>
                    <a class="algq-btn" href="#algq-document-request"><?php esc_html_e( 'Request Package Access', 'algq-document-library' ); ?></a>
                </article>
            <?php endforeach; ?>
        </div><?php
        return (string) ob_get_clean();
    }

    /**
     * Handle all protected file delivery.
     */
    public static function handle_download(): void {
        $document_id = absint( $_GET['document_id'] ?? 0 );
        $nonce       = sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ?? '' ) );

        if ( ! $document_id || ! wp_verify_nonce( $nonce, 'algq_document_download_' . $document_id ) ) {
            self::log_download( $document_id, 'denied', 'invalid_nonce' );
            wp_die( esc_html__( 'Invalid or expired download link.', 'algq-document-library' ), 403 );
        }

        if ( ! self::user_can_download( $document_id ) ) {
            self::log_download( $document_id, 'denied', 'not_authorized' );
            wp_die( esc_html__( 'You are not authorized to download this document.', 'algq-document-library' ), 403 );
        }

        if ( ! get_post_meta( $document_id, '_algq_doc_private_path', true ) ) {
            self::log_download( $document_id, 'denied', 'file_missing' );
            wp_die( esc_html__( 'No protected file is attached to this record.', 'algq-document-library' ), 404 );
        }

        self::log_download( $document_id, 'delivered', '' );
        self::audit( 'document.downloaded', array( 'document_id' => $document_id, 'user_id' => get_current_user_id() ) );
        ALGQ_Document_Library_Storage::stream_file( $document_id );
    }

    /**
     * Decide whether the current user may retrieve a record.
     */
    public static function user_can_download( int $document_id ): bool {
        if ( 'algq_document' !== get_post_type( $document_id ) || 'publish' !== get_post_status( $document_id ) ) {
            return false;
        }

        if ( current_user_can( 'manage_algq_documents' ) || current_user_can( 'download_algq_documents' ) ) {
            return true;
        }

        $expires_at = (string) get_post_meta( $document_id, '_algq_doc_expires_at', true );
        if ( $expires_at && strtotime( $expires_at . ' 23:59:59' ) < current_time( 'timestamp', true ) ) {
            return false;
        }

        $access  = (string) get_post_meta( $document_id, '_algq_doc_access', true );
        $allowed = false;

        switch ( $access ) {
            case 'public':
                $allowed = true;
                break;
            case 'authenticated':
                $allowed = is_user_logged_in() && current_user_can( 'view_algq_documents' );
                break;
            case 'lender':
                $allowed = is_user_logged_in() && ( current_user_can( 'view_algq_lender_documents' ) || current_user_can( 'view_algq_documents' ) );
                break;
            case 'buyer':
                $allowed = is_user_logged_in() && ( current_user_can( 'view_algq_buyer_documents' ) || current_user_can( 'view_algq_documents' ) );
                break;
            case 'approved_request':
                $allowed = self::current_user_has_approved_request( $document_id );
                break;
            case 'internal':
            default:
                $allowed = false;
                break;
        }

        return (bool) apply_filters( 'algq_document_user_can_access', $allowed, $document_id, get_current_user_id(), $access );
    }

    /**
     * Confirm an approved document-specific request for the current user.
     */
    private static function current_user_has_approved_request( int $document_id ): bool {
        $user_id = get_current_user_id();
        if ( ! $user_id ) {
            return false;
        }

        global $wpdb;
        $table = $wpdb->prefix . 'algq_document_requests';
        $found = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT id FROM {$table} WHERE requester_user_id = %d AND requested_document_id = %d AND status = 'approved' ORDER BY updated_at DESC LIMIT 1",
                $user_id,
                $document_id
            )
        );

        return ! empty( $found );
    }

    /**
     * Generate a nonce-protected download route.
     */
    public static function download_url( int $document_id ): string {
        return wp_nonce_url(
            add_query_arg(
                array(
                    'action'      => 'algq_document_download',
                    'document_id' => $document_id,
                ),
                admin_url( 'admin-post.php' )
            ),
            'algq_document_download_' . $document_id
        );
    }

    /**
     * Executive overview screen.
     */
    public static function admin_overview(): void {
        if ( ! current_user_can( 'view_algq_documents' ) ) {
            wp_die( esc_html__( 'You are not authorized to view the Document Library.', 'algq-document-library' ) );
        }

        global $wpdb;
        $request_table = $wpdb->prefix . 'algq_document_requests';
        $counts        = wp_count_posts( 'algq_document' );
        $total_docs    = array_sum( array_map( 'intval', (array) $counts ) );
        $pending       = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$request_table} WHERE status = 'pending'" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        $templates     = (int) ( new WP_Query( array( 'post_type' => 'algq_document', 'post_status' => 'publish', 'meta_key' => '_algq_doc_is_template', 'meta_value' => '1', 'fields' => 'ids', 'posts_per_page' => -1 ) ) )->found_posts;
        $package_counts = wp_count_posts( 'algq_doc_package' );
        $packages      = (int) ( $package_counts->publish ?? 0 );
        ?>
        <div class="wrap algq-admin">
            <div class="algq-admin-header">
                <div><span class="algq-eyebrow"><?php esc_html_e( 'Algonquian Real Estate Platform', 'algq-document-library' ); ?></span><h1><?php esc_html_e( 'Document Library', 'algq-document-library' ); ?></h1><p><?php esc_html_e( 'Secure document records, version history, packages, requests, and protected delivery.', 'algq-document-library' ); ?></p></div>
                <div class="algq-admin-actions"><a class="button button-primary" href="<?php echo esc_url( admin_url( 'post-new.php?post_type=algq_document' ) ); ?>"><?php esc_html_e( 'Add Document', 'algq-document-library' ); ?></a><a class="button" href="<?php echo esc_url( admin_url( 'edit.php?post_type=algq_document' ) ); ?>"><?php esc_html_e( 'Manage Documents', 'algq-document-library' ); ?></a></div>
            </div>
            <div class="algq-kpi-grid">
                <div class="algq-kpi"><span><?php esc_html_e( 'Document Records', 'algq-document-library' ); ?></span><strong><?php echo esc_html( (string) $total_docs ); ?></strong></div>
                <div class="algq-kpi"><span><?php esc_html_e( 'Reusable Templates', 'algq-document-library' ); ?></span><strong><?php echo esc_html( (string) $templates ); ?></strong></div>
                <div class="algq-kpi"><span><?php esc_html_e( 'Published Packages', 'algq-document-library' ); ?></span><strong><?php echo esc_html( (string) $packages ); ?></strong></div>
                <div class="algq-kpi"><span><?php esc_html_e( 'Pending Requests', 'algq-document-library' ); ?></span><strong><?php echo esc_html( (string) $pending ); ?></strong></div>
            </div>
            <div class="algq-panel-grid">
                <section class="algq-panel"><h2><?php esc_html_e( 'Security Status', 'algq-document-library' ); ?></h2><p><?php echo ALGQ_Document_Library_Storage::ensure_private_directory() ? esc_html__( 'Private storage is available.', 'algq-document-library' ) : esc_html__( 'Private storage requires attention.', 'algq-document-library' ); ?></p><p><?php esc_html_e( 'Raw storage paths are never exposed by the library or REST API.', 'algq-document-library' ); ?></p></section>
                <section class="algq-panel"><h2><?php esc_html_e( 'Operational Workflow', 'algq-document-library' ); ?></h2><ol><li><?php esc_html_e( 'Create the authoritative document record.', 'algq-document-library' ); ?></li><li><?php esc_html_e( 'Assign category, access, confidentiality, retention, and version.', 'algq-document-library' ); ?></li><li><?php esc_html_e( 'Upload the protected source file.', 'algq-document-library' ); ?></li><li><?php esc_html_e( 'Review requests and deliver only through authorized routes.', 'algq-document-library' ); ?></li></ol></section>
            </div>
        </div>
        <?php
    }

    /**
     * Built-in administrator documentation.
     */
    public static function docs_page(): void {
        ?>
        <div class="wrap algq-admin">
            <h1><?php esc_html_e( 'Document Library Documentation', 'algq-document-library' ); ?></h1>
            <div class="algq-panel-grid">
                <section class="algq-panel"><h2><?php esc_html_e( 'Shortcodes', 'algq-document-library' ); ?></h2><p><code>[algq_document_library]</code></p><p><code>[algq_document_request]</code></p><p><code>[algq_document_packages]</code></p></section>
                <section class="algq-panel"><h2><?php esc_html_e( 'Protected Files', 'algq-document-library' ); ?></h2><p><?php esc_html_e( 'Upload files through the Document editor. The plugin moves validated files into private storage and records a SHA-256 integrity hash.', 'algq-document-library' ); ?></p></section>
                <section class="algq-panel"><h2><?php esc_html_e( 'Version Control', 'algq-document-library' ); ?></h2><p><?php esc_html_e( 'WordPress revisions preserve record changes. File replacements append prior file metadata to the immutable version-history record.', 'algq-document-library' ); ?></p></section>
                <section class="algq-panel"><h2><?php esc_html_e( 'Platform Integration', 'algq-document-library' ); ?></h2><p><?php esc_html_e( 'The plugin emits structured audit events and uses the centralized Algonquian mail gateway when available.', 'algq-document-library' ); ?></p></section>
            </div>
        </div>
        <?php
    }

    /**
     * Create WPBakery-safe pages without overwriting administrator edits.
     */
    public static function create_generated_pages(): void {
        $pages = array(
            'documents'                     => array( 'Documents', self::wpbakery_page( 'Institutional Document Library', 'Controlled access to company, lender, acquisition, financial, compliance, and property-management records.', '[algq_document_library]' ) ),
            'documents/packages'            => array( 'Document Packages', self::wpbakery_page( 'Document Packages', 'Curated lender, acquisition, transaction, and governance packages.', '[algq_document_packages][algq_document_request]' ) ),
            'documents/request-access'      => array( 'Request Document Access', self::wpbakery_page( 'Request Document Access', 'Submit a business-purpose request for controlled document access.', '[algq_document_request]' ) ),
            'plugin/document-library'       => array( 'Algonquian Document Library', self::wpbakery_page( 'Algonquian Document Library', 'Secure institutional document records, access controls, versioning, and package assembly.', '[algq_document_library]' ) ),
            'plugin/document-library/start' => array( 'Getting Started With the Algonquian Document Library', self::wpbakery_page( 'Getting Started', 'Create records, classify access, upload protected files, and publish controlled document interfaces.', '<ol><li>Create the document record.</li><li>Assign its category and governance controls.</li><li>Upload the protected file.</li><li>Review access requests.</li><li>Deliver through the protected download controller.</li></ol>' ) ),
            'plugin/document-library/docs'  => array( 'Algonquian Document Library Documentation', self::wpbakery_page( 'Document Library Documentation', 'Administrator and implementation reference for the Document Library.', '<p><code>[algq_document_library]</code> renders the library. <code>[algq_document_request]</code> renders the request form. <code>[algq_document_packages]</code> renders published packages.</p>' ) ),
        );

        foreach ( $pages as $path => $data ) {
            self::create_page_path( $path, $data[0], $data[1] );
        }
    }

    /**
     * Seed the documented institutional categories and records idempotently.
     */
    public static function seed_document_records(): void {
        $seed = array(
            'entity-corporate' => array( 'Certificate of Organization (Connecticut)', 'Operating Agreement', 'EIN Confirmation', 'Banking Resolution', 'Member Structure Summary', 'Registered Agent Record' ),
            'lender-financing' => array( 'Financing Request Memorandum', 'Loan Request Summary Sheet', 'Underwriting Standards Overview', 'Acquisition Criteria Sheet', 'Schedule of Real Estate Owned', 'Personal Financial Statement', 'Global Cash Flow Worksheet', 'Source & Use of Funds Template', 'Rent Roll Template', 'Trailing 12-Month Financial Summary Template' ),
            'acquisition-due-diligence' => array( 'Property Intake Checklist', 'Initial Deal Evaluation Worksheet', 'Comparable Rent Analysis Template', 'Property Inspection Checklist', 'Unit Inspection Form', 'Contractor Bid Comparison Sheet', 'Title & Insurance Review Checklist', 'Due Diligence Completion Certification' ),
            'financial-controls' => array( 'Property-Level Profit & Loss Template', 'Monthly Operating Report Template', 'Expense Authorization Form', 'Capital Expenditure Approval Form', 'Reserve Tracking Log', 'Budget vs. Actual Report', 'Bank Reconciliation Worksheet' ),
            'risk-compliance' => array( 'Risk Management Policy', 'Insurance Tracking Log', 'Incident Report Template', 'Contractor Insurance Verification Form', 'Record Retention Policy', 'Data Protection Policy', 'Fair Housing Compliance Acknowledgment' ),
            'property-management' => array( 'Residential Lease Agreement (Connecticut)', 'Tenant Application', 'Income Verification Form', 'Move-In / Move-Out Inspection Form', 'Security Deposit Handling Form (Connecticut)', 'Maintenance Request Form', 'Late Notice Template', 'Lease Renewal / Non-Renewal Notice' ),
        );

        foreach ( $seed as $category => $titles ) {
            foreach ( $titles as $title ) {
                $existing = get_posts(
                    array(
                        'post_type'      => 'algq_document',
                        'post_status'    => 'any',
                        'posts_per_page' => 1,
                        'title'          => $title,
                        'fields'         => 'ids',
                    )
                );
                if ( $existing ) {
                    continue;
                }

                $document_id = wp_insert_post(
                    array(
                        'post_type'    => 'algq_document',
                        'post_status'  => 'publish',
                        'post_title'   => $title,
                        'post_excerpt' => __( 'Institutional document record. File access is controlled separately.', 'algq-document-library' ),
                        'post_content' => __( 'Authoritative Algonquian Real Estate document record. Review the document controls before attaching or releasing a file.', 'algq-document-library' ),
                    )
                );

                if ( $document_id && ! is_wp_error( $document_id ) ) {
                    wp_set_object_terms( $document_id, $category, 'algq_doc_category' );
                    update_post_meta( $document_id, '_algq_doc_version', '1.0.0' );
                    update_post_meta( $document_id, '_algq_doc_access', 'approved_request' );
                    update_post_meta( $document_id, '_algq_doc_confidentiality', 'confidential' );
                    update_post_meta( $document_id, '_algq_doc_retention', 'business_record' );
                }
            }
        }
    }

    /**
     * Expose platform registry metadata.
     */
    public static function register_with_platform( array $registry ): array {
        $registry['algq-document-library'] = array(
            'name'                      => 'Algonquian Document Library',
            'version'                   => ALGQ_DOC_LIB_VERSION,
            'schema_version'            => ALGQ_DOC_LIB_SCHEMA_VERSION,
            'required_platform_version' => '2.0.0',
            'admin_route'               => admin_url( 'admin.php?page=algq-document-library' ),
            'health_callback'           => array( __CLASS__, 'health_check' ),
            'capabilities'              => ALGQ_Document_Library_Activator::capabilities(),
            'rest_namespaces'           => array( 'algq/v1/documents' ),
        );
        return $registry;
    }

    /**
     * Health status consumed by the Platform and Command Center.
     */
    public static function health_check(): array {
        global $wpdb;
        $requests_table = $wpdb->prefix . 'algq_document_requests';
        $table_exists   = $requests_table === $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $requests_table ) );
        $storage_ok     = ALGQ_Document_Library_Storage::ensure_private_directory() && is_writable( ALGQ_Document_Library_Storage::base_dir() );

        return array(
            'status'  => $table_exists && $storage_ok ? 'healthy' : 'degraded',
            'checks'  => array(
                'request_table'   => $table_exists,
                'private_storage' => $storage_ok,
                'schema_current'  => ALGQ_DOC_LIB_SCHEMA_VERSION === get_option( 'algq_document_library_schema_version' ),
            ),
            'version' => ALGQ_DOC_LIB_VERSION,
        );
    }

    /**
     * Emit a structured audit event through the shared platform service.
     */
    public static function audit( string $event, array $context = array() ): void {
        $payload = array(
            'event'       => $event,
            'plugin'      => 'algq-document-library',
            'user_id'     => get_current_user_id(),
            'occurred_at' => current_time( 'mysql', true ),
            'context'     => $context,
        );

        if ( function_exists( 'algq_log_event' ) ) {
            algq_log_event( $payload );
        }

        do_action( 'algq_document_library_audit_event', $payload );
    }

    /**
     * Add convenient plugin-list actions.
     */
    public static function plugin_action_links( array $links ): array {
        array_unshift( $links, '<a href="' . esc_url( admin_url( 'admin.php?page=algq-document-library' ) ) . '">' . esc_html__( 'Open Library', 'algq-document-library' ) . '</a>' );
        return $links;
    }

    /**
     * Public request package labels.
     *
     * @return array<string,string>
     */
    public static function request_packages(): array {
        return array(
            'entity'              => __( 'Entity and Corporate Documents', 'algq-document-library' ),
            'lender'              => __( 'Lender and Financing Package', 'algq-document-library' ),
            'acquisition'         => __( 'Acquisition and Due-Diligence Package', 'algq-document-library' ),
            'financial'           => __( 'Financial Controls and Reporting Package', 'algq-document-library' ),
            'risk'                => __( 'Risk and Compliance Package', 'algq-document-library' ),
            'property-management' => __( 'Property Management Forms Package', 'algq-document-library' ),
            'transaction'         => __( 'Deal-Specific Transaction Package', 'algq-document-library' ),
        );
    }

    /**
     * Access classifications.
     *
     * @return array<string,string>
     */
    public static function access_levels(): array {
        return array(
            'internal'         => __( 'Internal only', 'algq-document-library' ),
            'lender'           => __( 'Authorized lender', 'algq-document-library' ),
            'buyer'            => __( 'Authorized buyer', 'algq-document-library' ),
            'authenticated'    => __( 'Authorized platform user', 'algq-document-library' ),
            'approved_request' => __( 'Available only after request approval', 'algq-document-library' ),
            'public'           => __( 'Public protected download', 'algq-document-library' ),
        );
    }

    /** @return array<string,string> */
    public static function confidentiality_levels(): array {
        return array(
            'public'       => __( 'Public', 'algq-document-library' ),
            'internal'     => __( 'Internal', 'algq-document-library' ),
            'confidential' => __( 'Confidential', 'algq-document-library' ),
            'restricted'   => __( 'Restricted', 'algq-document-library' ),
        );
    }

    /** @return array<string,string> */
    public static function retention_classes(): array {
        return array(
            'working_copy'    => __( 'Working copy', 'algq-document-library' ),
            'business_record' => __( 'Business record', 'algq-document-library' ),
            'transaction'     => __( 'Transaction record', 'algq-document-library' ),
            'corporate'       => __( 'Permanent corporate record', 'algq-document-library' ),
            'legal_hold'      => __( 'Legal hold', 'algq-document-library' ),
        );
    }

    /**
     * Build the document card while withholding raw file information.
     */
    private static function document_card( WP_Post $document ): string {
        $version         = (string) ( get_post_meta( $document->ID, '_algq_doc_version', true ) ?: '1.0.0' );
        $access          = (string) ( get_post_meta( $document->ID, '_algq_doc_access', true ) ?: 'approved_request' );
        $confidentiality = (string) ( get_post_meta( $document->ID, '_algq_doc_confidentiality', true ) ?: 'confidential' );
        $has_file        = (bool) get_post_meta( $document->ID, '_algq_doc_private_path', true );
        $can_download    = $has_file && self::user_can_download( $document->ID );
        $summary         = get_the_excerpt( $document ) ?: wp_trim_words( wp_strip_all_tags( $document->post_content ), 24 );

        ob_start();
        ?>
        <article class="algq-doc-card" data-algq-doc-card data-search-text="<?php echo esc_attr( strtolower( get_the_title( $document ) . ' ' . $summary ) ); ?>">
            <div class="algq-doc-card-head"><span class="algq-status algq-status-<?php echo esc_attr( $confidentiality ); ?>"><?php echo esc_html( ucfirst( $confidentiality ) ); ?></span><span><?php echo esc_html( 'v' . $version ); ?></span></div>
            <h4><?php echo esc_html( get_the_title( $document ) ); ?></h4>
            <p><?php echo esc_html( $summary ); ?></p>
            <p class="algq-doc-access"><?php echo esc_html( self::access_levels()[ $access ] ?? ucfirst( $access ) ); ?></p>
            <?php if ( $can_download ) : ?>
                <a class="algq-btn algq-btn-primary" href="<?php echo esc_url( self::download_url( $document->ID ) ); ?>"><?php esc_html_e( 'Protected Download', 'algq-document-library' ); ?></a>
            <?php else : ?>
                <a class="algq-btn" href="#algq-document-request" data-algq-document-id="<?php echo esc_attr( (string) $document->ID ); ?>"><?php esc_html_e( 'Request Access', 'algq-document-library' ); ?></a>
            <?php endif; ?>
        </article>
        <?php
        return (string) ob_get_clean();
    }

    /**
     * Preserve metadata for each replaced file.
     */
    private static function archive_current_file_metadata( int $post_id ): void {
        $path = (string) get_post_meta( $post_id, '_algq_doc_private_path', true );
        if ( ! $path ) {
            return;
        }

        $history   = (array) get_post_meta( $post_id, '_algq_doc_file_versions', true );
        $history[] = array(
            'relative_path' => $path,
            'original_name' => (string) get_post_meta( $post_id, '_algq_doc_original_name', true ),
            'mime_type'     => (string) get_post_meta( $post_id, '_algq_doc_mime_type', true ),
            'file_hash'     => (string) get_post_meta( $post_id, '_algq_doc_file_hash', true ),
            'file_size'     => absint( get_post_meta( $post_id, '_algq_doc_file_size', true ) ),
            'version'       => (string) get_post_meta( $post_id, '_algq_doc_version', true ),
            'uploaded_at'   => (string) get_post_meta( $post_id, '_algq_doc_file_uploaded_at', true ),
            'uploaded_by'   => absint( get_post_meta( $post_id, '_algq_doc_file_uploaded_by', true ) ),
            'archived_at'   => current_time( 'mysql', true ),
        );
        update_post_meta( $post_id, '_algq_doc_file_versions', $history );
    }

    /**
     * Store an append-only delivery audit row.
     */
    private static function log_download( int $document_id, string $outcome, string $denial_reason ): void {
        global $wpdb;
        $wpdb->insert(
            $wpdb->prefix . 'algq_document_downloads',
            array(
                'document_id'   => $document_id,
                'user_id'       => get_current_user_id(),
                'file_hash'     => (string) get_post_meta( $document_id, '_algq_doc_file_hash', true ),
                'outcome'       => $outcome,
                'denial_reason' => $denial_reason,
                'ip_hash'       => hash_hmac( 'sha256', sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ?? '' ) ), wp_salt( 'auth' ) ),
                'created_at'    => current_time( 'mysql', true ),
            ),
            array( '%d', '%d', '%s', '%s', '%s', '%s', '%s' )
        );
    }

    /**
     * Ensure documented default taxonomy terms.
     */
    private static function ensure_category_terms(): void {
        $terms = array(
            'entity-corporate'          => __( 'Entity & Corporate Documents', 'algq-document-library' ),
            'lender-financing'          => __( 'Lender & Financing Documents', 'algq-document-library' ),
            'acquisition-due-diligence' => __( 'Acquisition & Due Diligence Forms', 'algq-document-library' ),
            'financial-controls'        => __( 'Financial Controls & Reporting', 'algq-document-library' ),
            'risk-compliance'           => __( 'Risk Management & Compliance', 'algq-document-library' ),
            'property-management'       => __( 'Property Management Forms (Connecticut)', 'algq-document-library' ),
        );

        foreach ( $terms as $slug => $name ) {
            if ( ! term_exists( $slug, 'algq_doc_category' ) ) {
                wp_insert_term( $name, 'algq_doc_category', array( 'slug' => $slug ) );
            }
        }
    }

    /**
     * Create a nested page path one segment at a time.
     */
    private static function create_page_path( string $path, string $title, string $content ): int {
        $existing = get_page_by_path( $path );
        if ( $existing ) {
            return (int) $existing->ID;
        }

        $segments  = array_values( array_filter( explode( '/', trim( $path, '/' ) ) ) );
        $parent_id = 0;
        $built     = array();

        foreach ( $segments as $index => $segment ) {
            $built[]      = $segment;
            $partial_path = implode( '/', $built );
            $partial      = get_page_by_path( $partial_path );
            $is_final     = $index === count( $segments ) - 1;

            if ( $partial ) {
                $parent_id = (int) $partial->ID;
                continue;
            }

            $page_id = wp_insert_post(
                array(
                    'post_type'    => 'page',
                    'post_status'  => 'publish',
                    'post_parent'  => $parent_id,
                    'post_name'    => $segment,
                    'post_title'   => $is_final ? $title : ucwords( str_replace( '-', ' ', $segment ) ),
                    'post_content' => $is_final ? $content : '',
                    'post_author'  => get_current_user_id(),
                )
            );

            if ( is_wp_error( $page_id ) || ! $page_id ) {
                return 0;
            }
            $parent_id = (int) $page_id;
        }

        return $parent_id;
    }

    /**
     * Branded and valid WPBakery content scaffold.
     */
    private static function wpbakery_page( string $heading, string $summary, string $body ): string {
        return '[vc_row full_width="stretch_row_content" css=".vc_custom_algq_doc_hero{background:linear-gradient(180deg,rgba(11,31,51,.96),rgba(11,31,51,.88))!important;}"][vc_column][vc_empty_space height="36px"][vc_column_text]<div class="algq-page-hero"><span>Algonquian Real Estate Platform • Document Operations</span><h1>' . esc_html( $heading ) . '</h1><p>' . esc_html( $summary ) . '</p></div>[/vc_column_text][vc_empty_space height="36px"][/vc_column][/vc_row][vc_row][vc_column][vc_empty_space height="28px"][vc_column_text]' . $body . '[/vc_column_text][vc_empty_space height="28px"][/vc_column][/vc_row]';
    }

    /** @return array<string,string> */
    private static function document_capability_map(): array {
        return array(
            'edit_post'              => 'manage_algq_documents',
            'read_post'              => 'view_algq_documents',
            'delete_post'            => 'manage_algq_documents',
            'edit_posts'             => 'manage_algq_documents',
            'edit_others_posts'      => 'manage_algq_documents',
            'publish_posts'          => 'manage_algq_documents',
            'read_private_posts'     => 'view_algq_documents',
            'delete_posts'           => 'manage_algq_documents',
            'delete_private_posts'   => 'manage_algq_documents',
            'delete_published_posts' => 'manage_algq_documents',
            'delete_others_posts'    => 'manage_algq_documents',
            'edit_private_posts'     => 'manage_algq_documents',
            'edit_published_posts'   => 'manage_algq_documents',
            'create_posts'           => 'upload_algq_documents',
        );
    }
}
