/**
 * Shared E2E test helpers for creating/deleting vendors, pages, and products.
 */

import type { RequestUtils } from '@wordpress/e2e-test-utils-playwright';

export interface CreateVendorOptions {
	index: number;
	store_name?: string;
	featured?: boolean;
	address?: Record< string, string >;
	phone?: string;
	store_tnc?: string;
	dokan_store_time_enabled?: string;
	dokan_store_time?: Record< string, unknown >;
	store_open_close?: Record< string, unknown >;
}

export interface CreateProductOptions {
	vendor_id: number;
	title: string;
	price?: number;
	status?: string;
}

/**
 * Create a single vendor via the custom REST endpoint.
 */
export async function createVendor(
	requestUtils: RequestUtils,
	options: CreateVendorOptions | number
): Promise< number > {
	const data =
		typeof options === 'number' ? { index: options } : options;
	const result = await requestUtils.rest< { id: number } >( {
		method: 'POST',
		path: '/theabd-test/v1/create-vendor',
		data,
	} );
	return result.id;
}

/** Delete a vendor via the custom REST endpoint. */
export async function deleteVendor(
	requestUtils: RequestUtils,
	userId: number
): Promise< void > {
	try {
		await requestUtils.rest( {
			method: 'DELETE',
			path: `/theabd-test/v1/delete-vendor/${ userId }`,
		} );
	} catch {
		// Best-effort cleanup.
	}
}

/** Create a published page with the given block content. */
export async function createPage(
	requestUtils: RequestUtils,
	title: string,
	content: string
): Promise< { id: number; link: string } > {
	return requestUtils.rest< { id: number; link: string } >( {
		method: 'POST',
		path: '/wp/v2/pages',
		data: { title, content, status: 'publish' },
	} );
}

/** Delete a page (force). */
export async function deletePage(
	requestUtils: RequestUtils,
	pageId: number
): Promise< void > {
	try {
		await requestUtils.rest( {
			method: 'DELETE',
			path: `/wp/v2/pages/${ pageId }?force=true`,
		} );
	} catch {
		// Best-effort cleanup.
	}
}

/** Create a WooCommerce product assigned to a vendor. */
export async function createProduct(
	requestUtils: RequestUtils,
	options: CreateProductOptions
): Promise< number > {
	const result = await requestUtils.rest< { id: number } >( {
		method: 'POST',
		path: '/theabd-test/v1/create-product',
		data: options,
	} );
	return result.id;
}

/** Delete a product (force). */
export async function deleteProduct(
	requestUtils: RequestUtils,
	productId: number
): Promise< void > {
	try {
		await requestUtils.rest( {
			method: 'DELETE',
			path: `/theabd-test/v1/delete-product/${ productId }`,
		} );
	} catch {
		// Best-effort cleanup.
	}
}

/** Block markup builder for common query loop patterns. */
export function queryLoopMarkup(
	attrs: Record< string, unknown >,
	innerBlocks: string
): string {
	const attrStr = JSON.stringify( attrs );
	return `<!-- wp:the-another/blocks-for-dokan-vendor-query-loop ${ attrStr } -->
${ innerBlocks }
<!-- /wp:the-another/blocks-for-dokan-vendor-query-loop -->`;
}
