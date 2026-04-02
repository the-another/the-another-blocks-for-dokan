<?php
/**
 * Store name block render function.
 *
 * @package AnotherBlocksDokan
 * @since 1.0.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Store name block render function.
 *
 * @param array<string, mixed> $attributes Block attributes.
 * @param string               $content    Block content.
 * @param WP_Block             $block      Block instance.
 * @return string Rendered HTML.
 */
function theabd_render_vendor_store_name_block( array $attributes, string $content, WP_Block $block ): string {
	// Get vendor data from context.
	$vendor = $block->context['dokan/vendor'] ?? null;

	// If no vendor in context, try to detect from current page.
	if ( empty( $vendor ) || empty( $vendor['id'] ) ) {
		$vendor_id = \The_Another\Plugin\Blocks_Dokan\Helpers\Context_Detector::get_vendor_id();

		if ( $vendor_id > 0 ) {
			$vendor_data = \The_Another\Plugin\Blocks_Dokan\Renderers\Vendor_Renderer::get_vendor_data( $vendor_id );
			if ( $vendor_data ) {
				$vendor = array(
					'id'         => $vendor_data['id'],
					'store_name' => $vendor_data['shop_name'] ?? '',
					'shop_url'   => $vendor_data['shop_url'] ?? '',
				);
			}
		}
	}

	if ( empty( $vendor ) || empty( $vendor['id'] ) ) {
		return '<p>' . esc_html__( 'Store Name', 'another-dokan-blocks' ) . '</p>';
	}

	$shop_name = $vendor['store_name'] ?? $vendor['shop_name'] ?? '';
	$shop_url  = $vendor['shop_url'] ?? '';
	$tag_name  = isset( $attributes['tagName'] ) ? sanitize_text_field( $attributes['tagName'] ) : 'h2';
	$is_link   = $attributes['isLink'] ?? true;

	// Validate tag name.
	$allowed_tags = array( 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'p', 'div', 'span' );
	if ( ! in_array( $tag_name, $allowed_tags, true ) ) {
		$tag_name = 'h2';
	}

	// Build inline styles from style attribute.
	$inline_styles = array();

	// Typography styles.
	if ( ! empty( $attributes['style']['typography']['fontSize'] ) ) {
		$inline_styles[] = 'font-size: ' . esc_attr( $attributes['style']['typography']['fontSize'] );
	}
	if ( ! empty( $attributes['style']['typography']['fontWeight'] ) ) {
		$inline_styles[] = 'font-weight: ' . esc_attr( $attributes['style']['typography']['fontWeight'] );
	}
	if ( ! empty( $attributes['style']['typography']['lineHeight'] ) ) {
		$inline_styles[] = 'line-height: ' . esc_attr( $attributes['style']['typography']['lineHeight'] );
	}

	// Color styles.
	if ( ! empty( $attributes['style']['color']['text'] ) ) {
		$inline_styles[] = 'color: ' . esc_attr( $attributes['style']['color']['text'] );
	}
	if ( ! empty( $attributes['style']['color']['background'] ) ) {
		$inline_styles[] = 'background-color: ' . esc_attr( $attributes['style']['color']['background'] );
	}

	// Spacing styles.
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

	// Get wrapper attributes without style (to avoid conflicts).
	$wrapper_attributes = get_block_wrapper_attributes(
		array(
			'class' => 'theabd--vendor-store-name',
		)
	);

	// Build style attribute separately to avoid WordPress merging issues.
	$style_attr = '';
	if ( ! empty( $inline_styles ) ) {
		$style_attr = ' style="' . esc_attr( implode( '; ', $inline_styles ) ) . '"';
	}

	ob_start();
	?>
	<<?php echo esc_attr( $tag_name ); ?> <?php echo $wrapper_attributes; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?><?php echo $style_attr; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
		<?php if ( $is_link && ! empty( $shop_url ) ) : ?>
			<a href="<?php echo esc_url( $shop_url ); ?>">
				<?php echo esc_html( $shop_name ); ?>
			</a>
		<?php else : ?>
			<?php echo esc_html( $shop_name ); ?>
		<?php endif; ?>
	</<?php echo esc_attr( $tag_name ); ?>>
	<?php
	return ob_get_clean();
}
