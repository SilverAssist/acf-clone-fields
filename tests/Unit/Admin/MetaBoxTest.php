<?php
/**
 * Tests for Admin\MetaBox class
 *
 * @package SilverAssist\ACFCloneFields
 * @subpackage Tests\Unit\Admin
 * @since 1.1.0
 */

namespace SilverAssist\ACFCloneFields\Tests\Unit\Admin;

use SilverAssist\ACFCloneFields\Admin\MetaBox;
use SilverAssist\ACFCloneFields\Tests\Utils\TestCase;

/**
 * Class MetaBoxTest
 *
 * Tests the MetaBox class functionality including:
 * - Singleton pattern
 * - LoadableInterface implementation
 * - Meta box registration
 * - Meta box rendering
 * - Permission checks
 * - Asset enqueuing
 */
class MetaBoxTest extends TestCase {
	/**
	 * MetaBox instance
	 *
	 * @var MetaBox
	 */
	private MetaBox $metabox;

	/**
	 * Admin user ID
	 *
	 * @var int
	 */
	private int $admin_user_id;

	/**
	 * Editor user ID
	 *
	 * @var int
	 */
	private int $editor_user_id;

	/**
	 * Test post ID
	 *
	 * @var int
	 */
	private int $test_post_id;

	/**
	 * Set up test environment
	 *
	 * @return void
	 */
	public function setUp(): void {
		parent::setUp();

		// Set admin screen context (needed for is_admin() to return true in tests)
		set_current_screen( 'edit-post' );

		// Create admin user
		$this->admin_user_id = static::factory()->user->create(
			[
				'role' => 'administrator',
			]
		);

		// Create editor user
		$this->editor_user_id = static::factory()->user->create(
			[
				'role' => 'editor',
			]
		);

		// Set current user as admin
		\wp_set_current_user( $this->admin_user_id );

		// Create test post
		$this->test_post_id = static::factory()->post->create(
			[
				'post_title'  => 'Test Post for MetaBox',
				'post_status' => 'publish',
				'post_type'   => 'post',
			]
		);

		// Initialize MetaBox instance
		$this->metabox = MetaBox::instance();
	}

	/**
	 * Clean up after tests
	 *
	 * @return void
	 */
	public function tearDown(): void {
		\wp_delete_post( $this->test_post_id, true );
		\wp_delete_user( $this->admin_user_id );
		\wp_delete_user( $this->editor_user_id );

		parent::tearDown();
	}

	/**
	 * Test singleton pattern implementation
	 *
	 * @return void
	 */
	public function test_singleton_pattern(): void {
		$instance1 = MetaBox::instance();
		$instance2 = MetaBox::instance();

		$this->assertSame( $instance1, $instance2, 'Should return same instance' );
		$this->assertInstanceOf( MetaBox::class, $instance1, 'Should return MetaBox instance' );
	}

	/**
	 * Test LoadableInterface implementation
	 *
	 * @return void
	 */
	public function test_implements_loadable_interface(): void {
		$this->assertEquals( 40, $this->metabox->get_priority(), 'Priority should be 40 for Admin components' );
		$this->assertTrue( $this->metabox->should_load(), 'Should load in admin context with add_meta_box function' );
	}

	/**
	 * Test initialization
	 *
	 * @return void
	 */
	public function test_init(): void {
		// Initialize the metabox
		$this->metabox->init();

		// Check that hooks are registered
		$this->assertGreaterThan( 0, \has_action( 'add_meta_boxes', [ $this->metabox, 'add_meta_boxes' ] ), 'Should register add_meta_boxes hook' );
		$this->assertGreaterThan( 0, \has_filter( 'use_block_editor_for_post_type', [ $this->metabox, 'ensure_metabox_compatibility' ] ), 'Should register block editor filter' );
	}

