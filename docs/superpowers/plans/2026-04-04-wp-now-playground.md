# WP-Now Playground Implementation Plan

> **For agentic workers:** REQUIRED: Use superpowers:subagent-driven-development (if subagents available) or superpowers:executing-plans to implement this plan. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Add a single `npm run playground` command that boots a persistent wp-now dev server with WooCommerce, Dokan, the plugin activated, 10 vendor accounts seeded, and a live block-rebuild watcher — all in one terminal.

**Architecture:** A Node.js orchestration script (`scripts/playground-setup.js`) spawns two child processes (wp-now server + wp-scripts watcher), waits for WordPress readiness, then provisions plugins and vendors via REST API. A separate PHP mu-plugin (`scripts/playground-helpers.php`) provides an idempotent vendor-creation endpoint.

**Tech Stack:** Node.js (CommonJS), `child_process.spawn`, native `fetch()`, PHP 8.3, WordPress REST API, wp-now, wp-scripts

**Spec:** `docs/superpowers/specs/2026-04-04-wp-now-playground-design.md`

---

## Chunk 1: Playground mu-plugin and orchestration script

### Task 1: Create `scripts/playground-helpers.php`

**Files:**
- Create: `scripts/playground-helpers.php`

- [ ] **Step 1: Write the mu-plugin**

```php
<?php
/**
 * Playground helpers — idempotent vendor creation endpoint.
 *
 * Installed as a wp-now mu-plugin by the playground setup script.
 * Never loaded by the main plugin; not shipped in production releases.
 *
 * @package AnotherBlocksForDokan
 * @since 1.0.3
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action(
	'rest_api_init',
	function () {
		register_rest_route(
			'theabd-playground/v1',
			'/ensure-vendor',
			array(
				'methods'             => 'POST',
				'callback'            => function ( WP_REST_Request $request ) {
					$username = sanitize_user( $request->get_param( 'username' ) );

					$existing = get_user_by( 'login', $username );
					if ( $existing ) {
						return array(
							'id'       => $existing->ID,
							'username' => $username,
							'created'  => false,
						);
					}

					$user_id = wp_insert_user(
						array(
							'user_login'   => $username,
							'user_email'   => sanitize_email( $request->get_param( 'email' ) ),
							'user_pass'    => $request->get_param( 'password' ),
							'role'         => 'seller',
							'display_name' => sanitize_text_field( $request->get_param( 'display_name' ) ),
						)
					);

					if ( is_wp_error( $user_id ) ) {
						return $user_id;
					}

					$store_name = sanitize_text_field( $request->get_param( 'store_name' ) );
					$phone      = sanitize_text_field( $request->get_param( 'phone' ) );
					$address    = $request->get_param( 'address' ) ?? array();

					update_user_meta( $user_id, 'dokan_enable_selling', 'yes' );
					update_user_meta( $user_id, 'dokan_store_name', $store_name );

					$profile_settings = array(
						'store_name' => $store_name,
					);

					if ( ! empty( $address ) ) {
						$profile_settings['address'] = array_map( 'sanitize_text_field', (array) $address );
					}

					if ( ! empty( $phone ) ) {
						$profile_settings['phone'] = $phone;
					}

					update_user_meta( $user_id, 'dokan_profile_settings', $profile_settings );

					return array(
						'id'       => $user_id,
						'username' => $username,
						'created'  => true,
					);
				},
				'permission_callback' => function () {
					return current_user_can( 'create_users' );
				},
			)
		);
	}
);
```

- [ ] **Step 2: Commit**

```bash
git add scripts/playground-helpers.php
git commit -m "feat(playground): add idempotent vendor creation mu-plugin"
```

---

### Task 2: Create `scripts/playground-setup.js`

**Files:**
- Create: `scripts/playground-setup.js`

This is the main orchestration script. It has several logical sections built in sequence.

- [ ] **Step 1: Write the script with all sections**

```javascript
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

/** Install a plugin from wordpress.org and activate it. */
async function installRemotePlugin( slug, cookies, nonce ) {
	try {
		await restRequest( 'POST', '/wp/v2/plugins', cookies, nonce, {
			slug,
			status: 'active',
		} );
		console.log( `  ✓ ${ slug } installed and activated` );
	} catch {
		// Already installed — try activating.
		try {
			await restRequest(
				'POST',
				`/wp/v2/plugins/${ slug }/${ slug }`,
				cookies,
				nonce,
				{ status: 'active' }
			);
			console.log( `  ✓ ${ slug } activated` );
		} catch ( e2 ) {
			console.warn( `  ⚠ Could not install or activate ${ slug }: ${ e2.message }` );
		}
	}
}

/** Activate the local plugin (already on disk via wp-now mount). */
async function activateLocalPlugin( cookies, nonce ) {
	const pluginSlug = 'another-blocks-for-dokan';
	try {
		await restRequest(
			'POST',
			`/wp/v2/plugins/${ pluginSlug }/${ pluginSlug }`,
			cookies,
			nonce,
			{ status: 'active' }
		);
		console.log( `  ✓ ${ pluginSlug } activated` );
	} catch ( e ) {
		console.warn( `  ⚠ Could not activate ${ pluginSlug }: ${ e.message }` );
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
```

- [ ] **Step 2: Commit**

```bash
git add scripts/playground-setup.js
git commit -m "feat(playground): add orchestration script for wp-now dev server"
```

---

### Task 3: Add `playground` script to `package.json`

**Files:**
- Modify: `package.json`

- [ ] **Step 1: Add the script**

In `package.json` `scripts` section, add:

```json
"playground": "node scripts/playground-setup.js"
```

Place it after the `test:e2e:ui` entry.

- [ ] **Step 2: Commit**

```bash
git add package.json
git commit -m "feat(playground): add npm run playground script"
```

---

### Task 4: Manual smoke test

- [ ] **Step 1: Run the playground**

```bash
npm run playground
```

Expected output (first run):
1. `🔨 Building blocks (first run)…` — wp-scripts build runs
2. `📦 Playground mu-plugin installed`
3. `[wp-now]` and `[build]` prefixed output from both processes
4. `⏳ Waiting for WordPress…` → `✅ WordPress is ready`
5. Plugin install/activate messages for WooCommerce, Dokan, and the plugin
6. 10 vendor creation messages (`vendor1 (created)` through `vendor10 (created)`)
7. URL summary with WP Admin, Store Listing, and Vendor 1 Store links
8. Browser opens automatically

- [ ] **Step 2: Verify in browser**

1. Visit the WP Admin URL — confirm you can log in (admin/password)
2. Visit the Store Listing URL — confirm vendors appear
3. Visit a vendor store page — confirm store header/profile blocks render

- [ ] **Step 3: Verify idempotency**

Stop with Ctrl+C, then run again:

```bash
npm run playground
```

Expected: vendors show `(exists)` instead of `(created)`. No duplicate users.

- [ ] **Step 4: Verify live rebuild**

With playground running, edit any block file (e.g., change a CSS class in a `render.php`). The `[build]` watcher should rebuild. Refresh the browser to see the change.

- [ ] **Step 5: Fix any issues found during smoke test**

- [ ] **Step 6: Commit any fixes**

```bash
git add -A
git commit -m "fix(playground): address issues from smoke test"
```

(Skip this step if no fixes were needed.)
