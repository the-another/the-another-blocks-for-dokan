/**
 * Become vendor CTA block editor component.
 *
 * @package DokanBlocks
 * @since 1.0.0
 */

import { registerBlockType } from '@wordpress/blocks';
import { useBlockProps } from '@wordpress/block-editor';
import { __ } from '@wordpress/i18n';
import metadata from './block.json';

/**
 * Become vendor CTA block edit component.
 *
 * @return {JSX.Element} Block edit component.
 */
function Edit() {
	const blockProps = useBlockProps();

	return (
		<div { ...blockProps } >
			<div className = "dokan-become-vendor-cta-placeholder" >
				<h2 > { __( 'Become Vendor CTA', 'dokan-blocks' ) } </h2>
				<p > { __( 'This block will display a call-to-action to become a vendor on the frontend.', 'dokan-blocks' ) } </p>
			</div>
		</div>
	);
}

/**
 * Become vendor CTA block save component.
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
