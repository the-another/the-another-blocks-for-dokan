<?php
/**
 * REST controller for the Vendor Query Loop infinite scroll endpoint.
 *
 * @package AnotherBlocksForDokan
 * @since 1.1.0
 */

namespace The_Another\Plugin\Blocks_For_Dokan\Rest;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Returns rendered <li> markup for additional pages of the vendor query loop.
 */
class Vendor_Query_Loop_Controller {

	/**
	 * REST namespace.
	 */
	private const REST_NAMESPACE = 'another-blocks-for-dokan/v1';

	/**
	 * REST route.
	 */
	private const ROUTE = '/vendor-query-loop';

	/**
	 * Transient prefix used to cache parsed inner-block templates per query id.
	 */
	private const TPL_TRANSIENT_PREFIX = 'tanbfd_vql_tpl_';

	/**
	 * Register REST routes.
	 *
	 * @return void
	 */
	public function register_routes(): void {
		register_rest_route(
			self::REST_NAMESPACE,
			self::ROUTE,
			array(
				'methods'             => \WP_REST_Server::CREATABLE,
				'permission_callback' => '__return_true',
				'callback'            => array( $this, 'handle_request' ),
				'args'                => array(
					'queryId'    => array(
						'required'          => true,
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_key',
					),
					'postId'     => array(
						'required'          => true,
						'type'              => 'integer',
						'minimum'           => 1,
						'sanitize_callback' => 'absint',
					),
					'page'       => array(
						'required'          => true,
						'type'              => 'integer',
						'minimum'           => 2,
						'sanitize_callback' => 'absint',
					),
					'attributes' => array(
						'required' => true,
						'type'     => 'object',
					),
					'filters'    => array(
						'required' => false,
						'type'     => 'object',
						'default'  => array(),
					),
				),
			)
		);
	}

	/**
	 * Build the cache key used to persist a query loop's inner-block template.
	 *
	 * @param string $query_id Query identifier.
	 * @return string
	 */
	public static function template_cache_key( string $query_id ): string {
		return self::TPL_TRANSIENT_PREFIX . sanitize_key( $query_id );
	}

	/**
	 * Whitelist the attributes accepted from the client.
	 *
	 * @param array<string, mixed> $raw Raw attribute object from the request.
	 * @return array<string, mixed>
	 */
	private function sanitize_attributes( array $raw ): array {
		return array(
			'perPage'              => isset( $raw['perPage'] ) ? max( 1, min( 50, (int) $raw['perPage'] ) ) : 12,
			'columns'              => isset( $raw['columns'] ) ? max( 1, min( 6, (int) $raw['columns'] ) ) : 3,
			'displayLayout'        => isset( $raw['displayLayout'] ) && in_array( $raw['displayLayout'], array( 'grid', 'list' ), true )
				? $raw['displayLayout']
				: 'grid',
			'orderBy'              => isset( $raw['orderBy'] ) ? sanitize_text_field( (string) $raw['orderBy'] ) : 'name',
			'showFeaturedOnly'     => ! empty( $raw['showFeaturedOnly'] ),
			'enableInfiniteScroll' => true,
		);
	}

	/**
	 * Re-derive the inner-block template for a query loop from a post's content.
	 *
	 * Used as a fallback when the transient cache is missing/expired so that visitors
	 * still see the authored template instead of a stripped-down default card.
	 *
	 * @param int    $post_id  Post containing the block.
	 * @param string $query_id Deterministic query identifier we're searching for.
	 * @return array<int, array<string, mixed>>
	 */
	private function extract_template_from_post( int $post_id, string $query_id ): array {
		if ( $post_id <= 0 ) {
			return array();
		}

		$post = get_post( $post_id );
		if ( ! $post || empty( $post->post_content ) ) {
			return array();
		}

		if ( ! function_exists( 'tanbfd_vendor_query_loop_compute_query_id' ) ) {
			$render_file = THE_ANOTHER_BLOCKS_FOR_DOKAN_PLUGIN_DIR . 'blocks/vendor-query-loop/render.php';
			if ( file_exists( $render_file ) ) {
				require_once $render_file;
			}
		}

		$blocks = parse_blocks( $post->post_content );
		$found  = array();

		$walker = static function ( array $nodes ) use ( &$walker, $post_id, $query_id, &$found ): void {
			foreach ( $nodes as $node ) {
				if ( ! empty( $found ) ) {
					return;
				}
				$name = isset( $node['blockName'] ) ? (string) $node['blockName'] : '';
				if ( 'the-another/blocks-for-dokan-vendor-query-loop' === $name ) {
					$attrs     = isset( $node['attrs'] ) && is_array( $node['attrs'] ) ? $node['attrs'] : array();
					$candidate = tanbfd_vendor_query_loop_compute_query_id( $post_id, $attrs );
					if ( $candidate === $query_id ) {
						$inner = isset( $node['innerBlocks'] ) && is_array( $node['innerBlocks'] ) ? $node['innerBlocks'] : array();
						foreach ( $inner as $child ) {
							if ( isset( $child['blockName'] ) && 'the-another/blocks-for-dokan-vendor-card' === $child['blockName'] ) {
								$found[] = $child;
							}
						}
						return;
					}
				}
				if ( ! empty( $node['innerBlocks'] ) && is_array( $node['innerBlocks'] ) ) {
					$walker( $node['innerBlocks'] );
				}
			}
		};

		$walker( $blocks );
		return $found;
	}

