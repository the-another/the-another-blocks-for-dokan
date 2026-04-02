<?php
/**
 * Store terms and conditions block render function.
 *
 * @package AnotherBlocksDokan
 * @since 1.0.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Store terms and conditions block render function.
 *
 * @param array<string, mixed> $attributes Block attributes.
 * @param string               $content    Block content.
 * @param WP_Block             $block      Block instance.
 * @return string Rendered HTML.
 */
function theabd_render_vendor_store_terms_conditions_block( array $attributes, string $content, WP_Block $block ): string {
	// Get vendor ID from attributes or context.
	$vendor_id = ! empty( $attributes['vendorId'] ) ? absint( $attributes['vendorId'] ) : 0;

	if ( ! $vendor_id ) {
		$vendor_id = \The_Another\Plugin\Blocks_Dokan\Helpers\Context_Detector::get_vendor_id();
	}

	if ( ! $vendor_id || ! dokan_is_user_seller( $vendor_id ) ) {
		return '';
	}

	// Get vendor object.
	if ( ! function_exists( 'dokan' ) ) {
		return '';
	}

	$vendor = dokan()->vendor->get( $vendor_id );
	if ( ! $vendor || ! $vendor->get_id() ) {
		return '';
	}

	// Get terms and conditions content.
	$tnc_content = $vendor->get_store_tnc();
	if ( empty( $tnc_content ) ) {
		return '';
	}

	// Get attributes with defaults.
	$show_title = isset( $attributes['showTitle'] ) ? (bool) $attributes['showTitle'] : true;
	$title_tag  = isset( $attributes['titleTag'] ) ? sanitize_key( $attributes['titleTag'] ) : 'h2';

	// Validate title tag.
	$allowed_tags = array( 'h1', 'h2', 'h3', 'h4', 'h5', 'h6' );
	if ( ! in_array( $title_tag, $allowed_tags, true ) ) {
		$title_tag = 'h2';
	}

	// Get wrapper attributes.
	$wrapper_attributes = get_block_wrapper_attributes(
		array(
			'class' => 'theabd--vendor-store-terms-conditions',
		)
	);

	ob_start();
	?>
	<div <?php echo $wrapper_attributes; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
		<div id="theabd--store-toc-wrapper">
			<div id="theabd--store-toc">
				<?php if ( $show_title ) : ?>
					<<?php echo esc_attr( $title_tag ); ?> class="theabd--store-toc-title theabd--headline">
						<?php esc_html_e( 'Terms and Conditions', 'another-dokan-blocks' ); ?>
					</<?php echo esc_attr( $title_tag ); ?>>
				<?php endif; ?>
				<div class="theabd--store-toc-content">
					<?php echo wp_kses_post( wpautop( wptexturize( $tnc_content ) ) ); ?>
				</div>
			</div>
		</div>
	</div>
	<?php

	return ob_get_clean();
}
