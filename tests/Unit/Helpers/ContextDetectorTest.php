<?php
/**
 * Context detector unit tests.
 *
 * @package AnotherBlocksForDokan
 * @since 1.0.0
 */

namespace The_Another\Plugin\Blocks_Dokan\Blocks\Tests\Unit\Helpers;

use PHPUnit\Framework\TestCase;
use Brain\Monkey;
use Brain\Monkey\Functions;
use Mockery;
use The_Another\Plugin\Blocks_Dokan\Helpers\Context_Detector;

/**
 * Context detector test class.
 */
class ContextDetectorTest extends TestCase {

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
		Mockery::close();
		parent::tearDown();
	}

	/**
	 * Test detects vendor from query var.
	 *
	 * @return void
	 */
	public function test_detects_vendor_from_query_var(): void {
		Functions\when( 'dokan' )->justReturn( Mockery::mock( 'Dokan' ) );

		Functions\when( 'get_query_var' )->alias(
			function ( $query_var_name, $fallback = '' ) {
				if ( 'author' === $query_var_name ) {
					return 123;
				}
				return $fallback;
			}
		);

		Functions\when( 'dokan_is_user_seller' )->justReturn( true );

		Functions\when( 'absint' )->alias(
			function ( $value ) {
				return abs( (int) $value );
			}
		);

		$vendor_id = Context_Detector::get_vendor_id();

		$this->assertEquals( 123, $vendor_id );
	}

	/**
	 * Test detects vendor from store query var.
	 *
	 * @return void
	 */
	public function test_detects_vendor_from_store_query_var(): void {
		Functions\when( 'dokan' )->justReturn( Mockery::mock( 'Dokan' ) );

		Functions\when( 'get_query_var' )->alias(
			function ( $query_var_name, $fallback = '' ) {
				if ( 'store' === $query_var_name ) {
					return 'test-store';
				}
				if ( 'author' === $query_var_name ) {
					return 0;
				}
				return $fallback;
			}
		);

		$store_user     = Mockery::mock( 'WP_User' );
		$store_user->ID = 456;

		Functions\when( 'get_user_by' )->justReturn( $store_user );
		Functions\when( 'dokan_is_user_seller' )->justReturn( true );

		Functions\when( 'absint' )->alias(
			function ( $value ) {
				return abs( (int) $value );
			}
		);

		$vendor_id = Context_Detector::get_vendor_id();

		$this->assertEquals( 456, $vendor_id );
	}

	/**
	 * Test detects vendor from product context.
	 *
	 * @return void
	 */
	public function test_detects_vendor_from_product_context(): void {
		global $post;

		Functions\when( 'dokan' )->justReturn( Mockery::mock( 'Dokan' ) );

		Functions\when( 'get_query_var' )->alias(
			function ( $query_var_name, $fallback = '' ) {
				if ( 'author' === $query_var_name ) {
					return 0;
				}
				return $fallback;
			}
		);

		Functions\when( 'get_user_by' )->justReturn( false );

		$post              = Mockery::mock( 'WP_Post' );
		$post->ID          = 789;
		$post->post_author = 456;
		$post->post_type   = 'product';

		Functions\when( 'get_post_type' )->justReturn( 'product' );
		Functions\when( 'dokan_is_user_seller' )->justReturn( true );

		Functions\when( 'absint' )->alias(
			function ( $value ) {
				return abs( (int) $value );
			}
		);

		$vendor_id = Context_Detector::get_vendor_id();

		$this->assertEquals( 456, $vendor_id );
	}

	/**
	 * Test returns null when no context.
	 *
	 * @return void
	 */
	public function test_returns_null_when_no_context(): void {
		Functions\when( 'dokan' )->justReturn( Mockery::mock( 'Dokan' ) );

		Functions\when( 'get_query_var' )->alias(
			function ( $query_var_name, $fallback = '' ) {
				if ( 'author' === $query_var_name ) {
					return 0;
				}
				return $fallback;
			}
		);

		Functions\when( 'get_user_by' )->justReturn( false );
		Functions\when( 'get_post_type' )->justReturn( '' );

		Functions\when( 'absint' )->alias(
			function ( $value ) {
				return abs( (int) $value );
			}
		);

		global $post;
		$post = null;

		$vendor_id = Context_Detector::get_vendor_id();

		$this->assertNull( $vendor_id );
	}

	/**
	 * Test is store page.
	 *
	 * @return void
	 */
	public function test_is_store_page(): void {
		Functions\when( 'is_singular' )->justReturn( false );
		Functions\when( 'dokan_is_store_page' )->justReturn( true );

		$this->assertTrue( Context_Detector::is_store_page() );
	}

	/**
	 * Test is product page.
	 *
	 * @return void
	 */
	public function test_is_product_page(): void {
		Functions\when( 'is_singular' )->justReturn( true );

		$this->assertTrue( Context_Detector::is_product_page() );
	}
}