	/**
	 * Whitelist the filter values accepted from the client.
	 *
	 * @param array<string, mixed> $raw Raw filter object from the request.
	 * @return array<string, string>
	 */
	private function sanitize_filters( array $raw ): array {
		$known = array(
			'stores_orderby'       => isset( $raw['stores_orderby'] ) ? sanitize_text_field( (string) $raw['stores_orderby'] ) : '',
			'dokan_seller_search'  => isset( $raw['dokan_seller_search'] ) ? sanitize_text_field( (string) $raw['dokan_seller_search'] ) : '',
			'dokan_store_location' => isset( $raw['dokan_store_location'] ) ? sanitize_text_field( (string) $raw['dokan_store_location'] ) : '',
		);

		// Allow integrations to whitelist additional filter values forwarded from the
		// `tanbfd_vendor_query_loop_infinite_filters` filter on the render side. Each
		// extra value is sanitized as a plain string.
		foreach ( $raw as $key => $value ) {
			if ( isset( $known[ $key ] ) || ! is_string( $key ) ) {
				continue;
			}
			if ( is_scalar( $value ) ) {
				$known[ sanitize_key( $key ) ] = sanitize_text_field( (string) $value );
			}
		}

		return $known;
	}

	/**
	 * Handle a request for an additional page.
	 *
	 * @param \WP_REST_Request $request REST request.
	 * @return \WP_REST_Response
	 */
	public function handle_request( \WP_REST_Request $request ): \WP_REST_Response {
		$query_id = (string) $request->get_param( 'queryId' );
		$post_id  = (int) $request->get_param( 'postId' );
		$page     = (int) $request->get_param( 'page' );
		$attrs    = $this->sanitize_attributes( (array) $request->get_param( 'attributes' ) );
		$filters  = $this->sanitize_filters( (array) $request->get_param( 'filters' ) );

		// Ensure the render-time helpers are loaded — they live in the block render.php file.
		if ( ! function_exists( 'tanbfd_vendor_query_loop_build_query_args' ) ) {
			$render_file = THE_ANOTHER_BLOCKS_FOR_DOKAN_PLUGIN_DIR . 'blocks/vendor-query-loop/render.php';
			if ( file_exists( $render_file ) ) {
				require_once $render_file;
			}
		}

		// Re-inject forwarded values into the global query var bag so integrations
		// hooked into `tanbfd_store_list_query_args` that read `get_query_var()`
		// (e.g., aucteeno-nexus location routes reading `location_slug`) still see
		// the same context they did at first render.
		$known_filter_keys = array(
			'stores_orderby'       => true,
			'dokan_seller_search'  => true,
			'dokan_store_location' => true,
		);
		foreach ( $filters as $filter_key => $filter_value ) {
			if ( isset( $known_filter_keys[ $filter_key ] ) || '' === $filter_value ) {
				continue;
			}
			set_query_var( $filter_key, $filter_value );
		}

		$user_args   = tanbfd_vendor_query_loop_build_query_args( $attrs, $page, $filters );
		$user_query  = tanbfd_vendor_query_loop_run_query( $user_args );
		$sellers     = $user_query->get_results();
		$total_users = (int) $user_query->get_total();
		$total_pages = (int) ceil( $total_users / max( 1, (int) $user_args['number'] ) );

		$template_blocks = get_transient( self::template_cache_key( $query_id ) );
		if ( ! is_array( $template_blocks ) || empty( $template_blocks ) ) {
			$template_blocks = $this->extract_template_from_post( $post_id, $query_id );
			if ( ! empty( $template_blocks ) ) {
				set_transient( self::template_cache_key( $query_id ), $template_blocks, HOUR_IN_SECONDS );
			}
		}

		$items = tanbfd_vendor_query_loop_render_items(
			$sellers,
			$template_blocks,
			array( 'dokan/queryId' => $query_id )
		);

		return new \WP_REST_Response(
			array(
				'items'      => $items,
				'page'       => $page,
				'totalPages' => $total_pages,
				'hasMore'    => $page < $total_pages,
			),
			200
		);
	}
}
