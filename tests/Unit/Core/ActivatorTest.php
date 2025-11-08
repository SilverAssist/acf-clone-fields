<?php
/**
 * Tests for Activator class
 *
 * @package SilverAssist\ACFCloneFields
 * @subpackage Tests\Unit\Core
 */

namespace SilverAssist\ACFCloneFields\Tests\Unit\Core;

use SilverAssist\ACFCloneFields\Tests\Utils\TestCase;
use SilverAssist\ACFCloneFields\Core\Activator;

/**
 * Activator test case
 *
 * @coversDefaultClass \SilverAssist\ACFCloneFields\Core\Activator
 */
class ActivatorTest extends TestCase {

	/**
	 * Create shared fixtures before class
	 *
	 * @param \WP_UnitTest_Factory $factory Factory instance.
	 */
	public static function wpSetUpBeforeClass( $factory ): void {
		// Ensure backup table exists for testing.
		Activator::create_tables();
	}

	/**
	 * Setup test environment
	 */
	public function setUp(): void {
		parent::setUp();

		// Define constant if not already defined.
		if ( ! defined( 'SILVER_ACF_CLONE_VERSION' ) ) {
			define( 'SILVER_ACF_CLONE_VERSION', '1.1.0' );
		}

		// Clean up options before each test.
		delete_option( 'silver_acf_clone_version' );
		delete_option( 'silver_acf_clone_settings' );
		delete_option( 'silver_acf_clone_activated' );
		delete_option( 'silver_acf_clone_deactivated' );
		delete_option( 'silver_acf_clone_keep_data_on_uninstall' );
	}

	/**
	 * Test activation creates version option
	 *
	 * @covers ::activate
	 * @covers ::init_default_settings
	 */
	public function test_activate_creates_version_option(): void {
		// Mock ACF availability.
		if ( ! function_exists( 'acf_add_local_field_group' ) ) {
			$this->markTestSkipped( 'ACF not available in test environment' );
		}

		Activator::activate();

		$version = \get_option( 'silver_acf_clone_version' );
		$this->assertNotFalse( $version, 'Version option should be created' );
		$this->assertSame( SILVER_ACF_CLONE_VERSION, $version );
	}

	/**
	 * Test activation sets activation timestamp
	 *
	 * @covers ::activate
	 */
	public function test_activate_sets_activation_timestamp(): void {
		if ( ! function_exists( 'acf_add_local_field_group' ) ) {
			$this->markTestSkipped( 'ACF not available' );
		}

		$before = time();
		Activator::activate();
		$after = time();

		$activated = \get_option( 'silver_acf_clone_activated' );
		$this->assertNotFalse( $activated );
		$this->assertGreaterThanOrEqual( $before, $activated );
		$this->assertLessThanOrEqual( $after, $activated );
	}

	/**
	 * Test activation initializes default settings
	 *
	 * @covers ::activate
	 * @covers ::init_default_settings
	 */
	public function test_activate_initializes_default_settings(): void {
		if ( ! function_exists( 'acf_add_local_field_group' ) ) {
			$this->markTestSkipped( 'ACF not available' );
		}

		Activator::activate();

		$settings = \get_option( 'silver_acf_clone_settings' );
		$this->assertIsArray( $settings );
		$this->assertArrayHasKey( 'enabled_post_types', $settings );
		$this->assertArrayHasKey( 'show_in_sidebar', $settings );
		$this->assertArrayHasKey( 'clone_button_text', $settings );
		$this->assertArrayHasKey( 'logging_enabled', $settings );
		$this->assertArrayHasKey( 'cache_enabled', $settings );

		// Verify default values.
		$this->assertSame( [ 'post', 'page' ], $settings['enabled_post_types'] );
		$this->assertTrue( $settings['show_in_sidebar'] );
		$this->assertFalse( $settings['logging_enabled'] );
		$this->assertTrue( $settings['cache_enabled'] );
	}

	/**
	 * Test activation does not overwrite existing settings
	 *
	 * @covers ::activate
	 * @covers ::init_default_settings
	 */
	public function test_activate_preserves_existing_settings(): void {
		if ( ! function_exists( 'acf_add_local_field_group' ) ) {
			$this->markTestSkipped( 'ACF not available' );
		}

		// Set custom settings.
		$custom_settings = [
			'enabled_post_types' => [ 'custom_post_type' ],
			'logging_enabled'    => true,
		];
		\update_option( 'silver_acf_clone_settings', $custom_settings );

		Activator::activate();

		$settings = \get_option( 'silver_acf_clone_settings' );
		$this->assertSame( $custom_settings, $settings, 'Existing settings should not be overwritten' );
	}

