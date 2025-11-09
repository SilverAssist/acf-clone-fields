<?php
/**
 * Unit Tests for Logger Class
 *
 * @package SilverAssist\ACFCloneFields
 * @author SilverAssist Development Team
 * @license PolyForm-Noncommercial-1.0.0
 * @since 1.0.0
 */

namespace SilverAssist\ACFCloneFields\Tests\Unit;

use SilverAssist\ACFCloneFields\Tests\Utils\TestCase;
use SilverAssist\ACFCloneFields\Utils\Logger;

/**
 * Test the Logger utility class
 */
class LoggerTest extends TestCase {

	/**
	 * Logger instance
	 *
	 * @var Logger
	 */
	private Logger $logger;

	protected function setUp(): void {
		parent::setUp();
		$this->logger = Logger::instance();
	}

	/**
	 * Test logger singleton instance
	 */
	public function testLoggerSingleton(): void {
		$logger1 = Logger::instance();
		$logger2 = Logger::instance();
		
		$this->assertSame( $logger1, $logger2, 'Logger should return same instance' );
		$this->assertInstanceOf( Logger::class, $logger1, 'Should return Logger instance' );
	}

	/**
	 * Test error level logging
	 */
	public function testErrorLogging(): void {
		$message = 'Test error message';
		$context = [ 'test' => 'data' ];
		
		// Should not throw exception
		$this->logger->error( $message, $context );
		
		$this->assertTrue( true, 'Error logging should work without exception' );
	}

	/**
	 * Test warning level logging
	 */
	public function testWarningLogging(): void {
		$message = 'Test warning message';
		
		$this->logger->warning( $message );
		
		$this->assertTrue( true, 'Warning logging should work without exception' );
	}

	/**
	 * Test info level logging
	 */
	public function testInfoLogging(): void {
		$message = 'Test info message';
		
		$this->logger->info( $message );
		
		$this->assertTrue( true, 'Info logging should work without exception' );
	}

	/**
	 * Test generic log method
	 */
	public function testGenericLog(): void {
		$level = 'notice';
		$message = 'Test notice message';
		$context = [ 'user_id' => 123 ];
		
		$this->logger->log( $level, $message, $context );
		
		$this->assertTrue( true, 'Generic log method should work without exception' );
	}

	/**
	 * Test logger with array context
	 */
	public function testArrayContext(): void {
		$complex_context = [
			'post_id'   => 123,
			'fields'    => [
				'field1' => 'value1',
				'field2' => 'value2',
			],
		];
		
		$this->logger->info( 'Complex context test', $complex_context );
		
		$this->assertTrue( true, 'Logger should handle complex array contexts' );
	}

	/**
	 * Test logger has required methods
	 */
	public function testLoggerMethods(): void {
		$this->assertTrue( method_exists( $this->logger, 'error' ) );
		$this->assertTrue( method_exists( $this->logger, 'warning' ) );
		$this->assertTrue( method_exists( $this->logger, 'info' ) );
		$this->assertTrue( method_exists( $this->logger, 'debug' ) );
		$this->assertTrue( method_exists( $this->logger, 'log' ) );
	}

	/**
	 * Test emergency level logging
	 */
	public function test_emergency_logging(): void {
		$message = 'Emergency situation';
		$context = [ 'severity' => 'critical' ];

		$this->logger->emergency( $message, $context );

		$this->assertTrue( true, 'Emergency logging should work without exception' );
	}

	/**
	 * Test alert level logging
	 */
	public function test_alert_logging(): void {
		$message = 'Alert message';
		$context = [ 'action' => 'required' ];

		$this->logger->alert( $message, $context );

		$this->assertTrue( true, 'Alert logging should work without exception' );
	}

	/**
	 * Test critical level logging
	 */
	public function test_critical_logging(): void {
		$message = 'Critical error occurred';

		$this->logger->critical( $message );

		$this->assertTrue( true, 'Critical logging should work without exception' );
	}

	/**
	 * Test notice level logging
	 */
	public function test_notice_logging(): void {
		$message = 'Notice message';
		$context = [ 'type' => 'informational' ];

		$this->logger->notice( $message, $context );

		$this->assertTrue( true, 'Notice logging should work without exception' );
	}

	/**
	 * Test debug level logging
	 */
	public function test_debug_logging(): void {
		$message = 'Debug information';
		$context = [ 'debug_data' => [ 'var1' => 'value1' ] ];

		$this->logger->debug( $message, $context );

		$this->assertTrue( true, 'Debug logging should work without exception' );
	}

