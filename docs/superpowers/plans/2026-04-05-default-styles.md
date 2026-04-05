# Unified Form Element Default Styles — Implementation Plan

> **For agentic workers:** REQUIRED: Use superpowers:subagent-driven-development (if subagents available) or superpowers:executing-plans to implement this plan. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Unify form element styles across all blocks by layering `wp-element-button` (theme colors) with a shared SCSS partial (spacing, transitions, focus rings).

**Architecture:** Create `blocks/_shared-forms.scss` imported once via `src/blocks.js`. Add `wp-element-button` class to all buttons in render.php files. Remove all hardcoded colors from form element styles. Use CSS custom properties for overridability.

**Tech Stack:** SCSS, PHP (WordPress block render callbacks), JavaScript (block editor components), `@wordpress/scripts` build system.

**Spec:** `docs/superpowers/specs/2026-04-05-default-styles-design.md`

---

## Chunk 1: Shared SCSS Partial + Build Entry

### Task 1: Create shared forms SCSS partial

**Files:**
- Create: `blocks/_shared-forms.scss`

- [ ] **Step 1: Create `blocks/_shared-forms.scss`**

```scss
/**
 * Shared form element styles.
 *
 * Imported once via src/blocks.js. Individual block style.scss files
 * do NOT import this — they rely on these styles being in the same bundle.
 *
 * @package DokanBlocks
 * @since 1.0.4
 */

// ---------------------------------------------------------------------------
// Mixin: form-control base styles
// Used by .theabd--form-control below. Other blocks that can't add classes
// to their HTML (e.g., Dokan templates) duplicate these values directly
// since each block's style.scss compiles in its own SCSS context.
// ---------------------------------------------------------------------------
@mixin theabd-form-control-styles {
	padding: var(--theabd-input-padding);
	border: 1px solid rgba(0, 0, 0, 0.25);
	border-radius: var(--theabd-input-radius);
	transition:
		border-color var(--theabd-transition-duration) ease,
		box-shadow var(--theabd-transition-duration) ease;
	box-sizing: border-box;

	@supports (color: color-mix(in srgb, red, blue)) {
		border-color: color-mix(in srgb, currentColor 25%, transparent);
	}

	&:focus {
		outline: none;
		box-shadow: 0 0 0 var(--theabd-focus-ring-width) rgba(0, 0, 0, var(--theabd-focus-ring-opacity));

		@supports (color: color-mix(in srgb, red, blue)) {
			box-shadow: 0 0 0 var(--theabd-focus-ring-width) color-mix(in srgb, currentColor 15%, transparent);
		}
	}

	&:hover:not(:focus):not(:disabled) {
		border-color: rgba(0, 0, 0, 0.4);

		@supports (color: color-mix(in srgb, red, blue)) {
			border-color: color-mix(in srgb, currentColor 40%, transparent);
		}
	}
}

// ---------------------------------------------------------------------------
// Custom properties (scoped to form elements, not :root)
// ---------------------------------------------------------------------------
.theabd--form-control,
.theabd--btn {
	--theabd-input-padding: 0.625rem 0.75rem;
	--theabd-input-radius: 6px;
	--theabd-focus-ring-width: 3px;
	--theabd-focus-ring-opacity: 0.15;
	--theabd-transition-duration: 0.2s;
	--theabd-btn-radius: 6px;
}

// ---------------------------------------------------------------------------
// .theabd--form-control (inputs, selects, textareas)
// ---------------------------------------------------------------------------
.theabd--form-control {
	@include theabd-form-control-styles;
}

// ---------------------------------------------------------------------------
// .theabd--btn (layered on wp-element-button)
// No colors — inherited from wp-element-button / theme.
// ---------------------------------------------------------------------------
.theabd--btn {
	display: inline-flex;
	align-items: center;
	justify-content: center;
	gap: 0.5rem;
	border: none;
	border-radius: var(--theabd-btn-radius);
	cursor: pointer;
	transition:
		opacity var(--theabd-transition-duration) ease,
		box-shadow var(--theabd-transition-duration) ease;
	text-decoration: none;
	white-space: nowrap;
	line-height: 1.5;

	// Size variants.
	&.theabd--btn-small {
		padding: 0.375rem 1rem;
		font-size: 0.8125rem;
	}

	&.theabd--btn-medium {
		padding: 0.625rem 1.25rem;
		font-size: 0.875rem;
	}

	&.theabd--btn-large {
		padding: 0.75rem 1.75rem;
		font-size: 1rem;
	}

	&:disabled {
		opacity: 0.6;
		cursor: not-allowed;
	}

	.theabd--btn-text {
		display: inline-block;
		line-height: 1;
	}
}
```

