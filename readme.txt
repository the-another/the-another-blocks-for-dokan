=== Dokan Blocks ===
Contributors: theanother
Tags: dokan, woocommerce, multivendor, blocks, fse, full-site-editing, gutenberg
Requires at least: 6.0
Tested up to: 6.9
Requires PHP: 8.3
Stable tag: 1.0.7
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

FSE-compatible Gutenberg blocks for Dokan multi-vendor marketplace. Convert Dokan templates into dynamic blocks for Full Site Editing.

== Description ==

Dokan Blocks provides FSE-compatible Gutenberg blocks for Dokan multi-vendor marketplace. This plugin converts Dokan templates into dynamic blocks for Full Site Editing, allowing you to build vendor store pages using the block editor.

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

== Changelog ==






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
