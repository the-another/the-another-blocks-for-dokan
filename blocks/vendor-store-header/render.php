<?php
/**
 * Store header block render function.
 *
 * @package AnotherBlocksForDokan
 * @since 1.0.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Store header block render function.
 *
 * @param array<string, mixed> $attributes Block attributes.
 * @param string               $content    Block content.
 * @param WP_Block             $block      Block instance.
 * @return string Rendered HTML.
 */
function tanbfd_render_vendor_store_header_block( array $attributes, string $content, WP_Block $block ): string {
	// Get vendor ID from attributes or context.
	$vendor_id = ! empty( $attributes['vendorId'] ) ? absint( $attributes['vendorId'] ) : 0;

	if ( ! $vendor_id ) {
		// Auto-detect from context.
		$vendor_id = tanbfd_get_vendor_id();
	}

	if ( ! $vendor_id || ! dokan_is_user_seller( $vendor_id ) ) {
		return '';
	}

	// Get vendor data.
	$vendor_data = \The_Another\Plugin\Blocks_For_Dokan\Renderers\Vendor_Renderer::get_vendor_data( $vendor_id );
	if ( ! $vendor_data ) {
		return '';
	}

	// Extract attributes with defaults.
	$show_banner       = $attributes['showBanner'] ?? true;
	$show_contact_info = $attributes['showContactInfo'] ?? true;
	$show_social_links = $attributes['showSocialLinks'] ?? true;
	$show_store_hours  = $attributes['showStoreHours'] ?? true;
	$layout            = isset( $attributes['layout'] ) ? sanitize_text_field( $attributes['layout'] ) : 'default';

	// Get wrapper attributes.
	$wrapper_attributes = get_block_wrapper_attributes(
		array(
			'class' => "tanbfd--vendor-store-header tanbfd--vendor-store-header-{$layout}",
		)
	);

	ob_start();
	?>
	<div <?php echo wp_kses_post( $wrapper_attributes ); ?>>
		<?php if ( $show_banner && ! empty( $vendor_data['banner'] ) ) : ?>
			<div class="tanbfd--vendor-store-banner">
				<img src="<?php echo esc_url( $vendor_data['banner'] ); ?>" alt="<?php echo esc_attr( $vendor_data['shop_name'] ); ?>" />
			</div>
		<?php endif; ?>

		<div class="tanbfd--store-info">
			<?php if ( ! empty( $vendor_data['avatar'] ) ) : ?>
				<div class="tanbfd--vendor-avatar">
					<img src="<?php echo esc_url( $vendor_data['avatar'] ); ?>" alt="<?php echo esc_attr( $vendor_data['shop_name'] ); ?>" />
				</div>
			<?php endif; ?>

			<h1 class="tanbfd--vendor-store-name">
				<?php echo esc_html( $vendor_data['shop_name'] ); ?>
			</h1>

			<?php if ( $show_contact_info ) : ?>
				<ul class="tanbfd--store-contact-info">
					<?php if ( ! \The_Another\Plugin\Blocks_For_Dokan\Renderers\Vendor_Renderer::is_vendor_info_hidden( 'address' ) && ! empty( $vendor_data['address'] ) ) : ?>
						<li class="tanbfd--vendor-store-address">
							<?php echo wp_kses_post( $vendor_data['address'] ); ?>
						</li>
					<?php endif; ?>

					<?php if ( ! \The_Another\Plugin\Blocks_For_Dokan\Renderers\Vendor_Renderer::is_vendor_info_hidden( 'phone' ) && ! empty( $vendor_data['phone'] ) ) : ?>
						<li class="tanbfd--vendor-store-phone">
							<i class="fas fa-phone-alt"></i>
							<a href="tel:<?php echo esc_attr( $vendor_data['phone'] ); ?>">
								<?php echo esc_html( $vendor_data['phone'] ); ?>
							</a>
						</li>
					<?php endif; ?>

					<?php if ( ! \The_Another\Plugin\Blocks_For_Dokan\Renderers\Vendor_Renderer::is_vendor_info_hidden( 'email' ) && ! empty( $vendor_data['email'] ) ) : ?>
						<li class="tanbfd--store-email">
							<i class="far fa-envelope"></i>
							<a href="mailto:<?php echo esc_attr( antispambot( $vendor_data['email'] ) ); ?>">
								<?php echo esc_html( antispambot( $vendor_data['email'] ) ); ?>
							</a>
						</li>
					<?php endif; ?>

					<li class="tanbfd--vendor-rating">
						<i class="fas fa-star"></i>
						<?php echo wp_kses_post( \The_Another\Plugin\Blocks_For_Dokan\Renderers\Vendor_Renderer::get_seller_rating_html( $vendor_id ) ); ?>
					</li>

					<?php if ( $show_store_hours ) : ?>
						<li class="tanbfd--vendor-store-hours">
							<i class="fas fa-clock"></i>
							<?php
							if ( \The_Another\Plugin\Blocks_For_Dokan\Renderers\Vendor_Renderer::is_store_open( $vendor_id ) ) {
								echo '<span class="tanbfd--store-open">' . esc_html__( 'Store Open', 'the-another-blocks-for-dokan' ) . '</span>';
							} else {
								echo '<span class="tanbfd--store-closed">' . esc_html__( 'Store Closed', 'the-another-blocks-for-dokan' ) . '</span>';
							}
							?>
						</li>
					<?php endif; ?>
				</ul>
			<?php endif; ?>

			<?php if ( $show_social_links && ! empty( $vendor_data['social_profiles'] ) ) : ?>
				<?php
				$social_fields = dokan_get_social_profile_fields();
				if ( ! empty( $social_fields ) ) {
					?>
					<ul class="tanbfd--store-social-links">
						<?php
						foreach ( $social_fields as $key => $field ) {
							if ( ! empty( $vendor_data['social_profiles'][ $key ] ) ) {
								?>
								<li>
									<a href="<?php echo esc_url( $vendor_data['social_profiles'][ $key ] ); ?>" target="_blank" rel="noopener noreferrer">
										<span class="tanbfd--social-icon tanbfd--social-<?php echo esc_attr( $key ); ?>">
											<?php echo esc_html( $field['title'] ?? $key ); ?>
										</span>
									</a>
								</li>
								<?php
							}
						}
						?>
					</ul>
					<?php
				}
				?>
			<?php endif; ?>
		</div>
	</div>
	<?php

	return ob_get_clean();
}
