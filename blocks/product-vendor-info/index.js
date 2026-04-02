/**
 * Product vendor info block editor component.
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
import { PanelBody, TextControl, Placeholder, Spinner } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { useMemo, useEffect, useState } from '@wordpress/element';
import apiFetch from '@wordpress/api-fetch';
import metadata from './block.json';

/**
 * Allowed blocks inside product vendor info.
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
	'core/buttons',
	'core/button',
];

/**
 * Default template: minimal layout with avatar and name in a row.
 */
const TEMPLATE = [
	[ 'core/group', {
		layout: { type: 'flex', orientation: 'horizontal' },
		style: { spacing: { blockGap: '1rem' } },
	}, [
		[ 'the-another/blocks-for-dokan-vendor-avatar', { width: '80px', height: '80px' } ],
		[ 'the-another/blocks-for-dokan-vendor-store-name', { tagName: 'h3', isLink: true } ],
	] ],
];

/**
 * Placeholder vendor data for preview.
 */
const PLACEHOLDER_VENDOR = {
	id: 0,
	store_name: __( 'Sample Vendor', 'dokan-blocks' ),
	first_name: 'John',
	last_name: 'Doe',
	gravatar: '',
	banner: '',
	shop_url: '#',
	phone: '+1 (555) 123-4567',
	address: {
		street_1: '123 Main Street',
		city: 'New York',
		state: 'NY',
		zip: '10001',
		country: 'US',
	},
	rating: {
		rating: 4.5,
		count: 24,
	},
};

/**
 * Product vendor info block edit component.
 *
 * @param {Object} props Block props.
 * @param {Object} props.attributes Block attributes.
 * @param {Function} props.setAttributes Function to update attributes.
 * @return {JSX.Element} Block edit component.
 */
function Edit( { attributes, setAttributes } ) {
	const { productId = 0 } = attributes;

	// Local state for fetched vendor data
	const [ vendorData, setVendorData ] = useState( null );
	const [ isLoading, setIsLoading ] = useState( false );
	const [ error, setError ] = useState( null );

	// Fetch vendor data from product
	useEffect( () => {
		if ( productId === 0 ) {
			// Use placeholder for productId = 0
			setVendorData( PLACEHOLDER_VENDOR );
			setIsLoading( false );
			setError( null );
			return;
		}

		setIsLoading( true );
		setError( null );

		// Fetch product to get vendor ID from post_author
		apiFetch( {
			path: `/wp/v2/product/${ productId }`,
		} )
			.then( ( product ) => {
				const vendorId = product.author;

				if ( ! vendorId ) {
					setError( __( 'Product has no vendor associated.', 'dokan-blocks' ) );
					setVendorData( null );
					setIsLoading( false );
					return;
				}

				// Fetch vendor data
				return apiFetch( {
					path: `/dokan/v1/stores/${ vendorId }`,
				} );
			} )
			.then( ( vendor ) => {
				if ( vendor ) {
					setVendorData( vendor );
					setIsLoading( false );
				}
			} )
			.catch( ( err ) => {
				setError( err.message || __( 'Failed to load vendor data.', 'dokan-blocks' ) );
				setVendorData( null );
				setIsLoading( false );
			} );
	}, [ productId ] );

	// Use placeholder if no vendor data
	const displayVendorData = vendorData || PLACEHOLDER_VENDOR;

	const blockProps = useBlockProps( {
		className: 'dokan-product-vendor-info',
	} );

	// Context to provide to inner blocks
	const blockContext = useMemo( () => ( {
		'dokan/vendor': displayVendorData,
	} ), [ displayVendorData ] );

	// Show loading state
	if ( isLoading ) {
		return (
			<div { ...blockProps }>
				<Placeholder
					icon="admin-users"
					label={ __( 'Product Vendor Info', 'dokan-blocks' ) }
				>
					<Spinner />
					<p>{ __( 'Loading vendor data...', 'dokan-blocks' ) }</p>
				</Placeholder>
			</div>
		);
	}

	// Show error state
	if ( error ) {
		return (
			<>
				<InspectorControls>
					<PanelBody title={ __( 'Product Settings', 'dokan-blocks' ) } initialOpen={ true }>
						<TextControl
							label={ __( 'Product ID', 'dokan-blocks' ) }
							help={ __( 'Enter a specific product ID, or leave as 0 to auto-detect on product pages.', 'dokan-blocks' ) }
							type="number"
							value={ productId }
							onChange={ ( value ) => setAttributes( { productId: parseInt( value, 10 ) || 0 } ) }
							min={ 0 }
						/>
					</PanelBody>
				</InspectorControls>

				<div { ...blockProps }>
					<Placeholder
						icon="admin-users"
						label={ __( 'Product Vendor Info', 'dokan-blocks' ) }
					>
						<p style={ { color: '#d63638' } }>{ error }</p>
					</Placeholder>
				</div>
			</>
		);
	}

	return (
		<>
			<InspectorControls>
				<PanelBody title={ __( 'Product Settings', 'dokan-blocks' ) } initialOpen={ true }>
					<TextControl
						label={ __( 'Product ID', 'dokan-blocks' ) }
						help={ __( 'Enter a specific product ID, or leave as 0 to auto-detect on product pages.', 'dokan-blocks' ) }
						type="number"
						value={ productId }
						onChange={ ( value ) => setAttributes( { productId: parseInt( value, 10 ) || 0 } ) }
						min={ 0 }
					/>
					{ productId === 0 && (
						<p style={ { fontSize: '12px', color: '#757575', marginTop: '8px' } }>
							{ __( 'Using placeholder data. On the frontend, the vendor will be auto-detected from the current product.', 'dokan-blocks' ) }
						</p>
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
 * Product vendor info block save component.
 *
 * @return {JSX.Element} InnerBlocks content.
 */
function Save() {
	const blockProps = useBlockProps.save( {
		className: 'dokan-product-vendor-info',
	} );

	return (
		<div { ...blockProps }>
			<InnerBlocks.Content />
		</div>
	);
}

/**
 * Deprecated version 1: Old static attributes.
 * Migrates old layout/showAddress/showRating to new InnerBlocks structure.
 */
const deprecated = [
	{
		attributes: {
			productId: {
				type: 'number',
				default: 0,
			},
			layout: {
				type: 'string',
				default: 'inline',
			},
			showAddress: {
				type: 'boolean',
				default: true,
			},
			showRating: {
				type: 'boolean',
				default: true,
			},
		},
		save() {
			return null;
		},
		migrate( attributes ) {
			// Keep only productId, drop deprecated attributes
			return {
				productId: attributes.productId || 0,
			};
		},
	},
];

registerBlockType(
	metadata.name,
	{
		...metadata,
		edit: Edit,
		save: Save,
		deprecated,
	}
);