- [ ] **Step 2: Commit**

```bash
git add blocks/_shared-forms.scss
git commit -m "feat(styles): add shared forms SCSS partial with form-control and btn classes"
```

---

### Task 2: Import shared partial in build entry

**Files:**
- Modify: `src/blocks.js:1-11`

- [ ] **Step 1: Add import at top of `src/blocks.js`**

Add this line after the file header comment (line 9), before the first block import (line 12):

```js
// Shared form element styles (imported once to avoid CSS duplication).
import '../blocks/_shared-forms.scss';

```

So lines 10-13 become:

```js
// Shared form element styles (imported once to avoid CSS duplication).
import '../blocks/_shared-forms.scss';

// Import all block editor components (relative to src/).
```

- [ ] **Step 2: Build to verify no errors**

Run: `npm run build`
Expected: Build completes without errors. `dist/style-blocks.css` contains `.theabd--form-control` and `.theabd--btn` rules exactly once.

- [ ] **Step 3: Commit**

```bash
git add src/blocks.js
git commit -m "feat(styles): import shared forms partial in build entry point"
```

---

## Chunk 2: vendor-search Block Updates

### Task 3: Update vendor-search render.php — button classes

**Files:**
- Modify: `blocks/vendor-search/render.php:66-85`

- [ ] **Step 1: Replace button class construction (lines 66-85)**

Replace this block:

```php
	// Generate button classes and styles (reusable for all buttons).
	$button_classes = array( 'theabd--btn', 'theabd--btn-theme' );
	if ( ! empty( $button_size ) && 'medium' !== $button_size ) {
		$button_classes[] = 'dokan-btn-' . esc_attr( $button_size );
	}

	$button_style = match ( $button_size ) {
		'small' => 'padding: 0.375rem 1rem; font-size: 0.875rem;',
		'large' => 'padding: 0.75rem 2rem; font-size: 1.125rem;',
		default => 'padding: 0.5rem 1.5rem; font-size: 1rem;',
	};
	// Add button colors.
	if ( ! empty( $button_bg_color ) ) {
		$button_style .= ' background-color: ' . esc_attr( $button_bg_color ) . ';';
	}
	if ( ! empty( $button_text_color ) ) {
		$button_style .= ' color: ' . esc_attr( $button_text_color ) . ';';
	}

	$button_class_string = implode( ' ', $button_classes );
	$button_style_string = ! empty( $button_style ) ? trim( $button_style ) : '';
```

With:

```php
	// Generate button classes (colors from wp-element-button / theme).
	$button_classes = array( 'wp-element-button', 'theabd--btn' );
	if ( ! empty( $button_size ) ) {
		$button_classes[] = 'theabd--btn-' . esc_attr( $button_size );
	}

	// User-chosen color overrides (inline styles take priority over theme).
	$button_style = '';
	if ( ! empty( $button_bg_color ) ) {
		$button_style .= 'background-color: ' . esc_attr( $button_bg_color ) . ';';
	}
	if ( ! empty( $button_text_color ) ) {
		$button_style .= ' color: ' . esc_attr( $button_text_color ) . ';';
	}

	$button_class_string = implode( ' ', $button_classes );
	$button_style_string = ! empty( $button_style ) ? trim( $button_style ) : '';
```

- [ ] **Step 2: Commit**

```bash
git add blocks/vendor-search/render.php
git commit -m "fix(vendor-search): use wp-element-button and CSS size variants for buttons"
```

---

### Task 4: Update vendor-search render.php — form control classes

**Files:**
- Modify: `blocks/vendor-search/render.php:144,178-183,234,264`

- [ ] **Step 1: Add `theabd--form-control` to sort-by select (line 144)**

```php
// Before:
<select name="stores_orderby" id="stores_orderby" aria-label="<?php echo esc_attr( $sort_by_label ); ?>">

// After:
<select class="theabd--form-control" name="stores_orderby" id="stores_orderby" aria-label="<?php echo esc_attr( $sort_by_label ); ?>">
```

- [ ] **Step 2: Add `theabd--form-control` to search input (line 180)**

```php
// Before:
class="theabd--vendor-search-input theabd--vendor-search-input"

// After:
class="theabd--form-control theabd--vendor-search-input"
```

