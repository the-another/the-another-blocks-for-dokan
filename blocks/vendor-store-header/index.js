/**
 * Store header block editor component.
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
import { __ } from '@wordpress/i18n';
import metadata from './block.json';

/**
 * Store header block edit component.
 *
 * @param {Object}   props               Block props.
 * @param {Object}   props.attributes    Block attributes.
 * @param {Function} props.setAttributes Function to update attributes.
 * @return {JSX.Element} Block edit component.
 */
function Edit( { attributes, setAttributes } ) {
	const {
		showBanner = true,
		showContactInfo = true,
		showSocialLinks = true,
		showStoreHours = true,
		layout = 'default',
	} = attributes;

	const blockProps = useBlockProps();

	return (
		<>
			<InspectorControls>
				<PanelBody
					title={ __( 'Settings', 'dokan-blocks' ) }
					initialOpen={ true }
				>
					<SelectControl
						label={ __( 'Layout', 'dokan-blocks' ) }
						value={ layout }
						options={ [
							{
								label: __( 'Default', 'dokan-blocks' ),
								value: 'default',
							},
							{
								label: __( 'Layout 2', 'dokan-blocks' ),
								value: 'layout2',
							},
							{
								label: __( 'Layout 3', 'dokan-blocks' ),
								value: 'layout3',
							},
						] }
						onChange={ ( value ) =>
							setAttributes( { layout: value } )
						}
					/>
					<ToggleControl
						label={ __( 'Show Banner', 'dokan-blocks' ) }
						checked={ showBanner }
						onChange={ ( value ) =>
							setAttributes( { showBanner: value } )
						}
					/>
					<ToggleControl
						label={ __( 'Show Contact Info', 'dokan-blocks' ) }
						checked={ showContactInfo }
						onChange={ ( value ) =>
							setAttributes( { showContactInfo: value } )
						}
					/>
					<ToggleControl
						label={ __( 'Show Social Links', 'dokan-blocks' ) }
						checked={ showSocialLinks }
						onChange={ ( value ) =>
							setAttributes( { showSocialLinks: value } )
						}
					/>
					<ToggleControl
						label={ __( 'Show Store Hours', 'dokan-blocks' ) }
						checked={ showStoreHours }
						onChange={ ( value ) =>
							setAttributes( { showStoreHours: value } )
						}
					/>
				</PanelBody>
			</InspectorControls>

			<div { ...blockProps }>
				<Placeholder
					icon="store"
					label={ __( 'Vendor Store Header', 'dokan-blocks' ) }
					instructions={ __(
						'Displays the vendor store header with banner, avatar, and contact info. Configure options in the sidebar.',
						'dokan-blocks'
					) }
				/>
			</div>
		</>
	);
}

/**
 * Store header block save component (server-side rendered).
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
