<?php
/**
 * Vendor Query Loop build_query_args helper unit tests.
 *
 * @package AnotherBlocksForDokan
 * @since 1.1.0
 */

namespace The_Another\Plugin\Blocks_Dokan\Blocks\Tests\Unit\VendorQueryLoop;

use PHPUnit\Framework\TestCase;
use Brain\Monkey;
use Brain\Monkey\Functions;

/**
 * Tests for theabd_vendor_query_loop_build_query_args().
 */
class QueryArgsBuilderTest extends TestCase {

	/**
	 * Set up test.
	 */
	protected function setUp(): void {
		parent::setUp();
		Monkey\setUp();
		Functions\when( 'sanitize_text_field' )->returnArg();
		Functions\when( 'wp_unslash' )->returnArg();
		Functions\when( 'absint' )->alias( static fn( $v ) => (int) abs( (int) $v ) );
		Functions\when( 'apply_filters' )->alias( static fn( $tag, $value ) => $value );
		require_once dirname( __DIR__, 3 ) . '/blocks/vendor-query-loop/render.php';
	}

	/**
	 * Tear down test.
	 */
	protected function tearDown(): void {
		Monkey\tearDown();
		parent::tearDown();
	}

	/**
	 * Default attributes should produce a seller-role query with sane defaults.
	 */
	public function test_default_attributes_produce_seller_role_query(): void {
		$args = theabd_vendor_query_loop_build_query_args(
			array(
				'perPage' => 12,
				'orderBy' => 'name',
			),
			1
		);

		$this->assertSame( 'seller', $args['role'] );
		$this->assertSame( 12, $args['number'] );
		$this->assertSame( 1, $args['paged'] );
		$this->assertSame( 'display_name', $args['orderby'] );
		$this->assertSame(
			'dokan_enable_selling',
			$args['meta_query'][0]['key']
		);
	}

	/**
	 * Enabling showFeaturedOnly should add the dokan_feature_seller meta clause.
	 */
	public function test_featured_only_adds_meta_query_clause(): void {
		$args = theabd_vendor_query_loop_build_query_args(
			array( 'showFeaturedOnly' => true ),
			1
		);
		$keys = array_column( array_filter( $args['meta_query'], 'is_array' ), 'key' );
		$this->assertContains( 'dokan_feature_seller', $keys );
	}

	/**
	 * Selecting orderBy=date should map to dokan's "most_recent" sort.
	 */
	/**
	 * Explicit filters argument should add search/location/orderby clauses without touching $_GET.
	 */
	/**
	 * Explicit filters argument should add search/location/orderby clauses.
	 */
	public function test_explicit_filters_are_applied(): void {
		$_GET = array();
		$args = theabd_vendor_query_loop_build_query_args(
			array( 'orderBy' => 'name' ),
			1,
			array(
				'stores_orderby'       => 'date',
				'dokan_seller_search'  => 'acme',
				'dokan_store_location' => 'US:CA',
			)
		);

		$this->assertSame( 'most_recent', $args['orderby'] );
		$keys = array_column( array_filter( $args['meta_query'], 'is_array' ), 'key' );
		$this->assertContains( 'dokan_store_name', $keys );
		$this->assertContains( 'dokan_profile_settings', $keys );
	}

	/**
	 * Selecting orderBy=date should map to dokan's "most_recent" sort.
	 */
	public function test_orderby_date_maps_to_most_recent(): void {
		$args = theabd_vendor_query_loop_build_query_args(
			array( 'orderBy' => 'date' ),
			1
		);
		$this->assertSame( 'most_recent', $args['orderby'] );
	}
}
