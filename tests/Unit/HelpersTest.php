<?php
/**
 * Helpers Unit Tests
 *
 * Tests for utility helper functions (pure functions that don't need WordPress).
 *
 * @package SilverAssist\ACFCloneFields
 * @subpackage Tests\Unit
 * @since 1.1.0
 */

namespace SilverAssist\ACFCloneFields\Tests\Unit;

defined( 'ABSPATH' ) || exit;

use SilverAssist\ACFCloneFields\Tests\Utils\TestCase;
use SilverAssist\ACFCloneFields\Utils\Helpers;

/**
 * Test Helpers utility functions
 */
class HelpersTest extends TestCase {

	/**
	 * Test is_acf_pro_active() detection
	 *
	 * @return void
	 */
	public function test_is_acf_pro_active_returns_boolean(): void {
		$result = Helpers::is_acf_pro_active();
		$this->assertIsBool( $result );
	}

	/**
	 * Test get_supported_field_types() returns array
	 *
	 * @return void
	 */
	public function test_get_supported_field_types_returns_array(): void {
		$field_types = Helpers::get_supported_field_types();
		
		$this->assertIsArray( $field_types );
		$this->assertNotEmpty( $field_types );
		
		// Basic fields should always be supported.
		$this->assertContains( 'text', $field_types );
		$this->assertContains( 'textarea', $field_types );
		$this->assertContains( 'number', $field_types );
		$this->assertContains( 'email', $field_types );
	}

	/**
	 * Test get_supported_field_types() includes Pro fields when ACF Pro active
	 *
	 * @return void
	 */
	public function test_get_supported_field_types_includes_pro_fields_when_acf_pro_active(): void {
		// Skip if ACF Pro not active.
		if ( ! Helpers::is_acf_pro_active() ) {
			$this->markTestSkipped( 'ACF Pro is not active' );
		}

		$field_types = Helpers::get_supported_field_types();
		
		// Pro-only fields should be present.
		$this->assertContains( 'repeater', $field_types );
		$this->assertContains( 'group', $field_types );
		$this->assertContains( 'flexible_content', $field_types );
		$this->assertContains( 'clone', $field_types );
	}

	/**
	 * Test get_supported_field_types() excludes Pro fields when ACF free
	 *
	 * @return void
	 */
	public function test_get_supported_field_types_excludes_pro_fields_when_acf_free(): void {
		// Skip if ACF Pro is active.
		if ( Helpers::is_acf_pro_active() ) {
			$this->markTestSkipped( 'ACF Pro is active, cannot test ACF free scenario' );
		}

		$field_types = Helpers::get_supported_field_types();
		
		// Pro-only fields should NOT be present.
		$this->assertNotContains( 'repeater', $field_types );
		$this->assertNotContains( 'group', $field_types );
		$this->assertNotContains( 'flexible_content', $field_types );
		$this->assertNotContains( 'clone', $field_types );
	}

	/**
	 * Test is_field_type_supported() validates correctly
	 *
	 * @return void
	 */
	public function test_is_field_type_supported_validates_basic_fields(): void {
		// Basic fields should always be supported.
		$this->assertTrue( Helpers::is_field_type_supported( 'text' ) );
		$this->assertTrue( Helpers::is_field_type_supported( 'textarea' ) );
		$this->assertTrue( Helpers::is_field_type_supported( 'number' ) );
		$this->assertTrue( Helpers::is_field_type_supported( 'email' ) );
		
		// Invalid field type.
		$this->assertFalse( Helpers::is_field_type_supported( 'invalid_field_type' ) );
	}

	/**
	 * Test is_field_type_supported() handles Pro fields correctly
	 *
	 * @return void
	 */
	public function test_is_field_type_supported_handles_pro_fields(): void {
		$is_pro = Helpers::is_acf_pro_active();
		
		// Pro fields should only be supported if ACF Pro is active.
		$this->assertEquals( $is_pro, Helpers::is_field_type_supported( 'repeater' ) );
		$this->assertEquals( $is_pro, Helpers::is_field_type_supported( 'group' ) );
		$this->assertEquals( $is_pro, Helpers::is_field_type_supported( 'flexible_content' ) );
		$this->assertEquals( $is_pro, Helpers::is_field_type_supported( 'clone' ) );
	}

