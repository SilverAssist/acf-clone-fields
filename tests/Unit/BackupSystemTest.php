<?php
/**
 * Backup System Tests
 *
 * Tests for the backup functionality in FieldCloner service.
 *
 * @package SilverAssist\ACFCloneFields
 * @subpackage Tests\Unit
 * @since 1.1.0
 */

namespace SilverAssist\ACFCloneFields\Tests\Unit;

defined( 'ABSPATH' ) || exit;

use SilverAssist\ACFCloneFields\Tests\Utils\TestCase;
use SilverAssist\ACFCloneFields\Services\FieldCloner;

/**
 * Test the backup system in FieldCloner
 */
class BackupSystemTest extends TestCase {

	/**
	 * FieldCloner instance
	 *
	 * @var FieldCloner
	 */
	private FieldCloner $cloner;

	/**
	 * Set up test environment
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();
		$this->cloner = FieldCloner::instance();
		
		// Create backup table.
		$this->create_backup_table();
	}

	/**
	 * Clean up after tests
	 *
	 * @return void
	 */
	protected function tearDown(): void {
		global $wpdb;
		
		// Clean up backup table.
		$table_name = $wpdb->prefix . 'acf_field_backups';
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange
		$wpdb->query( "DROP TABLE IF EXISTS $table_name" );
		
		parent::tearDown();
	}

