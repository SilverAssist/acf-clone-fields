<?php
/**
 * MetaBox Component
 *
 * Adds a sidebar meta box to post edit screens for cloning ACF fields.
 * Provides the interface for selecting source posts and fields to clone.
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
use SilverAssist\ACFCloneFields\Utils\Helpers;
use SilverAssist\ACFCloneFields\Utils\Logger;

defined( 'ABSPATH' ) || exit;

/**
 * Class MetaBox
 *
 * Manages the clone fields meta box in post edit screens.
 */
class MetaBox implements LoadableInterface {
	/**
	 * Singleton instance
	 *
	 * @var MetaBox|null
	 */
	private static ?MetaBox $instance = null;

	/**
	 * Enabled post types for cloning
	 *
	 * @var array<string>
	 */
	private array $enabled_post_types = [];

	/**
	 * Get singleton instance
	 *
	 * @return MetaBox
	 */
	public static function instance(): MetaBox {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Initialization flag
	 *
	 * @var bool
	 */
	private bool $initialized = false;

	/**
	 * Private constructor to prevent direct instantiation
	 */
	private function __construct() {
		// Empty - initialization happens in init().
	}

	/**
	 * Initialize meta box functionality
	 *
	 * @return void
	 */
	public function init(): void {
		// Prevent multiple initialization.
		if ( $this->initialized ) {
			Logger::instance()->warning( 'MetaBox already initialized, skipping duplicate init' );
			return;
		}

		$this->load_settings();
		$this->init_hooks();
		$this->initialized = true;
		Logger::instance()->info( 'MetaBox initialization complete' );
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
	 * Determine if meta box should load
	 *
	 * @return bool
	 */
	public function should_load(): bool {
		return is_admin() && function_exists( 'add_meta_box' );
	}

	/**
	 * Load plugin settings
	 *
	 * @return void
	 */
	private function load_settings(): void {
		$this->enabled_post_types = get_option( 'silver_assist_acf_clone_fields_enabled_post_types', [ 'post', 'page' ] );
	}

	/**
	 * Initialize WordPress hooks
	 *
	 * @return void
	 */
	private function init_hooks(): void {
		// CRITICAL FIX: Prevent duplicate hook registration across all instances.
		static $enqueue_hook_registered = false;

		// Add meta boxes (safe to register multiple times).
		add_action( 'add_meta_boxes', [ $this, 'add_meta_boxes' ] );

		// Ensure metabox shows in Gutenberg (safe to register multiple times).
		add_filter( 'use_block_editor_for_post_type', [ $this, 'ensure_metabox_compatibility' ], 10, 2 );

		// CRITICAL: Use static method callback to prevent instance duplication issues.
		if ( ! $enqueue_hook_registered ) {
			add_action( 'admin_enqueue_scripts', [ self::class, 'static_enqueue_admin_assets' ] );
			$enqueue_hook_registered = true;
		}
	}

	/**
	 * Ensure metabox compatibility with Gutenberg.
	 *
	 * @param bool   $use_block_editor Whether to use block editor.
	 * @param string $post_type        The post type.
	 * @return bool
	 */
	public function ensure_metabox_compatibility( $use_block_editor, $post_type ): bool {
		// Allow both classic and block editor for our supported post types.
		return $use_block_editor;
	}

	/**
	 * Add meta boxes to post edit screen.
	 *
	 * @return void
	 */
	public function add_meta_boxes(): void {
		// Check if ACF is active.
		if ( ! function_exists( 'get_field' ) ) {
			Logger::instance()->error( 'ACF is not active - cannot add clone fields metabox' );
			return;
		}

		// Add meta boxes for enabled post types.
		foreach ( $this->enabled_post_types as $post_type ) {
			add_meta_box(
				'acf_clone_fields',
				__( 'Clone Custom Fields', 'silver-assist-acf-clone-fields' ),
				[ $this, 'render_meta_box' ],
				$post_type,
				'side',
				'high',
				[
					'__block_editor_compatible_meta_box' => true,
				]
			);
		}
	}   /**
		 * Render the clone fields meta box
		 *
		 * @param \WP_Post $post Current post object.
		 * @return void
		 */
	public function render_meta_box( \WP_Post $post ): void {
		// Check if ACF is available.
		if ( ! function_exists( 'get_field' ) ) {
			$this->render_acf_not_available();
			return;
		}

		// Check user permissions.
		if ( ! current_user_can( 'edit_post', $post->ID ) ) {
			$this->render_no_permissions();
			return;
		}

		// Get source posts count.
		$source_posts = $this->get_source_posts( $post );
		$source_count = count( $source_posts );

		// Get current post field statistics.
		$field_stats = FieldDetector::instance()->get_field_statistics( $post->ID );

		wp_nonce_field( 'silver_assist_acf_clone_fields_meta_box', 'silver_assist_acf_clone_fields_nonce' );
		?>
		<div class="acf-clone-fields-metabox">
			
			<!-- Current Post Info -->
			<div class="acf-clone-info">
				<h4><?php esc_html_e( 'Current Post Fields', 'silver-assist-acf-clone-fields' ); ?></h4>
				<ul class="acf-clone-stats">
					<?php /* translators: %d: number of field groups */ ?>
					<li><?php printf( esc_html__( 'Field Groups: %d', 'silver-assist-acf-clone-fields' ), intval( $field_stats['total_groups'] ) ); ?></li>
					<?php /* translators: %d: total number of fields */ ?>
					<li><?php printf( esc_html__( 'Total Fields: %d', 'silver-assist-acf-clone-fields' ), intval( $field_stats['total_fields'] ) ); ?></li>
					<?php /* translators: %d: number of fields with values */ ?>
					<li><?php printf( esc_html__( 'Fields with Values: %d', 'silver-assist-acf-clone-fields' ), intval( $field_stats['fields_with_values'] ) ); ?></li>
				</ul>
			</div>

			<!-- Clone Action -->
			<div class="acf-clone-actions">
				<?php if ( $source_count > 0 ) : ?>
					<button type="button" class="button button-secondary acf-clone-open-modal" 
							data-post-id="<?php echo esc_attr( (string) $post->ID ); ?>"
							data-post-type="<?php echo esc_attr( $post->post_type ); ?>">
						<?php esc_html_e( 'Clone Custom Fields', 'silver-assist-acf-clone-fields' ); ?>
					</button>
					<p class="description">
						<?php
						/* translators: %1$d: number of available posts, %2$s: post type name */
						printf(
							/* translators: %1$d: number of available posts, %2$s: post type name */
							esc_html__( 'Clone fields from %1$d available %2$s post(s)', 'silver-assist-acf-clone-fields' ),
							intval( $source_count ),
							esc_html( get_post_type_object( $post->post_type )->labels->name ?? $post->post_type )
						);
						?>
					</p>
				<?php else : ?>
					<p class="acf-clone-no-sources">
						<?php esc_html_e( 'No other posts available to clone from.', 'silver-assist-acf-clone-fields' ); ?>
					</p>
					<p class="description">
						<?php esc_html_e( 'Create more posts of this type to enable field cloning.', 'silver-assist-acf-clone-fields' ); ?>
					</p>
				<?php endif; ?>
			</div>

			<!-- Recent Activity -->
			<?php $this->render_recent_activity( $post->ID ); ?>

		</div>
		<?php
	}

	/**
	 * Render ACF not available message
	 *
	 * @return void
	 */
	private function render_acf_not_available(): void {
		?>
		<div class="acf-clone-error">
			<p><strong><?php esc_html_e( 'ACF Pro Required', 'silver-assist-acf-clone-fields' ); ?></strong></p>
			<p><?php esc_html_e( 'Advanced Custom Fields Pro must be active to use field cloning.', 'silver-assist-acf-clone-fields' ); ?></p>
		</div>
		<?php
	}

	/**
	 * Render no permissions message
	 *
	 * @return void
	 */
	private function render_no_permissions(): void {
		?>
		<div class="acf-clone-error">
			<p><?php esc_html_e( 'You do not have permission to clone fields for this post.', 'silver-assist-acf-clone-fields' ); ?></p>
		</div>
		<?php
	}

	/**
	 * Render recent cloning activity
	 *
	 * @param int $post_id Current post ID.
	 * @return void
	 */
	private function render_recent_activity( int $post_id ): void {
		$recent_activity = $this->get_recent_activity( $post_id );

		if ( empty( $recent_activity ) ) {
			return;
		}
		?>
		<div class="acf-clone-recent-activity">
			<h4><?php esc_html_e( 'Recent Activity', 'silver-assist-acf-clone-fields' ); ?></h4>
			<ul class="acf-clone-activity-list">
				<?php foreach ( $recent_activity as $activity ) : ?>
					<li>
						<span class="activity-time"><?php echo esc_html( $activity['time'] ); ?></span>
						<span class="activity-description"><?php echo esc_html( $activity['description'] ); ?></span>
					</li>
				<?php endforeach; ?>
			</ul>
		</div>
		<?php
	}

	/**
	 * Get source posts for cloning
	 *
	 * @param \WP_Post $current_post Current post.
	 * @return array<\WP_Post> Array of source posts
	 */
	private function get_source_posts( \WP_Post $current_post ): array {
		return Helpers::get_posts_by_type(
			$current_post->post_type,
			[
				'exclude'        => [ $current_post->ID ],
				'posts_per_page' => 50,
				'post_status'    => [ 'publish', 'draft', 'pending' ],
			]
		);
	}

	/**
	 * Get recent cloning activity
	 *
	 * @param int $post_id Post ID.
	 * @return array<array<string, string>> Recent activity entries
	 */
	private function get_recent_activity( int $post_id ): array {
		$activity = get_post_meta( $post_id, '_acf_clone_activity', true );

		if ( ! is_array( $activity ) ) {
			return [];
		}

		// Return last 3 activities.
		return array_slice( $activity, -3, 3 );
	}

	/**
	 * Static wrapper for enqueue admin assets to prevent instance duplication
	 *
	 * @param string $hook_suffix Current admin page hook.
	 * @return void
	 */
	public static function static_enqueue_admin_assets( string $hook_suffix ): void {
		// Get the singleton instance and delegate.
		$instance = self::instance();
		$instance->enqueue_admin_assets( $hook_suffix );
	}

	/**
	 * Enqueue admin assets
	 *
	 * @param string $hook_suffix Current admin page hook.
	 * @return void
	 */
	public function enqueue_admin_assets( string $hook_suffix ): void {
		// Only load on post edit screens.
		if ( ! in_array( $hook_suffix, [ 'post.php', 'post-new.php' ], true ) ) {
			return;
		}

		// Check if current post type is enabled.
		global $post;
		if ( ! $post || ! in_array( $post->post_type, $this->enabled_post_types, true ) ) {
			return;
		}

		// Enqueue styles.
		wp_enqueue_style(
			'silver-acf-clone-fields-admin',
			plugin_dir_url( dirname( __DIR__ ) ) . 'assets/css/silver-acf-clone-fields.css',
			[],
			SILVER_ACF_CLONE_VERSION
		);

		// Enqueue scripts.
		wp_enqueue_script(
			'acf-clone-fields-admin',
			plugin_dir_url( dirname( __DIR__ ) ) . 'assets/js/admin.js',
			[ 'jquery', 'wp-util' ],
			SILVER_ACF_CLONE_VERSION,
			true
		);

		// Localize script.
		wp_localize_script(
			'acf-clone-fields-admin',
			'acfCloneFields',
			[
				'ajaxUrl'   => admin_url( 'admin-ajax.php' ),
				'nonce'     => wp_create_nonce( 'silver_assist_acf_clone_fields_ajax' ),
				'postId'    => $post->ID,
				'postType'  => $post->post_type,
				'debugMode' => defined( 'WP_DEBUG' ) && WP_DEBUG,
				'strings'   => [
					'loading'          => __( 'Loading...', 'silver-assist-acf-clone-fields' ),
					'error'            => __( 'An error occurred. Please try again.', 'silver-assist-acf-clone-fields' ),
					'confirmClone'     => __( 'Are you sure you want to clone the selected fields? This will overwrite existing field values.', 'silver-assist-acf-clone-fields' ),
					'noFieldsSelected' => __( 'Please select at least one field to clone.', 'silver-assist-acf-clone-fields' ),
					'cloneSuccess'     => __( 'Fields cloned successfully!', 'silver-assist-acf-clone-fields' ),
					'cloneError'       => __( 'Error cloning fields. Please check the console for details.', 'silver-assist-acf-clone-fields' ),
				],
			]
		);
	}
}