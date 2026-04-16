<?php
/**
 * Main plugin class.
 *
 * @package AnotherBlocksForDokan
 * @since 1.0.0
 */

namespace The_Another\Plugin\Blocks_For_Dokan;

use The_Another\Plugin\Blocks_For_Dokan\Container\Container;
use The_Another\Plugin\Blocks_For_Dokan\Container\Hook_Manager;
use The_Another\Plugin\Blocks_For_Dokan\Exceptions\Container_Exception;
use The_Another\Plugin\Blocks_For_Dokan\Helpers\Context_Detector;
use The_Another\Plugin\Blocks_For_Dokan\Rest\Vendor_Query_Loop_Controller;
use The_Another\Plugin\Blocks_For_Dokan\Templates\Block_Templates_Controller;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Main plugin class.
 */
final class Blocks {

	/**
	 * Plugin instance.
	 *
	 * @var Blocks|null
	 */
	private static ?Blocks $instance = null;

	/**
	 * Dependency injection container.
	 *
	 * @var Container
	 */
	private readonly Container $container;

	/**
	 * Hook manager.
	 *
	 * @var Hook_Manager
	 */
	private readonly Hook_Manager $hook_manager;

	/**
	 * Block templates controller.
	 *
	 * @var Block_Templates_Controller
	 */
	private readonly Block_Templates_Controller $templates_controller;

	/**
	 * Block registry.
	 *
	 * @var Block_Registry
	 */
	private readonly Block_Registry $block_registry;

	/**
	 * Get plugin instance.
	 *
	 * @return Blocks
	 */
	public static function get_instance(): Blocks {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Constructor.
	 *
	 * @throws Container_Exception If container service resolution fails.
	 */
	private function __construct() {
		$this->container    = Container::get_instance();
		$this->hook_manager = $this->container->get_hook_manager();

		$this->setup_container();

		$this->templates_controller = $this->container->get( Block_Templates_Controller::class );
		$this->block_registry       = $this->container->get( Block_Registry::class );
	}

	/**
	 * Setup container bindings.
	 *
	 * @return void
	 */
	private function setup_container(): void {
		$this->container->register(
			Context_Detector::class,
			static fn() => new Context_Detector()
		);
		$this->container->register(
			Block_Registry::class,
			static fn() => new Block_Registry()
		);
		$this->container->register(
			Block_Templates_Controller::class,
			static fn() => new Block_Templates_Controller()
		);
	}

	/**
	 * Initialize plugin.
	 *
	 * @return void
	 */
	public function init(): void {
		// Initialize block templates system.
		$this->templates_controller->init();

		// Register hooks.
		$this->register_hooks();
	}

	/**
	 * Register all hooks.
	 *
	 * @return void
	 */
	private function register_hooks(): void {
		// Register blocks on init hook at early priority (required for block registration).
		$this->hook_manager->register_action( 'init', array( $this->block_registry, 'register_all_blocks' ), 5 );

		// Enqueue block assets.
		$this->hook_manager->register_action( 'enqueue_block_assets', array( $this, 'enqueue_block_assets' ) );
		$this->hook_manager->register_action( 'enqueue_block_editor_assets', array( $this, 'enqueue_block_editor_assets' ) );

		// Register the vendor query loop infinite-scroll REST route.
		$this->hook_manager->register_action(
			'rest_api_init',
			static function (): void {
				( new Vendor_Query_Loop_Controller() )->register_routes();
			}
		);

		// Register the vendor query loop infinite-scroll view script handle.
		$this->hook_manager->register_action(
			'init',
			static function (): void {
				wp_register_script(
					'tanbfd-vendor-query-loop-view',
					THE_ANOTHER_BLOCKS_FOR_DOKAN_PLUGIN_URL . 'blocks/vendor-query-loop/view.js',
					array( 'wp-api-fetch' ),
					THE_ANOTHER_BLOCKS_FOR_DOKAN_VERSION,
					true
				);
				wp_register_script(
					'tanbfd-vendor-search-view',
					THE_ANOTHER_BLOCKS_FOR_DOKAN_PLUGIN_URL . 'blocks/vendor-search/view.js',
					array(),
					THE_ANOTHER_BLOCKS_FOR_DOKAN_VERSION,
					true
				);
			}
		);
	}

	/**
	 * Get the container instance.
	 *
	 * @return Container
	 */
	public function get_container(): Container {
		return $this->container;
	}

	/**
	 * Get the hook manager instance.
	 *
	 * @return Hook_Manager
	 */
	public function get_hook_manager(): Hook_Manager {
		return $this->hook_manager;
	}

	/**
	 * Enqueue block assets (frontend + editor).
	 *
	 * @return void
	 */
	public function enqueue_block_assets(): void {
		// Always enqueue frontend/shared styles (both frontend and editor need them).
		$frontend_style = 'dist/style-blocks.css';
		if ( file_exists( THE_ANOTHER_BLOCKS_FOR_DOKAN_PLUGIN_DIR . $frontend_style ) ) {
			wp_enqueue_style(
				'tanbfd-blocks-frontend',
				THE_ANOTHER_BLOCKS_FOR_DOKAN_PLUGIN_URL . $frontend_style,
				array(),
				THE_ANOTHER_BLOCKS_FOR_DOKAN_VERSION
			);
		}

		// Enqueue editor-specific styles when in admin/editor context.
		if ( is_admin() ) {
			$editor_style = 'dist/blocks.css';
			if ( file_exists( THE_ANOTHER_BLOCKS_FOR_DOKAN_PLUGIN_DIR . $editor_style ) ) {
				wp_enqueue_style(
					'tanbfd-blocks-editor',
					THE_ANOTHER_BLOCKS_FOR_DOKAN_PLUGIN_URL . $editor_style,
					array( 'tanbfd-blocks-frontend' ),
					THE_ANOTHER_BLOCKS_FOR_DOKAN_VERSION
				);
			}
		}
	}

	/**
	 * Enqueue block editor assets.
	 *
	 * @return void
	 */
	public function enqueue_block_editor_assets(): void {
		// Check if build file exists.
		$editor_script      = 'dist/blocks.js';
		$editor_script_path = THE_ANOTHER_BLOCKS_FOR_DOKAN_PLUGIN_DIR . $editor_script;

		if ( file_exists( $editor_script_path ) ) {
			// Load asset file for dependencies and version.
			$asset_file = THE_ANOTHER_BLOCKS_FOR_DOKAN_PLUGIN_DIR . 'dist/blocks.asset.php';
			$asset      = file_exists( $asset_file ) ? require $asset_file : array(
				'dependencies' => array( 'wp-blocks', 'wp-element', 'wp-editor', 'wp-components', 'wp-i18n' ),
				'version'      => THE_ANOTHER_BLOCKS_FOR_DOKAN_VERSION,
			);

			wp_enqueue_script(
				'tanbfd-blocks-editor',
				THE_ANOTHER_BLOCKS_FOR_DOKAN_PLUGIN_URL . $editor_script,
				$asset['dependencies'],
				$asset['version'],
				true
			);

			// Editor styles should be declared in block.json using editorStyle property.
			// WordPress will automatically handle them correctly for the editor iframe.
		}
	}
}