	/**
	 * Create backup table for testing
	 *
	 * @return void
	 */
	protected function create_backup_table(): void {
		global $wpdb;
		
		$table_name      = $wpdb->prefix . 'acf_field_backups';
		$charset_collate = $wpdb->get_charset_collate();
		
		$sql = "CREATE TABLE IF NOT EXISTS $table_name (
			id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			backup_id varchar(100) NOT NULL,
			post_id bigint(20) UNSIGNED NOT NULL,
			user_id bigint(20) UNSIGNED NOT NULL,
			backup_data longtext NOT NULL,
			created_at datetime NOT NULL,
			PRIMARY KEY  (id),
			KEY backup_id (backup_id),
			KEY post_id (post_id),
			KEY created_at (created_at)
		) $charset_collate;";
		
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );
	}

	/**
	 * Test FieldCloner instantiation
	 *
	 * @return void
	 */
	public function test_instance_creation(): void {
		$this->assertInstanceOf( FieldCloner::class, $this->cloner );
		$this->assertEquals( 30, $this->cloner->get_priority() );
	}

	/**
	 * Test backup retrieval for a post
	 *
	 * @return void
	 */
	public function test_get_post_backups(): void {
		$post_id = 123;
		
		// Should return empty array initially.
		$backups = $this->cloner->get_post_backups( $post_id );
		$this->assertIsArray( $backups );
		$this->assertEmpty( $backups );
	}

	/**
	 * Test backup retrieval returns correct structure
	 *
	 * @return void
	 */
	public function test_backup_structure(): void {
		global $wpdb;
		
		$post_id   = 123;
		$user_id   = 1;
		$backup_id = 'backup_123_' . time() . '_test1234';
		
		$backup_data = [
			'post_id'    => $post_id,
			'timestamp'  => current_time( 'mysql' ),
			'user_id'    => $user_id,
			'field_data' => [
				'field_test1' => [
					'value' => 'Test Value 1',
					'label' => 'Test Field 1',
					'type'  => 'text',
				],
				'field_test2' => [
					'value' => 'Test Value 2',
					'label' => 'Test Field 2',
					'type'  => 'textarea',
				],
			],
		];
		
		$table_name = $wpdb->prefix . 'acf_field_backups';
		
		// Insert test backup.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->insert(
			$table_name,
			[
				'backup_id'   => $backup_id,
				'post_id'     => $post_id,
				'user_id'     => $user_id,
				'backup_data' => wp_json_encode( $backup_data ),
				'created_at'  => current_time( 'mysql' ),
			],
			[ '%s', '%d', '%d', '%s', '%s' ]
		);
		
		// Retrieve backups.
		$backups = $this->cloner->get_post_backups( $post_id );
		
		$this->assertIsArray( $backups );
		$this->assertCount( 1, $backups );
		
		$backup = $backups[0];
		$this->assertEquals( $backup_id, $backup['backup_id'] );
		$this->assertEquals( $post_id, $backup['post_id'] );
		$this->assertEquals( $user_id, $backup['user_id'] );
		$this->assertEquals( 2, $backup['field_count'] );
		$this->assertContains( 'field_test1', $backup['fields'] );
		$this->assertContains( 'field_test2', $backup['fields'] );
	}

	/**
	 * Test backup deletion
	 *
	 * @return void
	 */
	public function test_delete_backup(): void {
		global $wpdb;
		
		$backup_id = 'backup_123_' . time() . '_delete';
		$table_name = $wpdb->prefix . 'acf_field_backups';
		
		// Insert test backup.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->insert(
			$table_name,
			[
				'backup_id'   => $backup_id,
				'post_id'     => 123,
				'user_id'     => 1,
				'backup_data' => wp_json_encode( [ 'test' => 'data' ] ),
				'created_at'  => current_time( 'mysql' ),
			],
			[ '%s', '%d', '%d', '%s', '%s' ]
		);
		
		// Verify backup exists.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$exists = $wpdb->get_var(
			$wpdb->prepare( "SELECT COUNT(*) FROM $table_name WHERE backup_id = %s", $backup_id )
		);
		$this->assertEquals( 1, $exists );
		
		// Delete backup.
		$result = $this->cloner->delete_backup( $backup_id );
		$this->assertTrue( $result );
		
		// Verify backup is deleted.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$exists = $wpdb->get_var(
			$wpdb->prepare( "SELECT COUNT(*) FROM $table_name WHERE backup_id = %s", $backup_id )
		);
		$this->assertEquals( 0, $exists );
	}

	/**
	 * Test restore backup with invalid ID
	 *
	 * @return void
	 */
	public function test_restore_backup_invalid_id(): void {
		$result = $this->cloner->restore_backup( 'invalid_backup_id' );
		
		$this->assertIsArray( $result );
		$this->assertFalse( $result['success'] );
		$this->assertArrayHasKey( 'message', $result );
	}

	/**
	 * Test restore backup with non-existent backup
	 *
	 * @return void
	 */
	public function test_restore_backup_not_found(): void {
		$backup_id = 'backup_999_' . time() . '_notfound';
		$result    = $this->cloner->restore_backup( $backup_id );
		
		$this->assertIsArray( $result );
		$this->assertFalse( $result['success'] );
		$this->assertStringContainsString( 'not found', strtolower( $result['message'] ) );
	}

	/**
	 * Test backup settings defaults
	 *
	 * @return void
	 */
	public function test_backup_settings_defaults(): void {
		// Test default retention days.
		$retention_days = get_option( 'silver_assist_acf_clone_fields_backup_retention_days', 30 );
		$this->assertEquals( 30, $retention_days );
		
		// Test default max backups.
		$max_backups = get_option( 'silver_assist_acf_clone_fields_backup_max_count', 100 );
		$this->assertEquals( 100, $max_backups );
		
		// Test backup creation enabled by default.
		$create_backup = get_option( 'silver_assist_acf_clone_fields_create_backup', true );
		$this->assertTrue( $create_backup );
	}

	/**
	 * Test backup table exists after initialization
	 *
	 * @return void
	 */
	public function test_backup_table_exists(): void {
		global $wpdb;
		
		$table_name = $wpdb->prefix . 'acf_field_backups';
		
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$table_exists = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table_name ) );
		
		$this->assertEquals( $table_name, $table_exists );
	}

	/**
	 * Test backup data integrity
	 *
	 * @return void
	 */
	public function test_backup_data_integrity(): void {
		global $wpdb;
		
		$post_id   = 456;
		$user_id   = 2;
		$backup_id = 'backup_456_' . time() . '_integrity';
		
		$field_data = [
			'field_complex' => [
				'value' => [
					'nested' => 'value',
					'array'  => [ 1, 2, 3 ],
				],
				'label' => 'Complex Field',
				'type'  => 'repeater',
			],
		];
		
		$backup_data = [
			'post_id'    => $post_id,
			'timestamp'  => current_time( 'mysql' ),
			'user_id'    => $user_id,
			'field_data' => $field_data,
		];
		
		$table_name = $wpdb->prefix . 'acf_field_backups';
		
		// Insert backup.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->insert(
			$table_name,
			[
				'backup_id'   => $backup_id,
				'post_id'     => $post_id,
				'user_id'     => $user_id,
				'backup_data' => wp_json_encode( $backup_data ),
				'created_at'  => current_time( 'mysql' ),
			],
			[ '%s', '%d', '%d', '%s', '%s' ]
		);
		
		// Retrieve and verify.
		$backups = $this->cloner->get_post_backups( $post_id );
		
		$this->assertCount( 1, $backups );
		$this->assertEquals( $backup_id, $backups[0]['backup_id'] );
		$this->assertEquals( 1, $backups[0]['field_count'] );
	}

	/**
	 * Test multiple backups for same post
	 *
	 * @return void
	 */
	public function test_multiple_backups_same_post(): void {
		global $wpdb;
		
		$post_id    = 789;
		$table_name = $wpdb->prefix . 'acf_field_backups';
		
		// Create 3 backups for the same post.
		for ( $i = 1; $i <= 3; $i++ ) {
			$backup_id = 'backup_789_' . time() . '_multi' . $i;
			
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->insert(
				$table_name,
				[
					'backup_id'   => $backup_id,
					'post_id'     => $post_id,
					'user_id'     => 1,
					'backup_data' => wp_json_encode( [ 'test' => 'data' . $i ] ),
					'created_at'  => current_time( 'mysql' ),
				],
				[ '%s', '%d', '%d', '%s', '%s' ]
			);
			
			// Sleep to ensure different timestamps.
			sleep( 1 );
		}
		
		// Retrieve backups.
		$backups = $this->cloner->get_post_backups( $post_id );
		
		$this->assertCount( 3, $backups );
		
		// Verify they're ordered by created_at DESC (newest first).
		for ( $i = 0; $i < 2; $i++ ) {
			$this->assertGreaterThanOrEqual(
				strtotime( $backups[ $i + 1 ]['created_at'] ),
				strtotime( $backups[ $i ]['created_at'] )
			);
		}
	}
}
