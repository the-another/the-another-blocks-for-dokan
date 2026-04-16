<?php
/**
 * Store hours block render function.
 *
 * @package AnotherBlocksForDokan
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
function tanbfd_render_vendor_store_hours_block( array $attributes, string $content, WP_Block $block ): string {
	// Get vendor ID from attributes or context.
	$vendor_id = ! empty( $attributes['vendorId'] ) ? absint( $attributes['vendorId'] ) : 0;

	if ( ! $vendor_id ) {
		$vendor_id = tanbfd_get_vendor_id();
	}

	if ( ! $vendor_id || ! dokan_is_user_seller( $vendor_id ) ) {
		return '';
	}

	// Get vendor data.
	$vendor_data = \The_Another\Plugin\Blocks_For_Dokan\Renderers\Vendor_Renderer::get_vendor_data( $vendor_id );
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
		'monday'    => __( 'Monday', 'the-another-blocks-for-dokan' ),
		'tuesday'   => __( 'Tuesday', 'the-another-blocks-for-dokan' ),
		'wednesday' => __( 'Wednesday', 'the-another-blocks-for-dokan' ),
		'thursday'  => __( 'Thursday', 'the-another-blocks-for-dokan' ),
		'friday'    => __( 'Friday', 'the-another-blocks-for-dokan' ),
		'saturday'  => __( 'Saturday', 'the-another-blocks-for-dokan' ),
		'sunday'    => __( 'Sunday', 'the-another-blocks-for-dokan' ),
	);

	// Get wrapper attributes.
	$wrapper_attributes = get_block_wrapper_attributes(
		array(
			'class' => "tanbfd--vendor-store-hours tanbfd--vendor-store-hours-{$layout}",
		)
	);

	ob_start();
	?>
	<div <?php echo wp_kses_post( $wrapper_attributes ); ?>>
		<?php if ( $show_current_status ) : ?>
			<div class="tanbfd--vendor-store-hours-status">
				<?php
				if ( \The_Another\Plugin\Blocks_For_Dokan\Renderers\Vendor_Renderer::is_store_open( $vendor_id ) ) {
					$store_open_notice = isset( $store_info['dokan_store_open_notice'] ) && ! empty( $store_info['dokan_store_open_notice'] )
						? $store_info['dokan_store_open_notice']
						: __( 'Store Open', 'the-another-blocks-for-dokan' );
					?>
					<span class="tanbfd--store-open">
						<i class="fas fa-check-circle"></i>
						<?php echo esc_html( $store_open_notice ); ?>
					</span>
					<?php
				} else {
					$store_closed_notice = isset( $store_info['dokan_store_close_notice'] ) && ! empty( $store_info['dokan_store_close_notice'] )
						? $store_info['dokan_store_close_notice']
						: __( 'Store Closed', 'the-another-blocks-for-dokan' );
					?>
					<span class="tanbfd--store-closed">
						<i class="fas fa-times-circle"></i>
						<?php echo esc_html( $store_closed_notice ); ?>
					</span>
					<?php
				}
				?>
			</div>
		<?php endif; ?>

		<?php if ( 'detailed' === $layout ) : ?>
			<div class="tanbfd--vendor-store-hours-details">
				<h3><?php echo esc_html__( 'Weekly Store Timing', 'the-another-blocks-for-dokan' ); ?></h3>
				<ul class="tanbfd--vendor-store-hours-list">
					<?php foreach ( $dokan_days as $day_key => $day_label ) : ?>
						<?php
						$day_schedule = $dokan_store_times[ $day_key ] ?? array();
						$is_closed    = isset( $day_schedule['status'] ) && 'close' === $day_schedule['status'];
						$is_open      = isset( $day_schedule['status'] ) && 'open' === $day_schedule['status'];
						$opening_time = $day_schedule['opening_time'] ?? '';
						$closing_time = $day_schedule['closing_time'] ?? '';
						?>
						<li class="tanbfd--vendor-store-hours-day <?php echo $today === $day_key ? 'tanbfd--today' : ''; ?>">
							<span class="tanbfd--day-name"><?php echo esc_html( $day_label ); ?></span>
							<span class="tanbfd--day-hours">
								<?php if ( $is_closed ) : ?>
									<span class="tanbfd--closed"><?php echo esc_html__( 'CLOSED', 'the-another-blocks-for-dokan' ); ?></span>
								<?php elseif ( $is_open && ! empty( $opening_time ) && ! empty( $closing_time ) ) : ?>
									<span class="tanbfd--open">
										<?php echo esc_html( $opening_time ); ?> - <?php echo esc_html( $closing_time ); ?>
									</span>
								<?php else : ?>
									<span class="tanbfd--closed"><?php echo esc_html__( 'CLOSED', 'the-another-blocks-for-dokan' ); ?></span>
								<?php endif; ?>
							</span>
						</li>
					<?php endforeach; ?>
				</ul>
			</div>
		<?php else : ?>
			<div class="tanbfd--vendor-store-hours-compact">
				<p>
					<?php
					$current_day_schedule = $dokan_store_times[ $today ] ?? array();
					if ( ! empty( $current_day_schedule['opening_time'] ) && ! empty( $current_day_schedule['closing_time'] ) ) {
						echo esc_html(
							sprintf(
								// translators: %1$s is opening time, %2$s is closing time.
								__( 'Today: %1$s - %2$s', 'the-another-blocks-for-dokan' ),
								$current_day_schedule['opening_time'],
								$current_day_schedule['closing_time']
							)
						);
					} else {
						echo esc_html__( 'Store hours vary. Please check detailed schedule.', 'the-another-blocks-for-dokan' );
					}
					?>
				</p>
			</div>
		<?php endif; ?>
	</div>
	<?php

	return ob_get_clean();
}
