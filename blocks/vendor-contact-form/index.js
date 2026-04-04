/**
 * Store contact form block editor component.
 *
 * @package
 * @since 1.0.0
 */

import { registerBlockType } from '@wordpress/blocks';
import { useBlockProps, InspectorControls } from '@wordpress/block-editor';
import {
	PanelBody,
	Disabled,
	TextControl,
	Placeholder,
} from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import metadata from './block.json';
import './style.scss';

/**
 * Store contact form block edit component.
 *
 * @param {Object} props         Block props.
 * @param {Object} props.context Block context.
 * @return {JSX.Element} Block edit component.
 */
function Edit( { context } ) {
	const blockProps = useBlockProps( {
		className: 'dokan-vendor-contact-form',
	} );

	const vendor = context[ 'dokan/vendor' ] || {};
	const hasVendor = !! vendor.id;

	return (
		<>
			<InspectorControls>
				<PanelBody
					title={ __( 'Contact Form Settings', 'dokan-blocks' ) }
				>
					<p className="components-base-control__help">
						{ __(
							"This form uses Dokan's contact seller functionality including reCAPTCHA and privacy policy settings.",
							'dokan-blocks'
						) }
					</p>
				</PanelBody>
			</InspectorControls>

			<div { ...blockProps }>
				{ hasVendor ? (
					<Disabled>
						<form className="seller-form clearfix dokan-vendor-contact-form-preview">
							<ul className="dokan-form-list">
								<li className="dokan-form-group">
									<TextControl
										placeholder={ __(
											'Your Name',
											'dokan-blocks'
										) }
										value=""
										onChange={ () => {} }
										className="dokan-form-control"
									/>
								</li>

								<li className="dokan-form-group">
									<TextControl
										type="email"
										placeholder={ __(
											'you@example.com',
											'dokan-blocks'
										) }
										value=""
										onChange={ () => {} }
										className="dokan-form-control"
									/>
								</li>

								<li className="dokan-form-group">
									<textarea
										placeholder={ __(
											'Type your message…',
											'dokan-blocks'
										) }
										className="dokan-form-control dokan-textarea"
										rows="6"
										cols="25"
										readOnly
									/>
								</li>
							</ul>

							<button
								type="button"
								className="dokan-right dokan-btn dokan-btn-theme"
							>
								{ __( 'Send Message', 'dokan-blocks' ) }
							</button>
						</form>
					</Disabled>
				) : (
					<Placeholder
						icon="email"
						label={ __( 'Contact Form', 'dokan-blocks' ) }
						instructions={ __(
							'Displays a contact form for the vendor. Requires vendor context.',
							'dokan-blocks'
						) }
					/>
				) }
			</div>
		</>
	);
}

/**
 * Store contact form block save component.
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
