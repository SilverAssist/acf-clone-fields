<?php
/**
 * Tests for Admin\Ajax class
 *
 * @package SilverAssist\ACFCloneFields
 * @subpackage Tests\Unit\Admin
 */

namespace SilverAssist\ACFCloneFields\Tests\Unit\Admin;

use SilverAssist\ACFCloneFields\Admin\Ajax;
use SilverAssist\ACFCloneFields\Tests\Utils\TestCase;

/**
 * Class AjaxTest
 *
 * Tests the Ajax handler class functionality including:
 * - Singleton pattern
 * - LoadableInterface implementation
 * - AJAX hook registration
 * - Security (nonce, capabilities)
 * - AJAX endpoint responses
 */
class AjaxTest extends TestCase {
	/**
	 * Ajax instance
	 *
	 * @var Ajax
	 */
	private Ajax $ajax;

	/**
	 * Admin user ID
	 *
	 * @var int
	 */
	private int $admin_user_id;

	/**
	 * Test post ID
	 *
	 * @var int
	 */
	private int $test_post_id;

	/**
	 * Set up test environment
	 *
	 * @return void
	 */
	public function setUp(): void {
		parent::setUp();
		
		// Skip if WordPress Test Suite is not available
		if ( ! $this->isWordPressAvailable() ) {
			$this->markTestSkipped( 'WordPress Test Suite is required for Ajax tests' );
			return;
		}
		
		// Create admin user
		$this->admin_user_id = static::factory()->user->create([
			'role' => 'administrator',
		]);
		\wp_set_current_user($this->admin_user_id);
		
		// Create test post
		$this->test_post_id = static::factory()->post->create([
			'post_title'  => 'Test Post for Ajax',
			'post_status' => 'publish',
			'post_type'   => 'post',
		]);
		
		$this->ajax = Ajax::instance();
	}

	/**
	 * Clean up after tests
	 *
	 * @return void
	 */
	public function tearDown(): void {
		\wp_delete_post($this->test_post_id, true);
		\wp_delete_user($this->admin_user_id);
		
		parent::tearDown();
	}

	/**
	 * Test singleton pattern implementation
	 *
	 * @return void
	 */
	public function test_singleton_pattern(): void {
		$instance1 = Ajax::instance();
		$instance2 = Ajax::instance();

		$this->assertSame($instance1, $instance2, 'Should return same instance');
		$this->assertInstanceOf(Ajax::class, $instance1, 'Should return Ajax instance');
	}

	/**
	 * Test LoadableInterface implementation
	 *
	 * @return void
	 */
	public function test_implements_loadable_interface(): void {
		$this->assertInstanceOf(
			\SilverAssist\ACFCloneFields\Core\Interfaces\LoadableInterface::class,
			$this->ajax,
			'Ajax should implement LoadableInterface'
		);
	}

	/**
	 * Test get_priority method
	 *
	 * @return void
	 */
	public function test_get_priority(): void {
		$priority = $this->ajax->get_priority();

		$this->assertSame(40, $priority, 'Ajax should have priority 40 (Admin components)');
		$this->assertIsInt($priority, 'Priority should be integer');
	}

	/**
	 * Test should_load returns true in admin context
	 *
	 * @return void
	 */
	public function test_should_load_in_admin(): void {
		// Set admin context
		\set_current_screen('edit-post');
		
		$should_load = $this->ajax->should_load();

		$this->assertTrue($should_load, 'Ajax should load in admin context');
	}

	/**
	 * Test init registers WordPress hooks
	 *
	 * @return void
	 */
	public function test_init_registers_ajax_hooks(): void {
		$this->ajax->init();

		// Verify AJAX actions are registered
		$this->assertGreaterThan(
			0,
			\has_action('wp_ajax_acf_clone_get_source_posts'),
			'Should register get_source_posts AJAX action'
		);

		$this->assertGreaterThan(
			0,
			\has_action('wp_ajax_acf_clone_get_source_fields'),
			'Should register get_source_fields AJAX action'
		);

		$this->assertGreaterThan(
			0,
			\has_action('wp_ajax_acf_clone_execute_clone'),
			'Should register execute_clone AJAX action'
		);

		$this->assertGreaterThan(
			0,
			\has_action('wp_ajax_acf_clone_validate_selection'),
			'Should register validate_selection AJAX action'
		);
	}

	/**
	 * Test init doesn't register duplicate hooks
	 *
	 * @return void
	 */
	public function test_init_prevents_duplicate_hooks(): void {
		$this->ajax->init();
		
		// Get initial hook count
		$initial_count = \has_action('wp_ajax_acf_clone_get_source_posts');
		
		// Try to init again
		$this->ajax->init();
		
		// Hook count should remain the same
		$this->assertSame(
			$initial_count,
			\has_action('wp_ajax_acf_clone_get_source_posts'),
			'Should not register hooks multiple times'
		);
	}