Note: also fixing the duplicate `theabd--vendor-search-input` class.

- [ ] **Step 3: Add `theabd--form-control` to location filter select (line 234)**

```php
// Before:
<select name="dokan_store_location" class="theabd--store-filter-select">

// After:
<select name="dokan_store_location" class="theabd--form-control theabd--store-filter-select">
```

- [ ] **Step 4: Add `theabd--form-control` to rating filter select (line 264)**

```php
// Before:
<select name="dokan_store_rating" class="theabd--store-filter-select">

// After:
<select name="dokan_store_rating" class="theabd--form-control theabd--store-filter-select">
```

- [ ] **Step 5: Add `theabd--form-control` to category dropdown (line 286)**

The `wp_dropdown_categories` call uses `'class' => 'theabd--store-filter-select'`. Update to:

```php
'class' => 'theabd--form-control theabd--store-filter-select',
```

- [ ] **Step 6: Commit**

```bash
git add blocks/vendor-search/render.php
git commit -m "fix(vendor-search): add theabd--form-control class to all form inputs"
```

---

### Task 5: Update vendor-search style.scss — remove hardcoded form styles

**Files:**
- Modify: `blocks/vendor-search/style.scss`

- [ ] **Step 1: Remove hardcoded select styles from sort-by (lines 97-117)**

Replace lines 97-117:

```scss
					select {
						padding: 0.5rem 0.75rem;
						border: 1px solid #e0e0e0;
						border-radius: 6px;
						font-size: 0.875rem;
						min-width: 150px;
						background: #fff;
						color: #333;
						cursor: pointer;
						transition: border-color 0.2s ease, box-shadow 0.2s ease;

						&:hover {
							border-color: #0073aa;
						}

						&:focus {
							outline: none;
							border-color: #0073aa;
							box-shadow: 0 0 0 3px rgba(0, 115, 170, 0.1);
						}
					}
```

With:

```scss
					select {
						min-width: 150px;
						background: #fff;
					}
```

Only keep layout-relevant properties. Colors, padding, border, focus handled by `.theabd--form-control`.

- [ ] **Step 2: Remove hardcoded search input styles (lines 173-191)**

Replace lines 173-191:

```scss
				.theabd--vendor-search-input {
					width: 100%;
					padding: 0.75rem 1rem;
					border: 1px solid #e0e0e0;
					border-radius: 6px;
					font-size: 1rem;
					line-height: 1.5;
					transition: border-color 0.2s ease, box-shadow 0.2s ease;

					&:focus {
						outline: none;
						border-color: #0073aa;
						box-shadow: 0 0 0 3px rgba(0, 115, 170, 0.1);
					}

					&::placeholder {
						color: #999;
					}
				}
```

With:

```scss
				.theabd--vendor-search-input {
					width: 100%;

					&::placeholder {
						color: #999;
					}
				}
```

- [ ] **Step 3: Remove hardcoded advanced filter select styles (lines 222-243)**

Replace lines 222-243:

```scss
				.theabd--store-filter-select {
					width: 100%;
					padding: 0.625rem 0.75rem;
					border: 1px solid #e0e0e0;
					border-radius: 6px;
					font-size: 0.875rem;
					background: #fff;
					color: #333;
					cursor: pointer;
					transition: border-color 0.2s ease, box-shadow 0.2s ease;

					&:hover {
						border-color: #0073aa;
					}

					&:focus {
						outline: none;
						border-color: #0073aa;
						box-shadow: 0 0 0 3px rgba(0, 115, 170, 0.1);
					}
				}
```

With:

```scss
				.theabd--store-filter-select {
					width: 100%;
					background: #fff;
				}
```

- [ ] **Step 4: Remove entire `.theabd--btn` block (lines 248-307)**

Delete lines 248-307 (the entire `.theabd--btn { ... }` block including all size variants, `.theabd--btn-theme`, and disabled state). These are now in `_shared-forms.scss`.

Also remove the empty `.theabd--vendor-query-loop-filter-button` rule at lines 305-307.

- [ ] **Step 5: Commit**

```bash
git add blocks/vendor-search/style.scss
git commit -m "fix(vendor-search): remove hardcoded form element styles, use shared partial"
```

---

### Task 6: Update vendor-search editor.scss — remove dead btn-theme

**Files:**
- Modify: `blocks/vendor-search/editor.scss:221-228`

