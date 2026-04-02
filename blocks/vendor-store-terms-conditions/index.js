/**
 * Store terms and conditions block editor component.
 *
 * @package DokanBlocks
 * @since 1.0.0
 */

import { registerBlockType } from '@wordpress/blocks';
import { useBlockProps, InspectorControls } from '@wordpress/block-editor';
import { PanelBody, ToggleControl, SelectControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import metadata from './block.json';

/**
 * Store terms and conditions block edit component.
 *
 * @param {Object} props Block props.
 * @return {JSX.Element} Block edit component.
 */
function Edit( { attributes, setAttributes } ) {
	const blockProps = useBlockProps();
	const { showTitle, titleTag } = attributes;

	return (
		<>
			<InspectorControls>
				<PanelBody title={ __( 'Settings', 'dokan-blocks' ) }>
					<ToggleControl
						label={ __( 'Show Title', 'dokan-blocks' ) }
						checked={ showTitle }
						onChange={ ( value ) => setAttributes( { showTitle: value } ) }
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
							onChange={ ( value ) => setAttributes( { titleTag: value } ) }
						/>
					) }
				</PanelBody>
			</InspectorControls>
			<div { ...blockProps }>
				<div className="dokan-vendor-store-terms-conditions-placeholder">
					{ showTitle && (
						<h2>{ __( 'Terms and Conditions', 'dokan-blocks' ) }</h2>
					) }
					<p className="dokan-block-placeholder-text">
						{ __( 'Vendor\'s terms and conditions content will appear here.', 'dokan-blocks' ) }
					</p>
				</div>
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

registerBlockType(
	metadata.name,
	{
		...metadata,
		edit: Edit,
		save: Save,
	}
);
