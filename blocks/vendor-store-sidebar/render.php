<?php
/**
 * Store sidebar block render function.
 *
 * @package AnotherBlocksDokan
 * @since 1.0.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Store sidebar block render function.
 *
 * @param array<string, mixed> $attributes Block attributes.
 * @param string               $content    Block content.
 * @param WP_Block             $block      Block instance.
 * @return string Rendered HTML.
 */
function theabd_render_vendor_store_sidebar_block( array $attributes, string $content, WP_Block $block ): string {
	// Get vendor ID from attributes or context.
	$vendor_id = ! empty( $attributes['vendorId'] ) ? absint( $attributes['vendorId'] ) : 0;

	if ( ! $vendor_id ) {
		$vendor_id = \The_Another\Plugin\Blocks_Dokan\Helpers\Context_Detector::get_vendor_id();
	}

	if ( ! $vendor_id || ! dokan_is_user_seller( $vendor_id ) ) {
		return '';
	}

	// Get vendor data.
	$vendor_data = \The_Another\Plugin\Blocks_Dokan\Renderers\Vendor_Renderer::get_vendor_data( $vendor_id );
	if ( ! $vendor_data ) {
		return '';
	}

	$enable_theme_sidebar = $attributes['enableThemeSidebar'] ?? false;

	// Get wrapper attributes.
	$wrapper_attributes = get_block_wrapper_attributes(
		array(
			'class' => 'theabd--vendor-store-sidebar',
			'id'    => 'theabd--secondary',
			'role'  => 'complementary',
		)
	);

	ob_start();

	if ( $enable_theme_sidebar ) {
		// Use theme sidebar if enabled.
		get_sidebar( 'store' );
	} else {
		// Use Dokan sidebar widgets.
		?>
		<div <?php echo $wrapper_attributes; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
			<div class="theabd--widget-area theabd--widget-collapse">
				<?php
				if ( ! dynamic_sidebar( 'sidebar-store' ) ) {
					// Default widgets.
					if ( function_exists( 'dokan_store_category_widget' ) ) {
						dokan_store_category_widget();
					}

					if ( ! empty( $vendor_data['store_info']['address'] ) && function_exists( 'dokan_store_location_widget' ) ) {
						dokan_store_location_widget();
					}

					if ( function_exists( 'dokan_store_time_widget' ) ) {
						dokan_store_time_widget();
					}

					if ( function_exists( 'dokan_store_contact_widget' ) ) {
						dokan_store_contact_widget();
					}
				}
				?>
			</div>
		</div>
		<?php
	}

	return ob_get_clean();
}
