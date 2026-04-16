<?php
/**
 * Block rendering integration tests.
 *
 * @package AnotherBlocksForDokan
 * @since 1.0.0
 */

namespace The_Another\Plugin\Blocks_For_Dokan\Blocks\Tests\Integration;

use PHPUnit\Framework\TestCase;
use Brain\Monkey;
use Brain\Monkey\Functions;
use Mockery;

/**
 * Block rendering test class.
 */
class BlockRenderingTest extends TestCase {

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
	 * Test store header block renders correctly.
	 *
	 * @return void
	 */
	public function test_store_header_block_renders(): void {
		Functions\when( 'current_user_can' )->justReturn( true );
		Functions\when( 'dokan_is_user_seller' )->justReturn( true );

		$vendor_mock = Mockery::mock( 'WeDevs\Dokan\Vendor\Vendor' );
		$vendor_mock->shouldReceive( 'get_shop_name' )->andReturn( 'Test Store' );
		$vendor_mock->shouldReceive( 'get_shop_url' )->andReturn( 'http://example.com/store/test' );
		$vendor_mock->shouldReceive( 'get_avatar' )->andReturn( 'http://example.com/avatar.jpg' );
		$vendor_mock->shouldReceive( 'get_banner' )->andReturn( 'http://example.com/banner.jpg' );
		$vendor_mock->shouldReceive( 'get_phone' )->andReturn( '123-456-7890' );
		$vendor_mock->shouldReceive( 'get_email' )->andReturn( 'vendor@example.com' );
		$vendor_mock->shouldReceive( 'show_email' )->andReturn( true );
		$vendor_mock->shouldReceive( 'get_rating' )->andReturn(
			array(
				'rating' => 4.5,
				'count'  => 10,
			)
		);
		$vendor_mock->shouldReceive( 'get_social_profiles' )->andReturn( array() );
		$vendor_mock->shouldReceive( 'get_shop_info' )->andReturn( array( 'store_name' => 'Test Store' ) );
		$vendor_mock->shouldReceive( 'is_featured' )->andReturn( false );

		$vendor_manager = Mockery::mock( 'VendorManager' );
		$vendor_manager->shouldReceive( 'get' )->andReturn( $vendor_mock );

		$dokan_mock         = Mockery::mock( 'Dokan' );
		$dokan_mock->vendor = $vendor_manager;

		Functions\when( 'dokan' )->justReturn( $dokan_mock );
		Functions\when( 'get_block_wrapper_attributes' )->justReturn( 'class="tanbfd--vendor-store-header"' );
		Functions\when( 'dokan_get_seller_short_address' )->justReturn( '123 Main St' );
		Functions\when( 'dokan_is_vendor_info_hidden' )->justReturn( false );
		Functions\when( 'dokan_get_readable_seller_rating' )->justReturn( '<div>4.5</div>' );
		Functions\when( 'dokan_is_store_open' )->justReturn( true );
		Functions\when( 'dokan_get_social_profile_fields' )->justReturn( array() );
		Functions\when( 'get_query_var' )->justReturn( 0 );
		Functions\when( 'get_post_type' )->justReturn( '' );
		Functions\when( 'esc_html' )->returnArg();
		Functions\when( 'esc_url' )->returnArg();
		Functions\when( 'esc_attr' )->returnArg();
		Functions\when( 'esc_html__' )->returnArg();
		Functions\when( 'wp_kses_post' )->returnArg();
		Functions\when( 'antispambot' )->returnArg();
		Functions\when( 'sanitize_text_field' )->returnArg();
		Functions\when( 'absint' )->alias(
			function ( $value ) {
				return abs( (int) $value );
			}
		);

		// Load render function.
		require_once THE_ANOTHER_BLOCKS_FOR_DOKAN_PLUGIN_DIR . 'blocks/vendor-store-header/render.php';

		$block_mock = Mockery::mock( 'WP_Block' );
		$attributes = array(
			'vendorId'        => 123,
			'showBanner'      => true,
			'showContactInfo' => true,
			'showSocialLinks' => true,
			'showStoreHours'  => true,
			'layout'          => 'default',
		);

		$output = tanbfd_render_vendor_store_header_block( $attributes, '', $block_mock );

		$this->assertNotEmpty( $output );
		$this->assertStringContainsString( 'tanbfd--vendor-store-header', $output );
	}
}
