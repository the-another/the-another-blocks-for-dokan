# WordPress.org Plugin Review Fixes — Implementation Plan

> **For agentic workers:** REQUIRED: Use superpowers:subagent-driven-development (if subagents available) or superpowers:executing-plans to implement this plan. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Fix all issues raised in the WordPress.org plugin review so the plugin can be resubmitted.

**Architecture:** Mechanical fixes across the codebase — URI corrections, output escaping, script enqueuing, readme disclosure, path fixes, and a comprehensive prefix/namespace/text-domain rename. No architectural changes.

**Tech Stack:** PHP 8.3+, WordPress block API v3, SCSS, vanilla JS.

**Spec:** `docs/superpowers/specs/2026-04-15-wporg-review-fixes-design.md`

---

## Chunk 1: Simple Fixes

These four tasks are independent and can run in parallel.

### Task 1: Fix Author/Plugin URIs and composer.json URLs

**Files:**
- Modify: `another-blocks-for-dokan.php:4,8`
- Modify: `composer.json:9,21,23,24`

- [ ] **Step 1: Fix plugin header URIs**

In `another-blocks-for-dokan.php`, replace:
```
Plugin URI: https://theanother.org/plugin/another-blocks-for-dokan/
```
with:
```
Plugin URI: https://the-another.org/plugin/another-blocks-for-dokan/
```

And replace:
```
Author URI: https://theanother.org
```
with:
```
Author URI: https://the-another.org
```

- [ ] **Step 2: Fix composer.json URLs**

In `composer.json`, fix all `theanother.org` references to `the-another.org`:
- Line 9: `"email": "hello@the-another.org"`
- Line 10: `"url": "https://the-another.org"`
- Line 21: `"homepage": "https://the-another.org/plugin/blocks-for-dokan/"`
- Line 23: `"issues": "https://github.com/the-another/blocks-for-dokan/issues"`
- Line 24: `"source": "https://github.com/the-another/blocks-for-dokan"`

- [ ] **Step 3: Commit**

```bash
git add another-blocks-for-dokan.php composer.json
git commit -m "fix: correct Author/Plugin URIs to the-another.org"
```

---

### Task 2: Extract Inline Script to Enqueued File

**Files:**
- Create: `blocks/vendor-search/view.js`
- Modify: `blocks/vendor-search/render.php` (remove lines ~300-346)
- Modify: `includes/class-blocks.php` (add script registration near line 144)

- [ ] **Step 1: Create `blocks/vendor-search/view.js`**

```js
/**
 * Vendor search block frontend script.
 *
 * Handles filter form toggle and sort-by auto-submit.
 *
 * @package AnotherBlocksForDokan
 */

( function () {
	var filterButton = document.querySelector( '.theabd--vendor-query-loop-filter-button' );
	var filterForm = document.getElementById( 'theabd--vendor-query-looping-filter-form-wrap' );
	var cancelButton = document.getElementById( 'cancel-filter-btn' );
	var sortSelect = document.getElementById( 'stores_orderby' );

	// Toggle function for filter form.
	function toggleFilterForm() {
		if ( filterButton && filterForm ) {
			var isExpanded = filterButton.getAttribute( 'aria-expanded' ) === 'true';
			filterButton.setAttribute( 'aria-expanded', ! isExpanded );
			filterForm.style.display = isExpanded ? 'none' : 'block';
		}
	}

	if ( filterButton && filterForm ) {
		// Initialize aria-expanded based on current visibility.
		var isInitiallyVisible = filterForm.style.display !== 'none';
		filterButton.setAttribute( 'aria-expanded', isInitiallyVisible ? 'true' : 'false' );

		// Toggle filter form visibility.
		filterButton.addEventListener( 'click', function ( e ) {
			e.preventDefault();
			toggleFilterForm();
		} );

		// Cancel button uses the same toggle function.
		if ( cancelButton ) {
			cancelButton.addEventListener( 'click', function ( e ) {
				e.preventDefault();
				toggleFilterForm();
			} );
		}
	}

	// Auto-submit sort by select.
	if ( sortSelect ) {
		sortSelect.addEventListener( 'change', function () {
			if ( this.form ) {
				this.form.submit();
			}
		} );
	}
} )();
```