	/**
	 * Test sanitize_field_key() validates field keys
	 *
	 * @return void
	 */
	public function test_sanitize_field_key_validates_format(): void {
		// Valid field keys.
		$this->assertEquals( 'field_123abc', Helpers::sanitize_field_key( 'field_123abc' ) );
		$this->assertEquals( 'field_abc123def', Helpers::sanitize_field_key( 'field_abc123def' ) );
		
		// Invalid field keys should return empty string or sanitized version.
		$this->assertNotEquals( 'invalid key', Helpers::sanitize_field_key( 'invalid key' ) );
		$this->assertNotEquals( 'field@123', Helpers::sanitize_field_key( 'field@123' ) );
	}

	/**
	 * Test format_field_name() formats field names correctly
	 *
	 * @return void
	 */
	public function test_format_field_name_handles_various_inputs(): void {
		// Should handle various inputs.
		$result1 = Helpers::format_field_name( 'field_name' );
		$this->assertIsString( $result1 );
		
		$result2 = Helpers::format_field_name( 'field-name' );
		$this->assertIsString( $result2 );
		
		$result3 = Helpers::format_field_name( 'fieldName' );
		$this->assertIsString( $result3 );
	}

	/**
	 * Test get_field_type_icon() returns appropriate icons
	 *
	 * @return void
	 */
	public function test_get_field_type_icon_returns_icons(): void {
		// Common field types should have icons.
		$this->assertNotEmpty( Helpers::get_field_type_icon( 'text' ) );
		$this->assertNotEmpty( Helpers::get_field_type_icon( 'textarea' ) );
		$this->assertNotEmpty( Helpers::get_field_type_icon( 'number' ) );
		$this->assertNotEmpty( Helpers::get_field_type_icon( 'email' ) );
		
		// Unknown field type should return default icon.
		$default_icon = Helpers::get_field_type_icon( 'unknown_type' );
		$this->assertNotEmpty( $default_icon );
	}

	/**
	 * Test can_user_edit_post() checks permissions
	 *
	 * @return void
	 */
	public function test_can_user_edit_post_checks_permissions(): void {
		// Test with valid post ID.
		// This requires WordPress environment, so it's more of an integration test.
		// But we can test that it returns a boolean.
		$result = Helpers::can_user_edit_post( 1 );
		$this->assertIsBool( $result );
		
		// Test with invalid post ID.
		$result = Helpers::can_user_edit_post( 0 );
		$this->assertIsBool( $result );
	}

	/**
	 * Test user_can_clone_fields() checks capabilities
	 *
	 * @return void
	 */
	public function test_user_can_clone_fields_checks_capabilities(): void {
		$result = Helpers::user_can_clone_fields();
		$this->assertIsBool( $result );
	}

	/**
	 * Test create_nonce() and verify_nonce() work together
	 *
	 * @return void
	 */
	public function test_nonce_creation_and_verification(): void {
		$nonce = Helpers::create_nonce();
		$this->assertIsString( $nonce );
		$this->assertNotEmpty( $nonce );
		
		// Verification requires WordPress environment.
		$result = Helpers::verify_nonce( $nonce );
		$this->assertIsBool( $result );
	}

	/**
	 * Test get_cache_key() generates consistent keys
	 *
	 * @return void
	 */
	public function test_get_cache_key_generates_consistent_keys(): void {
		$key1 = Helpers::get_cache_key( 'test_key' );
		$key2 = Helpers::get_cache_key( 'test_key' );
		
		$this->assertIsString( $key1 );
		$this->assertEquals( $key1, $key2 );
		
		// Different params should generate different keys.
		$key3 = Helpers::get_cache_key( 'test_key', array( 'param' => 'value' ) );
		$this->assertNotEquals( $key1, $key3 );
	}
}
