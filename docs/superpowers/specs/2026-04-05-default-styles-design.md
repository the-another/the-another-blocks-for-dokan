# Unified Form Element Default Styles

## Problem

Form elements (inputs, buttons, selects) are styled independently in each block with inconsistent values:

- **vendor-search**: `#0073aa` blue, `6px` radius, `0.75rem 1rem` padding, `3px` focus ring
- **vendor-contact-form**: `#f05025` orange, `4px` radius, `8px 12px` padding, `1px` focus ring
- **vendor-card**: `#0073aa` blue hardcoded on footer button + avatar hover + store name hover
- **more-from-seller**: `theabd--btn-theme` with hardcoded colors (no own style.scss)
- **become-vendor-cta**: uses `theabd--btn-primary` (undefined in CSS)
- **vendor-query-loop**: pagination links use hardcoded `#0073aa`/`#005a87`

This creates visual inconsistency and fights theme styles.

## Solution

Two-layer approach:

1. **Base layer**: Add WordPress core element classes (`wp-element-button`) to buttons so themes handle colors, typography, and border-radius natively.
2. **Custom layer**: A shared SCSS partial (`blocks/_shared-forms.scss`) that adds what themes don't: consistent spacing, transitions, focus rings, size variants, and disabled states. No hardcoded colors. Uses CSS custom properties for overridability.

## Architecture

### New file: `blocks/_shared-forms.scss`

Imported **once** via `src/blocks.js` (`import '../blocks/_shared-forms.scss';`) to avoid CSS duplication in the compiled bundle. Individual block `style.scss` files do NOT import this partial — they rely on the shared styles being present in the same bundle.

#### CSS Custom Properties

Scoped to the form element selectors (not `:root`) to avoid polluting the global namespace:

```scss
.theabd--form-control,
.theabd--btn {
  --theabd-input-padding: 0.625rem 0.75rem;
  --theabd-input-radius: 6px;
  --theabd-focus-ring-width: 3px;
  --theabd-focus-ring-opacity: 0.15;
  --theabd-transition-duration: 0.2s;
  --theabd-btn-radius: 6px;
}
```

#### `.theabd--form-control` (inputs, selects, textareas)

- `padding: var(--theabd-input-padding)`
- `border: 1px solid color-mix(in srgb, currentColor 25%, transparent)` (theme text color at low opacity)
- `border-radius: var(--theabd-input-radius)`
- `transition: border-color var(--theabd-transition-duration) ease, box-shadow var(--theabd-transition-duration) ease`
- Focus: `outline: none; box-shadow: 0 0 0 var(--theabd-focus-ring-width) color-mix(in srgb, currentColor calc(var(--theabd-focus-ring-opacity) * 100%), transparent)`
- No hardcoded colors, no font-size (theme handles these)

For the border, `color-mix(in srgb, currentColor 25%, transparent)` gives a subtle border that adapts to whatever text color the theme sets.

#### `.theabd--btn` (layered on `wp-element-button`)

- `display: inline-flex; align-items: center; justify-content: center; gap: 0.5rem`
- `border: none; border-radius: var(--theabd-btn-radius)`
- `cursor: pointer; transition: opacity var(--theabd-transition-duration) ease, box-shadow var(--theabd-transition-duration) ease`
- `text-decoration: none; white-space: nowrap`
- No colors (inherited from `wp-element-button` / theme)
- Size variants:
  - `.theabd--btn-small`: `padding: 0.375rem 1rem; font-size: 0.8125rem`
  - `.theabd--btn-medium`: `padding: 0.625rem 1.25rem; font-size: 0.875rem`
  - `.theabd--btn-large`: `padding: 0.75rem 1.75rem; font-size: 1rem`
- `&:disabled { opacity: 0.6; cursor: not-allowed; }`
- Hover: light `box-shadow` only (no `transform: translateY` to avoid layout shifts in tight button rows)

### Render.php Changes

#### vendor-search (`render.php`)

**Buttons**: Add `wp-element-button` class alongside `theabd--btn`:
```php
// Before:
$button_classes = array( 'theabd--btn', 'theabd--btn-theme' );

// After:
$button_classes = array( 'wp-element-button', 'theabd--btn' );
```

Remove `theabd--btn-theme` — colors now come from the theme via `wp-element-button`.

Remove inline `$button_style` match expression for padding/font-size — size variants handled by CSS classes:
```php
// Before: match expression with inline padding styles
// After:
if ( ! empty( $button_size ) ) {
    $button_classes[] = 'theabd--btn-' . $button_size;
}
```

