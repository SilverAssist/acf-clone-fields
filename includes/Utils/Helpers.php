<?php
/**
 * Plugin Helpers
 *
 * Collection of utility functions for the Silver Assist ACF Clone Fields plugin.
 * Provides common functionality used throughout the plugin.
 *
 * @package SilverAssist\ACFCloneFields
 * @subpackage Utils
 * @since 1.0.0
 * @version 1.0.0
 * @author Silver Assist
 */

namespace SilverAssist\ACFCloneFields\Utils;

use SilverAssist\ACFCloneFields\Core\Interfaces\LoadableInterface;

defined( 'ABSPATH' ) || exit;

/**
 * Class Helpers
 *
 * Static utility methods for common plugin operations.
 */
class Helpers implements LoadableInterface {
	/**
	 * Singleton instance
	 *
	 * @var Helpers|null
	 */
	private static ?Helpers $instance = null;

	/**
	 * Get singleton instance
	 *
	 * @return Helpers
	 */
	public static function instance(): Helpers {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Initialize the helpers
	 *
	 * @return void
	 */
	public function init(): void {
		// No initialization needed for static helpers.
	}

	/**
	 * Get loading priority
	 *
	 * @return int
	 */
	public function get_priority(): int {
		return 40; // Utils load last.
	}

	/**
	 * Determine if helpers should load
	 *
	 * @return bool
	 */
	public function should_load(): bool {
		return true; // Always load helpers.
	}

	/**
	 * Get enabled post types for field cloning
	 *
	 * @return array<string>
	 */
	public static function get_enabled_post_types(): array {
		$settings      = get_option( 'silver_acf_clone_settings', [] );
		$default_types = [ 'post', 'page' ];

		return $settings['enabled_post_types'] ?? $default_types;
	}

	/**
	 * Check if post type is enabled for cloning
	 *
	 * @param string $post_type Post type to check.
	 * @return bool
	 */
	public static function is_post_type_enabled( string $post_type ): bool {
		$enabled_types = self::get_enabled_post_types();
		return in_array( $post_type, $enabled_types, true );
	}

	/**
	 * Get posts of same type for source selection
	 *
	 * @param int    $current_post_id Current post ID to exclude.
	 * @param string $post_type Post type to query.
	 * @return array<\WP_Post>
	 */
	public static function get_source_posts( int $current_post_id, string $post_type ): array {
		$query_args = [
			'post_type'      => $post_type,
			'post_status'    => 'publish',
			'posts_per_page' => 100, // Reasonable limit.
			'post__not_in'   => [ $current_post_id ],
			'orderby'        => 'title',
			'order'          => 'ASC',
			'meta_query'     => [
				'relation' => 'OR',
				[
					'key'     => '_acf_fields_exists',
					'value'   => '1',
					'compare' => '=',
				],
				[
					'key'     => '_',
					'value'   => '',
					'compare' => 'LIKE',
				],
			],
		];

		$posts = get_posts( $query_args );

		// Filter posts that actually have ACF fields.
		return array_filter(
			$posts,
			function ( $post ) {
				return self::post_has_acf_fields( $post->ID );
			}
		);
	}

	/**
	 * Check if post has ACF fields
	 *
	 * @param int $post_id Post ID to check.
	 * @return bool
	 */
	public static function post_has_acf_fields( int $post_id ): bool {
		if ( ! function_exists( 'get_fields' ) ) {
			return false;
		}

		$fields = get_fields( $post_id );
		return ! empty( $fields );
	}

	/**
	 * Sanitize field key
	 *
	 * @param string $field_key Field key to sanitize.
	 * @return string
	 */
	public static function sanitize_field_key( string $field_key ): string {
		return sanitize_key( $field_key );
	}

	/**
	 * Validate field data before cloning
	 *
	 * @param mixed                $field_data Field data to validate.
	 * @param array<string, mixed> $field_config Field configuration.
	 * @return bool
	 */
	public static function validate_field_data( $field_data, array $field_config ): bool {
		// Basic validation based on field type.
		$field_type = $field_config['type'] ?? '';

		switch ( $field_type ) {
			case 'text':
			case 'textarea':
			case 'email':
			case 'url':
				return is_string( $field_data );

			case 'number':
				return is_numeric( $field_data );

			case 'select':
			case 'checkbox':
			case 'radio':
				$choices = $field_config['choices'] ?? [];
				if ( is_array( $field_data ) ) {
					return array_intersect( $field_data, array_keys( $choices ) ) === $field_data;
				}
				return array_key_exists( $field_data, $choices );

			case 'repeater':
				return is_array( $field_data );

			case 'group':
				return is_array( $field_data );

			case 'image':
			case 'file':
				return is_numeric( $field_data ) || is_array( $field_data );

			default:
				return true; // Allow unknown field types.
		}
	}

	/**
	 * Format field name for display
	 *
	 * @param string $field_name Field name.
	 * @return string
	 */
	public static function format_field_name( string $field_name ): string {
		// Convert underscores to spaces and capitalize words.
		$formatted = str_replace( '_', ' ', $field_name );
		return ucwords( $formatted );
	}

	/**
	 * Get field type icon class
	 *
	 * @param string $field_type ACF field type.
	 * @return string
	 */
	public static function get_field_type_icon( string $field_type ): string {
		$icons = [
			'text'         => 'dashicons-editor-textcolor',
			'textarea'     => 'dashicons-text',
			'number'       => 'dashicons-calculator',
			'email'        => 'dashicons-email',
			'url'          => 'dashicons-admin-links',
			'select'       => 'dashicons-menu',
			'checkbox'     => 'dashicons-yes',
			'radio'        => 'dashicons-marker',
			'repeater'     => 'dashicons-screenoptions',
			'group'        => 'dashicons-admin-generic',
			'image'        => 'dashicons-format-image',
			'file'         => 'dashicons-media-default',
			'date_picker'  => 'dashicons-calendar',
			'color_picker' => 'dashicons-art',
			'true_false'   => 'dashicons-yes-alt',
		];

		return $icons[ $field_type ] ?? 'dashicons-admin-settings';
	}

	/**
	 * Create nonce for AJAX requests
	 *
	 * @param string $action Nonce action.
	 * @return string
	 */
	public static function create_nonce( string $action = 'silver_acf_clone_nonce' ): string {
		return wp_create_nonce( $action );
	}

	/**
	 * Verify nonce for AJAX requests
	 *
	 * @param string $nonce Nonce value.
	 * @param string $action Nonce action.
	 * @return bool
	 */
	public static function verify_nonce( string $nonce, string $action = 'silver_acf_clone_nonce' ): bool {
		return wp_verify_nonce( $nonce, $action ) !== false;
	}

	/**
	 * Check if user can clone fields
	 *
	 * @param int $post_id Post ID to check permissions for.
	 * @return bool
	 */
	public static function user_can_clone_fields( int $post_id = 0 ): bool {
		if ( $post_id > 0 ) {
			return current_user_can( 'edit_post', $post_id );
		}

		return current_user_can( 'edit_posts' );
	}

	/**
	 * Log plugin activity (if logging enabled)
	 *
	 * @param string               $message Log message.
	 * @param string               $level Log level (error, warning, info, debug).
	 * @param array<string, mixed> $context Additional context.
	 * @return void
	 */
	public static function log( string $message, string $level = 'info', array $context = [] ): void {
		$settings        = get_option( 'silver_acf_clone_settings', [] );
		$logging_enabled = $settings['logging_enabled'] ?? false;

		if ( ! $logging_enabled ) {
			return;
		}

		// Use Logger class if available, otherwise fallback to error_log.
		if ( class_exists( 'SilverAssist\\ACFCloneFields\\Utils\\Logger' ) ) {
			Logger::instance()->log( $level, $message, $context );
		} else {
			$log_entry = sprintf(
				'[%s] Silver ACF Clone: %s %s',
				gmdate( 'Y-m-d H:i:s' ),
				strtoupper( $level ),
				$message
			);

			if ( ! empty( $context ) ) {
				$log_entry .= ' Context: ' . wp_json_encode( $context );
			}

			// Log entry can be output here if needed..
		}
	}

	/**
	 * Get plugin cache key
	 *
	 * @param string       $key Base cache key.
	 * @param array<mixed> $params Additional parameters for key.
	 * @return string
	 */
	public static function get_cache_key( string $key, array $params = [] ): string {
		$cache_key = 'silver_acf_clone_' . $key;

		if ( ! empty( $params ) ) {
			$cache_key .= '_' . md5( // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.serialize_serialize -- Serialization required for data storage
				serialize( $params )
			);
		}

		return $cache_key;
	}

	/**
	 * Clear plugin cache
	 *
	 * @param string|null $key Specific cache key to clear, null for all.
	 * @return void
	 */
	public static function clear_cache( ?string $key = null ): void {
		if ( null !== $key ) {
			wp_cache_delete( self::get_cache_key( $key ), 'silver_acf_clone' );
		} else {
			wp_cache_flush();
		}
	}

	/**
	 * Check if user can edit specific post
	 *
	 * @param int      $post_id Post ID to check.
	 * @param int|null $user_id User ID, null for current user.
	 * @return bool
	 */
	public static function can_user_edit_post( int $post_id, ?int $user_id = null ): bool {
		if ( null === $user_id ) {
			$user_id = get_current_user_id();
		}

		if ( ! $user_id ) {
			return false;
		}

		$post = get_post( $post_id );
		if ( ! $post instanceof \WP_Post ) {
			return false;
		}

		// Check basic edit capability for post type.
		$post_type_object = get_post_type_object( $post->post_type );
		if ( ! $post_type_object ) {
			return false;
		}

		// Check if user can edit posts of this type.
		if ( ! user_can( $user_id, $post_type_object->cap->edit_posts ) ) {
			return false;
		}

		// Check if user can edit this specific post.
		if ( ! user_can( $user_id, $post_type_object->cap->edit_post, $post_id ) ) {
			return false;
		}

		// Additional check for published posts.
		if ( 'publish' === $post->post_status && ! user_can( $user_id, $post_type_object->cap->edit_published_posts ) ) {
			return false;
		}

		// Check if user owns the post or can edit others' posts.
		if ( (int) $post->post_author !== $user_id && ! user_can( $user_id, $post_type_object->cap->edit_others_posts ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Get posts by type with enhanced filtering
	 *
	 * @param string               $post_type Post type to query.
	 * @param array<string, mixed> $args Additional query arguments.
	 * @return array<\WP_Post>
	 */
	public static function get_posts_by_type( string $post_type, array $args = [] ): array {
		$default_args = [
			'post_type'              => $post_type,
			'post_status'            => 'publish',
			'posts_per_page'         => 100,
			'orderby'                => 'title',
			'order'                  => 'ASC',
			'no_found_rows'          => true,
			'update_post_meta_cache' => false,
			'update_post_term_cache' => false,
		];

		$query_args = array_merge( $default_args, $args );

		// Filter by user permissions if not admin.
		if ( ! current_user_can( 'edit_others_posts' ) ) {
			$query_args['author'] = get_current_user_id();
		}

		$posts = get_posts( $query_args );

		// Ensure we have WP_Post objects (not IDs) and filter by permissions.
		$wp_posts = [];
		foreach ( $posts as $post ) {
			if ( $post instanceof \WP_Post && current_user_can( 'edit_post', $post->ID ) ) {
				$wp_posts[] = $post;
			}
		}

		return $wp_posts;
	}
}
