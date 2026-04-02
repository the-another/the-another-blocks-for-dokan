<?php
/**
 * Abstract base class for Dokan block templates.
 *
 * @package AnotherBlocksDokan
 * @since 1.0.0
 */

namespace The_Another\Plugin\Blocks_Dokan\Templates;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Abstract Dokan template class.
 */
abstract class Abstract_Dokan_Template {

	/**
	 * Plugin template slug/namespace.
	 *
	 * @var string
	 */
	const PLUGIN_SLUG = 'another-blocks-for-dokan';

	/**
	 * Template slug.
	 *
	 * @var string
	 */
	const SLUG = '';

	/**
	 * Initialization method.
	 *
	 * @return void
	 */
	public function init(): void {
		$this->register_template();
	}

	/**
	 * Register block template with WordPress using register_block_template().
	 *
	 * @return void
	 */
	protected function register_template(): void {
		// Safety: only available in WP 6.7+.
		if ( ! function_exists( 'register_block_template' ) ) {
			return;
		}

		$template_name = self::PLUGIN_SLUG . '//' . static::SLUG;

		register_block_template(
			$template_name,
			array(
				'title'       => $this->get_template_title(),
				'description' => $this->get_template_description(),
				'plugin'      => self::PLUGIN_SLUG,
				'content'     => $this->get_template_content(),
			)
		);
	}

	/**
	 * Get template content from file.
	 *
	 * @return string Template content.
	 */
	protected function get_template_content(): string {
		$template_path = $this->get_template_file_path();

		if ( ! file_exists( $template_path ) ) {
			return '';
		}

		$content = file_get_contents( $template_path );

		return false !== $content ? $content : '';
	}

	/**
	 * Get template file path.
	 *
	 * @return string
	 */
	protected function get_template_file_path(): string {
		$template_file_map = array(
			'dokan-store'      => 'store.html',
			'dokan-store-list' => 'store-lists.html',
			'dokan-store-toc'  => 'store-toc.html',
		);

		$template_file = isset( $template_file_map[ static::SLUG ] )
			? $template_file_map[ static::SLUG ]
			: static::SLUG . '.html';

		return \ANOTHER_BLOCKS_DOKAN_PLUGIN_DIR . 'templates/' . $template_file;
	}

	/**
	 * Get the full template ID.
	 *
	 * @param string|null $slug Optional slug, defaults to static::SLUG.
	 * @return string Template ID in format 'plugin-slug//template-slug'.
	 */
	public static function get_template_id( ?string $slug = null ): string {
		return self::PLUGIN_SLUG . '//' . ( $slug ?? static::SLUG );
	}

	/**
	 * Check if this template should be rendered.
	 *
	 * @return bool
	 */
	abstract protected function should_render_template(): bool;

	/**
	 * Get template title.
	 *
	 * @return string
	 */
	abstract public function get_template_title(): string;

	/**
	 * Get template description.
	 *
	 * @return string
	 */
	abstract public function get_template_description(): string;
}
