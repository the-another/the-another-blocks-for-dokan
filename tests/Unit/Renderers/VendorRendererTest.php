<?php
/**
 * Vendor Renderer tests.
 *
 * @package AnotherBlocksForDokan
 * @since 1.0.3
 */

namespace The_Another\Plugin\Blocks_Dokan\Tests\Unit\Renderers;

use The_Another\Plugin\Blocks_Dokan\Renderers\Vendor_Renderer;
use PHPUnit\Framework\TestCase;
use Brain\Monkey;
use Brain\Monkey\Functions;
use Mockery;

/**
 * Tests for the Vendor_Renderer class.
 */
class VendorRendererTest extends TestCase {

	/**
	 * Set up test fixtures.
	 */
	protected function setUp(): void {
		parent::setUp();
		Monkey\setUp();

		// Stub functions that Context_Detector uses.
		Functions\when( 'get_query_var' )->justReturn( '' );
		Functions\when( 'get_user_by' )->justReturn( false );
		Functions\when( 'get_post_type' )->justReturn( '' );
		Functions\when( 'absint' )->alias(
			function ( $value ) {
				return abs( (int) $value );
			}
		);

		// Mock dokan() to return an object with a get_store_info method that exists
		// but returns nothing useful (so Context_Detector won't find a vendor).
		$vendor_manager = Mockery::mock();
		$vendor_manager->shouldReceive( 'get' )->andReturn( null );
		$dokan         = Mockery::mock();
		$dokan->vendor = $vendor_manager;
		Functions\when( 'dokan' )->justReturn( $dokan );
	}

	/**
	 * Tear down test fixtures.
	 */
	protected function tearDown(): void {
		Monkey\tearDown();
		Mockery::close();
		parent::tearDown();
	}

	/**
	 * Test resolve_vendor_from_context returns context vendor when valid.
	 */
	public function test_resolve_vendor_from_context_returns_context_vendor(): void {
		$vendor = array(
			'id'         => 42,
			'store_name' => 'Test Store',
			'shop_url'   => 'https://example.com/store/test',
		);

		$result = Vendor_Renderer::resolve_vendor_from_context( $vendor );

		$this->assertSame( 42, $result['id'] );
		$this->assertSame( 'Test Store', $result['store_name'] );
		$this->assertSame( 'https://example.com/store/test', $result['shop_url'] );
	}

	/**
	 * Test resolve_vendor_from_context returns null when context is null and no page context.
	 */
	public function test_resolve_vendor_from_context_returns_null_when_empty(): void {
		$result = Vendor_Renderer::resolve_vendor_from_context( null );

		$this->assertNull( $result );
	}

	/**
	 * Test resolve_vendor_from_context returns null when context has empty id.
	 */
	public function test_resolve_vendor_from_context_returns_null_for_empty_id(): void {
		$vendor = array(
			'id' => 0,
		);

		$result = Vendor_Renderer::resolve_vendor_from_context( $vendor );

		$this->assertNull( $result );
	}

	/**
	 * Test resolve_vendor_from_context ignores fields param when context vendor is valid.
	 */
	public function test_resolve_vendor_from_context_ignores_fields_when_context_valid(): void {
		$vendor = array(
			'id'    => 42,
			'phone' => '555-1234',
		);

		$result = Vendor_Renderer::resolve_vendor_from_context(
			$vendor,
			array( 'phone' => 'phone' )
		);

		// Should return context vendor as-is, not rebuild from fields.
		$this->assertSame( 42, $result['id'] );
		$this->assertSame( '555-1234', $result['phone'] );
	}
}
