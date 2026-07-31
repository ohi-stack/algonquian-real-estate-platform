<?php
/**
 * Core service for Algonquian PDF & Signature Engine.
 *
 * @package Algonquian_PDF_Signature
 */

defined( 'ABSPATH' ) || exit;

final class ALGQ_PDF_Signature {
    private const CAP_VIEW = 'view_algq_documents';
    private const CAP_GENERATE = 'generate_algq_pdfs';
    private const CAP_SEND = 'send_algq_signature_requests';
    private const CAP_MANAGE = 'manage_algq_signatures';
    private const CAP_AUDIT = 'view_algq_signature_audit';
    private const TABLES = array(
        'documents' => 'algq_pdf_documents',
        'requests' => 'algq_signature_requests',
        'signers' => 'algq_signature_signers',
        'events' => 'algq_signature_events',
    );
    private const REQUEST_STATUSES = array( 'draft', 'pending', 'sent', 'viewed', 'partially_signed', 'completed', 'declined', 'expired', 'cancelled', 'failed' );
    private static $admin_hook = '';

    public static function init(): void {
        load_plugin_textdomain( 'algq-pdf-signature', false, dirname( plugin_basename( ALGQ_PDF_SIGNATURE_FILE ) ) . '/languages' );
        if ( ALGQ_PDF_SIGNATURE_SCHEMA_VERSION !== (string) get_option( 'algq_pdf_signature_schema_version', '' ) ) {
            self::install();
        }
        add_action( 'admin_menu', array( __CLASS__, 'admin_menu' ) );
        add_action( 'admin_notices', array( __CLASS__, 'dependency_notice' ) );
        add_action( 'admin_enqueue_scripts', array( __CLASS__, 'admin_assets' ) );
        add_action( 'wp_enqueue_scripts', array( __CLASS__, 'public_assets' ) );
        add_shortcode( 'algq_pdf_engine', array( __CLASS__, 'shortcode_engine' ) );
        add_shortcode( 'algq_signature_archive', array( __CLASS__, 'shortcode_archive' ) );
        add_action( 'admin_post_algq_pdf_generate', array( __CLASS__, 'handle_generate' ) );
        add_action( 'admin_post_algq_pdf_download', array( __CLASS__, 'handle_download' ) );
        add_action( 'admin_post_algq_signature_request', array( __CLASS__, 'handle_request' ) );
        add_action( 'admin_post_algq_signature_status', array( __CLASS__, 'handle_status' ) );
        add_action( 'rest_api_init', array( __CLASS__, 'rest_routes' ) );
        add_filter( 'algq_pdf_signature_health_checks', array( __CLASS__, 'health_checks' ) );
    }

    public static function activate(): void {
        self::install();
        self::create_pages();
    }

    public static function deactivate(): void {}

    private static function install(): void {
        self::capabilities();
        self::tables();
        self::storage();
        update_option( 'algq_pdf_signature_version', ALGQ_PDF_SIGNATURE_VERSION, false );
        update_option( 'algq_pdf_signature_schema_version', ALGQ_PDF_SIGNATURE_SCHEMA_VERSION, false );
    }

    private static function capabilities(): void {
        $admin = get_role( 'administrator' );
        if ( ! $admin ) {
            return;
        }
        foreach ( array( self::CAP_VIEW, self::CAP_GENERATE, self::CAP_SEND, self::CAP_MANAGE, self::CAP_AUDIT ) as $cap ) {
            $admin->add_cap( $cap );
        }
    }

    private static function table( string $key ): string {
        global $wpdb;
        return $wpdb->prefix . self::TABLES[ $key ];
    }

    private static function tables(): void {
        global $wpdb;
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        $c = $wpdb->get_charset_collate();
        $d = self::table( 'documents' );
        $r = self::table( 'requests' );
        $s = self::table( 'signers' );
        $e = self::table( 'events' );
        dbDelta( "CREATE TABLE {$d} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            uuid CHAR(36) NOT NULL,
            deal_id BIGINT UNSIGNED NULL,
            source_plugin VARCHAR(100) NULL,
            source_record_id VARCHAR(100) NULL,
            document_title VARCHAR(190) NOT NULL,
            document_type VARCHAR(80) NOT NULL,
            version_number INT UNSIGNED NOT NULL DEFAULT 1,
            status VARCHAR(40) NOT NULL DEFAULT 'generated',
            file_path TEXT NULL,
            file_hash CHAR(64) NULL,
            mime_type VARCHAR(100) NULL,
            file_size BIGINT UNSIGNED NULL,
            created_by BIGINT UNSIGNED NULL,
            updated_by BIGINT UNSIGNED NULL,
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL,
            deleted_at DATETIME NULL,
            PRIMARY KEY (id), UNIQUE KEY uuid (uuid),
            KEY deal_id (deal_id), KEY source_record (source_plugin,source_record_id), KEY status (status)
        ) {$c};" );
        dbDelta( "CREATE TABLE {$r} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            uuid CHAR(36) NOT NULL,
            document_id BIGINT UNSIGNED NOT NULL,
            provider VARCHAR(80) NOT NULL DEFAULT 'manual',
            provider_request_id VARCHAR(190) NULL,
            status VARCHAR(40) NOT NULL DEFAULT 'pending',
            message TEXT NULL,
            sent_at DATETIME NULL,
            completed_at DATETIME NULL,
            created_by BIGINT UNSIGNED NULL,
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL,
            PRIMARY KEY (id), UNIQUE KEY uuid (uuid), KEY document_id (document_id), KEY provider_request_id (provider_request_id), KEY status (status)
        ) {$c};" );
        dbDelta( "CREATE TABLE {$s} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            request_id BIGINT UNSIGNED NOT NULL,
            signer_name VARCHAR(190) NOT NULL,
            signer_email VARCHAR(190) NOT NULL,
            signer_role VARCHAR(100) NULL,
            signing_order INT UNSIGNED NOT NULL DEFAULT 1,
            status VARCHAR(40) NOT NULL DEFAULT 'pending',
            provider_signer_id VARCHAR(190) NULL,
            viewed_at DATETIME NULL,
            signed_at DATETIME NULL,
            declined_at DATETIME NULL,
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL,
            PRIMARY KEY (id), KEY request_id (request_id), KEY signer_email (signer_email), KEY status (status)
        ) {$c};" );
        dbDelta( "CREATE TABLE {$e} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            uuid CHAR(36) NOT NULL,
            request_id BIGINT UNSIGNED NULL,
            document_id BIGINT UNSIGNED NULL,
            event_name VARCHAR(100) NOT NULL,
            event_status VARCHAR(40) NULL,
            provider VARCHAR(80) NULL,
            provider_event_id VARCHAR(190) NULL,
            actor_user_id BIGINT UNSIGNED NULL,
            payload_json LONGTEXT NULL,
            occurred_at DATETIME NOT NULL,
            PRIMARY KEY (id), UNIQUE KEY uuid (uuid), UNIQUE KEY provider_event (provider,provider_event_id),
            KEY request_id (request_id), KEY document_id (document_id), KEY event_name (event_name), KEY occurred_at (occurred_at)
        ) {$c};" );
    }

