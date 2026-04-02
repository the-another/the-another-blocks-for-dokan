/**
 * Store location block editor component.
 *
 * @package DokanBlocks
 * @since 1.0.0
 */

import { registerBlockType } from '@wordpress/blocks';
import { useBlockProps } from '@wordpress/block-editor';
import { __ } from '@wordpress/i18n';
import metadata from './block.json';

/**
 * Store location block edit component.
 *
 * @return {JSX.Element} Block edit component.
 */
function Edit() {
	const blockProps = useBlockProps();

	return (
		< div { ...blockProps } >
			< div className = "dokan-vendor-store-location-placeholder" >
				< h2 > { __( 'Store Location', 'dokan-blocks' ) } < / h2 >
				< p > { __( 'This block will display the store location on a map on the frontend.', 'dokan-blocks' ) } < / p >
			< / div >
		< / div >
	);
}

/**
 * Store location block save component.
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
