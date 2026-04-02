<?php
/**
 * Block templates controller.
 *
 * @package AnotherBlocksDokan
 * @since 1.0.0
 */

namespace The_Another\Plugin\Blocks_Dokan\Templates;

/**
 * Block templates controller class.
 */
class Block_Templates_Controller {

	/**
	 * Template instances.
	 *
	 * @var array<Abstract_Dokan_Template>
	 */
	private array $templates = array();

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->register_templates();
	}

	/**
	 * Initialize templates.
	 *
	 * @return void
	 */
	public function init(): void {
		foreach ( $this->templates as $template ) {
			$template->init();
		}
	}

	/**
	 * Register all templates.
	 *
	 * @return void
	 */
	private function register_templates(): void {
		$this->templates[] = new Store_Template();
		$this->templates[] = new Store_List_Template();

		/**
		 * Filter registered templates.
		 *
		 * @since 1.0.0
		 *
		 * @param array<Abstract_Dokan_Template> $templates Template instances.
		 */
		$this->templates = apply_filters( 'another_blocks_dokan_registered_templates', $this->templates );
	}

	/**
	 * Get all registered templates.
	 *
	 * @return array<Abstract_Dokan_Template>
	 */
	public function get_templates(): array {
		return $this->templates;
	}
}
