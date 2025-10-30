<?php
/**
 * Plugin Name: ACF Clone Fields
 * Plugin URI: https://github.com/SilverAssist/acf-clone-fields
 * Description: Advanced ACF field cloning system that allows selective copying of custom fields between posts of the same type. Features granular field selection, sidebar interface, and intelligent repeater field cloning.
 * Version: 1.0.0
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
 * Update Server: https://github.com/SilverAssist/acf-clone-fields
 * GitHub Plugin URI: SilverAssist/acf-clone-fields
 *
 * Silver Assist ACF Clone Fields
 * Copyright (c) 2025 Silver Assist Development Team
 *
 * This plugin is licensed under the PolyForm Noncommercial License 1.0.0.
 * You may not use this plugin for commercial purposes without a separate commercial license.
 * For commercial licensing, contact: licensing@silverassist.com
 *
 * @package SilverAssist\ACFCloneFields
 * @author Silver Assist Development Team
 * @license PolyForm-Noncommercial-1.0.0
 * @since 1.0.0
 * @version 1.0.0
 */

// Prevent direct access.
defined( 'ABSPATH' ) || exit;

// Define plugin constants.
define( 'SILVER_ACF_CLONE_VERSION', '1.0.0' );
define( 'SILVER_ACF_CLONE_FILE', __FILE__ );
define( 'SILVER_ACF_CLONE_PATH', plugin_dir_path( __FILE__ ) );
define( 'SILVER_ACF_CLONE_URL', plugin_dir_url( __FILE__ ) );
define( 'SILVER_ACF_CLONE_BASENAME', plugin_basename( __FILE__ ) );
define( 'SILVER_ACF_CLONE_SLUG', 'silver-assist-acf-clone-fields' );
define( 'SILVER_ACF_CLONE_TEXT_DOMAIN', 'silver-assist-acf-clone-fields' );

/**
 * Composer autoloader
 */
if ( file_exists( SILVER_ACF_CLONE_PATH . 'vendor/autoload.php' ) ) {
	require_once SILVER_ACF_CLONE_PATH . 'vendor/autoload.php';
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
 * @return bool
 */
function silver_acf_clone_check_dependencies(): bool {
	$missing_plugins = [];

	// Check if ACF Pro is active.
	if ( ! function_exists( 'acf_add_local_field_group' ) || ! class_exists( 'acf' ) ) {
		$missing_plugins[] = 'Advanced Custom Fields Pro';
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

// Global flag to prevent multiple initialization.
$GLOBALS['silver_acf_clone_initialized'] = $GLOBALS['silver_acf_clone_initialized'] ?? false;

// Initialize plugin after WordPress loads.
add_action(
	'plugins_loaded',
	function () {
		// Prevent multiple initialization.
		static $already_initialized = false;

		if ( $already_initialized ) {
			return;
		}

		$result = silver_acf_clone_init();

		if ( $result ) {
			$already_initialized = true;
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
