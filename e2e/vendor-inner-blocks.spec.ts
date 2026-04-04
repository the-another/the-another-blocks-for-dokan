/**
 * E2E tests for vendor inner blocks rendered inside a query loop.
 *
 * Covers: vendor-rating, vendor-store-phone, vendor-store-status,
 * vendor-store-address, vendor-avatar, and vendor-store-name when
 * used as inner blocks of a vendor-query-loop card.
 */

import { test, expect } from '@wordpress/e2e-test-utils-playwright';
import {
	createVendor,
	deleteVendor,
	createPage,
	deletePage,
	queryLoopMarkup,
} from './helpers';

let vendorIds: number[] = [];

// ---------------------------------------------------------------------------
// All inner blocks in a single query-loop card
// ---------------------------------------------------------------------------
test.describe( 'Vendor inner blocks – rendering inside query loop', () => {
	test.beforeAll( async ( { requestUtils } ) => {
		vendorIds = [];
		const id = await createVendor( requestUtils, {
			index: 1,
			store_name: 'Inner Blocks Test Store',
			phone: '+1-555-0100',
			address: {
				street_1: '123 Test Lane',
				city: 'Testville',
				state: 'TX',
				zip: '75001',
				country: 'US',
			},
		} );
		vendorIds.push( id );
	} );

	test.afterAll( async ( { requestUtils } ) => {
		for ( const id of vendorIds ) {
			await deleteVendor( requestUtils, id );
		}
		vendorIds = [];
	} );

	test( 'renders rating, phone, status, address, avatar, and store name', async ( {
		page,
		requestUtils,
	} ) => {
		const innerBlocks = `<!-- wp:the-another/blocks-for-dokan-vendor-card -->
<!-- wp:the-another/blocks-for-dokan-vendor-avatar {"width":"80px","height":"80px"} /-->
<!-- wp:the-another/blocks-for-dokan-vendor-store-name {"tagName":"h3","isLink":true} /-->
<!-- wp:the-another/blocks-for-dokan-vendor-store-address {"showIcon":true} /-->
<!-- wp:the-another/blocks-for-dokan-vendor-store-phone {"showIcon":true,"isLink":true} /-->
<!-- wp:the-another/blocks-for-dokan-vendor-rating {"showCount":true} /-->
<!-- wp:the-another/blocks-for-dokan-vendor-store-status /-->
<!-- /wp:the-another/blocks-for-dokan-vendor-card -->`;

		const markup = queryLoopMarkup(
			{ perPage: 12, columns: 3, displayLayout: 'grid' },
			innerBlocks
		);

		const newPage = await createPage(
			requestUtils,
			'Inner Blocks E2E',
			markup
		);

		await page.goto( newPage.link );

		const card = page.locator( '.theabd--single-vendor' ).first();
		await expect( card ).toBeVisible();

		// --- Avatar ---
		const avatar = card.locator( '.theabd--vendor-avatar' );
		await expect( avatar ).toBeVisible();
		const avatarImg = avatar.locator( '.theabd--vendor-avatar-image' );
		await expect( avatarImg ).toBeVisible();
		await expect( avatarImg ).toHaveAttribute( 'src', /.+/ );

		// --- Store name (H3, linked) ---
		const nameBlock = card.locator( '.theabd--vendor-store-name' );
		await expect( nameBlock ).toBeVisible();
		expect(
			await nameBlock.evaluate( ( el ) => el.tagName )
		).toBe( 'H3' );
		const nameText = await nameBlock.textContent();
		if ( nameText && nameText.trim().length > 0 ) {
			await expect( nameBlock.locator( 'a' ) ).toBeVisible();
			await expect( nameBlock.locator( 'a' ) ).toHaveAttribute(
				'href',
				/.+/
			);
		}

		// --- Address with icon ---
		const addressBlock = card.locator( '.theabd--vendor-store-address' );
		const addressCount = await addressBlock.count();
		if ( addressCount > 0 ) {
			await expect( addressBlock ).toBeVisible();
			await expect(
				addressBlock.locator( '.dashicons-location' )
			).toBeVisible();
		}

		// --- Phone with icon and link ---
		const phoneBlock = card.locator( '.theabd--vendor-store-phone' );
		await expect( phoneBlock ).toBeVisible();
		await expect(
			phoneBlock.locator( '.dashicons-phone' )
		).toBeVisible();
		const telLink = phoneBlock.locator( 'a[href^="tel:"]' );
		await expect( telLink ).toBeVisible();

		// --- Rating (may be visually empty for vendors with zero reviews) ---
		const ratingBlock = card.locator( '.theabd--vendor-rating' );
		expect( await ratingBlock.count() ).toBeGreaterThan( 0 );

		// --- Status (open or closed) ---
		const statusBlock = card.locator( '.theabd--vendor-store-status' );
		await expect( statusBlock ).toBeVisible();
		// Should have either open or closed class.
		const statusClass = await statusBlock.getAttribute( 'class' );
		expect(
			statusClass?.includes( 'theabd--store-open' ) ||
				statusClass?.includes( 'theabd--store-closed' )
		).toBe( true );

		await deletePage( requestUtils, newPage.id );
	} );

	test( 'phone renders without link and icon when disabled', async ( {
		page,
		requestUtils,
	} ) => {
		const innerBlocks = `<!-- wp:the-another/blocks-for-dokan-vendor-card -->
<!-- wp:the-another/blocks-for-dokan-vendor-store-phone {"showIcon":false,"isLink":false} /-->
<!-- /wp:the-another/blocks-for-dokan-vendor-card -->`;

		const markup = queryLoopMarkup(
			{ perPage: 12, columns: 3, displayLayout: 'grid' },
			innerBlocks
		);

		const newPage = await createPage(
			requestUtils,
			'Phone No Link E2E',
			markup
		);

		await page.goto( newPage.link );

		const card = page.locator( '.theabd--single-vendor' ).first();
		await expect( card ).toBeVisible();

		const phoneBlock = card.locator( '.theabd--vendor-store-phone' );
		await expect( phoneBlock ).toBeVisible();

		// Should NOT contain a link.
		await expect( phoneBlock.locator( 'a' ) ).toHaveCount( 0 );
		// Should NOT contain an icon.
		await expect( phoneBlock.locator( '.dashicons' ) ).toHaveCount( 0 );

		await deletePage( requestUtils, newPage.id );
	} );

	test( 'store name renders as P without link when configured', async ( {
		page,
		requestUtils,
	} ) => {
		const innerBlocks = `<!-- wp:the-another/blocks-for-dokan-vendor-card -->
<!-- wp:the-another/blocks-for-dokan-vendor-store-name {"tagName":"p","isLink":false} /-->
<!-- /wp:the-another/blocks-for-dokan-vendor-card -->`;

		const markup = queryLoopMarkup(
			{ perPage: 12, columns: 3, displayLayout: 'grid' },
			innerBlocks
		);

		const newPage = await createPage(
			requestUtils,
			'Name No Link E2E',
			markup
		);

		await page.goto( newPage.link );

		const card = page.locator( '.theabd--single-vendor' ).first();
		await expect( card ).toBeVisible();

		const nameBlock = card.locator( '.theabd--vendor-store-name' );
		expect(
			await nameBlock.evaluate( ( el ) => el.tagName )
		).toBe( 'P' );
		await expect( nameBlock.locator( 'a' ) ).toHaveCount( 0 );

		await deletePage( requestUtils, newPage.id );
	} );
} );