    private static function create_pages(): void {
        $pages = array(
            'overview' => array( 'Algonquian PDF & Signature Engine', 'plugin/pdf-signature-engine', '[algq_pdf_engine]' ),
            'start' => array( 'Getting Started With the Algonquian PDF & Signature Engine', 'plugin/pdf-signature-engine/start', '[algq_pdf_engine view="start"]' ),
            'docs' => array( 'Algonquian PDF & Signature Engine Documentation', 'plugin/pdf-signature-engine/docs', '[algq_pdf_engine view="docs"]' ),
            'archive' => array( 'Document Signatures', 'documents/signatures', '[algq_signature_archive]' ),
        );
        $stored = (array) get_option( 'algq_pdf_signature_pages', array() );
        foreach ( $pages as $key => $page ) {
            $existing = get_page_by_path( $page[1], OBJECT, 'page' );
            if ( $existing ) {
                $stored[ $key ] = (int) $existing->ID;
                continue;
            }
            $id = wp_insert_post( array(
                'post_title' => $page[0],
                'post_name' => sanitize_title( basename( $page[1] ) ),
                'post_content' => "[vc_column_text]\n{$page[2]}\n[/vc_column_text]",
                'post_status' => 'publish',
                'post_type' => 'page',
                'meta_input' => array( '_algq_generated_by' => 'algq-pdf-signature', '_algq_page_key' => $key ),
            ), true );
            if ( ! is_wp_error( $id ) ) {
                $stored[ $key ] = (int) $id;
            }
        }
        update_option( 'algq_pdf_signature_pages', $stored, false );
    }

    public static function dependency_notice(): void {
        if ( current_user_can( 'activate_plugins' ) && ! defined( 'ALGQ_PLATFORM_VERSION' ) && ! function_exists( 'algq_log_event' ) ) {
            echo '<div class="notice notice-warning"><p>' . esc_html__( 'PDF & Signature Engine is in standalone compatibility mode. Activate the Algonquian Real Estate Platform Plugin for centralized audit, mail, file, and health services.', 'algq-pdf-signature' ) . '</p></div>';
        }
    }

    public static function admin_menu(): void {
        self::$admin_hook = add_menu_page( __( 'PDF & Signature Engine', 'algq-pdf-signature' ), __( 'PDF & Signatures', 'algq-pdf-signature' ), self::CAP_VIEW, 'algq-pdf-signature', array( __CLASS__, 'admin_page' ), 'dashicons-media-document', 58 );
    }

    public static function admin_assets( string $hook ): void {
        if ( self::$admin_hook === $hook ) {
            wp_enqueue_style( 'algq-pdf-signature-admin', ALGQ_PDF_SIGNATURE_URL . 'assets/css/admin.css', array(), ALGQ_PDF_SIGNATURE_VERSION );
            wp_enqueue_script( 'algq-pdf-signature-admin', ALGQ_PDF_SIGNATURE_URL . 'assets/js/admin.js', array(), ALGQ_PDF_SIGNATURE_VERSION, true );
        }
    }

    public static function public_assets(): void {
        global $post;
        if ( $post instanceof WP_Post && ( has_shortcode( $post->post_content, 'algq_pdf_engine' ) || has_shortcode( $post->post_content, 'algq_signature_archive' ) ) ) {
            wp_enqueue_style( 'algq-pdf-signature-public', ALGQ_PDF_SIGNATURE_URL . 'assets/css/public.css', array(), ALGQ_PDF_SIGNATURE_VERSION );
        }
    }

