/**
 * Store address block editor component.
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
 * Format address object into a string.
 *
 * @param {Object} address Address object.
 * @return {string} Formatted address string.
 */
function formatAddress( address ) {
	if ( ! address ) {
		return __( 'No address available', 'dokan-blocks' );
	}

	const parts = [];

	if ( address.street_1 ) {
		parts.push( address.street_1 );
	}
	if ( address.street_2 ) {
		parts.push( address.street_2 );
	}

	const cityStateZip = [];
	if ( address.city ) {
		cityStateZip.push( address.city );
	}
	if ( address.state ) {
		cityStateZip.push( address.state );
	}
	if ( address.zip ) {
		cityStateZip.push( address.zip );
	}

	if ( cityStateZip.length > 0 ) {
		parts.push( cityStateZip.join( ', ' ) );
	}

	if ( address.country ) {
		parts.push( address.country );
	}

	return parts.length > 0 ? parts.join( ', ' ) : __( 'No address available', 'dokan-blocks' );
}

/**
 * Store address block edit component.
 *
 * @param {Object} props Block props.
 * @param {Object} props.attributes Block attributes.
 * @param {Function} props.setAttributes Function to update attributes.
 * @param {Object} props.context Block context.
 * @return {JSX.Element} Block edit component.
 */
function Edit( { attributes, setAttributes, context } ) {
	const { showIcon = true } = attributes;
	const vendor = context['dokan/vendor'] || {};

	const address = vendor.address || {};
	const formattedAddress = formatAddress( address );

	const blockProps = useBlockProps( {
		className: 'dokan-vendor-store-address',
	} );

	return (
		<>
			<InspectorControls>
				<PanelBody title={ __( 'Settings', 'dokan-blocks' ) } initialOpen={ true }>
					<ToggleControl
						label={ __( 'Show Icon', 'dokan-blocks' ) }
						help={ __( 'Display a location icon before the address.', 'dokan-blocks' ) }
						checked={ showIcon }
						onChange={ ( value ) => setAttributes( { showIcon: value } ) }
					/>
				</PanelBody>
			</InspectorControls>

			<div { ...blockProps }>
				{ showIcon && (
					<span className="dokan-vendor-store-address-icon" aria-hidden="true">
						📍
					</span>
				) }
				<span className="dokan-vendor-store-address-text">
					{ formattedAddress }
				</span>
			</div>
		</>
	);
}

/**
 * Store address block save component.
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
