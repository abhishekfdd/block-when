/**
 * RenderWhen — device-type condition (editor).
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
				label={ __( 'Desktop', 'renderwhen' ) }
				checked={ devices.includes( 'desktop' ) }
				onChange={ toggle( 'desktop' ) }
				__nextHasNoMarginBottom
			/>
			<CheckboxControl
				label={ __( 'Tablet', 'renderwhen' ) }
				checked={ devices.includes( 'tablet' ) }
				onChange={ toggle( 'tablet' ) }
				__nextHasNoMarginBottom
			/>
			<CheckboxControl
				label={ __( 'Mobile', 'renderwhen' ) }
				checked={ devices.includes( 'mobile' ) }
				onChange={ toggle( 'mobile' ) }
				__nextHasNoMarginBottom
			/>
		</>
	);
}

/**
 * Editor-side mirror of `Device_Condition::evaluate()`.
 *
 * Empty `devices` is "no constraint" — matches PHP, which returns true
 * before consulting the detected device. Otherwise the previewed device
 * must appear in the configured list.
 *
 * @param {Object} settings       Persisted settings, `{ devices: string[] }`.
 * @param {Object} previewContext Simulated audience. `{ loggedIn: boolean, role: string|null, device: string }`.
 * @return {boolean} True if the block should render for this audience.
 */
export function evaluate( settings, previewContext ) {
	const devices =
		settings && Array.isArray( settings.devices ) ? settings.devices : [];
	if ( devices.length === 0 ) {
		return true;
	}
	const device = previewContext && previewContext.device;
	return devices.includes( device );
}

registerCondition( {
	id: 'device',
	label: __( 'Device type', 'renderwhen' ),
	SettingsComponent: DeviceSettings,
	defaultSettings: () => ( { devices: [ 'desktop', 'tablet', 'mobile' ] } ),
	evaluate,
} );
