/**
 * Store sidebar block editor component.
 *
 * @package DokanBlocks
 * @since 1.0.0
 */

import { registerBlockType } from '@wordpress/blocks';
import { useBlockProps } from '@wordpress/block-editor';
import { __ } from '@wordpress/i18n';
import metadata from './block.json';

/**
 * Store sidebar block edit component.
 *
 * @return {JSX.Element} Block edit component.
 */
function Edit() {
	const blockProps = useBlockProps();

	return (
		< div { ...blockProps } >
			< div className = "dokan-vendor-store-sidebar-placeholder" >
				< h2 > { __( 'Store Sidebar', 'dokan-blocks' ) } < / h2 >
				< p > { __( 'This block will display store sidebar with widgets on the frontend.', 'dokan-blocks' ) } < / p >
			< / div >
		< / div >
	);
}

/**
 * Store sidebar block save component.
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