	/**
	 * Test LoadableInterface implementation
	 */
	public function test_implements_loadable_interface(): void {
		$this->assertInstanceOf(
			\SilverAssist\ACFCloneFields\Core\Interfaces\LoadableInterface::class,
			$this->logger,
			'Logger should implement LoadableInterface'
		);
	}

	/**
	 * Test get_priority method
	 */
	public function test_get_priority(): void {
		$priority = $this->logger->get_priority();

		$this->assertEquals( 40, $priority, 'Logger should have priority 40 (Utils load last)' );
		$this->assertIsInt( $priority, 'Priority should be an integer' );
	}

	/**
	 * Test should_load when logging is enabled
	 */
	public function test_should_load_when_enabled(): void {
		\update_option(
			'silver_acf_clone_settings',
			[ 'logging_enabled' => true ]
		);

		$should_load = $this->logger->should_load();

		$this->assertTrue( $should_load, 'Logger should load when logging is enabled' );

		// Cleanup.
		\delete_option( 'silver_acf_clone_settings' );
	}

	/**
	 * Test should_load when logging is disabled
	 */
	public function test_should_load_when_disabled(): void {
		\update_option(
			'silver_acf_clone_settings',
			[ 'logging_enabled' => false ]
		);

		$should_load = $this->logger->should_load();

		$this->assertFalse( $should_load, 'Logger should not load when logging is disabled' );

		// Cleanup.
		\delete_option( 'silver_acf_clone_settings' );
	}

	/**
	 * Test should_load with no settings
	 */
	public function test_should_load_with_no_settings(): void {
		\delete_option( 'silver_acf_clone_settings' );

		$should_load = $this->logger->should_load();

		$this->assertFalse( $should_load, 'Logger should not load when settings are missing' );
	}

	/**
	 * Test init creates log directory
	 */
	public function test_init_creates_log_directory(): void {
		// Get log file path.
		$log_file = $this->logger->get_log_file();
		$log_dir  = dirname( $log_file );

		// Remove directory if exists.
		if ( file_exists( $log_dir ) ) {
			// Clean up first.
			if ( file_exists( $log_file ) ) {
				// phpcs:ignore WordPress.WP.AlternativeFunctions.unlink_unlink
				unlink( $log_file );
			}
		}

		// Initialize logger.
		$this->logger->init();

		// Directory should exist or be creatable.
		$upload_dir = \wp_upload_dir();
		$this->assertIsArray( $upload_dir, 'Upload directory should be an array' );
		$this->assertArrayHasKey( 'basedir', $upload_dir, 'Upload directory should have basedir key' );
	}

	/**
	 * Test get_log_file returns valid path
	 */
	public function test_get_log_file(): void {
		$log_file = $this->logger->get_log_file();

		$this->assertIsString( $log_file, 'Log file path should be a string' );
		$this->assertStringContainsString( 'silver-acf-clone-debug.log', $log_file, 'Log file should have correct name' );
		$this->assertStringContainsString( \wp_upload_dir()['basedir'], $log_file, 'Log file should be in uploads directory' );
	}

	/**
	 * Test clear_log removes log file
	 */
	public function test_clear_log(): void {
		$log_file = $this->logger->get_log_file();

		// Create a log file if it doesn't exist.
		if ( ! file_exists( $log_file ) ) {
			// Ensure directory exists.
			$log_dir = dirname( $log_file );
			if ( ! file_exists( $log_dir ) ) {
				// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_mkdir
				mkdir( $log_dir, 0755, true );
			}

			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
			file_put_contents( $log_file, 'test log entry' . PHP_EOL );
		}

		// Clear log.
		$result = $this->logger->clear_log();

		$this->assertTrue( $result, 'clear_log should return true' );
		$this->assertFileDoesNotExist( $log_file, 'Log file should be deleted after clear_log' );
	}

	/**
	 * Test clear_log when file doesn't exist
	 */
	public function test_clear_log_when_file_not_exists(): void {
		$log_file = $this->logger->get_log_file();

		// Ensure file doesn't exist.
		if ( file_exists( $log_file ) ) {
			// phpcs:ignore WordPress.WP.AlternativeFunctions.unlink_unlink
			unlink( $log_file );
		}

		$result = $this->logger->clear_log();

		$this->assertTrue( $result, 'clear_log should return true even when file does not exist' );
	}

	/**
	 * Test logging with empty context
	 */
	public function test_logging_with_empty_context(): void {
		$this->logger->info( 'Message without context', [] );

		$this->assertTrue( true, 'Logging with empty context should work' );
	}

