/**
 * E2E tests for product-context blocks.
 *
 * Covers: product-vendor-info (context provider with inner blocks)
 * and more-from-seller (related products grid).
 *
 * Depends on the create-product / delete-product REST endpoints
 * in the e2e-test-helpers mu-plugin.
 */

import { test, expect } from '@wordpress/e2e-test-utils-playwright';
import {
	createVendors,
	deleteVendors,
	createProducts,
	deleteProducts,
	createPage,
	deletePage,
} from './helpers';

test.describe( 'Product context blocks – frontend rendering', () => {
	let vendorIds: number[] = [];
	let productIds: number[] = [];
	let pages: Array< { id: number; link: string } > = [];

	test.beforeAll( async ( { requestUtils } ) => {
		// Create all 3 vendors in one call.
		vendorIds = await createVendors( requestUtils, [
			{ index: 1, store_name: 'Product Info Vendor', phone: '+1-555-0300' },
			{ index: 2, store_name: 'Multi Product Vendor' },
			{ index: 3, store_name: 'Solo Product Vendor' },
		] );

		// Create all 5 products in one call.
		productIds = await createProducts( requestUtils, [
			{ vendor_id: vendorIds[ 0 ], title: 'Test Widget Alpha', price: 29.99 },
			{ vendor_id: vendorIds[ 1 ], title: 'Seller Product 1', price: 10 },
			{ vendor_id: vendorIds[ 1 ], title: 'Seller Product 2', price: 20 },
			{ vendor_id: vendorIds[ 1 ], title: 'Seller Product 3', price: 30 },
			{ vendor_id: vendorIds[ 2 ], title: 'Only Product', price: 15 },
		] );

		// Pre-create all 3 test pages.
		pages = [
			// Product vendor info page (uses productIds[0]).
			await createPage( requestUtils, 'Product Vendor Info E2E',
				`<!-- wp:the-another/blocks-for-dokan-product-vendor-info {"productId":${ productIds[ 0 ] }} -->
<!-- wp:the-another/blocks-for-dokan-vendor-store-name {"tagName":"h3","isLink":true} /-->
<!-- wp:the-another/blocks-for-dokan-vendor-avatar {"width":"60px","height":"60px"} /-->
<!-- /wp:the-another/blocks-for-dokan-product-vendor-info -->` ),
			// More from seller page (uses productIds[1] — multi-product vendor).
			await createPage( requestUtils, 'More From Seller E2E',
				`<!-- wp:the-another/blocks-for-dokan-more-from-seller {"productId":${ productIds[ 1 ] },"perPage":6,"columns":4,"orderBy":"date"} /-->` ),
			// More from seller empty page (uses productIds[4] — solo vendor).
			await createPage( requestUtils, 'More From Seller Empty E2E',
				`<!-- wp:the-another/blocks-for-dokan-more-from-seller {"productId":${ productIds[ 4 ] },"perPage":6,"columns":4} /-->` ),
		];
	} );

	test.afterAll( async ( { requestUtils } ) => {
		for ( const p of pages ) {
			await deletePage( requestUtils, p.id );
		}
		await deleteProducts( requestUtils, productIds );
		await deleteVendors( requestUtils, vendorIds );
		vendorIds = [];
		productIds = [];
		pages = [];
	} );

	test( 'renders vendor info with inner blocks for a product', async ( {
		page,
	} ) => {
		await page.goto( pages[ 0 ].link );

		const infoBlock = page.locator( '.theabd--product-vendor-info' );
		await expect( infoBlock ).toBeVisible();

		// Inner blocks should render with vendor context.
		const nameBlock = infoBlock.locator( '.theabd--vendor-store-name' );
		await expect( nameBlock ).toBeVisible();
		await expect( nameBlock ).not.toBeEmpty();
		await expect( nameBlock.locator( 'a' ) ).toBeVisible();

		const avatar = infoBlock.locator( '.theabd--vendor-avatar' );
		await expect( avatar ).toBeVisible();
		await expect(
			avatar.locator( '.theabd--vendor-avatar-image' )
		).toHaveAttribute( 'src', /.+/ );
	} );

	test( 'shows related products from the same seller', async ( {
		page,
	} ) => {
		await page.goto( pages[ 1 ].link );

		const moreBlock = page.locator( '.theabd--more-from-vendor' );
		await expect( moreBlock ).toBeVisible();

		// Title should be present.
		await expect(
			moreBlock.locator( '.theabd--more-from-vendor-title' )
		).toContainText( 'More from this seller' );

		// Should show other products (at least 1, ideally 2).
		const productList = moreBlock.locator( 'ul.products li' );
		const productCount = await productList.count();
		expect( productCount ).toBeGreaterThanOrEqual( 1 );

		// Footer link to vendor store should be present.
		const footerLink = moreBlock.locator(
			'.theabd--more-from-vendor-footer a'
		);
		await expect( footerLink ).toBeVisible();
		await expect( footerLink ).toContainText(
			'View all products from'
		);
	} );

	test( 'shows empty message when vendor has only one product', async ( {
		page,
	} ) => {
		await page.goto( pages[ 2 ].link );

		const moreBlock = page.locator( '.theabd--more-from-vendor' );
		await expect( moreBlock ).toBeVisible();

		// Should show empty state.
		await expect(
			moreBlock.locator( '.theabd--more-from-vendor-empty' )
		).toContainText( 'No other products found from this seller' );
	} );
} );
