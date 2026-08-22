<?php
/**
 * Authoritative companion-plugin registry.
 *
 * @package AlgonquianRealEstatePlatform
 */

defined( 'ABSPATH' ) || exit;

final class ALGQ_Platform_Registry {
	/** @var array<string,array<string,mixed>> */
	private static array $plugins = array();

	public static function init(): void {
		self::$plugins = self::defaults();
		add_filter( 'all_plugins', array( __CLASS__, 'standardize_plugin_metadata' ) );
		do_action( 'algq_platform_registry_ready' );
	}

	/**
	 * Normalize the developer identity and public plugin URL shown on the
	 * WordPress Plugins screen for every Algonquian Real Estate plugin.
	 *
	 * @param array<string,array<string,mixed>> $plugins Installed plugin data.
	 * @return array<string,array<string,mixed>>
	 */
	public static function standardize_plugin_metadata( array $plugins ): array {
		$developer     = 'Algonquian Real Estate, LLC';
		$developer_url = 'https://algonquianrealestate.com/technology/';
		$plugin_urls   = array(
			'algonquian-real-estate-platform/algonquian-real-estate-platform.php' => 'https://algonquianrealestate.com/algonquian-real-estate-platform/',
			'algq-command-center/algq-command-center.php'                         => 'https://algonquianrealestate.com/algonquian-admin-command-center/',
			'algq-automation-engine/algq-automation-engine.php'                   => 'https://algonquianrealestate.com/algonquian-automation-engine/',
			'algq-buyer-portal/algq-buyer-portal.php'                             => 'https://algonquianrealestate.com/algonquian-buyer-portal/',
			'algq-deal-intake/algq-deal-intake.php'                               => 'https://algonquianrealestate.com/algonquian-deal-intake/',
			'algq-deal-marketplace/algq-deal-marketplace.php'                     => 'https://algonquianrealestate.com/algonquian-deal-marketplace/',
			'algq-digital-products/algq-digital-products.php'                     => 'https://algonquianrealestate.com/algonquian-digital-products/',
			'algq-digital-store/algq-digital-store.php'                           => 'https://algonquianrealestate.com/algonquian-digital-store/',
			'algq-document-library/algq-document-library.php'                     => 'https://algonquianrealestate.com/algonquian-document-library/',
			'algq-funding-tracker/algq-funding-tracker.php'                       => 'https://algonquianrealestate.com/algonquian-funding-tracker/',
			'algq-mao-engine/algq-mao-engine.php'                                 => 'https://algonquianrealestate.com/algonquian-mao-engine/',
			'algonquian-navigation/algonquian-navigation.php'                     => 'https://algonquianrealestate.com/algonquian-navigation/',
			'algq-offer-generator/algq-offer-generator.php'                       => 'https://algonquianrealestate.com/algonquian-offer-generator/',
			'algq-pdf-signature/algq-pdf-signature.php'                           => 'https://algonquianrealestate.com/algonquian-pdf-signature-engine/',
			'algq-pipeline-crm/algq-pipeline-crm.php'                             => 'https://algonquianrealestate.com/algonquian-pipeline-crm/',
			'algq-property-stewardship/algq-property-stewardship.php'             => 'https://algonquianrealestate.com/algonquian-property-stewardship-services/',
			'algq-woocommerce-bridge/algq-woocommerce-bridge.php'                 => 'https://algonquianrealestate.com/algq-woocommerce-bridge/',
		);

		foreach ( $plugin_urls as $file => $plugin_url ) {
			if ( ! isset( $plugins[ $file ] ) ) {
				continue;
			}

			$plugins[ $file ]['Author']     = $developer;
			$plugins[ $file ]['AuthorName'] = $developer;
			$plugins[ $file ]['AuthorURI']  = $developer_url;
			$plugins[ $file ]['PluginURI']  = $plugin_url;
		}

		return $plugins;
	}

	/**
	 * @param array<string,mixed> $definition Plugin definition.
	 */
	public static function register( string $slug, array $definition ): void {
		$slug = sanitize_key( $slug );
		if ( '' === $slug ) {
			return;
		}

		self::$plugins[ $slug ] = array_merge(
			array(
				'name'                  => $slug,
				'file'                  => '',
				'min_platform_version'  => '2.0.0',
				'current_version'       => '',
				'required_plugins'      => array(),
				'optional_integrations' => array(),
				'health_callback'       => null,
			),
			$definition
		);
	}

