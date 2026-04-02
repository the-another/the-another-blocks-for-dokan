/**
 * Vendor Logo block editor component.
 *
 * @package DokanBlocks
 * @since 1.0.0
 */

import { registerBlockType } from '@wordpress/blocks';
import { useBlockProps, InspectorControls, BlockControls, AlignmentToolbar } from '@wordpress/block-editor';
import { ToggleControl, __experimentalUnitControl as UnitControl, __experimentalToolsPanel as ToolsPanel, __experimentalToolsPanelItem as ToolsPanelItem } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { useMemo } from '@wordpress/element';
import metadata from './block.json';
import './style.scss';

/**
 * Default placeholder avatar SVG.
 */
const PLACEHOLDER_AVATAR = 'data:image/svg+xml,%3Csvg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"%3E%3Crect fill="%23ddd" width="100" height="100"/%3E%3Ctext fill="%23999" x="50" y="50" text-anchor="middle" dy=".3em" font-size="16"%3ELogo%3C/text%3E%3C/svg%3E';

/**
 * Vendor Logo block edit component.
 *
 * @param {Object} props Block props.
 * @param {Object} props.attributes Block attributes.
 * @param {Function} props.setAttributes Function to update attributes.
 * @param {Object} props.context Block context.
 * @return {JSX.Element} Block edit component.
 */
function Edit( { attributes, setAttributes, context } ) {
	const { width = '80px', height = '80px', isLink = true, align, style } = attributes;
	const vendor = context['dokan/vendor'] || {};

	const avatarUrl = vendor.gravatar || PLACEHOLDER_AVATAR;
	const storeName = vendor.store_name || __( 'Store Logo', 'dokan-blocks' );
	const shopUrl = vendor.shop_url || '#';

	// Build inline styles for the wrapper to show border
	const wrapperStyle = useMemo( () => {
		const styles = {};

		// Apply border styles from block supports
		if ( style?.border?.radius ) {
			styles.borderRadius = style.border.radius;
			styles.overflow = 'hidden'; // Ensure border-radius clips the image
		}
		if ( style?.border?.width ) {
			styles.borderWidth = style.border.width;
		}
		if ( style?.border?.style ) {
			styles.borderStyle = style.border.style;
		}
		if ( style?.border?.color ) {
			styles.borderColor = style.border.color;
		}

		return styles;
	}, [ style ] );

	// Build inline styles for the image
	const imageStyle = useMemo( () => {
		return {
			width: width,
			height: height,
			objectFit: 'cover',
			display: 'block',
		};
	}, [ width, height ] );

	const blockProps = useBlockProps( {
		className: `dokan-vendor-avatar${ align ? ` has-text-align-${ align }` : '' }`,
		style: wrapperStyle,
	} );

	const imageElement = (
		<img
			src={ avatarUrl }
			alt={ storeName }
			style={ imageStyle }
			className="dokan-vendor-avatar-image"
		/>
	);

	return (
		<>
			<BlockControls>
				<AlignmentToolbar
					value={ align }
					onChange={ ( newAlign ) => setAttributes( { align: newAlign } ) }
				/>
			</BlockControls>

			<InspectorControls>
				<ToolsPanel label={ __( 'Settings', 'dokan-blocks' ) }>
					<ToolsPanelItem
						hasValue={ () => width !== '80px' }
						label={ __( 'Width', 'dokan-blocks' ) }
						onDeselect={ () => setAttributes( { width: '80px' } ) }
						isShownByDefault
					>
						<UnitControl
							label={ __( 'Width', 'dokan-blocks' ) }
							value={ width }
							onChange={ ( value ) => setAttributes( { width: value } ) }
							units={ [
								{ value: 'px', label: 'px', default: 80 },
								{ value: 'rem', label: 'rem', default: 5 },
								{ value: '%', label: '%', default: 100 },
							] }
						/>
					</ToolsPanelItem>

					<ToolsPanelItem
						hasValue={ () => height !== '80px' }
						label={ __( 'Height', 'dokan-blocks' ) }
						onDeselect={ () => setAttributes( { height: '80px' } ) }
						isShownByDefault
					>
						<UnitControl
							label={ __( 'Height', 'dokan-blocks' ) }
							value={ height }
							onChange={ ( value ) => setAttributes( { height: value } ) }
							units={ [
								{ value: 'px', label: 'px', default: 80 },
								{ value: 'rem', label: 'rem', default: 5 },
								{ value: '%', label: '%', default: 100 },
							] }
						/>
					</ToolsPanelItem>

					<ToolsPanelItem
						hasValue={ () => isLink !== true }
						label={ __( 'Link to Store', 'dokan-blocks' ) }
						onDeselect={ () => setAttributes( { isLink: true } ) }
						isShownByDefault
					>
						<ToggleControl
							label={ __( 'Link to Store', 'dokan-blocks' ) }
							help={ __( 'Make the logo a clickable link to the store page.', 'dokan-blocks' ) }
							checked={ isLink }
							onChange={ ( value ) => setAttributes( { isLink: value } ) }
						/>
					</ToolsPanelItem>
				</ToolsPanel>
			</InspectorControls>

			<div { ...blockProps }>
				{ isLink ? (
					<a href={ shopUrl } onClick={ ( e ) => e.preventDefault() }>
						{ imageElement }
					</a>
				) : (
					imageElement
				) }
			</div>
		</>
	);
}

/**
 * Vendor Logo block save component.
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
