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
}