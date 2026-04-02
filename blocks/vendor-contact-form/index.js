/**
 * Store contact form block editor component.
 *
 * @package DokanBlocks
 * @since 1.0.0
 */

import { registerBlockType } from '@wordpress/blocks';
import { useBlockProps, InspectorControls } from '@wordpress/block-editor';
import { PanelBody, Disabled, TextControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import metadata from './block.json';
import './style.scss';

/**
 * Store contact form block edit component.
 *
 * @return {JSX.Element} Block edit component.
 */
function Edit() {
	const blockProps = useBlockProps( {
		className: 'dokan-vendor-contact-form',
	} );

	// Sample form values for editor preview
	const sampleName = __( 'John Doe', 'dokan-blocks' );
	const sampleEmail = 'customer@example.com';

	return (
		<>
			<InspectorControls>
				<PanelBody title={ __( 'Contact Form Settings', 'dokan-blocks' ) }>
					<p className="components-base-control__help">
						{ __( 'This form uses Dokan\'s contact seller functionality including reCAPTCHA and privacy policy settings.', 'dokan-blocks' ) }
					</p>
				</PanelBody>
			</InspectorControls>

			<div { ...blockProps }>
				<Disabled>
					<form className="seller-form clearfix dokan-vendor-contact-form-preview">
						<ul className="dokan-form-list">
							<li className="dokan-form-group">
								<TextControl
									placeholder={ __( 'Your Name', 'dokan-blocks' ) }
									value={ sampleName }
									onChange={ () => {} }
									className="dokan-form-control"
								/>
							</li>

							<li className="dokan-form-group">
								<TextControl
									type="email"
									placeholder={ __( 'you@example.com', 'dokan-blocks' ) }
									value={ sampleEmail }
									onChange={ () => {} }
									className="dokan-form-control"
								/>
							</li>

							<li className="dokan-form-group">
								<textarea
									placeholder={ __( 'Type your message...', 'dokan-blocks' ) }
									className="dokan-form-control dokan-textarea"
									rows="6"
									cols="25"
									readOnly
								/>
							</li>
						</ul>

						<div className="dokan-privacy-policy-text">
							<span className="dokan-privacy-policy-preview">
								{ __( 'Your personal data will be used to process your request. See our privacy policy.', 'dokan-blocks' ) }
							</span>
						</div>

						<button
							type="button"
							className="dokan-right dokan-btn dokan-btn-theme"
						>
							{ __( 'Send Message', 'dokan-blocks' ) }
						</button>
					</form>
				</Disabled>
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

registerBlockType(
	metadata.name,
	{
		...metadata,
		edit: Edit,
		save: Save,
	}
);
