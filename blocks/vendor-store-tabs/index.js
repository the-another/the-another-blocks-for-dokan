/**
 * Store tabs block editor component.
 *
 * @package
 * @since 1.0.0
 */

import { registerBlockType } from '@wordpress/blocks';
import { useBlockProps } from '@wordpress/block-editor';
import { Placeholder } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import metadata from './block.json';
import './style.scss';

/**
 * Default preview tabs when no vendor context is available.
 */
const DEFAULT_TABS = [
	{ key: 'products', title: __( 'Products', 'dokan-blocks' ), active: true },
	{
		key: 'terms_and_conditions',
		title: __( 'Terms and Conditions', 'dokan-blocks' ),
		active: false,
	},
];

/**
 * Store tabs block edit component.
 *
 * @param {Object} props         Block props.
 * @param {Object} props.context Block context.
 * @return {JSX.Element} Block edit component.
 */
function Edit( { context } ) {
	const blockProps = useBlockProps();

	const vendor = context[ 'dokan/vendor' ] || {};
	const hasVendor = !! vendor.id;

	// Use vendor tabs if available, otherwise use defaults.
	const tabs =
		hasVendor && vendor.store_tabs
			? Object.entries( vendor.store_tabs ).map(
					( [ key, tab ], index ) => ( {
						key,
						title: tab.title || key,
						active: index === 0,
					} )
			  )
			: DEFAULT_TABS;

	if ( ! tabs.length ) {
		return (
			<div { ...blockProps }>
				<Placeholder
					icon="menu"
					label={ __( 'Store Tabs', 'dokan-blocks' ) }
					instructions={ __(
						'Displays store navigation tabs. Requires vendor context.',
						'dokan-blocks'
					) }
				/>
			</div>
		);
	}

	return (
		<div { ...blockProps }>
			<ul
				className="dokan-list-inline dokan-vendor-store-tabs-list"
				role="tablist"
			>
				{ tabs.map( ( tab ) => (
					<li
						key={ tab.key }
						className={ `dokan-store-tab-item${
							tab.active ? ' active' : ''
						}` }
						role="presentation"
					>
						<button
							type="button"
							role="tab"
							aria-selected={ tab.active }
						>
							{ tab.title }
						</button>
					</li>
				) ) }
			</ul>
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

registerBlockType( metadata.name, {
	...metadata,
	edit: Edit,
	save: Save,
} );
