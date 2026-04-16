<?php
/**
 * Playground helpers — idempotent vendor creation endpoint.
 *
 * Installed as a wp-now mu-plugin by the playground setup script.
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
			'tanbfd-playground/v1',
			'/ensure-vendor',
			array(
				'methods'             => 'POST',
				'callback'            => function ( WP_REST_Request $request ) {
					$username = sanitize_user( $request->get_param( 'username' ) );

					$existing = get_user_by( 'login', $username );
					if ( $existing ) {
						return array(
							'id'       => $existing->ID,
							'username' => $username,
							'created'  => false,
						);
					}

					$user_id = wp_insert_user(
						array(
							'user_login'   => $username,
							'user_email'   => sanitize_email( $request->get_param( 'email' ) ),
							'user_pass'    => $request->get_param( 'password' ),
							'role'         => 'seller',
							'display_name' => sanitize_text_field( $request->get_param( 'display_name' ) ),
						)
					);

					if ( is_wp_error( $user_id ) ) {
						return $user_id;
					}

					$store_name = sanitize_text_field( $request->get_param( 'store_name' ) );
					$phone      = sanitize_text_field( $request->get_param( 'phone' ) );
					$address    = $request->get_param( 'address' ) ?? array();

					update_user_meta( $user_id, 'dokan_enable_selling', 'yes' );
					update_user_meta( $user_id, 'dokan_store_name', $store_name );

					$profile_settings = array(
						'store_name' => $store_name,
					);

					if ( ! empty( $address ) ) {
						$profile_settings['address'] = array_map( 'sanitize_text_field', (array) $address );
					}

					if ( ! empty( $phone ) ) {
						$profile_settings['phone'] = $phone;
					}

					update_user_meta( $user_id, 'dokan_profile_settings', $profile_settings );

					return array(
						'id'       => $user_id,
						'username' => $username,
						'created'  => true,
					);
				},
				'permission_callback' => function () {
					return current_user_can( 'create_users' );
				},
			)
		);
	}
);
