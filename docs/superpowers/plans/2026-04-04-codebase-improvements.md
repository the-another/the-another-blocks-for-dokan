# Codebase Improvements Plan

> **For agentic workers:** REQUIRED: Use superpowers:subagent-driven-development (if subagents available) or superpowers:executing-plans to implement this plan. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Fix inconsistencies, reduce duplication, and improve code quality across the Another Blocks for Dokan plugin.

**Architecture:** Improvements are grouped into independent tasks that can be tackled in any order. Each task targets a specific category of issues. The most impactful changes (text domain fix, vendor context extraction) come first.

**Tech Stack:** PHP 8.3+, WordPress Block API v3, Dokan Lite 4.0+

---

## Chunk 1: Critical Fixes and Code Deduplication

### Task 1: Fix Text Domain in All block.json Files

All 21 `block.json` files declare `"textdomain": "dokan-blocks"` but the plugin's actual text domain is `"another-blocks-for-dokan"` (declared in the main plugin file header and used in all PHP render files). This breaks translation tooling.

**Files:**
- Modify: `blocks/become-vendor-cta/block.json`
- Modify: `blocks/more-from-seller/block.json`
- Modify: `blocks/product-vendor-info/block.json`
- Modify: `blocks/vendor-avatar/block.json`
- Modify: `blocks/vendor-card/block.json`
- Modify: `blocks/vendor-contact-form/block.json`
- Modify: `blocks/vendor-query-loop/block.json`
- Modify: `blocks/vendor-query-pagination/block.json`
- Modify: `blocks/vendor-rating/block.json`
- Modify: `blocks/vendor-search/block.json`
- Modify: `blocks/vendor-store-address/block.json`
- Modify: `blocks/vendor-store-banner/block.json`
- Modify: `blocks/vendor-store-header/block.json`
- Modify: `blocks/vendor-store-hours/block.json`
- Modify: `blocks/vendor-store-location/block.json`
- Modify: `blocks/vendor-store-name/block.json`
- Modify: `blocks/vendor-store-phone/block.json`
- Modify: `blocks/vendor-store-sidebar/block.json`
- Modify: `blocks/vendor-store-status/block.json`
- Modify: `blocks/vendor-store-tabs/block.json`
- Modify: `blocks/vendor-store-terms-conditions/block.json`
- Test: Existing `tests/Integration/BlockRegistrationTest.php`

- [ ] **Step 1: Replace text domain in all block.json files**

In every `blocks/*/block.json` file, change:
```json
"textdomain": "dokan-blocks"
```
to:
```json
"textdomain": "another-blocks-for-dokan"
```

This is a simple find-and-replace across 21 files.

- [ ] **Step 2: Run existing tests to verify nothing broke**

