/**
 * Browser-level warmup that runs before all test suites.
 *
 * The first editor load + publish in a fresh wp-now environment triggers
 * expensive one-time setup by WooCommerce and Dokan (DB tables, default pages,
 * editor welcome guide). Running that here absorbs the cost so real tests
 * aren't penalised.
 *
 * We cannot use editor.publishPost() because it waits for a Gutenberg snackbar
 * that never appears during WooCommerce's first-run page creation. Instead we
 * click the publish buttons manually and wait for the post-publish panel.
 */

import { test, expect } from '@wordpress/e2e-test-utils-playwright';

test( 'warmup: first editor load and publish', async ( { admin, page } ) => {
	test.setTimeout( 120_000 );

	// createNewPost triggers setPreferences (dismisses the welcome guide)
	// and forces WooCommerce/Dokan first-load initialisation.
	await admin.createNewPost( { postType: 'page', title: 'warmup' } );

	// Open the publish panel.
	await page
		.getByRole( 'region', { name: 'Editor top bar' } )
		.getByRole( 'button', { name: 'Publish', exact: true } )
		.click();

	// If an entities-save panel appeared, save those first.
	const entitiesSave = page
		.getByRole( 'region', { name: 'Editor publish' } )
		.getByRole( 'button', { name: 'Save', exact: true } );
	if ( await entitiesSave.isVisible() ) {
		await entitiesSave.click();
	}

	// Click the final Publish button.
	await page
		.getByRole( 'region', { name: 'Editor publish' } )
		.getByRole( 'button', { name: 'Publish', exact: true } )
		.click();

	// Wait for the post-publish panel confirmation instead of the snackbar.
	await expect( page.getByText( 'is now live.' ) ).toBeVisible( {
		timeout: 90_000,
	} );
} );
