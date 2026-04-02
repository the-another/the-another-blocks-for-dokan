<?php
/**
 * Store location block render function.
 *
 * @package AnotherBlocksDokan
 * @since 1.0.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Store location block render function.
 *
 * @param array<string, mixed> $attributes Block attributes.
 * @param string               $content    Block content.
 * @param WP_Block             $block      Block instance.
 * @return string Rendered HTML.
 */
function theabd_render_vendor_store_location_block( array $attributes, string $content, WP_Block $block ): string {
	// Get vendor ID from attributes or context.
	$vendor_id = ! empty( $attributes['vendorId'] ) ? absint( $attributes['vendorId'] ) : 0;

	if ( ! $vendor_id ) {
		$vendor_id = \The_Another\Plugin\Blocks_Dokan\Helpers\Context_Detector::get_vendor_id();
	}

	if ( ! $vendor_id || ! dokan_is_user_seller( $vendor_id ) ) {
		return '';
	}

	// Get vendor location.
	$vendor = dokan()->vendor->get( $vendor_id );
	if ( ! $vendor ) {
		return '';
	}

	$map_location = $vendor->get_location();
	if ( empty( $map_location ) ) {
		return '';
	}

	// get_location() returns a string like "lat,lng" or location data.
	// Get store info for address.
	$store_info = $vendor->get_shop_info();
	$address    = isset( $store_info['address'] ) ? $store_info['address'] : '';

	// Parse location string (format: "lat,lng").
	$lat = '';
	$lng = '';

	if ( is_string( $map_location ) && strpos( $map_location, ',' ) !== false ) {
		list( $lat, $lng ) = explode( ',', $map_location, 2 );
		$lat               = trim( $lat );
		$lng               = trim( $lng );
	} elseif ( is_array( $map_location ) ) {
		$lat     = $map_location['lat'] ?? $map_location['latitude'] ?? '';
		$lng     = $map_location['lng'] ?? $map_location['longitude'] ?? '';
		$address = $map_location['address'] ?? $address;
	}

	if ( empty( $address ) && empty( $lat ) && empty( $lng ) ) {
		return '';
	}

	// Extract attributes with defaults.
	$map_provider = isset( $attributes['mapProvider'] ) ? sanitize_text_field( $attributes['mapProvider'] ) : 'google';
	$height       = isset( $attributes['height'] ) ? absint( $attributes['height'] ) : 400;
	$zoom         = isset( $attributes['zoom'] ) ? absint( $attributes['zoom'] ) : 10;

	// Get wrapper attributes.
	$wrapper_attributes = get_block_wrapper_attributes(
		array(
			'class' => "theabd--vendor-store-location theabd--vendor-store-location-{$map_provider}",
			'style' => "height: {$height}px;",
		)
	);

	ob_start();

	// Use Dokan's map template if available.
	if ( function_exists( 'dokan_get_template_part' ) ) {
		$template_args = array(
			'map_location' => $map_location,
			'map_provider' => $map_provider,
			'height'       => $height,
			'zoom'         => $zoom,
		);

		?>
		<div <?php echo $wrapper_attributes; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
			<?php dokan_get_template_part( 'widgets/store-map', $map_provider, $template_args ); ?>
		</div>
		<?php
	} else {
		// Fallback: simple map embed (address, lat, lng already extracted above)

		if ( 'mapbox' === $map_provider && ! empty( $lat ) && ! empty( $lng ) ) {
			$mapbox_token = get_option( 'dokan_mapbox_token', '' );
			if ( ! empty( $mapbox_token ) ) {
				?>
				<div <?php echo $wrapper_attributes; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
					<iframe
						width="100%"
						height="<?php echo esc_attr( $height ); ?>"
						frameborder="0"
						style="border:0"
						src="https://api.mapbox.com/styles/v1/mapbox/streets-v11/static/pin-s+ff0000(<?php echo esc_attr( $lng ); ?>,<?php echo esc_attr( $lat ); ?>)/<?php echo esc_attr( $lng ); ?>,<?php echo esc_attr( $lat ); ?>,<?php echo esc_attr( $zoom ); ?>/600x<?php echo esc_attr( $height ); ?>?access_token=<?php echo esc_attr( $mapbox_token ); ?>"
						allowfullscreen>
					</iframe>
				</div>
				<?php
			}
		} elseif ( 'google' === $map_provider ) {
			$google_api_key  = get_option( 'dokan_google_api_key', '' );
			$address_encoded = rawurlencode( $address );
			?>
			<div <?php echo $wrapper_attributes; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
				<iframe
					width="100%"
					height="<?php echo esc_attr( $height ); ?>"
					frameborder="0"
					style="border:0"
					src="https://www.google.com/maps/embed/v1/place?key=<?php echo esc_attr( $google_api_key ); ?>&q=<?php echo esc_attr( $address_encoded ); ?>&zoom=<?php echo esc_attr( $zoom ); ?>"
					allowfullscreen>
				</iframe>
			</div>
			<?php
		}
	}

	return ob_get_clean();
}
