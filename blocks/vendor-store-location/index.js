/**
 * Store location block editor component.
 *
 * @package
 * @since 1.0.0
 */

import { registerBlockType } from '@wordpress/blocks';
import { useBlockProps } from '@wordpress/block-editor';
import { Placeholder } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import metadata from './block.json';

/**
 * Store location block edit component.
 *
 * @param {Object} props         Block props.
 * @param {Object} props.context Block context.
 * @return {JSX.Element} Block edit component.
 */
function Edit( { context } ) {
	const blockProps = useBlockProps();
	const vendor = context[ 'dokan/vendor' ] || {};
	const address = vendor.address || {};

	const hasLocation =
		address.street_1 || address.city || address.state || address.country;

	return (
		<div { ...blockProps }>
			{ hasLocation ? (
				<div className="theabd--vendor-store-location">
					<div className="theabd--vendor-store-location-address">
						<span className="dashicons dashicons-location"></span>
						<span>
							{ [
								address.street_1,
								address.street_2,
								address.city,
								address.state,
								address.zip,
								address.country,
							]
								.filter( Boolean )
								.join( ', ' ) }
						</span>
					</div>
					<div
						className="theabd--vendor-store-location-map-placeholder"
						style={ {
							background: '#f0f0f0',
							padding: '2rem',
							textAlign: 'center',
							color: '#757575',
						} }
					>
						{ __(
							'Map will be rendered on the frontend.',
							'dokan-blocks'
						) }
					</div>
				</div>
			) : (
				<Placeholder
					icon="location"
					label={ __( 'Store Location', 'dokan-blocks' ) }
					instructions={ __(
						'Displays the store location map. Requires vendor context.',
						'dokan-blocks'
					) }
				/>
			) }
		</div>
	);
}

/**
 * Store location block save component.
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
