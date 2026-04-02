<?php
/**
 * Store rating block render function.
 *
 * @package AnotherBlocksDokan
 * @since 1.0.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Store rating block render function.
 *
 * @param array<string, mixed> $attributes Block attributes.
 * @param string               $content    Block content.
 * @param WP_Block             $block      Block instance.
 * @return string Rendered HTML.
 */
function theabd_render_vendor_rating_block( array $attributes, string $content, WP_Block $block ): string {
	// Get vendor data from context.
	$vendor = $block->context['dokan/vendor'] ?? null;

	// If no vendor in context, try to detect from current page.
	if ( empty( $vendor ) || empty( $vendor['id'] ) ) {
		$vendor_id = \The_Another\Plugin\Blocks_Dokan\Helpers\Context_Detector::get_vendor_id();

		if ( $vendor_id > 0 ) {
			$vendor_data = \The_Another\Plugin\Blocks_Dokan\Renderers\Vendor_Renderer::get_vendor_data( $vendor_id );
			if ( $vendor_data ) {
				$vendor = array(
					'id'     => $vendor_data['id'],
					'rating' => $vendor_data['rating'] ?? array( 'rating' => 0, 'count' => 0 ),
				);
			}
		}
	}

	if ( empty( $vendor ) || empty( $vendor['id'] ) ) {
		return '<div class="theabd--vendor-rating">★★★★★ (0)</div>';
	}

	$rating      = $vendor['rating']['rating'] ?? 0;
	$count       = $vendor['rating']['count'] ?? 0;
	$show_count  = $attributes['showCount'] ?? true;

	// Get wrapper attributes.
	$wrapper_attributes = get_block_wrapper_attributes(
		array(
			'class' => 'theabd--vendor-rating',
		)
	);

	ob_start();
	?>
	<div <?php echo $wrapper_attributes; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
		<?php
		if ( function_exists( 'dokan_generate_ratings' ) ) {
			echo dokan_generate_ratings( $rating, $count ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		} else {
			// Fallback rating display.
			$percentage = ( $rating / 5 ) * 100;
			?>
			<?php /* translators: %s: rating value (e.g., 4.5) */ ?>
			<div class="theabd--star-rating" role="img" aria-label="<?php echo esc_attr( sprintf( __( 'Rated %s out of 5', 'another-dokan-blocks' ), $rating ) ); ?>">
				<span style="width:<?php echo esc_attr( $percentage ); ?>%">
					<?php /* translators: %s: rating value (e.g., 4.5) */ ?>
					<?php echo esc_html( sprintf( __( 'Rated %s out of 5', 'another-dokan-blocks' ), $rating ) ); ?>
				</span>
			</div>
			<?php if ( $show_count ) : ?>
				<span class="theabd--rating-count">(<?php echo esc_html( $count ); ?>)</span>
			<?php endif; ?>
			<?php
		}
		?>
	</div>
	<?php
	return ob_get_clean();
}
