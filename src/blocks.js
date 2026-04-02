/**
 * Block entry point for webpack build.
 *
 * This file imports all block editor JavaScript files for building.
 * Blocks are registered here for the editor, but rendered server-side via PHP.
 *
 * @package DokanBlocks
 * @since 1.0.0
 */

// Import all block editor components (relative to src/).
import '../blocks/vendor-store-header/index.js';
import '../blocks/vendor-store-sidebar/index.js';
import '../blocks/vendor-store-tabs/index.js';
import '../blocks/vendor-store-terms-conditions/index.js';
import '../blocks/vendor-query-loop/index.js';
import '../blocks/vendor-query-pagination/index.js';
import '../blocks/vendor-card/index.js';
import '../blocks/vendor-search/index.js';

// Vendor field blocks (for use inside vendor query loop).
import '../blocks/vendor-store-name/index.js';
import '../blocks/vendor-avatar/index.js';
import '../blocks/vendor-rating/index.js';
import '../blocks/vendor-store-address/index.js';
import '../blocks/vendor-store-phone/index.js';
import '../blocks/vendor-store-status/index.js';
import '../blocks/vendor-store-banner/index.js';

import '../blocks/product-vendor-info/index.js';
import '../blocks/more-from-seller/index.js';
import '../blocks/vendor-contact-form/index.js';
import '../blocks/vendor-store-location/index.js';
import '../blocks/vendor-store-hours/index.js';
import '../blocks/become-vendor-cta/index.js';
