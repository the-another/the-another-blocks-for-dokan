/**
 * Store rating block editor component.
 *
 * @package DokanBlocks
 * @since 1.0.0
 */

import { registerBlockType } from '@wordpress/blocks';
import { useBlockProps, InspectorControls } from '@wordpress/block-editor';
import { PanelBody, ToggleControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import metadata from './block.json';
import './style.scss';

/**
 * Render star rating.
 *
 * @param {number} rating Rating value (0-5).
 * @return {JSX.Element} Star rating element.
 */
function StarRating( { rating } ) {
	const fullStars = Math.floor( rating );
	const hasHalfStar = rating % 1 >= 0.5;
	const emptyStars = 5 - fullStars - ( hasHalfStar ? 1 : 0 );

	return (
		<span className="dokan-vendor-rating-stars" aria-label={ `${ rating } out of 5 stars` }>
			{ [ ...Array( fullStars ) ].map( ( _, i ) => (
				<span key={ `full-${ i }` } className="star star-full">★</span>
			) ) }
			{ hasHalfStar && <span className="star star-half">★</span> }
			{ [ ...Array( emptyStars ) ].map( ( _, i ) => (
				<span key={ `empty-${ i }` } className="star star-empty">☆</span>
			) ) }
		</span>
	);
}

/**
 * Store rating block edit component.
 *
 * @param {Object} props Block props.
 * @param {Object} props.attributes Block attributes.
 * @param {Function} props.setAttributes Function to update attributes.
 * @param {Object} props.context Block context.
 * @return {JSX.Element} Block edit component.
 */
function Edit( { attributes, setAttributes, context } ) {
	const { showCount = true } = attributes;
	const vendor = context['dokan/vendor'] || {};

	// Use vendor rating if available, otherwise show sample data for preview
	const hasVendorData = vendor.rating?.rating !== undefined;
	const rating = hasVendorData ? ( vendor.rating?.rating || 0 ) : 4.5;
	const count = hasVendorData ? ( vendor.rating?.count || 0 ) : 24;

	const blockProps = useBlockProps( {
		className: 'dokan-vendor-rating',
	} );

	return (
		<>
			<InspectorControls>
				<PanelBody title={ __( 'Settings', 'dokan-blocks' ) } initialOpen={ true }>
					<ToggleControl
						label={ __( 'Show Review Count', 'dokan-blocks' ) }
						help={ __( 'Display the number of reviews alongside the rating.', 'dokan-blocks' ) }
						checked={ showCount }
						onChange={ ( value ) => setAttributes( { showCount: value } ) }
					/>
				</PanelBody>
			</InspectorControls>

			<div { ...blockProps }>
				<StarRating rating={ rating } />
				{ showCount && (
					<span className="dokan-vendor-rating-count">
						({ count } { count === 1 ? __( 'review', 'dokan-blocks' ) : __( 'reviews', 'dokan-blocks' ) })
					</span>
				) }
			</div>
		</>
	);
}

/**
 * Store rating block save component.
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