Note: CSS class names use the current `theabd--` prefix. The mass rename in Chunk 2 (Task 7 Step 2) will convert them to `tanbfd--` automatically.

- [ ] **Step 2: Register the script in `includes/class-blocks.php`**

Find the existing `wp_register_script` block for `theabd-vendor-query-loop-view` (around line 144) and add a similar registration immediately after it:

```php
wp_register_script(
    'theabd-vendor-search-view',
    ANOTHER_BLOCKS_FOR_DOKAN_PLUGIN_URL . 'blocks/vendor-search/view.js',
    array(),
    ANOTHER_BLOCKS_FOR_DOKAN_VERSION,
    true
);
```

Note: Uses the current constant/handle names. The mass rename in Chunk 2 will convert them to `tanbfd-`/`THE_ANOTHER_BLOCKS_FOR_DOKAN_` automatically.

- [ ] **Step 3: Replace inline script in `blocks/vendor-search/render.php`**

Remove the entire `<script>...</script>` block (lines ~300-346) and the comment above it. Replace with:

```php
wp_enqueue_script( 'theabd-vendor-search-view' );
```

Place this line just before the `return ob_get_clean();` at the end of the function. The handle will be renamed to `tanbfd-vendor-search-view` by Chunk 2.

- [ ] **Step 4: Verify the script loads**

Run: `npm run build` then load a page with the vendor search block. Confirm:
- Filter toggle button works (click shows/hides filter form)
- Sort-by dropdown auto-submits
- No inline `<script>` tag in page source

- [ ] **Step 5: Commit**

```bash
git add blocks/vendor-search/view.js blocks/vendor-search/render.php includes/class-blocks.php
git commit -m "fix: extract vendor-search inline script to enqueued view.js"
```

---

### Task 3: Add External Services Disclosure to readme.txt

**Files:**
- Modify: `readme.txt` (add section before `== Changelog ==`)

- [ ] **Step 1: Add external services section**

In `readme.txt`, insert the following **before** the `== Changelog ==` line:

```
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

```

- [ ] **Step 2: Verify links resolve**

Open each URL in a browser and confirm they load:
- https://www.mapbox.com/tos/
- https://www.mapbox.com/privacy/
- https://cloud.google.com/maps-platform/terms
- https://policies.google.com/privacy

- [ ] **Step 3: Commit**

```bash
git add readme.txt
git commit -m "docs: disclose Mapbox and Google Maps external services in readme"
```

---

### Task 4: Fix Hardcoded File/Directory Paths

**Files:**
- Modify: `includes/templates/class-store-template.php:159`
- Modify: `includes/class-install.php:53,76`

- [ ] **Step 1: Wrap template canvas path with `wp_normalize_path()`**

In `includes/templates/class-store-template.php`, find line 159:
```php
$canvas_path = ABSPATH . WPINC . '/template-canvas.php';
```
Replace with:
```php
$canvas_path = wp_normalize_path( ABSPATH . WPINC . '/template-canvas.php' );
```

- [ ] **Step 2: Replace `WP_PLUGIN_DIR` usage for Dokan version check**

In `includes/class-install.php`, find lines 75-77:
```php
$dokan_plugin_file = WP_PLUGIN_DIR . '/dokan-lite/dokan.php';
$dokan_version     = self::get_plugin_version( $dokan_plugin_file );
```
Replace with:
```php
if ( ! function_exists( 'get_plugins' ) ) {
    require_once ABSPATH . 'wp-admin/includes/plugin.php';
}
$all_plugins   = get_plugins();
$dokan_version = $all_plugins['dokan-lite/dokan.php']['Version'] ?? null;
```

- [ ] **Step 3: Replace `WP_PLUGIN_DIR` usage for WooCommerce version check**

In `includes/class-install.php`, find lines 52-54:
```php
$wc_plugin_file = WP_PLUGIN_DIR . '/woocommerce/woocommerce.php';
$wc_version     = self::get_plugin_version( $wc_plugin_file );
```
Replace with:
```php
if ( ! function_exists( 'get_plugins' ) ) {
    require_once ABSPATH . 'wp-admin/includes/plugin.php';
}
$all_plugins = get_plugins();
$wc_version  = $all_plugins['woocommerce/woocommerce.php']['Version'] ?? null;
```

