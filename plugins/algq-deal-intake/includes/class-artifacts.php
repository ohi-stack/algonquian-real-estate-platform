<?php
/**
 * Submission artifacts, secure supporting uploads, and PDF archival delivery.
 *
 * @package Algonquian_Deal_Intake
 */

defined( 'ABSPATH' ) || exit;

final class ALGQ_Deal_Intake_Artifacts {
	private const UPLOAD_FIELD = 'property_files';
	private const DEFAULT_ARCHIVE_EMAIL = 'algonquianre@gmail.com';
	private const PRIVATE_ATTACHMENT_META = '_algq_di_private_attachment';
	private const SUBMISSION_META = '_algq_di_submission_id';
	private const HASH_META = '_algq_di_sha256';

	public static function register_hooks(): void {
		self::ensure_defaults();
		add_filter( 'do_shortcode_tag', array( __CLASS__, 'enhance_form_output' ), 20, 4 );
		add_action( 'admin_post_nopriv_algq_di_submit_public', array( __CLASS__, 'verify_turnstile' ), 2 );
		add_action( 'admin_post_algq_di_submit_public', array( __CLASS__, 'verify_turnstile' ), 2 );
		add_action( 'algq_deal_intake_submission_created', array( __CLASS__, 'handle_submission_created' ), 20, 2 );
		add_action( 'admin_post_algq_di_download_artifact', array( __CLASS__, 'download_artifact' ) );
		add_filter( 'wp_get_attachment_url', array( __CLASS__, 'protected_attachment_url' ), 10, 2 );
	}

	private static function ensure_defaults(): void {
		add_option( 'algq_di_archive_email', self::DEFAULT_ARCHIVE_EMAIL );
		add_option( 'algq_di_archive_pdf_enabled', 1 );
		add_option( 'algq_di_pdf_media_library_enabled', 1 );
		add_option( 'algq_di_max_upload_bytes', 10 * MB_IN_BYTES );
		add_option( 'algq_di_max_upload_files', 8 );
		add_option( 'algq_di_max_email_attachment_bytes', 15 * MB_IN_BYTES );
	}

	public static function enhance_form_output( string $output, string $tag, array|string $attr, array $match ): string { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
		$form_tags = array( 'algq_deal_intake_form', 'algq_property_submission', 'deal_intake_form_public', 'deal_intake_form_internal', 'deal_quick_capture', 'algq_seller_intake_entry' );
		if ( ! in_array( $tag, $form_tags, true ) || false === strpos( $output, 'algq-di-form' ) ) {
			return $output;
		}
		if ( false === stripos( $output, 'enctype=' ) ) {
			$output = preg_replace( '/<form\s+class="algq-di-form"\s+method="post"/i', '<form class="algq-di-form" method="post" enctype="multipart/form-data"', $output, 1 ) ?? $output;
		}
		if ( false === strpos( $output, 'name="' . self::UPLOAD_FIELD . '[]"' ) ) {
			$upload = '<div class="algq-di-upload algq-di-span-2"><label>' . esc_html__( 'Supporting Files (optional)', 'algq-deal-intake' ) . '<input type="file" name="' . esc_attr( self::UPLOAD_FIELD ) . '[]" multiple accept=".pdf,.jpg,.jpeg,.png,.webp,.docx"><small>' . esc_html__( 'Upload property photos, a tax bill, lease, inspection, floor plan, or another relevant non-executable document. Maximum 8 files; 10 MB each by default.', 'algq-deal-intake' ) . '</small></label></div>';
			$output = preg_replace( '/(<label\s+class="algq-di-consent">)/i', $upload . '$1', $output, 1 ) ?? $output;
		}
		$site_key = self::turnstile_site_key();
		$secret_key = self::turnstile_secret_key();
		if ( '' !== $site_key && '' !== $secret_key && false === strpos( $output, 'cf-turnstile' ) ) {
			wp_enqueue_script( 'algq-di-turnstile', 'https://challenges.cloudflare.com/turnstile/v0/api.js', array(), null, true ); // phpcs:ignore WordPress.WP.EnqueuedResourceParameters.NoExplicitVersion
			$challenge = '<div class="algq-di-turnstile algq-di-span-2"><div class="cf-turnstile" data-sitekey="' . esc_attr( $site_key ) . '"></div></div>';
			$output = preg_replace( '/(<label\s+class="algq-di-consent">)/i', $challenge . '$1', $output, 1 ) ?? $output;
		}
		return $output;
	}

