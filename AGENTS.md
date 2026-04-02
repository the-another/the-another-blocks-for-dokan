# Agent Development Guide - Another Blocks for Dokan

This document provides comprehensive information about the **Another Blocks for Dokan** plugin for AI agents and developers working on this project.

## Project Overview

**Another Blocks for Dokan** is a WordPress plugin that provides FSE (Full Site Editing) compatible Gutenberg blocks for the Dokan multi-vendor marketplace. It converts traditional Dokan PHP templates into dynamic, reusable Gutenberg blocks for modern block themes.

### Key Information
- **Plugin Name**: Another Blocks for Dokan
- **Version**: 1.0.1
- **Namespace**: `The_Another\Plugin\Blocks_Dokan`
- **Text Domain**: `another-blocks-for-dokan`
- **Prefix**: `dokan` (for functions and variables)
- **Author**: The Another
- **License**: GPL v2 or later

## System Requirements

### Minimum Requirements
- **WordPress**: 6.0+ (for FSE support)
- **PHP**: 7.4+
- **WooCommerce**: 7.0+
- **Dokan**: Latest stable version

### Development Environment
The plugin includes DDEV configuration for local development with a complete WordPress environment.

## Project Structure

```
another-blocks-for-dokan/
├── another-blocks-for-dokan.php    # Main plugin file
├── blocks/                          # Block definitions
│   ├── [block-name]/
│   │   ├── block.json              # Block metadata (API v3)
│   │   ├── index.js                # Block registration & editor
│   │   ├── editor.scss             # Editor-only styles
│   │   └── render.php              # Server-side rendering
├── includes/                        # PHP classes
│   ├── class-blocks.php            # Main plugin class
│   ├── class-block-registry.php    # Block registration
│   ├── helpers/                    # Helper classes
│   ├── renderers/                  # Rendering logic
│   └── templates/                  # Template controllers
├── templates/                       # FSE block templates
├── tests/                          # PHPUnit tests
│   ├── Unit/                       # Unit tests
│   ├── Integration/                # Integration tests
│   └── Factories/                  # Test factories
├── dist/                           # Built assets (generated)
├── vendor/                         # Composer dependencies
├── composer.json                   # PHP dependencies
├── package.json                    # NPM dependencies
├── phpunit.xml.dist               # PHPUnit configuration
├── .phpcs.xml.dist                # PHP CodeSniffer config
├── Makefile                        # Build automation
└── Dockerfile                      # Docker build environment
```

## Available Blocks

### Store Profile Blocks
- `the-another/blocks-for-dokan-vendor-store-header` - Display vendor store header with banner, avatar, and contact info
- `the-another/blocks-for-dokan-vendor-store-banner` - Store banner image
- `the-another/blocks-for-dokan-vendor-avatar` - Store avatar/logo
- `the-another/blocks-for-dokan-vendor-store-name` - Store name
- `the-another/blocks-for-dokan-vendor-store-address` - Store physical address
- `the-another/blocks-for-dokan-vendor-store-phone` - Store phone number
- `the-another/blocks-for-dokan-vendor-rating` - Store rating display
- `the-another/blocks-for-dokan-vendor-store-status` - Store open/closed status
- `the-another/blocks-for-dokan-vendor-store-tabs` - Store navigation tabs
- `the-another/blocks-for-dokan-vendor-store-sidebar` - Store sidebar with widgets

### Query & Listing Blocks
- `the-another/blocks-for-dokan-vendor-query-loop` - Query and display multiple stores
- `the-another/blocks-for-dokan-vendor-query-pagination` - Pagination for store queries
- `the-another/blocks-for-dokan-vendor-card` - Individual vendor store card
- `the-another/blocks-for-dokan-vendor-search` - Search and filter stores

### Widget Blocks
- `the-another/blocks-for-dokan-vendor-contact-form` - Contact vendor form
- `the-another/blocks-for-dokan-vendor-store-location` - Store location map
- `the-another/blocks-for-dokan-vendor-store-hours` - Store opening hours
- `the-another/blocks-for-dokan-vendor-store-terms-conditions` - Store terms and conditions

### Product Integration Blocks
- `the-another/blocks-for-dokan-product-vendor-info` - Display vendor info on product pages
- `the-another/blocks-for-dokan-more-from-seller` - Show more products from the same vendor

### Account/Registration Blocks
- `the-another/blocks-for-dokan-become-vendor-cta` - Call-to-action for becoming a vendor

## Development Practices

### PHP Development

