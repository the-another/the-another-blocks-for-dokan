<?php
/**
 * Store hours block render function.
 *
 * @package AnotherBlocksDokan
 * @since 1.0.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Store hours block render function.
 *
 * @param array<string, mixed> $attributes Block attributes.
 * @param string               $content    Block content.
 * @param WP_Block             $block      Block instance.
 * @return string Rendered HTML.
 */
function theabd_render_vendor_store_hours_block( array $attributes, string $content, WP_Block $block ): string {
	// Get vendor ID from attributes or context.
	$vendor_id = ! empty( $attributes['vendorId'] ) ? absint( $attributes['vendorId'] ) : 0;

	if ( ! $vendor_id ) {
		$vendor_id = \The_Another\Plugin\Blocks_Dokan\Helpers\Context_Detector::get_vendor_id();
	}

	if ( ! $vendor_id || ! dokan_is_user_seller( $vendor_id ) ) {
		return '';
	}

	// Get vendor data.
	$vendor_data = \The_Another\Plugin\Blocks_Dokan\Renderers\Vendor_Renderer::get_vendor_data( $vendor_id );
	if ( ! $vendor_data || empty( $vendor_data['store_info']['dokan_store_time'] ) ) {
		return '';
	}

	$store_info        = $vendor_data['store_info'];
	$dokan_store_times = $store_info['dokan_store_time'] ?? array();

	// Check if store time is enabled.
	$store_time_enabled = isset( $store_info['dokan_store_time_enabled'] ) && 'yes' === $store_info['dokan_store_time_enabled'];
	if ( ! $store_time_enabled || empty( $dokan_store_times ) ) {
		return '';
	}

	// Extract attributes with defaults.
	$layout              = isset( $attributes['layout'] ) ? sanitize_text_field( $attributes['layout'] ) : 'compact';
	$show_current_status = $attributes['showCurrentStatus'] ?? true;

	// Get current time and day.
	if ( function_exists( 'dokan_current_datetime' ) ) {
		$current_time = dokan_current_datetime();
		$today        = strtolower( $current_time->format( 'l' ) );
	} else {
		$today = strtolower( gmdate( 'l' ) );
	}

	$dokan_days = function_exists( 'dokan_get_translated_days' ) ? dokan_get_translated_days() : array(
		'monday'    => __( 'Monday', 'another-dokan-blocks' ),
		'tuesday'   => __( 'Tuesday', 'another-dokan-blocks' ),
		'wednesday' => __( 'Wednesday', 'another-dokan-blocks' ),
		'thursday'  => __( 'Thursday', 'another-dokan-blocks' ),
		'friday'    => __( 'Friday', 'another-dokan-blocks' ),
		'saturday'  => __( 'Saturday', 'another-dokan-blocks' ),
		'sunday'    => __( 'Sunday', 'another-dokan-blocks' ),
	);

	// Get wrapper attributes.
	$wrapper_attributes = get_block_wrapper_attributes(
		array(
			'class' => "theabd--vendor-store-hours theabd--vendor-store-hours-{$layout}",
		)
	);

	ob_start();
	?>
	<div <?php echo $wrapper_attributes; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
		<?php if ( $show_current_status ) : ?>
			<div class="theabd--vendor-store-hours-status">
				<?php
				if ( \The_Another\Plugin\Blocks_Dokan\Renderers\Vendor_Renderer::is_store_open( $vendor_id ) ) {
					$store_open_notice = isset( $store_info['dokan_store_open_notice'] ) && ! empty( $store_info['dokan_store_open_notice'] )
						? $store_info['dokan_store_open_notice']
						: __( 'Store Open', 'another-dokan-blocks' );
					?>
					<span class="theabd--store-open">
						<i class="fas fa-check-circle"></i>
						<?php echo esc_html( $store_open_notice ); ?>
					</span>
					<?php
				} else {
					$store_closed_notice = isset( $store_info['dokan_store_close_notice'] ) && ! empty( $store_info['dokan_store_close_notice'] )
						? $store_info['dokan_store_close_notice']
						: __( 'Store Closed', 'another-dokan-blocks' );
					?>
					<span class="theabd--store-closed">
						<i class="fas fa-times-circle"></i>
						<?php echo esc_html( $store_closed_notice ); ?>
					</span>
					<?php
				}
				?>
			</div>
		<?php endif; ?>

		<?php if ( 'detailed' === $layout ) : ?>
			<div class="theabd--vendor-store-hours-details">
				<h3><?php echo esc_html__( 'Weekly Store Timing', 'another-dokan-blocks' ); ?></h3>
				<ul class="theabd--vendor-store-hours-list">
					<?php foreach ( $dokan_days as $day_key => $day_label ) : ?>
						<?php
						$day_schedule = $dokan_store_times[ $day_key ] ?? array();
						$is_closed    = isset( $day_schedule['status'] ) && 'close' === $day_schedule['status'];
						$is_open      = isset( $day_schedule['status'] ) && 'open' === $day_schedule['status'];
						$opening_time = $day_schedule['opening_time'] ?? '';
						$closing_time = $day_schedule['closing_time'] ?? '';
						?>
						<li class="theabd--vendor-store-hours-day <?php echo $today === $day_key ? 'theabd--today' : ''; ?>">
							<span class="theabd--day-name"><?php echo esc_html( $day_label ); ?></span>
							<span class="theabd--day-hours">
								<?php if ( $is_closed ) : ?>
									<span class="theabd--closed"><?php echo esc_html__( 'CLOSED', 'another-dokan-blocks' ); ?></span>
								<?php elseif ( $is_open && ! empty( $opening_time ) && ! empty( $closing_time ) ) : ?>
									<span class="theabd--open">
										<?php echo esc_html( $opening_time ); ?> - <?php echo esc_html( $closing_time ); ?>
									</span>
								<?php else : ?>
									<span class="theabd--closed"><?php echo esc_html__( 'CLOSED', 'another-dokan-blocks' ); ?></span>
								<?php endif; ?>
							</span>
						</li>
					<?php endforeach; ?>
				</ul>
			</div>
		<?php else : ?>
			<div class="theabd--vendor-store-hours-compact">
				<p>
					<?php
					$current_day_schedule = $dokan_store_times[ $today ] ?? array();
					if ( ! empty( $current_day_schedule['opening_time'] ) && ! empty( $current_day_schedule['closing_time'] ) ) {
						echo esc_html(
							sprintf(
								// translators: %1$s is opening time, %2$s is closing time.
								__( 'Today: %1$s - %2$s', 'another-dokan-blocks' ),
								$current_day_schedule['opening_time'],
								$current_day_schedule['closing_time']
							)
						);
					} else {
						echo esc_html__( 'Store hours vary. Please check detailed schedule.', 'another-dokan-blocks' );
					}
					?>
				</p>
			</div>
		<?php endif; ?>
	</div>
	<?php

	return ob_get_clean();
}
