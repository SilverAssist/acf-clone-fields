<?php
/**
 * Tests for Core\Plugin class
 *
 * @package SilverAssist\ACFCloneFields
 * @subpackage Tests\Unit\Core
 * @since 1.1.1
 */

namespace SilverAssist\ACFCloneFields\Tests\Unit\Core;

use SilverAssist\ACFCloneFields\Core\Plugin;
use SilverAssist\ACFCloneFields\Tests\Utils\TestCase;

/**
 * Class PluginTest
 *
 * Tests the main Plugin class functionality including:
 * - Singleton pattern
 * - Component loading
 * - WordPress hooks registration
 * - Settings management
 * - LoadableInterface implementation
 */
class PluginTest extends TestCase {
	/**
	 * Plugin instance
	 *
	 * @var Plugin
	 */
	private Plugin $plugin;

	/**
	 * Set up test environment
	 *
	 * @return void
	 */
	public function setUp(): void {
		parent::setUp();
		
		// Define plugin constants if not already defined
		// Use real plugin file path for GitHub Updater compatibility
		$plugin_file = dirname( dirname( dirname( __DIR__ ) ) ) . '/silver-assist-acf-clone-fields.php';
		
		if ( ! defined( 'SILVER_ACF_CLONE_FILE' ) ) {
			define( 'SILVER_ACF_CLONE_FILE', $plugin_file );
		}
		if ( ! defined( 'SILVER_ACF_CLONE_PATH' ) ) {
			define( 'SILVER_ACF_CLONE_PATH', dirname( $plugin_file ) . '/' );
		}
		if ( ! defined( 'SILVER_ACF_CLONE_BASENAME' ) ) {
			define( 'SILVER_ACF_CLONE_BASENAME', 'silver-assist-acf-clone-fields/silver-assist-acf-clone-fields.php' );
		}
		if ( ! defined( 'SILVER_ACF_CLONE_VERSION' ) ) {
			define( 'SILVER_ACF_CLONE_VERSION', '1.1.1' );
		}
		
		// Get fresh plugin instance by resetting AbstractPlugin's per-class singleton registry.
		self::reset_plugin_singleton();

		$this->plugin = Plugin::instance();
	}

	/**
	 * Reset the AbstractPlugin-managed singleton for Plugin::class
	 *
	 * AbstractPlugin keys its singleton registry by static::class rather than
	 * declaring a per-subclass $instance property, so resetting it requires
	 * reflecting on the parent class instead of Plugin itself.
	 *
	 * @return void
	 */
	private static function reset_plugin_singleton(): void {
		$reflection = new \ReflectionClass( \SilverAssist\PluginKernel\AbstractPlugin::class );
		$instances_property = $reflection->getProperty( 'instances' );
		$instances_property->setAccessible( true );

		$instances = $instances_property->getValue();
		unset( $instances[ Plugin::class ] );
		$instances_property->setValue( null, $instances );
	}

	/**
	 * Clean up after tests
	 *
	 * @return void
	 */
	public function tearDown(): void {
		// Clean up plugin settings
		\delete_option( 'silver_acf_clone_settings' );
		
		parent::tearDown();
	}

	/**
	 * Test singleton pattern implementation
	 *
	 * @return void
	 */
	public function test_singleton_pattern(): void {
		$instance1 = Plugin::instance();
		$instance2 = Plugin::instance();

		$this->assertSame( $instance1, $instance2, 'Plugin should return same instance' );
		$this->assertInstanceOf( Plugin::class, $instance1, 'Should return Plugin instance' );
	}

	/**
	 * Test LoadableInterface implementation
	 *
	 * @return void
	 */
	public function test_implements_loadable_interface(): void {
		$this->assertInstanceOf(
			\SilverAssist\PluginKernel\Interfaces\LoadableInterface::class,
			$this->plugin,
			'Plugin should implement LoadableInterface'
		);
	}

	/**
	 * Test get_priority method
	 *
	 * @return void
	 */
	public function test_get_priority(): void {
		$priority = $this->plugin->get_priority();

		$this->assertSame( 10, $priority, 'Plugin should have priority 10 (Core)' );
		$this->assertIsInt( $priority, 'Priority should be integer' );
	}

	/**
	 * Test should_load returns true when ACF is available
	 *
	 * @return void
	 */
	public function test_should_load_with_acf_available(): void {
		// Mock ACF functions if not available in test environment
		if ( ! function_exists( 'acf_add_local_field_group' ) ) {
			eval( 'function acf_add_local_field_group() {}' );
		}
		if ( ! class_exists( 'acf' ) ) {
			eval( 'class acf {}' );
		}

		$should_load = $this->plugin->should_load();

		$this->assertTrue( $should_load, 'Plugin should load when ACF is available' );
	}

