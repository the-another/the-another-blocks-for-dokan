<?php
/**
 * Store phone block render function.
 *
 * @package AnotherBlocksForDokan
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
function tanbfd_render_vendor_store_phone_block( array $attributes, string $content, WP_Block $block ): string {
	// Get vendor data from context, falling back to page context detection.
	$vendor = \The_Another\Plugin\Blocks_For_Dokan\Renderers\Vendor_Renderer::resolve_vendor_from_context(
		$block->context['dokan/vendor'] ?? null,
		array(
			'phone' => 'phone',
		)
	);

	if ( empty( $vendor ) || empty( $vendor['id'] ) ) {
		return '<p class="tanbfd--vendor-store-phone">+1 234 567 8900</p>';
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
			'class' => 'tanbfd--vendor-store-phone',
		)
	);

	// Clean phone for tel: link.
	$phone_clean = preg_replace( '/[^0-9+]/', '', $phone );

	ob_start();
	?>
	<p <?php echo wp_kses_post( $wrapper_attributes ); ?>>
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