Keep `buttonBackgroundColor`/`buttonTextColor` attribute overrides as inline styles (user-chosen colors take priority).

**Inputs**: Add `theabd--form-control` class:
```html
<!-- Before -->
<input type="search" class="theabd--vendor-search-input" ... />

<!-- After -->
<input type="search" class="theabd--form-control theabd--vendor-search-input" ... />
```

**Selects**: Add `theabd--form-control` class to all selects:
```html
<!-- Before -->
<select class="theabd--store-filter-select">
<select name="stores_orderby"> <!-- sort-by, no class -->

<!-- After -->
<select class="theabd--form-control theabd--store-filter-select">
<select class="theabd--form-control" name="stores_orderby">
```

#### vendor-contact-form (`render.php`)

This block uses `dokan_get_template_part()` — we can't modify Dokan's template HTML. Instead, the `style.scss` will use a `@mixin` from the shared partial to apply form-control styles to element selectors within `.theabd--vendor-contact-form`. No render.php changes needed.

#### become-vendor-cta (`render.php` + `index.js`)

Both files need updating:

**render.php**:
```php
// Before:
<a href="..." class="theabd--btn theabd--btn-primary">

// After:
<a href="..." class="wp-element-button theabd--btn">
```

**index.js** (line 88):
```jsx
// Before:
className="theabd--btn theabd--btn-primary"

// After:
className="wp-element-button theabd--btn"
```

Remove `theabd--btn-primary` (undefined class, colors now from theme).

#### more-from-seller (`render.php`)

```php
// Before (line 124):
<a href="..." class="theabd--btn theabd--btn-theme">

// After:
<a href="..." class="wp-element-button theabd--btn">
```

This block has no `style.scss` — it relied on `.theabd--btn-theme` from the vendor-search bundle. With `wp-element-button`, it gets theme colors natively.

#### vendor-card (`render.php` + `style.scss`)

The footer button is a circular icon button (`width: 40px; height: 40px; border-radius: 50%`). Add `wp-element-button` for theme color integration:

```php
// Before:
class="theabd--btn-theme"

// After:
class="wp-element-button theabd--vendor-card-footer-btn"
```

The `style.scss` will define `.theabd--vendor-card-footer-btn` for the circular shape/sizing, inheriting colors from `wp-element-button`.

**Note**: The avatar hover `border-color: #0073aa` (line 142) and store name hover `color: #0073aa` (line 173) are intentionally **out of scope** — they're not form elements and are better addressed in a separate "theme color integration" pass. Documenting here for awareness.

### SCSS Changes Per Block

#### `blocks/vendor-search/style.scss`

- Remove the entire `.theabd--btn` block (lines 248-307) — now in shared partial
- Remove hardcoded input styles from `.theabd--vendor-search-input` (lines 173-191) — now uses `.theabd--form-control`
- Remove hardcoded select styles (lines 97-117, 222-243) — now uses `.theabd--form-control`
- Keep layout/positioning rules (flex, gap, grid structure)
- Keep the `::placeholder` color rule

#### `blocks/vendor-search/editor.scss`

- Remove `.theabd--btn-theme` block (lines 221-228) with hardcoded `#0073aa`/`#005a87` — dead code after `.theabd--btn-theme` class is removed from HTML
- Keep other editor preview styles

#### `blocks/vendor-contact-form/style.scss`

- Use a `@mixin theabd-form-control-styles` from the shared partial to apply styles to Dokan's template elements:
  ```scss
  .theabd--vendor-contact-form {
    input[type="text"],
    input[type="email"],
    textarea {
      @include theabd-form-control-styles;
    }

    .dokan-btn-theme,
    input[type="submit"] {
      @extend .wp-element-button; // or apply via mixin
    }
  }
  ```
- Remove `#f05025` and `#d9451d` color references entirely
- Keep `.theabd--form-group` margin, textarea min-height, privacy/alert styles

#### `blocks/vendor-card/style.scss`

- Replace `.theabd--btn-theme` in store footer (lines 219-241) with `.theabd--vendor-card-footer-btn`:
  - Keep circular shape styles (`width: 40px; height: 40px; border-radius: 50%; padding: 0`)
  - Remove hardcoded `background: #0073aa` and `background: #005a87` — inherited from `wp-element-button`
  - Keep hover `transform: scale(1.1)` (appropriate for isolated circular button)

