<?php
/**
 * Store search block render function.
 *
 * @package AnotherBlocksForDokan
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
function tanbfd_render_vendor_search_block( array $attributes, string $content, WP_Block $block ): string {
	// Extract attributes with defaults.
	$enable_search      = $attributes['enableSearch'] ?? true;
	$search_placeholder = $attributes['searchPlaceholder'] ?? __( 'Search stores...', 'the-another-blocks-for-dokan' );
	$enable_sort_by     = $attributes['enableSortBy'] ?? true;
	$sort_by_label      = $attributes['sortByLabel'] ?? __( 'Sort by:', 'the-another-blocks-for-dokan' );
	/* translators: %s: store count number */
	$store_count_label      = $attributes['storeCountLabel'] ?? __( 'Total store showing: %s', 'the-another-blocks-for-dokan' );
	$enable_location_filter = $attributes['enableLocationFilter'] ?? false;
	$enable_rating_filter   = $attributes['enableRatingFilter'] ?? false;
	$enable_category_filter = $attributes['enableCategoryFilter'] ?? false;
	$button_text            = $attributes['buttonText'] ?? __( 'Search', 'the-another-blocks-for-dokan' );
	$button_size            = $attributes['buttonSize'] ?? 'medium';
	$button_bg_color        = $attributes['buttonBackgroundColor'] ?? '';
	$button_text_color      = $attributes['buttonTextColor'] ?? '';

	// Get current search query.
	$search_query = isset( $_GET['dokan_seller_search'] ) ? sanitize_text_field( wp_unslash( $_GET['dokan_seller_search'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

	// Get current sort by value.
	$sort_by = isset( $_GET['stores_orderby'] ) ? sanitize_text_field( wp_unslash( $_GET['stores_orderby'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

	// Get sort by options (matching Dokan core).
	// Dokan core filter for compatibility.
	// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound
	$sort_by_options = apply_filters(
		'dokan_store_lists_sort_by_options',
	// phpcs:enable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound
		array(
			'name'         => __( 'Name', 'the-another-blocks-for-dokan' ),
			'most_recent'  => __( 'Most Recent', 'the-another-blocks-for-dokan' ),
			'total_orders' => __( 'Most Popular', 'the-another-blocks-for-dokan' ),
			'random'       => __( 'Random', 'the-another-blocks-for-dokan' ),
		)
	);

	// Get wrapper attributes.
	$wrapper_attributes = get_block_wrapper_attributes(
		array(
			'class' => 'tanbfd--vendor-search',
		)
	);

	// Generate button classes (colors from wp-element-button / theme).
	$button_classes = array( 'wp-element-button', 'tanbfd--btn' );
	if ( ! empty( $button_size ) ) {
		$button_classes[] = 'tanbfd--btn-' . esc_attr( $button_size );
	}

	// User-chosen color overrides (inline styles take priority over theme).
	$button_style = '';
	if ( ! empty( $button_bg_color ) ) {
		$button_style .= 'background-color: ' . esc_attr( $button_bg_color ) . ';';
	}
	if ( ! empty( $button_text_color ) ) {
		$button_style .= ' color: ' . esc_attr( $button_text_color ) . ';';
	}

	$button_class_string = implode( ' ', $button_classes );
	$button_style_string = ! empty( $button_style ) ? trim( $button_style ) : '';

	ob_start();
	?>
	<div <?php echo wp_kses_post( $wrapper_attributes ); ?>>
		<?php do_action( 'dokan_before_store_lists_filter' ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- Dokan core action for compatibility. ?>

		<div class="tanbfd--vendor-query-looping-filter-wrap">
			<div class="tanbfd--store-filter-row">
				<div class="tanbfd--store-filter-row-inner">
					<div class="tanbfd--store-filter-left">
						<?php
						// Store count can be filtered/hooked for dynamic count.
						$store_count = apply_filters( 'tanbfd_store_search_block_count', 0 );
						if ( $store_count >= 0 ) : // Show even if 0, allows customization.
							// Use custom label if provided, replace %s with count if placeholder exists.
							$count_text = strpos( $store_count_label, '%s' ) !== false
								? sprintf( $store_count_label, esc_html( number_format_i18n( $store_count ) ) )
								: $store_count_label . ' ' . esc_html( number_format_i18n( $store_count ) );
							?>
							<p class="tanbfd--item tanbfd--store-count">
								<?php echo esc_html( $count_text ); ?>
							</p>
						<?php endif; ?>
					</div>

					<?php if ( $enable_search ) : ?>
						<div class="tanbfd--store-filter-right-item">
							<div class="tanbfd--item">
								<div class="tanbfd--icons" aria-hidden="true">
									<div class="tanbfd--icon-div"></div>
									<div class="tanbfd--icon-div"></div>
									<div class="tanbfd--icon-div"></div>
								</div>
								<button type="button" class="tanbfd--vendor-query-loop-filter-button <?php echo esc_attr( $button_class_string ); ?>" style="<?php echo ! empty( $button_style_string ) ? esc_attr( $button_style_string ) : ''; ?>" aria-expanded="false" aria-controls="tanbfd--vendor-query-looping-filter-form-wrap" aria-label="<?php esc_attr_e( 'Toggle search filters', 'the-another-blocks-for-dokan' ); ?>" data-testid="vendor-filter-toggle">
									<span class="tanbfd--btn-text"><?php echo esc_html__( 'Filter', 'the-another-blocks-for-dokan' ); ?></span>
								</button>
							</div>
						</div>
					<?php endif; ?>
				</div>

				<?php if ( $enable_sort_by ) : ?>
					<div class="tanbfd--store-filter-row-inner tanbfd--store-filter-row-sort">
						<form name="stores_sorting" class="tanbfd--sort-by tanbfd--item" method="get">
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
							<select name="stores_orderby" id="stores_orderby" class="tanbfd--form-control" aria-label="<?php echo esc_attr( $sort_by_label ); ?>">
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
			<?php do_action( 'dokan_before_store_lists_filter_form' ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- Dokan core action for compatibility. ?>
			<?php
			// Show filter form if there's an active search query.
			$has_active_filters = ! empty( $search_query ) || ! empty( $_GET['dokan_store_location'] ) || ! empty( $_GET['dokan_store_rating'] ) || ! empty( $_GET['dokan_store_category'] ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			?>
			<form role="search" method="get" name="dokan_store_lists_filter_form" id="tanbfd--vendor-query-looping-filter-form-wrap" class="tanbfd--vendor-search-filter-form<?php echo $has_active_filters ? '' : ' tanbfd--hidden'; ?>" data-testid="vendor-filter-form">
				<?php do_action( 'dokan_before_store_lists_filter_search' ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- Dokan core action for compatibility. ?>

				<?php
				// Preserve sort by parameter in filter form.
				if ( ! empty( $sort_by ) ) {
					?>
					<input type="hidden" name="stores_orderby" value="<?php echo esc_attr( $sort_by ); ?>" />
					<?php
				}
				?>

				<div class="tanbfd--vendor-search-filter-row">
					<div class="tanbfd--vendor-search tanbfd--grid-item">
						<label for="dokan-seller-search" class="screen-reader-text"><?php echo esc_html( $search_placeholder ); ?></label>
						<input type="search"
							id="dokan-seller-search"
							class="tanbfd--form-control tanbfd--vendor-search-input"
							name="dokan_seller_search"
							value="<?php echo esc_attr( $search_query ); ?>"
							placeholder="<?php echo esc_attr( $search_placeholder ); ?>" />
					</div>

					<div class="tanbfd--apply-filter">
						<button id="cancel-filter-btn" type="button" data-testid="vendor-filter-cancel" class="<?php echo esc_attr( $button_class_string ); ?>" style="<?php echo ! empty( $button_style_string ) ? esc_attr( $button_style_string ) : ''; ?>">
							<span class="tanbfd--btn-text"><?php echo esc_html__( 'Cancel', 'the-another-blocks-for-dokan' ); ?></span>
						</button>
						<button id="apply-filter-btn" type="submit" data-testid="vendor-filter-apply" class="<?php echo esc_attr( $button_class_string ); ?>" style="<?php echo ! empty( $button_style_string ) ? esc_attr( $button_style_string ) : ''; ?>">
							<span class="tanbfd--btn-text"><?php echo esc_html( $button_text ); ?></span>
						</button>
					</div>
				</div>

				<?php if ( $enable_location_filter || $enable_rating_filter || $enable_category_filter ) : ?>
					<div class="tanbfd--store-advanced-filters">
						<?php if ( $enable_location_filter ) : ?>
							<div class="tanbfd--store-filter-field">
								<label><?php echo esc_html__( 'Location:', 'the-another-blocks-for-dokan' ); ?></label>
								<?php
								// phpcs:ignore WordPress.Security.NonceVerification.Recommended
								$selected_location = isset( $_GET['dokan_store_location'] ) ? sanitize_text_field( wp_unslash( $_GET['dokan_store_location'] ) ) : '';

								// Build country → states hierarchy from all vendor profiles.
								$seller_ids = get_users(
									array(
										'role'   => 'seller',
										'fields' => 'ID',
									)
								);

								$country_states = array();
								foreach ( $seller_ids as $seller_id ) {
									$profile      = get_user_meta( $seller_id, 'dokan_profile_settings', true );
									$country_code = $profile['address']['country'] ?? '';
									$state_code   = $profile['address']['state'] ?? '';
									if ( empty( $country_code ) ) {
										continue;
									}
									if ( ! isset( $country_states[ $country_code ] ) ) {
										$country_states[ $country_code ] = array();
									}
									if ( ! empty( $state_code ) ) {
										$country_states[ $country_code ][ $state_code ] = true;
									}
								}
								ksort( $country_states );

								// Resolve country and state names via WooCommerce.
								$wc_countries  = WC()->countries->get_countries();
								$wc_all_states = WC()->countries->get_states();
								?>
								<select name="dokan_store_location" class="tanbfd--form-control tanbfd--store-filter-select">
									<option value=""><?php echo esc_html__( 'All Locations', 'the-another-blocks-for-dokan' ); ?></option>
									<?php foreach ( $country_states as $cc => $states ) : ?>
										<?php
										$country_name = $wc_countries[ $cc ] ?? $cc;
										$state_names  = $wc_all_states[ $cc ] ?? array();
										ksort( $states );
										?>
										<optgroup label="<?php echo esc_attr( $country_name ); ?>">
											<option value="<?php echo esc_attr( $cc ); ?>" <?php selected( $selected_location, $cc ); ?>>
												<?php
												/* translators: %s: country name */
												echo esc_html( sprintf( __( 'All %s', 'the-another-blocks-for-dokan' ), $country_name ) );
												?>
											</option>
											<?php foreach ( $states as $sc => $__ ) : ?>
												<?php $state_name = $state_names[ $sc ] ?? $sc; ?>
												<option value="<?php echo esc_attr( $cc . ':' . $sc ); ?>" <?php selected( $selected_location, $cc . ':' . $sc ); ?>>
													<?php echo esc_html( $state_name ); ?>
												</option>
											<?php endforeach; ?>
										</optgroup>
									<?php endforeach; ?>
								</select>
							</div>
						<?php endif; ?>

						<?php if ( $enable_rating_filter ) : ?>
							<div class="tanbfd--store-filter-field">
								<label><?php echo esc_html__( 'Minimum Rating:', 'the-another-blocks-for-dokan' ); ?></label>
								<select name="dokan_store_rating" class="tanbfd--form-control tanbfd--store-filter-select">
									<option value=""><?php echo esc_html__( 'All Ratings', 'the-another-blocks-for-dokan' ); ?></option>
									<option value="5" <?php selected( isset( $_GET['dokan_store_rating'] ) ? sanitize_text_field( wp_unslash( $_GET['dokan_store_rating'] ) ) : '', '5' ); ?>>5 <?php echo esc_html__( 'Stars', 'the-another-blocks-for-dokan' ); ?></option><?php // phpcs:ignore WordPress.Security.NonceVerification.Recommended ?>
									<option value="4" <?php selected( isset( $_GET['dokan_store_rating'] ) ? sanitize_text_field( wp_unslash( $_GET['dokan_store_rating'] ) ) : '', '4' ); ?>>4+ <?php echo esc_html__( 'Stars', 'the-another-blocks-for-dokan' ); ?></option><?php // phpcs:ignore WordPress.Security.NonceVerification.Recommended ?>
									<option value="3" <?php selected( isset( $_GET['dokan_store_rating'] ) ? sanitize_text_field( wp_unslash( $_GET['dokan_store_rating'] ) ) : '', '3' ); ?>>3+ <?php echo esc_html__( 'Stars', 'the-another-blocks-for-dokan' ); ?></option><?php // phpcs:ignore WordPress.Security.NonceVerification.Recommended ?>
								</select>
							</div>
						<?php endif; ?>

						<?php if ( $enable_category_filter ) : ?>
							<div class="tanbfd--store-filter-field">
								<label><?php echo esc_html__( 'Category:', 'the-another-blocks-for-dokan' ); ?></label>
								<?php
								// phpcs:ignore WordPress.Security.NonceVerification.Recommended
								$selected_category = isset( $_GET['dokan_store_category'] ) ? sanitize_text_field( wp_unslash( $_GET['dokan_store_category'] ) ) : '';
								wp_dropdown_categories(
									array(
										'taxonomy'         => 'product_cat',
										'name'             => 'dokan_store_category',
										'selected'         => $selected_category,
										'show_option_none' => __( 'All Categories', 'the-another-blocks-for-dokan' ),
										'value_field'      => 'slug',
										'class'            => 'tanbfd--form-control tanbfd--store-filter-select',
									)
								);
								?>
							</div>
						<?php endif; ?>
					</div>
				<?php endif; ?>

				<?php do_action( 'dokan_after_store_lists_filter_apply_button' ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- Dokan core action for compatibility. ?>
				<?php wp_nonce_field( 'dokan_store_lists_filter_nonce', '_store_filter_nonce', false ); ?>
			</form>
		<?php endif; ?>

		</div>
	<?php
	wp_enqueue_script( 'tanbfd-vendor-search-view' );

	return ob_get_clean();
}
