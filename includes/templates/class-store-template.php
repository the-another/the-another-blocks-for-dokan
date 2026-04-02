<?php
/**
 * Single vendor store page template.
 *
 * Handles all store page tabs (products, terms & conditions, etc.)
 * with selective override - only overrides tabs we have block templates for,
 * falling back to Dokan's native templates for unknown tabs (Pro features, extensions).
 *
 * @package AnotherBlocksDokan
 * @since 1.0.0
 */

namespace The_Another\Plugin\Blocks_Dokan\Templates;

// Exit if accessed directly.
use The_Another\Plugin\Blocks_Dokan\Helpers\Context_Detector;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Store template class.
 */
class Store_Template extends Abstract_Dokan_Template {

	/**
	 * Template slug.
	 *
	 * @var string
	 */
	const SLUG = 'dokan-store';

	/**
	 * Store TOC template slug.
	 *
	 * @var string
	 */
	const SLUG_TOC = 'dokan-store-toc';

	/**
	 * Store tab identifier for products (default).
	 *
	 * @var string
	 */
	const TAB_PRODUCTS = 'products';

	/**
	 * Store tab identifier for terms and conditions.
	 *
	 * @var string
	 */
	const TAB_TOC = 'toc';

	/**
	 * Map of supported tabs to their template slugs.
	 *
	 * @var array<string, string>
	 */
	private const TAB_TEMPLATE_MAP = array(
		self::TAB_PRODUCTS => self::SLUG,
		self::TAB_TOC      => self::SLUG_TOC,
	);

	/**
	 * Initialization method.
	 *
	 * @return void
	 */
	public function init(): void {
		parent::init();

		// Also register the TOC template.
		$this->register_toc_template();

		// Hook into template_include at priority 100 (after Dokan's priority 99).
		add_filter( 'template_include', array( $this, 'override_store_template' ), 100, 1 );
	}

	/**
	 * Register the Terms & Conditions template.
	 *
	 * @return void
	 */
	protected function register_toc_template(): void {
		if ( ! function_exists( 'register_block_template' ) ) {
			return;
		}

		$template_name = self::PLUGIN_SLUG . '//' . self::SLUG_TOC;
		$template_path = \ANOTHER_BLOCKS_DOKAN_PLUGIN_DIR . 'templates/store-toc.html';

		if ( ! file_exists( $template_path ) ) {
			return;
		}

		$content = file_get_contents( $template_path );

		register_block_template(
			$template_name,
			array(
				'title'       => __( 'Vendor Terms & Conditions', 'another-dokan-blocks' ),
				'description' => __( 'Displays a vendor\'s terms and conditions page.', 'another-dokan-blocks' ),
				'plugin'      => self::PLUGIN_SLUG,
				'content'     => false !== $content ? $content : '',
			)
		);
	}

	/**
	 * Override Dokan's store template with our block template.
	 *
	 * Only overrides templates for tabs we explicitly support.
	 * Unknown tabs (from Pro, extensions) fall back to Dokan's native templates.
	 *
	 * @param string $template Template path.
	 * @return string Modified template path.
	 */
	public function override_store_template( string $template ): string {
		if ( ! Context_Detector::is_store_page() ) {
			return $template;
		}

		// Check if we're in a block theme.
		if ( ! function_exists( 'wp_is_block_theme' ) || ! wp_is_block_theme() ) {
			return $template;
		}

		// Determine which tab we're on.
		$current_tab = $this->get_current_store_tab();

		// Only override if we have a block template for this tab.
		// Unknown tabs (from Pro, extensions) fall back to Dokan's native templates.
		if ( ! $this->has_block_template_for_tab( $current_tab ) ) {
			return $template;
		}

		// Get our block template for this tab.
		$template_slug = self::TAB_TEMPLATE_MAP[ $current_tab ];

		// Allow other plugins to override the template.
		$override_template = apply_filters( 'dokan_blocks_store_template_override', null, $template_slug, $current_tab );

		$block_template = $override_template ?: $this->get_block_template_for_slug( $template_slug );

		if ( ! $block_template ) {
			return $template;
		}

		// Set global variables for WordPress to use our block template.
		global $_wp_current_template_id, $_wp_current_template_content;
		// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound
		$_wp_current_template_id      = $block_template->id;
		// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound
		$_wp_current_template_content = $block_template->content;

		// Return path to WordPress block template canvas.
		// This will trigger WordPress to render our registered block template.
		$canvas_path = ABSPATH . WPINC . '/template-canvas.php';
		if ( file_exists( $canvas_path ) ) {
			return $canvas_path;
		}

		return $template;
	}

