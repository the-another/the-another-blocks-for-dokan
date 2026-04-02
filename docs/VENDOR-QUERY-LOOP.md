# Vendor Query Loop Pattern

## Overview

The Vendor Query Loop block has been transformed from a fixed-layout block with toggle options into a **flexible query loop pattern**. This gives users complete control over what fields are displayed and how they're arranged for each store in the list.

## What Changed

### Before (Old Pattern)
- Fixed layout with toggle options in the sidebar
- All stores displayed the same predefined fields
- Limited customization - could only show/hide fields
- Fields: Banner, Rating, Address, Phone, Open/Closed Status

### After (New Query Loop Pattern)
- Flexible InnerBlocks-based layout
- Users can add, remove, and arrange field blocks
- Full control over each store card's layout
- Can use core blocks (Group, Columns, Spacer, etc.) for advanced layouts

## Available Field Blocks

The following field blocks can be used inside the Vendor Card query loop:

### 1. **Vendor Store Name** (`the-another/blocks-for-dokan-vendor-store-name`)
- Displays the vendor's store name
- Settings:
  - HTML Tag (h1-h6, p, div)
  - Link to Store (toggle)
- Supports: Typography, Colors, Spacing

### 2. **Vendor Avatar** (`the-another/blocks-for-dokan-vendor-avatar`)
- Displays the vendor's avatar/logo
- Settings:
  - Avatar Size (32-200px)
  - Link to Store (toggle)
- Supports: Spacing

### 3. **Vendor Rating** (`the-another/blocks-for-dokan-vendor-rating`)
- Displays star rating and review count
- Settings:
  - Show Review Count (toggle)
- Supports: Colors, Spacing

### 4. **Vendor Store Address** (`the-another/blocks-for-dokan-vendor-store-address`)
- Displays the store's physical address
- Settings:
  - Show Icon (toggle)
- Supports: Typography, Colors, Spacing

### 5. **Vendor Store Phone** (`the-another/blocks-for-dokan-vendor-store-phone`)
- Displays the store's phone number
- Settings:
  - Show Icon (toggle)
  - Make Clickable (tel: link)
- Supports: Typography, Colors, Spacing

### 6. **Vendor Store Status** (`the-another/blocks-for-dokan-vendor-store-status`)
- Displays open/closed status
- No settings
- Supports: Typography, Colors, Spacing

### 7. **Vendor Store Banner** (`the-another/blocks-for-dokan-vendor-store-banner`)
- Displays the store's banner image
- Settings:
  - Banner Height (100-500px)
  - Link to Store (toggle)
- Supports: Spacing

## How It Works

### Technical Architecture

1. **Context Provision**: The Vendor Query Loop block provides `dokan/vendorId` context to all inner blocks
2. **Query Loop**: For each vendor in the query, the inner blocks are rendered with that vendor's ID in context
3. **Field Blocks**: Each field block reads the `dokan/vendorId` from context and displays the appropriate data

### Block Hierarchy

```
Vendor Card (Query Container)
├── Query Settings (perPage, orderBy, featured only)
├── Layout Settings (grid/list, columns)
└── InnerBlocks (Template for each store)
    ├── Store Avatar
    ├── Store Name
    ├── Store Rating
    ├── Store Address
    ├── Store Phone
    └── ... (any combination of field blocks)
```

## Usage Examples

### Example 1: Simple Card Layout (Default)
```
Vendor Card
└── Vendor Avatar (80px)
└── Vendor Store Name (h3)
└── Vendor Rating (with count)
└── Vendor Store Address (with icon)
└── Vendor Store Phone (with icon)
```

### Example 2: Banner + Content Layout
```
Vendor Card
└── Vendor Store Banner (200px height)
└── Group
    └── Vendor Avatar (60px)
    └── Vendor Store Name (h2)
    └── Vendor Rating
    └── Columns
        └── Column
            └── Vendor Store Address
        └── Column
            └── Vendor Store Phone
            └── Vendor Store Status
```

### Example 3: Minimal Layout
```
Vendor Card
└── Vendor Store Name (h4, no link)
└── Vendor Rating (no count)
```

## Benefits

1. **Maximum Flexibility**: Users can create any layout they want
2. **Reusable Field Blocks**: Field blocks can be used in other contexts too
3. **Core Block Integration**: Can use core blocks (Group, Columns, Spacer) for advanced layouts
4. **Better UX**: Visual editing - see exactly what you're building
5. **Future-Proof**: Easy to add new field blocks without changing the query loop

## Migration Notes

### For Users
- Existing Vendor Query Loop blocks will need to be recreated with the new pattern
- The default template mimics the old layout for easy migration
- More customization options available now

### For Developers
- Field blocks use `usesContext: ['dokan/vendorId']` to access vendor data
- All field blocks have a `parent: ['the-another/blocks-for-dokan-vendor-card']` restriction
- Render functions check context first, then fall back to attributes

## File Structure

```
blocks/
├── vendor-card/             # Query loop container
│   ├── block.json          # Provides context
│   ├── index.js            # InnerBlocks implementation
│   └── render.php          # Loops through vendors
├── vendor-store-name/       # Field block
│   ├── block.json          # Uses context
│   ├── index.js
│   └── render.php
├── vendor-avatar/           # Field block
├── vendor-rating/           # Field block
├── vendor-store-address/    # Field block
├── vendor-store-phone/      # Field block
├── vendor-store-status/     # Field block
└── vendor-store-banner/     # Field block
```

## Next Steps

1. Test the new blocks in the WordPress editor
2. Create block patterns for common layouts
3. Add more field blocks as needed (e.g., Vendor Store Description, Vendor Store Products Count)
4. Consider adding a block variation system for quick layouts
5. Update documentation and user guides

## Technical Details

### Context System
- **Provider**: `vendor-card` block provides `dokan/vendorId` via `providesContext`
- **Consumers**: All field blocks declare `usesContext: ['dokan/vendorId']`
- **Rendering**: PHP render functions access context via `$block->context['dokan/vendorId']`

### InnerBlocks Template
The default template is defined in `vendor-card/index.js`:
```javascript
const TEMPLATE = [
  [ 'the-another/blocks-for-dokan-vendor-avatar', { size: 80 } ],
  [ 'the-another/blocks-for-dokan-vendor-store-name', { tagName: 'h3' } ],
  [ 'the-another/blocks-for-dokan-vendor-rating', { showCount: true } ],
  [ 'the-another/blocks-for-dokan-vendor-store-address', { showIcon: true } ],
  [ 'the-another/blocks-for-dokan-vendor-store-phone', { showIcon: true } ],
];
```

### Allowed Blocks
Field blocks + core layout blocks:
```javascript
const ALLOWED_BLOCKS = [
  'the-another/blocks-for-dokan-vendor-store-name',
  'the-another/blocks-for-dokan-vendor-avatar',
  'the-another/blocks-for-dokan-vendor-rating',
  'the-another/blocks-for-dokan-vendor-store-address',
  'the-another/blocks-for-dokan-vendor-store-phone',
  'the-another/blocks-for-dokan-vendor-store-status',
  'the-another/blocks-for-dokan-vendor-store-banner',
  'core/group',
  'core/columns',
  'core/column',
  'core/separator',
  'core/spacer',
];
```
