<?php
/**
 * ACF Test Helpers for Silver Assist ACF Clone Fields
 *
 * @package SilverAssist\ACFCloneFields
 * @author SilverAssist Development Team
 * @license PolyForm-Noncommercial-1.0.0
 * @since 1.0.0
 */

namespace SilverAssist\ACFCloneFields\Tests\Utils;

/**
 * Helper class for ACF testing functionality
 */
class ACFTestHelpers {

	/**
	 * Mock ACF field data
	 *
	 * @var array
	 */
	private static array $mock_field_data = [];

	/**
	 * Setup ACF mocks for testing
	 */
	public static function setup_acf_mocks(): void {
		// Mock ACF functions if ACF is not available.
		if ( ! function_exists( 'get_field' ) ) {
			function get_field( $selector, $post_id = false ) {
				return ACFTestHelpers::get_mock_field( $selector, $post_id );
			}
		}

		if ( ! function_exists( 'update_field' ) ) {
			function update_field( $selector, $value, $post_id = false ) {
				return ACFTestHelpers::set_mock_field( $selector, $value, $post_id );
			}
		}

		if ( ! function_exists( 'get_fields' ) ) {
			function get_fields( $post_id = false ) {
				return ACFTestHelpers::get_all_mock_fields( $post_id );
			}
		}
	}

	/**
	 * Get mock field value
	 *
	 * @param string $selector Field selector.
	 * @param int|false $post_id Post ID.
	 * @return mixed Field value
	 */
	public static function get_mock_field( string $selector, $post_id = false ) {
		$post_id = $post_id ?: get_the_ID();
		return self::$mock_field_data[ $post_id ][ $selector ] ?? null;
	}

	/**
	 * Set mock field value
	 *
	 * @param string $selector Field selector.
	 * @param mixed $value Field value.
	 * @param int|false $post_id Post ID.
	 * @return bool Success
	 */
	public static function set_mock_field( string $selector, $value, $post_id = false ): bool {
		$post_id = $post_id ?: get_the_ID();
		
		if ( ! isset( self::$mock_field_data[ $post_id ] ) ) {
			self::$mock_field_data[ $post_id ] = [];
		}
		
		self::$mock_field_data[ $post_id ][ $selector ] = $value;
		return true;
	}

	/**
	 * Get all mock fields for a post
	 *
	 * @param int|false $post_id Post ID.
	 * @return array All fields
	 */
	public static function get_all_mock_fields( $post_id = false ): array {
		$post_id = $post_id ?: get_the_ID();
		return self::$mock_field_data[ $post_id ] ?? [];
	}

	/**
	 * Create mock field group
	 *
	 * @param array $fields Field definitions.
	 * @return array Mock field group
	 */
	public static function create_mock_field_group( array $fields = [] ): array {
		$mock_fields = [];
		
		foreach ( $fields as $field_name => $field_config ) {
			$mock_fields[] = [
				'key'   => 'field_' . uniqid(),
				'name'  => $field_name,
				'label' => $field_config['label'] ?? ucfirst( str_replace( '_', ' ', $field_name ) ),
				'type'  => $field_config['type'] ?? 'text',
				'value' => $field_config['value'] ?? '',
			];
		}

		return [
			'key'    => 'group_test_' . uniqid(),
			'title'  => 'Test Field Group',
			'fields' => $mock_fields,
		];
	}

	/**
	 * Clear all mock data
	 */
	public static function clear_mock_data(): void {
		self::$mock_field_data = [];
	}

	/**
	 * Get sample ACF field types for testing
	 *
	 * @return array Sample field types and values
	 */
	public static function get_sample_field_types(): array {
		return [
			'text_field'     => [
				'type'  => 'text',
				'value' => 'Sample text value',
			],
			'textarea_field' => [
				'type'  => 'textarea',
				'value' => "Sample textarea content\nwith multiple lines",
			],
			'number_field'   => [
				'type'  => 'number',
				'value' => 42,
			],
			'email_field'    => [
				'type'  => 'email',
				'value' => 'test@example.com',
			],
			'url_field'      => [
				'type'  => 'url',
				'value' => 'https://example.com',
			],
			'password_field' => [
				'type'  => 'password',
				'value' => 'secret123',
			],
			'select_field'   => [
				'type'    => 'select',
				'value'   => 'option2',
				'choices' => [
					'option1' => 'Option 1',
					'option2' => 'Option 2',
					'option3' => 'Option 3',
				],
			],
			'checkbox_field' => [
				'type'    => 'checkbox',
				'value'   => [ 'choice1', 'choice3' ],
				'choices' => [
					'choice1' => 'Choice 1',
					'choice2' => 'Choice 2',
					'choice3' => 'Choice 3',
				],
			],
			'radio_field'    => [
				'type'    => 'radio',
				'value'   => 'choice2',
				'choices' => [
					'choice1' => 'Choice 1',
					'choice2' => 'Choice 2',
				],
			],
			'true_false_field' => [
				'type'  => 'true_false',
				'value' => true,
			],
		];
	}
}