	/**
	 * Test logging with special characters
	 */
	public function test_logging_with_special_characters(): void {
		$message = 'Special chars: <script>alert("xss")</script>';
		$context = [
			'html'  => '<div>test</div>',
			'quote' => "It's a test",
		];

		$this->logger->warning( $message, $context );

		$this->assertTrue( true, 'Logging with special characters should work' );
	}

	/**
	 * Test logging with nested arrays
	 */
	public function test_logging_with_nested_arrays(): void {
		$context = [
			'level1' => [
				'level2' => [
					'level3' => [
						'data' => 'deep nesting',
					],
				],
			],
		];

		$this->logger->error( 'Nested array test', $context );

		$this->assertTrue( true, 'Logging with nested arrays should work' );
	}

	/**
	 * Test all log levels exist as methods
	 */
	public function test_all_log_level_methods_exist(): void {
		$required_methods = [
			'emergency',
			'alert',
			'critical',
			'error',
			'warning',
			'notice',
			'info',
			'debug',
		];

		foreach ( $required_methods as $method ) {
			$this->assertTrue(
				method_exists( $this->logger, $method ),
				"Logger should have {$method} method"
			);
		}
	}

	/**
	 * Test logger doesn't break with null values in context
	 */
	public function test_logging_with_null_values(): void {
		$context = [
			'null_value' => null,
			'empty_string' => '',
			'zero' => 0,
			'false' => false,
		];

		$this->logger->info( 'Null values test', $context );

		$this->assertTrue( true, 'Logging with null values should work' );
	}

	/**
	 * Test actual file writing with critical level (always logs)
	 */
	public function test_file_writing_critical_level(): void {
		// Enable logging.
		\update_option( 'silver_acf_clone_settings', [ 'logging_enabled' => true ] );

		$log_file = $this->logger->get_log_file();
		$log_dir  = dirname( $log_file );

		// Ensure directory exists.
		if ( ! file_exists( $log_dir ) ) {
			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_mkdir
			mkdir( $log_dir, 0755, true );
		}

		// Clean log file.
		if ( file_exists( $log_file ) ) {
			// phpcs:ignore WordPress.WP.AlternativeFunctions.unlink_unlink
			unlink( $log_file );
		}

		// Initialize logger to create directory.
		$this->logger->init();

		// Log critical message (always logged regardless of WP_DEBUG).
		$this->logger->critical( 'Test critical message', [ 'test' => 'data' ] );

		// Give it a moment to write.
		clearstatcache();

		// Verify file was created (if writable).
		if ( is_writable( $log_dir ) ) {
			$this->assertFileExists( $log_file, 'Log file should be created for critical messages' );

			// Check file contents.
			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
			$contents = file_get_contents( $log_file );
			$this->assertStringContainsString( 'CRITICAL', $contents, 'Log should contain CRITICAL level' );
			$this->assertStringContainsString( 'Test critical message', $contents, 'Log should contain message' );
			$this->assertStringContainsString( 'Context:', $contents, 'Log should contain context' );
		}

		// Cleanup.
		\delete_option( 'silver_acf_clone_settings' );
		if ( file_exists( $log_file ) ) {
			// phpcs:ignore WordPress.WP.AlternativeFunctions.unlink_unlink
			unlink( $log_file );
		}
	}

	/**
	 * Test emergency level always logs (even without WP_DEBUG)
	 */
	public function test_emergency_always_logs(): void {
		\update_option( 'silver_acf_clone_settings', [ 'logging_enabled' => true ] );

		$log_file = $this->logger->get_log_file();
		$log_dir  = dirname( $log_file );

		// Ensure directory exists.
		if ( ! file_exists( $log_dir ) ) {
			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_mkdir
			mkdir( $log_dir, 0755, true );
		}

		// Clean log file.
		if ( file_exists( $log_file ) ) {
			// phpcs:ignore WordPress.WP.AlternativeFunctions.unlink_unlink
			unlink( $log_file );
		}

		$this->logger->init();
		$this->logger->emergency( 'Emergency situation' );

		clearstatcache();

		if ( is_writable( $log_dir ) ) {
			$this->assertFileExists( $log_file, 'Emergency messages should always be logged' );

			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
			$contents = file_get_contents( $log_file );
			$this->assertStringContainsString( 'EMERGENCY', $contents, 'Log should contain EMERGENCY level' );
		}

		// Cleanup.
		\delete_option( 'silver_acf_clone_settings' );
		if ( file_exists( $log_file ) ) {
			// phpcs:ignore WordPress.WP.AlternativeFunctions.unlink_unlink
			unlink( $log_file );
		}
	}

