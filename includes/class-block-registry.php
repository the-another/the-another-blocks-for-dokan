<?php
/**
 * Block registration handler.
 *
 * @package AnotherBlocksDokan
 * @since 1.0.0
 */

namespace The_Another\Plugin\Blocks_Dokan;

/**
 * Block registry class.
 */
class Block_Registry {

	/**
	 * Registered blocks.
	 *
	 * @var array<string, string>
	 */
	private array $blocks = array();

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->register_block_paths();
	}

	/**
	 * Register all blocks.
	 *
	 * @return void
	 */
	public function register_all_blocks(): void {
		foreach ( $this->blocks as $block_name => $block_dir ) {
			$this->register_block( $block_name, $block_dir );
		}
	}

	/**
	 * Register a single block.
	 *
	 * @param string $block_name Block name (e.g., 'the-another/blocks-for-dokan-store-header').
	 * @param string $block_dir  Block directory path.
	 * @return void
	 */
	private function register_block( string $block_name, string $block_dir ): void {
		$block_json_path = $block_dir . '/block.json';

		if ( ! file_exists( $block_json_path ) ) {
			return;
		}

		// Load render function if it exists.
		$render_file     = $block_dir . '/render.php';
		$render_callback = null;

		if ( file_exists( $render_file ) ) {
			require_once $render_file;

			// Map block name to render function.
			// Use the expected block name from our registry.
			$render_function_map = array(
				'the-another/blocks-for-dokan-vendor-store-header'           => 'theabd_render_vendor_store_header_block',
				'the-another/blocks-for-dokan-vendor-store-sidebar'          => 'theabd_render_vendor_store_sidebar_block',
				'the-another/blocks-for-dokan-vendor-store-tabs'             => 'theabd_render_vendor_store_tabs_block',
				'the-another/blocks-for-dokan-vendor-store-terms-conditions' => 'theabd_render_vendor_store_terms_conditions_block',
				'the-another/blocks-for-dokan-vendor-query-loop'              => 'theabd_render_vendor_query_loop_block',
				'the-another/blocks-for-dokan-vendor-query-pagination'        => 'theabd_render_vendor_query_pagination_block',
				'the-another/blocks-for-dokan-vendor-card'                    => 'theabd_render_vendor_card_block',
				'the-another/blocks-for-dokan-vendor-search'                  => 'theabd_render_vendor_search_block',
				'the-another/blocks-for-dokan-vendor-store-name'              => 'theabd_render_vendor_store_name_block',
				'the-another/blocks-for-dokan-vendor-avatar'                  => 'theabd_render_vendor_avatar_block',
				'the-another/blocks-for-dokan-vendor-rating'                  => 'theabd_render_vendor_rating_block',
				'the-another/blocks-for-dokan-vendor-store-address'           => 'theabd_render_vendor_store_address_block',
				'the-another/blocks-for-dokan-vendor-store-phone'             => 'theabd_render_vendor_store_phone_block',
				'the-another/blocks-for-dokan-vendor-store-status'            => 'theabd_render_vendor_store_status_block',
				'the-another/blocks-for-dokan-vendor-store-banner'            => 'theabd_render_vendor_store_banner_block',
				'the-another/blocks-for-dokan-product-vendor-info'            => 'theabd_render_product_vendor_info_block',
				'the-another/blocks-for-dokan-more-from-seller'               => 'theabd_render_more_from_seller_block',
				'the-another/blocks-for-dokan-vendor-contact-form'            => 'theabd_render_vendor_contact_form_block',
				'the-another/blocks-for-dokan-vendor-store-location'          => 'theabd_render_vendor_store_location_block',
				'the-another/blocks-for-dokan-vendor-store-hours'             => 'theabd_render_vendor_store_hours_block',
				'the-another/blocks-for-dokan-become-vendor-cta'              => 'theabd_render_become_vendor_cta_block',
			);

			if ( isset( $render_function_map[ $block_name ] ) && function_exists( $render_function_map[ $block_name ] ) ) {
				$render_callback = $render_function_map[ $block_name ];
			}
		}

		// Register block with render callback.
		// WordPress will read the block name from block.json automatically.
		$args = array();
		if ( $render_callback ) {
			$args['render_callback'] = $render_callback;
		}

		// Use register_block_type_from_metadata for better compatibility.
		// Pass the directory path, not the block.json file path.
		if ( function_exists( 'register_block_type_from_metadata' ) ) {
			register_block_type_from_metadata( $block_dir, $args );
		} else {
			register_block_type( $block_dir, $args );
		}
	}

	/**
	 * Register block paths.
	 *
	 * @return void
	 */
	private function register_block_paths(): void {
		$blocks_dir = \ANOTHER_BLOCKS_DOKAN_PLUGIN_DIR . 'blocks/';

		// Vendor store profile blocks.
		$this->blocks['the-another/blocks-for-dokan-vendor-store-header']           = $blocks_dir . 'vendor-store-header';
		$this->blocks['the-another/blocks-for-dokan-vendor-store-sidebar']          = $blocks_dir . 'vendor-store-sidebar';
		$this->blocks['the-another/blocks-for-dokan-vendor-store-tabs']             = $blocks_dir . 'vendor-store-tabs';
		$this->blocks['the-another/blocks-for-dokan-vendor-store-terms-conditions'] = $blocks_dir . 'vendor-store-terms-conditions';

		// Vendor listing blocks.
		$this->blocks['the-another/blocks-for-dokan-vendor-query-loop']       = $blocks_dir . 'vendor-query-loop';
		$this->blocks['the-another/blocks-for-dokan-vendor-query-pagination'] = $blocks_dir . 'vendor-query-pagination';
		$this->blocks['the-another/blocks-for-dokan-vendor-card']             = $blocks_dir . 'vendor-card';
		$this->blocks['the-another/blocks-for-dokan-vendor-search']           = $blocks_dir . 'vendor-search';

		// Vendor field blocks (for use inside vendor query loop).
		$this->blocks['the-another/blocks-for-dokan-vendor-store-name']    = $blocks_dir . 'vendor-store-name';
		$this->blocks['the-another/blocks-for-dokan-vendor-avatar']        = $blocks_dir . 'vendor-avatar';
		$this->blocks['the-another/blocks-for-dokan-vendor-rating']        = $blocks_dir . 'vendor-rating';
		$this->blocks['the-another/blocks-for-dokan-vendor-store-address'] = $blocks_dir . 'vendor-store-address';
		$this->blocks['the-another/blocks-for-dokan-vendor-store-phone']   = $blocks_dir . 'vendor-store-phone';
		$this->blocks['the-another/blocks-for-dokan-vendor-store-status']  = $blocks_dir . 'vendor-store-status';
		$this->blocks['the-another/blocks-for-dokan-vendor-store-banner']  = $blocks_dir . 'vendor-store-banner';

		// Product integration blocks.
		$this->blocks['the-another/blocks-for-dokan-product-vendor-info'] = $blocks_dir . 'product-vendor-info';
		$this->blocks['the-another/blocks-for-dokan-more-from-seller']    = $blocks_dir . 'more-from-seller';

		// Account/registration blocks.
		$this->blocks['the-another/blocks-for-dokan-become-vendor-cta'] = $blocks_dir . 'become-vendor-cta';

		// Widget blocks.
		$this->blocks['the-another/blocks-for-dokan-vendor-contact-form']   = $blocks_dir . 'vendor-contact-form';
		$this->blocks['the-another/blocks-for-dokan-vendor-store-location'] = $blocks_dir . 'vendor-store-location';
		$this->blocks['the-another/blocks-for-dokan-vendor-store-hours']    = $blocks_dir . 'vendor-store-hours';

		/**
		 * Filter registered block paths.
		 *
		 * @since 1.0.0
		 *
		 * @param array<string, string> $blocks Block paths.
		 */
		$this->blocks = apply_filters( 'dokan_blocks_registered_blocks', $this->blocks );
	}

	/**
	 * Check if a block is registered.
	 *
	 * @param string $block_name Block name.
	 * @return bool
	 */
	public function is_registered( string $block_name ): bool {
		return isset( $this->blocks[ $block_name ] );
	}

	/**
	 * Get all registered block names.
	 *
	 * @return array<string>
	 */
	public function get_registered_blocks(): array {
		return array_keys( $this->blocks );
	}
}
