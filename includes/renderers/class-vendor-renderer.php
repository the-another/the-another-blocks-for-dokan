<?php
/**
 * Vendor data renderer.
 *
 * @package AnotherBlocksForDokan
 * @since 1.0.0
 */

namespace The_Another\Plugin\Blocks_Dokan\Renderers;

/**
 * Vendor renderer class.
 */
class Vendor_Renderer {

	/**
	 * Get vendor data for rendering.
	 *
	 * @param int $vendor_id Vendor ID.
	 * @return array<string, mixed>|null Vendor data or null if not found.
	 */
	public static function get_vendor_data( int $vendor_id ): ?array {
		if ( ! $vendor_id || ! dokan_is_user_seller( $vendor_id ) ) {
			return null;
		}

		$vendor = dokan()->vendor->get( $vendor_id );
		if ( ! $vendor ) {
			return null;
		}

		$store_info = $vendor->get_shop_info();

		return array(
			'id'              => $vendor_id,
			'shop_name'       => $vendor->get_shop_name(),
			'shop_url'        => $vendor->get_shop_url(),
			'avatar'          => $vendor->get_avatar(),
			'banner'          => $vendor->get_banner(),
			'phone'           => $vendor->get_phone(),
			'email'           => $vendor->show_email() ? $vendor->get_email() : '',
			'address'         => dokan_get_seller_short_address( $vendor_id, false ),
			'rating'          => $vendor->get_rating(),
			'social_profiles' => $vendor->get_social_profiles(),
			'store_info'      => $store_info,
			'is_featured'     => $vendor->is_featured(),
		);
	}

	/**
	 * Get vendor store URL.
	 *
	 * @param int $vendor_id Vendor ID.
	 * @return string Store URL or empty string.
	 */
	public static function get_store_url( int $vendor_id ): string {
		if ( ! $vendor_id || ! dokan_is_user_seller( $vendor_id ) ) {
			return '';
		}

		$vendor = dokan()->vendor->get( $vendor_id );
		if ( ! $vendor ) {
			return '';
		}

		return esc_url( $vendor->get_shop_url() );
	}

	/**
	 * Check if vendor info field is hidden.
	 *
	 * @param string $field Field name (address, phone, email).
	 * @return bool True if hidden, false otherwise.
	 */
	public static function is_vendor_info_hidden( string $field ): bool {
		return dokan_is_vendor_info_hidden( $field );
	}

	/**
	 * Get readable seller rating HTML.
	 *
	 * @param int $vendor_id Vendor ID.
	 * @return string Rating HTML.
	 */
	public static function get_seller_rating_html( int $vendor_id ): string {
		if ( ! $vendor_id || ! dokan_is_user_seller( $vendor_id ) ) {
			return '';
		}

		return dokan_get_readable_seller_rating( $vendor_id );
	}

	/**
	 * Check if store is open.
	 *
	 * @param int $vendor_id Vendor ID.
	 * @return bool True if store is open.
	 */
	public static function is_store_open( int $vendor_id ): bool {
		if ( ! function_exists( 'dokan_is_store_open' ) ) {
			return true; // Default to open if function doesn't exist.
		}

		return dokan_is_store_open( $vendor_id );
	}

	/**
	 * Compute store open/closed status from context data without a DB query.
	 *
	 * Replicates the logic of dokan_is_store_open() using pre-fetched
	 * store_open_close data from $vendor->to_array().
	 *
	 * @param array<string, mixed> $store_open_close Store open/close data from vendor context.
	 * @param int                  $vendor_id        Vendor ID for filter compatibility.
	 * @return bool True if store is open.
	 */
	public static function is_store_open_from_context( array $store_open_close, int $vendor_id ): bool {
		if ( empty( $store_open_close['enabled'] ) || empty( $store_open_close['time'] ) ) {
			return false;
		}

		$dokan_store_times = $store_open_close['time'];

		$current_time = dokan_current_datetime();
		$today        = strtolower( $current_time->format( 'l' ) );

		// Check if status is closed.
		if ( empty( $dokan_store_times[ $today ] ) || ( isset( $dokan_store_times[ $today ]['status'] ) && 'close' === $dokan_store_times[ $today ]['status'] ) ) {
			return apply_filters( 'dokan_is_store_open', false, $today, $dokan_store_times, $vendor_id ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- Dokan core filter for compatibility.
		}

		// Get store opening time.
		$opening_times = ! empty( $dokan_store_times[ $today ]['opening_time'] ) ? $dokan_store_times[ $today ]['opening_time'] : '';
		$opening_time  = ! empty( $opening_times ) && is_array( $opening_times ) ? $opening_times[0] : $opening_times;
		$opening_time  = ! empty( $opening_time ) ? $current_time->modify( $opening_time ) : false;

		// Get closing time.
		$closing_times = ! empty( $dokan_store_times[ $today ]['closing_time'] ) ? $dokan_store_times[ $today ]['closing_time'] : '';
		$closing_time  = ! empty( $closing_times ) && is_array( $closing_times ) ? $closing_times[0] : $closing_times;
		$closing_time  = ! empty( $closing_time ) ? $current_time->modify( $closing_time ) : false;

		if ( empty( $opening_time ) || empty( $closing_time ) ) {
			return apply_filters( 'dokan_is_store_open', false, $today, $dokan_store_times, $vendor_id ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- Dokan core filter for compatibility.
		}

		$store_open = $opening_time <= $current_time && $closing_time >= $current_time;

		return apply_filters( 'dokan_is_store_open', $store_open, $today, $dokan_store_times, $vendor_id ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- Dokan core filter for compatibility.
	}
}
