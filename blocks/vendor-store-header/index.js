/**
 * Store header block editor component.
 *
 * @package DokanBlocks
 * @since 1.0.0
 */

import { registerBlockType } from '@wordpress/blocks';
import { useBlockProps } from '@wordpress/block-editor';
import { __ } from '@wordpress/i18n';
import metadata from './block.json';

/**
 * Store header block edit component.
 *
 * @return {JSX.Element} Block edit component.
 */
function Edit() {
	const blockProps = useBlockProps();

	return (
		< div { ...blockProps } >
			< div className = "dokan-vendor-store-header-placeholder" >
				< h2 > { __( 'Store Header', 'dokan-blocks' ) } < / h2 >
				< p > { __( 'This block will display the vendor store header on the frontend.', 'dokan-blocks' ) } < / p >
			< / div >
		< / div >
	);
}

/**
 * Store header block save component (server-side rendered).
 *
 * @return {null} Always null for server-side blocks.
 */
function Save() {
	return null;
}

registerBlockType(
	metadata.name,
	{
		...metadata,
		edit: Edit,
		save: Save,
	}
);
