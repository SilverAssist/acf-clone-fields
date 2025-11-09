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
	// Removed create_backup_table() - use Activator::create_tables() instead.
}
