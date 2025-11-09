<?php
/**
 * Services Loader
 *
 * Loads and initializes all Services components including FieldDetector and FieldCloner.
 *
 * @package SilverAssist\ACFCloneFields
 * @subpackage Services
 * @since 1.0.0
 * @version 1.1.1
 * @author Silver Assist
 */

namespace SilverAssist\ACFCloneFields\Services;

use SilverAssist\ACFCloneFields\Core\Interfaces\LoadableInterface;

defined( 'ABSPATH' ) || exit;

/**
 * Class Loader
 *
 * Manages loading of Services components.
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
	 * Initialize Services components
	 *
	 * @return void
	 */
	public function init(): void {
		$this->load_services();
		$this->init_services();
	}

	/**
	 * Get loading priority
	 *
	 * @return int
	 */
	public function get_priority(): int {
		return 30; // Services - after Utils (20).
	}

	/**
	 * Determine if Services should load
	 *
	 * @return bool
	 */
	public function should_load(): bool {
		return function_exists( 'acf_get_field_groups' ) && function_exists( 'get_field' );
	}

	/**
	 * Load Services classes
	 *
	 * @return void
	 */
	private function load_services(): void {
		$services_dir = plugin_dir_path( __FILE__ );

		// Core Services files.
		$services_files = [
			'FieldDetector.php',
			'FieldCloner.php',
		];

		foreach ( $services_files as $file ) {
			$file_path = $services_dir . $file;
			if ( file_exists( $file_path ) ) {
				require_once $file_path;
			}
		}
	}

	/**
	 * Initialize Services components
	 *
	 * @return void
	 */
	private function init_services(): void {
		// Initialize FieldDetector.
		if ( class_exists( 'SilverAssist\\ACFCloneFields\\Services\\FieldDetector' ) ) {
			FieldDetector::instance()->init();
		}

		// Initialize FieldCloner.
		if ( class_exists( 'SilverAssist\\ACFCloneFields\\Services\\FieldCloner' ) ) {
			FieldCloner::instance()->init();
		}
	}
}