#### `blocks/vendor-query-loop/style.scss`

- Replace hardcoded `#0073aa`/`#005a87` in pagination links (lines 120-135) with `currentColor` or theme CSS variables:
  ```scss
  .theabd--pagination-link {
    &:hover {
      border-color: currentColor;
      color: inherit;
    }
  }
  .theabd--current {
    border-color: currentColor;
    background: currentColor;
    color: #fff; // inverted text on currentColor background
  }
  ```
  Note: pagination using `currentColor` for both background and text won't work directly. Better approach: use `wp-element-button` class on the current page indicator, or use `color-mix()` for hover states. Final CSS to be determined during implementation.

#### `src/blocks.js`

Add shared forms import (once, at the top):
```js
// Shared form element styles.
import '../blocks/_shared-forms.scss';

// Import all block editor components...
import '../blocks/vendor-store-header/index.js';
// ...
```

### What Gets Removed

| Item | Location | Reason |
|------|----------|--------|
| `#0073aa`, `#005a87` | vendor-search `style.scss` + `editor.scss` | Theme handles button colors |
| `#f05025`, `#d9451d` | vendor-contact-form `style.scss` | Theme handles button/focus colors |
| `#0073aa`, `#005a87` | vendor-card `style.scss` (footer btn only) | Theme handles button colors |
| `#0073aa`, `#005a87` | vendor-query-loop `style.scss` (pagination) | Theme handles interactive colors |
| `.theabd--btn-theme` class | All render.php + SCSS | Replaced by `wp-element-button` |
| `.theabd--btn-primary` class | become-vendor-cta `render.php` + `index.js` | Replaced by `wp-element-button` |
| `dokan-btn-*` size classes | vendor-search `render.php` | Replaced by `theabd--btn-*` size classes |
| Inline button padding/font-size | vendor-search `render.php` | Handled by size variant CSS classes |
| Duplicate input/select/button style blocks | Per-block SCSS files | Consolidated into `_shared-forms.scss` |

### What Stays Block-Specific

- Layout rules (flex, grid, positioning)
- Vendor-search filter panel structure (caret arrow, box-shadow, collapse behavior)
- Vendor-card hover transform and card structure
- Vendor-card avatar/store-name hover colors (out of scope — not form elements)
- Contact form `.theabd--form-group` margin, textarea min-height, alert styles

### What Needs Coordinated Editor Updates

- `blocks/vendor-search/editor.scss` — remove dead `.theabd--btn-theme` block
- `blocks/become-vendor-cta/index.js` — update button className

## `color-mix()` Browser Support

`color-mix(in srgb, ...)` has 94%+ global support (Chrome 111+, Firefox 113+, Safari 16.2+). For the border and focus ring, we use a simple fallback:

```scss
// Fallback
border: 1px solid rgba(0, 0, 0, 0.25);
box-shadow: 0 0 0 var(--theabd-focus-ring-width) rgba(0, 0, 0, var(--theabd-focus-ring-opacity));

// Modern
@supports (color: color-mix(in srgb, red, blue)) {
  border-color: color-mix(in srgb, currentColor 25%, transparent);
  &:focus {
    box-shadow: 0 0 0 var(--theabd-focus-ring-width) color-mix(in srgb, currentColor calc(var(--theabd-focus-ring-opacity) * 100%), transparent);
  }
}
```

Given WP 6.0+ requirement and typical WordPress user browser profiles, the fallback is a safety net at minimal cost.

## Testing

- Visual check: buttons, inputs, selects look consistent across all affected blocks (vendor-search, contact-form, become-vendor-cta, more-from-seller, vendor-card footer)
- Theme compatibility: test with Twenty Twenty-Four (block theme) — elements should inherit theme colors
- Custom properties: verify overriding `--theabd-btn-radius` in theme CSS changes button radius
- Button color attributes: verify `buttonBackgroundColor`/`buttonTextColor` in vendor-search still work as inline overrides
- Focus states: tab through all form elements, verify consistent focus ring
- Size variants: test `small`, `medium`, `large` button sizes in vendor-search block settings
- Pagination: verify vendor-query-loop pagination colors adapt to theme
- Editor preview: verify become-vendor-cta and vendor-search editor views match frontend
- CSS bundle: verify `_shared-forms.scss` styles appear only once in compiled `dist/style-blocks.css`
- RTL: verify styles work in RTL mode