	/**
	 * Test add_meta_boxes registers meta box for enabled post types
	 *
	 * @return void
	 */
	public function test_add_meta_boxes_for_enabled_post_types(): void {
		global $wp_meta_boxes;

		// Set enabled post types
		\update_option( 'silver_assist_acf_clone_fields_enabled_post_types', [ 'post', 'page' ] );

		// Reset meta boxes
		$wp_meta_boxes = [];

		// Initialize first
		$this->metabox->init();

		// Register meta boxes
		$this->metabox->add_meta_boxes();

		// Verify meta box registered for 'post' type
		$this->assertArrayHasKey( 'post', $wp_meta_boxes, 'Meta box should be registered for post type' );
		$this->assertArrayHasKey( 'side', $wp_meta_boxes['post'], 'Meta box should be in sidebar' );
		$this->assertArrayHasKey( 'high', $wp_meta_boxes['post']['side'], 'Meta box should have high priority' );
		$this->assertArrayHasKey( 'acf_clone_fields', $wp_meta_boxes['post']['side']['high'], 'Meta box should have correct ID' );

		// Verify meta box registered for 'page' type
		$this->assertArrayHasKey( 'page', $wp_meta_boxes, 'Meta box should be registered for page type' );
	}

	/**
	 * Test meta box not registered for disabled post types
	 *
	 * @return void
	 */
	public function test_meta_box_not_added_for_disabled_post_types(): void {
		global $wp_meta_boxes;

		// Set enabled post types (only 'post')
		\update_option( 'silver_assist_acf_clone_fields_enabled_post_types', [ 'post' ] );

		// Reset meta boxes
		$wp_meta_boxes = [];

		// Initialize first
		$this->metabox->init();

		// Register meta boxes
		$this->metabox->add_meta_boxes();

		// Verify meta box registered for 'post' type
		$this->assertArrayHasKey( 'post', $wp_meta_boxes, 'Meta box should be registered for enabled post type' );

		// Verify meta box NOT registered for 'page' type
		$this->assertArrayNotHasKey( 'page', $wp_meta_boxes, 'Meta box should not be registered for disabled post type' );
	}

	/**
	 * Test render_meta_box with valid post
	 *
	 * @return void
	 */
	public function test_render_meta_box_with_valid_post(): void {
		$post = \get_post( $this->test_post_id );

		// Capture output
		ob_start();
		$this->metabox->render_meta_box( $post );
		$output = ob_get_clean();

		// Verify nonce field is present
		$this->assertStringContainsString( 'silver_assist_acf_clone_fields_nonce', $output, 'Output should contain nonce field' );

		// Verify metabox structure
		$this->assertStringContainsString( 'acf-clone-fields-metabox', $output, 'Output should contain metabox container' );
		$this->assertStringContainsString( 'Current Post Fields', $output, 'Output should show current post fields section' );
	}

	/**
	 * Test render_meta_box without edit permission
	 *
	 * @return void
	 */
	public function test_render_meta_box_without_edit_permission(): void {
		// Create subscriber user (no edit_posts capability)
		$subscriber_id = static::factory()->user->create( [ 'role' => 'subscriber' ] );
		\wp_set_current_user( $subscriber_id );

		$post = \get_post( $this->test_post_id );

		// Capture output
		ob_start();
		$this->metabox->render_meta_box( $post );
		$output = ob_get_clean();

		// Verify permission message
		$this->assertStringContainsString( 'do not have permission', $output, 'Output should show permission error' );

		\wp_delete_user( $subscriber_id );
	}

	/**
	 * Test ensure_metabox_compatibility filter
	 *
	 * @return void
	 */
	public function test_ensure_metabox_compatibility(): void {
		$result = $this->metabox->ensure_metabox_compatibility( true, 'post' );
		$this->assertTrue( $result, 'Should return true to allow block editor' );

		$result = $this->metabox->ensure_metabox_compatibility( false, 'post' );
		$this->assertFalse( $result, 'Should return false if block editor was disabled' );
	}