- [ ] **Step 1: Remove `.theabd--btn-theme` block (lines 221-228)**

Delete:

```scss
		&.theabd--btn-theme {
			background-color: #0073aa;
			color: #fff;

			&:hover:not(:disabled) {
				background-color: #005a87;
			}
		}
```

This is dead code after `.theabd--btn-theme` class is removed from the HTML.

- [ ] **Step 2: Build to verify**

Run: `npm run build`
Expected: No errors.

- [ ] **Step 3: Commit**

```bash
git add blocks/vendor-search/editor.scss
git commit -m "fix(vendor-search): remove dead btn-theme editor styles"
```

---

## Chunk 3: Other Block Render.php Updates

### Task 7: Update become-vendor-cta render.php + index.js

**Files:**
- Modify: `blocks/become-vendor-cta/render.php:60`
- Modify: `blocks/become-vendor-cta/index.js:88`

- [ ] **Step 1: Update render.php (line 60)**

```php
// Before:
<a href="<?php echo esc_url( $button_link ); ?>" class="theabd--btn theabd--btn-primary">

// After:
<a href="<?php echo esc_url( $button_link ); ?>" class="wp-element-button theabd--btn">
```

- [ ] **Step 2: Update index.js (line 88)**

```jsx
// Before:
className="theabd--btn theabd--btn-primary"

// After:
className="wp-element-button theabd--btn"
```

- [ ] **Step 3: Commit**

```bash
git add blocks/become-vendor-cta/render.php blocks/become-vendor-cta/index.js
git commit -m "fix(become-vendor-cta): use wp-element-button for theme color integration"
```

---

### Task 8: Update more-from-seller render.php

**Files:**
- Modify: `blocks/more-from-seller/render.php:124`

- [ ] **Step 1: Update button class (line 124)**

```php
// Before:
<a href="<?php echo esc_url( $vendor_data['shop_url'] ); ?>" class="theabd--btn theabd--btn-theme">

// After:
<a href="<?php echo esc_url( $vendor_data['shop_url'] ); ?>" class="wp-element-button theabd--btn">
```

- [ ] **Step 2: Commit**

```bash
git add blocks/more-from-seller/render.php
git commit -m "fix(more-from-seller): use wp-element-button for theme color integration"
```

---

## Chunk 4: Contact Form + Vendor Card + Pagination SCSS

### Task 9: Update vendor-contact-form style.scss

**Files:**
- Modify: `blocks/vendor-contact-form/style.scss`

**Important:** The `@mixin theabd-form-control-styles` from `_shared-forms.scss` is NOT available here because each block's `style.scss` is compiled in its own SCSS context by `@wordpress/scripts`. We write the styles as plain CSS that matches the shared partial's output. This is intentional duplication — the contact form can't add classes to Dokan's template HTML.

- [ ] **Step 1: Replace hardcoded form element styles (lines 22-83)**

Replace lines 22-83:

```scss
	// Input and textarea styling
	.theabd--form-control,
	input[type="text"],
	input[type="email"],
	textarea {
		width: 100%;
		padding: 8px 12px;
		border: 1px solid #ddd;
		border-radius: 4px;
		font-size: 14px;
		line-height: 1.5;
		box-sizing: border-box;

		&:focus {
			border-color: #f05025;
			outline: none;
			box-shadow: 0 0 0 1px #f05025;
		}
	}

	/* stylelint-disable-next-line no-descending-specificity */
	textarea.theabd--textarea,
	/* stylelint-disable-next-line no-descending-specificity */
	textarea {
		resize: vertical;
		min-height: 120px;
	}

	// Privacy policy text
	.theabd--privacy-policy-text {
		margin: 1em 0;
		padding: 10px;
		background-color: #f9f9f9;
		border-radius: 4px;
		font-size: 13px;
		color: #666;
	}

	// Submit button
	.theabd--btn {
		display: inline-block;
		padding: 10px 20px;
		font-size: 14px;
		font-weight: 500;
		line-height: 1.5;
		text-align: center;
		white-space: nowrap;
		vertical-align: middle;
		cursor: pointer;
		border: none;
		border-radius: 4px;
		transition: background-color 0.15s ease-in-out;
	}

	.theabd--btn-theme {
		color: #fff;
		background-color: #f05025;

		&:hover {
			background-color: #d9451d;
		}
	}
```

With:

