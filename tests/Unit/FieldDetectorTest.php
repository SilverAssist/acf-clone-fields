<?php

namespace SilverAssist\ACFCloneFields\Tests\Unit;

defined('ABSPATH') || exit;

use SilverAssist\ACFCloneFields\Tests\Utils\TestCase;
use SilverAssist\ACFCloneFields\Services\FieldDetector;

/**
 * Test the FieldDetector service class
 */
class FieldDetectorTest extends TestCase {

	private FieldDetector $detector;

	protected function setUp(): void {
		parent::setUp();
		$this->detector = FieldDetector::instance();
	}

	/**
	 * Test field detection service instantiation
	 */
	public function test_instance_creation(): void {
		$this->assertInstanceOf( FieldDetector::class, $this->detector );
		$this->assertEquals( 30, $this->detector->get_priority() );
	}

	/**
	 * Test get available fields with mock post ID
	 */
	public function test_get_available_fields(): void {
		$post_id = 123;
		
		$detected_fields = $this->detector->get_available_fields( $post_id );

		$this->assertIsArray( $detected_fields );
		// Without ACF active, should return empty array
		$this->assertEmpty( $detected_fields );
	}

	/**
	 * Test get field groups with post type
	 */
	public function test_get_field_groups(): void {
		$post_type = 'post';

		$field_groups = $this->detector->get_field_groups( $post_type );

		$this->assertIsArray( $field_groups );
		// Without ACF active, should return empty array
		$this->assertEmpty( $field_groups );
	}

	/**
	 * Test get field statistics
	 */
	public function test_get_field_statistics(): void {
		$post_id = 123;

		$statistics = $this->detector->get_field_statistics( $post_id );

		$this->assertIsArray( $statistics );
		$this->assertArrayHasKey( 'total_fields', $statistics );
		$this->assertArrayHasKey( 'total_groups', $statistics );
		$this->assertArrayHasKey( 'repeater_fields', $statistics );
	}

	/**
	 * Test cache clearing functionality
	 */
	public function test_clear_field_cache(): void {
		$post_id = 123;

		// Should not throw exception
		$this->detector->clear_field_cache( $post_id );
		$this->detector->clear_field_cache(); // Test without post_id

		$this->assertTrue( true ); // If we reach here, no exceptions were thrown
	}

	/**
	 * Test repeater sub fields functionality
	 */
	public function test_get_repeater_sub_fields(): void {
		$post_id = 123;
		$repeater_field = [
			'type' => 'repeater',
			'key' => 'field_test_repeater',
			'name' => 'test_repeater'
		];

		$sub_fields = $this->detector->get_repeater_sub_fields( $repeater_field, $post_id );

		$this->assertIsArray( $sub_fields );
		// Without ACF active, should return empty array
		$this->assertEmpty( $sub_fields );
	}

	/**
	 * Test should load functionality
	 */
	public function testShouldLoad(): void {
		// Without ACF active, should return false
		$should_load = $this->detector->should_load();

		$this->assertIsBool( $should_load );
	}
}