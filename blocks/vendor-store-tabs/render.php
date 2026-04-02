<?php
/**
 * Store tabs block render function.
 *
 * @package AnotherBlocksDokan
 * @since 1.0.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Get the current store tab identifier based on query vars.
 *
 * @return string Current tab key.
 */
function dokan_blocks_get_current_store_tab(): string {
	// Check for Terms & Conditions tab.
	if ( get_query_var( 'toc' ) ) {
		return 'terms_and_conditions';
	}

	// Check for Reviews tab (Dokan Pro).
	if ( get_query_var( 'store_review' ) ) {
		return 'reviews';
	}

	// Default to products tab.
	return 'products';
}

/**
 * Check if a tab URL matches the current page.
 *
 * @param string $tab_key Tab key identifier.
 * @param string $tab_url Tab URL.
 * @return bool Whether the tab is currently active.
 */
function dokan_blocks_is_tab_active( string $tab_key, string $tab_url ): bool {
	$current_tab = dokan_blocks_get_current_store_tab();

	// Direct key match.
	if ( $current_tab === $tab_key ) {
		return true;
	}

	// For products tab, check if we're on the base store URL.
	if ( 'products' === $tab_key && 'products' === $current_tab ) {
		return true;
	}

	// Fallback: Compare current URL with tab URL.
	$current_url = home_url( add_query_arg( array() ) );
	$current_url = trailingslashit( strtok( $current_url, '?' ) );
	$tab_url     = trailingslashit( strtok( $tab_url, '?' ) );

	return $current_url === $tab_url;
}

/**
 * Store tabs block render function.
 *
 * @param array<string, mixed> $attributes Block attributes.
 * @param string               $content    Block content.
 * @param WP_Block             $block      Block instance.
 * @return string Rendered HTML.
 */
function theabd_render_vendor_store_tabs_block( array $attributes, string $content, WP_Block $block ): string {
	// Get vendor ID from attributes or context.
	$vendor_id = ! empty( $attributes['vendorId'] ) ? absint( $attributes['vendorId'] ) : 0;

	if ( ! $vendor_id ) {
		$vendor_id = \The_Another\Plugin\Blocks_Dokan\Helpers\Context_Detector::get_vendor_id();
	}

	if ( ! $vendor_id || ! dokan_is_user_seller( $vendor_id ) ) {
		return '';
	}

	// Get store tabs.
	$store_tabs = dokan_get_store_tabs( $vendor_id );
	if ( empty( $store_tabs ) ) {
		return '';
	}

	// Get wrapper attributes.
	$wrapper_attributes = get_block_wrapper_attributes(
		array(
			'class' => 'theabd--vendor-store-tabs',
		)
	);

	ob_start();
	?>
	<div <?php echo $wrapper_attributes; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
		<ul class="theabd--list-inline theabd--vendor-store-tabs-list" role="tablist">
			<?php foreach ( $store_tabs as $key => $tab ) : ?>
				<?php if ( ! empty( $tab['url'] ) ) : ?>
					<?php
					$is_active  = dokan_blocks_is_tab_active( $key, $tab['url'] );
					$tab_class  = $is_active ? 'theabd--store-tab-item theabd--active' : 'theabd--store-tab-item';
					$aria_attrs = $is_active ? 'aria-selected="true" aria-current="page"' : 'aria-selected="false"';
					?>
					<li class="<?php echo esc_attr( $tab_class ); ?>" role="presentation">
						<a
							href="<?php echo esc_url( $tab['url'] ); ?>"
							role="tab"
							<?php echo $aria_attrs; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						>
							<?php echo esc_html( $tab['title'] ?? $key ); ?>
						</a>
					</li>
				<?php endif; ?>
			<?php endforeach; ?>
		</ul>
	</div>
	<?php

	return ob_get_clean();
}
