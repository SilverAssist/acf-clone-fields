<?php
/**
 * Ajax Handler Component
 *
 * Handles AJAX requests for field cloning operations and modal interactions.
 * Provides secure endpoints for getting posts, fields, and executing clones.
 *
 * @package SilverAssist\ACFCloneFields
 * @subpackage Admin
 * @since 1.0.0
 * @version 1.1.0
 * @author Silver Assist
 */

namespace SilverAssist\ACFCloneFields\Admin;

use SilverAssist\ACFCloneFields\Core\Interfaces\LoadableInterface;
use SilverAssist\ACFCloneFields\Services\FieldDetector;
use SilverAssist\ACFCloneFields\Services\FieldCloner;
use SilverAssist\ACFCloneFields\Utils\Helpers;
use SilverAssist\ACFCloneFields\Utils\Logger;

defined( 'ABSPATH' ) || exit;

/**
 * Class Ajax
 *
 * Manages AJAX endpoints for field cloning functionality.
 */
class Ajax implements LoadableInterface {
	/**
	 * Singleton instance
	 *
	 * @var Ajax|null
	 */
	private static ?Ajax $instance = null;



	/**
	 * Get singleton instance
	 *
	 * @return Ajax
	 */
	public static function instance(): Ajax {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Initialize AJAX functionality
	 *
	 * @return void
	 */
	public function init(): void {
		$this->register_ajax_handlers();
	}

	/**
	 * Get loading priority
	 *
	 * @return int
	 */
	public function get_priority(): int {
		return 40; // Admin components.
	}

	/**
	 * Determine if AJAX handler should load
	 *
	 * @return bool
	 */
	public function should_load(): bool {
		return is_admin();
	}

	/**
	 * Register AJAX handlers
	 *
	 * @return void
	 */
	private function register_ajax_handlers(): void {
		\add_action( 'wp_ajax_acf_clone_get_source_posts', [ $this, 'handle_get_source_posts' ] );
		\add_action( 'wp_ajax_acf_clone_get_source_fields', [ $this, 'handle_get_source_fields' ] );
		\add_action( 'wp_ajax_acf_clone_execute_clone', [ $this, 'handle_execute_clone' ] );
		\add_action( 'wp_ajax_acf_clone_validate_selection', [ $this, 'handle_validate_selection' ] );
	}

	/**
	 * Handle get source posts AJAX request
	 *
	 * @return void
	 */
	public function handle_get_source_posts(): void {
		// Verify nonce.
		if ( ! $this->verify_ajax_nonce() ) {
			wp_send_json_error( 'Invalid nonce' );
		}

		// Get and validate parameters.
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verified in verify_ajax_nonce().
		$post_type = sanitize_text_field( $_POST['post_type'] ?? '' );
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verified in verify_ajax_nonce().
		$current_post_id = (int) ( $_POST['post_id'] ?? 0 );

		if ( ! $post_type || ! $current_post_id ) {
			wp_send_json_error( 'Missing required parameters' );
		}

		// Check permissions.
		if ( ! current_user_can( 'edit_post', $current_post_id ) ) {
			wp_send_json_error( 'Insufficient permissions' );
		}

		try {
			// Get source posts.
			$args = [
				'exclude'        => [ $current_post_id ],
				'posts_per_page' => get_option( 'silver_assist_acf_clone_fields_max_source_posts', 50 ),
				'post_status'    => [ 'publish', 'draft', 'pending' ],
				'orderby'        => 'modified',
				'order'          => 'DESC',
			];

			$source_posts    = Helpers::get_posts_by_type( $post_type, $args );
			$formatted_posts = $this->format_posts_for_response( $source_posts );

			wp_send_json_success(
				[
					'posts' => $formatted_posts,
					'total' => count( $formatted_posts ),
				]
			);

		} catch ( \Exception $e ) {
			Logger::instance()->error(
				'Failed to get source posts',
				[
					'error'           => $e->getMessage(),
					'post_type'       => $post_type,
					'current_post_id' => $current_post_id,
				]
			);

			wp_send_json_error( 'Failed to load source posts' );
		}
	}

	/**
	 * Handle get source fields AJAX request
	 *
	 * @return void
	 */
	public function handle_get_source_fields(): void {
		// Verify nonce.
		if ( ! $this->verify_ajax_nonce() ) {
			wp_send_json_error( 'Invalid nonce' );
		}

		// Get and validate parameters.
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verified in verify_ajax_nonce().
		$source_post_id = (int) ( $_POST['source_post_id'] ?? 0 );
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verified in verify_ajax_nonce().
		$target_post_id = (int) ( $_POST['target_post_id'] ?? 0 );

		if ( ! $source_post_id || ! $target_post_id ) {
			wp_send_json_error( 'Missing required parameters' );
		}

		// Check permissions.
		if ( ! current_user_can( 'edit_post', $target_post_id ) ) {
			wp_send_json_error( 'Insufficient permissions' );
		}

		// Validate posts exist and are same type.
		$source_post = get_post( $source_post_id );
		$target_post = get_post( $target_post_id );

		if ( ! $source_post || ! $target_post ) {
			wp_send_json_error( 'Invalid post IDs' );
		}

		if ( $source_post->post_type !== $target_post->post_type ) {
			wp_send_json_error( 'Posts must be the same type' );
		}

		try {
			// Get field data.
			$source_fields = FieldDetector::instance()->get_available_fields( $source_post_id );
			$target_fields = FieldDetector::instance()->get_available_fields( $target_post_id );

			// Format for frontend.
			$formatted_fields = $this->format_fields_for_response( $source_fields, $target_fields );

			// Get statistics.
			$source_stats = FieldDetector::instance()->get_field_statistics( $source_post_id );
			$target_stats = FieldDetector::instance()->get_field_statistics( $target_post_id );

			wp_send_json_success(
				[
					'fields'      => $formatted_fields,
					'source_post' => [
						'id'    => $source_post_id,
						'title' => $source_post->post_title,
						'stats' => $source_stats,
					],
					'target_post' => [
						'id'    => $target_post_id,
						'title' => $target_post->post_title,
						'stats' => $target_stats,
					],
				]
			);

		} catch ( \Exception $e ) {
			Logger::instance()->error(
				'Failed to get source fields',
				[
					'error'          => $e->getMessage(),
					'source_post_id' => $source_post_id,
					'target_post_id' => $target_post_id,
				]
			);

			wp_send_json_error( 'Failed to load field data' );
		}
	}

	/**
	 * Handle execute clone AJAX request
	 *
	 * @return void
	 */
	public function handle_execute_clone(): void {
		// Verify nonce.
		if ( ! $this->verify_ajax_nonce() ) {
			wp_send_json_error( 'Invalid nonce' );
		}

		// Get and validate parameters.
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verified above via verify_ajax_nonce().
		$source_post_id = (int) ( $_POST['source_post_id'] ?? 0 );
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verified above via verify_ajax_nonce().
		$target_post_id = (int) ( $_POST['target_post_id'] ?? 0 );
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verified above via verify_ajax_nonce().
		$field_keys = $_POST['field_keys'] ?? [];
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verified above via verify_ajax_nonce().
		$options = $_POST['options'] ?? [];

		// Validate required parameters.
		if ( ! $source_post_id || ! $target_post_id || ! is_array( $field_keys ) || empty( $field_keys ) ) {
			wp_send_json_error( 'Missing or invalid parameters' );
		}

		// Sanitize field keys.
		$field_keys = array_map( 'sanitize_text_field', $field_keys );

		// Check permissions.
		if ( ! current_user_can( 'edit_post', $target_post_id ) ) {
			wp_send_json_error( 'Insufficient permissions' );
		}

		// Validate posts.
		$source_post = get_post( $source_post_id );
		$target_post = get_post( $target_post_id );

		if ( ! $source_post || ! $target_post ) {
			wp_send_json_error( 'Invalid post IDs' );
		}

		if ( $source_post->post_type !== $target_post->post_type ) {
			wp_send_json_error( 'Posts must be the same type' );
		}

		try {
			// Prepare cloning options.
			$clone_options = $this->prepare_clone_options( $options );

			// Execute clone operation.
			$clone_result = FieldCloner::instance()->clone_fields(
				$source_post_id,
				$target_post_id,
				$field_keys,
				$clone_options
			);

			// Log activity.
			$this->log_clone_activity( $target_post_id, $source_post_id, $clone_result );

			// Send response.
			wp_send_json_success(
				[
					'message'       => $clone_result['message'],
					'cloned_fields' => $clone_result['cloned_fields'],
					'errors'        => $clone_result['errors'],
					'warnings'      => $clone_result['warnings'],
					'success'       => $clone_result['success'],
				]
			);

		} catch ( \Exception $e ) {
			Logger::instance()->error(
				'Clone operation failed',
				[
					'error'          => $e->getMessage(),
					'source_post_id' => $source_post_id,
					'target_post_id' => $target_post_id,
					'field_keys'     => $field_keys,
				]
			);

			wp_send_json_error( 'Clone operation failed: ' . $e->getMessage() );
		}
	}

	/**
	 * Handle validate selection AJAX request
	 *
	 * @return void
	 */
	public function handle_validate_selection(): void {
		// Verify nonce.
		if ( ! $this->verify_ajax_nonce() ) {
			wp_send_json_error( 'Invalid nonce' );
		}

		// Get and validate parameters.
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verified above via verify_ajax_nonce().
		$source_post_id = (int) ( $_POST['source_post_id'] ?? 0 );
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verified above via verify_ajax_nonce().
		$target_post_id = (int) ( $_POST['target_post_id'] ?? 0 );
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verified above via verify_ajax_nonce().
		$field_keys = $_POST['field_keys'] ?? [];

		if ( ! $source_post_id || ! $target_post_id || ! is_array( $field_keys ) ) {
			wp_send_json_error( 'Missing or invalid parameters' );
		}

		// Sanitize field keys.
		$field_keys = array_map( 'sanitize_text_field', $field_keys );

		// Check permissions.
		if ( ! current_user_can( 'edit_post', $target_post_id ) ) {
			wp_send_json_error( 'Insufficient permissions' );
		}

		try {
			// Validate selection.
			$validation_result = $this->validate_field_selection( $source_post_id, $target_post_id, $field_keys );

			wp_send_json_success( $validation_result );

		} catch ( \Exception $e ) {
			wp_send_json_error( 'Validation failed: ' . $e->getMessage() );
		}
	}

	/**
	 * Verify AJAX nonce
	 *
	 * @return bool True if valid
	 */
	private function verify_ajax_nonce(): bool {
		$nonce = $_POST['nonce'] ?? '';
		return (bool) wp_verify_nonce( $nonce, 'silver_assist_acf_clone_fields_ajax' );
	}

	/**
	 * Format posts for AJAX response
	 *
	 * @param array<\WP_Post> $posts Posts to format.
	 * @return array<array<string, mixed>> Formatted posts
	 */
	private function format_posts_for_response( array $posts ): array {
		$formatted = [];

		foreach ( $posts as $post ) {
			$field_stats = FieldDetector::instance()->get_field_statistics( $post->ID );

			$formatted[] = [
				'id'           => $post->ID,
				'title'        => $post->post_title,
				'status'       => $post->post_status,
				'modified'     => get_post_modified_time( 'Y-m-d H:i:s', false, $post->ID ),
				'modified_ago' => human_time_diff( (int) get_post_modified_time( 'U', false, $post->ID ), time() ) . ' ago',
				'field_count'  => $field_stats['total_fields'] ?? 0,
				'field_stats'  => $field_stats,
				'edit_url'     => get_edit_post_link( $post->ID ),
				'preview_url'  => get_permalink( $post->ID ),
			];
		}

		return $formatted;
	}

	/**
	 * Format fields for AJAX response
	 *
	 * @param array<string, mixed> $source_fields Source fields.
	 * @param array<string, mixed> $target_fields Target fields.
	 * @return list<array<string, mixed>> Formatted fields
	 */
	private function format_fields_for_response( array $source_fields, array $target_fields ): array {
		$formatted = [];

		foreach ( $source_fields as $group_key => $group_data ) {
			$formatted_group = [
				'key'    => $group_key,
				'title'  => $group_data['title'],
				'fields' => [],
			];

			foreach ( $group_data['fields'] as $field_key => $field_data ) {
				// Check if target has this field and has value.
				$target_field_data = $target_fields[ $group_key ]['fields'][ $field_key ] ?? null;
				$target_has_value  = $target_field_data ? $target_field_data['has_value'] : false;

				$formatted_field = [
					'key'              => $field_key,
					'name'             => $field_data['name'],
					'label'            => $field_data['label'],
					'type'             => $field_data['type'],
					'has_value'        => $field_data['has_value'],
					'target_has_value' => $target_has_value,
					'is_cloneable'     => $field_data['is_cloneable'],
					'preview'          => $this->get_field_preview( $field_data ),
					'conflict_warning' => $target_has_value,
				];

				// Add type-specific data.
				$formatted_field = $this->add_type_specific_data( $formatted_field, $field_data );

				if ( $field_data['is_cloneable'] ) {
					$formatted_group['fields'][] = $formatted_field;
				}
			}

			if ( ! empty( $formatted_group['fields'] ) ) {
				$formatted[] = $formatted_group;
			}
		}

		return $formatted;
	}

	/**
	 * Add type-specific data to field
	 *
	 * @param array<string, mixed> $formatted_field Formatted field.
	 * @param array<string, mixed> $field_data Raw field data.
	 * @return array<string, mixed> Field with type-specific data
	 */
	private function add_type_specific_data( array $formatted_field, array $field_data ): array {
		switch ( $field_data['type'] ) {
			case 'repeater':
				$formatted_field['row_count']        = $field_data['row_count'] ?? 0;
				$formatted_field['sub_fields_count'] = count( $field_data['sub_fields'] ?? [] );
				break;

			case 'group':
				$formatted_field['sub_fields_count'] = count( $field_data['sub_fields'] ?? [] );
				break;

			case 'flexible_content':
				$formatted_field['layouts_count'] = count( $field_data['layouts'] ?? [] );
				break;

			case 'image':
			case 'file':
				if ( isset( $field_data['attachment_info'] ) ) {
					$formatted_field['attachment_info'] = $field_data['attachment_info'];
				}
				break;
		}

		return $formatted_field;
	}

	/**
	 * Get field preview for display
	 *
	 * @param array<string, mixed> $field_data Field data.
	 * @return string Field preview
	 */
	private function get_field_preview( array $field_data ): string {
		if ( ! $field_data['has_value'] ) {
			return __( '(empty)', 'silver-assist-acf-clone-fields' );
		}

		$value = $field_data['value'];
		$type  = $field_data['type'];

		switch ( $type ) {
			case 'text':
			case 'textarea':
			case 'email':
			case 'url':
				return is_string( $value ) ? wp_trim_words( wp_strip_all_tags( $value ), 8 ) : '';

			case 'number':
			case 'range':
				return (string) $value;

			case 'select':
			case 'radio':
			case 'button_group':
				return is_string( $value ) ? $value : '';

			case 'checkbox':
				if ( is_array( $value ) ) {
					return implode( ', ', array_slice( $value, 0, 3 ) ) . ( count( $value ) > 3 ? '...' : '' );
				}
				return '';

			case 'true_false':
				return $value ? __( 'Yes', 'silver-assist-acf-clone-fields' ) : __( 'No', 'silver-assist-acf-clone-fields' );

			case 'image':
				return isset( $field_data['attachment_info']['title'] )
					? $field_data['attachment_info']['title']
					: __( 'Image', 'silver-assist-acf-clone-fields' );

			case 'file':
				return isset( $field_data['attachment_info']['filename'] )
					? $field_data['attachment_info']['filename']
					: __( 'File', 'silver-assist-acf-clone-fields' );

			case 'repeater':
				$count = $field_data['row_count'] ?? 0;
				return sprintf(
					/* translators: %d: number of rows */
					_n( '%d row', '%d rows', $count, 'silver-assist-acf-clone-fields' ),
					$count
				);

			case 'group':
				$count = count( $field_data['sub_fields'] ?? [] );
				return sprintf(
					/* translators: %d: number of fields */
					_n( '%d field', '%d fields', $count, 'silver-assist-acf-clone-fields' ),
					$count
				);

			case 'flexible_content':
				$count = count( $field_data['layouts'] ?? [] );
				return sprintf(
					/* translators: %d: number of layouts */
					_n( '%d layout', '%d layouts', $count, 'silver-assist-acf-clone-fields' ),
					$count
				);

			default:
				return __( 'Has value', 'silver-assist-acf-clone-fields' );
		}
	}

	/**
	 * Prepare clone options from request
	 *
	 * @param array<string, mixed> $request_options Raw options.
	 * @return array<string, mixed> Prepared options
	 */
	private function prepare_clone_options( array $request_options ): array {
		$default_options = [
			'overwrite_existing' => get_option( 'silver_assist_acf_clone_fields_default_overwrite', false ),
			'create_backup'      => get_option( 'silver_assist_acf_clone_fields_create_backup', true ),
			'copy_attachments'   => get_option( 'silver_assist_acf_clone_fields_copy_attachments', true ),
			'validate_data'      => get_option( 'silver_assist_acf_clone_fields_validate_data', true ),
		];

		// Override with request options.
		foreach ( $request_options as $key => $value ) {
			if ( array_key_exists( $key, $default_options ) ) {
				// Handle boolean conversion - catch string 'false' and '0'.
				if ( is_string( $value ) ) {
					$value = filter_var( $value, FILTER_VALIDATE_BOOLEAN );
				} else {
					$value = (bool) $value;
				}
				$default_options[ $key ] = $value;
			}
		}

		return $default_options;
	}

	/**
	 * Validate field selection
	 *
	 * @param int           $source_post_id Source post ID.
	 * @param int           $target_post_id Target post ID.
	 * @param array<string> $field_keys Field keys to validate.
	 * @return array<string, mixed> Validation result
	 */
	private function validate_field_selection( int $source_post_id, int $target_post_id, array $field_keys ): array {
		$source_fields = FieldDetector::instance()->get_available_fields( $source_post_id );
		$target_fields = FieldDetector::instance()->get_available_fields( $target_post_id );

		$conflicts    = [];
		$warnings     = [];
		$valid_fields = [];

		foreach ( $field_keys as $field_key ) {
			// Find field in source.
			$source_field = $this->find_field_in_groups( $source_fields, $field_key );
			if ( ! $source_field ) {
				$warnings[] = sprintf( 'Field %s not found in source post', $field_key );
				continue;
			}

			// Check if cloneable.
			if ( ! $source_field['is_cloneable'] ) {
				$warnings[] = sprintf( 'Field %s (%s) is not cloneable', $source_field['label'], $field_key );
				continue;
			}

			// Check for conflicts in target.
			$target_field = $this->find_field_in_groups( $target_fields, $field_key );
			if ( $target_field && $target_field['has_value'] ) {
				$conflicts[] = [
					'field_key'   => $field_key,
					'field_label' => $source_field['label'],
					'field_type'  => $source_field['type'],
				];
			}

			$valid_fields[] = $field_key;
		}

		return [
			'valid_fields'  => $valid_fields,
			'conflicts'     => $conflicts,
			'warnings'      => $warnings,
			'has_conflicts' => ! empty( $conflicts ),
			'can_proceed'   => ! empty( $valid_fields ),
		];
	}

	/**
	 * Find field in field groups
	 *
	 * @param array<string, mixed> $field_groups Field groups.
	 * @param string               $field_key Field key to find.
	 * @return array<string, mixed>|null Field data or null
	 */
	private function find_field_in_groups( array $field_groups, string $field_key ): ?array {
		foreach ( $field_groups as $group_data ) {
			if ( isset( $group_data['fields'][ $field_key ] ) ) {
				return $group_data['fields'][ $field_key ];
			}
		}
		return null;
	}

	/**
	 * Log clone activity to post meta
	 *
	 * @param int                  $target_post_id Target post ID.
	 * @param int                  $source_post_id Source post ID.
	 * @param array<string, mixed> $clone_result Clone result.
	 * @return void
	 */
	private function log_clone_activity( int $target_post_id, int $source_post_id, array $clone_result ): void {
		$activity = get_post_meta( $target_post_id, '_acf_clone_activity', true );
		if ( ! is_array( $activity ) ) {
			$activity = [];
		}

		$source_post  = get_post( $source_post_id );
		$source_title = $source_post ? $source_post->post_title : "Post #{$source_post_id}";

		$activity[] = [
			'time'           => current_time( 'Y-m-d H:i:s' ),
			'timestamp'      => time(),
			'source_post_id' => $source_post_id,
			'source_title'   => $source_title,
			'fields_cloned'  => count( $clone_result['cloned_fields'] ),
			'success'        => $clone_result['success'],
			'user_id'        => get_current_user_id(),
		];

		// Keep only last 10 activities.
		$activity = array_slice( $activity, -10, 10 );

		update_post_meta( $target_post_id, '_acf_clone_activity', $activity );
	}
}
