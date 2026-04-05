/**
 * Become vendor CTA block editor component.
 *
 * @package
 * @since 1.0.0
 */

import { registerBlockType } from '@wordpress/blocks';
import { useBlockProps, InspectorControls } from '@wordpress/block-editor';
import { PanelBody, TextControl, Disabled } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import metadata from './block.json';

/**
 * Become vendor CTA block edit component.
 *
 * @param {Object}   props               Block props.
 * @param {Object}   props.attributes    Block attributes.
 * @param {Function} props.setAttributes Function to update attributes.
 * @return {JSX.Element} Block edit component.
 */
function Edit( { attributes, setAttributes } ) {
	const {
		heading = __( 'Become a Vendor', 'dokan-blocks' ),
		description = __(
			'Vendors can sell products and manage a store with a vendor dashboard.',
			'dokan-blocks'
		),
		buttonText = __( 'Become a Vendor', 'dokan-blocks' ),
		buttonLink = '',
	} = attributes;

	const blockProps = useBlockProps( {
		className: 'theabd--become-vendor-cta',
	} );

	return (
		<>
			<InspectorControls>
				<PanelBody
					title={ __( 'Content', 'dokan-blocks' ) }
					initialOpen={ true }
				>
					<TextControl
						label={ __( 'Heading', 'dokan-blocks' ) }
						value={ heading }
						onChange={ ( value ) =>
							setAttributes( { heading: value } )
						}
					/>
					<TextControl
						label={ __( 'Description', 'dokan-blocks' ) }
						value={ description }
						onChange={ ( value ) =>
							setAttributes( { description: value } )
						}
					/>
					<TextControl
						label={ __( 'Button Text', 'dokan-blocks' ) }
						value={ buttonText }
						onChange={ ( value ) =>
							setAttributes( { buttonText: value } )
						}
					/>
					<TextControl
						label={ __( 'Button Link', 'dokan-blocks' ) }
						value={ buttonLink }
						onChange={ ( value ) =>
							setAttributes( { buttonLink: value } )
						}
					/>
				</PanelBody>
			</InspectorControls>

			<div { ...blockProps }>
				<Disabled>
					<ul className="theabd--account-migration-lists">
						<li>
							<div className="theabd--w8 theabd--left-content">
								<p>
									<strong>{ heading }</strong>
								</p>
								<p>{ description }</p>
							</div>
							<div className="theabd--w4 theabd--right-content">
								<button
									type="button"
									className="wp-element-button theabd--btn"
								>
									{ buttonText }
								</button>
							</div>
							<div className="theabd--clearfix"></div>
						</li>
					</ul>
				</Disabled>
			</div>
		</>
	);
}

/**
 * Become vendor CTA block save component.
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
