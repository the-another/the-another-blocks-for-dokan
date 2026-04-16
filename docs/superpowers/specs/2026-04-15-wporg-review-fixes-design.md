# WordPress.org Plugin Review Fixes — Design Spec

**Date**: 2026-04-15
**Plugin**: Another Blocks for Dokan v1.0.9
**Context**: Addressing all issues raised in the WordPress.org plugin review.

---

## Issue 1: Invalid Author/Plugin URIs

**Problem**: `Author URI` and `Plugin URI` use `theanother.org` which doesn't resolve. The correct domain is `the-another.org`.

**Fix**: Update the main plugin file header:

| Field      | Current                                              | New                                                     |
|------------|------------------------------------------------------|---------------------------------------------------------|
| Author URI | `https://theanother.org`                             | `https://the-another.org`                               |
| Plugin URI | `https://theanother.org/plugin/another-blocks-for-dokan/` | `https://the-another.org/plugin/another-blocks-for-dokan/` |

**Files**: `another-blocks-for-dokan.php` (lines 4, 8)

---

## Issue 2: Inline `<script>` — Use `wp_enqueue` Commands

**Problem**: `blocks/vendor-search/render.php:301` outputs a raw `<script>` tag for filter toggle/sort functionality.

**Fix**: Extract the JavaScript to a standalone file and enqueue it properly:

1. Create `blocks/vendor-search/view.js` containing the filter toggle logic.
2. Register the script handle `theabd-vendor-search-view` in `class-blocks.php` via `wp_register_script()` (same pattern as the existing `theabd-vendor-query-loop-view` registration at line 144).
3. In `render.php`, replace the inline `<script>` block with `wp_enqueue_script( 'theabd-vendor-search-view' )`.
4. The script is self-contained DOM manipulation with no dynamic PHP data, so no `wp_add_inline_script()` or `wp_localize_script()` is needed.

**Files**:
- `blocks/vendor-search/view.js` (new)
- `blocks/vendor-search/render.php` (remove lines 300-346)
- `includes/class-blocks.php` (add registration)

---

## Issue 3: Undocumented External Services (Mapbox & Google Maps)

**Problem**: `blocks/vendor-store-location/render.php` uses Mapbox static maps API and Google Maps Embed API. The `readme.txt` does not disclose these services, their data transmission, or link to terms/privacy policies.

**Fix**: Add an `== External services ==` section to `readme.txt` before `== Changelog ==`:

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

**Files**: `readme.txt`

---

## Issue 4: Hardcoded File/Directory Paths

**Problem**: Two locations use WordPress internal constants directly instead of recommended functions.

### 4a. `includes/templates/class-store-template.php:159`

```php
$canvas_path = ABSPATH . WPINC . '/template-canvas.php';
```

This is actually the standard WordPress approach for loading `template-canvas.php` — WordPress core itself constructs this path the same way. However, to satisfy the reviewer, wrap it with `wp_normalize_path()`:

```php
$canvas_path = wp_normalize_path( ABSPATH . WPINC . '/template-canvas.php' );
```

### 4b. `includes/class-install.php:76`

```php
$dokan_plugin_file = WP_PLUGIN_DIR . '/dokan-lite/dokan.php';
```

Replace with WordPress's `get_plugins()` API, which avoids hardcoding the path entirely:

```php
if ( ! function_exists( 'get_plugins' ) ) {
    require_once ABSPATH . 'wp-admin/includes/plugin.php';
}
$all_plugins   = get_plugins();
$dokan_version = $all_plugins['dokan-lite/dokan.php']['Version'] ?? '';
```

