/**
 * Store banner block editor component.
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
	BlockControls,
} from '@wordpress/block-editor';
import { 
	PanelBody, 
	RangeControl, 
	SelectControl, 
	ComboboxControl,
	Spinner,
	Notice,
	ToolbarGroup,
	ToolbarDropdownMenu,
} from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { useMemo, useEffect, useState, useCallback } from '@wordpress/element';
import { debounce } from '@wordpress/compose';
import apiFetch from '@wordpress/api-fetch';
import metadata from './block.json';
import './editor.scss';
import './style.scss';

/**
 * Allowed blocks inside store banner.
 * Includes all store-specific blocks and basic layout blocks.
 */
const ALLOWED_BLOCKS = [
	'the-another/blocks-for-dokan-vendor-store-name',
	'the-another/blocks-for-dokan-vendor-avatar',
	'the-another/blocks-for-dokan-vendor-rating',
	'the-another/blocks-for-dokan-vendor-store-address',
	'the-another/blocks-for-dokan-vendor-store-phone',
	'the-another/blocks-for-dokan-vendor-store-status',
	'the-another/blocks-for-dokan-vendor-store-hours',
	'the-another/blocks-for-dokan-vendor-store-location',
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
 * Default template for store banner (single vendor page).
 */
const TEMPLATE = [
	[ 'core/group', {
		style: {
			spacing: {
				padding: { top: '2rem', bottom: '2rem', left: '2rem', right: '2rem' }
			}
		},
		layout: { type: 'flex', flexWrap: 'wrap', justifyContent: 'left' },
	}, [
		[ 'the-another/blocks-for-dokan-vendor-avatar', { width: '8rem', height: '8rem' } ],
		[ 'core/group', {
			style: { spacing: { margin: { left: '2rem' } } },
			layout: { type: 'flex', orientation: 'vertical' },
		}, [
			[ 'the-another/blocks-for-dokan-vendor-store-name', { tagName: 'h1' } ],
			[ 'the-another/blocks-for-dokan-vendor-rating' ],
			[ 'the-another/blocks-for-dokan-vendor-store-hours', { layout: 'compact', showCurrentStatus: true } ],
		] ],
	] ],
];

/**
 * Default placeholder banner SVG.
 */
const PLACEHOLDER_BANNER = 'data:image/svg+xml,%3Csvg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1200 300"%3E%3Crect fill="%23e0e0e0" width="1200" height="300"/%3E%3Ctext fill="%23999" x="600" y="150" text-anchor="middle" dy=".3em" font-size="24"%3EStore Banner%3C/text%3E%3C/svg%3E';

/**
 * Placeholder vendor data for preview.
 */
const PLACEHOLDER_VENDOR = {
	id: 0,
	store_name: __( 'Sample Store', 'dokan-blocks' ),
	first_name: 'John',
	last_name: 'Doe',
	gravatar: '',
	banner: PLACEHOLDER_BANNER,
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
	store_open_close: {
		enabled: true,
		open_notice: __( 'Store Open', 'dokan-blocks' ),
		close_notice: __( 'Store Closed', 'dokan-blocks' ),
	},
};

/**
 * Store banner block edit component.
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
		height = 300,
		minHeight = 200,
		backgroundOverlay = 0.3,
		overlayColor = '#000000',
		backgroundPosition = 'center',
		backgroundSize = 'cover',
	} = attributes;

	// Preview vendor state - NOT saved to attributes, only for editor preview
	const [ previewVendorId, setPreviewVendorId ] = useState( 0 );
	const [ vendorOptions, setVendorOptions ] = useState( [] );
	const [ isSearching, setIsSearching ] = useState( false );
	const [ previewVendorData, setPreviewVendorData ] = useState( null );
	const [ recentVendors, setRecentVendors ] = useState( [] );

	// Get vendor data from context (if inside store-list or other parent)
	const contextVendor = context['dokan/vendor'];

	// Fetch initial vendors list for dropdown
	useEffect( () => {
		apiFetch( {
			path: '/dokan/v1/stores?per_page=10&orderby=display_name',
		} )
			.then( ( response ) => {
				const options = ( response || [] ).map( ( vendor ) => ( {
					value: vendor.id,
					label: vendor.store_name || `Vendor #${ vendor.id }`,
					vendor: vendor,
				} ) );
				setRecentVendors( options );
				if ( vendorOptions.length === 0 ) {
					setVendorOptions( options );
				}
			} )
			.catch( () => {
				// Silently fail - vendors will be searched on demand
			} );
	}, [] );

	// Debounced search function
	const searchVendors = useCallback(
		debounce( ( searchTerm ) => {
			if ( ! searchTerm || searchTerm.length < 2 ) {
				setVendorOptions( recentVendors );
				setIsSearching( false );
				return;
			}

			setIsSearching( true );

			apiFetch( {
				path: `/dokan/v1/stores?search=${ encodeURIComponent( searchTerm ) }&per_page=20`,
			} )
				.then( ( response ) => {
					const options = ( response || [] ).map( ( vendor ) => ( {
						value: vendor.id,
						label: vendor.store_name || `Vendor #${ vendor.id }`,
						vendor: vendor,
					} ) );
					setVendorOptions( options );
					setIsSearching( false );
				} )
				.catch( () => {
					setVendorOptions( [] );
					setIsSearching( false );
				} );
		}, 300 ),
		[ recentVendors ]
	);

	// Fetch vendor data when preview vendor changes
	useEffect( () => {
		if ( previewVendorId > 0 ) {
			// First check if vendor is in our cached options
			const cachedVendor = [ ...recentVendors, ...vendorOptions ].find( 
				( opt ) => opt.value === previewVendorId 
			);
			
			if ( cachedVendor?.vendor ) {
				setPreviewVendorData( cachedVendor.vendor );
				return;
			}

			// Fetch vendor data from API
			apiFetch( {
				path: `/dokan/v1/stores/${ previewVendorId }`,
			} )
				.then( ( data ) => {
					setPreviewVendorData( data );
				} )
				.catch( () => {
					setPreviewVendorData( null );
				} );
		} else {
			setPreviewVendorData( null );
		}
	}, [ previewVendorId, recentVendors, vendorOptions ] );

	// Determine which vendor data to use for preview:
	// 1. Context vendor (if inside store-list)
	// 2. Preview vendor (selected in editor for preview)
	// 3. Placeholder vendor
	const vendorData = contextVendor || previewVendorData || PLACEHOLDER_VENDOR;

	// Get current vendor name for display
	const currentVendorName = useMemo( () => {
		if ( contextVendor ) {
			return contextVendor.store_name || __( 'Current Vendor (from context)', 'dokan-blocks' );
		}
		if ( previewVendorId === 0 ) {
			return __( 'Sample Store', 'dokan-blocks' );
		}
		if ( previewVendorData ) {
			return previewVendorData.store_name || `Vendor #${ previewVendorId }`;
		}
		return __( 'Loading...', 'dokan-blocks' );
	}, [ contextVendor, previewVendorId, previewVendorData ] );

	// Parse overlay color to get RGB values for overlay
	const overlayRgba = useMemo( () => {
		const hex = overlayColor.replace( '#', '' );
		const r = parseInt( hex.substring( 0, 2 ), 16 ) || 0;
		const g = parseInt( hex.substring( 2, 4 ), 16 ) || 0;
		const b = parseInt( hex.substring( 4, 6 ), 16 ) || 0;
		return `rgba(${ r }, ${ g }, ${ b }, ${ backgroundOverlay })`;
	}, [ overlayColor, backgroundOverlay ] );

	// Build background style with banner as background
	const backgroundStyle = useMemo( () => {
		const bannerUrl = vendorData?.banner || PLACEHOLDER_BANNER;

		return {
			backgroundImage: `linear-gradient(${ overlayRgba }, ${ overlayRgba }), url(${ bannerUrl })`,
			backgroundSize: backgroundSize,
			backgroundPosition: backgroundPosition,
			backgroundRepeat: 'no-repeat',
			minHeight: `${ minHeight }px`,
			height: height ? `${ height }px` : 'auto',
		};
	}, [ vendorData?.banner, overlayRgba, backgroundSize, backgroundPosition, height, minHeight ] );

	const blockProps = useBlockProps( {
		className: 'dokan-vendor-store-banner',
		style: backgroundStyle,
	} );

	// Context to provide to inner blocks
	const blockContext = useMemo( () => ( {
		'dokan/vendor': vendorData,
	} ), [ vendorData ] );

	// Build dropdown items for toolbar vendor selector
	const vendorDropdownItems = useMemo( () => {
		const items = recentVendors.slice( 0, 5 ).map( ( option ) => ( {
			title: option.label,
			icon: previewVendorId === option.value ? 'yes' : undefined,
			onClick: () => setPreviewVendorId( option.value ),
		} ) );

		if ( items.length === 0 ) {
			return [
				{
					title: __( 'No vendors available', 'dokan-blocks' ),
					isDisabled: true,
				},
			];
		}

		// Add option to use placeholder
		items.unshift( {
			title: __( '— Sample Store —', 'dokan-blocks' ),
			icon: previewVendorId === 0 ? 'yes' : undefined,
			onClick: () => setPreviewVendorId( 0 ),
		} );

		return items;
	}, [ recentVendors, previewVendorId ] );

	return (
		<>
			{ ! contextVendor && (
				<BlockControls>
					<ToolbarGroup>
						<ToolbarDropdownMenu
							icon="store"
							label={ __( 'Preview Vendor', 'dokan-blocks' ) }
							controls={ vendorDropdownItems }
						/>
					</ToolbarGroup>
				</BlockControls>
			) }

			<InspectorControls>
				{ ! contextVendor && (
					<PanelBody title={ __( 'Preview Settings', 'dokan-blocks' ) } initialOpen={ true }>
						<ComboboxControl
							label={ __( 'Preview Vendor', 'dokan-blocks' ) }
							help={ __( 'Select a vendor to preview how the banner will look with real data. This is for preview only and will not be saved.', 'dokan-blocks' ) }
							value={ previewVendorId || '' }
							options={ [
								{ value: 0, label: __( '— Sample Store —', 'dokan-blocks' ) },
								...vendorOptions,
							] }
							onChange={ ( value ) => {
								const newVendorId = parseInt( value, 10 ) || 0;
								setPreviewVendorId( newVendorId );
								
								// Update preview data from cached options
								if ( newVendorId > 0 ) {
									const selected = vendorOptions.find( ( opt ) => opt.value === newVendorId );
									if ( selected?.vendor ) {
										setPreviewVendorData( selected.vendor );
									}
								}
							} }
							onFilterValueChange={ searchVendors }
						/>
						{ isSearching && (
							<div style={ { display: 'flex', alignItems: 'center', gap: '8px', marginTop: '8px' } }>
								<Spinner />
								<span>{ __( 'Searching vendors...', 'dokan-blocks' ) }</span>
							</div>
						) }
						<div style={ { marginTop: '12px', padding: '8px', background: '#f0f0f0', borderRadius: '4px' } }>
							<strong>{ __( 'Previewing:', 'dokan-blocks' ) }</strong> { currentVendorName }
						</div>
						<Notice status="info" isDismissible={ false } style={ { marginTop: '12px' } }>
							{ __( 'On the frontend, the vendor is automatically detected from the store page. This preview selection is not saved.', 'dokan-blocks' ) }
						</Notice>
					</PanelBody>
				) }

				<PanelBody title={ __( 'Banner Settings', 'dokan-blocks' ) } initialOpen={ !! contextVendor }>
					<RangeControl
						label={ __( 'Banner Height', 'dokan-blocks' ) }
						help={ __( 'Set the height of the banner container.', 'dokan-blocks' ) }
						value={ height }
						onChange={ ( value ) => setAttributes( { height: value } ) }
						min={ 100 }
						max={ 800 }
						step={ 10 }
					/>

					<RangeControl
						label={ __( 'Minimum Height', 'dokan-blocks' ) }
						help={ __( 'Set the minimum height to ensure content is always visible.', 'dokan-blocks' ) }
						value={ minHeight }
						onChange={ ( value ) => setAttributes( { minHeight: value } ) }
						min={ 100 }
						max={ 600 }
						step={ 10 }
					/>

					<SelectControl
						label={ __( 'Background Size', 'dokan-blocks' ) }
						value={ backgroundSize }
						options={ [
							{ label: __( 'Cover', 'dokan-blocks' ), value: 'cover' },
							{ label: __( 'Contain', 'dokan-blocks' ), value: 'contain' },
							{ label: __( 'Auto', 'dokan-blocks' ), value: 'auto' },
						] }
						onChange={ ( value ) => setAttributes( { backgroundSize: value } ) }
					/>

					<SelectControl
						label={ __( 'Background Position', 'dokan-blocks' ) }
						value={ backgroundPosition }
						options={ [
							{ label: __( 'Center', 'dokan-blocks' ), value: 'center' },
							{ label: __( 'Top', 'dokan-blocks' ), value: 'top' },
							{ label: __( 'Bottom', 'dokan-blocks' ), value: 'bottom' },
							{ label: __( 'Left', 'dokan-blocks' ), value: 'left' },
							{ label: __( 'Right', 'dokan-blocks' ), value: 'right' },
						] }
						onChange={ ( value ) => setAttributes( { backgroundPosition: value } ) }
					/>
				</PanelBody>

				<PanelBody title={ __( 'Overlay Settings', 'dokan-blocks' ) } initialOpen={ false }>
					<RangeControl
						label={ __( 'Overlay Opacity', 'dokan-blocks' ) }
						help={ __( 'Add a dark overlay to improve text readability.', 'dokan-blocks' ) }
						value={ backgroundOverlay }
						onChange={ ( value ) => setAttributes( { backgroundOverlay: value } ) }
						min={ 0 }
						max={ 1 }
						step={ 0.05 }
					/>

					<SelectControl
						label={ __( 'Overlay Color', 'dokan-blocks' ) }
						value={ overlayColor }
						options={ [
							{ label: __( 'Black', 'dokan-blocks' ), value: '#000000' },
							{ label: __( 'Dark Blue', 'dokan-blocks' ), value: '#1a237e' },
							{ label: __( 'Dark Green', 'dokan-blocks' ), value: '#1b5e20' },
							{ label: __( 'Dark Red', 'dokan-blocks' ), value: '#b71c1c' },
							{ label: __( 'Dark Purple', 'dokan-blocks' ), value: '#4a148c' },
							{ label: __( 'White', 'dokan-blocks' ), value: '#ffffff' },
						] }
						onChange={ ( value ) => setAttributes( { overlayColor: value } ) }
					/>
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
 * Store banner block save component.
 *
 * @return {JSX.Element} InnerBlocks content.
 */
function Save() {
	const blockProps = useBlockProps.save( {
		className: 'dokan-vendor-store-banner',
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
