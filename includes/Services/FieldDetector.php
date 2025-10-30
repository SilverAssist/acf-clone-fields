<?php
/**
 * Field Detector Service
 *
 * Detects and analyzes ACF fields for cloning operations.
 * Provides functionality to identify available fields, groups, and repeater sub-fields.
 *
 * @package SilverAssist\ACFCloneFields
 * @subpackage Services
 * @since 1.0.0
 * @version 1.0.0
 * @author Silver Assist
 */

namespace SilverAssist\ACFCloneFields\Services;

use SilverAssist\ACFCloneFields\Core\Interfaces\LoadableInterface;
use SilverAssist\ACFCloneFields\Utils\Helpers;

defined( 'ABSPATH' ) || exit;

/**
 * Class FieldDetector
 *
 * Analyzes posts and field groups to identify cloneable ACF fields.
 */
class FieldDetector implements LoadableInterface {
	/**
	 * Singleton instance
	 *
	 * @var FieldDetector|null
	 */
	private static ?FieldDetector $instance = null;

	/**
	 * Cached field configurations
	 *
	 * @var array<int, array<string, mixed>>
	 */
	private array $field_cache = [];

	/**
	 * Get singleton instance
	 *
	 * @return FieldDetector
	 */
	public static function instance(): FieldDetector {
		if ( self::$instance === null ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Initialize the field detector
	 *
	 * @return void
	 */
	public function init(): void {
		// Hook into ACF to clear cache when field groups are updated.
		add_action( 'acf/save_post', [ $this, 'clear_field_cache' ] );
		add_action( 'acf/field_group/admin_save_field_group', [ $this, 'clear_field_cache' ] );
	}

	/**
	 * Get loading priority
	 *
	 * @return int
	 */
	public function get_priority(): int {
		return 30; // Services.
	}

	/**
	 * Determine if field detector should load
	 *
	 * @return bool
	 */
	public function should_load(): bool {
		return function_exists( 'acf_get_field_groups' ) && function_exists( 'get_fields' );
	}

	/**
	 * Get available fields for a post
	 *
	 * @param int $post_id Post ID to analyze.
	 * @return array<string, mixed> Available fields organized by groups
	 */
	public function get_available_fields( int $post_id ): array {
		// Check cache first.
		if ( isset( $this->field_cache[ $post_id ] ) ) {
			return $this->field_cache[ $post_id ];
		}

		$post_type = get_post_type( $post_id );
		if ( ! $post_type ) {
			return [];
		}

		$available_fields = [];

		// Get field groups for this post type.
		$field_groups = $this->get_field_groups( $post_type );

		foreach ( $field_groups as $field_group ) {
			$group_fields = $this->get_fields_from_group( $field_group, $post_id );

			if ( ! empty( $group_fields ) ) {
				$available_fields[ $field_group['key'] ] = [
					'title'  => $field_group['title'],
					'key'    => $field_group['key'],
					'fields' => $group_fields,
				];
			}
		}

		// Cache the result.
		$this->field_cache[ $post_id ] = $available_fields;

		return $available_fields;
	}

	/**
	 * Get field groups for a specific post type
	 *
	 * @param string $post_type Post type to query.
	 * @return array<array<string, mixed>> Field groups
	 */
	public function get_field_groups( string $post_type ): array {
		if ( ! function_exists( 'acf_get_field_groups' ) ) {
			return [];
		}

		$field_groups = acf_get_field_groups(
			[
				'post_type' => $post_type,
			]
		);

		return $field_groups ?: [];
	}

	/**
	 * Get fields from a field group
	 *
	 * @param array<string, mixed> $field_group Field group configuration.
	 * @param int                  $post_id Post ID for field value context.
	 * @return array<string, mixed> Fields from the group
	 */
	private function get_fields_from_group( array $field_group, int $post_id ): array {
		if ( ! function_exists( 'acf_get_fields' ) || empty( $field_group['key'] ) ) {
			return [];
		}

		$fields = acf_get_fields( $field_group['key'] );
		if ( ! $fields ) {
			return [];
		}

		$processed_fields = [];

		foreach ( $fields as $field ) {
			$field_data = $this->process_field( $field, $post_id );
			if ( $field_data ) {
				$processed_fields[ $field['key'] ] = $field_data;
			}
		}

		return $processed_fields;
	}

	/**
	 * Process individual field for cloning analysis
	 *
	 * @param array<string, mixed> $field ACF field configuration.
	 * @param int                  $post_id Post ID for context.
	 * @return array<string, mixed>|null Processed field data or null if not cloneable
	 */
	private function process_field( array $field, int $post_id ): ?array {
		// Get current field value.
		$current_value = function_exists( 'get_field' ) ? get_field( $field['key'], $post_id ) : null;

		// Skip empty fields unless they're containers (repeater/group).
		if ( empty( $current_value ) && ! in_array( $field['type'], [ 'repeater', 'group' ], true ) ) {
			return null;
		}

		$processed_field = [
			'key'          => $field['key'],
			'name'         => $field['name'],
			'label'        => $field['label'],
			'type'         => $field['type'],
			'value'        => $current_value,
			'has_value'    => ! empty( $current_value ),
			'is_cloneable' => $this->is_field_cloneable( $field ),
		];

		// Add type-specific processing.
		switch ( $field['type'] ) {
			case 'repeater':
				$processed_field['sub_fields'] = $this->get_repeater_sub_fields( $field, $post_id );
				$processed_field['row_count']  = is_array( $current_value ) ? count( $current_value ) : 0;
				break;

			case 'group':
				$processed_field['sub_fields'] = $this->get_group_sub_fields( $field, $post_id );
				break;

			case 'flexible_content':
				$processed_field['layouts'] = $this->get_flexible_content_layouts( $field, $post_id );
				break;

			case 'image':
			case 'file':
				$processed_field['attachment_info'] = $this->get_attachment_info( $current_value );
				break;
		}

		return $processed_field;
	}

	/**
	 * Get repeater sub-fields
	 *
	 * @param array<string, mixed> $repeater_field Repeater field configuration.
	 * @param int                  $post_id Post ID for context.
	 * @return array<string, mixed> Sub-fields data
	 */
	public function get_repeater_sub_fields( array $repeater_field, int $post_id ): array {
		$sub_fields = [];

		if ( isset( $repeater_field['sub_fields'] ) && is_array( $repeater_field['sub_fields'] ) ) {
			foreach ( $repeater_field['sub_fields'] as $sub_field ) {
				$sub_fields[ $sub_field['key'] ] = [
					'key'          => $sub_field['key'],
					'name'         => $sub_field['name'],
					'label'        => $sub_field['label'],
					'type'         => $sub_field['type'],
					'is_cloneable' => $this->is_field_cloneable( $sub_field ),
				];
			}
		}

		return $sub_fields;
	}

	/**
	 * Get group sub-fields
	 *
	 * @param array<string, mixed> $group_field Group field configuration.
	 * @param int                  $post_id Post ID for context.
	 * @return array<string, mixed> Sub-fields data
	 */
	private function get_group_sub_fields( array $group_field, int $post_id ): array {
		$sub_fields = [];

		if ( isset( $group_field['sub_fields'] ) && is_array( $group_field['sub_fields'] ) ) {
			foreach ( $group_field['sub_fields'] as $sub_field ) {
				$processed_sub_field = $this->process_field( $sub_field, $post_id );
				if ( $processed_sub_field ) {
					$sub_fields[ $sub_field['key'] ] = $processed_sub_field;
				}
			}
		}

		return $sub_fields;
	}

	/**
	 * Get flexible content layouts
	 *
	 * @param array<string, mixed> $flexible_field Flexible content field configuration.
	 * @param int                  $post_id Post ID for context.
	 * @return array<string, mixed> Layouts data
	 */
	private function get_flexible_content_layouts( array $flexible_field, int $post_id ): array {
		$layouts = [];

		if ( isset( $flexible_field['layouts'] ) && is_array( $flexible_field['layouts'] ) ) {
			foreach ( $flexible_field['layouts'] as $layout ) {
				$layouts[ $layout['key'] ] = [
					'key'        => $layout['key'],
					'name'       => $layout['name'],
					'label'      => $layout['label'],
					'sub_fields' => [],
				];

				if ( isset( $layout['sub_fields'] ) && is_array( $layout['sub_fields'] ) ) {
					foreach ( $layout['sub_fields'] as $sub_field ) {
						$layouts[ $layout['key'] ]['sub_fields'][ $sub_field['key'] ] = [
							'key'          => $sub_field['key'],
							'name'         => $sub_field['name'],
							'label'        => $sub_field['label'],
							'type'         => $sub_field['type'],
							'is_cloneable' => $this->is_field_cloneable( $sub_field ),
						];
					}
				}
			}
		}

		return $layouts;
	}

	/**
	 * Get attachment information for file/image fields
	 *
	 * @param mixed $attachment_value Attachment value (ID or array).
	 * @return array<string, mixed>|null Attachment info or null
	 */
	private function get_attachment_info( $attachment_value ): ?array {
		if ( empty( $attachment_value ) ) {
			return null;
		}

		$attachment_id = is_array( $attachment_value ) ? ( $attachment_value['ID'] ?? null ) : $attachment_value;

		if ( ! is_numeric( $attachment_id ) ) {
			return null;
		}

		$attachment = get_post( (int) $attachment_id );
		if ( ! $attachment || $attachment->post_type !== 'attachment' ) {
			return null;
		}

		return [
			'id'       => $attachment_id,
			'title'    => $attachment->post_title,
			'filename' => basename( get_attached_file( (int) $attachment_id ) ?: '' ),
			'url'      => wp_get_attachment_url( (int) $attachment_id ),
		];
	}

	/**
	 * Check if field is cloneable
	 *
	 * @param array<string, mixed> $field Field configuration.
	 * @return bool True if field can be cloned
	 */
	private function is_field_cloneable( array $field ): bool {
		// List of non-cloneable field types.
		$non_cloneable_types = [
			'message', // Display only.
			'tab',     // Layout only.
			'accordion', // Layout only.
		];

		$field_type = $field['type'] ?? '';

		return ! in_array( $field_type, $non_cloneable_types, true );
	}

	/**
	 * Clear field cache
	 *
	 * @param int|null $post_id Optional post ID to clear specific cache.
	 * @return void
	 */
	public function clear_field_cache( ?int $post_id = null ): void {
		if ( null !== $post_id ) {
			unset( $this->field_cache[ $post_id ] );
		} else {
			$this->field_cache = [];
		}

		// Clear WordPress cache as well.
		Helpers::clear_cache( 'field_detection' );
	}

	/**
	 * Get field statistics for a post
	 *
	 * @param int $post_id Post ID to analyze.
	 * @return array<string, int> Field statistics
	 */
	public function get_field_statistics( int $post_id ): array {
		$fields = $this->get_available_fields( $post_id );
		$stats  = [
			'total_groups'       => 0,
			'total_fields'       => 0,
			'cloneable_fields'   => 0,
			'repeater_fields'    => 0,
			'group_fields'       => 0,
			'fields_with_values' => 0,
		];

		foreach ( $fields as $group ) {
			++$stats['total_groups'];

			foreach ( $group['fields'] as $field ) {
				++$stats['total_fields'];

				if ( $field['is_cloneable'] ) {
					++$stats['cloneable_fields'];
				}

				if ( $field['has_value'] ) {
					++$stats['fields_with_values'];
				}

				switch ( $field['type'] ) {
					case 'repeater':
						++$stats['repeater_fields'];
						break;
					case 'group':
						++$stats['group_fields'];
						break;
				}
			}
		}

		return $stats;
	}
}
