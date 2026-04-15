<?php
/**
 * Store name block render function.
 *
 * @package AnotherBlocksForDokan
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
function tanbfd_render_vendor_store_name_block( array $attributes, string $content, WP_Block $block ): string {
	// Get vendor data from context, falling back to page context detection.
	$vendor = \The_Another\Plugin\Blocks_For_Dokan\Renderers\Vendor_Renderer::resolve_vendor_from_context(
		$block->context['dokan/vendor'] ?? null,
		array(
			'store_name' => 'shop_name',
			'shop_url'   => 'shop_url',
		)
	);

	if ( empty( $vendor ) || empty( $vendor['id'] ) ) {
		return '<p>' . esc_html__( 'Store Name', 'the-another-blocks-for-dokan' ) . '</p>';
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

	// WordPress handles color, typography, and spacing styles automatically via block.json supports.
	$wrapper_attributes = get_block_wrapper_attributes(
		array(
			'class' => 'tanbfd--vendor-store-name',
		)
	);

	ob_start();
	?>
	<<?php echo esc_attr( $tag_name ); ?> <?php echo $wrapper_attributes; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
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
