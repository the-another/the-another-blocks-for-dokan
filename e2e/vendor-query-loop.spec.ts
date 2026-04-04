/**
 * E2E test: Vendor Query Loop with search, pagination, and cards.
 *
 * Creates 10 vendor users, builds a page with the query loop block
 * (perPage=3, with search and pagination), then verifies the frontend
 * renders correctly: search bar visible, 4 pagination pages,
 * 3 cards on page 1, and 1 card on page 4.
 */

import { test, expect } from '@wordpress/e2e-test-utils-playwright';
import { createVendor, deleteVendor } from './helpers';

const VENDOR_COUNT = 10;
const PER_PAGE = 3;
const TOTAL_PAGES = Math.ceil( VENDOR_COUNT / PER_PAGE );

/** Vendor user IDs created during the test, cleaned up afterwards. */
const vendorIds: number[] = [];

/**
 * Build block markup for the query loop page.
 * Uses raw Gutenberg block comments to avoid editor serialization issues.
 */
function buildPageContent(): string {
	return `<!-- wp:the-another/blocks-for-dokan-vendor-query-loop {"perPage":${ PER_PAGE },"columns":3,"displayLayout":"grid"} -->
<!-- wp:the-another/blocks-for-dokan-vendor-search {"enableSearch":true} /-->
<!-- wp:the-another/blocks-for-dokan-vendor-card -->
<!-- wp:the-another/blocks-for-dokan-vendor-store-name {"tagName":"h3","isLink":true} /-->
<!-- /wp:the-another/blocks-for-dokan-vendor-card -->
<!-- wp:the-another/blocks-for-dokan-vendor-query-pagination {"showLabel":true} /-->
<!-- /wp:the-another/blocks-for-dokan-vendor-query-loop -->`;
}

test.describe( 'Vendor Query Loop – frontend rendering', () => {
	// ---- Setup: create 10 vendors before the suite runs. ----
	test.beforeAll( async ( { requestUtils } ) => {
		for ( let i = 1; i <= VENDOR_COUNT; i++ ) {
			const id = await createVendor( requestUtils, i );
			vendorIds.push( id );
		}
	} );

	// ---- Teardown: remove the vendors we created. ----
	test.afterAll( async ( { requestUtils } ) => {
		for ( const id of vendorIds ) {
			await deleteVendor( requestUtils, id );
		}
		vendorIds.length = 0;
	} );

	test( 'shows search bar, 4 pagination pages, 3 cards on page 1 and 1 card on page 4', async ( {
		page,
		requestUtils,
	} ) => {
		// --- 1. Create a page with block markup via REST API. ---
		const newPage = await requestUtils.rest< { id: number; link: string } >(
			{
				method: 'POST',
				path: '/wp/v2/pages',
				data: {
					title: 'Vendor Query Loop E2E',
					content: buildPageContent(),
					status: 'publish',
				},
			}
		);

		const frontendUrl = newPage.link;

		// --- 2. Visit page 1 on the frontend. ---
		await page.goto( frontendUrl );

		// Search bar: the search block wrapper should be present.
		const searchBlock = page.locator(
			'.wp-block-the-another-blocks-for-dokan-vendor-search'
		);
		await expect( searchBlock ).toBeVisible();

		// Vendor cards on page 1: should be exactly PER_PAGE (3).
		const cardsPage1 = page.locator( '.theabd--single-vendor' );
		await expect( cardsPage1 ).toHaveCount( PER_PAGE );

		// Pagination: should be visible.
		const pagination = page.locator( '[data-testid="vendor-pagination"]' );
		await expect( pagination ).toBeVisible();

		// Verify 4 page links exist (numbered links, excludes prev/next).
		const pageLinks = pagination.locator(
			'.page-numbers:not(.prev):not(.next)'
		);
		await expect( pageLinks ).toHaveCount( TOTAL_PAGES );

		// --- 3. Navigate to page 4 and verify 1 card. ---
		const lastPageLink = pageLinks.filter( {
			hasText: `${ TOTAL_PAGES }`,
		} );
		await lastPageLink.click();
		await page.waitForLoadState( 'domcontentloaded' );

		const cardsLastPage = page.locator( '.theabd--single-vendor' );
		await expect( cardsLastPage ).toHaveCount(
			VENDOR_COUNT % PER_PAGE || PER_PAGE
		);
	} );
} );
