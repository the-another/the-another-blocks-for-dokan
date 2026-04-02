/**
 * Store name block editor component.
 *
 * @package DokanBlocks
 * @since 1.0.0
 */

import { registerBlockType } from '@wordpress/blocks';
import { useBlockProps, InspectorControls, BlockControls, AlignmentToolbar } from '@wordpress/block-editor';
import { PanelBody, SelectControl, ToggleControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import metadata from './block.json';
import './style.scss';

/**
 * Store name block edit component.
 *
 * @param {Object} props Block props.
 * @param {Object} props.attributes Block attributes.
 * @param {Function} props.setAttributes Function to update attributes.
 * @param {Object} props.context Block context.
 * @return {JSX.Element} Block edit component.
 */
function Edit( { attributes, setAttributes, context } ) {
	const { tagName = 'h2', isLink = true, align } = attributes;
	const vendor = context['dokan/vendor'] || {};
	
	const storeName = vendor.store_name || __( 'Store Name', 'dokan-blocks' );
	const shopUrl = vendor.shop_url || '#';

	const blockProps = useBlockProps( {
		className: 'dokan-vendor-store-name',
	} );

	// Dynamically create the tag name element
	const TagName = tagName;

	return (
		<>
			<BlockControls>
				<AlignmentToolbar
					value={ align }
					onChange={ ( newAlign ) => setAttributes( { align: newAlign } ) }
				/>
			</BlockControls>

			<InspectorControls>
				<PanelBody title={ __( 'Settings', 'dokan-blocks' ) } initialOpen={ true }>
					<SelectControl
						label={ __( 'HTML Tag', 'dokan-blocks' ) }
						value={ tagName }
						options={ [
							{ label: 'H1', value: 'h1' },
							{ label: 'H2', value: 'h2' },
							{ label: 'H3', value: 'h3' },
							{ label: 'H4', value: 'h4' },
							{ label: 'H5', value: 'h5' },
							{ label: 'H6', value: 'h6' },
							{ label: 'P', value: 'p' },
							{ label: 'Div', value: 'div' },
						] }
						onChange={ ( value ) => setAttributes( { tagName: value } ) }
					/>
					<ToggleControl
						label={ __( 'Link to Store', 'dokan-blocks' ) }
						help={ __( 'Make the store name a clickable link to the store page.', 'dokan-blocks' ) }
						checked={ isLink }
						onChange={ ( value ) => setAttributes( { isLink: value } ) }
					/>
				</PanelBody>
			</InspectorControls>

			<TagName { ...blockProps }>
				{ isLink ? (
					<a href={ shopUrl } onClick={ ( e ) => e.preventDefault() }>
						{ storeName }
					</a>
				) : (
					storeName
				) }
			</TagName>
		</>
	);
}

/**
 * Store name block save component.
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