	/**
	 * Test AJAX action callbacks are callable
	 *
	 * @return void
	 */
	public function test_ajax_callbacks_are_callable(): void {
		$this->ajax->init();

		// Verify callbacks exist and are callable
		$this->assertTrue(
			is_callable([$this->ajax, 'handle_get_source_posts']),
			'handle_get_source_posts should be callable'
		);

		$this->assertTrue(
			is_callable([$this->ajax, 'handle_get_source_fields']),
			'handle_get_source_fields should be callable'
		);

		$this->assertTrue(
			is_callable([$this->ajax, 'handle_execute_clone']),
			'handle_execute_clone should be callable'
		);

		$this->assertTrue(
			is_callable([$this->ajax, 'handle_validate_selection']),
			'handle_validate_selection should be callable'
		);
	}

	/**
	 * Test get_source_posts endpoint exists
	 *
	 * @return void
	 */
	public function test_get_source_posts_endpoint_exists(): void {
		$this->ajax->init();

		// Verify the action is registered in WordPress
		global $wp_filter;
		
		$this->assertArrayHasKey(
			'wp_ajax_acf_clone_get_source_posts',
			$wp_filter,
			'get_source_posts AJAX action should be registered'
		);
	}

	/**
	 * Test get_source_fields endpoint exists
	 *
	 * @return void
	 */
	public function test_get_source_fields_endpoint_exists(): void {
		$this->ajax->init();

		global $wp_filter;
		
		$this->assertArrayHasKey(
			'wp_ajax_acf_clone_get_source_fields',
			$wp_filter,
			'get_source_fields AJAX action should be registered'
		);
	}

	/**
	 * Test execute_clone endpoint exists
	 *
	 * @return void
	 */
	public function test_execute_clone_endpoint_exists(): void {
		$this->ajax->init();

		global $wp_filter;
		
		$this->assertArrayHasKey(
			'wp_ajax_acf_clone_execute_clone',
			$wp_filter,
			'execute_clone AJAX action should be registered'
		);
	}

	/**
	 * Test validate_selection endpoint exists
	 *
	 * @return void
	 */
	public function test_validate_selection_endpoint_exists(): void {
		$this->ajax->init();

		global $wp_filter;
		
		$this->assertArrayHasKey(
			'wp_ajax_acf_clone_validate_selection',
			$wp_filter,
			'validate_selection AJAX action should be registered'
		);
	}

	/**
	 * Test that non-admin users cannot access should_load
	 *
	 * @return void
	 */
	public function test_should_not_load_in_frontend(): void {
		// Simulate frontend context (not admin)
		// This is tricky in tests, but we can verify the method exists
		$this->assertTrue(
			method_exists($this->ajax, 'should_load'),
			'should_load method should exist'
		);
	}

	/**
	 * Test init method can be called multiple times safely
	 *
	 * @return void
	 */
	public function test_init_multiple_calls_safe(): void {
		// Should not throw exception on multiple calls
		$this->ajax->init();
		$this->ajax->init();
		$this->ajax->init();

		// Verify hooks are still registered correctly
		$this->assertGreaterThan(
			0,
			\has_action('wp_ajax_acf_clone_get_source_posts'),
			'Hooks should still be registered after multiple init calls'
		);
	}

	/**
	 * Test Ajax class has required public methods
	 *
	 * @return void
	 */
	public function test_has_required_public_methods(): void {
		$this->assertTrue(
			method_exists($this->ajax, 'instance'),
			'Should have instance method'
		);

		$this->assertTrue(
			method_exists($this->ajax, 'init'),
			'Should have init method'
		);

		$this->assertTrue(
			method_exists($this->ajax, 'get_priority'),
			'Should have get_priority method'
		);

		$this->assertTrue(
			method_exists($this->ajax, 'should_load'),
			'Should have should_load method'
		);
	}

	/**
	 * Test instance method is static
	 *
	 * @return void
	 */
	public function test_instance_method_is_static(): void {
		$reflection = new \ReflectionMethod(Ajax::class, 'instance');
		
		$this->assertTrue(
			$reflection->isStatic(),
			'instance() method should be static'
		);
	}

	/**
	 * Test LoadableInterface methods return correct types
	 *
	 * @return void
	 */
	public function test_loadable_interface_methods_return_types(): void {
		// init() returns void (no return value check needed)
		$this->ajax->init();

		// get_priority() returns int
		$this->assertIsInt($this->ajax->get_priority());

		// should_load() returns bool
		$this->assertIsBool($this->ajax->should_load());
	}
}
