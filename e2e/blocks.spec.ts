/**
 * E2E tests for Another Blocks for Dokan.
 *
 * Tests block insertion and editor rendering for plugin blocks.
 * Frontend rendering requires Dokan + WooCommerce vendor data and is not tested here.
 *
 * Blocks are grouped into fewer editor sessions to reduce overhead from
 * admin.createNewPost() which takes 3-5 seconds per call.
 */

import { test, expect } from '@wordpress/e2e-test-utils-playwright';

test.describe( 'Block editor rendering', () => {
	test( 'standalone blocks are visible in editor', async ( {
		admin,
		editor,
	} ) => {
		await admin.createNewPost( { title: 'Standalone Blocks Test' } );

		const blocks = [
			{
				name: 'the-another/blocks-for-dokan-become-vendor-cta',
				selector: '.wp-block-the-another-blocks-for-dokan-become-vendor-cta',
				text: 'Become a Vendor',
			},
			{
				name: 'the-another/blocks-for-dokan-vendor-store-header',
				selector: '.wp-block-the-another-blocks-for-dokan-vendor-store-header',
				text: 'Vendor Store Header',
			},
			{
				name: 'the-another/blocks-for-dokan-vendor-store-banner',
				selector: '.wp-block-the-another-blocks-for-dokan-vendor-store-banner',
			},
			{
				name: 'the-another/blocks-for-dokan-more-from-seller',
				selector: '.wp-block-the-another-blocks-for-dokan-more-from-seller',
				text: 'More from Seller',
			},
			{
				name: 'the-another/blocks-for-dokan-product-vendor-info',
				selector: '.wp-block-the-another-blocks-for-dokan-product-vendor-info',
			},
		];

		for ( const block of blocks ) {
			await editor.insertBlock( { name: block.name } );
		}

		for ( const block of blocks ) {
			const el = editor.canvas.locator( block.selector );
			await expect( el ).toBeVisible();
			if ( block.text ) {
				await expect( el ).toContainText( block.text );
			}
		}
	} );

	test( 'widget and sidebar blocks are visible in editor', async ( {
		admin,
		editor,
	} ) => {
		await admin.createNewPost( { title: 'Widget Blocks Test' } );

		const blocks = [
			{
				name: 'the-another/blocks-for-dokan-vendor-store-tabs',
				selector: '.wp-block-the-another-blocks-for-dokan-vendor-store-tabs',
				text: 'Products',
			},
			{
				name: 'the-another/blocks-for-dokan-vendor-store-sidebar',
				selector: '.wp-block-the-another-blocks-for-dokan-vendor-store-sidebar',
				text: 'Vendor Store Sidebar',
			},
			{
				name: 'the-another/blocks-for-dokan-vendor-contact-form',
				selector: '.wp-block-the-another-blocks-for-dokan-vendor-contact-form',
				text: 'Contact Form',
			},
			{
				name: 'the-another/blocks-for-dokan-vendor-store-terms-conditions',
				selector: '.wp-block-the-another-blocks-for-dokan-vendor-store-terms-conditions',
				text: 'Terms & Conditions',
			},
		];

		for ( const block of blocks ) {
			await editor.insertBlock( { name: block.name } );
		}

		for ( const block of blocks ) {
			const el = editor.canvas.locator( block.selector );
			await expect( el ).toBeVisible();
			if ( block.text ) {
				await expect( el ).toContainText( block.text );
			}
		}
	} );

	test( 'query loop block can be inserted and published', async ( {
		admin,
		editor,
		page,
	} ) => {
		await admin.createNewPost( { title: 'Query Loop Publish Test' } );
		await editor.insertBlock( {
			name: 'the-another/blocks-for-dokan-vendor-query-loop',
		} );

		const block = editor.canvas.locator(
			'.wp-block-the-another-blocks-for-dokan-vendor-query-loop'
		);
		await expect( block ).toBeVisible();

		await editor.publishPost();
		const errorNotice = page.locator(
			'.components-snackbar-list .is-error'
		);
		await expect( errorNotice ).toHaveCount( 0 );
	} );
} );
