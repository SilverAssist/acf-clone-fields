<?php
/**
 * Clone Options Integration Tests
 *
 * Tests the complete flow of clone options from AJAX request to field cloning,
 * validating that backup creation and field preservation options work correctly.
 *
 * @package SilverAssist\ACFCloneFields\Tests\Integration
 */

namespace SilverAssist\ACFCloneFields\Tests\Integration;

use SilverAssist\ACFCloneFields\Tests\Utils\TestCase;
use SilverAssist\ACFCloneFields\Admin\Ajax;
use SilverAssist\ACFCloneFields\Services\FieldCloner;

/**
 * Test clone options processing
 *
 * @covers \SilverAssist\ACFCloneFields\Admin\Ajax::prepare_clone_options
 * @covers \SilverAssist\ACFCloneFields\Services\FieldCloner::clone_fields
 */
class CloneOptionsTest extends TestCase {

	/**
	 * Ajax handler instance
	 *
	 * @var Ajax
	 */
	private Ajax $ajax;

	/**
	 * Field cloner instance
	 *
	 * @var FieldCloner
	 */
	private FieldCloner $cloner;

	/**
	 * Test source post ID
	 *
	 * @var int
	 */
	private int $source_post_id;

	/**
	 * Test target post ID
	 *
	 * @var int
	 */
	private int $target_post_id;

	/**
	 * Test user ID
	 *
	 * @var int
	 */
	private int $test_user_id;

	/**
	 * Set up test environment
	 */
	protected function setUp(): void {
		parent::setUp();

		$this->ajax   = Ajax::instance();
		$this->cloner = FieldCloner::instance();

		// Create test user with admin capabilities using factory.
		$this->test_user_id = static::factory()->user->create(
			[
				'role' => 'administrator',
			]
		);
		wp_set_current_user( $this->test_user_id );

		// Create backup table.
		$this->create_backup_table();

		// Create test posts with admin author.
		$this->source_post_id = wp_insert_post(
			[
				'post_title'  => 'Source Post for Clone Options Test',
				'post_type'   => 'post',
				'post_status' => 'publish',
				'post_author' => $this->test_user_id,
			]
		);

		$this->target_post_id = wp_insert_post(
			[
				'post_title'  => 'Target Post for Clone Options Test',
				'post_type'   => 'post',
				'post_status' => 'publish',
				'post_author' => $this->test_user_id,
			]
		);

		// Register test ACF field group if ACF is available.
		if ( function_exists( 'acf_add_local_field_group' ) ) {
			acf_add_local_field_group(
				[
					'key'      => 'group_test_clone_options',
					'title'    => 'Test Clone Options Fields',
					'fields'   => [
						[
							'key'   => 'field_test_text',
							'label' => 'Test Text Field',
							'name'  => 'test_text_field',
							'type'  => 'text',
						],
						[
							'key'   => 'field_test_textarea',
							'label' => 'Test Textarea Field',
							'name'  => 'test_textarea_field',
							'type'  => 'textarea',
						],
						[
							'key'   => 'field_ajax_test_no_backup',
							'label' => 'AJAX Test Field (No Backup)',
							'name'  => 'ajax_test_no_backup',
							'type'  => 'text',
						],
						[
							'key'   => 'field_ajax_test_with_backup',
							'label' => 'AJAX Test Field (With Backup)',
							'name'  => 'ajax_test_with_backup',
							'type'  => 'text',
						],
						[
							'key'   => 'field_test_no_backup',
							'label' => 'Test Field (No Backup)',
							'name'  => 'test_no_backup',
							'type'  => 'text',
						],
					],
					'location' => [
						[
							[
								'param'    => 'post_type',
								'operator' => '==',
								'value'    => 'post',
							],
						],
					],
				]
			);
		}
	}

