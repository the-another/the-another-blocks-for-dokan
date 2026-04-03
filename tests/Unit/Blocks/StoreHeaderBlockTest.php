<?php
/**
 * Store header block unit tests.
 *
 * @package AnotherBlocksForDokan
 * @since 1.0.0
 */

namespace The_Another\Plugin\Blocks_Dokan\Blocks\Tests\Unit\Blocks;

use PHPUnit\Framework\TestCase;
use Brain\Monkey;
use Brain\Monkey\Functions;
use Mockery;
use The_Another\Plugin\Blocks_Dokan\Blocks\Tests\Factories\VendorFactory;

/**
 * Store header block test class.
 */
class StoreHeaderBlockTest extends TestCase {

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
	 * Test render with valid vendor ID.
	 *
	 * @return void
	 */
	public function test_render_with_valid_vendor_id(): void {
		$vendor_id = 123;
		$vendor    = VendorFactory::create( array( 'ID' => $vendor_id ) );

		Functions\when( 'current_user_can' )->justReturn( true );
		Functions\when( 'dokan_is_user_seller' )->justReturn( true );

		$vendor_manager = Mockery::mock( 'VendorManager' );
		$vendor_manager->shouldReceive( 'get' )->andReturn( $vendor );

		$dokan_mock         = Mockery::mock( 'Dokan' );
		$dokan_mock->vendor = $vendor_manager;

		Functions\when( 'dokan' )->justReturn( $dokan_mock );
		Functions\when( 'dokan_get_seller_short_address' )->justReturn( '123 Main St' );

		Functions\when( 'get_block_wrapper_attributes' )
			->justReturn( 'class="wp-block-the-another-blocks-for-dokan-vendor-store-header theabd--vendor-store-header theabd--vendor-store-header-default"' );

		Functions\when( 'dokan_is_vendor_info_hidden' )->justReturn( false );
		Functions\when( 'dokan_get_readable_seller_rating' )->justReturn( '<div class="rating">4.5</div>' );
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

		$attributes = array(
			'vendorId'        => $vendor_id,
			'showBanner'      => true,
			'showContactInfo' => true,
			'showSocialLinks' => true,
			'showStoreHours'  => true,
			'layout'          => 'default',
		);

		$block_mock = Mockery::mock( 'WP_Block' );

		// Load render function.
		require_once ANOTHER_BLOCKS_FOR_DOKAN_PLUGIN_DIR . 'blocks/vendor-store-header/render.php';

		$output = theabd_render_vendor_store_header_block( $attributes, '', $block_mock );

		$this->assertNotEmpty( $output );
		$this->assertStringContainsString( 'theabd--vendor-store-header', $output );
		$this->assertStringContainsString( 'Test Store', $output );
	}

	/**
	 * Test render escapes output.
	 *
	 * @return void
	 */
	public function test_render_escapes_output(): void {
		$vendor_id = 123;
		$vendor    = VendorFactory::create(
			array(
				'ID'        => $vendor_id,
				'shop_name' => '<script>alert("xss")</script>',
			)
		);

		Functions\when( 'current_user_can' )->justReturn( true );
		Functions\when( 'dokan_is_user_seller' )->justReturn( true );

		$vendor_manager = Mockery::mock( 'VendorManager' );
		$vendor_manager->shouldReceive( 'get' )->andReturn( $vendor );

		$dokan_mock         = Mockery::mock( 'Dokan' );
		$dokan_mock->vendor = $vendor_manager;

		Functions\when( 'dokan' )->justReturn( $dokan_mock );
		Functions\when( 'get_block_wrapper_attributes' )->justReturn( '' );
		Functions\when( 'dokan_get_seller_short_address' )->justReturn( '' );
		Functions\when( 'dokan_is_vendor_info_hidden' )->justReturn( false );
		Functions\when( 'dokan_get_readable_seller_rating' )->justReturn( '' );
		Functions\when( 'dokan_is_store_open' )->justReturn( true );
		Functions\when( 'dokan_get_social_profile_fields' )->justReturn( array() );
		Functions\when( 'get_query_var' )->justReturn( 0 );
		Functions\when( 'get_post_type' )->justReturn( '' );
		Functions\when( 'esc_url' )->returnArg();
		Functions\when( 'esc_html__' )->returnArg();
		Functions\when( 'wp_kses_post' )->returnArg();
		Functions\when( 'antispambot' )->returnArg();
		Functions\when( 'sanitize_text_field' )->returnArg();
		Functions\when( 'absint' )->alias(
			function ( $value ) {
				return abs( (int) $value );
			}
		);

		$escape_fn = function ( $text ) {
			return htmlspecialchars( (string) $text, ENT_QUOTES, 'UTF-8' );
		};

		Functions\when( 'esc_html' )->alias( $escape_fn );
		Functions\when( 'esc_attr' )->alias( $escape_fn );

		require_once ANOTHER_BLOCKS_FOR_DOKAN_PLUGIN_DIR . 'blocks/vendor-store-header/render.php';

		$attributes = array( 'vendorId' => $vendor_id );
		$block_mock = Mockery::mock( 'WP_Block' );

		$output = theabd_render_vendor_store_header_block( $attributes, '', $block_mock );

		$this->assertStringNotContainsString( '<script>', $output );
		$this->assertStringContainsString( '&lt;script&gt;', $output );
	}

	/**
	 * Test render with invalid vendor ID.
	 *
	 * @return void
	 */
	public function test_render_with_invalid_vendor_id(): void {
		Functions\when( 'current_user_can' )->justReturn( true );
		Functions\when( 'dokan_is_user_seller' )->justReturn( false );
		Functions\when( 'get_query_var' )->justReturn( 0 );
		Functions\when( 'get_post_type' )->justReturn( '' );
		Functions\when( 'dokan' )->justReturn( Mockery::mock( 'Dokan' ) );
		Functions\when( 'get_user_by' )->justReturn( false );
		Functions\when( 'absint' )->alias(
			function ( $value ) {
				return abs( (int) $value );
			}
		);

		require_once ANOTHER_BLOCKS_FOR_DOKAN_PLUGIN_DIR . 'blocks/vendor-store-header/render.php';

		$attributes = array( 'vendorId' => 0 );
		$block_mock = Mockery::mock( 'WP_Block' );

		$output = theabd_render_vendor_store_header_block( $attributes, '', $block_mock );

		$this->assertEmpty( $output );
	}
}
