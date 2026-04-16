<?php
/**
 * Block registration integration tests.
 *
 * @package AnotherBlocksForDokan
 * @since 1.0.0
 */

namespace The_Another\Plugin\Blocks_For_Dokan\Blocks\Tests\Integration;

use PHPUnit\Framework\TestCase;
use Brain\Monkey;
use Brain\Monkey\Functions;
use The_Another\Plugin\Blocks_For_Dokan\Block_Registry;

/**
 * Block registration test class.
 */
class BlockRegistrationTest extends TestCase {

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
	 * Test all blocks are registered.
	 *
	 * @return void
	 */
	public function test_all_blocks_are_registered(): void {
		Functions\when( 'register_block_type' )->justReturn( true );
		Functions\when( 'register_block_type_from_metadata' )->justReturn( true );
		Functions\when( 'apply_filters' )->alias(
			function ( $filter, $value ) {
				return $value;
			}
		);

		$registry = new Block_Registry();

		$expected_blocks = array(
			'the-another/blocks-for-dokan-vendor-store-header',
			'the-another/blocks-for-dokan-vendor-store-sidebar',
			'the-another/blocks-for-dokan-vendor-store-tabs',
			'the-another/blocks-for-dokan-vendor-store-terms-conditions',
			'the-another/blocks-for-dokan-vendor-query-loop',
			'the-another/blocks-for-dokan-vendor-query-pagination',
			'the-another/blocks-for-dokan-vendor-card',
			'the-another/blocks-for-dokan-vendor-search',
			'the-another/blocks-for-dokan-vendor-store-name',
			'the-another/blocks-for-dokan-vendor-avatar',
			'the-another/blocks-for-dokan-vendor-rating',
			'the-another/blocks-for-dokan-vendor-store-address',
			'the-another/blocks-for-dokan-vendor-store-phone',
			'the-another/blocks-for-dokan-vendor-store-status',
			'the-another/blocks-for-dokan-vendor-store-banner',
			'the-another/blocks-for-dokan-product-vendor-info',
			'the-another/blocks-for-dokan-more-from-seller',
			'the-another/blocks-for-dokan-become-vendor-cta',
			'the-another/blocks-for-dokan-vendor-contact-form',
			'the-another/blocks-for-dokan-vendor-store-location',
			'the-another/blocks-for-dokan-vendor-store-hours',
		);

		$registered_blocks = $registry->get_registered_blocks();

		foreach ( $expected_blocks as $block_name ) {
			$this->assertTrue(
				$registry->is_registered( $block_name ),
				"Block {$block_name} should be registered"
			);
			$this->assertContains( $block_name, $registered_blocks );
		}
	}

	/**
	 * Test block registry filter.
	 *
	 * @return void
	 */
	public function test_block_registry_filter(): void {
		Functions\when( 'register_block_type' )->justReturn( true );
		Functions\when( 'register_block_type_from_metadata' )->justReturn( true );
		Functions\when( 'apply_filters' )->alias(
			function ( $filter, $value ) {
				if ( 'tanbfd_registered_blocks' === $filter ) {
					$value['the-another/blocks-for-dokan-custom-block'] = '/path/to/custom-block';
				}
				return $value;
			}
		);

		$registry = new Block_Registry();

		$this->assertTrue( $registry->is_registered( 'the-another/blocks-for-dokan-custom-block' ) );
	}
}
