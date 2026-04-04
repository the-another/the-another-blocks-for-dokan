#!/usr/bin/env node

const { spawn, execSync } = require( 'child_process' );
const fs = require( 'fs' );
const path = require( 'path' );
const os = require( 'os' );

const PORT = 8882;
const BASE_URL = `http://localhost:${ PORT }`;
const WP_NOW_MU_PLUGINS_DIR = path.join( os.homedir(), '.wp-now', 'mu-plugins' );
const MU_PLUGIN_SOURCE = path.resolve( __dirname, 'playground-helpers.php' );
const MU_PLUGIN_DEST = path.join( WP_NOW_MU_PLUGINS_DIR, 'playground-helpers.php' );

// ── Vendor seed data ──────────────────────────────────────────────────
const CITIES = [
	{ city: 'New York', state: 'NY', zip: '10001' },
	{ city: 'Los Angeles', state: 'CA', zip: '90001' },
	{ city: 'Chicago', state: 'IL', zip: '60601' },
	{ city: 'Houston', state: 'TX', zip: '77001' },
	{ city: 'Phoenix', state: 'AZ', zip: '85001' },
	{ city: 'Philadelphia', state: 'PA', zip: '19101' },
	{ city: 'San Antonio', state: 'TX', zip: '78201' },
	{ city: 'San Diego', state: 'CA', zip: '92101' },
	{ city: 'Dallas', state: 'TX', zip: '75201' },
	{ city: 'San Jose', state: 'CA', zip: '95101' },
];

function getVendorData( index ) {
	const n = index + 1;
	const loc = CITIES[ index % CITIES.length ];
	return {
		username: `vendor${ n }`,
		email: `vendor${ n }@example.com`,
		password: 'password',
		display_name: `Test Vendor ${ n }`,
		store_name: `Vendor ${ n } Store`,
		phone: `555-010${ n }`,
		address: {
			street_1: `${ n }00 Main St`,
			city: loc.city,
			state: loc.state,
			zip: loc.zip,
			country: 'US',
		},
	};
}

// ── Helpers ───────────────────────────────────────────────────────────

/** Prefix stream lines with a tag and pipe to the given writable. */
function prefixStream( stream, tag, out ) {
	let buffer = '';
	stream.on( 'data', ( chunk ) => {
		buffer += chunk.toString();
		const lines = buffer.split( '\n' );
		buffer = lines.pop(); // keep incomplete line in buffer
		for ( const line of lines ) {
			out.write( `${ tag } ${ line }\n` );
		}
	} );
	stream.on( 'end', () => {
		if ( buffer ) {
			out.write( `${ tag } ${ buffer }\n` );
		}
	} );
}

/** Poll URL until it returns a 200, or throw after timeout. */
async function waitForReady( url, timeoutMs = 60_000 ) {
	const start = Date.now();
	while ( Date.now() - start < timeoutMs ) {
		try {
			const res = await fetch( url );
			if ( res.ok ) {
				return;
			}
		} catch {
			// server not up yet
		}
		await new Promise( ( r ) => setTimeout( r, 1_000 ) );
	}
	throw new Error( `WordPress did not become ready at ${ url } within ${ timeoutMs / 1000 }s` );
}

/**
 * Authenticate with WordPress and return cookies string for subsequent requests.
 * wp-now defaults: admin / password
 */
async function authenticate() {
	const loginUrl = `${ BASE_URL }/wp-login.php`;
	const body = new URLSearchParams( {
		log: 'admin',
		pwd: 'password',
		'wp-submit': 'Log In',
		redirect_to: `${ BASE_URL }/wp-admin/`,
		testcookie: '1',
	} );

	const res = await fetch( loginUrl, {
		method: 'POST',
		headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
		body: body.toString(),
		redirect: 'manual', // don't follow redirect — we need Set-Cookie headers
	} );

	const setCookies = res.headers.getSetCookie();
	const cookies = setCookies
		.map( ( c ) => c.split( ';' )[ 0 ] )
		.join( '; ' );

	if ( ! cookies.includes( 'wordpress_logged_in' ) ) {
		throw new Error( 'Authentication failed — no wordpress_logged_in cookie received' );
	}

	return cookies;
}