Note: The `require_once` guard is idempotent, so having it in both branches is fine. To DRY it up, you can extract the `get_plugins()` call to a local variable at the top of `check_dependencies()` when `$use_constants` is false:

```php
$all_plugins = null;
if ( ! $use_constants ) {
    if ( ! function_exists( 'get_plugins' ) ) {
        require_once ABSPATH . 'wp-admin/includes/plugin.php';
    }
    $all_plugins = get_plugins();
}

// Then for WooCommerce:
$wc_version = $all_plugins['woocommerce/woocommerce.php']['Version'] ?? null;

// Then for Dokan:
$dokan_version = $all_plugins['dokan-lite/dokan.php']['Version'] ?? null;
```

- [ ] **Step 4: Run tests**

Run: `composer test`
Expected: All tests pass (the Install class tests should still work since the version check logic is equivalent).

- [ ] **Step 5: Commit**

```bash
git add includes/templates/class-store-template.php includes/class-install.php
git commit -m "fix: replace hardcoded WP_PLUGIN_DIR with get_plugins() API"
```

---

## Chunk 2: Mass Prefix Renames

These are global find-and-replace operations. They must run **sequentially** because they can touch the same files. The order below avoids conflicts — longer/more-specific patterns first.

**Important**: Exclude `vendor/`, `node_modules/`, `dist/`, and `docs/` directories from all replacements. Do not edit `dist/` — it will be rebuilt in Chunk 4.

### Task 5: Rename Constants

**Pattern:** `ANOTHER_BLOCKS_FOR_DOKAN_` → `THE_ANOTHER_BLOCKS_FOR_DOKAN_`

**Files (~45 references):**
- `another-blocks-for-dokan.php`
- `includes/class-install.php`
- `includes/class-blocks.php`
- `includes/class-block-registry.php`
- `includes/templates/class-store-template.php`
- `includes/templates/class-abstract-dokan-template.php`
- `includes/rest/class-vendor-query-loop-controller.php`
- `tests/bootstrap.php`
- `tests/Unit/Blocks/StoreHeaderBlockTest.php`
- `tests/Integration/BlockRenderingTest.php`

- [ ] **Step 1: Run find-and-replace**

```bash
find . -name '*.php' \
  -not -path './vendor/*' \
  -not -path './node_modules/*' \
  -not -path './dist/*' \
  -not -path './docs/*' \
  -exec sed -i '' 's/ANOTHER_BLOCKS_FOR_DOKAN_/THE_ANOTHER_BLOCKS_FOR_DOKAN_/g' {} +
```

- [ ] **Step 2: Verify no stale references remain**

```bash
grep -rn 'ANOTHER_BLOCKS_FOR_DOKAN_' --include='*.php' | grep -v vendor/ | grep -v node_modules/ | grep -v dist/ | grep -v docs/ | grep -v 'THE_ANOTHER'
```

Expected: No output (all instances prefixed with `THE_ANOTHER`).

- [ ] **Step 3: Commit**

```bash
git add -A
git commit -m "refactor: rename constants ANOTHER_BLOCKS_FOR_DOKAN_ → THE_ANOTHER_BLOCKS_FOR_DOKAN_"
```

---

### Task 6: Rename Namespace

**Pattern:** `Blocks_Dokan` → `Blocks_For_Dokan` (in namespace/use contexts)

Also update `composer.json` autoload mappings and Mozart config.

**Files (~75 references):**
- All `includes/**/*.php` files
- `another-blocks-for-dokan.php`
- `tests/**/*.php`
- `composer.json`

- [ ] **Step 1: Run find-and-replace in PHP files**

```bash
find . -name '*.php' \
  -not -path './vendor/*' \
  -not -path './node_modules/*' \
  -not -path './dist/*' \
  -not -path './docs/*' \
  -exec sed -i '' 's/Blocks_Dokan/Blocks_For_Dokan/g' {} +
```

