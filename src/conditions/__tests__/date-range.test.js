/**
 * Tests for the DateRangeSettings render and write decisions.
 *
 * Mirrors the pattern in user-state.test.js: mocks are spies on the
 * `@wordpress/components` exports, the WP packages are virtualised
 * because they're build-time externals, and rendering goes through
 * `react-dom/server.renderToStaticMarkup`.
 */

const mockCheckboxControl = jest.fn( () => null );
const mockDateTimePicker = jest.fn( () => null );

jest.mock(
	'@wordpress/components',
	() => ( {
		CheckboxControl: ( props ) => mockCheckboxControl( props ),
		DateTimePicker: ( props ) => mockDateTimePicker( props ),
	} ),
	{ virtual: true }
);

jest.mock( '@wordpress/i18n', () => ( { __: ( s ) => s } ), {
	virtual: true,
} );

jest.mock( '../../store/conditions-registry', () => ( {
	registerCondition: jest.fn(),
} ) );

if ( typeof global.TextEncoder === 'undefined' ) {
	// eslint-disable-next-line @typescript-eslint/no-var-requires
	const util = require( 'util' );
	global.TextEncoder = util.TextEncoder;
	global.TextDecoder = util.TextDecoder;
}

const React = require( 'react' );
const { renderToStaticMarkup } = require( 'react-dom/server' );
const { DateRangeSettings } = require( '../date-range' );

const findCallByLabel = ( spy, label ) =>
	spy.mock.calls.find( ( [ props ] ) => props.label === label );

describe( 'DateRangeSettings', () => {
	let onChange;

	beforeEach( () => {
		mockCheckboxControl.mockClear();
		mockDateTimePicker.mockClear();
		onChange = jest.fn();
	} );

	const render = ( settings ) =>
		renderToStaticMarkup(
			React.createElement( DateRangeSettings, { settings, onChange } )
		);

	it( 'renders no DateTimePicker when both bounds are null', () => {
		render( { start: null, end: null } );
		expect( mockDateTimePicker ).not.toHaveBeenCalled();
	} );

	it( 'renders the start DateTimePicker when settings.start is set', () => {
		render( { start: '2026-01-01 00:00:00', end: null } );

		expect( mockDateTimePicker ).toHaveBeenCalledTimes( 1 );

		const startCheckbox = findCallByLabel(
			mockCheckboxControl,
			'Has start date'
		);
		expect( startCheckbox[ 0 ].checked ).toBe( true );

		// The picker is fed the ISO form so its UI renders correctly,
		// even though the persisted shape is `Y-m-d H:i:s`.
		expect( mockDateTimePicker.mock.calls[ 0 ][ 0 ].currentDate ).toBe(
			'2026-01-01T00:00:00'
		);
	} );

	it( 'writes the new bound back in Y-m-d H:i:s format (not ISO)', () => {
		render( { start: '2026-01-01 00:00:00', end: null } );

		// Drive the picker's onChange directly with the ISO it would emit.
		mockDateTimePicker.mock.calls[ 0 ][ 0 ].onChange(
			'2026-05-10T14:30:00'
		);

		expect( onChange ).toHaveBeenCalledWith( {
			start: '2026-05-10 14:30:00',
			end: null,
		} );
	} );

	it( 'unchecks the start checkbox and hides the picker when start is null', () => {
		render( { start: null, end: null } );

		const startCheckbox = findCallByLabel(
			mockCheckboxControl,
			'Has start date'
		);
		const endCheckbox = findCallByLabel(
			mockCheckboxControl,
			'Has end date'
		);

		expect( startCheckbox[ 0 ].checked ).toBe( false );
		expect( endCheckbox[ 0 ].checked ).toBe( false );
	} );
} );