	/**
	 * Test WordPress hooks registration
	 *
	 * @return void
	 */
	public function test_init_registers_wordpress_hooks(): void {
		// Initialize plugin
		$this->plugin->init();

		// Check init hooks
		$this->assertGreaterThan(
			0,
			\has_action( 'init', [ $this->plugin, 'handle_init' ] ),
			'Should register init hook'
		);

		$this->assertGreaterThan(
			0,
			\has_action( 'admin_init', [ $this->plugin, 'handle_admin_init' ] ),
			'Should register admin_init hook'
		);

		$this->assertGreaterThan(
			0,
			\has_action( 'wp_enqueue_scripts', [ $this->plugin, 'enqueue_frontend_assets' ] ),
			'Should register wp_enqueue_scripts hook'
		);
	}

	/**
	 * Test plugin initialization only happens once
	 *
	 * @return void
	 */
	public function test_init_prevents_multiple_initialization(): void {
		// Initialize once
		$this->plugin->init();

		// Get initial hook count
		$init_hooks_count = \has_action( 'init', [ $this->plugin, 'handle_init' ] );

		// Try to initialize again
		$this->plugin->init();

		// Verify hooks weren't duplicated
		$this->assertSame(
			$init_hooks_count,
			\has_action( 'init', [ $this->plugin, 'handle_init' ] ),
			'Should not register hooks multiple times'
		);
	}

	/**
	 * Test handle_init triggers custom action
	 *
	 * @return void
	 */
	public function test_handle_init_triggers_custom_action(): void {
		$action_triggered = false;

		\add_action(
			'silver_acf_clone_init',
			function () use ( &$action_triggered ) {
				$action_triggered = true;
			}
		);

		$this->plugin->handle_init();

		$this->assertTrue( $action_triggered, 'Should trigger silver_acf_clone_init action' );
	}

	/**
	 * Test handle_admin_init triggers custom action
	 *
	 * @return void
	 */
	public function test_handle_admin_init_triggers_custom_action(): void {
		$action_triggered = false;

		\add_action(
			'silver_acf_clone_admin_init',
			function () use ( &$action_triggered ) {
				$action_triggered = true;
			}
		);

		$this->plugin->handle_admin_init();

		$this->assertTrue( $action_triggered, 'Should trigger silver_acf_clone_admin_init action' );
	}

	/**
	 * Test get_setting with no key returns all settings
	 *
	 * @return void
	 */
	public function test_get_setting_without_key_returns_all_settings(): void {
		// Set some test settings
		\update_option( 'silver_acf_clone_settings', [ 'test_key' => 'test_value' ] );

		// Create new instance to load settings
		self::reset_plugin_singleton();
		$plugin = Plugin::instance();

		$settings = $plugin->get_setting();

		$this->assertIsArray( $settings, 'Should return array' );
		$this->assertArrayHasKey( 'test_key', $settings, 'Should contain test_key' );
		$this->assertSame( 'test_value', $settings['test_key'], 'Should have correct value' );
	}

	/**
	 * Test get_setting with specific key
	 *
	 * @return void
	 */
	public function test_get_setting_with_key_returns_specific_value(): void {
		// Set test settings
		\update_option( 'silver_acf_clone_settings', [ 'enabled' => true, 'mode' => 'advanced' ] );

		// Create new instance
		self::reset_plugin_singleton();
		$plugin = Plugin::instance();

		$this->assertTrue( $plugin->get_setting( 'enabled' ), 'Should return true for enabled' );
		$this->assertSame( 'advanced', $plugin->get_setting( 'mode' ), 'Should return mode value' );
		$this->assertNull( $plugin->get_setting( 'nonexistent' ), 'Should return null for nonexistent key' );
	}

	/**
	 * Test update_settings updates and persists settings
	 *
	 * @return void
	 */
	public function test_update_settings(): void {
		$new_settings = [
			'enabled'    => true,
			'post_types' => [ 'post', 'page' ],
		];

		$result = $this->plugin->update_settings( $new_settings );

		$this->assertTrue( $result, 'Should return true on successful update' );

		// Verify settings were saved to database
		$saved_settings = \get_option( 'silver_acf_clone_settings' );
		$this->assertArrayHasKey( 'enabled', $saved_settings, 'Should save enabled setting' );
		$this->assertArrayHasKey( 'post_types', $saved_settings, 'Should save post_types setting' );
		$this->assertTrue( $saved_settings['enabled'], 'Should have correct enabled value' );
	}

	/**
	 * Test update_settings merges with existing settings
	 *
	 * @return void
	 */
	public function test_update_settings_merges_with_existing(): void {
		// Set initial settings
		$this->plugin->update_settings( [ 'initial_key' => 'initial_value' ] );

		// Update with new settings
		$this->plugin->update_settings( [ 'new_key' => 'new_value' ] );

		// Verify both keys exist
		$all_settings = $this->plugin->get_setting();
		$this->assertArrayHasKey( 'initial_key', $all_settings, 'Should keep initial key' );
		$this->assertArrayHasKey( 'new_key', $all_settings, 'Should add new key' );
	}

