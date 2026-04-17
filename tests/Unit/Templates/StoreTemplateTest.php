<?php
/**
 * Store template unit tests.
 *
 * @package The_Another_Blocks_For_Dokan
 * @since 1.0.0
 */

namespace The_Another\Plugin\Blocks_For_Dokan\Blocks\Tests\Unit\Templates;

use PHPUnit\Framework\TestCase;
use Brain\Monkey;
use Brain\Monkey\Functions;
use The_Another\Plugin\Blocks_For_Dokan\Templates\Store_Template;

/**
 * Store template test class.
 */
class StoreTemplateTest extends TestCase {

	/**
	 * Set up test.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();
		Monkey\setUp();

		// Common stubs needed for Store_Template.
		Functions\when( '__' )->returnArg();
		Functions\when( 'register_block_template' )->justReturn( null );
		Functions\when( 'add_filter' )->justReturn( true );
		Functions\when( 'add_action' )->justReturn( true );
		Functions\when( 'apply_filters' )->alias(
			function ( $filter, $value ) {
				return $value;
			}
		);
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
	 * Test template gets correct slug.
	 *
	 * @return void
	 */
	public function test_template_slug(): void {
		$this->assertEquals( 'dokan-store', Store_Template::SLUG );
	}

	/**
	 * Test get template title.
	 *
	 * @return void
	 */
	public function test_get_template_title(): void {
		$template = new Store_Template();
		$title    = $template->get_template_title();

		$this->assertEquals( 'Single Vendor Store', $title );
	}

	/**
	 * Test get template description.
	 *
	 * @return void
	 */
	public function test_get_template_description(): void {
		$template    = new Store_Template();
		$description = $template->get_template_description();

		$this->assertNotEmpty( $description );
		$this->assertStringContainsString( 'vendor', strtolower( $description ) );
	}

	/**
	 * Test template file path.
	 *
	 * @return void
	 */
	public function test_template_file_path(): void {
		Functions\when( 'plugin_dir_path' )->alias(
			function ( $file ) {
				return dirname( $file ) . '/';
			}
		);

		$template   = new Store_Template();
		$reflection = new \ReflectionClass( $template );
		$method     = $reflection->getMethod( 'get_template_file_path' );
		$method->setAccessible( true );

		$path = $method->invoke( $template );

		$this->assertStringEndsWith( 'templates/store.html', $path );
	}

	/**
	 * Test should render template.
	 *
	 * @return void
	 */
	public function test_should_render_template(): void {
		Functions\when( 'is_singular' )->justReturn( false );
		Functions\when( 'dokan_is_store_page' )->justReturn( true );

		$template   = new Store_Template();
		$reflection = new \ReflectionClass( $template );
		$method     = $reflection->getMethod( 'should_render_template' );
		$method->setAccessible( true );

		$this->assertTrue( $method->invoke( $template ) );
	}

	/**
	 * Assert that override_store_template() no longer dereferences WordPress
	 * core's internal canvas path via ABSPATH . WPINC.
	 *
	 * Canvas resolution is now delegated to WordPress core via the
	 * pre_get_block_file_template filter (wp.org review requirement).
	 *
	 * @return void
	 */
	public function test_override_store_template_does_not_reference_canvas_path(): void {
		$reflection = new \ReflectionMethod( Store_Template::class, 'override_store_template' );
		$source     = file_get_contents( $reflection->getFileName() ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- Reading local source file in test.
		$start      = $reflection->getStartLine();
		$end        = $reflection->getEndLine();

		$method_source = implode(
			"\n",
			array_slice( explode( "\n", $source ), $start - 1, $end - $start + 1 )
		);

		$this->assertStringNotContainsString( 'template-canvas.php', $method_source );
		$this->assertStringNotContainsString( 'ABSPATH . WPINC', $method_source );
	}
}