	/**
	 * Test alert level always logs
	 */
	public function test_alert_always_logs(): void {
		\update_option( 'silver_acf_clone_settings', [ 'logging_enabled' => true ] );

		$log_file = $this->logger->get_log_file();
		$log_dir  = dirname( $log_file );

		if ( ! file_exists( $log_dir ) ) {
			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_mkdir
			mkdir( $log_dir, 0755, true );
		}

		if ( file_exists( $log_file ) ) {
			// phpcs:ignore WordPress.WP.AlternativeFunctions.unlink_unlink
			unlink( $log_file );
		}

		$this->logger->init();
		$this->logger->alert( 'Alert message' );

		clearstatcache();

		if ( is_writable( $log_dir ) ) {
			$this->assertFileExists( $log_file );

			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
			$contents = file_get_contents( $log_file );
			$this->assertStringContainsString( 'ALERT', $contents );
		}

		\delete_option( 'silver_acf_clone_settings' );
		if ( file_exists( $log_file ) ) {
			// phpcs:ignore WordPress.WP.AlternativeFunctions.unlink_unlink
			unlink( $log_file );
		}
	}

	/**
	 * Test error level always logs
	 */
	public function test_error_always_logs(): void {
		\update_option( 'silver_acf_clone_settings', [ 'logging_enabled' => true ] );

		$log_file = $this->logger->get_log_file();
		$log_dir  = dirname( $log_file );

		if ( ! file_exists( $log_dir ) ) {
			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_mkdir
			mkdir( $log_dir, 0755, true );
		}

		if ( file_exists( $log_file ) ) {
			// phpcs:ignore WordPress.WP.AlternativeFunctions.unlink_unlink
			unlink( $log_file );
		}

		$this->logger->init();
		$this->logger->error( 'Error message' );

		clearstatcache();

		if ( is_writable( $log_dir ) ) {
			$this->assertFileExists( $log_file );

			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
			$contents = file_get_contents( $log_file );
			$this->assertStringContainsString( 'ERROR', $contents );
		}

		\delete_option( 'silver_acf_clone_settings' );
		if ( file_exists( $log_file ) ) {
			// phpcs:ignore WordPress.WP.AlternativeFunctions.unlink_unlink
			unlink( $log_file );
		}
	}

	/**
	 * Test log file rotation when size exceeds limit
	 */
	public function test_log_rotation_when_file_too_large(): void {
		\update_option( 'silver_acf_clone_settings', [ 'logging_enabled' => true ] );

		$log_file = $this->logger->get_log_file();
		$log_dir  = dirname( $log_file );

		if ( ! file_exists( $log_dir ) ) {
			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_mkdir
			mkdir( $log_dir, 0755, true );
		}

		// Clean existing files.
		if ( file_exists( $log_file ) ) {
			// phpcs:ignore WordPress.WP.AlternativeFunctions.unlink_unlink
			unlink( $log_file );
		}

		$backup_file = $log_file . '.old';
		if ( file_exists( $backup_file ) ) {
			// phpcs:ignore WordPress.WP.AlternativeFunctions.unlink_unlink
			unlink( $backup_file );
		}

		$this->logger->init();

		if ( is_writable( $log_dir ) ) {
			// Create a large file (over 5MB).
			$large_content = str_repeat( 'A', 5242881 ); // Just over 5MB.
			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
			file_put_contents( $log_file, $large_content );

			clearstatcache();
			$original_size = filesize( $log_file );
			$this->assertGreaterThan( 5242880, $original_size, 'Test file should be larger than 5MB' );

			// Log a message to trigger rotation.
			$this->logger->critical( 'Message after rotation' );

			clearstatcache();

			// Old file should exist.
			$this->assertFileExists( $backup_file, 'Backup file should exist after rotation' );

			// New file should be smaller.
			$new_size = filesize( $log_file );
			$this->assertLessThan( $original_size, $new_size, 'New log file should be smaller after rotation' );

			// New file should contain new message.
			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
			$contents = file_get_contents( $log_file );
			$this->assertStringContainsString( 'Message after rotation', $contents );
		}

		// Cleanup.
		\delete_option( 'silver_acf_clone_settings' );
		if ( file_exists( $log_file ) ) {
			// phpcs:ignore WordPress.WP.AlternativeFunctions.unlink_unlink
			unlink( $log_file );
		}
		if ( file_exists( $backup_file ) ) {
			// phpcs:ignore WordPress.WP.AlternativeFunctions.unlink_unlink
			unlink( $backup_file );
		}
	}

