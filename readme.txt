=== The Another Blocks for Dokan ===
Contributors: theanother
Tags: dokan, woocommerce, multivendor, blocks, gutenberg
Requires at least: 6.0
Tested up to: 6.9
Requires PHP: 8.3
Stable tag: 1.1.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

FSE-compatible Gutenberg blocks for Dokan multi-vendor marketplace. Convert Dokan templates into dynamic blocks for Full Site Editing.

== Description ==

The Another's Blocks for Dokan provides FSE-compatible Gutenberg blocks for Dokan multi-vendor marketplace. This plugin converts Dokan templates into dynamic blocks for Full Site Editing, allowing you to build vendor store pages using the block editor.

== Installation ==

1. Upload the plugin files to the `/wp-content/plugins/dokan-blocks` directory, or install the plugin through the WordPress plugins screen directly.
2. Activate the plugin through the 'Plugins' screen in WordPress.
3. Use the blocks in the block editor to build vendor store pages.

== Frequently Asked Questions ==

= What version of WordPress is required? =

WordPress 6.0 or higher is required for FSE support.

= What version of Dokan is required? =

The latest stable version of Dokan is recommended.

= What version of PHP is required? =

PHP 8.3 or higher is required.

== External services ==

= Mapbox Static Maps =

The Vendor Store Location block can display a static map image using the Mapbox Static Images API. When a vendor has configured a Mapbox access token and store coordinates (latitude/longitude), the block loads a map image from Mapbox's servers.

* **Data sent**: Store latitude, longitude, zoom level, and the site's Mapbox access token.
* **When**: Each time the Vendor Store Location block renders on the frontend with Mapbox selected as the map provider.
* **Service provider**: Mapbox, Inc.
* [Mapbox Terms of Service](https://www.mapbox.com/tos/)
* [Mapbox Privacy Policy](https://www.mapbox.com/privacy/)

= Google Maps Embed =

The Vendor Store Location block can alternatively display an interactive embedded map using the Google Maps Embed API. When a vendor has configured a Google Maps API key and store address, the block loads a map iframe from Google's servers.

* **Data sent**: Store address, zoom level, and the site's Google Maps API key.
* **When**: Each time the Vendor Store Location block renders on the frontend with Google Maps selected as the map provider.
* **Service provider**: Google LLC.
* [Google Maps Platform Terms of Service](https://cloud.google.com/maps-platform/terms)
* [Google Privacy Policy](https://policies.google.com/privacy)

== Changelog ==


= 1.1.0 - 2026-04-17 =
* Add: Vendor search block can now be placed in any parent block, no longer restricted to the vendor query loop
* Add: "Results Page URL" setting to redirect search and sort forms to a different page
* Fix: Store count displays actual number of active sellers when block is used outside the vendor query loop

= 1.0.14 - 2026-04-17 =
* Fix: `block.json` render callbacks now use the correct `tanbfd_` prefix — seven blocks previously referenced legacy `theabd_render_*` callbacks that did not match the actual function definitions
* Refactor: Store template override now delegates to WordPress core's public `locate_block_template()` helper (available since WP 5.9) instead of dereferencing `wp-includes/template-canvas.php` directly; plugin templates surface via a new `get_block_templates` filter

= 1.0.13 - 2026-04-16 =
* Version bump

= 1.0.12 - 2026-04-16 =
* Version bump

= 1.0.11 - 2026-04-17 =
* Fix: Release workflow now uploads both versioned and unversioned ZIP to GitHub releases

= 1.0.10 - 2026-04-15 =
* Fix: Corrected Author URI and Plugin URI domain (the-another.org)
* Fix: Extracted inline script in Vendor Search block to properly enqueued view.js
* Fix: Replaced hardcoded WP_PLUGIN_DIR paths with WordPress get_plugins() API
* Fix: Escaped all block output with wp_kses_post() — removed 45 phpcs:ignore suppressions
* Refactor: Renamed function prefix from theabd_ to tanbfd_ for uniqueness
* Refactor: Renamed constants prefix to THE_ANOTHER_BLOCKS_FOR_DOKAN_
* Refactor: Renamed namespace to The_Another\Plugin\Blocks_For_Dokan
* Refactor: Renamed CSS class prefix from theabd-- to tanbfd--
* Refactor: Renamed text domain to the-another-blocks-for-dokan
* Refactor: Renamed script/style handles and transients to use tanbfd- prefix
* Docs: Added External Services disclosure for Mapbox and Google Maps in readme

= 1.0.9 - 2026-04-07 =
* Fix: Plugin Check compliance — text domain renamed to `theanother-blocks-for-dokan` across all PHP and block.json files; added missing `languages/` directory; added direct-access guard to `functions/functions.php`; bumped main plugin header `Requires PHP` to 8.3 to match readme
* Fix: Vendor Query Loop infinite-scroll status box now shows a centered animated "Loading…" indicator only while fetching, instead of announcing the loaded page number
* Add: Configurable infinite-scroll trigger offset (px) on the Vendor Query Loop block — controls how far from the bottom the next page begins loading (default 400)
* Fix: Vendor Search and Vendor Query Pagination editor previews — JSX now uses the `theabd--*` class names so the existing styles apply inside Gutenberg
* Add: `core/paragraph`, `core/separator`, and `core/spacer` are now allowed inside the Vendor Query Loop block and rendered before or after the loop based on their position relative to the Vendor Card
* Fix: readme.txt plugin name and tag count to satisfy WordPress.org plugin guidelines
* Docs: New user-facing `README.md`; previous developer guide moved to `CONTRIBUTING.md`

= 1.0.8 - 2026-04-07 =
* Add: Opt-in infinite scroll for the Vendor Query Loop block — auto-loads next page near the bottom without changing the URL (off by default)
* Add: REST endpoint `POST /another-blocks-for-dokan/v1/vendor-query-loop` reusing the server render helpers so paginated results match the first page
* Add: `theabd_vendor_query_loop_infinite_filters` filter for integrations to forward request-time query vars (e.g., location slugs) through paginated fetches
* Refactor: Split `vendor-query-loop/render.php` into reusable helpers (`build_query_args`, `run_query`, `render_items`, `compute_query_id`)

= 1.0.7 - 2026-04-06 =
* Add: GitHub Actions release workflow for automated plugin packaging
* Chore: Version bump syncs lock files (package-lock.json, composer.lock)

= 1.0.6 - 2026-04-05 =
* Fix: Unified form element styles across all blocks for consistent theme integration
* Fix: Buttons now use WordPress `wp-element-button` class for native theme color support
* Fix: Removed hardcoded colors from inputs, buttons, selects, and pagination
* Fix: Added CSS custom properties for overriding form element spacing and border-radius
* Fix: Adjusted vendor-search and vendor-card spacing defaults

= 1.0.5 - 2026-04-05 =
* Fix: Vendor Search block now renders on the frontend when added inside the Vendor Query Loop
* Add: Location filter with country/state dropdown to Vendor Search block
* Add: Filter Settings panel in the editor for toggling location, rating, and category filters

= 1.0.4 - 2026-04-05 =
* Add Depot.dev CI workflow support

= 1.0.3 - 2026-02-18 =
* Version bump

= 1.0.2 - 2026-02-18 =
* Fix: Store Query Loop now only lists sellers who are allowed to sell (filters by dokan_enable_selling status)

= 1.0.1 - 2026-01-19 =
* Fixes issue where blocks were not rendering correctly for anonymous users.

= 1.0.0 =
* Initial release

== Upgrade Notice ==

= 1.0.0 =
Initial release of Dokan Blocks.
