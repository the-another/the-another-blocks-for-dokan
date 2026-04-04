/**
 * Store sidebar block editor component.
 *
 * @package
 * @since 1.0.0
 */

import { registerBlockType } from '@wordpress/blocks';
import { useBlockProps, InspectorControls } from '@wordpress/block-editor';
import { PanelBody, ToggleControl, Placeholder } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import metadata from './block.json';

/**
 * Store sidebar block edit component.
 *
 * @param {Object}   props               Block props.
 * @param {Object}   props.attributes    Block attributes.
 * @param {Function} props.setAttributes Function to update attributes.
 * @return {JSX.Element} Block edit component.
 */
function Edit( { attributes, setAttributes } ) {
	const { enableThemeSidebar = false } = attributes;
	const blockProps = useBlockProps();

	return (
		<>
			<InspectorControls>
				<PanelBody
					title={ __( 'Settings', 'dokan-blocks' ) }
					initialOpen={ true }
				>
					<ToggleControl
						label={ __( 'Use Theme Sidebar', 'dokan-blocks' ) }
						help={ __(
							"Display the theme's sidebar instead of the Dokan store sidebar.",
							'dokan-blocks'
						) }
						checked={ enableThemeSidebar }
						onChange={ ( value ) =>
							setAttributes( { enableThemeSidebar: value } )
						}
					/>
				</PanelBody>
			</InspectorControls>

			<div { ...blockProps }>
				<Placeholder
					icon="sidebar"
					label={ __( 'Vendor Store Sidebar', 'dokan-blocks' ) }
					instructions={
						enableThemeSidebar
							? __(
									'Displays the theme sidebar. Rendered on the frontend.',
									'dokan-blocks'
							  )
							: __(
									'Displays the Dokan store sidebar with widgets. Rendered on the frontend.',
									'dokan-blocks'
							  )
					}
				/>
			</div>
		</>
	);
}

/**
 * Store sidebar block save component.
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