    public static function admin_page(): void {
        self::require_cap( self::CAP_VIEW );
        $tab = isset( $_GET['tab'] ) ? sanitize_key( wp_unslash( $_GET['tab'] ) ) : 'documents';
        echo '<div class="wrap algq-pdf-admin"><div class="algq-page-header"><div><p class="algq-eyebrow">Algonquian Real Estate Platform</p><h1>PDF &amp; Signature Engine</h1><p>Protected PDF generation, version control, signature coordination, and execution evidence.</p></div><div class="algq-version">v' . esc_html( ALGQ_PDF_SIGNATURE_VERSION ) . '</div></div>';
        foreach ( array( 'documents' => 'Documents', 'signatures' => 'Signature Requests', 'audit' => 'Audit Evidence' ) as $key => $label ) {
            $url = add_query_arg( array( 'page' => 'algq-pdf-signature', 'tab' => $key ), admin_url( 'admin.php' ) );
            echo '<a class="nav-tab ' . ( $tab === $key ? 'nav-tab-active' : '' ) . '" href="' . esc_url( $url ) . '">' . esc_html( $label ) . '</a>';
        }
        if ( 'signatures' === $tab ) {
            echo self::signature_table();
        } elseif ( 'audit' === $tab ) {
            echo self::audit_table();
        } else {
            echo self::generate_form() . self::document_table( true );
        }
        echo '</div>';
    }

    private static function generate_form(): string {
        if ( ! current_user_can( self::CAP_GENERATE ) ) {
            return '';
        }
        ob_start(); ?>
        <section class="algq-panel"><h2><?php esc_html_e( 'Generate protected PDF', 'algq-pdf-signature' ); ?></h2>
        <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="algq-form-grid">
        <?php wp_nonce_field( 'algq_pdf_generate', 'algq_pdf_nonce' ); ?><input type="hidden" name="action" value="algq_pdf_generate">
        <label><span><?php esc_html_e( 'Document title', 'algq-pdf-signature' ); ?></span><input name="document_title" maxlength="190" required></label>
        <label><span><?php esc_html_e( 'Document type', 'algq-pdf-signature' ); ?></span><select name="document_type"><option value="letter_of_intent">Letter of Intent</option><option value="purchase_agreement">Purchase Agreement</option><option value="seller_financing_offer">Seller Financing Offer</option><option value="assignment_agreement">Assignment Agreement</option><option value="general_document">General Document</option></select></label>
        <label><span><?php esc_html_e( 'Deal ID', 'algq-pdf-signature' ); ?></span><input type="number" name="deal_id" min="0"></label>
        <label><span><?php esc_html_e( 'Source record', 'algq-pdf-signature' ); ?></span><input name="source_record_id" maxlength="100"></label>
        <label class="algq-field-full"><span><?php esc_html_e( 'Document content', 'algq-pdf-signature' ); ?></span><textarea name="document_content" rows="12" required></textarea><small><?php esc_html_e( 'The built-in renderer creates a text PDF. Rich renderers may use algq_pdf_render_document.', 'algq-pdf-signature' ); ?></small></label>
        <div class="algq-field-full"><button class="button button-primary"><?php esc_html_e( 'Generate PDF', 'algq-pdf-signature' ); ?></button></div></form></section>
        <?php return (string) ob_get_clean();
    }

    private static function documents( int $limit = 50 ): array {
        global $wpdb;
        $sql = $wpdb->prepare( 'SELECT * FROM ' . self::table( 'documents' ) . ' WHERE deleted_at IS NULL ORDER BY created_at DESC LIMIT %d', min( 100, max( 1, $limit ) ) );
        return (array) $wpdb->get_results( $sql );
    }

    private static function document_table( bool $admin ): string {
        $rows = self::documents();
        ob_start(); ?>
        <section class="algq-panel"><h2><?php esc_html_e( 'Document archive', 'algq-pdf-signature' ); ?></h2><div class="algq-table-wrap"><table class="widefat striped"><thead><tr><th>Title</th><th>Type</th><th>Version</th><th>Status</th><th>Deal</th><th>Created</th><th>Actions</th></tr></thead><tbody>
        <?php if ( ! $rows ) : ?><tr><td colspan="7"><?php esc_html_e( 'No PDF records are available.', 'algq-pdf-signature' ); ?></td></tr><?php else : foreach ( $rows as $row ) : ?>
        <tr><td><strong><?php echo esc_html( $row->document_title ); ?></strong><br><code><?php echo esc_html( $row->uuid ); ?></code></td><td><?php echo esc_html( $row->document_type ); ?></td><td><?php echo esc_html( 'v' . $row->version_number ); ?></td><td><span class="algq-status"><?php echo esc_html( ucwords( str_replace( '_', ' ', $row->status ) ) ); ?></span></td><td><?php echo esc_html( (string) $row->deal_id ); ?></td><td><?php echo esc_html( mysql2date( 'Y-m-d H:i', $row->created_at ) ); ?></td><td>
        <a class="button button-small" href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=algq_pdf_download&document_id=' . absint( $row->id ) ), 'algq_pdf_download_' . absint( $row->id ) ) ); ?>">Download</a>
        <?php if ( $admin && current_user_can( self::CAP_SEND ) ) : ?><button type="button" class="button button-small algq-open-signature" data-document-id="<?php echo esc_attr( (string) $row->id ); ?>" data-document-title="<?php echo esc_attr( $row->document_title ); ?>">Request signature</button><?php endif; ?></td></tr>
        <?php endforeach; endif; ?></tbody></table></div></section>
        <?php if ( $admin && current_user_can( self::CAP_SEND ) ) : ?>
        <div class="algq-modal" id="algq-signature-modal" hidden><div class="algq-modal-dialog"><button type="button" class="algq-modal-close" aria-label="Close">&times;</button><h2>Create signature request</h2>
        <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="algq-form-grid"><?php wp_nonce_field( 'algq_signature_request', 'algq_signature_nonce' ); ?><input type="hidden" name="action" value="algq_signature_request"><input type="hidden" name="document_id" id="algq-signature-document-id"><p class="algq-field-full"><strong id="algq-signature-document-title"></strong></p><label><span>Signer name</span><input name="signer_name" required></label><label><span>Signer email</span><input type="email" name="signer_email" required></label><label><span>Signer role</span><input name="signer_role"></label><label><span>Provider</span><select name="provider"><?php foreach ( self::providers() as $key => $provider ) : ?><option value="<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $provider['label'] ); ?></option><?php endforeach; ?></select></label><label class="algq-field-full"><span>Message</span><textarea name="message"></textarea></label><button class="button button-primary">Create request</button></form></div></div>
        <?php endif; return (string) ob_get_clean();
    }

