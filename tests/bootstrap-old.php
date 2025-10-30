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

// WordPress environment constants.
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

// WordPress test environment check.
$_tests_dir = getenv( 'WP_TESTS_DIR' );
if ( ! $_tests_dir ) {
	$_tests_dir = rtrim( sys_get_temp_dir(), '/\\' ) . '/wordpress-tests-lib';
}

// WordPress mocks for testing
if ( ! function_exists( 'wp_upload_dir' ) ) {
	function wp_upload_dir() {
		return [
			'basedir' => '/tmp/uploads',
			'baseurl' => 'http://example.com/wp-content/uploads',
		];
	}
}

if ( ! function_exists( 'add_action' ) ) {
	function add_action( $hook, $callback, $priority = 10, $accepted_args = 1 ) {
		// Mock implementation
	}
}

if ( ! function_exists( 'add_meta_box' ) ) {
	function add_meta_box( $id, $title, $callback, $screen = null, $context = 'advanced', $priority = 'default', $callback_args = null ) {
		// Mock implementation
	}
}

if ( ! function_exists( 'wp_nonce_field' ) ) {
	function wp_nonce_field( $action = -1, $name = '_wpnonce', $referer = true, $echo = true ) {
		return '<input type="hidden" id="' . $name . '" name="' . $name . '" value="mock-nonce" />';
	}
}

if ( ! function_exists( 'wp_send_json_success' ) ) {
	function wp_send_json_success( $data = null ) {
		echo json_encode( [ 'success' => true, 'data' => $data ] );
		exit;
	}
}

if ( ! function_exists( 'wp_send_json_error' ) ) {
	function wp_send_json_error( $data = null ) {
		echo json_encode( [ 'success' => false, 'data' => $data ] );
		exit;
	}
}

if ( ! function_exists( 'register_activation_hook' ) ) {
	function register_activation_hook( $file, $function ) {
		// Mock implementation
	}
}

if ( ! function_exists( 'register_deactivation_hook' ) ) {
	function register_deactivation_hook( $file, $function ) {
		// Mock implementation
	}
}

if ( ! function_exists( 'plugin_dir_path' ) ) {
	function plugin_dir_path( $file ) {
		return dirname( $file ) . '/';
	}
}

if ( ! function_exists( 'plugin_dir_url' ) ) {
	function plugin_dir_url( $file ) {
		return 'http://example.com/wp-content/plugins/' . basename( dirname( $file ) ) . '/';
	}
}

if ( ! function_exists( 'get_option' ) ) {
	function get_option( $option, $default = false ) {
		return $default;
	}
}

if ( ! function_exists( 'update_option' ) ) {
	function update_option( $option, $value, $autoload = null ) {
		return true;
	}
}

if ( ! function_exists( 'wp_die' ) ) {
	function wp_die( $message = '', $title = '', $args = [] ) {
		exit( $message );
	}
}

if ( ! function_exists( 'is_wp_error' ) ) {
	function is_wp_error( $thing ) {
		return false;
	}
}

if ( ! function_exists( '__' ) ) {
	function __( $text, $domain = 'default' ) {
		return $text;
	}
}

if ( ! function_exists( '_e' ) ) {
	function _e( $text, $domain = 'default' ) {
		echo $text;
	}
}

if ( ! function_exists( 'esc_html' ) ) {
	function esc_html( $text ) {
		return htmlspecialchars( $text, ENT_QUOTES, 'UTF-8' );
	}
}

if ( ! function_exists( 'esc_attr' ) ) {
	function esc_attr( $text ) {
		return htmlspecialchars( $text, ENT_QUOTES, 'UTF-8' );
	}
}

if ( ! function_exists( 'get_post_type' ) ) {
	function get_post_type( $post = null ) {
		return 'post';
	}
}

if ( ! function_exists( 'wp_cache_delete' ) ) {
	function wp_cache_delete( $key, $group = '' ) {
		return true;
	}
}

if ( ! function_exists( 'wp_cache_set' ) ) {
	function wp_cache_set( $key, $data, $group = '', $expire = 0 ) {
		return true;
	}
}

if ( ! function_exists( 'wp_cache_get' ) ) {
	function wp_cache_get( $key, $group = '' ) {
		return false;
	}
}

if ( ! function_exists( 'wp_get_current_user' ) ) {
	function wp_get_current_user() {
		return new stdClass();
	}
}

if ( ! function_exists( 'current_user_can' ) ) {
	function current_user_can( $capability ) {
		return true;
	}
}

if ( ! function_exists( 'is_admin' ) ) {
	function is_admin() {
		return true;
	}
}

if ( ! function_exists( 'wp_enqueue_script' ) ) {
	function wp_enqueue_script( $handle, $src = '', $deps = [], $ver = false, $in_footer = false ) {
		// Mock implementation
	}
}

if ( ! function_exists( 'wp_enqueue_style' ) ) {
	function wp_enqueue_style( $handle, $src = '', $deps = [], $ver = false, $media = 'all' ) {
		// Mock implementation
	}
}

if ( ! function_exists( 'wp_mkdir_p' ) ) {
	function wp_mkdir_p( $target ) {
		return mkdir( $target, 0755, true );
	}
}

if ( ! function_exists( 'wp_json_encode' ) ) {
	function wp_json_encode( $data, $options = 0, $depth = 512 ) {
		return json_encode( $data, $options, $depth );
	}
}

// Mock ACF functions for testing without ACF.
if ( ! function_exists( 'acf_add_local_field_group' ) ) {
	function acf_add_local_field_group( $field_group ) {
		return true;
	}
}

if ( ! class_exists( 'acf' ) ) {
	class acf {
		public static function get_field( $selector, $post_id = false, $format_value = true ) {
			return null;
		}
	}
}

if ( ! function_exists( 'get_field' ) ) {
	function get_field( $selector, $post_id = false, $format_value = true ) {
		return null;
	}
}

// Try to load WordPress test environment if available, otherwise mock essential functions.
if ( file_exists( $_tests_dir . '/includes/functions.php' ) ) {
	require_once $_tests_dir . '/includes/functions.php';
} else {
	// Mock essential WordPress functions for unit testing.
	require_once __DIR__ . '/Utils/WordPressMocks.php';
}

/**
 * Manually load the plugin being tested.
 */
function _manually_load_plugin() {
	// Load composer autoloader.
	if ( file_exists( SILVER_ACF_CLONE_PLUGIN_DIR . '/vendor/autoload.php' ) ) {
		require_once SILVER_ACF_CLONE_PLUGIN_DIR . '/vendor/autoload.php';
	}
	
	// Load main plugin file.
	require SILVER_ACF_CLONE_PLUGIN_FILE;
}

// Load WordPress test environment if available.
if ( file_exists( $_tests_dir . '/includes/bootstrap.php' ) ) {
	// WordPress test functions.
	if ( function_exists( 'tests_add_filter' ) ) {
		tests_add_filter( 'muplugins_loaded', '_manually_load_plugin' );
	}
	
	require $_tests_dir . '/includes/bootstrap.php';
} else {
	// Load plugin directly for unit testing without WordPress.
	_manually_load_plugin();
}

// Load test utilities.
require_once __DIR__ . '/Utils/TestCase.php';
if ( file_exists( __DIR__ . '/Utils/ACFTestHelpers.php' ) ) {
	require_once __DIR__ . '/Utils/ACFTestHelpers.php';
}