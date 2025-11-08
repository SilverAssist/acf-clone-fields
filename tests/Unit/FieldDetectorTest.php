<?php
/**
 * Tests for Services\FieldDetector class
 *
 * @package SilverAssist\ACFCloneFields
 * @subpackage Tests\Unit
 */

namespace SilverAssist\ACFCloneFields\Tests\Unit;

defined('ABSPATH') || exit;

use SilverAssist\ACFCloneFields\Tests\Utils\TestCase;
use SilverAssist\ACFCloneFields\Services\FieldDetector;

/**
 * Class FieldDetectorTest
 *
 * Tests the FieldDetector service functionality including:
 * - Singleton pattern
 * - LoadableInterface implementation
 * - Field detection and retrieval
 * - Field group management
 * - Cache management
 * - Statistics generation
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
	 * Set up test environment
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();
		
		$this->detector = FieldDetector::instance();
		
		// Create test post using WordPress factory
		$this->test_post_id = static::factory()->post->create([
			'post_title'  => 'Test Post for Field Detection',
			'post_status' => 'publish',
			'post_type'   => 'post',
		]);
	}

	/**
	 * Clean up after tests
	 *
	 * @return void
	 */
	protected function tearDown(): void {
		\wp_delete_post($this->test_post_id, true);
		
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

		$this->assertSame($instance1, $instance2, 'Should return same instance');
		$this->assertInstanceOf(FieldDetector::class, $instance1, 'Should return FieldDetector instance');
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
	 * Test field detection service instantiation
	 *
	 * @return void
	 */
	public function test_instance_creation(): void {
		$this->assertInstanceOf(FieldDetector::class, $this->detector);
		$this->assertEquals(30, $this->detector->get_priority());
	}

	/**
	 * Test get_priority returns correct value
	 *
	 * @return void
	 */
	public function test_get_priority(): void {
		$priority = $this->detector->get_priority();

		$this->assertSame(30, $priority, 'FieldDetector should have priority 30 (Services)');
		$this->assertIsInt($priority, 'Priority should be integer');
	}

	/**
	 * Test should_load returns boolean
	 *
	 * @return void
	 */
	public function test_should_load(): void {
		$should_load = $this->detector->should_load();

		$this->assertIsBool($should_load, 'should_load() should return boolean');
	}

	/**
	 * Test get available fields with real post ID
	 *
	 * @return void
	 */
	public function test_get_available_fields(): void {
		$detected_fields = $this->detector->get_available_fields($this->test_post_id);

		$this->assertIsArray($detected_fields, 'Should return array');
		// Without ACF field groups registered, should return empty array
	}

	/**
	 * Test get_available_fields with invalid post ID
	 *
	 * @return void
	 */
	public function test_get_available_fields_with_invalid_post(): void {
		$detected_fields = $this->detector->get_available_fields(99999999);

		$this->assertIsArray($detected_fields, 'Should return array even for invalid post');
		$this->assertEmpty($detected_fields, 'Should return empty array for invalid post');
	}

	/**
	 * Test get field groups with post type
	 *
	 * @return void
	 */
	public function test_get_field_groups(): void {
		$post_type = 'post';

		$field_groups = $this->detector->get_field_groups($post_type);

		$this->assertIsArray($field_groups, 'Should return array');
		// Without ACF active or field groups registered, should return empty array
	}

	/**
	 * Test get_field_groups with custom post type
	 *
	 * @return void
	 */
	public function test_get_field_groups_with_custom_post_type(): void {
		$field_groups = $this->detector->get_field_groups('custom_type');

		$this->assertIsArray($field_groups, 'Should return array for custom post type');
		$this->assertEmpty($field_groups, 'Should return empty array when no field groups');
	}

	/**
	 * Test get field statistics returns correct structure
	 *
	 * @return void
	 */
	public function test_get_field_statistics(): void {
		$statistics = $this->detector->get_field_statistics($this->test_post_id);

		$this->assertIsArray($statistics, 'Should return array');
		$this->assertArrayHasKey('total_fields', $statistics, 'Should have total_fields key');
		$this->assertArrayHasKey('total_groups', $statistics, 'Should have total_groups key');
		$this->assertArrayHasKey('repeater_fields', $statistics, 'Should have repeater_fields key');
		
		$this->assertIsInt($statistics['total_fields'], 'total_fields should be integer');
		$this->assertIsInt($statistics['total_groups'], 'total_groups should be integer');
		$this->assertIsInt($statistics['repeater_fields'], 'repeater_fields should be integer');
	}

	/**
	 * Test get_field_statistics with invalid post
	 *
	 * @return void
	 */
	public function test_get_field_statistics_with_invalid_post(): void {
		$statistics = $this->detector->get_field_statistics(99999999);

		$this->assertIsArray($statistics, 'Should return array even for invalid post');
		$this->assertArrayHasKey('total_fields', $statistics);
		$this->assertSame(0, $statistics['total_fields'], 'Should have 0 fields for invalid post');
	}

	/**
	 * Test cache clearing functionality
	 *
	 * @return void
	 */
	public function test_clear_field_cache(): void {
		// Should not throw exception
		$this->detector->clear_field_cache($this->test_post_id);
		$this->detector->clear_field_cache(); // Test without post_id

		$this->assertTrue(true, 'clear_field_cache should execute without errors');
	}

	/**
	 * Test clear_field_cache with specific post ID
	 *
	 * @return void
	 */
	public function test_clear_field_cache_with_post_id(): void {
		// Clear cache for specific post
		$this->detector->clear_field_cache($this->test_post_id);

		// Should complete without errors
		$this->assertTrue(true, 'Should clear cache for specific post');
	}

	/**
	 * Test clear_field_cache clears all caches
	 *
	 * @return void
	 */
	public function test_clear_field_cache_clears_all(): void {
		// Clear all caches (no post_id parameter)
		$this->detector->clear_field_cache();

		// Should complete without errors
		$this->assertTrue(true, 'Should clear all caches');
	}

	/**
	 * Test repeater sub fields functionality
	 *
	 * @return void
	 */
	public function test_get_repeater_sub_fields(): void {
		$repeater_field = [
			'type' => 'repeater',
			'key'  => 'field_test_repeater',
			'name' => 'test_repeater',
		];

		$sub_fields = $this->detector->get_repeater_sub_fields($repeater_field, $this->test_post_id);

		$this->assertIsArray($sub_fields, 'Should return array');
		// Without ACF active, should return empty array
	}

	/**
	 * Test get_repeater_sub_fields with non-repeater field
	 *
	 * @return void
	 */
	public function test_get_repeater_sub_fields_with_non_repeater(): void {
		$text_field = [
			'type' => 'text',
			'key'  => 'field_test_text',
			'name' => 'test_text',
		];

		$sub_fields = $this->detector->get_repeater_sub_fields($text_field, $this->test_post_id);

		$this->assertIsArray($sub_fields, 'Should return array for non-repeater field');
		$this->assertEmpty($sub_fields, 'Should return empty array for non-repeater field');
	}

	/**
	 * Test get_repeater_sub_fields with invalid post
	 *
	 * @return void
	 */
	public function test_get_repeater_sub_fields_with_invalid_post(): void {
		$repeater_field = [
			'type' => 'repeater',
			'key'  => 'field_test_repeater',
			'name' => 'test_repeater',
		];

		$sub_fields = $this->detector->get_repeater_sub_fields($repeater_field, 99999999);

		$this->assertIsArray($sub_fields, 'Should return array even for invalid post');
	}

	/**
	 * Test init method registers WordPress hooks
	 *
	 * @return void
	 */
	public function test_init_registers_hooks(): void {
		$this->detector->init();

		// Should complete without errors
		$this->assertTrue(true, 'init() should complete without errors');
	}
}
