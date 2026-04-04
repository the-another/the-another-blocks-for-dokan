/**
 * More from seller block editor component.
 *
 * @package
 * @since 1.0.0
 */

import { registerBlockType } from '@wordpress/blocks';
import { useBlockProps, InspectorControls } from '@wordpress/block-editor';
import {
	PanelBody,
	RangeControl,
	SelectControl,
	Placeholder,
} from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import metadata from './block.json';

/**
 * More from seller block edit component.
 *
 * @param {Object}   props               Block props.
 * @param {Object}   props.attributes    Block attributes.
 * @param {Function} props.setAttributes Function to update attributes.
 * @return {JSX.Element} Block edit component.
 */
function Edit( { attributes, setAttributes } ) {
	const { perPage = 6, columns = 4, orderBy = 'rand' } = attributes;
	const blockProps = useBlockProps();

	return (
		<>
			<InspectorControls>
				<PanelBody
					title={ __( 'Settings', 'dokan-blocks' ) }
					initialOpen={ true }
				>
					<RangeControl
						label={ __( 'Products to Show', 'dokan-blocks' ) }
						value={ perPage }
						onChange={ ( value ) =>
							setAttributes( { perPage: value } )
						}
						min={ 1 }
						max={ 24 }
					/>
					<RangeControl
						label={ __( 'Columns', 'dokan-blocks' ) }
						value={ columns }
						onChange={ ( value ) =>
							setAttributes( { columns: value } )
						}
						min={ 1 }
						max={ 6 }
					/>
					<SelectControl
						label={ __( 'Order By', 'dokan-blocks' ) }
						value={ orderBy }
						options={ [
							{
								label: __( 'Random', 'dokan-blocks' ),
								value: 'rand',
							},
							{
								label: __( 'Date', 'dokan-blocks' ),
								value: 'date',
							},
							{
								label: __( 'Title', 'dokan-blocks' ),
								value: 'title',
							},
							{
								label: __( 'Price', 'dokan-blocks' ),
								value: 'price',
							},
							{
								label: __( 'Popularity', 'dokan-blocks' ),
								value: 'popularity',
							},
						] }
						onChange={ ( value ) =>
							setAttributes( { orderBy: value } )
						}
					/>
				</PanelBody>
			</InspectorControls>

			<div { ...blockProps }>
				<Placeholder
					icon="products"
					label={ __( 'More from Seller', 'dokan-blocks' ) }
					instructions={ __(
						'Displays products from the same vendor. Configure products count and layout in the sidebar.',
						'dokan-blocks'
					) }
				/>
			</div>
		</>
	);
}

/**
 * More from seller block save component.
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
