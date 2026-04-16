<?php
/**
 * Store status block render function.
 *
 * @package AnotherBlocksForDokan
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
function tanbfd_render_vendor_store_status_block( array $attributes, string $content, WP_Block $block ): string {
	// Get vendor data from context.
	$vendor = $block->context['dokan/vendor'] ?? null;

	// If no vendor in context, try to detect from current page.
	if ( empty( $vendor ) || empty( $vendor['id'] ) ) {
		$vendor_id = tanbfd_get_vendor_id();

		if ( $vendor_id > 0 ) {
			$vendor_data = \The_Another\Plugin\Blocks_For_Dokan\Renderers\Vendor_Renderer::get_vendor_data( $vendor_id );
			if ( $vendor_data ) {
				$vendor = array(
					'id'               => $vendor_data['id'],
					'store_open_close' => $vendor_data['store_info']['store_open_close'] ?? array(),
				);
			}
		}
	}

	if ( empty( $vendor ) || empty( $vendor['id'] ) ) {
		return '<span class="tanbfd--vendor-store-status tanbfd--store-open">' . esc_html__( 'Open Now', 'the-another-blocks-for-dokan' ) . '</span>';
	}

	$vendor_id = absint( $vendor['id'] );

	// Check if store is open using context data when available, avoiding a DB query.
	if ( ! empty( $vendor['store_open_close'] ) ) {
		$is_store_open = \The_Another\Plugin\Blocks_For_Dokan\Renderers\Vendor_Renderer::is_store_open_from_context( $vendor['store_open_close'], $vendor_id );
	} else {
		$is_store_open = function_exists( 'dokan_is_store_open' ) ? dokan_is_store_open( $vendor_id ) : true;
	}

	// Get custom notices from vendor data.
	$store_open_close = $vendor['store_open_close'] ?? array();
	$open_notice      = $store_open_close['open_notice'] ?? __( 'Open Now', 'the-another-blocks-for-dokan' );
	$close_notice     = $store_open_close['close_notice'] ?? __( 'Closed', 'the-another-blocks-for-dokan' );

	// Get wrapper attributes.
	$status_class       = $is_store_open ? 'tanbfd--store-open' : 'tanbfd--store-closed';
	$wrapper_attributes = get_block_wrapper_attributes(
		array(
			'class' => "tanbfd--vendor-store-status {$status_class}",
		)
	);

	$status_text = $is_store_open ? $open_notice : $close_notice;

	ob_start();
	?>
	<span <?php echo wp_kses_post( $wrapper_attributes ); ?>>
		<?php echo esc_html( $status_text ); ?>
	</span>
	<?php
	return ob_get_clean();
}