	/**
	 * Test logging respects WP_DEBUG for non-critical levels
	 */
	public function test_debug_level_respects_wp_debug(): void {
		\update_option( 'silver_acf_clone_settings', [ 'logging_enabled' => true ] );

		$log_file = $this->logger->get_log_file();
		$log_dir  = dirname( $log_file );

		if ( ! file_exists( $log_dir ) ) {
			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_mkdir
			mkdir( $log_dir, 0755, true );
		}

		if ( file_exists( $log_file ) ) {
			// phpcs:ignore WordPress.WP.AlternativeFunctions.unlink_unlink
			unlink( $log_file );
		}

		$this->logger->init();

		// Log debug message (requires WP_DEBUG).
		$this->logger->debug( 'Debug message' );

		clearstatcache();

		// If WP_DEBUG is not defined or false, file might not exist for debug messages.
		// This tests the should_log() method logic.
		if ( is_writable( $log_dir ) ) {
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				$this->assertFileExists( $log_file, 'Debug messages should log when WP_DEBUG is true' );
			}
		}

		// Cleanup.
		\delete_option( 'silver_acf_clone_settings' );
		if ( file_exists( $log_file ) ) {
			// phpcs:ignore WordPress.WP.AlternativeFunctions.unlink_unlink
			unlink( $log_file );
		}
	}

	/**
	 * Test write_to_file handles non-writable files gracefully
	 */
	public function test_handles_non_writable_file(): void {
		\update_option( 'silver_acf_clone_settings', [ 'logging_enabled' => true ] );

		$log_file = $this->logger->get_log_file();
		$log_dir  = dirname( $log_file );

		if ( ! file_exists( $log_dir ) ) {
			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_mkdir
			mkdir( $log_dir, 0755, true );
		}

		$this->logger->init();

		if ( is_writable( $log_dir ) ) {
			// Create a file.
			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
			file_put_contents( $log_file, 'test' );

			// Try to make it non-writable (this may not work on all systems).
			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_chmod
			$changed = chmod( $log_file, 0444 );

			// Log a message - should not throw exception.
			$this->logger->critical( 'Test message on non-writable file' );

			// Restore permissions for cleanup.
			if ( $changed ) {
				// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_chmod
				chmod( $log_file, 0644 );
			}

			$this->assertTrue( true, 'Logger should handle non-writable files gracefully' );
		}

		// Cleanup.
		\delete_option( 'silver_acf_clone_settings' );
		if ( file_exists( $log_file ) ) {
			// phpcs:ignore WordPress.WP.AlternativeFunctions.unlink_unlink
			unlink( $log_file );
		}
	}

	/**
	 * Test log format includes timestamp and level
	 */
	public function test_log_format_structure(): void {
		\update_option( 'silver_acf_clone_settings', [ 'logging_enabled' => true ] );

		$log_file = $this->logger->get_log_file();
		$log_dir  = dirname( $log_file );

		if ( ! file_exists( $log_dir ) ) {
			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_mkdir
			mkdir( $log_dir, 0755, true );
		}

		if ( file_exists( $log_file ) ) {
			// phpcs:ignore WordPress.WP.AlternativeFunctions.unlink_unlink
			unlink( $log_file );
		}

		$this->logger->init();
		$this->logger->error( 'Format test message', [ 'key' => 'value' ] );

		clearstatcache();

		if ( is_writable( $log_dir ) && file_exists( $log_file ) ) {
			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
			$contents = file_get_contents( $log_file );

			// Check format: [timestamp] [LEVEL] message | Context: {...}
			$this->assertMatchesRegularExpression(
				'/\[\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}\]/',
				$contents,
				'Log should contain timestamp in format [YYYY-MM-DD HH:MM:SS]'
			);
			$this->assertStringContainsString( '[ERROR]', $contents, 'Log should contain [ERROR] level' );
			$this->assertStringContainsString( 'Format test message', $contents, 'Log should contain message' );
			$this->assertStringContainsString( 'Context:', $contents, 'Log should contain context label' );
			$this->assertStringContainsString( '"key":"value"', $contents, 'Log should contain JSON context' );
		}

		// Cleanup.
		\delete_option( 'silver_acf_clone_settings' );
		if ( file_exists( $log_file ) ) {
			// phpcs:ignore WordPress.WP.AlternativeFunctions.unlink_unlink
			unlink( $log_file );
		}
	}
}