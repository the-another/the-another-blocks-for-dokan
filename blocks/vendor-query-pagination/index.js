/**
 * Store Query Pagination block editor component.
 *
 * @package DokanBlocks
 * @since 1.0.0
 */

import { registerBlockType } from '@wordpress/blocks';
import { useBlockProps, InspectorControls } from '@wordpress/block-editor';
import { PanelBody, SelectControl, ToggleControl, RangeControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { useMemo } from '@wordpress/element';
import metadata from './block.json';
import './style.scss';

/**
 * Store Query Pagination block edit component.
 *
 * @param {Object} props Block props.
 * @param {Object} props.attributes Block attributes.
 * @param {Function} props.setAttributes Function to update attributes.
 * @param {Object} props.context Block context.
 * @return {JSX.Element} Block edit component.
 */
function Edit( { attributes, setAttributes, context } ) {
	const {
		paginationArrow = 'none',
		showLabel = true,
		midSize = 2,
	} = attributes;

	const blockProps = useBlockProps( {
		className: 'dokan-vendor-query-pagination',
	} );

	// Generate preview pagination based on settings
	const paginationPreview = useMemo( () => {
		const arrow = paginationArrow === 'arrow';
		const prevText = ( arrow ? '← ' : '' ) + ( showLabel ? __( 'Previous', 'dokan-blocks' ) : '' );
		const nextText = ( showLabel ? __( 'Next', 'dokan-blocks' ) : '' ) + ( arrow ? ' →' : '' );

		// Generate page numbers based on midSize
		const pages = [];
		pages.push( { num: 1, current: false } );
		for ( let i = 1; i <= midSize; i++ ) {
			pages.push( { num: 1 + i, current: i === 1 } ); // Current page is 2
		}
		if ( midSize > 0 ) {
			pages.push( { num: '...', current: false } );
			pages.push( { num: 5, current: false } );
		}

		return { prevText, nextText, pages };
	}, [ paginationArrow, showLabel, midSize ] );

	return (
		<>
			<InspectorControls>
				<PanelBody title={ __( 'Pagination Settings', 'dokan-blocks' ) } initialOpen={ true }>
					<SelectControl
						label={ __( 'Pagination Arrow', 'dokan-blocks' ) }
						value={ paginationArrow }
						options={ [
							{ label: __( 'None', 'dokan-blocks' ), value: 'none' },
							{ label: __( 'Arrow', 'dokan-blocks' ), value: 'arrow' },
						] }
						onChange={ ( value ) => setAttributes( { paginationArrow: value } ) }
					/>
					<ToggleControl
						label={ __( 'Show Label', 'dokan-blocks' ) }
						help={ __( 'Display text labels on pagination buttons.', 'dokan-blocks' ) }
						checked={ showLabel }
						onChange={ ( value ) => setAttributes( { showLabel: value } ) }
					/>
					<RangeControl
						label={ __( 'Mid Size', 'dokan-blocks' ) }
						help={ __( 'How many numbers to either side of the current page.', 'dokan-blocks' ) }
						value={ midSize }
						onChange={ ( value ) => setAttributes( { midSize: value } ) }
						min={ 0 }
						max={ 5 }
					/>
				</PanelBody>
			</InspectorControls>

			<nav { ...blockProps }>
				<div className="dokan-vendor-query-pagination-preview">
					{ ( paginationPreview.prevText.trim() ) && (
						<span className="page-numbers prev">{ paginationPreview.prevText }</span>
					) }
					{ paginationPreview.pages.map( ( page, index ) => (
						<span
							key={ index }
							className={ `page-numbers${ page.current ? ' current' : '' }${ page.num === '...' ? ' dots' : '' }` }
						>
							{ page.num }
						</span>
					) ) }
					{ ( paginationPreview.nextText.trim() ) && (
						<span className="page-numbers next">{ paginationPreview.nextText }</span>
					) }
				</div>
			</nav>
		</>
	);
}

/**
 * Store Query Pagination block save component.
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
