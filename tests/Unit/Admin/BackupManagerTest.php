<?php
/**
 * Tests for Admin\BackupManager class
 *
 * @package SilverAssist\ACFCloneFields
 * @subpackage Tests\Unit\Admin
 * @since 1.1.0
 */

namespace SilverAssist\ACFCloneFields\Tests\Unit\Admin;

use SilverAssist\ACFCloneFields\Admin\BackupManager;
use SilverAssist\ACFCloneFields\Services\FieldCloner;
use SilverAssist\ACFCloneFields\Core\Activator;
use SilverAssist\ACFCloneFields\Tests\Utils\TestCase;

/**
 * Class BackupManagerTest
 *
 * Tests the BackupManager class functionality including:
 * - Singleton pattern
 * - LoadableInterface implementation
 * - Meta box registration
 * - AJAX handlers (restore, delete, cleanup)
 * - Permission checks
 */
class BackupManagerTest extends TestCase {
	/**
	 * BackupManager instance
	 *
	 * @var BackupManager
	 */
	private BackupManager $manager;

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
	 * Create shared fixtures before class
	 *
	 * @param WP_UnitTest_Factory $factory Factory instance.
	 * @return void
	 */
	public static function wpSetUpBeforeClass( $factory ): void {
		// Create backup table once for all tests.
		Activator::create_tables();
	}

