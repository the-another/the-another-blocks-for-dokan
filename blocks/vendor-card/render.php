<?php
/**
 * Vendor card block render function.
 *
 * @package AnotherBlocksDokan
 * @since 1.0.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Vendor card block render function.
 *
 * @param array<string, mixed> $attributes Block attributes.
 * @param string               $content    Block content (inner blocks).
 * @param WP_Block             $block      Block instance.
 * @return string Rendered HTML.
 */
function theabd_render_vendor_card_block( array $attributes, string $content, WP_Block $block ): string {
	// Extract attributes with defaults.
	$use_banner_as_background = $attributes['useBannerAsBackground'] ?? false;
	$background_overlay       = $attributes['backgroundOverlay'] ?? 0.5;

	// Build wrapper classes.
	$wrapper_classes = array( 'theabd--vendor-card' );
	if ( $use_banner_as_background ) {
		$wrapper_classes[] = 'has-banner-background';
	}

	// Initialize inline styles array.
	$inline_styles = array();

	// Check if vendor data is provided via context (from store-list query loop).
	$vendor_data = null;
	$vendor_id   = 0;

	if ( ! empty( $block->context['dokan/vendor'] ) ) {
		// Use vendor data from context (inherited from store-list).
		$vendor_data = $block->context['dokan/vendor'];
		$vendor_id   = ! empty( $vendor_data['id'] ) ? absint( $vendor_data['id'] ) : 0;
	} else {
		// Fallback: fetch vendor data using vendorId attribute.
		$vendor_id = ! empty( $attributes['vendorId'] ) ? absint( $attributes['vendorId'] ) : 0;

		if ( $vendor_id && dokan_is_user_seller( $vendor_id ) ) {
			$vendor_data = \The_Another\Plugin\Blocks_Dokan\Renderers\Vendor_Renderer::get_vendor_data( $vendor_id );
		}
	}

	// Add background image if enabled and vendor data is available.
	if ( $use_banner_as_background && $vendor_data && ! empty( $vendor_data['banner'] ) ) {
		$inline_styles[] = sprintf(
			'background-image: linear-gradient(rgba(0, 0, 0, %s), rgba(0, 0, 0, %s)), url(%s)',
			esc_attr( $background_overlay ),
			esc_attr( $background_overlay ),
			esc_url( $vendor_data['banner'] )
		);
		$inline_styles[] = 'background-size: cover';
		$inline_styles[] = 'background-position: center';
		$inline_styles[] = 'background-repeat: no-repeat';
	}

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

	// Build wrapper attributes with layout support (without style to avoid conflicts).
	// WordPress automatically adds layout classes when block has layout support.
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

	// If no vendor ID or invalid vendor, show placeholder in editor.
	if ( ! $vendor_id || ! $vendor_data ) {
		// Only show placeholder in editor context.
		if ( is_admin() || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) ) {
			ob_start();
			?>
			<div <?php echo $wrapper_attributes; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?><?php echo $style_attr; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
				<div class="theabd--vendor-card-placeholder">
					<?php if ( ! $vendor_id ) : ?>
						<p class="theabd--store-placeholder-notice" style="padding: 1rem; background: #f0f0f0; border-radius: 4px; font-size: 0.875rem; color: #666;">
							<?php echo esc_html__( 'Please enter a valid Vendor ID in the block settings to display a store card.', 'another-dokan-blocks' ); ?>
						</p>
					<?php else : ?>
						<p class="theabd--store-placeholder-notice" style="padding: 1rem; background: #fff3cd; border-radius: 4px; font-size: 0.875rem; color: #856404;">
							<?php echo esc_html__( 'Vendor ID not found or user is not a seller.', 'another-dokan-blocks' ); ?>
						</p>
					<?php endif; ?>
					<?php
					// Render inner blocks even in placeholder mode.
					if ( ! empty( $block->inner_blocks ) ) {
						// Render inner blocks without vendor context (will show placeholders).
						foreach ( $block->inner_blocks as $inner_block ) {
							$inner_block_instance = new WP_Block(
								$inner_block->parsed_block,
								array()
							);
							echo $inner_block_instance->render(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
						}
					} elseif ( ! empty( $content ) ) {
						echo $content; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
					}
					?>
				</div>
			</div>
			<?php
			return ob_get_clean();
		}
		return '';
	}

	// Render the store card with inner blocks.
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
		} else {
			// Fallback to pre-rendered content if no vendor data or no inner blocks.
			echo $content; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		}
		?>
	</div>
	<?php

	return ob_get_clean();
}
