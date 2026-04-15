<?php
/**
 * Global helper functions.
 *
 * @package AnotherBlocksForDokan
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use The_Another\Plugin\Blocks_For_Dokan\Blocks;
use The_Another\Plugin\Blocks_For_Dokan\Container\Container;
use The_Another\Plugin\Blocks_For_Dokan\Container\Hook_Manager;

/**
 * Get the Another Blocks for Dokan instance.
 *
 * @return Blocks The Blocks instance.
 */
function tanbfd_plugin(): Blocks {
	return Blocks::get_instance();
}

/**
 * Get the dependency injection container.
 *
 * @return Container The container instance.
 */
function tanbfd_container(): Container {
	return tanbfd_plugin()->get_container();
}

/**
 * Get the hook manager.
 *
 * @return Hook_Manager The hook manager instance.
 */
function tanbfd_hooks(): Hook_Manager {
	return tanbfd_plugin()->get_hook_manager();
}

/**
 * Sanitize block render output, allowing form elements.
 *
 * Uses wp_kses() with an extended allowed tags list that includes form
 * elements (form, select, option, input, button, label) and iframes,
 * which wp_kses_post() strips. Needed for blocks that contain search
 * forms, filter controls, and embedded maps.
 *
 * @param string $html The HTML to sanitize.
 * @return string Sanitized HTML with form elements preserved.
 */
function tanbfd_kses_block( string $html ): string {
	$allowed = wp_kses_allowed_html( 'post' );

	$form_tags = array(
		'form'     => array(
			'action'      => true,
			'method'      => true,
			'name'        => true,
			'id'          => true,
			'class'       => true,
			'role'        => true,
			'style'       => true,
			'data-testid' => true,
		),
		'input'    => array(
			'type'        => true,
			'name'        => true,
			'value'       => true,
			'placeholder' => true,
			'id'          => true,
			'class'       => true,
			'checked'     => true,
			'disabled'    => true,
			'readonly'    => true,
			'required'    => true,
			'min'         => true,
			'max'         => true,
			'step'        => true,
			'aria-label'  => true,
			'style'       => true,
			'data-testid' => true,
		),
		'select'   => array(
			'name'       => true,
			'id'         => true,
			'class'      => true,
			'multiple'   => true,
			'disabled'   => true,
			'required'   => true,
			'aria-label' => true,
			'style'      => true,
		),
		'option'   => array(
			'value'    => true,
			'selected' => true,
			'disabled' => true,
		),
		'label'    => array(
			'for'   => true,
			'class' => true,
		),
		'button'   => array(
			'type'          => true,
			'name'          => true,
			'value'         => true,
			'id'            => true,
			'class'         => true,
			'disabled'      => true,
			'aria-expanded' => true,
			'aria-controls' => true,
			'aria-label'    => true,
			'style'         => true,
			'data-testid'   => true,
		),
		'textarea' => array(
			'name'        => true,
			'id'          => true,
			'class'       => true,
			'rows'        => true,
			'cols'        => true,
			'placeholder' => true,
			'required'    => true,
			'disabled'    => true,
			'readonly'    => true,
			'aria-label'  => true,
		),
		'fieldset' => array(
			'class' => true,
			'id'    => true,
		),
		'legend'   => array(
			'class' => true,
		),
		'iframe'   => array(
			'src'             => true,
			'width'           => true,
			'height'          => true,
			'frameborder'     => true,
			'style'           => true,
			'allowfullscreen' => true,
			'loading'         => true,
			'title'           => true,
		),
	);

	return wp_kses( $html, array_merge( $allowed, $form_tags ) );
}
