<?php
/**
 * PHPUnit bootstrap file for Another Blocks for Dokan plugin tests.
 *
 * @package AnotherBlocksForDokan
 * @since 1.0.0
 */

// Load BrainMonkey.
require_once dirname( __DIR__ ) . '/vendor/autoload.php';

// Load Mockery
require_once dirname( __DIR__ ) . '/vendor/mockery/mockery/library/helpers.php';

// Set up BrainMonkey
use Brain\Monkey;
use Brain\Monkey\Functions;

Monkey\setUp();

// Define WordPress constants (mocked)
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', '/tmp/wordpress/' );
}

if ( ! defined( 'WP_CONTENT_DIR' ) ) {
	define( 'WP_CONTENT_DIR', ABSPATH . 'wp-content' );
}

if ( ! defined( 'WP_PLUGIN_DIR' ) ) {
	define( 'WP_PLUGIN_DIR', WP_CONTENT_DIR . '/plugins' );
}

if ( ! defined( 'WPINC' ) ) {
	define( 'WPINC', 'wp-includes' );
}

// Common WordPress function stubs
Functions\when( 'plugin_dir_path' )->alias(
	function ( $file ) {
		return dirname( $file ) . '/';
	}
);

Functions\when( 'plugin_dir_url' )->alias(
	function ( $file ) {
		return 'http://example.org/wp-content/plugins/' . basename( dirname( $file ) ) . '/';
	}
);

Functions\when( 'plugin_basename' )->alias(
	function ( $file ) {
		return basename( dirname( $file ) ) . '/' . basename( $file );
	}
);

// Escaping functions (return sanitized input)
Functions\when( 'esc_html' )->returnArg();
Functions\when( 'esc_attr' )->returnArg();
Functions\when( 'esc_url' )->returnArg();
Functions\when( 'esc_js' )->returnArg();
Functions\when( 'esc_html__' )->returnArg();
Functions\when( 'esc_html_e' )->returnArg();
Functions\when( 'esc_attr__' )->returnArg();
Functions\when( 'esc_attr_e' )->returnArg();
Functions\when( '__' )->returnArg();
Functions\when( '_e' )->returnArg();
Functions\when( '_x' )->returnArg();
Functions\when( '_n' )->returnArg();
Functions\when( '_nx' )->returnArg();

// Dokan function stubs (will be mocked in individual tests)
Functions\when( 'dokan' )->justReturn( null );
Functions\when( 'dokan_is_user_seller' )->returnArg();
Functions\when( 'dokan_get_store_url' )->returnArg();
Functions\when( 'dokan_get_seller_short_address' )->returnArg();
Functions\when( 'dokan_is_vendor_info_hidden' )->returnArg();
Functions\when( 'dokan_get_readable_seller_rating' )->returnArg();
Functions\when( 'dokan_generate_ratings' )->returnArg();
Functions\when( 'dokan_get_social_profile_fields' )->justReturn( array() );
Functions\when( 'dokan_is_store_open' )->justReturn( true );
Functions\when( 'dokan_is_store_page' )->justReturn( false );
Functions\when( 'dokan_get_store_tabs' )->justReturn( array() );
Functions\when( 'dokan_get_option' )->justReturn( '' );
Functions\when( 'dokan_current_datetime' )->justReturn( new \DateTime() );
Functions\when( 'dokan_get_translated_days' )->justReturn( array() );
Functions\when( 'dokan_get_page_url' )->justReturn( '' );
Functions\when( 'dokan_get_toc_url' )->justReturn( '' );

// WooCommerce function stubs
Functions\when( 'wc_get_template_part' )->returnArg();
Functions\when( 'wc_get_product' )->justReturn( null );

