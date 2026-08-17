<?php
/**
 * Private file-storage abstraction.
 *
 * @package AlgonquianRealEstatePlatform
 */

defined( 'ABSPATH' ) || exit;

final class ALGQ_Private_Files {
	private const TOKEN_PREFIX = 'algq_private_download_';

	public static function init(): void {
		add_action( 'admin_post_algq_private_download', array( __CLASS__, 'handle_download' ) );
		add_action( 'admin_post_nopriv_algq_private_download', array( __CLASS__, 'handle_download' ) );
	}

	public static function ensure_storage(): bool {
		$root = self::root();
		if ( ! wp_mkdir_p( $root ) ) {
			return false;
		}

		$guards = array(
			'index.php'  => "<?php\n// Silence is golden.\n",
			'.htaccess'  => "Require all denied\nDeny from all\n",
			'web.config' => "<?xml version=\"1.0\" encoding=\"UTF-8\"?><configuration><system.webServer><security><authorization><remove users=\"*\" roles=\"\" verbs=\"\"/><add accessType=\"Deny\" users=\"*\"/></authorization></security></system.webServer></configuration>",
		);

		foreach ( $guards as $file => $contents ) {
			$path = trailingslashit( $root ) . $file;
			if ( ! file_exists( $path ) ) {
				file_put_contents( $path, $contents ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
			}
		}
		return is_writable( $root );
	}

	public static function root(): string {
		$uploads = wp_upload_dir();
		return trailingslashit( (string) $uploads['basedir'] ) . 'algq-private';
	}

	public static function store( string $source_path, string $relative_path ): string|WP_Error {
		if ( ! is_readable( $source_path ) || ! self::ensure_storage() ) {
			return new WP_Error( 'algq_private_source_unavailable', __( 'The source file is unavailable or private storage is not writable.', 'algonquian-real-estate-platform' ) );
		}

		$relative_path = self::sanitize_relative_path( $relative_path );
		if ( '' === $relative_path ) {
			return new WP_Error( 'algq_private_invalid_path', __( 'The private file path is invalid.', 'algonquian-real-estate-platform' ) );
		}

		$destination = trailingslashit( self::root() ) . $relative_path;
		if ( ! wp_mkdir_p( dirname( $destination ) ) || ! copy( $source_path, $destination ) ) {
			return new WP_Error( 'algq_private_store_failed', __( 'The file could not be written to private storage.', 'algonquian-real-estate-platform' ) );
		}

		ALGQ_Platform_Audit_Log::log( 'file.stored', array( 'path_hash' => hash( 'sha256', $relative_path ), 'sha256' => hash_file( 'sha256', $destination ) ) );
		return $relative_path;
	}

	public static function create_download_url( string $relative_path, int $user_id = 0, int $ttl = 300 ): string|WP_Error {
		$relative_path = self::sanitize_relative_path( $relative_path );
		$file          = trailingslashit( self::root() ) . $relative_path;
		if ( '' === $relative_path || ! is_file( $file ) ) {
			return new WP_Error( 'algq_private_missing_file', __( 'The requested private file does not exist.', 'algonquian-real-estate-platform' ) );
		}

		$token = wp_generate_password( 48, false, false );
		set_transient(
			self::TOKEN_PREFIX . hash( 'sha256', $token ),
			array( 'path' => $relative_path, 'user_id' => $user_id ),
			max( 60, min( DAY_IN_SECONDS, $ttl ) )
		);

		return add_query_arg( array( 'action' => 'algq_private_download', 'token' => rawurlencode( $token ) ), admin_url( 'admin-post.php' ) );
	}

	public static function handle_download(): void {
		$token = isset( $_GET['token'] ) ? sanitize_text_field( wp_unslash( $_GET['token'] ) ) : '';
		$data  = $token ? get_transient( self::TOKEN_PREFIX . hash( 'sha256', $token ) ) : false;
		if ( ! is_array( $data ) ) {
			status_header( 403 );
			exit;
		}

		$user_id = absint( $data['user_id'] ?? 0 );
		if ( $user_id && get_current_user_id() !== $user_id ) {
			status_header( 403 );
			exit;
		}

		$relative_path = self::sanitize_relative_path( (string) ( $data['path'] ?? '' ) );
		$file          = trailingslashit( self::root() ) . $relative_path;
		if ( '' === $relative_path || ! is_readable( $file ) ) {
			status_header( 404 );
			exit;
		}

		delete_transient( self::TOKEN_PREFIX . hash( 'sha256', $token ) );
		ALGQ_Platform_Audit_Log::log( 'file.downloaded', array( 'path_hash' => hash( 'sha256', $relative_path ), 'sha256' => hash_file( 'sha256', $file ) ) );

		nocache_headers();
		header( 'X-Content-Type-Options: nosniff' );
		header( 'Content-Type: application/octet-stream' );
		header( 'Content-Disposition: attachment; filename="' . rawurlencode( basename( $file ) ) . '"' );
		header( 'Content-Length: ' . (string) filesize( $file ) );
		readfile( $file ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_readfile
		exit;
	}

	private static function sanitize_relative_path( string $path ): string {
		$path = ltrim( wp_normalize_path( $path ), '/' );
		if ( '' === $path || str_contains( $path, '../' ) || str_contains( $path, "\0" ) ) {
			return '';
		}
		$parts = array_map( 'sanitize_file_name', explode( '/', $path ) );
		return implode( '/', array_filter( $parts, static fn( string $part ): bool => '' !== $part ) );
	}
}
