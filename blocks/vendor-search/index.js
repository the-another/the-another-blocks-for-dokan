/**
 * Store search block editor component.
 *
 * @package
 * @since 1.0.0
 */

import { registerBlockType } from '@wordpress/blocks';
import { useBlockProps, InspectorControls } from '@wordpress/block-editor';
import {
	PanelBody,
	TextControl,
	SelectControl,
	ColorPalette,
	ToggleControl,
} from '@wordpress/components';
import { useSelect } from '@wordpress/data';
import { __ } from '@wordpress/i18n';
import metadata from './block.json';
import './editor.scss';
import '../_shared/style.scss';
import './style.scss';

/**
 * Store search block edit component.
 *
 * @param {Object}   props               Block props.
 * @param {Object}   props.attributes    Block attributes.
 * @param {Function} props.setAttributes Function to update attributes.
 * @return {JSX.Element} Block edit component.
 */
function Edit( { attributes, setAttributes } ) {
	const blockProps = useBlockProps( {
		className: 'dokan-vendor-search',
	} );

	// Get theme colors for the color palette.
	const themeColors = useSelect( ( select ) => {
		const settings = select( 'core/block-editor' )?.getSettings();
		return settings?.colors || [];
	}, [] );

	const {
		enableSearch = true,
		searchPlaceholder = __( 'Search stores…', 'dokan-blocks' ),
		enableSortBy = true,
		sortByLabel = __( 'Sort by:', 'dokan-blocks' ),
		/* translators: %s: store count number */
		storeCountLabel = __( 'Total store showing: %s', 'dokan-blocks' ),
		enableLocationFilter = false,
		enableRatingFilter = false,
		enableCategoryFilter = false,
		buttonText = __( 'Search', 'dokan-blocks' ),
		buttonSize = 'medium',
		buttonBackgroundColor = '',
		buttonTextColor = '',
	} = attributes;

	// Button style object for preview (user-chosen color overrides only).
	const buttonStyle = {};
	if ( buttonBackgroundColor ) {
		buttonStyle.backgroundColor = buttonBackgroundColor;
	}
	if ( buttonTextColor ) {
		buttonStyle.color = buttonTextColor;
	}

	// Button classes — colors from wp-element-button / theme, sizes from CSS.
	const buttonClasses = [ 'wp-element-button', 'theabd--btn' ];
	if ( buttonSize ) {
		buttonClasses.push( 'theabd--btn-' + buttonSize );
	}

	return (
		<>
			<InspectorControls>
				<PanelBody title={ __( 'Search Settings', 'dokan-blocks' ) }>
					{ /* translators: %s: store count number placeholder */ }
					<TextControl
						label={ __( 'Store Count Label', 'dokan-blocks' ) }
						help={
							/* translators: %s: store count number placeholder */
							__(
								'Use %s as placeholder for the store count number. Example: "Total store showing: %s"',
								'dokan-blocks'
							)
						}
						value={ storeCountLabel }
						onChange={ ( value ) =>
							setAttributes( { storeCountLabel: value } )
						}
					/>

					{ enableSearch && (
						<TextControl
							label={ __( 'Search Placeholder', 'dokan-blocks' ) }
							value={ searchPlaceholder }
							onChange={ ( value ) =>
								setAttributes( { searchPlaceholder: value } )
							}
						/>
					) }

					{ enableSortBy && (
						<TextControl
							label={ __( 'Sort By Label', 'dokan-blocks' ) }
							value={ sortByLabel }
							onChange={ ( value ) =>
								setAttributes( { sortByLabel: value } )
							}
						/>
					) }
				</PanelBody>

				<PanelBody
					title={ __( 'Filter Settings', 'dokan-blocks' ) }
					initialOpen={ false }
				>
					<ToggleControl
						label={ __( 'Enable Location Filter', 'dokan-blocks' ) }
						help={ __(
							'Show a country/state dropdown to filter stores by location.',
							'dokan-blocks'
						) }
						checked={ enableLocationFilter }
						onChange={ ( value ) =>
							setAttributes( {
								enableLocationFilter: value,
							} )
						}
					/>
					<ToggleControl
						label={ __( 'Enable Rating Filter', 'dokan-blocks' ) }
						checked={ enableRatingFilter }
						onChange={ ( value ) =>
							setAttributes( {
								enableRatingFilter: value,
							} )
						}
					/>
					<ToggleControl
						label={ __( 'Enable Category Filter', 'dokan-blocks' ) }
						checked={ enableCategoryFilter }
						onChange={ ( value ) =>
							setAttributes( {
								enableCategoryFilter: value,
							} )
						}
					/>
				</PanelBody>

				<PanelBody title={ __( 'Button Settings', 'dokan-blocks' ) }>
					<TextControl
						label={ __( 'Button Text', 'dokan-blocks' ) }
						value={ buttonText }
						onChange={ ( value ) =>
							setAttributes( { buttonText: value } )
						}
					/>

					<SelectControl
						label={ __( 'Button Size', 'dokan-blocks' ) }
						value={ buttonSize }
						options={ [
							{
								label: __( 'Small', 'dokan-blocks' ),
								value: 'small',
							},
							{
								label: __( 'Medium', 'dokan-blocks' ),
								value: 'medium',
							},
							{
								label: __( 'Large', 'dokan-blocks' ),
								value: 'large',
							},
						] }
						onChange={ ( value ) =>
							setAttributes( { buttonSize: value } )
						}
					/>

					<div className="dokan-block-button-colors">
						<div className="dokan-color-control">
							<label htmlFor="dokan-btn-bg-color">
								{ __( 'Background Color', 'dokan-blocks' ) }
							</label>
							<ColorPalette
								colors={ themeColors }
								value={ buttonBackgroundColor }
								onChange={ ( value ) =>
									setAttributes( {
										buttonBackgroundColor: value || '',
									} )
								}
								clearable={ true }
							/>
						</div>

						<div
							className="dokan-color-control"
							style={ { marginTop: '1rem' } }
						>
							<label htmlFor="dokan-btn-text-color">
								{ __( 'Text Color', 'dokan-blocks' ) }
							</label>
							<ColorPalette
								colors={ themeColors }
								value={ buttonTextColor }
								onChange={ ( value ) =>
									setAttributes( {
										buttonTextColor: value || '',
									} )
								}
								clearable={ true }
							/>
						</div>
					</div>
				</PanelBody>
			</InspectorControls>

			<div { ...blockProps }>
				<div className="dokan-vendor-query-looping-filter-wrap">
					<div className="dokan-store-filter-row">
						<div className="dokan-store-filter-row-inner">
							<div className="dokan-store-filter-left">
								<p className="item store-count">
									{ storeCountLabel.includes( '%s' )
										? storeCountLabel.replace( '%s', '1' )
										: storeCountLabel + ' 1' }
								</p>
							</div>

							{ enableSearch && (
								<div className="dokan-store-filter-right-item">
									<div className="item">
										<div className="dokan-icons">
											<div className="dokan-icon-div"></div>
											<div className="dokan-icon-div"></div>
											<div className="dokan-icon-div"></div>
										</div>
										<button
											type="button"
											className={
												'dokan-vendor-query-loop-filter-button ' +
												buttonClasses.join( ' ' )
											}
											style={
												Object.keys( buttonStyle )
													.length > 0
													? buttonStyle
													: undefined
											}
											disabled
										>
											<span className="dokan-btn-text">
												{ __(
													'Filter',
													'dokan-blocks'
												) }
											</span>
										</button>
									</div>
								</div>
							) }
						</div>

						{ enableSortBy && (
							<div className="dokan-store-filter-row-inner dokan-store-filter-row-sort">
								<form
									name="stores_sorting"
									className="sort-by item"
								>
									<label htmlFor="stores_orderby">
										{ sortByLabel }
									</label>
									<select
										name="stores_orderby"
										id="stores_orderby"
										disabled
									>
										<option value="most_recent">
											{ __(
												'Most Recent',
												'dokan-blocks'
											) }
										</option>
										<option value="total_orders">
											{ __(
												'Most Popular',
												'dokan-blocks'
											) }
										</option>
										<option value="random">
											{ __( 'Random', 'dokan-blocks' ) }
										</option>
									</select>
								</form>
							</div>
						) }
					</div>
				</div>

				{ enableSearch && (
					<form
						role="search"
						method="get"
						name="dokan_store_lists_filter_form"
						id="dokan-vendor-query-looping-filter-form-wrap"
						className="dokan-vendor-search-filter-form"
						style={ { display: 'block' } }
					>
						<div className="dokan-vendor-search-filter-row">
							<div className="store-search grid-item">
								<input
									type="search"
									className="store-search-input dokan-vendor-search-input"
									name="dokan_seller_search"
									placeholder={ searchPlaceholder }
									disabled
								/>
							</div>

							<div className="apply-filter">
								<button
									id="cancel-filter-btn"
									type="button"
									className={ buttonClasses.join( ' ' ) }
									style={
										Object.keys( buttonStyle ).length > 0
											? buttonStyle
											: undefined
									}
									disabled
								>
									<span className="dokan-btn-text">
										{ __( 'Cancel', 'dokan-blocks' ) }
									</span>
								</button>
								<button
									id="apply-filter-btn"
									type="submit"
									className={ buttonClasses.join( ' ' ) }
									style={
										Object.keys( buttonStyle ).length > 0
											? buttonStyle
											: undefined
									}
									disabled
								>
									<span className="dokan-btn-text">
										{ buttonText }
									</span>
								</button>
							</div>
						</div>

						{ ( enableLocationFilter ||
							enableRatingFilter ||
							enableCategoryFilter ) && (
							<div className="dokan-store-advanced-filters">
								{ enableLocationFilter && (
									<div className="dokan-store-filter-field">
										<label htmlFor="dokan_store_location">
											{ __(
												'Location:',
												'dokan-blocks'
											) }
										</label>
										<select
											name="dokan_store_location"
											id="dokan_store_location"
											className="dokan-store-filter-select"
											disabled
										>
											<option value="">
												{ __(
													'All Locations',
													'dokan-blocks'
												) }
											</option>
										</select>
									</div>
								) }

								{ enableRatingFilter && (
									<div className="dokan-store-filter-field">
										<label htmlFor="dokan_store_rating">
											{ __(
												'Minimum Rating:',
												'dokan-blocks'
											) }
										</label>
										<select
											name="dokan_store_rating"
											id="dokan_store_rating"
											className="dokan-store-filter-select"
											disabled
										>
											<option value="">
												{ __(
													'All Ratings',
													'dokan-blocks'
												) }
											</option>
											<option value="5">
												5{ ' ' }
												{ __(
													'Stars',
													'dokan-blocks'
												) }
											</option>
											<option value="4">
												4+{ ' ' }
												{ __(
													'Stars',
													'dokan-blocks'
												) }
											</option>
											<option value="3">
												3+{ ' ' }
												{ __(
													'Stars',
													'dokan-blocks'
												) }
											</option>
										</select>
									</div>
								) }

								{ enableCategoryFilter && (
									<div className="dokan-store-filter-field">
										<label htmlFor="dokan_store_category">
											{ __(
												'Category:',
												'dokan-blocks'
											) }
										</label>
										<select
											name="dokan_store_category"
											id="dokan_store_category"
											className="dokan-store-filter-select"
											disabled
										>
											<option value="">
												{ __(
													'All Categories',
													'dokan-blocks'
												) }
											</option>
										</select>
									</div>
								) }
							</div>
						) }
					</form>
				) }
			</div>
		</>
	);
}

/**
 * Store search block save component.
 *
 * @return {null} Always null for server-side blocks.
 */
function Save() {
	return null;
}

registerBlockType( metadata.name, {
	...metadata,
	edit: Edit,
	save: Save,
} );
