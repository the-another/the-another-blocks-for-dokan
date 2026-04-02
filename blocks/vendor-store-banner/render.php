<?php
/**
 * Store banner block render function.
 *
 * @package AnotherBlocksDokan
 * @since 1.0.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Store banner block render function.
 *
 * @param array<string, mixed> $attributes Block attributes.
 * @param string               $content    Block content (InnerBlocks).
 * @param WP_Block             $block      Block instance.
 * @return string Rendered HTML.
 */
function theabd_render_vendor_store_banner_block( array $attributes, string $content, WP_Block $block ): string {
	// Get vendor data from context first (passed from parent block like store-list).
	$vendor = $block->context['dokan/vendor'] ?? null;

	// If no vendor in context, try to detect from current page.
	if ( empty( $vendor ) || empty( $vendor['id'] ) ) {
		$vendor_id = \The_Another\Plugin\Blocks_Dokan\Helpers\Context_Detector::get_vendor_id();

		if ( $vendor_id > 0 ) {
			// Get vendor data using our renderer.
			$vendor_data = \The_Another\Plugin\Blocks_Dokan\Renderers\Vendor_Renderer::get_vendor_data( $vendor_id );
			if ( $vendor_data ) {
				$vendor = array(
					'id'         => $vendor_data['id'],
					'store_name' => $vendor_data['shop_name'] ?? $vendor_data['store_name'] ?? '',
					'banner'     => $vendor_data['banner'] ?? '',
					'shop_url'   => $vendor_data['shop_url'] ?? '',
					'gravatar'   => $vendor_data['avatar'] ?? $vendor_data['gravatar'] ?? '',
					'rating'     => $vendor_data['rating'] ?? array(),
					'store_info' => $vendor_data['store_info'] ?? array(),
				);
			}
		}
	}

	// If still no vendor, return empty or minimal container.
	if ( empty( $vendor ) || empty( $vendor['id'] ) ) {
		// Still render InnerBlocks content with placeholder styling.
		$wrapper_attributes = get_block_wrapper_attributes(
			array(
				'class' => 'theabd--vendor-store-banner theabd--vendor-store-banner--no-vendor',
				'style' => 'min-height: 200px; background: #f0f0f0;',
			)
		);

		return sprintf(
			'<div %s>%s</div>',
			$wrapper_attributes,
			$content
		);
	}

	// Get attributes with defaults.
	$height              = isset( $attributes['height'] ) ? absint( $attributes['height'] ) : 300;
	$min_height          = isset( $attributes['minHeight'] ) ? absint( $attributes['minHeight'] ) : 200;
	$background_overlay  = isset( $attributes['backgroundOverlay'] ) ? floatval( $attributes['backgroundOverlay'] ) : 0.3;
	$overlay_color       = $attributes['overlayColor'] ?? '#000000';
	$background_position = $attributes['backgroundPosition'] ?? 'center';
	$background_size     = $attributes['backgroundSize'] ?? 'cover';

	// Get banner URL.
	$banner_url = '';
	if ( ! empty( $vendor['banner'] ) ) {
		$banner_url = esc_url( $vendor['banner'] );
	}

	// Convert hex color to RGB for overlay.
	$overlay_rgba = 'rgba(0, 0, 0, ' . $background_overlay . ')';
	if ( preg_match( '/^#([A-Fa-f0-9]{6})$/', $overlay_color, $matches ) ) {
		$hex = $matches[1];
		$r   = hexdec( substr( $hex, 0, 2 ) );
		$g   = hexdec( substr( $hex, 2, 2 ) );
		$b   = hexdec( substr( $hex, 4, 2 ) );
		$overlay_rgba = sprintf( 'rgba(%d, %d, %d, %s)', $r, $g, $b, $background_overlay );
	}

	// Build inline styles.
	$inline_styles = array(
		'min-height'          => $min_height . 'px',
		'background-size'     => $background_size,
		'background-position' => $background_position,
		'background-repeat'   => 'no-repeat',
	);

	// Add height if set.
	if ( $height > 0 ) {
		$inline_styles['height'] = $height . 'px';
	}

	// Add background image with overlay if banner exists.
	if ( ! empty( $banner_url ) ) {
		$inline_styles['background-image'] = sprintf(
			'linear-gradient(%s, %s), url(%s)',
			$overlay_rgba,
			$overlay_rgba,
			$banner_url
		);
	}

	// Convert inline styles array to string.
	$style_string = '';
	foreach ( $inline_styles as $property => $value ) {
		$style_string .= sprintf( '%s: %s; ', $property, $value );
	}

	// Get wrapper attributes.
	$wrapper_attributes = get_block_wrapper_attributes(
		array(
			'class' => 'theabd--vendor-store-banner',
			'style' => trim( $style_string ),
		)
	);

	// Return the banner container with inner blocks content.
	return sprintf(
		'<div %s>%s</div>',
		$wrapper_attributes,
		$content
	);
}