Run: `composer test`
Expected: All tests pass (block registration tests should still pass since text domain doesn't affect PHP registration).

- [ ] **Step 3: Build and verify**

Run: `npm run build`
Expected: Build succeeds without errors.

- [ ] **Step 4: Commit**

```bash
git add blocks/*/block.json
git commit -m "fix: correct text domain from 'dokan-blocks' to 'another-blocks-for-dokan' in all block.json files"
```

---

### Task 2: Extract Vendor Context Resolution Helper

8+ render files repeat identical vendor context detection logic (~15 lines each). Extract this into a static helper method on `Vendor_Renderer`.

**Files:**
- Modify: `includes/renderers/class-vendor-renderer.php` (add new method)
- Modify: `blocks/vendor-avatar/render.php:26-44`
- Modify: `blocks/vendor-rating/render.php:23-42`
- Modify: `blocks/vendor-store-name/render.php:23-40`
- Modify: `blocks/vendor-store-phone/render.php:23-39`
- Modify: `blocks/vendor-store-address/render.php:61-77`
- Modify: `blocks/vendor-store-status/render.php` (same pattern)
- Modify: `blocks/vendor-store-banner/render.php` (same pattern)
- Create: `tests/Unit/Renderers/VendorRendererTest.php`

- [ ] **Step 1: Write failing test for the new helper method**

Create `tests/Unit/Renderers/VendorRendererTest.php`:

```php
<?php
/**
 * Vendor Renderer tests.
 *
 * @package AnotherBlocksForDokan
 */

namespace The_Another\Plugin\Blocks_Dokan\Tests\Unit\Renderers;

use The_Another\Plugin\Blocks_Dokan\Renderers\Vendor_Renderer;
use PHPUnit\Framework\TestCase;
use Brain\Monkey;
use Mockery;

class VendorRendererTest extends TestCase {

    protected function setUp(): void {
        parent::setUp();
        Monkey\setUp();
    }

    protected function tearDown(): void {
        Monkey\tearDown();
        Mockery::close();
        parent::tearDown();
    }

    public function test_resolve_vendor_from_context_returns_context_vendor(): void {
        $vendor = array(
            'id'         => 42,
            'store_name' => 'Test Store',
        );

        $result = Vendor_Renderer::resolve_vendor_from_context( $vendor );

        $this->assertSame( 42, $result['id'] );
        $this->assertSame( 'Test Store', $result['store_name'] );
    }

    public function test_resolve_vendor_from_context_returns_null_when_empty(): void {
        $result = Vendor_Renderer::resolve_vendor_from_context( null );

        // When context is null and Context_Detector returns 0, result should be null.
        $this->assertNull( $result );
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `composer test:unit -- --filter=VendorRendererTest`
Expected: FAIL — method `resolve_vendor_from_context` does not exist.

- [ ] **Step 3: Implement the helper method**

Add to `includes/renderers/class-vendor-renderer.php` at the end of the class (before closing brace):

```php
/**
 * Resolve vendor data from block context, falling back to page context detection.
 *
 * Use this in render callbacks to avoid repeating the context-then-detect pattern.
 *
 * @param array<string, mixed>|null $context_vendor The vendor from $block->context['dokan/vendor'], or null.
 * @param array<string, string>     $fields         Map of context keys to Vendor_Renderer data keys to include
 *                                                  when falling back to get_vendor_data(). Always includes 'id'.
 *                                                  Example: [ 'store_name' => 'shop_name', 'shop_url' => 'shop_url' ].
 * @return array<string, mixed>|null Vendor array with at least 'id', or null if no vendor found.
 */
public static function resolve_vendor_from_context( ?array $context_vendor, array $fields = array() ): ?array {
    // If context already has valid vendor data, return it directly.
    if ( ! empty( $context_vendor ) && ! empty( $context_vendor['id'] ) ) {
        return $context_vendor;
    }

    // Fall back to detecting vendor from page context.
    $vendor_id = \The_Another\Plugin\Blocks_Dokan\Helpers\Context_Detector::get_vendor_id();

    if ( $vendor_id <= 0 ) {
        return null;
    }

    $vendor_data = self::get_vendor_data( $vendor_id );
    if ( ! $vendor_data ) {
        return null;
    }

    // Build a minimal vendor array with requested fields.
    $vendor = array( 'id' => $vendor_data['id'] );
    foreach ( $fields as $context_key => $data_key ) {
        $vendor[ $context_key ] = $vendor_data[ $data_key ] ?? null;
    }

    return $vendor;
}
```

- [ ] **Step 4: Run test to verify it passes**

Run: `composer test:unit -- --filter=VendorRendererTest`
Expected: PASS

- [ ] **Step 5: Refactor render files to use the new helper**

For each render file, replace the duplicated pattern. Example for `blocks/vendor-avatar/render.php`, replace lines 26-44:

```php
// Before (lines 26-44):
$vendor = $block->context['dokan/vendor'] ?? null;
if ( empty( $vendor ) || empty( $vendor['id'] ) ) {
    $vendor_id = Context_Detector::get_vendor_id();
    if ( $vendor_id > 0 ) {
        $vendor_data = Vendor_Renderer::get_vendor_data( $vendor_id );
        if ( $vendor_data ) {
            $vendor = array(
                'id'         => $vendor_data['id'],
                'store_name' => $vendor_data['shop_name'] ?? '',
                'shop_url'   => $vendor_data['shop_url'] ?? '',
                'gravatar'   => $vendor_data['avatar'] ?? '',
            );
        }
    }
}
```

```php
// After:
$vendor = Vendor_Renderer::resolve_vendor_from_context(
    $block->context['dokan/vendor'] ?? null,
    array(
        'store_name' => 'shop_name',
        'shop_url'   => 'shop_url',
        'gravatar'   => 'avatar',
    )
);
```

Apply similar refactoring to all affected render files:
- `blocks/vendor-rating/render.php` — fields: `'rating' => 'rating'`
- `blocks/vendor-store-name/render.php` — fields: `'store_name' => 'shop_name', 'shop_url' => 'shop_url'`
- `blocks/vendor-store-phone/render.php` — fields: `'phone' => 'phone'`
- `blocks/vendor-store-address/render.php` — fields: `'address' => 'address'`
- `blocks/vendor-store-status/render.php` — fields: depends on what it needs
- `blocks/vendor-store-banner/render.php` — fields: depends on what it needs

Remove the now-unused `use` import for `Context_Detector` from files that imported it only for this pattern (keep it in files that use it elsewhere).

- [ ] **Step 6: Run all tests**

Run: `composer test`
Expected: All tests pass.

- [ ] **Step 7: Commit**

```bash
git add includes/renderers/class-vendor-renderer.php blocks/*/render.php tests/Unit/Renderers/VendorRendererTest.php
git commit -m "refactor: extract vendor context resolution into Vendor_Renderer::resolve_vendor_from_context()"
```

---

### Task 3: Eliminate Manual Inline Style Building in vendor-store-name

The `vendor-store-name/render.php` manually builds inline styles for typography, color, and spacing (lines 58-122) — but WordPress's `get_block_wrapper_attributes()` already handles these when `block.json` declares the correct `supports`. The block.json already declares `color`, `typography`, and `spacing` supports. The manual style building is redundant and likely conflicts with WordPress's own style generation.

**Files:**
- Modify: `blocks/vendor-store-name/render.php:57-122`

- [ ] **Step 1: Verify block.json supports already cover these styles**

Read `blocks/vendor-store-name/block.json` — confirm it has:
- `"color": { "text": true, "background": true }`
- `"typography": { "fontSize": true, "lineHeight": true, "fontWeight": true }`
- `"spacing": { "margin": true, "padding": true }`

If these are declared, WordPress automatically applies the corresponding inline styles via `get_block_wrapper_attributes()`.

- [ ] **Step 2: Remove manual style building**

In `blocks/vendor-store-name/render.php`, remove lines 57-122 (the entire inline styles building section and the separate `$style_attr` variable). Update the template to just use `$wrapper_attributes` without the extra `$style_attr`.

Replace lines 57-134:
```php
// Get wrapper attributes - WordPress handles color, typography, spacing styles automatically via block.json supports.
$wrapper_attributes = get_block_wrapper_attributes(
    array(
        'class' => 'theabd--vendor-store-name',
    )
);

ob_start();
?>
<<?php echo esc_attr( $tag_name ); ?> <?php echo $wrapper_attributes; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
    <?php if ( $is_link && ! empty( $shop_url ) ) : ?>
        <a href="<?php echo esc_url( $shop_url ); ?>">
            <?php echo esc_html( $shop_name ); ?>
        </a>
    <?php else : ?>
        <?php echo esc_html( $shop_name ); ?>
    <?php endif; ?>
</<?php echo esc_attr( $tag_name ); ?>>
<?php
return ob_get_clean();
```

- [ ] **Step 3: E2E test to verify styles still work**

Run: `npm run test:e2e -- --grep "store name"`
Expected: E2E tests pass. If no E2E test covers store name styling specifically, do a manual visual check.

- [ ] **Step 4: Commit**

```bash
git add blocks/vendor-store-name/render.php
git commit -m "refactor: remove redundant manual inline styles from vendor-store-name (handled by block.json supports)"
```

---

## Chunk 2: Performance and Robustness

### Task 4: Optimize N+1 Query in vendor-query-loop

The vendor query loop calls `dokan()->vendor->get($vendor_id)` individually for each seller in the loop (line 251 of `blocks/vendor-query-loop/render.php`). For a default `perPage` of 12, this means 12 separate queries.

**Files:**
- Modify: `blocks/vendor-query-loop/render.php:249-260`

- [ ] **Step 1: Analyze Dokan's vendor caching**

Check whether `dokan()->vendor->get()` already uses object caching internally. If it does, the N+1 issue is mitigated (cache hits after first call in a request). 

To check: `grep -r "wp_cache" vendor/wedevs/dokan/includes/` or read Dokan's vendor manager source.

If Dokan already caches vendor objects, document this finding and skip further optimization. If not, proceed with step 2.

- [ ] **Step 2: (If needed) Prime the user cache**

Before the loop, prime WordPress's user object cache for all seller IDs at once:

```php
// Prime user cache for all sellers at once to avoid N+1 queries.
$seller_ids = wp_list_pluck( $sellers, 'ID' );
cache_users( $seller_ids );
```

Add this right after `$sellers = $user_query->get_results();` (line 139), before the foreach loop. `cache_users()` is a WordPress core function that batch-loads user data.

Note: This only helps with the WordPress user data portion. The Dokan vendor metadata may still be loaded individually. This is acceptable because:
- `$user_query->get_results()` already loads user objects
- The main cost is vendor meta, which Dokan caches internally per request

- [ ] **Step 3: Run E2E tests**

Run: `npm run test:e2e -- --grep "query loop"`
Expected: All query loop tests pass.

- [ ] **Step 4: Commit**

```bash
git add blocks/vendor-query-loop/render.php
git commit -m "perf: prime user cache before vendor query loop to reduce queries"
```

---

### Task 5: Deduplicate Inner Block Sorting Logic

The vendor-query-loop render function separates inner blocks into template/search/pagination categories twice — once for the non-empty case (lines 218-232) and identically for the empty case (lines 351-365). Extract this.

**Files:**
- Modify: `blocks/vendor-query-loop/render.php:218-232, 351-365`

- [ ] **Step 1: Extract block sorting to before the if/else branch**

Move the inner block sorting logic before the `if ( ! empty( $sellers ) )` check. Replace lines 217-232 and 350-365 with a single block before line 208:

```php
// Separate template blocks (vendor-card) from query-level blocks (search, pagination).
$template_blocks   = array();
$search_blocks     = array();
$pagination_blocks = array();

if ( ! empty( $block->inner_blocks ) ) {
    foreach ( $block->inner_blocks as $inner_block ) {
        if ( 'the-another/blocks-for-dokan-vendor-search' === $inner_block->name ) {
            $search_blocks[] = $inner_block;
        } elseif ( 'the-another/blocks-for-dokan-vendor-query-pagination' === $inner_block->name ) {
            $pagination_blocks[] = $inner_block;
        } elseif ( 'the-another/blocks-for-dokan-vendor-card' === $inner_block->name ) {
            $template_blocks[] = $inner_block;
        }
    }
}
```

Remove the duplicate block from the else branch. Also remove the now-unused `$has_pagination_block` and `$has_search_block` variables (lines 178-196) since they're not used anywhere — the code sorts inner blocks by type instead.

- [ ] **Step 2: Run E2E tests**

Run: `npm run test:e2e -- --grep "query loop"`
Expected: All tests pass.

- [ ] **Step 3: Commit**

```bash
git add blocks/vendor-query-loop/render.php
git commit -m "refactor: deduplicate inner block sorting logic in vendor-query-loop"
```

---

### Task 6: Fix Random Order Transient Check

In `blocks/vendor-query-loop/render.php:125-129`, the code sets a transient every time random ordering is used, even if one already exists with remaining TTL. The `get_transient` call on line 125 already returns false only when expired, so the `set_transient` on line 128 only fires when needed. 

**Verdict:** After re-reading the code, this is actually already correct:
```php
$selected_orderby = get_transient( 'dokan_store_listing_random_orderby' );
if ( false === $selected_orderby ) {
    $selected_orderby = $order_by_options[ array_rand( $order_by_options, 1 ) ];
    set_transient( 'dokan_store_listing_random_orderby', $selected_orderby, MINUTE_IN_SECONDS * 5 );
}
```

The `set_transient` is inside the `if ( false === $selected_orderby )` check. No change needed.

---

## Chunk 3: Accessibility and i18n

### Task 7: Improve Accessibility in vendor-search Block

**Files:**
- Modify: `blocks/vendor-search/render.php`

- [ ] **Step 1: Read the vendor-search render.php**

Read the full file to identify all accessibility issues.

- [ ] **Step 2: Add aria-hidden to decorative icon divs**

Find any `<div class="theabd--icon-div">` elements and add `aria-hidden="true"` to confirm they're decorative.

- [ ] **Step 3: Add aria-label to filter toggle button**

If the filter toggle button relies on visual styling only, add an appropriate `aria-label`:
```php
aria-label="<?php esc_attr_e( 'Toggle search filters', 'another-blocks-for-dokan' ); ?>"
```

- [ ] **Step 4: Run E2E tests**

Run: `npm run test:e2e -- --grep "search"`
Expected: Tests pass.

- [ ] **Step 5: Commit**

```bash
git add blocks/vendor-search/render.php
git commit -m "fix: improve accessibility in vendor-search block (aria labels for decorative elements)"
```

---

### Task 8: Add Missing Translator Comments

Several `sprintf()` + `__()` calls lack `/* translators: */` comments, making it harder for translators to understand what placeholders represent.

**Files:**
- Modify: `blocks/vendor-query-loop/render.php` (pagination text)
- Modify: `blocks/vendor-store-header/render.php` (if applicable)
- Modify: Any other render files with `sprintf` + `__()` patterns

- [ ] **Step 1: Search for sprintf + translation patterns without translator comments**

```bash
grep -rn 'sprintf.*__(' blocks/*/render.php | head -20
```

Cross-reference with existing `/* translators: */` comments.

- [ ] **Step 2: Add missing translator comments**

Add `/* translators: %s: description */` comments before each `sprintf` + translation call that lacks one.

- [ ] **Step 3: Run linter**

Run: `composer lint`
Expected: No new linting errors related to translator comments.

- [ ] **Step 4: Commit**

```bash
git add blocks/*/render.php
git commit -m "docs: add missing translator comments for sprintf translation strings"
```

---

## Chunk 4: Code Modernization (PHP 8.3+)

### Task 9: Audit and Modernize PHP Patterns

The plugin targets PHP 8.3+ but some code uses older patterns. This task is about identifying low-risk modernization opportunities — not rewriting working code.

**Files:**
- Potentially all PHP files in `includes/` and `blocks/`

- [ ] **Step 1: Identify modernization opportunities**

Scan for:
1. `switch` statements that could be `match` expressions
2. Missing `readonly` on properties that are set once in constructors
3. Constructor property promotion candidates
4. Places where named arguments would improve readability

- [ ] **Step 2: Apply match expressions where appropriate**

Example in `blocks/more-from-seller/render.php:63-86`:
```php
// Before:
switch ( $order_by ) {
    case 'title':
        $query_args['orderby'] = 'title';
        $query_args['order']   = 'ASC';
        break;
    case 'price':
        ...
}

// After:
[$query_args['orderby'], $query_args['order'], $query_args['meta_key'] ?? null] = match ( $order_by ) {
    'title'      => ['title', 'ASC', null],
    'price'      => ['meta_value_num', 'ASC', '_price'],
    'popularity' => ['meta_value_num', 'DESC', 'total_sales'],
    'date'       => ['date', 'DESC', null],
    default      => ['rand', '', null],
};
```

Note: Only modernize where it improves clarity. Don't modernize for the sake of modernization.

- [ ] **Step 3: Apply readonly properties where appropriate**

In classes like `Container` and `Hook_Manager`, if properties are set only in the constructor and never changed, mark them as `readonly`.

- [ ] **Step 4: Run tests**

Run: `composer test`
Expected: All tests pass.

- [ ] **Step 5: Commit**

```bash
git add includes/ blocks/
git commit -m "refactor: modernize PHP patterns for PHP 8.3+ (match expressions, readonly properties)"
```

---

## Summary of Issues NOT Addressed (and Why)

| Issue | Reason for Skipping |
|-------|-------------------|
| Nonce verification on GET params | These are public search/sort params on a publicly visible page. Adding nonce verification would break bookmark/share URLs and is not standard WordPress practice for read-only public parameters. The `phpcs:ignore` comments acknowledge this. |
| `$_GET` loop in vendor-search | Same as above — public search form parameters. |
| Manual render function map in Block_Registry | Works correctly and changing to auto-discovery adds complexity without clear benefit. The map serves as documentation of which function renders which block. |
| `file_get_contents()` error handling in Store_Template | WordPress template loading is well-established and `file_get_contents()` on a known local file path is reliable. Adding error handling here adds noise. |
| `dokan()` null checks in Context_Detector | The plugin's `Install` class already validates Dokan is active before the plugin initializes. If Dokan is not present, the plugin never runs. |
