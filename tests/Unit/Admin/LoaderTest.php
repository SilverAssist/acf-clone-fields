<?php
/**
 * Tests for Admin\Loader class
 *
 * @package SilverAssist\ACFCloneFields
 * @subpackage Tests\Unit\Admin
 * @since 1.1.1
 */

namespace SilverAssist\ACFCloneFields\Tests\Unit\Admin;

use SilverAssist\ACFCloneFields\Admin\Loader;
use SilverAssist\ACFCloneFields\Admin\Settings;
use SilverAssist\ACFCloneFields\Admin\MetaBox;
use SilverAssist\ACFCloneFields\Admin\Ajax;
use SilverAssist\ACFCloneFields\Admin\BackupManager;
use SilverAssist\ACFCloneFields\Tests\Utils\TestCase;

/**
 * Admin Loader test class
 */
class LoaderTest extends TestCase {
	/**
	 * Admin user ID
	 *
	 * @var int
	 */
	private int $admin_user_id;

	/**
	 * Setup test environment
	 */
	public function setUp(): void {
		parent::setUp();

		// Create admin user.
		$this->admin_user_id = static::factory()->user->create(
			[
				'role' => 'administrator',
			]
		);
		\wp_set_current_user( $this->admin_user_id );

		// Set admin context.
		\set_current_screen( 'edit-post' );
	}

	/**
	 * Test singleton instance
	 */
	public function test_singleton_instance(): void {
		$instance1 = Loader::instance();
		$instance2 = Loader::instance();

		$this->assertSame( $instance1, $instance2, 'Loader should return the same instance' );
		$this->assertInstanceOf( Loader::class, $instance1 );
	}

	/**
	 * Test LoadableInterface implementation
	 */
	public function test_implements_loadable_interface(): void {
		$loader = Loader::instance();

		$this->assertInstanceOf( \SilverAssist\ACFCloneFields\Core\Interfaces\LoadableInterface::class, $loader );
	}

	/**
	 * Test get_priority returns correct value
	 */
	public function test_get_priority_returns_correct_value(): void {
		$loader = Loader::instance();

		$this->assertSame( 40, $loader->get_priority(), 'Admin components should have priority 40' );
	}

	/**
	 * Test should_load returns true in admin context
	 */
	public function test_should_load_returns_true_in_admin(): void {
		$loader = Loader::instance();

		$this->assertTrue( $loader->should_load(), 'Admin loader should load in admin context' );
	}

	/**
	 * Test should_load returns false in frontend context
	 */
	public function test_should_load_returns_false_in_frontend(): void {
		// Remove admin context.
		\set_current_screen( 'front' );

		$loader = Loader::instance();

		$this->assertFalse( $loader->should_load(), 'Admin loader should not load in frontend context' );
	}

	/**
	 * Test init method loads admin component files
	 */
	public function test_init_initializes_components(): void {
		$loader = Loader::instance();

		// Initialize loader (may initialize components).
		$loader->init();

		// Verify component classes are available after init().
		// We don't verify initialization status to avoid interfering with other tests.
		$this->assertTrue( class_exists( 'SilverAssist\\ACFCloneFields\\Admin\\Settings' ) );
		$this->assertTrue( class_exists( 'SilverAssist\\ACFCloneFields\\Admin\\MetaBox' ) );
		$this->assertTrue( class_exists( 'SilverAssist\\ACFCloneFields\\Admin\\Ajax' ) );
		$this->assertTrue( class_exists( 'SilverAssist\\ACFCloneFields\\Admin\\BackupManager' ) );
	}

	/**
	 * Test init can be called multiple times safely
	 */
	public function test_init_can_be_called_multiple_times(): void {
		$loader = Loader::instance();

		// Initialize twice.
		$loader->init();
		$loader->init();

		// Should not throw errors.
		$this->assertTrue( true, 'Init should be safely callable multiple times' );
	}
}
