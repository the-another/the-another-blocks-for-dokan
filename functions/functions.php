<?php

use The_Another\Plugin\Blocks_Dokan\Blocks;
use The_Another\Plugin\Blocks_Dokan\Container\Container;
use The_Another\Plugin\Blocks_Dokan\Container\Hook_Manager;

/**
 * Get the Another Blocks for Dokan instance.
 *
 * @return Blocks The Blocks instance.
 */
function another_blocks_for_dokan(): Blocks {
	return Blocks::get_instance();
}

/**
 * Get the dependency injection container.
 *
 * @return Container The container instance.
 */
function another_blocks_for_dokan_container(): Container {
	return another_blocks_for_dokan()->get_container();
}

/**
 * Get the hook manager.
 *
 * @return Hook_Manager The hook manager instance.
 */
function another_blocks_for_dokan_hooks(): Hook_Manager {
	return another_blocks_for_dokan()->get_hook_manager();
}
