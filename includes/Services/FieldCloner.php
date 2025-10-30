<?php
/**
 * Field Cloner Service
 *
 * Handles the actual cloning of ACF fields between posts.
 * Provides safe field copying with validation and rollback capabilities.
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
use SilverAssist\ACFCloneFields\Utils\Logger;

defined( 'ABSPATH' ) || exit;

/**
 * Class FieldCloner
 *
 * Performs the actual cloning operations between posts.
 */
class FieldCloner implements LoadableInterface {
	/**
	 * Singleton instance
	 *
	 * @var FieldCloner|null
	 */
	private static ?FieldCloner $instance = null;



	/**
	 * Get singleton instance
	 *
	 * @return FieldCloner
	 */
	public static function instance(): FieldCloner {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Initialize the field cloner
	 *
	 * @return void
	 */
	public function init(): void {
		// Actions for logging clone operations.
		add_action( 'silver_assist_acf_clone_fields_before_clone', $this->log_clone_operation( ... ), 10, 3 );
		add_action( 'silver_assist_acf_clone_fields_after_clone', $this->clear_clone_cache( ... ), 10, 1 );
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
	 * Determine if field cloner should load
	 *
	 * @return bool
	 */
	public function should_load(): bool {
		return function_exists( 'update_field' ) && function_exists( 'get_field' );
	}

	/**
	 * Clone selected fields from source post to target post
	 *
	 * @param int                  $source_post_id Source post ID.
	 * @param int                  $target_post_id Target post ID.
	 * @param array<string>        $field_keys Array of field keys to clone.
	 * @param array<string, mixed> $options Cloning options.
	 * @return array<string, mixed> Result of cloning operation
	 */
	public function clone_fields( int $source_post_id, int $target_post_id, array $field_keys, array $options = [] ): array {
		// Validate inputs.
		$validation_result = $this->validate_clone_request( $source_post_id, $target_post_id, $field_keys );
		if ( ! $validation_result['valid'] ) {
			return [
				'success'       => false,
				'message'       => $validation_result['message'],
				'cloned_fields' => [],
				'errors'        => [ $validation_result['message'] ],
			];
		}

		// Default options.
		$default_options = [
			'overwrite_existing' => false,
			'create_backup'      => true,
			'copy_attachments'   => true,
			'validate_data'      => true,
		];
		$options         = array_merge( $default_options, $options );

		// Create backup if requested.
		if ( $options['create_backup'] ) {
			$this->create_backup( $target_post_id, $field_keys );
		}

		$result = [
			'success'       => true,
			'message'       => '',
			'cloned_fields' => [],
			'errors'        => [],
			'warnings'      => [],
		];

		// Fire before clone action.
		do_action( 'silver_assist_acf_clone_fields_before_clone', $source_post_id, $target_post_id, $field_keys, $options );

		// Process each field.
		foreach ( $field_keys as $field_key ) {
			$clone_result = $this->clone_single_field( $source_post_id, $target_post_id, $field_key, $options );

			if ( $clone_result['success'] ) {
				$result['cloned_fields'][] = $field_key;
			} else {
				$result['errors'][] = $clone_result['message'];
			}

			if ( ! empty( $clone_result['warnings'] ) ) {
				$result['warnings'] = array_merge( $result['warnings'], $clone_result['warnings'] );
			}
		}

		// Determine overall success.
		$result['success'] = empty( $result['errors'] );
		$result['message'] = $this->generate_result_message( $result );

		// Fire after clone action.
		do_action( 'silver_assist_acf_clone_fields_after_clone', $target_post_id, $result );

		// Log operation.
		$this->log_clone_result( $source_post_id, $target_post_id, $result );

		return $result;
	}

	/**
	 * Clone a single field between posts
	 *
	 * @param int                  $source_post_id Source post ID.
	 * @param int                  $target_post_id Target post ID.
	 * @param string               $field_key Field key to clone.
	 * @param array<string, mixed> $options Cloning options.
	 * @return array<string, mixed> Clone result
	 */
	private function clone_single_field( int $source_post_id, int $target_post_id, string $field_key, array $options ): array {
		// Get source field value.
		$source_value = get_field( $field_key, $source_post_id, false );

		if ( $source_value === false || null === $source_value ) {
			return [
				'success'  => false,
				'message'  => sprintf( 'Field %s not found in source post', $field_key ),
				'warnings' => [],
			];
		}

		// Get field configuration.
		$field_object = get_field_object( $field_key, $source_post_id );
		if ( ! $field_object ) {
			return [
				'success'  => false,
				'message'  => sprintf( 'Field configuration not found for %s', $field_key ),
				'warnings' => [],
			];
		}

		$warnings = [];

		// Check if target field exists and handle overwrite logic.
		$existing_value = get_field( $field_key, $target_post_id, false );
		if ( false !== $existing_value && null !== $existing_value && ! $options['overwrite_existing'] ) {
			return [
				'success'  => false,
				'message'  => sprintf( 'Field %s already has a value and overwrite is disabled', $field_object['label'] ),
				'warnings' => [],
			];
		}

		// Process field value based on type.
		$processed_value = $this->process_field_value( $source_value, $field_object, $options, $warnings );

		// Validate processed value.
		if ( $options['validate_data'] && ! $this->validate_field_value( $processed_value, $field_object ) ) {
			return [
				'success'  => false,
				'message'  => sprintf( 'Validation failed for field %s', $field_object['label'] ),
				'warnings' => $warnings,
			];
		}

		// Update the field.
		$update_result = update_field( $field_key, $processed_value, $target_post_id );

		if ( ! $update_result ) {
			return [
				'success'  => false,
				'message'  => sprintf( 'Failed to update field %s', $field_object['label'] ),
				'warnings' => $warnings,
			];
		}

		return [
			'success'  => true,
			'message'  => sprintf( 'Successfully cloned field %s', $field_object['label'] ),
			'warnings' => $warnings,
		];
	}

	/**
	 * Process field value based on field type
	 *
	 * @param mixed                $value Field value to process.
	 * @param array<string, mixed> $field_object Field configuration.
	 * @param array<string, mixed> $options Processing options.
	 * @param array<string>        &$warnings Reference to warnings array.
	 * @return mixed Processed value
	 */
	private function process_field_value( $value, array $field_object, array $options, array &$warnings ) {
		$field_type = $field_object['type'] ?? 'text';

		switch ( $field_type ) {
			case 'image':
			case 'file':
				return $this->process_attachment_field( $value, $options, $warnings );

			case 'repeater':
				return $this->process_repeater_field( $value, $field_object, $options, $warnings );

			case 'group':
				return $this->process_group_field( $value, $field_object, $options, $warnings );

			case 'flexible_content':
				return $this->process_flexible_content_field( $value, $field_object, $options, $warnings );

			case 'relationship':
			case 'post_object':
				return $this->process_post_reference_field( $value, $warnings );

			case 'taxonomy':
				return $this->process_taxonomy_field( $value, $field_object, $warnings );

			case 'user':
				return $this->process_user_field( $value, $warnings );

			default:
				return $value; // Return as-is for simple field types.
		}
	}

	/**
	 * Process attachment field (image/file)
	 *
	 * @param mixed                $value Attachment value.
	 * @param array<string, mixed> $options Processing options.
	 * @param array<string>        &$warnings Reference to warnings array.
	 * @return mixed Processed attachment value
	 */
	private function process_attachment_field( $value, array $options, array &$warnings ) {
		if ( ! $options['copy_attachments'] ) {
			return $value; // Return original attachment reference.
		}

		// Handle single attachment.
		if ( is_numeric( $value ) ) {
			$attachment_id = (int) $value;
			if ( get_post_type( $attachment_id ) === 'attachment' ) {
				return $attachment_id; // Attachment exists, use reference.
			} else {
				$warnings[] = "Attachment ID {$attachment_id} not found";
				return null;
			}
		}

		// Handle attachment array format.
		if ( is_array( $value ) && isset( $value['ID'] ) ) {
			$attachment_id = (int) $value['ID'];
			if ( get_post_type( $attachment_id ) === 'attachment' ) {
				return $value; // Return full attachment array.
			} else {
				$warnings[] = "Attachment ID {$attachment_id} not found";
				return null;
			}
		}

		return $value;
	}

	/**
	 * Process repeater field
	 *
	 * @param mixed                $value Repeater value.
	 * @param array<string, mixed> $field_object Field configuration.
	 * @param array<string, mixed> $options Processing options.
	 * @param array<string>        &$warnings Reference to warnings array.
	 * @return array<mixed> Processed repeater value
	 */
	private function process_repeater_field( $value, array $field_object, array $options, array &$warnings ): array {
		if ( ! is_array( $value ) ) {
			return [];
		}

		$processed_rows = [];
		$sub_fields     = $field_object['sub_fields'] ?? [];

		foreach ( $value as $row_index => $row_data ) {
			if ( ! is_array( $row_data ) ) {
				continue;
			}

			$processed_row = [];

			foreach ( $row_data as $sub_field_name => $sub_value ) {
				// Find sub-field configuration.
				$sub_field_object = null;
				foreach ( $sub_fields as $sub_field ) {
					if ( $sub_field['name'] === $sub_field_name ) {
						$sub_field_object = $sub_field;
						break;
					}
				}

				if ( $sub_field_object ) {
					$processed_row[ $sub_field_name ] = $this->process_field_value( $sub_value, $sub_field_object, $options, $warnings );
				} else {
					$processed_row[ $sub_field_name ] = $sub_value;
				}
			}

			$processed_rows[] = $processed_row;
		}

		return $processed_rows;
	}

	/**
	 * Process group field
	 *
	 * @param mixed                $value Group value.
	 * @param array<string, mixed> $field_object Field configuration.
	 * @param array<string, mixed> $options Processing options.
	 * @param array<string>        &$warnings Reference to warnings array.
	 * @return array<string, mixed> Processed group value
	 */
	private function process_group_field( $value, array $field_object, array $options, array &$warnings ): array {
		if ( ! is_array( $value ) ) {
			return [];
		}

		$processed_group = [];
		$sub_fields      = $field_object['sub_fields'] ?? [];

		foreach ( $value as $sub_field_name => $sub_value ) {
			// Find sub-field configuration.
			$sub_field_object = null;
			foreach ( $sub_fields as $sub_field ) {
				if ( $sub_field['name'] === $sub_field_name ) {
					$sub_field_object = $sub_field;
					break;
				}
			}

			if ( $sub_field_object ) {
				$processed_group[ $sub_field_name ] = $this->process_field_value( $sub_value, $sub_field_object, $options, $warnings );
			} else {
				$processed_group[ $sub_field_name ] = $sub_value;
			}
		}

		return $processed_group;
	}

	/**
	 * Process flexible content field
	 *
	 * @param mixed                $value Flexible content value.
	 * @param array<string, mixed> $field_object Field configuration.
	 * @param array<string, mixed> $options Processing options.
	 * @param array<string>        &$warnings Reference to warnings array.
	 * @return array<mixed> Processed flexible content value
	 */
	private function process_flexible_content_field( $value, array $field_object, array $options, array &$warnings ): array {
		if ( ! is_array( $value ) ) {
			return [];
		}

		$processed_layouts = [];
		$layouts_config    = $field_object['layouts'] ?? [];

		foreach ( $value as $layout_index => $layout_data ) {
			if ( ! is_array( $layout_data ) || ! isset( $layout_data['acf_fc_layout'] ) ) {
				continue;
			}

			$layout_name      = $layout_data['acf_fc_layout'];
			$processed_layout = [ 'acf_fc_layout' => $layout_name ];

			// Find layout configuration.
			$layout_config = null;
			foreach ( $layouts_config as $config_layout ) {
				if ( $config_layout['name'] === $layout_name ) {
					$layout_config = $config_layout;
					break;
				}
			}

			if ( $layout_config && isset( $layout_config['sub_fields'] ) ) {
				foreach ( $layout_data as $field_name => $field_value ) {
					if ( $field_name === 'acf_fc_layout' ) {
						continue;
					}

					// Find sub-field configuration.
					$sub_field_object = null;
					foreach ( $layout_config['sub_fields'] as $sub_field ) {
						if ( $sub_field['name'] === $field_name ) {
							$sub_field_object = $sub_field;
							break;
						}
					}

					if ( $sub_field_object ) {
						$processed_layout[ $field_name ] = $this->process_field_value( $field_value, $sub_field_object, $options, $warnings );
					} else {
						$processed_layout[ $field_name ] = $field_value;
					}
				}
			} else {
				$processed_layout = $layout_data;
				$warnings[]       = "Layout configuration not found for: {$layout_name}";
			}

			$processed_layouts[] = $processed_layout;
		}

		return $processed_layouts;
	}

	/**
	 * Process post reference field (relationship/post_object)
	 *
	 * @param mixed         $value Post reference value.
	 * @param array<string> &$warnings Reference to warnings array.
	 * @return mixed Processed post reference value
	 */
	private function process_post_reference_field( $value, array &$warnings ) {
		// Validate post references exist.
		if ( is_array( $value ) ) {
			$validated_posts = [];
			foreach ( $value as $post_id ) {
				if ( is_numeric( $post_id ) && get_post( (int) $post_id ) ) {
					$validated_posts[] = (int) $post_id;
				} else {
					$warnings[] = "Referenced post ID {$post_id} not found";
				}
			}
			return $validated_posts;
		}

		if ( is_numeric( $value ) ) {
			$post_id = (int) $value;
			if ( get_post( $post_id ) ) {
				return $post_id;
			} else {
				$warnings[] = "Referenced post ID {$post_id} not found";
				return null;
			}
		}

		return $value;
	}

	/**
	 * Process taxonomy field
	 *
	 * @param mixed                $value Taxonomy value.
	 * @param array<string, mixed> $field_object Field configuration.
	 * @param array<string>        &$warnings Reference to warnings array.
	 * @return mixed Processed taxonomy value
	 */
	private function process_taxonomy_field( $value, array $field_object, array &$warnings ) {
		$taxonomy = $field_object['taxonomy'] ?? '';

		if ( ! $taxonomy || ! taxonomy_exists( $taxonomy ) ) {
			$warnings[] = "Taxonomy {$taxonomy} does not exist";
			return $value;
		}

		// Validate term references.
		if ( is_array( $value ) ) {
			$validated_terms = [];
			foreach ( $value as $term_id ) {
				if ( is_numeric( $term_id ) ) {
					$term = get_term( (int) $term_id, $taxonomy );
					if ( $term && ! is_wp_error( $term ) ) {
						$validated_terms[] = (int) $term_id;
					} else {
						$warnings[] = "Term ID {$term_id} not found in taxonomy {$taxonomy}";
					}
				}
			}
			return $validated_terms;
		}

		if ( is_numeric( $value ) ) {
			$term = get_term( (int) $value, $taxonomy );
			if ( $term && ! is_wp_error( $term ) ) {
				return (int) $value;
			} else {
				$warnings[] = "Term ID {$value} not found in taxonomy {$taxonomy}";
				return null;
			}
		}

		return $value;
	}

	/**
	 * Process user field
	 *
	 * @param mixed         $value User value.
	 * @param array<string> &$warnings Reference to warnings array.
	 * @return mixed Processed user value
	 */
	private function process_user_field( $value, array &$warnings ) {
		// Validate user references.
		if ( is_array( $value ) ) {
			$validated_users = [];
			foreach ( $value as $user_id ) {
				if ( is_numeric( $user_id ) && get_user_by( 'id', (int) $user_id ) ) {
					$validated_users[] = (int) $user_id;
				} else {
					$warnings[] = "User ID {$user_id} not found";
				}
			}
			return $validated_users;
		}

		if ( is_numeric( $value ) ) {
			$user_id = (int) $value;
			if ( get_user_by( 'id', $user_id ) ) {
				return $user_id;
			} else {
				$warnings[] = "User ID {$user_id} not found";
				return null;
			}
		}

		return $value;
	}

	/**
	 * Validate clone request
	 *
	 * @param int           $source_post_id Source post ID.
	 * @param int           $target_post_id Target post ID.
	 * @param array<string> $field_keys Field keys to clone.
	 * @return array<string, mixed> Validation result
	 */
	private function validate_clone_request( int $source_post_id, int $target_post_id, array $field_keys ): array {
		// Check if posts exist.
		$source_post = get_post( $source_post_id );
		$target_post = get_post( $target_post_id );

		if ( ! $source_post ) {
			return [
				'valid'   => false,
				'message' => 'Source post not found',
			];
		}

		if ( ! $target_post ) {
			return [
				'valid'   => false,
				'message' => 'Target post not found',
			];
		}

		// Check post types match.
		if ( $source_post->post_type !== $target_post->post_type ) {
			return [
				'valid'   => false,
				'message' => 'Source and target posts must be the same post type',
			];
		}

		// Check user permissions.
		if ( ! Helpers::can_user_edit_post( $target_post_id ) ) {
			return [
				'valid'   => false,
				'message' => 'You do not have permission to edit the target post',
			];
		}

		// Validate field keys.
		if ( empty( $field_keys ) ) {
			return [
				'valid'   => false,
				'message' => 'No field keys provided for cloning',
			];
		}

		return [
			'valid'   => true,
			'message' => 'Validation passed',
		];
	}

	/**
	 * Validate field value against field configuration
	 *
	 * @param mixed                $value Field value.
	 * @param array<string, mixed> $field_object Field configuration.
	 * @return bool True if valid
	 */
	private function validate_field_value( $value, array $field_object ): bool {
		$field_type = $field_object['type'] ?? '';
		$required   = $field_object['required'] ?? false;

		// Check required fields.
		if ( $required && ( null === $value || $value === '' || $value === [] ) ) {
			return false;
		}

		// Type-specific validation.
		switch ( $field_type ) {
			case 'email':
				return ! $value || is_email( $value );

			case 'url':
				return ! $value || filter_var( $value, FILTER_VALIDATE_URL ) !== false;

			case 'number':
				return ! $value || is_numeric( $value );

			case 'range':
				if ( $value && is_numeric( $value ) ) {
					$min       = $field_object['min'] ?? null;
					$max       = $field_object['max'] ?? null;
					$num_value = (float) $value;

					if ( null !== $min && $num_value < $min ) {
						return false;
					}
					if ( null !== $max && $num_value > $max ) {
						return false;
					}
				}
				return true;

			default:
				return true; // Basic validation passed.
		}
	}

	/**
	 * Create backup of existing field values
	 *
	 * @param int           $post_id Post ID to backup.
	 * @param array<string> $field_keys Field keys to backup.
	 * @return void
	 */
	private function create_backup( int $post_id, array $field_keys ): void {
		$backup = [];

		foreach ( $field_keys as $field_key ) {
			$existing_value = get_field( $field_key, $post_id, false );
			if ( $existing_value !== false ) {
				$backup[ $field_key ] = $existing_value;
			}
		}

		// Backup created but not stored (future enhancement).
	}

	/**
	 * Generate result message from clone operation
	 *
	 * @param array<string, mixed> $result Clone result.
	 * @return string Result message
	 */
	private function generate_result_message( array $result ): string {
		$cloned_count  = count( $result['cloned_fields'] );
		$error_count   = count( $result['errors'] );
		$warning_count = count( $result['warnings'] );

		if ( $error_count === 0 ) {
			$message = sprintf( 'Successfully cloned %d field(s)', $cloned_count );
			if ( $warning_count > 0 ) {
				$message .= sprintf( ' with %d warning(s)', $warning_count );
			}
		} else {
			$message = sprintf( 'Cloned %d field(s) with %d error(s)', $cloned_count, $error_count );
			if ( $warning_count > 0 ) {
				$message .= sprintf( ' and %d warning(s)', $warning_count );
			}
		}

		return $message;
	}

	/**
	 * Log clone operation
	 *
	 * @param mixed $source_post_id Source post ID.
	 * @param mixed $target_post_id Target post ID.
	 * @param mixed $field_keys Field keys being cloned.
	 * @return void
	 */
	public function log_clone_operation( $source_post_id, $target_post_id, $field_keys ): void {
		Logger::instance()->info(
			'ACF field clone operation started',
			[
				'source_post_id' => $source_post_id,
				'target_post_id' => $target_post_id,
				'field_keys'     => $field_keys,
				'user_id'        => get_current_user_id(),
			]
		);
	}

	/**
	 * Log clone result
	 *
	 * @param int                  $source_post_id Source post ID.
	 * @param int                  $target_post_id Target post ID.
	 * @param array<string, mixed> $result Clone result.
	 * @return void
	 */
	private function log_clone_result( int $source_post_id, int $target_post_id, array $result ): void {
		$log_level = $result['success'] ? 'info' : 'warning';

		Logger::instance()->{$log_level}(
			'ACF field clone operation completed',
			[
				'source_post_id'      => $source_post_id,
				'target_post_id'      => $target_post_id,
				'success'             => $result['success'],
				'cloned_fields_count' => count( $result['cloned_fields'] ),
				'errors_count'        => count( $result['errors'] ),
				'warnings_count'      => count( $result['warnings'] ),
				'message'             => $result['message'],
			]
		);
	}

	/**
	 * Clear cache after cloning operation
	 *
	 * @param int $post_id Target post ID.
	 * @return void
	 */
	public function clear_clone_cache( int $post_id ): void {
		Helpers::clear_cache( 'field_clone_' . $post_id );

		// Clear field detector cache.
		FieldDetector::instance()->clear_field_cache( $post_id );
	}
}