	/**
	 * Create backup table for testing
	 */
	protected function create_backup_table(): void {
		global $wpdb;

		$table_name      = $wpdb->prefix . 'acf_field_backups';
		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE IF NOT EXISTS $table_name (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			backup_id varchar(255) NOT NULL,
			post_id bigint(20) unsigned NOT NULL,
			user_id bigint(20) unsigned NOT NULL,
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
	 * Clean up after tests
	 */
	protected function tearDown(): void {
		// Clean up posts.
		wp_delete_post( $this->source_post_id, true );
		wp_delete_post( $this->target_post_id, true );

		// Clean up test user.
		if ( $this->test_user_id ) {
			wp_delete_user( $this->test_user_id );
		}

		parent::tearDown();
	}	/**
	 * Test that create_backup option as boolean true is processed correctly
	 *
	 * Validates scenario: User checks "Create backup before cloning" checkbox.
	 * Expected: Backup should be created.
	 *
	 * @test
	 */
	public function test_create_backup_option_true_as_boolean(): void {
		$options = [ 'create_backup' => true ];

		$prepared_options = $this->invoke_private_method( $this->ajax, 'prepare_clone_options', [ $options ] );

		$this->assertTrue( $prepared_options['create_backup'], 'create_backup should be true when passed as boolean true' );
	}

	/**
	 * Test that create_backup option as boolean false is processed correctly
	 *
	 * Validates scenario: User unchecks "Create backup before cloning" checkbox.
	 * Expected: Backup should NOT be created.
	 *
	 * @test
	 */
	public function test_create_backup_option_false_as_boolean(): void {
		$options = [ 'create_backup' => false ];

		$prepared_options = $this->invoke_private_method( $this->ajax, 'prepare_clone_options', [ $options ] );

		$this->assertFalse( $prepared_options['create_backup'], 'create_backup should be false when passed as boolean false' );
	}

	/**
	 * Test that create_backup option as string "true" is processed correctly
	 *
	 * Validates scenario: jQuery sends checkbox value as string "true".
	 * Expected: Should be converted to boolean true.
	 *
	 * @test
	 */
	public function test_create_backup_option_true_as_string(): void {
		$options = [ 'create_backup' => 'true' ];

		$prepared_options = $this->invoke_private_method( $this->ajax, 'prepare_clone_options', [ $options ] );

		$this->assertTrue( $prepared_options['create_backup'], 'create_backup should be true when passed as string "true"' );
	}

	/**
	 * Test that create_backup option as string "false" is processed correctly
	 *
	 * Validates scenario: jQuery sends unchecked checkbox value as string "false".
	 * Expected: Should be converted to boolean false (this is the critical fix).
	 *
	 * @test
	 */
	public function test_create_backup_option_false_as_string(): void {
		$options = [ 'create_backup' => 'false' ];

		$prepared_options = $this->invoke_private_method( $this->ajax, 'prepare_clone_options', [ $options ] );

		$this->assertFalse( $prepared_options['create_backup'], 'create_backup should be false when passed as string "false"' );
	}

	/**
	 * Test that create_backup option as string "0" is processed correctly
	 *
	 * Validates scenario: jQuery sends unchecked checkbox value as string "0".
	 * Expected: Should be converted to boolean false.
	 *
	 * @test
	 */
	public function test_create_backup_option_zero_as_string(): void {
		$options = [ 'create_backup' => '0' ];

		$prepared_options = $this->invoke_private_method( $this->ajax, 'prepare_clone_options', [ $options ] );

		$this->assertFalse( $prepared_options['create_backup'], 'create_backup should be false when passed as string "0"' );
	}

	/**
	 * Test that create_backup option as integer 0 is processed correctly
	 *
	 * Validates scenario: jQuery sends unchecked checkbox value as integer 0.
	 * Expected: Should be converted to boolean false.
	 *
	 * @test
	 */
	public function test_create_backup_option_zero_as_integer(): void {
		$options = [ 'create_backup' => 0 ];

		$prepared_options = $this->invoke_private_method( $this->ajax, 'prepare_clone_options', [ $options ] );

		$this->assertFalse( $prepared_options['create_backup'], 'create_backup should be false when passed as integer 0' );
	}

	/**
	 * Test that preserve_empty option processes correctly when true
	 *
	 * Note: preserve_empty is not currently implemented in backend,
	 * but we test that unknown options are ignored.
	 *
	 * @test
	 */
	public function test_preserve_empty_option_true(): void {
		$options = [ 'preserve_empty' => true ];

		$prepared_options = $this->invoke_private_method( $this->ajax, 'prepare_clone_options', [ $options ] );

		// preserve_empty is not in default_options, so it should not be in result.
		$this->assertArrayNotHasKey( 'preserve_empty', $prepared_options, 'Unknown options should be ignored' );
	}

	/**
	 * Test that preserve_empty option processes correctly when false
	 *
	 * @test
	 */
	public function test_preserve_empty_option_false(): void {
		$options = [ 'preserve_empty' => false ];

		$prepared_options = $this->invoke_private_method( $this->ajax, 'prepare_clone_options', [ $options ] );

		// preserve_empty is not in default_options, so it should not be in result.
		$this->assertArrayNotHasKey( 'preserve_empty', $prepared_options, 'Unknown options should be ignored' );
	}

	/**
	 * Test that overwrite_existing option processes correctly
	 *
	 * @test
	 */
	public function test_overwrite_existing_option_true(): void {
		$options = [ 'overwrite_existing' => true ];

		$prepared_options = $this->invoke_private_method( $this->ajax, 'prepare_clone_options', [ $options ] );

		$this->assertTrue( $prepared_options['overwrite_existing'], 'overwrite_existing should be true when passed as true' );
	}

	/**
	 * Test that overwrite_existing option processes correctly when false
	 *
	 * @test
	 */
	public function test_overwrite_existing_option_false(): void {
		$options = [ 'overwrite_existing' => false ];

		$prepared_options = $this->invoke_private_method( $this->ajax, 'prepare_clone_options', [ $options ] );

		$this->assertFalse( $prepared_options['overwrite_existing'], 'overwrite_existing should be false when passed as false' );
	}

	/**
	 * Test that backup is NOT created when create_backup is false
	 *
	 * This is the critical integration test that validates the complete flow.
	 *
	 * @test
	 */
	public function test_no_backup_created_when_option_false(): void {
		global $wpdb;

		// Mock ACF field.
		$field_key = 'field_test_no_backup';
		update_post_meta( $this->source_post_id, $field_key, 'test_value' );
		update_post_meta( $this->target_post_id, $field_key, 'old_value' );

		$options = [
			'create_backup'      => false,
			'overwrite_existing' => true,
		];

		// Count backups before clone.
		$table_name     = $wpdb->prefix . 'acf_field_backups';
		$backups_before = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$table_name} WHERE post_id = %d",
				$this->target_post_id
			)
		);

