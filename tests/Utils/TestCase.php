<?php
/**
 * Base Test Case for Silver Assist ACF Clone Fields
 */

namespace SilverAssist\ACFCloneFields\Tests\Utils;

use PHPUnit\Framework\TestCase as PHPUnitTestCase;

abstract class TestCase extends PHPUnitTestCase {

	protected function setUp(): void {
		parent::setUp();
	}

	protected function tearDown(): void {
		parent::tearDown();
	}

	protected function create_mock_post( array $args = [] ): \stdClass {
		$defaults = [
			'ID' => rand( 1, 1000 ),
			'post_title' => 'Test Post',
			'post_type' => 'post',
		];
		
		$args = array_merge( $defaults, $args );
		$post = new \stdClass();
		
		foreach ( $args as $key => $value ) {
			$post->$key = $value;
		}
		
		return $post;
	}
}
