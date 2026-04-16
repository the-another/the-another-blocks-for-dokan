/**
 * Store terms and conditions block editor component.
 *
 * @package
 * @since 1.0.0
 */

import { registerBlockType } from '@wordpress/blocks';
import { useBlockProps, InspectorControls } from '@wordpress/block-editor';
import {
	PanelBody,
	ToggleControl,
	SelectControl,
	Placeholder,
} from '@wordpress/components';
import { RawHTML } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import metadata from './block.json';

/**
 * Store terms and conditions block edit component.
 *
 * @param {Object}   props               Block props.
 * @param {Object}   props.attributes    Block attributes.
 * @param {Function} props.setAttributes Function to update attributes.
 * @param {Object}   props.context       Block context.
 * @return {JSX.Element} Block edit component.
 */
function Edit( { attributes, setAttributes, context } ) {
	const blockProps = useBlockProps();
	const { showTitle, titleTag } = attributes;
	const vendor = context[ 'dokan/vendor' ] || {};

	const tocContent = vendor.store_info?.dokan_store_toc || '';
	const TitleTag = titleTag || 'h2';

	return (
		<>
			<InspectorControls>
				<PanelBody title={ __( 'Settings', 'dokan-blocks' ) }>
					<ToggleControl
						label={ __( 'Show Title', 'dokan-blocks' ) }
						checked={ showTitle }
						onChange={ ( value ) =>
							setAttributes( { showTitle: value } )
						}
					/>
					{ showTitle && (
						<SelectControl
							label={ __( 'Title Tag', 'dokan-blocks' ) }
							value={ titleTag }
							options={ [
								{ label: 'H1', value: 'h1' },
								{ label: 'H2', value: 'h2' },
								{ label: 'H3', value: 'h3' },
								{ label: 'H4', value: 'h4' },
							] }
							onChange={ ( value ) =>
								setAttributes( { titleTag: value } )
							}
						/>
					) }
				</PanelBody>
			</InspectorControls>

			<div { ...blockProps }>
				{ tocContent ? (
					<div className="tanbfd--vendor-store-terms-conditions">
						{ showTitle && (
							<TitleTag>
								{ __( 'Terms and Conditions', 'dokan-blocks' ) }
							</TitleTag>
						) }
						<RawHTML>{ tocContent }</RawHTML>
					</div>
				) : (
					<Placeholder
						icon="media-document"
						label={ __( 'Terms & Conditions', 'dokan-blocks' ) }
						instructions={ __(
							'Displays vendor terms and conditions. Content will appear when vendor context is available.',
							'dokan-blocks'
						) }
					/>
				) }
			</div>
		</>
	);
}

/**
 * Store terms and conditions block save component.
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
