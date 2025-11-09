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

	/**
	 * Test get_enabled_post_types() returns array
	 *
	 * @return void
	 */
	public function test_get_enabled_post_types_returns_array(): void {
		$post_types = Helpers::get_enabled_post_types();
		
		$this->assertIsArray( $post_types );
		$this->assertNotEmpty( $post_types );
	}

	/**
	 * Test get_enabled_post_types() respects settings
	 *
	 * @return void
	 */
	public function test_get_enabled_post_types_respects_settings(): void {
		// Set custom post types.
		\update_option(
			'silver_acf_clone_settings',
			[
				'enabled_post_types' => [ 'custom_post', 'another_type' ],
			]
		);
		
		$post_types = Helpers::get_enabled_post_types();
		
		$this->assertContains( 'custom_post', $post_types );
		$this->assertContains( 'another_type', $post_types );
		
		// Cleanup.
		\delete_option( 'silver_acf_clone_settings' );
	}

	/**
	 * Test is_post_type_enabled() validates post types
	 *
	 * @return void
	 */
	public function test_is_post_type_enabled_validates_post_types(): void {
		\update_option(
			'silver_acf_clone_settings',
			[
				'enabled_post_types' => [ 'post', 'page' ],
			]
		);
		
		$this->assertTrue( Helpers::is_post_type_enabled( 'post' ) );
		$this->assertTrue( Helpers::is_post_type_enabled( 'page' ) );
		$this->assertFalse( Helpers::is_post_type_enabled( 'custom_type' ) );
		
		// Cleanup.
		\delete_option( 'silver_acf_clone_settings' );
	}

	/**
	 * Test get_source_posts() returns posts
	 *
	 * @return void
	 */
	public function test_get_source_posts_returns_posts(): void {
		// Create test posts.
		$post1_id = static::factory()->post->create(
			[
				'post_title'  => 'Source Post 1',
				'post_status' => 'publish',
			]
		);
		$post2_id = static::factory()->post->create(
			[
				'post_title'  => 'Source Post 2',
				'post_status' => 'publish',
			]
		);
		$current_post_id = static::factory()->post->create(
			[
				'post_title'  => 'Current Post',
				'post_status' => 'publish',
			]
		);
		
		$posts = Helpers::get_source_posts( $current_post_id, 'post' );
		
		$this->assertIsArray( $posts );
		
		// Verify current post is excluded.
		$post_ids = \wp_list_pluck( $posts, 'ID' );
		$this->assertNotContains( $current_post_id, $post_ids );
		
		// Cleanup.
		\wp_delete_post( $post1_id, true );
		\wp_delete_post( $post2_id, true );
		\wp_delete_post( $current_post_id, true );
	}

	/**
	 * Test post_has_acf_fields() handles missing ACF
	 *
	 * @return void
	 */
	public function test_post_has_acf_fields_handles_missing_acf(): void {
		$post_id = static::factory()->post->create();
		
		// Should return false when get_fields() doesn't exist or no fields.
		$result = Helpers::post_has_acf_fields( $post_id );
		$this->assertIsBool( $result );
		
		// Cleanup.
		\wp_delete_post( $post_id, true );
	}

	/**
	 * Test validate_field_data() validates field data types
	 *
	 * @return void
	 */
	public function test_validate_field_data_validates_field_data(): void {
		// Valid text data.
		$field_config = [ 'type' => 'text' ];
		$this->assertTrue( Helpers::validate_field_data( 'Valid text', $field_config ) );
		
		// Empty string is still valid string type.
		$this->assertTrue( Helpers::validate_field_data( '', $field_config ) );
		
		// Number field.
		$field_config = [ 'type' => 'number' ];
		$this->assertTrue( Helpers::validate_field_data( 123, $field_config ) );
		$this->assertTrue( Helpers::validate_field_data( '456', $field_config ) );
		
		// Array field types.
		$field_config = [ 'type' => 'repeater' ];
		$this->assertTrue( Helpers::validate_field_data( [], $field_config ) );
		$this->assertTrue( Helpers::validate_field_data( [ 'item1', 'item2' ], $field_config ) );
	}

	/**
	 * Test clear_cache() works
	 *
	 * @return void
	 */
	public function test_clear_cache_works(): void {
		// Should not throw errors.
		Helpers::clear_cache( 'test_key' );
		Helpers::clear_cache();
		
		$this->assertTrue( true, 'clear_cache should execute without errors' );
	}

	/**
	 * Test log() executes without errors
	 *
	 * @return void
	 */
	public function test_log_executes_without_errors(): void {
		Helpers::log( 'Test message', 'info' );
		Helpers::log( 'Error message', 'error', [ 'context' => 'test' ] );
		
		$this->assertTrue( true, 'log should execute without errors' );
	}

	/**
	 * Test get_posts_by_type() returns array
	 *
	 * @return void
	 */
	public function test_get_posts_by_type_returns_posts(): void {
		// Create test posts with current user as author.
		$current_user_id = \get_current_user_id();
		$post1_id = static::factory()->post->create(
			[
				'post_title'  => 'Test Post 1',
				'post_status' => 'publish',
				'post_author' => $current_user_id,
			]
		);
		
		$posts = Helpers::get_posts_by_type( 'post' );
		
		// Should return an array (may be empty depending on permissions).
		$this->assertIsArray( $posts );
		
		// If posts returned, verify they are WP_Post objects.
		if ( ! empty( $posts ) ) {
			foreach ( $posts as $post ) {
				$this->assertInstanceOf( \WP_Post::class, $post );
			}
		}
		
		// Cleanup.
		\wp_delete_post( $post1_id, true );
	}

	/**
	 * Test get_posts_by_type() respects custom args
	 *
	 * @return void
	 */
	public function test_get_posts_by_type_respects_custom_args(): void {
		// Create test posts with current user as author.
		$current_user_id = \get_current_user_id();
		$post1_id = static::factory()->post->create(
			[
				'post_title'  => 'Alpha Post',
				'post_status' => 'publish',
				'post_author' => $current_user_id,
			]
		);
		$post2_id = static::factory()->post->create(
			[
				'post_title'  => 'Beta Post',
				'post_status' => 'publish',
				'post_author' => $current_user_id,
			]
		);
		
		// Query with limit.
		$posts = Helpers::get_posts_by_type( 'post', [ 'posts_per_page' => 1 ] );
		
		$this->assertIsArray( $posts );
		$this->assertLessThanOrEqual( 1, count( $posts ), 'Should respect posts_per_page limit' );
		
		// Cleanup.
		\wp_delete_post( $post1_id, true );
		\wp_delete_post( $post2_id, true );
	}
}
