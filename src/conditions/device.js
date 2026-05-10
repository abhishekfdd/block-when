/**
 * Block When — device-type condition (editor).
 *
 * Editor-side counterpart of `Device_Condition`. Three CheckboxControls
 * — Desktop, Tablet, Mobile — drive the persisted settings shape:
 *
 *   { devices: ('desktop' | 'tablet' | 'mobile')[] }
 *
 * matching the PHP-side contract documented in
 * {@link ../../includes/conditions/class-device-condition.php}. An empty
 * array means "always visible," which the default settings deliberately
 * approximate by checking all three boxes — a sensible no-op starting
 * point that turns into a real constraint as soon as the user unticks
 * one.
 *
 * TODO(Phase 4): expose an `evaluate( settings )` so the editor's
 * "preview as device" toggle can drive a live show/hide preview.
 * Until then, server-side evaluation is the only source of truth.
 */

import { CheckboxControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

import { registerCondition } from '../store/conditions-registry';

/**
 * Canonical order for the persisted `devices` array. Toggling a device
 * always rewrites the array through this filter, so the stored order
 * is stable regardless of the click sequence — the PHP evaluator does
 * not care, but a deterministic shape keeps diffs and tests readable.
 */
const DEVICES_ORDER = [ 'desktop', 'tablet', 'mobile' ];

/**
 * Settings UI for the Device condition.
 *
 * @param {Object}   props
 * @param {Object}   [props.settings] Current settings, or undefined on first render.
 * @param {Function} props.onChange   Called with the next settings object.
 * @return {JSX.Element} Settings UI.
 */
export function DeviceSettings( { settings, onChange } ) {
	const devices =
		settings && Array.isArray( settings.devices ) ? settings.devices : [];

	const toggle = ( device ) => ( checked ) => {
		const set = new Set( devices );
		if ( checked ) {
			set.add( device );
		} else {
			set.delete( device );
		}
		onChange( { devices: DEVICES_ORDER.filter( ( d ) => set.has( d ) ) } );
	};

	return (
		<>
			<CheckboxControl
				label={ __( 'Desktop', 'block-when' ) }
				checked={ devices.includes( 'desktop' ) }
				onChange={ toggle( 'desktop' ) }
				__nextHasNoMarginBottom
			/>
			<CheckboxControl
				label={ __( 'Tablet', 'block-when' ) }
				checked={ devices.includes( 'tablet' ) }
				onChange={ toggle( 'tablet' ) }
				__nextHasNoMarginBottom
			/>
			<CheckboxControl
				label={ __( 'Mobile', 'block-when' ) }
				checked={ devices.includes( 'mobile' ) }
				onChange={ toggle( 'mobile' ) }
				__nextHasNoMarginBottom
			/>
		</>
	);
}

registerCondition( {
	id: 'device',
	label: __( 'Device type', 'block-when' ),
	SettingsComponent: DeviceSettings,
	defaultSettings: () => ( { devices: [ 'desktop', 'tablet', 'mobile' ] } ),
} );
