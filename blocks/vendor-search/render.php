<?php
/**
 * Store search block render function.
 *
 * @package AnotherBlocksDokan
 * @since 1.0.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Store search block render function.
 *
 * @param array<string, mixed> $attributes Block attributes.
 * @param string               $content    Block content.
 * @param WP_Block             $block      Block instance.
 * @return string Rendered HTML.
 */
function theabd_render_vendor_search_block( array $attributes, string $content, WP_Block $block ): string {
	// Extract attributes with defaults.
	$enable_search          = $attributes['enableSearch'] ?? true;
	$search_placeholder     = $attributes['searchPlaceholder'] ?? __( 'Search stores...', 'another-dokan-blocks' );
	$enable_sort_by         = $attributes['enableSortBy'] ?? true;
	$sort_by_label          = $attributes['sortByLabel'] ?? __( 'Sort by:', 'another-dokan-blocks' );
	/* translators: %s: store count number */
	$store_count_label      = $attributes['storeCountLabel'] ?? __( 'Total store showing: %s', 'another-dokan-blocks' );
	$enable_location_filter = $attributes['enableLocationFilter'] ?? false;
	$enable_rating_filter   = $attributes['enableRatingFilter'] ?? false;
	$enable_category_filter = $attributes['enableCategoryFilter'] ?? false;
	$button_text            = $attributes['buttonText'] ?? __( 'Search', 'another-dokan-blocks' );
	$button_size            = $attributes['buttonSize'] ?? 'medium';
	$button_bg_color        = $attributes['buttonBackgroundColor'] ?? '';
	$button_text_color      = $attributes['buttonTextColor'] ?? '';

	// Get current search query.
	$search_query = isset( $_GET['dokan_seller_search'] ) ? sanitize_text_field( wp_unslash( $_GET['dokan_seller_search'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

	// Get current sort by value.
	$sort_by = isset( $_GET['stores_orderby'] ) ? sanitize_text_field( wp_unslash( $_GET['stores_orderby'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

	// Get sort by options (matching Dokan core).
	$sort_by_options = apply_filters(
		'dokan_store_lists_sort_by_options',
		array(
			'name'         => __( 'Name', 'another-dokan-blocks' ),
			'most_recent'  => __( 'Most Recent', 'another-dokan-blocks' ),
			'total_orders' => __( 'Most Popular', 'another-dokan-blocks' ),
			'random'       => __( 'Random', 'another-dokan-blocks' ),
		)
	);

	// Get wrapper attributes.
	$wrapper_attributes = get_block_wrapper_attributes(
		array(
			'class' => 'theabd--vendor-search',
		)
	);

	// Generate button classes and styles (reusable for all buttons).
	$button_classes = array( 'theabd--btn', 'theabd--btn-theme' );
	if ( ! empty( $button_size ) && 'medium' !== $button_size ) {
		$button_classes[] = 'dokan-btn-' . esc_attr( $button_size );
	}

	$button_style = '';
	// Add button size padding.
	switch ( $button_size ) {
		case 'small':
			$button_style .= 'padding: 0.375rem 1rem; font-size: 0.875rem;';
			break;
		case 'large':
			$button_style .= 'padding: 0.75rem 2rem; font-size: 1.125rem;';
			break;
		default: // medium
			$button_style .= 'padding: 0.5rem 1.5rem; font-size: 1rem;';
			break;
	}
	// Add button colors.
	if ( ! empty( $button_bg_color ) ) {
		$button_style .= ' background-color: ' . esc_attr( $button_bg_color ) . ';';
	}
	if ( ! empty( $button_text_color ) ) {
		$button_style .= ' color: ' . esc_attr( $button_text_color ) . ';';
	}

	$button_class_string = implode( ' ', $button_classes );
	$button_style_string = ! empty( $button_style ) ? trim( $button_style ) : '';

	ob_start();
	?>
	<div <?php echo $wrapper_attributes; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
		<?php do_action( 'dokan_before_store_lists_filter' ); ?>

		<div class="theabd--vendor-query-looping-filter-wrap">
			<div class="theabd--store-filter-row">
				<div class="theabd--store-filter-row-inner">
					<div class="theabd--store-filter-left">
						<?php
						// Store count can be filtered/hooked for dynamic count.
						$store_count = apply_filters( 'dokan_store_search_block_count', 0 );
						if ( $store_count >= 0 ) : // Show even if 0, allows customization.
							// Use custom label if provided, replace %s with count if placeholder exists.
							$count_text = strpos( $store_count_label, '%s' ) !== false
								? sprintf( $store_count_label, esc_html( number_format_i18n( $store_count ) ) )
								: $store_count_label . ' ' . esc_html( number_format_i18n( $store_count ) );
							?>
							<p class="theabd--item theabd--store-count">
								<?php echo esc_html( $count_text ); ?>
							</p>
						<?php endif; ?>
					</div>

					<?php if ( $enable_search ) : ?>
						<div class="theabd--store-filter-right-item">
							<div class="theabd--item">
								<div class="theabd--icons">
									<div class="theabd--icon-div"></div>
									<div class="theabd--icon-div"></div>
									<div class="theabd--icon-div"></div>
								</div>
								<button type="button" class="theabd--vendor-query-loop-filter-button <?php echo esc_attr( $button_class_string ); ?>" style="<?php echo ! empty( $button_style_string ) ? esc_attr( $button_style_string ) : ''; ?>" aria-expanded="false" aria-controls="theabd--vendor-query-looping-filter-form-wrap">
									<span class="theabd--btn-text"><?php echo esc_html__( 'Filter', 'another-dokan-blocks' ); ?></span>
								</button>
							</div>
						</div>
					<?php endif; ?>
				</div>

				<?php if ( $enable_sort_by ) : ?>
					<div class="theabd--store-filter-row-inner theabd--store-filter-row-sort">
						<form name="stores_sorting" class="theabd--sort-by theabd--item" method="get">
							<?php
							// Preserve current query parameters.
							if ( ! empty( $_GET ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
								foreach ( $_GET as $key => $value ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
									if ( 'stores_orderby' !== $key ) {
										$value = sanitize_text_field( wp_unslash( $value ) );
										?>
										<input type="hidden" name="<?php echo esc_attr( $key ); ?>" value="<?php echo esc_attr( $value ); ?>" />
										<?php
									}
								}
							}
							?>
							<label><?php echo esc_html( $sort_by_label ); ?></label>
							<select name="stores_orderby" id="stores_orderby" aria-label="<?php echo esc_attr( $sort_by_label ); ?>">
								<?php foreach ( $sort_by_options as $key => $label ) : ?>
									<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $sort_by, $key ); ?>>
										<?php echo esc_html( $label ); ?>
									</option>
								<?php endforeach; ?>
							</select>
						</form>
					</div>
				<?php endif; ?>
			</div>
		</div>

		<?php if ( $enable_search ) : ?>
			<?php do_action( 'dokan_before_store_lists_filter_form' ); ?>
			<?php
			// Show filter form if there's an active search query.
			$has_active_filters = ! empty( $search_query ) || ! empty( $_GET['dokan_store_location'] ) || ! empty( $_GET['dokan_store_rating'] ) || ! empty( $_GET['dokan_store_category'] ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			?>
			<form role="store-list-filter" method="get" name="dokan_store_lists_filter_form" id="theabd--vendor-query-looping-filter-form-wrap" class="theabd--vendor-search-filter-form" style="<?php echo $has_active_filters ? 'display: block;' : 'display: none;'; ?>">
				<?php do_action( 'dokan_before_store_lists_filter_search' ); ?>

				<?php
				// Preserve sort by parameter in filter form.
				if ( ! empty( $sort_by ) ) {
					?>
					<input type="hidden" name="stores_orderby" value="<?php echo esc_attr( $sort_by ); ?>" />
					<?php
				}
				?>

				<div class="theabd--vendor-search-filter-row">
					<div class="theabd--vendor-search theabd--grid-item">
						<input type="search"
							class="theabd--vendor-search-input theabd--vendor-search-input"
							name="dokan_seller_search"
							value="<?php echo esc_attr( $search_query ); ?>"
							placeholder="<?php echo esc_attr( $search_placeholder ); ?>" />
					</div>

					<div class="theabd--apply-filter">
						<button id="cancel-filter-btn" type="button" class="<?php echo esc_attr( $button_class_string ); ?>" style="<?php echo ! empty( $button_style_string ) ? esc_attr( $button_style_string ) : ''; ?>">
							<span class="theabd--btn-text"><?php echo esc_html__( 'Cancel', 'another-dokan-blocks' ); ?></span>
						</button>
						<button id="apply-filter-btn" type="submit" class="<?php echo esc_attr( $button_class_string ); ?>" style="<?php echo ! empty( $button_style_string ) ? esc_attr( $button_style_string ) : ''; ?>">
							<span class="theabd--btn-text"><?php echo esc_html( $button_text ); ?></span>
						</button>
					</div>
				</div>

				<?php if ( $enable_location_filter || $enable_rating_filter || $enable_category_filter ) : ?>
					<div class="theabd--store-advanced-filters">
						<?php if ( $enable_location_filter ) : ?>
							<div class="theabd--store-filter-field">
								<label><?php echo esc_html__( 'Location:', 'another-dokan-blocks' ); ?></label>
								<select name="dokan_store_location" class="theabd--store-filter-select">
									<option value=""><?php echo esc_html__( 'All Locations', 'another-dokan-blocks' ); ?></option>
									<!-- Location options would be populated dynamically -->
								</select>
							</div>
						<?php endif; ?>

						<?php if ( $enable_rating_filter ) : ?>
							<div class="theabd--store-filter-field">
								<label><?php echo esc_html__( 'Minimum Rating:', 'another-dokan-blocks' ); ?></label>
								<select name="dokan_store_rating" class="theabd--store-filter-select">
									<option value=""><?php echo esc_html__( 'All Ratings', 'another-dokan-blocks' ); ?></option>
									<?php // phpcs:ignore WordPress.Security.NonceVerification.Recommended ?>
									<option value="5" <?php selected( isset( $_GET['dokan_store_rating'] ) ? sanitize_text_field( wp_unslash( $_GET['dokan_store_rating'] ) ) : '', '5' ); ?>>5 <?php echo esc_html__( 'Stars', 'another-dokan-blocks' ); ?></option>
									<option value="4" <?php selected( isset( $_GET['dokan_store_rating'] ) ? sanitize_text_field( wp_unslash( $_GET['dokan_store_rating'] ) ) : '', '4' ); ?>>4+ <?php echo esc_html__( 'Stars', 'another-dokan-blocks' ); ?></option>
									<option value="3" <?php selected( isset( $_GET['dokan_store_rating'] ) ? sanitize_text_field( wp_unslash( $_GET['dokan_store_rating'] ) ) : '', '3' ); ?>>3+ <?php echo esc_html__( 'Stars', 'another-dokan-blocks' ); ?></option>
								</select>
							</div>
						<?php endif; ?>

						<?php if ( $enable_category_filter ) : ?>
							<div class="theabd--store-filter-field">
								<label><?php echo esc_html__( 'Category:', 'another-dokan-blocks' ); ?></label>
								<?php
								// phpcs:ignore WordPress.Security.NonceVerification.Recommended
								$selected_category = isset( $_GET['dokan_store_category'] ) ? sanitize_text_field( wp_unslash( $_GET['dokan_store_category'] ) ) : '';
								wp_dropdown_categories(
									array(
										'taxonomy'         => 'product_cat',
										'name'             => 'dokan_store_category',
										'selected'         => $selected_category,
										'show_option_none' => __( 'All Categories', 'another-dokan-blocks' ),
										'value_field'      => 'slug',
										'class'            => 'theabd--store-filter-select',
									)
								);
								?>
							</div>
						<?php endif; ?>
					</div>
				<?php endif; ?>

				<?php do_action( 'dokan_after_store_lists_filter_apply_button' ); ?>
				<?php wp_nonce_field( 'dokan_store_lists_filter_nonce', '_store_filter_nonce', false ); ?>
			</form>
		<?php endif; ?>

		</div>
	<?php

	// Add JavaScript for filter toggle functionality.
	?>
	<script>
	(function() {
		var filterButton = document.querySelector('.theabd--vendor-query-loop-filter-button');
		var filterForm = document.getElementById('theabd--vendor-query-looping-filter-form-wrap');
		var cancelButton = document.getElementById('cancel-filter-btn');
		var sortSelect = document.getElementById('stores_orderby');

		// Toggle function for filter form.
		function toggleFilterForm() {
			if (filterButton && filterForm) {
				var isExpanded = filterButton.getAttribute('aria-expanded') === 'true';
				filterButton.setAttribute('aria-expanded', !isExpanded);
				filterForm.style.display = isExpanded ? 'none' : 'block';
			}
		}

		if (filterButton && filterForm) {
			// Initialize aria-expanded based on current visibility.
			var isInitiallyVisible = filterForm.style.display !== 'none';
			filterButton.setAttribute('aria-expanded', isInitiallyVisible ? 'true' : 'false');

			// Toggle filter form visibility.
			filterButton.addEventListener('click', function(e) {
				e.preventDefault();
				toggleFilterForm();
			});

			// Cancel button uses the same toggle function.
			if (cancelButton) {
				cancelButton.addEventListener('click', function(e) {
					e.preventDefault();
					toggleFilterForm();
				});
			}
		}

		// Auto-submit sort by select.
		if (sortSelect) {
			sortSelect.addEventListener('change', function() {
				if (this.form) {
					this.form.submit();
				}
			});
		}
	})();
	</script>
	<?php

	return ob_get_clean();
}
