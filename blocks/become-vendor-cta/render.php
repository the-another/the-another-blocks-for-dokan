<?php
/**
 * Become vendor CTA block render function.
 *
 * @package AnotherBlocksDokan
 * @since 1.0.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Become vendor CTA block render function.
 *
 * @param array<string, mixed> $attributes Block attributes.
 * @param string               $content    Block content.
 * @param WP_Block             $block      Block instance.
 * @return string Rendered HTML.
 */
function theabd_render_become_vendor_cta_block( array $attributes, string $content, WP_Block $block ): string {
	// Only show to non-vendors.
	if ( dokan_is_user_seller( get_current_user_id() ) ) {
		return '';
	}

	// Extract attributes with defaults.
	$heading     = isset( $attributes['heading'] ) ? sanitize_text_field( $attributes['heading'] ) : __( 'Become a Vendor', 'another-dokan-blocks' );
	$description = isset( $attributes['description'] ) ? sanitize_textarea_field( $attributes['description'] ) : __( 'Vendors can sell products and manage a store with a vendor dashboard.', 'another-dokan-blocks' );
	$button_text = isset( $attributes['buttonText'] ) ? sanitize_text_field( $attributes['buttonText'] ) : __( 'Become a Vendor', 'another-dokan-blocks' );
	$button_link = isset( $attributes['buttonLink'] ) ? esc_url_raw( $attributes['buttonLink'] ) : '';

	// Get default link if not provided.
	if ( empty( $button_link ) ) {
		$button_link = dokan_get_page_url( 'myaccount', 'woocommerce', 'account-migration' );
	}

	if ( empty( $button_link ) ) {
		return '';
	}

	// Get wrapper attributes.
	$wrapper_attributes = get_block_wrapper_attributes(
		array(
			'class' => 'theabd--become-vendor-cta',
		)
	);

	ob_start();
	?>
	<div <?php echo $wrapper_attributes; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
		<ul class="theabd--account-migration-lists">
			<li>
				<div class="theabd--w8 theabd--left-content">
					<p><strong><?php echo esc_html( $heading ); ?></strong></p>
					<p><?php echo esc_html( $description ); ?></p>
				</div>
				<div class="theabd--w4 theabd--right-content">
					<a href="<?php echo esc_url( $button_link ); ?>" class="theabd--btn theabd--btn-primary">
						<?php echo esc_html( $button_text ); ?>
					</a>
				</div>
				<div class="theabd--clearfix"></div>
			</li>
		</ul>
	</div>
	<?php

	return ob_get_clean();
}