		// Execute clone.
		$result = $this->cloner->clone_fields(
			$this->source_post_id,
			$this->target_post_id,
			[ $field_key ],
			$options
		);

		// Count backups after clone.
		$backups_after = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$table_name} WHERE post_id = %d",
				$this->target_post_id
			)
		);

		$this->assertEquals(
			$backups_before,
			$backups_after,
			'No new backup should be created when create_backup is false'
		);
	}

	/**
	 * Test that backup IS created when create_backup is true
	 *
	 * @test
	 */
	public function test_backup_created_when_option_true(): void {
		global $wpdb;

		// Use the test field we registered in setUp.
		$field_name = 'test_text_field';

		// Set field values using ACF or post meta.
		if ( function_exists( 'update_field' ) ) {
			update_field( $field_name, 'source_test_value', $this->source_post_id );
			update_field( $field_name, 'target_old_value', $this->target_post_id );
		} else {
			update_post_meta( $this->source_post_id, $field_name, 'source_test_value' );
			update_post_meta( $this->target_post_id, $field_name, 'target_old_value' );
		}

		$options = [
			'create_backup'      => true,
			'overwrite_existing' => true,
		];

		// Count backups before clone.
		$table_name     = $wpdb->prefix . 'acf_field_backups';
		$backups_before = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$table_name} WHERE post_id = %d",
				$this->target_post_id
			)
		);

		// Execute clone.
		$result = $this->cloner->clone_fields(
			$this->source_post_id,
			$this->target_post_id,
			[ $field_name ],
			$options
		);

		// Count backups after clone.
		$backups_after = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$table_name} WHERE post_id = %d",
				$this->target_post_id
			)
		);

		$this->assertEquals(
			$backups_before + 1,
			$backups_after,
			'A new backup should be created when create_backup is true'
		);
	}

	/**
	 * Test complete AJAX flow with create_backup as false
	 *
	 * Simulates the actual AJAX request from JavaScript to verify
	 * end-to-end behavior.
	 *
	 * @test
	 */
	public function test_ajax_flow_with_backup_disabled(): void {
		global $wpdb;

		// Note: User already set up in setUp() with proper capabilities.

		// Mock ACF field.
		$field_key = 'field_ajax_test_no_backup';
		update_post_meta( $this->source_post_id, $field_key, 'ajax_test_value' );
		update_post_meta( $this->target_post_id, $field_key, 'ajax_old_value' );

		// Simulate AJAX request with create_backup as false (as string, like jQuery sends).
		$_POST = [
			'action'         => 'acf_clone_execute_clone',
			'nonce'          => wp_create_nonce( 'silver_assist_acf_clone_fields_ajax' ),
			'source_post_id' => $this->source_post_id,
			'target_post_id' => $this->target_post_id,
			'field_keys'     => [ $field_key ],
			'options'        => [
				'create_backup'      => 'false', // String "false" like jQuery sends.
				'overwrite_existing' => 'true',
			],
		];

		// Count backups before.
		$table_name     = $wpdb->prefix . 'acf_field_backups';
		$backups_before = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$table_name} WHERE post_id = %d",
				$this->target_post_id
			)
		);

		// Execute AJAX handler.
		try {
			$this->ajax->handle_execute_clone();
		} catch ( \WPAjaxDieContinueException $e ) {
			// Expected - wp_send_json_success() throws this in tests.
		}

		// Count backups after.
		$backups_after = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$table_name} WHERE post_id = %d",
				$this->target_post_id
			)
		);

		$this->assertEquals(
			$backups_before,
			$backups_after,
			'AJAX flow with create_backup="false" should not create backup'
		);
	}

	/**
	 * Test complete AJAX flow with create_backup as true
	 *
	 * @test
	 */
	public function test_ajax_flow_with_backup_enabled(): void {
		global $wpdb;

		// Note: User already set up in setUp() with proper capabilities.

		// Mock ACF field.
		$field_key = 'field_ajax_test_with_backup';
		update_post_meta( $this->source_post_id, $field_key, 'ajax_test_value' );
		update_post_meta( $this->target_post_id, $field_key, 'ajax_old_value' );

		// Simulate AJAX request with create_backup as true (as string, like jQuery sends).
		$_POST = [
			'action'         => 'acf_clone_execute_clone',
			'nonce'          => wp_create_nonce( 'silver_assist_acf_clone_fields_ajax' ),
			'source_post_id' => $this->source_post_id,
			'target_post_id' => $this->target_post_id,
			'field_keys'     => [ $field_key ],
			'options'        => [
				'create_backup'      => 'true', // String "true" like jQuery sends.
				'overwrite_existing' => 'true',
			],
		];

		// Count backups before.
		$table_name     = $wpdb->prefix . 'acf_field_backups';
		$backups_before = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$table_name} WHERE post_id = %d",
				$this->target_post_id
			)
		);

		// Execute AJAX handler.
		try {
			$this->ajax->handle_execute_clone();
		} catch ( \WPAjaxDieContinueException $e ) {
			// Expected - wp_send_json_success() throws this in tests.
		}

		// Count backups after.
		$backups_after = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$table_name} WHERE post_id = %d",
				$this->target_post_id
			)
		);

		$this->assertEquals(
			$backups_before + 1,
			$backups_after,
			'AJAX flow with create_backup="true" should create backup'
		);
	}

	/**
	 * Helper method to invoke private methods for testing
	 *
	 * @param object $object Object instance.
	 * @param string $method_name Method name.
	 * @param array  $parameters Method parameters.
	 * @return mixed Method result.
	 * @throws \ReflectionException If method doesn't exist.
	 */
	private function invoke_private_method( object $object, string $method_name, array $parameters = [] ) {
		$reflection = new \ReflectionClass( get_class( $object ) );
		$method     = $reflection->getMethod( $method_name );
		$method->setAccessible( true );

		return $method->invokeArgs( $object, $parameters );
	}
}
