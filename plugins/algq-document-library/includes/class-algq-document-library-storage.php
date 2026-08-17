<?php
/**
 * Private document storage and protected delivery.
 */

defined( 'ABSPATH' ) || exit;

final class ALGQ_Document_Library_Storage {

    /**
     * Return the private base directory.
     */
    public static function base_dir(): string {
        $upload = wp_upload_dir();
        return trailingslashit( $upload['basedir'] ) . 'algq-private-documents';
    }

    /**
     * Ensure private storage and defense-in-depth blocking files exist.
     */
    public static function ensure_private_directory(): bool {
        $dir = self::base_dir();

        if ( ! wp_mkdir_p( $dir ) ) {
            return false;
        }

        $files = array(
            'index.php'  => "<?php\n// Silence is golden.\n",
            '.htaccess'  => "Options -Indexes\n<FilesMatch \".*\">\nRequire all denied\n</FilesMatch>\n",
            'web.config' => "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n<configuration><system.webServer><security><authorization><remove users=\"*\" roles=\"\" verbs=\"\"/><add accessType=\"Deny\" users=\"*\"/></authorization></security></system.webServer></configuration>\n",
        );

        foreach ( $files as $filename => $contents ) {
            $path = trailingslashit( $dir ) . $filename;
            if ( ! file_exists( $path ) ) {
                wp_mkdir_p( dirname( $path ) );
                file_put_contents( $path, $contents ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
            }
        }

        return true;
    }

    /**
     * Validate and move an uploaded document into private storage.
     *
     * @param array<string,mixed> $file Uploaded file array.
     * @return array<string,string>|WP_Error
     */
    public static function store_upload( array $file ) {
        if ( empty( $file['tmp_name'] ) || ! is_uploaded_file( $file['tmp_name'] ) ) {
            return new WP_Error( 'algq_invalid_upload', __( 'The uploaded file could not be validated.', 'algq-document-library' ) );
        }

        $allowed = apply_filters(
            'algq_document_library_allowed_mimes',
            array(
                'pdf'      => 'application/pdf',
                'doc'      => 'application/msword',
                'docx'     => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                'xls'      => 'application/vnd.ms-excel',
                'xlsx'     => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'csv'      => 'text/csv',
                'txt'      => 'text/plain',
                'jpg|jpeg' => 'image/jpeg',
                'png'      => 'image/png',
            )
        );

        $check = wp_check_filetype_and_ext( $file['tmp_name'], sanitize_file_name( $file['name'] ), $allowed );

        if ( empty( $check['ext'] ) || empty( $check['type'] ) ) {
            return new WP_Error( 'algq_disallowed_file_type', __( 'This file type is not permitted.', 'algq-document-library' ) );
        }

        $max_size = (int) apply_filters( 'algq_document_library_max_upload_bytes', 15 * MB_IN_BYTES );
        if ( (int) $file['size'] > $max_size ) {
            return new WP_Error( 'algq_file_too_large', __( 'The uploaded document exceeds the permitted size.', 'algq-document-library' ) );
        }

        if ( ! self::ensure_private_directory() ) {
            return new WP_Error( 'algq_storage_unavailable', __( 'Private document storage is unavailable.', 'algq-document-library' ) );
        }

        $safe_name = wp_unique_filename(
            self::base_dir(),
            wp_generate_uuid4() . '-' . sanitize_file_name( pathinfo( $file['name'], PATHINFO_FILENAME ) ) . '.' . $check['ext']
        );
        $target = trailingslashit( self::base_dir() ) . $safe_name;

        if ( ! move_uploaded_file( $file['tmp_name'], $target ) ) {
            return new WP_Error( 'algq_move_failed', __( 'The document could not be moved into private storage.', 'algq-document-library' ) );
        }

        @chmod( $target, 0640 ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged

        return array(
            'relative_path' => $safe_name,
            'original_name' => sanitize_file_name( $file['name'] ),
            'mime_type'     => $check['type'],
            'file_hash'     => hash_file( 'sha256', $target ),
            'file_size'     => (string) filesize( $target ),
        );
    }

    /**
     * Resolve a stored relative path without allowing traversal.
     */
    public static function resolve_path( string $relative_path ): string {
        $relative_path = ltrim( str_replace( array( '../', '..\\' ), '', $relative_path ), '/\\' );
        $candidate     = trailingslashit( self::base_dir() ) . $relative_path;
        $real_base     = realpath( self::base_dir() );
        $real_file     = realpath( $candidate );

        if ( ! $real_base || ! $real_file || ! str_starts_with( $real_file, $real_base . DIRECTORY_SEPARATOR ) ) {
            return '';
        }

        return $real_file;
    }

    /**
     * Stream a private file after authorization has already been confirmed.
     */
    public static function stream_file( int $document_id ): void {
        $relative_path = (string) get_post_meta( $document_id, '_algq_doc_private_path', true );
        $path          = self::resolve_path( $relative_path );

        if ( ! $path || ! is_readable( $path ) ) {
            status_header( 404 );
            wp_die( esc_html__( 'The requested document file is not available.', 'algq-document-library' ) );
        }

        $filename = (string) get_post_meta( $document_id, '_algq_doc_original_name', true );
        $mime     = (string) get_post_meta( $document_id, '_algq_doc_mime_type', true );
        $filename = $filename ?: basename( $path );
        $mime     = $mime ?: 'application/octet-stream';

        nocache_headers();
        header( 'X-Content-Type-Options: nosniff' );
        header( 'Content-Type: ' . $mime );
        header( 'Content-Length: ' . (string) filesize( $path ) );
        header( 'Content-Disposition: attachment; filename="' . rawurlencode( $filename ) . '"' );
        header( 'Content-Security-Policy: sandbox' );
        readfile( $path ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_readfile
        exit;
    }
}
