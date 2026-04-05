<?php
/**
 * E2E test helpers – REST endpoints for creating/deleting Dokan vendors.
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

add_action(
	'rest_api_init',
	function () {
		register_rest_route(
			'theabd-test/v1',
			'/create-vendor',
			array(
				'methods'             => 'POST',
				'callback'            => function ( WP_REST_Request $request ) {
					$index    = (int) $request->get_param( 'index' );
					$username = 'e2evendor' . $index . '_' . time() . wp_rand( 100, 999 );

					$store_name = $request->get_param( 'store_name' ) ?? 'Test Store ' . $index;
					$featured   = $request->get_param( 'featured' ) ?? false;
					$address    = $request->get_param( 'address' ) ?? array();

					$user_id = wp_insert_user(
						array(
							'user_login'   => $username,
							'user_email'   => $username . '@example.com',
							'user_pass'    => wp_generate_password(),
							'role'         => 'seller',
							'display_name' => $store_name,
						)
					);

					if ( is_wp_error( $user_id ) ) {
						return $user_id;
					}

					update_user_meta( $user_id, 'dokan_enable_selling', 'yes' );
					update_user_meta( $user_id, 'dokan_store_name', $store_name );

					if ( $featured ) {
						update_user_meta( $user_id, 'dokan_feature_seller', 'yes' );
					}

					// Dokan reads vendor data from dokan_profile_settings.
					// Always set it so to_array() returns store_name and address.
					$profile_settings = array(
						'store_name' => $store_name,
					);
					if ( ! empty( $address ) ) {
						$profile_settings['address'] = $address;
					}

					$phone = $request->get_param( 'phone' );
					if ( ! empty( $phone ) ) {
						$profile_settings['phone'] = sanitize_text_field( $phone );
					}

					$store_tnc = $request->get_param( 'store_tnc' );
					if ( ! empty( $store_tnc ) ) {
						$profile_settings['store_tnc'] = wp_kses_post( $store_tnc );
					}

					$store_open_close = $request->get_param( 'store_open_close' );
					if ( ! empty( $store_open_close ) ) {
						$profile_settings['store_open_close'] = $store_open_close;
					}

					$dokan_store_time = $request->get_param( 'dokan_store_time' );
					if ( ! empty( $dokan_store_time ) ) {
						$profile_settings['dokan_store_time'] = $dokan_store_time;
					}

					$dokan_store_time_enabled = $request->get_param( 'dokan_store_time_enabled' );
					if ( ! empty( $dokan_store_time_enabled ) ) {
						$profile_settings['dokan_store_time_enabled'] = sanitize_text_field( $dokan_store_time_enabled );
					}

					update_user_meta( $user_id, 'dokan_profile_settings', $profile_settings );

					return array(
						'id'       => $user_id,
						'username' => $username,
					);
				},
				'permission_callback' => function () {
					return current_user_can( 'create_users' );
				},
			)
		);

		register_rest_route(
			'theabd-test/v1',
			'/delete-vendor/(?P<id>[\d]+)',
			array(
				'methods'             => 'DELETE',
				'callback'            => function ( WP_REST_Request $request ) {
					require_once ABSPATH . 'wp-admin/includes/user.php';
					return array( 'deleted' => wp_delete_user( (int) $request['id'], 1 ) );
				},
				'permission_callback' => function () {
					return current_user_can( 'delete_users' );
				},
			)
		);

		register_rest_route(
			'theabd-test/v1',
			'/create-product',
			array(
				'methods'             => 'POST',
				'callback'            => function ( WP_REST_Request $request ) {
					$vendor_id = (int) $request->get_param( 'vendor_id' );
					$title     = $request->get_param( 'title' ) ?? 'Test Product';
					$price     = $request->get_param( 'price' ) ?? 19.99;
					$status    = $request->get_param( 'status' ) ?? 'publish';

					$post_id = wp_insert_post(
						array(
							'post_title'  => sanitize_text_field( $title ),
							'post_type'   => 'product',
							'post_status' => sanitize_text_field( $status ),
							'post_author' => $vendor_id,
						)
					);

					if ( is_wp_error( $post_id ) ) {
						return $post_id;
					}

					update_post_meta( $post_id, '_price', (float) $price );
					update_post_meta( $post_id, '_regular_price', (float) $price );
					update_post_meta( $post_id, '_visibility', 'visible' );
					wp_set_object_terms( $post_id, 'simple', 'product_type' );

					return array( 'id' => $post_id );
				},
				'permission_callback' => function () {
					return current_user_can( 'edit_posts' );
				},
			)
		);

		register_rest_route(
			'theabd-test/v1',
			'/delete-product/(?P<id>[\d]+)',
			array(
				'methods'             => 'DELETE',
				'callback'            => function ( WP_REST_Request $request ) {
					return array( 'deleted' => wp_delete_post( (int) $request['id'], true ) );
				},
				'permission_callback' => function () {
					return current_user_can( 'delete_posts' );
				},
			)
		);

		// Bulk endpoints — reduce sequential HTTP calls during test setup/teardown.

		register_rest_route(
			'theabd-test/v1',
			'/create-vendors',
			array(
				'methods'             => 'POST',
				'callback'            => function ( WP_REST_Request $request ) {
					$vendors = $request->get_param( 'vendors' );
					if ( ! is_array( $vendors ) ) {
						return new WP_Error( 'invalid_param', 'vendors must be an array', array( 'status' => 400 ) );
					}

					$results = array();

					foreach ( $vendors as $vendor ) {
						$index    = (int) ( $vendor['index'] ?? 0 );
						$username = 'e2evendor' . $index . '_' . time() . wp_rand( 100, 999 );

						$store_name = $vendor['store_name'] ?? 'Test Store ' . $index;
						$featured   = $vendor['featured'] ?? false;
						$address    = $vendor['address'] ?? array();

						$user_id = wp_insert_user(
							array(
								'user_login'   => $username,
								'user_email'   => $username . '@example.com',
								'user_pass'    => wp_generate_password(),
								'role'         => 'seller',
								'display_name' => $store_name,
							)
						);

						if ( is_wp_error( $user_id ) ) {
							$results[] = array( 'error' => $user_id->get_error_message() );
							continue;
						}

						update_user_meta( $user_id, 'dokan_enable_selling', 'yes' );
						update_user_meta( $user_id, 'dokan_store_name', $store_name );

						if ( $featured ) {
							update_user_meta( $user_id, 'dokan_feature_seller', 'yes' );
						}

						$profile_settings = array(
							'store_name' => $store_name,
						);
						if ( ! empty( $address ) ) {
							$profile_settings['address'] = $address;
						}

						$phone = $vendor['phone'] ?? '';
						if ( ! empty( $phone ) ) {
							$profile_settings['phone'] = sanitize_text_field( $phone );
						}

						$store_tnc = $vendor['store_tnc'] ?? '';
						if ( ! empty( $store_tnc ) ) {
							$profile_settings['store_tnc'] = wp_kses_post( $store_tnc );
						}

						$store_open_close = $vendor['store_open_close'] ?? '';
						if ( ! empty( $store_open_close ) ) {
							$profile_settings['store_open_close'] = $store_open_close;
						}

						$dokan_store_time = $vendor['dokan_store_time'] ?? '';
						if ( ! empty( $dokan_store_time ) ) {
							$profile_settings['dokan_store_time'] = $dokan_store_time;
						}

						$dokan_store_time_enabled = $vendor['dokan_store_time_enabled'] ?? '';
						if ( ! empty( $dokan_store_time_enabled ) ) {
							$profile_settings['dokan_store_time_enabled'] = sanitize_text_field( $dokan_store_time_enabled );
						}

						update_user_meta( $user_id, 'dokan_profile_settings', $profile_settings );

						$results[] = array(
							'id'       => $user_id,
							'username' => $username,
						);
					}

					return $results;
				},
				'permission_callback' => function () {
					return current_user_can( 'create_users' );
				},
			)
		);

		register_rest_route(
			'theabd-test/v1',
			'/delete-vendors',
			array(
				'methods'             => 'POST',
				'callback'            => function ( WP_REST_Request $request ) {
					require_once ABSPATH . 'wp-admin/includes/user.php';

					$ids = $request->get_param( 'ids' );
					if ( ! is_array( $ids ) ) {
						return new WP_Error( 'invalid_param', 'ids must be an array', array( 'status' => 400 ) );
					}

					$results = array();
					foreach ( $ids as $id ) {
						$results[] = array(
							'id'      => (int) $id,
							'deleted' => wp_delete_user( (int) $id, 1 ),
						);
					}

					return $results;
				},
				'permission_callback' => function () {
					return current_user_can( 'delete_users' );
				},
			)
		);

		register_rest_route(
			'theabd-test/v1',
			'/create-products',
			array(
				'methods'             => 'POST',
				'callback'            => function ( WP_REST_Request $request ) {
					$products = $request->get_param( 'products' );
					if ( ! is_array( $products ) ) {
						return new WP_Error( 'invalid_param', 'products must be an array', array( 'status' => 400 ) );
					}

					$results = array();

					foreach ( $products as $product ) {
						$vendor_id = (int) ( $product['vendor_id'] ?? 0 );
						$title     = $product['title'] ?? 'Test Product';
						$price     = $product['price'] ?? 19.99;
						$status    = $product['status'] ?? 'publish';

						$post_id = wp_insert_post(
							array(
								'post_title'  => sanitize_text_field( $title ),
								'post_type'   => 'product',
								'post_status' => sanitize_text_field( $status ),
								'post_author' => $vendor_id,
							)
						);

						if ( is_wp_error( $post_id ) ) {
							$results[] = array( 'error' => $post_id->get_error_message() );
							continue;
						}

						update_post_meta( $post_id, '_price', (float) $price );
						update_post_meta( $post_id, '_regular_price', (float) $price );
						update_post_meta( $post_id, '_visibility', 'visible' );
						wp_set_object_terms( $post_id, 'simple', 'product_type' );

						$results[] = array( 'id' => $post_id );
					}

					return $results;
				},
				'permission_callback' => function () {
					return current_user_can( 'edit_posts' );
				},
			)
		);

		register_rest_route(
			'theabd-test/v1',
			'/delete-products',
			array(
				'methods'             => 'POST',
				'callback'            => function ( WP_REST_Request $request ) {
					$ids = $request->get_param( 'ids' );
					if ( ! is_array( $ids ) ) {
						return new WP_Error( 'invalid_param', 'ids must be an array', array( 'status' => 400 ) );
					}

					$results = array();
					foreach ( $ids as $id ) {
						$results[] = array(
							'id'      => (int) $id,
							'deleted' => (bool) wp_delete_post( (int) $id, true ),
						);
					}

					return $results;
				},
				'permission_callback' => function () {
					return current_user_can( 'delete_posts' );
				},
			)
		);
	}
);
