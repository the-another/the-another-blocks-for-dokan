<?php
/**
 * Vendor factory for tests.
 *
 * @package AnotherBlocksForDokan
 * @since 1.0.0
 */

namespace The_Another\Plugin\Blocks_For_Dokan\Blocks\Tests\Factories;

use Mockery;
use Mockery\MockInterface;

/**
 * Vendor factory class.
 */
class VendorFactory {

	/**
	 * Create a mock vendor object.
	 *
	 * @param array<string, mixed> $args Vendor data arguments.
	 * @return MockInterface Mock vendor object.
	 */
	public static function create( array $args = array() ): MockInterface {
		$defaults = array(
			'ID'              => 123,
			'user_login'      => 'testvendor',
			'user_email'      => 'vendor@example.com',
			'shop_name'       => 'Test Store',
			'shop_url'        => 'http://example.com/store/testvendor',
			'avatar'          => 'http://example.com/avatar.jpg',
			'banner'          => 'http://example.com/banner.jpg',
			'phone'           => '123-456-7890',
			'address'         => '123 Main St, City, State 12345',
			'rating'          => 4.5,
			'rating_count'    => 100,
			'is_featured'     => false,
			'social_profiles' => array(),
		);

		$args = array_merge( $defaults, $args );

		return Mockery::mock( 'WeDevs\Dokan\Vendor\Vendor' )
			->shouldReceive( 'get_id' )->andReturn( $args['ID'] )
			->shouldReceive( 'get_shop_name' )->andReturn( $args['shop_name'] )
			->shouldReceive( 'get_shop_url' )->andReturn( $args['shop_url'] )
			->shouldReceive( 'get_avatar' )->andReturn( $args['avatar'] )
			->shouldReceive( 'get_banner' )->andReturn( $args['banner'] )
			->shouldReceive( 'get_phone' )->andReturn( $args['phone'] )
			->shouldReceive( 'get_email' )->andReturn( $args['user_email'] )
			->shouldReceive( 'show_email' )->andReturn( true )
			->shouldReceive( 'get_rating' )->andReturn(
				array(
					'rating' => $args['rating'],
					'count'  => $args['rating_count'],
				)
			)
			->shouldReceive( 'get_social_profiles' )->andReturn( $args['social_profiles'] )
			->shouldReceive( 'is_featured' )->andReturn( $args['is_featured'] )
			->shouldReceive( 'get_shop_info' )->andReturn(
				array(
					'store_name' => $args['shop_name'],
					'address'    => $args['address'],
				)
			)
			->getMock();
	}
}
