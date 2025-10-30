<?php
/**
 * Plugin Logger
 *
 * Provides logging functionality for the Silver Assist ACF Clone Fields plugin.
 * Supports multiple log levels and configurable output destinations.
 *
 * @package SilverAssist\ACFCloneFields
 * @subpackage Utils
 * @since 1.0.0
 * @version 1.0.0
 * @author Silver Assist
 */

namespace SilverAssist\ACFCloneFields\Utils;

use SilverAssist\ACFCloneFields\Core\Interfaces\LoadableInterface;

defined( 'ABSPATH' ) || exit;

/**
 * Class Logger
 *
 * Simple logging system for plugin debugging and monitoring.
 */
class Logger implements LoadableInterface {
	/**
	 * Singleton instance
	 *
	 * @var Logger|null
	 */
	private static ?Logger $instance = null;

	/**
	 * Log levels
	 */
	private const EMERGENCY = 'emergency';
	private const ALERT     = 'alert';
	private const CRITICAL  = 'critical';
	private const ERROR     = 'error';
	private const WARNING   = 'warning';
	private const NOTICE    = 'notice';
	private const INFO      = 'info';
	private const DEBUG     = 'debug';

	/**
	 * Log file path
	 *
	 * @var string
	 */
	private string $log_file;

	/**
	 * Get singleton instance
	 *
	 * @return Logger
	 */
	public static function instance(): Logger {
		if ( self::$instance === null ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Private constructor
	 */
	private function __construct() {
		$upload_dir     = wp_upload_dir();
		$this->log_file = $upload_dir['basedir'] . '/silver-acf-clone-debug.log';
	}

	/**
	 * Initialize the logger
	 *
	 * @return void
	 */
	public function init(): void {
		// Create log directory if it doesn't exist.
		$log_dir = dirname( $this->log_file );
		if ( ! file_exists( $log_dir ) ) {
			wp_mkdir_p( $log_dir );
		}
	}

	/**
	 * Get loading priority
	 *
	 * @return int
	 */
	public function get_priority(): int {
		return 40; // Utils load last.
	}

	/**
	 * Determine if logger should load
	 *
	 * @return bool
	 */
	public function should_load(): bool {
		$settings = get_option( 'silver_acf_clone_settings', [] );
		return $settings['logging_enabled'] ?? false;
	}

	/**
	 * Log a message with context
	 *
	 * @param string               $level Log level.
	 * @param string               $message Log message.
	 * @param array<string, mixed> $context Additional context.
	 * @return void
	 */
	public function log( string $level, string $message, array $context = [] ): void {
		if ( ! $this->should_log( $level ) ) {
			return;
		}

		$log_entry = $this->format_log_entry( $level, $message, $context );
		$this->write_to_file( $log_entry );
	}

	/**
	 * Log emergency message
	 *
	 * @param string               $message Log message.
	 * @param array<string, mixed> $context Additional context.
	 * @return void
	 */
	public function emergency( string $message, array $context = [] ): void {
		$this->log( self::EMERGENCY, $message, $context );
	}

	/**
	 * Log alert message
	 *
	 * @param string               $message Log message.
	 * @param array<string, mixed> $context Additional context.
	 * @return void
	 */
	public function alert( string $message, array $context = [] ): void {
		$this->log( self::ALERT, $message, $context );
	}

	/**
	 * Log critical message
	 *
	 * @param string               $message Log message.
	 * @param array<string, mixed> $context Additional context.
	 * @return void
	 */
	public function critical( string $message, array $context = [] ): void {
		$this->log( self::CRITICAL, $message, $context );
	}

	/**
	 * Log error message
	 *
	 * @param string               $message Log message.
	 * @param array<string, mixed> $context Additional context.
	 * @return void
	 */
	public function error( string $message, array $context = [] ): void {
		$this->log( self::ERROR, $message, $context );
	}

	/**
	 * Log warning message
	 *
	 * @param string               $message Log message.
	 * @param array<string, mixed> $context Additional context.
	 * @return void
	 */
	public function warning( string $message, array $context = [] ): void {
		$this->log( self::WARNING, $message, $context );
	}

	/**
	 * Log notice message
	 *
	 * @param string               $message Log message.
	 * @param array<string, mixed> $context Additional context.
	 * @return void
	 */
	public function notice( string $message, array $context = [] ): void {
		$this->log( self::NOTICE, $message, $context );
	}

	/**
	 * Log info message
	 *
	 * @param string               $message Log message.
	 * @param array<string, mixed> $context Additional context.
	 * @return void
	 */
	public function info( string $message, array $context = [] ): void {
		$this->log( self::INFO, $message, $context );
	}

	/**
	 * Log debug message
	 *
	 * @param string               $message Log message.
	 * @param array<string, mixed> $context Additional context.
	 * @return void
	 */
	public function debug( string $message, array $context = [] ): void {
		$this->log( self::DEBUG, $message, $context );
	}

	/**
	 * Check if should log based on level and WP_DEBUG setting
	 *
	 * @param string $level Log level.
	 * @return bool
	 */
	private function should_log( string $level ): bool {
		// Always log errors and critical messages.
		$critical_levels = [ self::EMERGENCY, self::ALERT, self::CRITICAL, self::ERROR ];
		if ( in_array( $level, $critical_levels, true ) ) {
			return true;
		}

		// Log other levels only if WP_DEBUG is enabled.
		return defined( 'WP_DEBUG' ) && WP_DEBUG;
	}

	/**
	 * Format log entry
	 *
	 * @param string               $level Log level.
	 * @param string               $message Log message.
	 * @param array<string, mixed> $context Additional context.
	 * @return string
	 */
	private function format_log_entry( string $level, string $message, array $context ): string {
		$timestamp = date( 'Y-m-d H:i:s' );
		$formatted = sprintf( '[%s] [%s] %s', $timestamp, strtoupper( $level ), $message );

		if ( ! empty( $context ) ) {
			$formatted .= ' | Context: ' . wp_json_encode( $context, JSON_UNESCAPED_SLASHES );
		}

		return $formatted . PHP_EOL;
	}

	/**
	 * Write log entry to file
	 *
	 * @param string $log_entry Formatted log entry.
	 * @return void
	 */
	private function write_to_file( string $log_entry ): void {
		// Check if file exists and is writable.
		if ( file_exists( $this->log_file ) && ! is_writable( $this->log_file ) ) {
			return;
		}

		// Check file size (rotate if too large - 5MB limit).
		if ( file_exists( $this->log_file ) && filesize( $this->log_file ) > 5242880 ) {
			$this->rotate_log_file();
		}

		// Write to file.
		// phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged -- Error handling managed
		@file_put_contents( $this->log_file, $log_entry, FILE_APPEND | LOCK_EX );
	}

	/**
	 * Rotate log file when it gets too large
	 *
	 * @return void
	 */
	private function rotate_log_file(): void {
		if ( ! file_exists( $this->log_file ) ) {
			return;
		}

		$backup_file = $this->log_file . '.old';

		// Remove old backup if exists.
		if ( file_exists( $backup_file ) ) {
			// phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged -- Error handling managed
			@unlink( $backup_file );
		}

		// Move current log to backup.
		// phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged -- Error handling managed
		@rename( $this->log_file, $backup_file );
	}

	/**
	 * Get log file path
	 *
	 * @return string
	 */
	public function get_log_file(): string {
		return $this->log_file;
	}

	/**
	 * Clear log file
	 *
	 * @return bool
	 */
	public function clear_log(): bool {
		if ( file_exists( $this->log_file ) ) {
			// phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged -- Error handling managed
			return @unlink( $this->log_file );
		}
		return true;
	}
}
