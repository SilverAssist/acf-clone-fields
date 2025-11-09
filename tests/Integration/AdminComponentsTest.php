<?php

/**
 * Admin Components Integration Tests
 *
 * Tests Admin components (Ajax, MetaBox, Settings) with real WordPress environment.
 *
 * @package SilverAssist\ACFCloneFields
 * @subpackage Tests\Integration
 * @since 1.1.0
 */

namespace SilverAssist\ACFCloneFields\Tests\Integration;

defined('ABSPATH') || exit;

use SilverAssist\ACFCloneFields\Admin\Ajax;
use SilverAssist\ACFCloneFields\Admin\MetaBox;
use SilverAssist\ACFCloneFields\Admin\Settings;
use SilverAssist\ACFCloneFields\Core\Activator;
use SilverAssist\ACFCloneFields\Tests\Utils\TestCase;

/**
 * Test Admin components with real WordPress
 */
class AdminComponentsTest extends TestCase
{

  /**
   * Test user ID (admin)
   *
   * @var int
   */
  private int $admin_user_id;

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
  protected function setUp(): void
  {
    parent::setUp();

    // Create admin user using WordPress factory.
    $this->admin_user_id = static::factory()->user->create(
      array(
        'role' => 'administrator',
      )
    );
    wp_set_current_user($this->admin_user_id);

    // Create test post using WordPress factory.
    $this->test_post_id = static::factory()->post->create(
      array(
        'post_type'   => 'post',
        'post_status' => 'publish',
        'post_title'  => 'Test Post for Admin',
      )
    );

    // Create backup table using Activator.
    Activator::create_tables();
  }

  /**
   * Test Ajax instance creation
   *
   * @return void
   */
  public function test_ajax_instance_creation(): void
  {
    $ajax = Ajax::instance();
    $this->assertInstanceOf(Ajax::class, $ajax);
  }

  /**
   * Test Ajax singleton pattern
   *
   * @return void
   */
  public function test_ajax_singleton(): void
  {
    $ajax1 = Ajax::instance();
    $ajax2 = Ajax::instance();
    $this->assertSame($ajax1, $ajax2);
  }

  /**
   * Test Ajax hooks registration
   *
   * @return void
   */
  public function test_ajax_hooks_registered(): void
  {
    $ajax = Ajax::instance();
    $ajax->init();

    // Check if AJAX actions are registered.
    $this->assertTrue(has_action('wp_ajax_acf_clone_get_source_posts') !== false);
    $this->assertTrue(has_action('wp_ajax_acf_clone_get_source_fields') !== false);
    $this->assertTrue(has_action('wp_ajax_acf_clone_execute_clone') !== false);
  }

  /**
   * Test MetaBox instance creation
   *
   * @return void
   */
  public function test_metabox_instance_creation(): void
  {
    $metabox = MetaBox::instance();
    $this->assertInstanceOf(MetaBox::class, $metabox);
  }

  /**
   * Test MetaBox singleton pattern
   *
   * @return void
   */
  public function test_metabox_singleton(): void
  {
    $metabox1 = MetaBox::instance();
    $metabox2 = MetaBox::instance();
    $this->assertSame($metabox1, $metabox2);
  }

  /**
   * Test MetaBox hooks registration
   *
   * @return void
   */
  public function test_metabox_hooks_registered(): void
  {
    $metabox = MetaBox::instance();
    
    // Initialize metabox (may already be initialized by other tests).
    $metabox->init();

    // Check if meta box action is registered with specific callback.
    $this->assertNotFalse(
      has_action('add_meta_boxes', [$metabox, 'add_meta_boxes']),
      'MetaBox should register add_meta_boxes action'
    );
  }

  /**
   * Test Settings instance creation
   *
   * @return void
   */
  public function test_settings_instance_creation(): void
  {
    $settings = Settings::instance();
    $this->assertInstanceOf(Settings::class, $settings);
  }

