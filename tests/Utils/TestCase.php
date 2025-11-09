<?php
/**
 * Base Test Case for Silver Assist ACF Clone Fields
 *
 * Extends WP_UnitTestCase for WordPress Test Suite integration.
 *
 * @package SilverAssist\ACFCloneFields
 * @subpackage Tests\Utils
 */

namespace SilverAssist\ACFCloneFields\Tests\Utils;

/**
 * Base test case using WordPress Test Suite
 *
 * All tests extend this class to have access to WordPress functions,
 * factory methods, and proper database transaction rollback.
 */
abstract class TestCase extends \WP_UnitTestCase {
	/**
	 * Create backup table for tests
	 *
	 * @return void
	 */
	protected function create_backup_table(): void {
		global $wpdb;
		$table_name      = $wpdb->prefix . 'acf_field_backups';
		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE IF NOT EXISTS $table_name (
			id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			post_id bigint(20) UNSIGNED NOT NULL,
			backup_data longtext NOT NULL,
			created_at datetime NOT NULL,
			created_by bigint(20) UNSIGNED NOT NULL,
			PRIMARY KEY  (id),
			KEY post_id (post_id),
			KEY created_at (created_at)
		) $charset_collate;";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );
	}
}