```scss
	// Input and textarea styling.
	// Mirrors _shared-forms.scss form-control styles (can't use @include here
	// because each block's style.scss compiles in its own SCSS context).
	input[type="text"],
	input[type="email"],
	textarea {
		width: 100%;
		padding: 0.625rem 0.75rem;
		border: 1px solid rgba(0, 0, 0, 0.25);
		border-radius: 6px;
		line-height: 1.5;
		box-sizing: border-box;
		transition: border-color 0.2s ease, box-shadow 0.2s ease;

		@supports (color: color-mix(in srgb, red, blue)) {
			border-color: color-mix(in srgb, currentColor 25%, transparent);
		}

		&:focus {
			outline: none;
			box-shadow: 0 0 0 3px rgba(0, 0, 0, 0.15);

			@supports (color: color-mix(in srgb, red, blue)) {
				box-shadow: 0 0 0 3px color-mix(in srgb, currentColor 15%, transparent);
			}
		}

		&:hover:not(:focus):not(:disabled) {
			border-color: rgba(0, 0, 0, 0.4);

			@supports (color: color-mix(in srgb, red, blue)) {
				border-color: color-mix(in srgb, currentColor 40%, transparent);
			}
		}
	}

	/* stylelint-disable-next-line no-descending-specificity */
	textarea {
		resize: vertical;
		min-height: 120px;
	}

	// Privacy policy text.
	.theabd--privacy-policy-text {
		margin: 1em 0;
		padding: 10px;
		background-color: #f9f9f9;
		border-radius: 4px;
		font-size: 13px;
		color: #666;
	}
```

Note: The submit button in Dokan's template gets styled via the theme defaults. We remove the local `.theabd--btn` and `.theabd--btn-theme` definitions since this block doesn't control the button HTML.

- [ ] **Step 2: Commit**

```bash
git add blocks/vendor-contact-form/style.scss
git commit -m "fix(contact-form): use shared form-control mixin, remove hardcoded colors"
```

---

### Task 10: Update vendor-card style.scss — remove footer button colors

**Files:**
- Modify: `blocks/vendor-card/style.scss:219-244`

**Note:** The vendor-card uses InnerBlocks — its `render.php` does NOT generate the footer button. The `.theabd--btn-theme` selector in the SCSS targets content composed by users via the block editor. No render.php change needed. We only update the SCSS to remove hardcoded colors and add `.wp-element-button` as an additional selector for forward compatibility.

- [ ] **Step 1: Replace `.theabd--btn-theme` footer styles (lines 219-244)**

Replace lines 219-244:

```scss
		.theabd--btn-theme {
			display: flex;
			align-items: center;
			justify-content: center;
			width: 40px;
			height: 40px;
			padding: 0;
			background: #0073aa;
			color: #fff;
			border-radius: 50%;
			text-decoration: none;
			transition: background-color 0.2s ease, transform 0.2s ease;

			&:hover {
				background: #005a87;
				transform: scale(1.1);
			}

			.dashicons {
				font-size: 20px;
				width: 20px;
				height: 20px;
				line-height: 1;
			}
		}
```

With:

```scss
		.theabd--btn-theme,
		.wp-element-button {
			display: flex;
			align-items: center;
			justify-content: center;
			width: 40px;
			height: 40px;
			padding: 0;
			border-radius: 50%;
			text-decoration: none;
			transition: transform 0.2s ease;

			&:hover {
				transform: scale(1.1);
			}

			.dashicons {
				font-size: 20px;
				width: 20px;
				height: 20px;
				line-height: 1;
			}
		}
```

Note: We keep `.theabd--btn-theme` selector temporarily alongside `.wp-element-button` for backward compatibility with any saved block content. Colors are removed — `wp-element-button` provides them from the theme.

- [ ] **Step 2: Commit**

```bash
git add blocks/vendor-card/style.scss
git commit -m "fix(vendor-card): remove hardcoded footer button colors, use theme colors"
```

---

### Task 11: Update vendor-query-loop pagination colors

**Files:**
- Modify: `blocks/vendor-query-loop/style.scss:104-135`

**Important:** WordPress's `paginate_links()` outputs standard classes: `.page-numbers` on all links/spans and `.current` on the active page. The existing SCSS uses `.theabd--page-numbers` (line 90) and `.theabd--current` (line 126) which are custom class wrappers in the SCSS nesting. The `a, span` selector at line 104 and `.theabd--current` at line 126 are both nested inside `.theabd--page-numbers` — these match because `.theabd--page-numbers` is the class on the wrapping `<nav>` or list element in the rendered HTML, not on individual pagination items.