- [ ] **Step 2: Update composer.json**

In `composer.json`, update these values:

Autoload-dev PSR-4 key (line 49):
```json
"The_Another\\Plugin\\Blocks_For_Dokan\\Blocks\\Tests\\": "tests/"
```

Mozart dep_namespace (line 77):
```json
"dep_namespace": "The_Another\\Plugin\\Blocks_For_Dokan\\Blocks\\Dependencies\\"
```

Mozart classmap_prefix (line 80):
```json
"classmap_prefix": "TheAnother_Plugin_Blocks_For_Dokan_"
```

- [ ] **Step 3: Regenerate autoloader**

```bash
composer dump-autoload
```

- [ ] **Step 4: Verify no stale references remain**

```bash
grep -rn 'Blocks_Dokan' --include='*.php' --include='*.json' | grep -v vendor/ | grep -v node_modules/ | grep -v dist/ | grep -v docs/ | grep -v 'Blocks_For_Dokan'
```

Expected: No output.

- [ ] **Step 5: Commit**

```bash
git add -A
git commit -m "refactor: rename namespace Blocks_Dokan → Blocks_For_Dokan"
```

---

### Task 7: Rename Functions and Filter Hook Names (`theabd_` → `tanbfd_`)

**Pattern:** `theabd_` → `tanbfd_` across all PHP files and JS/SCSS where used as identifiers.

This covers standalone functions (31 definitions, ~70 call sites), filter names (6 unique), script handles (`theabd-` → `tanbfd-`), and the transient prefix (`theabd_vql_tpl_` → `tanbfd_vql_tpl_`).

- [ ] **Step 1: Replace `theabd_` in PHP files (functions, filters, transients)**

```bash
find . -name '*.php' \
  -not -path './vendor/*' \
  -not -path './node_modules/*' \
  -not -path './dist/*' \
  -not -path './docs/*' \
  -exec sed -i '' "s/theabd_/tanbfd_/g" {} +
```

- [ ] **Step 2: Replace `theabd-` in PHP files (script/style handles)**

```bash
find . -name '*.php' \
  -not -path './vendor/*' \
  -not -path './node_modules/*' \
  -not -path './dist/*' \
  -not -path './docs/*' \
  -exec sed -i '' "s/theabd-/tanbfd-/g" {} +
```

**Warning**: This must only match handle strings like `'theabd-vendor-query-loop-view'`. Verify it didn't accidentally touch CSS class references `theabd--` (double dash) — those are handled in Task 9. The sed pattern `theabd-` will match `theabd--` too, turning it into `tanbfd--`, which is actually correct and desired. So this is fine.

- [ ] **Step 3: Rename `another_blocks_for_dokan` functions and filter**

```bash
find . -name '*.php' \
  -not -path './vendor/*' \
  -not -path './node_modules/*' \
  -not -path './dist/*' \
  -not -path './docs/*' \
  -exec sed -i '' \
    -e 's/another_blocks_for_dokan_registered_templates/tanbfd_registered_templates/g' \
    -e 's/another_blocks_for_dokan_container/tanbfd_container/g' \
    -e 's/another_blocks_for_dokan_hooks/tanbfd_hooks/g' \
    -e 's/another_blocks_for_dokan()/tanbfd_plugin()/g' \
    -e 's/function another_blocks_for_dokan/function tanbfd_plugin/g' \
    {} +
```

Note: Order matters — replace longer matches first to avoid partial replacements.

- [ ] **Step 4: Rename `dokan-blocks-` script/style handles**

```bash
find . -name '*.php' \
  -not -path './vendor/*' \
  -not -path './node_modules/*' \
  -not -path './dist/*' \
  -not -path './docs/*' \
  -exec sed -i '' "s/dokan-blocks-frontend/tanbfd-blocks-frontend/g; s/dokan-blocks-editor/tanbfd-blocks-editor/g" {} +
```

- [ ] **Step 5: Rename `dokan_store_listing_random_orderby` transient**

