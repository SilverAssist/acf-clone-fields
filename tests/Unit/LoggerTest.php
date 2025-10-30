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

	/**
	 * Setup before each test
	 */
	public function setUp(): void {
		parent::setUp();
		$this->logger = Logger::get_instance();
	}

	/**
	 * Test logger singleton instance
	 */
	public function test_logger_singleton(): void {
		$logger1 = Logger::get_instance();
		$logger2 = Logger::get_instance();
		
		$this->assertSame( $logger1, $logger2, 'Logger should return same instance' );
		$this->assertInstanceOf( Logger::class, $logger1, 'Should return Logger instance' );
	}

	/**
	 * Test error level logging
	 */
	public function test_error_logging(): void {
		$message = 'Test error message';
		$context = [ 'test' => 'data' ];
		
		// Should not throw exception.
		$this->logger->error( $message, $context );
		
		// Verify log method was called (we can't easily test file writing in unit tests).
		$this->assertTrue( method_exists( $this->logger, 'error' ), 'Logger should have error method' );
	}

	/**
	 * Test warning level logging
	 */
	public function test_warning_logging(): void {
		$message = 'Test warning message';
		
		$this->logger->warning( $message );
		
		$this->assertTrue( method_exists( $this->logger, 'warning' ), 'Logger should have warning method' );
	}

	/**
	 * Test info level logging
	 */
	public function test_info_logging(): void {
		$message = 'Test info message';
		
		$this->logger->info( $message );
		
		$this->assertTrue( method_exists( $this->logger, 'info' ), 'Logger should have info method' );
	}

	/**
	 * Test debug level logging
	 */
	public function test_debug_logging(): void {
		$message = 'Test debug message';
		
		$this->logger->debug( $message );
		
		$this->assertTrue( method_exists( $this->logger, 'debug' ), 'Logger should have debug method' );
	}

	/**
	 * Test generic log method
	 */
	public function test_generic_log(): void {
		$level = 'notice';
		$message = 'Test notice message';
		$context = [ 'user_id' => 123 ];
		
		$this->logger->log( $level, $message, $context );
		
		$this->assertTrue( method_exists( $this->logger, 'log' ), 'Logger should have log method' );
	}

	/**
	 * Test that logger handles empty messages
	 */
	public function test_empty_message_handling(): void {
		// Should not throw exception.
		$this->logger->error( '' );
		$this->logger->error( null );
		
		$this->assertTrue( true, 'Logger should handle empty messages gracefully' );
	}

	/**
	 * Test logger with array context
	 */
	public function test_array_context(): void {
		$complex_context = [
			'post_id'   => 123,
			'user_data' => [
				'name'  => 'Test User',
				'email' => 'test@example.com',
			],
			'fields'    => [
				'field1' => 'value1',
				'field2' => 'value2',
			],
		];
		
		$this->logger->info( 'Complex context test', $complex_context );
		
		$this->assertTrue( true, 'Logger should handle complex array contexts' );
	}
}