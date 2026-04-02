/**
 * Store phone block editor component.
 *
 * @package DokanBlocks
 * @since 1.0.0
 */

import { registerBlockType } from '@wordpress/blocks';
import { useBlockProps, InspectorControls } from '@wordpress/block-editor';
import { PanelBody, ToggleControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import metadata from './block.json';

/**
 * Store phone block edit component.
 *
 * @param {Object} props Block props.
 * @param {Object} props.attributes Block attributes.
 * @param {Function} props.setAttributes Function to update attributes.
 * @param {Object} props.context Block context.
 * @return {JSX.Element} Block edit component.
 */
function Edit( { attributes, setAttributes, context } ) {
	const { showIcon = true, isLink = true } = attributes;
	const vendor = context['dokan/vendor'] || {};

	const phone = vendor.phone || __( 'No phone number', 'dokan-blocks' );
	const hasPhone = Boolean( vendor.phone );

	const blockProps = useBlockProps( {
		className: 'dokan-vendor-store-phone',
	} );

	const phoneContent = (
		<>
			{ showIcon && (
				<span className="dokan-vendor-store-phone-icon" aria-hidden="true">
					📞
				</span>
			) }
			<span className="dokan-vendor-store-phone-number">
				{ phone }
			</span>
		</>
	);

	return (
		<>
			<InspectorControls>
				<PanelBody title={ __( 'Settings', 'dokan-blocks' ) } initialOpen={ true }>
					<ToggleControl
						label={ __( 'Show Icon', 'dokan-blocks' ) }
						help={ __( 'Display a phone icon before the number.', 'dokan-blocks' ) }
						checked={ showIcon }
						onChange={ ( value ) => setAttributes( { showIcon: value } ) }
					/>
					<ToggleControl
						label={ __( 'Make Clickable', 'dokan-blocks' ) }
						help={ __( 'Make the phone number a clickable tel: link.', 'dokan-blocks' ) }
						checked={ isLink }
						onChange={ ( value ) => setAttributes( { isLink: value } ) }
					/>
				</PanelBody>
			</InspectorControls>

			<div { ...blockProps }>
				{ isLink && hasPhone ? (
					<a href={ `tel:${ phone }` } onClick={ ( e ) => e.preventDefault() }>
						{ phoneContent }
					</a>
				) : (
					phoneContent
				) }
			</div>
		</>
	);
}

/**
 * Store phone block save component.
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
