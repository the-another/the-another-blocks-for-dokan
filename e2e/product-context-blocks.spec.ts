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
	createVendor,
	deleteVendor,
	createProduct,
	deleteProduct,
	createPage,
	deletePage,
} from './helpers';

let vendorIds: number[] = [];
let productIds: number[] = [];

// ---------------------------------------------------------------------------
// Product vendor info
// ---------------------------------------------------------------------------
test.describe( 'Product Vendor Info – context provider rendering', () => {
	test.beforeAll( async ( { requestUtils } ) => {
		vendorIds = [];
		productIds = [];

		const vendorId = await createVendor( requestUtils, {
			index: 1,
			store_name: 'Product Info Vendor',
			phone: '+1-555-0300',
		} );
		vendorIds.push( vendorId );

		const productId = await createProduct( requestUtils, {
			vendor_id: vendorId,
			title: 'Test Widget Alpha',
			price: 29.99,
		} );
		productIds.push( productId );
	} );

	test.afterAll( async ( { requestUtils } ) => {
		for ( const id of productIds ) {
			await deleteProduct( requestUtils, id );
		}
		for ( const id of vendorIds ) {
			await deleteVendor( requestUtils, id );
		}
		vendorIds = [];
		productIds = [];
	} );

	test( 'renders vendor info with inner blocks for a product', async ( {
		page,
		requestUtils,
	} ) => {
		const productId = productIds[ 0 ];
		const content = `<!-- wp:the-another/blocks-for-dokan-product-vendor-info {"productId":${ productId }} -->
<!-- wp:the-another/blocks-for-dokan-vendor-store-name {"tagName":"h3","isLink":true} /-->
<!-- wp:the-another/blocks-for-dokan-vendor-avatar {"width":"60px","height":"60px"} /-->
<!-- /wp:the-another/blocks-for-dokan-product-vendor-info -->`;

		const newPage = await createPage(
			requestUtils,
			'Product Vendor Info E2E',
			content
		);

		await page.goto( newPage.link );

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

		await deletePage( requestUtils, newPage.id );
	} );
} );

// ---------------------------------------------------------------------------
// More from seller
// ---------------------------------------------------------------------------
test.describe( 'More From Seller – related products rendering', () => {
	/** Vendor with 3 products (for "shows related" test). */
	let multiProductIds: number[] = [];

	/** Vendor with exactly 1 product (for "empty state" test). */
	let soloProductId: number;

	test.beforeAll( async ( { requestUtils } ) => {
		vendorIds = [];
		productIds = [];
		multiProductIds = [];

		// Multi-product vendor.
		const multiVendorId = await createVendor( requestUtils, {
			index: 1,
			store_name: 'Multi Product Vendor',
		} );
		vendorIds.push( multiVendorId );

		// Create 3 products so when we exclude one, 2 remain.
		for ( let i = 1; i <= 3; i++ ) {
			const productId = await createProduct( requestUtils, {
				vendor_id: multiVendorId,
				title: `Seller Product ${ i }`,
				price: 10 * i,
			} );
			productIds.push( productId );
			multiProductIds.push( productId );
		}

		// Solo-product vendor (for empty-state test).
		const soloVendorId = await createVendor( requestUtils, {
			index: 2,
			store_name: 'Solo Product Vendor',
		} );
		vendorIds.push( soloVendorId );

		soloProductId = await createProduct( requestUtils, {
			vendor_id: soloVendorId,
			title: 'Only Product',
			price: 15,
		} );
		productIds.push( soloProductId );
	} );

	test.afterAll( async ( { requestUtils } ) => {
		for ( const id of productIds ) {
			await deleteProduct( requestUtils, id );
		}
		for ( const id of vendorIds ) {
			await deleteVendor( requestUtils, id );
		}
		vendorIds = [];
		productIds = [];
		multiProductIds = [];
	} );

	test( 'shows related products from the same seller', async ( {
		page,
		requestUtils,
	} ) => {
		const productId = multiProductIds[ 0 ];
		const content = `<!-- wp:the-another/blocks-for-dokan-more-from-seller {"productId":${ productId },"perPage":6,"columns":4,"orderBy":"date"} /-->`;

		const newPage = await createPage(
			requestUtils,
			'More From Seller E2E',
			content
		);

		await page.goto( newPage.link );

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

		await deletePage( requestUtils, newPage.id );
	} );

	test( 'shows empty message when vendor has only one product', async ( {
		page,
		requestUtils,
	} ) => {
		const content = `<!-- wp:the-another/blocks-for-dokan-more-from-seller {"productId":${ soloProductId },"perPage":6,"columns":4} /-->`;

		const newPage = await createPage(
			requestUtils,
			'More From Seller Empty E2E',
			content
		);

		await page.goto( newPage.link );

		const moreBlock = page.locator( '.theabd--more-from-vendor' );
		await expect( moreBlock ).toBeVisible();

		// Should show empty state.
		await expect(
			moreBlock.locator( '.theabd--more-from-vendor-empty' )
		).toContainText( 'No other products found from this seller' );

		await deletePage( requestUtils, newPage.id );
	} );
} );
