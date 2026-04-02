<?php
/**
 * Product vendor info block render function.
 *
 * @package AnotherBlocksDokan
 * @since 1.0.0
 */

// Exit if accessed directly.
use The_Another\Plugin\Blocks_Dokan\Helpers\Context_Detector;
use The_Another\Plugin\Blocks_Dokan\Renderers\Vendor_Renderer;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Product vendor info block render function.
 *
 * @param array<string, mixed> $attributes Block attributes.
 * @param string               $content    Block content (inner blocks).
 * @param WP_Block             $block      Block instance.
 * @return string Rendered HTML.
 */
function theabd_render_product_vendor_info_block( array $attributes, string $content, WP_Block $block ): string {
	// Get product ID from attributes or context.
	$product_id = ! empty( $attributes['productId'] ) ? absint( $attributes['productId'] ) : 0;

	if ( ! $product_id ) {
		$product_id = Context_Detector::get_product_id();
	}

	if ( ! $product_id ) {
		// Only show placeholder in editor context.
		if ( is_admin() || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) ) {
			$wrapper_attributes = get_block_wrapper_attributes(
				array(
					'class' => 'theabd--product-vendor-info',
				)
			);

			ob_start();
			?>
			<div <?php echo $wrapper_attributes; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
				<div class="theabd--product-vendor-info-placeholder">
					<p style="padding: 1rem; background: #f0f0f0; border-radius: 4px; font-size: 0.875rem; color: #666;">
						<?php echo esc_html__( 'Product Vendor Info: Add this block to a product page or specify a product ID in the block settings.', 'another-dokan-blocks' ); ?>
					</p>
				</div>
			</div>
			<?php
			return ob_get_clean();
		}
		return '';
	}

	// Get product.
	$product = wc_get_product( $product_id );
	if ( ! $product ) {
		return '';
	}

	// Get vendor ID from product author.
	$vendor_id = absint( get_post_field( 'post_author', $product->get_id() ) );
	if ( ! $vendor_id || ! dokan_is_user_seller( $vendor_id ) ) {
		return '';
	}

	// Get vendor data.
	$vendor_data = Vendor_Renderer::get_vendor_data( $vendor_id );
	if ( ! $vendor_data ) {
		return '';
	}

	// Build wrapper classes.
	$wrapper_classes = array( 'theabd--product-vendor-info' );

	// Initialize inline styles array.
	$inline_styles = array();

	// Add spacing styles (padding/margin) from style attribute.
	if ( ! empty( $attributes['style']['spacing']['padding'] ) ) {
		$padding = $attributes['style']['spacing']['padding'];
		if ( ! empty( $padding['top'] ) ) {
			$inline_styles[] = 'padding-top: ' . esc_attr( $padding['top'] );
		}
		if ( ! empty( $padding['right'] ) ) {
			$inline_styles[] = 'padding-right: ' . esc_attr( $padding['right'] );
		}
		if ( ! empty( $padding['bottom'] ) ) {
			$inline_styles[] = 'padding-bottom: ' . esc_attr( $padding['bottom'] );
		}
		if ( ! empty( $padding['left'] ) ) {
			$inline_styles[] = 'padding-left: ' . esc_attr( $padding['left'] );
		}
	}
	if ( ! empty( $attributes['style']['spacing']['margin'] ) ) {
		$margin = $attributes['style']['spacing']['margin'];
		if ( ! empty( $margin['top'] ) ) {
			$inline_styles[] = 'margin-top: ' . esc_attr( $margin['top'] );
		}
		if ( ! empty( $margin['right'] ) ) {
			$inline_styles[] = 'margin-right: ' . esc_attr( $margin['right'] );
		}
		if ( ! empty( $margin['bottom'] ) ) {
			$inline_styles[] = 'margin-bottom: ' . esc_attr( $margin['bottom'] );
		}
		if ( ! empty( $margin['left'] ) ) {
			$inline_styles[] = 'margin-left: ' . esc_attr( $margin['left'] );
		}
	}

	// Build wrapper attributes with layout support.
	$wrapper_attributes = get_block_wrapper_attributes(
		array(
			'class' => implode( ' ', $wrapper_classes ),
		)
	);

	// Build style attribute separately to avoid WordPress merging issues.
	$style_attr = '';
	if ( ! empty( $inline_styles ) ) {
		$style_attr = ' style="' . esc_attr( implode( '; ', $inline_styles ) ) . '"';
	}

	// Manually add layout classes if WordPress didn't add them.
	// This ensures flex layout works properly on frontend.
	if ( ! empty( $attributes['layout']['type'] ) && strpos( $wrapper_attributes, 'is-layout-' ) === false ) {
		$layout_type = $attributes['layout']['type'];
		$layout_class = 'is-layout-' . $layout_type;

		// Add orientation class for flex layouts.
		if ( 'flex' === $layout_type && ! empty( $attributes['layout']['orientation'] ) ) {
			$orientation = $attributes['layout']['orientation'];
			if ( 'vertical' === $orientation ) {
				$layout_class .= ' is-vertical';
			}
		}

		// Inject layout class into wrapper attributes.
		$wrapper_attributes = str_replace(
			'class="',
			'class="' . esc_attr( $layout_class ) . ' ',
			$wrapper_attributes
		);
	}

	// Render the product vendor info with inner blocks.
	ob_start();
	?>
	<div <?php echo $wrapper_attributes; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?><?php echo $style_attr; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
		<?php
		// Render inner blocks with vendor context.
		if ( ! empty( $block->inner_blocks ) && $vendor_data ) {
			// Re-render inner blocks with proper vendor context.
			foreach ( $block->inner_blocks as $inner_block ) {
				// Create a new block instance with vendor context.
				$inner_block_instance = new WP_Block(
					$inner_block->parsed_block,
					array( 'dokan/vendor' => $vendor_data )
				);
				echo $inner_block_instance->render(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			}
		} elseif ( ! empty( $content ) ) {
			// Fallback to pre-rendered content if no inner blocks.
			echo $content; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		} else {
			// No inner blocks and no content - show minimal default.
			?>
			<div class="theabd--product-vendor-info-default">
				<?php if ( ! empty( $vendor_data['shop_name'] ) ) : ?>
					<h3>
						<a href="<?php echo esc_url( $vendor_data['shop_url'] ); ?>">
							<?php echo esc_html( $vendor_data['shop_name'] ); ?>
						</a>
					</h3>
				<?php endif; ?>
			</div>
			<?php
		}
		?>
	</div>
	<?php

	return ob_get_clean();
}