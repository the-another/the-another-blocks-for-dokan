<?php
/**
 * E2E test environment fixes for wp-now.
 *
 * Suppresses noisy plugin side-effects that break or pollute E2E tests
 * when running under wp-now's SQLite environment.
 *
 * Installed as a wp-now mu-plugin by the Playwright global setup.
 * Never loaded by the main plugin; not shipped in production releases.
 *
 * @package AnotherBlocksForDokan
 * @since 1.0.3
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// --- Pretty permalinks ------------------------------------------------------

// Tell WordPress the server supports URL rewriting.
add_filter( 'got_url_rewrite', '__return_true' );

// wp-now starts with "Plain" permalinks. Set a proper structure so
// plugins like Dokan can register their rewrite rules (store pages, tabs, etc.).
add_action( 'init', function () {
	if ( get_option( 'permalink_structure' ) === '' ) {
		global $wp_rewrite;
		$wp_rewrite->set_permalink_structure( '/%postname%/' );
		flush_rewrite_rules();
	}
}, 999 );

// --- WooCommerce onboarding suppression -------------------------------------

// Prevent the automatic redirect to the WC setup wizard after activation.
add_filter( 'woocommerce_prevent_automatic_wizard_redirect', '__return_true' );

// Disable the WooCommerce Admin onboarding experience entirely.
add_filter( 'woocommerce_admin_onboarding_valid', '__return_false' );

// Skip the extended task list and inbox notes that trigger remote fetches.
add_filter( 'woocommerce_admin_get_feature_config', function ( $features ) {
	$features['onboarding']                 = false;
	$features['remote-inbox-notifications'] = false;
	$features['marketing']                  = false;
	return $features;
} );

// Prevent WC from creating default pages (shop, cart, checkout, etc.) on the
// first REST save.
add_filter( 'woocommerce_create_pages', '__return_empty_array' );

// --- Dokan setup wizard suppression -----------------------------------------

// Dokan sets a transient on activation that redirects admin to its setup wizard.
// Delete it on every load so the redirect never fires.
add_action( 'admin_init', function () {
	delete_transient( '_dokan_setup_page_redirect' );
}, 1 );

// WooCommerce also sets a redirect transient on activation.
add_action( 'admin_init', function () {
	delete_transient( '_wc_activation_redirect' );
}, 1 );

// --- Dokan admin notice scripts suppression ---------------------------------

// Dokan enqueues JS that fetches /dokan/v1/admin/notices/admin which 404s
// under wp-now (REST route not fully registered). Dequeue the scripts.
add_action( 'admin_enqueue_scripts', function () {
	wp_dequeue_script( 'dokan-promo-notice-js' );
	wp_dequeue_script( 'dokan-admin-notice-js' );
}, 20 );

// --- Action Scheduler suppression -------------------------------------------

add_filter(
	'action_scheduler_logger_class',
	function () {
		return 'ActionScheduler_NullLogger';
	}
);

add_filter(
	'action_scheduler_queue_runner_batch_size',
	function () {
		return 0;
	}
);

add_filter(
	'action_scheduler_queue_runner_concurrent_batches',
	function () {
		return 0;
	}
);

// Disable Action Scheduler's async request runner entirely.
add_filter( 'action_scheduler_allow_async_request_runner', '__return_false' );

// Suppress PHP errors originating from Action Scheduler.
set_error_handler(
	function ( $errno, $errstr, $errfile ) {
		if ( str_contains( $errfile, 'action-scheduler' ) || str_contains( $errfile, 'ActionScheduler' ) ) {
			return true;
		}
		return false;
	},
	E_ALL
);
