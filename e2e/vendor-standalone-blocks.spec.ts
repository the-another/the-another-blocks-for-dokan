/**
 * E2E tests for standalone vendor blocks that accept a vendorId attribute.
 *
 * Covers: vendor-store-hours (compact + detailed), vendor-store-terms-conditions,
 * vendor-store-tabs (soft assertion), and vendor-store-sidebar (soft assertion).
 */

import { test, expect } from '@wordpress/e2e-test-utils-playwright';
import {
	createVendors,
	deleteVendors,
	createPage,
	deletePage,
} from './helpers';

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

test.describe( 'Vendor standalone blocks – frontend rendering', () => {
	let vendorIds: number[] = [];
	let pages: Array< { id: number; link: string } > = [];

	test.beforeAll( async ( { requestUtils } ) => {
		vendorIds = await createVendors( requestUtils, [
			{
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
			},
			{
				index: 2,
				store_name: 'T&C Test Store',
				store_tnc: TNC_TEXT,
			},
			{
				index: 3,
				store_name: 'Tabs Test Store',
			},
		] );

		pages = [
			await createPage( requestUtils, 'Store Hours Compact E2E',
				`<!-- wp:the-another/blocks-for-dokan-vendor-store-hours {"vendorId":${ vendorIds[ 0 ] },"layout":"compact","showCurrentStatus":true} /-->` ),
			await createPage( requestUtils, 'Store Hours Detailed E2E',
				`<!-- wp:the-another/blocks-for-dokan-vendor-store-hours {"vendorId":${ vendorIds[ 0 ] },"layout":"detailed","showCurrentStatus":true} /-->` ),
			await createPage( requestUtils, 'T&C With Title E2E',
				`<!-- wp:the-another/blocks-for-dokan-vendor-store-terms-conditions {"vendorId":${ vendorIds[ 1 ] },"showTitle":true,"titleTag":"h2"} /-->` ),
			await createPage( requestUtils, 'T&C No Title E2E',
				`<!-- wp:the-another/blocks-for-dokan-vendor-store-terms-conditions {"vendorId":${ vendorIds[ 1 ] },"showTitle":false} /-->` ),
			await createPage( requestUtils, 'Store Tabs E2E',
				`<!-- wp:the-another/blocks-for-dokan-vendor-store-tabs {"vendorId":${ vendorIds[ 2 ] }} /-->` ),
		];
	} );

	test.afterAll( async ( { requestUtils } ) => {
		for ( const p of pages ) {
			await deletePage( requestUtils, p.id );
		}
		await deleteVendors( requestUtils, vendorIds );
		vendorIds = [];
		pages = [];
	} );

	// Store hours – compact layout
	test( 'renders compact layout with status section', async ( { page } ) => {
		await page.goto( pages[ 0 ].link );

		const hoursBlock = page.locator( '.tanbfd--vendor-store-hours' );
		await expect( hoursBlock ).toBeVisible();

		// Should have compact layout class.
		await expect( hoursBlock ).toHaveClass(
			/tanbfd--vendor-store-hours-compact/
		);

		// Status section should be present.
		const status = hoursBlock.locator(
			'.tanbfd--vendor-store-hours-status'
		);
		await expect( status ).toBeVisible();

		// Should show either open or closed.
		const openBadge = status.locator( '.tanbfd--store-open' );
		const closedBadge = status.locator( '.tanbfd--store-closed' );
		const openCount = await openBadge.count();
		const closedCount = await closedBadge.count();
		expect( openCount + closedCount ).toBe( 1 );
	} );

	// Store hours – detailed layout
	test( 'renders detailed layout with 7 days and today marker', async ( { page } ) => {
		await page.goto( pages[ 1 ].link );

		const hoursBlock = page.locator( '.tanbfd--vendor-store-hours' );
		await expect( hoursBlock ).toBeVisible();

		// Should have detailed layout class.
		await expect( hoursBlock ).toHaveClass(
			/tanbfd--vendor-store-hours-detailed/
		);

		// Should have 7 day items.
		const dayItems = hoursBlock.locator(
			'li.tanbfd--vendor-store-hours-day'
		);
		await expect( dayItems ).toHaveCount( 7 );

		// Exactly one day should be marked as today.
		const todayItems = hoursBlock.locator( 'li.tanbfd--today' );
		await expect( todayItems ).toHaveCount( 1 );

		// Each day should have a name and hours.
		const firstDay = dayItems.first();
		await expect(
			firstDay.locator( '.tanbfd--day-name' )
		).toBeVisible();
		await expect(
			firstDay.locator( '.tanbfd--day-hours' )
		).toBeVisible();
	} );

	// Terms & Conditions – with title
	test( 'renders T&C content with title', async ( { page } ) => {
		await page.goto( pages[ 2 ].link );

		const tncBlock = page.locator(
			'.tanbfd--vendor-store-terms-conditions'
		);
		await expect( tncBlock ).toBeVisible();

		// Title should be visible as H2.
		const title = tncBlock.locator( '.tanbfd--store-toc-title' );
		await expect( title ).toBeVisible();
		expect(
			await title.evaluate( ( el ) => el.tagName )
		).toBe( 'H2' );
		await expect( title ).toContainText( 'Terms and Conditions' );

		// Content should contain our test text.
		const tncContent = tncBlock.locator( '.tanbfd--store-toc-content' );
		await expect( tncContent ).toContainText( TNC_TEXT );
	} );

	// Terms & Conditions – without title
	test( 'hides title when showTitle is false', async ( { page } ) => {
		await page.goto( pages[ 3 ].link );

		const tncBlock = page.locator(
			'.tanbfd--vendor-store-terms-conditions'
		);
		await expect( tncBlock ).toBeVisible();

		// Title should NOT be present.
		await expect(
			tncBlock.locator( '.tanbfd--store-toc-title' )
		).toHaveCount( 0 );

		// Content should still render.
		await expect(
			tncBlock.locator( '.tanbfd--store-toc-content' )
		).toContainText( TNC_TEXT );
	} );

	// Store tabs
	test( 'renders tab items when Dokan rewrite rules are active', async ( { page } ) => {
		await page.goto( pages[ 4 ].link );

		const tabsBlock = page.locator( '.tanbfd--vendor-store-tabs' );
		await expect( tabsBlock ).toBeVisible();

		const tabItems = tabsBlock.locator( '.tanbfd--store-tab-item' );
		expect( await tabItems.count() ).toBeGreaterThan( 0 );

		const firstTab = tabItems.first();
		await expect( firstTab.locator( 'a' ) ).toBeVisible();
	} );
} );
