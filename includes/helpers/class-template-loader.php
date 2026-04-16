<?php
/**
 * Template loader helper.
 *
 * @package AnotherBlocksForDokan
 * @since 1.0.0
 */

namespace The_Another\Plugin\Blocks_For_Dokan\Helpers;

/**
 * Template loader class.
 */
class Template_Loader {

	/**
	 * Load Dokan template as fallback.
	 *
	 * @param string $template_name Template name.
	 * @param array  $args          Template arguments.
	 * @return void
	 */
	public static function load_dokan_template( string $template_name, array $args = array() ): void {
		if ( function_exists( 'dokan_get_template_part' ) ) {
			dokan_get_template_part( $template_name, '', $args );
		}
	}

	/**
	 * Get Dokan template path.
	 *
	 * @param string $template_name Template name.
	 * @return string Template path or empty string.
	 */
	public static function get_dokan_template_path( string $template_name ): string {
		if ( function_exists( 'dokan_locate_template' ) ) {
			return dokan_locate_template( $template_name );
		}

		return '';
	}

	/**
	 * Check if Dokan template exists.
	 *
	 * @param string $template_name Template name.
	 * @return bool True if template exists.
	 */
	public static function dokan_template_exists( string $template_name ): bool {
		$template_path = self::get_dokan_template_path( $template_name );
		return ! empty( $template_path ) && file_exists( $template_path );
	}
}
