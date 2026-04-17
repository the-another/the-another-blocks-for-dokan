<?php
/**
 * Single vendor store page template.
 *
 * Handles all store page tabs (products, terms & conditions, etc.)
 * with selective override - only overrides tabs we have block templates for,
 * falling back to Dokan's native templates for unknown tabs (Pro features, extensions).
 *
 * @package The_Another_Blocks_For_Dokan
 * @since 1.0.0
 */

namespace The_Another\Plugin\Blocks_For_Dokan\Templates;

// Exit if accessed directly.

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

		// Feed our plugin-provided templates into WP core's block template registry
		// so locate_block_template() can resolve them via the normal flow.
		add_filter( 'get_block_templates', array( $this, 'provide_store_block_templates' ), 10, 3 );
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
		$template_path = THE_ANOTHER_BLOCKS_FOR_DOKAN_PLUGIN_DIR . 'templates/store-toc.html';

		if ( ! file_exists( $template_path ) ) {
			return;
		}

		$content = file_get_contents( $template_path ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- Reading local template file.

		register_block_template(
			$template_name,
			array(
				'title'       => __( 'Vendor Terms & Conditions', 'the-another-blocks-for-dokan' ),
				'description' => __( 'Displays a vendor\'s terms and conditions page.', 'the-another-blocks-for-dokan' ),
				'plugin'      => self::PLUGIN_SLUG,
				'content'     => false !== $content ? $content : '',
			)
		);
	}

	/**
	 * Override Dokan's store template with our block template under block themes.
	 *
	 * Delegates the actual resolution (and the canvas path) to WordPress core's
	 * public {@see locate_block_template()} helper, which in turn calls
	 * {@see get_block_templates()} — fed by {@see self::provide_store_block_templates()}
	 * — to find our plugin template, sets the `$_wp_current_template_*` globals,
	 * and returns wp-includes/template-canvas.php itself. Our code stays free of
	 * direct references to ABSPATH or WPINC, which wp.org review flagged as a
	 * violation.
	 *
	 * Only overrides templates for tabs we explicitly support. Unknown tabs
	 * (from Pro, extensions) fall back to Dokan's native templates.
	 *
	 * @param string $template Template path.
	 * @return string Modified template path.
	 */
	public function override_store_template( string $template ): string {
		if ( ! tanbfd_is_store_page() ) {
			return $template;
		}

		if ( ! function_exists( 'wp_is_block_theme' ) || ! wp_is_block_theme() ) {
			return $template;
		}

		$current_tab = $this->get_current_store_tab();

		if ( ! $this->has_block_template_for_tab( $current_tab ) ) {
			return $template;
		}

		$template_slug = self::TAB_TEMPLATE_MAP[ $current_tab ];

		if ( ! function_exists( 'locate_block_template' ) ) {
			return $template;
		}

		return locate_block_template( $template, 'wp_template', array( $template_slug ) );
	}

	/**
	 * Feed our plugin templates into WP core's block template query results.
	 *
	 * Hooked on `get_block_templates`. When WordPress queries the block template
	 * registry with a `slug__in` filter that matches one of our supported slugs,
	 * we append the plugin-provided {@see \WP_Block_Template} so
	 * {@see locate_block_template()} can resolve it without requiring our
	 * templates to live in the active theme.
	 *
	 * Honors the `tanbfd_store_template_override` filter so third parties can
	 * swap in a custom {@see \WP_Block_Template} for a given slug/tab.
	 *
	 * @param \WP_Block_Template[] $query_result  Current list of resolved templates.
	 * @param array<string,mixed>  $query         The query being processed.
	 * @param string               $template_type Either 'wp_template' or 'wp_template_part'.
	 * @return \WP_Block_Template[] Possibly-augmented list of templates.
	 */
	public function provide_store_block_templates( $query_result, $query, $template_type ) {
		if ( 'wp_template' !== $template_type ) {
			return $query_result;
		}

		if ( ! is_array( $query_result ) ) {
			$query_result = array();
		}

		$requested_slugs = is_array( $query ) && isset( $query['slug__in'] ) && is_array( $query['slug__in'] )
			? $query['slug__in']
			: array();

		if ( empty( $requested_slugs ) ) {
			return $query_result;
		}

		foreach ( self::TAB_TEMPLATE_MAP as $tab => $slug ) {
			if ( ! in_array( $slug, $requested_slugs, true ) ) {
				continue;
			}

			/**
			 * Filter the block template used for a given store tab.
			 *
			 * @since 1.0.0
			 *
			 * @param \WP_Block_Template|null $template       Override template, or null to use the plugin default.
			 * @param string                  $template_slug  Internal slug (dokan-store / dokan-store-toc).
			 * @param string                  $current_tab    Store tab identifier (products / toc).
			 */
			$override = apply_filters( 'tanbfd_store_template_override', null, $slug, $tab );

			$template = $override instanceof \WP_Block_Template
				? $override
				: $this->get_block_template_for_slug( $slug );

			if ( $template instanceof \WP_Block_Template ) {
				$query_result[] = $template;
			}
		}

		return $query_result;
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

		return THE_ANOTHER_BLOCKS_FOR_DOKAN_PLUGIN_DIR . 'templates/' . $template_file;
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

		$template_content = file_get_contents( $template_path ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- Reading local template file.
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
			self::SLUG     => __( 'Single Vendor Store', 'the-another-blocks-for-dokan' ),
			self::SLUG_TOC => __( 'Vendor Terms & Conditions', 'the-another-blocks-for-dokan' ),
		);

		return $titles[ $slug ] ?? __( 'Vendor Store', 'the-another-blocks-for-dokan' );
	}

	/**
	 * Get template description for a specific slug.
	 *
	 * @param string $slug Template slug.
	 * @return string Template description.
	 */
	private function get_template_description_for_slug( string $slug ): string {
		$descriptions = array(
			self::SLUG     => __( 'Displays a single vendor store page with products and information.', 'the-another-blocks-for-dokan' ),
			self::SLUG_TOC => __( 'Displays a vendor\'s terms and conditions page.', 'the-another-blocks-for-dokan' ),
		);

		return $descriptions[ $slug ] ?? __( 'Vendor store template.', 'the-another-blocks-for-dokan' );
	}

	/**
	 * Check if this template should be rendered.
	 *
	 * @return bool
	 */
	protected function should_render_template(): bool {
		return tanbfd_is_store_page();
	}

	/**
	 * Get template title.
	 *
	 * @return string
	 */
	public function get_template_title(): string {
		return __( 'Single Vendor Store', 'the-another-blocks-for-dokan' );
	}

	/**
	 * Get template description.
	 *
	 * @return string
	 */
	public function get_template_description(): string {
		return __( 'Displays a single vendor store page with products and information.', 'the-another-blocks-for-dokan' );
	}
}