	/**
	 * Test deactivation sets deactivation timestamp
	 *
	 * @covers ::deactivate
	 */
	public function test_deactivate_sets_deactivation_timestamp(): void {
		$before = time();
		Activator::deactivate();
		$after = time();

		$deactivated = \get_option( 'silver_acf_clone_deactivated' );
		$this->assertNotFalse( $deactivated );
		$this->assertGreaterThanOrEqual( $before, $deactivated );
		$this->assertLessThanOrEqual( $after, $deactivated );
	}

	/**
	 * Test create_tables creates backup table
	 *
	 * @covers ::create_tables
	 */
	public function test_create_tables_creates_backup_table(): void {
		global $wpdb;

		// Drop table first to ensure clean state.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}acf_field_backups" );

		// Create tables.
		Activator::create_tables();

		// Verify table exists.
		$table_name = $wpdb->prefix . 'acf_field_backups';
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$table_exists = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table_name ) );

		$this->assertSame( $table_name, $table_exists, 'Backup table should be created' );

		// Verify table structure.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$columns = $wpdb->get_results( "DESCRIBE {$table_name}" );
		$column_names = wp_list_pluck( $columns, 'Field' );

		$this->assertContains( 'id', $column_names );
		$this->assertContains( 'backup_id', $column_names );
		$this->assertContains( 'post_id', $column_names );
		$this->assertContains( 'user_id', $column_names );
		$this->assertContains( 'backup_data', $column_names );
		$this->assertContains( 'created_at', $column_names );
	}

	/**
	 * Test create_tables is idempotent
	 *
	 * @covers ::create_tables
	 */
	public function test_create_tables_is_idempotent(): void {
		global $wpdb;

		$table_name = $wpdb->prefix . 'acf_field_backups';

		// Create tables twice.
		Activator::create_tables();
		Activator::create_tables();

		// Verify table still exists and is valid.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$table_exists = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table_name ) );

		$this->assertSame( $table_name, $table_exists, 'Table should exist after multiple create calls' );
	}

	/**
	 * Test uninstall removes options when keep_data is false
	 *
	 * @covers ::uninstall
	 */
	public function test_uninstall_removes_options_when_keep_data_false(): void {
		// Set up options.
		\update_option( 'silver_acf_clone_version', '1.0.0' );
		\update_option( 'silver_acf_clone_settings', [ 'test' => 'data' ] );
		\update_option( 'silver_acf_clone_activated', time() );
		\update_option( 'silver_acf_clone_keep_data_on_uninstall', false );

		// Run uninstall.
		Activator::uninstall();

		// Verify options removed.
		$this->assertFalse( \get_option( 'silver_acf_clone_version' ) );
		$this->assertFalse( \get_option( 'silver_acf_clone_settings' ) );
		$this->assertFalse( \get_option( 'silver_acf_clone_activated' ) );
	}

	/**
	 * Test uninstall preserves options when keep_data is true
	 *
	 * @covers ::uninstall
	 */
	public function test_uninstall_preserves_options_when_keep_data_true(): void {
		// Set up options.
		\update_option( 'silver_acf_clone_version', '1.0.0' );
		\update_option( 'silver_acf_clone_settings', [ 'test' => 'data' ] );
		\update_option( 'silver_acf_clone_keep_data_on_uninstall', true );

		// Run uninstall.
		Activator::uninstall();

		// Verify options preserved.
		$this->assertSame( '1.0.0', \get_option( 'silver_acf_clone_version' ) );
		$this->assertIsArray( \get_option( 'silver_acf_clone_settings' ) );
	}

	/**
	 * Test check_requirements passes with valid environment
	 *
	 * Note: We can't directly test check_requirements as it's private,
	 * but it's called during activate(), so we test indirectly.
	 *
	 * @covers ::check_requirements
	 */
	public function test_check_requirements_passes_with_valid_environment(): void {
		if ( ! function_exists( 'acf_add_local_field_group' ) ) {
			$this->markTestSkipped( 'ACF not available' );
		}

		// Should not throw exception with valid environment.
		$this->expectNotToPerformAssertions();
		Activator::activate();
	}
}