    private static function signature_table(): string {
        global $wpdb;
        $r = self::table( 'requests' ); $d = self::table( 'documents' ); $s = self::table( 'signers' );
        $rows = (array) $wpdb->get_results( "SELECT r.*,d.document_title,s.signer_name,s.signer_email FROM {$r} r JOIN {$d} d ON d.id=r.document_id LEFT JOIN {$s} s ON s.request_id=r.id ORDER BY r.created_at DESC LIMIT 100" );
        ob_start(); ?><section class="algq-panel"><h2>Signature requests</h2><div class="algq-table-wrap"><table class="widefat striped"><thead><tr><th>Document</th><th>Signer</th><th>Provider</th><th>Status</th><th>Created</th><th>Update</th></tr></thead><tbody>
        <?php if ( ! $rows ) : ?><tr><td colspan="6">No signature requests are available.</td></tr><?php else : foreach ( $rows as $row ) : ?><tr><td><?php echo esc_html( $row->document_title ); ?></td><td><?php echo esc_html( trim( $row->signer_name . ' ' . $row->signer_email ) ); ?></td><td><?php echo esc_html( $row->provider ); ?></td><td><?php echo esc_html( $row->status ); ?></td><td><?php echo esc_html( mysql2date( 'Y-m-d H:i', $row->created_at ) ); ?></td><td><?php if ( current_user_can( self::CAP_MANAGE ) ) : ?><form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="algq-inline-form"><?php wp_nonce_field( 'algq_signature_status_' . absint( $row->id ), 'algq_signature_status_nonce' ); ?><input type="hidden" name="action" value="algq_signature_status"><input type="hidden" name="request_id" value="<?php echo esc_attr( (string) $row->id ); ?>"><select name="status"><?php foreach ( self::REQUEST_STATUSES as $status ) : ?><option value="<?php echo esc_attr( $status ); ?>" <?php selected( $row->status, $status ); ?>><?php echo esc_html( $status ); ?></option><?php endforeach; ?></select><button class="button button-small">Save</button></form><?php endif; ?></td></tr><?php endforeach; endif; ?></tbody></table></div></section><?php return (string) ob_get_clean();
    }

    private static function audit_table(): string {
        if ( ! current_user_can( self::CAP_AUDIT ) ) { return ''; }
        global $wpdb; $rows = (array) $wpdb->get_results( 'SELECT * FROM ' . self::table( 'events' ) . ' ORDER BY occurred_at DESC LIMIT 200' );
        ob_start(); ?><section class="algq-panel"><h2>Append-only event evidence</h2><div class="algq-table-wrap"><table class="widefat striped"><thead><tr><th>Event</th><th>Status</th><th>Provider</th><th>Document</th><th>Request</th><th>Occurred</th></tr></thead><tbody><?php if ( ! $rows ) : ?><tr><td colspan="6">No audit events are available.</td></tr><?php else : foreach ( $rows as $row ) : ?><tr><td><?php echo esc_html( $row->event_name ); ?></td><td><?php echo esc_html( (string) $row->event_status ); ?></td><td><?php echo esc_html( (string) $row->provider ); ?></td><td><?php echo esc_html( (string) $row->document_id ); ?></td><td><?php echo esc_html( (string) $row->request_id ); ?></td><td><?php echo esc_html( $row->occurred_at ); ?></td></tr><?php endforeach; endif; ?></tbody></table></div></section><?php return (string) ob_get_clean();
    }

