import type { FullConfig } from '@playwright/test';
import { RequestUtils } from '@wordpress/e2e-test-utils-playwright';
import * as fs from 'node:fs';
import * as path from 'node:path';
import * as os from 'node:os';

/**
 * Path where wp-now loads shared mu-plugins from.
 */
const WP_NOW_MU_PLUGINS_DIR = path.join( os.homedir(), '.wp-now', 'mu-plugins' );

/**
 * Mu-plugins to copy into wp-now's shared directory.
 */
const MU_PLUGINS = [ 'e2e-test-helpers.php', 'e2e-environment.php' ];

async function installPlugin(
	requestUtils: RequestUtils,
	slug: string
): Promise< void > {
	try {
		await requestUtils.rest( {
			method: 'POST',
			path: '/wp/v2/plugins',
			data: { slug, status: 'active' },
		} );
	} catch ( error ) {
		// Plugin may already be installed — try activating it.
		try {
			await requestUtils.activatePlugin( slug );
		} catch {
			console.warn( `Could not install or activate plugin: ${ slug }` );
		}
	}
}

/**
 * Copy E2E mu-plugins into wp-now's shared mu-plugins directory
 * so WordPress loads them without any coupling to the main plugin.
 */
function installMuPlugins(): void {
	if ( ! fs.existsSync( WP_NOW_MU_PLUGINS_DIR ) ) {
		fs.mkdirSync( WP_NOW_MU_PLUGINS_DIR, { recursive: true } );
	}

	for ( const filename of MU_PLUGINS ) {
		fs.copyFileSync(
			path.resolve( __dirname, filename ),
			path.join( WP_NOW_MU_PLUGINS_DIR, filename )
		);
	}
}

export default async function globalSetup( config: FullConfig ) {
	const { baseURL } =
		config.projects[ 0 ].use as { baseURL: string };
	const storageStatePath = 'artifacts/storage-states/admin.json';

	// Install mu-plugins before any REST calls that depend on them.
	installMuPlugins();

	const requestUtils = await RequestUtils.setup( {
		baseURL,
		storageStatePath,
	} );

	// Install and activate required dependencies.
	await installPlugin( requestUtils, 'woocommerce' );
	await installPlugin( requestUtils, 'dokan-lite' );

	await requestUtils.activatePlugin( 'the-another-blocks-for-dokan' );
}
