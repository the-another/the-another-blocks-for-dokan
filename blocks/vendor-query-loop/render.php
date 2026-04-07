<?php
/**
 * Store Query Loop block render function.
 *
 * @package AnotherBlocksForDokan
 * @since 1.0.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Build WP_User_Query args for the vendor query loop.
 *
 * Pure function — reads $_GET for sort/search/location filters but has no side effects.
 *
 * @param array<string, mixed> $attributes Block attributes.
 * @param int                  $paged      Page number (1-based).
 * @return array<string, mixed>
 */
function theabd_vendor_query_loop_build_query_args( array $attributes, int $paged ): array {
	$per_page           = isset( $attributes['perPage'] ) ? absint( $attributes['perPage'] ) : 12;
	$order_by           = isset( $attributes['orderBy'] ) ? sanitize_text_field( $attributes['orderBy'] ) : 'name';
	$show_featured_only = isset( $attributes['showFeaturedOnly'] ) && $attributes['showFeaturedOnly'];

	$stores_orderby = isset( $_GET['stores_orderby'] ) ? sanitize_text_field( wp_unslash( $_GET['stores_orderby'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	if ( ! empty( $stores_orderby ) ) {
		$order_by = $stores_orderby;
	}

	$dokan_seller_search  = isset( $_GET['dokan_seller_search'] ) ? sanitize_text_field( wp_unslash( $_GET['dokan_seller_search'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	$dokan_store_location = isset( $_GET['dokan_store_location'] ) ? sanitize_text_field( wp_unslash( $_GET['dokan_store_location'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

	$wp_orderby = $order_by;
	if ( 'date' === $order_by || 'most_recent' === $order_by ) {
		$wp_orderby = 'most_recent';
	} elseif ( 'name' === $order_by ) {
		$wp_orderby = 'display_name';
	} elseif ( 'total_orders' === $order_by || 'random' === $order_by ) {
		$wp_orderby = $order_by;
	}

	$user_args = array(
		'role'       => 'seller',
		'number'     => $per_page,
		'paged'      => $paged,
		'orderby'    => $wp_orderby,
		'meta_query' => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
			'relation' => 'AND',
			array(
				'key'     => 'dokan_enable_selling',
				'value'   => 'yes',
				'compare' => '=',
			),
		),
	);

	if ( ! empty( $dokan_seller_search ) ) {
		$user_args['meta_query'][] = array(
			'key'     => 'dokan_store_name',
			'value'   => $dokan_seller_search,
			'compare' => 'LIKE',
		);
	}

	if ( ! empty( $dokan_store_location ) ) {
		if ( str_contains( $dokan_store_location, ':' ) ) {
			list( $filter_country, $filter_state ) = explode( ':', $dokan_store_location, 2 );
			$user_args['meta_query'][]             = array(
				'key'     => 'dokan_profile_settings',
				'value'   => '"country";s:' . strlen( $filter_country ) . ':"' . $filter_country . '"',
				'compare' => 'LIKE',
			);
			$user_args['meta_query'][]             = array(
				'key'     => 'dokan_profile_settings',
				'value'   => '"state";s:' . strlen( $filter_state ) . ':"' . $filter_state . '"',
				'compare' => 'LIKE',
			);
		} else {
			$user_args['meta_query'][] = array(
				'key'     => 'dokan_profile_settings',
				'value'   => '"country";s:' . strlen( $dokan_store_location ) . ':"' . $dokan_store_location . '"',
				'compare' => 'LIKE',
			);
		}
	}

	if ( $show_featured_only ) {
		$user_args['meta_query'][] = array(
			'key'     => 'dokan_feature_seller',
			'value'   => 'yes',
			'compare' => '=',
		);
	}

	/**
	 * Filter store query loop query arguments.
	 *
	 * @since 1.0.0
	 *
	 * @param array<string, mixed> $user_args  User query arguments.
	 * @param array<string, mixed> $attributes Block attributes.
	 */
	return apply_filters( 'theabd_store_list_query_args', $user_args, $attributes );
}

/**
 * Run the vendor user query, handling custom sort hooks internally.
 *
 * @param array<string, mixed> $user_args Args from build_query_args().
 * @return \WP_User_Query
 */
function theabd_vendor_query_loop_run_query( array $user_args ): \WP_User_Query {
	$wp_orderby = isset( $user_args['orderby'] ) ? (string) $user_args['orderby'] : '';
	$callback   = null;

	if ( in_array( $wp_orderby, array( 'most_recent', 'total_orders', 'random' ), true ) ) {
		$callback = function ( $query ) use ( $wp_orderby ) {
			global $wpdb;

			if ( 'total_orders' === $wp_orderby ) {
				$query->query_from   .= " LEFT JOIN (
					SELECT seller_id,
					COUNT(*) AS orders_count
					FROM {$wpdb->prefix}dokan_orders
					GROUP BY seller_id
				) as dokan_orders
				ON ( {$wpdb->users}.ID = dokan_orders.seller_id )";
				$query->query_orderby = 'ORDER BY orders_count DESC';
			} elseif ( 'most_recent' === $wp_orderby ) {
				$query->query_orderby = 'ORDER BY ID DESC';
			} elseif ( 'random' === $wp_orderby ) {
				$order_by_options = array(
					'ID',
					'user_login, ID',
					'user_email',
					'user_registered, ID',
					'user_nicename, ID',
				);

				$selected_orderby = get_transient( 'dokan_store_listing_random_orderby' );
				if ( false === $selected_orderby ) {
					$selected_orderby = $order_by_options[ array_rand( $order_by_options, 1 ) ];
					set_transient( 'dokan_store_listing_random_orderby', $selected_orderby, MINUTE_IN_SECONDS * 5 );
				}

				$query->query_orderby = "ORDER BY $selected_orderby";
			}
		};

		add_action( 'pre_user_query', $callback, 9 );
	}

	$query = new \WP_User_Query( $user_args );

	if ( $callback ) {
		remove_action( 'pre_user_query', $callback, 9 );
	}

	return $query;
}

/**
 * Render the <li> items for a list of seller user objects.
 *
 * @param array<int, \WP_User>                       $sellers         Seller users.
 * @param array<int, \WP_Block|array<string, mixed>> $template_blocks Inner template blocks (vendor-card) — accepts WP_Block instances or raw parsed-block arrays.
 * @param array<string, mixed>                       $base_context    Block context (will be merged with vendor data per item).
 * @return string
 */
function theabd_vendor_query_loop_render_items( array $sellers, array $template_blocks, array $base_context ): string {
	if ( empty( $sellers ) ) {
		return '';
	}

	$seller_ids = wp_list_pluck( $sellers, 'ID' );
	cache_users( $seller_ids );

	ob_start();
	foreach ( $sellers as $seller ) {
		$vendor_id = absint( $seller->ID );
		$vendor    = dokan()->vendor->get( $vendor_id );

		if ( ! $vendor || ! dokan_is_user_seller( $vendor_id ) ) {
			continue;
		}

		$vendor_data    = $vendor->to_array();
		$vendor_context = array_merge( $base_context, array( 'dokan/vendor' => $vendor_data ) );
		?>
		<li class="theabd--single-vendor">
			<?php
			if ( ! empty( $template_blocks ) ) {
				foreach ( $template_blocks as $template_block ) {
					$parsed   = is_array( $template_block ) ? $template_block : $template_block->parsed_block;
					$instance = new \WP_Block( $parsed, $vendor_context );
					echo $instance->render(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				}
			} else {
				if ( function_exists( 'theabd_render_vendor_avatar_block' ) ) {
					$avatar_block = new \WP_Block(
						array(
							'blockName' => 'the-another/blocks-for-dokan-vendor-avatar',
							'attrs'     => array(
								'width'  => '80px',
								'height' => '80px',
							),
						),
						array( 'dokan/vendor' => $vendor_data )
					);
					echo $avatar_block->render(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				}

				if ( function_exists( 'theabd_render_vendor_store_name_block' ) ) {
					$name_block = new \WP_Block(
						array(
							'blockName' => 'the-another/blocks-for-dokan-vendor-store-name',
							'attrs'     => array( 'tagName' => 'h3' ),
						),
						array( 'dokan/vendor' => $vendor_data )
					);
					echo $name_block->render(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				}
			}
			?>
		</li>
		<?php
	}
	return (string) ob_get_clean();
}

/**
 * Store Query Loop block render function.
 *
 * @param array<string, mixed> $attributes Block attributes.
 * @param string               $content    Block content.
 * @param WP_Block             $block      Block instance.
 * @return string Rendered HTML.
 */
function theabd_render_vendor_query_loop_block( array $attributes, string $content, WP_Block $block ): string {
	// Extract attributes with defaults.
	$columns                = isset( $attributes['columns'] ) ? absint( $attributes['columns'] ) : 3;
	$display_layout         = isset( $attributes['displayLayout'] ) ? sanitize_text_field( $attributes['displayLayout'] ) : 'grid';
	$order_by               = isset( $attributes['orderBy'] ) ? sanitize_text_field( $attributes['orderBy'] ) : 'name';
	$show_featured_only     = isset( $attributes['showFeaturedOnly'] ) && $attributes['showFeaturedOnly'];
	$enable_infinite_scroll = ! empty( $attributes['enableInfiniteScroll'] );

	// Get current page for pagination.
	$paged = get_query_var( 'paged' ) ? absint( get_query_var( 'paged' ) ) : 1;

	$user_args  = theabd_vendor_query_loop_build_query_args( $attributes, $paged );
	$per_page   = (int) $user_args['number'];
	$user_query = theabd_vendor_query_loop_run_query( $user_args );
	$sellers    = $user_query->get_results();

	// Calculate pagination info.
	$total_users = (int) $user_query->get_total();
	$total_pages = (int) ceil( $total_users / max( 1, $per_page ) );

	// Hook the count into the filter for store-search block to use.
	$count_filter_callback = function ( $count ) use ( $total_users ) {
		return $total_users;
	};
	add_filter( 'theabd_store_search_block_count', $count_filter_callback, 10, 1 );

	// Generate unique query ID for this block instance.
	$query_id = 'store-query-' . ( isset( $block->parsed_block['attrs']['queryId'] ) ? $block->parsed_block['attrs']['queryId'] : wp_unique_id() );

	// Provide pagination context for child blocks (pagination block).
	$query_context = array(
		'queryId' => $query_id,
		'query'   => array(
			'totalPages'  => $total_pages,
			'currentPage' => $paged,
			'total'       => $total_users,
			'perPage'     => $per_page,
		),
	);

	// Merge context for child blocks.
	if ( ! isset( $block->context ) ) {
		$block->context = array();
	}
	$block->context['dokan/queryId'] = $query_context['queryId'];
	$block->context['dokan/query']   = $query_context['query'];

	// Separate template blocks (vendor-card) from query-level blocks (search, pagination).
	$template_blocks   = array();
	$search_blocks     = array();
	$pagination_blocks = array();

	if ( ! empty( $block->inner_blocks ) ) {
		foreach ( $block->inner_blocks as $inner_block ) {
			if ( 'the-another/blocks-for-dokan-vendor-search' === $inner_block->name ) {
				$search_blocks[] = $inner_block;
			} elseif ( 'the-another/blocks-for-dokan-vendor-query-pagination' === $inner_block->name ) {
				$pagination_blocks[] = $inner_block;
			} elseif ( 'the-another/blocks-for-dokan-vendor-card' === $inner_block->name ) {
				$template_blocks[] = $inner_block;
			}
		}
	}

	// Persist the inner-block template so the REST endpoint can re-render subsequent pages.
	if ( $enable_infinite_scroll && ! empty( $template_blocks ) && class_exists( '\\The_Another\\Plugin\\Blocks_Dokan\\Rest\\Vendor_Query_Loop_Controller' ) ) {
		$template_block_arrays = array_map(
			static fn( $b ) => $b->parsed_block,
			$template_blocks
		);
		set_transient(
			\The_Another\Plugin\Blocks_Dokan\Rest\Vendor_Query_Loop_Controller::template_cache_key( $query_id ),
			$template_block_arrays,
			HOUR_IN_SECONDS
		);
	}

	// Get wrapper attributes.
	$wrapper_args = array(
		'class' => "theabd--vendor-query-loop theabd--vendor-query-loop-{$display_layout} theabd--vendor-query-loop-columns-{$columns}",
	);

	if ( $enable_infinite_scroll ) {
		$wrapper_args['data-infinite']     = '1';
		$wrapper_args['data-query-id']     = $query_id;
		$wrapper_args['data-per-page']     = (string) $per_page;
		$wrapper_args['data-current-page'] = (string) $paged;
		$wrapper_args['data-total-pages']  = (string) $total_pages;
		$wrapper_args['data-attributes']   = (string) wp_json_encode(
			array(
				'perPage'          => $per_page,
				'columns'          => $columns,
				'displayLayout'    => $display_layout,
				'orderBy'          => $order_by,
				'showFeaturedOnly' => $show_featured_only,
			)
		);

		wp_enqueue_script( 'theabd-vendor-query-loop-view' );
	}

	$wrapper_attributes = get_block_wrapper_attributes( $wrapper_args );

	ob_start();

	if ( ! empty( $sellers ) ) {
		// Generate class for grid layout (columns handled via CSS media queries).
		$grid_classes = 'theabd--vendor-query-loop-wrap';
		if ( 'grid' === $display_layout ) {
			$grid_classes .= ' theabd--vendor-query-loop-grid theabd--vendor-query-loop-columns-' . absint( $columns );
		} elseif ( 'list' === $display_layout ) {
			$grid_classes .= ' theabd--vendor-query-loop-list';
		}

		?>
		<div <?php echo $wrapper_attributes; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
			<?php
			// Render search blocks before the loop.
			if ( ! empty( $search_blocks ) ) {
				foreach ( $search_blocks as $search_block ) {
					$search_block_instance = new WP_Block(
						$search_block->parsed_block,
						$block->context
					);
					echo $search_block_instance->render(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				}
			}
			?>
			<ul class="theabd--vendor-wrap <?php echo esc_attr( $grid_classes ); ?>">
				<?php
				echo theabd_vendor_query_loop_render_items( $sellers, $template_blocks, $block->context ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				?>
			</ul>

			<?php
			if ( $enable_infinite_scroll ) {
				echo '<div class="theabd--vendor-query-loop-sentinel" aria-hidden="true"></div>';
				echo '<div class="theabd--vendor-query-loop-status" role="status" aria-live="polite"></div>';
			}

			if ( ! $enable_infinite_scroll ) :
				// Render pagination blocks after the loop.
				$pagination_rendered = false;
				if ( ! empty( $pagination_blocks ) ) {
					foreach ( $pagination_blocks as $pagination_block ) {
						$pagination_block_instance = new WP_Block(
							$pagination_block->parsed_block,
							$block->context
						);
						echo $pagination_block_instance->render(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
						$pagination_rendered = true;
						break; // Only render first pagination block.
					}
				}

				// Fallback: Show default pagination if no pagination block is present and pages > 1.
				if ( ! $pagination_rendered && $total_pages > 1 ) :
					?>
					<nav class="theabd--vendor-query-loop-pagination" data-testid="vendor-pagination">
						<?php
						echo wp_kses_post(
							paginate_links(
								array(
									'total'     => $total_pages,
									'current'   => $paged,
									'prev_text' => __( '&larr; Previous', 'another-blocks-for-dokan' ),
									'next_text' => __( 'Next &rarr;', 'another-blocks-for-dokan' ),
								)
							)
						);
						?>
					</nav>
					<?php
				endif;
			endif;
			?>
		</div>
		<?php
	} else {
		?>
		<div <?php echo $wrapper_attributes; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
			<?php
			// Render search blocks even when no results.
			if ( ! empty( $search_blocks ) ) {
				foreach ( $search_blocks as $search_block ) {
					$search_block_instance = new WP_Block(
						$search_block->parsed_block,
						$block->context
					);
					echo $search_block_instance->render(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				}
			}
			?>
			<p class="theabd--vendor-query-loop-empty">
				<?php echo esc_html__( 'No vendors found.', 'another-blocks-for-dokan' ); ?>
			</p>
		</div>
		<?php
	}

	$output = ob_get_clean();

	// Remove the filter after rendering is complete.
	if ( isset( $count_filter_callback ) ) {
		remove_filter( 'theabd_store_search_block_count', $count_filter_callback, 10 );
	}

	return $output;
}