    public static function shortcode_engine( array $atts = array() ): string {
        $atts = shortcode_atts( array( 'view' => 'dashboard' ), $atts, 'algq_pdf_engine' );
        if ( 'start' === $atts['view'] ) { return '<div class="algq-pdf-wrap"><div class="algq-public-hero"><h1>Getting Started</h1></div><section class="algq-panel"><ol><li>Confirm platform, private storage, permissions, and mail health.</li><li>Generate a protected PDF from an approved record.</li><li>Verify version, hash, deal association, and content.</li><li>Create a provider request or use manual tracking.</li><li>Archive the completed copy and evidence.</li></ol></section></div>'; }
        if ( 'docs' === $atts['view'] ) { return '<div class="algq-pdf-wrap"><div class="algq-public-hero"><h1>PDF &amp; Signature Engine Documentation</h1></div><section class="algq-panel"><p><code>[algq_pdf_engine]</code> <code>[algq_signature_archive]</code></p><p>Provider hooks: <code>algq_signature_providers</code>, <code>algq_signature_send_request</code>, <code>algq_signature_webhook_authorized</code>, and <code>algq_signature_normalize_webhook</code>.</p><p>This plugin manages records and evidence; it does not determine legal sufficiency.</p></section></div>'; }
        if ( ! is_user_logged_in() || ! current_user_can( self::CAP_VIEW ) ) { return '<div class="algq-pdf-wrap"><section class="algq-panel"><p>Authorized access is required.</p></section></div>'; }
        return '<div class="algq-pdf-wrap"><div class="algq-public-hero"><h1>PDF &amp; Signature Engine</h1></div>' . ( current_user_can( self::CAP_GENERATE ) ? self::generate_form() : '' ) . self::document_table( false ) . '</div>';
    }

    public static function shortcode_archive(): string {
        return is_user_logged_in() && current_user_can( self::CAP_VIEW ) ? '<div class="algq-pdf-wrap">' . self::document_table( false ) . '</div>' : '<div class="algq-pdf-wrap"><section class="algq-panel"><p>Authorized access is required.</p></section></div>';
    }

    public static function handle_generate(): void {
        self::require_cap( self::CAP_GENERATE ); check_admin_referer( 'algq_pdf_generate', 'algq_pdf_nonce' );
        $payload = array(
            'document_title' => sanitize_text_field( wp_unslash( $_POST['document_title'] ?? '' ) ),
            'document_type' => sanitize_key( wp_unslash( $_POST['document_type'] ?? 'general_document' ) ),
            'document_content' => wp_kses_post( wp_unslash( $_POST['document_content'] ?? '' ) ),
            'deal_id' => absint( $_POST['deal_id'] ?? 0 ),
            'source_plugin' => 'algq-offer-generator',
            'source_record_id' => sanitize_text_field( wp_unslash( $_POST['source_record_id'] ?? '' ) ),
        );
        $result = self::generate( $payload );
        self::redirect( 'documents', is_wp_error( $result ) ? $result->get_error_code() : 'document_generated' );
    }

    public static function generate( array $payload ) {
        global $wpdb;
        if ( empty( $payload['document_title'] ) || '' === trim( wp_strip_all_tags( (string) $payload['document_content'] ) ) ) {
            return new WP_Error( 'missing_document_data', __( 'A title and document content are required.', 'algq-pdf-signature' ) );
        }
        $uuid = wp_generate_uuid4();
        $version = self::next_version( (string) $payload['source_plugin'], (string) $payload['source_record_id'], (string) $payload['document_type'] );
        $path = trailingslashit( self::storage_dir() ) . sanitize_file_name( $payload['document_type'] . '-' . $uuid . '-v' . $version . '.pdf' );
        $context = array( 'title' => $payload['document_title'], 'content' => $payload['document_content'], 'uuid' => $uuid, 'version' => $version, 'deal_id' => $payload['deal_id'] );
        $custom = apply_filters( 'algq_pdf_render_document', null, $context, $path );
        if ( is_wp_error( $custom ) ) { return $custom; }
        if ( null === $custom ) {
            if ( false === file_put_contents( $path, self::basic_pdf( $context['title'], wp_strip_all_tags( $context['content'] ) ), LOCK_EX ) ) { return new WP_Error( 'pdf_write_failed', 'The PDF could not be written.' ); }
        } elseif ( is_string( $custom ) && is_file( $custom ) && ! copy( $custom, $path ) ) { return new WP_Error( 'pdf_copy_failed', 'The rendered PDF could not be stored.' ); }
        if ( ! is_file( $path ) ) { return new WP_Error( 'pdf_renderer_invalid', 'The renderer did not create a PDF.' ); }
        $now = current_time( 'mysql' ); $hash = hash_file( 'sha256', $path );
        $ok = $wpdb->insert( self::table( 'documents' ), array(
            'uuid' => $uuid, 'deal_id' => $payload['deal_id'] ?: null, 'source_plugin' => sanitize_key( (string) $payload['source_plugin'] ), 'source_record_id' => sanitize_text_field( (string) $payload['source_record_id'] ),
            'document_title' => sanitize_text_field( (string) $payload['document_title'] ), 'document_type' => sanitize_key( (string) $payload['document_type'] ), 'version_number' => $version, 'status' => 'generated',
            'file_path' => basename( $path ), 'file_hash' => $hash, 'mime_type' => 'application/pdf', 'file_size' => filesize( $path ), 'created_by' => get_current_user_id(), 'updated_by' => get_current_user_id(), 'created_at' => $now, 'updated_at' => $now,
        ) );
        if ( false === $ok ) { @unlink( $path ); return new WP_Error( 'document_record_failed', 'The document record could not be saved.' ); }
        $id = (int) $wpdb->insert_id; self::event( 'document.generated', $id, null, 'generated', 'local', null, array( 'hash' => $hash, 'version' => $version ) ); do_action( 'algq_pdf_document_generated', $id, $context ); return $id;
    }

