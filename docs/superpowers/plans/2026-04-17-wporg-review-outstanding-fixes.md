# WP.org Review — Outstanding Fixes Implementation Plan

> **For agentic workers:** REQUIRED: Use superpowers:subagent-driven-development (if subagents available) or superpowers:executing-plans to implement this plan. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Resolve the three outstanding issues from the wp.org plugin review of *Another Blocks for Dokan* v1.0.13 — broken `block.json` render callbacks, the `ABSPATH . WPINC` canvas path, and the accompanying release metadata — so the plugin can be resubmitted for re-review.

**Architecture:** This is a remediation patch against an existing codebase, not new functionality. Work proceeds in three independent chunks: (1) rename `theabd_` → `tanbfd_` in 7 `block.json` files to match the actual `tanbfd_render_*_block` function names, (2) refactor `Store_Template::override_store_template()` to stop referencing `ABSPATH . WPINC . '/template-canvas.php'` directly by delegating canvas resolution to WordPress core, and (3) bump version + changelog. No new files are created; all changes are in-place edits.

**Tech Stack:** PHP 8.3, WordPress 6.0+, `register_block_type_from_metadata()`, `composer lint` (PHPCS / WordPress-Extra), Plugin Check plugin, `scripts/version-bump.js`.

**Out of scope:**
- Removing `// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped` suppressions in `blocks/*/render.php`. Per `memory/feedback_phpcs_ignore_not_redundant.md`, Plugin Check still reports those echoes as unescaped if the suppressions are removed, even though `tanbfd_kses_block` is registered in `.phpcs.xml.dist`. Leave them in place.
- The readme `== External services ==` section (already present and reviewed).
- The `Author URI` / `Plugin URI` domain (already corrected to `the-another.org`).
- The `includes/class-install.php:76` hardcoded path (already replaced with `get_plugins()`).

---

## File Structure

Files touched by this plan:

- **Modify** — 7 block metadata files (rename `render` field):
  - `blocks/become-vendor-cta/block.json`
  - `blocks/vendor-contact-form/block.json`
  - `blocks/vendor-store-hours/block.json`
  - `blocks/vendor-store-location/block.json`
  - `blocks/vendor-store-sidebar/block.json`
  - `blocks/vendor-store-tabs/block.json`
  - `blocks/vendor-store-terms-conditions/block.json`
- **Modify** — `includes/templates/class-store-template.php` (refactor canvas path handling in `override_store_template()`)
- **Modify** — `the-another-blocks-for-dokan.php` (version header)
- **Modify** — `package.json`, `composer.json` (version)
- **Modify** — `readme.txt` (stable tag + changelog entry)

No new files. No changes to JavaScript, SCSS, or tests outside what the refactor requires.

---

## Chunk 1: Fix broken block.json render callbacks

### Task 1: Confirm current render callback mismatch

**Files:**
- Read-only: `blocks/*/block.json`, `blocks/*/render.php`, `includes/class-block-registry.php`

- [ ] **Step 1: Enumerate the mismatches**

Run:
```bash
grep -rn '"render": *"theabd_' blocks/
```

Expected output (exactly 7 lines):
```
blocks/become-vendor-cta/block.json:48:    "render": "theabd_render_become_vendor_cta_block"
blocks/vendor-contact-form/block.json:31:    "render": "theabd_render_vendor_contact_form_block"
blocks/vendor-store-hours/block.json:42:    "render": "theabd_render_vendor_store_hours_block"
blocks/vendor-store-location/block.json:48:    "render": "theabd_render_vendor_store_location_block"
blocks/vendor-store-sidebar/block.json:34:    "render": "theabd_render_vendor_store_sidebar_block"
blocks/vendor-store-tabs/block.json:35:    "render": "theabd_render_vendor_store_tabs_block"
blocks/vendor-store-terms-conditions/block.json:48:    "render": "theabd_render_vendor_store_terms_conditions_block"
```

- [ ] **Step 2: Confirm the actual function names are `tanbfd_render_*`**

