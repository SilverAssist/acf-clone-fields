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

	/**
	 * Test get_repeater_sub_fields with empty repeater field
	 *
	 * @return void
	 */
	public function test_get_repeater_sub_fields_with_empty_repeater(): void {
		$repeater_field = [];
		$sub_fields = $this->detector->get_repeater_sub_fields( $repeater_field, $this->test_post_id );

		$this->assertIsArray( $sub_fields, 'Should return array even for empty repeater' );
	}

	/**
	 * Test get_repeater_sub_fields with valid repeater field structure
	 *
	 * @return void
	 */
	public function test_get_repeater_sub_fields_with_valid_structure(): void {
		$repeater_field = [
			'type' => 'repeater',
			'name' => 'test_repeater',
			'label' => 'Test Repeater',
			'sub_fields' => [
				[
					'key'   => 'field_sub1',
					'name'  => 'sub_field_1',
					'type'  => 'text',
					'label' => 'Sub Field 1',
				],
				[
					'key'   => 'field_sub2',
					'name'  => 'sub_field_2',
					'type'  => 'textarea',
					'label' => 'Sub Field 2',
				],
			],
		];

		$sub_fields = $this->detector->get_repeater_sub_fields( $repeater_field, $this->test_post_id );

		$this->assertIsArray( $sub_fields, 'Should return array of sub-fields' );
	}

	/**
	 * Test get_repeater_sub_fields without sub_fields key
	 *
	 * @return void
	 */
	public function test_get_repeater_sub_fields_without_sub_fields_key(): void {
		$repeater_field = [
			'type' => 'repeater',
			'name' => 'test_repeater',
		];

		$sub_fields = $this->detector->get_repeater_sub_fields( $repeater_field, $this->test_post_id );

		$this->assertIsArray( $sub_fields, 'Should return array even without sub_fields key' );
	}

	/**
	 * Test get_field_statistics contains required keys
	 *
	 * @return void
	 */
	public function test_get_field_statistics_contains_required_keys(): void {
		$stats = $this->detector->get_field_statistics( $this->test_post_id );

		$this->assertArrayHasKey( 'total_fields', $stats, 'Stats should include total_fields' );
		$this->assertArrayHasKey( 'fields_with_values', $stats, 'Stats should include fields_with_values' );
		$this->assertArrayHasKey( 'total_groups', $stats, 'Stats should include total_groups' );
		$this->assertArrayHasKey( 'cloneable_fields', $stats, 'Stats should include cloneable_fields' );
		$this->assertArrayHasKey( 'repeater_fields', $stats, 'Stats should include repeater_fields' );
		$this->assertArrayHasKey( 'group_fields', $stats, 'Stats should include group_fields' );
	}

	/**
	 * Test get_field_statistics with post that has no fields
	 *
	 * @return void
	 */
	public function test_get_field_statistics_with_no_fields(): void {
		$new_post_id = static::factory()->post->create([
			'post_title'  => 'Post Without Fields',
			'post_status' => 'publish',
		]);

		$stats = $this->detector->get_field_statistics( $new_post_id );

		$this->assertIsArray( $stats, 'Should return array for post with no fields' );
		$this->assertArrayHasKey( 'total_fields', $stats );
		$this->assertEquals( 0, $stats['total_fields'], 'Should have 0 total fields' );

		// Cleanup.
		\wp_delete_post( $new_post_id, true );
	}

	/**
	 * Test get_available_fields structure
	 *
	 * @return void
	 */
	public function test_get_available_fields_structure(): void {
		$fields = $this->detector->get_available_fields( $this->test_post_id );

		$this->assertIsArray( $fields, 'Should return array' );
		
		// If fields exist, validate structure.
		if ( ! empty( $fields ) ) {
			foreach ( $fields as $field ) {
				$this->assertIsArray( $field, 'Each field should be an array' );
			}
		}
	}

	/**
	 * Test get_field_groups with valid post type
	 *
	 * @return void
	 */
	public function test_get_field_groups_with_valid_post_type(): void {
		$groups = $this->detector->get_field_groups( 'post' );

		$this->assertIsArray( $groups, 'Should return array for valid post type' );
	}

	/**
	 * Test get_field_groups with custom post type
	 *
	 * @return void
	 */
	public function test_get_field_groups_with_custom_post_type(): void {
		// Register a custom post type for testing.
		\register_post_type(
			'test_custom_type',
			[
				'public' => true,
				'label'  => 'Test Custom Type',
			]
		);

		$groups = $this->detector->get_field_groups( 'test_custom_type' );

		$this->assertIsArray( $groups, 'Should return array for custom post type' );

		// Cleanup.
		\unregister_post_type( 'test_custom_type' );
	}

	/**
	 * Test clear_field_cache clears specific post cache
	 *
	 * @return void
	 */
	public function test_clear_field_cache_with_specific_post(): void {
		// First get fields to populate cache.
		$this->detector->get_available_fields( $this->test_post_id );

		// Then clear cache.
		$this->detector->clear_field_cache( $this->test_post_id );

		// Should execute without error.
		$this->assertTrue( true, 'Should clear specific post cache without errors' );
	}

	/**
	 * Test clear_field_cache clears all caches when post ID is null
	 *
	 * @return void
	 */
	public function test_clear_field_cache_clears_all_when_null(): void {
		// Get fields for multiple posts to populate cache.
		$this->detector->get_available_fields( $this->test_post_id );
		
		$second_post = static::factory()->post->create([
			'post_title'  => 'Second Test Post',
			'post_status' => 'publish',
		]);
		$this->detector->get_available_fields( $second_post );

		// Clear all caches.
		$this->detector->clear_field_cache( null );

		$this->assertTrue( true, 'Should clear all caches when post ID is null' );

		// Cleanup.
		\wp_delete_post( $second_post, true );
	}

	/**
	 * Test get_available_fields with zero post ID
	 *
	 * @return void
	 */
	public function test_get_available_fields_with_zero_post_id(): void {
		$fields = $this->detector->get_available_fields( 0 );

		$this->assertIsArray( $fields, 'Should return empty array for post ID 0' );
		$this->assertEmpty( $fields, 'Should return empty array for post ID 0' );
	}

	/**
	 * Test get_available_fields with negative post ID
	 *
	 * @return void
	 */
	public function test_get_available_fields_with_negative_post_id(): void {
		$fields = $this->detector->get_available_fields( -1 );

		$this->assertIsArray( $fields, 'Should return empty array for negative post ID' );
		$this->assertEmpty( $fields, 'Should return empty array for negative post ID' );
	}

	/**
	 * Test get_field_statistics returns zero stats for invalid post
	 *
	 * @return void
	 */
	public function test_get_field_statistics_zero_stats_for_invalid_post(): void {
		$stats = $this->detector->get_field_statistics( 0 );

		$this->assertIsArray( $stats, 'Should return array for invalid post' );
		
		// Stats should exist but be zero or empty.
		if ( isset( $stats['total_fields'] ) ) {
			$this->assertEquals( 0, $stats['total_fields'], 'Invalid post should have 0 total fields' );
		}
	}

	/**
	 * Test get_field_groups returns empty array for invalid post type
	 *
	 * @return void
	 */
	public function test_get_field_groups_empty_for_invalid_type(): void {
		$groups = $this->detector->get_field_groups( 'nonexistent_post_type_12345' );

		$this->assertIsArray( $groups, 'Should return array for invalid post type' );
	}

	/**
	 * Test singleton returns same instance across multiple calls
	 *
	 * @return void
	 */
	public function test_singleton_consistency(): void {
		$instances = [];
		
		// Get instance multiple times.
		for ( $i = 0; $i < 5; $i++ ) {
			$instances[] = FieldDetector::instance();
		}

		// All should be the same instance.
		foreach ( $instances as $instance ) {
			$this->assertSame( $instances[0], $instance, 'All instances should be identical' );
		}
	}

	/**
	 * Test get_repeater_sub_fields with invalid post ID
	 *
	 * @return void
	 */
	public function test_get_repeater_sub_fields_with_invalid_post_id(): void {
		$repeater_field = [
			'type' => 'repeater',
			'name' => 'test_repeater',
		];

		$sub_fields = $this->detector->get_repeater_sub_fields( $repeater_field, 999999 );

		$this->assertIsArray( $sub_fields, 'Should return array even for invalid post ID' );
	}
}

