/**
 * Vendor card block editor component.
 *
 * @package DokanBlocks
 * @since 1.0.0
 */

import { registerBlockType } from '@wordpress/blocks';
import { 
	useBlockProps, 
	InspectorControls, 
	InnerBlocks,
	BlockContextProvider,
} from '@wordpress/block-editor';
import { PanelBody, ToggleControl, TextControl, RangeControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { useMemo, useEffect, useState } from '@wordpress/element';
import apiFetch from '@wordpress/api-fetch';
import metadata from './block.json';
import './editor.scss';
import './style.scss';

/**
 * Allowed blocks inside vendor card.
 * Note: Store banner is NOT allowed as a block, but can be used as background.
 */
const ALLOWED_BLOCKS = [
	'the-another/blocks-for-dokan-vendor-store-name',
	'the-another/blocks-for-dokan-vendor-avatar',
	'the-another/blocks-for-dokan-vendor-rating',
	'the-another/blocks-for-dokan-vendor-store-address',
	'the-another/blocks-for-dokan-vendor-store-phone',
	'the-another/blocks-for-dokan-vendor-store-status',
	'core/group',
	'core/columns',
	'core/column',
	'core/separator',
	'core/spacer',
	'core/heading',
	'core/paragraph',
	'core/image',
	'core/buttons',
	'core/button',
];

/**
 * Default template for vendor card.
 */
const TEMPLATE = [
	[ 'the-another/blocks-for-dokan-vendor-avatar', { width: '10rem', height: '10rem' } ],
	[ 'core/group', {
		style: { spacing: { margin: { top: '1rem' } } },
		layout: { type: 'flex', flexWrap: 'nowrap', justifyContent: 'center' },
	}, [
		[ 'the-another/blocks-for-dokan-vendor-store-name', { tagName: 'h3' } ],
	] ],
];

/**
 * Default placeholder banner SVG.
 */
const PLACEHOLDER_BANNER = 'data:image/svg+xml,%3Csvg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 800 200"%3E%3Crect fill="%23e0e0e0" width="800" height="200"/%3E%3Ctext fill="%23999" x="400" y="100" text-anchor="middle" dy=".3em" font-size="24"%3EStore Banner%3C/text%3E%3C/svg%3E';

/**
 * Vendor card block edit component.
 *
 * @param {Object} props Block props.
 * @param {Object} props.attributes Block attributes.
 * @param {Function} props.setAttributes Function to update attributes.
 * @param {string} props.clientId Block client ID.
 * @param {Object} props.context Block context.
 * @return {JSX.Element} Block edit component.
 */
function Edit( { attributes, setAttributes, clientId, context } ) {
	const {
		vendorId = 0,
		useBannerAsBackground = false,
		backgroundOverlay = 0.5,
	} = attributes;

	// Local state for fetched vendor data (not saved to database)
	const [ fetchedVendorData, setFetchedVendorData ] = useState( null );

	// Get vendor data from context (if inside store-list)
	const contextVendor = context['dokan/vendor'];

	// Fetch vendor data when vendorId changes (only if not in context)
	useEffect( () => {
		if ( ! contextVendor && vendorId > 0 ) {
			// Fetch vendor data from API
			apiFetch( {
				path: `/dokan/v1/stores/${ vendorId }`,
			} )
				.then( ( data ) => {
					setFetchedVendorData( data );
				} )
				.catch( () => {
					// If fetch fails, set to null
					setFetchedVendorData( null );
				} );
		} else if ( ! contextVendor && vendorId === 0 ) {
			// Clear vendor data if vendorId is 0
			setFetchedVendorData( null );
		}
	}, [ vendorId, contextVendor ] );

	// Determine which vendor data to use: context first, then fetched, then placeholder
	const vendorData = contextVendor || fetchedVendorData || {
		banner: PLACEHOLDER_BANNER,
		store_name: __( 'Sample Store', 'dokan-blocks' ),
	};

	// Build background style if using banner as background
	const backgroundStyle = useMemo( () => {
		if ( ! useBannerAsBackground || ! vendorData?.banner ) {
			return {};
		}

		return {
			backgroundImage: `linear-gradient(rgba(0, 0, 0, ${ backgroundOverlay }), rgba(0, 0, 0, ${ backgroundOverlay })), url(${ vendorData.banner })`,
			backgroundSize: 'cover',
			backgroundPosition: 'center',
			backgroundRepeat: 'no-repeat',
		};
	}, [ useBannerAsBackground, backgroundOverlay, vendorData?.banner ] );

	const blockProps = useBlockProps( {
		className: 'dokan-vendor-card',
		style: backgroundStyle,
	} );

	// Context to provide to inner blocks
	const blockContext = useMemo( () => ( {
		'dokan/vendor': vendorData,
	} ), [ vendorData ] );

	return (
		<>
			<InspectorControls>
				{ ! contextVendor && (
					<PanelBody title={ __( 'Store Settings', 'dokan-blocks' ) } initialOpen={ true }>
						<TextControl
							label={ __( 'Vendor ID', 'dokan-blocks' ) }
							help={ __( 'Enter the vendor/store ID to display. Leave as 0 to show a placeholder.', 'dokan-blocks' ) }
							type="number"
							value={ vendorId }
							onChange={ ( value ) => setAttributes( { vendorId: parseInt( value, 10 ) || 0 } ) }
							min={ 0 }
						/>
					</PanelBody>
				) }

				<PanelBody title={ __( 'Background Options', 'dokan-blocks' ) } initialOpen={ true }>
					<ToggleControl
						label={ __( 'Use Banner as Background', 'dokan-blocks' ) }
						help={ __( 'Display the store banner as a background image instead of as a separate block.', 'dokan-blocks' ) }
						checked={ useBannerAsBackground }
						onChange={ ( value ) => setAttributes( { useBannerAsBackground: value } ) }
					/>

					{ useBannerAsBackground && (
						<RangeControl
							label={ __( 'Background Overlay', 'dokan-blocks' ) }
							help={ __( 'Add a dark overlay to improve text readability.', 'dokan-blocks' ) }
							value={ backgroundOverlay }
							onChange={ ( value ) => setAttributes( { backgroundOverlay: value } ) }
							min={ 0 }
							max={ 1 }
							step={ 0.1 }
						/>
					) }
				</PanelBody>
			</InspectorControls>

			<div { ...blockProps }>
				<BlockContextProvider value={ blockContext }>
					<InnerBlocks
						allowedBlocks={ ALLOWED_BLOCKS }
						template={ TEMPLATE }
						templateLock={ false }
						renderAppender={ InnerBlocks.ButtonBlockAppender }
					/>
				</BlockContextProvider>
			</div>
		</>
	);
}

/**
 * Vendor card block save component.
 *
 * @return {JSX.Element} InnerBlocks content.
 */
function Save() {
	const blockProps = useBlockProps.save( {
		className: 'dokan-vendor-card',
	} );

	return (
		<div { ...blockProps }>
			<InnerBlocks.Content />
		</div>
	);
}

registerBlockType(
	metadata.name,
	{
		...metadata,
		edit: Edit,
		save: Save,
	}
);
