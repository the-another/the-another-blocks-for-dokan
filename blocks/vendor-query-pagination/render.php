<?php
/**
 * Store Query Pagination block render function.
 *
 * @package AnotherBlocksDokan
 * @since 1.0.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Store Query Pagination block render function.
 *
 * @param array<string, mixed> $attributes Block attributes.
 * @param string               $content    Block content.
 * @param WP_Block             $block      Block instance.
 * @return string Rendered HTML.
 */
function theabd_render_vendor_query_pagination_block( array $attributes, string $content, WP_Block $block ): string {
	// Get context from parent store query loop block.
	$query_id = $block->context['dokan/queryId'] ?? null;
	$query    = $block->context['dokan/query'] ?? null;

	// If no query context, return empty.
	if ( ! $query_id || ! $query ) {
		return '';
	}

	// Extract pagination data from query context.
	$total_pages = $query['totalPages'] ?? 0;
	$current_page = $query['currentPage'] ?? 1;

	// If only one page or no pages, don't show pagination.
	if ( $total_pages <= 1 ) {
		return '';
	}

	// Extract attributes with defaults.
	$pagination_arrow = isset( $attributes['paginationArrow'] ) ? sanitize_text_field( $attributes['paginationArrow'] ) : 'none';
	$show_label       = isset( $attributes['showLabel'] ) && $attributes['showLabel'];
	$mid_size         = isset( $attributes['midSize'] ) ? absint( $attributes['midSize'] ) : 2;

	// Get wrapper attributes.
	$wrapper_attributes = get_block_wrapper_attributes(
		array(
			'class' => 'theabd--vendor-query-pagination',
		)
	);

	// Build pagination links using current URL.
	$base_url = get_pagenum_link( 1, false );
	$format   = '?paged=%#%';

	// Use proper format for pagination.
	if ( strpos( $base_url, '?' ) !== false ) {
		$format = '&paged=%#%';
	}

	ob_start();
	?>

	<nav <?php echo $wrapper_attributes; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
		<?php
		echo wp_kses_post(
			paginate_links(
				array(
					'base'      => str_replace( 999999999, '%#%', esc_url( get_pagenum_link( 999999999, false ) ) ),
					'format'    => $format,
					'current'   => $current_page,
					'total'     => $total_pages,
					'mid_size'  => $mid_size,
					'prev_text' => ( 'arrow' === $pagination_arrow ? '&larr; ' : '' ) . ( $show_label ? __( 'Previous', 'another-dokan-blocks' ) : '' ),
					'next_text' => ( $show_label ? __( 'Next', 'another-dokan-blocks' ) : '' ) . ( 'arrow' === $pagination_arrow ? ' &rarr;' : '' ),
					'type'      => 'plain',
				)
			)
		);
		?>
	</nav>

	<?php
	return ob_get_clean();
}