	/**
	 * Test enqueue_admin_assets on post edit screen
	 *
	 * @return void
	 */
	public function test_enqueue_admin_assets_on_post_edit_screen(): void {
		global $post;

		// Set up global post
		$post = \get_post( $this->test_post_id );

		// Set enabled post types
		\update_option( 'silver_assist_acf_clone_fields_enabled_post_types', [ 'post' ] );

		// Reinitialize to pick up the option
		$this->metabox = MetaBox::instance();
		$this->metabox->init();

		// Simulate post.php screen
		$this->metabox->enqueue_admin_assets( 'post.php' );

		// Verify scripts and styles are enqueued
		$this->assertTrue( \wp_script_is( 'acf-clone-fields-admin', 'enqueued' ), 'Admin script should be enqueued' );
		$this->assertTrue( \wp_style_is( 'acf-clone-fields-admin', 'enqueued' ), 'Admin style should be enqueued' );
	}

	/**
	 * Test enqueue_admin_assets not loaded on other screens
	 *
	 * @return void
	 */
	public function test_enqueue_admin_assets_not_loaded_on_other_screens(): void {
		global $post;

		// Set up global post
		$post = \get_post( $this->test_post_id );

		// Set enabled post types
		\update_option( 'silver_assist_acf_clone_fields_enabled_post_types', [ 'post' ] );

		// Reinitialize
		$this->metabox = MetaBox::instance();
		$this->metabox->init();

		// Simulate other screen (not post.php or post-new.php)
		$this->metabox->enqueue_admin_assets( 'index.php' );

		// Verify scripts and styles are NOT enqueued
		$this->assertFalse( \wp_script_is( 'acf-clone-fields-admin', 'enqueued' ), 'Admin script should not be enqueued on other screens' );
		$this->assertFalse( \wp_style_is( 'acf-clone-fields-admin', 'enqueued' ), 'Admin style should not be enqueued on other screens' );
	}

	/**
	 * Test enqueue_admin_assets not loaded for disabled post types
	 *
	 * @return void
	 */
	public function test_enqueue_admin_assets_not_loaded_for_disabled_post_types(): void {
		global $post;

		// Create a page post
		$page_id = static::factory()->post->create(
			[
				'post_type' => 'page',
			]
		);
		$post    = \get_post( $page_id );

		// Set enabled post types (only 'post', not 'page')
		\update_option( 'silver_assist_acf_clone_fields_enabled_post_types', [ 'post' ] );

		// Reinitialize
		$this->metabox = MetaBox::instance();
		$this->metabox->init();

		// Simulate post.php screen with page post type
		$this->metabox->enqueue_admin_assets( 'post.php' );

		// Verify scripts and styles are NOT enqueued
		$this->assertFalse( \wp_script_is( 'acf-clone-fields-admin', 'enqueued' ), 'Admin script should not be enqueued for disabled post types' );

		\wp_delete_post( $page_id, true );
	}

	/**
	 * Test should_load returns true in admin with add_meta_box function
	 *
	 * @return void
	 */
	public function test_should_load_returns_true_in_admin(): void {
		// add_meta_box function exists in test environment
		$should_load = $this->metabox->should_load();

		$this->assertTrue( $should_load, 'Should load in admin context with add_meta_box function' );
	}

	/**
	 * Test initialization prevents duplicate init
	 *
	 * @return void
	 */
	public function test_init_prevents_duplicate_initialization(): void {
		// Initialize once
		$this->metabox->init();

		// Count current hook registrations
		$hooks_count_1 = \has_action( 'add_meta_boxes', [ $this->metabox, 'add_meta_boxes' ] );

		// Initialize again
		$this->metabox->init();

		// Count hook registrations again
		$hooks_count_2 = \has_action( 'add_meta_boxes', [ $this->metabox, 'add_meta_boxes' ] );

		// Should be the same (not doubled)
		$this->assertEquals( $hooks_count_1, $hooks_count_2, 'Duplicate init should not register hooks again' );
	}
}
