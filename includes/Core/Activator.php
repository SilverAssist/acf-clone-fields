<?php
/**
 * Plugin Activator
 *
 * Handles plugin activation and deactivation tasks including database setup,
 * option initialization, and cleanup operations.
 *
 * @package SilverAssist\ACFCloneFields
 * @subpackage Core
 * @since 1.0.0
 * @version 1.0.0
 * @author Silver Assist
 */

namespace SilverAssist\ACFCloneFields\Core;

defined( 'ABSPATH' ) || exit;

/**
 * Class Activator
 *
 * Manages plugin lifecycle events including activation, deactivation,
 * and uninstall procedures.
 */
class Activator {
	/**
	 * Plugin activation handler
	 *
	 * Performs necessary tasks when the plugin is activated:
	 * - Creates database tables if needed
	 * - Sets default options
	 * - Checks system requirements
	 * - Initializes plugin data
	 *
	 * @return void
	 */
	public static function activate(): void {
		// Verify minimum requirements.
		self::check_requirements();

		// Set plugin version.
		update_option( 'silver_acf_clone_version', SILVER_ACF_CLONE_VERSION );

		// Initialize default settings.
		self::init_default_settings();

		// Set activation flag for first-time setup.
		update_option( 'silver_acf_clone_activated', time() );

		// Clear any cached data.
		wp_cache_flush();
	}

	/**
	 * Plugin deactivation handler
	 *
	 * Performs cleanup tasks when the plugin is deactivated:
	 * - Removes temporary data
	 * - Clears caches
	 * - Unregisters scheduled events
	 *
	 * @return void
	 */
	public static function deactivate(): void {
		// Clear any scheduled cron events.
		wp_clear_scheduled_hook( 'silver_acf_clone_cleanup' );

		// Clear cached data.
		wp_cache_flush();

		// Set deactivation timestamp.
		update_option( 'silver_acf_clone_deactivated', time() );
	}

	/**
	 * Plugin uninstall handler
	 *
	 * Completely removes plugin data when uninstalled (if configured to do so).
	 * This method should only be called from the uninstall.php file.
	 *
	 * @return void
	 */
	public static function uninstall(): void {
		// Check if user wants to keep data.
		$keep_data = get_option( 'silver_acf_clone_keep_data_on_uninstall', false );

		if ( ! $keep_data ) {
			// Remove plugin options.
			delete_option( 'silver_acf_clone_version' );
			delete_option( 'silver_acf_clone_settings' );
			delete_option( 'silver_acf_clone_activated' );
			delete_option( 'silver_acf_clone_deactivated' );
			delete_option( 'silver_acf_clone_keep_data_on_uninstall' );

			// Clear any cached data.
			wp_cache_flush();
		}
	}

	/**
	 * Check plugin requirements
	 *
	 * Verifies that the system meets minimum requirements for the plugin.
	 *
	 * @throws \Exception If requirements are not met.
	 * @return void
	 */
	private static function check_requirements(): void {
		// Check PHP version.
		// @phpstan-ignore-next-line if.alwaysFalse
		if ( version_compare( PHP_VERSION, '8.2', '<' ) ) {
			if ( defined( 'SILVER_ACF_CLONE_BASENAME' ) ) {
				\deactivate_plugins( (string) SILVER_ACF_CLONE_BASENAME );
			}

			/* translators: %s: required PHP version */
			throw new \Exception(
				sprintf(
				/* translators: %s: required PHP version */
					esc_html__( 'Silver Assist - ACF Clone Fields requires PHP %s or higher.', 'silver-assist-acf-clone-fields' ),
					'8.2'
				)
			);
		}

		// Check WordPress version.
		global $wp_version;
		if ( version_compare( $wp_version, '5.0', '<' ) ) {
			if ( defined( 'SILVER_ACF_CLONE_BASENAME' ) ) {
				\deactivate_plugins( (string) SILVER_ACF_CLONE_BASENAME );
			}

			/* translators: %s: required WordPress version */
			throw new \Exception(
				sprintf(
					/* translators: %s: required WordPress version */
					esc_html__( 'Silver Assist - ACF Clone Fields requires WordPress %s or higher.', 'silver-assist-acf-clone-fields' ),
					'5.0'
				)
			);
		}

		// Check ACF availability.
		if ( ! \function_exists( 'acf_add_local_field_group' ) || ! \class_exists( 'acf' ) ) {
			if ( defined( 'SILVER_ACF_CLONE_BASENAME' ) ) {
				\deactivate_plugins( (string) SILVER_ACF_CLONE_BASENAME );
			}
			throw new \Exception(
				// phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- Exception messages don't require escaping.
				__( 'Silver Assist - ACF Clone Fields requires Advanced Custom Fields Pro to be active.', 'silver-assist-acf-clone-fields' )
			);
		}
	}

	/**
	 * Initialize default plugin settings
	 *
	 * Sets up default configuration options for the plugin.
	 *
	 * @return void
	 */
	private static function init_default_settings(): void {
		$default_settings = [
			'enabled_post_types'   => [ 'post', 'page' ],
			'show_in_sidebar'      => true,
			'clone_button_text'    => __( 'Clone Custom Fields', 'silver-assist-acf-clone-fields' ),
			'confirmation_message' => __( 'This will overwrite existing custom fields. Continue?', 'silver-assist-acf-clone-fields' ),
			'success_message'      => __( 'Custom fields cloned successfully!', 'silver-assist-acf-clone-fields' ),
			'logging_enabled'      => false,
			'cache_enabled'        => true,
		];

		// Only set if no settings exist.
		if ( ! get_option( 'silver_acf_clone_settings' ) ) {
			update_option( 'silver_acf_clone_settings', $default_settings );
		}
	}
}
