<?php
/**
 * Base Test Case for Silver Assist ACF Clone Fields
 *
 * Extends WP_UnitTestCase when WordPress Test Suite is available,
 * otherwise falls back to PHPUnit TestCase.
 */

namespace SilverAssist\ACFCloneFields\Tests\Utils;

use PHPUnit\Framework\TestCase as PHPUnitTestCase;

// Determine which base class to use.
if ( class_exists( 'WP_UnitTestCase' ) ) {
	/**
	 * Base test case using WordPress Test Suite
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
} else {
	/**
	 * Base test case using PHPUnit (fallback)
	 */
	abstract class TestCase extends PHPUnitTestCase {

		protected function setUp(): void {
			parent::setUp();
		}

		protected function tearDown(): void {
			parent::tearDown();
		}

		/**
		 * Create backup table for tests (mock implementation)
		 *
		 * @return void
		 */
		protected function create_backup_table(): void {
			// Mock implementation - does nothing without WordPress.
		}

		protected function create_mock_post( array $args = [] ): \stdClass {
			$defaults = [
				'ID'         => rand( 1, 1000 ),
				'post_title' => 'Test Post',
				'post_type'  => 'post',
			];

			$args = array_merge( $defaults, $args );
			$post = new \stdClass();

			foreach ( $args as $key => $value ) {
				$post->$key = $value;
			}

			return $post;
		}
	}
}
