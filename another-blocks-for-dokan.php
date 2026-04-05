<?php
/**
 * Plugin Name: Another Blocks for Dokan
 * Plugin URI: https://theanother.org/plugin/another-blocks-for-dokan/
 * Description: FSE-compatible Gutenberg blocks for Dokan multi-vendor marketplace. Convert Dokan templates into dynamic blocks for Full Site Editing.
 * Version: 1.0.5
 * Author: The Another
 * Author URI: https://theanother.org
 * Requires at least: 6.0
 * Requires PHP: 7.4
 * Requires Plugins: woocommerce, dokan-lite
 * Text Domain: another-blocks-for-dokan
 * Domain Path: /languages
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * GitHub Plugin URI: https://github.com/the-another/another-blocks-for-dokan
 * Primary Branch: master
 * Release Asset: true
 *
 * @package AnotherBlocksForDokan
 * @since 1.0.0
 */

// Exit if accessed directly.

use The_Another\Plugin\Blocks_Dokan\Blocks;
use The_Another\Plugin\Blocks_Dokan\Install;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Define plugin constants.
define( 'ANOTHER_BLOCKS_FOR_DOKAN_VERSION', '1.0.3' );
define( 'ANOTHER_BLOCKS_FOR_DOKAN_PLUGIN_FILE', __FILE__ );
define( 'ANOTHER_BLOCKS_FOR_DOKAN_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'ANOTHER_BLOCKS_FOR_DOKAN_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'ANOTHER_BLOCKS_FOR_DOKAN_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

// Required plugin versions.
define( 'ANOTHER_BLOCKS_FOR_DOKAN_MIN_WOOCOMMERCE_VERSION', '10.0.0' );
define( 'ANOTHER_BLOCKS_FOR_DOKAN_MIN_DOKAN_VERSION', '4.0.0' );

// Load Composer autoloader.
$another_blocks_for_dokan_autoload_file = ANOTHER_BLOCKS_FOR_DOKAN_PLUGIN_DIR . 'vendor/autoload.php';
require_once $another_blocks_for_dokan_autoload_file;

// Load helper functions.
require_once ANOTHER_BLOCKS_FOR_DOKAN_PLUGIN_DIR . 'functions/functions.php';

// Register activation hook.
register_activation_hook( __FILE__, array( Install::class, 'activation_check' ) );

// Runtime check to prevent loading if dependencies are not met.
if ( ! Install::runtime_check() ) {
	return;
}

// Initialize plugin.
add_action(
	'plugins_loaded',
	function () {
		try {
			Blocks::get_instance()->init();
		} catch ( Exception $e ) {
			wp_die(
				esc_html( $e->getMessage() ),
				'Another Blocks for Dokan experienced an Error',
				array( 'response' => 500 )
			);
		}
	},
	10 // Priority 10 to register blocks early.
);
