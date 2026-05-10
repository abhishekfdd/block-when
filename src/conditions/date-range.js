/**
 * Block When — date-range condition (editor).
 *
 * Editor-side counterpart of `Date_Range_Condition`. Provides two
 * optional bounds (start, end) via a CheckboxControl + DateTimePicker
 * pair each. The settings shape is:
 *
 *   { start: "Y-m-d H:i:s" | null, end: "Y-m-d H:i:s" | null }
 *
 * matching the PHP-side contract documented in
 * {@link ../../includes/conditions/class-date-range-condition.php}.
 *
 * The `Y-m-d H:i:s` format (no `T` separator, no zone suffix) is what
 * `Date_Range_Condition::BOUND_FORMAT` expects when it parses the
 * stored bound with `DateTimeImmutable::createFromFormat`. The editor's
 * DateTimePicker, however, emits ISO-8601 strings like
 * `2026-05-10T14:30:00`. The two helpers at the top of this file are
 * the entire bridge between those formats — they're tiny on purpose so
 * the contract is easy to audit alongside the PHP class.
 *
 * TODO(Phase 4): add an `evaluate( settings )` export so the editor
 * can preview visibility against the picked window without a round-trip
 * to PHP. Until then, server-side evaluation is the only source of
 * truth for whether a block actually renders.
 */

import { CheckboxControl, DateTimePicker } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

import { registerCondition } from '../store/conditions-registry';

/**
 * Convert a `Y-m-d H:i:s` bound to the ISO-8601-ish string the
 * DateTimePicker uses for `currentDate`. Returns null for null/empty
 * input so the picker shows its own default rather than crashing.
 *
 * @param {string|null} db Stored bound.
 * @return {string|null} ISO string, or null.
 */
function dbToIso( db ) {
	if ( typeof db !== 'string' || db === '' ) {
		return null;
	}
	return db.replace( ' ', 'T' );
}

/**
 * Convert the ISO-8601 string DateTimePicker emits back into the
 * `Y-m-d H:i:s` shape PHP expects. Trailing fractional seconds or a
 * `Z` suffix (some pickers add them) are dropped by the slice.
 *
 * @param {string|null} iso ISO-formatted string.
 * @return {string|null} Stored bound shape, or null.
 */
function isoToDb( iso ) {
	if ( typeof iso !== 'string' || iso === '' ) {
		return null;
	}
	return iso.replace( 'T', ' ' ).slice( 0, 19 );
}

/**
 * Snapshot the user's wall-clock "now" in the stored format. Used as
 * the seed value when a bound checkbox is first enabled — picking
 * "right now" is the most common starting point and saves a click.
 * Local-time is correct here: the editor displays local wall-clock,
 * the PHP evaluator interprets the same wall-clock in site timezone,
 * and Block When deliberately treats both as the same configured
 * moment (see Date_Range_Condition's class docblock).
 *
 * @return {string} Y-m-d H:i:s.
 */
function nowAsDb() {
	const d = new Date();
	const pad = ( n ) => String( n ).padStart( 2, '0' );
	return (
		`${ d.getFullYear() }-${ pad( d.getMonth() + 1 ) }-${ pad(
			d.getDate()
		) } ` +
		`${ pad( d.getHours() ) }:${ pad( d.getMinutes() ) }:${ pad(
			d.getSeconds()
		) }`
	);
}

/**
 * Coerce a stored bound to a string-or-null. Empty strings are treated
 * as "no bound" to match `Date_Range_Condition::is_bound()`, so a saved
 * `""` doesn't render as a checked-but-broken checkbox in the editor.
 *
 * @param {unknown} value Raw value from settings.
 * @return {string|null} Normalised bound.
 */
function normaliseBound( value ) {
	return typeof value === 'string' && value !== '' ? value : null;
}

/**
 * Settings UI for the Date Range condition.
 *
 * @param {Object}   props
 * @param {Object}   [props.settings] Current settings, or undefined on first render.
 * @param {Function} props.onChange   Called with the next settings object.
 * @return {JSX.Element} Settings UI.
 */
export function DateRangeSettings( { settings, onChange } ) {
	const start = normaliseBound( settings && settings.start );
	const end = normaliseBound( settings && settings.end );

	const setStart = ( nextStart ) => onChange( { start: nextStart, end } );
	const setEnd = ( nextEnd ) => onChange( { start, end: nextEnd } );

	const toggleStart = ( checked ) => setStart( checked ? nowAsDb() : null );
	const toggleEnd = ( checked ) => setEnd( checked ? nowAsDb() : null );

	const handleStartPick = ( iso ) => setStart( isoToDb( iso ) );
	const handleEndPick = ( iso ) => setEnd( isoToDb( iso ) );

	return (
		<>
			<CheckboxControl
				label={ __( 'Has start date', 'block-when' ) }
				checked={ start !== null }
				onChange={ toggleStart }
				__nextHasNoMarginBottom
			/>
			{ start !== null && (
				<DateTimePicker
					currentDate={ dbToIso( start ) }
					onChange={ handleStartPick }
				/>
			) }
			<CheckboxControl
				label={ __( 'Has end date', 'block-when' ) }
				checked={ end !== null }
				onChange={ toggleEnd }
				__nextHasNoMarginBottom
			/>
			{ end !== null && (
				<DateTimePicker
					currentDate={ dbToIso( end ) }
					onChange={ handleEndPick }
				/>
			) }
		</>
	);
}

registerCondition( {
	id: 'date_range',
	label: __( 'Date range', 'block-when' ),
	SettingsComponent: DateRangeSettings,
	defaultSettings: () => ( { start: null, end: null } ),
} );