```bash
find . -name '*.php' \
  -not -path './vendor/*' \
  -not -path './node_modules/*' \
  -not -path './dist/*' \
  -not -path './docs/*' \
  -exec sed -i '' "s/dokan_store_listing_random_orderby/tanbfd_store_listing_random_orderby/g" {} +
```

- [ ] **Step 6: Rename local variable in main plugin file**

In `another-blocks-for-dokan.php`, replace:
```php
$another_blocks_for_dokan_autoload_file
```
with:
```php
$tanbfd_autoload_file
```

(2 occurrences: the assignment and the `require_once`.)

- [ ] **Step 7: Verify no stale function/filter references remain**

```bash
grep -rn "theabd_\|theabd-\|another_blocks_for_dokan\|dokan-blocks-\|dokan_store_listing" --include='*.php' | grep -v vendor/ | grep -v node_modules/ | grep -v dist/ | grep -v docs/ | grep -v 'tanbfd'
```

Expected: No output (except possibly Dokan core hooks like `dokan_is_store_open` which are external).

- [ ] **Step 8: Commit**

```bash
git add -A
git commit -m "refactor: rename functions/filters/handles theabd_ → tanbfd_, fix dokan_ prefixes"
```

---

### Task 8: Rename Text Domain

**Pattern:** `theanother-blocks-for-dokan` → `the-another-blocks-for-dokan` across PHP and JSON files.

- [ ] **Step 1: Replace in PHP files**

```bash
find . -name '*.php' \
  -not -path './vendor/*' \
  -not -path './node_modules/*' \
  -not -path './dist/*' \
  -not -path './docs/*' \
  -exec sed -i '' "s/theanother-blocks-for-dokan/the-another-blocks-for-dokan/g" {} +
```

- [ ] **Step 2: Replace in block.json files**

```bash
find . -name 'block.json' \
  -not -path './vendor/*' \
  -not -path './node_modules/*' \
  -not -path './dist/*' \
  -exec sed -i '' "s/theanother-blocks-for-dokan/the-another-blocks-for-dokan/g" {} +
```

- [ ] **Step 3: Verify no stale references remain**

```bash
grep -rn 'theanother-blocks-for-dokan' --include='*.php' --include='*.json' | grep -v vendor/ | grep -v node_modules/ | grep -v dist/ | grep -v docs/
```

Expected: No output.

- [ ] **Step 4: Commit**

```bash
git add -A
git commit -m "refactor: rename text domain to the-another-blocks-for-dokan"
```

---

### Task 9: Rename CSS Classes

**Pattern:** `theabd--` → `tanbfd--` across PHP, SCSS, and JS files.

Note: If Task 7 Step 2 already converted `theabd-` → `tanbfd-` (which catches `theabd--` too), this task may already be done. Verify first.

- [ ] **Step 1: Check if already renamed**

```bash
grep -rc 'theabd--' --include='*.php' --include='*.scss' --include='*.js' -r . | grep -v vendor/ | grep -v node_modules/ | grep -v dist/ | grep -v docs/ | grep -v ':0$'
```

If output is empty, skip to Step 3. If references remain, proceed to Step 2.

- [ ] **Step 2: Run find-and-replace (if needed)**

```bash
find . \( -name '*.php' -o -name '*.scss' -o -name '*.js' \) \
  -not -path './vendor/*' \
  -not -path './node_modules/*' \
  -not -path './dist/*' \
  -not -path './docs/*' \
  -exec sed -i '' "s/theabd--/tanbfd--/g" {} +
```

- [ ] **Step 3: Also rename in SCSS/JS files any single-dash `theabd-` references not yet caught**

```bash
grep -rn 'theabd' --include='*.scss' --include='*.js' | grep -v vendor/ | grep -v node_modules/ | grep -v dist/ | grep -v docs/
```

If any remain, replace them.

- [ ] **Step 4: Commit**

```bash
git add -A
git commit -m "refactor: rename CSS class prefix theabd-- → tanbfd--"
```

---

### Task 10: Update `.phpcs.xml.dist` Prefixes

The PHPCS config defines allowed prefixes and text domain. Without updating this, `composer lint` will flag every renamed identifier.

**Files:**
- Modify: `.phpcs.xml.dist` (lines 64, 72-74)