/** Fetch the WP REST API nonce needed for cookie-based authentication. */
async function fetchNonce( cookies ) {
	const res = await fetch( `${ BASE_URL }/wp-admin/admin-ajax.php?action=rest-nonce`, {
		headers: { Cookie: cookies },
	} );
	const nonce = await res.text();
	return nonce.trim();
}

/** Make an authenticated REST API request. */
async function restRequest( method, restPath, cookies, nonce, data ) {
	const url = `${ BASE_URL }/index.php?rest_route=${ encodeURIComponent( restPath ) }`;
	const options = {
		method,
		headers: {
			Cookie: cookies,
			'X-WP-Nonce': nonce,
			'Content-Type': 'application/json',
		},
	};
	if ( data ) {
		options.body = JSON.stringify( data );
	}
	const res = await fetch( url, options );
	const json = await res.json();
	if ( ! res.ok ) {
		const code = json?.code || res.status;
		const message = json?.message || res.statusText;
		throw new Error( `REST ${ method } ${ restPath } failed: ${ code } — ${ message }` );
	}
	return json;
}

/**
 * Find the REST API plugin identifier for an installed plugin by matching its
 * directory name against the slug. Returns e.g. "dokan-lite/dokan" for slug "dokan-lite".
 */
async function findPluginId( slug, cookies, nonce ) {
	const plugins = await restRequest( 'GET', '/wp/v2/plugins', cookies, nonce );
	const match = plugins.find( ( p ) => p.plugin.startsWith( slug + '/' ) );
	return match ? match.plugin : null;
}

/** Install a plugin from wordpress.org and activate it, with retries. */
async function installRemotePlugin( slug, cookies, nonce, retries = 3 ) {
	for ( let attempt = 1; attempt <= retries; attempt++ ) {
		try {
			await restRequest( 'POST', '/wp/v2/plugins', cookies, nonce, {
				slug,
				status: 'active',
			} );
			console.log( `  ✓ ${ slug } installed and activated` );
			return;
		} catch {
			// Already installed, or install failed. Try to find and activate.
			const pluginId = await findPluginId( slug, cookies, nonce ).catch( () => null );
			if ( pluginId ) {
				try {
					await restRequest(
						'POST',
						`/wp/v2/plugins/${ pluginId }`,
						cookies,
						nonce,
						{ status: 'active' }
					);
					console.log( `  ✓ ${ slug } activated` );
					return;
				} catch ( e2 ) {
					if ( attempt < retries ) {
						console.log( `  ⏳ ${ slug } attempt ${ attempt } failed, retrying in 5s…` );
						await new Promise( ( r ) => setTimeout( r, 5_000 ) );
					} else {
						console.warn( `  ⚠ Could not activate ${ slug }: ${ e2.message }` );
					}
				}
			} else if ( attempt < retries ) {
				console.log( `  ⏳ ${ slug } not found yet, retrying in 5s…` );
				await new Promise( ( r ) => setTimeout( r, 5_000 ) );
			} else {
				console.warn( `  ⚠ Could not install or find ${ slug } after ${ retries } attempts` );
			}
		}
	}
}

/** Activate the local plugin (already on disk via wp-now mount). */
async function activateLocalPlugin( cookies, nonce ) {
	const slug = 'another-blocks-for-dokan';
	const pluginId = await findPluginId( slug, cookies, nonce ).catch( () => null );
	const id = pluginId || `${ slug }/${ slug }`;
	try {
		await restRequest(
			'POST',
			`/wp/v2/plugins/${ id }`,
			cookies,
			nonce,
			{ status: 'active' }
		);
		console.log( `  ✓ ${ slug } activated` );
	} catch ( e ) {
		console.warn( `  ⚠ Could not activate ${ slug }: ${ e.message }` );
	}
}

/** Create a vendor via the playground mu-plugin endpoint (idempotent). */
async function ensureVendor( vendorData, cookies, nonce ) {
	const result = await restRequest(
		'POST',
		'/theabd-playground/v1/ensure-vendor',
		cookies,
		nonce,
		vendorData
	);
	const status = result.created ? 'created' : 'exists';
	console.log( `  ✓ ${ vendorData.username } (${ status })` );
}

