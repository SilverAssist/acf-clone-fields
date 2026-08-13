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
 * @version 1.3.0
 * @author Silver Assist
 */

namespace SilverAssist\ACFCloneFields\Core;

use SilverAssist\ACFCloneFields\Admin\Loader as AdminLoader;
use SilverAssist\ACFCloneFields\Services\Loader as ServicesLoader;
use SilverAssist\PluginKernel\AbstractPlugin;

defined( 'ABSPATH' ) || exit;

/**
 * Class Plugin
 *
 * Singleton access (instance()) and the priority-ordered component loading
 * loop are inherited from AbstractPlugin (silverassist/wp-plugin-kernel) —
 * this class only declares which components to load (get_components()) and
 * the plugin-specific setup that runs alongside them (init_hooks()).
 */
class Plugin extends AbstractPlugin {
	/**
	 * Plugin settings
	 *
	 * @var array<string, mixed>
	 */
	private array $settings = [];

	/**
	 * GitHub Updater instance
	 *
	 * @var \SilverAssist\WpGithubUpdater\Updater|null
	 */
	private ?\SilverAssist\WpGithubUpdater\Updater $updater = null;

	/**
	 * Private constructor to prevent direct instantiation
	 */
	protected function __construct() {
		parent::__construct();

		// Initialize settings.
		$this->settings = get_option( 'silver_acf_clone_settings', [] );
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
	 * List the component classes this plugin loads
	 *
	 * Both are sub-loaders (each a small LoadableInterface implementer in
	 * their own right) that in turn manually require/init their own
	 * component files — kept as-is rather than flattened into a single
	 * list, to avoid changing the load-order/gating semantics of the
	 * individual Admin/Services classes as part of this migration.
	 *
	 * @return array<class-string>
	 */
	protected function get_components(): array {
		return [
			ServicesLoader::class,
			AdminLoader::class,
		];
	}

	/**
	 * Plugin-level setup that isn't itself a LoadableInterface component
	 *
	 * Runs after all components have loaded.
	 *
	 * @return void
	 */
	protected function init_hooks(): void {
		$this->init_github_updater();

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
		add_filter( 'plugin_action_links_' . SILVER_ACF_CLONE_BASENAME, [ $this, 'add_action_links' ] );

		$this->load_textdomain();
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

			$this->updater = new \SilverAssist\WpGithubUpdater\Updater( $config );
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
			'<a href="' . admin_url( 'admin.php?page=acf-clone-fields' ) . '">' .
			__( 'Settings', 'silver-assist-acf-clone-fields' ) . '</a>',
		];

		return array_merge( $plugin_links, $links );
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
	 * Get GitHub Updater instance
	 *
	 * @return \SilverAssist\WpGithubUpdater\Updater|null
	 */
	public function get_updater(): ?\SilverAssist\WpGithubUpdater\Updater {
		return $this->updater;
	}
}
