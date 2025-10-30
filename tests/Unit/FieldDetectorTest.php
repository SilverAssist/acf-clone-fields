<?php
/**
 * Unit Tests for Field Detector Service
 *
 * @package SilverAssist\ACFCloneFields
 * @author SilverAssist Development Team
 * @license PolyForm-Noncommercial-1.0.0
 * @since 1.0.0
 */

namespace SilverAssist\ACFCloneFields\Tests\Unit;

use SilverAssist\ACFCloneFields\Tests\Utils\TestCase;
use SilverAssist\ACFCloneFields\Tests\Utils\ACFTestHelpers;
use SilverAssist\ACFCloneFields\Services\FieldDetector;

/**
 * Test the FieldDetector service class
 */
class FieldDetectorTest extends TestCase {

	/**
	 * FieldDetector instance
	 *
	 * @var FieldDetector
	 */
	private FieldDetector $detector;

	/**
	 * Setup before each test
	 */
	public function setUp(): void {
		parent::setUp();
		$this->detector = new FieldDetector();
		
		// Setup ACF mocks.
		ACFTestHelpers::setup_acf_mocks();
	}

	/**
	 * Teardown after each test
	 */
	public function tearDown(): void {
		ACFTestHelpers::clear_mock_data();
		parent::tearDown();
	}

	/**
	 * Test detecting fields from a post
	 */
	public function test_detect_post_fields(): void {
		// Create test post with mock fields.
		$post_id = $this->create_test_post_with_fields( 
			[ 'post_type' => 'page' ],
			[
				'test_text_field'   => 'Sample text',
				'test_number_field' => 42,
				'test_email_field'  => 'test@example.com',
			]
		);

		$detected_fields = $this->detector->detect_post_fields( $post_id );

		// Should return array of detected fields.
		$this->assertIsArray( $detected_fields, 'Should return array of fields' );
		$this->assertNotEmpty( $detected_fields, 'Should detect some fields' );
	}

	/**
	 * Test detecting fields with no ACF fields present
	 */
	public function test_detect_empty_fields(): void {
		$post_id = $this->create_test_post_with_fields( [ 'post_type' => 'post' ] );

		$detected_fields = $this->detector->detect_post_fields( $post_id );

		$this->assertIsArray( $detected_fields, 'Should return array even with no fields' );
	}

	/**
	 * Test detecting fields with invalid post ID
	 */
	public function test_detect_fields_invalid_post(): void {
		$detected_fields = $this->detector->detect_post_fields( 99999 );

		$this->assertIsArray( $detected_fields, 'Should return array for invalid post ID' );
		$this->assertEmpty( $detected_fields, 'Should return empty array for invalid post ID' );
	}

	/**
	 * Test field type detection
	 */
	public function test_field_type_detection(): void {
		$sample_fields = ACFTestHelpers::get_sample_field_types();
		
		foreach ( $sample_fields as $field_name => $field_config ) {
			$field_type = $this->detector->get_field_type( $field_name );
			
			// Field type detection should work (mock may return different results).
			$this->assertIsString( $field_type, "Field type should be string for {$field_name}" );
		}
	}

	/**
	 * Test getting supported field types
	 */
	public function test_get_supported_field_types(): void {
		$supported_types = $this->detector->get_supported_field_types();

		$this->assertIsArray( $supported_types, 'Should return array of supported types' );
		$this->assertNotEmpty( $supported_types, 'Should have some supported field types' );
		
		// Should include common ACF field types.
		$expected_types = [ 'text', 'textarea', 'number', 'email', 'url', 'select', 'checkbox', 'radio', 'true_false' ];
		
		foreach ( $expected_types as $type ) {
			$this->assertContains( $type, $supported_types, "Should support {$type} field type" );
		}
	}

	/**
	 * Test field compatibility checking
	 */
	public function test_field_compatibility(): void {
		// Test compatible field types.
		$this->assertTrue( $this->detector->are_fields_compatible( 'text', 'text' ), 'Same field types should be compatible' );
		$this->assertTrue( $this->detector->are_fields_compatible( 'text', 'textarea' ), 'Text fields should be compatible' );
		$this->assertTrue( $this->detector->are_fields_compatible( 'number', 'number' ), 'Number fields should be compatible' );
		
		// Test incompatible field types.
		$this->assertFalse( $this->detector->are_fields_compatible( 'text', 'image' ), 'Text and image should not be compatible' );
		$this->assertFalse( $this->detector->are_fields_compatible( 'number', 'gallery' ), 'Number and gallery should not be compatible' );
	}
}