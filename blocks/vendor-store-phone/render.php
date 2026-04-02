<?php
/**
 * Store phone block render function.
 *
 * @package AnotherBlocksDokan
 * @since 1.0.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Store phone block render function.
 *
 * @param array<string, mixed> $attributes Block attributes.
 * @param string               $content    Block content.
 * @param WP_Block             $block      Block instance.
 * @return string Rendered HTML.
 */
function theabd_render_vendor_store_phone_block( array $attributes, string $content, WP_Block $block ): string {
	// Get vendor data from context.
	$vendor = $block->context['dokan/vendor'] ?? null;

	// If no vendor in context, try to detect from current page.
	if ( empty( $vendor ) || empty( $vendor['id'] ) ) {
		$vendor_id = \The_Another\Plugin\Blocks_Dokan\Helpers\Context_Detector::get_vendor_id();

		if ( $vendor_id > 0 ) {
			$vendor_data = \The_Another\Plugin\Blocks_Dokan\Renderers\Vendor_Renderer::get_vendor_data( $vendor_id );
			if ( $vendor_data ) {
				$vendor = array(
					'id'    => $vendor_data['id'],
					'phone' => $vendor_data['phone'] ?? '',
				);
			}
		}
	}

	if ( empty( $vendor ) || empty( $vendor['id'] ) ) {
		return '<p class="theabd--vendor-store-phone">+1 234 567 8900</p>';
	}

	$phone     = $vendor['phone'] ?? '';
	$show_icon = $attributes['showIcon'] ?? true;
	$is_link   = $attributes['isLink'] ?? true;

	// If no phone, return empty.
	if ( empty( $phone ) ) {
		return '';
	}

	// Get wrapper attributes.
	$wrapper_attributes = get_block_wrapper_attributes(
		array(
			'class' => 'theabd--vendor-store-phone',
		)
	);

	// Clean phone for tel: link.
	$phone_clean = preg_replace( '/[^0-9+]/', '', $phone );

	ob_start();
	?>
	<p <?php echo $wrapper_attributes; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
		<?php if ( $show_icon ) : ?>
			<span class="dashicons dashicons-phone" aria-hidden="true"></span>
		<?php endif; ?>
		<?php if ( $is_link ) : ?>
			<a href="tel:<?php echo esc_attr( $phone_clean ); ?>">
				<?php echo esc_html( $phone ); ?>
			</a>
		<?php else : ?>
			<?php echo esc_html( $phone ); ?>
		<?php endif; ?>
	</p>
	<?php
	return ob_get_clean();
}