Run:
```bash
grep -rn '^function \(theabd\|tanbfd\)_render_' blocks/ | grep -E '(become-vendor-cta|vendor-contact-form|vendor-store-hours|vendor-store-location|vendor-store-sidebar|vendor-store-tabs|vendor-store-terms-conditions)/render.php'
```

Expected: 7 lines, each starting `function tanbfd_render_<block>_block(`. If any line shows `theabd_` as the function name, STOP and report — the scope of this plan is wrong and needs revision before proceeding.

- [ ] **Step 3: Note that runtime works via Block_Registry's override**

Read `includes/class-block-registry.php:60-97`. Confirm that `register_block_type_from_metadata( $block_dir, $args )` is called with `$args['render_callback']` set from the `$render_function_map` in the class. This explicit callback override is why the blocks function at runtime despite the bad block.json values. The fix is still required because:
1. Plugin Check (wp.org review tool) performs static analysis on `block.json` and flags the legacy `theabd_` prefix against the plugin's declared prefix list (`tanbfd`, `THE_ANOTHER_BLOCKS_FOR_DOKAN`, `The_Another\Plugin\Blocks_For_Dokan`).
2. If anyone removes `Block_Registry`'s fallback map in the future, the blocks will silently stop rendering.
3. Block metadata should reflect reality for third-party tools that read `block.json`.

No commit yet — this task is investigation only.

---

### Task 2: Rename render callback in `become-vendor-cta/block.json`

**Files:**
- Modify: `blocks/become-vendor-cta/block.json:48`

- [ ] **Step 1: Edit block.json**

Replace:
```json
"render": "theabd_render_become_vendor_cta_block"
```
With:
```json
"render": "tanbfd_render_become_vendor_cta_block"
```

- [ ] **Step 2: Verify the referenced function exists**

Run:
```bash
grep -n '^function tanbfd_render_become_vendor_cta_block' blocks/become-vendor-cta/render.php
```

Expected: exactly one match. If zero matches, STOP — the rename would break the block.

- [ ] **Step 3: Re-build so the dist/blocks manifest picks up the new metadata**

Run:
```bash
npm run build
```

Expected: exit code 0, no errors mentioning `become-vendor-cta`.

- [ ] **Step 4: Do not commit yet**

We will batch all 7 renames into a single commit at the end of this chunk.

---

### Task 3: Rename render callback in `vendor-contact-form/block.json`

**Files:**
- Modify: `blocks/vendor-contact-form/block.json:31`

- [ ] **Step 1: Edit block.json**

Replace `"render": "theabd_render_vendor_contact_form_block"` with `"render": "tanbfd_render_vendor_contact_form_block"`.

- [ ] **Step 2: Verify the function exists**

Run: `grep -n '^function tanbfd_render_vendor_contact_form_block' blocks/vendor-contact-form/render.php`

Expected: exactly one match.

---

### Task 4: Rename render callback in `vendor-store-hours/block.json`

**Files:**
- Modify: `blocks/vendor-store-hours/block.json:42`

- [ ] **Step 1: Edit block.json**

Replace `"render": "theabd_render_vendor_store_hours_block"` with `"render": "tanbfd_render_vendor_store_hours_block"`.

- [ ] **Step 2: Verify the function exists**

Run: `grep -n '^function tanbfd_render_vendor_store_hours_block' blocks/vendor-store-hours/render.php`

Expected: exactly one match.

---

### Task 5: Rename render callback in `vendor-store-location/block.json`

**Files:**
- Modify: `blocks/vendor-store-location/block.json:48`

- [ ] **Step 1: Edit block.json**

Replace `"render": "theabd_render_vendor_store_location_block"` with `"render": "tanbfd_render_vendor_store_location_block"`.

- [ ] **Step 2: Verify the function exists**

Run: `grep -n '^function tanbfd_render_vendor_store_location_block' blocks/vendor-store-location/render.php`

Expected: exactly one match.

---

