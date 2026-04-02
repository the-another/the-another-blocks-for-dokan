<?php
/**
 * Store status block render function.
 *
 * @package AnotherBlocksDokan
 * @since 1.0.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Store status block render function.
 *
 * @param array<string, mixed> $attributes Block attributes.
 * @param string               $content    Block content.
 * @param WP_Block             $block      Block instance.
 * @return string Rendered HTML.
 */
function theabd_render_vendor_store_status_block( array $attributes, string $content, WP_Block $block ): string {
	// Get vendor data from context.
	$vendor = $block->context['dokan/vendor'] ?? null;

	// If no vendor in context, try to detect from current page.
	if ( empty( $vendor ) || empty( $vendor['id'] ) ) {
		$vendor_id = \The_Another\Plugin\Blocks_Dokan\Helpers\Context_Detector::get_vendor_id();

		if ( $vendor_id > 0 ) {
			$vendor_data = \The_Another\Plugin\Blocks_Dokan\Renderers\Vendor_Renderer::get_vendor_data( $vendor_id );
			if ( $vendor_data ) {
				$vendor = array(
					'id'               => $vendor_data['id'],
					'store_open_close' => $vendor_data['store_info']['store_open_close'] ?? array(),
				);
			}
		}
	}

	if ( empty( $vendor ) || empty( $vendor['id'] ) ) {
		return '<span class="theabd--vendor-store-status theabd--store-open">' . esc_html__( 'Open Now', 'another-dokan-blocks' ) . '</span>';
	}

	$vendor_id = absint( $vendor['id'] );

	// Check if store is open (this needs to be calculated in real-time).
	$is_store_open = function_exists( 'dokan_is_store_open' ) ? dokan_is_store_open( $vendor_id ) : true;

	// Get custom notices from vendor data.
	$store_open_close = $vendor['store_open_close'] ?? array();
	$open_notice      = $store_open_close['open_notice'] ?? __( 'Open Now', 'another-dokan-blocks' );
	$close_notice     = $store_open_close['close_notice'] ?? __( 'Closed', 'another-dokan-blocks' );

	// Get wrapper attributes.
	$status_class       = $is_store_open ? 'theabd--store-open' : 'theabd--store-closed';
	$wrapper_attributes = get_block_wrapper_attributes(
		array(
			'class' => "theabd--vendor-store-status {$status_class}",
		)
	);

	$status_text = $is_store_open ? $open_notice : $close_notice;

	ob_start();
	?>
	<span <?php echo $wrapper_attributes; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
		<?php echo esc_html( $status_text ); ?>
	</span>
	<?php
	return ob_get_clean();
}