#### Coding Standards
- Follow **WordPress Coding Standards** (WordPress-Extra ruleset)
- Use **WordPress-Docs** for documentation
- Minimum supported WordPress version: 6.0
- Text domain: `dokan-blocks`
- All global functions/classes must use `dokan` prefix

#### PHP Version Features
- Use PHP 7.4+ features (typed properties, arrow functions, null coalescing assignment, etc.)
- Use strict typing: type hints for parameters and return types
- Use nullable types (`?Type`) where appropriate
- Avoid PHP 8.0+ features (union types, named arguments, match expressions, nullsafe operator, constructor property promotion)

#### Architecture Patterns
1. **Singleton Pattern**: Main plugin class uses singleton pattern
   ```php
   public static function get_instance(): Blocks {
       if ( null === self::$instance ) {
           self::$instance = new self();
       }
       return self::$instance;
   }
   ```

2. **Namespace Usage**: All classes use the namespace `The_Another\Plugin\Blocks_Dokan`

3. **Separation of Concerns**:
   - `includes/` - Core PHP classes
   - `includes/helpers/` - Helper utilities
   - `includes/renderers/` - Rendering logic
   - `includes/templates/` - Template controllers

4. **Block Registration**: Blocks are registered via `Block_Registry` class which scans the `blocks/` directory

#### Security Practices
- Always escape output (`esc_html()`, `esc_url()`, `esc_attr()`, `wp_kses_post()`)
- Validate and sanitize user input
- Use nonces for form submissions
- Check capabilities before performing privileged operations
- Exit if `ABSPATH` is not defined:
  ```php
  if ( ! defined( 'ABSPATH' ) ) {
      exit;
  }
  ```

#### File Headers
All PHP files must include proper headers:
```php
<?php
/**
 * Brief description of the file.
 *
 * @package AnotherBlocksDokan
 * @since 1.0.0
 */
```

Functions must include PHPDoc:
```php
/**
 * Function description.
 *
 * @param array<string, mixed> $attributes Block attributes.
 * @param string               $content    Block content.
 * @param WP_Block             $block      Block instance.
 * @return string Rendered HTML.
 */
```

### JavaScript Development

#### Build System
- Uses `@wordpress/scripts` package for building blocks
- Entry point: `src/blocks.js`
- Output directory: `dist/`

#### Block Registration
Blocks use **Block API v3** with `block.json` metadata:
```json
{
    "$schema": "https://schemas.wp.org/trunk/block.json",
    "apiVersion": 3,
    "name": "the-another/blocks-for-dokan-block-name",
    "title": "Block Title",
    "category": "dokan",
    "textdomain": "dokan-blocks"
}
```

#### Block Structure
Each block consists of:
- `block.json` - Block metadata and attributes
- `index.js` - Block registration and edit function
- `editor.scss` - Editor-only styles
- `render.php` - Server-side render callback

### Block Rendering Pattern

#### Dynamic Blocks
All blocks use server-side rendering via `render.php`:

```php
function dokan_render_block_name( array $attributes, string $content, WP_Block $block ): string {
    // 1. Get vendor ID from attributes or context
    $vendor_id = $attributes['vendorId'] ?? 0;

    if ( ! $vendor_id ) {
        $vendor_id = \The_Another\Plugin\Blocks_Dokan\Helpers\Context_Detector::get_vendor_id();
    }

    // 2. Validate vendor
    if ( ! $vendor_id || ! dokan_is_user_seller( $vendor_id ) ) {
        return '';
    }

    // 3. Get vendor data
    $vendor_data = \The_Another\Plugin\Blocks_Dokan\Renderers\Vendor_Renderer::get_vendor_data( $vendor_id );

    // 4. Extract attributes with defaults
    $show_something = $attributes['showSomething'] ?? true;

    // 5. Build wrapper attributes
    $wrapper_attributes = get_block_wrapper_attributes(
        array(
            'class' => 'custom-class',
        )
    );

    // 6. Use output buffering for HTML
    ob_start();
    ?>
    <div <?php echo $wrapper_attributes; ?>>
        <!-- Block content -->
    </div>
    <?php
    return ob_get_clean();
}
```

#### Key Rendering Utilities
- `Context_Detector::get_vendor_id()` - Auto-detect vendor from current context
- `Vendor_Renderer::get_vendor_data()` - Get formatted vendor data
- `Vendor_Renderer::is_vendor_info_hidden()` - Check privacy settings
- `Vendor_Renderer::get_seller_rating_html()` - Get rating HTML
- `Vendor_Renderer::is_store_open()` - Check store hours status