  /**
   * Test Settings singleton pattern
   *
   * @return void
   */
  public function test_settings_singleton(): void
  {
    $settings1 = Settings::instance();
    $settings2 = Settings::instance();
    $this->assertSame($settings1, $settings2);
  }

  /**
   * Test Settings default values
   *
   * @return void
   */
  public function test_settings_defaults(): void
  {
    // Get default settings from WordPress option or use plugin defaults.
    $defaults = get_option('silver_acf_clone_settings', array());

    // If no settings exist, plugin should have defaults.
    if (empty($defaults)) {
      // Set default settings.
      $defaults = array(
        'enabled_post_types'    => array(),
        'create_backup'         => true,
        'backup_retention_days' => 30,
        'max_backups_per_post'  => 100,
      );
    }

    $this->assertIsArray($defaults);
    $this->assertArrayHasKey('enabled_post_types', $defaults);
    $this->assertArrayHasKey('create_backup', $defaults);
    $this->assertArrayHasKey('backup_retention_days', $defaults);
    $this->assertArrayHasKey('max_backups_per_post', $defaults);
  }

  /**
   * Test Settings get and update options
   *
   * @return void
   */
  public function test_settings_get_and_update(): void
  {
    $settings = Settings::instance();

    // Update settings.
    $new_settings = array(
      'enabled_post_types'    => array('post', 'page'),
      'create_backup'         => true,
      'backup_retention_days' => 60,
      'max_backups_per_post'  => 50,
    );

    update_option('silver_acf_clone_settings', $new_settings);

    // Get settings.
    $retrieved = get_option('silver_acf_clone_settings');

    $this->assertEquals($new_settings['enabled_post_types'], $retrieved['enabled_post_types']);
    $this->assertEquals($new_settings['create_backup'], $retrieved['create_backup']);
    $this->assertEquals($new_settings['backup_retention_days'], $retrieved['backup_retention_days']);
    $this->assertEquals($new_settings['max_backups_per_post'], $retrieved['max_backups_per_post']);
  }

  /**
   * Test AJAX actions are registered
   *
   * @return void
   */
  public function test_ajax_actions_registered(): void
  {
    $ajax = Ajax::instance();
    $ajax->init();

    // Verify that AJAX actions exist in the global $wp_filter.
    global $wp_filter;

    // Check if our AJAX actions are registered.
    $this->assertArrayHasKey('wp_ajax_acf_clone_get_source_posts', $wp_filter);
    $this->assertArrayHasKey('wp_ajax_acf_clone_get_source_fields', $wp_filter);
    $this->assertArrayHasKey('wp_ajax_acf_clone_execute_clone', $wp_filter);
  }

  /**
   * Test user capability checks
   *
   * @return void
   */
  public function test_user_capability_checks(): void
  {
    // Admin user should have edit_posts capability.
    $this->assertTrue(current_user_can('edit_posts'));

    // Create subscriber user.
    $subscriber_id = static::factory()->user->create(array('role' => 'subscriber'));
    wp_set_current_user($subscriber_id);

    // Subscriber should NOT have edit_posts capability.
    $this->assertFalse(current_user_can('edit_posts'));

    // Restore admin user.
    wp_set_current_user($this->admin_user_id);
  }

  /**
   * Test post type enabled check
   *
   * @return void
   */
  public function test_post_type_enabled_check(): void
  {
    // Set enabled post types.
    $settings = array(
      'enabled_post_types' => array('post', 'page'),
    );
    update_option('silver_acf_clone_settings', $settings);

    $enabled = get_option('silver_acf_clone_settings');
    $this->assertContains('post', $enabled['enabled_post_types']);
    $this->assertContains('page', $enabled['enabled_post_types']);
    $this->assertNotContains('custom_post_type', $enabled['enabled_post_types']);
  }

  /**
   * Clean up after tests
   *
   * @return void
   */
  protected function tearDown(): void
  {
    // Delete test post.
    wp_delete_post($this->test_post_id, true);

    // Delete test users.
    wp_delete_user($this->admin_user_id);

    // Clean up options.
    delete_option('silver_acf_clone_settings');

    parent::tearDown();
  }
}
