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

### 2. `scripts/playground-setup.js` (Node script)

Responsibilities:

1. **Start wp-now** on port 8881, PHP 8.3, without `--reset` or `--skip-browser`
2. **Wait for readiness** — poll the health endpoint until WordPress responds
3. **Install and activate plugins** via the WordPress REST API (reusing the same pattern as `e2e/global-setup.ts`):
   - WooCommerce
   - Dokan-lite
   - another-blocks-for-dokan (the plugin itself)
4. **Create 10 vendor accounts** (idempotent — skip if they already exist):
   - Usernames: `vendor1` through `vendor10`
   - Each gets: display name, shop name, store address, phone number
   - Each is registered as a Dokan seller
5. **Log useful URLs** to the console:
   - WP Admin: `http://localhost:8881/wp-admin/`
   - Store Listing: `http://localhost:8881/store-listing/`
   - Individual vendor stores
6. **Keep wp-now running** in the foreground (Ctrl+C to stop)

### Vendor Seed Data

Each vendor gets deterministic data based on index:

| Field | Pattern |
|-------|---------|
| Username | `vendor{n}` |
| Email | `vendor{n}@example.com` |
| Password | `password` |
| Display Name | `Test Vendor {n}` |
| Shop Name | `Vendor {n} Store` |
| Address | Varies per vendor |
| Phone | `555-010{n}` |

### Idempotency

- Before creating each vendor, check if the username already exists via REST API
- Before installing each plugin, check if already installed (same try/catch pattern as global-setup.ts)
- Safe to run repeatedly without duplicating data

## Usage

Terminal 1 (block rebuild watcher):
```bash
npm start
```

Terminal 2 (WordPress server):
```bash
npm run playground
```

The browser opens automatically. Block changes rebuild via `npm start` and are served live by wp-now since it reads from the plugin directory.

## What This Does NOT Do

- No `blueprint.json` — that's for browser-based WP Playground (WASM), not wp-now
- No Docker — wp-now handles the PHP/WordPress runtime
- No new dependencies — uses `@wp-now/wp-now` and `@wordpress/e2e-test-utils-playwright` already in devDependencies
- No test data beyond vendors — products, ratings, store hours can be added later if needed

## Files Changed

| File | Change |
|------|--------|
| `package.json` | Add `playground` script |
| `scripts/playground-setup.js` | New file — orchestration script |