// WordPress query/context functions
Functions\when( 'get_query_var' )->justReturn( '' );
Functions\when( 'get_user_by' )->justReturn( false );
Functions\when( 'get_post_type' )->justReturn( '' );
Functions\when( 'is_singular' )->justReturn( false );
Functions\when( 'is_page' )->justReturn( false );
Functions\when( 'wp_is_block_theme' )->justReturn( true );
Functions\when( 'current_user_can' )->justReturn( true );
Functions\when( 'get_block_wrapper_attributes' )->justReturn( '' );
Functions\when( 'register_block_type' )->justReturn( null );
Functions\when( 'register_block_type_from_metadata' )->justReturn( null );
Functions\when( 'get_block_template' )->justReturn( null );
Functions\when( 'wp_kses_post' )->returnArg();
Functions\when( 'wp_get_current_user' )->justReturn(
	(object) array(
		'exists'       => false,
		'display_name' => '',
		'user_email'   => '',
	)
);
Functions\when( 'wp_get_attachment_url' )->returnArg();
Functions\when( 'wp_dropdown_categories' )->justReturn( '' );
Functions\when( 'remove_query_arg' )->returnArg();
Functions\when( 'get_option' )->justReturn( '' );
Functions\when( 'wp_nonce_field' )->justReturn( '' );
Functions\when( 'do_action' )->justReturn( null );
Functions\when( 'apply_filters' )->alias(
	function ( $filter, $value ) {
		return $value;
	}
);
Functions\when( 'add_filter' )->justReturn( true );
Functions\when( 'add_action' )->justReturn( true );
Functions\when( 'get_current_user_id' )->justReturn( 0 );
Functions\when( 'wp_reset_postdata' )->justReturn( null );
Functions\when( 'the_post' )->justReturn( null );
Functions\when( 'have_posts' )->justReturn( false );
Functions\when( 'dynamic_sidebar' )->justReturn( false );
Functions\when( 'get_sidebar' )->justReturn( null );

// Utility functions
Functions\when( 'absint' )->alias(
	function ( $value ) {
		return abs( (int) $value );
	}
);

Functions\when( 'sanitize_text_field' )->returnArg();
Functions\when( 'sanitize_email' )->returnArg();
Functions\when( 'antispambot' )->returnArg();
Functions\when( 'number_format_i18n' )->alias(
	function ( $number ) {
		return number_format( $number, 2 );
	}
);

// Load plugin autoloader if available
if ( file_exists( dirname( __DIR__ ) . '/vendor/autoload.php' ) ) {
	require_once dirname( __DIR__ ) . '/vendor/autoload.php';
}

// Define plugin constants for tests
if ( ! defined( 'THE_ANOTHER_BLOCKS_FOR_DOKAN_PLUGIN_FILE' ) ) {
	define( 'THE_ANOTHER_BLOCKS_FOR_DOKAN_PLUGIN_FILE', dirname( __DIR__ ) . '/the-another-blocks-for-dokan.php' );
}

if ( ! defined( 'THE_ANOTHER_BLOCKS_FOR_DOKAN_PLUGIN_DIR' ) ) {
	define( 'THE_ANOTHER_BLOCKS_FOR_DOKAN_PLUGIN_DIR', dirname( __DIR__ ) . '/' );
}

if ( ! defined( 'THE_ANOTHER_BLOCKS_FOR_DOKAN_VERSION' ) ) {
	define( 'THE_ANOTHER_BLOCKS_FOR_DOKAN_VERSION', '1.0.3' );
}

// Provide lightweight stubs for the public API functions used by internal
// classes. In production these go through the container; in tests we
// instantiate Context_Detector directly to avoid the full container chain.
// phpcs:disable Squiz.Commenting.FunctionComment.Missing, Squiz.Commenting.FunctionComment.MissingParamComment
if ( ! function_exists( 'tanbfd_get_vendor_id' ) ) {
	function tanbfd_get_vendor_id(): ?int {
		return ( new \The_Another\Plugin\Blocks_For_Dokan\Helpers\Context_Detector() )->get_vendor_id();
	}
}

if ( ! function_exists( 'tanbfd_get_product_id' ) ) {
	function tanbfd_get_product_id(): ?int {
		return ( new \The_Another\Plugin\Blocks_For_Dokan\Helpers\Context_Detector() )->get_product_id();
	}
}

if ( ! function_exists( 'tanbfd_is_store_page' ) ) {
	function tanbfd_is_store_page(): bool {
		return ( new \The_Another\Plugin\Blocks_For_Dokan\Helpers\Context_Detector() )->is_store_page();
	}
}

if ( ! function_exists( 'tanbfd_is_product_page' ) ) {
	function tanbfd_is_product_page(): bool {
		return ( new \The_Another\Plugin\Blocks_For_Dokan\Helpers\Context_Detector() )->is_product_page();
	}
}

if ( ! function_exists( 'tanbfd_is_store_list_page' ) ) {
	function tanbfd_is_store_list_page(): bool {
		return ( new \The_Another\Plugin\Blocks_For_Dokan\Helpers\Context_Detector() )->is_store_list_page();
	}
}
