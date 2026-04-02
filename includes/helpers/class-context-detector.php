<?php
/**
 * Context detection helper.
 *
 * @package AnotherBlocksDokan
 * @since 1.0.0
 */

namespace The_Another\Plugin\Blocks_Dokan\Helpers;

/**
 * Context detector class.
 */
class Context_Detector {

	/**
	 * Get vendor ID from current context.
	 *
	 * @return int|null Vendor ID or null if not found.
	 */
	public static function get_vendor_id(): ?int {
		// Try Dokan's store object.
		if ( function_exists( 'dokan' ) && method_exists( dokan(), 'get_store_info' ) ) {
			$store = dokan()->vendor->get( get_query_var( 'author' ) );
			if ( $store && $store->get_id() > 0 ) {
				return absint( $store->get_id() );
			}
		}

		// Try to get from 'store' query var (Dokan store page URL).
		$store_name = get_query_var( 'store', '' );
		if ( ! empty( $store_name ) ) {
			$store_user = get_user_by( 'slug', $store_name );
			if ( $store_user && function_exists( 'dokan_is_user_seller' ) && dokan_is_user_seller( $store_user->ID ) ) {
				return absint( $store_user->ID );
			}
		}

		// Try to get from query var (author page / store page).
		$vendor_id = get_query_var( 'author', 0 );
		if ( ! empty( $vendor_id ) ) {
			$vendor_id = absint( $vendor_id );
			if ( $vendor_id > 0 && function_exists( 'dokan_is_user_seller' ) && dokan_is_user_seller( $vendor_id ) ) {
				return $vendor_id;
			}
		}

		// Try to get from product context.
		global $post;
		if ( isset( $post ) && 'product' === get_post_type( $post ) ) {
			$vendor_id = absint( $post->post_author );
			if ( $vendor_id > 0 && function_exists( 'dokan_is_user_seller' ) && dokan_is_user_seller( $vendor_id ) ) {
				return $vendor_id;
			}
		}

		return null;
	}

	/**
	 * Get product ID from current context.
	 *
	 * @return int|null Product ID or null if not found.
	 */
	public static function get_product_id(): ?int {
		global $post;

		if ( isset( $post ) && 'product' === $post->post_type ) {
			return absint( $post->ID );
		}

		// Try to get from query var.
		$product_id = get_query_var( 'product_id', 0 );
		if ( ! empty( $product_id ) ) {
			return absint( $product_id );
		}

		return null;
	}

	/**
	 * Check if we're on a vendor store page.
	 *
	 * @return bool
	 */
	public static function is_store_page(): bool {
		// If we're on a single product page, we're NOT on a store page.
		// This prevents auction/item products from being treated as store pages.
		if ( is_singular( 'product' ) ) {
			return false;
		}

		// First, try Dokan's native function if available.
		if ( function_exists( 'dokan_is_store_page' ) && dokan_is_store_page() ) {
			return true;
		}

		// Check query vars.
		$store_name = get_query_var( 'store', '' );
		if ( ! empty( $store_name ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Check if we're on a product page.
	 *
	 * @return bool
	 */
	public static function is_product_page(): bool {
		return is_singular( 'product' );
	}

	/**
	 * Check if we're on a vendor listing page.
	 *
	 * @return bool
	 */
	public static function is_store_list_page(): bool {
		return is_page( get_option( 'dokan_pages', array() )['store_listing'] ?? 0 );
	}
}