	public static function verify_turnstile(): void {
		$secret = self::turnstile_secret_key();
		if ( '' === $secret ) {
			return;
		}
		$response_token = isset( $_POST['cf-turnstile-response'] ) ? sanitize_text_field( wp_unslash( $_POST['cf-turnstile-response'] ) ) : '';
		if ( '' === $response_token ) {
			self::security_redirect();
		}
		$response = wp_remote_post( 'https://challenges.cloudflare.com/turnstile/v0/siteverify', array( 'timeout' => 5, 'body' => array( 'secret' => $secret, 'response' => $response_token, 'remoteip' => ALGQ_Deal_Intake_Security::request_ip() ) ) );
		if ( is_wp_error( $response ) ) {
			self::security_redirect();
		}
		$body = json_decode( wp_remote_retrieve_body( $response ), true );
		if ( ! is_array( $body ) || empty( $body['success'] ) ) {
			self::security_redirect();
		}
	}

	private static function security_redirect(): void {
		do_action( 'algq_audit_event', 'deal_intake.security_challenge_failed', array( 'ip_hash' => hash_hmac( 'sha256', ALGQ_Deal_Intake_Security::request_ip(), wp_salt( 'nonce' ) ) ) );
		$referer = wp_get_referer() ?: home_url( '/' );
		wp_safe_redirect( add_query_arg( 'algq_di_error', 'security_challenge_failed', $referer ) );
		exit;
	}

	private static function turnstile_site_key(): string {
		$value = defined( 'ALGQ_DI_TURNSTILE_SITE_KEY' ) ? (string) ALGQ_DI_TURNSTILE_SITE_KEY : (string) get_option( 'algq_di_turnstile_site_key', '' );
		return trim( (string) apply_filters( 'algq_di_turnstile_site_key', $value ) );
	}

	private static function turnstile_secret_key(): string {
		$value = defined( 'ALGQ_DI_TURNSTILE_SECRET_KEY' ) ? (string) ALGQ_DI_TURNSTILE_SECRET_KEY : (string) get_option( 'algq_di_turnstile_secret_key', '' );
		return trim( (string) apply_filters( 'algq_di_turnstile_secret_key', $value ) );
	}

	public static function handle_submission_created( int $submission_id, array $record ): void {
		$uploaded = self::process_uploaded_files( $submission_id );
		if ( ! get_option( 'algq_di_archive_pdf_enabled', 1 ) ) {
			return;
		}
		$pdf = self::create_submission_pdf( $submission_id, $record, $uploaded );
		if ( is_wp_error( $pdf ) ) {
			do_action( 'algq_audit_event', 'deal_intake.pdf_archive_failed', array( 'submission_id' => $submission_id, 'error' => $pdf->get_error_message() ) );
			return;
		}
		self::send_archive_email( $submission_id, $record, $pdf, $uploaded );
	}

