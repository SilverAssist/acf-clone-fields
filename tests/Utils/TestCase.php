<?php
/**
 * Base Test Case for Silver Assist ACF Clone Fields
 *
 * @package SilverAssist\ACFCloneFields
 * @author SilverAssist Development Team
 * @license PolyForm-Noncommercial-1.0.0
 * @since 1.0.0
 */

namespace SilverAssist\ACFCloneFields\Tests\Utils;

use WP_UnitTestCase;

/**
 * Base test case class for ACF Clone Fields tests
 */
abstract class TestCase extends WP_UnitTestCase {

	/**
	 * Setup before each test
	 */
	public function setUp(): void {
		parent::setUp();
		
		// Enable ACF if available.
		if ( class_exists( 'ACF' ) ) {
			// Initialize ACF for testing.
			acf_get_setting( 'show_admin' );
		}
	}

	/**
	 * Teardown after each test
	 */
	public function tearDown(): void {
		// Clean up any test data.
		$this->cleanup_test_posts();
		$this->cleanup_test_field_groups();
		
		parent::tearDown();
	}

	/**
	 * Create a test post with ACF fields
	 *
	 * @param array $post_args Post arguments.
	 * @param array $fields ACF field values.
	 * @return int Post ID
	 */
	protected function create_test_post_with_fields( array $post_args = [], array $fields = [] ): int {
		$default_args = [
			'post_title'   => 'Test Post',
			'post_content' => 'Test content',
			'post_status'  => 'publish',
			'post_type'    => 'post',
		];

		$post_args = array_merge( $default_args, $post_args );
		$post_id   = $this->factory()->post->create( $post_args );

		// Add ACF fields if provided.
		foreach ( $fields as $field_name => $field_value ) {
			update_field( $field_name, $field_value, $post_id );
		}

		return $post_id;
	}

	/**
	 * Create a test ACF field group
	 *
	 * @param array $fields Field definitions.
	 * @param array $location Location rules.
	 * @return array Field group array
	 */
	protected function create_test_field_group( array $fields = [], array $location = [] ): array {
		$field_group = [
			'key'        => 'group_test_' . uniqid(),
			'title'      => 'Test Field Group',
			'fields'     => $fields,
			'location'   => $location,
			'options'    => [
				'position'       => 'normal',
				'layout'         => 'no_box',
				'hide_on_screen' => [],
			],
		];

		if ( function_exists( 'acf_add_local_field_group' ) ) {
			acf_add_local_field_group( $field_group );
		}

		return $field_group;
	}

	/**
	 * Assert that ACF field values match
	 *
	 * @param mixed  $expected Expected value.
	 * @param mixed  $actual Actual value.
	 * @param string $message Error message.
	 */
	protected function assertFieldEquals( $expected, $actual, string $message = '' ): void {
		if ( is_array( $expected ) && is_array( $actual ) ) {
			$this->assertEquals( $expected, $actual, $message );
		} else {
			$this->assertEquals( $expected, $actual, $message );
		}
	}

	/**
	 * Clean up test posts created during testing
	 */
	private function cleanup_test_posts(): void {
		global $wpdb;
		
		// Delete any posts created during testing.
		$test_posts = get_posts( [
			'post_type'      => 'any',
			'post_status'    => 'any',
			'posts_per_page' => -1,
			'meta_query'     => [
				[
					'key'     => '_test_post',
					'compare' => 'EXISTS',
				],
			],
		] );

		foreach ( $test_posts as $post ) {
			wp_delete_post( $post->ID, true );
		}
	}

	/**
	 * Clean up test field groups
	 */
	private function cleanup_test_field_groups(): void {
		if ( function_exists( 'acf_get_local_field_groups' ) ) {
			$field_groups = acf_get_local_field_groups();
			
			foreach ( $field_groups as $field_group ) {
				if ( strpos( $field_group['key'], 'group_test_' ) === 0 ) {
					acf_remove_local_field_group( $field_group['key'] );
				}
			}
		}
	}
}