### Task 6: Rename render callback in `vendor-store-sidebar/block.json`

**Files:**
- Modify: `blocks/vendor-store-sidebar/block.json:34`

- [ ] **Step 1: Edit block.json**

Replace `"render": "theabd_render_vendor_store_sidebar_block"` with `"render": "tanbfd_render_vendor_store_sidebar_block"`.

- [ ] **Step 2: Verify the function exists**

Run: `grep -n '^function tanbfd_render_vendor_store_sidebar_block' blocks/vendor-store-sidebar/render.php`

Expected: exactly one match.

---

### Task 7: Rename render callback in `vendor-store-tabs/block.json`

**Files:**
- Modify: `blocks/vendor-store-tabs/block.json:35`

- [ ] **Step 1: Edit block.json**

Replace `"render": "theabd_render_vendor_store_tabs_block"` with `"render": "tanbfd_render_vendor_store_tabs_block"`.

- [ ] **Step 2: Verify the function exists**

Run: `grep -n '^function tanbfd_render_vendor_store_tabs_block' blocks/vendor-store-tabs/render.php`

Expected: exactly one match.

---

### Task 8: Rename render callback in `vendor-store-terms-conditions/block.json`

**Files:**
- Modify: `blocks/vendor-store-terms-conditions/block.json:48`

- [ ] **Step 1: Edit block.json**

Replace `"render": "theabd_render_vendor_store_terms_conditions_block"` with `"render": "tanbfd_render_vendor_store_terms_conditions_block"`.

- [ ] **Step 2: Verify the function exists**

Run: `grep -n '^function tanbfd_render_vendor_store_terms_conditions_block' blocks/vendor-store-terms-conditions/render.php`

Expected: exactly one match.

---

### Task 9: Build, lint, and verify no `theabd_` references remain in source

**Files:** none modified in this task

- [ ] **Step 1: Rebuild block assets**

Run: `npm run build`

Expected: exit 0, no errors.

- [ ] **Step 2: Confirm zero `theabd_` references outside docs and readme changelog**

Run:
```bash
grep -rn 'theabd' --include='*.php' --include='*.json' --include='*.js' \
  --exclude-dir=vendor --exclude-dir=node_modules --exclude-dir=dist --exclude-dir=docs \
  blocks includes src the-another-blocks-for-dokan.php
```

Expected: **no output** (empty result, exit 1 from grep is fine). If any match appears in these trees, rename it.

Note: `readme.txt:106` has a historical changelog entry mentioning `theabd_vendor_query_loop_infinite_filters`. Do **not** edit historical changelog entries — they record past releases accurately.

- [ ] **Step 3: Run PHPCS**

Run: `composer lint`

Expected: exit 0, empty or near-empty output. If new violations appear, they likely relate to the block.json change indirectly (unlikely) — investigate before proceeding.

- [ ] **Step 4: Smoke-test the blocks manually**

Per `CLAUDE.md` guidance on UI changes, this step requires the dev environment. If DDEV is available:

```bash
ddev start
ddev launch /wp-admin
```

In the editor, insert each of the 7 renamed blocks on a test page and confirm they render without PHP errors in `wp-content/debug.log`. If DDEV is not running, note this in the commit message as "manual testing pending" so the reviewer runs through it before the release zip.

- [ ] **Step 5: Commit**

```bash
git add blocks/become-vendor-cta/block.json \
  blocks/vendor-contact-form/block.json \
  blocks/vendor-store-hours/block.json \
  blocks/vendor-store-location/block.json \
  blocks/vendor-store-sidebar/block.json \
  blocks/vendor-store-tabs/block.json \
  blocks/vendor-store-terms-conditions/block.json \
  dist/

git commit -m "fix: align block.json render callbacks with actual tanbfd_ function names"
```

Expected: commit succeeds, no hook failures.

---

## Chunk 2: Refactor canvas path in Store_Template

### Task 10: Investigate public WordPress APIs for the canvas path

**Files:** read-only review of `includes/templates/class-store-template.php:118-164`

