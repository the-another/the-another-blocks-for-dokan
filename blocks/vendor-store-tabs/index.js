/**
 * Store tabs block editor component.
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
 * Sample tabs for editor preview.
 * These represent the default Dokan store tabs.
 */
const PREVIEW_TABS = [
	{
		key: 'products',
		title: __( 'Products', 'dokan-blocks' ),
		active: true,
	},
	{
		key: 'terms_and_conditions',
		title: __( 'Terms and Conditions', 'dokan-blocks' ),
		active: false,
	},
];

/**
 * Store tabs block edit component.
 *
 * @return {JSX.Element} Block edit component.
 */
function Edit() {
	const blockProps = useBlockProps( {
		className: 'dokan-vendor-store-tabs',
	} );

	return (
		<div { ...blockProps }>
			<ul className="dokan-list-inline dokan-vendor-store-tabs-list" role="tablist">
				{ PREVIEW_TABS.map( ( tab ) => (
					<li
						key={ tab.key }
						className={ `dokan-store-tab-item${ tab.active ? ' active' : '' }` }
						role="presentation"
					>
						<a
							href="#"
							role="tab"
							aria-selected={ tab.active }
							onClick={ ( e ) => e.preventDefault() }
						>
							{ tab.title }
						</a>
					</li>
				) ) }
			</ul>
			<p className="dokan-vendor-store-tabs-editor-note">
				{ __( 'Tabs from Dokan Pro or extensions will also appear here.', 'dokan-blocks' ) }
			</p>
		</div>
	);
}

/**
 * Store tabs block save component.
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