    private static function next_version( string $plugin, string $record, string $type ): int {
        if ( '' === $plugin || '' === $record ) { return 1; }
        global $wpdb; $max = $wpdb->get_var( $wpdb->prepare( 'SELECT MAX(version_number) FROM ' . self::table( 'documents' ) . ' WHERE source_plugin=%s AND source_record_id=%s AND document_type=%s', $plugin, $record, $type ) );
        return max( 1, (int) $max + 1 );
    }

    private static function basic_pdf( string $title, string $content ): string {
        $lines = array();
        foreach ( explode( "\n", trim( $title . "\n\n" . $content ) ) as $paragraph ) {
            $paragraph = trim( preg_replace( '/\s+/', ' ', $paragraph ) );
            if ( '' === $paragraph ) { $lines[] = ''; continue; }
            $current = '';
            foreach ( preg_split( '/\s+/', $paragraph ) as $word ) {
                if ( '' === $current || strlen( $current . ' ' . $word ) <= 88 ) { $current = trim( $current . ' ' . $word ); } else { $lines[] = $current; $current = $word; }
            }
            if ( '' !== $current ) { $lines[] = $current; }
        }
        $pages = array_chunk( $lines ?: array( '' ), 48 ); $objects = array( 1 => '<< /Type /Catalog /Pages 2 0 R >>', 3 => '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>' ); $page_ids = array(); $next = 4;
        foreach ( $pages as $page ) {
            $pid = $next++; $cid = $next++; $page_ids[] = $pid; $stream = "BT\n/F1 10 Tf\n50 760 Td\n14 TL\n";
            foreach ( $page as $line ) { $line = str_replace( array( '\\', '(', ')' ), array( '\\\\', '\\(', '\\)' ), $line ); $stream .= '(' . $line . ") Tj\nT*\n"; }
            $stream .= 'ET'; $objects[ $cid ] = '<< /Length ' . strlen( $stream ) . ">>\nstream\n{$stream}\nendstream"; $objects[ $pid ] = '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 612 792] /Resources << /Font << /F1 3 0 R >> >> /Contents ' . $cid . ' 0 R >>';
        }
        $objects[2] = '<< /Type /Pages /Kids [ ' . implode( ' ', array_map( static fn( $id ) => $id . ' 0 R', $page_ids ) ) . ' ] /Count ' . count( $page_ids ) . ' >>'; ksort( $objects ); $pdf = "%PDF-1.4\n"; $offsets = array( 0 );
        foreach ( $objects as $id => $object ) { $offsets[ $id ] = strlen( $pdf ); $pdf .= $id . " 0 obj\n" . $object . "\nendobj\n"; }
        $xref = strlen( $pdf ); $count = max( array_keys( $objects ) ) + 1; $pdf .= "xref\n0 {$count}\n0000000000 65535 f \n"; for ( $i = 1; $i < $count; $i++ ) { $pdf .= sprintf( "%010d 00000 n \n", $offsets[ $i ] ?? 0 ); }
        return $pdf . "trailer\n<< /Size {$count} /Root 1 0 R >>\nstartxref\n{$xref}\n%%EOF";
    }

