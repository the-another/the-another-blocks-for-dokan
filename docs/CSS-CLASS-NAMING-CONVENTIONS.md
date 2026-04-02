# CSS Class Naming Conventions

This document outlines the CSS class naming conventions used in Another Blocks for Dokan, following WordPress standards and query loop patterns.

## WordPress Official Standards

### BEM-Inspired Methodology

WordPress Gutenberg follows naming guidelines loosely inspired by the [BEM (Block, Element, Modifier) methodology](https://en.bem.info/methodology/) to prevent class name collisions and improve maintainability.

### Prefix Requirements

**Root Block Element:**
```css
.package-directory { }
```

**Child Elements (use double underscore):**
```css
.package-directory__descriptor-foo-bar { }
```

**State Modifiers (use is- or has- prefix):**
```css
.package-directory.is-active { }
.package-directory.has-background { }
```

### Key Principles

1. All class names must be prefixed with the package name
2. Descendant elements append descriptors after two consecutive underscores `__`
3. State modifiers use `is-` prefix for states (active, opened, selected)
4. Variations use `has-` prefix for features (has-background, has-border)
5. Use lowercase and separate words with hyphens
6. Component class names should remain isolated within their folders

---

## Another Blocks for Dokan Naming Patterns

> **Note:** The `theabd--` prefix (short for "The Another Blocks for Dokan") is used throughout this plugin to prevent conflicts with other plugins and ensure unique class names. This prefix follows the BEM methodology and provides a clear namespace for all plugin-specific styles.

### 1. WordPress Block Wrapper Classes

WordPress automatically generates these classes via `get_block_wrapper_attributes()`:

```css
.wp-block-{namespace}-{block-slug} { }
```

**Examples:**
- `.wp-block-the-another-blocks-for-dokan-vendor-store-header`
- `.wp-block-the-another-blocks-for-dokan-vendor-card`
- `.wp-block-the-another-blocks-for-dokan-vendor-store-name`

### 2. Custom Block Classes

All custom blocks use the `theabd--` prefix followed by the block type:

```css
.theabd--{block-type} { }
```

**Examples:**
```css
.theabd--vendor-store-header { }
.theabd--vendor-card { }
.theabd--vendor-avatar { }
.theabd--vendor-store-name { }
.theabd--vendor-rating { }
```

### 3. Nested Element Classes (BEM __element)

Child elements within a block use double underscore notation:

```css
.theabd--{block-type}__{element} { }
```

**Examples:**
```css
.theabd--vendor-avatar__image { }
.theabd--vendor-avatar__link { }
.theabd--vendor-store-header__banner { }
.theabd--vendor-store-header__info { }
.theabd--vendor-store-header__contact-info { }
```

### 4. Layout-Specific Classes

Query loop and layout containers use descriptive suffixes:

```css
/* Layout variants */
.theabd--store-list-{layout} { }           /* grid, list */
.theabd--store-list-columns-{n} { }        /* 1, 2, 3, 4, 5, 6 */

/* Wrappers */
.theabd--vendor-wrap { }                   /* Main list container */
.theabd--single-vendor { }                 /* Individual vendor wrapper */
.theabd--store-list-wrap { }               /* Grid wrapper */
```

**Examples:**
```css
.theabd--store-list-grid { }
.theabd--store-list-list { }
.theabd--store-list-columns-3 { }
```

### 5. State Modifiers (has- prefix)

Use `has-` prefix for feature variations:

```css
.theabd--{block-type}.has-{feature} { }
```

**Examples:**
```css
.theabd--vendor-card.has-banner-background { }
.theabd--vendor-store-name.has-text-align-center { }
.theabd--vendor-store-header.has-custom-background { }
```

### 6. State Classes (is- prefix)

Use `is-` prefix for dynamic states:

```css
.store-{status} { }
```

**Examples:**
```css
.store-open { }
.store-closed { }
```

### 7. Layout Variant Classes

Header and major blocks can have layout variants using suffix pattern:

```css
.theabd--{block-type}-{variant} { }
```

**Examples:**
```css
.theabd--vendor-store-header-default { }
.theabd--vendor-store-header-layout2 { }
.theabd--vendor-store-header-layout3 { }
```

### 8. Editor/Placeholder Classes

Editor-only classes for placeholders and empty states:

```css
.theabd--{block-type}-placeholder { }
```

**Examples:**
```css
.theabd--vendor-store-header-placeholder { }
.theabd--vendor-card-placeholder { }
.store-placeholder-notice { }
```

### 9. Component-Specific Classes

Social icons and other reusable components:

```css
.theabd--social-icon { }
.theabd--social-{platform} { }
```

**Examples:**
```css
.theabd--social-facebook { }
.theabd--social-twitter { }
.theabd--social-instagram { }
```

---

## Implementation Examples

### Vendor Store Header Block

```php
// render.php
$layout = $attributes['layout'] ?? 'default';
$wrapper_classes = array(
    'theabd--vendor-store-header',
    "theabd--vendor-store-header-{$layout}"
);

if ( ! empty( $attributes['backgroundColor'] ) ) {
    $wrapper_classes[] = 'has-background';
}

$wrapper_attributes = get_block_wrapper_attributes(
    array( 'class' => implode( ' ', $wrapper_classes ) )
);
```

**Generated HTML:**
```html
<div class="wp-block-the-another-blocks-for-dokan-vendor-store-header theabd--vendor-store-header theabd--vendor-store-header-default has-background">
    <div class="theabd--vendor-store-header__banner">...</div>
    <div class="theabd--vendor-store-header__info">
        <div class="theabd--vendor-store-header__contact-info">...</div>
    </div>
</div>
```

### Vendor Card Block (Query Loop)

```php
// render.php
$wrapper_classes = array( 'theabd--vendor-card' );

if ( $attributes['useBannerAsBackground'] ?? false ) {
    $wrapper_classes[] = 'has-banner-background';
}

$wrapper_attributes = get_block_wrapper_attributes(
    array( 'class' => implode( ' ', $wrapper_classes ) )
);
```

**Generated HTML:**
```html
<div class="wp-block-the-another-blocks-for-dokan-vendor-card theabd--vendor-card has-banner-background">
    <!-- InnerBlocks content -->
</div>
```

### Vendor Avatar Block

```php
// render.php
$wrapper_attributes = get_block_wrapper_attributes(
    array( 'class' => 'theabd--vendor-avatar' )
);
```

**Generated HTML:**
```html
<div class="wp-block-the-another-blocks-for-dokan-vendor-avatar theabd--vendor-avatar">
    <a href="..." class="theabd--vendor-avatar__link">
        <img class="theabd--vendor-avatar__image" src="..." alt="...">
    </a>
</div>
```

### Vendor Query Loop Block (Query Container)

```php
// render.php
$layout = $attributes['layout'] ?? 'grid';
$columns = $attributes['columns'] ?? 3;

$wrapper_classes = array(
    'theabd--vendor-wrap',
    "theabd--vendor-list-{$layout}",
    "theabd--vendor-list-columns-{$columns}"
);

$wrapper_attributes = get_block_wrapper_attributes(
    array( 'class' => implode( ' ', $wrapper_classes ) )
);
```

**Generated HTML:**
```html
<div class="wp-block-the-another-blocks-for-dokan-vendor-query-loop theabd--vendor-wrap theabd--vendor-list-grid theabd--vendor-list-columns-3">
    <div class="theabd--vendor-list-wrap">
        <div class="theabd--single-vendor">
            <!-- Vendor Card with InnerBlocks -->
        </div>
        <div class="theabd--single-vendor">
            <!-- Vendor Card with InnerBlocks -->
        </div>
    </div>
</div>
```

---

## Query Loop Pattern Classes

### Container Classes

The query loop container (Vendor Query Loop) provides these classes:

1. **WordPress wrapper:** `.wp-block-the-another-blocks-for-dokan-vendor-query-loop`
2. **Main container:** `.theabd--vendor-wrap`
3. **Layout variant:** `.theabd--vendor-list-{layout}` (grid or list)
4. **Column count:** `.theabd--vendor-list-columns-{n}` (1-6)
5. **Inner wrapper:** `.theabd--vendor-list-wrap`

### Item Classes

Each query loop item (Vendor Card) uses:

1. **Item wrapper:** `.theabd--single-vendor`
2. **Card wrapper:** `.wp-block-the-another-blocks-for-dokan-vendor-card`
3. **Custom class:** `.theabd--vendor-card`
4. **State modifiers:** `.has-banner-background`

### Field Block Classes

Field blocks inside the query loop follow the standard pattern:

```css
.wp-block-the-another-blocks-for-dokan-{field} { }  /* WordPress wrapper */
.theabd--{field} { }                                 /* Custom class */
.theabd--{field}__{element} { }                      /* Child elements */
```

---

## SCSS Organization

### File Structure

Each block has two stylesheet files:

**style.scss** - Frontend + Editor (loaded everywhere)
```scss
// Block wrapper classes
.wp-block-the-another-blocks-for-dokan-vendor-store-header {
    // Styles here
}

// Custom block classes
.theabd--vendor-store-header {
    // Base styles

    &__banner {
        // Child element
    }

    &.has-background {
        // State modifier
    }

    &-layout2 {
        // Layout variant
    }
}
```

**editor.scss** - Editor-only styles (placeholders, borders)
```scss
.wp-block-the-another-blocks-for-dokan-vendor-store-header {
    .theabd--vendor-store-header-placeholder {
        // Placeholder styles
    }
}
```

### Naming in SCSS

Use SCSS nesting to maintain BEM structure:

```scss
.theabd--vendor-avatar {
    // Block styles

    &__link {
        // Element: .theabd--vendor-avatar__link
    }

    &__image {
        // Element: .theabd--vendor-avatar__image
    }

    &.is-loading {
        // State: .theabd--vendor-avatar.is-loading
    }
}
```

---

## Grid/Layout CSS Classes

### Responsive Grid System

The Vendor Query Loop block uses a responsive grid system:

```scss
.theabd--vendor-list-wrap {
    display: grid;
    gap: 20px;

    // Column classes
    &.theabd--vendor-list-columns-1 { grid-template-columns: repeat(1, 1fr); }
    &.theabd--vendor-list-columns-2 { grid-template-columns: repeat(2, 1fr); }
    &.theabd--vendor-list-columns-3 { grid-template-columns: repeat(3, 1fr); }
    &.theabd--vendor-list-columns-4 { grid-template-columns: repeat(4, 1fr); }
    &.theabd--vendor-list-columns-5 { grid-template-columns: repeat(5, 1fr); }
    &.theabd--vendor-list-columns-6 { grid-template-columns: repeat(6, 1fr); }
}
```

### Responsive Breakpoints

```scss
// Mobile: max-width 600px
@media (max-width: 600px) {
    .theabd--vendor-list-columns-3,
    .theabd--vendor-list-columns-4,
    .theabd--vendor-list-columns-5,
    .theabd--vendor-list-columns-6 {
        grid-template-columns: repeat(1, 1fr);
    }
}

// Tablet: max-width 782px
@media (max-width: 782px) {
    .theabd--vendor-list-columns-4,
    .theabd--vendor-list-columns-5,
    .theabd--vendor-list-columns-6 {
        grid-template-columns: repeat(2, 1fr);
    }
}
```

---

## Checklist for New Blocks

When creating a new block, ensure you follow these naming conventions:

### PHP Render Function

- [ ] Use `get_block_wrapper_attributes()` for the main wrapper
- [ ] Add custom class: `theabd--{block-type}`
- [ ] Add layout variant if needed: `theabd--{block-type}-{variant}`
- [ ] Add state modifiers with `has-` prefix for features
- [ ] Use double underscore `__` for child elements
- [ ] Build class arrays and implode before passing to wrapper

```php
$wrapper_classes = array(
    'theabd--{block-type}',
    "theabd--{block-type}-{$variant}"
);

if ( $has_feature ) {
    $wrapper_classes[] = 'has-feature-name';
}

$wrapper_attributes = get_block_wrapper_attributes(
    array( 'class' => implode( ' ', $wrapper_classes ) )
);
```

### SCSS Files

- [ ] Create `style.scss` for frontend + editor styles
- [ ] Create `editor.scss` for editor-only styles
- [ ] Use `.wp-block-the-another-blocks-for-dokan-{slug}` as outer wrapper
- [ ] Use `.theabd--{block-type}` for custom styles
- [ ] Use `&__element` for child elements (BEM)
- [ ] Use `&.has-feature` for state modifiers
- [ ] Use `&-variant` for layout variants
- [ ] Add responsive media queries if needed

### block.json

- [ ] Set `name: "the-another/blocks-for-dokan-{block-type}"`
- [ ] Add `supports` for typography, color, spacing as needed
- [ ] Define `usesContext` for query loop field blocks
- [ ] Define `providesContext` for query loop containers

---

## Common Patterns Summary

| Pattern | Convention | Example |
|---------|-----------|---------|
| **WordPress wrapper** | `.wp-block-{namespace}-{slug}` | `.wp-block-the-another-blocks-for-dokan-vendor-store-header` |
| **Block base** | `.theabd--{block-type}` | `.theabd--vendor-card` |
| **Child element** | `.theabd--{block}__element` | `.theabd--vendor-avatar__image` |
| **State modifier** | `.has-{feature}` | `.has-banner-background` |
| **Status** | `.{status}` or `.is-{state}` | `.store-open`, `.is-active` |
| **Layout variant** | `.theabd--{block}-{variant}` | `.theabd--vendor-store-header-layout2` |
| **Layout type** | `.theabd--vendor-list-{layout}` | `.theabd--vendor-list-grid` |
| **Columns** | `.theabd--vendor-list-columns-{n}` | `.theabd--vendor-list-columns-3` |
| **Placeholder** | `.theabd--{block}-placeholder` | `.theabd--vendor-card-placeholder` |
| **Social** | `.theabd--social-{platform}` | `.theabd--social-facebook` |

---

## WordPress Support Classes

WordPress automatically adds these classes when block supports are enabled:

### Typography Support
```css
.has-text-align-left { }
.has-text-align-center { }
.has-text-align-right { }
.has-{size}-font-size { }
```

### Color Support
```css
.has-{color}-color { }
.has-{color}-background-color { }
```

### Spacing Support
```css
/* Applied via inline styles */
padding: var(--wp--preset--spacing--{value});
margin: var(--wp--preset--spacing--{value});
```

### Layout Support
```css
.is-layout-flex { }
.is-layout-grid { }
.is-layout-flow { }
```

---

## References

- [WordPress CSS Coding Standards](https://developer.wordpress.org/coding-standards/wordpress-coding-standards/css/)
- [Block Editor Coding Guidelines](https://developer.wordpress.org/block-editor/contributors/code/coding-guidelines/)
- [Block Stylesheets Documentation](https://developer.wordpress.org/themes/features/block-stylesheets/)
- [BEM Methodology](https://en.bem.info/methodology/)
- [WooCommerce CSS/Sass Naming Conventions](https://developer.woocommerce.com/docs/best-practices/coding-standards/css-sass-naming-conventions/)

---

## Additional Resources

### Query Loop Pattern
See [VENDOR-QUERY-LOOP.md](./VENDOR-QUERY-LOOP.md) for details on how the query loop pattern works with context and field blocks.

### Testing
See [TESTING.md](./TESTING.md) for information on testing blocks in isolation.