- [ ] **Step 1: Replace hardcoded pagination colors (lines 104-135)**

Replace lines 104-135:

```scss
			a,
			span {
				display: inline-flex;
				align-items: center;
				justify-content: center;
				min-width: 2.5rem;
				height: 2.5rem;
				padding: 0.5rem 0.75rem;
				border: 1px solid #e0e0e0;
				border-radius: 4px;
				text-decoration: none;
				color: #333;
				background: #fff;
				transition: all 0.2s ease;

				&:hover {
					border-color: #0073aa;
					color: #0073aa;
					background: #f0f7fa;
				}
			}

			.theabd--current {
				border-color: #0073aa;
				color: #fff;
				background: #0073aa;

				&:hover {
					background: #005a87;
					border-color: #005a87;
				}
			}
```

With:

```scss
			a,
			span {
				display: inline-flex;
				align-items: center;
				justify-content: center;
				min-width: 2.5rem;
				height: 2.5rem;
				padding: 0.5rem 0.75rem;
				border: 1px solid rgba(0, 0, 0, 0.15);
				border-radius: 4px;
				text-decoration: none;
				background: #fff;
				transition: all 0.2s ease;

				@supports (color: color-mix(in srgb, red, blue)) {
					border-color: color-mix(in srgb, currentColor 15%, transparent);
				}

				&:hover {
					border-color: rgba(0, 0, 0, 0.3);
					background: rgba(0, 0, 0, 0.04);

					@supports (color: color-mix(in srgb, red, blue)) {
						border-color: color-mix(in srgb, currentColor 30%, transparent);
						background: color-mix(in srgb, currentColor 4%, transparent);
					}
				}
			}

			// WordPress paginate_links() outputs .current on active page span.
			// Also keep .theabd--current for backward compat.
			.current,
			.theabd--current {
				// Use wp-element-button-like styling: theme's accent as bg.
				// Since we can't use @extend across SCSS contexts, apply
				// a neutral inverted style that works with any theme.
				color: #fff;
				background: #333;
				border-color: #333;

				@supports (color: color-mix(in srgb, red, blue)) {
					background: color-mix(in srgb, currentColor 85%, transparent);
					border-color: color-mix(in srgb, currentColor 85%, transparent);
				}

				&:hover {
					background: #111;
					border-color: #111;

					@supports (color: color-mix(in srgb, red, blue)) {
						background: currentColor;
						border-color: currentColor;
					}
				}
			}
```

Note: The `.current` page uses `currentColor` at high opacity for its background, creating a dark fill that adapts to the theme's text color. This is not as theme-integrated as `wp-element-button` but avoids the `@extend` cross-context issue and works without modifying the `paginate_links()` HTML output.

- [ ] **Step 2: Commit**

```bash
git add blocks/vendor-query-loop/style.scss
git commit -m "fix(query-loop): remove hardcoded pagination colors, use theme-adaptive styles"
```

---

## Chunk 5: Build + Visual Verification

### Task 12: Final build and verification

**Files:**
- None (verification only)

- [ ] **Step 1: Run full build**

Run: `npm run build`
Expected: No errors or warnings.

- [ ] **Step 2: Verify no CSS duplication**

Run: `grep -c 'theabd--form-control' dist/style-blocks.css`
Expected: The `.theabd--form-control` selector definition appears exactly once (the mixin `@include` outputs will be separate declarations scoped to their blocks, which is expected).

- [ ] **Step 3: Verify wp-element-button class presence**

Run: `grep -r 'wp-element-button' blocks/ --include='*.php' --include='*.js' -l`
Expected: Files listed: `vendor-search/render.php`, `become-vendor-cta/render.php`, `become-vendor-cta/index.js`, `more-from-seller/render.php`

- [ ] **Step 4: Verify no remaining hardcoded btn-theme in render files**

Run: `grep -r 'theabd--btn-theme' blocks/ --include='*.php' --include='*.js'`
Expected: No matches in render.php or index.js files. (SCSS files may still have it for backward compat in vendor-card.)

- [ ] **Step 5: Run linter**

Run: `composer lint`
Expected: No new violations from our changes.

- [ ] **Step 6: Commit build artifacts if needed**

If `dist/` is committed (check `.gitignore`):
```bash
npm run build
git add dist/
git commit -m "chore: rebuild assets with unified form element styles"
```
