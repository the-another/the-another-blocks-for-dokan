<?php
/**
 * Vendor Logo block render function.
 *
 * @package AnotherBlocksDokan
 * @since 1.0.0
 */

use The_Another\Plugin\Blocks_Dokan\Helpers\Context_Detector;
use The_Another\Plugin\Blocks_Dokan\Renderers\Vendor_Renderer;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Vendor Logo block render function.
 *
 * @param array<string, mixed> $attributes Block attributes.
 * @param string               $content    Block content.
 * @param WP_Block             $block      Block instance.
 * @return string Rendered HTML.
 */
function theabd_render_vendor_avatar_block( array $attributes, string $content, WP_Block $block ): string {
	// Get vendor data from context.
	$vendor = $block->context['dokan/vendor'] ?? null;

	// If no vendor in context, try to detect from current page.
	if ( empty( $vendor ) || empty( $vendor['id'] ) ) {
		$vendor_id = Context_Detector::get_vendor_id();

		if ( $vendor_id > 0 ) {
			$vendor_data = Vendor_Renderer::get_vendor_data( $vendor_id );
			if ( $vendor_data ) {
				$vendor = array(
					'id'         => $vendor_data['id'],
					'store_name' => $vendor_data['shop_name'] ?? '',
					'shop_url'   => $vendor_data['shop_url'] ?? '',
					'gravatar'   => $vendor_data['avatar'] ?? '',
				);
			}
		}
	}

	if ( empty( $vendor ) || empty( $vendor['id'] ) ) {
		return '<div class="theabd--vendor-avatar"><img src="' . esc_url( get_avatar_url( 0 ) ) . '" alt="" /></div>';
	}

	$shop_url   = $vendor['shop_url'] ?? '';
	$shop_name  = $vendor['store_name'] ?? '';
	$avatar_url = $vendor['gravatar'] ?? $vendor['avatar'] ?? get_avatar_url( $vendor['id'] ?? 0 );
	$width      = $attributes['width'] ?? '80px';
	$height     = $attributes['height'] ?? '80px';
	$is_link    = $attributes['isLink'] ?? true;
	$align      = $attributes['align'] ?? '';

	// Build wrapper classes.
	$wrapper_classes = array( 'theabd--vendor-avatar' );
	if ( ! empty( $align ) ) {
		$wrapper_classes[] = 'has-text-align-' . $align;
	}

	// Build wrapper styles to ensure border-radius clips properly.
	$wrapper_styles = array();

	// Check if border radius is set in style attribute and apply it.
	$border_radius = null;
	if ( ! empty( $attributes['style']['border']['radius'] ) ) {
		$border_radius = $attributes['style']['border']['radius'];
		$wrapper_styles[] = 'border-radius: ' . esc_attr( $border_radius );
		$wrapper_styles[] = 'overflow: hidden';
	}

	// Get wrapper attributes without style (to avoid conflicts).
	$wrapper_attributes = get_block_wrapper_attributes(
		array(
			'class' => implode( ' ', $wrapper_classes ),
		)
	);

	// Build style attribute separately to avoid WordPress merging issues.
	$wrapper_style_attr = '';
	if ( ! empty( $wrapper_styles ) ) {
		$wrapper_style_attr = ' style="' . esc_attr( implode( '; ', $wrapper_styles ) ) . '"';
	}

	// Build image styles.
	$img_styles = array();
	if ( ! empty( $width ) ) {
		$img_styles[] = 'width: ' . esc_attr( $width );
	}
	if ( ! empty( $height ) ) {
		$img_styles[] = 'height: ' . esc_attr( $height );
	}
	$img_styles[] = 'object-fit: cover';

	// Apply border radius to image as well for better browser support.
	if ( $border_radius ) {
		$img_styles[] = 'border-radius: ' . esc_attr( $border_radius );
	}

	$img_style_attr = ! empty( $img_styles ) ? 'style="' . implode( '; ', $img_styles ) . ';"' : '';

	ob_start();
	?>
	<div <?php echo $wrapper_attributes; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?><?php echo $wrapper_style_attr; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
		<?php if ( $is_link && ! empty( $shop_url ) ) : ?>
			<a href="<?php echo esc_url( $shop_url ); ?>" class="theabd--vendor-avatar-link">
				<img
					src="<?php echo esc_url( $avatar_url ); ?>"
					alt="<?php echo esc_attr( $shop_name ); ?>"
					class="theabd--vendor-avatar-image"
					<?php echo $img_style_attr; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				/>
			</a>
		<?php else : ?>
			<img
				src="<?php echo esc_url( $avatar_url ); ?>"
				alt="<?php echo esc_attr( $shop_name ); ?>"
				class="theabd--vendor-avatar-image"
				<?php echo $img_style_attr; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			/>
		<?php endif; ?>
	</div>
	<?php
	return ob_get_clean();
}