- [ ] **Step 1: Update text domain**

In `.phpcs.xml.dist`, find:
```xml
<element value="theanother-blocks-for-dokan"/>
```
Replace with:
```xml
<element value="the-another-blocks-for-dokan"/>
```

- [ ] **Step 2: Update prefix list**

Find the `prefixes` property block:
```xml
<element value="theabd"/>
<element value="another_blocks_for_dokan"/>
<element value="The_Another\Plugin\Blocks_Dokan"/>
```
Replace with:
```xml
<element value="tanbfd"/>
<element value="The_Another\Plugin\Blocks_For_Dokan"/>
```

(Two prefixes is sufficient — `tanbfd` covers functions/filters/handles/transients, and the namespace covers classes.)

- [ ] **Step 3: Commit**

```bash
git add .phpcs.xml.dist
git commit -m "config: update .phpcs.xml.dist with new prefixes and text domain"
```

---

## Chunk 3: Output Escaping

### Task 11: Fix All Output Escaping

**Strategy:** Find every `phpcs:ignore WordPress.Security.EscapeOutput` in block render files, wrap the echoed expression with `wp_kses_post()`, and remove the ignore comment.

**Files:** All `blocks/*/render.php` files (~19 files, ~45 instances).

- [ ] **Step 1: Get the full list of instances**

```bash
grep -rn 'phpcs:ignore WordPress.Security.EscapeOutput' blocks/*/render.php
```

Record the output — this is your checklist.

- [ ] **Step 2: Fix `$wrapper_attributes` instances (~20)**

For every block's `render.php`, find patterns like:
```php
echo $wrapper_attributes; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
```
Replace with:
```php
echo wp_kses_post( $wrapper_attributes );
```

Blocks to check: `vendor-card`, `vendor-search`, `vendor-store-location`, `vendor-store-status`, `vendor-store-sidebar`, `vendor-store-terms-conditions`, `vendor-query-pagination`, `vendor-store-tabs`, `vendor-avatar`, `vendor-store-phone`, `vendor-store-name`, `vendor-store-header`, `vendor-store-hours`, `vendor-store-address`, `become-vendor-cta`, `vendor-contact-form`, `vendor-query-loop`, `product-vendor-info`, `more-from-seller`.

- [ ] **Step 3: Fix `$style_attr` / `$wrapper_style_attr` / `$img_style_attr` instances (~5)**

Same pattern — wrap with `wp_kses_post()`, remove phpcs:ignore. Check `vendor-card`, `vendor-avatar`, `product-vendor-info`.

- [ ] **Step 4: Fix `$aria_attrs` instance (~1)**

In `vendor-store-tabs/render.php`, wrap with `wp_kses_post()`.

- [ ] **Step 5: Fix `WP_Block::render()` instances (~10)**

For every `echo $inner_block_instance->render()` or similar, wrap with `wp_kses_post()`. Check `vendor-card`, `vendor-query-loop`, `product-vendor-info`.

- [ ] **Step 6: Fix `$content` instances (~3)**

Wrap with `wp_kses_post()`. Check `vendor-card` (x2), `product-vendor-info`.

- [ ] **Step 7: Fix helper function output instances (~2)**

Wrap `tanbfd_vendor_query_loop_render_items()` calls (already renamed from `theabd_`) with `wp_kses_post()` in `vendor-query-loop/render.php`.

- [ ] **Step 8: Fix third-party function output (~1)**

Wrap `dokan_generate_ratings()` in `vendor-rating/render.php` with `wp_kses_post()`.

- [ ] **Step 9: Verify no phpcs:ignore escaping comments remain**

```bash
grep -rn 'phpcs:ignore WordPress.Security.EscapeOutput' blocks/*/render.php
```

Expected: No output.

- [ ] **Step 10: Commit**

```bash
git add blocks/
git commit -m "fix: escape all output with wp_kses_post(), remove phpcs:ignore suppressions"
```

---

## Chunk 4: Build, Verify, and Update Docs

### Task 12: Rebuild dist/ and Run Quality Checks

- [ ] **Step 1: Rebuild JavaScript/CSS assets**

```bash
npm run build
```

