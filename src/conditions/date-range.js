/**
 * RenderWhen — date-range condition (editor).
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
 * and RenderWhen deliberately treats both as the same configured
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
				label={ __( 'Has start date', 'renderwhen' ) }
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
				label={ __( 'Has end date', 'renderwhen' ) }
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

/**
 * Parse a stored `Y-m-d H:i:s` bound into a local-time Date.
 *
 * The PHP evaluator interprets the same string in the site's configured
 * timezone via `wp_timezone()`. The editor preview deliberately treats
 * the wall-clock value as local time — the editor displays local time
 * and the user picks "the same configured moment" regardless of where
 * the request is served from. See the class docblock on
 * `Date_Range_Condition` for the policy.
 *
 * Returns null on malformed input so the caller can collapse the bound
 * to "open-ended on that side", matching PHP's bail-on-parse-failure
 * branch.
 *
 * @param {unknown} bound Stored bound, e.g. "2026-05-10 14:30:00".
 * @return {Date|null} Parsed local-time Date, or null.
 */
function parseBound( bound ) {
	if ( typeof bound !== 'string' || bound === '' ) {
		return null;
	}
	const match = /^(\d{4})-(\d{2})-(\d{2}) (\d{2}):(\d{2}):(\d{2})$/.exec(
		bound
	);
	if ( ! match ) {
		return null;
	}
	const [ , y, mo, d, h, mi, s ] = match;
	const date = new Date(
		Number( y ),
		Number( mo ) - 1,
		Number( d ),
		Number( h ),
		Number( mi ),
		Number( s )
	);
	return Number.isNaN( date.getTime() ) ? null : date;
}

/**
 * Editor-side mirror of `Date_Range_Condition::evaluate()`.
 *
 * Returns true when `previewContext.now` is inside the inclusive
 * `[start, end]` window. Either bound may be null/empty/unparseable,
 * which is treated as open-ended on that side (the PHP `is_bound` plus
 * parse-failure branches collapsed into one `parseBound` helper here).
 * Both bounds open => always visible. The comparison uses JS Date
 * ordering, which is total over valid Dates.
 *
 * @param {Object} settings       Persisted settings, `{ start, end }`.
 * @param {Object} previewContext Simulated audience. `{ loggedIn, role, device, now: Date|undefined }`; `now` defaults to current time.
 * @return {boolean} True if the block should render for this audience.
 */
export function evaluate( settings, previewContext ) {
	const start = parseBound( settings && settings.start );
	const end = parseBound( settings && settings.end );

	if ( start === null && end === null ) {
		return true;
	}

	const now =
		previewContext && previewContext.now instanceof Date
			? previewContext.now
			: new Date();

	if ( start !== null && now < start ) {
		return false;
	}
	if ( end !== null && now > end ) {
		return false;
	}
	return true;
}

registerCondition( {
	id: 'date_range',
	label: __( 'Date range', 'renderwhen' ),
	SettingsComponent: DateRangeSettings,
	defaultSettings: () => ( { start: null, end: null } ),
	evaluate,
} );