// ── Main ──────────────────────────────────────────────────────────────

const children = [];
let shuttingDown = false;

function cleanup() {
	shuttingDown = true;
	for ( const child of children ) {
		if ( ! child.killed ) {
			child.kill( 'SIGTERM' );
		}
	}
}

process.on( 'SIGINT', () => {
	console.log( '\nShutting down…' );
	cleanup();
	process.exit( 0 );
} );

process.on( 'SIGTERM', () => {
	cleanup();
	process.exit( 0 );
} );

async function main() {
	// 1. Initial build (unless --no-build is passed and dist/ exists).
	const noBuild = process.argv.includes( '--no-build' );
	if ( ! noBuild || ! fs.existsSync( path.resolve( __dirname, '../dist' ) ) ) {
		console.log( '🔨 Building blocks…' );
		execSync( 'npx wp-scripts build src/blocks.js --output-path=dist', {
			cwd: path.resolve( __dirname, '..' ),
			stdio: 'inherit',
		} );
	}

	// 2. Install mu-plugin.
	if ( ! fs.existsSync( WP_NOW_MU_PLUGINS_DIR ) ) {
		fs.mkdirSync( WP_NOW_MU_PLUGINS_DIR, { recursive: true } );
	}
	fs.copyFileSync( MU_PLUGIN_SOURCE, MU_PLUGIN_DEST );
	console.log( '📦 Playground mu-plugin installed' );

	// 3. Start wp-now + block watcher concurrently.
	const wpNow = spawn(
		'npx',
		[ 'wp-now', 'start', `--port=${ PORT }`, '--php=8.3' ],
		{ cwd: path.resolve( __dirname, '..' ) }
	);
	children.push( wpNow );
	prefixStream( wpNow.stdout, '[wp-now]', process.stdout );
	prefixStream( wpNow.stderr, '[wp-now]', process.stderr );

	const watcher = spawn(
		'npx',
		[ 'wp-scripts', 'start', 'src/blocks.js', '--output-path=dist' ],
		{ cwd: path.resolve( __dirname, '..' ) }
	);
	children.push( watcher );
	prefixStream( watcher.stdout, '[build]', process.stdout );
	prefixStream( watcher.stderr, '[build]', process.stderr );

	// Exit if either child exits unexpectedly.
	for ( const child of children ) {
		child.on( 'exit', ( code ) => {
			if ( ! shuttingDown && code !== null && code !== 0 ) {
				console.error( `Child process exited with code ${ code }` );
				cleanup();
				process.exit( code );
			}
		} );
	}

	// 4. Wait for WordPress to be ready.
	console.log( `⏳ Waiting for WordPress at ${ BASE_URL }…` );
	await waitForReady( BASE_URL );
	console.log( '✅ WordPress is ready' );

	// 5. Authenticate.
	const cookies = await authenticate();
	const nonce = await fetchNonce( cookies );

	// 6. Install and activate plugins.
	console.log( '📦 Setting up plugins…' );
	await installRemotePlugin( 'woocommerce', cookies, nonce );
	await installRemotePlugin( 'dokan-lite', cookies, nonce );
	await activateLocalPlugin( cookies, nonce );

	// 7. Seed vendors.
	console.log( '👥 Seeding vendor accounts…' );
	for ( let i = 0; i < 10; i++ ) {
		await ensureVendor( getVendorData( i ), cookies, nonce );
	}

	// 8. Log URLs.
	console.log( '' );
	console.log( '🚀 Playground is ready!' );
	console.log( '' );
	console.log( `   WP Admin:       ${ BASE_URL }/wp-admin/` );
	console.log( `   Store Listing:  ${ BASE_URL }/store-listing/` );
	console.log( `   Vendor 1 Store: ${ BASE_URL }/store/vendor1/` );
	console.log( '' );
	console.log( '   Edit block files → auto-rebuild → refresh browser' );
	console.log( '   Press Ctrl+C to stop' );
	console.log( '' );
}

main().catch( ( err ) => {
	console.error( 'Playground setup failed:', err.message );
	cleanup();
	process.exit( 1 );
} );
