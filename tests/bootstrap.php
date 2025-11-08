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
$wp_tests_available = file_exists( $_tests_dir . '/includes/functions.php' );

/**
 * Manually load the plugin being tested.
 */
function _manually_load_plugin() {
	// Load composer autoloader.
	if ( file_exists( SILVER_ACF_CLONE_PLUGIN_DIR . '/vendor/autoload.php' ) ) {
		require_once SILVER_ACF_CLONE_PLUGIN_DIR . '/vendor/autoload.php';
	}
	
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
	
	// Load main plugin file.
	require SILVER_ACF_CLONE_PLUGIN_FILE;
}

// Load WordPress test environment if available.
if ( $wp_tests_available ) {
	// Load WordPress test functions.
	require_once $_tests_dir . '/includes/functions.php';
	
	// Hook plugin loading.
	tests_add_filter( 'muplugins_loaded', '_manually_load_plugin' );
	
	// Load WordPress test bootstrap.
	require $_tests_dir . '/includes/bootstrap.php';
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
	
	// Load WordPress mocks.
	require_once __DIR__ . '/Utils/WordPressMocks.php';
	
	// Load plugin directly.
	_manually_load_plugin();
}

// Load test utilities.
require_once __DIR__ . '/Utils/TestCase.php';
if ( file_exists( __DIR__ . '/Utils/ACFTestHelpers.php' ) ) {
	require_once __DIR__ . '/Utils/ACFTestHelpers.php';
}
