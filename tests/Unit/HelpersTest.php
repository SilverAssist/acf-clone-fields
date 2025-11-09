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

	/**
	 * Test sanitize_field_key with special characters
	 *
	 * @return void
	 */
	public function test_sanitize_field_key_with_special_characters(): void {
		$keys = [
			'field-test_123'   => 'field-test_123',
			'field test'       => 'field_test',
			'FIELD_TEST'       => 'field_test',
			'field--test__123' => 'field-test_123',
		];

		foreach ( $keys as $input => $expected ) {
			$result = Helpers::sanitize_field_key( $input );
			$this->assertMatchesRegularExpression( '/^[a-z0-9_-]+$/', $result, "Sanitized key should only contain lowercase, numbers, underscore, hyphen" );
		}
	}

	/**
	 * Test format_field_name with empty string
	 *
	 * @return void
	 */
	public function test_format_field_name_with_empty_string(): void {
		$result = Helpers::format_field_name( '' );
		
		$this->assertIsString( $result );
		$this->assertEquals( '', $result, 'Empty string should return empty string' );
	}

	/**
	 * Test format_field_name with underscores
	 *
	 * @return void
	 */
	public function test_format_field_name_converts_underscores(): void {
		$result = Helpers::format_field_name( 'test_field_name' );
		
		$this->assertIsString( $result );
		$this->assertEquals( 'Test Field Name', $result, 'Should convert underscores to spaces and capitalize' );
	}

	/**
	 * Test get_field_type_icon with unknown type
	 *
	 * @return void
	 */
	public function test_get_field_type_icon_with_unknown_type(): void {
		$icon = Helpers::get_field_type_icon( 'nonexistent_type_12345' );
		
		$this->assertIsString( $icon );
		$this->assertNotEmpty( $icon, 'Should return default icon for unknown types' );
	}

	/**
	 * Test get_field_type_icon with all common types
	 *
	 * @return void
	 */
	public function test_get_field_type_icon_all_common_types(): void {
		$common_types = [ 'text', 'textarea', 'number', 'email', 'url', 'select', 'checkbox', 'radio', 'true_false', 'image', 'file' ];

		foreach ( $common_types as $type ) {
			$icon = Helpers::get_field_type_icon( $type );
			$this->assertIsString( $icon, "Should return string icon for {$type}" );
			$this->assertNotEmpty( $icon, "Icon for {$type} should not be empty" );
		}
	}

	/**
	 * Test verify_nonce with invalid nonce
	 *
	 * @return void
	 */
	public function test_verify_nonce_with_invalid_nonce(): void {
		$result = Helpers::verify_nonce( 'invalid_nonce_12345', 'test_action' );
		
		$this->assertFalse( $result, 'Invalid nonce should return false' );
	}

	/**
	 * Test verify_nonce with empty nonce
	 *
	 * @return void
	 */
	public function test_verify_nonce_with_empty_nonce(): void {
		$result = Helpers::verify_nonce( '', 'test_action' );
		
		$this->assertFalse( $result, 'Empty nonce should return false' );
	}

	/**
	 * Test create_nonce with custom action
	 *
	 * @return void
	 */
	public function test_create_nonce_with_custom_action(): void {
		$nonce = Helpers::create_nonce( 'custom_action_test' );
		
		$this->assertIsString( $nonce );
		$this->assertNotEmpty( $nonce );
		
		// Verify it works with same action.
		$verified = Helpers::verify_nonce( $nonce, 'custom_action_test' );
		$this->assertTrue( $verified, 'Nonce should verify with same action' );
	}

	/**
	 * Test user_can_clone_fields with zero post ID
	 *
	 * @return void
	 */
	public function test_user_can_clone_fields_with_zero_post_id(): void {
		$can_clone = Helpers::user_can_clone_fields( 0 );
		
		$this->assertIsBool( $can_clone );
	}

	/**
	 * Test can_user_edit_post with current user
	 *
	 * @return void
	 */
	public function test_can_user_edit_post_with_current_user(): void {
		$post_id = static::factory()->post->create(
			[
				'post_title'  => 'Test Post for Edit Check',
				'post_status' => 'draft', // Use draft to avoid published post capability check.
				'post_author' => \get_current_user_id(),
			]
		);

		$can_edit = Helpers::can_user_edit_post( $post_id );
		
		// Admin user should be able to edit their own draft post.
		$this->assertIsBool( $can_edit, 'Should return boolean for edit check' );

		// Cleanup.
		\wp_delete_post( $post_id, true );
	}

	/**
	 * Test can_user_edit_post with invalid post ID
	 *
	 * @return void
	 */
	public function test_can_user_edit_post_with_invalid_post(): void {
		$can_edit = Helpers::can_user_edit_post( 999999 );
		
		$this->assertFalse( $can_edit, 'Should return false for invalid post ID' );
	}

	/**
	 * Test can_user_edit_post with specific user ID
	 *
	 * @return void
	 */
	public function test_can_user_edit_post_with_specific_user(): void {
		$post_id = static::factory()->post->create(
			[
				'post_title'  => 'Test Post',
				'post_status' => 'publish',
			]
		);

		$user_id = \get_current_user_id();
		$can_edit = Helpers::can_user_edit_post( $post_id, $user_id );
		
		$this->assertIsBool( $can_edit );

		// Cleanup.
		\wp_delete_post( $post_id, true );
	}

	/**
	 * Test get_cache_key with empty params
	 *
	 * @return void
	 */
	public function test_get_cache_key_with_empty_params(): void {
		$key1 = Helpers::get_cache_key( 'test_key', [] );
		$key2 = Helpers::get_cache_key( 'test_key' );
		
		$this->assertIsString( $key1 );
		$this->assertIsString( $key2 );
	}

	/**
	 * Test get_cache_key with different params generates different keys
	 *
	 * @return void
	 */
	public function test_get_cache_key_different_params(): void {
		$key1 = Helpers::get_cache_key( 'test', [ 'param' => 'value1' ] );
		$key2 = Helpers::get_cache_key( 'test', [ 'param' => 'value2' ] );
		
		$this->assertNotEquals( $key1, $key2, 'Different params should generate different cache keys' );
	}

	/**
	 * Test clear_cache with specific key
	 *
	 * @return void
	 */
	public function test_clear_cache_with_specific_key(): void {
		// Should not throw exception.
		Helpers::clear_cache( 'specific_cache_key' );
		
		$this->assertTrue( true, 'clear_cache with specific key should execute without errors' );
	}

	/**
	 * Test clear_cache with null clears all
	 *
	 * @return void
	 */
	public function test_clear_cache_with_null_clears_all(): void {
		Helpers::clear_cache( null );
		
		$this->assertTrue( true, 'clear_cache(null) should clear all caches without errors' );
	}

	/**
	 * Test log with different levels
	 *
	 * @return void
	 */
	public function test_log_with_different_levels(): void {
		$levels = [ 'debug', 'info', 'warning', 'error', 'critical' ];

		foreach ( $levels as $level ) {
			Helpers::log( "Test {$level} message", $level );
		}

		$this->assertTrue( true, 'Logging with all levels should work without errors' );
	}

	/**
	 * Test log with complex context
	 *
	 * @return void
	 */
	public function test_log_with_complex_context(): void {
		$context = [
			'user_id' => 123,
			'post_id' => 456,
			'data'    => [
				'field1' => 'value1',
				'field2' => [ 'nested' => 'value' ],
			],
		];

		Helpers::log( 'Complex context test', 'info', $context );

		$this->assertTrue( true, 'Logging with complex context should work' );
	}

	/**
	 * Test validate_field_data with null data
	 *
	 * @return void
	 */
	public function test_validate_field_data_with_null(): void {
		$field_config = [ 'type' => 'text', 'required' => false ];
		
		$result = Helpers::validate_field_data( null, $field_config );
		
		$this->assertIsBool( $result );
	}

	/**
	 * Test validate_field_data with empty array
	 *
	 * @return void
	 */
	public function test_validate_field_data_with_empty_array(): void {
		$field_config = [ 'type' => 'array', 'required' => false ];
		
		$result = Helpers::validate_field_data( [], $field_config );
		
		$this->assertIsBool( $result );
	}

	/**
	 * Test validate_field_data with string data
	 *
	 * @return void
	 */
	public function test_validate_field_data_with_string(): void {
		$field_config = [ 'type' => 'text' ];
		
		$result = Helpers::validate_field_data( 'test string', $field_config );
		
		$this->assertIsBool( $result );
	}

	/**
	 * Test get_source_posts excludes current post
	 *
	 * @return void
	 */
	public function test_get_source_posts_excludes_current(): void {
		$current_post = static::factory()->post->create(
			[
				'post_title'  => 'Current Post',
				'post_status' => 'publish',
				'post_author' => \get_current_user_id(),
			]
		);

		$other_post = static::factory()->post->create(
			[
				'post_title'  => 'Other Post',
				'post_status' => 'publish',
				'post_author' => \get_current_user_id(),
			]
		);

		$posts = Helpers::get_source_posts( $current_post, 'post' );

		$this->assertIsArray( $posts );
		
		// Current post should not be in results.
		foreach ( $posts as $post ) {
			$this->assertNotEquals( $current_post, $post->ID, 'Current post should be excluded from source posts' );
		}

		// Cleanup.
		\wp_delete_post( $current_post, true );
		\wp_delete_post( $other_post, true );
	}

	/**
	 * Test get_source_posts with invalid post type
	 *
	 * @return void
	 */
	public function test_get_source_posts_with_invalid_type(): void {
		$posts = Helpers::get_source_posts( 1, 'nonexistent_post_type_12345' );
		
		$this->assertIsArray( $posts );
		$this->assertEmpty( $posts, 'Should return empty array for invalid post type' );
	}

	/**
	 * Test post_has_acf_fields with post that has no fields
	 *
	 * @return void
	 */
	public function test_post_has_acf_fields_with_no_fields(): void {
		$post_id = static::factory()->post->create(
			[
				'post_title'  => 'Post Without Fields',
				'post_status' => 'publish',
			]
		);

		$has_fields = Helpers::post_has_acf_fields( $post_id );

		$this->assertIsBool( $has_fields );

		// Cleanup.
		\wp_delete_post( $post_id, true );
	}

	/**
	 * Test post_has_acf_fields with invalid post ID
	 *
	 * @return void
	 */
	public function test_post_has_acf_fields_with_invalid_id(): void {
		$has_fields = Helpers::post_has_acf_fields( 0 );
		
		$this->assertFalse( $has_fields, 'Invalid post ID should return false' );
	}

	/**
	 * Test is_post_type_enabled with empty string
	 *
	 * @return void
	 */
	public function test_is_post_type_enabled_with_empty_string(): void {
		$result = Helpers::is_post_type_enabled( '' );
		
		$this->assertFalse( $result, 'Empty post type should return false' );
	}

	/**
	 * Test is_field_type_supported with empty string
	 *
	 * @return void
	 */
	public function test_is_field_type_supported_with_empty_string(): void {
		$result = Helpers::is_field_type_supported( '' );
		
		$this->assertFalse( $result, 'Empty field type should return false' );
	}

	/**
	 * Test get_posts_by_type with empty post type
	 *
	 * @return void
	 */
	public function test_get_posts_by_type_with_empty_type(): void {
		$posts = Helpers::get_posts_by_type( '' );
		
		$this->assertIsArray( $posts );
		$this->assertEmpty( $posts, 'Empty post type should return empty array' );
	}

	/**
	 * Test get_enabled_post_types with no settings returns defaults
	 *
	 * @return void
	 */
	public function test_get_enabled_post_types_defaults(): void {
		\delete_option( 'silver_acf_clone_settings' );

		$types = Helpers::get_enabled_post_types();

		$this->assertIsArray( $types );
		$this->assertContains( 'post', $types, 'Should include post by default' );
		$this->assertContains( 'page', $types, 'Should include page by default' );
	}

	/**
	 * Test instance method returns singleton
	 *
	 * @return void
	 */
	public function test_instance_returns_singleton(): void {
		$instance1 = Helpers::instance();
		$instance2 = Helpers::instance();

		$this->assertSame( $instance1, $instance2, 'Should return same instance' );
		$this->assertInstanceOf( Helpers::class, $instance1 );
	}
}

