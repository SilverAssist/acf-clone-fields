<?php
/**
 * Plugin Name: ACF Clone Fields
 * Plugin URI: https://github.com/SilverAssist/acf-clone-fields
 * Description: Clone Advanced Custom Fields between posts with precision. Compatible with ACF free (basic fields) and ACF Pro (repeater, group, flexible content). Automatic backups and smart conflict detection included.
 * Version: 1.1.1
 * Author: Silver Assist
 * Author URI: https://silverassist.com
 * License: PolyForm-Noncommercial-1.0.0
 * License URI: https://polyformproject.org/licenses/noncommercial/1.0.0/
 * Text Domain: silver-assist-acf-clone-fields
 * Domain Path: /languages
 * Requires at least: 5.0
 * Tested up to: 6.8.3
 * Requires PHP: 8.2
 * Network: false
 * Update URI: https://github.com/SilverAssist/acf-clone-fields
 *
 * Silver Assist ACF Clone Fields
 * Copyright (c) 2025 Miguel Colmenares
 *
 * This plugin is licensed under the PolyForm Noncommercial License 1.0.0.
 * You may not use this plugin for commercial purposes without a separate commercial license.
 * For commercial licensing, contact: licensing@silverassist.com
 *
 * @package SilverAssist\ACFCloneFields
 * @author Miguel Colmenares
 * @license PolyForm-Noncommercial-1.0.0
 * @since 1.0.0
 * @version 1.1.1
 */

// Prevent direct access.
defined( 'ABSPATH' ) || exit;

// Define plugin constants.
define( 'SILVER_ACF_CLONE_VERSION', '1.1.1' );
define( 'SILVER_ACF_CLONE_FILE', __FILE__ );
define( 'SILVER_ACF_CLONE_PATH', plugin_dir_path( __FILE__ ) );
define( 'SILVER_ACF_CLONE_BASENAME', plugin_basename( __FILE__ ) );

/**
 * Composer autoloader
 */
$autoload_path = SILVER_ACF_CLONE_PATH . 'vendor/autoload.php';
$real_autoload_path = realpath( $autoload_path );
// Validate: file exists, realpath resolves, path inside plugin directory
if (
	$real_autoload_path &&
	file_exists( $real_autoload_path ) &&
	strpos( $real_autoload_path, realpath( SILVER_ACF_CLONE_PATH ) ) === 0
) {
	require_once $real_autoload_path;
}

/**
 * Load plugin classes
 */
require_once SILVER_ACF_CLONE_PATH . 'includes/Core/Interfaces/LoadableInterface.php';
require_once SILVER_ACF_CLONE_PATH . 'includes/Core/Plugin.php';
require_once SILVER_ACF_CLONE_PATH . 'includes/Core/Activator.php';

// Import main plugin classes.
use SilverAssist\ACFCloneFields\Core\Plugin;
use SilverAssist\ACFCloneFields\Core\Activator;

/**
 * Initialize the plugin
 *
 * @return Plugin|null
 */
function silver_acf_clone_init(): ?Plugin {
	// Verify dependencies.
	if ( ! silver_acf_clone_check_dependencies() ) {
		return null;
	}

	// Initialize main plugin class.
	$plugin = Plugin::instance();
	$plugin->init();

	return $plugin;
}

/**
 * Check plugin dependencies
 *
 * Verifies that either ACF (free) or ACF Pro is installed and active.
 * The plugin is compatible with both versions, with enhanced features for Pro.
 *
 * @return bool True if ACF (free or Pro) is active, false otherwise.
 */
function silver_acf_clone_check_dependencies(): bool {
	$missing_plugins = [];

	// Check if ACF is active (either free or Pro version).
	// Both versions provide the 'acf' class and 'acf_get_field_groups' function.
	$has_acf      = class_exists( 'acf' );
	$has_acf_core = function_exists( 'acf_get_field_groups' );

	if ( ! $has_acf && ! $has_acf_core ) {
		$missing_plugins[] = 'Advanced Custom Fields (free or Pro)';
	}

	// Show admin notice if dependencies are missing.
	if ( ! empty( $missing_plugins ) ) {
		add_action(
			'admin_notices',
			function () use ( $missing_plugins ) {
				$plugin_names = implode( ', ', $missing_plugins );
				$message      = sprintf(
				/* translators: %s: comma-separated list of required plugins */
					__( 'Silver Assist - ACF Clone Fields requires the following plugins to be active: %s', 'silver-assist-acf-clone-fields' ),
					$plugin_names
				);

				printf(
					'<div class="notice notice-error"><p><strong>%s</strong></p></div>',
					esc_html( $message )
				);
			}
		);

		return false;
	}

	return true;
}

/**
 * Check if ACF Pro is active
 *
 * Used to determine if Pro-only fields (repeater, group, flexible_content, clone)
 * should be available for cloning.
 *
 * @return bool True if ACF Pro is active, false if only ACF free.
 */
function silver_acf_clone_is_pro(): bool {
	return defined( 'ACF_PRO' ) && ACF_PRO;
}

/**
 * Plugin activation hook
 */
function silver_acf_clone_activate(): void {
	Activator::activate();
}

/**
 * Plugin deactivation hook
 */
function silver_acf_clone_deactivate(): void {
	Activator::deactivate();
}

// Register activation/deactivation hooks.
register_activation_hook( __FILE__, 'silver_acf_clone_activate' );
register_deactivation_hook( __FILE__, 'silver_acf_clone_deactivate' );

// Initialize plugin after WordPress loads.
add_action(
	'plugins_loaded',
	function () {
		// Prevent multiple initialization.
		if ( ! empty( $GLOBALS['silver_acf_clone_initialized'] ) ) {
			return;
		}

		$result = silver_acf_clone_init();

		if ( $result ) {
			$GLOBALS['silver_acf_clone_initialized'] = true;
		}
	},
	10
);

/**
 * Load text domain for internationalization
 */
add_action(
	'init',
	function () {
		load_plugin_textdomain(
			'silver-assist-acf-clone-fields',
			false,
			dirname( SILVER_ACF_CLONE_BASENAME ) . '/languages'
		);
	}
);