	/**
	 * Get the current store tab based on query vars.
	 *
	 * @return string Tab identifier (e.g., 'products', 'toc', 'store_review').
	 */
	public function get_current_store_tab(): string {
		// Check for Terms & Conditions tab.
		if ( get_query_var( 'toc' ) ) {
			return self::TAB_TOC;
		}

		// Check for Reviews tab (Dokan Pro).
		if ( get_query_var( 'store_review' ) ) {
			return 'store_review';
		}

		// Default to products tab.
		return self::TAB_PRODUCTS;
	}

	/**
	 * Check if we have a block template for a given tab.
	 *
	 * @param string $tab Tab identifier.
	 * @return bool Whether we have a block template for this tab.
	 */
	private function has_block_template_for_tab( string $tab ): bool {
		if ( ! isset( self::TAB_TEMPLATE_MAP[ $tab ] ) ) {
			return false;
		}

		$template_slug = self::TAB_TEMPLATE_MAP[ $tab ];
		$template_path = $this->get_template_file_path_for_slug( $template_slug );

		return file_exists( $template_path );
	}

	/**
	 * Get template file path for a specific slug.
	 *
	 * @param string $slug Template slug.
	 * @return string Template file path.
	 */
	private function get_template_file_path_for_slug( string $slug ): string {
		$template_file_map = array(
			self::SLUG     => 'store.html',
			self::SLUG_TOC => 'store-toc.html',
		);

		$template_file = $template_file_map[ $slug ] ?? $slug . '.html';

		return \ANOTHER_BLOCKS_DOKAN_PLUGIN_DIR . 'templates/' . $template_file;
	}

	/**
	 * Get block template object for a specific slug.
	 *
	 * @param string $slug Template slug.
	 * @return \WP_Block_Template|null Block template object or null if not found.
	 */
	private function get_block_template_for_slug( string $slug ): ?\WP_Block_Template {
		$template_id = self::PLUGIN_SLUG . '//' . $slug;

		// Try to get the template using WordPress's get_block_template function.
		if ( function_exists( 'get_block_template' ) ) {
			$template = get_block_template( $template_id, 'wp_template' );
			if ( $template ) {
				return $template;
			}
		}

		// Fallback: manually create the template object from our file.
		$template_path = $this->get_template_file_path_for_slug( $slug );
		if ( ! file_exists( $template_path ) ) {
			return null;
		}

		$template_content = file_get_contents( $template_path );
		if ( false === $template_content ) {
			return null;
		}

		$title       = $this->get_template_title_for_slug( $slug );
		$description = $this->get_template_description_for_slug( $slug );

		$block_template                 = new \WP_Block_Template();
		$block_template->id             = $template_id;
		$block_template->theme          = self::PLUGIN_SLUG;
		$block_template->content        = $template_content;
		$block_template->slug           = $slug;
		$block_template->title          = $title;
		$block_template->description    = $description;
		$block_template->source         = 'plugin';
		$block_template->type           = 'wp_template';
		$block_template->area           = 'uncategorized';
		$block_template->has_theme_file = false;
		$block_template->is_custom      = false;

		return $block_template;
	}

	/**
	 * Get template title for a specific slug.
	 *
	 * @param string $slug Template slug.
	 * @return string Template title.
	 */
	private function get_template_title_for_slug( string $slug ): string {
		$titles = array(
			self::SLUG     => __( 'Single Vendor Store', 'another-dokan-blocks' ),
			self::SLUG_TOC => __( 'Vendor Terms & Conditions', 'another-dokan-blocks' ),
		);

		return $titles[ $slug ] ?? __( 'Vendor Store', 'another-dokan-blocks' );
	}

	/**
	 * Get template description for a specific slug.
	 *
	 * @param string $slug Template slug.
	 * @return string Template description.
	 */
	private function get_template_description_for_slug( string $slug ): string {
		$descriptions = array(
			self::SLUG     => __( 'Displays a single vendor store page with products and information.', 'another-dokan-blocks' ),
			self::SLUG_TOC => __( 'Displays a vendor\'s terms and conditions page.', 'another-dokan-blocks' ),
		);

		return $descriptions[ $slug ] ?? __( 'Vendor store template.', 'another-dokan-blocks' );
	}

	/**
	 * Check if this template should be rendered.
	 *
	 * @return bool
	 */
	protected function should_render_template(): bool {
		return Context_Detector::is_store_page();
	}

	/**
	 * Get template title.
	 *
	 * @return string
	 */
	public function get_template_title(): string {
		return __( 'Single Vendor Store', 'another-dokan-blocks' );
	}

	/**
	 * Get template description.
	 *
	 * @return string
	 */
	public function get_template_description(): string {
		return __( 'Displays a single vendor store page with products and information.', 'another-dokan-blocks' );
	}
}