- [ ] **Step 1: Read the current implementation**

Read `includes/templates/class-store-template.php:118-164`. The method `override_store_template()`:
1. Detects store pages and block-theme activation.
2. Resolves a block template object via `get_block_template()`.
3. Sets `$_wp_current_template_id` and `$_wp_current_template_content` globals.
4. Returns `wp_normalize_path( ABSPATH . WPINC . '/template-canvas.php' )`.

The wp.org reviewer flagged step 4 — `ABSPATH` and `WPINC` are WordPress internal constants.

- [ ] **Step 2: Understand why WP core returns this same path**

WordPress 6.0+ `wp-includes/template-loader.php` does the exact same thing: when a block template resolves, it returns the canvas path using `ABSPATH . WPINC . '/template-canvas.php'`. There is **no public WordPress API** that exposes this path. The reviewer's objection is about the pattern, not the correctness — the plugin is replicating WP core's own internal behavior.

- [ ] **Step 3: Choose between two remediation approaches**

**Approach A (preferred) — Let WP core handle the canvas.**
Instead of returning the canvas path from `template_include`, hook earlier (at the `block_template_hierarchy` or `get_block_template` filter) to inject our block template, then return the incoming `$template` unchanged from `template_include`. WordPress core's own `locate_block_template()` flow will then find our template and set the canvas itself.

**Approach B (fallback) — Annotate with phpcs:ignore.**
Keep the current code and add `// phpcs:ignore WordPress.Files.FileName,WordPress.WP.AlternativeFunctions -- referencing WP core's internal template-canvas.php; no public API exists.` to line 158. Add a PHPDoc comment on the method explaining the limitation.