	/**
	 * Set up test environment
	 *
	 * @return void
	 */
	public function setUp(): void {
		parent::setUp();

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

		// Set current user as admin by default
		\wp_set_current_user( $this->admin_user_id );

		// Create test post
		$this->test_post_id = static::factory()->post->create(
			[
				'post_title'  => 'Test Post for BackupManager',
				'post_status' => 'publish',
				'post_type'   => 'post',
			]
		);

		// Initialize BackupManager instance
		$this->manager = BackupManager::instance();

		// Clean backup table
		$this->clean_backup_table();
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
	 * Clean backup table data between tests
	 *
	 * @return void
	 */
	protected function clean_backup_table(): void {
		global $wpdb;

		$table_name = $wpdb->prefix . 'acf_field_backups';
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->query( "TRUNCATE TABLE $table_name" );
	}

	/**
	 * Test singleton pattern implementation
	 *
	 * @return void
	 */
	public function test_singleton_pattern(): void {
		$instance1 = BackupManager::instance();
		$instance2 = BackupManager::instance();

		$this->assertSame( $instance1, $instance2, 'Should return same instance' );
		$this->assertInstanceOf( BackupManager::class, $instance1, 'Should return BackupManager instance' );
	}

	/**
	 * Test LoadableInterface implementation
	 *
	 * @return void
	 */
	public function test_implements_loadable_interface(): void {
		$this->assertEquals( 40, $this->manager->get_priority(), 'Priority should be 40 for Admin components' );
		
		// Set admin screen context for testing should_load()
		// In WordPress Test Suite, is_admin() returns false by default
		// We need to simulate admin context
		\set_current_screen( 'edit-post' );
		
		$this->assertTrue( $this->manager->should_load(), 'Should load in admin context' );
		
		// Clean up
		\set_current_screen( 'front' );
	}

	/**
	 * Test initialization
	 *
	 * @return void
	 */
	public function test_init(): void {
		// Initialize the manager
		$this->manager->init();

		// Check that hooks are registered
		$this->assertGreaterThan( 0, \has_action( 'add_meta_boxes', [ $this->manager, 'add_backup_meta_box' ] ) );
		$this->assertGreaterThan( 0, \has_action( 'wp_ajax_acf_clone_restore_backup', [ $this->manager, 'handle_restore_backup' ] ) );
		$this->assertGreaterThan( 0, \has_action( 'wp_ajax_acf_clone_delete_backup', [ $this->manager, 'handle_delete_backup' ] ) );
		$this->assertGreaterThan( 0, \has_action( 'wp_ajax_acf_clone_cleanup_backups', [ $this->manager, 'handle_cleanup_backups' ] ) );
	}

	/**
	 * Test meta box registration
	 *
	 * @return void
	 */
	public function test_add_backup_meta_box(): void {
		global $wp_meta_boxes;

		// Set enabled post types
		\update_option( 'silver_assist_acf_clone_fields_enabled_post_types', [ 'post', 'page' ] );

		// Reset meta boxes
		$wp_meta_boxes = [];

		// Register meta boxes
		$this->manager->add_backup_meta_box();

		// Verify meta box registered for 'post' type
		$this->assertArrayHasKey( 'post', $wp_meta_boxes, 'Meta box should be registered for post type' );
		$this->assertArrayHasKey( 'side', $wp_meta_boxes['post'], 'Meta box should be in sidebar' );
		$this->assertArrayHasKey( 'low', $wp_meta_boxes['post']['side'], 'Meta box should have low priority' );
		$this->assertArrayHasKey( 'silver-acf-clone-backups', $wp_meta_boxes['post']['side']['low'], 'Meta box should have correct ID' );

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

		// Set enabled post types (only 'post', not 'page')
		\update_option( 'silver_assist_acf_clone_fields_enabled_post_types', [ 'post' ] );

		// Reset meta boxes
		$wp_meta_boxes = [];

		// Register meta boxes
		$this->manager->add_backup_meta_box();

		// Verify meta box registered for 'post' type
		$this->assertArrayHasKey( 'post', $wp_meta_boxes, 'Meta box should be registered for enabled post type' );

		// Verify meta box NOT registered for 'page' type
		$this->assertArrayNotHasKey( 'page', $wp_meta_boxes, 'Meta box should not be registered for disabled post type' );
	}

	/**
	 * Test render_backup_meta_box with no backups
	 *
	 * @return void
	 */
	public function test_render_backup_meta_box_no_backups(): void {
		$post = \get_post( $this->test_post_id );

		// Capture output
		ob_start();
		$this->manager->render_backup_meta_box( $post );
		$output = ob_get_clean();

		// Verify nonce field is present
		$this->assertStringContainsString( 'acf_clone_backup_nonce', $output, 'Output should contain nonce field' );

		// Verify "no backups" message
		$this->assertStringContainsString( 'No backups available', $output, 'Output should show no backups message' );
	}

	/**
	 * Test render_backup_meta_box with backups
	 *
	 * @return void
	 */
	public function test_render_backup_meta_box_with_backups(): void {
		global $wpdb;

		// Create a backup manually
		$table_name = $wpdb->prefix . 'acf_field_backups';
		$backup_id  = wp_generate_uuid4();

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->insert(
			$table_name,
			[
				'backup_id'   => $backup_id,
				'post_id'     => $this->test_post_id,
				'backup_data' => wp_json_encode( [ 'test_field' => 'test_value' ] ),
				'field_count' => 1,
				'user_id'     => $this->admin_user_id,
				'created_at'  => current_time( 'mysql' ),
			]
		);

		$post = \get_post( $this->test_post_id );

		// Capture output
		ob_start();
		$this->manager->render_backup_meta_box( $post );
		$output = ob_get_clean();

		// Verify backup is shown
		$this->assertStringContainsString( 'acf-clone-backup-item', $output, 'Output should contain backup item' );
		$this->assertStringContainsString( $backup_id, $output, 'Output should contain backup ID' );
		$this->assertStringContainsString( 'Restore', $output, 'Output should contain restore button' );
		$this->assertStringContainsString( 'Delete', $output, 'Output should contain delete button' );
	}

	/**
	 * Test handle_restore_backup without nonce (should fail)
	 *
	 * @return void
	 */
	public function test_handle_restore_backup_without_nonce(): void {
		$this->expectException( \WPAjaxDieContinueException::class );

		// Attempt to restore without nonce
		$_POST['backup_id'] = 'test-backup-id';
		$_POST['post_id']   = $this->test_post_id;

		try {
			$this->manager->handle_restore_backup();
		} catch ( \WPAjaxDieContinueException $e ) {
			// Verify it died due to nonce failure
			throw $e;
		}
	}

	/**
	 * Test handle_restore_backup without permission
	 *
	 * @return void
	 */
	public function test_handle_restore_backup_without_permission(): void {
		// Create subscriber user (no edit_posts capability)
		$subscriber_id = static::factory()->user->create( [ 'role' => 'subscriber' ] );
		\wp_set_current_user( $subscriber_id );

		// Set up valid nonce
		$_POST['nonce']     = \wp_create_nonce( 'acf_clone_backup_action' );
		$_POST['backup_id'] = 'test-backup-id';
		$_POST['post_id']   = $this->test_post_id;

		// Attempt to restore
		$this->expectException( \WPAjaxDieStopException::class );
		$this->manager->handle_restore_backup();

		\wp_delete_user( $subscriber_id );
	}

	/**
	 * Test handle_restore_backup with missing backup_id
	 *
	 * @return void
	 */
	public function test_handle_restore_backup_missing_backup_id(): void {
		// Set up valid nonce
		$_POST['nonce']   = \wp_create_nonce( 'acf_clone_backup_action' );
		$_POST['post_id'] = $this->test_post_id;
		// Intentionally omit backup_id

		$this->expectException( \WPAjaxDieStopException::class );
		$this->manager->handle_restore_backup();
	}

	/**
	 * Test handle_delete_backup without nonce
	 *
	 * @return void
	 */
	public function test_handle_delete_backup_without_nonce(): void {
		$this->expectException( \WPAjaxDieContinueException::class );

		$_POST['backup_id'] = 'test-backup-id';

		try {
			$this->manager->handle_delete_backup();
		} catch ( \WPAjaxDieContinueException $e ) {
			throw $e;
		}
	}

	/**
	 * Test handle_delete_backup without permission
	 *
	 * @return void
	 */
	public function test_handle_delete_backup_without_permission(): void {
		// Create subscriber user
		$subscriber_id = static::factory()->user->create( [ 'role' => 'subscriber' ] );
		\wp_set_current_user( $subscriber_id );

		$_POST['nonce']     = \wp_create_nonce( 'acf_clone_backup_action' );
		$_POST['backup_id'] = 'test-backup-id';

		$this->expectException( \WPAjaxDieStopException::class );
		$this->manager->handle_delete_backup();

		\wp_delete_user( $subscriber_id );
	}

	/**
	 * Test handle_delete_backup with missing backup_id
	 *
	 * @return void
	 */
	public function test_handle_delete_backup_missing_backup_id(): void {
		$_POST['nonce'] = \wp_create_nonce( 'acf_clone_backup_action' );
		// Intentionally omit backup_id

		$this->expectException( \WPAjaxDieStopException::class );
		$this->manager->handle_delete_backup();
	}

	/**
	 * Test handle_cleanup_backups without nonce
	 *
	 * @return void
	 */
	public function test_handle_cleanup_backups_without_nonce(): void {
		$this->expectException( \WPAjaxDieContinueException::class );

		try {
			$this->manager->handle_cleanup_backups();
		} catch ( \WPAjaxDieContinueException $e ) {
			throw $e;
		}
	}

	/**
	 * Test handle_cleanup_backups without manage_options capability
	 *
	 * @return void
	 */
	public function test_handle_cleanup_backups_without_permission(): void {
		// Set current user as editor (has edit_posts but not manage_options)
		\wp_set_current_user( $this->editor_user_id );

		$_POST['nonce'] = \wp_create_nonce( 'acf_clone_backup_action' );

		$this->expectException( \WPAjaxDieStopException::class );
		$this->manager->handle_cleanup_backups();
	}

	/**
	 * Test should_load returns false outside admin
	 *
	 * @return void
	 */
	public function test_should_load_returns_false_outside_admin(): void {
		// Simulate non-admin context
		set_current_screen( 'front' );

		$should_load = $this->manager->should_load();

		$this->assertFalse( $should_load, 'Should not load outside admin context' );
	}
}
