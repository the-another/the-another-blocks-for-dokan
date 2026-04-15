<?php
/**
 * More from seller block render function.
 *
 * @package AnotherBlocksForDokan
 * @since 1.0.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * More from seller block render function.
 *
 * @param array<string, mixed> $attributes Block attributes.
 * @param string               $content    Block content.
 * @param WP_Block             $block      Block instance.
 * @return string Rendered HTML.
 */
function tanbfd_render_more_from_seller_block( array $attributes, string $content, WP_Block $block ): string {
	// Get product ID from attributes or context.
	$product_id = ! empty( $attributes['productId'] ) ? absint( $attributes['productId'] ) : 0;

	if ( ! $product_id ) {
		$product_id = \The_Another\Plugin\Blocks_For_Dokan\Helpers\Context_Detector::get_product_id();
	}

	if ( ! $product_id ) {
		return '';
	}

	// Get product.
	global $post;
	$product = wc_get_product( $product_id );
	if ( ! $product ) {
		return '';
	}

	// Get vendor ID from product author.
	$vendor_id = absint( get_post_field( 'post_author', $product->get_id() ) );
	if ( ! $vendor_id || ! dokan_is_user_seller( $vendor_id ) ) {
		return '';
	}

	// Extract attributes with defaults.
	$per_page = isset( $attributes['perPage'] ) ? absint( $attributes['perPage'] ) : 6;
	$columns  = isset( $attributes['columns'] ) ? absint( $attributes['columns'] ) : 4;
	$order_by = isset( $attributes['orderBy'] ) ? sanitize_text_field( $attributes['orderBy'] ) : 'rand';

	// Build query args.
	$query_args = array(
		'post_type'      => 'product',
		'posts_per_page' => $per_page,
		// phpcs:ignore WordPressVIPMinimum.Performance.WPQueryParams.PostNotIn_post__not_in
		'post__not_in'   => array( $product_id ),
		'author'         => $vendor_id,
		'post_status'    => 'publish',
	);

	// Add orderby using match expression.
	// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- Required for price/popularity sorting.
	[ $query_args['orderby'], $query_args['order'], $query_args['meta_key'] ] = match ( $order_by ) {
		'title'      => array( 'title', 'ASC', null ),
		'price'      => array( 'meta_value_num', 'ASC', '_price' ),
		'popularity' => array( 'meta_value_num', 'DESC', 'total_sales' ),
		'date'       => array( 'date', 'DESC', null ),
		default      => array( 'rand', '', null ),
	};

	// Remove meta_key if not needed.
	if ( null === $query_args['meta_key'] ) {
		unset( $query_args['meta_key'] );
	}

	/**
	 * Filter more from seller query arguments.
	 *
	 * @since 1.0.0
	 *
	 * @param array<string, mixed> $query_args Query arguments.
	 * @param array<string, mixed> $attributes Block attributes.
	 * @param int                  $vendor_id  Vendor ID.
	 * @param int                  $product_id Product ID.
	 */
	$query_args = apply_filters( 'tanbfd_more_from_seller_query_args', $query_args, $attributes, $vendor_id, $product_id );

	$products_query = new WP_Query( $query_args );

	// Get vendor data for store URL.
	$vendor_data = \The_Another\Plugin\Blocks_For_Dokan\Renderers\Vendor_Renderer::get_vendor_data( $vendor_id );

	// Get wrapper attributes.
	$wrapper_attributes = get_block_wrapper_attributes(
		array(
			'class' => "tanbfd--more-from-vendor tanbfd--more-from-vendor-columns-{$columns}",
		)
	);

	ob_start();

	if ( $products_query->have_posts() ) {
		?>
		<div <?php echo $wrapper_attributes; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
			<h2 class="tanbfd--more-from-vendor-title">
				<?php echo esc_html__( 'More from this seller', 'the-another-blocks-for-dokan' ); ?>
			</h2>

			<div class="woocommerce tanbfd--more-from-vendor-grid">
				<ul class="products columns-<?php echo esc_attr( $columns ); ?>">
					<?php
					while ( $products_query->have_posts() ) {
						$products_query->the_post();
						wc_get_template_part( 'content', 'product' );
					}
					wp_reset_postdata();
					?>
				</ul>
			</div>

			<?php if ( ! empty( $vendor_data['shop_url'] ) ) : ?>
				<div class="tanbfd--more-from-vendor-footer">
					<a href="<?php echo esc_url( $vendor_data['shop_url'] ); ?>" class="wp-element-button tanbfd--btn">
						<?php
						echo esc_html(
							sprintf(
								// translators: %s is the vendor store name.
								__( 'View all products from %s', 'the-another-blocks-for-dokan' ),
								$vendor_data['shop_name'] ?? __( 'this vendor', 'the-another-blocks-for-dokan' )
							)
						);
						?>
					</a>
				</div>
			<?php endif; ?>
		</div>
		<?php
	} else {
		?>
		<div <?php echo $wrapper_attributes; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
			<p class="tanbfd--more-from-vendor-empty">
				<?php echo esc_html__( 'No other products found from this seller.', 'the-another-blocks-for-dokan' ); ?>
			</p>
		</div>
		<?php
	}

	return ob_get_clean();
}