Note: This code runs during plugin activation and admin contexts where `wp-admin/includes/plugin.php` is typically already loaded, making the `require_once` guard a no-op in practice. The `ABSPATH` usage in the guard is acceptable per WordPress conventions (it's not a plugin path).

**Files**:
- `includes/templates/class-store-template.php` (line 159)
- `includes/class-install.php` (line 76, plus refactor the version detection)

---

## Issue 5: Escaping Output

**Problem**: The reviewer flagged 10 specific instances, but the codebase has ~45 `phpcs:ignore WordPress.Security.EscapeOutput` comments across all block render files. All must be addressed.

### Comprehensive strategy

**Find every instance**: Search all `blocks/*/render.php` files for `phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped` and fix each one. The categories below cover every pattern in the codebase.

**A. `$wrapper_attributes` from `get_block_wrapper_attributes()`** (~20 instances across all blocks):

Present in virtually every block's `render.php`. Wrap with `wp_kses_post()`:

```php
echo wp_kses_post( $wrapper_attributes );
```

**Affected files**: `vendor-card`, `vendor-search`, `vendor-store-location`, `vendor-store-status`, `vendor-store-sidebar`, `vendor-store-terms-conditions`, `vendor-query-pagination`, `vendor-store-tabs`, `vendor-avatar`, `vendor-store-phone`, `vendor-store-name`, `vendor-store-header`, `vendor-store-hours`, `vendor-store-address`, `become-vendor-cta`, `vendor-contact-form`, `vendor-query-loop`, `product-vendor-info`, `more-from-seller` — all their `render.php` files.

**B. `$style_attr`, `$wrapper_style_attr`, `$img_style_attr`** (~5 instances):

Manually constructed style attribute strings. Wrap with `wp_kses_post()`:

```php
echo wp_kses_post( $style_attr );
```

**Affected files**: `vendor-card`, `vendor-avatar`, `product-vendor-info`.

**C. `$aria_attrs` and other manually constructed HTML attribute strings** (~1 instance):

```php
echo wp_kses_post( $aria_attrs );
```

**Affected files**: `vendor-store-tabs`.

**D. `WP_Block::render()` output** (~10 instances):

Block renders return HTML from WordPress's block rendering pipeline. Wrap with `wp_kses_post()`:

```php
echo wp_kses_post( $inner_block_instance->render() );
```

**Affected files**: `vendor-card`, `vendor-query-loop`, `product-vendor-info`.

**E. `$content` variable (block inner content)** (~3 instances):

```php
echo wp_kses_post( $content );
```

**Affected files**: `vendor-card` (x2), `product-vendor-info`.

**F. Plugin render helper functions** (~2 instances):

```php
echo wp_kses_post( theabd_vendor_query_loop_render_items( ... ) );
```

**Affected files**: `vendor-query-loop`.

**G. Third-party function output** (~1 instance):

```php
echo wp_kses_post( dokan_generate_ratings( $rating, $count ) );
```

**Affected files**: `vendor-rating`.

### Implementation approach

Rather than tracking individual line numbers (which shift as we edit), the implementer should:
1. Run `grep -rn 'phpcs:ignore WordPress.Security.EscapeOutput' blocks/*/render.php` to get the full list.
2. For each instance, wrap the echoed expression with `wp_kses_post()`.
3. Remove the `// phpcs:ignore` comment.
4. Run `composer lint` to verify no escaping warnings remain.

---

## Issue 6: Generic Prefixes

**Problem**: The reviewer flagged three prefix groups and the namespace starting with "the":
1. `dokan` prefix — 7 elements
2. `another_blocks_for` prefix — 7 elements (these are the `ANOTHER_BLOCKS_FOR_DOKAN_*` constants)
3. `the_another_plugin` prefix — 7 elements

**Decision**: Keep the `The_Another` vendor namespace — it's a proper brand name, not a generic "the" prefix. We'll note this in the review response. However, rename `Blocks_Dokan` → `Blocks_For_Dokan` for clarity and consistency with the plugin name.

### Prefix strategy

| Identifier type | Prefix | Example |
|----------------|--------|---------|
| Standalone functions | `tanbfd_` | `tanbfd_render_vendor_card_block()` |
| Constants | `THE_ANOTHER_BLOCKS_FOR_DOKAN_` | `THE_ANOTHER_BLOCKS_FOR_DOKAN_VERSION` |
| Filter/action hook names | `tanbfd_` | `tanbfd_registered_blocks` |
| Script/style handles | `tanbfd-` | `tanbfd-blocks-frontend` |
| Transient names | `tanbfd_` | `tanbfd_store_listing_random_orderby` |
| CSS classes | `tanbfd--` | `tanbfd--vendor-store-header` |
| Namespace | `The_Another\Plugin\Blocks_For_Dokan` | `The_Another\Plugin\Blocks_For_Dokan\Templates` |
| Text domain | `the-another-blocks-for-dokan` | `__( 'text', 'the-another-blocks-for-dokan' )` |
| Class methods | unchanged | unchanged |

### Identifiers to rename

**A. All standalone functions: `theabd_` → `tanbfd_`** (31 functions, ~70 call sites):

Global find-and-replace of `theabd_` → `tanbfd_` in function definitions and all call sites. Covers:
- 21 render functions (`theabd_render_*_block` → `tanbfd_render_*_block`)
- 4 vendor-query-loop helpers (`theabd_vendor_query_loop_*` → `tanbfd_vendor_query_loop_*`)
- 2 store-tabs helpers (`theabd_get_current_store_tab`, `theabd_is_tab_active`)
- 1 address helper (`theabd_format_address`)
- All references in `includes/class-block-registry.php` (21 render callback strings)
- All references in `includes/rest/class-vendor-query-loop-controller.php`
- All references in test files

**B. Functions using `another_blocks_for_` prefix → rename to `tanbfd_`** (3 functions):
- `functions/functions.php:22` — `another_blocks_for_dokan()` → `tanbfd_plugin()`
- `functions/functions.php:31` — `another_blocks_for_dokan_container()` → `tanbfd_container()`
- `functions/functions.php:40` — `another_blocks_for_dokan_hooks()` → `tanbfd_hooks()`
- Call sites are internal to `functions/functions.php` (they call each other).

**C. Filter/action hook names: `theabd_` → `tanbfd_`** (6 unique filters):
- `theabd_registered_blocks` → `tanbfd_registered_blocks`
- `theabd_store_list_query_args` → `tanbfd_store_list_query_args`
- `theabd_store_search_block_count` → `tanbfd_store_search_block_count`
- `theabd_vendor_query_loop_infinite_filters` → `tanbfd_vendor_query_loop_infinite_filters`
- `theabd_more_from_seller_query_args` → `tanbfd_more_from_seller_query_args`
- `theabd_store_template_override` → `tanbfd_store_template_override`

**D. Filter using `another_blocks_for_` prefix** (1 filter):
- `includes/templates/class-block-templates-controller.php:57` — `another_blocks_for_dokan_registered_templates` → `tanbfd_registered_templates`

**E. Script/style handles using `dokan_` prefix → rename to `tanbfd-`** (3 handles + 1 existing):
- `includes/class-blocks.php:183` — `dokan-blocks-frontend` → `tanbfd-blocks-frontend`
- `includes/class-blocks.php:195` — `dokan-blocks-editor` → `tanbfd-blocks-editor`
- `includes/class-blocks.php:223` — `dokan-blocks-editor` → `tanbfd-blocks-editor`
- Line 197 dependency reference `dokan-blocks-frontend` → `tanbfd-blocks-frontend`
- `includes/class-blocks.php:145` — `theabd-vendor-query-loop-view` → `tanbfd-vendor-query-loop-view`
- `blocks/vendor-query-loop/render.php:402` — same handle in `wp_enqueue_script()`

**F. Transient names using `dokan_` prefix → rename to `tanbfd_`** (2 instances):
- `blocks/vendor-query-loop/render.php:170` — `dokan_store_listing_random_orderby` → `tanbfd_store_listing_random_orderby`
- `blocks/vendor-query-loop/render.php:173` — same transient in `set_transient()`
- Also rename `theabd_vql_tpl_` transient prefix in `class-vendor-query-loop-controller.php:34` → `tanbfd_vql_tpl_`

**G. Local variable in main plugin file** (1 instance):
- `another-blocks-for-dokan.php:45` — `$another_blocks_for_dokan_autoload_file` → `$tanbfd_autoload_file`

**H. Constants: `ANOTHER_BLOCKS_FOR_DOKAN_*` → `THE_ANOTHER_BLOCKS_FOR_DOKAN_*`** (7 defines, ~45 references):

| Old | New |
|-----|-----|
| `ANOTHER_BLOCKS_FOR_DOKAN_VERSION` | `THE_ANOTHER_BLOCKS_FOR_DOKAN_VERSION` |
| `ANOTHER_BLOCKS_FOR_DOKAN_PLUGIN_FILE` | `THE_ANOTHER_BLOCKS_FOR_DOKAN_PLUGIN_FILE` |
| `ANOTHER_BLOCKS_FOR_DOKAN_PLUGIN_DIR` | `THE_ANOTHER_BLOCKS_FOR_DOKAN_PLUGIN_DIR` |
| `ANOTHER_BLOCKS_FOR_DOKAN_PLUGIN_URL` | `THE_ANOTHER_BLOCKS_FOR_DOKAN_PLUGIN_URL` |
| `ANOTHER_BLOCKS_FOR_DOKAN_PLUGIN_BASENAME` | `THE_ANOTHER_BLOCKS_FOR_DOKAN_PLUGIN_BASENAME` |
| `ANOTHER_BLOCKS_FOR_DOKAN_MIN_WOOCOMMERCE_VERSION` | `THE_ANOTHER_BLOCKS_FOR_DOKAN_MIN_WOOCOMMERCE_VERSION` |
| `ANOTHER_BLOCKS_FOR_DOKAN_MIN_DOKAN_VERSION` | `THE_ANOTHER_BLOCKS_FOR_DOKAN_MIN_DOKAN_VERSION` |

Implementation: global find-and-replace `ANOTHER_BLOCKS_FOR_DOKAN_` → `THE_ANOTHER_BLOCKS_FOR_DOKAN_` across all `.php` files (excluding `vendor/`).

**I. CSS classes: `theabd--` → `tanbfd--`** (~415 references across ~45 source files):

Global find-and-replace `theabd--` → `tanbfd--` across all `.php`, `.scss`, `.js` files (excluding `vendor/`, `node_modules/`, `dist/`).

**Affected file types**:
- `blocks/*/render.php` — class names in HTML output
- `blocks/*/style.scss` — frontend styles
- `blocks/*/editor.scss` — editor styles
- `blocks/*/index.js` — editor JSX class names
- `blocks/*/view.js` — frontend JS selectors
- `blocks/_shared/style.scss` — shared styles
- Test files referencing CSS classes

**Important**: After renaming, `dist/` files must be rebuilt with `npm run build`. The built CSS/JS files in `dist/` will be regenerated automatically — do not manually edit them.

**J. Text domain: `theanother-blocks-for-dokan` → `the-another-blocks-for-dokan`** (~104 references):

Global find-and-replace across all `.php` and `block.json` files (excluding `vendor/`, `node_modules/`, `dist/`).

**Key locations**:
- `another-blocks-for-dokan.php` — plugin header `Text Domain:` field
- `blocks/*/block.json` — `"textdomain"` field in every block metadata file
- All `render.php` files — translation function calls (`__()`, `_e()`, `esc_html__()`, `esc_attr__()`, `_n()`, etc.)
- `languages/` directory — rename any `.po`/`.mo` files if they exist

**Important**: After renaming, rebuild with `npm run build` since `block.json` files are bundled into `dist/`.

**K. Namespace: `The_Another\Plugin\Blocks_Dokan` → `The_Another\Plugin\Blocks_For_Dokan`** (~75 references):

Global find-and-replace `Blocks_Dokan` → `Blocks_For_Dokan` in namespace declarations and `use` statements across all `.php` files (excluding `vendor/`).

Also update `composer.json` PSR-4 autoload mapping to point the new namespace to the correct directory.

### Already correct (no changes needed)
- Dokan core hooks like `dokan_is_store_open` (external API, not our prefix)
- `$_wp_current_template_content` (WordPress core global, already has phpcs:ignore)

---

## Out of Scope

- No changes to the block registration names (`the-another/blocks-for-dokan-*`) — these are the published block names and changing them would break existing content.
- Text domain rename covered in Issue 6J below.
- No architectural changes.

---

## Testing Strategy

1. Run `composer lint` after all changes to verify PHPCS compliance.
2. Run `composer test` to verify no regressions.
3. Manual test: activate plugin, verify blocks render correctly on frontend.
4. Verify the inline script replacement works (filter toggle, sort-by auto-submit).
5. Verify external service links in readme resolve correctly.