Attempt Approach A first. If it breaks Dokan integration (Approach A requires the template to be discoverable via WP's own template lookup, which depends on our template being registered in a way core recognises), fall back to Approach B.

No edits yet — this task is decision-making only.

---

### Task 11: Attempt Approach A — delegate canvas resolution to WP core

**Files:**
- Modify: `includes/templates/class-store-template.php:118-164`
- Test: `tests/Integration/Templates/StoreTemplateTest.php` (may need new cases)

- [ ] **Step 1: Write the failing test first (TDD)**

Read `tests/Integration/Templates/StoreTemplateTest.php` (if it exists) or locate the nearest equivalent. Add a test asserting that `override_store_template()` no longer references `template-canvas.php` directly:

```php
public function test_override_store_template_does_not_reference_canvas_path(): void {
    $reflection = new \ReflectionMethod( Store_Template::class, 'override_store_template' );
    $source     = file_get_contents( $reflection->getFileName() );
    $start_line = $reflection->getStartLine();
    $end_line   = $reflection->getEndLine();
    $lines      = array_slice(
        explode( "\n", $source ),
        $start_line - 1,
        $end_line - $start_line + 1
    );
    $method_source = implode( "\n", $lines );

    $this->assertStringNotContainsString( 'template-canvas.php', $method_source );
    $this->assertStringNotContainsString( 'ABSPATH . WPINC', $method_source );
}
```

If the `tests/Integration/Templates/` directory does not exist, STOP and ask: should the test live in `tests/Unit/Templates/` alongside existing unit tests, or should a new integration test directory be created? Do not guess.

- [ ] **Step 2: Run the new test — expect RED**

Run: `composer test:unit -- --filter=test_override_store_template_does_not_reference_canvas_path` (or the integration equivalent).

Expected: FAIL with "template-canvas.php" found.

- [ ] **Step 3: Refactor `override_store_template()` to the new approach**

Replace the body of `override_store_template()` (lines 118–164) so that:

1. The method hooks our template into WP's block template resolution via the `pre_get_block_file_template` or `get_block_templates` filter (instead of setting globals manually).
2. From `template_include`, simply return the incoming `$template` unchanged. WordPress will then invoke its own block template resolution, find our injected template, and return the canvas path itself.

Sketch (adapt to the surrounding class):

```php
public function override_store_template( string $template ): string {
    if ( ! tanbfd_is_store_page() ) {
        return $template;
    }

    if ( ! function_exists( 'wp_is_block_theme' ) || ! wp_is_block_theme() ) {
        return $template;
    }

    // Injection is registered separately (see hook_into_block_template_resolution()).
    // At this point, returning $template unchanged lets WP core's block template
    // loader resolve and render our injected template using the canvas.
    return $template;
}

/**
 * Inject our block template into WordPress's block template resolution.
 *
 * Hook: `pre_get_block_file_template` (filter, 10, 3).
 *
 * @param \WP_Block_Template|null $template  Current resolved template (or null).
 * @param string                  $id        Template ID being requested.
 * @param string                  $template_type Template type ('wp_template').
 * @return \WP_Block_Template|null
 */
public function inject_store_block_template( $template, string $id, string $template_type ) {
    if ( null !== $template ) {
        return $template; // Another handler already resolved it.
    }
    if ( 'wp_template' !== $template_type ) {
        return $template;
    }
    if ( ! tanbfd_is_store_page() ) {
        return $template;
    }

    $current_tab = $this->get_current_store_tab();
    if ( ! $this->has_block_template_for_tab( $current_tab ) ) {
        return $template;
    }

    $template_slug = self::TAB_TEMPLATE_MAP[ $current_tab ];

    $override_template = apply_filters( 'tanbfd_store_template_override', null, $template_slug, $current_tab );

    return $override_template ? $override_template : $this->get_block_template_for_slug( $template_slug );
}
```

Then in the class's hook registration method (find where `template_include` is hooked currently), add:

```php
add_filter( 'pre_get_block_file_template', array( $this, 'inject_store_block_template' ), 10, 3 );
```

- [ ] **Step 4: Run the failing test — expect GREEN**

Run: `composer test:unit -- --filter=test_override_store_template_does_not_reference_canvas_path`

Expected: PASS.

- [ ] **Step 5: Run the full test suite**

Run: `composer test`

Expected: all tests pass. If any `Store_Template` test fails because it depended on the old `template-canvas.php` return value, update the test to reflect the new contract (`override_store_template` returns `$template` unchanged; injection happens via `inject_store_block_template`).

- [ ] **Step 6: Run PHPCS**

Run: `composer lint`

Expected: exit 0, no new violations. In particular, no more `WordPress.WP.DiscouragedConstants` or `WordPress.WP.CapitalPDangit` hits on the store template file.

- [ ] **Step 7: End-to-end smoke test in a block theme**

With DDEV + WP + Dokan running, a block theme active, and a vendor store set up:

1. Visit a vendor's storefront URL (e.g., `/store/test-vendor/`).
2. Confirm the store page renders with the block template (banner, tabs, etc. appear).
3. Visit each supported tab (products, TOC, reviews) and confirm the correct block template loads.
4. Check `wp-content/debug.log` for warnings about undefined templates or missing canvas.

If the page 404s or renders the wrong template, fall back to Task 12 (Approach B).

- [ ] **Step 8: Commit**

```bash
git add includes/templates/class-store-template.php tests/
git commit -m "refactor: inject store block template via pre_get_block_file_template instead of referencing WPINC canvas path"
```

---

### Task 12 (fallback): If Approach A fails, apply Approach B

**Files:**
- Modify: `includes/templates/class-store-template.php:118-164`

Only execute this task if Task 11 Step 7 fails or Task 11 Step 5 reveals integration-breaking test failures that cannot be fixed in scope.

- [ ] **Step 1: Revert Task 11's changes**

```bash
git restore includes/templates/class-store-template.php tests/
```

Confirm: `git diff includes/templates/class-store-template.php` is empty.

- [ ] **Step 2: Add an annotated suppression and a PHPDoc note**

Replace line 158:

```php
$canvas_path = wp_normalize_path( ABSPATH . WPINC . '/template-canvas.php' );
```

With:

```php
// phpcs:ignore WordPress.WP.AlternativeFunctions,WordPress.PHP.DiscouragedConstants -- Dereferences WP core's own block template canvas; no public API exposes this path. See https://core.trac.wordpress.org/ticket/XXXXX for discussion.
$canvas_path = wp_normalize_path( ABSPATH . WPINC . '/template-canvas.php' );
```

And add a PHPDoc note at the top of `override_store_template()`:

```
 *
 * NOTE: This method returns the path to WordPress core's template-canvas.php.
 * WordPress does not expose a public API for locating this file, so we
 * dereference ABSPATH . WPINC directly — the same construction WP core itself
 * uses in wp-includes/template-loader.php. If WordPress ever ships a helper,
 * swap this for it.
```

- [ ] **Step 3: Verify PHPCS passes**

Run: `composer lint`

Expected: exit 0. The phpcs:ignore comment should suppress the relevant sniffs.

- [ ] **Step 4: Document the decision in the review response email**

The response email (see Chunk 3 Task 14) must note that we evaluated the cleaner approach (hooking `pre_get_block_file_template`) but fell back because [reason]. The reviewer needs to see the diligence.

- [ ] **Step 5: Commit**

```bash
git add includes/templates/class-store-template.php
git commit -m "chore: annotate canvas path reference with rationale for WP core dereference"
```

---

## Chunk 3: Version bump, changelog, and release readiness

### Task 13: Bump version to 1.0.14

**Files:**
- Modify: `the-another-blocks-for-dokan.php` (header `Version:` field)
- Modify: `package.json` (`version` field)
- Modify: `composer.json` (if it has a version field; check first)
- Modify: `readme.txt` (`Stable tag`)

- [ ] **Step 1: Use the existing version-bump script**

Run: `npm run version:patch`

Expected: script updates `package.json` from `1.0.13` to `1.0.14`, and propagates to `the-another-blocks-for-dokan.php` and `readme.txt`.

- [ ] **Step 2: Verify all version references are consistent**

Run:
```bash
grep -n '1\.0\.13\|1\.0\.14' the-another-blocks-for-dokan.php package.json composer.json readme.txt
```

Expected: all references point to `1.0.14`. Any lingering `1.0.13` must be manually updated (except historical changelog entries in `readme.txt` — those stay).

- [ ] **Step 3: Lock files**

Run: `composer update --lock` and `npm install` if `package-lock.json` / `composer.lock` need to be refreshed.

Expected: only the version change is reflected in lockfiles.

---

### Task 14: Write the 1.0.14 changelog entry

**Files:**
- Modify: `readme.txt` (changelog section)

- [ ] **Step 1: Add a new changelog entry at the top of the changelog list**

After line containing `== Changelog ==` and before the `= 1.0.13 - 2026-04-16 =` entry, insert:

```
= 1.0.14 - 2026-04-17 =
* Fix: `block.json` render callbacks now use the correct `tanbfd_` prefix (previously referenced legacy `theabd_` names that did not match the actual function definitions)
* Refactor: Removed direct reference to WordPress core's `template-canvas.php` from the store template handler [OR: annotated the remaining reference — adjust to reflect which path was taken in Chunk 2]
```

Adjust the second bullet to match the actual remediation chosen in Chunk 2 (Task 11 refactor vs. Task 12 annotation).

- [ ] **Step 2: Update the `Stable tag` if `npm run version:patch` did not**

Confirm `readme.txt` line 7 reads `Stable tag: 1.0.14`.

---

### Task 15: Final lint, build, and commit

**Files:** none new

- [ ] **Step 1: Clean build**

```bash
rm -rf dist/
npm run build
```

Expected: fresh `dist/` assets, exit 0.

- [ ] **Step 2: Full PHPCS run**

Run: `composer lint`

Expected: exit 0.

- [ ] **Step 3: Full test suite**

Run: `composer test`

Expected: all tests pass.

- [ ] **Step 4: Final grep for `theabd_` in source trees (excluding docs and readme)**

```bash
grep -rn 'theabd' --include='*.php' --include='*.json' --include='*.js' \
  --exclude-dir=vendor --exclude-dir=node_modules --exclude-dir=dist --exclude-dir=docs \
  blocks includes src the-another-blocks-for-dokan.php
```

Expected: no output.

- [ ] **Step 5: Run WordPress Plugin Check against the current working tree**

If Plugin Check is installed in the DDEV environment:

```bash
ddev wp plugin check the-another-blocks-for-dokan --require=plugin-check/plugin.php
```

Expected: no errors for items 1–6 from the original review (URLs, inline script, external services, hardcoded paths, escape output, prefixes). Warnings unrelated to the review items are acceptable but should be triaged.

If Plugin Check is unavailable, note this in the commit message as "Plugin Check verification pending — run before submitting to wp.org."

- [ ] **Step 6: Commit the release**

```bash
git add the-another-blocks-for-dokan.php package.json package-lock.json composer.json composer.lock readme.txt dist/
git commit -m "chore: bump version to 1.0.14"
```

- [ ] **Step 7: Tag (optional, per team convention — check `git log --oneline | head -5` for prior tagging patterns before tagging)**

Do **not** push or create a PR from this plan — the user will handle release ceremony themselves.

---

## Chunk 4: Post-implementation verification before resubmission

### Task 16: Package the release zip and smoke-test it

**Files:** none modified — verification only

- [ ] **Step 1: Build the plugin zip**

Run: `npm run plugin-zip`

Expected: exit 0; a versioned zip appears at the project root (e.g., `the-another-blocks-for-dokan-1.0.14.zip`).

- [ ] **Step 2: Unzip and inspect the contents**

```bash
unzip -l the-another-blocks-for-dokan-1.0.14.zip | head -50
```

Expected: the zip contains `the-another-blocks-for-dokan.php`, `readme.txt`, `blocks/`, `includes/`, `dist/`, `languages/` — but NOT `vendor/`, `node_modules/`, `docs/`, `tests/`, `src/`, or `.git*`.

- [ ] **Step 3: Run Plugin Check against the zip contents**

Upload the zip to a clean WordPress test site (or use the `wp plugin install` + Plugin Check combo in DDEV) and run Plugin Check. The previously-flagged items should all be clear.

- [ ] **Step 4: Confirm readme renders correctly on wp.org parser**

Run the readme through the wp.org readme validator: https://wordpress.org/plugins/developers/readme-validator/

Paste `readme.txt` contents and confirm no errors. Confirm the `== External services ==` section displays correctly.

---

## Risk & Rollback

**Risk:** Task 11's refactor (hooking `pre_get_block_file_template`) may not catch all the template-resolution paths that the existing `template_include` approach covers. If a Dokan store subpage (e.g., `/store/x/reviews/`) stops rendering its block template, that is the regression to watch for.

**Rollback:** Each chunk is an independent commit. To roll back Chunk 2 only:

```bash
git log --oneline | head -10          # Identify commit SHAs
git revert <chunk-2-commit-sha>
```

Chunk 1 is low-risk (metadata rename only, runtime already worked via Block_Registry).

---

## Summary for the Reviewer Email

Once this plan executes cleanly, update the response email drafted earlier with:

- **Item 4 (canvas path):** State which approach was taken — refactored to `pre_get_block_file_template` (Approach A), or retained with annotated suppression (Approach B) and explain why.
- **Item 6 (prefixes):** Confirm the 7 `block.json` callbacks are now renamed; all plugin-defined identifiers use `tanbfd` / `THE_ANOTHER_BLOCKS_FOR_DOKAN` / `The_Another\Plugin\Blocks_For_Dokan`.
- **Version:** Submitted as 1.0.14.

The `phpcs:ignore` suppressions in `blocks/*/render.php` remain intentionally — they are required because Plugin Check reports the wrapped echoes as unescaped regardless of `.phpcs.xml.dist` customEscapingFunctions registration.