Expected: Build completes successfully. The `dist/` directory now contains assets with the new `tanbfd--` CSS class names and `the-another-blocks-for-dokan` text domain.

- [ ] **Step 2: Run PHP linter**

```bash
composer lint
```

Expected: No errors. If escaping warnings remain, fix them and re-run.

- [ ] **Step 3: Run PHP tests**

```bash
composer test
```

Expected: All tests pass. If tests fail due to renamed constants/functions/namespaces, update the test files accordingly (they should have been caught by the find-and-replace in Chunk 2, but verify).

- [ ] **Step 4: Commit rebuilt dist/**

```bash
git add dist/
git commit -m "build: rebuild dist/ with new prefixes and text domain"
```

---

### Task 13: Update CLAUDE.md

The CLAUDE.md contains references to old prefixes, constants, and namespace. Update it to reflect the new naming.

**Files:**
- Modify: `CLAUDE.md`

- [ ] **Step 1: Update all references**

Key changes:
- Namespace: `The_Another\Plugin\Blocks_Dokan` → `The_Another\Plugin\Blocks_For_Dokan`
- Prefix: `theabd` → `tanbfd` (render functions, CSS prefix)
- CSS prefix: `theabd--` → `tanbfd--`
- Constants: `ANOTHER_BLOCKS_DOKAN_*` → `THE_ANOTHER_BLOCKS_FOR_DOKAN_*`
- Text domain: `theanother-blocks-for-dokan` → `the-another-blocks-for-dokan`
- Author URI / Homepage URLs: `theanother.org` → `the-another.org`

Search for all occurrences of these old strings and replace them throughout the file.

- [ ] **Step 2: Commit**

```bash
git add CLAUDE.md
git commit -m "docs: update CLAUDE.md with new prefixes, namespace, and text domain"
```

---

### Task 14: Final Verification

- [ ] **Step 1: Comprehensive stale-reference check**

Run all of these and confirm no output for each:

```bash
# Old constants
grep -rn 'ANOTHER_BLOCKS_FOR_DOKAN_' --include='*.php' | grep -v vendor/ | grep -v node_modules/ | grep -v dist/ | grep -v docs/ | grep -v 'THE_ANOTHER'

# Old function prefix
grep -rn 'theabd_' --include='*.php' | grep -v vendor/ | grep -v node_modules/ | grep -v dist/ | grep -v docs/

# Old CSS prefix
grep -rn 'theabd--' --include='*.php' --include='*.scss' --include='*.js' | grep -v vendor/ | grep -v node_modules/ | grep -v dist/ | grep -v docs/

# Old handle prefix
grep -rn "'theabd-" --include='*.php' | grep -v vendor/ | grep -v node_modules/ | grep -v dist/ | grep -v docs/

# Old text domain
grep -rn 'theanother-blocks-for-dokan' --include='*.php' --include='*.json' | grep -v vendor/ | grep -v node_modules/ | grep -v dist/ | grep -v docs/

# Old namespace
grep -rn 'Blocks_Dokan' --include='*.php' --include='*.json' | grep -v vendor/ | grep -v node_modules/ | grep -v dist/ | grep -v docs/ | grep -v 'Blocks_For_Dokan'

# Old script handles
grep -rn 'dokan-blocks-' --include='*.php' | grep -v vendor/ | grep -v node_modules/ | grep -v dist/ | grep -v docs/

# Remaining escape suppressions in render files
grep -rn 'phpcs:ignore WordPress.Security.EscapeOutput' blocks/*/render.php

# Old function names
grep -rn 'another_blocks_for_dokan' --include='*.php' | grep -v vendor/ | grep -v node_modules/ | grep -v dist/ | grep -v docs/
```

- [ ] **Step 2: Run full test suite one final time**

```bash
composer lint && composer test
```

Expected: All pass.

- [ ] **Step 3: Manual smoke test**

Activate the plugin in a WordPress environment with Dokan and WooCommerce. Verify:
- Plugin activates without errors
- Blocks appear in the block editor
- Vendor store pages render correctly on the frontend
- Filter toggle works on the store list page
- No console errors in browser developer tools
