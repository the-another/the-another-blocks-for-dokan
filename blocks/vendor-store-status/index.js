/**
 * Store status block editor component.
 *
 * @package DokanBlocks
 * @since 1.0.0
 */

import { registerBlockType } from '@wordpress/blocks';
import { useBlockProps } from '@wordpress/block-editor';
import { __ } from '@wordpress/i18n';
import metadata from './block.json';
import './style.scss';

/**
 * Store status block edit component.
 *
 * @param {Object} props Block props.
 * @param {Object} props.context Block context.
 * @return {JSX.Element} Block edit component.
 */
function Edit( { context } ) {
	const vendor = context['dokan/vendor'] || {};

	const storeOpenClose = vendor.store_open_close || {};
	// Default to enabled for preview if no vendor data
	const hasVendorData = Object.keys( vendor ).length > 0 && vendor.id;
	const isEnabled = hasVendorData ? ( storeOpenClose.enabled || false ) : true;

	// For the editor preview, we'll show the open notice as a default preview
	// The actual open/closed status would be calculated server-side based on time
	const openNotice = storeOpenClose.open_notice || __( 'Store Open', 'dokan-blocks' );

	const blockProps = useBlockProps( {
		className: 'dokan-vendor-store-status',
	} );

	if ( ! isEnabled ) {
		return (
			<div { ...blockProps }>
				<span className="dokan-vendor-store-status-disabled">
					{ __( 'Store hours not configured', 'dokan-blocks' ) }
				</span>
			</div>
		);
	}

	return (
		<div { ...blockProps }>
			<span className="dokan-vendor-store-status-badge dokan-vendor-store-status-open">
				<span className="dashicons dashicons-yes-alt"></span>
				{ openNotice }
			</span>
		</div>
	);
}

/**
 * Store status block save component.
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