	private static function process_uploaded_files( int $submission_id ): array {
		if ( empty( $_FILES[ self::UPLOAD_FIELD ] ) || ! is_array( $_FILES[ self::UPLOAD_FIELD ] ) ) {
			return array();
		}
		$files = self::normalize_file_array( $_FILES[ self::UPLOAD_FIELD ] ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$max_files = max( 1, absint( apply_filters( 'algq_di_max_upload_files', get_option( 'algq_di_max_upload_files', 8 ) ) ) );
		$stored = array();
		foreach ( array_slice( $files, 0, $max_files ) as $file ) {
			$result = self::store_uploaded_file( $submission_id, $file );
			if ( is_wp_error( $result ) ) {
				do_action( 'algq_audit_event', 'deal_intake.attachment_rejected', array( 'submission_id' => $submission_id, 'file_name' => sanitize_file_name( (string) ( $file['name'] ?? '' ) ), 'error' => $result->get_error_code() ) );
				continue;
			}
			$stored[] = $result;
		}
		if ( $stored ) {
			do_action( 'algq_audit_event', 'deal_intake.attachments_stored', array( 'submission_id' => $submission_id, 'count' => count( $stored ) ) );
		}
		return $stored;
	}

	private static function normalize_file_array( array $input ): array {
		$names = $input['name'] ?? array();
		if ( ! is_array( $names ) ) {
			return array( $input );
		}
		$files = array();
		foreach ( array_keys( $names ) as $index ) {
			$files[] = array( 'name' => $input['name'][ $index ] ?? '', 'type' => $input['type'][ $index ] ?? '', 'tmp_name' => $input['tmp_name'][ $index ] ?? '', 'error' => $input['error'][ $index ] ?? UPLOAD_ERR_NO_FILE, 'size' => $input['size'][ $index ] ?? 0 );
		}
		return $files;
	}

	private static function store_uploaded_file( int $submission_id, array $file ): array|WP_Error {
		global $wpdb;
		$error = absint( $file['error'] ?? UPLOAD_ERR_NO_FILE );
		if ( UPLOAD_ERR_NO_FILE === $error ) {
			return new WP_Error( 'no_file', __( 'No file was selected.', 'algq-deal-intake' ) );
		}
		if ( UPLOAD_ERR_OK !== $error ) {
			return new WP_Error( 'upload_error', __( 'The supporting file upload did not complete.', 'algq-deal-intake' ) );
		}
		$name = sanitize_file_name( (string) ( $file['name'] ?? '' ) );
		$tmp = (string) ( $file['tmp_name'] ?? '' );
		$size = absint( $file['size'] ?? 0 );
		$max_bytes = max( 1, absint( apply_filters( 'algq_di_max_upload_bytes', get_option( 'algq_di_max_upload_bytes', 10 * MB_IN_BYTES ) ) ) );
		if ( '' === $name || '' === $tmp || ! is_uploaded_file( $tmp ) ) {
			return new WP_Error( 'invalid_upload', __( 'The supporting file could not be verified as an uploaded file.', 'algq-deal-intake' ) );
		}
		if ( 0 === $size || $size > $max_bytes ) {
			return new WP_Error( 'file_size', __( 'The supporting file exceeds the permitted size.', 'algq-deal-intake' ) );
		}
		$mimes = (array) apply_filters( 'algq_di_allowed_upload_mimes', array( 'pdf' => 'application/pdf', 'jpg|jpeg|jpe' => 'image/jpeg', 'png' => 'image/png', 'webp' => 'image/webp', 'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document' ) );
		$checked = wp_check_filetype_and_ext( $tmp, $name, $mimes );
		$ext = sanitize_key( (string) ( $checked['ext'] ?? '' ) );
		$mime = sanitize_mime_type( (string) ( $checked['type'] ?? '' ) );
		if ( '' === $ext || '' === $mime || ! in_array( $mime, array_values( $mimes ), true ) ) {
			return new WP_Error( 'file_type', __( 'The supporting file type is not permitted.', 'algq-deal-intake' ) );
		}
		$root = self::private_storage_root();
		$relative_dir = 'uploads/' . gmdate( 'Y/m' );
		$target_dir = trailingslashit( $root ) . $relative_dir;
		if ( ! self::ensure_private_directory( $target_dir ) ) {
			return new WP_Error( 'storage_unavailable', __( 'Secure file storage is unavailable.', 'algq-deal-intake' ) );
		}
		$target_name = wp_generate_uuid4() . '.' . $ext;
		$target_path = trailingslashit( $target_dir ) . $target_name;
		if ( ! move_uploaded_file( $tmp, $target_path ) ) {
			return new WP_Error( 'move_failed', __( 'The supporting file could not be moved into secure storage.', 'algq-deal-intake' ) );
		}
		@chmod( $target_path, 0640 );
		$checksum = hash_file( 'sha256', $target_path ) ?: '';
		$storage_key = 'private:' . $relative_dir . '/' . $target_name;
		$inserted = $wpdb->insert( ALGQ_Deal_Intake_Database::table( 'attachments' ), array( 'submission_id' => $submission_id, 'storage_key' => $storage_key, 'original_name' => $name, 'mime_type' => $mime, 'file_size' => (int) filesize( $target_path ), 'checksum' => $checksum, 'created_by' => ALGQ_Deal_Intake_Security::current_user_id(), 'created_at' => current_time( 'mysql', true ) ), array( '%d', '%s', '%s', '%s', '%d', '%s', '%d', '%s' ) );
		if ( false === $inserted ) {
			@unlink( $target_path );
			return new WP_Error( 'attachment_record_failed', __( 'The supporting file record could not be saved.', 'algq-deal-intake' ) );
		}
		return array( 'id' => (int) $wpdb->insert_id, 'original_name' => $name, 'mime_type' => $mime, 'file_size' => (int) filesize( $target_path ), 'checksum' => $checksum, 'storage_key' => $storage_key, 'path' => $target_path );
	}

	private static function create_submission_pdf( int $submission_id, array $record, array $uploaded ): array|WP_Error {
		$pdf_bytes = self::build_pdf( self::pdf_lines( $record, $uploaded ) );
		$root = self::private_storage_root();
		$relative_dir = 'pdfs/' . gmdate( 'Y/m' );
		$target_dir = trailingslashit( $root ) . $relative_dir;
		if ( ! self::ensure_private_directory( $target_dir ) ) {
			return new WP_Error( 'pdf_storage_unavailable', __( 'PDF archive storage is unavailable.', 'algq-deal-intake' ) );
		}
		$reference = sanitize_file_name( (string) ( $record['uuid'] ?? (string) $submission_id ) );
		$filename = 'algq-intake-' . ( $reference ?: (string) $submission_id ) . '-' . wp_generate_uuid4() . '.pdf';
		$path = trailingslashit( $target_dir ) . $filename;
		if ( false === file_put_contents( $path, $pdf_bytes, LOCK_EX ) ) {
			return new WP_Error( 'pdf_write_failed', __( 'The intake PDF could not be written.', 'algq-deal-intake' ) );
		}
		@chmod( $path, 0640 );
		$checksum = hash_file( 'sha256', $path ) ?: '';
		$attachment_id = get_option( 'algq_di_pdf_media_library_enabled', 1 ) ? self::register_pdf_attachment( $submission_id, $record, $path, $checksum ) : 0;
		self::record_pdf_attachment( $submission_id, $path, $filename, $checksum, $attachment_id );
		do_action( 'algq_audit_event', 'deal_intake.pdf_archived', array( 'submission_id' => $submission_id, 'attachment_id' => $attachment_id, 'sha256' => $checksum ) );
		return array( 'path' => $path, 'filename' => $filename, 'attachment_id' => $attachment_id, 'checksum' => $checksum );
	}

	private static function register_pdf_attachment( int $submission_id, array $record, string $path, string $checksum ): int {
		$title = sprintf( __( 'Deal Intake %1$s — %2$s', 'algq-deal-intake' ), (string) ( $record['uuid'] ?? $submission_id ), (string) ( $record['address'] ?? '' ) );
		$attachment_id = wp_insert_attachment( array( 'post_mime_type' => 'application/pdf', 'post_title' => sanitize_text_field( $title ), 'post_content' => '', 'post_status' => 'inherit' ), $path, 0, true );
		if ( is_wp_error( $attachment_id ) ) {
			return 0;
		}
		$attachment_id = (int) $attachment_id;
		update_attached_file( $attachment_id, $path );
		update_post_meta( $attachment_id, self::PRIVATE_ATTACHMENT_META, 1 );
		update_post_meta( $attachment_id, self::SUBMISSION_META, $submission_id );
		update_post_meta( $attachment_id, self::HASH_META, $checksum );
		update_post_meta( $attachment_id, '_algq_document_class', 'deal-intake-pdf' );
		if ( ! function_exists( 'wp_generate_attachment_metadata' ) ) {
			require_once ABSPATH . 'wp-admin/includes/image.php';
		}
		$metadata = wp_generate_attachment_metadata( $attachment_id, $path );
		if ( is_array( $metadata ) ) {
			wp_update_attachment_metadata( $attachment_id, $metadata );
		}
		return $attachment_id;
	}

	private static function record_pdf_attachment( int $submission_id, string $path, string $filename, string $checksum, int $attachment_id ): void {
		global $wpdb;
		$root = trailingslashit( self::private_storage_root() );
		$relative = ltrim( str_replace( $root, '', $path ), '/\\' );
		$storage_key = $attachment_id ? 'media:' . $attachment_id : 'private:' . str_replace( DIRECTORY_SEPARATOR, '/', $relative );
		$wpdb->insert( ALGQ_Deal_Intake_Database::table( 'attachments' ), array( 'submission_id' => $submission_id, 'storage_key' => $storage_key, 'original_name' => $filename, 'mime_type' => 'application/pdf', 'file_size' => (int) filesize( $path ), 'checksum' => $checksum, 'created_by' => ALGQ_Deal_Intake_Security::current_user_id(), 'created_at' => current_time( 'mysql', true ) ), array( '%d', '%s', '%s', '%s', '%d', '%s', '%d', '%s' ) );
	}

	private static function pdf_lines( array $record, array $uploaded ): array {
		$money = static function ( mixed $value ): string { $amount = (float) $value; return $amount > 0 ? '$' . number_format( $amount, 2 ) : 'Not provided'; };
		return (array) apply_filters( 'algq_di_submission_pdf_lines', array(
			'ALGONQUIAN REAL ESTATE LLC', 'DEAL INTAKE SUBMISSION RECORD', '',
			'Reference: ' . (string) ( $record['uuid'] ?? '' ), 'Submission ID: ' . (string) ( $record['id'] ?? '' ), 'Received (UTC): ' . (string) ( $record['created_at'] ?? current_time( 'mysql', true ) ), 'Status: ' . (string) ( $record['status'] ?? 'pending_review' ), '',
			'SELLER / CONTACT', 'Name: ' . (string) ( $record['seller_name'] ?? '' ), 'Email: ' . (string) ( $record['seller_email'] ?? '' ), 'Phone: ' . (string) ( $record['seller_phone'] ?? '' ), '',
			'PROPERTY', 'Address: ' . trim( (string) ( $record['address'] ?? '' ) . ', ' . (string) ( $record['city'] ?? '' ) . ', ' . (string) ( $record['state'] ?? '' ) . ' ' . (string) ( $record['postal_code'] ?? '' ), ', ' ), 'Property type: ' . (string) ( $record['property_type'] ?? '' ), 'Asking price: ' . $money( $record['asking_price'] ?? 0 ), 'Timeline: ' . (string) ( $record['timeline'] ?? '' ), 'Lead source: ' . (string) ( $record['lead_source'] ?? '' ), 'Lead score: ' . (string) ( $record['lead_score'] ?? 0 ), 'Duplicate status: ' . (string) ( $record['duplicate_status'] ?? '' ), '',
			'PROPERTY CONDITION / SITUATION', (string) ( $record['condition_summary'] ?? 'Not provided' ), '', 'REASON FOR EXPLORING OPTIONS', (string) ( $record['motivation'] ?? 'Not provided' ), '',
			'SUPPORTING FILES', $uploaded ? (string) count( $uploaded ) . ' supporting file(s) stored in protected Deal Intake storage.' : 'No supporting files were uploaded.', '',
			'NOTICE', 'This record documents an intake submission. It is not an appraisal, brokerage agreement, loan commitment, purchase contract, legal opinion, or commitment by Algonquian Real Estate LLC to acquire the property.'
		), $record, $uploaded );
	}

	private static function build_pdf( array $lines ): string {
		$wrapped = array();
		foreach ( $lines as $line ) {
			$line = trim( preg_replace( '/\s+/u', ' ', (string) $line ) ?? (string) $line );
			if ( '' === $line ) { $wrapped[] = ''; continue; }
			foreach ( explode( "\n", wordwrap( $line, 88, "\n", true ) ) as $part ) { $wrapped[] = $part; }
		}
		$pages = array_chunk( $wrapped, 42 );
		if ( ! $pages ) { $pages = array( array( 'No content.' ) ); }
		$objects = array( 1 => '<< /Type /Catalog /Pages 2 0 R >>', 3 => '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>' );
		$kids = array();
		foreach ( $pages as $index => $page_lines ) {
			$page_object = 4 + ( $index * 2 ); $content_object = $page_object + 1; $kids[] = $page_object . ' 0 R';
			$objects[ $page_object ] = '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 612 792] /Resources << /Font << /F1 3 0 R >> >> /Contents ' . $content_object . ' 0 R >>';
			$stream = "BT\n/F1 10 Tf\n"; $y = 748;
			foreach ( $page_lines as $line ) { $stream .= '1 0 0 1 54 ' . $y . " Tm\n(" . self::pdf_escape( $line ) . ") Tj\n"; $y -= 16; }
			$stream .= "ET\n"; $objects[ $content_object ] = '<< /Length ' . strlen( $stream ) . ">>\nstream\n" . $stream . 'endstream';
		}
		$objects[2] = '<< /Type /Pages /Count ' . count( $pages ) . ' /Kids [' . implode( ' ', $kids ) . '] >>'; ksort( $objects );
		$pdf = "%PDF-1.4\n%\xE2\xE3\xCF\xD3\n"; $offsets = array( 0 => 0 );
		foreach ( $objects as $number => $object ) { $offsets[ $number ] = strlen( $pdf ); $pdf .= $number . " 0 obj\n" . $object . "\nendobj\n"; }
		$xref = strlen( $pdf ); $max_object = max( array_keys( $objects ) ); $pdf .= "xref\n0 " . ( $max_object + 1 ) . "\n0000000000 65535 f \n";
		for ( $i = 1; $i <= $max_object; $i++ ) { $pdf .= sprintf( "%010d 00000 n \n", $offsets[ $i ] ?? 0 ); }
		$pdf .= "trailer\n<< /Size " . ( $max_object + 1 ) . " /Root 1 0 R >>\nstartxref\n" . $xref . "\n%%EOF\n";
		return $pdf;
	}

	private static function pdf_escape( string $text ): string {
		if ( function_exists( 'iconv' ) ) { $converted = iconv( 'UTF-8', 'Windows-1252//TRANSLIT//IGNORE', $text ); if ( false !== $converted ) { $text = $converted; } }
		$text = preg_replace( '/[^\x20-\x7E\x80-\xFF]/', ' ', $text ) ?? $text;
		return str_replace( array( '\\', '(', ')' ), array( '\\\\', '\\(', '\\)' ), $text );
	}

	private static function send_archive_email( int $submission_id, array $record, array $pdf, array $uploaded ): void {
		$email = sanitize_email( (string) apply_filters( 'algq_di_archive_email', get_option( 'algq_di_archive_email', self::DEFAULT_ARCHIVE_EMAIL ), $record ) );
		if ( ! is_email( $email ) || empty( $pdf['path'] ) || ! is_readable( (string) $pdf['path'] ) ) { return; }
		$subject = sprintf( 'Algonquian Deal Intake PDF — %s — %s', (string) ( $record['uuid'] ?? $submission_id ), (string) ( $record['address'] ?? '' ) );
		$message = implode( "\n", array( 'A new Algonquian Deal Intake submission has been archived as PDF.', '', 'Reference: ' . (string) ( $record['uuid'] ?? '' ), 'Submission ID: ' . $submission_id, 'Property: ' . (string) ( $record['address'] ?? '' ) . ', ' . (string) ( $record['city'] ?? '' ) . ', ' . (string) ( $record['state'] ?? '' ), 'Seller: ' . (string) ( $record['seller_name'] ?? '' ), 'Lead score: ' . (string) ( $record['lead_score'] ?? 0 ), 'Supporting files stored: ' . count( $uploaded ), 'PDF SHA-256: ' . (string) ( $pdf['checksum'] ?? '' ), 'Media Library attachment ID: ' . (string) ( $pdf['attachment_id'] ?? 0 ) ) );
		$max_attachment = max( 1, absint( apply_filters( 'algq_di_max_email_attachment_bytes', get_option( 'algq_di_max_email_attachment_bytes', 15 * MB_IN_BYTES ) ) ) );
		$attachments = filesize( (string) $pdf['path'] ) <= $max_attachment ? array( (string) $pdf['path'] ) : array();
		if ( ! $attachments ) { $message .= "\nPDF attachment skipped because it exceeded the configured email attachment ceiling; the protected archive remains authoritative."; }
		$sent = wp_mail( $email, $subject, $message, array( 'Content-Type: text/plain; charset=UTF-8' ), $attachments );
		do_action( 'algq_audit_event', $sent ? 'deal_intake.pdf_archive_emailed' : 'deal_intake.pdf_archive_email_failed', array( 'submission_id' => $submission_id, 'to' => $email, 'attachment_id' => (int) ( $pdf['attachment_id'] ?? 0 ), 'attached' => ! empty( $attachments ) ) );
	}

	private static function private_storage_root(): string {
		$uploads = wp_upload_dir(); $default = trailingslashit( (string) $uploads['basedir'] ) . 'algq-private/deal-intake'; $root = (string) apply_filters( 'algq_di_private_storage_dir', $default ); return untrailingslashit( wp_normalize_path( $root ) );
	}

	private static function ensure_private_directory( string $directory ): bool {
		if ( ! wp_mkdir_p( $directory ) ) { return false; }
		$root = self::private_storage_root(); if ( ! is_dir( $root ) && ! wp_mkdir_p( $root ) ) { return false; }
		$index = trailingslashit( $root ) . 'index.php'; if ( ! file_exists( $index ) ) { @file_put_contents( $index, "<?php\n// Silence is golden.\n" ); }
		$htaccess = trailingslashit( $root ) . '.htaccess'; if ( ! file_exists( $htaccess ) ) { @file_put_contents( $htaccess, "Options -Indexes\n<IfModule mod_authz_core.c>\nRequire all denied\n</IfModule>\n<IfModule !mod_authz_core.c>\nDeny from all\n</IfModule>\n" ); }
		return is_writable( $directory );
	}

	public static function protected_attachment_url( string $url, int $attachment_id ): string {
		if ( 1 !== absint( get_post_meta( $attachment_id, self::PRIVATE_ATTACHMENT_META, true ) ) ) { return $url; }
		$file = get_attached_file( $attachment_id ); if ( ! $file ) { return $url; }
		return add_query_arg( array( 'action' => 'algq_di_download_artifact', 'attachment_id' => $attachment_id, 'token' => self::artifact_token( $attachment_id, $file ) ), admin_url( 'admin-post.php' ) );
	}

	public static function download_artifact(): void {
		if ( ! current_user_can( ALGQ_Deal_Intake_Security::CAP_VIEW_PRIVATE ) ) { wp_die( esc_html__( 'You do not have permission to download this intake artifact.', 'algq-deal-intake' ), '', array( 'response' => 403 ) ); }
		$attachment_id = isset( $_GET['attachment_id'] ) ? absint( $_GET['attachment_id'] ) : 0; $token = isset( $_GET['token'] ) ? sanitize_text_field( wp_unslash( $_GET['token'] ) ) : ''; $file = $attachment_id ? get_attached_file( $attachment_id ) : '';
		if ( ! $attachment_id || ! $file || ! hash_equals( self::artifact_token( $attachment_id, $file ), $token ) ) { wp_die( esc_html__( 'The intake artifact request is invalid.', 'algq-deal-intake' ), '', array( 'response' => 403 ) ); }
		$root_real = realpath( self::private_storage_root() ); $file_real = realpath( $file );
		if ( false === $root_real || false === $file_real || 0 !== strpos( wp_normalize_path( $file_real ), trailingslashit( wp_normalize_path( $root_real ) ) ) || ! is_readable( $file_real ) ) { wp_die( esc_html__( 'The intake artifact is unavailable.', 'algq-deal-intake' ), '', array( 'response' => 404 ) ); }
		nocache_headers(); header( 'Content-Type: application/pdf' ); header( 'Content-Length: ' . (string) filesize( $file_real ) ); header( 'Content-Disposition: attachment; filename="' . sanitize_file_name( basename( $file_real ) ) . '"' ); header( 'X-Content-Type-Options: nosniff' ); readfile( $file_real ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_readfile
		exit;
	}

	private static function artifact_token( int $attachment_id, string $file ): string { return hash_hmac( 'sha256', $attachment_id . '|' . wp_normalize_path( $file ), wp_salt( 'auth' ) ); }
}
