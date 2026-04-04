# WP-Now Playground for Manual Testing

**Date:** 2026-04-04
**Status:** Approved

## Problem

No simple way to launch a local WordPress instance with WooCommerce, Dokan, and the plugin pre-configured for manual browser testing. The existing `wp-now` integration is coupled to Playwright E2E tests and uses `--reset` which wipes state each run.

## Solution

A single `npm run playground` command that boots a persistent wp-now dev server with all dependencies installed, the plugin activated, and 10 vendor accounts seeded with profile data.

## Components

### 1. `npm run playground` script (package.json)

Runs `scripts/playground-setup.js` which orchestrates the full setup.

### 2. `scripts/playground-setup.js` (Node script, CommonJS)

CommonJS to match the existing convention in `scripts/` (e.g., `version-bump.js`). Does not import from E2E TypeScript files — all logic is self-contained.

Responsibilities:

1. **Install playground mu-plugin** — copies `scripts/playground-helpers.php` to `~/.wp-now/mu-plugins/` (see below)
2. **Start wp-now** via `child_process.spawn('npx', ['wp-now', 'start', ...])` on port 8882, PHP 8.3, without `--reset`. Pipe stdout/stderr to the parent process so the user sees wp-now output.
3. **Wait for readiness** — poll `http://localhost:8882/` with `fetch()` in a retry loop (1s interval, 60s timeout) until a 200 response
4. **Authenticate** — POST to `/wp-login.php` with admin credentials (wp-now default: `admin`/`password`) to obtain auth cookies, then use those cookies for subsequent REST API calls via plain `fetch()`
5. **Install and activate plugins** via `POST /wp/v2/plugins` with `{ slug, status: 'active' }`, catching "already installed" errors and falling back to activation (same try/catch pattern as `e2e/global-setup.ts`, but implemented with plain `fetch`)
   - WooCommerce (remote install from wordpress.org)
   - Dokan-lite (remote install from wordpress.org)
   - another-blocks-for-dokan — **activate only** (already on disk, wp-now mounts the project directory). Use `POST /wp/v2/plugins/another-blocks-for-dokan/another-blocks-for-dokan` with `{ status: 'active' }`
6. **Create 10 vendor accounts** via the playground mu-plugin endpoint `POST /theabd-playground/v1/ensure-vendor` (see below) — inherently idempotent
7. **Log useful URLs** to the console:
   - WP Admin: `http://localhost:8882/wp-admin/`
   - Store Listing: `http://localhost:8882/store-listing/`
   - Sample vendor store URLs
8. **Keep wp-now running** in the foreground — the spawned process stays attached. Ctrl+C sends SIGINT to wp-now and exits.

### 3. `scripts/playground-helpers.php` (mu-plugin)

A **separate** mu-plugin from `e2e/e2e-test-helpers.php`. This avoids conflicts with E2E teardown (`global-teardown.ts` deletes the E2E mu-plugin when tests finish — the playground mu-plugin is unaffected).

Registers one REST endpoint:

**`POST /theabd-playground/v1/ensure-vendor`**

Parameters: `username`, `email`, `password`, `store_name`, `display_name`, `address` (object), `phone`

Logic:
1. Check if user with `username` already exists via `get_user_by('login', $username)`
2. If exists, return `{ id, username, created: false }`
3. If not, create the user with `wp_insert_user()` (role: `seller`), set Dokan meta (`dokan_enable_selling`, `dokan_store_name`, `dokan_profile_settings`), and return `{ id, username, created: true }`

Permission: `current_user_can('create_users')`

Namespace: `theabd-playground/v1` (distinct from the E2E namespace `theabd-test/v1`)

### Port Selection

Uses **port 8882** (not 8881) to avoid conflict with E2E tests which use 8881. This means you can run the playground and E2E tests simultaneously without port collisions.

### Vendor Seed Data

Each vendor gets deterministic data based on index:

| Field | Pattern |
|-------|---------|
| Username | `vendor{n}` |
| Email | `vendor{n}@example.com` |
| Password | `password` |
| Display Name | `Test Vendor {n}` |
| Shop Name | `Vendor {n} Store` |
| Address | Varies per vendor (city/state cycle) |
| Phone | `555-010{n}` |

### Idempotency

- `ensure-vendor` endpoint checks for existing username before creating — safe to call repeatedly
- Plugin installation uses try/catch with activation fallback — safe if already installed
- mu-plugin copy is a simple `fs.copyFileSync` overwrite — always current

## Usage

**Prerequisites:** Run `npm run build` at least once (or use `npm start` for watch mode) so the `dist/` folder exists.

Terminal 1 (block rebuild watcher):
```bash
npm start
```

Terminal 2 (WordPress server):
```bash
npm run playground
```

The browser opens automatically. Block changes rebuild via `npm start` and are served live by wp-now since it reads from the plugin directory.

**Subsequent runs:** Data persists across sessions (no `--reset`). Vendors, products, and settings you configure manually survive restarts. Re-running `npm run playground` skips vendor creation (idempotent) and picks up where you left off.

## What This Does NOT Do

- No `blueprint.json` — that's for browser-based WP Playground (WASM), not wp-now
- No Docker — wp-now handles the PHP/WordPress runtime
- No new npm dependencies — uses `@wp-now/wp-now` already in devDependencies, REST calls use Node's built-in `fetch()`
- No test data beyond vendors — products, ratings, store hours can be added later if needed
- Does not share the E2E mu-plugin — uses its own `playground-helpers.php` to avoid teardown conflicts

## Files Changed

| File | Change |
|------|--------|
| `package.json` | Add `playground` script |
| `scripts/playground-setup.js` | New file — orchestration script (CommonJS) |
| `scripts/playground-helpers.php` | New file — playground-specific mu-plugin |
