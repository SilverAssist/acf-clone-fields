<?php
/**
 * Admin Loader
 *
 * Loads and initializes all Admin components including MetaBox, Settings, and Ajax handlers.
 *
 * @package SilverAssist\ACFCloneFields
 * @subpackage Admin
 * @since 1.0.0
 * @version 1.1.1
 * @author Silver Assist
 */

namespace SilverAssist\ACFCloneFields\Admin;

use SilverAssist\ACFCloneFields\Core\Interfaces\LoadableInterface;

defined( 'ABSPATH' ) || exit;

/**
 * Class Loader
 *
 * Manages loading of Admin components.
 */
class Loader implements LoadableInterface {
	/**
	 * Singleton instance
	 *
	 * @var Loader|null
	 */
	private static ?Loader $instance = null;

	/**
	 * Get singleton instance
	 *
	 * @return Loader
	 */
	public static function instance(): Loader {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Private constructor to prevent direct instantiation
	 */
	private function __construct() {}

	/**
	 * Initialize Admin components
	 *
	 * @return void
	 */
	public function init(): void {
		$this->load_admin_components();
		$this->init_components();
	}

	/**
	 * Get loading priority
	 *
	 * @return int
	 */
	public function get_priority(): int {
		return 40; // Admin components - after Services (30).
	}

	/**
	 * Determine if Admin should load
	 *
	 * @return bool
	 */
	public function should_load(): bool {
		return is_admin();
	}

	/**
	 * Load Admin component files
	 *
	 * @return void
	 */
	private function load_admin_components(): void {
		$admin_dir = plugin_dir_path( __FILE__ );

		// Admin component files.
		$admin_files = [
			'MetaBox.php',
			'Settings.php',
			'Ajax.php',
			'BackupManager.php',
		];

		foreach ( $admin_files as $file ) {
			$file_path = $admin_dir . $file;
			if ( file_exists( $file_path ) ) {
				require_once $file_path;
			}
		}
	}

	/**
	 * Initialize Admin components
	 *
	 * @return void
	 */
	private function init_components(): void {
		// Initialize Settings (always load in admin).
		if ( class_exists( 'SilverAssist\\ACFCloneFields\\Admin\\Settings' ) ) {
			// Initializing Settings component.
			Settings::instance()->init();
		}

		// Initialize MetaBox (only on edit screens).
		if ( class_exists( 'SilverAssist\\ACFCloneFields\\Admin\\MetaBox' ) ) {
			MetaBox::instance()->init();
		}

		// Initialize Ajax (always in admin to handle AJAX requests).
		if ( class_exists( 'SilverAssist\\ACFCloneFields\\Admin\\Ajax' ) ) {
			Ajax::instance()->init();
		}

		// Initialize BackupManager (for backup interface).
		if ( class_exists( 'SilverAssist\\ACFCloneFields\\Admin\\BackupManager' ) ) {
			BackupManager::instance()->init();
		}
	}
}
