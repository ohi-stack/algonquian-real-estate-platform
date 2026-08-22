<?php
/**
 * Plugin Name: Algonquian Mega Menu
 * Plugin URI: https://algonquianrealestate.com
 * Description: Responsive, accessible mega navigation for Algonquian Real Estate public services, acquisition systems, investor resources, documentation, and protected platform operations.
 * Version: 1.0.0
 * Author: Onegodian | Algonquian Real Estate
 * Text Domain: algq-mega-menu
 * Requires at least: 6.0
 * Requires PHP: 7.4
 *
 * @package AlgonquianMegaMenu
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'ALGQ_MEGA_MENU_VERSION', '1.0.0' );
define( 'ALGQ_MEGA_MENU_FILE', __FILE__ );
define( 'ALGQ_MEGA_MENU_DIR', plugin_dir_path( __FILE__ ) );
define( 'ALGQ_MEGA_MENU_URL', plugin_dir_url( __FILE__ ) );

final class ALGQ_Mega_Menu {
	/**
	 * Initialize hooks.
	 */
	public static function init() {
		add_action( 'init', array( __CLASS__, 'register_shortcode' ) );
		add_action( 'after_setup_theme', array( __CLASS__, 'register_menu_location' ) );
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue_assets' ) );
	}

	/**
	 * Register the optional theme menu location.
	 */
	public static function register_menu_location() {
		register_nav_menus(
			array(
				'algq_mega_menu' => __( 'Algonquian Mega Menu', 'algq-mega-menu' ),
			)
		);
	}

	/**
	 * Register the shortcode.
	 */
	public static function register_shortcode() {
		add_shortcode( 'algq_mega_menu', array( __CLASS__, 'shortcode' ) );
	}

	/**
	 * Enqueue module assets.
	 */
	public static function enqueue_assets() {
		$css = ALGQ_MEGA_MENU_DIR . 'assets/algq-mega-menu.css';
		$js  = ALGQ_MEGA_MENU_DIR . 'assets/algq-mega-menu.js';

		if ( file_exists( $css ) ) {
			wp_enqueue_style(
				'algq-mega-menu',
				ALGQ_MEGA_MENU_URL . 'assets/algq-mega-menu.css',
				array(),
				filemtime( $css )
			);
		}

		if ( file_exists( $js ) ) {
			wp_enqueue_script(
				'algq-mega-menu',
				ALGQ_MEGA_MENU_URL . 'assets/algq-mega-menu.js',
				array(),
				filemtime( $js ),
				true
			);
		}
	}

	/**
	 * Shortcode callback.
	 *
	 * @param array<string,mixed> $atts Shortcode attributes.
	 * @return string
	 */
	public static function shortcode( $atts = array() ) {
		$atts = shortcode_atts(
			array(
				'label' => __( 'Explore Algonquian Real Estate', 'algq-mega-menu' ),
			),
			$atts,
			'algq_mega_menu'
		);

		return self::render( (string) $atts['label'] );
	}

	/**
	 * Public template function output.
	 *
	 * @param string $label Toggle label.
	 * @return string
	 */
	public static function render( $label = '' ) {
		$menu_id = wp_unique_id( 'algq-mega-menu-' );
		$label   = $label ? $label : __( 'Explore Algonquian Real Estate', 'algq-mega-menu' );

		ob_start();
		?>
		<nav class="algq-mega" aria-label="<?php echo esc_attr__( 'Algonquian Real Estate', 'algq-mega-menu' ); ?>">
			<div class="algq-mega__bar">
				<a class="algq-mega__brand" href="<?php echo esc_url( home_url( '/' ) ); ?>">
					<span class="algq-mega__brand-mark" aria-hidden="true">ARE</span>
					<span class="algq-mega__brand-copy">
						<strong><?php echo esc_html__( 'Algonquian Real Estate', 'algq-mega-menu' ); ?></strong>
						<small><?php echo esc_html__( 'Connecticut Real Estate & Technology', 'algq-mega-menu' ); ?></small>
					</span>
				</a>

				<button class="algq-mega__toggle" type="button" aria-expanded="false" aria-controls="<?php echo esc_attr( $menu_id ); ?>">
					<span><?php echo esc_html( $label ); ?></span>
					<span class="algq-mega__toggle-icon" aria-hidden="true"></span>
				</button>
			</div>

			<div id="<?php echo esc_attr( $menu_id ); ?>" class="algq-mega__panel" hidden>
				<div class="algq-mega__grid">
					<?php echo self::column( __( 'Property Owners', 'algq-mega-menu' ), self::owner_links() ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					<?php echo self::column( __( 'Investors & Buyers', 'algq-mega-menu' ), self::investor_links() ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					<?php echo self::column( __( 'Deals & Acquisitions', 'algq-mega-menu' ), self::acquisition_links() ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					<?php echo self::column( __( 'Technology Platform', 'algq-mega-menu' ), self::platform_links() ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				</div>

				<div class="algq-mega__footer">
					<div>
						<strong><?php echo esc_html__( 'Helping Connecticut property owners find the right path forward.', 'algq-mega-menu' ); ?></strong>
						<span><?php echo esc_html__( 'Explore options, submit a property, review opportunities, or access the ARE platform.', 'algq-mega-menu' ); ?></span>
					</div>
					<div class="algq-mega__actions">
						<a class="algq-mega__button algq-mega__button--secondary" href="<?php echo esc_url( home_url( '/contact/' ) ); ?>"><?php echo esc_html__( 'Contact ARE', 'algq-mega-menu' ); ?></a>
						<a class="algq-mega__button" href="<?php echo esc_url( home_url( '/sell-your-property/' ) ); ?>"><?php echo esc_html__( 'Submit a Property', 'algq-mega-menu' ); ?></a>
					</div>
				</div>
			</div>
		</nav>
		<?php
		return ob_get_clean();
	}

	/**
	 * Build one menu column.
	 *
	 * @param string                            $title Column title.
	 * @param array<int,array<string,string>>   $links Links.
	 * @return string
	 */
	private static function column( $title, $links ) {
		ob_start();
		?>
		<section class="algq-mega__column">
			<h2><?php echo esc_html( $title ); ?></h2>
			<ul>
				<?php foreach ( $links as $link ) : ?>
					<li>
						<a href="<?php echo esc_url( home_url( $link['url'] ) ); ?>">
							<span class="algq-mega__link-title"><?php echo esc_html( $link['title'] ); ?></span>
							<span class="algq-mega__link-copy"><?php echo esc_html( $link['copy'] ); ?></span>
						</a>
					</li>
				<?php endforeach; ?>
			</ul>
		</section>
		<?php
		return ob_get_clean();
	}

	/** @return array<int,array<string,string>> */
	private static function owner_links() {
		return array(
			array( 'title' => __( 'Explore Your Options', 'algq-mega-menu' ), 'copy' => __( 'Compare selling, holding, stewardship, and transition paths.', 'algq-mega-menu' ), 'url' => '/what-are-my-options/' ),
			array( 'title' => __( 'Sell Your Property', 'algq-mega-menu' ), 'copy' => __( 'Submit property and owner information for confidential review.', 'algq-mega-menu' ), 'url' => '/sell-your-property/' ),
			array( 'title' => __( 'Property Stewardship', 'algq-mega-menu' ), 'copy' => __( 'Local property check-ins, coordination, and transition support.', 'algq-mega-menu' ), 'url' => '/property-stewardship-services/' ),
			array( 'title' => __( 'Inherited Property Guidance', 'algq-mega-menu' ), 'copy' => __( 'Review keep, rent, repair, or sale alternatives.', 'algq-mega-menu' ), 'url' => '/inherited-property-guidance/' ),
			array( 'title' => __( 'Senior Property Assistance', 'algq-mega-menu' ), 'copy' => __( 'Property-focused help for aging, downsizing, or planning ahead.', 'algq-mega-menu' ), 'url' => '/senior-property-assistance/' ),
		);
	}

	/** @return array<int,array<string,string>> */
	private static function investor_links() {
		return array(
			array( 'title' => __( 'Investor Network', 'algq-mega-menu' ), 'copy' => __( 'Connect with ARE for Connecticut opportunities and partnerships.', 'algq-mega-menu' ), 'url' => '/investors/' ),
			array( 'title' => __( 'Buyer Registration', 'algq-mega-menu' ), 'copy' => __( 'Create a buyer profile and provide acquisition criteria.', 'algq-mega-menu' ), 'url' => '/buyers-register/' ),
			array( 'title' => __( 'Buyer Portal', 'algq-mega-menu' ), 'copy' => __( 'Access approved deal packages and controlled downloads.', 'algq-mega-menu' ), 'url' => '/buyer-dashboard/' ),
			array( 'title' => __( 'Funding & Capital', 'algq-mega-menu' ), 'copy' => __( 'Review lender, private-capital, and joint-venture pathways.', 'algq-mega-menu' ), 'url' => '/funding/' ),
			array( 'title' => __( 'Development Concepts', 'algq-mega-menu' ), 'copy' => __( 'Explore conceptual redevelopment and adaptive-use opportunities.', 'algq-mega-menu' ), 'url' => '/development-concepts/' ),
		);
	}

	/** @return array<int,array<string,string>> */
	private static function acquisition_links() {
		$links = array(
			array( 'title' => __( 'Acquisition Criteria', 'algq-mega-menu' ), 'copy' => __( 'Review ARE target markets, property types, and transaction profiles.', 'algq-mega-menu' ), 'url' => '/acquisition-criteria/' ),
			array( 'title' => __( 'Underwriting', 'algq-mega-menu' ), 'copy' => __( 'Learn how opportunities are screened and analyzed.', 'algq-mega-menu' ), 'url' => '/underwriting/' ),
			array( 'title' => __( 'Deal Marketplace', 'algq-mega-menu' ), 'copy' => __( 'Review controlled investment opportunities when available.', 'algq-mega-menu' ), 'url' => '/deal-marketplace/' ),
			array( 'title' => __( 'Documents & Due Diligence', 'algq-mega-menu' ), 'copy' => __( 'Access institutional document categories and review workflows.', 'algq-mega-menu' ), 'url' => '/documents/' ),
		);

		if ( is_user_logged_in() && current_user_can( 'view_algq_deals' ) ) {
			$links[] = array( 'title' => __( 'Internal Deal Pipeline', 'algq-mega-menu' ), 'copy' => __( 'Open the protected canonical deal lifecycle workspace.', 'algq-mega-menu' ), 'url' => '/pipeline/' );
		}

		return $links;
	}

	/** @return array<int,array<string,string>> */
	private static function platform_links() {
		$links = array(
			array( 'title' => __( 'Plugin Library', 'algq-mega-menu' ), 'copy' => __( 'Explore the Algonquian Real Estate plugin suite.', 'algq-mega-menu' ), 'url' => '/plugins/' ),
			array( 'title' => __( 'Getting Started', 'algq-mega-menu' ), 'copy' => __( 'Install, configure, and operate the platform modules.', 'algq-mega-menu' ), 'url' => '/plugin/platform-plugin/how-to-use/' ),
			array( 'title' => __( 'Documentation', 'algq-mega-menu' ), 'copy' => __( 'Open user, administrator, security, and technical guides.', 'algq-mega-menu' ), 'url' => '/documentation/' ),
			array( 'title' => __( 'Digital Store', 'algq-mega-menu' ), 'copy' => __( 'Browse templates, forms, calculators, and workflow products.', 'algq-mega-menu' ), 'url' => '/digital-store/' ),
		);

		if ( is_user_logged_in() && current_user_can( 'manage_algq_platform' ) ) {
			$links[] = array( 'title' => __( 'Admin Command Center', 'algq-mega-menu' ), 'copy' => __( 'Open platform health, reporting, alerts, and administrative commands.', 'algq-mega-menu' ), 'url' => '/dashboard/' );
		}

		return $links;
	}
}

ALGQ_Mega_Menu::init();

/**
 * Render the mega menu from a theme template.
 *
 * @param string $label Optional mobile toggle label.
 */
function algq_render_mega_menu( $label = '' ) {
	echo ALGQ_Mega_Menu::render( $label ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
}
