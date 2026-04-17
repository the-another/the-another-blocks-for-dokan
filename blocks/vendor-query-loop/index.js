/**
 * Store Query Loop block editor component.
 *
 * @package
 * @since 1.0.0
 */

import { registerBlockType } from '@wordpress/blocks';
import {
	useBlockProps,
	InspectorControls,
	BlockControls,
	InnerBlocks,
	useInnerBlocksProps,
} from '@wordpress/block-editor';
import {
	PanelBody,
	RangeControl,
	SelectControl,
	ToggleControl,
	Placeholder,
	Spinner,
	ToolbarGroup,
	ToolbarDropdownMenu,
	Notice,
} from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { useState, useEffect, useMemo } from '@wordpress/element';
import apiFetch from '@wordpress/api-fetch';
import metadata from './block.json';
import './editor.scss';
import './style.scss';

// Default template for vendor cards - includes search at the beginning and pagination at the end
const TEMPLATE = [
	[
		'the-another/blocks-for-dokan-vendor-search',
		{ enableSearch: true, enableSortBy: true },
	],
	[
		'the-another/blocks-for-dokan-vendor-card',
		{
			layout: {
				type: 'flex',
				orientation: 'vertical',
				justifyContent: 'center',
			},
		},
		[
			[
				'the-another/blocks-for-dokan-vendor-avatar',
				{ width: '10rem', height: '10rem' },
			],
			[
				'core/group',
				{
					style: { spacing: { margin: { top: '1rem' } } },
					layout: {
						type: 'flex',
						flexWrap: 'nowrap',
						justifyContent: 'center',
					},
				},
				[
					[
						'the-another/blocks-for-dokan-vendor-store-name',
						{ tagName: 'h3' },
					],
				],
			],
		],
	],
	[
		'the-another/blocks-for-dokan-vendor-query-pagination',
		{ paginationArrow: 'arrow', showLabel: true },
	],
];

// Allowed blocks inside store query loop (top level)
const ALLOWED_BLOCKS = [
	'the-another/blocks-for-dokan-vendor-card',
	'the-another/blocks-for-dokan-vendor-query-pagination',
	'the-another/blocks-for-dokan-vendor-search',
	'core/paragraph',
	'core/separator',
	'core/spacer',
];

/**
 * Placeholder vendor data for when no real vendors are available.
 */
const PLACEHOLDER_VENDOR = {
	id: 0,
	store_name: __( 'Sample Store', 'dokan-blocks' ),
	first_name: 'John',
	last_name: 'Doe',
	gravatar: '',
	banner: '',
	shop_url: '#',
	phone: '+1 (555) 123-4567',
	address: {
		street_1: '123 Main Street',
		street_2: '',
		city: 'New York',
		state: 'NY',
		zip: '10001',
		country: 'US',
	},
	rating: {
		rating: 4.5,
		count: 25,
	},
	store_open_close: {
		enabled: true,
		open_notice: __( 'Store is open', 'dokan-blocks' ),
		close_notice: __( 'Store is closed', 'dokan-blocks' ),
	},
	featured: false,
};

/**
 * Store Query Loop block edit component.
 *
 * @param {Object}   props               Block props.
 * @param {Object}   props.attributes    Block attributes.
 * @param {Function} props.setAttributes Function to update attributes.
 * @return {JSX.Element} Block edit component.
 */
