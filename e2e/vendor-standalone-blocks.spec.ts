/**
 * E2E tests for standalone vendor blocks that accept a vendorId attribute.
 *
 * Covers: vendor-store-hours (compact + detailed), vendor-store-terms-conditions,
 * vendor-store-tabs (soft assertion), and vendor-store-sidebar (soft assertion).
 */

import { test, expect } from '@wordpress/e2e-test-utils-playwright';
import {
	createVendor,
	deleteVendor,
	createPage,
	deletePage,
} from './helpers';

let vendorIds: number[] = [];

/** Store time schedule: Mon-Fri 09:00-17:00, Sat-Sun closed. */
const STORE_TIME: Record< string, unknown > = {
	monday: { status: 'open', opening_time: '09:00 AM', closing_time: '05:00 PM' },
	tuesday: { status: 'open', opening_time: '09:00 AM', closing_time: '05:00 PM' },
	wednesday: { status: 'open', opening_time: '09:00 AM', closing_time: '05:00 PM' },
	thursday: { status: 'open', opening_time: '09:00 AM', closing_time: '05:00 PM' },
	friday: { status: 'open', opening_time: '09:00 AM', closing_time: '05:00 PM' },
	saturday: { status: 'close', opening_time: '', closing_time: '' },
	sunday: { status: 'close', opening_time: '', closing_time: '' },
};

const TNC_TEXT = 'These are our test terms and conditions for e2e testing purposes.';

// ---------------------------------------------------------------------------
// Store hours
// ---------------------------------------------------------------------------
test.describe( 'Vendor Store Hours – standalone rendering', () => {
	test.beforeAll( async ( { requestUtils } ) => {
		vendorIds = [];
		const id = await createVendor( requestUtils, {
			index: 1,
			store_name: 'Hours Test Store',
			phone: '+1-555-0200',
			address: {
				street_1: '456 Schedule Ave',
				city: 'Clocktown',
				state: 'CA',
				zip: '90001',
				country: 'US',
			},
			dokan_store_time_enabled: 'yes',
			dokan_store_time: STORE_TIME,
		} );
		vendorIds.push( id );
	} );

	test.afterAll( async ( { requestUtils } ) => {
		for ( const id of vendorIds ) {
			await deleteVendor( requestUtils, id );
		}
		vendorIds = [];
	} );

	test( 'renders compact layout with status section', async ( {
		page,
		requestUtils,
	} ) => {
		const vendorId = vendorIds[ 0 ];
		const content = `<!-- wp:the-another/blocks-for-dokan-vendor-store-hours {"vendorId":${ vendorId },"layout":"compact","showCurrentStatus":true} /-->`;

		const newPage = await createPage(
			requestUtils,
			'Store Hours Compact E2E',
			content
		);

		await page.goto( newPage.link );

		const hoursBlock = page.locator( '.theabd--vendor-store-hours' );
		await expect( hoursBlock ).toBeVisible();

		// Should have compact layout class.
		await expect( hoursBlock ).toHaveClass(
			/theabd--vendor-store-hours-compact/
		);

		// Status section should be present.
		const status = hoursBlock.locator(
			'.theabd--vendor-store-hours-status'
		);
		await expect( status ).toBeVisible();

		// Should show either open or closed.
		const openBadge = status.locator( '.theabd--store-open' );
		const closedBadge = status.locator( '.theabd--store-closed' );
		const openCount = await openBadge.count();
		const closedCount = await closedBadge.count();
		expect( openCount + closedCount ).toBe( 1 );

		await deletePage( requestUtils, newPage.id );
	} );

	test( 'renders detailed layout with 7 days and today marker', async ( {
		page,
		requestUtils,
	} ) => {
		const vendorId = vendorIds[ 0 ];
		const content = `<!-- wp:the-another/blocks-for-dokan-vendor-store-hours {"vendorId":${ vendorId },"layout":"detailed","showCurrentStatus":true} /-->`;

		const newPage = await createPage(
			requestUtils,
			'Store Hours Detailed E2E',
			content
		);

		await page.goto( newPage.link );

		const hoursBlock = page.locator( '.theabd--vendor-store-hours' );
		await expect( hoursBlock ).toBeVisible();

		// Should have detailed layout class.
		await expect( hoursBlock ).toHaveClass(
			/theabd--vendor-store-hours-detailed/
		);

		// Should have 7 day items.
		const dayItems = hoursBlock.locator(
			'li.theabd--vendor-store-hours-day'
		);
		await expect( dayItems ).toHaveCount( 7 );

		// Exactly one day should be marked as today.
		const todayItems = hoursBlock.locator( 'li.theabd--today' );
		await expect( todayItems ).toHaveCount( 1 );

		// Each day should have a name and hours.
		const firstDay = dayItems.first();
		await expect(
			firstDay.locator( '.theabd--day-name' )
		).toBeVisible();
		await expect(
			firstDay.locator( '.theabd--day-hours' )
		).toBeVisible();

		await deletePage( requestUtils, newPage.id );
	} );
} );

