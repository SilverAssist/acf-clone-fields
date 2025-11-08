<?php
/**
 * PHPUnit Bootstrap for Silver Assist ACF Clone Fields
 *
 * @package SilverAssist\ACFCloneFields
 * @author SilverAssist Development Team
 * @license PolyForm-Noncommercial-1.0.0
 * @since 1.0.0
 */

// Composer autoloader for stubs and dependencies.
require_once dirname( __DIR__ ) . '/vendor/autoload.php';

// Define test constants.
if ( ! defined( 'SILVER_ACF_CLONE_TESTING' ) ) {
	define( 'SILVER_ACF_CLONE_TESTING', true );
}
define( 'SILVER_ACF_CLONE_TESTS_DIR', __DIR__ );
define( 'SILVER_ACF_CLONE_PLUGIN_DIR', dirname( __DIR__ ) );
define( 'SILVER_ACF_CLONE_PLUGIN_FILE', SILVER_ACF_CLONE_PLUGIN_DIR . '/silver-assist-acf-clone-fields.php' );

// WordPress test environment check.
$_tests_dir = getenv( 'WP_TESTS_DIR' );
if ( ! $_tests_dir ) {
	$_tests_dir = rtrim( sys_get_temp_dir(), '/\\' ) . '/wordpress-tests-lib';
}

// Check if WordPress test suite is available.
// Support both wordpress-tests-lib (standard) and wordpress-develop (full repo) structures.
$wp_tests_available = false;
$_tests_includes_dir = null;

if ( file_exists( $_tests_dir . '/includes/functions.php' ) ) {
	// Standard wordpress-tests-lib structure.
	$wp_tests_available = true;
	$_tests_includes_dir = $_tests_dir . '/includes';
} elseif ( file_exists( $_tests_dir . '/tests/phpunit/includes/functions.php' ) ) {
	// wordpress-develop repository structure.
	$wp_tests_available = true;
	$_tests_includes_dir = $_tests_dir . '/tests/phpunit/includes';
}

/**
 * Manually load the plugin being tested.
 */
function _manually_load_plugin() {
	// Load composer autoloader.
	if ( file_exists( SILVER_ACF_CLONE_PLUGIN_DIR . '/vendor/autoload.php' ) ) {
		require_once SILVER_ACF_CLONE_PLUGIN_DIR . '/vendor/autoload.php';
	}
	
	// Only load ACF if WordPress is available (prevents errors in mock environment).
	if ( function_exists( 'plugin_dir_path' ) ) {
		// Load ACF plugin if available.
		$acf_plugin_paths = [
			'/tmp/wordpress-tests/wp-content/plugins/advanced-custom-fields/acf.php',
			getenv( 'WP_TESTS_DIR' ) ? dirname( getenv( 'WP_TESTS_DIR' ) ) . '/wordpress/wp-content/plugins/advanced-custom-fields/acf.php' : '',
			WP_CONTENT_DIR . '/plugins/advanced-custom-fields/acf.php',
		];
		
		foreach ( $acf_plugin_paths as $acf_path ) {
			if ( $acf_path && file_exists( $acf_path ) ) {
				require_once $acf_path;
				break;
			}
		}
	}
	
	// Note: Do NOT load main plugin file here.
	// The main plugin file uses register_activation_hook() which requires fully initialized WordPress.
	// For unit tests, we only need the autoloader - classes are instantiated directly in tests.
}

// Load WordPress test environment if available.
if ( $wp_tests_available ) {
	// Load WordPress test functions.
	require_once $_tests_includes_dir . '/functions.php';
	
	// Hook plugin loading.
	tests_add_filter( 'muplugins_loaded', '_manually_load_plugin' );
	
	// Load WordPress test bootstrap.
	require $_tests_includes_dir . '/bootstrap.php';
} else {
	// Mock WordPress environment for unit tests without WordPress.
	if ( ! defined( 'ABSPATH' ) ) {
		define( 'ABSPATH', '/tmp/wordpress/' );
	}

	if ( ! defined( 'WP_CONTENT_DIR' ) ) {
		define( 'WP_CONTENT_DIR', '/tmp/wordpress/wp-content' );
	}

	if ( ! defined( 'WP_DEBUG' ) ) {
		define( 'WP_DEBUG', true );
	}

	if ( ! defined( 'WP_DEBUG_LOG' ) ) {
		define( 'WP_DEBUG_LOG', false );
	}

	if ( ! defined( 'WP_DEBUG_DISPLAY' ) ) {
		define( 'WP_DEBUG_DISPLAY', false );
	}
	
	// Load WordPress mocks if available (fallback for local development).
	if ( file_exists( __DIR__ . '/Utils/WordPressMocks.php' ) ) {
		require_once __DIR__ . '/Utils/WordPressMocks.php';
	}
	
	// Load plugin directly.
	_manually_load_plugin();
}

// Load test utilities.
require_once __DIR__ . '/Utils/TestCase.php';
if ( file_exists( __DIR__ . '/Utils/ACFTestHelpers.php' ) ) {
	require_once __DIR__ . '/Utils/ACFTestHelpers.php';
}
