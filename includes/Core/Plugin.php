<?php
/**
 * Main Plugin Class
 *
 * Central controller for the Silver Assist ACF Clone Fields plugin.
 * Manages component loading, initialization, and integration with SilverAssist packages.
 *
 * @package SilverAssist\ACFCloneFields
 * @subpackage Core
 * @since 1.0.0
 * @version 1.0.0
 * @author Silver Assist
 */

namespace SilverAssist\ACFCloneFields\Core;

use SilverAssist\ACFCloneFields\Core\Interfaces\LoadableInterface;

defined( 'ABSPATH' ) || exit;

/**
 * Class Plugin
 *
 * Main plugin controller implementing singleton pattern and LoadableInterface
 * for consistent initialization and component management.
 */
class Plugin implements LoadableInterface {
	/**
	 * Plugin instance
	 *
	 * @var Plugin|null
	 */
	private static ?Plugin $instance = null;

	/**
	 * Loaded components
	 *
	 * @var LoadableInterface[]
	 */
	private array $components = [];

	/**
	 * Plugin settings
	 *
	 * @var array<string, mixed>
	 */
	private array $settings = [];

	/**
	 * Get singleton instance
	 *
	 * @return Plugin
	 */
	public static function instance(): Plugin {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Private constructor to prevent direct instantiation
	 */
	private function __construct() {
		// Initialize settings.
		$this->settings = get_option( 'silver_acf_clone_settings', [] );
	}

	/**
	 * Plugin initialization flag
	 *
	 * @var bool
	 */
	private bool $initialized = false;

	/**
	 * Initialize the plugin
	 *
	 * @return void
	 */
	public function init(): void {
		// Prevent multiple initialization.
		if ( $this->initialized ) {
			return;
		}

		// Initialize GitHub updater integration.
		$this->init_github_updater();

		// Settings Hub integration is handled by Admin\Settings class.

		// Load plugin components.
		$this->load_components();

		// Initialize WordPress hooks.
		$this->init_hooks();

		// Load plugin textdomain.
		$this->load_textdomain();

		// Admin assets are handled by Admin\MetaBox class.
		// No need to initialize them here to prevent duplication.

		// Mark as initialized.
		$this->initialized = true;
	}

	/**
	 * Get loading priority
	 *
	 * @return int
	 */
	public function get_priority(): int {
		return 10; // High priority for core plugin.
	}

	/**
	 * Determine if plugin should load
	 *
	 * @return bool
	 */
	public function should_load(): bool {
		// Check if ACF is available.
		if ( ! function_exists( 'acf_add_local_field_group' ) || ! class_exists( 'acf' ) ) {
			return false;
		}

		// Check minimum WordPress version.
		global $wp_version;
		if ( version_compare( $wp_version, '5.0', '<' ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Load plugin components
	 *
	 * @return void
	 */
	private function load_components(): void {
		// Load component loaders in priority order.
		$loaders = [
			// Services loader (priority 20).
			'SilverAssist\\ACFCloneFields\\Services\\Loader',

			// Admin loader (priority 30).
			'SilverAssist\\ACFCloneFields\\Admin\\Loader',
		];

		foreach ( $loaders as $loader_class ) {
			if ( class_exists( $loader_class ) ) {
				try {
					$loader = $loader_class::instance();
					if ( $loader->should_load() ) {
						$loader->init();
						$this->components[] = $loader;
					}
				} catch ( \Exception $e ) {
					// Log error using proper logger system.
					if ( class_exists( 'SilverAssist\\ACFCloneFields\\Utils\\Logger' ) ) {
						\SilverAssist\ACFCloneFields\Utils\Logger::instance()->error(
							sprintf( 'Failed to load component %s', $loader_class ),
							[ 'exception' => $e->getMessage() ]
						);
					}
				}
			}
		}
	}

	/**
	 * Initialize WordPress hooks
	 *
	 * @return void
	 */
	private function init_hooks(): void {
		// Plugin lifecycle hooks.
		add_action( 'init', [ $this, 'handle_init' ], 20 );
		add_action( 'admin_init', [ $this, 'handle_admin_init' ] );
		add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_frontend_assets' ] );

		// AJAX hooks for non-logged users (if needed).
		add_action(
			'wp_ajax_nopriv_silver_acf_clone_get_posts',
			function () {
				wp_die( 'Forbidden', 'Access Denied', [ 'response' => 403 ] );
			}
		);

		// Add plugin action links.
		// @phpstan-ignore-next-line deadCode.unreachable
		add_filter( 'plugin_action_links_' . SILVER_ACF_CLONE_BASENAME, [ $this, 'add_action_links' ] );

		// Add plugin row meta.
		add_filter( 'plugin_row_meta', [ $this, 'add_row_meta' ], 10, 2 );
	}

	/**
	 * Initialize GitHub updater integration
	 *
	 * @return void
	 */
	private function init_github_updater(): void {
		// Check if SilverAssist GitHub Updater is available.
		if ( class_exists( 'SilverAssist\\WpGithubUpdater\\Updater' ) && class_exists( 'SilverAssist\\WpGithubUpdater\\UpdaterConfig' ) ) {
			$config = new \SilverAssist\WpGithubUpdater\UpdaterConfig(
				SILVER_ACF_CLONE_FILE,
				'SilverAssist/acf-clone-fields',
				[
					'plugin_name'        => 'Silver Assist - ACF Clone Fields',
					'plugin_description' => 'Advanced ACF field cloning with granular selection',
					'plugin_author'      => 'Silver Assist Development Team',
					'plugin_homepage'    => 'https://silverassist.com',
					'requires_wordpress' => '5.0',
					'requires_php'       => '8.2',
					'asset_pattern'      => 'acf-clone-fields-v{version}.zip',
					'ajax_action'        => 'silver_acf_clone_check_version',
					'ajax_nonce'         => 'silver_acf_clone_version_nonce',
					'text_domain'        => 'silver-assist-acf-clone-fields',
				]
			);

			new \SilverAssist\WpGithubUpdater\Updater( $config );
		}
	}



	/**
	 * Handle WordPress init action
	 *
	 * @return void
	 */
	public function handle_init(): void {
		// Register any additional post types or taxonomies if needed.
		do_action( 'silver_acf_clone_init' );
	}

	/**
	 * Handle admin init action
	 *
	 * @return void
	 */
	public function handle_admin_init(): void {
		// Admin-specific initialization.
		do_action( 'silver_acf_clone_admin_init' );
	}

	/**
	 * Load plugin textdomain
	 *
	 * @return void
	 */
	private function load_textdomain(): void {
		load_plugin_textdomain(
			'silver-assist-acf-clone-fields',
			false,
			dirname( (string) SILVER_ACF_CLONE_BASENAME ) . '/languages'
		);
	}





	/**
	 * Enqueue frontend assets (if needed)
	 *
	 * @return void
	 */
	public function enqueue_frontend_assets(): void {
		// Frontend assets not needed for this plugin.
		// Method kept for potential future use.
	}

	/**
	 * Add plugin action links
	 *
	 * @param array<string> $links Existing action links.
	 * @return array<string>
	 */
	public function add_action_links( array $links ): array {
		$plugin_links = [
			'<a href="' . admin_url( 'options-general.php?page=silver-acf-clone-fields' ) . '">' .
			__( 'Settings', 'silver-assist-acf-clone-fields' ) . '</a>',
		];

		return array_merge( $plugin_links, $links );
	}

	/**
	 * Add plugin row meta
	 *
	 * @param array<string> $meta Existing meta.
	 * @param string        $file Plugin file.
	 * @return array<string>
	 */
	public function add_row_meta( array $meta, string $file ): array {
		if ( defined( 'SILVER_ACF_CLONE_BASENAME' ) && (string) SILVER_ACF_CLONE_BASENAME === $file ) {
			$meta[] = '<a href="https://github.com/SilverAssist/acf-clone-fields" target="_blank">' .
						__( 'GitHub Repository', 'silver-assist-acf-clone-fields' ) . '</a>';
			$meta[] = '<a href="https://github.com/SilverAssist/acf-clone-fields/issues" target="_blank">' .
						__( 'Support', 'silver-assist-acf-clone-fields' ) . '</a>';
		}

		return $meta;
	}

	/**
	 * Get plugin settings
	 *
	 * @param string|null $key Optional setting key.
	 * @return mixed
	 */
	public function get_setting( ?string $key = null ) {
		if ( null === $key ) {
			return $this->settings;
		}

		return $this->settings[ $key ] ?? null;
	}

	/**
	 * Render settings page for Settings Hub integration
	 *
	 * @return void
	 */
	public function render_settings_page(): void {
		// Check if Settings class exists and delegate to it.
		if ( class_exists( 'SilverAssist\\ACFCloneFields\\Admin\\Settings' ) ) {
			$settings = \SilverAssist\ACFCloneFields\Admin\Settings::instance();
			$settings->render_settings_page();
			return;
		}

		// Fallback basic settings page.
		?>
		<div class="silverassist-plugin-settings">
			<h2><?php esc_html_e( 'ACF Clone Fields Settings', 'silver-assist-acf-clone-fields' ); ?></h2>
			<p><?php esc_html_e( 'Configure ACF field cloning options and preferences.', 'silver-assist-acf-clone-fields' ); ?></p>
			
			<div class="notice notice-info">
				<p><?php esc_html_e( 'Settings integration is being developed. Check back soon for configuration options.', 'silver-assist-acf-clone-fields' ); ?></p>
			</div>
		</div>
		<?php
	}



	/**
	 * Update plugin settings
	 *
	 * @param array<string, mixed> $settings New settings.
	 * @return bool
	 */
	public function update_settings( array $settings ): bool {
		$this->settings = array_merge( $this->settings, $settings );
		return update_option( 'silver_acf_clone_settings', $this->settings );
	}

	/**
	 * Get loaded components
	 *
	 * @return LoadableInterface[]
	 */
	public function get_components(): array {
		return $this->components;
	}
}