// ---------------------------------------------------------------------------
// Terms and conditions
// ---------------------------------------------------------------------------
test.describe( 'Vendor Store Terms & Conditions – standalone rendering', () => {
	test.beforeAll( async ( { requestUtils } ) => {
		vendorIds = [];
		const id = await createVendor( requestUtils, {
			index: 1,
			store_name: 'T&C Test Store',
			store_tnc: TNC_TEXT,
		} );
		vendorIds.push( id );
	} );

	test.afterAll( async ( { requestUtils } ) => {
		for ( const id of vendorIds ) {
			await deleteVendor( requestUtils, id );
		}
		vendorIds = [];
	} );

	test( 'renders T&C content with title', async ( {
		page,
		requestUtils,
	} ) => {
		const vendorId = vendorIds[ 0 ];
		const content = `<!-- wp:the-another/blocks-for-dokan-vendor-store-terms-conditions {"vendorId":${ vendorId },"showTitle":true,"titleTag":"h2"} /-->`;

		const newPage = await createPage(
			requestUtils,
			'T&C With Title E2E',
			content
		);

		await page.goto( newPage.link );

		const tncBlock = page.locator(
			'.theabd--vendor-store-terms-conditions'
		);
		await expect( tncBlock ).toBeVisible();

		// Title should be visible as H2.
		const title = tncBlock.locator( '.theabd--store-toc-title' );
		await expect( title ).toBeVisible();
		expect(
			await title.evaluate( ( el ) => el.tagName )
		).toBe( 'H2' );
		await expect( title ).toContainText( 'Terms and Conditions' );

		// Content should contain our test text.
		const tncContent = tncBlock.locator( '.theabd--store-toc-content' );
		await expect( tncContent ).toContainText( TNC_TEXT );

		await deletePage( requestUtils, newPage.id );
	} );

	test( 'hides title when showTitle is false', async ( {
		page,
		requestUtils,
	} ) => {
		const vendorId = vendorIds[ 0 ];
		const content = `<!-- wp:the-another/blocks-for-dokan-vendor-store-terms-conditions {"vendorId":${ vendorId },"showTitle":false} /-->`;

		const newPage = await createPage(
			requestUtils,
			'T&C No Title E2E',
			content
		);

		await page.goto( newPage.link );

		const tncBlock = page.locator(
			'.theabd--vendor-store-terms-conditions'
		);
		await expect( tncBlock ).toBeVisible();

		// Title should NOT be present.
		await expect(
			tncBlock.locator( '.theabd--store-toc-title' )
		).toHaveCount( 0 );

		// Content should still render.
		await expect(
			tncBlock.locator( '.theabd--store-toc-content' )
		).toContainText( TNC_TEXT );

		await deletePage( requestUtils, newPage.id );
	} );
} );

// ---------------------------------------------------------------------------
// Store tabs (soft assertion – depends on Dokan's rewrite rules)
// ---------------------------------------------------------------------------
test.describe( 'Vendor Store Tabs – standalone rendering', () => {
	test.beforeAll( async ( { requestUtils } ) => {
		vendorIds = [];
		const id = await createVendor( requestUtils, {
			index: 1,
			store_name: 'Tabs Test Store',
		} );
		vendorIds.push( id );
	} );

	test.afterAll( async ( { requestUtils } ) => {
		for ( const id of vendorIds ) {
			await deleteVendor( requestUtils, id );
		}
		vendorIds = [];
	} );

	// Store tabs require Dokan's rewrite rules which aren't active in wp-now.
	test.fixme( 'renders tab items when Dokan rewrite rules are active', async ( {
		page,
		requestUtils,
	} ) => {
		const vendorId = vendorIds[ 0 ];
		const content = `<!-- wp:the-another/blocks-for-dokan-vendor-store-tabs {"vendorId":${ vendorId }} /-->`;

		const newPage = await createPage(
			requestUtils,
			'Store Tabs E2E',
			content
		);

		await page.goto( newPage.link );

		const tabsBlock = page.locator( '.theabd--vendor-store-tabs' );
		await expect( tabsBlock ).toBeVisible();

		const tabItems = tabsBlock.locator( '.theabd--store-tab-item' );
		expect( await tabItems.count() ).toBeGreaterThan( 0 );

		const firstTab = tabItems.first();
		await expect( firstTab.locator( 'a' ) ).toBeVisible();

		await deletePage( requestUtils, newPage.id );
	} );
} );

// ---------------------------------------------------------------------------
// Store sidebar (soft assertion – depends on widget area registration)
// ---------------------------------------------------------------------------
test.describe( 'Vendor Store Sidebar – standalone rendering', () => {
	test.beforeAll( async ( { requestUtils } ) => {
		vendorIds = [];
		const id = await createVendor( requestUtils, {
			index: 1,
			store_name: 'Sidebar Test Store',
			address: {
				street_1: '789 Widget Way',
				city: 'Sidebarville',
				state: 'NY',
				zip: '10001',
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

	// Sidebar rendering requires Dokan widget areas which aren't
	// registered in wp-now.
	test.fixme( 'renders sidebar with widget areas when Dokan is fully active', async ( {
		page,
		requestUtils,
	} ) => {
		const vendorId = vendorIds[ 0 ];
		const content = `<!-- wp:the-another/blocks-for-dokan-vendor-store-sidebar {"vendorId":${ vendorId }} /-->`;

		const newPage = await createPage(
			requestUtils,
			'Store Sidebar E2E',
			content
		);

		await page.goto( newPage.link );

		const sidebar = page.locator( '.theabd--vendor-store-sidebar' );
		await expect( sidebar ).toBeVisible();
		await expect( sidebar ).toHaveAttribute( 'role', 'complementary' );

		await deletePage( requestUtils, newPage.id );
	} );
} );
