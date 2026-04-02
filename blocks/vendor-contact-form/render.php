<?php
/**
 * Store contact form block render function.
 *
 * Uses Dokan's built-in contact form template for full compatibility
 * with Dokan's AJAX handling, reCAPTCHA, and privacy policy.
 *
 * @package AnotherBlocksDokan
 * @since 1.0.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Store contact form block render function.
 *
 * @param array<string, mixed> $attributes Block attributes.
 * @param string               $content    Block content.
 * @param WP_Block             $block      Block instance.
 * @return string Rendered HTML.
 */
function theabd_render_vendor_contact_form_block( array $attributes, string $content, WP_Block $block ): string {
	// Get vendor ID from attributes or context.
	$vendor_id = ! empty( $attributes['vendorId'] ) ? absint( $attributes['vendorId'] ) : 0;

	if ( ! $vendor_id ) {
		$vendor_id = \The_Another\Plugin\Blocks_Dokan\Helpers\Context_Detector::get_vendor_id();
	}

	if ( ! $vendor_id || ! dokan_is_user_seller( $vendor_id ) ) {
		return '';
	}

	// Check if contact form is enabled in Dokan settings.
	if ( 'on' !== dokan_get_option( 'contact_seller', 'dokan_general', 'on' ) ) {
		return '';
	}

	// Get current user info if logged in.
	$username = '';
	$email    = '';

	if ( is_user_logged_in() ) {
		$current_user = wp_get_current_user();
		$username     = $current_user->display_name;
		$email        = $current_user->user_email;
	}

	// Get store info for Dokan template.
	$store_info = dokan_get_store_info( $vendor_id );

	// Get wrapper attributes.
	$wrapper_attributes = get_block_wrapper_attributes(
		array(
			'class' => 'theabd--vendor-contact-form',
		)
	);

	ob_start();
	?>
	<div <?php echo $wrapper_attributes; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
		<?php
		// Use Dokan's contact form template for full compatibility.
		if ( function_exists( 'dokan_get_template_part' ) ) {
			dokan_get_template_part(
				'widgets/store-contact-form',
				'',
				array(
					'seller_id'  => $vendor_id,
					'store_info' => $store_info,
					'username'   => $username,
					'email'      => $email,
				)
			);
		}
		?>
	</div>
	<?php

	return ob_get_clean();
}