function Edit( { attributes, setAttributes } ) {
	const [ vendors, setVendors ] = useState( [] );
	const [ isLoading, setIsLoading ] = useState( true );
	const [ error, setError ] = useState( null );
	const [ selectedVendorId, setSelectedVendorId ] = useState( null );

	const {
		perPage = 12,
		columns = 3,
		displayLayout = 'grid',
		orderBy = 'name',
		showFeaturedOnly = false,
		enableInfiniteScroll = false,
		infiniteScrollOffset = 400,
	} = attributes;

	// Fetch vendors from Dokan REST API
	useEffect( () => {
		setIsLoading( true );
		setError( null );

		let orderByValue;
		if ( orderBy === 'date' ) {
			orderByValue = 'registered';
		} else if ( orderBy === 'name' ) {
			orderByValue = 'display_name';
		} else {
			orderByValue = orderBy;
		}

		const queryParams = new URLSearchParams( {
			per_page: '10', // Fetch a few vendors for the preview selector
			orderby: orderByValue,
		} );

		if ( showFeaturedOnly ) {
			queryParams.append( 'featured', 'yes' );
		}

		apiFetch( {
			path: `/dokan/v1/stores?${ queryParams.toString() }`,
		} )
			.then( ( response ) => {
				setVendors( response || [] );
				// Auto-select first vendor if none selected
				if ( response?.length > 0 && ! selectedVendorId ) {
					setSelectedVendorId( response[ 0 ].id );
				}
				setIsLoading( false );
			} )
			.catch( ( err ) => {
				// eslint-disable-next-line no-console
				console.error( 'Failed to fetch vendors:', err );
				setError(
					err.message ||
						__( 'Failed to load vendors', 'dokan-blocks' )
				);
				setIsLoading( false );
			} );
	}, [ orderBy, showFeaturedOnly ] );

	// Get the currently selected vendor for preview
	const previewVendor = useMemo( () => {
		if ( vendors.length === 0 ) {
			return PLACEHOLDER_VENDOR;
		}
		const found = vendors.find( ( v ) => v.id === selectedVendorId );
		return found || vendors[ 0 ] || PLACEHOLDER_VENDOR;
	}, [ vendors, selectedVendorId ] );

	// Update the previewVendor attribute so inner blocks can access it via context
	useEffect( () => {
		setAttributes( { previewVendor } );
	}, [ previewVendor, setAttributes ] );

	// Build dropdown items for vendor selection
	const vendorDropdownItems = useMemo( () => {
		if ( vendors.length === 0 ) {
			return [
				{
					title: __( 'No vendors available', 'dokan-blocks' ),
					isDisabled: true,
				},
			];
		}
		return vendors.map( ( vendor ) => ( {
			title: vendor.store_name || `Vendor #${ vendor.id }`,
			icon: selectedVendorId === vendor.id ? 'yes' : undefined,
			onClick: () => setSelectedVendorId( vendor.id ),
		} ) );
	}, [ vendors, selectedVendorId ] );

	const blockProps = useBlockProps();

	// Use inner blocks for the entire query structure
	const innerBlocksProps = useInnerBlocksProps( blockProps, {
		template: TEMPLATE,
		allowedBlocks: ALLOWED_BLOCKS,
		templateLock: false,
		renderAppender: InnerBlocks.ButtonBlockAppender,
	} );

	return (
		<>
			<BlockControls>
				<ToolbarGroup>
					<ToolbarDropdownMenu
						icon="store"
						label={ __( 'Preview Vendor', 'dokan-blocks' ) }
						controls={ vendorDropdownItems }
					/>
				</ToolbarGroup>
			</BlockControls>

			<InspectorControls>
				<PanelBody
					title={ __( 'Preview Settings', 'dokan-blocks' ) }
					initialOpen={ true }
				>
					<SelectControl
						label={ __( 'Preview Vendor', 'dokan-blocks' ) }
						help={ __(
							'Select a vendor to preview how your template will look with real data.',
							'dokan-blocks'
						) }
						value={ selectedVendorId || '' }
						options={ [
							{
								label: __(
									'— Select a vendor —',
									'dokan-blocks'
								),
								value: '',
							},
							...vendors.map( ( vendor ) => ( {
								label:
									vendor.store_name ||
									`Vendor #${ vendor.id }`,
								value: vendor.id,
							} ) ),
						] }
						onChange={ ( value ) =>
							setSelectedVendorId( parseInt( value, 10 ) || null )
						}
					/>
					{ vendors.length === 0 && ! isLoading && (
						<Notice status="warning" isDismissible={ false }>
							{ __(
								'No vendors found. Showing placeholder data.',
								'dokan-blocks'
							) }
						</Notice>
					) }
				</PanelBody>

				<PanelBody
					title={ __( 'Query Settings', 'dokan-blocks' ) }
					initialOpen={ true }
				>
					<RangeControl
						label={ __( 'Items per Page', 'dokan-blocks' ) }
						help={ __(
							'Number of stores to display per page on the frontend.',
							'dokan-blocks'
						) }
						value={ perPage }
						onChange={ ( value ) =>
							setAttributes( { perPage: value } )
						}
						min={ 1 }
						max={ 50 }
					/>
					<SelectControl
						label={ __( 'Order By', 'dokan-blocks' ) }
						value={ orderBy }
						options={ [
							{
								label: __( 'Date', 'dokan-blocks' ),
								value: 'date',
							},
							{
								label: __( 'Name', 'dokan-blocks' ),
								value: 'name',
							},
							{
								label: __( 'Rating', 'dokan-blocks' ),
								value: 'rating',
							},
							{
								label: __( 'Featured', 'dokan-blocks' ),
								value: 'featured',
							},
						] }
						onChange={ ( value ) =>
							setAttributes( { orderBy: value } )
						}
					/>
					<ToggleControl
						label={ __( 'Featured Only', 'dokan-blocks' ) }
						help={ __(
							'Show only featured stores.',
							'dokan-blocks'
						) }
						checked={ showFeaturedOnly }
						onChange={ ( value ) =>
							setAttributes( { showFeaturedOnly: value } )
						}
					/>
					<ToggleControl
						label={ __(
							'Infinite Scroll',
							'another-blocks-for-dokan'
						) }
						help={ __(
							'Automatically load more vendors as the visitor scrolls. URL stays unchanged. When enabled, the pagination block is hidden.',
							'another-blocks-for-dokan'
						) }
						checked={ enableInfiniteScroll }
						onChange={ ( value ) =>
							setAttributes( {
								enableInfiniteScroll: value,
							} )
						}
					/>
					{ enableInfiniteScroll && (
						<RangeControl
							label={ __(
								'Load Trigger Offset (px)',
								'another-blocks-for-dokan'
							) }
							help={ __(
								'How far from the bottom of the list (in pixels) the next page begins loading. Larger values start loading earlier.',
								'another-blocks-for-dokan'
							) }
							value={ infiniteScrollOffset }
							onChange={ ( value ) =>
								setAttributes( {
									infiniteScrollOffset:
										value === undefined ? 400 : value,
								} )
							}
							min={ 0 }
							max={ 2000 }
							step={ 50 }
						/>
					) }
				</PanelBody>

				<PanelBody
					title={ __( 'Layout Settings', 'dokan-blocks' ) }
					initialOpen={ true }
				>
					<SelectControl
						label={ __( 'Layout', 'dokan-blocks' ) }
						value={ displayLayout }
						options={ [
							{
								label: __( 'Grid', 'dokan-blocks' ),
								value: 'grid',
							},
							{
								label: __( 'List', 'dokan-blocks' ),
								value: 'list',
							},
						] }
						onChange={ ( value ) =>
							setAttributes( { displayLayout: value } )
						}
					/>
					{ displayLayout === 'grid' && (
						<RangeControl
							label={ __( 'Columns', 'dokan-blocks' ) }
							help={ __(
								'Number of stores to display per row.',
								'dokan-blocks'
							) }
							value={ columns }
							onChange={ ( value ) =>
								setAttributes( { columns: value } )
							}
							min={ 1 }
							max={ 6 }
						/>
					) }
				</PanelBody>
			</InspectorControls>

			{ isLoading && (
				<div { ...blockProps }>
					<Placeholder>
						<Spinner />
						<span>
							{ __( 'Loading vendors…', 'dokan-blocks' ) }
						</span>
					</Placeholder>
				</div>
			) }
			{ ! isLoading && error && (
				<div { ...blockProps }>
					<Placeholder>
						<Notice status="error" isDismissible={ false }>
							{ error }
						</Notice>
					</Placeholder>
				</div>
			) }
			{ ! isLoading && ! error && <div { ...innerBlocksProps } /> }
		</>
	);
}

/**
 * Store Query Loop block save component.
 *
 * Must return InnerBlocks.Content so inner blocks (search, card, pagination)
 * are serialized to post content and available via $block->inner_blocks on the frontend.
 *
 * @return {JSX.Element} Inner blocks content.
 */
function Save() {
	return <InnerBlocks.Content />;
}

registerBlockType( metadata.name, {
	...metadata,
	edit: Edit,
	save: Save,
} );