	/** @return array<string,array<string,mixed>> */
	public static function all(): array {
		return apply_filters( 'algq_platform_registry', self::$plugins );
	}

	/** @return array<string,array<string,mixed>> */
	public static function status(): array {
		$active = (array) get_option( 'active_plugins', array() );
		$result = array();

		foreach ( self::all() as $slug => $plugin ) {
			$file      = (string) ( $plugin['file'] ?? '' );
			$full_path = $file ? WP_PLUGIN_DIR . '/' . $file : '';
			$installed = $full_path && file_exists( $full_path );
			$is_active = $file && in_array( $file, $active, true );
			$version   = $installed ? self::plugin_version( $full_path ) : '';
			$minimum   = (string) ( $plugin['min_platform_version'] ?? '2.0.0' );

			$result[ $slug ] = array_merge(
				$plugin,
				array(
					'slug'       => $slug,
					'installed'  => (bool) $installed,
					'active'     => (bool) $is_active,
					'version'    => $version,
					'compatible' => version_compare( ALGQ_PLATFORM_VERSION, $minimum, '>=' ),
				)
			);
		}

		return $result;
	}

	private static function plugin_version( string $file ): string {
		if ( ! function_exists( 'get_plugin_data' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		$data = get_plugin_data( $file, false, false );
		return sanitize_text_field( (string) ( $data['Version'] ?? '' ) );
	}

	/** @return array<string,array<string,mixed>> */
	private static function defaults(): array {
		return array(
			'algq-deal-intake'        => self::definition( 'Algonquian Deal Intake', 'algq-deal-intake/algq-deal-intake.php' ),
			'algq-pipeline-crm'       => self::definition( 'Algonquian Pipeline CRM', 'algq-pipeline-crm/algq-pipeline-crm.php' ),
			'algq-mao-engine'         => self::definition( 'Algonquian MAO Engine', 'algq-mao-engine/algq-mao-engine.php' ),
			'algq-offer-generator'    => self::definition( 'Algonquian Offer Generator', 'algq-offer-generator/algq-offer-generator.php' ),
			'algq-document-library'   => self::definition( 'Algonquian Document Library', 'algq-document-library/algq-document-library.php' ),
			'algq-pdf-signature'      => self::definition( 'Algonquian PDF & Signature Engine', 'algq-pdf-signature/algq-pdf-signature.php' ),
			'algq-automation-engine'  => self::definition( 'Algonquian Automation Engine', 'algq-automation-engine/algq-automation-engine.php' ),
			'algq-command-center'     => self::definition( 'Algonquian Admin Command Center', 'algq-command-center/algq-command-center.php' ),
			'algq-buyer-portal'       => self::definition( 'Algonquian Buyer Portal', 'algq-buyer-portal/algq-buyer-portal.php' ),
			'algq-funding-tracker'    => self::definition( 'Algonquian Funding Tracker', 'algq-funding-tracker/algq-funding-tracker.php' ),
			'algq-deal-marketplace'   => self::definition( 'Algonquian Deal Marketplace', 'algq-deal-marketplace/algq-deal-marketplace.php' ),
			'algq-digital-store'      => self::definition( 'Algonquian Digital Store', 'algq-digital-store/algq-digital-store.php' ),
			'algq-woocommerce-bridge' => self::definition( 'Algonquian WooCommerce Bridge', 'algq-woocommerce-bridge/algq-woocommerce-bridge.php' ),
		);
	}

	/** @return array<string,mixed> */
	private static function definition( string $name, string $file ): array {
		return array(
			'name'                 => $name,
			'file'                 => $file,
			'min_platform_version' => '2.0.0',
			'required_plugins'     => array(),
		);
	}
}

if ( ! function_exists( 'algq_register_plugin' ) ) {
	/**
	 * @param array<string,mixed> $definition Plugin definition.
	 */
	function algq_register_plugin( string $slug, array $definition ): void {
		ALGQ_Platform_Registry::register( $slug, $definition );
	}
}
