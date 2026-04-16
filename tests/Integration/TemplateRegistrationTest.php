<?php
/**
 * Template registration integration tests.
 *
 * @package AnotherBlocksForDokan
 * @since 1.0.0
 */

namespace The_Another\Plugin\Blocks_For_Dokan\Blocks\Tests\Integration;

use PHPUnit\Framework\TestCase;
use Brain\Monkey;
use Brain\Monkey\Functions;
use The_Another\Plugin\Blocks_For_Dokan\Templates\Block_Templates_Controller;

/**
 * Template registration test class.
 */
class TemplateRegistrationTest extends TestCase {

	/**
	 * Set up test.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();
		Monkey\setUp();
	}

	/**
	 * Tear down test.
	 *
	 * @return void
	 */
	protected function tearDown(): void {
		Monkey\tearDown();
		parent::tearDown();
	}

	/**
	 * Test templates are initialized.
	 *
	 * @return void
	 */
	public function test_templates_are_initialized(): void {
		Functions\when( 'wp_is_block_theme' )->justReturn( true );
		Functions\when( 'get_block_templates' )->justReturn( array() );
		Functions\when( 'add_filter' )->justReturn( true );
		Functions\when( 'add_action' )->justReturn( true );
		Functions\when( '__' )->returnArg();
		Functions\when( 'register_block_template' )->justReturn( null );
		Functions\when( 'apply_filters' )->alias(
			function ( $filter, $value ) {
				return $value;
			}
		);

		$controller = new Block_Templates_Controller();
		$controller->init();

		$templates = $controller->get_templates();

		$this->assertNotEmpty( $templates );
		$this->assertCount( 2, $templates );
	}

	/**
	 * Test template filter.
	 *
	 * @return void
	 */
	public function test_template_filter(): void {
		Functions\when( 'wp_is_block_theme' )->justReturn( true );
		Functions\when( 'get_block_templates' )->justReturn( array() );
		Functions\when( 'add_filter' )->justReturn( true );
		Functions\when( 'add_action' )->justReturn( true );
		Functions\when( '__' )->returnArg();
		Functions\when( 'register_block_template' )->justReturn( null );
		Functions\when( 'apply_filters' )->alias(
			function ( $filter, $value ) {
				$this->assertIsArray( $value );
				return $value;
			}
		);

		$controller = new Block_Templates_Controller();
		$controller->init();

		$this->assertNotEmpty( $controller->get_templates() );
	}
}
