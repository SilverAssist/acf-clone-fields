<?php
/**
 * Tests for Admin\Settings class
 *
 * @package SilverAssist\ACFCloneFields
 * @subpackage Tests\Unit\Admin
 * @since 1.1.0
 */

namespace SilverAssist\ACFCloneFields\Tests\Unit\Admin;

use SilverAssist\ACFCloneFields\Admin\Settings;
use SilverAssist\ACFCloneFields\Tests\Utils\TestCase;

/**
 * Class SettingsTest
 *
 * Tests the Settings class functionality including:
 * - Singleton pattern
 * - LoadableInterface implementation
 * - Settings registration
 * - Default settings
 * - Settings validation
 * - Settings Hub integration
 */
class SettingsTest extends TestCase {
	/**
	 * Settings instance
	 *
	 * @var Settings
	 */
	private Settings $settings;

	/**
	 * Admin user ID
	 *
	 * @var int
	 */
	private int $admin_user_id;

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

		// Set current user as admin
		\wp_set_current_user( $this->admin_user_id );

		// Initialize Settings instance
		$this->settings = Settings::instance();

		// Clean up options
		$this->clean_settings();
	}

	/**
	 * Clean up after tests
	 *
	 * @return void
	 */
	public function tearDown(): void {
		$this->clean_settings();
		\wp_delete_user( $this->admin_user_id );

		parent::tearDown();
	}

	/**
	 * Clean settings options
	 *
	 * @return void
	 */
	protected function clean_settings(): void {
		\delete_option( 'silver_assist_acf_clone_fields_enabled_post_types' );
		\delete_option( 'silver_assist_acf_clone_fields_default_overwrite' );
		\delete_option( 'silver_assist_acf_clone_fields_create_backup' );
		\delete_option( 'silver_assist_acf_clone_fields_copy_attachments' );
		\delete_option( 'silver_assist_acf_clone_fields_validate_data' );
		\delete_option( 'silver_assist_acf_clone_fields_log_operations' );
		\delete_option( 'silver_assist_acf_clone_fields_max_source_posts' );
		\delete_option( 'silver_assist_acf_clone_fields_backup_retention_days' );
		\delete_option( 'silver_assist_acf_clone_fields_backup_max_count' );
	}

	/**
	 * Test singleton pattern implementation
	 *
	 * @return void
	 */
	public function test_singleton_pattern(): void {
		$instance1 = Settings::instance();
		$instance2 = Settings::instance();

		$this->assertSame( $instance1, $instance2, 'Should return same instance' );
		$this->assertInstanceOf( Settings::class, $instance1, 'Should return Settings instance' );
	}

	/**
	 * Test LoadableInterface implementation
	 *
	 * @return void
	 */
	public function test_implements_loadable_interface(): void {
		$this->assertEquals( 40, $this->settings->get_priority(), 'Priority should be 40 for Admin components' );
		$this->assertTrue( $this->settings->should_load(), 'Should load in admin context' );
	}

	/**
	 * Test initialization
	 *
	 * @return void
	 */
	public function test_init(): void {
		// Initialize settings
		$this->settings->init();

		// Check that hooks are registered
		$this->assertGreaterThan( 0, \has_action( 'plugins_loaded', [ $this->settings, 'register_with_settings_hub' ] ), 'Should register Settings Hub hook' );
		$this->assertGreaterThan( 0, \has_action( 'admin_init', [ $this->settings, 'init_settings' ] ), 'Should register admin_init hook' );
		$this->assertGreaterThan( 0, \has_action( 'admin_enqueue_scripts', [ $this->settings, 'enqueue_settings_assets' ] ), 'Should register enqueue hook' );
		$this->assertGreaterThan( 0, \has_filter( 'pre_update_option_silver_assist_acf_clone_fields_enabled_post_types', [ $this->settings, 'validate_enabled_post_types' ] ), 'Should register validation filter' );
	}

	/**
	 * Test default settings are correct
	 *
	 * @return void
	 */
	public function test_default_settings(): void {
		// Initialize which should set default settings
		$this->settings->init();

		// Check default values
		$enabled_post_types = \get_option( 'silver_assist_acf_clone_fields_enabled_post_types', [] );
		$this->assertEquals( [ 'post', 'page' ], $enabled_post_types, 'Default enabled post types should be post and page' );

		$default_overwrite = \get_option( 'silver_assist_acf_clone_fields_default_overwrite', null );
		$this->assertFalse( $default_overwrite, 'Default overwrite should be false' );

		$create_backup = \get_option( 'silver_assist_acf_clone_fields_create_backup', null );
		$this->assertTrue( $create_backup, 'Create backup should be true by default' );

		$copy_attachments = \get_option( 'silver_assist_acf_clone_fields_copy_attachments', null );
		$this->assertTrue( $copy_attachments, 'Copy attachments should be true by default' );

		$validate_data = \get_option( 'silver_assist_acf_clone_fields_validate_data', null );
		$this->assertTrue( $validate_data, 'Validate data should be true by default' );

		$log_operations = \get_option( 'silver_assist_acf_clone_fields_log_operations', null );
		$this->assertTrue( $log_operations, 'Log operations should be true by default' );

		$max_source_posts = \get_option( 'silver_assist_acf_clone_fields_max_source_posts', 0 );
		$this->assertEquals( 50, $max_source_posts, 'Max source posts should be 50 by default' );

		$retention_days = \get_option( 'silver_assist_acf_clone_fields_backup_retention_days', 0 );
		$this->assertEquals( 30, $retention_days, 'Backup retention days should be 30 by default' );

		$max_backups = \get_option( 'silver_assist_acf_clone_fields_backup_max_count', 0 );
		$this->assertEquals( 100, $max_backups, 'Max backups should be 100 by default' );
	}

	/**
	 * Test validate_enabled_post_types with valid post types
	 *
	 * @return void
	 */
	public function test_validate_enabled_post_types_valid(): void {
		$input = [ 'post', 'page', 'attachment' ];

		$result = $this->settings->validate_enabled_post_types( $input );

		$this->assertIsArray( $result, 'Result should be an array' );
		$this->assertContains( 'post', $result, 'Result should contain post' );
		$this->assertContains( 'page', $result, 'Result should contain page' );
		$this->assertContains( 'attachment', $result, 'Result should contain attachment' );
	}

	/**
	 * Test validate_enabled_post_types removes invalid post types
	 *
	 * @return void
	 */
	public function test_validate_enabled_post_types_removes_invalid(): void {
		$input = [ 'post', 'invalid_post_type', 'page' ];

		$result = $this->settings->validate_enabled_post_types( $input );

		$this->assertIsArray( $result, 'Result should be an array' );
		$this->assertContains( 'post', $result, 'Result should contain post' );
		$this->assertContains( 'page', $result, 'Result should contain page' );
		$this->assertNotContains( 'invalid_post_type', $result, 'Result should not contain invalid post type' );
	}

	/**
	 * Test validate_enabled_post_types with non-array input
	 *
	 * @return void
	 */
	public function test_validate_enabled_post_types_non_array_input(): void {
		$input = 'not_an_array';

		$result = $this->settings->validate_enabled_post_types( $input );

		$this->assertIsArray( $result, 'Result should be an array' );
		$this->assertEmpty( $result, 'Result should be empty for non-array input' );
	}

	/**
	 * Test validate_enabled_post_types removes duplicates
	 *
	 * @return void
	 */
	public function test_validate_enabled_post_types_removes_duplicates(): void {
		$input = [ 'post', 'post', 'page', 'page' ];

		$result = $this->settings->validate_enabled_post_types( $input );

		$this->assertIsArray( $result, 'Result should be an array' );
		$this->assertCount( 2, $result, 'Result should have unique values only' );
	}

	/**
	 * Test get_settings returns current settings
	 *
	 * @return void
	 */
	public function test_get_settings_returns_current_settings(): void {
		// Set some options
		\update_option( 'silver_assist_acf_clone_fields_enabled_post_types', [ 'post' ] );
		\update_option( 'silver_assist_acf_clone_fields_default_overwrite', true );
		\update_option( 'silver_assist_acf_clone_fields_max_source_posts', 25 );

		$settings = $this->settings->get_settings();

		$this->assertIsArray( $settings, 'Settings should be an array' );
		$this->assertEquals( [ 'post' ], $settings['enabled_post_types'], 'Should return correct enabled post types' );
		$this->assertTrue( $settings['default_overwrite'], 'Should return correct overwrite setting' );
		$this->assertEquals( 25, $settings['max_source_posts'], 'Should return correct max source posts' );
	}

	/**
	 * Test init_settings registers settings sections and fields
	 *
	 * @return void
	 */
	public function test_init_settings_registers_sections_and_fields(): void {
		global $wp_settings_sections, $wp_settings_fields;

		// Initialize settings sections and fields
		$this->settings->init_settings();

		$page_slug = 'acf-clone-fields';

		// Verify sections are registered
		$this->assertArrayHasKey( $page_slug, $wp_settings_sections, 'Settings page should have sections' );
		$this->assertArrayHasKey( 'acf_clone_general', $wp_settings_sections[ $page_slug ], 'General section should be registered' );
		$this->assertArrayHasKey( 'acf_clone_behavior', $wp_settings_sections[ $page_slug ], 'Behavior section should be registered' );
		$this->assertArrayHasKey( 'acf_clone_advanced', $wp_settings_sections[ $page_slug ], 'Advanced section should be registered' );
		$this->assertArrayHasKey( 'acf_clone_backup', $wp_settings_sections[ $page_slug ], 'Backup section should be registered' );

		// Verify fields are registered
		$this->assertArrayHasKey( $page_slug, $wp_settings_fields, 'Settings page should have fields' );
	}

	/**
	 * Test render_post_types_field outputs checkboxes
	 *
	 * @return void
	 */
	public function test_render_post_types_field(): void {
		// Set enabled post types
		\update_option( 'silver_assist_acf_clone_fields_enabled_post_types', [ 'post' ] );

		// Capture output
		ob_start();
		$this->settings->render_post_types_field();
		$output = ob_get_clean();

		// Verify output contains checkboxes
		$this->assertStringContainsString( 'type="checkbox"', $output, 'Output should contain checkboxes' );
		$this->assertStringContainsString( 'silver_assist_acf_clone_fields_enabled_post_types[]', $output, 'Output should contain correct field name' );
		$this->assertStringContainsString( 'checked', $output, 'Output should have checked checkbox for enabled post type' );
	}

	/**
	 * Test render_overwrite_field outputs checkbox
	 *
	 * @return void
	 */
	public function test_render_overwrite_field(): void {
		// Set option
		\update_option( 'silver_assist_acf_clone_fields_default_overwrite', true );

		// Capture output
		ob_start();
		$this->settings->render_overwrite_field();
		$output = ob_get_clean();

		$this->assertStringContainsString( 'type="checkbox"', $output, 'Output should contain checkbox' );
		$this->assertStringContainsString( 'checked', $output, 'Output should have checked checkbox' );
	}

	/**
	 * Test render_backup_field outputs checkbox
	 *
	 * @return void
	 */
	public function test_render_backup_field(): void {
		// Set option
		\update_option( 'silver_assist_acf_clone_fields_create_backup', false );

		// Capture output
		ob_start();
		$this->settings->render_backup_field();
		$output = ob_get_clean();

		$this->assertStringContainsString( 'type="checkbox"', $output, 'Output should contain checkbox' );
		$this->assertStringNotContainsString( 'checked', $output, 'Output should not have checked checkbox when false' );
	}

	/**
	 * Test render_max_posts_field outputs number input
	 *
	 * @return void
	 */
	public function test_render_max_posts_field(): void {
		// Set option
		\update_option( 'silver_assist_acf_clone_fields_max_source_posts', 75 );

		// Capture output
		ob_start();
		$this->settings->render_max_posts_field();
		$output = ob_get_clean();

		$this->assertStringContainsString( 'type="number"', $output, 'Output should contain number input' );
		$this->assertStringContainsString( 'value="75"', $output, 'Output should contain correct value' );
	}

	/**
	 * Test render_backup_retention_field outputs number input
	 *
	 * @return void
	 */
	public function test_render_backup_retention_field(): void {
		// Set option
		\update_option( 'silver_assist_acf_clone_fields_backup_retention_days', 60 );

		// Capture output
		ob_start();
		$this->settings->render_backup_retention_field();
		$output = ob_get_clean();

		$this->assertStringContainsString( 'type="number"', $output, 'Output should contain number input' );
		$this->assertStringContainsString( 'value="60"', $output, 'Output should contain correct value' );
	}

	/**
	 * Test render_settings_page outputs form
	 *
	 * @return void
	 */
	public function test_render_settings_page(): void {
		// Capture output
		ob_start();
		$this->settings->render_settings_page();
		$output = ob_get_clean();

		// Verify page structure
		$this->assertStringContainsString( '<div class="wrap">', $output, 'Output should contain wrap div' );
		$this->assertStringContainsString( '<form', $output, 'Output should contain form' );
		$this->assertStringContainsString( 'method="post"', $output, 'Form should use POST method' );
	}

	/**
	 * Test enqueue_settings_assets on settings page
	 *
	 * @return void
	 */
	public function test_enqueue_settings_assets_on_settings_page(): void {
		// Simulate settings page
		$this->settings->enqueue_settings_assets( 'settings_page_acf-clone-fields' );

		// Verify style is enqueued
		$this->assertTrue( \wp_style_is( 'acf-clone-fields-admin', 'enqueued' ), 'Admin style should be enqueued on settings page' );
	}

	/**
	 * Test enqueue_settings_assets not loaded on other pages
	 *
	 * @return void
	 */
	public function test_enqueue_settings_assets_not_loaded_on_other_pages(): void {
		// Simulate other page
		$this->settings->enqueue_settings_assets( 'index.php' );

		// Verify style is NOT enqueued
		$this->assertFalse( \wp_style_is( 'acf-clone-fields-admin', 'enqueued' ), 'Admin style should not be enqueued on other pages' );
	}

	/**
	 * Test should_load returns true in admin
	 *
	 * @return void
	 */
	public function test_should_load_returns_true_in_admin(): void {
		$should_load = $this->settings->should_load();

		$this->assertTrue( $should_load, 'Should load in admin context' );
	}

	/**
	 * Test render section methods
	 *
	 * @return void
	 */
	public function test_render_section_methods(): void {
		// Test general section
		ob_start();
		$this->settings->render_general_section();
		$output = ob_get_clean();
		$this->assertNotEmpty( $output, 'General section should have output' );

		// Test behavior section
		ob_start();
		$this->settings->render_behavior_section();
		$output = ob_get_clean();
		$this->assertNotEmpty( $output, 'Behavior section should have output' );

		// Test advanced section
		ob_start();
		$this->settings->render_advanced_section();
		$output = ob_get_clean();
		$this->assertNotEmpty( $output, 'Advanced section should have output' );

		// Test backup section
		ob_start();
		$this->settings->render_backup_section();
		$output = ob_get_clean();
		$this->assertNotEmpty( $output, 'Backup section should have output' );
	}
}