### Styling

#### CSS Architecture
- Frontend styles: `dist/style-blocks.css` (loaded on both frontend and editor)
- Editor styles: `dist/blocks.css` (loaded only in editor)
- Individual block editor styles: `blocks/[block-name]/editor.scss`

#### BEM-like Naming Convention
```css
.theabd--vendor-store-header { }
.theabd--vendor-store-header-default { }
.theabd--vendor-store-header__element { }
.theabd--vendor-store-header--modifier { }
```

### Testing

#### Test Structure
- **Unit Tests**: `tests/Unit/` - Test individual classes/functions
- **Integration Tests**: `tests/Integration/` - Test component integration
- **Factories**: `tests/Factories/` - Test data factories

#### Testing Framework
- **PHPUnit**: 11.0+
- **Brain Monkey**: For mocking WordPress functions
- **Mockery**: For mocking objects
- Tests run WITHOUT requiring WordPress/Dokan installation

#### Running Tests
```bash
composer test                  # Run all tests
composer test:unit            # Run unit tests only
composer test:integration     # Run integration tests only
composer test:coverage        # Generate coverage report
```

### Code Quality

#### Linting
```bash
composer lint                 # Check code standards
composer lint-fix            # Auto-fix code standards
npm run format               # Format JavaScript
```

#### PHP CodeSniffer Configuration
- Standard: WordPress-Extra + WordPress-Docs
- Parallel processing: 8 files
- Excludes: `/vendor/`, `/node_modules/`, `/build/`, `*.min.js`

## Build & Deployment

### Development Workflow

#### Initial Setup
```bash
# Install dependencies
composer install
npm install

# Build blocks
npm run build
```

#### Development Mode
```bash
npm start                    # Watch mode for block development
```

#### Building for Production
```bash
composer build              # Build PHP dependencies with Mozart
npm run build              # Build JavaScript/CSS assets
```

### Version Management
```bash
npm run version:patch       # Bump patch version (1.0.0 -> 1.0.1)
npm run version:minor       # Bump minor version (1.0.0 -> 1.1.0)
npm run version:major       # Bump major version (1.0.0 -> 2.0.0)
```

### Creating Release Package
```bash
npm run plugin-zip          # Create distributable ZIP file
```

This will:
1. Build PHP dependencies (`composer build`)
2. Build JS/CSS assets (`npm run build`)
3. Create ZIP file
4. Version the ZIP file

## Constants

The plugin defines these constants:
```php
ANOTHER_BLOCKS_DOKAN_VERSION         // Plugin version
ANOTHER_BLOCKS_DOKAN_PLUGIN_FILE    // Main plugin file path
ANOTHER_BLOCKS_DOKAN_PLUGIN_DIR     // Plugin directory path
ANOTHER_BLOCKS_DOKAN_PLUGIN_URL     // Plugin URL
ANOTHER_BLOCKS_DOKAN_PLUGIN_BASENAME // Plugin basename
```

## FSE Templates

The plugin provides FSE block templates in `templates/`:
- `store.html` - Single vendor store page template
- `store-lists.html` - Vendor listing page template
- `store-toc.html` - Store table of contents template

These templates automatically replace PHP templates when using block themes.

## Common Development Tasks

### Adding a New Block

1. **Create block directory**: `blocks/new-block-name/`

2. **Create `block.json`**:
```json
{
    "$schema": "https://schemas.wp.org/trunk/block.json",
    "apiVersion": 3,
    "name": "the-another/blocks-for-dokan-new-block",
    "title": "New Block",
    "category": "dokan",
    "icon": "store",
    "description": "Block description",
    "textdomain": "dokan-blocks",
    "supports": {
        "html": false
    },
    "attributes": {}
}
```

3. **Create `render.php`**:
```php
<?php
/**
 * New block render function.
 *
 * @package AnotherBlocksDokan
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function dokan_render_new_block( array $attributes, string $content, WP_Block $block ): string {
    // Implementation
}
```

4. **Create `index.js`**:
```javascript
import { registerBlockType } from '@wordpress/blocks';
import metadata from './block.json';

registerBlockType(metadata.name, {
    edit: () => {
        return <div>Block Editor View</div>;
    }
});
```

5. **Create `editor.scss`** (if needed):
```scss
.wp-block-the-another-blocks-for-dokan-new-block {
    // Editor-specific styles
}
```

6. **Import in `src/blocks.js`**:
```javascript
import './blocks/new-block-name';
```