	/**
	 * Test get_components lists the Services and Admin sub-loaders
	 *
	 * get_components() is protected (declared by AbstractPlugin's contract as
	 * a list of class-strings, not loaded instances), so it's invoked via
	 * Reflection here.
	 *
	 * @return void
	 */
	public function test_get_components_returns_array(): void {
		$method = new \ReflectionMethod( Plugin::class, 'get_components' );
		$method->setAccessible( true );
		$components = $method->invoke( $this->plugin );

		$this->assertIsArray( $components, 'Should return array' );
		$this->assertContains( \SilverAssist\ACFCloneFields\Services\Loader::class, $components );
		$this->assertContains( \SilverAssist\ACFCloneFields\Admin\Loader::class, $components );
	}

	/**
	 * Test loaded_components() after initialization
	 *
	 * loaded_components() (also protected, from AbstractPlugin) exposes the
	 * actual should_load()-filtered, init()'d instances — as opposed to
	 * get_components()'s class-string list.
	 *
	 * @return void
	 */
	public function test_get_components_after_init(): void {
		$this->plugin->init();

		$method = new \ReflectionMethod( \SilverAssist\PluginKernel\AbstractPlugin::class, 'loaded_components' );
		$method->setAccessible( true );
		$components = $method->invoke( $this->plugin );

		$this->assertIsArray( $components, 'Should return array after init' );

		// Loaders gate on should_load() (ACF functions / is_admin()), neither
		// guaranteed true in this test environment, so the array may be
		// legitimately empty here — just verify anything that did load
		// implements LoadableInterface.
		foreach ( $components as $component ) {
			$this->assertInstanceOf(
				\SilverAssist\PluginKernel\Interfaces\LoadableInterface::class,
				$component,
				'Each component should implement LoadableInterface'
			);
		}
	}

	/**
	 * Test get_loaded_components() matches the protected loaded_components()
	 *
	 * Regression coverage for the deprecated public accessor that preserves
	 * the pre-1.3.0 public get_components() behavior.
	 *
	 * @return void
	 */
	public function test_get_loaded_components_matches_inherited_loaded_components(): void {
		$this->plugin->init();

		$method = new \ReflectionMethod( \SilverAssist\PluginKernel\AbstractPlugin::class, 'loaded_components' );
		$method->setAccessible( true );
		$expected = $method->invoke( $this->plugin );

		$this->assertSame( $expected, $this->plugin->get_loaded_components() );
	}

	/**
	 * Test add_action_links adds settings link
	 *
	 * @return void
	 */
	public function test_add_action_links(): void {
		$original_links = [ '<a href="#">Deactivate</a>' ];

		$links = $this->plugin->add_action_links( $original_links );

		$this->assertIsArray( $links, 'Should return array' );
		$this->assertGreaterThan( count( $original_links ), count( $links ), 'Should add new links' );
		
		// Check if settings link was added
		$settings_link_found = false;
		foreach ( $links as $link ) {
			if ( strpos( $link, 'Settings' ) !== false || strpos( $link, 'settings' ) !== false ) {
				$settings_link_found = true;
				break;
			}
		}
		
		$this->assertTrue( $settings_link_found, 'Should add settings link' );
	}

	/**
	 * Test get_updater returns updater instance or null
	 *
	 * @return void
	 */
	public function test_get_updater(): void {
		$this->plugin->init();

		$updater = $this->plugin->get_updater();

		// Updater may be null if GitHub Updater package is not available
		$this->assertTrue(
			$updater === null || $updater instanceof \SilverAssist\WpGithubUpdater\Updater,
			'Should return null or Updater instance'
		);
	}

	/**
	 * Test enqueue_frontend_assets doesn't enqueue anything (by design)
	 *
	 * @return void
	 */
	public function test_enqueue_frontend_assets(): void {
		global $wp_scripts, $wp_styles;
		
		// Store initial counts
		$scripts_before = isset( $wp_scripts->registered ) ? count( $wp_scripts->registered ) : 0;
		$styles_before = isset( $wp_styles->registered ) ? count( $wp_styles->registered ) : 0;

		$this->plugin->enqueue_frontend_assets();

		// Verify no scripts or styles were added
		$scripts_after = isset( $wp_scripts->registered ) ? count( $wp_scripts->registered ) : 0;
		$styles_after = isset( $wp_styles->registered ) ? count( $wp_styles->registered ) : 0;

		$this->assertSame( $scripts_before, $scripts_after, 'Should not enqueue frontend scripts' );
		$this->assertSame( $styles_before, $styles_after, 'Should not enqueue frontend styles' );
	}
}
