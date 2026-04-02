<?php
/**
 * Store address block render function.
 *
 * @package AnotherBlocksDokan
 * @since 1.0.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Format address array into a string.
 *
 * @param array $address Address array.
 * @return string Formatted address.
 */
function dokan_blocks_format_address( array $address ): string {
	$parts = array();

	if ( ! empty( $address['street_1'] ) ) {
		$parts[] = $address['street_1'];
	}
	if ( ! empty( $address['street_2'] ) ) {
		$parts[] = $address['street_2'];
	}

	$city_state_zip = array();
	if ( ! empty( $address['city'] ) ) {
		$city_state_zip[] = $address['city'];
	}
	if ( ! empty( $address['state'] ) ) {
		$city_state_zip[] = $address['state'];
	}
	if ( ! empty( $address['zip'] ) ) {
		$city_state_zip[] = $address['zip'];
	}

	if ( ! empty( $city_state_zip ) ) {
		$parts[] = implode( ', ', $city_state_zip );
	}

	if ( ! empty( $address['country'] ) ) {
		$parts[] = $address['country'];
	}

	return ! empty( $parts ) ? implode( ', ', $parts ) : '';
}

/**
 * Store address block render function.
 *
 * @param array<string, mixed> $attributes Block attributes.
 * @param string               $content    Block content.
 * @param WP_Block             $block      Block instance.
 * @return string Rendered HTML.
 */
function theabd_render_vendor_store_address_block( array $attributes, string $content, WP_Block $block ): string {
	// Get vendor data from context.
	$vendor = $block->context['dokan/vendor'] ?? null;

	// If no vendor in context, try to detect from current page.
	if ( empty( $vendor ) || empty( $vendor['id'] ) ) {
		$vendor_id = \The_Another\Plugin\Blocks_Dokan\Helpers\Context_Detector::get_vendor_id();

		if ( $vendor_id > 0 ) {
			$vendor_data = \The_Another\Plugin\Blocks_Dokan\Renderers\Vendor_Renderer::get_vendor_data( $vendor_id );
			if ( $vendor_data ) {
				$vendor = array(
					'id'      => $vendor_data['id'],
					'address' => $vendor_data['address'] ?? '',
				);
			}
		}
	}

	if ( empty( $vendor ) || empty( $vendor['id'] ) ) {
		return '<p class="theabd--vendor-store-address">123 Main St, City, Country</p>';
	}

	$address   = $vendor['address'] ?? array();
	$show_icon = $attributes['showIcon'] ?? true;

	// Format the address.
	$formatted_address = '';
	if ( is_array( $address ) ) {
		$formatted_address = dokan_blocks_format_address( $address );
	} elseif ( is_string( $address ) ) {
		$formatted_address = $address;
	}

	// If no address, return empty.
	if ( empty( $formatted_address ) ) {
		return '';
	}

	// Get wrapper attributes.
	$wrapper_attributes = get_block_wrapper_attributes(
		array(
			'class' => 'theabd--vendor-store-address',
		)
	);

	ob_start();
	?>
	<p <?php echo $wrapper_attributes; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
		<?php if ( $show_icon ) : ?>
			<span class="dashicons dashicons-location" aria-hidden="true"></span>
		<?php endif; ?>
		<?php echo wp_kses_post( $formatted_address ); ?>
	</p>
	<?php
	return ob_get_clean();
}
