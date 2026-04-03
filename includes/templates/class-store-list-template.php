<?php
/**
 * Vendor listing page template.
 *
 * @package AnotherBlocksForDokan
 * @since 1.0.0
 */

namespace The_Another\Plugin\Blocks_Dokan\Templates;

use The_Another\Plugin\Blocks_Dokan\Helpers\Context_Detector;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


/**
 * Store list template class.
 */
class Store_List_Template extends Abstract_Dokan_Template {

	/**
	 * Template slug.
	 *
	 * @var string
	 */
	const SLUG = 'dokan-store-list';

	/**
	 * Initialization method.
	 *
	 * @return void
	 */
	public function init(): void {
		parent::init();
		add_filter( 'page_template_hierarchy', array( $this, 'add_template_to_hierarchy' ), 10, 1 );
	}

	/**
	 * Add template to page template hierarchy when on store listing page.
	 *
	 * @param array $templates Template hierarchy array.
	 * @return array Modified template hierarchy array.
	 */
	public function add_template_to_hierarchy( array $templates ): array {
		if ( ! Context_Detector::is_store_list_page() ) {
			return $templates;
		}

		// Prepend our template slug to the hierarchy so it's used first.
		array_unshift( $templates, static::SLUG );

		return $templates;
	}

	/**
	 * Check if this template should be rendered.
	 *
	 * @return bool
	 */
	protected function should_render_template(): bool {
		return Context_Detector::is_store_list_page();
	}

	/**
	 * Get template title.
	 *
	 * @return string
	 */
	public function get_template_title(): string {
		return __( 'Vendor Listing', 'another-blocks-for-dokan' );
	}

	/**
	 * Get template description.
	 *
	 * @return string
	 */
	public function get_template_description(): string {
		return __( 'Displays a listing of all vendor stores.', 'another-blocks-for-dokan' );
	}
}