    public static function handle_download(): void {
        self::require_cap( self::CAP_VIEW ); $id = absint( $_GET['document_id'] ?? 0 ); check_admin_referer( 'algq_pdf_download_' . $id ); global $wpdb;
        $row = $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . self::table( 'documents' ) . ' WHERE id=%d AND deleted_at IS NULL', $id ) );
        $path = $row ? trailingslashit( self::storage_dir() ) . basename( $row->file_path ) : '';
        if ( ! $row || ! is_file( $path ) || ! hash_equals( (string) $row->file_hash, (string) hash_file( 'sha256', $path ) ) ) { wp_die( 'Document integrity verification failed.', '', array( 'response' => 409 ) ); }
        self::event( 'document.downloaded', $id, null, 'completed', 'local', null, array() ); nocache_headers(); header( 'Content-Type: application/pdf' ); header( 'Content-Disposition: attachment; filename="' . sanitize_file_name( $row->document_title . '-v' . $row->version_number . '.pdf' ) . '"' ); header( 'Content-Length: ' . filesize( $path ) ); header( 'X-Content-Type-Options: nosniff' ); readfile( $path ); exit;
    }

    public static function handle_request(): void {
        self::require_cap( self::CAP_SEND ); check_admin_referer( 'algq_signature_request', 'algq_signature_nonce' ); global $wpdb;
        $document_id = absint( $_POST['document_id'] ?? 0 ); $name = sanitize_text_field( wp_unslash( $_POST['signer_name'] ?? '' ) ); $email = sanitize_email( wp_unslash( $_POST['signer_email'] ?? '' ) ); $provider = sanitize_key( wp_unslash( $_POST['provider'] ?? 'manual' ) );
        if ( ! $document_id || '' === $name || ! is_email( $email ) || ! isset( self::providers()[ $provider ] ) ) { self::redirect( 'documents', 'invalid_signature_request' ); }
        $now = current_time( 'mysql' ); $ok = $wpdb->insert( self::table( 'requests' ), array( 'uuid' => wp_generate_uuid4(), 'document_id' => $document_id, 'provider' => $provider, 'status' => 'pending', 'message' => sanitize_textarea_field( wp_unslash( $_POST['message'] ?? '' ) ), 'created_by' => get_current_user_id(), 'created_at' => $now, 'updated_at' => $now ) );
        if ( false === $ok ) { self::redirect( 'documents', 'signature_request_save_failed' ); } $request_id = (int) $wpdb->insert_id;
        $wpdb->insert( self::table( 'signers' ), array( 'request_id' => $request_id, 'signer_name' => $name, 'signer_email' => $email, 'signer_role' => sanitize_text_field( wp_unslash( $_POST['signer_role'] ?? '' ) ), 'status' => 'pending', 'created_at' => $now, 'updated_at' => $now ) );
        $adapter = apply_filters( 'algq_signature_send_request', null, $request_id, $document_id, array( 'provider' => $provider, 'signer_name' => $name, 'signer_email' => $email ) );
        if ( is_wp_error( $adapter ) ) { $wpdb->update( self::table( 'requests' ), array( 'status' => 'failed', 'updated_at' => $now ), array( 'id' => $request_id ) ); self::event( 'signature.request_failed', $document_id, $request_id, 'failed', $provider, null, array( 'error' => $adapter->get_error_code() ) ); self::redirect( 'signatures', $adapter->get_error_code() ); }
        if ( is_array( $adapter ) ) { $status = sanitize_key( $adapter['status'] ?? 'sent' ); if ( ! in_array( $status, self::REQUEST_STATUSES, true ) ) { $status = 'sent'; } $wpdb->update( self::table( 'requests' ), array( 'provider_request_id' => sanitize_text_field( $adapter['provider_request_id'] ?? '' ), 'status' => $status, 'sent_at' => $now, 'updated_at' => $now ), array( 'id' => $request_id ) ); }
        self::event( 'signature.request_created', $document_id, $request_id, 'pending', $provider, null, array( 'signer_email_hash' => hash( 'sha256', strtolower( $email ) ) ) ); do_action( 'algq_signature_request_created', $request_id, $document_id ); self::redirect( 'signatures', 'signature_request_created' );
    }

    public static function handle_status(): void {
        self::require_cap( self::CAP_MANAGE ); $id = absint( $_POST['request_id'] ?? 0 ); check_admin_referer( 'algq_signature_status_' . $id, 'algq_signature_status_nonce' ); $status = sanitize_key( wp_unslash( $_POST['status'] ?? '' ) );
        if ( ! in_array( $status, self::REQUEST_STATUSES, true ) ) { self::redirect( 'signatures', 'invalid_signature_status' ); }
        global $wpdb; $row = $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . self::table( 'requests' ) . ' WHERE id=%d', $id ) );
        if ( ! $row ) { self::redirect( 'signatures', 'signature_request_not_found' ); }
        $update = array( 'status' => $status, 'updated_at' => current_time( 'mysql' ) ); if ( 'completed' === $status ) { $update['completed_at'] = current_time( 'mysql' ); }
        $wpdb->update( self::table( 'requests' ), $update, array( 'id' => $id ) ); self::event( 'signature.status_changed', (int) $row->document_id, $id, $status, $row->provider, null, array( 'previous_status' => $row->status ) ); do_action( 'algq_signature_status_changed', $id, $status, $row->status ); self::redirect( 'signatures', 'signature_status_updated' );
    }

    public static function rest_routes(): void {
        register_rest_route( 'algq/v1', '/pdf-documents', array(
            array( 'methods' => WP_REST_Server::READABLE, 'callback' => array( __CLASS__, 'rest_list' ), 'permission_callback' => static fn() => current_user_can( self::CAP_VIEW ) ),
            array( 'methods' => WP_REST_Server::CREATABLE, 'callback' => array( __CLASS__, 'rest_generate' ), 'permission_callback' => static fn() => current_user_can( self::CAP_GENERATE ) ),
        ) );
        register_rest_route( 'algq/v1', '/signature-webhook/(?P<provider>[a-z0-9_-]+)', array( 'methods' => WP_REST_Server::CREATABLE, 'callback' => array( __CLASS__, 'rest_webhook' ), 'permission_callback' => '__return_true' ) );
    }

    public static function rest_list(): WP_REST_Response {
        return new WP_REST_Response( array_map( static fn( $r ) => array( 'id' => (int) $r->id, 'uuid' => $r->uuid, 'deal_id' => (int) $r->deal_id, 'title' => $r->document_title, 'type' => $r->document_type, 'version' => (int) $r->version_number, 'status' => $r->status, 'hash' => $r->file_hash, 'created_at' => mysql_to_rfc3339( $r->created_at ) ), self::documents( 100 ) ), 200 );
    }

    public static function rest_generate( WP_REST_Request $request ) {
        $result = self::generate( array( 'document_title' => sanitize_text_field( $request['document_title'] ), 'document_type' => sanitize_key( $request['document_type'] ), 'document_content' => wp_kses_post( $request['document_content'] ), 'deal_id' => absint( $request['deal_id'] ), 'source_plugin' => sanitize_key( $request['source_plugin'] ), 'source_record_id' => sanitize_text_field( $request['source_record_id'] ) ) );
        return is_wp_error( $result ) ? $result : new WP_REST_Response( array( 'document_id' => (int) $result ), 201 );
    }

    public static function rest_webhook( WP_REST_Request $request ) {
        $provider = sanitize_key( $request['provider'] ); $raw = $request->get_body();
        if ( ! apply_filters( 'algq_signature_webhook_authorized', false, $provider, $request, $raw ) ) { return new WP_Error( 'webhook_unauthorized', 'Signature webhook authentication failed.', array( 'status' => 401 ) ); }
        $event = apply_filters( 'algq_signature_normalize_webhook', null, $provider, $request, $raw );
        if ( ! is_array( $event ) || empty( $event['provider_event_id'] ) || empty( $event['provider_request_id'] ) || ! in_array( sanitize_key( $event['status'] ?? '' ), self::REQUEST_STATUSES, true ) ) { return new WP_Error( 'webhook_invalid', 'Signature webhook payload is invalid.', array( 'status' => 400 ) ); }
        global $wpdb; $duplicate = $wpdb->get_var( $wpdb->prepare( 'SELECT id FROM ' . self::table( 'events' ) . ' WHERE provider=%s AND provider_event_id=%s', $provider, $event['provider_event_id'] ) );
        if ( $duplicate ) { return new WP_REST_Response( array( 'status' => 'duplicate_ignored' ), 200 ); }
        $row = $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . self::table( 'requests' ) . ' WHERE provider=%s AND provider_request_id=%s', $provider, $event['provider_request_id'] ) );
        if ( ! $row ) { return new WP_Error( 'request_not_found', 'Signature request was not found.', array( 'status' => 404 ) ); }
        $status = sanitize_key( $event['status'] ); $wpdb->update( self::table( 'requests' ), array( 'status' => $status, 'updated_at' => current_time( 'mysql' ) ), array( 'id' => $row->id ) ); self::event( 'signature.provider_event', (int) $row->document_id, (int) $row->id, $status, $provider, sanitize_text_field( $event['provider_event_id'] ), array( 'event_type' => sanitize_key( $event['event_type'] ?? '' ) ) ); do_action( 'algq_signature_status_changed', (int) $row->id, $status, $row->status ); return new WP_REST_Response( array( 'status' => 'accepted' ), 202 );
    }

    private static function providers(): array {
        $providers = apply_filters( 'algq_signature_providers', array( 'manual' => array( 'label' => __( 'Manual status tracking', 'algq-pdf-signature' ), 'supports_webhooks' => false ) ) );
        return is_array( $providers ) ? $providers : array();
    }

    private static function storage_dir(): string {
        $uploads = wp_upload_dir( null, false ); return untrailingslashit( (string) apply_filters( 'algq_pdf_signature_storage_directory', trailingslashit( $uploads['basedir'] ) . 'algq-private/pdf-signature' ) );
    }

    private static function storage(): void {
        $dir = self::storage_dir(); if ( ! wp_mkdir_p( $dir ) ) { return; }
        foreach ( array( 'index.php' => "<?php\n// Silence is golden.\n", '.htaccess' => "Require all denied\nDeny from all\n", 'web.config' => '<configuration><system.webServer><authorization><deny users="*" /></authorization></system.webServer></configuration>' ) as $file => $content ) {
            if ( ! file_exists( trailingslashit( $dir ) . $file ) ) { file_put_contents( trailingslashit( $dir ) . $file, $content ); }
        }
    }

    private static function event( string $name, ?int $document_id, ?int $request_id, ?string $status, ?string $provider, ?string $provider_event_id, array $payload ): void {
        global $wpdb; $clean = array(); foreach ( $payload as $key => $value ) { if ( is_scalar( $value ) || null === $value ) { $clean[ sanitize_key( (string) $key ) ] = is_string( $value ) ? sanitize_text_field( $value ) : $value; } }
        $wpdb->insert( self::table( 'events' ), array( 'uuid' => wp_generate_uuid4(), 'request_id' => $request_id, 'document_id' => $document_id, 'event_name' => sanitize_key( $name ), 'event_status' => $status ? sanitize_key( $status ) : null, 'provider' => $provider ? sanitize_key( $provider ) : null, 'provider_event_id' => $provider_event_id ? sanitize_text_field( $provider_event_id ) : null, 'actor_user_id' => get_current_user_id() ?: null, 'payload_json' => wp_json_encode( $clean ), 'occurred_at' => current_time( 'mysql' ) ) );
        if ( function_exists( 'algq_log_event' ) ) { algq_log_event( $name, array( 'plugin' => 'algq-pdf-signature', 'document_id' => $document_id, 'request_id' => $request_id, 'status' => $status ) ); } else { do_action( 'algq_audit_event', $name, array( 'plugin' => 'algq-pdf-signature', 'document_id' => $document_id, 'request_id' => $request_id, 'status' => $status ) ); }
    }

    public static function health_checks( array $checks ): array {
        global $wpdb; $checks['pdf_signature_storage'] = array( 'label' => 'PDF & Signature private storage', 'status' => is_dir( self::storage_dir() ) && is_writable( self::storage_dir() ) ? 'healthy' : 'failed' );
        foreach ( self::TABLES as $key => $name ) { $full = $wpdb->prefix . $name; $checks[ 'pdf_signature_' . $key ] = array( 'label' => $full, 'status' => $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $full ) ) === $full ? 'healthy' : 'failed' ); }
        return $checks;
    }

    private static function require_cap( string $cap ): void {
        if ( ! is_user_logged_in() || ! current_user_can( $cap ) ) { wp_die( esc_html__( 'You are not authorized to perform this action.', 'algq-pdf-signature' ), '', array( 'response' => 403 ) ); }
    }

    private static function redirect( string $tab, string $notice ): void {
        wp_safe_redirect( add_query_arg( array( 'page' => 'algq-pdf-signature', 'tab' => sanitize_key( $tab ), 'algq_notice' => sanitize_key( $notice ) ), admin_url( 'admin.php' ) ) ); exit;
    }
}
