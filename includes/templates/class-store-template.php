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
	 * Request-scoped override produced by the `tanbfd_store_template_override`
	 * filter inside {@see self::override_store_template()}. Consumed by
	 * {@see self::provide_store_block_templates()} during the WP core
	 * `get_block_templates` call so the filter's return value reaches
	 * `locate_block_template()` without re-firing the filter in unrelated
	 * contexts (Site Editor, REST, theme.json processing).
	 *
	 * @var \WP_Block_Template|null
	 */
	private ?\WP_Block_Template $request_override = null;

	/**
	 * Initialization method.
	 *
	 * Registers both store templates with WP core's block-template registry
	 * (via `parent::init()` for `dokan-store` and `register_toc_template()` for
	 * `dokan-store-toc`) so the Site Editor auto-lists them for user
	 * customization. The resulting precedence when `get_block_templates()` runs:
	 *
	 *   theme file > Site Editor DB customization > plugin registry > plugin default
	 *
	 * `provide_store_block_templates()` layers in the request-scoped override
	 * from `tanbfd_store_template_override`: when set, the override replaces our
	 * own registry entry (but NOT a user customization or a theme file, which
	 * always reflect explicit user/theme intent).
	 *
	 * @return void
	 */
	public function init(): void {
		parent::init();
		$this->register_toc_template();

		// Hook into template_include at priority 100 (after Dokan's priority 99).
		add_filter( 'template_include', array( $this, 'override_store_template' ), 100, 1 );

		// Layer the request-scoped override and plugin-default fallback into
		// WP core's block-template query results.
		add_filter( 'get_block_templates', array( $this, 'provide_store_block_templates' ), 10, 3 );
	}

	/**
	 * Register the Terms & Conditions template with WP core.
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

		/**
		 * Filter the block template used for a given store tab.
		 *
		 * Fires exactly once per vendor page render (not during Site Editor /
		 * REST / theme.json lookups). Returning a {@see \WP_Block_Template}
		 * swaps it in for the default; returning null keeps the plugin default.
		 *
		 * @since 1.0.0
		 *
		 * @param \WP_Block_Template|null $template      Override template, or null to use the plugin default.
		 * @param string                  $template_slug Internal slug (dokan-store / dokan-store-toc).
		 * @param string                  $current_tab   Store tab identifier (products / toc).
		 */
		$override = apply_filters( 'tanbfd_store_template_override', null, $template_slug, $current_tab );

		$this->request_override = $override instanceof \WP_Block_Template ? $override : null;

		try {
			return locate_block_template( $template, 'wp_template', array( $template_slug ) );
		} finally {
			$this->request_override = null;
		}
	}

	/**
	 * Feed our plugin templates into WP core's block template query results.
	 *
	 * Hooked on `get_block_templates`. Two responsibilities:
	 *
	 * 1. If {@see self::override_store_template()} captured a request-scoped
	 *    override (from the `tanbfd_store_template_override` filter), inject
	 *    that into `$query_result` for the matching slug so
	 *    `locate_block_template()` finds it.
	 * 2. Otherwise, append our plugin default as a last-resort fallback — only
	 *    when no other source (active theme file, Site Editor DB customization,
	 *    WP block template registry, earlier-priority plugin) has already
	 *    provided a template with the same slug.
	 *
	 * The `tanbfd_store_template_override` filter is NOT fired from here — it
	 * fires once per vendor page render from `override_store_template()` so its
	 * timing and side effects match the pre-1.0.14 contract.
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

		// Bucket existing results by provenance: our own registry entries (the
		// plugin default from register_block_template()) are preempted by the
		// request-scoped override, while external entries (theme file, Site
		// Editor DB customization, another plugin) always win.
		$external_slugs           = array();
		$our_registry_idx_by_slug = array();
		foreach ( $query_result as $idx => $existing ) {
			if ( ! $existing instanceof \WP_Block_Template ) {
				continue;
			}
			if ( ! is_string( $existing->slug ) ) {
				continue;
			}
			if ( $this->is_own_registered_template( $existing ) ) {
				$our_registry_idx_by_slug[ $existing->slug ] = $idx;
				continue;
			}
			$external_slugs[ $existing->slug ] = true;
		}

		// (1) Request-scoped override from tanbfd_store_template_override.
		// Injected only when no external (theme / DB / other-plugin) entry
		// already claims the slug — those always reflect explicit user/theme
		// intent and take precedence over a programmatic substitution.
		if ( $this->request_override instanceof \WP_Block_Template
			&& is_string( $this->request_override->slug )
			&& in_array( $this->request_override->slug, $requested_slugs, true )
			&& ! isset( $external_slugs[ $this->request_override->slug ] )
		) {
			$override_slug = $this->request_override->slug;

			if ( isset( $our_registry_idx_by_slug[ $override_slug ] ) ) {
				unset( $query_result[ $our_registry_idx_by_slug[ $override_slug ] ] );
				$query_result = array_values( $query_result );
				unset( $our_registry_idx_by_slug[ $override_slug ] );
			}

			$query_result[]                   = $this->request_override;
			$external_slugs[ $override_slug ] = true;
		}

		// (2) Plugin default as last-resort fallback — only when neither an
		// external source nor our own registry entry already provides the slug.
		// This path matters on WP < 6.7 (where register_block_template() is
		// unavailable) or if registration was skipped for any reason.
		foreach ( self::TAB_TEMPLATE_MAP as $slug ) {
			if ( ! in_array( $slug, $requested_slugs, true ) ) {
				continue;
			}
			if ( isset( $external_slugs[ $slug ] ) ) {
				continue;
			}
			if ( isset( $our_registry_idx_by_slug[ $slug ] ) ) {
				continue;
			}

			$default = $this->get_block_template_for_slug( $slug );
			if ( $default instanceof \WP_Block_Template ) {
				$query_result[] = $default;
			}
		}

		return $query_result;
	}

	/**
	 * Determine whether a block template originated from this plugin's call to
	 * {@see register_block_template()}.
	 *
	 * The registry sets `$template->source = 'plugin'` and `$template->plugin`
	 * to the namespace passed in (our PLUGIN_SLUG). Note: `$template->theme` is
	 * set to the active theme's stylesheet, NOT the plugin slug, so it cannot
	 * be used for provenance checks.
	 *
	 * @param \WP_Block_Template $template Template to inspect.
	 * @return bool True if the template came from this plugin's registry registration.
	 */
	private function is_own_registered_template( \WP_Block_Template $template ): bool {
		return 'plugin' === $template->source
			&& self::PLUGIN_SLUG === $template->plugin;
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
