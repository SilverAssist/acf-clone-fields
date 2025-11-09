<?php
/**
 * Tests for Services\FieldDetector class
 *
 * @package SilverAssist\ACFCloneFields
 * @subpackage Tests\Unit\Services
 */

namespace SilverAssist\ACFCloneFields\Tests\Unit\Services;

use SilverAssist\ACFCloneFields\Services\FieldDetector;
use SilverAssist\ACFCloneFields\Tests\Utils\TestCase;

/**
 * Class FieldDetectorTest
 *
 * Tests the FieldDetector service functionality including:
 * - Singleton pattern
 * - LoadableInterface implementation
 * - Field detection operations
 * - Field group discovery
 * - Repeater and flexible content handling
 */
class FieldDetectorTest extends TestCase {
	/**
	 * FieldDetector instance
	 *
	 * @var FieldDetector
	 */
	private FieldDetector $detector;

	/**
	 * Test post ID
	 *
	 * @var int
	 */
	private int $test_post_id;

	/**
	 * Admin user ID for tests
	 *
	 * @var int
	 */
	private int $admin_user_id;

	/**
	 * Set up test environment
	 *
	 * @return void
	 */
	public function setUp(): void {
		parent::setUp();
		
		// Create admin user for capability checks
		$this->admin_user_id = static::factory()->user->create([
			'role' => 'administrator',
		]);
		\wp_set_current_user( $this->admin_user_id );
		
		$this->detector = FieldDetector::instance();
		
		// Create test post
		$this->test_post_id = static::factory()->post->create([
			'post_title'   => 'Test Post for Field Detection',
			'post_content' => 'Test content',
			'post_status'  => 'publish',
		]);
	}

	/**
	 * Clean up after tests
	 *
	 * @return void
	 */
	public function tearDown(): void {
		\wp_delete_post( $this->test_post_id, true );
		\wp_delete_user( $this->admin_user_id );
		
		parent::tearDown();
	}

	/**
	 * Test singleton pattern implementation
	 *
	 * @return void
	 */
	public function test_singleton_pattern(): void {
		$instance1 = FieldDetector::instance();
		$instance2 = FieldDetector::instance();

		$this->assertSame( $instance1, $instance2, 'Should return same instance' );
		$this->assertInstanceOf( FieldDetector::class, $instance1, 'Should return FieldDetector instance' );
	}

	/**
	 * Test LoadableInterface implementation
	 *
	 * @return void
	 */
	public function test_implements_loadable_interface(): void {
		$this->assertInstanceOf(
			\SilverAssist\ACFCloneFields\Core\Interfaces\LoadableInterface::class,
			$this->detector,
			'FieldDetector should implement LoadableInterface'
		);
	}

	/**
	 * Test get_priority method
	 *
	 * @return void
	 */
	public function test_get_priority(): void {
		$priority = $this->detector->get_priority();

		$this->assertSame( 30, $priority, 'FieldDetector should have priority 30 (Services)' );
		$this->assertIsInt( $priority, 'Priority should be integer' );
	}

	/**
	 * Test should_load returns true when ACF functions are available
	 *
	 * @return void
	 */
	public function test_should_load_with_acf_available(): void {
		// Mock ACF functions if not available
		if ( ! function_exists( 'acf_get_field_groups' ) ) {
			eval( 'function acf_get_field_groups() { return []; }' );
		}

		$should_load = $this->detector->should_load();

		$this->assertTrue( $should_load, 'FieldDetector should load when ACF functions are available' );
	}

	/**
	 * Test init method executes without error
	 *
	 * @return void
	 */
	public function test_init_executes(): void {
		// Should not throw exception
		$this->detector->init();

		$this->assertTrue( true, 'init() should execute without errors' );
	}

	/**
	 * Test get_available_fields returns array
	 *
	 * @return void
	 */
	public function test_get_available_fields_returns_array(): void {
		$fields = $this->detector->get_available_fields( $this->test_post_id );

		$this->assertIsArray( $fields, 'Should return array of fields' );
	}

	/**
	 * Test get_available_fields with invalid post ID
	 *
	 * @return void
	 */
	public function test_get_available_fields_with_invalid_post(): void {
		$fields = $this->detector->get_available_fields( 999999 );

		$this->assertIsArray( $fields, 'Should return empty array for invalid post' );
	}

	/**
	 * Test get_field_groups returns array
	 *
	 * @return void
	 */
	public function test_get_field_groups_returns_array(): void {
		$groups = $this->detector->get_field_groups( 'post' );

		$this->assertIsArray( $groups, 'Should return array of field groups' );
	}

	/**
	 * Test get_field_groups with empty post type
	 *
	 * @return void
	 */
	public function test_get_field_groups_with_empty_type(): void {
		$groups = $this->detector->get_field_groups( '' );

		$this->assertIsArray( $groups, 'Should return empty array for empty post type' );
	}

	/**
	 * Test clear_field_cache executes without error
	 *
	 * @return void
	 */
	public function test_clear_field_cache_executes(): void {
		// Should not throw exception
		$this->detector->clear_field_cache( $this->test_post_id );

		$this->assertTrue( true, 'clear_field_cache() should execute without errors' );
	}

	/**
	 * Test clear_field_cache with null post ID
	 *
	 * @return void
	 */
	public function test_clear_field_cache_with_null(): void {
		// Should not throw exception
		$this->detector->clear_field_cache();

		$this->assertTrue( true, 'clear_field_cache(null) should execute without errors' );
	}

	/**
	 * Test get_field_statistics returns array
	 *
	 * @return void
	 */
	public function test_get_field_statistics_returns_array(): void {
		$stats = $this->detector->get_field_statistics( $this->test_post_id );

		$this->assertIsArray( $stats, 'Should return array of statistics' );
	}

	/**
	 * Test get_field_statistics with invalid post ID
	 *
	 * @return void
	 */
	public function test_get_field_statistics_with_invalid_post(): void {
		$stats = $this->detector->get_field_statistics( 999999 );

		$this->assertIsArray( $stats, 'Should return array even for invalid post' );
	}

	/**
	 * Test instance method is static
	 *
	 * @return void
	 */
	public function test_instance_method_is_static(): void {
		$reflection = new \ReflectionMethod( FieldDetector::class, 'instance' );
		
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
		$this->detector->init();

		// get_priority() returns int
		$this->assertIsInt( $this->detector->get_priority() );

		// should_load() returns bool
		$this->assertIsBool( $this->detector->should_load() );
	}
}
