/**
 * Store hours block editor component.
 *
 * @package DokanBlocks
 * @since 1.0.0
 */

import { registerBlockType } from '@wordpress/blocks';
import { useBlockProps, InspectorControls } from '@wordpress/block-editor';
import { PanelBody, SelectControl, ToggleControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { useMemo } from '@wordpress/element';
import metadata from './block.json';
import './style.scss';

/**
 * Days of the week with translations.
 */
const DAYS = {
	monday: __( 'Monday', 'dokan-blocks' ),
	tuesday: __( 'Tuesday', 'dokan-blocks' ),
	wednesday: __( 'Wednesday', 'dokan-blocks' ),
	thursday: __( 'Thursday', 'dokan-blocks' ),
	friday: __( 'Friday', 'dokan-blocks' ),
	saturday: __( 'Saturday', 'dokan-blocks' ),
	sunday: __( 'Sunday', 'dokan-blocks' ),
};

/**
 * Mock store hours for preview.
 */
const MOCK_HOURS = {
	monday: { status: 'open', opening_time: '9:00 AM', closing_time: '6:00 PM' },
	tuesday: { status: 'open', opening_time: '9:00 AM', closing_time: '6:00 PM' },
	wednesday: { status: 'open', opening_time: '9:00 AM', closing_time: '6:00 PM' },
	thursday: { status: 'open', opening_time: '9:00 AM', closing_time: '6:00 PM' },
	friday: { status: 'open', opening_time: '9:00 AM', closing_time: '8:00 PM' },
	saturday: { status: 'open', opening_time: '10:00 AM', closing_time: '4:00 PM' },
	sunday: { status: 'close', opening_time: '', closing_time: '' },
};

/**
 * Get current day of the week.
 */
function getCurrentDay() {
	const days = [ 'sunday', 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday' ];
	return days[ new Date().getDay() ];
}

/**
 * Store hours block edit component.
 *
 * @param {Object} props Block props.
 * @param {Object} props.attributes Block attributes.
 * @param {Function} props.setAttributes Function to update attributes.
 * @param {Object} props.context Block context.
 * @return {JSX.Element} Block edit component.
 */
function Edit( { attributes, setAttributes, context } ) {
	const { layout = 'compact', showCurrentStatus = true } = attributes;
	const vendor = context['dokan/vendor'] || {};
	
	const blockProps = useBlockProps( {
		className: `dokan-vendor-store-hours dokan-vendor-store-hours-${ layout }`,
	} );

	// Get store hours from vendor data or use mock data for preview
	const storeHours = useMemo( () => {
		if ( vendor.store_info?.dokan_store_time ) {
			return vendor.store_info.dokan_store_time;
		}
		return MOCK_HOURS;
	}, [ vendor.store_info ] );

	// Check if store time is enabled (default to true for preview)
	const storeTimeEnabled = vendor.store_info?.dokan_store_time_enabled !== 'no';

	const currentDay = getCurrentDay();
	const todaySchedule = storeHours[ currentDay ] || {};
	const isOpen = todaySchedule.status === 'open' && todaySchedule.opening_time && todaySchedule.closing_time;

	// Get custom open/close notices
	const openNotice = vendor.store_info?.dokan_store_open_notice || __( 'Store Open', 'dokan-blocks' );
	const closedNotice = vendor.store_info?.dokan_store_close_notice || __( 'Store Closed', 'dokan-blocks' );

	return (
		<>
			<InspectorControls>
				<PanelBody title={ __( 'Settings', 'dokan-blocks' ) } initialOpen={ true }>
					<SelectControl
						label={ __( 'Layout', 'dokan-blocks' ) }
						value={ layout }
						options={ [
							{ label: __( 'Compact (Today only)', 'dokan-blocks' ), value: 'compact' },
							{ label: __( 'Detailed (Full week)', 'dokan-blocks' ), value: 'detailed' },
						] }
						onChange={ ( value ) => setAttributes( { layout: value } ) }
					/>
					<ToggleControl
						label={ __( 'Show Current Status', 'dokan-blocks' ) }
						help={ __( 'Display open/closed status indicator.', 'dokan-blocks' ) }
						checked={ showCurrentStatus }
						onChange={ ( value ) => setAttributes( { showCurrentStatus: value } ) }
					/>
				</PanelBody>
			</InspectorControls>

			<div { ...blockProps }>
				{ ! storeTimeEnabled ? (
					<div className="dokan-vendor-store-hours-disabled">
						<span className="dashicons dashicons-clock"></span>
						{ __( 'Store hours not configured', 'dokan-blocks' ) }
					</div>
				) : (
					<>
						{ showCurrentStatus && (
							<div className="dokan-vendor-store-hours-status">
								{ isOpen ? (
									<span className="store-open">
										<span className="dashicons dashicons-yes-alt"></span>
										{ openNotice }
									</span>
								) : (
									<span className="store-closed">
										<span className="dashicons dashicons-dismiss"></span>
										{ closedNotice }
									</span>
								) }
							</div>
						) }

						{ layout === 'detailed' ? (
							<div className="dokan-vendor-store-hours-details">
								<h4>{ __( 'Weekly Store Timing', 'dokan-blocks' ) }</h4>
								<ul className="dokan-vendor-store-hours-list">
									{ Object.entries( DAYS ).map( ( [ dayKey, dayLabel ] ) => {
										const schedule = storeHours[ dayKey ] || {};
										const dayIsOpen = schedule.status === 'open';
										const hasTime = schedule.opening_time && schedule.closing_time;
										const isToday = currentDay === dayKey;

										return (
											<li 
												key={ dayKey } 
												className={ `dokan-vendor-store-hours-day ${ isToday ? 'today' : '' }` }
											>
												<span className="day-name">{ dayLabel }</span>
												<span className="day-hours">
													{ dayIsOpen && hasTime ? (
														<span className="open">
															{ schedule.opening_time } - { schedule.closing_time }
														</span>
													) : (
														<span className="closed">
															{ __( 'CLOSED', 'dokan-blocks' ) }
														</span>
													) }
												</span>
											</li>
										);
									} ) }
								</ul>
							</div>
						) : (
							<div className="dokan-vendor-store-hours-compact">
								<span className="dashicons dashicons-clock"></span>
								{ isOpen ? (
									<span>
										{ __( 'Today:', 'dokan-blocks' ) } { todaySchedule.opening_time } - { todaySchedule.closing_time }
									</span>
								) : (
									<span>{ __( 'Closed today', 'dokan-blocks' ) }</span>
								) }
							</div>
						) }
					</>
				) }
			</div>
		</>
	);
}

/**
 * Store hours block save component.
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
