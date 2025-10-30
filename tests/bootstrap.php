<?php
/**
 * PHPUnit Bootstrap for Silver Assist ACF Clone Fields
 *
 * @package SilverAssist\ACFCloneFields
 * @author SilverAssist Development Team
 * @license PolyForm-Noncommercial-1.0.0
 * @since 1.0.0
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Define test constants.
define( 'SILVER_ACF_CLONE_TESTING', true );
define( 'SILVER_ACF_CLONE_TESTS_DIR', __DIR__ );
define( 'SILVER_ACF_CLONE_PLUGIN_DIR', dirname( __DIR__ ) );
define( 'SILVER_ACF_CLONE_PLUGIN_FILE', SILVER_ACF_CLONE_PLUGIN_DIR . '/silver-assist-acf-clone-fields.php' );

// WordPress test environment.
$_tests_dir = getenv( 'WP_TESTS_DIR' );
if ( ! $_tests_dir ) {
	$_tests_dir = rtrim( sys_get_temp_dir(), '/\\' ) . '/wordpress-tests-lib';
}

// WordPress test bootstrap.
require_once $_tests_dir . '/includes/functions.php';

/**
 * Manually load the plugin being tested.
 */
function _manually_load_plugin() {
	require SILVER_ACF_CLONE_PLUGIN_FILE;
}

tests_add_filter( 'muplugins_loaded', '_manually_load_plugin' );

/**
 * Load ACF plugin for testing if available
 */
function _maybe_load_acf_plugin() {
	$acf_plugin_paths = [
		WP_PLUGIN_DIR . '/advanced-custom-fields-pro/acf.php',
		WP_PLUGIN_DIR . '/advanced-custom-fields/acf.php',
	];
	
	foreach ( $acf_plugin_paths as $acf_path ) {
		if ( file_exists( $acf_path ) ) {
			require_once $acf_path;
			break;
		}
	}
}

tests_add_filter( 'muplugins_loaded', '_maybe_load_acf_plugin', 9 );

// Load WordPress test environment.
require $_tests_dir . '/includes/bootstrap.php';

// Load test utilities.
require_once __DIR__ . '/Utils/TestCase.php';
require_once __DIR__ . '/Utils/ACFTestHelpers.php';