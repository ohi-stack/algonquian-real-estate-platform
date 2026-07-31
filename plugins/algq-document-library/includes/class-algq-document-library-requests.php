<?php
/**
 * Durable access request workflow.
 */

defined( 'ABSPATH' ) || exit;

final class ALGQ_Document_Library_Requests {

    public const CONSENT_VERSION = '2026-07-31';

    /**
     * Register request handlers.
     */
    public static function init(): void {
        add_action( 'admin_post_algq_doc_request', array( __CLASS__, 'handle_request' ) );
        add_action( 'admin_post_nopriv_algq_doc_request', array( __CLASS__, 'handle_request' ) );
        add_action( 'admin_post_algq_doc_request_status', array( __CLASS__, 'handle_status_change' ) );
    }

    /**
     * Render a public-safe request form.
     */
    public static function render_form( array $atts = array() ): string {
        $atts = shortcode_atts(
            array(
                'document_id' => 0,
                'package'     => 'lender',
            ),
            $atts,
            'algq_document_request'
        );

        $document_id = absint( $atts['document_id'] );
        $package     = sanitize_key( $atts['package'] );
        $return_url  = self::current_url();
        $status      = isset( $_GET['document-request'] ) ? sanitize_key( wp_unslash( $_GET['document-request'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

        ob_start();
        ?>
        <div class="algq-doc-request-wrap" id="algq-document-request">
            <?php if ( 'received' === $status ) : ?>
                <div class="algq-alert algq-alert-success" role="status">
                    <?php esc_html_e( 'Your document access request was received. A representative will review it before access is granted.', 'algq-document-library' ); ?>
                </div>
            <?php elseif ( 'error' === $status ) : ?>
                <div class="algq-alert algq-alert-error" role="alert">
                    <?php esc_html_e( 'The request could not be submitted. Review the required fields and try again.', 'algq-document-library' ); ?>
                </div>
            <?php endif; ?>

            <form class="algq-doc-request" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
                <h3><?php esc_html_e( 'Request Document Access', 'algq-document-library' ); ?></h3>
                <p><?php esc_html_e( 'Requests are reviewed individually. Submission does not create an entitlement or guarantee access.', 'algq-document-library' ); ?></p>

                <input type="hidden" name="action" value="algq_doc_request" />
                <input type="hidden" name="document_id" value="<?php echo esc_attr( (string) $document_id ); ?>" />
                <input type="hidden" name="return_url" value="<?php echo esc_url( $return_url ); ?>" />
                <input type="text" name="website" value="" class="algq-honeypot" tabindex="-1" autocomplete="off" aria-hidden="true" />
                <?php wp_nonce_field( 'algq_doc_request', 'algq_doc_request_nonce' ); ?>

                <div class="algq-form-grid">
                    <label>
                        <span><?php esc_html_e( 'Name', 'algq-document-library' ); ?> *</span>
                        <input required maxlength="190" name="name" type="text" autocomplete="name" />
                    </label>
                    <label>
                        <span><?php esc_html_e( 'Email', 'algq-document-library' ); ?> *</span>
                        <input required maxlength="190" name="email" type="email" autocomplete="email" />
                    </label>
                    <label>
                        <span><?php esc_html_e( 'Company or organization', 'algq-document-library' ); ?></span>
                        <input maxlength="190" name="company" type="text" autocomplete="organization" />
                    </label>
                    <label>
                        <span><?php esc_html_e( 'Requested package', 'algq-document-library' ); ?> *</span>
                        <select required name="package">
                            <?php foreach ( ALGQ_Document_Library::request_packages() as $key => $label ) : ?>
                                <option value="<?php echo esc_attr( $key ); ?>" <?php selected( $package, $key ); ?>><?php echo esc_html( $label ); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                </div>

                <label>
                    <span><?php esc_html_e( 'Business purpose or reason for request', 'algq-document-library' ); ?> *</span>
                    <textarea required maxlength="2000" name="reason" rows="5"></textarea>
                </label>

                <label class="algq-checkbox">
                    <input required type="checkbox" name="consent" value="1" />
                    <span><?php esc_html_e( 'I authorize Algonquian Real Estate LLC to review this request and contact me about document access. I understand that confidential materials remain subject to approval and applicable access restrictions.', 'algq-document-library' ); ?></span>
                </label>

                <button class="algq-btn algq-btn-primary" type="submit"><?php esc_html_e( 'Submit Request', 'algq-document-library' ); ?></button>
            </form>
        </div>
        <?php
        return (string) ob_get_clean();
    }

    /**
     * Validate and store an access request.
     */
    public static function handle_request(): void {
        if ( ! isset( $_POST['algq_doc_request_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['algq_doc_request_nonce'] ) ), 'algq_doc_request' ) ) {
            wp_die( esc_html__( 'Invalid document request.', 'algq-document-library' ), 403 );
        }

        $return_url = isset( $_POST['return_url'] ) ? esc_url_raw( wp_unslash( $_POST['return_url'] ) ) : home_url( '/documents/' );
        $return_url = wp_validate_redirect( $return_url, home_url( '/documents/' ) );

        if ( ! empty( $_POST['website'] ) ) {
            self::redirect( $return_url, 'received' );
        }

        $name        = sanitize_text_field( wp_unslash( $_POST['name'] ?? '' ) );
        $email       = sanitize_email( wp_unslash( $_POST['email'] ?? '' ) );
        $company     = sanitize_text_field( wp_unslash( $_POST['company'] ?? '' ) );
        $package     = sanitize_key( wp_unslash( $_POST['package'] ?? '' ) );
        $reason      = sanitize_textarea_field( wp_unslash( $_POST['reason'] ?? '' ) );
        $document_id = absint( $_POST['document_id'] ?? 0 );
        $consent     = ! empty( $_POST['consent'] );

        if (
            '' === $name ||
            ! is_email( $email ) ||
            ! array_key_exists( $package, ALGQ_Document_Library::request_packages() ) ||
            '' === $reason ||
            ! $consent ||
            ( $document_id && 'algq_document' !== get_post_type( $document_id ) )
        ) {
            self::redirect( $return_url, 'error' );
        }

        $rate_key = 'algq_doc_request_' . hash( 'sha256', strtolower( $email ) . '|' . self::client_ip() );
        if ( get_transient( $rate_key ) ) {
            self::redirect( $return_url, 'received' );
        }
        set_transient( $rate_key, 1, 10 * MINUTE_IN_SECONDS );

        global $wpdb;
        $table = $wpdb->prefix . 'algq_document_requests';
        $now   = current_time( 'mysql', true );
        $uuid  = wp_generate_uuid4();

        $inserted = $wpdb->insert(
            $table,
            array(
                'request_uuid'          => $uuid,
                'requester_user_id'     => get_current_user_id(),
                'requester_name'        => $name,
                'requester_email'       => $email,
                'requester_company'     => $company,
                'package_key'           => $package,
                'requested_document_id' => $document_id,
                'reason'                => $reason,
                'consent_version'       => self::CONSENT_VERSION,
                'status'                => 'pending',
                'ip_hash'               => self::privacy_hash( self::client_ip() ),
                'user_agent_hash'       => self::privacy_hash( sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ?? '' ) ) ),
                'created_at'            => $now,
                'updated_at'            => $now,
            ),
            array( '%s', '%d', '%s', '%s', '%s', '%s', '%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s' )
        );

        if ( false === $inserted ) {
            self::redirect( $return_url, 'error' );
        }

        ALGQ_Document_Library::audit(
            'document_request.created',
            array(
                'request_uuid' => $uuid,
                'package'      => $package,
                'document_id'  => $document_id,
            )
        );

        self::send_notifications( $uuid, $name, $email, $company, $package, $document_id );
        self::redirect( $return_url, 'received' );
    }

    /**
     * Process an authorized request status update.
     */
    public static function handle_status_change(): void {
        if ( ! current_user_can( 'manage_algq_document_requests' ) ) {
            wp_die( esc_html__( 'You are not authorized to manage document requests.', 'algq-document-library' ), 403 );
        }

        $request_id = absint( $_POST['request_id'] ?? 0 );
        check_admin_referer( 'algq_doc_request_status_' . $request_id );

        $status = sanitize_key( wp_unslash( $_POST['status'] ?? '' ) );
        $note   = sanitize_textarea_field( wp_unslash( $_POST['reviewer_note'] ?? '' ) );

        if ( ! in_array( $status, array( 'pending', 'approved', 'denied', 'expired', 'closed' ), true ) ) {
            wp_die( esc_html__( 'Invalid request status.', 'algq-document-library' ), 400 );
        }

        global $wpdb;
        $table = $wpdb->prefix . 'algq_document_requests';
        $wpdb->update(
            $table,
            array(
                'status'           => $status,
                'reviewer_user_id' => get_current_user_id(),
                'reviewer_note'    => $note,
                'updated_at'       => current_time( 'mysql', true ),
            ),
            array( 'id' => $request_id ),
            array( '%s', '%d', '%s', '%s' ),
            array( '%d' )
        );

        ALGQ_Document_Library::audit( 'document_request.status_changed', array( 'request_id' => $request_id, 'status' => $status ) );
        wp_safe_redirect( admin_url( 'admin.php?page=algq-document-requests&updated=1' ) );
        exit;
    }

    /**
     * Render the request-management screen.
     */
    public static function admin_page(): void {
        if ( ! current_user_can( 'manage_algq_document_requests' ) ) {
            wp_die( esc_html__( 'You are not authorized to view document requests.', 'algq-document-library' ) );
        }

        global $wpdb;
        $table    = $wpdb->prefix . 'algq_document_requests';
        $requests = $wpdb->get_results( "SELECT * FROM {$table} ORDER BY created_at DESC LIMIT 200" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        ?>
        <div class="wrap algq-admin">
            <h1><?php esc_html_e( 'Document Access Requests', 'algq-document-library' ); ?></h1>
            <p><?php esc_html_e( 'Review, approve, deny, expire, or close access requests. Approval does not bypass document-level authorization.', 'algq-document-library' ); ?></p>
            <div class="algq-table-wrap">
                <table class="widefat striped algq-table">
                    <thead><tr>
                        <th><?php esc_html_e( 'Requester', 'algq-document-library' ); ?></th>
                        <th><?php esc_html_e( 'Package', 'algq-document-library' ); ?></th>
                        <th><?php esc_html_e( 'Reason', 'algq-document-library' ); ?></th>
                        <th><?php esc_html_e( 'Status', 'algq-document-library' ); ?></th>
                        <th><?php esc_html_e( 'Submitted', 'algq-document-library' ); ?></th>
                        <th><?php esc_html_e( 'Review', 'algq-document-library' ); ?></th>
                    </tr></thead>
                    <tbody>
                    <?php if ( empty( $requests ) ) : ?>
                        <tr><td colspan="6"><?php esc_html_e( 'No document requests have been submitted.', 'algq-document-library' ); ?></td></tr>
                    <?php else : ?>
                        <?php foreach ( $requests as $request ) : ?>
                            <tr>
                                <td><strong><?php echo esc_html( $request->requester_name ); ?></strong><br><a href="mailto:<?php echo esc_attr( $request->requester_email ); ?>"><?php echo esc_html( $request->requester_email ); ?></a><br><?php echo esc_html( $request->requester_company ); ?></td>
                                <td><?php echo esc_html( ALGQ_Document_Library::request_packages()[ $request->package_key ] ?? $request->package_key ); ?><br><code><?php echo esc_html( $request->request_uuid ); ?></code></td>
                                <td><?php echo esc_html( wp_trim_words( $request->reason, 35 ) ); ?></td>
                                <td><span class="algq-status algq-status-<?php echo esc_attr( $request->status ); ?>"><?php echo esc_html( ucfirst( $request->status ) ); ?></span></td>
                                <td><?php echo esc_html( get_date_from_gmt( $request->created_at, 'M j, Y g:i a' ) ); ?></td>
                                <td>
                                    <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
                                        <input type="hidden" name="action" value="algq_doc_request_status" />
                                        <input type="hidden" name="request_id" value="<?php echo esc_attr( (string) $request->id ); ?>" />
                                        <?php wp_nonce_field( 'algq_doc_request_status_' . $request->id ); ?>
                                        <select name="status">
                                            <?php foreach ( array( 'pending', 'approved', 'denied', 'expired', 'closed' ) as $status ) : ?>
                                                <option value="<?php echo esc_attr( $status ); ?>" <?php selected( $request->status, $status ); ?>><?php echo esc_html( ucfirst( $status ) ); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                        <textarea name="reviewer_note" rows="2" placeholder="<?php esc_attr_e( 'Internal review note', 'algq-document-library' ); ?>"><?php echo esc_textarea( $request->reviewer_note ); ?></textarea>
                                        <button class="button" type="submit"><?php esc_html_e( 'Save', 'algq-document-library' ); ?></button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php
    }

    private static function send_notifications( string $uuid, string $name, string $email, string $company, string $package, int $document_id ): void {
        $admin_email = (string) get_option( 'admin_email' );
        $subject     = sprintf( __( 'Document access request %s', 'algq-document-library' ), $uuid );
        $message     = sprintf(
            "Name: %s\nEmail: %s\nCompany: %s\nPackage: %s\nDocument ID: %d\nRequest UUID: %s",
            $name,
            $email,
            $company,
            $package,
            $document_id,
            $uuid
        );

        if ( function_exists( 'algq_send_mail' ) ) {
            algq_send_mail(
                array(
                    'to'         => $admin_email,
                    'subject'    => $subject,
                    'message'    => $message,
                    'module'     => 'document-library',
                    'event'      => 'document_request_received',
                    'related_id' => $uuid,
                )
            );
            return;
        }

        wp_mail( $admin_email, $subject, $message );
    }

    private static function redirect( string $url, string $status ): void {
        wp_safe_redirect( add_query_arg( 'document-request', $status, $url ) );
        exit;
    }

    private static function privacy_hash( string $value ): string {
        return '' === $value ? '' : hash_hmac( 'sha256', $value, wp_salt( 'auth' ) );
    }

    private static function client_ip(): string {
        return sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ?? '' ) );
    }

    private static function current_url(): string {
        $scheme = is_ssl() ? 'https://' : 'http://';
        $host   = sanitize_text_field( wp_unslash( $_SERVER['HTTP_HOST'] ?? wp_parse_url( home_url(), PHP_URL_HOST ) ) );
        $uri    = esc_url_raw( wp_unslash( $_SERVER['REQUEST_URI'] ?? '/' ) );
        return $scheme . $host . $uri;
    }
}
