# Another Blocks for Dokan

FSE-compatible Gutenberg blocks for the [Dokan](https://wordpress.org/plugins/dokan-lite/) multi-vendor marketplace. Convert traditional Dokan PHP templates into dynamic, reusable blocks so you can build vendor store pages and listings directly in the Site Editor.

- **Author:** [The Another](https://theanother.org)
- **License:** GPL v2 or later
- **Plugin URI:** https://the-another.org/plugin/the-another-blocks-for-dokan/

## Requirements

| Software     | Version  |
| ------------ | -------- |
| WordPress    | 6.0+     |
| PHP          | 8.3+     |
| WooCommerce  | 10.0.0+  |
| Dokan Lite   | 4.0.0+   |

A block-based (FSE) theme is recommended to take full advantage of the included templates.

## Installation

1. Download the latest release ZIP from the [Releases page](https://github.com/the-another/the-another-blocks-for-dokan/releases).
2. In WordPress admin, go to **Plugins → Add New → Upload Plugin** and upload the ZIP.
3. Activate **Another Blocks for Dokan**.
4. Make sure WooCommerce and Dokan Lite are installed and active.

## What's Included

### FSE Templates

When using a block theme, these templates automatically replace Dokan's PHP templates:

- **Single Vendor Store** (`store.html`)
- **Vendor Listing** (`store-lists.html`)
- **Store Terms & Conditions** (`store-toc.html`)

You can edit them in **Appearance → Editor → Templates**.

### Blocks

#### Store Profile

- **Vendor Store Header** — banner, avatar, name, contact
- **Vendor Store Banner** — store cover image
- **Vendor Avatar** — store logo / avatar
- **Vendor Store Name** — store name with optional link
- **Vendor Store Address** — physical address
- **Vendor Store Phone** — phone number
- **Vendor Rating** — star rating display
- **Vendor Store Status** — open / closed indicator
- **Vendor Store Tabs** — store navigation
- **Vendor Store Sidebar** — sidebar with widgets

#### Listings & Query

- **Vendor Query Loop** — grid/list of vendors with layout, ordering, and **opt-in infinite scroll** (configurable trigger offset, no URL changes)
- **Vendor Query Pagination** — paginate the query loop
- **Vendor Card** — single vendor card used inside the loop
- **Vendor Search** — search and filter by name, location, rating, and category

#### Widgets

- **Vendor Contact Form** — front-end contact form
- **Vendor Store Location** — map of the store location
- **Vendor Store Hours** — opening hours table
- **Vendor Store Terms & Conditions** — terms content

#### Product Integration

- **Product Vendor Info** — vendor info on single product pages
- **More from Seller** — additional products from the same vendor

#### Account

- **Become Vendor CTA** — call-to-action to register as a vendor

## Highlights

- **Fully dynamic** — every block is server-rendered and respects Dokan's privacy and visibility settings.
- **Theme-friendly** — uses native WordPress block supports (spacing, colors, typography) and `wp-element-button` styling so buttons inherit your theme palette.
- **Infinite scroll** — opt-in for the Vendor Query Loop, with a centered animated "Loading…" indicator and a configurable trigger offset.
- **Translation-ready** — text domain `theanother-blocks-for-dokan`, translation files live in `/languages`.
- **REST-powered pagination** — `POST /another-blocks-for-dokan/v1/vendor-query-loop` reuses the same render helpers as the initial page so paginated results match.

## Support

- Issues: https://github.com/the-another/the-another-blocks-for-dokan/issues
- Homepage: https://the-another.org/plugin/the-another-blocks-for-dokan/

## Contributing

Development setup, build commands, coding standards, and the testing workflow are documented in [CONTRIBUTING.md](CONTRIBUTING.md).

## License

This plugin is released under the [GPL v2 or later](https://www.gnu.org/licenses/gpl-2.0.html).