7. **Build**:
```bash
npm run build
```

The block will be automatically registered by `Block_Registry`.

### Accessing Vendor Data

Use the `Vendor_Renderer` class:

```php
use The_Another\Plugin\Blocks_Dokan\Renderers\Vendor_Renderer;

$vendor_data = Vendor_Renderer::get_vendor_data( $vendor_id );

// Available data:
// $vendor_data['shop_name']
// $vendor_data['shop_url']
// $vendor_data['avatar']
// $vendor_data['banner']
// $vendor_data['address']
// $vendor_data['phone']
// $vendor_data['email']
// $vendor_data['social_profiles']
// etc.
```

### Context Detection

Automatically detect vendor ID in different contexts:

```php
use The_Another\Plugin\Blocks_Dokan\Helpers\Context_Detector;

$vendor_id = Context_Detector::get_vendor_id();
```

This works in:
- Single store pages
- Product pages (gets the product's vendor)
- Archive pages
- Any Dokan context

## Debugging

### Enable WordPress Debug Mode
In `wp-config.php`:
```php
define( 'WP_DEBUG', true );
define( 'WP_DEBUG_LOG', true );
define( 'WP_DEBUG_DISPLAY', false );
define( 'SCRIPT_DEBUG', true );
```

### View Logs
- PHP errors: `wp-content/debug.log`
- Browser console for JavaScript errors

### Common Issues

1. **Blocks not appearing**: Run `npm run build` and clear browser cache
2. **PHP errors**: Check PHP version is 7.4+
3. **Styling issues**: Ensure both `style-blocks.css` and `blocks.css` are enqueued
4. **Vendor data not loading**: Verify Dokan is active and vendor exists

## Performance Considerations

1. **Asset Loading**: Frontend styles are loaded on all pages; editor assets only in admin
2. **Data Fetching**: Vendor data is cached where possible via Dokan's caching
3. **Conditional Rendering**: Blocks return empty string if vendor not found
4. **Lazy Loading**: Use WordPress block supports for lazy loading when applicable

## Internationalization (i18n)

All strings must be translatable:
```php
esc_html__( 'Text', 'another-blocks-for-dokan' )
esc_html_e( 'Text', 'another-blocks-for-dokan' )
esc_attr__( 'Text', 'another-blocks-for-dokan' )
_n( 'Singular', 'Plural', $count, 'another-blocks-for-dokan' )
```

Translation files location: `languages/`

## Git Workflow (Recommended)

1. Work on feature branches
2. Follow conventional commits:
   - `feat: add new block`
   - `fix: resolve rendering issue`
   - `docs: update documentation`
   - `refactor: improve code structure`
   - `test: add unit tests`
   - `chore: update dependencies`

## Support & Resources

- **Issues**: https://github.com/theanother/blocks-for-dokan/issues
- **Source**: https://github.com/theanother/blocks-for-dokan
- **Homepage**: https://theanother.org/plugin/blocks-for-dokan/

## Important Notes for AI Agents

1. **Always maintain PHP 7.4+ compatibility** - use PHP 7.4 features, avoid PHP 8.0+ features
2. **Follow WordPress coding standards strictly** - run `composer lint` before committing
3. **All blocks must be dynamic** - use server-side rendering with `render.php`
4. **Security first** - escape all output, validate/sanitize all input
5. **Test thoroughly** - write unit and integration tests for new functionality
6. **Document everything** - PHPDoc for all functions, clear comments for complex logic
7. **Use existing patterns** - follow the established architecture and naming conventions
8. **Vendor context** - always check if vendor exists and is valid before rendering
9. **Respect privacy settings** - use `Vendor_Renderer::is_vendor_info_hidden()` to check privacy
10. **Build before testing** - always run `npm run build` after JS/CSS changes

## Quick Reference Commands

```bash
# Development
npm install              # Install Node dependencies
composer install         # Install PHP dependencies
npm start               # Start development mode (watch)
npm run build           # Build production assets

# Quality Assurance
composer lint           # Check PHP code standards
composer lint-fix       # Fix PHP code standards
composer test           # Run all tests
npm run format          # Format JavaScript code

# Deployment
npm run version:patch   # Bump version (patch)
npm run plugin-zip      # Create release ZIP

# Docker (using Makefile)
make docker-build       # Build Docker image
make install-dev        # Install with dev dependencies
make test              # Run tests in Docker
make lint              # Run linter in Docker
```

---

**Last Updated**: 2026-01-22
**Plugin Version**: 1.0.1
